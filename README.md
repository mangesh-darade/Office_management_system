# Office Management System — Modules, Roles, and Quick Links

This document summarizes all available modules, their functionality, default role access (overridable via the Permissions module), and quick navigation.

## Tech
- Framework: CodeIgniter 3
- DB: MySQL/MariaDB
- Auth: Session-based

## Quick start
1) Configure DB in `application/config/database.php`.
2) Run installer at `/install/schema` to create core schema and seeds. Many modules also self-create their minimal schema at first use.
3) Login at `/login`.

## Roles
- 1 Admin
- 2 Manager
- 3 Lead
- 4 Staff

## Role access matrix (defaults)
- Dashboard: 1,2,3,4
- Employees: 1,2
- Projects: 1,2,3
- Tasks: 1,2,3,4
- Attendance: 1,2,3,4
- Leaves: 1,2,3,4
- Reports: 1,2,3
- Permissions: 2,3
- Chats: 1,2,3,4
- Calls: 1,2,3,4
- Departments: 1,2
- Designations: 1,2
- Timesheets: 1,2,3,4
- Announcements: 1,2,3,4 (manage: Admin/Manager only)
- Settings: 1
- Activity: 1,2

Note: If a `permissions` table exists, it overrides the defaults via the Permissions UI (`/permissions`).

## Modules and links
- Authentication
  - Login: `/login`
  - Logout: `/logout`
  - Register (optional): `/register`

- Dashboard: `/dashboard`
  - Cards/links based on role access
  - Announcements widget (recent published)

- Employees: `/employees`
  - CRUD, CSV import, reporting line

- Departments: `/departments`
  - CRUD with manager assignment

- Designations: `/designations`
  - CRUD with department and level

- Projects: `/projects`
  - CRUD, CSV import, members management
  - Members: `/projects/{id}/members`

- Tasks: `/tasks`
  - CRUD, rich description, Kanban board (`/tasks/board`)
  - Comments with AJAX; notifications to assignee

- Attendance: `/attendance`
  - Record attendance (schema-aware: supports `att_date/punch_in/punch_out` or legacy columns)

- Leaves
  - Index: `/leaves`
  - Apply: `/leave/apply`
  - My leaves: `/leave/my`
  - Team leaves (Mgr/Lead): `/leave/team`
  - Calendar: `/leave/calendar`

- Timesheets: `/timesheets`
  - Weekly entries, submit/approve, monthly report (`/timesheets/report`)
  - Creates minimal schema at first use if missing

- Announcements: `/announcements`
  - Create/Edit/Delete (Admin/Manager)
  - Creates table at first use if missing

- Activity: `/activity`
  - Filterable audit trail, CSV export

- Settings: `/settings`
  - Company, Attendance, Leave, Email, Notifications

- Permissions: `/permissions`
  - Role × module access matrix (Manager/Lead)

- Reports: `/reports`
  - Task status, project status, leaves trend; CSV export

- Chats & Calls: `/chats`, `/calls/*`
  - DMs/groups with attachments and basic WebRTC signaling via polling

- Mail: `/mail`
  - Test SMTP send, reply-to current user

## Notes
- Logging: Activity logger records CRUD and key actions across modules.
- Uploads: stored under `uploads/*` and created on demand.
- Security: Passwords via `password_hash/verify`; restrict `/install/schema` in production.

## Troubleshooting
- Missing tables: run `/install/schema` or visit module once (self-creates minimal schema).
- Permissions: adjust via `/permissions`.
- Email: set SMTP env vars (SMTP_USER/SMTP_PASS) and test at `/settings` or `/mail/test`.
