# Office Management System — Project Context

Internal HR/operations portal for a single organization. **Not** a POS, restaurant, inventory, or webshop system.

## Stack

| Layer | Detail |
|-------|--------|
| Framework | CodeIgniter 3 (`application/`, `system/`) |
| Runtime | PHP 8.4 on WAMP, MySQL/mysqli |
| Frontend | Bootstrap 5, jQuery, DataTables, server-rendered views |
| Auth | Session-based; `AuthHook` + `require_module_access()` RBAC |

## What the system does

Modules include employees, attendance, leave, payroll, expenses, projects, tasks, requirements, My Works, reports, training LMS, coaching CRM, recruitment, performance, assets, chats, notifications, settings, permissions, API integrations, subscription builder, and office meals.

## Key paths

| Path | Purpose |
|------|---------|
| `application/controllers/` | HTTP entry points |
| `application/models/` | Database queries |
| `application/views/` | HTML templates |
| `application/helpers/` | Shared functions (many autoloaded) |
| `application/hooks/AuthHook.php` | Session auth + route RBAC |
| `application/config/routes.php` | URL routing |
| `docs/FUNCTIONAL_GRAPH.md` | Module map, request flow, RBAC (read first for new work) |
| `docs/SIDEBAR_SCREEN_INDEX.md` | Sidebar links and routes |
| `docs/user-guide/` | End-user documentation + screenshots |

## RBAC summary

- Permission keys registered in `application/controllers/Permissions.php`
- Route map in `application/helpers/permission_helper.php` → `get_controller_module_access_map()`
- `role_id = 1` (Super Admin) bypasses RBAC except **Office Meals** (uses `meal_helper.php`)
- New features: follow `.cursor/rules/new-feature-rbac-and-guide.mdc`

## Agent / dev rules

| File | Role |
|------|------|
| `.cursorrules` | Production constraints and prohibitions |
| `.cursor/rules/project-workflow.mdc` | Always-on workflow |
| `.cursor/rules/*.mdc` | File-scoped patterns |
| `DEVELOPMENT_GUIDELINES.md` | Dev standards index |
| `PROJECT_MAP.md` | Living log: DB changes, cron, CDN pins |

## Primary goal for AI-assisted changes

Improve code quality **without** changing business logic, API response shapes, database schema, UI workflows, or auth rules unless explicitly requested.
