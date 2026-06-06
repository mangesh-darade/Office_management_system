# Project Architecture — Office Management System

## 1. Architectural Style

**Monolithic MVC (CodeIgniter 3)** with:

- Fat controllers (especially Reports, Attendance, Auth, Db)
- Mixed fat/thin models
- Procedural helpers for cross-cutting concerns
- Global authentication hook (middleware-like)
- Runtime schema bootstrapping (non-traditional migration-first approach)
- No service layer, no DI container, no REST API framework

---

## 2. Request Lifecycle

```
HTTP Request
    │
    ▼
index.php (ENVIRONMENT, error reporting, constants)
    │
    ▼
system/core/CodeIgniter.php (bootstrap)
    │
    ├── Autoload: database, session
    ├── Autoload helpers: permission, attendance, training, company, etc.
    │
    ▼
Router (routes.php → controller/method)
    │
    ▼
Controller instantiated
    │
    ▼
Hook: AuthHook::check (post_controller_constructor)
    ├── Public URI whitelist → skip auth
    ├── Session required → redirect login
    ├── Maintenance / IP whitelist / session timeout
    ├── Single-session check
    └── Route RBAC (non-admin)
    │
    ▼
Controller::__construct
    ├── require_module_access() (many controllers)
    ├── coaching_require_access() (Coaching_Controller)
    └── Model/library loads
    │
    ▼
Controller method
    ├── Query models / DB
    ├── Return view OR json_encode (AJAX)
    └── Redirect
    │
    ▼
View (partials: header, sidebar, footer)
```

---

## 3. Layer Responsibilities

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Controllers | `application/controllers/` | HTTP I/O, validation, orchestration, some business logic |
| Models | `application/models/` | DB access, schema bootstrap, domain logic (mixed) |
| Views | `application/views/` | HTML presentation, inline PHP, jQuery |
| Helpers | `application/helpers/` | Reusable functions (permissions, coaching, training) |
| Libraries | `application/libraries/` | AI handler, PDF generators, URL shortener |
| Hooks | `application/hooks/` | Global auth gate |
| Core extensions | `application/core/` | Base controllers, custom exceptions |
| Config | `application/config/` | Routes, constants, integration credentials |

---

## 4. Controller Hierarchy

```
CI_Controller (system/core/Controller.php)
    │
    ├── MY_Controller (empty stub, loads Coaching_Controller)
    │
    ├── CI_Controller (standard — 60+ controllers)
    │       Auth, Dashboard, Employees, Reports, Tasks, ...
    │
    └── Coaching_Controller (coaching RBAC bootstrap)
            Coaching_clients, Coaching_billing, Coaching_sessions, ...
            (11 admin coaching controllers)

Exceptions (no Coaching_Controller):
    Coaching_portal, Coaching_payments, Coaching_webhooks
```

---

## 5. Authorization Architecture

### Three layers

1. **AuthHook (global)** — Maps controller name → module key → `permissions` table
2. **require_module_access()** — Controller constructor guard (~45 controllers)
3. **coaching_require_access()** — Coaching submodule guard

### Role model

```
roles (1=Admin, 2=Manager, 3=Lead, 4=Staff, 5=Coaching Client)
    └── permissions (role_id, module, can_access)
    └── user_module_access (per-user overrides via System_settings)
```

Admin (`role_id = 1`) bypasses route RBAC entirely.

---

## 6. Data Access Patterns

| Pattern | Usage | Example |
|---------|-------|---------|
| Query Builder | ~93% of data access | `$this->db->where()->get('users')` |
| Raw SQL | DDL, complex reports | `ensure_schema()`, Reports aggregations |
| Runtime schema checks | Widespread | `field_exists()`, `table_exists()` |
| Lazy table creation | 40+ sites | `ensure_schema()` on first use |

---

## 7. Module Boundaries

Modules are **logical**, not physically isolated:

- Shared `users`, `employees`, `settings`, `permissions` tables
- Shared layout partials (`partials/header`, `sidebar`, `footer`)
- Shared notification and audit infrastructure
- Coaching module uses dedicated `coaching_*` tables + helpers
- Training uses `training_*` and `ta_*` table prefixes

---

## 8. Integration Architecture

```
┌─────────────────┐     ┌──────────────────┐
│  Settings UI    │────▶│  settings table  │
│  Api_integrations│────▶│ api_integrations │
└─────────────────┘     └────────┬─────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         ▼                       ▼                       ▼
   Ai_handler.php          coaching_helper          email_helper
   (multi-provider)       (Razorpay, notify)       (SMTP/SendGrid)
         │                       │                       │
         ▼                       ▼                       ▼
   Ai_chat, Analytics     Coaching_payments        Mail, Reminders
                          Coaching_webhooks
```

External webhooks (no session):
- `POST /coaching-webhooks/razorpay`
- `POST /coaching-webhooks/whatsapp-inbound`

---

## 9. Multi-Tenant Database Tool

`Db` controller provides schema comparison and migration between:

- Master portal database
- Per-client databases (credentials stored on `clients` table)

Creates audit tables: `dm_manager`, `client_migrations`.

This is an **admin tool**, not application multi-tenancy at runtime.

---

## 10. Frontend Architecture

- Server-rendered views with Bootstrap-style components
- jQuery AJAX for dynamic UI (tasks, chat, notifications, attendance)
- PWA support via service worker
- No SPA framework (React/Vue/Angular)
- CSRF token: `ci_csrf_token` (broad exclusions for AJAX routes)

---

## 11. Caching & Performance

| Mechanism | Location |
|-----------|----------|
| Dashboard stats file cache | `dashboard_helper.php` (TTL 300s) |
| Permission in-request cache | `permission_helper.php` static |
| CI query cache | Disabled by default |
| Session files | `application/cache/sessions/` |

---

## 12. Error Handling

- `MY_Exceptions` — Custom 403 page; admin redirect safety net
- `error_handler_helper` — Centralized error handling (autoloaded)
- Environment-based `db_debug` and `log_threshold`
- Custom 404 via `Errors` controller

---

## 13. Deployment Topology (Typical)

```
Browser → Apache/Nginx (WAMP)
       → PHP 8.4 + CI3
       → MySQL (single primary DB)
       → File uploads (uploads/)
       → Optional: per-client MySQL via Db tool
       → External: SMTP, Twilio, Razorpay, AI APIs
```

---

## 14. Architecture Strengths

- Consistent CI3 patterns familiar to PHP developers
- Global auth hook reduces per-controller auth boilerplate
- Module permission matrix is flexible
- Coaching submodule has dedicated base controller
- Query builder dominant (SQL injection resistant by default)

---

## 15. Architecture Weaknesses

- No formal service/repository layer — business logic scattered
- God controllers (Reports: 4,010 lines)
- Runtime schema mutation on request path
- No versioned REST API — JSON endpoints are ad-hoc AJAX
- Fail-open route RBAC when permission rows missing
- Schema sources fragmented (dump + install + ensure_schema + migrations)

See `TECHNICAL_DEBT_REPORT.md` and `FINAL_PROJECT_HEALTH_REPORT.md` for scored assessment.
