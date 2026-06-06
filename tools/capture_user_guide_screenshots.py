#!/usr/bin/env python3
"""Capture screenshots from module_catalog.json — list, add, and edit screens."""
from __future__ import annotations

import json
import os
import sys
from pathlib import Path

from playwright.sync_api import Page, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "docs/user-guide/module_catalog.json"
IMG = ROOT / "docs/user-guide/images"
BASE = os.environ.get("OMS_BASE_URL", "http://localhost/Office_management_system/").rstrip("/") + "/"

EDIT_SELECTORS = [
    'a[href*="/edit"]',
    'a.btn-warning[href*="edit"]',
    'a:has-text("Edit")',
    'a[title="Edit"]',
    '.btn-edit',
]


def login(page: Page, login_id: str, password: str) -> None:
    page.goto(BASE + "auth/login", wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(800)
    page.fill('input[name="login"]', login_id)
    page.fill('input[name="password"]', password)
    page.click('button.btn-login, button[type="submit"]')
    page.wait_for_timeout(3000)
    if "verify-2fa" in page.url.lower():
        raise RuntimeError("2FA required")


def dismiss_overlays(page: Page) -> None:
    for sel in (".modal.show button.btn-close", ".swal2-confirm"):
        try:
            loc = page.locator(sel).first
            if loc.is_visible(timeout=300):
                loc.click(timeout=500)
                page.wait_for_timeout(200)
        except Exception:
            pass


def shot(page: Page, rel_image: str) -> bool:
    """rel_image like images/02-people/users-add.png"""
    rel = rel_image.replace("\\", "/")
    if rel.startswith("images/"):
        rel = rel[len("images/") :]
    path = IMG / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    try:
        dismiss_overlays(page)
        page.wait_for_timeout(400)
        page.screenshot(path=str(path), full_page=True)
        print(f"  OK  images/{rel}")
        return True
    except Exception as exc:
        print(f"  SKIP images/{rel}: {exc}")
        return False


def goto_route(page: Page, route: str) -> bool:
    try:
        page.goto(BASE + route.lstrip("/"), wait_until="domcontentloaded", timeout=60000)
        page.wait_for_timeout(1800)
        if "login" in page.url.lower() and "auth/login" not in route:
            print(f"  WARN redirected to login for {route}")
            return False
        if page.locator("text=Access Denied").count() and page.locator("text=Access Denied").first.is_visible():
            print(f"  SKIP access denied: {route}")
            return False
        return True
    except Exception as exc:
        print(f"  SKIP route {route}: {exc}")
        return False


def capture_edit(page: Page, list_route: str, rel_image: str) -> bool:
    if not goto_route(page, list_route):
        return False
    for sel in EDIT_SELECTORS:
        loc = page.locator(sel).first
        try:
            if loc.count() > 0 and loc.is_visible(timeout=1500):
                loc.click(timeout=8000)
                page.wait_for_timeout(2000)
                return shot(page, rel_image)
        except Exception:
            continue
    print(f"  SKIP edit link not found for {list_route}")
    return False


def collect_jobs(catalog: dict) -> list[tuple[str, str, str | None]]:
    """Return (kind, rel_image, route) where kind is list|add|edit|route."""
    jobs: list[tuple[str, str, str | None]] = []
    seen: set[str] = set()

    def add(kind: str, image: str | None, route: str | None, list_route: str | None = None):
        if not image or image in seen:
            return
        seen.add(image)
        if kind == "edit":
            jobs.append(("edit", image, list_route or route))
        else:
            jobs.append((kind, image, route))

    for mod in catalog["modules"]:
        for ent in mod.get("entities", []):
            lst = ent.get("list") or {}
            add("list", lst.get("image"), lst.get("route"))
            add_block = ent.get("add") or {}
            if add_block.get("image"):
                add("add", add_block["image"], add_block.get("route"))
            edit = ent.get("edit") or {}
            if edit.get("image"):
                if edit.get("list_route"):
                    add("edit", edit["image"], edit.get("list_route"))
                elif edit.get("route"):
                    add("route", edit["image"], edit.get("route"))

    # Static auth screens without catalog list route
    extras = [
        ("list", "images/01-auth/login.png", "auth/login"),
        ("list", "images/01-auth/forgot-password.png", "forgot-password"),
    ]
    for kind, img, route in extras:
        if img not in seen:
            seen.add(img)
            jobs.append((kind, img, route))

    return jobs


def main() -> int:
    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("Set OMS_TEST_LOGIN and OMS_TEST_PASSWORD")
        return 2

    catalog = json.loads(CATALOG.read_text(encoding="utf-8"))
    jobs = collect_jobs(catalog)
    ok = skip = 0

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1440, "height": 900}, device_scale_factor=2)
        page = context.new_page()

        for kind, image, route in jobs:
            if kind == "list" and route in ("auth/login", "forgot-password"):
                if goto_route(page, route) and shot(page, image):
                    ok += 1
                else:
                    skip += 1

        login(page, login_id, password)

        for kind, image, route in jobs:
            if kind == "list" and route in ("auth/login", "forgot-password"):
                continue
            if kind == "edit":
                if capture_edit(page, route or "", image):
                    ok += 1
                else:
                    skip += 1
            elif route:
                if goto_route(page, route) and shot(page, image):
                    ok += 1
                else:
                    skip += 1
            else:
                skip += 1

        browser.close()

    total = len(list(IMG.rglob("*.png")))
    print(f"\nDone — {ok} captured, {skip} skipped, {total} PNG files total")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
