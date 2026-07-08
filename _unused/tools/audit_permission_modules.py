#!/usr/bin/env python3
"""Compare has_module_access / require_module_access keys vs Permissions.php matrix."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "application"

KEY_RE = re.compile(
    r"(?:has_module_access|require_module_access)\s*\(\s*(?:\[([^\]]+)\]|['\"]([a-z0-9_]+)['\"])",
    re.I,
)


def keys_from_permissions_php() -> set[str]:
    text = (APP / "controllers/Permissions.php").read_text(encoding="utf-8", errors="replace")
    keys = set(re.findall(r"'([a-z0-9_]+)'\s*=>\s*'", text))
    keys |= set(re.findall(r"'([a-z0-9_]+)'\s*=>\s*array\s*\(", text))
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


def main() -> int:
    defined = keys_from_permissions_php()
    used = keys_from_codebase()
    missing = sorted(used - defined)
    unused = sorted(defined - used)
    print(f"Defined in Permissions.php: {len(defined)}")
    print(f"Used in application code:   {len(used)}")
    print(f"\nMISSING from permission matrix ({len(missing)}):")
    for k in missing:
        print(f"  - {k}")
    print(f"\nDefined but unused in code ({len(unused)}) — OK to keep for admin granularity")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
