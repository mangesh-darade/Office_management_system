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
$route['404_override'] = 'errors/page_missing';
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

// User Guide (in-app help with screenshots)
$route['guide'] = 'guide/index';
$route['guide/(:any)'] = 'guide/module/$1';

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
$route['projects/matrix'] = 'projects/matrix';
$route['projects/dashboard'] = 'projects/dashboard_index';
$route['projects/create'] = 'projects/create';
$route['projects/(:num)/dashboard'] = 'projects/dashboard/$1';
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

// My Works (personal / assigned work items)
$route['my-works'] = 'my_works/index';
$route['my-works/todays-focus'] = 'my_works/todays_focus';
$route['my-works/create'] = 'my_works/create';
$route['my-works/template-tasks'] = 'my_works/template_tasks';
$route['my-works/quick-add'] = 'my_works/quick_add';
$route['my-works/export'] = 'my_works/export';
$route['my-works/update-status'] = 'my_works/update_status';
$route['my-works/update-matrix'] = 'my_works/update_matrix';
$route['my-works/update-lane'] = 'my_works/update_lane';
$route['my-works/(:num)/comment'] = 'my_works/add_comment/$1';
$route['my-works/(:num)/edit'] = 'my_works/edit/$1';
$route['my-works/(:num)/delete'] = 'my_works/delete/$1';
$route['my-works/(:num)/attachment/(:num)/download'] = 'my_works/attachment_download/$1/$2';
$route['my-works/(:num)/attachment/(:num)/preview'] = 'my_works/attachment_preview/$1/$2';
$route['my-works/(:num)/download'] = 'my_works/download/$1';
$route['my-works/(:num)/preview'] = 'my_works/preview/$1';
$route['my-works/(:num)'] = 'my_works/show/$1';

// Statuses Management
$route['types'] = 'types/index';
$route['types/create'] = 'types/create';
$route['types/view/(:num)'] = 'types/view/$1';
$route['types/edit/(:num)'] = 'types/edit/$1';
$route['types/delete/(:num)'] = 'types/delete/$1';
$route['types/(:num)'] = 'types/show/$1';
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
// Legacy alias, see also attendance/get-data below
// $route['attendance/get_data'] = 'attendance/get_data';
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
$route['reports/requirements'] = 'reports_projects/requirements';
$route['reports/tasks-assignment'] = 'reports_projects/tasks_assignment';
$route['reports/projects-status'] = 'reports_projects/projects_status';
$route['reports/daily-activity'] = 'reports_projects/daily_activity';
$route['reports/leaves'] = 'reports_hr/leaves';
$route['reports/payroll'] = 'reports_hr/payroll';
$route['reports/expenses'] = 'reports_hr/expenses';
$route['reports/performance'] = 'reports_hr/performance';
$route['reports/attendance'] = 'reports_attendance/attendance';
$route['reports/attendance-employee'] = 'reports_attendance/attendance_employee';
$route['reports/attendance-employee/(:num)'] = 'reports_attendance/attendance_employee/$1';
$route['reports/export-attendance-employee'] = 'reports_attendance/export_attendance_employee';

// Profile
$route['profile'] = 'profile/index';

// Activity Logs
$route['activity'] = 'activity/index';
$route['activity/export'] = 'activity/export';

// Settings
$route['settings'] = 'settings/index';
$route['settings/update'] = 'settings/update';
$route['settings/upload-logo'] = 'settings/upload_logo';

// Shifts
$route['shifts'] = 'shifts/index';
$route['shifts/create'] = 'shifts/create';
$route['shifts/edit/(:num)'] = 'shifts/edit/$1';
$route['shifts/delete/(:num)'] = 'shifts/delete/$1';
$route['settings/test-email'] = 'settings/test_email';
// Leave Types Management
$route['settings/leave-types'] = 'settings/leave_types';
$route['settings/leave-types/create'] = 'settings/leave_types_create';
$route['settings/leave-types/(:num)/edit'] = 'settings/leave_types_edit/$1';
$route['settings/leave-types/(:num)/delete'] = 'settings/leave_types_delete/$1';
$route['settings/leave-types/(:num)/restore'] = 'settings/leave_types_restore/$1';

// Module Types Management (Settings)
$route['settings/types'] = 'settings/module_types';
$route['settings/types/create'] = 'settings/module_types_create';
$route['settings/types/(:num)/edit'] = 'settings/module_types_edit/$1';
$route['settings/types/(:num)/delete'] = 'settings/module_types_delete/$1';

// Holidays Management
$route['settings/holidays'] = 'settings/holidays';
$route['settings/holidays/create'] = 'settings/holidays_create';
$route['settings/holidays/(:num)/edit'] = 'settings/holidays_edit/$1';
$route['settings/holidays/(:num)/delete'] = 'settings/holidays_delete/$1';

// Subscription Builder catalog (Settings)
$route['settings/subscription-builder'] = 'settings/subscription_builder_catalog';
$route['settings/subscription-builder/create'] = 'settings/subscription_builder_create';
$route['settings/subscription-builder/import'] = 'settings/subscription_builder_import';
$route['settings/subscription-builder/sample-csv'] = 'settings/subscription_builder_sample_csv';
$route['settings/subscription-builder/sample-xlsx'] = 'settings/subscription_builder_sample_xlsx';
$route['settings/subscription-builder/(:num)/edit'] = 'settings/subscription_builder_edit/$1';
$route['settings/subscription-builder/(:num)/delete'] = 'settings/subscription_builder_delete/$1';

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
$route['db/ensure-schemas'] = 'db/ensure_schemas';
$route['db/apply-database-diff'] = 'db/apply_database_diff';
$route['db/test-client-connection'] = 'db/test_client_connection';
$route['db/create-local-client-db'] = 'db/create_local_client_database';
$route['db/save-client-db-host'] = 'db/save_client_db_host';
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
$route['requirements/import'] = 'requirements/import';

// Subscription Builder
$route['subscription-builder'] = 'subscription_builder/index';
$route['subscription-builder/catalog'] = 'subscription_builder/catalog';
$route['subscription-builder/quote-preview'] = 'subscription_builder/quote_preview';
$route['subscription-builder/quote-pdf'] = 'subscription_builder/quote_pdf';
$route['subscription-builder/quote-excel'] = 'subscription_builder/quote_excel';
$route['subscription-builder/quote-doc'] = 'subscription_builder/quote_doc';
$route['subscription-builder/quote-save'] = 'subscription_builder/quote_save';

// ElintOm Proposals
$route['elintom-proposals'] = 'elintom_proposals/index';
$route['elintom-proposals/(:num)/download'] = 'elintom_proposals/download/$1';

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
$route['lead-mapping'] = 'lead_mapping/index';
$route['lead-mapping/save'] = 'lead_mapping/save';

// Approvals
$route['approvals'] = 'approvals/index';
$route['approvals/create'] = 'approvals/create';
$route['approvals/edit/(:num)'] = 'approvals/edit/$1';
$route['approvals/save'] = 'approvals/save';
$route['approvals/delete/(:num)'] = 'approvals/delete/$1';

// Training & Assessment
$route['training-assessment'] = 'training_assessment/dashboard';
$route['training-assessment/dashboard'] = 'training_assessment/dashboard';
$route['training-assessment/create'] = 'training_assessment/create_assessment';
$route['training-assessment/edit/(:num)'] = 'training_assessment/create_assessment/$1';
$route['training-assessment/save'] = 'training_assessment/save_assessment';
$route['training-assessment/delete/(:num)'] = 'training_assessment/delete_assessment/$1';
$route['training-assessment/duplicate/(:num)'] = 'training_assessment/duplicate_assessment/$1';
$route['training-assessment/preview/(:num)'] = 'training_assessment/preview_assessment/$1';
$route['training-assessment/questions/reorder'] = 'training_assessment/reorder_questions';
$route['training-assessment/questions/(:num)'] = 'training_assessment/question_list/$1';
$route['training-assessment/question/add/(:num)'] = 'training_assessment/add_question/$1';
$route['training-assessment/question/edit/(:num)'] = 'training_assessment/edit_question/$1';
$route['training-assessment/question/duplicate/(:num)'] = 'training_assessment/duplicate_question/$1';
$route['training-assessment/question/save'] = 'training_assessment/save_question';
$route['training-assessment/question/import/(:num)'] = 'training_assessment/import_questions_process/$1';
$route['training-assessment/question/import/sample'] = 'training_assessment/import_questions_sample_csv';
$route['training-assessment/question/import-dashboard'] = 'training_assessment/import_questions_dashboard_process';
$route['training-assessment/question/delete/(:num)'] = 'training_assessment/delete_question/$1';
$route['training-assessment/assign/(:num)'] = 'training_assessment/assign/$1';
$route['training-assessment/take/(:any)'] = 'training_assessment_take/take_assessment/$1';
$route['training-assessment/result/(:num)'] = 'training_assessment/result/$1';
$route['training-assessment/result-token/(:any)'] = 'training_assessment_take/result_token/$1';
$route['training-assessment/submit-assessment'] = 'training_assessment_take/submit_assessment';
$route['training-assessment/ajax-load-question'] = 'training_assessment_take/ajax_load_question';
$route['training-assessment/ajax-save-answer'] = 'training_assessment_take/ajax_save_answer';
$route['training-assessment/ajax-run-code'] = 'training_assessment_take/ajax_run_code';
$route['training-assessment/ajax-timer-sync'] = 'training_assessment_take/ajax_timer_sync';
$route['training-assessment/ajax-upload-screenshot'] = 'training_assessment_take/ajax_upload_screenshot';
$route['training-assessment/candidate-profile'] = 'training_assessment_take/candidate_profile';
$route['training-assessment/retake-assessment'] = 'training_assessment_take/retake_assessment';
$route['training-assessment/certificate/(:num)'] = 'training_assessment/certificate/$1';
$route['training-assessment/screenshots/(:num)'] = 'training_assessment/screenshots/$1';
$route['training-assessment/screenshots/(:num)/delete/(:num)'] = 'training_assessment/delete_screenshot/$1/$2';
$route['training-assessment/screenshots/(:num)/delete-bulk'] = 'training_assessment/delete_screenshots_bulk/$1';
$route['training-assessment/report'] = 'training_assessment/report';
$route['training-assessment/submissions'] = 'training_assessment/submissions';
$route['training-assessment/report/export'] = 'training_assessment/report_export';
$route['training-assessment/office-export/questions'] = 'training_assessment/office_export_questions';
$route['training-assessment/office-export/attempt-detail'] = 'training_assessment/office_export_attempt_detail';
$route['training-assessment/import'] = 'training_assessment/import_assessment';
$route['training-assessment/import/process'] = 'training_assessment/import_process';
$route['training-assessment/import/sample'] = 'training_assessment/import_sample_csv';

// Training LMS (courses + topic file assignments)
$route['training'] = 'training_lms/index';
$route['training/module/(:num)'] = 'training_lms/module/$1';
$route['training/topic/(:num)'] = 'training_lms/topic/$1';
$route['training/start-assessment/(:num)'] = 'training_lms/start_assessment/$1';
$route['training/submit-assignment'] = 'training_lms/submit_assignment';
$route['training/download/(:num)'] = 'training_lms/download/$1';
$route['training/my-training'] = 'training_lms/learner_hub';
$route['training/complete-topic'] = 'training_lms/complete_topic';
$route['training/import'] = 'training_import/index';
$route['training/import/sample/(:any)'] = 'training_import/sample/$1';
$route['training/import/process'] = 'training_import/process';

// External Trainings (simple CRUD on sma_external_trainings)
$route['external-training'] = 'external_training/index';
$route['external-training/create'] = 'external_training/create';
$route['external-training/edit/(:num)'] = 'external_training/edit/$1';
$route['external-training/watch/(:num)'] = 'external_training/watch/$1';
$route['external-training/save'] = 'external_training/save';
$route['external-training/delete/(:num)'] = 'external_training/delete/$1';

$route['training-lms-admin'] = 'training_lms_admin/index';
$route['training-lms-admin/module/create'] = 'training_lms_admin/module_form';
$route['training-lms-admin/module/edit/(:num)'] = 'training_lms_admin/module_form/$1';
$route['training-lms-admin/save-module'] = 'training_lms_admin/save_module';
$route['training-lms-admin/module/delete/(:num)'] = 'training_lms_admin/delete_module/$1';
$route['training-lms-admin/topics/(:num)'] = 'training_lms_admin/topics/$1';
$route['training-lms-admin/enrollments/(:num)'] = 'training_lms_admin/module_enrollments/$1';
$route['training-lms-admin/enrollment-save'] = 'training_lms_admin/enrollment_save';
$route['training-lms-admin/enrollment-remove'] = 'training_lms_admin/enrollment_remove';
$route['training-lms-admin/topic/create/(:num)'] = 'training_lms_admin/topic_form/$1';
$route['training-lms-admin/topic/edit/(:num)/(:num)'] = 'training_lms_admin/topic_form/$1/$2';
$route['training-lms-admin/save-topic'] = 'training_lms_admin/save_topic';
$route['training-lms-admin/topic/delete/(:num)'] = 'training_lms_admin/delete_topic/$1';
$route['training-lms-admin/submissions/(:num)'] = 'training_lms_admin/submissions/$1';
$route['training-lms-admin/submission/save'] = 'training_lms_admin/submission_save';
$route['training-lms-admin/download/(:num)'] = 'training_lms_admin/download/$1';
$route['training-lms-admin/assignment-submissions'] = 'training_lms_admin/assignment_submissions_list';

// Performance Appraisals
$route['performance'] = 'performance/index';
$route['performance/create'] = 'performance/create';
$route['performance/view/(:num)'] = 'performance/view/$1';
$route['performance/edit/(:num)'] = 'performance/edit/$1';
$route['performance/delete/(:num)'] = 'performance/delete/$1';
$route['performance/export'] = 'performance/export';
$route['performance/self-assess'] = 'performance/self_assess';
$route['performance/self-assess/(:num)'] = 'performance/self_assess/$1';

// Coaching
$route['coaching'] = 'coaching/index';
$route['coaching/admin'] = 'coaching_admin/index';
$route['coaching-admin'] = 'coaching_admin/index';
$route['coaching-admin/backup'] = 'coaching_admin/backup';
$route['coaching-admin/run-automation'] = 'coaching_admin/run_automation';
$route['coaching-portal'] = 'coaching_portal/index';
$route['coaching-portal/(:any)'] = 'coaching_portal/$1';
$route['coaching-clients'] = 'coaching_clients/index';
$route['coaching-clients/create'] = 'coaching_clients/create';
$route['coaching-clients/edit/(:num)'] = 'coaching_clients/edit/$1';
$route['coaching-clients/view/(:num)'] = 'coaching_clients/view/$1';
$route['coaching-coaches'] = 'coaching_coaches/index';
$route['coaching-coaches/create'] = 'coaching_coaches/create';
$route['coaching-coaches/edit/(:num)'] = 'coaching_coaches/edit/$1';
$route['coaching-coaches/delete/(:num)'] = 'coaching_coaches/delete/$1';
$route['coaching-sessions'] = 'coaching_sessions/index';
$route['coaching-sessions/calendar'] = 'coaching_sessions/calendar';
$route['coaching-sessions/create'] = 'coaching_sessions/create';
$route['coaching-sessions/edit/(:num)'] = 'coaching_sessions/edit/$1';
$route['coaching-sessions/delete/(:num)'] = 'coaching_sessions/delete/$1';
$route['coaching-goals'] = 'coaching_goals/index';
$route['coaching-goals/save-goal'] = 'coaching_goals/save_goal';
$route['coaching-goals/save-homework'] = 'coaching_goals/save_homework';
$route['coaching-leads'] = 'coaching_leads/index';
$route['coaching-leads/create'] = 'coaching_leads/create';
$route['coaching-leads/edit/(:num)'] = 'coaching_leads/edit/$1';
$route['coaching-leads/convert/(:num)'] = 'coaching_leads/convert/$1';
$route['coaching-leads/workshops'] = 'coaching_leads/workshops';
$route['coaching-leads/workshop-form'] = 'coaching_leads/workshop_form';
$route['coaching-leads/workshop-form/(:num)'] = 'coaching_leads/workshop_form/$1';
$route['coaching-leads/workshop-register/(:num)'] = 'coaching_leads/workshop_register/$1';
$route['coaching-billing'] = 'coaching_billing/index';
$route['coaching-billing/save-program'] = 'coaching_billing/save_program';
$route['coaching-billing/create-invoice'] = 'coaching_billing/create_invoice';
$route['coaching-billing/invoice/(:num)'] = 'coaching_billing/invoice/$1';
$route['coaching-billing/mark-paid/(:num)'] = 'coaching_billing/mark_paid/$1';
$route['coaching-billing/payouts'] = 'coaching_billing/payouts';
$route['coaching-reports'] = 'coaching_reports/index';
$route['coaching-resources'] = 'coaching_resources/index';
$route['coaching-resources/save'] = 'coaching_resources/save';
$route['coaching-whatsapp'] = 'coaching_whatsapp_crm/index';
$route['coaching-whatsapp-crm'] = 'coaching_whatsapp_crm/index';
$route['coaching-whatsapp-crm/save-enquiry'] = 'coaching_whatsapp_crm/save_enquiry';
$route['coaching-whatsapp-crm/broadcast'] = 'coaching_whatsapp_crm/broadcast';
$route['coaching-payments/pay/(:num)'] = 'coaching_payments/pay/$1';
$route['coaching-payments/verify'] = 'coaching_payments/verify';
$route['coaching-payments/confirm-manual/(:num)'] = 'coaching_payments/confirm_manual/$1';
$route['coaching-payments/success'] = 'coaching_payments/success';
$route['coaching/payments/(:num)'] = 'coaching_payments/pay/$1';
$route['coaching-webhooks/razorpay'] = 'coaching_webhooks/razorpay';
$route['coaching-webhooks/whatsapp-inbound'] = 'coaching_webhooks/whatsapp_inbound';

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
$route['expenses/edit/(:num)'] = 'expenses/edit/$1';
$route['expenses/delete/(:num)'] = 'expenses/delete/$1';
$route['expenses/approve/(:num)'] = 'expenses/approve/$1';
$route['expenses/reject/(:num)'] = 'expenses/reject/$1';
$route['expenses/reimburse/(:num)'] = 'expenses/reimburse/$1';
$route['expenses/categories'] = 'expenses/categories';
$route['expenses/categories/save'] = 'expenses/save_category';
$route['expenses/categories/toggle/(:num)'] = 'expenses/toggle_category/$1';
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
$route['analytics/calendar-feed/(:any)'] = 'analytics/calendar_feed/$1';

// Superadmin
$route['superadmin'] = 'superadmin/index';
$route['superadmin/(:any)'] = 'superadmin/$1';

// Expenses export (duplicate removed above)
// Payroll export (no id = all payslips) — defined above
// Clients contacts — defined above
// Approvals delete — defined above

// Profile sub-pages
$route['profile/edit'] = 'profile/edit';
$route['profile/remove-avatar'] = 'profile/remove_avatar';
$route['profile/delete'] = 'profile/delete_profile';

// Timesheets sub-actions
$route['timesheets/delete-entry/(:num)'] = 'timesheets/delete_entry/$1';
$route['timesheets/analytics'] = 'timesheets/analytics';
$route['timesheets/task-tracking/(:num)'] = 'timesheets/task_tracking/$1';
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

// Engagement modules (missing modules for rewards)
$route['releases'] = 'releases/index';
$route['releases/create'] = 'releases/create';
$route['releases/edit/(:num)'] = 'releases/edit/$1';
$route['releases/send-notes/(:num)'] = 'releases/send_notes/$1';
$route['defects'] = 'defects/index';
$route['defects/create'] = 'defects/create';
$route['defects/view/(:num)'] = 'defects/view/$1';
$route['defects/edit/(:num)'] = 'defects/edit/$1';
$route['defects/delete/(:num)'] = 'defects/delete/$1';
$route['knowledge-base'] = 'knowledge_base/index';
$route['knowledge-base/create'] = 'knowledge_base/create';
$route['knowledge-base/edit/(:num)'] = 'knowledge_base/edit/$1';
$route['knowledge-base/view/(:num)'] = 'knowledge_base/view/$1';
$route['helpdesk'] = 'helpdesk/index';
$route['helpdesk/create'] = 'helpdesk/create';
$route['helpdesk/edit/(:num)'] = 'helpdesk/edit/$1';
$route['events'] = 'events/index';
$route['events/create'] = 'events/create';
$route['events/edit/(:num)'] = 'events/edit/$1';
$route['certifications'] = 'certifications/index';
$route['certifications/create'] = 'certifications/create';
$route['certifications/approve/(:num)'] = 'certifications/approve/$1';
$route['certifications/reject/(:num)'] = 'certifications/reject/$1';
$route['customer-feedback'] = 'customer_feedback/index';
$route['customer-feedback/create'] = 'customer_feedback/create';

// Rewards & Recognition
$route['rewards'] = 'rewards/index';
$route['rewards/history'] = 'rewards/history';
$route['rewards/leaderboard'] = 'rewards/leaderboard';
$route['rewards/cheer'] = 'rewards/cheer';
$route['rewards/rules'] = 'rewards/rules';
$route['rewards/edit-rule'] = 'rewards/edit_rule';
$route['rewards/edit-rule/(:num)'] = 'rewards/edit_rule/$1';
$route['rewards/manual-grant'] = 'rewards/manual_grant';
$route['rewards/submit-claim'] = 'rewards/submit_claim';
$route['rewards/approvals'] = 'rewards/approvals';
$route['rewards/approve-claim/(:num)'] = 'rewards/approve_claim/$1';
$route['rewards/reject-claim/(:num)'] = 'rewards/reject_claim/$1';
$route['rewards/office-closing'] = 'rewards/office_closing';

// Office Meals
$route['meals'] = 'meals/index';
$route['meals/save_order'] = 'meals/save_order';
$route['meals/submit_request'] = 'meals/submit_request';
$route['meals/review_request'] = 'meals/review_request';
$route['meals/calendar'] = 'meals/calendar';
$route['meals/provider'] = 'meals/provider';
$route['meals/settings'] = 'meals/settings';
$route['meals/history'] = 'meals/history';
$route['meals/all_orders'] = 'meals/all_orders';
$route['meals/export'] = 'meals/export';
