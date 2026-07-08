#!/usr/bin/env python3
"""
Generate docs/FUNCTIONAL_GRAPH.md — token-efficient codebase map for AI sessions.

Usage:
  python tools/generate_functional_graph.py
"""
import json
import re
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
APP = ROOT / "application"
OUT_MD = ROOT / "docs" / "FUNCTIONAL_GRAPH.md"
OUT_JSON = ROOT / "docs" / "CODEBASE_GRAPH_DATA.json"

# Domain grouping for navigation
DOMAIN_GROUPS = {
    "01 — Auth & Core": [
        "Auth", "Dashboard", "Profile", "Guide", "Errors", "Welcome", "Install", "Migrate",
        "Short_url", "Test_company",
    ],
    "02 — People & Org": [
        "Users", "Employees", "Departments", "Designations", "Roles", "Permissions",
        "Shifts", "Lead_mapping", "Clients",
    ],
    "03 — Attendance & Leave": [
        "Attendance", "Leaves", "Leave_requests",
    ],
    "04 — Projects & Work": [
        "Projects", "Tasks", "Requirements", "My_works", "Timesheets", "Daily_activity",
        "Statuses", "Types", "Subscription_builder", "Elintom_proposals",
    ],
    "05 — HR & Finance": [
        "Payroll", "Expenses", "Performance", "Recruitment", "Assets", "Approvals",
    ],
    "06 — Reports & Analytics": [
        "Reports", "Reports_attendance", "Reports_hr", "Reports_projects", "Analytics", "Ai_chat",
    ],
    "07 — Communication": [
        "Chats", "Calls", "Announcements", "Notifications", "Reminders", "Mail", "Sendgrid",
        "Whatsapp", "Activity",
    ],
    "08 — Training & LMS": [
        "Training_assessment", "Training_assessment_take", "Training_lms", "Training_lms_admin",
        "Training_import", "External_training",
    ],
    "09 — Coaching CRM": [
        "Coaching", "Coaching_admin", "Coaching_billing", "Coaching_clients", "Coaching_coaches",
        "Coaching_goals", "Coaching_leads", "Coaching_payments", "Coaching_portal",
        "Coaching_reports", "Coaching_resources", "Coaching_sessions", "Coaching_webhooks",
        "Coaching_whatsapp_crm",
    ],
    "10 — Admin & Settings": [
        "Settings", "System_settings", "Email_settings", "Api_integrations", "Db", "Superadmin",
        "Cron",
    ],
    "11 — Engagement & Rewards": [
        "Releases", "Defects", "Knowledge_base", "Helpdesk", "Events", "Certifications",
        "Customer_feedback", "Rewards",
    ],
    "12 — Office Meals": ["Meals"],
}

PUBLIC_ROUTES = """
| Pattern | Purpose |
|---------|---------|
| `auth/login`, `login`, `auth/register`, `register` | Login / registration |
| `auth/send-verify-code`, `auth/verify-code`, `auth/verify-2fa` | Email / 2FA verification |
| `auth/forgot_password`, `auth/reset_password` | Password reset |
| `install/schema` | DB installer |
| `training-assessment/take/*`, `training_assessment_take/*` | Candidate assessment (token) |
| `coaching-webhooks/razorpay`, `coaching-webhooks/whatsapp-inbound` | Payment / WhatsApp webhooks |
| `coaching-leads/workshop-register/*` | Public workshop registration |
"""

AUTOLOAD_HELPERS = [
    "url", "form", "download", "permission", "attendance", "training", "company",
    "api_integration", "error_handler", "notification", "hierarchy_filter", "data_scope",
    "coaching", "schema_columns", "view_output",
]

CORE_CLASSES = {
    "Coaching_Controller": "Base for coaching admin modules; loads Coaching_model + coaching_require_access()",
    "Reports_base": "Shared report helpers; auto require_module_access(reports keys)",
    "Org_structure_trait": "Department/designation hierarchy helpers for controllers",
    "Schema_columns_trait": "Runtime column existence checks",
    "MY_Security": "CSRF / security overrides for PHP 8.4",
    "MY_Exceptions": "Custom exception rendering",
}


def extract_controllers():
    out = {}
    for f in sorted((APP / "controllers").glob("*.php")):
        content = f.read_text(encoding="utf-8", errors="ignore")
        name = f.stem
        extends = re.search(r"class\s+\w+\s+extends\s+(\w+)", content)
        models = re.findall(r"load->model\s*\(\s*['\"](\w+)['\"]", content)
        models += [m[0] for m in re.findall(r"load->model\s*\(\s*['\"](\w+)['\"]\s*,\s*['\"](\w+)['\"]", content)]
        for block in re.findall(r"load->model\s*\(\s*\[([^\]]+)\]", content):
            models += re.findall(r"['\"](\w+)['\"]", block)
        views = re.findall(r"load->view\s*\(\s*['\"]([^'\"]+)['\"]", content)
        methods = []
        for m in re.finditer(r"(public|protected|private)\s+function\s+(\w+)\s*\(", content):
            fn = m.group(2)
            if fn not in ("__construct",) and not fn.startswith("_"):
                methods.append(fn)
        coaching = re.search(r"coaching_permission\s*=\s*['\"]([^'\"]+)['\"]", content)
        out[name] = {
            "extends": extends.group(1) if extends else "CI_Controller",
            "models": list(dict.fromkeys(models)),
            "views": list(dict.fromkeys(views)),
            "methods": methods,
            "coaching_permission": coaching.group(1) if coaching else None,
        }
    return out


def extract_models():
    out = {}
    for f in sorted((APP / "models").glob("*.php")):
        content = f.read_text(encoding="utf-8", errors="ignore")
        name = f.stem
        tables = re.findall(
            r"->(?:from|get|insert|update|delete|replace)\s*\(\s*['\"]([a-z0-9_]+)['\"]", content, re.I
        )
        tables += re.findall(r"\$this->table\s*=\s*['\"]([^'\"]+)['\"]", content)
        out[name] = list(dict.fromkeys(tables))[:12]
    return out


def extract_permission_map():
    content = (APP / "helpers" / "permission_helper.php").read_text(encoding="utf-8", errors="ignore")
    block = re.search(r"function get_controller_module_access_map\(\)\s*\{[\s\S]*?return\s*\[([\s\S]*?)\];\s*\}", content)
    if not block:
        return {}
    inner = block.group(1)
    result = {}
    for m in re.finditer(r"'([a-z0-9_]+)'\s*=>\s*\[([^\]]*)\]", inner):
        ctrl = m.group(1)
        keys = re.findall(r"'([a-z0-9_]+)'", m.group(2))
        result[ctrl] = keys
    return result


def ctrl_route_prefix(name):
    """CI3 default URI: snake_case controller name."""
    s = re.sub(r"(?<!^)(?=[A-Z])", "_", name).lower()
    return s.replace("__", "_")


def build_markdown(controllers, models, perm_map):
    lines = []
    today = date.today().isoformat()
    lines += [
        "# Office Management System — Functional Graph",
        "",
        f"> Auto-generated reference map · {today} · Regenerate: `python tools/generate_functional_graph.py`",
        "",
        "## How to use (AI / new sessions)",
        "",
        "1. **Read this file first** before exploring the full codebase — saves tokens.",
        "2. Find your module in [Domain Modules](#domain-modules) → follow Controller → Model → View chain.",
        "3. Check [RBAC keys](#rbac--permission-flow) in `permission_helper.php` + `Permissions.php`.",
        "4. Routes: `application/config/routes.php` or `docs/user-guide/_ROUTE_INDEX.md`.",
        "5. User-facing docs: `docs/user-guide/` + `module_catalog.json`.",
        "",
        "---",
        "",
        "## System stack",
        "",
        "| Layer | Location | Notes |",
        "|-------|----------|-------|",
        "| Framework | CodeIgniter 3 | `system/` has PHP 8.4 patches |",
        "| App | `application/` | controllers, models, views, helpers, hooks |",
        "| Entry | `index.php` | Front controller |",
        "| Default route | `auth` | Login page |",
        "| Layout | `views/partials/header`, `sidebar`, `footer` | Bootstrap 5 UI |",
        "",
        "---",
        "",
        "## Request lifecycle",
        "",
        "```mermaid",
        "flowchart TD",
        "    A[HTTP Request] --> B[index.php]",
        "    B --> C[CI Router routes.php]",
        "    C --> D[Controller __construct]",
        "    D --> E[AuthHook post_controller_constructor]",
        "    E --> F{Public URI?}",
        "    F -->|yes| H[Controller method]",
        "    F -->|no| G{Session user_id?}",
        "    G -->|no| L[redirect auth/login]",
        "    G -->|yes| M[Maintenance / IP / Session checks]",
        "    M --> N{role_id = 1?}",
        "    N -->|yes admin| H",
        "    N -->|no| O[Route-level RBAC via permission map]",
        "    O --> P{Has controller permission?}",
        "    P -->|no| Q[redirect dashboard]",
        "    P -->|yes| H",
        "    H --> R[require_module_access in method]",
        "    R --> S[Model / Helper / DB]",
        "    S --> T[load->view partials + module view]",
        "    T --> U[HTML or JSON response]",
        "```",
        "",
        "---",
        "",
        "## RBAC & permission flow",
        "",
        "```mermaid",
        "flowchart LR",
        "    subgraph storage [DB]",
        "        P[(permissions table)]",
        "        R[(roles table)]",
        "    end",
        "    subgraph code [Code layers]",
        "        PM[Permissions.php modules list]",
        "        MAP[get_controller_module_access_map]",
        "        AH[AuthHook route gate]",
        "        RMA[require_module_access]",
        "        HMA[has_module_access in views]",
        "    end",
        "    PM --> P",
        "    MAP --> AH",
        "    MAP --> RMA",
        "    P --> HMA",
        "    P --> RMA",
        "    AH -->|non-admin| MAP",
        "    RMA -->|role_id=1 bypass| OK[Allow]",
        "    HMA -->|role_id=1 bypass| OK",
        "```",
        "",
        "**Rules:**",
        "- `role_id = 1` (Super Admin): bypasses all RBAC in `has_module_access()` and AuthHook.",
        "- **Office Meals exception**: uses `meal_helper.php` (`require_meal_access`) — **no admin bypass**.",
        "- Coaching controllers extend `Coaching_Controller` → `coaching_require_access($coaching_permission)`.",
        "- Permission matrix source of truth: `Permissions.php` → `modules()` + `get_controller_module_access_map()`.",
        "- Always-allowed controllers (AuthHook): `dashboard`, `profile`, `auth`, `errors`, `cron`, `coaching_portal`, etc.",
        "",
        "---",
        "",
        "## Public endpoints (no login)",
        "",
        PUBLIC_ROUTES.strip(),
        "",
        "---",
        "",
        "## Autoloaded helpers (every request)",
        "",
        ", ".join(f"`{h}`" for h in AUTOLOAD_HELPERS),
        "",
        "**Key helpers by concern:**",
        "",
        "| Helper | Role |",
        "|--------|------|",
        "| `permission_helper` | RBAC: has/require module access, controller map |",
        "| `data_scope_helper` | Org-wide vs own-record filtering |",
        "| `hierarchy_filter_helper` | Manager/lead team visibility |",
        "| `attendance_helper` | Punch, shifts, geo, export |",
        "| `my_works_*` | Personal work items, attachments, matrix |",
        "| `coaching_helper` | Coaching CRM access + notifications |",
        "| `meal_helper` | Office Meals strict RBAC |",
        "| `training_helper` | LMS + assessment screen gates |",
        "| `api_integration_helper` | Credentials from `api_integrations` table |",
        "| `notification_helper` | In-app + push notifications |",
        "",
        "---",
        "",
        "## Core classes (`application/core/`)",
        "",
        "| Class | Purpose |",
        "|-------|---------|",
    ]
    for cls, desc in CORE_CLASSES.items():
        lines.append(f"| `{cls}` | {desc} |")

    lines += ["", "---", "", "## Domain modules", ""]

    assigned = set()
    for domain, ctrl_names in DOMAIN_GROUPS.items():
        lines.append(f"### {domain}")
        lines.append("")
        lines.append("| Controller | Extends | Models | Primary views | RBAC keys (first 3) | Key methods |")
        lines.append("|------------|---------|--------|---------------|---------------------|-------------|")
        for cn in ctrl_names:
            if cn not in controllers:
                continue
            assigned.add(cn)
            c = controllers[cn]
            pk = perm_map.get(ctrl_route_prefix(cn), perm_map.get(cn.lower(), []))
            pk_show = ", ".join(pk[:3]) + ("…" if len(pk) > 3 else "")
            if c.get("coaching_permission"):
                pk_show = f"coaching:{c['coaching_permission']}"
            model_list = ", ".join(c["models"][:4]) or "—"
            views = ", ".join(c["views"][:2]) or "—"
            methods = ", ".join(c["methods"][:6]) or "—"
            if len(c["methods"]) > 6:
                methods += "…"
            lines.append(
                f"| `{cn}` | {c['extends']} | {model_list} | {views} | {pk_show or '—'} | {methods} |"
            )
        lines.append("")

    unassigned = sorted(set(controllers) - assigned)
    if unassigned:
        lines.append("### Other controllers")
        lines.append("")
        for cn in unassigned:
            c = controllers[cn]
            lines.append(f"- `{cn}` → models: {', '.join(c['models'][:3]) or 'none'}")
        lines.append("")

    lines += ["---", "", "## Controller → Model map (full)", ""]
    for cn in sorted(controllers):
        c = controllers[cn]
        if not c["models"]:
            continue
        lines.append(f"- **{cn}**: {', '.join(c['models'])}")

    lines += ["", "---", "", "## Model → DB tables (inferred)", ""]
    for mn in sorted(models):
        if models[mn]:
            lines.append(f"- **{mn}**: `{', '.join(models[mn])}`")

    lines += [
        "",
        "---",
        "",
        "## Cross-module dependencies",
        "",
        "```mermaid",
        "flowchart TB",
        "    Users --> Employees",
        "    Employees --> Attendance",
        "    Employees --> Payroll",
        "    Employees --> Leave_requests",
        "    Projects --> Tasks",
        "    Projects --> Requirements",
        "    Tasks --> Timesheets",
        "    Tasks --> My_works",
        "    Clients --> Projects",
        "    Clients --> Subscription_builder",
        "    Training_lms --> Training_assessment",
        "    Coaching_leads --> Coaching_clients",
        "    Coaching_sessions --> Coaching_billing",
        "    Attendance --> Rewards",
        "    Announcements --> Reminders",
        "    Reminders --> Cron",
        "    Settings --> Api_integrations",
        "    Permissions --> AuthHook",
        "```",
        "",
        "---",
        "",
        "## Cron jobs (`Cron.php`)",
        "",
        "| Method | Purpose |",
        "|--------|---------|",
        "| `process_announcements` | Publish scheduled announcements |",
        "| `send_emails` | Process reminder email queue |",
        "| `process_rewards` | Rewards automation |",
        "| `coaching_automation` | Coaching session reminders |",
        "| `coaching_homework_reminders` | Homework nudges |",
        "| `meals_daily` | Office meals daily processing |",
        "",
        "HTTP cron requires `?token=` matching `cron_secret_token` setting or `CRON_TOKEN` env.",
        "",
        "---",
        "",
        "## File location quick map",
        "",
        "| What | Path |",
        "|------|------|",
        "| Routes | `application/config/routes.php` |",
        "| Auth hook | `application/hooks/AuthHook.php` |",
        "| Permission matrix UI | `application/controllers/Permissions.php` |",
        "| Controller→module map | `application/helpers/permission_helper.php` |",
        "| Sidebar nav | `application/views/partials/sidebar.php` |",
        "| Migrations | `application/migrations/` |",
        "| User guide catalog | `docs/user-guide/module_catalog.json` |",
        "| Route index | `docs/user-guide/_ROUTE_INDEX.md` |",
        "| Raw graph JSON | `docs/CODEBASE_GRAPH_DATA.json` |",
        "",
        "---",
        "",
        "## Implementation checklist (new feature)",
        "",
        "1. Controller in `application/controllers/`",
        "2. Model in `application/models/`",
        "3. Views in `application/views/{module}/`",
        "4. Route in `routes.php`",
        "5. Add keys to `Permissions.php` + `get_controller_module_access_map()`",
        "6. `require_module_access()` in constructor/methods",
        "7. Sidebar link with `has_module_access()`",
        "8. Update `docs/user-guide/module_catalog.json` + run `python tools/generate_user_guide_modules.py`",
        "9. Run `python tools/audit_permission_modules.py`",
        "",
    ]
    return "\n".join(lines)


def main():
    controllers = extract_controllers()
    models = extract_models()
    perm_map = extract_permission_map()

    OUT_JSON.write_text(
        json.dumps({"controllers": controllers, "models": models, "permission_map": perm_map}, indent=2),
        encoding="utf-8",
    )
    OUT_MD.write_text(build_markdown(controllers, models, perm_map), encoding="utf-8")
    print(f"Wrote {OUT_JSON}")
    print(f"Wrote {OUT_MD} ({len(controllers)} controllers)")


if __name__ == "__main__":
    main()
