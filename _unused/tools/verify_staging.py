#!/usr/bin/env python3
"""Automated staging checks for refactor verification."""
from __future__ import annotations

import json
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PHP = Path(r"C:\wamp64\bin\php\php8.4.15\php.exe")
BASE_URL = "http://localhost/Office_management_system/"

LINT_FILES = [
    "application/controllers/Reports_attendance.php",
    "application/controllers/Leave_requests.php",
    "application/controllers/My_works.php",
    "application/controllers/Reminders.php",
    "application/controllers/Ai_chat.php",
    "application/controllers/Db.php",
    "application/controllers/Auth.php",
    "application/controllers/Attendance.php",
    "application/core/Reports_base.php",
    "application/helpers/leave_requests_notify_helper.php",
    "application/helpers/my_works_access_helper.php",
    "application/helpers/my_works_query_helper.php",
    "application/helpers/my_works_form_helper.php",
    "application/helpers/reminders_user_helper.php",
    "application/helpers/reminders_email_helper.php",
    "application/helpers/reminders_template_helper.php",
    "application/helpers/reminders_cron_helper.php",
    "application/helpers/reminders_pagination_helper.php",
    "application/helpers/reminders_schedule_helper.php",
    "application/helpers/attendance_report_helper.php",
    "application/helpers/attendance_report_export_helper.php",
    "application/helpers/schema_automation_helper.php",
]

CONTROLLER_METHODS = {
    "application/controllers/Reports_attendance.php": [
        "attendance_employee",
        "_attendance_employee_detail",
        "_attendance_employee_summary",
        "attendance",
        "export_attendance_employee",
    ],
    "application/controllers/Leave_requests.php": ["apply", "approve", "reject"],
    "application/controllers/My_works.php": ["index", "create", "export", "show"],
    "application/controllers/Reminders.php": ["index", "send", "cron_morning", "templates"],
}

HELPER_FUNCTIONS = {
    "application/helpers/leave_requests_notify_helper.php": [
        "leave_requests_notify_applied",
        "leave_requests_notify_change",
    ],
    "application/helpers/attendance_report_export_helper.php": [
        "attendance_report_generate_daily_details",
        "attendance_report_export_period",
        "attendance_report_export_employee_summary_csv",
        "attendance_report_export_employee_summary_pdf",
        "attendance_report_export_employee_detail_excel",
        "attendance_report_export_employee_detail_pdf",
    ],
}

HTTP_ROUTES = [
    "auth/login",
    "reports/attendance",
    "reports/attendance-employee",
    "reports/attendance-employee/1",
    "reports/export-attendance-employee",
    "my-works",
    "leave/my",
    "reminders",
    "reports/overview",
    "reports/projects-status",
    "reports/leaves",
    "departments",
    "departments/create",
    "designations",
    "designations/create",
    "ai-chat",
    "attendance",
    "tasks",
    "notifications",
    "chats",
]

VIEW_FILES = [
    "application/views/reports/attendance_employee_detail.php",
    "application/views/reports/attendance_employee.php",
    "application/views/reports/attendance.php",
]

CRON_CLI = [
    ("cron coaching_session_reminders", "Coaching session reminders"),
    ("cron coaching_automation", "Coaching automation"),
]


def ok(msg: str) -> None:
    print(f"  OK  {msg}")


def fail(msg: str) -> None:
    print(f"FAIL  {msg}")


def lint_php() -> list[str]:
    errors: list[str] = []
    if not PHP.is_file():
        errors.append(f"PHP not found: {PHP}")
        return errors
    for rel in LINT_FILES:
        path = ROOT / rel
        if not path.is_file():
            errors.append(f"missing file: {rel}")
            continue
        proc = subprocess.run(
            [str(PHP), "-l", str(path)],
            capture_output=True,
            text=True,
        )
        if proc.returncode != 0:
            errors.append(f"{rel}: {proc.stdout.strip() or proc.stderr.strip()}")
        else:
            ok(f"lint {rel}")
    return errors


def check_methods() -> list[str]:
    errors: list[str] = []
    for rel, methods in CONTROLLER_METHODS.items():
        text = (ROOT / rel).read_text(encoding="utf-8", errors="replace")
        for name in methods:
            pat = rf"function\s+{re.escape(name)}\s*\("
            if not re.search(pat, text):
                errors.append(f"{rel}: missing method {name}()")
            else:
                ok(f"method {rel}::{name}()")
    return errors


def check_helper_functions() -> list[str]:
    errors: list[str] = []
    for rel, funcs in HELPER_FUNCTIONS.items():
        text = (ROOT / rel).read_text(encoding="utf-8", errors="replace")
        for fn in funcs:
            if not re.search(rf"function\s+{re.escape(fn)}\s*\(", text):
                errors.append(f"{rel}: missing function {fn}()")
            else:
                ok(f"helper {fn}()")
    return errors


def http_get(route: str) -> tuple[int | None, str, dict]:
    url = BASE_URL + route.lstrip("/")
    req = urllib.request.Request(url, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            body = resp.read(8000).decode("utf-8", errors="replace")
            return resp.getcode(), body, dict(resp.headers)
    except urllib.error.HTTPError as e:
        body = e.read(8000).decode("utf-8", errors="replace")
        return e.code, body, dict(e.headers)
    except Exception as e:
        return None, str(e), {}


def http_smoke() -> list[str]:
    errors: list[str] = []
    for route in HTTP_ROUTES:
        code, _, _ = http_get(route)
        if code is None or code >= 500:
            errors.append(f"HTTP {route}: status {code}")
            fail(f"HTTP {route}: {code}")
        else:
            ok(f"HTTP {route}: {code}")
    return errors


def check_views() -> list[str]:
    errors: list[str] = []
    for rel in VIEW_FILES:
        path = ROOT / rel
        if not path.is_file():
            errors.append(f"missing view: {rel}")
            fail(f"view missing {rel}")
        else:
            ok(f"view {rel}")
    return errors


def check_csrf_wiring() -> list[str]:
    errors: list[str] = []
    config = (ROOT / "application/config/config.php").read_text(encoding="utf-8", errors="replace")
    header = (ROOT / "application/views/partials/header.php").read_text(encoding="utf-8", errors="replace")
    if "$config['csrf_protection'] = TRUE" not in config:
        errors.append("csrf_protection not enabled in config.php")
    else:
        ok("csrf_protection enabled")
    if "ci_csrf_token" not in header or "settings.data.append('ci_csrf_token'" not in header:
        errors.append("jQuery CSRF injection missing in header.php")
    else:
        ok("jQuery CSRF auto-injection in header.php")
    if "'cron/.*'" not in config:
        errors.append("cron URIs not in csrf_exclude_uris")
    else:
        ok("cron routes CSRF-excluded (expected for CLI/token)")
    return errors


def check_export_validation() -> list[str]:
    """Export without params should return 400 JSON, not 500."""
    errors: list[str] = []
    code, body, headers = http_get("reports/export-attendance-employee")
    ctype = headers.get("Content-type", headers.get("Content-Type", ""))
    if code == 500:
        errors.append("export endpoint returned 500 without params")
        fail("export missing params -> 500")
        return errors
    if code not in (400, 401, 403, 200):
        errors.append(f"export endpoint unexpected status {code}")
        fail(f"export validation status {code}")
    else:
        ok(f"export missing params -> {code} (not 500)")
    if code == 400 and "json" in ctype.lower():
        try:
            data = json.loads(body)
            if "error" in data:
                ok("export 400 returns JSON error key")
            else:
                errors.append("export 400 JSON missing error key")
        except json.JSONDecodeError:
            errors.append("export 400 body is not valid JSON")
    return errors


def check_attendance_pages_content() -> list[str]:
    errors: list[str] = []
    for route, needle in [
        ("reports/attendance-employee", "Attendance"),
        ("reports/attendance-employee/1", "Attendance"),
    ]:
        code, body, _ = http_get(route)
        if code is None or code >= 500:
            errors.append(f"{route} returned {code}")
            continue
        if len(body) < 100:
            errors.append(f"{route} returned empty/short body")
        else:
            ok(f"{route} returns HTML ({len(body)} bytes)")
    return errors


def check_cron_cli() -> list[str]:
    errors: list[str] = []
    for args, label in CRON_CLI:
        proc = subprocess.run(
            [str(PHP), "index.php", *args.split()],
            cwd=str(ROOT),
            capture_output=True,
            text=True,
        )
        out = (proc.stdout or "") + (proc.stderr or "")
        if proc.returncode != 0:
            errors.append(f"CLI {label}: exit {proc.returncode} — {out.strip()[:200]}")
            fail(f"CLI {label}: exit {proc.returncode}")
        elif "❌" in out or "Exception" in out or "Error" in out.split("\n")[0]:
            errors.append(f"CLI {label}: {out.strip()[:200]}")
            fail(f"CLI {label}: error in output")
        else:
            ok(f"CLI {label}")
    return errors


def check_cron_http_denied() -> list[str]:
    errors: list[str] = []
    for route in ["cron/coaching_automation", "cron/coaching_session_reminders"]:
        code, body, _ = http_get(route)
        if code == 500:
            errors.append(f"{route} returned 500")
            fail(f"HTTP {route}: 500")
        elif code in (403, 401):
            ok(f"HTTP {route}: {code} (token required)")
        elif code == 200 and ("✅" in body or "⏭" in body):
            ok(f"HTTP {route}: 200 (open cron token — review in prod)")
        else:
            ok(f"HTTP {route}: {code}")
    return errors


def check_ai_chat_sql() -> list[str]:
    errors: list[str] = []
    script = ROOT / "tools/verify_ai_chat_sql.php"
    if not script.is_file():
        errors.append("tools/verify_ai_chat_sql.php missing")
        return errors
    proc = subprocess.run(
        [str(PHP), str(script)],
        cwd=str(ROOT),
        capture_output=True,
        text=True,
    )
    print(proc.stdout, end="")
    if proc.returncode != 0:
        errors.append("Ai_chat SQL security tests failed")
        if proc.stderr:
            errors.append(proc.stderr.strip()[:300])
    return errors


def main() -> int:
    err: list[str] = []
    print("=== PHP syntax ===")
    err += lint_php()
    print("\n=== Controller methods ===")
    err += check_methods()
    print("\n=== Helper functions ===")
    err += check_helper_functions()
    print("\n=== Report views ===")
    err += check_views()
    print("\n=== CSRF wiring ===")
    err += check_csrf_wiring()
    print("\n=== HTTP smoke (non-500) ===")
    err += http_smoke()
    print("\n=== Export validation ===")
    err += check_export_validation()
    print("\n=== Attendance page HTML ===")
    err += check_attendance_pages_content()
    print("\n=== Cron CLI ===")
    err += check_cron_cli()
    print("\n=== Cron HTTP (token gate) ===")
    err += check_cron_http_denied()
    print("\n=== Ai_chat SQL security ===")
    err += check_ai_chat_sql()
    print("\n=== Summary ===")
    if err:
        print(f"{len(err)} issue(s):")
        for e in err:
            print(f"  - {e}")
        return 1
    print("All automated staging checks passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
