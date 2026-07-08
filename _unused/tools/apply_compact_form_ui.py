#!/usr/bin/env python3
"""Wrap add/edit form views with .oms-form-compact and tighten layout classes."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "application" / "views"

FORM_FILES = [
    "departments/form.php",
    "designations/form.php",
    "shifts/form.php",
    "employees/form.php",
    "users/form.php",
    "clients/create.php",
    "clients/edit.php",
    "assets/form.php",
    "types/form.php",
    "statuses/form.php",
    "approvals/form.php",
    "announcements/form.php",
    "api_integrations/form.php",
    "projects/form.php",
    "tasks/form.php",
    "requirements/create.php",
    "requirements/edit.php",
    "my_works/form.php",
    "my_works/form_create.php",
    "daily_activity/edit.php",
    "leave_requests/edit.php",
    "attendance/create.php",
    "attendance/edit.php",
    "expenses/create.php",
    "expenses/edit.php",
    "payroll/structure_form.php",
    "performance/create.php",
    "performance/edit.php",
    "recruitment/create_job.php",
    "external_training/form.php",
    "training_assessment/create_assessment.php",
    "training_lms/admin/module_form.php",
    "training_lms/admin/topic_form.php",
    "settings/holidays/form.php",
    "settings/leave_types/form.php",
    "settings/module_types/form.php",
    "settings/subscription_builder/form.php",
    "releases/form.php",
    "defects/form.php",
    "knowledge_base/form.php",
    "helpdesk/form.php",
    "events/form.php",
    "certifications/form.php",
    "customer_feedback/form.php",
    "rewards/rule_form.php",
    "reminders/edit.php",
    "reminders/schedule_form.php",
    "profile/edit.php",
    "email_settings/edit_template.php",
    "coaching/clients/form.php",
    "coaching/coaches/form.php",
    "coaching/leads/form.php",
    "coaching/leads/workshop_form.php",
    "coaching/sessions/form.php",
    "leave_requests/apply.php",
    "my_works/quick_add.php",
    "my_works/template_tasks.php",
    "assets/assign.php",
    "recruitment/schedule_interview.php",
    "training_assessment/add_question.php",
    "training_assessment/assign.php",
    "rewards/submit_claim.php",
    "rewards/manual_grant.php",
    "performance/self_assess.php",
]


def apply_compact(content: str) -> str:
    if "oms-form-compact" in content:
        return content

    # Insert open wrapper after header load (must be outside PHP blocks).
    open_tag = '<div class="oms-form-compact">\n'
    header_patterns = [
        r"\<\?php \$this->load->view\('partials/header'[^;]*;\s*\?\>",
        r"\<\?php\s+\$this->load->view\('partials/header'[^;]*;\s*\?\>",
    ]
    inserted = False
    for pat in header_patterns:
        m = re.search(pat, content, re.DOTALL)
        if m:
            pos = m.end()
            content = content[:pos] + "\n" + open_tag + content[pos:]
            inserted = True
            break

    if not inserted:
        # Multi-line PHP block: insert after first ?> following partials/header
        m = re.search(r"load->view\('partials/header'", content)
        if not m:
            return content
        close = content.find("?>", m.start())
        if close == -1:
            return content
        pos = close + 2
        content = content[:pos] + "\n" + open_tag + content[pos:]
        inserted = True

    if not inserted:
        return content

    # Close before footer
    footer_m = re.search(r"\<\?php \$this->load->view\('partials/footer'", content)
    if not footer_m:
        return content
    pos = footer_m.start()
    content = content[:pos] + "</div>\n" + content[pos:]

    text = content
    text = text.replace("row g-3", "row g-2 oms-form-grid")
    text = text.replace(
        'class="d-flex justify-content-between align-items-center mb-3"',
        'class="oms-form-page-head d-flex justify-content-between align-items-center mb-2"',
    )
    text = text.replace(
        'class="d-flex justify-content-between align-items-center mb-4"',
        'class="oms-form-page-head d-flex justify-content-between align-items-center mb-2"',
    )
    text = text.replace('class="card shadow-soft"', 'class="card shadow-soft oms-form-card"')
    text = text.replace('class="card shadow-sm"', 'class="card shadow-sm oms-form-card"')
    text = text.replace('<div class="mt-3">', '<div class="oms-form-actions">')
    text = text.replace('<div class="mt-4">', '<div class="oms-form-actions">')
    return text


def main():
    changed = 0
    missing = []
    for rel in FORM_FILES:
        path = VIEWS / rel.replace("/", "\\") if "\\" not in rel else VIEWS / rel
        path = VIEWS / rel
        if not path.exists():
            missing.append(rel)
            continue
        original = path.read_text(encoding="utf-8")
        updated = apply_compact(original)
        if updated != original:
            path.write_text(updated, encoding="utf-8")
            changed += 1
            print(f"  updated: {rel}")
    print(f"Done: {changed} files updated, {len(missing)} missing")
    for m in missing:
        print(f"  missing: {m}")


if __name__ == "__main__":
    main()
