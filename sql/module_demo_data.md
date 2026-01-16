## Core Modules & Demo Data

### 1. Users

**Table**: `users`  
**Purpose**: Application login identities, linked to employees and used as foreign key across the system.

```sql
-- Users demo data
INSERT INTO `users` (`id`, `email`, `role`, `password_hash`, `is_verified`, `last_login_at`, `status`, `role_id`, `name`, `phone`, `avatar`, `created_at`, `updated_at`) VALUES
(101, 'admin@example.com', 'admin', '$2y$10$dummyhashadminxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, NULL, 'active', 1, 'Admin User', '9990000001', NULL, NOW(), NOW()),
(102, 'manager@example.com', 'user', '$2y$10$dummyhashmanagerxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, NULL, 'active', 2, 'Project Manager', '9990000002', NULL, NOW(), NOW()),
(103, 'lead@example.com', 'user', '$2y$10$dummyhashleadxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, NULL, 'active', 3, 'Team Lead', '9990000003', NULL, NOW(), NOW()),
(104, 'employee1@example.com', 'user', '$2y$10$dummyhashemp1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, NULL, 'active', 4, 'Employee One', '9990000004', NULL, NOW(), NOW()),
(105, 'employee2@example.com', 'user', '$2y$10$dummyhashemp2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, NULL, 'active', 4, 'Employee Two', '9990000005', NULL, NOW(), NOW());
```

### 2. Departments

**Table**: `departments`  
**Purpose**: High-level organizational units; referenced logically by employees and designations.

```sql
-- Departments demo data
INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `description`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(201, 'D-ENG', 'Engineering', 'Software development and technical delivery', 102, 'active', NOW(), NOW()),
(202, 'D-HR', 'Human Resources', 'Employee relations, recruitment, and policies', 101, 'active', NOW(), NOW()),
(203, 'D-SALES', 'Sales', 'Client acquisition and account management', 102, 'active', NOW(), NOW());
```

### 3. Designations

**Table**: `designations`  
**Purpose**: Job titles/roles within departments, optionally with seniority level.

```sql
-- Designations demo data
INSERT INTO `designations` (`id`, `designation_code`, `designation_name`, `department_id`, `level`, `status`, `created_at`, `updated_at`) VALUES
(301, 'ENG-LEAD', 'Engineering Lead', 201, 3, 'active', NOW(), NOW()),
(302, 'ENG-DEV', 'Software Engineer', 201, 2, 'active', NOW(), NOW()),
(303, 'HR-MGR', 'HR Manager', 202, 3, 'active', NOW(), NOW()),
(304, 'SALES-EXEC', 'Sales Executive', 203, 1, 'active', NOW(), NOW());
```

### 4. Employees

**Table**: `employees`  
**Purpose**: HR master data for staff, linked to `users` and logically to departments/designations.

```sql
-- Employees demo data
INSERT INTO `employees`
(`id`, `user_id`, `emp_code`, `first_name`, `last_name`, `gender`, `dob`, `personal_email`, `phone`, `address`, `city`, `state`, `country`, `zipcode`, `join_date`, `probation_end`, `department`, `designation`, `reporting_to`, `employment_type`, `salary_ctc`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`, `updated_at`)
VALUES
(401, 101, 'E101', 'Admin', 'User', 'male', '1990-01-15', 'admin.personal@example.com', '9990000001', 'HQ Campus', 'Pune', 'Maharashtra', 'India', '411001', '2025-01-01', '2025-04-01', 'Engineering', 'Engineering Lead', NULL, 'full_time', 1800000.00, 'Admin EC', '9000000001', NOW(), NOW()),
(402, 102, 'E102', 'Priya', 'Manager', 'female', '1992-05-20', 'priya.manager@example.com', '9990000002', 'Magarpatta Road', 'Pune', 'Maharashtra', 'India', '411028', '2025-02-01', '2025-05-01', 'Engineering', 'Engineering Lead', 101, 'full_time', 1500000.00, 'Priya EC', '9000000002', NOW(), NOW()),
(403, 103, 'E103', 'Rohan', 'Lead', 'male', '1993-08-10', 'rohan.lead@example.com', '9990000003', 'Baner', 'Pune', 'Maharashtra', 'India', '411045', '2025-03-01', '2025-06-01', 'Engineering', 'Engineering Lead', 102, 'full_time', 1200000.00, 'Rohan EC', '9000000003', NOW(), NOW()),
(404, 104, 'E104', 'Sneha', 'Dev', 'female', '1995-11-05', 'sneha.dev@example.com', '9990000004', 'Kothrud', 'Pune', 'Maharashtra', 'India', '411038', '2025-03-15', '2025-06-15', 'Engineering', 'Software Engineer', 103, 'full_time', 800000.00, 'Sneha EC', '9000000004', NOW(), NOW()),
(405, 105, 'E105', 'Amit', 'Sales', 'male', '1994-02-25', 'amit.sales@example.com', '9990000005', 'Viman Nagar', 'Pune', 'Maharashtra', 'India', '411014', '2025-04-01', '2025-07-01', 'Sales', 'Sales Executive', 102, 'full_time', 700000.00, 'Amit EC', '9000000005', NOW(), NOW());
```

### 5. Projects

**Table**: `projects`  
**Purpose**: Client or internal initiatives; linked to a manager (`users`) and to project members.

```sql
-- Projects demo data
INSERT INTO `projects` (`id`, `code`, `name`, `db_name`, `description`, `start_date`, `end_date`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES
(501, 'PRJ-EMP-PORTAL', 'Employee Portal Revamp', NULL, 'Redesign and enhance internal employee self-service portal', '2025-05-01', '2025-12-31', 'active', 102, NOW(), NOW()),
(502, 'PRJ-CRM-IMPL', 'CRM Implementation', NULL, 'End-to-end CRM rollout for sales and support', '2025-06-01', '2025-11-30', 'planned', 103, NOW(), NOW());
```

### 6. Project Members

**Table**: `project_members`  
**Purpose**: Assigns users to projects with specific roles.

```sql
-- Project members demo data
INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `created_at`, `updated_at`) VALUES
(601, 501, 102, 'lead', NOW(), NOW()),
(602, 501, 103, 'member', NOW(), NOW()),
(603, 501, 104, 'member', NOW(), NOW()),
(604, 502, 102, 'lead', NOW(), NOW()),
(605, 502, 105, 'member', NOW(), NOW());
```

### 7. Requirements

**Table**: `requirements`  
**Purpose**: Client requirements/feature requests linked to clients and projects. Tracks business needs, priorities, budgets, and assignments. Supports versioning and attachments.

**Business Logic**:
- Auto-generates unique `req_number` (format: REQ-YYYY-NNNNN) when created
- Links to `clients` (required) and optionally to `projects`
- Tracks `requirement_type` (new_feature, enhancement, bug_fix, etc.)
- Has `status` workflow: received → in_progress → completed → closed
- Supports `owner_id` (business owner), `guide_id` (technical guide), and `assigned_to` (implementer)
- Budget tracking with `budget_estimate` and `currency`
- Version history maintained in `requirement_versions` table
- Attachments stored in `requirement_attachments` table

**Note**: Requirements require a `clients` record. Ensure you have at least one client record before inserting requirements. Example client INSERT:
```sql
-- Example client (adjust ID as needed for your schema)
INSERT INTO `clients` (`id`, `client_code`, `company_name`, `contact_person`, `email`, `phone`, `status`, `created_at`, `updated_at`) 
VALUES (501, 'CLI-2025-00001', 'Demo Client Corp', 'John Doe', 'contact@democlient.com', '9999999999', 'active', NOW(), NOW());
```

```sql
-- Requirements demo data
-- Prerequisite: Ensure clients table has at least client_id = 501 before inserting
INSERT INTO `requirements` 
(`id`, `req_number`, `client_id`, `project_id`, `title`, `description`, `requirement_type`, `priority`, `status`, `budget_estimate`, `currency`, `expected_delivery_date`, `received_date`, `owner_id`, `guide_id`, `assigned_to`, `created_by`, `created_at`, `updated_at`) 
VALUES
(801, 'REQ-2025-00001', 501, 501, 'User Authentication Enhancement', 'Implement OAuth2 and SSO support for employee portal', 'enhancement', 'high', 'received', 500000.00, 'INR', '2025-08-15', '2025-05-10', 102, 103, 104, 101, NOW(), NOW()),
(802, 'REQ-2025-00002', 501, 501, 'Mobile App Dashboard', 'Create responsive mobile dashboard for iOS and Android', 'new_feature', 'medium', 'in_progress', 1200000.00, 'INR', '2025-10-30', '2025-04-20', 102, NULL, 103, 101, NOW(), NOW()),
(803, 'REQ-2025-00003', 501, 502, 'Payment Gateway Integration Bug', 'Fix transaction timeout issue in payment processing', 'bug_fix', 'urgent', 'received', NULL, 'INR', '2025-06-30', '2025-05-15', 103, NULL, 104, 102, NOW(), NOW()),
(804, 'REQ-2025-00004', 501, NULL, 'Analytics Dashboard', 'Build comprehensive analytics dashboard for business metrics', 'new_feature', 'medium', 'received', 800000.00, 'INR', '2025-12-31', '2025-05-20', 102, 103, NULL, 101, NOW(), NOW());
```

**Related Tables**:
- `requirement_attachments`: File attachments for requirements
- `requirement_versions`: Version history tracking

```sql
-- Requirement attachments demo data
INSERT INTO `requirement_attachments` 
(`id`, `requirement_id`, `file_name`, `original_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `uploaded_at`) 
VALUES
(901, 801, 'req_801_spec.pdf', 'Authentication_Specification.pdf', '/uploads/requirements/req_801_spec.pdf', 245760, 'application/pdf', 101, NOW()),
(902, 802, 'req_802_mockups.zip', 'Mobile_Dashboard_Mockups.zip', '/uploads/requirements/req_802_mockups.zip', 1024000, 'application/zip', 102, NOW());
```

```sql
-- Requirement versions demo data (initial versions created automatically on requirement creation)
INSERT INTO `requirement_versions` 
(`id`, `requirement_id`, `version_no`, `title`, `description`, `requirement_type`, `priority`, `status`, `budget_estimate`, `expected_delivery_date`, `received_date`, `owner_id`, `guide_id`, `assigned_to`, `created_by`, `created_at`) 
VALUES
(1001, 801, 1, 'User Authentication Enhancement', 'Implement OAuth2 and SSO support for employee portal', 'enhancement', 'high', 'received', 500000.00, '2025-08-15', '2025-05-10', 102, 103, 104, 101, NOW()),
(1002, 802, 1, 'Mobile App Dashboard', 'Create responsive mobile dashboard for iOS and Android', 'new_feature', 'medium', 'in_progress', 1200000.00, '2025-10-30', '2025-04-20', 102, NULL, 103, 101, NOW());
```

### 8. Tasks

**Table**: `tasks`  
**Purpose**: Work items within projects. Tracks assignments, status, priority, time estimates, and completion. Supports comments, attachments, and activity logging.

**Business Logic**:
- Must belong to a `project` (required `project_id`)
- Can optionally link to a `requirement` via `requirement_id` (if task implements a requirement)
- Status workflow: `pending` → `in_progress` → `completed` or `blocked`
- Priority levels: `low`, `medium`, `high`, `urgent`
- Time tracking: `estimate_hours` (planned) vs `actual_hours` (logged)
- Assignment: `assigned_to` (user who works on it) and `created_by` (user who created it)
- Dates: `start_date`, `due_date`, `completed_at` (auto-set when status = completed)
- Activity log tracks all changes (status, assignment, comments, attachments)
- Comments enable team collaboration
- Attachments support file uploads

```sql
-- Tasks demo data
-- Note: Ensure projects table has project_id = 501, 502 before inserting
INSERT INTO `tasks` 
(`id`, `project_id`, `title`, `description`, `assigned_to`, `created_by`, `status`, `priority`, `start_date`, `due_date`, `completed_at`, `estimate_hours`, `actual_hours`, `created_at`, `updated_at`) 
VALUES
(1101, 501, 'Design OAuth2 Flow', 'Create detailed design document for OAuth2 authentication flow', 103, 102, 'completed', 'high', '2025-05-15', '2025-05-25', '2025-05-24 18:30:00', 16.00, 14.50, NOW(), NOW()),
(1102, 501, 'Implement SSO Provider Integration', 'Integrate with Azure AD and Google Workspace for SSO', 104, 102, 'in_progress', 'high', '2025-05-26', '2025-06-15', NULL, 40.00, 25.00, NOW(), NOW()),
(1103, 501, 'Write Unit Tests for Auth Module', 'Coverage for authentication and authorization logic', 104, 103, 'pending', 'medium', NULL, '2025-06-20', NULL, 20.00, NULL, NOW(), NOW()),
(1104, 502, 'Setup CRM Database Schema', 'Design and create database tables for CRM module', 103, 102, 'in_progress', 'medium', '2025-06-01', '2025-06-10', NULL, 24.00, 18.00, NOW(), NOW()),
(1105, 502, 'Create API Endpoints', 'RESTful API endpoints for customer management', 104, 103, 'pending', 'high', NULL, '2025-06-25', NULL, 32.00, NULL, NOW(), NOW()),
(1106, 501, 'Fix Payment Gateway Timeout', 'Investigate and resolve transaction timeout in payment processing', 104, 103, 'blocked', 'urgent', '2025-05-20', '2025-05-30', NULL, 8.00, 2.00, NOW(), NOW());
```

**Related Tables**:
- `task_comments`: Discussion threads on tasks
- `task_attachments`: File attachments
- `task_activity`: Audit log of all task changes

```sql
-- Task comments demo data
INSERT INTO `task_comments` 
(`id`, `task_id`, `user_id`, `comment`, `created_at`) 
VALUES
(1201, 1101, 103, 'Design document reviewed and approved. Ready for implementation.', NOW()),
(1202, 1102, 104, 'Azure AD integration completed. Working on Google Workspace now.', NOW()),
(1203, 1102, 102, 'Great progress! Let me know if you need any clarification.', NOW()),
(1204, 1106, 104, 'Waiting for payment gateway vendor to provide API documentation.', NOW());
```

```sql
-- Task attachments demo data
INSERT INTO `task_attachments` 
(`id`, `task_id`, `file_name`, `file_path`, `mime_type`, `size_bytes`, `uploaded_by`, `created_at`) 
VALUES
(1301, 1101, 'oauth2_design_v1.pdf', '/uploads/tasks/1101/oauth2_design_v1.pdf', 'application/pdf', 512000, 103, NOW()),
(1302, 1102, 'sso_integration_guide.docx', '/uploads/tasks/1102/sso_integration_guide.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 256000, 104, NOW()),
(1303, 1104, 'crm_schema.sql', '/uploads/tasks/1104/crm_schema.sql', 'text/plain', 128000, 103, NOW());
```

```sql
-- Task activity demo data (audit log)
INSERT INTO `task_activity` 
(`id`, `task_id`, `user_id`, `action`, `old_value`, `new_value`, `created_at`) 
VALUES
(1401, 1101, 102, 'created', NULL, '{"status":"pending"}', NOW()),
(1402, 1101, 102, 'assigned', NULL, '{"assigned_to":103}', NOW()),
(1403, 1101, 102, 'status_changed', '{"status":"pending"}', '{"status":"in_progress"}', NOW()),
(1404, 1101, 103, 'status_changed', '{"status":"in_progress"}', '{"status":"completed"}', NOW()),
(1405, 1102, 102, 'created', NULL, '{"status":"pending"}', NOW()),
(1406, 1102, 102, 'assigned', NULL, '{"assigned_to":104}', NOW()),
(1407, 1102, 104, 'status_changed', '{"status":"pending"}', '{"status":"in_progress"}', NOW());
```

### 9. Permissions (Module Access)

**Table**: `permissions`  
**Purpose**: Which role IDs can access which modules (employees, projects, etc.).

```sql
-- Permissions demo data
INSERT INTO `permissions` (`id`, `role_id`, `module`, `can_access`) VALUES
(701, 1, 'employees', 1),
(702, 1, 'projects', 1),
(703, 1, 'departments', 1),
(704, 1, 'designations', 1),
(705, 1, 'users', 1),
(706, 1, 'requirements', 1),
(707, 1, 'tasks', 1),
(708, 2, 'projects', 1),
(709, 2, 'employees', 1),
(710, 2, 'requirements', 1),
(711, 2, 'tasks', 1),
(712, 3, 'projects', 1),
(713, 3, 'employees', 1),
(714, 3, 'tasks', 1),
(715, 4, 'employees', 1),
(716, 4, 'tasks', 1);
```

### 10. High-Level Business Logic Summary

- **Users module**: Manages login accounts, roles, and basic profile details. Other modules (employees, projects, attendance, leave, etc.) reference `users.id` for ownership, assignments, and audit trails.

- **Departments module**: Organizes the company into units like Engineering, HR, and Sales. Employees and designations are grouped logically by department, and `manager_id` usually points to the department head’s `users.id`.

- **Designations module**: Defines job titles such as Engineering Lead or Software Engineer, optionally tied to departments and with `level` indicating seniority.

- **Employees module**: Stores HR master data and links each employee to exactly one `users` record. Fields like `department`, `designation`, `employment_type`, and `reporting_to` drive reporting structure, approvals, and filtering in other modules.

- **Projects & project members**: `projects` capture initiatives with codes, dates, and statuses, while `project_members` assign `users` as leads or members. These records are used by tasks, timesheets, and reporting to understand who is working on what.

- **Requirements module**: Captures client or business requirements/feature requests. Each requirement has a unique auto-generated `req_number` (REQ-YYYY-NNNNN format). Links to `clients` (required) and optionally to `projects`. Tracks `requirement_type` (new_feature, enhancement, bug_fix), `priority`, `status` workflow (received → in_progress → completed → closed), budget estimates, delivery dates, and assignments (`owner_id`, `guide_id`, `assigned_to`). Maintains version history in `requirement_versions` and supports file attachments via `requirement_attachments`. Requirements can spawn multiple tasks during implementation.

- **Tasks module**: Work items within projects that break down project work into actionable units. Every task must belong to a `project` and can optionally link to a `requirement` (if implementing a requirement). Tracks `status` (pending → in_progress → completed/blocked), `priority` (low, medium, high, urgent), time estimates vs actuals, assignments (`assigned_to`, `created_by`), and dates (start, due, completion). Supports collaboration through `task_comments`, file attachments via `task_attachments`, and maintains a complete audit trail in `task_activity`. Tasks are the primary unit for timesheet logging and project progress tracking.

- **Permissions module**: Uses `role_id` and `module` to decide whether a logged-in user can access specific modules (employees, projects, departments, requirements, tasks, etc.), controlling UI visibility and backend authorization checks.

