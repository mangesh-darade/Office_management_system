# Performance Audit — Office Management System

**Audit date:** 2026-06-06  
**Scope:** Application layer query patterns, caching, large files

---

## Executive Summary

| Area | Score | Status |
|------|-------|--------|
| Query efficiency | 55/100 | N+1 and field_exists hotspots |
| Caching strategy | 40/100 | Minimal (dashboard only) |
| File/controller size | 45/100 | God controllers impact load time |
| Index usage | 50/100 | Runtime index creation, few formal indexes |
| Asset delivery | 60/100 | Static assets, no CDN config |
| Session performance | 65/100 | File-based sessions adequate for scale |

---

## Critical Performance Issues

### PERF-001: Reports N+1 Name Resolution
- **File:** `application/controllers/Reports.php` (~lines 875–881)
- **Issue:** `get_user_name()` and `get_client_name()` called per project in loop
- **Each call:** Up to 6× `field_exists()` + 1 DB query
- **Impact:** 50 projects = 50+ queries + 300 field_exists calls
- **Fix:** Batch preload users/clients into maps before loop
- **Risk class:** SAFE refactor

### PERF-002: field_exists() on Hot Paths
- **Attendance.php:** 46 calls (many per punch request)
- **Reports.php:** 91 calls
- **Mechanism:** Each `field_exists()` ≈ `SHOW COLUMNS` round-trip in CI3
- **Fix:** Cache `list_fields()` result per request
- **Risk class:** SAFE refactor

### PERF-003: Reports God Controller
- **File:** `Reports.php` — 4,010 lines, 27 methods
- **Issue:** Single `attendance_employee()` method spans ~826 lines
- **Impact:** Memory footprint, parse time, maintenance drag
- **Fix:** Split into sub-controllers or libraries
- **Risk class:** HIGH refactor

---

## Medium Performance Issues

### PERF-004: Runtime Schema Checks (ensure_schema)
- **Sites:** 40+ across models/controllers
- **Issue:** DDL operations (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE`) on request path
- **Impact:** First-access latency spike; lock contention on MySQL
- **Fix:** Move to CI migrations or one-time deployment scripts
- **Risk class:** MEDIUM refactor

### PERF-005: Coaching Automation Cron N+1
- **File:** `Coaching_model::run_automation_cron()`
- **Issue:** Per stale goal: individual client_get() + coach join query
- **Fix:** Bulk-fetch clients and coaches before loop
- **Risk class:** MEDIUM refactor

### PERF-006: Activity Log Export Limit
- **File:** `Activity.php`
- **Issue:** `limit(10000)` on activity log export
- **Impact:** Large memory usage for CSV generation
- **Fix:** Streaming export with pagination
- **Risk class:** MEDIUM refactor

### PERF-007: AI Vector Store Memory
- **File:** `Ai_handler.php` (~line 816)
- **Issue:** Full vector store JSON loaded into memory
- **Impact:** Memory spike on AI chat requests
- **Fix:** Lazy load or file streaming
- **Risk class:** MEDIUM refactor

### PERF-008: User Dropdown Limits
- **Files:** Departments, Designations, Payroll
- **Issue:** `limit(500)` on user dropdowns
- **Impact:** Acceptable for now; will degrade at scale
- **Fix:** Autocomplete/search instead of full dropdown
- **Risk class:** LOW priority

---

## Positive Patterns

| Pattern | Location | Benefit |
|---------|----------|---------|
| Dashboard stats file cache | `dashboard_helper.php` (TTL 300s) | Reduces repeated aggregate queries |
| Permission in-request cache | `permission_helper.php` static | Avoids repeated permission DB hits |
| Batch manager lookup | `Departments.php` index | Uses `where_in` not N+1 |
| Query builder dominance | All models | Parameterized, efficient for simple queries |
| Activity log indexes | Migration 003 | Indexed actor_id, action, created_at |

---

## Database Query Statistics

| Metric | Value |
|--------|-------|
| Raw SQL (`->query(`) | 255 occurrences |
| Query builder operations | ~3,910 |
| QB : raw ratio | ~15:1 |
| Raw SQL for DDL | ~60% of raw queries |
| Raw SQL for reports | ~13 in Reports.php |

---

## Caching Inventory

| Type | Implemented | Location |
|------|-------------|----------|
| Dashboard stats | Yes (file, 300s TTL) | dashboard_helper |
| Permission cache | Yes (in-request static) | permission_helper |
| Query result cache | No | — |
| OPcache | Server-level (not app config) | php.ini |
| Redis/Memcached | Config exists, not used | memcached.php |
| HTTP cache headers | Not configured | — |
| CDN | Not configured | — |

---

## Session Performance

- Driver: files (`application/cache/sessions/`)
- Expiration: 7200s (2 hours)
- Regenerate on login: Yes
- File I/O per request: 1 read + 1 write (typical)
- **At scale:** Consider Redis/database session driver

---

## Frontend Performance

- Server-rendered pages (no SPA overhead)
- jQuery AJAX for dynamic sections
- PWA service worker for offline shell
- No asset bundling/minification pipeline observed
- Multiple PDF libraries (5 variants) — consider consolidating

---

## Recommendations by Priority

### Immediate (SAFE, high ROI)
1. Batch user/client name resolution in Reports (PERF-001)
2. Cache attendance column map per request (PERF-002)
3. Enable OPcache in production php.ini

### Short-term (MEDIUM)
4. Move ensure_schema to deployment-time migrations (PERF-004)
5. Paginate activity log exports (PERF-006)
6. Bulk-fetch in coaching cron (PERF-005)

### Long-term (HIGH)
7. Split Reports.php (PERF-003)
8. Evaluate Redis sessions at user scale threshold
9. Consolidate PDF libraries (5 → 1)

See `REFACTOR_PLAN.md` for classified execution plan.
