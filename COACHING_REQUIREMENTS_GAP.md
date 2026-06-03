# Coaching Platform Requirements vs Office Management System

Comparison of the **coaching / training business** feature list (from requirements image) against the current **Office Management System (OMS)**.

| Symbol | Meaning |
|--------|---------|
| ✅ | Fully covered or strong fit |
| ⚠️ | Partial — exists but not designed for coaching use case |
| ❌ | Missing — not in OMS |

---

## Summary

| Status | Count |
|--------|-------|
| ✅ Strong fit | 4 |
| ⚠️ Partial | 7 |
| ❌ Missing | 9 |

**Your OMS is built for:** HR, employees, projects, tasks, attendance, payroll (internal), training LMS (internal staff), clients (B2B account records).

**The requirements image is for:** Multi-coach coaching business with **client-facing portal**, **session booking**, **client billing/installments**, and **sales/enquiry** workflows.

---

## Feature-by-feature comparison

| Priority | Required feature | In your OMS? | What you have today | What is missing |
|----------|------------------|--------------|---------------------|-----------------|
| **High** | Multi-user / multi-coach — assign clients & sessions to coaches | ⚠️ | Users, roles, employees, `lead_mapping` (lead → staff), project members, account manager on clients | No **coach** entity, no **assign client to coach**, no **session** assignment |
| **High** | Session scheduling & calendar | ⚠️ | Leave calendar, requirements calendar, attendance, shifts, recruitment interviews | No **coaching session** booking, no coach calendar, no client self-book |
| **High** | Client portal (progress, notes, homework, next steps) | ❌ | `Clients` module is **admin-only** CRM (company, contacts, GST). Training LMS is for **staff learners**, not external coaching clients | Dedicated **client login**, dashboard, session notes, homework, progress visible to client |
| **High** | Progress tracking & goal management | ⚠️ | Performance appraisals, daily activity, tasks/requirements on projects | No **client business goals**, action plans between review sessions, coaching-specific milestones |
| **High** | Strong WhatsApp integration (bulk, reminders, broadcast, enquiry) | ⚠️ | `Whatsapp` controller — Twilio, send to employees, task/report hooks; `Reminders` (email/SMS queue); announcements broadcast | No **bulk broadcast lists**, no **enquiry/inbox**, no Maharashtra-specific list management, limited **client** WhatsApp CRM |
| **High** | Billing & installment plans | ❌ | **Payroll** (employee payslips, pay structures) — internal HR only | Client **invoicing**, program fees, **EMI/installments**, payment status per client |
| **High** | Good Indian payment gateway support | ❌ | None found (no Razorpay, PayU, PhonePe, Cashfree, etc.) | Payment gateway integration + webhook + receipt |
| **High** | Data security & backup | ⚠️ | 2FA settings, encryption for client DB passwords, RBAC permissions, activity log | No automated **backup** UI/schedule, no documented DR; security is basic app-level |
| **High** | Fast implementation | ✅ | OMS already deployed (CodeIgniter, WAMP) | N/A — you have a running base; coaching features need **new modules** |
| **Medium** | Lead management & workshop registration | ⚠️ | `lead_mapping` = map **internal lead role** to staff (not sales leads); **Recruitment** (jobs/candidates); **Clients** CRUD | Sales **lead pipeline**, workshop **registration form**, lead → client conversion funnel |
| **Medium** | Coach payment / commission tracking | ❌ | Payroll pays **employees**; no per-session coach payout | Commission rules, session-wise coach fees, payout reports |
| **Medium** | Reporting & dashboards | ✅ | `Reports`, `Dashboard`, analytics, attendance, payroll, expenses, training reports | Coaching KPIs: active clients per coach, batch performance, **revenue**, retention — not built |
| **Medium** | Document & resource sharing with clients | ⚠️ | Employee documents, training LMS topics, external training videos, project/task attachments | **Client-facing** resource library (SOPs, worksheets, recordings) with access control per client |
| **Medium** | Online course / recorded session library | ⚠️ | `Training_lms`, `External_training` (YouTube watch), training assessment | Built for **employees**, not paying coaching clients; no sellable course catalog |
| **Medium** | Advanced automation (reminders by client progress) | ⚠️ | `Reminders` cron, email templates, leave/attendance reminders | Rules like “if goal not updated in 7 days → WhatsApp client + coach” |
| **Medium** | Custom branding / white label | ⚠️ | Company logo/name in settings, login page branding | Per-tenant or per-coach white label, custom domain, client portal branding |
| **Low** | Marketing automation / funnels | ❌ | Announcements, reminders bulk import | Email/WhatsApp **drip campaigns**, landing pages, funnel stages |

---

## Side-by-side: concept mapping

| Coaching concept | Closest OMS module | Gap |
|------------------|-------------------|-----|
| Coach | Employee / User with role | No coach profile, availability, or rate card |
| Coaching client | Clients (B2B) or Employee | Clients have no login; not “coachee” lifecycle |
| Review session | — | No session entity |
| Homework / action items | Tasks (project) | Tied to projects, not client coaching program |
| Workshop signup | Recruitment apply form | Job application, not workshop registration |
| Program fee + EMI | Payroll | Wrong domain (salary vs client billing) |
| Enquiry from WhatsApp | WhatsApp send UI | One-way send, no CRM enquiry queue |

---

## What your tool already does well (for an office)

| Area | OMS modules |
|------|-------------|
| Internal team & HR | Users, employees, attendance, leave, shifts, departments, payroll |
| Project delivery | Projects, requirements, tasks, timesheets |
| Internal training | Training LMS, assessments, external training videos |
| Communication (basic) | Mail, SendGrid, WhatsApp (Twilio), chats, announcements |
| B2B client records | Clients module (admin CRM) |
| Access control | Roles, permissions matrix, 2FA option |
| Reports (operations) | Attendance, leaves, projects, tasks, payroll, expenses |

---

## Recommended build order (if moving toward coaching platform)

| Phase | Features to add |
|-------|-----------------|
| 1 | Coach entity + assign clients to coaches + session scheduling calendar |
| 2 | Client portal (login, view sessions, notes, homework) |
| 3 | Client billing + Indian payment gateway + installments |
| 4 | WhatsApp enquiry inbox + bulk broadcast + automation rules |
| 5 | Lead/workshop registration + coach commission + coaching dashboards |

---

## Quick verdict

| Question | Answer |
|----------|--------|
| Can OMS run a coaching business **as-is**? | **No** — core coaching workflows are missing |
| Can OMS be **extended**? | **Yes** — strong base for users, calendar patterns, training content, reminders, clients CRM |
| Biggest gaps vs image | Client portal, session scheduling, billing/installments, payment gateway, coach commissions, sales leads/workshops |

---

*Compare with `MODULE_SCREENS_INVENTORY.md` for full list of existing OMS screens.*
