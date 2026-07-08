#!/usr/bin/env python3
"""Full audit: views with header + form → compact wrapper status."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "application" / "views"

EXCLUDE_SUBSTR = [
    "partials/", "errors/", "auth/login", "auth/register", "auth/forgot",
    "auth/reset", "auth/verify", "training_assessment/take_assessment",
    "training_assessment/candidate_register", "recruitment/apply.php",
    "workshop_register", "_mobile_card", "_filters", "_attachment",
    "_list_comment", "_change_request", "_scope_banner", "_csrf",
    "partials/module_type_select", "question_block",
]

# Full-page forms that are list+inline (not dedicated add/edit) — informational
EXCLUDE_EXACT = {
    "daily_activity/index.php",  # log form on index
    "meals/index.php",
    "settings/index.php",
    "system_settings/index.php",
    "permissions/index.php",
    "db/index.php",
    "ai_chat/index.php",
    "analytics/dashboard.php",
    "chats/app.php",
    "chats/conversation.php",
    "coaching/billing/index.php",
    "coaching/goals/index.php",
    "subscription_builder/index.php",
}


def is_excluded(rel: str) -> bool:
    if rel in EXCLUDE_EXACT:
        return True
    return any(x in rel for x in EXCLUDE_SUBSTR)


def module_group(rel: str) -> str:
    parts = rel.replace("\\", "/").split("/")
    if parts[0] == "coaching":
        return "coaching/" + (parts[1] if len(parts) > 1 else "root")
    if parts[0] == "settings":
        return "settings/" + (parts[1] if len(parts) > 1 else "root")
    if parts[0] == "training_lms":
        return "training_lms/" + (parts[1] if len(parts) > 1 else "root")
    if parts[0] == "training_assessment":
        return "training_assessment"
    return parts[0]


def main():
    rows = []
    for f in sorted(VIEWS.rglob("*.php")):
        rel = str(f.relative_to(VIEWS)).replace("\\", "/")
        if is_excluded(rel):
            continue
        content = f.read_text(encoding="utf-8", errors="ignore")
        if "partials/header" not in content:
            continue
        if not re.search(r"<\s*form\b", content, re.I):
            continue
        # Skip pure list pages with filter forms only (no create/edit intent)
        is_dedicated = bool(re.search(
            r"(form\.php|create\.php|edit\.php|_form\.php|apply\.php|assign\.php|"
            r"quick_add\.php|self_assess\.php|structure_form|rule_form|workshop_form|"
            r"add_question|create_assessment|edit_template|manual_grant|submit_claim|"
            r"schedule_form|module_form|topic_form|create_job|schedule_interview)",
            rel, re.I
        ))
        has_post = bool(re.search(r"<form[^>]+method\s*=\s*['\"]post", content, re.I))
        has_form_open = "form_open(" in content
        is_mutation = has_post or has_form_open or is_dedicated
        if not is_mutation and not is_dedicated:
            continue
        compact = "oms-form-compact" in content
        rows.append((module_group(rel), rel, compact, is_dedicated))

    dedicated = [r for r in rows if r[3]]
    dedicated_missing = [r for r in dedicated if not r[2]]
    all_missing = [r for r in rows if not r[2]]

    print("=== DEDICATED ADD/EDIT SCREENS ===")
    print(f"Total: {len(dedicated)} | Compact: {len(dedicated)-len(dedicated_missing)} | Missing: {len(dedicated_missing)}")
    for mod, rel, compact, _ in sorted(dedicated, key=lambda x: (x[0], x[1])):
        mark = "OK" if compact else "MISSING"
        print(f"  [{mark}] {rel}")
    print()
    print("=== OTHER FORM PAGES (list/index with POST — not compact by design) ===")
    other = [r for r in rows if not r[3] and not r[2]]
    for mod, rel, _, _ in sorted(other, key=lambda x: (x[0], x[1])):
        print(f"  [SKIP] {rel}")
    print()
    print("=== MODULE SUMMARY (dedicated add/edit only) ===")
    mods = {}
    for mod, rel, compact, ded in dedicated:
        mods.setdefault(mod, {"ok": 0, "miss": 0})
        if compact:
            mods[mod]["ok"] += 1
        else:
            mods[mod]["miss"] += 1
    for mod in sorted(mods):
        s = mods[mod]
        total = s["ok"] + s["miss"]
        st = "OK" if s["miss"] == 0 else f"MISSING {s['miss']}"
        print(f"  {mod}: {s['ok']}/{total} {st}")


if __name__ == "__main__":
    main()
