#!/usr/bin/env python3
"""Generate module markdown guides from module_catalog.json (purpose + list/add/edit/delete)."""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "docs/user-guide/module_catalog.json"
OUT = ROOT / "docs/user-guide"


def pick_video(path: str | None) -> str | None:
    if not path:
        return None
    p = ROOT / "docs/user-guide" / path.replace("\\", "/")
    voiced = p.with_name(p.stem + "-voiced.webm")
    if voiced.is_file():
        return str(voiced.relative_to(OUT)).replace("\\", "/")
    if p.is_file():
        return path.replace("\\", "/")
    return path.replace("\\", "/")


def md_image(alt: str, src: str | None) -> str:
    if not src:
        return ""
    return f"![{alt}]({src})\n\n"


def md_steps(steps: list[str] | None, title: str) -> str:
    if not steps:
        return ""
    lines = [f"**{title}:**", ""]
    for i, s in enumerate(steps, 1):
        lines.append(f"{i}. {s}")
    lines.append("")
    return "\n".join(lines)


def render_entity(ent: dict) -> str:
    parts = [
        f"## {ent['name']}",
        "",
        f"**Purpose:** {ent['purpose']}",
        "",
        f"**Menu:** {ent['menu']}",
        "",
    ]

    lst = ent.get("list") or {}
    vid = pick_video(lst.get("video") or ent.get("video"))
    if vid:
        parts.append(md_image(f"Video — {ent['name']}", vid))
    if lst.get("image"):
        parts.append(md_image(f"View list — {ent['name']}", lst["image"]))
    if lst.get("steps"):
        parts.append("### How to use\n")
        parts.append(md_steps(lst.get("steps"), "Steps"))

    add = ent.get("add")
    if add:
        parts.append("### Add (create new)\n")
        if add.get("image"):
            parts.append(md_image(f"Add screen — {ent['name']}", add["image"]))
        parts.append(md_steps(add.get("steps"), "Steps"))

    edit = ent.get("edit")
    if edit:
        parts.append("### Edit (update existing)\n")
        if edit.get("image"):
            parts.append(md_image(f"Edit screen — {ent['name']}", edit["image"]))
        parts.append(md_steps(edit.get("steps"), "Steps"))

    delete = ent.get("delete")
    if delete:
        parts.append("### Delete / remove\n")
        parts.append(md_steps(delete.get("steps"), "How"))

    parts.append("---\n")
    return "\n".join(parts)


def render_module(mod: dict, modules: list[dict]) -> str:
    idx = next(i for i, m in enumerate(modules) if m["id"] == mod["id"])
    nxt = modules[idx + 1] if idx + 1 < len(modules) else None

    overview_vid = pick_video(mod.get("video"))
    body = [
        f"# Module {mod['id']} — {mod['title']}",
        "",
        "## What is this module for?",
        "",
        mod["purpose"],
        "",
    ]
    if overview_vid:
        body.append(md_image(f"Animated overview — {mod['title']}", overview_vid))
    body.append("---\n")

    for ent in mod.get("entities", []):
        body.append(render_entity(ent))

    if nxt:
        body.append(f"**Next module:** [{nxt['id']} — {nxt['title']}]({nxt['file']})")
    else:
        body.append("**Back to start:** [USER_GUIDE.md](../../USER_GUIDE.md)")

    body.append("")
    return "\n".join(body)


def main() -> int:
    data = json.loads(CATALOG.read_text(encoding="utf-8"))
    modules = data["modules"]
    for mod in modules:
        path = OUT / mod["file"]
        path.write_text(render_module(mod, modules), encoding="utf-8")
        print(f"  wrote {path.name}")
    print(f"Done — {len(modules)} module guides")
    return 0


if __name__ == "__main__":
    sys.exit(main())
