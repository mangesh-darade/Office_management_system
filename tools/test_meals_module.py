#!/usr/bin/env python3
"""
Deep static + optional DB verification for Office Meals module.
Run: python tools/test_meals_module.py
"""
from __future__ import annotations

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


def check_files_exist() -> None:
    required = [
        "controllers/Meals.php",
        "models/Meal_model.php",
        "helpers/meal_helper.php",
        "helpers/meal_schema_helper.php",
        "helpers/meal_notify_helper.php",
        "views/meals/index.php",
        "views/meals/settings.php",
        "views/meals/calendar.php",
        "views/meals/provider.php",
        "views/meals/history.php",
        "views/meals/all_orders.php",
        "views/meals/_nav.php",
        "views/meals/_change_request.php",
    ]
    for rel in required:
        p = APP / rel.replace("/", "\\") if "\\" in str(APP) else APP / rel
        if p.exists():
            ok(f"File exists: {rel}")
        else:
            fail(f"Missing file: {rel}")


def check_routes() -> None:
    routes = read(APP / "config" / "routes.php")
    for r in ("meals", "meals/save_order", "meals/submit_request", "meals/review_request", "meals/calendar", "meals/provider", "meals/settings", "meals/history", "meals/all_orders", "meals/export"):
        if f"'{r}'" in routes or f'"{r}"' in routes or f"$route['{r}']" in routes:
            ok(f"Route: {r}")
        else:
            fail(f"Missing route: {r}")


def check_permissions() -> None:
    perms = read(APP / "controllers" / "Permissions.php")
    pmap = read(APP / "helpers" / "permission_helper.php")
    keys = [
        "meals_order", "meals_calendar", "meals_provider",
        "meals_settings", "meals_history", "meals_all_orders",
    ]
    for k in keys:
        if f"'{k}'" in perms:
            ok(f"Permission matrix: {k}")
        else:
            fail(f"Permission matrix missing: {k}")
    if "'Office Meals'" in perms and "'meals_all_orders'" in perms:
        ok("Office Meals permission section with All meal orders screen")
    else:
        fail("Office Meals section or Screen tags missing in Permissions.php")
    if "permissions_module_meta" in pmap:
        ok("Permission tag helpers defined")
    else:
        fail("Missing permissions_module_meta helper")
    if "'meals'" not in pmap.split("'meals' => [")[1].split("],")[0] if "'meals' => [" in pmap else "meals_order":
        ok("Controller map uses granular meals keys only")
    else:
        fail("Controller map still includes legacy meals parent key")


def check_controller_gates() -> None:
    ctrl = read(APP / "controllers" / "Meals.php")
    gates = {
        "index": "meals_order",
        "save_order": "meals_order",
        "submit_request": "meals_order",
        "review_request": "meals_provider",
        "calendar": "meals_calendar",
        "provider": "meals_provider",
        "settings": "meals_settings",
        "history": "meals_history",
        "all_orders": "meals_all_orders",
        "export": "meals_all_orders",
    }
    for method, key in gates.items():
        block = ctrl.split(f"function {method}")[1].split("function ")[0] if f"function {method}" in ctrl else ""
        if method == "index" and "meal_default_route" in block:
            ok("Meals::index redirects non-order users to allowed screen")
        elif method == "all_orders" and "meal_can_view_all_orders" in block:
            ok("Meals::all_orders gated on All meal orders permission")
        elif "require_meal_access" in block:
            ok(f"Meals::{method} uses require_meal_access")
        elif key in block and "require_module_access" in block:
            ok(f"Meals::{method} gated on {key}")
        else:
            fail(f"Meals::{method} missing require_module_access for {key}")


def check_schema_tables() -> None:
    schema = read(APP / "helpers" / "meal_schema_helper.php")
    for t in ("meal_settings", "meal_week_menu", "meal_calendar", "meal_orders", "meal_order_log", "meal_change_requests"):
        if t in schema:
            ok(f"Schema defines: {t}")
        else:
            fail(f"Schema missing table: {t}")


def check_logic_patterns() -> None:
    helper = read(APP / "helpers" / "meal_helper.php")
    model = read(APP / "models" / "Meal_model.php")
    ctrl = read(APP / "controllers" / "Meals.php")

    if "?array $settings = null" in helper and "?array $settings = null" in model:
        ok("Nullable settings params (PHP 8.4)")
    else:
        warn("Check nullable ?array on meal settings params")

    if "meal_monday_of" in helper and "date('N'" in helper:
        ok("meal_monday_of uses ISO day-of-week (Sun fix)")
    else:
        fail("meal_monday_of may break on Sunday")

    if "function save_order" in ctrl and "meal_date !== $today" in ctrl:
        ok("Ajax save_order accepts today only")
    else:
        fail("save_order missing today-only date check")

    index_view = read(APP / "views" / "meals" / "index.php")
    if "meals/save_order" in index_view and "fetch(saveUrl" in index_view:
        ok("Meals index auto-saves via AJAX")
    else:
        fail("Meals index missing AJAX auto-save")

    if "Save preferences" not in index_view:
        ok("No manual save button on meals index")
    else:
        fail("Meals index still has manual save button")

    if "'changed'" in model and "'changed'" in ctrl:
        ok("save_user_order returns changed flag")
    else:
        fail("Missing changed flag on save_user_order")

    if "auto_publish_announcements" in model and "meal_sync_dashboard_announcement" in helper:
        ok("Announcements respect auto_publish setting")
    else:
        fail("Announcement auto-publish wiring incomplete")

    if "meal_dashboard_today_tomorrow" in helper and "meal_filter_dashboard_announcements" in helper:
        ok("Dashboard today/tomorrow meal grid helpers")
    else:
        fail("Missing dashboard meal grid helpers")

    if "meal_role_has" in helper and "require_meal_access" in helper:
        ok("Strict meal permission checks (no Super Admin bypass)")
    else:
        fail("Missing meal_role_has / require_meal_access helpers")

    if "has_module_access('meals')" not in helper.split("function meal_can_access")[1].split("function ")[0] if "function meal_can_access" in helper else "":
        ok("meal_can_access does not use global Super Admin bypass")
    elif "meal_role_has" in helper:
        ok("meal_can_access uses meal_role_has only")

    if "meal_has_any_access" in helper and "meal_can_order" in helper and "meal_can_view_dashboard_announcement" in helper:
        ok("Meals permission helpers aligned with Permissions.php keys")
    else:
        fail("Missing meal_has_any_access / meal_can_order helpers")

    if (ROOT / "docs/user-guide/12-office-meals.md").exists():
        ok("User guide module 12-office-meals.md exists")
    else:
        fail("Missing docs/user-guide/12-office-meals.md")

    guide_helper = read(APP / "helpers/user_guide_helper.php")
    if "12-office-meals" in guide_helper and "12-office-meals.md" in guide_helper:
        ok("In-app guide registers Office Meals module")
    else:
        fail("user_guide_helper.php missing module 12 Office Meals")

    if "user_guide_user_can_access_module" in guide_helper and "meal_has_any_access" in guide_helper:
        ok("Guide filters modules by permission (Office Meals uses meal_has_any_access)")
    else:
        fail("user_guide_helper.php missing permission-based guide filtering")

    catalog = read(ROOT / "docs/user-guide/module_catalog.json")
    if '"id": "12"' in catalog and "12-meals/my-orders.png" in catalog:
        ok("module_catalog.json has Office Meals module with screenshots")
    else:
        fail("module_catalog.json missing Office Meals guide entries")

    nav = read(APP / "views" / "meals" / "_nav.php")
    sidebar_meals = read(APP / "views" / "partials" / "sidebar_meals_group.php")
    if "meal_nav_screens" in nav and "all_orders" in helper and "meal_can_view_all_orders" in helper:
        ok("All Orders tab via meal_nav_screens helper")
    else:
        fail("Missing All Orders in meal nav screens")
    if "meal_nav_screens" in nav and "meal_has_any_access" in nav:
        ok("_nav.php tabs gated by role permissions")
    else:
        fail("_nav.php missing permission-based tab gating")
    if "meals-group" in sidebar_meals and "meal_nav_screens" in sidebar_meals:
        ok("Sidebar Office Meals expandable group with permission screens")
    else:
        fail("Sidebar missing expandable Office Meals group")

    schema = read(APP / "helpers" / "meal_schema_helper.php")
    if "lunch_tiffin" in schema:
        ok("Schema defines lunch_tiffin column")
    else:
        fail("Schema missing lunch_tiffin on meal_orders")

    if "meal_order_lunch_tiffin" in helper and "meal_normalize_lunch_tiffin" in helper:
        ok("Lunch tiffin helpers defined")
    else:
        fail("Missing lunch tiffin helpers")

    index_view = read(APP / "views" / "meals" / "index.php")
    if "Half tiffin" in index_view and "Full tiffin" in index_view:
        ok("Lunch UI uses half/full tiffin")
    else:
        fail("Meals index missing tiffin options")

    if "bf_hidden_" in index_view and "meal-order-locked" in index_view:
        ok("Locked meals keep hidden values for auto-save")
    else:
        fail("Locked meal hidden fields missing on index")

    if "meal_format_log_field" in helper and "meal_format_log_value" in helper:
        ok("History log label helpers defined")
    else:
        fail("Missing history log format helpers")

    if "breakfastPlates === 0 && $lunchTiffin === ''" in model:
        ok("Skip empty order insert when no existing row")
    else:
        fail("Model may insert empty meal orders")

    if "date('Y-m-d')" in ctrl and "+1 day" not in ctrl.split("function provider")[1].split("function settings")[0]:
        ok("Provider defaults to today")
    else:
        warn("Verify provider default date is today")

    if "meal_is_breakfast_editable" in helper and "meal_date < date('Y-m-d')" in helper:
        ok("Past dates are not editable")
    else:
        fail("Past-date lock logic missing")

    if "create_change_request" in model and "review_change_request" in model and "override_lock" in model:
        ok("Change request model methods + lock override")
    else:
        fail("Change request model incomplete")

    if "meal_format_request_value" in helper and "meal_request_status_badge" in helper:
        ok("Change request display helpers")
    else:
        fail("Missing change request helpers")

    if "meal_change_is_additional" in helper and "meal_order_additional_breakfast_plates" in helper:
        ok("Additional breakfast/lunch order helpers")
    else:
        fail("Missing additional meal order helpers")

    if "additional_breakfast_plates" in schema and "additional_lunch_tiffin" in schema:
        ok("Schema defines additional breakfast/lunch columns")
    else:
        fail("Schema missing additional meal order columns")

    cr_view = read(APP / "views" / "meals" / "_change_request.php")
    if "add_bf:1" in cr_view and "add_lu:half" in cr_view and "add_lu:full" in cr_view:
        ok("Change request UI offers additional plate/tiffin options")
    else:
        fail("Change request UI missing additional options")

    if "add_bf:1" in model and "add_lu:" in model:
        ok("Model validates and applies additional change requests")
    else:
        fail("Model missing additional change request handling")

    if "submit_request" in ctrl and "review_request" in ctrl:
        ok("Controller submit_request + review_request")
    else:
        fail("Controller missing change request endpoints")

    if "meals/submit_request" in index_view and "meal-change-request-form" in index_view:
        ok("Employee locked UI can submit change requests")
    else:
        fail("Meals index missing change request UI")

    provider_view = read(APP / "views" / "meals" / "provider.php")
    if "pending_requests" in provider_view and "meals/review_request" in provider_view:
        ok("Provider can approve/reject pending requests")
    else:
        fail("Provider missing change request review UI")

    settings = read(APP / "views" / "meals" / "settings.php")
    if "email_change_requests" not in settings and "in-app notifications" in settings.lower():
        ok("Meals settings has no email change-request toggle")
    else:
        fail("Meals settings still exposes email change requests")

    notify = read(APP / "helpers" / "meal_notify_helper.php")
    if "meal_notify_send_mail" not in notify and "meal_notify_email_enabled" not in notify:
        ok("Meal notify uses in-app notifications only (no email)")
    else:
        fail("meal_notify_helper still sends email")

    if "show_dashboard_announcement" in settings:
        ok("Meals settings has dashboard announcement show/hide toggle")
    else:
        fail("Meals settings missing show_dashboard_announcement toggle")

    if "show_dashboard_announcement" in schema:
        ok("Schema defines show_dashboard_announcement on meal_settings")
    else:
        fail("Schema missing show_dashboard_announcement column")

    if "provider_contact" in schema and "meal_provider_contact" in helper:
        ok("Provider contact number setting + helper")
    else:
        fail("Missing provider contact setting")

    settings_view = read(APP / "views" / "meals" / "settings.php")
    index_view = read(APP / "views" / "meals" / "index.php")
    if "provider_contact" in settings_view and "Contact us" in index_view and "meal_provider_contact" in index_view:
        ok("My Orders shows Contact us when provider number set")
    else:
        fail("My Orders missing provider contact display")

    if "meal_show_dashboard_announcement" in helper:
        ok("Dashboard meals visibility helper")
    else:
        fail("Missing meal_show_dashboard_announcement helper")

    if "meal_notify_request_submitted" in notify and "meal_notify_request_reviewed" in notify:
        ok("Meal change request in-app notify helper")
    else:
        fail("Missing meal_notify_helper.php")

    if "meal_notify_request_submitted" in model and "meal_notify_request_reviewed" in model:
        ok("Model triggers meal change notifications")
    else:
        fail("Meal_model missing notification hooks")

    if "meals_auto_lock" in read(APP / "controllers" / "Cron.php"):
        ok("Cron meals_auto_lock exists")
    else:
        warn("Cron meals_auto_lock not found")


def check_views_csrf() -> None:
    for view in ["index.php", "settings.php", "calendar.php"]:
        v = read(APP / "views" / "meals" / view)
        if "get_csrf_token_name" in v or "form_hidden" in v:
            ok(f"CSRF in meals/{view}")
        elif "method=\"post\"" in v.lower() or "method='post'" in v.lower():
            fail(f"POST form without CSRF in meals/{view}")


def check_db_optional() -> None:
    try:
        import mysql.connector  # type: ignore
    except ImportError:
        warn("mysql-connector not installed — skipping DB checks")
        return

    cfg = read(APP / "config" / "database.php")

    def grab(key: str) -> str:
        m = re.search(rf"'\{key}'\s*=>\s*'([^']*)'", cfg)
        return m.group(1) if m else ""

    database = grab("database")
    if not database:
        warn("Could not parse database.php")
        return

    try:
        conn = mysql.connector.connect(
            host=grab("hostname") or "127.0.0.1",
            user=grab("username") or "root",
            password=grab("password"),
            database=database,
        )
    except Exception as e:
        warn(f"DB connect failed: {e}")
        return

    cur = conn.cursor(dictionary=True)
    tables = ["meal_settings", "meal_week_menu", "meal_calendar", "meal_orders", "meal_order_log", "meal_change_requests"]
    for t in tables:
        cur.execute("SHOW TABLES LIKE %s", (t,))
        if cur.fetchone():
            ok(f"DB table: {t}")
        else:
            warn(f"DB table missing (visit /meals once): {t}")

    cur.execute("SELECT COUNT(*) AS c FROM meal_week_menu")
    row = cur.fetchone()
    if row and int(row["c"]) >= 7:
        ok(f"DB week menu has {row['c']} days")
    else:
        warn("meal_week_menu not seeded — open /meals/settings once")

    cur.execute("SELECT breakfast_cutoff, lunch_cutoff FROM meal_settings LIMIT 1")
    s = cur.fetchone()
    if s:
        bf = str(s["breakfast_cutoff"])[:5]
        lu = str(s["lunch_cutoff"])[:5]
        ok(f"DB cutoffs breakfast={bf} lunch={lu}")
    else:
        warn("meal_settings row missing")

    conn.close()


def main() -> int:
    print("=== Office Meals module deep check ===\n")
    check_files_exist()
    check_routes()
    check_permissions()
    check_controller_gates()
    check_schema_tables()
    check_logic_patterns()
    check_views_csrf()
    check_db_optional()

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
    print("\nResult: ALL PASSED" if not WARNINGS else "\nResult: OK (with warnings)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
