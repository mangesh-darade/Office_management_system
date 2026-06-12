#!/usr/bin/env python3
"""Capture Project Members user-guide screenshot (live app or fixture fallback)."""
from __future__ import annotations

import json
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "docs/user-guide/module_catalog.json"
FIXTURE = ROOT / "tools/fixtures/project-members-guide.html"
TARGET = ROOT / "docs/user-guide/images/04-projects/project-members.png"

sys.path.insert(0, str(ROOT / "tools"))
from capture_user_guide_screenshots import (  # noqa: E402
    BASE,
    GUIDE_PROJECT_ID,
    goto_route,
    login,
    resolve_route,
    shot,
)

IMAGE = "images/04-projects/project-members.png"
ROUTE = "projects/{id}/members"


def capture_fixture() -> bool:
    from playwright.sync_api import sync_playwright

    TARGET.parent.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(viewport={"width": 1440, "height": 900}, device_scale_factor=2)
        page.goto(FIXTURE.as_uri(), wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(600)
        page.screenshot(path=str(TARGET), full_page=True)
        browser.close()
    print(f"  OK  fixture -> {TARGET.relative_to(ROOT)}")
    return True


def capture_live(login_id: str, password: str) -> bool:
    from playwright.sync_api import sync_playwright

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1440, "height": 900}, device_scale_factor=2)
        page = context.new_page()
        login(page, login_id, password)
        route = resolve_route(ROUTE)
        if not goto_route(page, route):
            browser.close()
            return False
        ok = shot(page, IMAGE)
        browser.close()
        return ok


def main() -> int:
    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    print(f"Base URL: {BASE}")
    print(f"Project ID: {GUIDE_PROJECT_ID}")

    if login_id and password:
        print("Capturing from live app...")
        if capture_live(login_id, password):
            return 0
        print("Live capture failed; using fixture fallback.")

    print("Capturing guide fixture (set OMS_TEST_LOGIN/PASSWORD for live QA/local capture).")
    return 0 if capture_fixture() else 1


if __name__ == "__main__":
    sys.exit(main())
