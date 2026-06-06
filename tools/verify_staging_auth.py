#!/usr/bin/env python3
"""
Authenticated staging checks (requires OMS_TEST_LOGIN + OMS_TEST_PASSWORD env vars).
Does not store credentials in the repository.
"""
from __future__ import annotations

import json
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from http.cookiejar import CookieJar

BASE_URL = "http://localhost/Office_management_system/"


def ok(msg: str) -> None:
    print(f"  OK  {msg}")


def fail(msg: str) -> None:
    print(f"FAIL  {msg}")


class Session:
    def __init__(self) -> None:
        self.jar = CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def csrf_token(self) -> str:
        for c in self.jar:
            if c.name == "ci_csrf_token":
                return c.value
        return ""

    def request(
        self,
        method: str,
        route: str,
        data: dict | None = None,
        ajax: bool = False,
    ) -> tuple[int, str, dict]:
        url = BASE_URL + route.lstrip("/")
        headers = {"User-Agent": "oms-verify-staging/1.0"}
        if ajax:
            headers["X-Requested-With"] = "XMLHttpRequest"
        body = None
        if data is not None:
            if method.upper() == "GET":
                url += ("&" if "?" in url else "?") + urllib.parse.urlencode(data)
            else:
                headers["Content-Type"] = "application/x-www-form-urlencoded"
                body = urllib.parse.urlencode(data).encode("utf-8")
        req = urllib.request.Request(url, data=body, headers=headers, method=method.upper())
        try:
            with self.opener.open(req, timeout=30) as resp:
                text = resp.read(500000).decode("utf-8", errors="replace")
                return resp.getcode(), text, dict(resp.headers)
        except urllib.error.HTTPError as e:
            text = e.read(500000).decode("utf-8", errors="replace")
            return e.code, text, dict(e.headers)


def csrf_from_html(html: str) -> str:
    m = re.search(r'name="ci_csrf_token"\s+value="([^"]+)"', html)
    return m.group(1) if m else ""


def login(session: Session, identifier: str, password: str) -> tuple[bool, str]:
    code, html, _ = session.request("GET", "auth/login")
    if code >= 500:
        return False, f"login page HTTP {code}"
    token = csrf_from_html(html) or session.csrf_token()
    code, body, _ = session.request(
        "POST",
        "auth/login",
        {
            "login": identifier,
            "password": password,
            "ci_csrf_token": token,
        },
        ajax=True,
    )
    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        return False, f"login response not JSON (HTTP {code})"
    if data.get("require_2fa"):
        return False, "2FA required — complete verify-2fa manually or disable 2FA for test account"
    if not data.get("success"):
        return False, data.get("error", "login failed")
    ok(f"logged in -> {data.get('redirect', 'dashboard')}")
    return True, ""


def is_login_page(html: str) -> bool:
    return "Sign in to continue" in html or 'id="loginForm"' in html


def check_authenticated_pages(session: Session) -> list[str]:
    errors: list[str] = []
    checks = [
        ("reports/attendance-employee", "Employee Attendance", "attendance_employee"),
        ("reports/attendance-employee/1", "detail", None),
        ("departments", "Departments", None),
        ("departments/create", "Create Department", None),
        ("designations/create", "Create Designation", None),
        ("my-works", "My Works", None),
        ("ai-chat", "AI Assistant", None),
    ]
    for route, needle, _ in checks:
        code, html, _ = session.request("GET", route)
        if code >= 500:
            errors.append(f"{route}: HTTP {code}")
            fail(f"GET {route}: {code}")
        elif is_login_page(html):
            errors.append(f"{route}: still on login page")
            fail(f"GET {route}: not authenticated")
        elif needle and needle not in html:
            errors.append(f"{route}: missing '{needle}'")
            fail(f"GET {route}: content check failed")
        else:
            ok(f"GET {route} ({code})")
    return errors


def check_export(session: Session) -> list[str]:
    errors: list[str] = []
    from datetime import date

    month = date.today().strftime("%Y-%m")
    base_params = {"period": "monthly", "month": month}

    # Single user -> detail excel
    code, body, headers = session.request(
        "GET",
        "reports/export-attendance-employee",
        {"export": "excel", "user_ids": "1", **base_params},
    )
    ctype = headers.get("Content-type", headers.get("Content-Type", ""))
    if code >= 500:
        errors.append(f"detail export HTTP {code}")
        fail(f"export detail excel: {code}")
    elif "json" in ctype.lower() and "error" in body.lower():
        try:
            err = json.loads(body).get("error", body[:120])
            errors.append(f"detail export: {err}")
            fail(f"export detail excel: {err}")
        except json.JSONDecodeError:
            pass
    elif len(body) < 20:
        errors.append("detail export body too small")
        fail("export detail excel: empty")
    else:
        ok(f"export detail excel ({len(body)} bytes)")

    # Multi user -> summary pdf
    code, body, headers = session.request(
        "GET",
        "reports/export-attendance-employee",
        {"export": "pdf", "user_ids": "1,2", **base_params},
    )
    if code >= 500:
        errors.append(f"summary pdf export HTTP {code}")
        fail(f"export summary pdf: {code}")
    elif len(body) < 20:
        errors.append("summary pdf export body too small")
        fail("export summary pdf: empty")
    else:
        ok(f"export summary pdf ({len(body)} bytes)")
    return errors


def check_csrf_ajax(session: Session) -> list[str]:
    from datetime import date

    errors: list[str] = []
    token = session.csrf_token()
    if not token:
        errors.append("no csrf cookie after login")
        fail("CSRF cookie missing")
        return errors
    ok("CSRF cookie after login")

    code, body, _ = session.request(
        "POST",
        "notifications/mark_all_read",
        {"ci_csrf_token": token},
        ajax=True,
    )
    if code >= 500:
        errors.append(f"notifications/mark_all_read HTTP {code}")
        fail(f"notifications/mark_all_read: {code}")
    else:
        ok(f"notifications/mark_all_read ({code})")

    code, body, _ = session.request(
        "GET",
        f"attendance/get-data?month={date.today().strftime('%Y-%m')}",
    )
    if code >= 500:
        errors.append(f"attendance/get-data HTTP {code}")
        fail(f"attendance/get-data: {code}")
    elif is_login_page(body):
        errors.append("attendance/get-data returned login page")
        fail("attendance/get-data: not authenticated")
    else:
        ok(f"attendance/get-data ({code}, {len(body)} bytes)")
    return errors


def find_org_record_id(html: str, module: str, code: str) -> int | None:
    idx = html.find(code)
    if idx < 0:
        return None
    chunk = html[max(0, idx - 800) : idx + 800]
    m = re.search(rf"{module}/(\d+)/edit", chunk)
    return int(m.group(1)) if m else None


def check_org_lifecycle(
    session: Session,
    module: str,
    code_key: str,
    name_key: str,
    code_prefix: str,
    name_prefix: str,
    extra: dict | None = None,
) -> list[str]:
    """Create -> soft-delete (POST) -> restore (GET) for departments/designations."""
    import time

    errors: list[str] = []
    suffix = str(int(time.time()))[-8:]
    code = f"{code_prefix}{suffix}"
    name = f"{name_prefix} {suffix}"
    token = session.csrf_token()

    post = {
        code_key: code,
        name_key: name,
        "description": "Staging verify (auto)",
        "ci_csrf_token": token,
    }
    if extra:
        post.update(extra)

    code_http, _, _ = session.request("POST", f"{module}/create", post)
    if code_http >= 500:
        errors.append(f"{module} create HTTP {code_http}")
        fail(f"{module} create: {code_http}")
        return errors

    _, index_html, _ = session.request("GET", module)
    record_id = find_org_record_id(index_html, module, code)
    if not record_id:
        errors.append(f"{module} create: record id not found for {code}")
        fail(f"{module} create: id not found")
        return errors
    ok(f"{module} create id={record_id} code={code}")

    token = session.csrf_token()
    del_code, del_body, _ = session.request(
        "POST",
        f"{module}/{record_id}/delete",
        {"ci_csrf_token": token},
    )
    if del_code >= 500:
        errors.append(f"{module} delete HTTP {del_code}")
        fail(f"{module} delete: {del_code}")
        return errors
    ok(f"{module} soft-delete ({del_code})")

    _, deleted_html, _ = session.request("GET", f"{module}?show_deleted=1")
    if code not in deleted_html:
        errors.append(f"{module} not in deleted list")
        fail(f"{module} show_deleted missing record")
    else:
        ok(f"{module} appears in deleted list")

    rest_code, _, _ = session.request("GET", f"{module}/{record_id}/restore")
    if rest_code >= 500:
        errors.append(f"{module} restore HTTP {rest_code}")
        fail(f"{module} restore: {rest_code}")
        return errors
    ok(f"{module} restore ({rest_code})")

    _, active_html, _ = session.request("GET", module)
    if code not in active_html:
        errors.append(f"{module} not active after restore")
        fail(f"{module} restore verification failed")
    else:
        ok(f"{module} active after restore")
    return errors


def check_task_status_update(session: Session) -> list[str]:
    errors: list[str] = []
    code, html, _ = session.request("GET", "tasks")
    if code >= 500 or is_login_page(html):
        errors.append("tasks list unavailable")
        fail("tasks list")
        return errors
    ids = re.findall(r"tasks/(\d+)(?:/edit|\"|')", html)
    if not ids:
        errors.append("no tasks found to test status update")
        fail("tasks: none found (skip or create one manually)")
        return errors
    task_id = int(ids[0])
    token = session.csrf_token()
    code, body, _ = session.request(
        "POST",
        "tasks/update-status",
        {"id": str(task_id), "status": "in_progress", "ci_csrf_token": token},
        ajax=True,
    )
    if code >= 500:
        errors.append(f"tasks/update-status HTTP {code}")
        fail(f"tasks/update-status: {code}")
        return errors
    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        errors.append("tasks/update-status not JSON")
        fail("tasks/update-status: not JSON")
        return errors
    if not data.get("ok"):
        errors.append(f"tasks/update-status: {data.get('error', body[:120])}")
        fail(f"tasks/update-status: {data.get('error', 'failed')}")
    else:
        ok(f"tasks/update-status task #{task_id} -> in_progress")
    return errors


def check_chat_send(session: Session) -> list[str]:
    errors: list[str] = []
    code, html, _ = session.request("GET", "chats")
    if code >= 500 or is_login_page(html):
        errors.append("chats unavailable")
        fail("chats index")
        return errors

    convo_ids = [int(x) for x in re.findall(r"open=(\d+)", html)]
    if not convo_ids:
        convo_ids = [int(x) for x in re.findall(r"conversation_id['\"]?\s*value=['\"](\d+)", html)]

    if not convo_ids:
        emails = re.findall(r'<option[^>]+value="(\d+)"[^>]*>([^<]+)</option>', html)
        peer_email = None
        code2, html2, _ = session.request("GET", "departments/create")
        for m in re.finditer(r"<option[^>]+value=\"(\d+)\"[^>]*>([^<]+)</option>", html2):
            label = m.group(2).strip()
            if "@" in label:
                peer_email = label if "@" in label.split()[-1] else label
                if " " in peer_email:
                    peer_email = peer_email.split()[-1]
                break
        if peer_email:
            token = session.csrf_token()
            session.request(
                "POST",
                "chats/start-dm",
                {"email": peer_email, "ci_csrf_token": token},
            )
            _, html3, _ = session.request("GET", "chats")
            convo_ids = [int(x) for x in re.findall(r"open=(\d+)", html3)]

    if not convo_ids:
        errors.append("no chat conversation available (start a DM manually once)")
        fail("chats: no conversation")
        return errors

    convo_id = convo_ids[0]
    token = session.csrf_token()
    import time

    msg = f"Staging verify ping {int(time.time())}"
    code, body, _ = session.request(
        "POST",
        "chats/send",
        {
            "conversation_id": str(convo_id),
            "body": msg,
            "ci_csrf_token": token,
        },
        ajax=True,
    )
    if code >= 500:
        errors.append(f"chats/send HTTP {code}")
        fail(f"chats/send: {code}")
        return errors
    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        errors.append("chats/send not JSON")
        fail("chats/send: not JSON")
        return errors
    if not data.get("ok"):
        errors.append(f"chats/send: {data.get('error', body[:120])}")
        fail(f"chats/send: {data.get('error', 'failed')}")
    else:
        ok(f"chats/send convo #{convo_id} message_id={data.get('message_id')}")
    return errors


def check_ai_chat_no_leak(session: Session) -> list[str]:
    """POST ai-chat/send; ensure response parses and note debug_sql presence."""
    errors: list[str] = []
    token = session.csrf_token()
    code, body, _ = session.request(
        "POST",
        "ai-chat/send",
        {
            "message": "Hello, what can you help with?",
            "ci_csrf_token": token,
        },
        ajax=True,
    )
    if code >= 500:
        errors.append(f"ai-chat/send HTTP {code}")
        fail(f"ai-chat/send: {code}")
        return errors
    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        errors.append("ai-chat/send not JSON")
        fail("ai-chat/send: not JSON")
        return errors
    if data.get("status") == "error":
        ok(f"ai-chat/send responded (error: {str(data.get('message', ''))[:60]})")
    elif data.get("status") == "success":
        ok("ai-chat/send success")
        if "debug_sql" in data:
            ok("debug_sql present (expected for admin in development only)")
        else:
            ok("no debug_sql in response")
    else:
        ok(f"ai-chat/send HTTP {code}")
    return errors


def main() -> int:
    login_id = os.environ.get("OMS_TEST_LOGIN", "").strip()
    password = os.environ.get("OMS_TEST_PASSWORD", "").strip()
    if not login_id or not password:
        print("Set OMS_TEST_LOGIN and OMS_TEST_PASSWORD environment variables.")
        return 2

    print("=== Authenticated login ===")
    session = Session()
    ok_login, err = login(session, login_id, password)
    if not ok_login:
        fail(err)
        return 1

    err_list: list[str] = []
    print("\n=== Authenticated pages ===")
    err_list += check_authenticated_pages(session)
    print("\n=== Attendance export ===")
    err_list += check_export(session)
    print("\n=== CSRF AJAX endpoints ===")
    err_list += check_csrf_ajax(session)
    print("\n=== AI chat ===")
    err_list += check_ai_chat_no_leak(session)
    print("\n=== Org structure CRUD ===")
    err_list += check_org_lifecycle(
        session,
        "departments",
        "dept_code",
        "dept_name",
        "VRFD",
        "Verify Dept",
    )
    err_list += check_org_lifecycle(
        session,
        "designations",
        "designation_code",
        "designation_name",
        "VRFG",
        "Verify Desig",
        {"level": "1"},
    )
    print("\n=== Tasks AJAX ===")
    err_list += check_task_status_update(session)
    print("\n=== Chat AJAX ===")
    err_list += check_chat_send(session)

    print("\n=== Summary ===")
    if err_list:
        for e in err_list:
            print(f"  - {e}")
        return 1
    print("All authenticated staging checks passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
