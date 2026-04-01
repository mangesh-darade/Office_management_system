# Department Module Implementation

## Permissions

| Group | Screens |
|---|---|
| Manager | Department -> List Department |
| Manager | Department -> Add Department |
| Manager | Department -> Edit Department |
| User | Department -> List Department (View Only, if allowed) |

## High Level Work Flow

### Manager + Employee (Same Premises)

1. Manager opens Department module.
2. Manager creates department master (e.g., Engineering, HR, Finance).
3. Manager sets parent department (optional), location, and status.
4. While enrolling/editing employee, manager selects department.
5. System stores department mapping for reporting and access filters.
6. Manager can view and maintain department list.

### Distant Location

1. Manager performs department maintenance remotely.
2. System applies updates to all dependent modules centrally.

## Details Level Work Flow (Manager + Employee Same Premises)

### Manager Adds Department

**Screen Name**: Add Department

**Screen Fields**
- Department Name*
- Department Code (optional)
- Parent Department (optional)
- Department Head/User (optional)
- Location (optional)
- Description (optional)
- Active (Checkbox)

**Validation**
- Department Name is required.
- Department Name should be unique (case-insensitive).

**DB Inserts**
- `departments` table

### Manager Edits Department

- Update department details and status.
- Restrict delete/inactive if active users are mapped (recommended rule).

### Employee Record Mapping

During employee create/edit:
- Manager selects Department in employee profile.
- System saves selected department in user/employee table.

## DB Changes

### Department Master Table (`departments`)
- `id`
- `name`
- `code` (nullable)
- `parent_department_id` (nullable)
- `head_user_id` (nullable)
- `location_id` (nullable)
- `description` (nullable)
- `status` (active/inactive)
- `created_at`
- `updated_at`

### User/Employee Table
- Ensure `department_id` exists and is indexed.

### Optional Department History Table (`department_history`)
- `id`
- `user_id`
- `old_department_id`
- `new_department_id`
- `effective_date`
- `changed_by`
- `created_at`

## External Api

### Optional Integrations
- HRMS API for department master sync
- Payroll API for department cost-center mapping
- Analytics/BI API for org hierarchy reporting

### Optional Geo/Location Integration
- Location Master API for department-site mapping consistency
