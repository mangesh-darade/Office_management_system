#!/usr/bin/env python3
"""
Deep verification for rewards automation — rules, schema, hooks, idempotency logic.
Run: python tools/test_rewards_automation.py
"""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "application"

FAILURES: list[str] = []
WARNINGS: list[str] = []
PASSED: list[str] = []


def ok(msg: str) -> None:
    PASSED.append(msg)


def warn(msg: str) -> None:
    WARNINGS.append(msg)


def fail(msg: str) -> None:
    FAILURES.append(msg)


def read(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="replace")


# --- Expected trigger hooks in controllers ---
EXPECTED_HOOKS = {
    "Attendance.php": ["attendance_checkin", "attendance_checkout", "rewards_automation_after_checkin"],
    "Daily_activity.php": ["daily_activity_logged"],
    "Projects.php": ["project_status_update"],
    "Rewards.php": ["peer_cheer_received", "peer_cheer_sent", "reward_engine_claim", "office_closing_checklist"],
    "Releases.php": ["release_completed"],
    "Training_lms.php": ["lms_topic_completed"],
    "Certifications.php": ["certification_approved"],
}

ACTIVE_RULE_CODES = None


def load_rule_codes_from_schema() -> set[str]:
    text = read(APP / "helpers" / "rewards_schema_helper.php")
    codes = set(re.findall(r"'code'\s*=>\s*'([a-z0-9_]+)'", text))
    return codes


def check_controller_hooks() -> None:
    for file, needles in EXPECTED_HOOKS.items():
        path = APP / "controllers" / file
        if not path.exists():
            fail(f"Missing controller: {file}")
            continue
        content = read(path)
        for needle in needles:
            if needle not in content:
                fail(f"{file}: missing hook/reference `{needle}`")
            else:
                ok(f"{file}: has `{needle}`")


def check_race_checkin_rewards() -> None:
    content = read(APP / "controllers" / "Attendance.php")
    race_block = content.split("if ($existing_final)")[1].split("} else {")[0] if "if ($existing_final)" in content else ""
    if "rewards_after_checkin" not in race_block:
        fail("Attendance.php race check-in path missing rewards_after_checkin")
    else:
        ok("Attendance.php race check-in path calls rewards_after_checkin")
    dup_block = content.split("existing_after->id)->update")[1][:600] if "existing_after->id)->update" in content else ""
    if "rewards_after_checkin" not in dup_block:
        fail("Attendance.php duplicate-key check-in path missing rewards_after_checkin")
    else:
        ok("Attendance.php duplicate-key check-in path calls rewards_after_checkin")


def check_routes() -> None:
    routes = read(ROOT / "application" / "config" / "routes.php")
    needed = [
        "rewards/submit-claim",
        "rewards/approvals",
        "rewards/office-closing",
        "cron/rewards_attendance_penalties",
    ]
    for r in needed:
        if r not in routes and f"'{r}'" not in routes:
            # cron is method not route - check controller
            if r.startswith("cron/"):
                if "rewards_attendance_penalties" not in routes and "rewards_attendance_penalties" not in read(APP / "controllers" / "Cron.php"):
                    fail(f"Cron missing rewards_attendance_penalties method")
                else:
                    ok("Cron.php has rewards_attendance_penalties")
                continue
            fail(f"routes.php missing `{r}`")
        else:
            ok(f"routes.php has `{r}`")


def check_idempotency_uses_occurred_at() -> None:
    engine = read(APP / "libraries" / "Reward_engine.php")
    if "occurred_at" in engine and "build_idempotency_key" in engine:
        fn = engine.split("function build_idempotency_key")[1].split("}", 1)[0]
        if "occurred_at" in fn:
            ok("Reward_engine idempotency uses occurred_at")
        else:
            fail("Reward_engine build_idempotency_key ignores occurred_at — duplicate penalties possible")
    else:
        fail("Reward_engine idempotency function not found")


STALE_HOOKS = {
    "Tasks.php": ("reward_engine_claim", "delivery_before_deadline"),
    "Defects.php": ("reward_engine_claim", "critical_production_issue"),
    "Customer_feedback.php": ("reward_engine_claim", "exceptional_customer_feedback"),
    "Knowledge_base.php": ("reward_engine_claim", "company_blog_linkedin"),
    "Helpdesk.php": None,  # intentionally no auto reward on ticket resolve
}


def check_stale_hooks() -> None:
    """Hooks should use reward_engine_claim mapped to current rules."""
    for ctrl, mapping in STALE_HOOKS.items():
        path = APP / "controllers" / ctrl
        if not path.exists():
            continue
        content = read(path)
        if mapping is None:
            if "reward_engine_dispatch" in content and "ticket_resolved" in content:
                fail(f"{ctrl} still dispatches obsolete ticket_resolved")
            else:
                ok(f"{ctrl}: no obsolete ticket_resolved dispatch")
            continue
        fn, claim = mapping
        if fn not in content or claim not in content:
            fail(f"{ctrl}: expected {fn}('{claim}')")
        else:
            ok(f"{ctrl}: wired to {claim}")
        if "reward_engine_dispatch('task_completed'" in content or "defect_resolved" in content or "feedback_submitted" in content or "kb_article_published" in content:
            fail(f"{ctrl}: still contains obsolete reward_engine_dispatch event")


def check_consistency_no_checkin_guard() -> None:
    helper = read(APP / "helpers" / "rewards_automation_helper.php")
    if "daysWithCheckin" in helper and "daysWithCheckin > 0 && $missedCheckoutDays === 0" in helper.replace(" ", ""):
        ok("Consistency no_missed_checkout requires at least one check-in")
    elif "daysWithCheckin > 0 && $missedCheckoutDays === 0" in helper:
        ok("Consistency no_missed_checkout requires at least one check-in")
    else:
        fail("Consistency no_missed_checkout may award users absent all month — add daysWithCheckin guard")


def check_leave_excuse_helper() -> None:
    helper = read(APP / "helpers" / "rewards_automation_helper.php")
    if "rewards_automation_user_excused_for_date" in helper and "leave_requests" in helper:
        ok("Leave/WFH excuse helper for attendance penalties")
    else:
        fail("Missing rewards_automation_user_excused_for_date")
    if "rewards_automation_user_excused_for_date($db, $uid, $d)" in helper:
        ok("Monthly consistency skips excused leave/WFH days")
    else:
        fail("Monthly consistency should skip excused days for missed checkout")


def check_helper_loads_attendance_punch() -> None:
    helper = read(APP / "helpers" / "rewards_automation_helper.php")
    if "attendance_punch" in helper and ("load->helper" in helper or "attendance_punch_has_column" in helper):
        if "load->helper(array('rewards', 'attendance_punch'" in helper or "load->helper(array('rewards', 'schema_columns', 'attendance_punch'" in helper:
            ok("rewards_automation loads attendance_punch where needed")
        elif "function rewards_automation_attendance_meta" in helper:
            if "rewards_automation_daily_attendance_penalties" in helper and "attendance_punch" not in helper.split("rewards_automation_daily_attendance_penalties")[1][:400]:
                fail("daily_attendance_penalties may call attendance_punch without loading helper")
            else:
                warn("rewards_automation_attendance_meta relies on caller to load attendance_punch")


def check_mysql_schema() -> None:
    try:
        import mysql.connector  # type: ignore
    except ImportError:
        warn("mysql-connector not installed — skipping DB checks")
        return

    sys.path.insert(0, str(ROOT / "tools"))
    cfg_path = APP / "config" / "database.php"
    text = read(cfg_path)
    def grab(key: str) -> str:
        m = re.search(rf"'\{key}'\s*=>\s*'([^']*)'", text)
        return m.group(1) if m else ""

    host = grab("hostname") or "127.0.0.1"
    user = grab("username") or "root"
    password = grab("password")
    database = grab("database")
    if not database:
        warn("Could not parse database.php — skipping DB checks")
        return

    try:
        conn = mysql.connector.connect(host=host, user=user, password=password, database=database)
    except Exception as e:
        warn(f"DB connect failed ({e}) — skipping live DB checks")
        return

    cur = conn.cursor(dictionary=True)
    tables = [
        "reward_rules",
        "reward_transactions",
        "reward_approval_queue",
        "office_closing_submissions",
        "meal_mantri_assignments",
    ]
    for t in tables:
        cur.execute("SHOW TABLES LIKE %s", (t,))
        if cur.fetchone():
            ok(f"DB table exists: {t}")
        else:
            fail(f"DB table missing: {t} (visit /rewards once to run schema seed)")

    cur.execute("SELECT COUNT(*) AS c FROM reward_rules WHERE is_active=1")
    row = cur.fetchone()
    active_count = int(row["c"]) if row else 0
    if active_count >= 40:
        ok(f"DB has {active_count} active reward rules")
    else:
        warn(f"Only {active_count} active rules in DB — seed may not have run")

    cur.execute(
        "SELECT code, trigger_event, points, requires_approval FROM reward_rules WHERE is_active=1 AND code IN (%s)"
        % ",".join(["%s"] * 5),
        ("self_work_update_submitted", "checkout_submitted", "on_or_before_time", "major_release", "cheer_received"),
    )
    samples = {r["code"]: r for r in cur.fetchall()}
    expected_pts = {
        "self_work_update_submitted": 20,
        "checkout_submitted": 10,
        "on_or_before_time": 10,
        "major_release": 100,
        "cheer_received": 20,
    }
    for code, pts in expected_pts.items():
        if code not in samples:
            fail(f"DB missing active rule: {code}")
        elif float(samples[code]["points"]) != pts:
            fail(f"Rule {code} points={samples[code]['points']} expected {pts}")
        else:
            ok(f"DB rule {code} = {pts} pts")

    conn.close()


def main() -> int:
    print("=== Rewards automation deep check ===\n")
    check_controller_hooks()
    check_race_checkin_rewards()
    check_routes()
    check_idempotency_uses_occurred_at()
    check_stale_hooks()
    check_consistency_no_checkin_guard()
    check_leave_excuse_helper()
    check_helper_loads_attendance_punch()
    check_mysql_schema()

    print(f"\nPASSED ({len(PASSED)}):")
    for p in PASSED:
        print(f"  [OK] {p}")
    if WARNINGS:
        print(f"\nWARNINGS ({len(WARNINGS)}):")
        for w in WARNINGS:
            print(f"  [WARN] {w}")
    if FAILURES:
        print(f"\nFAILURES ({len(FAILURES)}):")
        for f in FAILURES:
            print(f"  [FAIL] {f}")
        print("\nResult: FAILED")
        return 1
    print("\nResult: OK (with warnings)" if WARNINGS else "\nResult: ALL PASSED")
    return 0


if __name__ == "__main__":
    sys.exit(main())
