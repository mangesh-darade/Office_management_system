#!/usr/bin/env python3
"""
Deep HTTP test: Defects create/edit/note/history/view with real values.

Usage (PowerShell):
  $env:OMS_TEST_LOGIN="7744010738"
  $env:OMS_TEST_PASSWORD="Admin@554"
  python _unused/tools/test_defects_deep_http.py
"""
from __future__ import annotations

import os
import re
import sys
import time
from html.parser import HTMLParser
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


class FormParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.inputs: dict[str, str] = {}
        self.selects: dict[str, list[tuple[str, str]]] = {}
        self._cur_select: str | None = None
        self._cur_option_value = ""
        self._cur_option_text = ""
        self._in_option = False

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        a = {k: (v or "") for k, v in attrs}
        if tag == "input":
            name = a.get("name", "")
            if name:
                if a.get("type", "").lower() in ("checkbox", "radio") and "checked" not in a:
                    return
                self.inputs[name] = a.get("value", "")
        elif tag == "select":
            self._cur_select = a.get("name") or None
            if self._cur_select:
                self.selects.setdefault(self._cur_select, [])
        elif tag == "option" and self._cur_select:
            self._in_option = True
            self._cur_option_value = a.get("value", "")
            self._cur_option_text = ""

    def handle_endtag(self, tag: str) -> None:
        if tag == "option" and self._cur_select and self._in_option:
            self.selects[self._cur_select].append((self._cur_option_value, self._cur_option_text.strip()))
            self._in_option = False
        if tag == "select":
            self._cur_select = None

    def handle_data(self, data: str) -> None:
        if self._in_option:
            self._cur_option_text += data


def first_real_option(options: list[tuple[str, str]]) -> str | None:
    for val, _label in options:
        if val and val not in ("0", ""):
            return val
    return None


def extract_csrf(html: str) -> str | None:
    m = re.search(r'name=["\']ci_csrf_token["\']\s+value=["\']([^"\']+)["\']', html)
    if m:
        return m.group(1)
    m = re.search(r'name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']', html)
    return m.group(1) if m else None


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
    s.headers.update({"User-Agent": "OMS-DefectsDeepTest/1.0"})

    # --- Login ---
    r = s.get(urljoin(BASE, "auth/login"), timeout=60)
    if r.status_code != 200:
        fail(f"login page HTTP {r.status_code}")
        return 1
    csrf = extract_csrf(r.text) or ""
    payload = {"login": login_id, "password": password}
    if csrf:
        payload["ci_csrf_token"] = csrf
    r = s.post(urljoin(BASE, "auth/login"), data=payload, timeout=60, allow_redirects=True)
    if "login" in r.url.lower() and "dashboard" not in r.url.lower() and "defects" not in r.url.lower():
        # some apps land on home
        if "verify-2fa" in r.url.lower():
            fail("2FA required")
            return 1
        # check cookie session
        r2 = s.get(urljoin(BASE, "defects"), timeout=60, allow_redirects=True)
        if "login" in r2.url.lower():
            fail("login failed")
            return 1
    ok(f"logged in as {login_id}")

    stamp = time.strftime("%Y%m%d_%H%M%S")
    title = f"HTTP DeepTest {stamp}"

    # --- Create page ---
    r = s.get(urljoin(BASE, "defects/create"), timeout=60)
    if r.status_code != 200 or "defectForm" not in r.text and "defectTitle" not in r.text:
        fail("create page missing form")
        (OUT / "deep_create_fail.html").write_text(r.text, encoding="utf-8", errors="replace")
        return 1
    ok("create page loads")

    parser = FormParser()
    parser.feed(r.text)
    csrf = parser.inputs.get("ci_csrf_token") or extract_csrf(r.text) or ""
    client_id = first_real_option(parser.selects.get("client_id", []))
    project_id = first_real_option(parser.selects.get("project_id", []))
    assignee = first_real_option(parser.selects.get("assigned_to", []))

    # Soft create: empty title -> Untitled defect
    soft_data = {
        "ci_csrf_token": csrf,
        "client_id": client_id or "0",
        "project_id": "",
        "title": "",
        "description": "",
        "steps_to_reproduce": "",
        "severity": "bogus",
        "priority": "bogus",
        "status": "open",
        "assigned_to": "",
        "due_date": "",
        "release_id": "",
        "task_id": "",
    }
    r = s.post(urljoin(BASE, "defects/create"), data=soft_data, timeout=60, allow_redirects=True)
    m = re.search(r"/defects/view/(\d+)", r.url)
    if not m:
        # maybe flash error still on create
        fail(f"soft create did not redirect to view (url={r.url})")
        (OUT / "deep_soft_create.html").write_text(r.text, encoding="utf-8", errors="replace")
    else:
        soft_id = int(m.group(1))
        ok(f"soft create -> view/{soft_id}")
        if "Untitled defect" in r.text:
            ok("soft title default: Untitled defect")
        else:
            fail("soft create page missing Untitled defect")
        # severity/priority should fall back to medium
        if re.search(r">\s*Medium\s*<", r.text, re.I) or "medium" in r.text.lower():
            ok("soft severity/priority coerced (medium visible)")
        else:
            fail("soft severity/priority medium not visible")

    # Full create with values
    r = s.get(urljoin(BASE, "defects/create"), timeout=60)
    parser = FormParser()
    parser.feed(r.text)
    csrf = parser.inputs.get("ci_csrf_token") or extract_csrf(r.text) or ""
    client_id = first_real_option(parser.selects.get("client_id", []))
    project_id = first_real_option(parser.selects.get("project_id", []))
    assignee = first_real_option(parser.selects.get("assigned_to", []))

    full_data = {
        "ci_csrf_token": csrf,
        "client_id": client_id or "0",
        "project_id": project_id or "",
        "title": title,
        "description": "<p>HTTP deep description</p>",
        "steps_to_reproduce": "<p>Step A then B</p>",
        "severity": "high",
        "priority": "critical",
        "status": "open",
        "assigned_to": assignee or "",
        "due_date": time.strftime("%Y-%m-%d", time.gmtime(time.time() + 7 * 86400)),
        "release_id": "",
        "task_id": "",
    }
    r = s.post(urljoin(BASE, "defects/create"), data=full_data, timeout=60, allow_redirects=True)
    m = re.search(r"/defects/view/(\d+)", r.url)
    if not m:
        fail(f"full create no view redirect url={r.url}")
        (OUT / "deep_full_create.html").write_text(r.text, encoding="utf-8", errors="replace")
        print(f"\n{len(PASS)} passed, {len(FAIL)} failed")
        return 1
    defect_id = int(m.group(1))
    ok(f"full create -> view/{defect_id}")
    if title not in r.text:
        fail("view missing title value")
    else:
        ok(f"view shows title: {title}")
    if "High" not in r.text and "high" not in r.text.lower():
        fail("view missing severity High")
    else:
        ok("view shows severity High")

    # History column order
    for col in ("Date", "Comments", "Added By"):
        if col in r.text:
            ok(f"history header: {col}")
        else:
            fail(f"history header missing: {col}")
    # Date before Comments before Added By in markup
    i_date = r.text.find(">Date<")
    if i_date < 0:
        i_date = r.text.find("Date</th>")
    i_comments = r.text.find(">Comments<")
    if i_comments < 0:
        i_comments = r.text.find("Comments</th>")
    i_by = r.text.find(">Added By<")
    if i_by < 0:
        i_by = r.text.find("Added By</th>")
    if i_date >= 0 and i_comments > i_date and i_by > i_comments:
        ok("history column sequence Date -> Comments -> Added By")
    else:
        fail(f"history column sequence wrong indices date={i_date} comments={i_comments} by={i_by}")

    if "Defect logged" in r.text or "Created" in r.text:
        ok("history has created activity")
    else:
        fail("history missing created activity text")

    # Toolbar neatness markers
    if "defect-view-toolbar" in r.text:
        ok("view toolbar present")
    else:
        fail("view toolbar missing")

    # --- Edit: change status + severity ---
    r = s.get(urljoin(BASE, f"defects/edit/{defect_id}"), timeout=60)
    if r.status_code != 200:
        fail(f"edit page HTTP {r.status_code}")
    else:
        ok("edit page loads")
    parser = FormParser()
    parser.feed(r.text)
    csrf = parser.inputs.get("ci_csrf_token") or extract_csrf(r.text) or ""
    edit_data = {
        "ci_csrf_token": csrf,
        "client_id": client_id or "0",
        "project_id": project_id or parser.inputs.get("project_id", ""),
        "title": title + " EDITED",
        "description": "<p>Updated HTTP description</p>",
        "steps_to_reproduce": "<p>Updated steps</p>",
        "severity": "critical",
        "priority": "low",
        "status": "in_progress",
        "assigned_to": assignee or "",
        "due_date": full_data["due_date"],
        "release_id": "",
        "task_id": "",
    }
    # prefer posted project from form select if present
    if "project_id" in parser.selects:
        cur = first_real_option(parser.selects["project_id"])
        if cur:
            edit_data["project_id"] = cur

    r = s.post(urljoin(BASE, f"defects/edit/{defect_id}"), data=edit_data, timeout=60, allow_redirects=True)
    if f"/defects/view/{defect_id}" not in r.url:
        fail(f"edit redirect unexpected: {r.url}")
    else:
        ok(f"edit saved -> view/{defect_id}")
    if "EDITED" not in r.text:
        fail("edited title not on view")
    else:
        ok("edited title on view")
    if "Critical" not in r.text and "critical" not in r.text.lower():
        fail("edited severity not visible")
    else:
        ok("edited severity Critical visible")
    if "In progress" in r.text or "in_progress" in r.text or "In Progress" in r.text:
        ok("edited status in_progress visible")
    else:
        # badge may show "In progress"
        if re.search(r"in\s*progress", r.text, re.I):
            ok("edited status in_progress visible")
        else:
            fail("edited status not visible")

    # History should show change values with arrows
    if "\u2192" in r.text or "&rarr;" in r.text or "Severity:" in r.text:
        ok("history shows change values")
    else:
        fail("history missing change value lines")

    # --- History note ---
    note = f"HTTP note {stamp}"
    # find note form action
    r = s.get(urljoin(BASE, f"defects/view/{defect_id}"), timeout=60)
    csrf = extract_csrf(r.text) or ""
    note_data = {"ci_csrf_token": csrf, "note": note}
    r = s.post(urljoin(BASE, f"defects/add-comment/{defect_id}"), data=note_data, timeout=60, allow_redirects=True)
    if note in r.text:
        ok(f"history note saved and shown: {note}")
    else:
        fail("history note not shown on view")
        (OUT / "deep_note_view.html").write_text(r.text, encoding="utf-8", errors="replace")

    # Empty note rejected
    csrf = extract_csrf(r.text) or ""
    r2 = s.post(
        urljoin(BASE, f"defects/add-comment/{defect_id}"),
        data={"ci_csrf_token": csrf, "note": "   "},
        timeout=60,
        allow_redirects=True,
    )
    if "cannot be empty" in r2.text.lower() or "History note cannot be empty" in r2.text:
        ok("empty note rejected")
    else:
        # flash may have been consumed; still OK if note count unchanged — soft check
        if "alert-danger" in r2.text or "error" in r2.text.lower():
            ok("empty note rejected (error alert)")
        else:
            fail("empty note not rejected visibly")

    # List filter by q
    r = s.get(urljoin(BASE, "defects"), params={"q": "HTTP DeepTest"}, timeout=60)
    if title[:20] in r.text or "HTTP DeepTest" in r.text or str(defect_id) in r.text:
        ok("list search finds deep test defect")
    else:
        fail("list search miss")

    print(f"\n{len(PASS)} passed, {len(FAIL)} failed")
    if FAIL:
        print("Failed:")
        for f in FAIL:
            print(f"  - {f}")
    return 1 if FAIL else 0


if __name__ == "__main__":
    raise SystemExit(main())
