#!/usr/bin/env python3
"""
Static checks for Release module + Reminders email integration.
Run: python tools/test_releases_module.py
"""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "application"

FAILURES: list[str] = []
PASSED: list[str] = []


def ok(msg: str) -> None:
    PASSED.append(msg)


def fail(msg: str) -> None:
    FAILURES.append(msg)


def read(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="replace")


def check_files() -> None:
    required = [
        "controllers/Releases.php",
        "helpers/release_notify_helper.php",
        "views/releases/form.php",
        "views/releases/index.php",
        "models/Engagement_model.php",
        "models/Defect_model.php",
        "models/Reminder_model.php",
    ]
    for rel in required:
        p = APP / rel.replace("/", "\\")
        if p.exists():
            ok(f"File: {rel}")
        else:
            fail(f"Missing: {rel}")


def check_schema() -> None:
    schema = read(APP / "helpers" / "engagement_schema_helper.php")
    for token in ("project_release_notes", "notes_sent_at"):
        if token in schema:
            ok(f"Schema: {token}")
        else:
            fail(f"Schema missing: {token}")


def check_routes() -> None:
    routes = read(APP / "config" / "routes.php")
    for r in ("releases/send-notes", "releases/create", "releases/edit"):
        if r in routes:
            ok(f"Route: {r}")
        else:
            fail(f"Route missing: {r}")


def check_permissions() -> None:
    perms = read(APP / "controllers" / "Permissions.php")
    pmap = read(APP / "helpers" / "permission_helper.php")
    key = "releases_send_notes"
    if f"'{key}'" in perms:
        ok(f"Permission matrix: {key}")
    else:
        fail(f"Permission matrix missing: {key}")
    if f"'{key}'" in pmap:
        ok(f"permission_helper: {key}")
    else:
        fail(f"permission_helper missing: {key}")


def check_controller() -> None:
    ctrl = read(APP / "controllers" / "Releases.php")
    for token in (
        "send_notes",
        "release_notify",
        "save_release_notes",
        "release_send_notes_to_users",
        "list_by_release",
        "Reminder_model",
    ):
        if token in ctrl:
            ok(f"Controller: {token}")
        else:
            fail(f"Controller missing: {token}")


def check_notify_helper() -> None:
    helper = read(APP / "helpers" / "release_notify_helper.php")
    for token in ("release_note", "enqueue", "reminders_send_one", "release_email_body"):
        if token in helper:
            ok(f"release_notify: {token}")
        else:
            fail(f"release_notify missing: {token}")


def check_form_view() -> None:
    form = read(APP / "views" / "releases" / "form.php")
    for token in ("note_points[]", "releaseRecipients", "btnSendNotesNow", "related_defects", "send-notes"):
        if token in form:
            ok(f"Form view: {token}")
        else:
            fail(f"Form view missing: {token}")


def check_catalog() -> None:
    cat = read(ROOT / "docs" / "user-guide" / "module_catalog.json")
    if "Send notes now" in cat or "release note points" in cat:
        ok("module_catalog release notes docs")
    else:
        fail("module_catalog missing release notes documentation")


def main() -> int:
    check_files()
    check_schema()
    check_routes()
    check_permissions()
    check_controller()
    check_notify_helper()
    check_form_view()
    check_catalog()

    print(f"\nPassed: {len(PASSED)}")
    if FAILURES:
        print(f"Failed: {len(FAILURES)}")
        for f in FAILURES:
            print(f"  - {f}")
        return 1
    print("All release module checks passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
