# Refactoring Plan — Office Management System

**Plan date:** 2026-06-06  
**Principle:** Improve code quality without changing business logic, UI behavior, API responses, or database structure.

Each item is classified:
- **SAFE** — Isolated change, same outputs, low regression risk
- **MEDIUM RISK** — Cross-cutting, needs staging verification
- **HIGH RISK** — Core flows, security, or schema-sensitive; requires approval + tests

---

## Phase 1 — SAFE (Execute First)

| ID | Action | Files | Why | Status |
|----|--------|-------|-----|--------|
| R-01 | Remove redundant `$this->load->helper('notification')` | 12 controllers | Already autoloaded; no behavior change | **DONE** |
| R-02 | Batch user/client name lookup in Reports | Reports.php | Eliminates N+1; same output | **DONE** |
| R-03 | Cache `list_fields('attendance')` per request | Attendance.php | Reduces SHOW COLUMNS calls | **DONE** |
| R-04 | Remove or wire dead helpers | display_helper, system_access_helper | Dead code cleanup | **DONE** (removed) |
| R-05 | Retire dev scaffolding | Welcome.php, Test_company.php | Unused in production | **DONE** (Welcome redirects; Test_company 404 in prod) |
| R-06 | Unify coaching constructors | Coaching_webhooks only | Extend Coaching_Controller | **DONE** (portal/payments keep custom auth) |
| R-07 | Fix camelCase method outlier | Attendance.php | Naming consistency | **DONE** |
| R-08 | Add comments to complex methods | Reports.php, Auth.php | Readability only | **DONE** |

---

## Phase 2 — MEDIUM RISK (Staging Required)

| ID | Action | Files | Why | Prerequisite |
|----|--------|-------|-----|--------------|
| R-09 | Split Reports.php into sub-controllers | Reports_base, Reports_attendance, Reports_projects, Reports_hr, Reports | Maintainability | **DONE** |
| R-10 | Extract shared CRUD trait for org structure | Org_structure_trait, Departments, Designations | Reduce duplication | **DONE** (Roles uses different pattern) |
| R-11 | Move ensure_schema to CI migrations | org_schema_helper, migrations 006–009, schema_automation | Remove runtime DDL | **DONE** — 28 registry modules; 10 schema helpers; dead no-op removed; migration 009 |
| R-12 | Split Training_assessment controller | Training_assessment.php, Training_assessment_take.php | 41 methods too many | **DONE** (take flow in separate controller) |
| R-13 | Extract CoachingAutomationService | Coaching_automation.php library | Separate cron logic | **DONE** |
| R-14 | Paginate Activity log exports | Activity.php | Memory safety | **DONE** — batched CSV stream (500/batch, max 50k) |
| R-15 | Fix settings key mismatches | Auth.php, settings view | 2FA/IP whitelist broken | **DONE** (prior session) |
| R-16 | Reduce CSRF exclusions | config.php + JS layout | Security improvement | **DONE** — verify AJAX modules in staging |
| R-17 | Enable PHP 8.4 full deprecations in staging | index.php | Surface hidden warnings | **DONE** — `testing` env + `CI_DEPRECATIONS=1` on production |
| R-18 | Consolidate PDF libraries | Pdf_export facade + Working_pdf_generator | Reduce maintenance | **DONE** — facade for Dompdf/Working; 5 dead libs removed earlier |

---

## Phase 3 — HIGH RISK (Approval + Test Harness Required)

| ID | Action | Files | Why | Blocker |
|----|--------|-------|-----|---------|
| R-19 | Rewrite Attendance punch pipeline | attendance_* helpers, Attendance.php | Was 1,802-line monolith | **DONE** — punch, bulk, export, notify, geo, list helpers; ~1,044 lines |
| R-20 | Extract Auth service layer | Auth.php | Security-critical | **DONE** — auth_* helpers; ~702 lines (from ~1,310) |
| R-21 | Isolate Db controller | Db.php | Admin-only, 53 methods | **DONE** — db_* helpers; ~1,710 lines (from ~2,518) |
| R-22 | Split Reports::attendance_employee | Reports_attendance.php, attendance_report_helper | 826-line method | **DONE** — split into detail/summary private methods + shared helpers |
| R-23 | Eliminate global field_exists | schema_columns autoload + cached helper app-wide | Schema version constant | **DONE** (runtime); migrations keep raw checks |
| R-24 | Fix open registration role escalation | Auth.php | Security vulnerability | **DONE** (Staff only) |
| R-25 | Add Twilio webhook signature validation | Coaching_webhooks.php | Security | **DONE** |
| R-26 | Restrict Ai_chat SQL execution | Ai_chat.php | Data exfiltration risk | **DONE** — hardened execute_safe_query; debug_sql dev/admin only |

---

## Phase 4 — Post-Plan Maintainability (Staging Required)

| ID | Action | Files | Why | Status |
|----|--------|-------|-----|--------|
| R-27 | Extract Leave_requests notifications | leave_requests_notify_helper.php, Leave_requests.php | 300+ lines of email logic in controller | **DONE** — apply + approve/reject emails |
| R-28 | Extract My_works query/access layer | my_works_* helpers, My_works.php | 740-line controller; filters/scope in private methods | **DONE** — access, query, form helpers; ~405 lines |
| R-29 | Extract Reminders controller helpers | reminders_* helpers, Reminders.php | 1,098-line controller | **DONE** — 6 helpers; ~554 lines |
| R-30 | Slim Reports_attendance further | attendance_report_* helpers, Reports_attendance.php | 1,466 lines post-split | **DONE** — export helper + shared utilities; ~720 lines (detail/summary restored) |

---

## Explicitly NOT Planned (Out of Scope)

- Migrating to CodeIgniter 4 or Laravel
- Adding Composer dependencies
- Introducing service layer / repository pattern globally
- Changing database schema or table structures
- Modifying API/JSON response shapes
- Changing UI layouts or user workflows
- Adding POS/Restaurant/Inventory/Webshop modules (not in codebase)
- Removing runtime ensure_schema without migration replacement

---

## Execution Order

```
Phase 1 (SAFE)     → Immediate, no approval needed
    ↓
Phase 2 (MEDIUM)   → Staging verification after each item
    ↓
Phase 3 (HIGH)     → Written approval + test plan per item
```

---

## Success Criteria

After each refactor phase:
1. All existing routes respond correctly
2. No new PHP errors/warnings in logs
3. JSON AJAX responses unchanged (same keys/values)
4. Permission checks unchanged for all roles
5. Database schema unchanged (unless explicitly approved)
6. Report exports produce identical output

See `REFACTOR_REPORT.md` for executed changes log.
