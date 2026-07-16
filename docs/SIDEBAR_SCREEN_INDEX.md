# Sidebar Screen Index

This document lists sidebar modules and the screens linked from each module.
Visibility is role/permission based in `application/views/partials/sidebar.php`.

## Single Links

- Dashboard -> `/dashboard`
- My Works -> `/my-works`
- Today's Focus -> `/my-works/todays-focus`
- Subscription Builder -> `/subscription-builder`
- ElintOm Proposals -> `/elintom-proposals`
- Business Assessment -> `/eba-platform`
- Clients -> `/clients`
- Employees -> `/employees`
- Chats -> `/chats/app`
- AI Assistant -> `/ai_chat`
- Announcements -> `/announcements`
- Notifications -> `/notifications`
- Super Admin -> `/superadmin`

## Daily Activity

- Add Activity -> `/daily-activity`
- All Activities -> `/daily-activity/list`
- Export CSV -> `/daily-activity/export`

## Communication

- Mail (SMTP) -> `/mail`
- SendGrid (API) -> `/sendgrid`
- WhatsApp -> `/whatsapp`

## Recruitment

- Job Openings -> `/recruitment`
- Post New Job -> `/recruitment/create-job`
- Candidates -> `/recruitment/candidates`
- Export CSV -> `/recruitment/export`

## Performance

- All Appraisals -> `/performance`
- New Appraisal -> `/performance/create`
- Self-Assessment -> `/performance/self-assess`
- Export CSV -> `/performance/export`

## Coaching

- Dashboard -> `/coaching`
- Clients -> `/coaching-clients`
- Coaches -> `/coaching-coaches`
- Sessions -> `/coaching-sessions`
- Goals -> `/coaching-goals`
- Leads -> `/coaching-leads`
- Billing -> `/coaching-billing`
- Reports -> `/coaching-reports`
- WhatsApp CRM -> `/coaching-whatsapp-crm`
- Resources -> `/coaching-resources`
- Admin -> `/coaching-admin`

## Training & Assessment

- Dashboard -> `/training-assessment`
- New assessment -> `/training-assessment/create`
- Import CSV -> `/training-assessment/import`
- Master CSV Import -> `/training/import`
- Report -> `/training-assessment/report`
- Assessment submissions -> `/training-assessment/submissions`
- Training hub -> `/training/my-training`
- Module -> `/training`
- External trainings -> `/external-training`
- LMS admin -> `/training-lms-admin`
- Assignment submissions -> `/training-lms-admin/assignment-submissions`

## User

- Users -> `/users`
- Add User -> `/users/create`
- Roles -> `/roles`
- Assets -> `/assets-mgmt`
- Attendance -> `/attendance`
- Mark Attendance -> `/attendance/create`
- Shifts -> `/shifts`
- Department -> `/departments`
- Designation -> `/designations`

## Payroll

- Payslips -> `/payroll/payslips`
- Pay Structures -> `/payroll/structures`
- Generate Payroll -> `/payroll/generate`
- Payroll Report -> `/reports/payroll`

## Expenses

- My Expenses -> `/expenses`
- Create Request -> `/expenses/create`
- Approvals -> `/expenses/pending`
- Reports -> `/expenses/report`
- Categories -> `/expenses/categories`

## Leave

- Apply Leave -> `/leave/apply`
- My Leaves -> `/leave/my`
- Team Leaves -> `/leave/team`
- Leave Calendar -> `/leave/calendar`

## Project

- Projects -> `/projects`
- Portfolio Matrix -> `/projects/matrix`
- Project Dashboard -> `/projects/dashboard`
- Add Project -> `/projects/create`
- Requirement -> `/requirements`
- Task -> `/tasks/board`
- Timesheet -> `/timesheets`
- Monthly Report -> `/timesheets/report`
- Analytics -> `/timesheets/analytics`
- Releases -> `/releases`
- Defects -> `/defects`

## Rewards & Engagement

- My Rewards -> `/rewards`
- Leaderboard -> `/rewards/leaderboard`
- Knowledge Base -> `/knowledge-base`
- Helpdesk -> `/helpdesk`
- Events -> `/events`
- Certifications -> `/certifications`
- Customer Feedback -> `/customer-feedback`
- Reward Rules -> `/spl/dashboard?tab=rules`
- Reward Categories -> `/spl/dashboard?tab=rules&rules_view=categories` (under Rules)

## Office Meals (Dynamic)

Defined by `meal_nav_screens()` and permission checks:

- My Orders -> `/meals`
- Calendar -> `/meals/calendar`
- Provider -> `/meals/provider`
- Settings -> `/meals/settings`
- History -> `/meals/history`
- All Orders -> `/meals/all_orders`

## Admin > Settings

- System Settings -> `/settings`
- Leave Types -> `/settings/leave-types`
- Module Types -> `/settings/types`
- Holidays -> `/settings/holidays`
- Attendance Manage -> `/settings/attendance-manage` (admin only)
- Subscription Builder Catalog -> `/settings/subscription-builder`
- Permission Manager -> `/permissions`
- User Access -> `/system-settings/user-access`
- Email Settings -> `/email-settings`
- Approval Workflows -> `/approvals`
- Database Manager -> `/db`
- Client DB Panel -> `/db/clients`
- Client DB Migrations -> `/db/client-migrations`
- Reminders -> `/reminders`
- Google Calendar Reminders -> `/calendar-reminders`
- Activity Log -> `/activity`
- Status Management -> `/statuses`
- API Integrations -> `/api-integrations`
- Lead Mapping -> `/lead-mapping`

## User Guide (Dynamic)

- All Modules -> `/guide`
- Module pages -> `/guide/{slug}`

