# Project Map

Living reference — update **only the relevant section** per task. Do not regenerate this file fully.

## Project overview

| Field | Value |
|-------|-------|
| Name | Office Management System |
| Stack | CodeIgniter 3, PHP 8.4, MySQL, Bootstrap 5, jQuery |
| Environment | WAMP (Windows), internal portal |
| Entry | `index.php` → default route `auth` |

## Module list

See `docs/FUNCTIONAL_GRAPH.md` (domain modules) and `docs/SIDEBAR_SCREEN_INDEX.md` (sidebar screens).

## Routes

- App routes: `application/config/routes.php`
- User-guide route index: `docs/user-guide/_ROUTE_INDEX.md` (regenerate: `python tools/generate_user_guide_index.py`)

## DB tables

Schema reference: search `application/models/` and `application/migrations/`. No single generated schema doc in repo — check model `ensure_schema()` and migration files per module.

## DB changes

Log format: `table | change | reason | date`

| Table | Change | Reason | Date |
|-------|--------|--------|------|
| *(none logged yet)* | | | |

## Frontend dependencies

Pinned in `application/views/partials/header.php` and `footer.php`:

| Library | Version | CDN |
|---------|---------|-----|
| Bootstrap | 5.3.2 | jsDelivr |
| jQuery | 3.7.1 | code.jquery.com |
| DataTables | 2.0.7 | cdn.datatables.net |
| DataTables Responsive | 3.0.2 | cdn.datatables.net |
| Chart.js (EBA Platform) | 4.4.1 | jsDelivr (`assets/eba/eba-platform.html`) |

Module CSS/JS: `assets/css/`, `assets/js/`.

## Cron / scheduled tasks

| Script | Purpose | Schedule |
|--------|---------|----------|
| `reminders_cron.php` | Reminder processing entry | Task Scheduler via `.ps1`/`.bat` |
| `reminders_cron_generate.ps1` | Generate reminders | Windows Task Scheduler |
| `reminders_cron_send.ps1` | Send pending reminders | Windows Task Scheduler |
| `application/controllers/Cron.php` | Web cron guard (CLI only) | — |

PHP path (WAMP): `C:\wamp64\bin\php\php8.4.0\php.exe`

## Auth flow

1. Login → session (`user_id`, `role_id`, …)
2. `AuthHook` → public URI whitelist or session check
3. Non-admin → controller permission map gate
4. Method-level `require_module_access()` / `has_module_access()` in views

## Known issues

| Issue | Owner | Status |
|-------|-------|--------|
| *(add active bugs/TODOs here)* | | |
