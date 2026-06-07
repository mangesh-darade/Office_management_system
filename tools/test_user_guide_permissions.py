#!/usr/bin/env python3
"""Verify in-app user guide filters topics by module permissions."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
HELPER = ROOT / "application/helpers/user_guide_helper.php"


def read(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="replace")


def ok(msg: str) -> None:
    print(f"  [OK] {msg}")
    passed.append(msg)


def fail(msg: str) -> None:
    print(f"  [FAIL] {msg}")
    failures.append(msg)


passed: list[str] = []
failures: list[str] = []


def main() -> int:
    print("=== User guide permission filter check ===\n")
    if not HELPER.is_file():
        fail(f"Missing {HELPER}")
        return 1

    src = read(HELPER)

    required = [
        "user_guide_all_modules",
        "user_guide_user_can_access_module",
        "user_guide_public_module",
        "'always' => true",
        "'access_fn' => 'meal_has_any_access'",
        "'access_keys'",
        "user_guide_modules()",
        "user_guide_user_can_access_module($module)",
    ]
    for token in required:
        if token in src:
            ok(f"Helper defines: {token}")
        else:
            fail(f"Helper missing: {token}")

    for mod_id, slug in [
        ("01", "01-login-dashboard"),
        ("12", "12-office-meals"),
    ]:
        if slug in src and f"'id' => '{mod_id}'" in src:
            ok(f"Module {mod_id} registered ({slug})")
        else:
            fail(f"Module {mod_id} not registered")

    guide_ctrl = read(ROOT / "application/controllers/Guide.php")
    if "user_guide_modules()" in guide_ctrl:
        ok("Guide controller uses filtered user_guide_modules()")
    else:
        fail("Guide controller does not use user_guide_modules()")

    sidebar = read(ROOT / "application/views/partials/sidebar_guide_nav.php")
    if "user_guide_modules()" in sidebar and "empty($guide_modules)" in sidebar:
        ok("Sidebar guide nav hides when no accessible topics")
    else:
        fail("Sidebar guide nav missing permission filter")

    print(f"\nPASSED ({len(passed)})")
    if failures:
        print(f"FAILURES ({len(failures)}):")
        for f in failures:
            print(f"  {f}")
        return 1
    print("\nResult: OK")
    return 0


if __name__ == "__main__":
    sys.exit(main())
