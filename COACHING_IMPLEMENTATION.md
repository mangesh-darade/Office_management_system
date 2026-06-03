# Coaching Platform — Implementation Guide

All features from `COACHING_REQUIREMENTS_GAP.md` have been **scaffolded** in the Office Management System.

## Access

| Who | URL | Notes |
|-----|-----|--------|
| Admin / staff | `/coaching` | Enable **Coaching Platform** permissions in Permissions (Super Admin has all) |
| Coaching client | `/coaching-portal` | Role ID **5** (`ROLE_COACHING_CLIENT`) — auto-created when enabling portal on client |
| Public workshop signup | `/coaching/workshop-register/{id}` | Workshop must be **published** |

## Modules implemented

| # | Feature | Route | Status |
|---|---------|-------|--------|
| 1 | Multi-coach + assign clients | `coaching-coaches`, `coaching-clients` | Done |
| 2 | Session scheduling & calendar | `coaching-sessions`, `coaching-sessions/calendar` | Done |
| 3 | Client portal | `coaching-portal` | Done |
| 4 | Goals & homework | `coaching-goals` | Done |
| 5 | WhatsApp CRM + broadcast | `coaching-whatsapp-crm` | Done (uses Twilio creds) |
| 6 | Billing & installments | `coaching-billing` | Done |
| 7 | Payment gateway (India) | `coaching-admin` + `coaching-payments/pay/{id}` | Stub (Razorpay keys + manual confirm) |
| 8 | Data backup | `coaching-admin/backup` | SQL export |
| 9 | Lead management | `coaching-leads` | Done |
| 10 | Workshop registration | `coaching-leads/workshops`, public register URL | Done |
| 11 | Coach commission / payouts | `coaching-billing/payouts` | Done |
| 12 | Coaching reports | `coaching-reports` | Done |
| 13 | Document & resources | `coaching-resources` | Done |
| 14 | Automation rules | `coaching-admin` | Basic (stale goals cron hook) |
| 15 | Portal branding | `coaching-admin` | Done |
| 16 | Marketing funnels | — | Not built (low priority) |

## Setup steps

1. Log in as **Super Admin**.
2. Open **Permissions** → enable **Coaching Platform** for your roles.
3. Visit **Coaching** in the sidebar (tables auto-create on first load).
4. Add **Coaches** (pick existing users).
5. Add **Clients** — check **Enable client portal** to create login (password shown once).
6. Add **role_id = 5** in `roles` table if missing: `INSERT INTO roles (id, role_name) VALUES (5, 'Coaching Client') ON DUPLICATE KEY UPDATE role_name='Coaching Client';`
7. Configure **Razorpay** keys under Coaching → Settings (optional).
8. Configure **WhatsApp** under API Integrations / existing Twilio setup for broadcasts.

## Files added

- `application/helpers/coaching_helper.php` — schema + helpers
- `application/models/Coaching_model.php`
- Controllers: `Coaching.php`, `Coaching_coaches.php`, `Coaching_clients.php`, `Coaching_sessions.php`, `Coaching_goals.php`, `Coaching_leads.php`, `Coaching_billing.php`, `Coaching_portal.php`, `Coaching_resources.php`, `Coaching_whatsapp_crm.php`, `Coaching_reports.php`, `Coaching_admin.php`, `Coaching_payments.php`
- Views: `application/views/coaching/**`
- Routes: end of `application/config/routes.php`

## Production features (implemented)

### Razorpay Checkout
- Client pays at `coaching-payments/pay/{installment_id}`
- Creates Razorpay order via API; Checkout.js on pay page
- `POST coaching-payments/verify` verifies signature and marks installment paid
- Webhook: `coaching-webhooks/razorpay` (set in Razorpay Dashboard + webhook secret in Coaching → Settings)

### WhatsApp inbound enquiries
- Twilio webhook URL: `coaching-webhooks/whatsapp-inbound`
- Incoming messages create **enquiry** + **lead** (if new phone)

### Session email reminders
- **Confirmation email** when a new session is scheduled (if SMTP configured in Settings → Email)
- **24h** and **1h** reminders via cron: `cron/coaching-session-reminders?token=...`
- Included in `cron/run_all`

## Optional next

- Marketing funnel module
- PayU / PhonePe gateways
- Auto-reply on WhatsApp inbound
