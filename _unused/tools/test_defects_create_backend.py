#!/usr/bin/env python3
"""Static backend checks for Defects create (client + assign validation)."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / "application"
FAIL: list[str] = []
PASS: list[str] = []


def ok(m: str) -> None:
    PASS.append(m)
    print(f"OK  {m}")


def fail(m: str) -> None:
    FAIL.append(m)
    print(f"FAIL {m}")


def read(rel: str) -> str:
    return (APP / rel).read_text(encoding="utf-8", errors="replace")


def main() -> int:
    model = read("models/Defect_model.php")
    ctrl = read("controllers/Defects.php")
    form = read("views/defects/form.php")
    css = (ROOT / "assets/css/defects-form.css").read_text(encoding="utf-8", errors="replace")

    for token in (
        "client_options",
        "project_client_id",
        "is_project_accessible",
        "is_user_assignable",
        "release_belongs_to_project",
        "task_belongs_to_project",
        "client_id",
    ):
        if token in model:
            ok(f"model:{token}")
        else:
            fail(f"model missing:{token}")

    for token in (
        "client_options()",
        "selected_client_id",
        "is_project_accessible",
        "project_client_id",
        "is_user_assignable",
        "release_belongs_to_project",
        "task_belongs_to_project",
        "Untitled defect",
    ):
        if token in ctrl:
            ok(f"controller:{token}")
        else:
            fail(f"controller missing:{token}")

    # Mandatory blockers should be gone from soft payload path
    for token in (
        "Please select a valid project",
        "Title is required",
        "Selected project does not belong to that client",
        "Selected assignee is invalid",
    ):
        if token in ctrl:
            fail(f"controller still blocks with:{token}")
        else:
            ok(f"controller soft:{token}")

    for token in (
        "defectClientId",
        "defectAssignedTo",
        "defectProjectId",
        "data-client-id",
        "defect-form-actions",
    ):
        if token in form:
            ok(f"view:{token}")
        else:
            fail(f"view missing:{token}")

    if "position: sticky" in css or "position: fixed" in css:
        ok("css:sticky/fixed actions")
    else:
        fail("css missing sticky/fixed actions")

    print(f"\n{len(PASS)} passed, {len(FAIL)} failed")
    return 1 if FAIL else 0


if __name__ == "__main__":
    raise SystemExit(main())
