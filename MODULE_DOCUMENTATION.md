# Module Documentation — Office Management System

**End-user guides:** See [USER_GUIDE.md](USER_GUIDE.md) and [docs/user-guide/](docs/user-guide/) for screen-by-screen instructions.

## Module Index

| # | Module | Controller(s) | Model(s) | Permission Key(s) |
|---|--------|---------------|----------|-------------------|
| 1 | Authentication | Auth | User_model | (public + session) |
| 2 | Dashboard | Dashboard | multiple | dashboard |
| 3 | Users | Users | User_model, Face_model | users |
| 4 | Employees | Employees | Employee_model | employees |
| 5 | Departments | Departments | Department_model | departments |
| 6 | Designations | Designations | Designation_model | designations |
| 7 | Shifts | Shifts | Shift_model | shifts |
| 8 | Attendance | Attendance | Attendance_model | attendance |
| 9 | Leave | Leaves, Leave_requests | Leave_request_model, Leave_type_model | leaves, leave_requests |
| 10 | Timesheets | Timesheets | Timesheet_model | timesheets |
| 11 | Payroll | Payroll | Payroll_model | payroll |
| 12 | Expenses | Expenses | Expense_model | expenses |
| 13 | Projects | Projects | Project_model | projects |
| 14 | Tasks | Tasks | Task_model | tasks |
| 15 | Requirements | Requirements | Requirement_model | requirements |
| 16 | My Works | My_works | My_work_model | my_works |
| 17 | Clients (CRM) | Clients | Client_model | clients |
| 18 | Daily Activity | Daily_activity | — | daily_activity |
| 19 | Reports | Reports | Report_model | reports |
| 20 | Analytics | Analytics | Ai_model | analytics |
| 21 | AI Chat | Ai_chat | Ai_model, Setting_model | ai_chat |
| 22 | Training LMS | Training_lms, Training_lms_admin | Training_lms_* | training_lms |
| 23 | Training Assessment | Training_assessment, Training_assessment_take | Training_assessment_model | training_assessment |
| 24 | External Training | External_training | External_training_model | external_training |
| 25 | Training Import | Training_import | — | training_import |
| 26 | Coaching | Coaching_* (14 controllers) | Coaching_model | coaching_* |
| 27 | Recruitment | Recruitment | Recruitment_model | recruitment |
| 28 | Performance | Performance | Performance_model | performance |
| 29 | Assets | Assets | Asset_model | assets |
| 30 | Approvals | Approvals | Approval_model | approvals |
| 31 | Chats | Chats | Chat_model | chats |
| 32 | Calls | Calls | Call_model | calls |
| 33 | Notifications | Notifications | Notification_model | notifications |
| 34 | Announcements | Announcements | Announcement_model | announcements |
| 35 | Reminders | Reminders | Reminder_model | reminders |
| 36 | Mail / Email | Mail, Email_settings, Sendgrid | Setting_model | mail, email_settings |
| 37 | WhatsApp | Whatsapp | — | whatsapp |
| 38 | Settings | Settings, System_settings | Setting_model | settings, system_settings |
| 39 | Permissions | Permissions, Roles | Role_model | permissions, roles |
| 40 | Superadmin | Superadmin | — | superadmin |
| 41 | Profile | Profile | User_model | profile |
| 42 | Activity Log | Activity | — | activity |
| 43 | API Integrations | Api_integrations | Api_integration_model | api_integrations |
| 44 | DB Manager | Db | Client_model | db |
| 45 | Lead Mapping | Lead_mapping | Lead_user_mapping_model | lead_mapping |
| 46 | Statuses | Statuses | Status_model | statuses |
| 47 | Short URL | Short_url | Url_shortener | — |
| 48 | Cron | Cron | multiple | (CLI/token) |
| 49 | Install | Install | — | (dev/admin) |

---

## Non-Existent Modules (Template Expectations)

These were checked and **not found** in the codebase:

- **POS** — Only `clients.pos_url` external link field
- **Restaurant Management**
- **Inventory / Stock Management**
- **Webshop / E-commerce**

---

## Detailed Module Notes

### Authentication (`Auth`)
- **Routes:** `/login`, `/logout`, `/register`, `/forgot-password`, `/reset-password`, `/auth/verify-2fa`
- **Features:** bcrypt passwords, login lockout, 2FA (email OTP), remember-me, Gmail-only registration option, IP whitelist, password expiry
- **Views:** `application/views/auth/`

### Attendance (`Attendance`)
- **Size:** ~1,800 lines — largest operational controller
- **Features:** Punch in/out, geo-fencing, face verification, shift rules, notifications
- **Helpers:** `attendance_helper`, `workday_helper`

### Reports (`Reports`)
- **Size:** ~4,010 lines — largest controller
- **Reports:** Attendance, leave, payroll, projects, tasks, requirements, daily activity, expenses
- **Exports:** CSV, Excel-style HTML exports

### Coaching CRM (14 controllers)
- **Base:** `Coaching_Controller` for admin; portal/payments/webhooks standalone
- **Tables:** 20× `coaching_*` tables
- **Payments:** Razorpay via `Coaching_payments`, webhooks via `Coaching_webhooks`
- **WhatsApp:** `Coaching_whatsapp_crm`, inbound webhook
- **Views:** `application/views/coaching/` (12 subdirectories)

### Training LMS
- **Admin:** `Training_lms_admin` — modules, topics, assignments, enrollments
- **Learner:** `Training_lms` — topic progression, assignment submission
- **Tables:** `training_modules`, `training_topics`, `assignments`, `training_enrollments`

### Training Assessment
- **Admin:** `Training_assessment` — build assessments, questions, assign users
- **Public take:** `Training_assessment_take` — token-based, AJAX, code execution, screenshots
- **Tables:** `ta_*` prefix (legacy unprefixed fallback supported)

### DB Manager (`Db`)
- **Purpose:** Multi-client schema compare, migrate, sync
- **Auth:** `require_module_access('db')` + custom CSRF token
- **Size:** ~2,374 lines, 53 methods

---

## Shared Infrastructure

### Helpers (Autoloaded)
`permission`, `attendance`, `training`, `company`, `api_integration`, `error_handler`, `notification`, `hierarchy_filter`, `data_scope`, `coaching`

### Layout Partials
- `application/views/partials/header.php`
- `application/views/partials/sidebar.php`
- `application/views/partials/footer.php`
- `application/views/partials/page_header.php`

### Activity Logging
- `Activity_logger` library + `activity_log` table
- Table→module mapping in `application/config/table_module_mapping.php`

---

## AJAX-Heavy Modules

| Module | JSON Endpoints | CSRF Excluded |
|--------|----------------|---------------|
| Tasks | Status, comments, board | Yes |
| Attendance | Monthly data | Yes |
| Chats | Messages, reactions, typing | Yes |
| Notifications | Mark read, delete | Yes |
| Ai_chat | Send message, TTS | Yes |
| Whatsapp | Send message | Yes |
| Db | Schema operations | Custom CSRF |
| Training assessment take | Load/save/run code | Token-based |
| Auth | Login AJAX, verify code | Partial |

See `API_DOCUMENTATION.md` for endpoint details.
