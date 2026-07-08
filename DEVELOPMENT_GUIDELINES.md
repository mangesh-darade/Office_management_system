# Development Guidelines

Concise index for humans and AI agents. **Authoritative Cursor rules** live in `.cursor/rules/`; this file summarizes where to look.

## Read before coding

1. `PROJECT_CONTEXT.md` — what this system is
2. `docs/FUNCTIONAL_GRAPH.md` — modules, request flow, RBAC
3. `.cursorrules` — prohibitions and CI3 conventions
4. `.cursor/rules/project-workflow.mdc` — scope, API, forms, logging (always applied)

## File-scoped rules (`.cursor/rules/`)

| Rule | When it applies |
|------|-----------------|
| `codeigniter-patterns.mdc` | Controllers, views |
| `database-rules.mdc` | Models, migrations |
| `security-rules.mdc` | Controllers, models, helpers |
| `frontend-standards.mdc` | `assets/`, views |
| `permissions-rbac.mdc` | RBAC, AuthHook, permission helpers |
| `integrations.mdc` | API integrations, webhooks |
| `cron-jobs.mdc` | Cron scripts, reminders |
| `new-feature-rbac-and-guide.mdc` | New user-facing features |
| `my-works-module.mdc` | My Works module only |

Extended reference (Copilot parity): `.github/copilot-instructions.md` and `.github/instructions/`.

## Core conventions

- **MVC**: logic in controller, queries in model, HTML in view; never `echo` from controllers
- **POST**: PRG pattern — redirect after POST, flash messages, never re-render on POST
- **API JSON**: `{ "status": "success|error", "message": "", "data": {} }`
- **Models**: insert → `insert_id`/false; update/delete → true/false; lists → array (empty if none)
- **Security**: Query Builder, CSRF on POST, escape output in views (`esc_view()`, `sanitize_html_output()` for rich text), secrets in DB settings or env
- **RBAC**: `require_module_access()` in constructor; `has_module_access()` in views
- **Diffs**: minimal, surgical; no refactors unrelated to the task

## New features checklist

1. Controller, model, views, routes
2. Permission keys + `permission_helper.php` map (see `new-feature-rbac-and-guide.mdc`)
3. Sidebar link if user-facing
4. `docs/user-guide/module_catalog.json` + regenerate guide
5. Document schema changes in `PROJECT_MAP.md` (get approval first)
6. Production: set `CI_ENCRYPTION_KEY` and/or `application/config/local_secrets.php` (see `PROJECT_MAP.md`)

## Audit scripts

Scripts live under `_unused/tools/` (moved from root `tools/`):

```bash
python _unused/tools/audit_permission_modules.py
python _unused/tools/audit_rbac_alignment.py
python _unused/tools/generate_user_guide_modules.py
```

Deep logged-in smoke test (CLI): `_unused/dev_scripts/deep_login_check.php` with `TEST_LOGIN` and `TEST_PASS` env vars.

## Testing before done

- No PHP errors/warnings on affected routes
- JSON response keys unchanged for API edits
- Permission checks for non-admin roles
- No `var_dump` / `print_r` left in code
