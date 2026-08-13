#!/usr/bin/env python3
"""
Deep HTTP functional test: Clients / Projects / Releases / My Works History.

Usage (PowerShell):
  $env:OMS_TEST_LOGIN="..."
  $env:OMS_TEST_PASSWORD="..."
  python _unused/tools/test_history_deep_http.py
"""
from __future__ import annotations

import os
import re
import sys
import time
from pathlib import Path
from urllib.parse import urljoin

ROOT = Path(__file__).resolve().parents[2]
BASE = os.environ.get("OMS_BASE_URL", "http://localhost/Office_management_system/").rstrip("/") + "/"
OUT = ROOT / "_unused" / "tools" / "_pw_out"

PASS: list[str] = []
FAIL: list[str] = []


def ok(m: str) -> None:
    PASS.append(m)
    print(f"OK  {m}")


def fail(m: str) -> None:
    FAIL.append(m)
    print(f"FAIL {m}")


def extract_csrf(html: str) -> str:
    m = re.search(r'name=["\']ci_csrf_token["\']\s+value=["\']([^"\']+)["\']', html)
    if m:
        return m.group(1)
    m = re.search(r'name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']', html)
    return m.group(1) if m else ""


def has_php_error(html: str) -> str | None:
    for pat in (
        r"A PHP Error was encountered",
        r"Fatal error",
        r"Parse error",
        r"Uncaught Error",
        r"TypeError",
        r"Undefined variable",
        r"Call to undefined",
    ):
        if re.search(pat, html, re.I):
            return pat
    return None


def history_col_indices(html: str) -> tuple[int, int, int]:
    i_date = html.find("Date</th>")
    if i_date < 0:
        i_date = html.find(">Date<")
    i_comments = html.find("Comments</th>")
    if i_comments < 0:
        i_comments = html.find(">Comments<")
    i_by = html.find("Added By</th>")
    if i_by < 0:
        i_by = html.find(">Added By<")
    return i_date, i_comments, i_by


def check_history_ui(html: str, label: str, require_table: bool = False) -> None:
    err = has_php_error(html)
    if err:
        fail(f"{label}: PHP error ({err})")
        return
    if 'id="history"' in html or "id='history'" in html:
        ok(f"{label}: #history present")
    else:
        fail(f"{label}: #history missing")
    if "Save note" in html:
        ok(f"{label}: Save note present")
    else:
        fail(f"{label}: Save note missing")
    i_date, i_comments, i_by = history_col_indices(html)
    empty = "No history yet" in html
    if empty and not require_table:
        ok(f"{label}: empty state (No history yet)")
        return
    if i_date >= 0:
        ok(f"{label}: Date header")
    else:
        fail(f"{label}: Date header missing")
    if i_comments >= 0:
        ok(f"{label}: Comments header")
    else:
        fail(f"{label}: Comments header missing")
    if i_by >= 0:
        ok(f"{label}: Added By header")
    else:
        fail(f"{label}: Added By header missing")
    if i_date >= 0 and i_comments > i_date and i_by > i_comments:
        ok(f"{label}: column order Date -> Comments -> Added By")
    else:
        fail(f"{label}: column order wrong date={i_date} comments={i_comments} by={i_by}")


def first_id(html: str, patterns: list[str]) -> int | None:
    for pat in patterns:
        m = re.search(pat, html)
        if m:
            return int(m.group(1))
    return None


def post_note(s, add_url: str, view_url: str, note: str, label: str) -> None:
    r = s.get(view_url, timeout=60)
    csrf = extract_csrf(r.text)
    r = s.post(add_url, data={"ci_csrf_token": csrf, "note": note}, timeout=60, allow_redirects=True)
    err = has_php_error(r.text)
    if err:
        fail(f"{label}: note POST PHP error ({err})")
        (OUT / f"hist_{label}_note.html").write_text(r.text, encoding="utf-8", errors="replace")
        return
    shown = r.text
    if note in r.text:
        ok(f"{label}: note saved and shown")
    else:
        r2 = s.get(view_url, timeout=60)
        shown = r2.text
        if note in r2.text:
            ok(f"{label}: note saved and shown (after GET)")
        else:
            fail(f"{label}: note not shown")
            (OUT / f"hist_{label}_note.html").write_text(r2.text, encoding="utf-8", errors="replace")
            return
    check_history_ui(shown, f"{label} after note", require_table=True)

    csrf = extract_csrf(r.text) or extract_csrf(s.get(view_url, timeout=60).text)
    r_empty = s.post(
        add_url,
        data={"ci_csrf_token": csrf, "note": "   "},
        timeout=60,
        allow_redirects=True,
    )
    body = r_empty.text.lower()
    if "cannot be empty" in body or "history note cannot be empty" in r_empty.text:
        ok(f"{label}: empty note rejected")
    elif "alert-danger" in body:
        ok(f"{label}: empty note rejected (error alert)")
    else:
        fail(f"{label}: empty note not rejected")


def main() -> int:
    try:
        import requests
    except ImportError:
        import subprocess

        subprocess.check_call([sys.executable, "-m", "pip", "install", "requests"])
        import requests

    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("Set OMS_TEST_LOGIN and OMS_TEST_PASSWORD")
        return 2

    OUT.mkdir(parents=True, exist_ok=True)
    s = requests.Session()
    s.headers.update({"User-Agent": "OMS-HistoryDeepTest/1.0"})

    r = s.get(urljoin(BASE, "auth/login"), timeout=60)
    if r.status_code != 200:
        fail(f"login page HTTP {r.status_code}")
        return 1
    csrf = extract_csrf(r.text)
    payload = {"login": login_id, "password": password}
    if csrf:
        payload["ci_csrf_token"] = csrf
    r = s.post(urljoin(BASE, "auth/login"), data=payload, timeout=60, allow_redirects=True)
    r2 = s.get(urljoin(BASE, "dashboard"), timeout=60, allow_redirects=True)
    if "login" in r2.url.lower():
        fail("login failed")
        return 1
    ok(f"logged in as {login_id}")

    stamp = time.strftime("%H%M%S")
    pid = None

    # --- Clients ---
    r = s.get(urljoin(BASE, "clients"), timeout=60)
    cid = first_id(r.text, [r"clients/view/(\d+)", r"clients/edit/(\d+)"])
    if not cid:
        fail("no client id on list")
    else:
        view = urljoin(BASE, f"clients/view/{cid}")
        r = s.get(view, timeout=60)
        check_history_ui(r.text, "clients")
        post_note(s, urljoin(BASE, f"clients/add-comment/{cid}"), view, f"HTTP client note {stamp}", "clients")

    # --- Projects ---
    r = s.get(urljoin(BASE, "projects"), timeout=60)
    pid = first_id(r.text, [r"projects/(\d+)/edit", r'href="[^"]*projects/(\d+)"'])
    if not pid:
        fail("no project id on list")
    else:
        view = urljoin(BASE, f"projects/{pid}")
        r = s.get(view, timeout=60)
        check_history_ui(r.text, "projects")
        post_note(s, urljoin(BASE, f"projects/add-comment/{pid}"), view, f"HTTP project note {stamp}", "projects")

    # --- Releases ---
    r = s.get(urljoin(BASE, "releases"), timeout=60)
    rid = first_id(r.text, [r"releases/view/(\d+)", r"releases/edit/(\d+)"])
    if not rid and pid:
        cr = s.get(urljoin(BASE, "releases/create"), timeout=60)
        csrf = extract_csrf(cr.text)
        r = s.post(
            urljoin(BASE, "releases/create"),
            data={
                "ci_csrf_token": csrf,
                "project_id": str(pid),
                "version": f"DT.{stamp}",
                "title": f"HTTP History Release {stamp}",
                "description": "deep test",
                "status": "planned",
            },
            timeout=60,
            allow_redirects=True,
        )
        rid = first_id(r.url + " " + r.text, [r"releases/view/(\d+)"])
        if rid:
            ok(f"releases: created view/{rid}")
        else:
            fail(f"releases: create did not land on view url={r.url}")
    if not rid:
        fail("no release id on list")
    else:
        view = urljoin(BASE, f"releases/view/{rid}")
        r = s.get(view, timeout=60)
        check_history_ui(r.text, "releases")
        post_note(s, urljoin(BASE, f"releases/add-comment/{rid}"), view, f"HTTP release note {stamp}", "releases")

    # --- My Works ---
    r = s.get(urljoin(BASE, "my-works?view=list&embed=1"), timeout=60)
    wid = first_id(r.text, [r"my-works/(\d+)/edit", r"my-works/(\d+)"])
    if not wid:
        cr = s.get(urljoin(BASE, "my-works/create"), timeout=60)
        csrf = extract_csrf(cr.text)
        r = s.post(
            urljoin(BASE, "my-works/create"),
            data={
                "ci_csrf_token": csrf,
                "title": f"HTTP History Work {stamp}",
                "status": "new",
            },
            timeout=60,
            allow_redirects=True,
        )
        wid = first_id(r.url + " " + r.text, [r"my-works/(\d+)"])
        if wid:
            ok(f"my_works: created view/{wid}")
        else:
            fail(f"my_works: create did not land on view url={r.url}")
            (OUT / "hist_my_works_create.html").write_text(r.text, encoding="utf-8", errors="replace")
    if not wid:
        fail("no my-works id on list")
    else:
        view = urljoin(BASE, f"my-works/{wid}")
        r = s.get(view, timeout=60)
        check_history_ui(r.text, "my_works")
        post_note(s, urljoin(BASE, f"my-works/{wid}/comment"), view, f"HTTP work note {stamp}", "my_works")

    print(f"\n{len(PASS)} passed, {len(FAIL)} failed")
    if FAIL:
        print("Failed:")
        for m in FAIL:
            print(" -", m)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
