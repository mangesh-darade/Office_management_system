# Permission Audit Report

Generated: 2026-06-29 09:50:49

## Summary

| Metric | Value |
|--------|-------|
| Screens audited | 103 |
| Roles | 10 |
| Key alignment issues | 3 |
| Sidebar-show / route-block mismatches | 0 |
| Orphan DB permission keys | 4 |

## Roles & visible screens

| Role ID | Role | Visible sidebar screens |
|---------|------|-------------------------|
| 2 | Manager | 77 |
| 3 | Lead | 33 |
| 4 | Employee | 21 |
| 5 | Coaching Client | 9 |
| 7 | HR | 23 |
| 8 | db_manager | 25 |
| 10 | Intern | 21 |
| 12 | Srujan_lead | 24 |
| 13 | sXsx | 9 |

## Key alignment issues

### SendGrid (`sendgrid`)
- Sidebar keys: `mail`
- Controller `sendgrid` keys: `sendgrid`, `email_settings`, `settings`, `admin`

### Payroll Report (`reports/payroll`)
- Sidebar keys: `payroll`, `payroll_view`, `payroll_manage`
- Controller `reports` keys: `reports`, `reports_overview`, `reports_requirements`, `reports_tasks_assignment`, `reports_projects_status`, `reports_leaves`, `reports_attendance`, `reports_attendance_employee`, `reports_daily_activity`, `daily_activity_report`, `analytics`, `reports_payroll`, `reports_expenses`, `reports_performance`

### Subscription Builder Catalog (`settings/subscription-builder`)
- Sidebar keys: `subscription_builder`
- Controller `settings` keys: `settings`, `holidays`, `holidays_add`, `holidays_edit`, `holidays_delete`, `leave_types`, `leave_types_add`, `leave_types_edit`, `leave_types_delete`, `types`, `admin`

## Methodology

1. Sidebar permission keys (OR logic) compared to `get_controller_module_access_map()`.
2. Per role: if sidebar would show link, simulate AuthHook route RBAC (any mapped key granted).
3. Role 1 (Super Admin) bypasses all checks.
4. Settings admin submenu requires `is_admin_group()` in sidebar — not fully simulated here.

Re-run: `php tools/audit_sidebar_permissions.php`
