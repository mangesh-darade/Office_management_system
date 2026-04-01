# Designation Module Implementation

## Permissions

| Group | Screens |
|---|---|
| Manager | Designation -> List Designation |
| Manager | Designation -> Add Designation |
| Manager | Designation -> Edit Designation |
| User | Designation -> List Designation (View Only, if allowed) |

## High Level Work Flow

### Manager + Employee (Same Premises)

1. Manager opens Designation module.
2. Manager creates designation master (e.g., Developer, Team Lead, HR Manager).
3. Manager maps designation with department and active status.
4. While enrolling/editing employee, manager selects designation.
5. System stores mapping and uses it in reports/filtering.
6. Manager can list and update designations.

### Distant Location

1. Manager performs same create/edit operations remotely.
2. System persists designation master and employee mapping centrally.

## Details Level Work Flow (Manager + Employee Same Premises)

### Manager Adds Designation

**Screen Name**: Add Designation

**Screen Fields**
- Designation Name*
- Designation Code (optional)
- Department (optional or mandatory by policy)
- Description (optional)
- Sort Order (optional)
- Active (Checkbox)

**Validation**
- Designation Name is required.
- Designation Name should be unique (case-insensitive).

**DB Inserts**
- `designations` table

### Manager Edits Designation

- Can update name/code/department/status.
- Cannot deactivate/delete if mapped to active users (recommended rule).

### Employee Record Mapping

During employee create/edit:
- Manager selects Designation in employee profile.
- System saves selected designation in user/employee table.

## DB Changes

### Designation Master Table (`designations`)
- `id`
- `name`
- `code` (nullable)
- `department_id` (nullable)
- `description` (nullable)
- `sort_order` (nullable)
- `status` (active/inactive)
- `created_at`
- `updated_at`

### User/Employee Table
- Add `designation_id` (foreign key or indexed field)

### Optional Designation History Table (`designation_history`)
- `id`
- `user_id`
- `old_designation_id`
- `new_designation_id`
- `effective_date`
- `changed_by`
- `created_at`

## External Api

### Optional Integrations
- HRMS/Payroll API for designation sync
- Organization Directory API (if central org master exists)

### Optional Validation API
- Duplicate check API (if multi-system master governance is used)
