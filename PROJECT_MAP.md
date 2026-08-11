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

See **DB Changes** log at end of this file (full table).

## Frontend dependencies

Pinned in `application/views/partials/header.php` and `footer.php`:

| Library | Version | CDN |
|---------|---------|-----|
| Bootstrap | 5.3.2 | jsDelivr |
| jQuery | 3.7.1 | code.jquery.com |
| DataTables | 2.0.7 | cdn.datatables.net |
| DataTables Responsive | 3.0.2 | cdn.datatables.net |
| Chart.js (Business Assessment) | 4.4.1 | jsDelivr (`assets/eba/eba-platform.html`) |
| Select2 | 4.1.0-rc.0 | jsDelivr (`partials/oms_select2_multi.php`) |
| Select2 Bootstrap 5 theme | 1.3.0 | jsDelivr |

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
| `task_activity` | CREATE TABLE (id, task_id, user_id, action, old_value, new_value, created_at) | Per-task change history on task detail | 2026-07-27 |
| `todays_plan_items` | ADD `repeat_type` varchar(20) DEFAULT 'once' | One time vs recurring plan points | 2026-07-15 |
| `users` | ADD `google_alert_checkin`, `google_alert_checkout` TINYINT(1) DEFAULT 1 | Per-user check-in/out Google alerts | 2026-07-15 |
| `settings` | Keys `attendance_checkin_alert_enabled`, `attendance_checkout_alert_enabled`, `attendance_checkin_alert_minutes_before`, `attendance_checkout_alert_minutes_before` | Org-level attendance Google alert toggles | 2026-07-15 |
| `ai_conversations` | CREATE TABLE (id, user_id, title, created_at, updated_at) | Persistent AI chat conversations | 2026-07-17 |
| `ai_conversation_messages` | CREATE TABLE (id, conversation_id, role, content, meta_json, created_at) | Persistent AI chat messages | 2026-07-17 |
| `ai_chat_intent_log` | CREATE TABLE (id, user_id, message, tool, source, ok, created_at) | AI intent audit / eval | 2026-07-17 |
| `tasks` | ENSURE `estimate_hours` DECIMAL(6,2) NULL | Planned estimate hours on tasks | 2026-07-21 |
| `my_works` | ADD `estimate_hours` DECIMAL(6,2) NULL | Planned estimate hours on work items | 2026-07-21 |
| `template_tasks` | ADD `estimate_hours` DECIMAL(6,2) NULL | Planned estimate hours on templates | 2026-07-21 |
| `projects` | ADD `estimate_hours` DECIMAL(6,2) NULL | Planned estimate hours on projects | 2026-07-21 |
| `my_works` | ADD `actual_hours` DECIMAL(6,2) NULL | Actual hours required when status closed/complete | 2026-07-29 |
| `projects` | ADD `actual_hours` DECIMAL(6,2) NULL | Actual hours required when status completed | 2026-07-29 |
| `tasks` | ENSURE `actual_hours` DECIMAL(6,2) NULL | Actual hours required when status completed | 2026-07-29 |
| `daily_work_log_attachments` | CREATE TABLE (log_id, original_name, stored_name, mime_type, file_size, sort_order) | File attachments on daily activity logs | 2026-07-21 |
| `client_urls` | CREATE TABLE (id, client_id, version, url, url_type, db_name, db_username, db_password, db_host, db_port, …) | Multiple URL+DB sets per client | 2026-07-23 |
| `task_assignees` | CREATE TABLE (id, task_id, user_id, created_at; UNIQUE task_id+user_id) | Multi-user task assignment (primary stays on tasks.assigned_to) | 2026-07-23 |
| `requirement_assignees` | CREATE TABLE (id, requirement_id, user_id, created_at; UNIQUE requirement_id+user_id) | Multi-user requirement assignment (primary stays on requirements.assigned_to) | 2026-07-23 |
| `my_works_assignees` | CREATE TABLE (id, work_id, user_id, created_at; UNIQUE work_id+user_id) | Multi-user My Works assignment (primary stays on my_works.created_for) | 2026-07-23 |
| `leave_requests` | ADD `apply_email_message_id` VARCHAR(255) NULL | Thread approve/reject emails as reply to apply mail | 2026-07-23 |
| `permissions` | SET `can_access=1` for Admin role_id=1 on System Settings / Admin / holidays / leave_types / permissions / etc. | Align Permission Manager checkboxes with Settings access (checked = allowed) | 2026-07-23 |
| `client_activity` | CREATE TABLE (id, client_id, user_id, action, old_value, new_value, created_at) | Per-client change history on client detail (Task-style) | 2026-08-10 |
| `clients` | Repair: dedupe triplicate rows; ADD PRIMARY KEY (`id`); MODIFY `id` AUTO_INCREMENT; ADD UNIQUE `uq_client_code` | Missing AI/PK caused insert_id=0 → "Clients create error" | 2026-08-10 |
| `project_defects` | MODIFY `project_id` int(11) DEFAULT NULL | Project optional on defect create/edit | 2026-08-11 |
