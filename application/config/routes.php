<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'dashboard';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
// Installer route to execute DB schema and seeds
$route['install/schema'] = 'install/schema';

// Root and index fallbacks to avoid 404s when rewrite/DirectoryIndex varies
$route['^$'] = 'auth/index';
$route['index.php'] = 'auth/index';

// Auth
$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
$route['register'] = 'auth/register';
$route['auth/send-verify-code'] = 'auth/send_verify_code';
$route['auth/verify-code'] = 'auth/verify_code';
$route['auth/verify-2fa'] = 'auth/verify_2fa';
$route['forgot-password'] = 'auth/forgot_password';
$route['reset-password'] = 'auth/reset_password';

// Dashboard
$route['dashboard'] = 'dashboard/index';

// Employees
$route['employees'] = 'employees/index';
$route['employees/create'] = 'employees/create';
$route['employees/(:num)'] = 'employees/show/$1';
$route['employees/(:num)/edit'] = 'employees/edit/$1';
$route['employees/(:num)/delete'] = 'employees/delete/$1';
$route['employees/import'] = 'employees/import';
$route['employees/(:num)/documents'] = 'employees/documents/$1';
$route['employees/documents/(:num)/download'] = 'employees/download_document/$1';
$route['employees/documents/(:num)/delete'] = 'employees/delete_document/$1';

// Departments
$route['departments'] = 'departments/index';
$route['departments/create'] = 'departments/create';
$route['departments/(:num)/edit'] = 'departments/edit/$1';
$route['departments/(:num)/delete'] = 'departments/delete/$1';
$route['departments/(:num)/restore'] = 'departments/restore/$1';

// Designations
$route['designations'] = 'designations/index';
$route['designations/create'] = 'designations/create';
$route['designations/(:num)/edit'] = 'designations/edit/$1';
$route['designations/(:num)/delete'] = 'designations/delete/$1';
$route['designations/(:num)/restore'] = 'designations/restore/$1';

// Clients
$route['clients'] = 'clients/index';
$route['clients/create'] = 'clients/create';
$route['clients/view/(:num)'] = 'clients/view/$1';
$route['clients/edit/(:num)'] = 'clients/edit/$1';
$route['clients/delete/(:num)'] = 'clients/delete/$1';
$route['clients/export'] = 'clients/export';
$route['clients/(:num)/contacts'] = 'clients/contacts/$1';

// Projects
$route['projects'] = 'projects/index';
$route['projects/create'] = 'projects/create';
$route['projects/(:num)'] = 'projects/show/$1';
$route['projects/(:num)/edit'] = 'projects/edit/$1';
$route['projects/(:num)/delete'] = 'projects/delete/$1';
$route['projects/import'] = 'projects/import';
// Project members
$route['projects/(:num)/members'] = 'projects/manage_members/$1';
$route['projects/(:num)/add-member'] = 'projects/add_member/$1';
$route['projects/(:num)/remove-member/(:num)'] = 'projects/remove_member/$1/$2';
$route['projects/(:num)/member/(:num)/role'] = 'projects/update_member_role/$1/$2';

// System Settings
$route['system-settings'] = 'system_settings/index';
$route['system-settings/update-settings'] = 'system_settings/update_settings';
$route['system-settings/permissions'] = 'system_settings/permissions';
$route['system-settings/update-permissions'] = 'system_settings/update_permissions';
$route['system-settings/user-access'] = 'system_settings/user_access';
$route['system-settings/update-user-access'] = 'system_settings/update_user_access';
$route['system-settings/success-screen'] = 'system_settings/success_screen';
$route['system-settings/update-success-screen'] = 'system_settings/update_success_screen';

// Email Settings
$route['email-settings'] = 'email_settings/index';
$route['email-settings/update'] = 'email_settings/update';
$route['email-settings/user-preferences'] = 'email_settings/user_preferences';
$route['email-settings/test-email'] = 'email_settings/test_email';

// Tasks
$route['tasks'] = 'tasks/index';
$route['tasks/create'] = 'tasks/create';
$route['tasks/(:num)'] = 'tasks/show/$1';
$route['tasks/(:num)/edit'] = 'tasks/edit/$1';
$route['tasks/(:num)/delete'] = 'tasks/delete/$1';
$route['tasks/(:num)/preview'] = 'tasks/preview/$1';
$route['tasks/import'] = 'tasks/import';
$route['tasks/board'] = 'tasks/board';
$route['tasks/update-status'] = 'tasks/update_status';
$route['tasks/send-daily-summary'] = 'tasks/send_daily_summary';
$route['tasks/send-all-summaries'] = 'tasks/send_all_summaries';
// Task comments
$route['tasks/(:num)/comment'] = 'tasks/add_comment/$1';
$route['tasks/(:num)/comments'] = 'tasks/get_comments/$1';
$route['tasks/comment/(:num)/delete'] = 'tasks/delete_comment/$1';

// Statuses Management
$route['statuses'] = 'statuses/index';
$route['statuses/create'] = 'statuses/create';
$route['statuses/view/(:num)'] = 'statuses/view/$1';
$route['statuses/edit/(:num)'] = 'statuses/edit/$1';
$route['statuses/delete/(:num)'] = 'statuses/delete/$1';
$route['statuses/(:num)'] = 'statuses/show/$1';

// Permissions Manager
$route['permissions'] = 'permissions/index';
$route['permissions/save'] = 'permissions/save';

// Roles
$route['roles'] = 'roles/index';
$route['roles/store'] = 'roles/store';
$route['roles/update/(:num)'] = 'roles/update/$1';
$route['roles/delete/(:num)'] = 'roles/delete/$1';

// Attendance
$route['attendance'] = 'attendance/index';
$route['attendance/create'] = 'attendance/create';
$route['attendance/get_data'] = 'attendance/get_data';
$route['attendance/(:num)/edit'] = 'attendance/edit/$1';
$route['attendance/(:num)/delete'] = 'attendance/delete/$1';

// Assets (IT equipment)
$route['assets-mgmt'] = 'assets/index';
$route['assets-mgmt/create'] = 'assets/create';
$route['assets-mgmt/edit/(:num)'] = 'assets/edit/$1';
$route['assets-mgmt/assign/(:num)'] = 'assets/assign/$1';
$route['assets-mgmt/return_asset/(:num)'] = 'assets/return_asset/$1';
$route['assets-mgmt/my'] = 'assets/my';

// Leaves
$route['leaves'] = 'leaves/index';
$route['leaves/export'] = 'leaves/export_csv';
$route['leaves/test-email'] = 'leaves/test_email';
// Leave Requests (Phase 1)
$route['leave/apply'] = 'leave_requests/apply';
$route['leave/my'] = 'leave_requests/my';
$route['leave/edit/(:num)'] = 'leave_requests/edit/$1';
$route['leave/delete/(:num)'] = 'leave_requests/delete/$1';
// Leave Requests (Phase 2)
$route['leave/team'] = 'leave_requests/team';
$route['leave/approve/(:num)'] = 'leave_requests/approve/$1';
$route['leave/reject/(:num)'] = 'leave_requests/reject/$1';
$route['leave/calendar'] = 'leave_requests/calendar';
$route['leave/get-employee-tasks/(:num)'] = 'leave_requests/get_employee_tasks/$1';

// Notifications
$route['notifications'] = 'notifications/index';
$route['notifications/count'] = 'notifications/count';
$route['notifications/recent'] = 'notifications/recent';
$route['notifications/mark-read/(:num)'] = 'notifications/mark_read/$1';
$route['notifications/mark-all-read'] = 'notifications/mark_all_read';
$route['notifications/delete/(:num)'] = 'notifications/delete/$1';
$route['notifications/subscribe-push'] = 'notifications/subscribe_push';
$route['notifications/unsubscribe-push'] = 'notifications/unsubscribe_push';

// Reports
$route['reports'] = 'reports/index';
$route['reports/export'] = 'reports/export_csv';
$route['reports/requirements'] = 'reports/requirements';
$route['reports/tasks-assignment'] = 'reports/tasks_assignment';
$route['reports/projects-status'] = 'reports/projects_status';
$route['reports/leaves'] = 'reports/leaves';
$route['reports/attendance'] = 'reports/attendance';
$route['reports/attendance-employee'] = 'reports/attendance_employee';
$route['reports/attendance-employee/(:num)'] = 'reports/attendance_employee/$1';
$route['reports/export-attendance-employee'] = 'reports/export_attendance_employee';
$route['reports/daily-activity'] = 'reports/daily_activity';
$route['reports/payroll'] = 'reports/payroll';
$route['reports/expenses'] = 'reports/expenses';

// Profile
$route['profile'] = 'profile/index';

// Activity Logs
$route['activity'] = 'activity/index';
$route['activity/export'] = 'activity/export';

// Settings
$route['settings'] = 'settings/index';
$route['settings/update'] = 'settings/update';
$route['settings/upload-logo'] = 'settings/upload_logo';
$route['settings/test-email'] = 'settings/test_email';
// Leave Types Management
$route['settings/leave-types'] = 'settings/leave_types';
$route['settings/leave-types/create'] = 'settings/leave_types_create';
$route['settings/leave-types/(:num)/edit'] = 'settings/leave_types_edit/$1';
$route['settings/leave-types/(:num)/delete'] = 'settings/leave_types_delete/$1';
$route['settings/leave-types/(:num)/restore'] = 'settings/leave_types_restore/$1';

// Holidays Management
$route['settings/holidays'] = 'settings/holidays';
$route['settings/holidays/create'] = 'settings/holidays_create';
$route['settings/holidays/(:num)/edit'] = 'settings/holidays_edit/$1';
$route['settings/holidays/(:num)/delete'] = 'settings/holidays_delete/$1';

// API Integrations
$route['api-integrations'] = 'api_integrations/index';
$route['api-integrations/create'] = 'api_integrations/create';
$route['api-integrations/store'] = 'api_integrations/store';
$route['api-integrations/edit/(:num)'] = 'api_integrations/edit/$1';
$route['api-integrations/update/(:num)'] = 'api_integrations/update/$1';
$route['api-integrations/delete/(:num)'] = 'api_integrations/delete/$1';

// DB Manager (MVC + AJAX)
$route['db'] = 'db/index';
$route['db/clients'] = 'db/client_panel';
$route['db/client-migrations'] = 'db/client_migrations';
$route['db/difference'] = 'db/db_difference';
$route['db/compare-databases'] = 'db/compare_databases';
$route['db/queries/list'] = 'db/list_queries';
$route['db/queries/save'] = 'db/save_query';
$route['db/queries/update/(\d+)'] = 'db/update_query/$1';
$route['db/queries/delete/(\d+)'] = 'db/delete_query/$1';
$route['db/queries/export/(\d+)'] = 'db/export_saved_query/$1';
$route['db/queries/export-bulk'] = 'db/export_bulk_saved_queries';
$route['db/queries/revert/(\d+)'] = 'db/revert_query/$1';
// DB Compare
$route['db/compare'] = 'db/compare';
$route['db/compare/scan'] = 'db/compare_scan';
$route['db/compare/merge'] = 'db/compare_merge';
$route['db/compare/update-file-missing'] = 'db/compare_update_file_missing';
$route['db/compare/drop-db-only'] = 'db/compare_drop_db_only';
$route['db/databases'] = 'db/list_databases';

// Timesheets
$route['timesheets'] = 'timesheets/index';
$route['timesheets/submit'] = 'timesheets/submit';
$route['timesheets/approve/(:num)'] = 'timesheets/approve/$1';
$route['timesheets/reject/(:num)'] = 'timesheets/reject/$1';
$route['timesheets/report'] = 'timesheets/report';

// Requirements
$route['requirements'] = 'requirements/index';
$route['requirements/create'] = 'requirements/create';
$route['requirements/edit/(:num)'] = 'requirements/edit/$1';
$route['requirements/view/(:num)'] = 'requirements/view/$1';
$route['requirements/version/(:num)'] = 'requirements/version/$1';
$route['requirements/board'] = 'requirements/board';
$route['requirements/calendar'] = 'requirements/calendar';
$route['requirements/export'] = 'requirements/export';

// Announcements
$route['announcements'] = 'announcements/index';
$route['announcements/create'] = 'announcements/create';
$route['announcements/(:num)/edit'] = 'announcements/edit/$1';
$route['announcements/(:num)/delete'] = 'announcements/delete/$1';

// Chats
$route['chats'] = 'chats/index';
$route['chats/app'] = 'chats/app';
$route['chats/start-dm'] = 'chats/start_dm';
$route['chats/create-group'] = 'chats/create_group';
$route['chats/conversation/(:num)'] = 'chats/conversation/$1';
$route['chats/send'] = 'chats/send_message';
$route['chats/send_message'] = 'chats/send_message';
$route['chats/fetch'] = 'chats/fetch_messages';
$route['chats/fetch_messages'] = 'chats/fetch_messages';
$route['chats/add-participants'] = 'chats/add_participants';
$route['chats/remove-participant'] = 'chats/remove_participant';
$route['chats/typing'] = 'chats/typing';
$route['chats/set-typing'] = 'chats/set_typing';
$route['chats/get-typing'] = 'chats/get_typing';
$route['chats/online-status'] = 'chats/online_status';
$route['chats/set-online-status'] = 'chats/set_online_status';
$route['chats/get-online-status'] = 'chats/get_online_status';
$route['chats/reaction'] = 'chats/add_reaction';
$route['chats/reaction/remove'] = 'chats/remove_reaction';
$route['chats/reactions'] = 'chats/get_reactions';
$route['chats/delete-message'] = 'chats/delete_message';
$route['chats/edit-message'] = 'chats/edit_message';

// Calls (WebRTC signaling over AJAX)
$route['calls/start/(:num)'] = 'calls/start/$1';
$route['calls/signal/(:num)'] = 'calls/signal/$1';
$route['calls/poll/(:num)'] = 'calls/poll_signals/$1';
$route['calls/end/(:num)'] = 'calls/end/$1';
$route['calls/incoming/(:num)'] = 'calls/poll_incoming/$1';
$route['calls/incoming-any'] = 'calls/poll_incoming_any';

// Mail (SMTP test & UI)
$route['mail'] = 'mail/index';
$route['mail/test'] = 'mail/test';
$route['mail/send'] = 'mail/send';

// SendGrid (API-based mailer)
$route['sendgrid'] = 'sendgrid/index';
$route['sendgrid/send'] = 'sendgrid/send';
$route['sendgrid/test'] = 'sendgrid/test';

// WhatsApp Integration
$route['whatsapp'] = 'whatsapp/index';
$route['whatsapp/send'] = 'whatsapp/send';
$route['whatsapp/send-task'] = 'whatsapp/send_task';
$route['whatsapp/send-report'] = 'whatsapp/send_report';

// Reminders
$route['reminders'] = 'reminders/index';
$route['reminders/cron/morning'] = 'reminders/cron_morning';
$route['reminders/cron/night'] = 'reminders/cron_night';
$route['reminders/cron/send-queue'] = 'reminders/send_queue';
$route['reminders/send'] = 'reminders/send';
$route['reminders/cron/send-selected'] = 'reminders/send_selected';
$route['reminders/send-now/(:num)'] = 'reminders/send_now/$1';
$route['reminders/edit/(:num)'] = 'reminders/edit/$1';
$route['reminders/delete/(:num)'] = 'reminders/delete/$1';
$route['reminders/delete-selected'] = 'reminders/delete_selected';
$route['reminders/templates'] = 'reminders/templates';
$route['reminders/schedules'] = 'reminders/schedules';
$route['reminders/schedules/create'] = 'reminders/schedule_create';
$route['reminders/schedules/(:num)/edit'] = 'reminders/schedule_edit/$1';
$route['reminders/schedules/(:num)/delete'] = 'reminders/schedule_delete/$1';
$route['reminders/schedules/(:num)/activate'] = 'reminders/schedule_activate/$1';
$route['reminders/schedules/(:num)/deactivate'] = 'reminders/schedule_deactivate/$1';
$route['reminders/cron/generate-today'] = 'reminders/cron_generate_today';
$route['reminders/announce'] = 'reminders/announce';
$route['reminders/bulk'] = 'reminders/bulk';
$route['reminders/import'] = 'reminders/import';
$route['reminders/import-sample'] = 'reminders/import_sample';

// Users
$route['users'] = 'users/index';
$route['users/create'] = 'users/create';
$route['users/store'] = 'users/store';
$route['users/edit/(:num)'] = 'users/edit/$1';
$route['users/view/(:num)'] = 'users/view/$1';
$route['users/update/(:num)'] = 'users/update/$1';
$route['users/delete/(:num)'] = 'users/delete/$1';
$route['users/check-email'] = 'users/check_email';
$route['users/check-phone'] = 'users/check_phone';
$route['users/save_face'] = 'users/save_face';

// Approvals
$route['approvals'] = 'approvals/index';
$route['approvals/create'] = 'approvals/create';
$route['approvals/edit/(:num)'] = 'approvals/edit/$1';
$route['approvals/save'] = 'approvals/save';
$route['approvals/delete/(:num)'] = 'approvals/delete/$1';

// Performance Appraisals
$route['performance'] = 'performance/index';
$route['performance/create'] = 'performance/create';
$route['performance/view/(:num)'] = 'performance/view/$1';
$route['performance/edit/(:num)'] = 'performance/edit/$1';
$route['performance/delete/(:num)'] = 'performance/delete/$1';
$route['performance/export'] = 'performance/export';
$route['performance/self-assess'] = 'performance/self_assess';
$route['performance/self-assess/(:num)'] = 'performance/self_assess/$1';

// Recruitment
$route['recruitment'] = 'recruitment/index';
$route['recruitment/create-job'] = 'recruitment/create_job';
$route['recruitment/edit-job/(:num)'] = 'recruitment/edit_job/$1';
$route['recruitment/delete-job/(:num)'] = 'recruitment/delete_job/$1';
$route['recruitment/close-job/(:num)'] = 'recruitment/close_job/$1';
$route['recruitment/candidates'] = 'recruitment/candidates';
$route['recruitment/candidate/(:num)'] = 'recruitment/candidate_view/$1';
$route['recruitment/candidate/(:num)/status'] = 'recruitment/candidate_status/$1';
$route['recruitment/apply/(:num)'] = 'recruitment/apply/$1';
$route['recruitment/schedule-interview/(:num)'] = 'recruitment/schedule_interview/$1';
$route['recruitment/export'] = 'recruitment/export';

// Daily Activity
$route['daily-activity'] = 'daily_activity/index';
$route['daily-activity/save'] = 'daily_activity/save';
$route['daily-activity/edit/(:num)'] = 'daily_activity/edit/$1';
$route['daily-activity/delete/(:num)'] = 'daily_activity/delete/$1';
$route['daily-activity/list'] = 'daily_activity/list_all';
$route['daily-activity/export'] = 'daily_activity/export';

// Expenses
$route['expenses'] = 'expenses/index';
$route['expenses/create'] = 'expenses/create';
$route['expenses/view/(:num)'] = 'expenses/view/$1';
$route['expenses/approve/(:num)'] = 'expenses/approve/$1';
$route['expenses/reject/(:num)'] = 'expenses/reject/$1';
$route['expenses/reimburse/(:num)'] = 'expenses/reimburse/$1';
$route['expenses/categories'] = 'expenses/categories';
$route['expenses/reports'] = 'expenses/reports';
$route['expenses/pending'] = 'expenses/pending';
$route['expenses/export'] = 'expenses/export';

// Payroll (sub-pages)
$route['payroll'] = 'payroll/index';
$route['payroll/payslips'] = 'payroll/payslips';
$route['payroll/structures'] = 'payroll/structures';
$route['payroll/structure/create'] = 'payroll/structure';
$route['payroll/structure/(:num)'] = 'payroll/structure/$1';
$route['payroll/generate'] = 'payroll/generate';
$route['payroll/view/(:num)'] = 'payroll/view/$1';
$route['payroll/export/(:num)'] = 'payroll/export/$1';
$route['payroll/send-payslips'] = 'payroll/send_payslips';

// AI Chat
$route['ai-chat'] = 'ai_chat/index';
$route['ai-chat/send'] = 'ai_chat/send_message';
$route['ai_chat'] = 'ai_chat/index';
$route['ai_chat/send_message'] = 'ai_chat/send_message';

// Analytics
$route['analytics'] = 'analytics/index';
$route['analytics/save-integrations'] = 'analytics/save_integrations';
$route['analytics/start-quick-call'] = 'analytics/start_quick_call';
$route['analytics/analyze-feedback'] = 'analytics/analyze_feedback';
$route['analytics/parse-resume'] = 'analytics/parse_resume';
$route['analytics/calendar-feed'] = 'analytics/calendar_feed';

// Superadmin
$route['superadmin'] = 'superadmin/index';
$route['superadmin/(:any)'] = 'superadmin/$1';

// Expenses export
$route['expenses/export'] = 'expenses/export';

// Payroll export (no id = all payslips)
$route['payroll/export'] = 'payroll/export';

// Clients contacts
$route['clients/(:num)/contacts'] = 'clients/contacts/$1';

// Approvals delete
$route['approvals/delete/(:num)'] = 'approvals/delete/$1';

// Profile sub-pages
$route['profile'] = 'profile/index';
$route['profile/edit'] = 'profile/edit';
$route['profile/remove-avatar'] = 'profile/remove_avatar';
$route['profile/delete'] = 'profile/delete_profile';

// Timesheets sub-actions
$route['timesheets/delete-entry/(:num)'] = 'timesheets/delete_entry/$1';
$route['timesheets/analytics'] = 'timesheets/analytics';
$route['timesheets/task-tracking'] = 'timesheets/task_tracking';

// Requirements comments (AJAX)
$route['requirements/(:num)/comments'] = 'requirements/get_comments/$1';
$route['requirements/(:num)/add-comment'] = 'requirements/add_comment/$1';
$route['requirements/(:num)/delete-comment/(:num)'] = 'requirements/delete_comment/$1/$2';

// AI Chat extras
$route['ai-chat/export'] = 'ai_chat/export';
$route['ai-chat/clear-history'] = 'ai_chat/clear_history';
$route['ai-chat/tts'] = 'ai_chat/tts';

// Tasks bulk update
$route['tasks/bulk-update-status'] = 'tasks/bulk_update_status';

// Attendance get_data (AJAX)
$route['attendance/get-data'] = 'attendance/get_user_monthly_attendance';

// Settings remove logo
$route['settings/remove-logo'] = 'settings/remove_logo';

// Announcements templates
$route['announcements/templates'] = 'announcements/templates';
$route['announcements/save-template'] = 'announcements/save_template';

// Email settings edit template
$route['email-settings/edit-template/(:num)'] = 'email_settings/edit_template/$1';

// Users destroy (actual delete POST action)
$route['users/destroy/(:num)'] = 'users/destroy/$1';
