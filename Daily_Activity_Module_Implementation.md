# Daily Activity Module Implementation

## Permissions

| Group | Screens |
|---|---|
| Manager | Daily Activity -> List Daily Activity |
| User | Daily Activity -> Add Daily Activity |
| User | Daily Activity -> List Daily Activity (Self Records Only) |
| User | Daily Activity -> Edit Daily Activity (Self Records Only) |

## High Level Work Flow

### Manager + Employee (Same Premises)

1. Manager enrolls or activates employee in the system.
2. Employee opens Daily Activity URL on a daily basis.
3. Employee adds work update with date, task details, and status.
4. System validates mandatory fields and stores activity.
5. Manager can view team Daily Activity records.
6. Manager can open list view and detail view for monitoring.

### Distant Location

1. Employee submits Daily Activity from any location.
2. System captures metadata (optional location/time/device if enabled).
3. Manager reviews records in the same list/detail flow.

## Details Level Work Flow (Manager + Employee Same Premises)

### Manager Enrolls User

**Screen Name**: Employee Enrollment

**Screen Fields**
- First Name
- Last Name
- Gender
- Phone
- Email
- Group
- Date of Joining (Default: Today)
- Locations
- Active (Checkbox)
- Password* (Encrypted)
- Confirm Password* (Encrypted)

**Fields Auto-populated**
- Company* -> Site Setting company name
- Email* -> If not received from FE then Manager Email
- UserName* -> Phone
- Status* -> Active

**DB Inserts**
- `users` table
- (Optional) `user_location_mapping` table

### Employee Creates Daily Activity

1. Employee opens Daily Activity create screen.
2. Enters activity date, project/task, description, and status.
3. Optionally enters start/end time and remarks.
4. Clicks Save.
5. System validates input and stores record.
6. Success message shown to employee.

### Edit Employee Daily Activity

- Employee can edit own record for allowed date range.
- Manager can edit team records based on permissions.

### Manager Views Daily Activity

**List Daily Activity**
- Employee Name
- Activity Date
- Project/Task
- Activity Summary
- Status
- Created Time

**Daily Activity Detail**
- Full description
- Time spent
- Remarks
- Last updated by / updated time

## DB Changes

### User Table
- Date of Joining
- Termination Date

### Daily Activity Table (`daily_activities`)
- `id`
- `user_id`
- `activity_date`
- `project_id` (nullable)
- `task_id` (nullable)
- `title`
- `description`
- `status` (planned/in-progress/completed/blocked)
- `start_time` (nullable)
- `end_time` (nullable)
- `duration_minutes` (nullable)
- `remarks` (nullable)
- `created_at`
- `updated_at`

### Optional Activity Audit Table (`daily_activity_logs`)
- `id`
- `daily_activity_id`
- `action` (create/update/delete/status_change)
- `old_value` (json/longtext)
- `new_value` (json/longtext)
- `created_by`
- `created_at`

## External Api

### Optional Integrations
- Notification API (email/SMS/WhatsApp) for reminder or submission alerts
- Project Management API (if syncing activity to external tool)

### Optional Geo/Device Metadata
- Geo-Location API (if activity submit location is required)
- Device Info API (if audit policy requires)
