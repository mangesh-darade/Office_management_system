# Security Audit — Office Management System

**Audit date:** 2026-06-06  
**Scope:** Full application layer + CI3 configuration  
**Runtime:** PHP 8.4 / CodeIgniter 3

---

## Executive Summary

| Area | Score | Status |
|------|-------|--------|
| Authentication design | 75/100 | Good foundation, critical config bugs |
| Authorization (RBAC) | 70/100 | Functional, fail-open gaps |
| Input validation | 60/100 | Ad-hoc, inconsistent |
| SQL injection protection | 80/100 | Query builder dominant; few risky areas |
| XSS protection | 65/100 | htmlspecialchars widespread; rich text gaps |
| CSRF protection | 55/100 | Enabled but broadly excluded |
| Session security | 70/100 | Good defaults; match_ip disabled |
| Audit logging | 50/100 | Partial implementation |
| Webhook security | 45/100 | Razorpay signed; Twilio unsigned |

---

## Critical Findings (P0)

### SEC-001: 2FA Settings Key Mismatch
- **UI saves:** `security_enable_2fa`
- **Auth reads:** `security_2fa_enabled`
- **Impact:** Two-factor authentication never activates despite UI toggle
- **Files:** `Auth.php`, `application/views/settings/index.php`
- **Risk:** HIGH

### SEC-002: IP Whitelist Settings Key Mismatch
- **UI saves:** `security_enable_ip_whitelist`
- **Auth reads:** `security_ip_whitelist_enabled`
- **Impact:** IP whitelist never activates
- **Risk:** HIGH

### SEC-003: Open Registration Role Escalation
- **Issue:** `Auth::register()` accepts `role_id` from POST; role picker may include Admin
- **Impact:** Self-registration can grant elevated privileges
- **Risk:** HIGH
- **Recommendation:** Default to Staff (4) or pending approval; remove role picker from public register

---

## High Findings (P1)

### SEC-004: Broad CSRF Exclusions
- **Issue:** State-changing AJAX routes excluded from CSRF (tasks, chats, notifications, AI chat, WhatsApp)
- **Impact:** CSRF attacks against authenticated users
- **Risk:** HIGH
- **File:** `application/config/config.php` → `csrf_exclude_uris`

### SEC-005: Cron Token Hardcoded
- **Issue:** `Cron.php` uses placeholder `CHANGE_THIS_TO_A_SECURE_RANDOM_TOKEN`
- **Risk:** HIGH if cron URL is reachable via web

### SEC-006: Twilio Webhook Unsigned
- **Route:** `POST /coaching-webhooks/whatsapp-inbound`
- **Issue:** No Twilio signature validation
- **Risk:** HIGH — spoofed inbound messages

### SEC-007: AI Chat SQL Execution
- **File:** `Ai_chat.php` → `execute_safe_query()`
- **Issue:** LLM-generated SQL with regex guards only
- **Risk:** MEDIUM-HIGH — potential data exfiltration if bypassed

---

## Medium Findings (P2)

### SEC-008: Route RBAC Fail-Open
- When no permission rows exist for a controller, AuthHook grants access (non-admin)
- **Risk:** MEDIUM

### SEC-009: Remember-Me Incomplete
- Cookie/token written on login; no auto-login handler on subsequent visits
- **Risk:** MEDIUM (feature broken, not exploitable)

### SEC-010: Single-Session Weak
- Compares stored vs current session_id only; doesn't invalidate other sessions
- **Risk:** MEDIUM

### SEC-011: Rich Text XSS
- Daily activity, announcements may store HTML without consistent sanitization on save
- Views may echo without escaping
- **Risk:** MEDIUM

### SEC-012: Training Assessment HTML in JSON
- `ajax_load_question` returns HTML fragments — XSS if question content is attacker-controlled
- **Risk:** MEDIUM

### SEC-013: Open Redirect Partial
- `redirect_url` stored from `current_url()` with partial blocklist
- **Risk:** MEDIUM

### SEC-014: Db Controller Power
- DDL/DML on client databases; gated by module permission + custom CSRF
- **Risk:** MEDIUM (admin-only but high impact)

### SEC-015: Security Audit Settings Unwired
- `security_audit_settings` and `security_audit_data` saved but never read for logging
- **Risk:** LOW-MEDIUM

---

## Low Findings (P3)

| ID | Issue | Risk |
|----|-------|------|
| SEC-016 | Logout doesn't clear remember_me cookie/tokens | LOW |
| SEC-017 | `global_xss_filtering = FALSE` | LOW (mitigated by manual escaping) |
| SEC-018 | `csrf_regenerate = FALSE` | LOW |
| SEC-019 | `sess_match_ip = FALSE` | LOW |
| SEC-020 | `security_2fa_required_admin` setting unused | INFO |

---

## Authentication Flow (Verified)

```
POST /auth/login
  → get_by_login() [parameterized QB]
  → check_ip_whitelist()
  → login_attempts rate limit
  → password_verify(bcrypt)
  → password expiry check
  → account status check
  → email_verified check
  → 2FA branch (if enabled — currently broken due to SEC-001)
  → sess_regenerate(TRUE)
  → session: user_id, role_id, email, last_activity, session_id
  → remember_me token (optional)
  → Security_audit_model::log() (if enabled)
  → redirect
```

---

## Authorization Flow (Verified)

```
AuthHook::check
  → public URI whitelist?
  → session user_id required
  → maintenance mode (admin bypass)
  → IP whitelist (authenticated — broken due to SEC-002)
  → session timeout (last_activity)
  → single-session check
  → role_id === 1 → bypass RBAC
  → controller → module map → permissions table
  → denied → redirect dashboard

Controller::__construct
  → require_module_access('module_key') [~45 controllers]
  → coaching_require_access() [Coaching_Controller]
```

---

## SQL Injection Assessment

| Pattern | Count | Risk |
|---------|-------|------|
| Query Builder (parameterized) | ~3,910 ops | LOW |
| Raw SQL (DDL/schema) | ~150 | LOW (no user input) |
| Raw SQL (reports) | ~13 in Reports.php | LOW-MEDIUM (mostly bound) |
| User input in raw SQL | Not found in models reviewed | — |
| AI-generated SQL | Ai_chat.php | MEDIUM-HIGH |

---

## XSS Assessment

- **Views:** 200+ files use `htmlspecialchars()` in newer/coaching views
- **Gaps:** Rich text fields (Summernote), some legacy views echo DB fields directly
- **JSON HTML:** Training assessment returns HTML in AJAX responses

---

## Remediation Priority Matrix

| Priority | Item | Effort |
|----------|------|--------|
| P0 | Fix settings key mismatches (SEC-001, SEC-002) | Small |
| P0 | Remove role picker from public register (SEC-003) | Small |
| P1 | Reduce CSRF exclusions; pass token from layout (SEC-004) | Medium |
| P1 | Rotate cron token to env/settings (SEC-005) | Small |
| P1 | Add Twilio signature validation (SEC-006) | Medium |
| P2 | Wire security audit settings logging (SEC-015) | Small |
| P2 | Sanitize rich text on input (SEC-011) | Medium |
| P3 | Complete remember-me or remove feature (SEC-009) | Medium |

---

## Compliance Notes

- Passwords: bcrypt via `password_hash()` / `password_verify()`
- OTPs: hashed before storage
- Session cookies: HttpOnly, Secure on HTTPS
- No evidence of PCI-DSS scope (Razorpay handles card data externally)
- GDPR: employee PII in users/employees; no formal data retention policy in code

See `FINAL_PROJECT_HEALTH_REPORT.md` for scored recommendations list.
