# Project Context — Office Management System (OMS)

**Last analyzed:** 2026-06-06  
**Framework:** CodeIgniter 3  
**Runtime:** PHP 8.4 (WAMP)  
**Database:** MySQL / mysqli  
**Environment:** Production-grade internal portal with multi-module HR, operations, training, and coaching CRM

---

## 1. What This System Is

This is a **large internal Office Management System** used for:

- HR and employee lifecycle (employees, departments, designations, shifts)
- Attendance, leave, timesheets, payroll, expenses
- Project, task, requirement, and daily activity tracking
- Internal communication (chat, calls, notifications, announcements)
- Training LMS and proctored assessments
- Life-coaching CRM (clients, sessions, billing, Razorpay, WhatsApp CRM)
- Recruitment, performance appraisals, assets, approvals
- Multi-client database management and schema sync
- AI assistant, analytics, and reporting

### What This System Is NOT

The following modules from generic POS/restaurant templates **do not exist** in this codebase:

| Module | Status | Notes |
|--------|--------|-------|
| POS | Not implemented | `clients.pos_url` stores an external POS link only |
| Restaurant | Not present | No routes, controllers, or models |
| Inventory / stock | Not present | `Assets` module covers IT equipment, not inventory |
| Webshop / e-commerce | Not present | No cart/checkout product catalog |

---

## 2. Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | CodeIgniter 3 (`application/` + `system/`) |
| Frontend | Server-rendered PHP views, Bootstrap-style UI, jQuery AJAX |
| Database | MySQL (`admin_stadmin_internal_portal` in local config) |
| Session | File-based (`application/cache/sessions`) |
| Auth | Session + optional 2FA + remember-me tokens |
| Authorization | Role-based module permissions (`permissions` table) |
| PDF | Custom libraries (FPDF/native/simple variants) |
| PWA | Service worker in `assets/pwa/` |
| Composer | Not used (`composer_autoload = FALSE`) |

---

## 3. Directory Structure

```
Office_management_system/
├── application/
│   ├── config/          # Routes, autoload, hooks, constants, integrations
│   ├── controllers/     # 73 controllers
│   ├── core/            # MY_Controller, Coaching_Controller, MY_Exceptions
│   ├── helpers/         # 26 helpers (13 autoloaded)
│   ├── hooks/           # AuthHook (global auth middleware)
│   ├── libraries/       # 10 custom libraries (AI, PDF, URL shortener)
│   ├── migrations/      # 6 CI migrations (disabled by default)
│   ├── models/          # 46 models
│   └── views/           # ~50 view directories
├── system/              # CodeIgniter 3 core (PHP 8.4 patched)
├── assets/              # CSS, JS, PWA, sample CSVs
├── database/            # Module SQL scripts and seeds
├── O_db/                # Core schema dump (employmanagement.sql)
├── uploads/             # Runtime file uploads
└── index.php            # Front controller (default env: development)
```

---

## 4. Business Domains

### Core HR & Operations
Employees, departments, designations, shifts, attendance, leave, payroll, expenses, performance, assets, approvals, settings, roles, permissions, users, profile, superadmin.

### Projects & Work
Projects, tasks, requirements, my works, statuses, daily activity, timesheets, clients (CRM), lead mapping, activity log.

### Communication
Chats, calls (WebRTC signaling), notifications (in-app + push), announcements, reminders, mail, SendGrid, WhatsApp.

### Training
External training links, LMS (modules/topics/assignments/enrollments), assessment builder, public assessment taking (token-based), bulk import.

### Coaching CRM
Full submodule: coaches, clients, sessions, goals, leads, workshops, billing, installments, Razorpay payments, portal, WhatsApp CRM, webhooks, reports, automation.

### Admin & Tools
Reports (cross-module), analytics (AI), AI chat, API integrations UI, DB manager (multi-tenant schema sync), install schema (dev), cron jobs.

---

## 5. Entry Points & Routing

- **Default controller:** `auth` (login-first)
- **Dashboard:** `/dashboard`
- **Routes file:** `application/config/routes.php` (~670 lines of explicit friendly URLs)
- **404 handler:** `errors/page_missing`

---

## 6. Authentication Summary

- Global `AuthHook` on `post_controller_constructor`
- Public URIs: auth flows, training assessment take, coaching webhooks, workshop registration
- Session stores: `user_id`, `role_id`, `email`, `last_activity`, `session_id`
- Roles: Admin (1), Manager (2), Lead (3), Staff (4), Coaching Client (5)

See `SECURITY_AUDIT.md` for full auth/authz analysis.

---

## 7. Schema Strategy

Schema is **distributed**, not migration-driven:

1. Core dump: `O_db/employmanagement.sql`
2. Dev installer: `Install.php` (`/install/schema`)
3. Runtime bootstrap: `ensure_schema()` in models/controllers
4. Registry: `application/config/schema_automation.php`
5. CI migrations: 6 files, **disabled** (`migration_enabled = FALSE`)

Estimated **100+ tables** in a fully bootstrapped instance.

---

## 8. Third-Party Integrations

| Service | Usage |
|---------|-------|
| SMTP / Gmail | Email, auth OTP, reminders |
| SendGrid | Transactional email |
| Twilio / WhatsApp Business | Messaging, coaching CRM |
| Razorpay | Coaching payments |
| Gemini / OpenAI / OpenRouter / Hugging Face | AI chat, analytics |
| Azure Speech | Text-to-speech |
| Web Push | PWA notifications |
| Face recognition | User enrollment |

Credentials stored in `settings`, `api_integrations`, and config files (env fallbacks).

---

## 9. Coding Patterns (Observed)

- Controllers extend `CI_Controller` or `Coaching_Controller`
- Models extend `CI_Model`; many include `ensure_schema()`
- RBAC via `require_module_access('module_key')` in constructors
- Data scoping via `data_scope_helper`, `hierarchy_filter_helper`
- Notifications via `notification_helper` (autoloaded)
- Change tracking via `change_tracker_helper`
- Mixed validation: manual checks + `validation_helper` + limited `form_validation`
- AJAX JSON endpoints return ad-hoc shapes (`success`, `ok`, `status`)

---

## 10. Production Assumptions

- Real users depend on attendance, payroll, leave, and coaching billing workflows
- Schema may evolve at runtime on first module access
- Admin role (role_id = 1) bypasses route-level RBAC
- Multi-client DB tooling exists for managed client deployments
- PHP 8.4 compatibility patches applied to `system/` core

---

## 11. Related Documents

| Document | Purpose |
|----------|---------|
| `PROJECT_ARCHITECTURE.md` | Technical architecture and request flow |
| `MODULE_DOCUMENTATION.md` | Per-module controller/model/view map |
| `DATABASE_DOCUMENTATION.md` | Tables, relationships, schema sources |
| `API_DOCUMENTATION.md` | JSON/AJAX/webhook endpoints |
| `SECURITY_AUDIT.md` | Security findings and remediation |
| `PERFORMANCE_AUDIT.md` | Performance hotspots |
| `DEVELOPMENT_GUIDELINES.md` | How to extend the codebase safely |
| `TESTING_GUIDELINES.md` | Testing strategy |
| `TECHNICAL_DEBT_REPORT.md` | Debt inventory |
| `REFACTOR_PLAN.md` | Classified refactoring roadmap |
| `REFACTOR_REPORT.md` | Safe refactoring execution log |
| `FINAL_PROJECT_HEALTH_REPORT.md` | Overall health scores |
