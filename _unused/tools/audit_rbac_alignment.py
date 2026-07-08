#!/usr/bin/env python3
"""Audit RBAC: code keys vs Permissions matrix vs controller access map."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "application"

KEY_RE = re.compile(
    r"(?:has_module_access|require_module_access|require_meal_access|meal_role_has|meal_can_access|get_controller_module_access_keys|"
    r"require_controller_access|can_access_any_module)\s*\(\s*(?:\[([^\]]+)\]|['\"]([a-z0-9_]+)['\"])",
    re.I,
)
MAP_KEY_RE = re.compile(r"'([a-z0-9_]+)'\s*=>\s*\[")
PERM_KEY_RE = re.compile(r"'([a-z0-9_]+)'\s*=>\s*'")
PERM_ARRAY_KEY_RE = re.compile(r"'([a-z0-9_]+)'\s*=>\s*array\s*\(")


def keys_from_permissions_php() -> set[str]:
    text = (APP / "controllers/Permissions.php").read_text(encoding="utf-8", errors="replace")
    keys = set(PERM_KEY_RE.findall(text))
    keys |= set(PERM_ARRAY_KEY_RE.findall(text))
    return keys


def keys_from_codebase() -> set[str]:
    found: set[str] = set()
    for path in APP.rglob("*.php"):
        if "vendor" in path.parts:
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for m in KEY_RE.finditer(text):
            if m.group(2):
                found.add(m.group(2).lower())
            if m.group(1):
                for part in re.findall(r"['\"]([a-z0-9_]+)['\"]", m.group(1)):
                    found.add(part.lower())
    return found


def keys_from_controller_map() -> set[str]:
    text = (APP / "helpers/permission_helper.php").read_text(encoding="utf-8", errors="replace")
    start = text.find("function get_controller_module_access_map()")
    if start < 0:
        return set()
    chunk = text[start : start + 20000]
    return set(MAP_KEY_RE.findall(chunk))


def main() -> int:
    defined = keys_from_permissions_php()
    used = keys_from_codebase()
    map_controllers = keys_from_controller_map()

    map_keys: set[str] = set()
    helper = (APP / "helpers/permission_helper.php").read_text(encoding="utf-8", errors="replace")
    start = helper.find("function get_controller_module_access_map()")
    chunk = helper[start : start + 25000]
    for block in re.findall(r"=>\s*\[([^\]]+)\]", chunk, re.S):
        for k in re.findall(r"'([a-z0-9_]+)'", block):
            map_keys.add(k.lower())

    missing_matrix = sorted(used - defined)
    missing_map = sorted(used - map_keys - {"true", "false"})
    map_not_matrix = sorted(map_keys - defined)

    print("=== RBAC alignment audit ===")
    print(f"Permissions matrix keys: {len(defined)}")
    print(f"Keys used in code:        {len(used)}")
    print(f"Keys in controller map:   {len(map_keys)}")
    print(f"Controllers in map:       {len(map_controllers)}")
    print()
    print(f"Used in code but MISSING from Permissions.php ({len(missing_matrix)}):")
    for k in missing_matrix:
        print(f"  - {k}")
    print()
    print(f"In controller map but MISSING from Permissions.php ({len(map_not_matrix)}):")
    for k in map_not_matrix:
        print(f"  - {k}")
    print()
    print(f"Used in code but not in any controller map entry ({len(missing_map)}):")
    for k in missing_map[:40]:
        print(f"  - {k}")
    if len(missing_map) > 40:
        print(f"  ... and {len(missing_map) - 40} more")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
