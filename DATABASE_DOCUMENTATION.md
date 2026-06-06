# Database Documentation — Office Management System

## 1. Connection Configuration

**File:** `application/config/database.php`

| Setting | Value |
|---------|-------|
| Driver | mysqli |
| Query Builder | Enabled |
| Host | localhost |
| Database | `admin_stadmin_internal_portal` |
| Charset | utf8 / utf8_general_ci |
| Prefix | None |
| Debug | ON when ENVIRONMENT ≠ production |

> **Note:** Canonical dump `O_db/employmanagement.sql` targets database name `employmanagement`. Live config may differ per environment.

---

## 2. Schema Sources

| Source | Path | Role |
|--------|------|------|
| Core dump | `O_db/employmanagement.sql` | 56 core tables with FKs |
| Dev installer | `application/controllers/Install.php` | Idempotent CREATE TABLE (~35 tables) |
| Module SQL | `database/*.sql` | Training LMS, assessment, permission seeds |
| CI migrations | `application/migrations/` (6 files) | **Disabled** (`migration_enabled = FALSE`) |
| Runtime bootstrap | `ensure_schema()` in models/controllers | Lazy table creation (40+ sites) |
| Schema registry | `application/config/schema_automation.php` | Auto-bootstrap hook registry |

---

## 3. Entity Relationship Overview

```
users ──1:1── employees
  │
  ├── attendance, leave_requests, leave_balances
  ├── projects (manager_id), project_members
  ├── tasks (assigned_to, created_by)
  ├── payslips, salary_structures, expenses
  ├── training_enrollments, ta_assessment_users
  └── coaching_coaches, coaching_clients (via user link)

clients ── requirements, client_contacts
       └── coaching_clients.crm_client_id

projects ── tasks, requirements, project_members
        └── optional db_name (client DB link)

training_modules ── training_topics ── assignments
                └── training_enrollments

ta_assessments ── ta_questions ── ta_question_options
              └── ta_assessment_users ── ta_user_answers ── ta_results

coaching_clients ── coaching_sessions, coaching_goals, coaching_invoices
                └── coaching_installments, coaching_homework
```

---

## 4. Table Inventory by Domain

### Auth & RBAC (9 tables)
`users`, `roles`, `permissions`, `role_permissions`, `user_module_access`, `login_attempts`, `remember_tokens`, `sessions`, `lead_user_mapping`

### HR (10 tables)
`employees`, `employee_documents`, `departments`, `designations`, `shifts`, `holidays`, `statuses`, `performance_appraisals`, `user_faces`, `salary_structures`

### Time & Attendance (6 tables)
`attendance`, `attendance_logs`, `daily_work_logs`, `timesheets`, `timesheet_entries`, `leave_types`

### Leave (3 tables)
`leave_requests`, `leave_balances`, `leave_approvals`

### Projects & Tasks (10 tables)
`projects`, `project_members`, `project_status_history`, `tasks`, `task_comments`, `task_attachments`, `task_activity`, `my_works`, `my_work_activity`, `my_work_comments`

### CRM (7 tables)
`clients`, `client_contacts`, `requirements`, `requirement_attachments`, `requirement_versions`, `requirement_comments`, `lead_user_mapping`

### Payroll & Expenses (4 tables)
`payslips`, `expense_categories`, `expenses`, `salary_structures`

### Training LMS (6 tables)
`training_modules`, `training_topics`, `assignments`, `assignment_submissions`, `training_enrollments`, `training_topic_completions`, `sma_external_trainings`

### Training Assessment (7 tables)
`ta_assessments`, `ta_questions`, `ta_question_options`, `ta_assessment_users`, `ta_user_answers`, `ta_results`, `ta_attempt_screenshots`

### Coaching (20 tables)
`coaching_coaches`, `coaching_clients`, `coaching_client_coaches`, `coaching_sessions`, `coaching_goals`, `coaching_homework`, `coaching_leads`, `coaching_workshops`, `coaching_workshop_registrations`, `coaching_programs`, `coaching_invoices`, `coaching_installments`, `coaching_coach_payouts`, `coaching_client_resources`, `coaching_whatsapp_enquiries`, `coaching_whatsapp_broadcasts`, `coaching_automation_rules`, `coaching_payment_settings`, `coaching_branding`, `coaching_payment_orders`

### Communication (12 tables)
`conversations`, `conversation_participants`, `messages`, `message_reads`, `message_reactions`, `typing_indicators`, `user_online_status`, `calls`, `call_participants`, `signaling_messages`, `notifications`, `push_subscriptions`

### System (15+ tables)
`settings`, `system_settings`, `email_settings`, `user_email_preferences`, `api_integrations`, `approval_flows`, `approval_steps`, `approval_requests`, `approval_logs`, `security_audit_log`, `activity_log`, `reminders`, `reminder_schedules`, `reminder_templates`, `announcements`, `short_urls`, `assets`, `asset_allocations`

### Release Management (5 tables)
`rm_projects`, `rm_modules`, `rm_screens`, `rm_releases`, `rm_daily_tasks`

### DB Tools (3 tables)
`dm_manager`, `client_migrations`, `saved_queries` (legacy)

### Recruitment (3 tables)
`recruitment_job_posts`, `recruitment_candidates`, `recruitment_interviews`

---

## 5. Model → Table Mapping

| Model | Primary Tables |
|-------|----------------|
| User_model | users |
| Employee_model | employees, employee_documents |
| Attendance_model | attendance |
| Leave_request_model | leave_requests, leave_balances, leave_approvals |
| Project_model | projects, project_members |
| Task_model | tasks, task_comments |
| Client_model | clients, client_contacts |
| Coaching_model | 20× coaching_* |
| Training_assessment_model | ta_* (legacy fallback) |
| Training_lms_module_model | training_modules |
| Training_lms_topic_model | training_topics |
| Training_lms_assignment_model | assignments, assignment_submissions |
| Training_lms_enrollment_model | training_enrollments, training_topic_completions |
| Payroll_model | salary_structures, payslips |
| Expense_model | expenses, expense_categories |
| Chat_model | conversations, messages, participants |
| Report_model | reads across attendance, tasks, leave, projects |
| Setting_model | settings |
| Security_audit_model | security_audit_log |

Full list: 46 models — see `MODULE_DOCUMENTATION.md`.

---

## 6. Migrations

| File | Purpose |
|------|---------|
| 001_Add_task_schema_fields.php | Extend tasks table |
| 002_Create_timesheets_tables.php | Timesheets module |
| 003_Add_activity_log_indexes.php | Performance indexes |
| 003_Create_lead_user_mapping_table.php | Lead hierarchy (**duplicate 003 prefix**) |
| 004_Create_my_works_table.php | My Works module |
| 005_Extend_my_works_module.php | My Works extensions |

**Status:** Disabled. Config version = 4, highest file = 005.

---

## 7. Key Design Observations

1. **No single schema source of truth** — dump + install + runtime bootstrap coexist
2. **ID type inconsistency** — BIGINT in core, INT in newer modules
3. **FK enforcement partial** — core dump has FKs; newer modules rely on app joins
4. **Legacy CMS tables** — `sma_*` WordPress-like tables (legacy, may be unused)
5. **Dual notification schemas** — evolved separately in Install vs Notification_model
6. **Multi-tenant readiness** — `clients.db_*` fields + Db controller sync tool

---

## 8. Permissions Table

Module access controlled via:

```sql
permissions (role_id, module, can_access)
```

50+ module keys including: `employees`, `attendance`, `coaching_billing`, `training_lms`, `my_works`, `db`, `ai_chat`, etc.

Seed files: `database/permissions_coaching_seed.sql`, `database/permissions_training_learning_seed.sql`
