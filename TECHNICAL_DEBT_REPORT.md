# Technical Debt Report — Office Management System

**Report date:** 2026-06-06  
**Scope:** application/ layer (185 PHP files, 73 controllers, 46 models)

---

## Debt Summary

| Category | Count | Severity |
|----------|-------|----------|
| God controllers (>1000 lines) | 6 | HIGH |
| Runtime schema bootstrap sites | 40+ | HIGH |
| field_exists() hot path calls | 350+ | MEDIUM |
| Raw SQL occurrences | 255 | LOW-MEDIUM |
| Duplicate CRUD patterns | 4+ module pairs | MEDIUM |
| Unused helpers | 2 | SAFE |
| PHP 8.4 masked deprecations | 1 (index.php) | MEDIUM |
| Migration system disabled | 1 | MEDIUM |
| Duplicate migration prefix | 1 (003_) | LOW |

---

## Top 20 Technical Debts

| ID | Finding | Location | Severity | Effort |
|----|---------|----------|----------|--------|
| TD-01 | God controller 4,010 lines | Reports.php | HIGH | L |
| TD-02 | 826-line single method | Reports::attendance_employee | HIGH | L |
| TD-03 | Runtime DDL on request path | 40+ ensure_schema sites | HIGH | L |
| TD-04 | Attendance punch monolith | Attendance.php (1,802 lines) | HIGH | L |
| TD-05 | Auth monolith | Auth.php (1,257 lines) | HIGH | M |
| TD-06 | DB admin god object | Db.php (53 methods) | HIGH | L |
| TD-07 | N+1 name resolution | Reports.php:875-984 | MEDIUM | S |
| TD-08 | field_exists per punch | Attendance.php (46 calls) | MEDIUM | M |
| TD-09 | Unused helpers | display_helper, system_access_helper | SAFE | S |
| TD-10 | Duplicate CRUD controllers | Departments/Designations | MEDIUM | M |
| TD-11 | Fat models | Training_assessment_model, Coaching_model | MEDIUM | M |
| TD-12 | Coaching cron N+1 | Coaching_model::run_automation_cron | MEDIUM | S |
| TD-13 | PHP 8.4 deprecations masked | index.php production config | MEDIUM | S |
| TD-14 | Redundant helper loads | 12 controllers (notification) | SAFE | S — **FIXED** |
| TD-15 | 14 coaching controllers | Coaching_*.php | MEDIUM | M |
| TD-16 | Activity export 10k rows | Activity.php | MEDIUM | S |
| TD-17 | Views bypass display_helper | 50+ views | SAFE | M |
| TD-18 | Settings key mismatches | Auth.php vs settings UI | HIGH | S |
| TD-19 | CSRF broad exclusions | config.php | HIGH | M |
| TD-20 | Migration numbering collision | 003_ duplicate prefix | LOW | S |

---

## God Controllers (>500 lines)

| Lines | File | Methods |
|------:|------|--------:|
| 4010 | Reports.php | 27 |
| 2374 | Db.php | 53 |
| 1802 | Attendance.php | 23 |
| 1391 | Training_assessment.php | 41 |
| 1257 | Auth.php | 25 |
| 1202 | Tasks.php | 18 |
| 1109 | Leave_requests.php | — |
| 1098 | Reminders.php | 24 |

---

## Fat Models

| Model | Lines | Methods | Assessment |
|-------|------:|--------:|------------|
| Training_assessment_model | 1160 | 60 | Schema + scoring + table resolution |
| Coaching_model | 592 | 59 | Cron + email + payments + stats |
| Leave_request_model | 592 | 14 | Approval workflow in model |
| Chat_model | 308 | 27 | Schema + complex SQL |

---

## Schema Debt

- **6 schema sources** coexist without single source of truth
- **Migrations disabled** — schema changes happen at runtime
- **ID type inconsistency** — BIGINT (core) vs INT (newer modules)
- **FK enforcement partial** — only in core dump
- **Dual notification schemas** — Install.php vs Notification_model evolved separately

---

## Security Debt (Cross-Reference)

See `SECURITY_AUDIT.md` for full list. Key items affecting technical debt:

- SEC-001/002: Settings key mismatches (2FA, IP whitelist broken)
- SEC-003: Open registration role escalation
- SEC-004: CSRF exclusions
- SEC-005: Cron token placeholder

---

## Duplicate Code Patterns

1. **Departments ↔ Designations** — near-identical CRUD + ensure_schema + change_tracker
2. **Reports export helpers** — repeated date-range and CSV generation logic
3. **Coaching constructor** — 3 controllers reimplement bootstrap instead of extending Coaching_Controller
4. **Inline notification helper loads** — 57 redundant calls across 12 controllers (**removed in Phase 4**)

---

## Dead Code Candidates

| Item | Evidence | Risk to Remove |
|------|----------|----------------|
| display_helper.php | Never loaded or used in views | SAFE |
| system_access_helper.php | Functions only self-referenced | SAFE |
| Welcome.php | CI scaffold; auth is default route | SAFE |
| Test_company.php | Dev-only, show_404 in production | SAFE |
| sma_* CMS tables | Legacy WordPress-like, likely unused | MEDIUM (verify first) |

---

## PHP 8.4 Compatibility Debt

| Item | Status |
|------|--------|
| system/ core patches | Applied (AllowDynamicProperties, E_STRICT, session.sid_length) |
| Application code | No PHP 8-only syntax; compatible |
| Production error masking | Deprecations suppressed on PHP 8.4+ |
| Recommendation | Run staging with full E_ALL to surface remaining warnings |

---

## Naming Inconsistencies

- **Methods:** 99% snake_case (CI convention); 1 camelCase outlier in Attendance.php
- **Tables:** Mix of prefixes (`ta_*`, `coaching_*`, `training_*`, unprefixed core)
- **Permission keys:** Mix of snake_case and module-specific naming

---

## Maintainability Score Factors

| Factor | Impact |
|--------|--------|
| No automated tests | HIGH negative |
| God controllers | HIGH negative |
| Runtime schema | HIGH negative |
| Consistent CI3 patterns | POSITIVE |
| Good helper organization | POSITIVE |
| Comprehensive routes file | POSITIVE |
| Permission system | POSITIVE |
| Documentation (now created) | POSITIVE |

See `FINAL_PROJECT_HEALTH_REPORT.md` for scored assessment.
