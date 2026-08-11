#!/usr/bin/env python3
"""
Playwright e2e: Defects create — Client filter, Assign to, save.

Usage (PowerShell):
  $env:OMS_TEST_LOGIN="your_login"
  $env:OMS_TEST_PASSWORD="your_password"
  python _unused/tools/test_defects_create_playwright.py

Optional:
  $env:OMS_BASE_URL="http://localhost/Office_management_system/"
  $env:OMS_PW_HEADED="1"   # show browser
"""
from __future__ import annotations

import os
import sys
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "_unused" / "tools" / "_pw_out"
BASE = os.environ.get("OMS_BASE_URL", "http://localhost/Office_management_system/").rstrip("/") + "/"


def fail(msg: str) -> None:
    print(f"FAIL: {msg}")
    raise SystemExit(1)


def ok(msg: str) -> None:
    print(f"OK: {msg}")


def main() -> int:
    try:
        from playwright.sync_api import sync_playwright
    except ImportError:
        print("Installing playwright…")
        import subprocess

        subprocess.check_call([sys.executable, "-m", "pip", "install", "playwright"])
        subprocess.check_call([sys.executable, "-m", "playwright", "install", "chromium"])
        from playwright.sync_api import sync_playwright

    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("Set OMS_TEST_LOGIN and OMS_TEST_PASSWORD, then re-run.")
        return 2

    headed = os.environ.get("OMS_PW_HEADED", "").strip() in ("1", "true", "yes")
    OUT.mkdir(parents=True, exist_ok=True)
    stamp = time.strftime("%Y%m%d_%H%M%S")
    title = f"PW defect {stamp}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=not headed)
        desktop = browser.new_context(viewport={"width": 1366, "height": 900})
        page = desktop.new_page()

        # --- Login ---
        page.goto(BASE + "auth/login", wait_until="domcontentloaded", timeout=60000)
        page.fill('input[name="login"]', login_id)
        page.fill('input[name="password"]', password)
        page.click('button.btn-login, button[type="submit"]')
        page.wait_for_timeout(2500)
        if "verify-2fa" in page.url.lower():
            fail("2FA required — disable for test user or complete manually")
        if "login" in page.url.lower():
            page.screenshot(path=str(OUT / "login_failed.png"), full_page=True)
            fail("Login failed (still on login page)")
        ok("Logged in")

        # --- Open create ---
        page.goto(BASE + "defects/create", wait_until="domcontentloaded", timeout=60000)
        page.wait_for_timeout(1200)
        if "login" in page.url.lower():
            fail("Redirected to login on defects/create")
        if page.locator("text=Access Denied").count() and page.locator("text=Access Denied").first.is_visible():
            fail("Access Denied on defects/create")

        page.wait_for_selector("#defectForm", timeout=15000)
        page.wait_for_selector("#defectClientId", timeout=10000)
        page.wait_for_selector("#defectProjectId", timeout=10000)
        page.wait_for_selector("#defectAssignedTo", timeout=10000)
        ok("Create form fields present (client, project, assign)")

        page.screenshot(path=str(OUT / "defects_create_desktop.png"), full_page=True)

        client = page.locator("#defectClientId")
        project = page.locator("#defectProjectId")
        assign = page.locator("#defectAssignedTo")

        client_options = client.locator("option").count()
        if client_options < 1:
            fail("Client dropdown empty")
        ok(f"Client options: {client_options}")

        # Prefer a real client (skip "All clients")
        chosen_client = None
        for i in range(client_options):
            val = client.locator("option").nth(i).get_attribute("value") or "0"
            if val not in ("", "0"):
                chosen_client = val
                break

        if chosen_client:
            client.select_option(value=chosen_client)
            page.wait_for_timeout(400)
            # After filter, project list should only include matching / empty placeholder
            proj_count = project.locator("option").count()
            ok(f"After client filter, project options: {proj_count}")
            # Pick first real project under filtered list
            picked_project = None
            for i in range(proj_count):
                opt = project.locator("option").nth(i)
                val = opt.get_attribute("value") or ""
                if not val:
                    continue
                cid = opt.get_attribute("data-client-id") or "0"
                if cid in ("0", chosen_client):
                    picked_project = val
                    break
            if not picked_project:
                # Fallback: clear client filter and pick any project
                client.select_option(value="0")
                page.wait_for_timeout(300)
                for i in range(project.locator("option").count()):
                    val = project.locator("option").nth(i).get_attribute("value") or ""
                    if val:
                        picked_project = val
                        break
            if not picked_project:
                page.screenshot(path=str(OUT / "no_project.png"), full_page=True)
                fail("No project available to select")
            project.select_option(value=picked_project)
            page.wait_for_timeout(800)  # ajax-options
            ok(f"Selected client={chosen_client} project={picked_project}")
        else:
            # No clients — still need a project
            picked_project = None
            for i in range(project.locator("option").count()):
                val = project.locator("option").nth(i).get_attribute("value") or ""
                if val:
                    picked_project = val
                    break
            if not picked_project:
                page.screenshot(path=str(OUT / "no_project.png"), full_page=True)
                fail("No project available (and no clients)")
            project.select_option(value=picked_project)
            page.wait_for_timeout(800)
            ok(f"Selected project={picked_project} (no clients)")

        # Assign first non-empty user if available
        assign_val = ""
        for i in range(assign.locator("option").count()):
            val = assign.locator("option").nth(i).get_attribute("value") or ""
            if val:
                assign_val = val
                break
        if assign_val:
            assign.select_option(value=assign_val)
            ok(f"Assigned to user id={assign_val}")
        else:
            ok("Assign left Unassigned (no members)")

        page.fill("#defectTitle", title)
        page.select_option("#defectSeverity", "high")
        page.select_option("#defectPriority", "medium")

        # TinyMCE may own description; title is enough for save
        page.locator('button.defect-btn-save, #defectForm button[type="submit"]').first.click()
        page.wait_for_timeout(3500)

        if "defects/view/" not in page.url.lower() and "defects/create" in page.url.lower():
            err = page.locator(".alert-danger").first
            msg = err.inner_text() if err.count() and err.is_visible() else "unknown"
            page.screenshot(path=str(OUT / "save_failed.png"), full_page=True)
            fail(f"Save did not redirect to view. Flash/error: {msg}")

        if "defects/view/" not in page.url.lower():
            page.screenshot(path=str(OUT / "unexpected_url.png"), full_page=True)
            fail(f"Unexpected URL after save: {page.url}")

        page.wait_for_timeout(800)
        body = page.locator("body").inner_text()
        if title not in body and "Defect" not in body:
            page.screenshot(path=str(OUT / "view_missing_title.png"), full_page=True)
            fail("Defect view did not show expected content")
        ok(f"Saved and opened view: {page.url}")
        page.screenshot(path=str(OUT / "defects_view_after_create.png"), full_page=True)

        # --- Mobile viewport smoke ---
        state = desktop.storage_state()
        mobile = browser.new_context(
            viewport={"width": 390, "height": 844},
            is_mobile=True,
            has_touch=True,
            storage_state=state,
        )
        mpage = mobile.new_page()
        mpage.goto(BASE + "defects/create", wait_until="domcontentloaded", timeout=60000)
        mpage.wait_for_selector("#defectForm", timeout=15000)
        mpage.wait_for_selector("#defectClientId", timeout=10000)
        sticky = mpage.locator(".defect-form-actions")
        if sticky.count() < 1:
            fail("Mobile: sticky actions bar missing")
        mpage.screenshot(path=str(OUT / "defects_create_mobile.png"), full_page=True)
        ok("Mobile form loads with Client + sticky actions")

        mobile.close()
        desktop.close()
        browser.close()

    print("\nAll Playwright checks passed.")
    print(f"Screenshots: {OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
