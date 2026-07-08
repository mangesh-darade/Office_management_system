#!/usr/bin/env python3
"""Generate route/screen index for user guide from routes.php."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
routes_text = (ROOT / "application/config/routes.php").read_text(encoding="utf-8")
pat = re.compile(r"\$route\['([^']+)'\]\s*=\s*'([^']+)'")
groups: dict[str, list[tuple[str, str]]] = {}
for m in pat.finditer(routes_text):
    route, target = m.group(1), m.group(2)
    if route.startswith("^") or route == "index.php":
        continue
    seg = re.split(r"[/\-]", route)[0]
    groups.setdefault(seg, []).append((route, target))

out = ["# Route index (auto-generated)\n"]
for seg in sorted(groups, key=str.lower):
    out.append(f"\n## {seg}\n")
    out.append("| URL | Controller action |\n|-----|-------------------|\n")
    for route, target in sorted(groups[seg]):
        out.append(f"| `/{route}` | `{target}` |\n")

if not (ROOT / "docs/user-guide").is_dir():
    (ROOT / "docs/user-guide").mkdir(parents=True)

(ROOT / "docs/user-guide/_ROUTE_INDEX.md").write_text("".join(out), encoding="utf-8")
print("Wrote docs/user-guide/_ROUTE_INDEX.md", len(groups), "groups")
