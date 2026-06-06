# Testing Guidelines — Office Management System

## 1. Current State

| Aspect | Status |
|--------|--------|
| Automated test suite | **Not present** (no `tests/` directory) |
| PHPUnit | Not configured |
| CI/CD pipeline | Not observed |
| Manual testing | Primary QA method |
| Test controllers | `Test_company.php` (dev-only, show_404 in production) |

---

## 2. Testing Strategy (Recommended)

### Priority 1 — Critical Path Manual Tests

Before any production deployment, manually verify:

| Flow | Route | Verify |
|------|-------|--------|
| Login | `/login` | Valid/invalid credentials, lockout, 2FA |
| Logout | `/logout` | Session destroyed |
| Dashboard | `/dashboard` | Loads for each role |
| Attendance punch | `/attendance` | Punch in/out, geo, notifications |
| Leave apply | `/leave/apply` | Submit, approve, reject |
| Payroll generate | `/payroll` | Payslip generation |
| Task CRUD | `/tasks` | Create, assign, status change |
| Reports export | `/reports/*` | CSV downloads |
| Coaching payment | `/coaching-payments` | Razorpay flow (sandbox) |
| Training assessment | `/training-assessment/take/{token}` | Public token flow |
| Permission denied | Any restricted module | Redirect to dashboard |

### Priority 2 — Role-Based Access Tests

Test each role (Admin, Manager, Lead, Staff, Coaching Client):

1. Login as role
2. Verify sidebar shows only permitted modules
3. Attempt direct URL access to restricted module
4. Verify redirect or 403

### Priority 3 — Security Tests

| Test | Method |
|------|--------|
| CSRF on forms | Submit form without token → should fail |
| XSS in text fields | Submit `<script>alert(1)</script>` → should be escaped on display |
| SQL injection | Submit `' OR 1=1 --` in search fields → no error/data leak |
| Session timeout | Wait past timeout → should redirect to login |
| Registration role | Register with admin role_id → should NOT succeed (after SEC-003 fix) |

---

## 3. Recommended Automated Testing Setup

### PHPUnit for CodeIgniter 3

```bash
# Future setup (not currently configured)
composer require --dev phpunit/phpunit ^9.0
```

### Suggested test structure
```
tests/
├── Unit/
│   ├── helpers/
│   │   ├── PermissionHelperTest.php
│   │   └── AttendanceHelperTest.php
│   └── models/
│       ├── UserModelTest.php
│       └── LeaveRequestModelTest.php
├── Integration/
│   ├── AuthTest.php
│   └── AttendancePunchTest.php
└── bootstrap.php
```

### What to test first (highest ROI)

1. **permission_helper.php** — `has_module_access()`, `require_module_access()`
2. **password_helper.php** — `validate_password_strength()`
3. **attendance_helper.php** — workday calculations
4. **User_model** — `get_by_login()` with various inputs
5. **Leave_request_model** — approval workflow state transitions

---

## 4. Testing Environments

| Environment | Purpose | Config |
|-------------|---------|--------|
| Local (WAMP) | Development | `ENVIRONMENT = development` |
| Staging | Pre-production QA | Separate DB, `ENVIRONMENT = testing` |
| Production | Live users | `ENVIRONMENT = production`, `db_debug = FALSE` |

### Environment checklist before production deploy

- [ ] `ENVIRONMENT = production` in index.php or server env
- [ ] `db_debug = FALSE`
- [ ] `display_errors = 0`
- [ ] Cron token changed from placeholder
- [ ] Integration credentials in DB, not config files
- [ ] `/install/schema` blocked or removed
- [ ] `Test_company.php` returns 404

---

## 5. Module-Specific Test Cases

### Attendance
- Punch in during shift hours → success
- Punch in outside shift → appropriate message
- Duplicate punch same day → handled
- Geo-fence enabled → location validated
- Face verification enabled → enrollment required

### Leave
- Apply leave with sufficient balance → pending
- Approve leave → balance deducted, attendance marked
- Reject leave → balance restored
- Overlapping leave dates → rejected

### Coaching Payments
- Create installment → Razorpay order created
- Webhook payment success → installment marked paid
- Webhook invalid signature → rejected

### Training Assessment
- Valid access_token → assessment loads
- Expired/invalid token → error page
- Submit assessment → result calculated correctly
- Timer expiry → auto-submit

### DB Manager
- Compare schemas → diff returned
- Migrate without CSRF token → rejected
- Non-admin access → denied

---

## 6. AJAX/JSON Endpoint Testing

Use browser DevTools Network tab or curl:

```bash
# Example: check login AJAX (with session cookie)
curl -X POST http://localhost/Office_management_system/auth/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "identifier=user@example.com&password=test123&ci_csrf_token=TOKEN"
```

Verify response shapes match `API_DOCUMENTATION.md`.

---

## 7. Database Testing

### Before schema changes
1. Backup database
2. Run migration on staging copy
3. Verify table structure: `DESCRIBE table_name`
4. Verify existing data intact
5. Test affected module CRUD

### Schema bootstrap testing
- Fresh install: run `/install/schema` (dev only)
- Verify all expected tables created
- Verify seed data present (roles, permissions)

---

## 8. Performance Testing

| Test | Tool | Threshold |
|------|------|-----------|
| Dashboard load | Browser DevTools | < 3s |
| Reports export (1000 rows) | Browser DevTools | < 10s |
| Attendance punch | Browser DevTools | < 2s |
| Chat message send | Browser DevTools | < 1s |

Watch for N+1 queries using CI profiler in development.

---

## 9. Regression Testing After Changes

When modifying any file, verify:

1. **Same inputs → same outputs** (compare before/after)
2. **No new PHP errors/warnings** (check with `display_errors = 1` in dev)
3. **No broken routes** (click through affected module)
4. **No permission regressions** (test as non-admin role)
5. **No AJAX response shape changes** (compare JSON keys)

---

## 10. Bug Report Template

```markdown
## Bug Report

**Module:** (e.g., Attendance)
**Route:** (e.g., /attendance/punch)
**Role:** (e.g., Staff, role_id=4)
**Steps to reproduce:**
1. ...
2. ...

**Expected:** ...
**Actual:** ...
**PHP errors:** (from application/logs/ or browser)
**Browser:** ...
**Screenshot:** (if UI issue)
```

---

## 11. What NOT To Test Automatically (Yet)

- External API integrations (Razorpay, Twilio, AI providers) — use sandbox/mock
- Face recognition — requires camera hardware
- WebRTC calls — requires browser media permissions
- Multi-client DB sync — requires multiple MySQL instances

These require manual or integration test environment setup.
