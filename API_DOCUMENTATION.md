# API Documentation — Office Management System

## Overview

This application has **no versioned REST API**. All JSON endpoints are **session-bound controller actions** used by internal AJAX, plus a few **public webhook/token endpoints**.

There is no `api/v1/` namespace. The `api/.*` CSRF exclusion in config is unused (no matching controllers).

---

## 1. Public Endpoints (No Session)

### Coaching Webhooks

| Method | Route | Controller | Auth |
|--------|-------|------------|------|
| POST | `/coaching-webhooks/razorpay` | Coaching_webhooks::razorpay | Razorpay HMAC signature |
| POST | `/coaching-webhooks/whatsapp-inbound` | Coaching_webhooks::whatsapp_inbound | None (Twilio POST) |

### Training Assessment (Token-Based)

| Method | Route | Auth | Response |
|--------|-------|------|----------|
| POST | `/training-assessment/ajax-load-question` | `access_token` | `{ok, html, csrf}` |
| POST | `/training-assessment/ajax-save-answer` | `access_token` | `{ok, csrf}` |
| POST | `/training-assessment/ajax-run-code` | `access_token` | Code execution result |
| POST | `/training-assessment/ajax-timer-sync` | `access_token` | Timer state |
| POST | `/training-assessment/ajax-upload-screenshot` | `access_token` | Upload status |
| POST | `/training-assessment/submit-assessment` | `access_token` | Submit result |
| GET | `/training-assessment/result-token/{token}` | Token + optional HMAC | HTML result page |

Token: 64-char `access_token` on `ta_assessment_users` row.

### Cron (CLI or Token)

| Route | Auth |
|-------|------|
| `/cron/*` | CLI or `?token=` query param |

> **Warning:** Cron token is hardcoded placeholder — must be changed for production.

### Auth (Pre-Login)

| Route | Method | Response Shape |
|-------|--------|----------------|
| `/auth/login` | POST (AJAX) | `{success, redirect}` or `{success, false, error, require_2fa}` |
| `/auth/send-verify-code` | POST | `{valid, message}` or `{ok, false, error}` |
| `/auth/verify-2fa` | POST | Redirect or JSON |

---

## 2. Authenticated AJAX Endpoints

All require valid session (AuthHook). CSRF excluded for many (see Security Audit).

### Auth & Users

| Route | Method | Response |
|-------|--------|----------|
| `/users/check-email` | POST | `{exists: bool}` |
| `/users/check-phone` | POST | `{exists: bool}` |
| `/users/save-face` | POST | `{success, message}` |

### Tasks

| Route | Method | Response |
|-------|--------|----------|
| `/tasks/update-status` | POST | `{success, message}` |
| `/tasks/add-comment` | POST | `{success, ...}` |
| Various board AJAX | POST | JSON status objects |

### Attendance

| Route | Method | Response |
|-------|--------|----------|
| `/attendance/get-monthly-data` | POST | Monthly attendance JSON |

### Notifications

| Route | Method | Response |
|-------|--------|----------|
| `/notifications/mark-read` | POST | `{success}` |
| `/notifications/delete` | POST | `{success}` |
| `/notifications/get-unread-count` | GET | `{count}` |

### Chats & Calls

| Route | Method | Response |
|-------|--------|----------|
| `/chats/send-message` | POST | Message object |
| `/chats/get-messages` | GET/POST | Message array |
| `/chats/set-typing` | POST | `{success}` |
| `/calls/signaling` | POST | WebRTC signaling data |

### AI Chat

| Route | Method | Response |
|-------|--------|----------|
| `/ai-chat/send-message` | POST | `{status, data/message}` |
| `/ai-chat/tts` | POST | Audio URL or binary |
| `/ai-chat/execute-query` | POST | SQL query results (restricted) |

### WhatsApp

| Route | Method | Response |
|-------|--------|----------|
| `/whatsapp/send` | POST | `{success, message, details}` |
| `/whatsapp/send-task-report` | POST | `{success, ...}` |

### Coaching Payments

| Route | Method | Response |
|-------|--------|----------|
| `/coaching-payments/verify` | POST | `{success, redirect}` |

### DB Manager

| Route | Method | Response |
|-------|--------|----------|
| `/db/compare` | POST | Schema diff JSON |
| `/db/migrate` | POST | Migration result JSON |
| `/db/execute-query` | POST | Query result JSON |

Uses custom `db_csrf_token` header (separate from CI CSRF).

### Leave Requests

| Route | Method | Response |
|-------|--------|----------|
| `/leave/employee-tasks` | POST | Task list JSON |

---

## 3. Common JSON Response Patterns

```json
// Success pattern (most modules)
{"success": true, "message": "...", "redirect": "..."}

// Training assessment
{"ok": true, "html": "...", "csrf": "..."}

// AI chat
{"status": "success", "data": {...}}
{"status": "error", "message": "..."}

// Duplicate check
{"exists": true}

// WhatsApp
{"success": true, "message": "...", "details": {...}}
```

---

## 4. API Integrations (Admin UI — Not JSON API)

**Controller:** `Api_integrations`  
**Routes:** `/api-integrations`, `/create`, `/store`, `/edit/{id}`, `/update/{id}`, `/delete/{id}`  
**Auth:** `require_module_access(['api_integrations', 'settings'])`  
**Purpose:** CRUD HTML UI for storing Twilio, SendGrid, SMTP credentials in `api_integrations` table.

---

## 5. AJAX Detection

Controllers use:
- `$this->input->is_ajax_request()` — checks `X-Requested-With: XMLHttpRequest`
- Direct `json_encode` + `Content-Type: application/json` without AJAX check

> **Note:** `is_ajax_request()` is NOT a security control — header can be spoofed.

---

## 6. CSRF Policy for AJAX

Global CSRF enabled (`ci_csrf_token`). Broad exclusions in `config.php`:

```
tasks/*, attendance/*, notifications/*, chats/*, calls/*, ai_chat/*, whatsapp/*, cron/*, coaching-webhooks/*
```

State-changing AJAX on excluded routes relies on session cookie only — CSRF-vulnerable if attacker triggers authenticated browser.

Training assessment returns fresh `csrf` hash in JSON responses for subsequent calls.

---

## 7. External Integration Callbacks

| Provider | Direction | Endpoint |
|----------|-----------|----------|
| Razorpay | Inbound webhook | `/coaching-webhooks/razorpay` |
| Twilio WhatsApp | Inbound webhook | `/coaching-webhooks/whatsapp-inbound` |
| SendGrid | Outbound only | Via Mail/Sendgrid controllers |
| AI providers | Outbound only | Via Ai_handler library |

---

## 8. Recommendations for API Consumers

This system is **not designed for third-party API consumption**. If external integration is needed:

1. Do NOT expose existing AJAX endpoints externally
2. Build a dedicated authenticated API layer with versioning
3. Use API keys or OAuth, not session cookies
4. Re-enable CSRF or use token-based auth for all state-changing endpoints
5. Document response schemas formally

See `SECURITY_AUDIT.md` for security implications.
