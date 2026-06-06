#!/usr/bin/env python3
"""Record CRUD walkthrough videos + module tours for user guide."""
from __future__ import annotations

import json
import os
import shutil
import sys
import tempfile
from pathlib import Path
from typing import Callable

from playwright.sync_api import Page, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
VID = ROOT / "docs/user-guide/videos"
CATALOG = ROOT / "docs/user-guide/module_catalog.json"
BASE = os.environ.get("OMS_BASE_URL", "http://localhost/Office_management_system/").rstrip("/") + "/"
VIEWPORT = {"width": 1280, "height": 720}


def type_slow(page: Page, selector: str, text: str, delay_ms: int = 70) -> None:
    page.click(selector, timeout=15000)
    page.fill(selector, "")
    page.type(selector, text, delay=delay_ms)


def smooth_scroll(page: Page, steps: int = 10, pause_ms: int = 300) -> None:
    page.evaluate(
        """async ([steps, pause]) => {
            const max = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
            const step = max / Math.max(steps, 1);
            for (let i = 1; i <= steps; i++) {
                window.scrollTo({ top: step * i, behavior: 'smooth' });
                await new Promise(r => setTimeout(r, pause));
            }
        }""",
        [steps, pause_ms],
    )


def record(browser, out_file: Path, fn: Callable[[Page], None], storage_state: dict | None = None) -> bool:
    out_file.parent.mkdir(parents=True, exist_ok=True)
    tmp = Path(tempfile.mkdtemp(prefix="guide_vid_"))
    try:
        kwargs = {"viewport": VIEWPORT, "record_video_dir": str(tmp), "record_video_size": VIEWPORT}
        if storage_state:
            kwargs["storage_state"] = storage_state
        context = browser.new_context(**kwargs)
        page = context.new_page()
        try:
            fn(page)
            page.wait_for_timeout(1000)
        finally:
            page.close()
            context.close()
        webms = list(tmp.glob("*.webm"))
        if not webms:
            return False
        if out_file.exists():
            out_file.unlink()
        shutil.move(str(webms[0]), str(out_file))
        print(f"  OK  {out_file.relative_to(ROOT)}")
        return True
    except Exception as exc:
        print(f"  SKIP {out_file.name}: {exc}")
        return False
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def login_state(browser, login_id: str, password: str) -> dict:
    ctx = browser.new_context(viewport=VIEWPORT)
    page = ctx.new_page()
    page.goto(BASE + "auth/login", wait_until="domcontentloaded", timeout=60000)
    page.fill('input[name="login"]', login_id)
    page.fill('input[name="password"]', password)
    page.click('button.btn-login, button[type="submit"]')
    page.wait_for_timeout(3500)
    if "verify-2fa" in page.url.lower():
        ctx.close()
        raise RuntimeError("2FA required")
    state = ctx.storage_state()
    ctx.close()
    return state


def crud_tour(list_route: str, add_route: str | None, edit_list_route: str | None) -> Callable[[Page], None]:
    def _fn(page: Page) -> None:
        page.goto(BASE + list_route, wait_until="domcontentloaded", timeout=60000)
        page.wait_for_timeout(2000)
        smooth_scroll(page, 6, 280)
        if add_route:
            page.goto(BASE + add_route, wait_until="domcontentloaded", timeout=60000)
            page.wait_for_timeout(2200)
            smooth_scroll(page, 5, 280)
        if edit_list_route:
            page.goto(BASE + edit_list_route, wait_until="domcontentloaded", timeout=60000)
            page.wait_for_timeout(1500)
            for sel in ('a[href*="/edit"]', 'a:has-text("Edit")'):
                loc = page.locator(sel).first
                try:
                    if loc.count() > 0 and loc.is_visible(timeout=1200):
                        loc.click(timeout=6000)
                        page.wait_for_timeout(2200)
                        smooth_scroll(page, 4, 280)
                        break
                except Exception:
                    pass

    return _fn


def main() -> int:
    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("Set OMS_TEST_LOGIN and OMS_TEST_PASSWORD")
        return 2

    ok = skip = 0

    crud_videos = [
        ("02-people/users-crud.webm", "users", "users/create", "users"),
        ("02-people/departments-crud.webm", "departments", "departments/create", "departments"),
        ("03-attendance-leave/leave-crud.webm", "leave/my", "leave/apply", "leave/my"),
        ("04-projects/tasks-crud.webm", "tasks", "tasks/create", "tasks"),
        ("05-work/my-works-crud.webm", "my-works", "my-works/create", "my-works"),
        ("08-finance/expenses-crud.webm", "expenses", "expenses/create", "expenses"),
        ("07-communication/announcements-crud.webm", "announcements", "announcements/create", "announcements"),
    ]

    tours = [
        ("01-auth/login-demo.webm", None, lambda p, lid, pw: (
            p.goto(BASE + "auth/login", wait_until="domcontentloaded"),
            p.wait_for_timeout(1000),
            type_slow(p, 'input[name="login"]', lid),
            type_slow(p, 'input[name="password"]', pw, 55),
            p.click('button.btn-login, button[type="submit"]'),
            p.wait_for_timeout(4000),
        )),
        ("01-auth/dashboard-tour.webm", "dashboard", None),
        ("01-auth/user-guide-tour.webm", "guide", None),
        ("02-people/employees-tour.webm", "employees", None),
        ("03-attendance-leave/attendance-tour.webm", "attendance", None),
        ("03-attendance-leave/leave-apply-tour.webm", "leave/apply", None),
        ("04-projects/tasks-board-tour.webm", "tasks/board", None),
        ("05-work/my-works-tour.webm", "my-works", None),
        ("06-reports/reports-tour.webm", "reports/attendance-employee", None),
        ("07-communication/chats-tour.webm", "chats/app", None),
        ("08-finance/expenses-tour.webm", "expenses", None),
        ("09-training/assessment-tour.webm", "training-assessment", None),
        ("10-admin/settings-tour.webm", "settings", None),
    ]

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        def login_demo(page: Page) -> None:
            page.goto(BASE + "auth/login", wait_until="domcontentloaded", timeout=60000)
            page.wait_for_timeout(1000)
            type_slow(page, 'input[name="login"]', login_id)
            type_slow(page, 'input[name="password"]', password, 55)
            page.click('button.btn-login, button[type="submit"]')
            page.wait_for_timeout(4000)

        if record(browser, VID / "01-auth/login-demo.webm", login_demo):
            ok += 1
        else:
            skip += 1

        state = login_state(browser, login_id, password)

        for rel, route, _ in tours[1:]:
            sub, name = rel.rsplit("/", 1)

            def make_scroll(r: str):
                def _s(page: Page) -> None:
                    page.goto(BASE + r, wait_until="domcontentloaded", timeout=60000)
                    page.wait_for_timeout(1800)
                    smooth_scroll(page, 12, 300)
                    page.wait_for_timeout(600)

                return _s

            if record(browser, VID / sub / name, make_scroll(route), state):
                ok += 1
            else:
                skip += 1

        for rel, lst, add, edit_lst in crud_videos:
            sub, name = rel.rsplit("/", 1)
            fn = crud_tour(lst, add, edit_lst)
            if record(browser, VID / sub / name, fn, state):
                ok += 1
            else:
                skip += 1

        browser.close()

    # Add CRUD narrations for new videos
    narr_path = ROOT / "docs/user-guide/narrations.json"
    if narr_path.is_file():
        cfg = json.loads(narr_path.read_text(encoding="utf-8"))
        extra = {
            "02-people/users-crud.webm": "Users: view the list, open Add User, fill the form and save. Use Edit to update a user or delete to remove access.",
            "02-people/departments-crud.webm": "Departments: browse the list, create with a unique code, edit names or managers, and soft-delete with restore from Show Deleted.",
            "03-attendance-leave/leave-crud.webm": "Leave: apply from the form, track status in My Leaves, edit while pending, and delete if you cancel the request.",
            "04-projects/tasks-crud.webm": "Tasks: view the list, create a new task with assignee and due date, edit from the list, delete from task detail.",
            "05-work/my-works-crud.webm": "My Works: list your items, create a new one, edit details, and delete when complete.",
            "08-finance/expenses-crud.webm": "Expenses: submit a new claim with receipt, edit before approval, manager approves from pending list.",
            "07-communication/announcements-crud.webm": "Announcements: create company news, edit before publish, delete old posts from the list.",
        }
        cfg["tracks"].update(extra)
        narr_path.write_text(json.dumps(cfg, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    print(f"\nDone — {ok} videos, {skip} skipped")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
