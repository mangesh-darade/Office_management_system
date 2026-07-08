#!/usr/bin/env python3
"""
Notification wiring spot-check for refactored controllers.
Static: helper calls still present in controllers.
Runtime (optional): notifications center + profile update flash path.
Requires OMS_TEST_LOGIN + OMS_TEST_PASSWORD for runtime section.
"""
from __future__ import annotations

import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CTRL = ROOT / "application/controllers"
HELPERS = ROOT / "application/helpers"

# Expected notification-related patterns per controller (at least one must match)
CONTROLLER_WIRING: dict[str, list[str]] = {
    "Attendance.php": ["attendance_notify_", "get_notification_message"],
    "Leave_requests.php": ["leave_requests_notify_", "get_notification_message"],
    "Tasks.php": ["send_notification_with_settings|send_task_notification", "get_notification_message"],
    "Projects.php": ["get_notification_message"],
    "Departments.php": ["get_notification_message"],
    "Designations.php": ["get_notification_message"],
    "Expenses.php": ["create_notification"],
    "Users.php": ["get_notification_message"],
    "Clients.php": ["get_notification_message"],
    "Employees.php": ["get_notification_message"],
    "Profile.php": ["get_notification_message"],
    "Settings.php": ["get_notification_message"],
}

REFACTOR_HELPERS: dict[str, list[str]] = {
    "leave_requests_notify_helper.php": ["leave_requests_notify_applied", "leave_requests_notify_change"],
    "attendance_notify_helper.php": ["attendance_notify_send_email", "attendance_notify_load_user"],
    "notification_helper.php": ["get_notification_message", "create_notification", "notify_task_assigned"],
    "email_helper.php": ["send_task_notification"],
    "email_settings_helper.php": ["send_notification_with_settings"],
}


def ok(msg: str) -> None:
    print(f"  OK  {msg}")


def fail(msg: str) -> None:
    print(f"FAIL  {msg}")


def check_controller_wiring() -> list[str]:
    errors: list[str] = []
    for filename, patterns in CONTROLLER_WIRING.items():
        path = CTRL / filename
        if not path.is_file():
            errors.append(f"missing controller {filename}")
            fail(f"missing {filename}")
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        matched = False
        for pat in patterns:
            if "|" in pat:
                if any(re.search(p, text) for p in pat.split("|")):
                    matched = True
                    break
            elif pat in text:
                matched = True
                break
        if not matched:
            errors.append(f"{filename}: no notification wiring ({patterns})")
            fail(f"wiring {filename}")
        else:
            ok(f"wiring {filename}")
    return errors


def check_helper_functions() -> list[str]:
    errors: list[str] = []
    for filename, funcs in REFACTOR_HELPERS.items():
        path = HELPERS / filename
        if not path.is_file():
            errors.append(f"missing helper {filename}")
            fail(f"missing helper {filename}")
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for fn in funcs:
            if not re.search(rf"function\s+{re.escape(fn)}\s*\(", text):
                errors.append(f"{filename}: missing {fn}()")
                fail(f"{filename}::{fn}")
            else:
                ok(f"helper {fn}()")
    return errors


def check_leave_requests_loads_helper() -> list[str]:
    errors: list[str] = []
    text = (CTRL / "Leave_requests.php").read_text(encoding="utf-8", errors="replace")
    if "leave_requests_notify" not in text:
        errors.append("Leave_requests.php does not load leave_requests_notify helper")
        fail("Leave_requests helper load")
    else:
        ok("Leave_requests loads leave_requests_notify helper")
    return errors


def run_runtime_checks() -> list[str]:
    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("  -- skip runtime (set OMS_TEST_LOGIN / OMS_TEST_PASSWORD)")
        return []

    # Import session helpers from auth verify script
    sys.path.insert(0, str(ROOT / "tools"))
    import verify_staging_auth as auth

    errors: list[str] = []
    session = auth.Session()
    ok_login, err = auth.login(session, login_id, password)
    if not ok_login:
        errors.append(err)
        fail(f"login: {err}")
        return errors

    code, html, _ = session.request("GET", "notifications")
    if code >= 500 or auth.is_login_page(html):
        errors.append("notifications index failed")
        fail("notifications index")
    else:
        ok("notifications index loads")

    m = re.search(r"unread[^0-9]*(\d+)", html, re.I)
    if m:
        ok(f"notifications unread count visible ({m.group(1)})")
    else:
        ok("notifications page rendered (unread count not parsed)")

    code, html, _ = session.request("GET", "profile/edit")
    if code >= 500:
        errors.append("profile/edit HTTP error")
        fail("profile/edit")
        return errors

    fields: dict[str, str] = {}
    for name in ("name", "email", "phone", "first_name", "last_name", "department", "designation", "address", "bio"):
        m = re.search(rf'name="{name}"[^>]*value="([^"]*)"', html)
        if m:
            fields[name] = m.group(1)
    fields.setdefault("address", "")
    fields.setdefault("bio", "")
    if not fields.get("name") and not fields.get("email"):
        ok("profile/edit loaded (form fields not parsed — skip POST)")
        return errors

    token = session.csrf_token()
    post = {**fields, "ci_csrf_token": token}
    code, body, _ = session.request("POST", "profile/edit", post)
    if code >= 500:
        errors.append(f"profile update HTTP {code}")
        fail(f"profile update: {code}")
    else:
        ok(f"profile update POST ({code}) — get_notification_message path")

    return errors


def main() -> int:
    err: list[str] = []
    print("=== Notification controller wiring ===")
    err += check_controller_wiring()
    print("\n=== Notification helpers ===")
    err += check_helper_functions()
    print("\n=== Refactor-specific loads ===")
    err += check_leave_requests_loads_helper()
    print("\n=== Runtime (authenticated) ===")
    err += run_runtime_checks()
    print("\n=== Summary ===")
    if err:
        for e in err:
            print(f"  - {e}")
        return 1
    print("All notification spot-checks passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
