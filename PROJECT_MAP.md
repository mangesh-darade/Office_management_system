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
- User-guide route index: `docs/user-guide/_ROUTE_INDEX.md` (regenerate: `python _unused/tools/generate_user_guide_index.py`)

## Dev / archive folder (`_unused/`)

Non-runtime files moved here to keep the project root clean. See `_unused/README.md`.

| Path | Contents |
|------|----------|
| `_unused/tools/` | Audit scripts, screenshot generators, one-off Python/PHP tools |
| `_unused/dev_scripts/` | Root test/debug PHP, schema checker, deep login smoke test |
| `_unused/database/` | One-time marketing/training seed SQL |
| `_unused/samples/` | LMS import sample CSVs (reference only) |
| `_unused/O_db/` | Old DB dump |
| `_unused/sql/` | Demo data notes |

**Still at root (in use):** `samples/training_assessment_import_sample.csv`, `database/subscription_builder_*`, `database/training_*_module.sql`, `reminders_cron.php`, `application/`, `assets/`, `docs/`.

## Environment secrets (optional)

| Variable / file | Purpose |
|-----------------|--------|
| `CI_ENCRYPTION_KEY` | Preferred encryption key (env) |
| `application/config/local_secrets.php` | Gitignored override; copy from `local_secrets.example.php` |
| `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` | Override WAMP defaults in `database.php` |
| `CRON_TOKEN` or Settings → `cron_secret_token` | HTTP cron / Task Scheduler token |
| `AI_DB_RO_*` | Read-only DB user for AI Chat SQL (optional) |


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
| Chart.js (Business Assessment) | 4.4.1 | jsDelivr (`assets/eba/eba-platform.html`) |

Module CSS/JS: `assets/css/`, `assets/js/`.

## Cron / scheduled tasks

| Script | Purpose | Schedule |
|--------|---------|----------|
| `reminders_cron.php` | Generate today's schedule → queue (Google sync on enqueue) | Task Scheduler via `.ps1`/`.bat` |
| `reminders_cron_generate.ps1` | Generate reminders from schedules | Windows Task Scheduler |
| `reminders_cron_send.ps1` | **DISABLED** (no-op) — delivery via Google Calendar | Stop / ignore in Task Scheduler |
| `application/controllers/Cron.php` | Web cron (`?token=`); `send_emails` skips reminder SMTP | Task Scheduler or CLI |

PHP path (WAMP): `C:\wamp64\bin\php\php8.4.0\php.exe`

## Auth flow

1. Login → session (`user_id`, `role_id`, …)
2. `AuthHook` → public URI whitelist or session check
3. Non-admin → controller permission map gate
4. Method-level `require_module_access()` / `has_module_access()` in views

## Known issues

| Issue | Owner | Status |
|-------|-------|--------|
| Inactive holidays still appear in some attendance reports (no `status` filter) | — | Open |
| Same holiday date cannot be re-added after soft delete (must edit existing row) | — | Open |
| `holidays.date` / `holidays.type` legacy DB columns unused by code | — | Low / cleanup |

## DB Changes

| Table | Change | Reason | Date |
|-------|--------|--------|------|
| `todays_plan_items` | ADD `repeat_type` varchar(20) DEFAULT 'once' | One time vs recurring plan points | 2026-07-15 |
| `users` | ADD `google_alert_checkin`, `google_alert_checkout` TINYINT(1) DEFAULT 1 | Per-user check-in/out Google alerts | 2026-07-15 |
| `settings` | Keys `attendance_checkin_alert_enabled`, `attendance_checkout_alert_enabled`, `attendance_checkin_alert_minutes_before`, `attendance_checkout_alert_minutes_before` | Org-level attendance Google alert toggles | 2026-07-15 |
