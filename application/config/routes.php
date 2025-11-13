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

// Dashboard
$route['dashboard'] = 'dashboard/index';

// Employees
$route['employees'] = 'employees/index';
$route['employees/create'] = 'employees/create';
$route['employees/(:num)'] = 'employees/show/$1';
$route['employees/(:num)/edit'] = 'employees/edit/$1';
$route['employees/(:num)/delete'] = 'employees/delete/$1';
$route['employees/import'] = 'employees/import';

// Departments
$route['departments'] = 'departments/index';
$route['departments/create'] = 'departments/create';
$route['departments/(:num)/edit'] = 'departments/edit/$1';
$route['departments/(:num)/delete'] = 'departments/delete/$1';

// Designations
$route['designations'] = 'designations/index';
$route['designations/create'] = 'designations/create';
$route['designations/(:num)/edit'] = 'designations/edit/$1';
$route['designations/(:num)/delete'] = 'designations/delete/$1';

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

// Tasks
$route['tasks'] = 'tasks/index';
$route['tasks/create'] = 'tasks/create';
$route['tasks/(:num)'] = 'tasks/show/$1';
$route['tasks/(:num)/edit'] = 'tasks/edit/$1';
$route['tasks/(:num)/delete'] = 'tasks/delete/$1';
$route['tasks/import'] = 'tasks/import';
$route['tasks/board'] = 'tasks/board';
$route['tasks/update-status'] = 'tasks/update_status';
// Task comments
$route['tasks/(:num)/comment'] = 'tasks/add_comment/$1';
$route['tasks/(:num)/comments'] = 'tasks/get_comments/$1';
$route['tasks/comment/(:num)/delete'] = 'tasks/delete_comment/$1';

// Permissions Manager
$route['permissions'] = 'permissions/index';
$route['permissions/save'] = 'permissions/save';

// Attendance
$route['attendance'] = 'attendance/index';
$route['attendance/create'] = 'attendance/create';
$route['attendance/(:num)/edit'] = 'attendance/edit/$1';
$route['attendance/(:num)/delete'] = 'attendance/delete/$1';

// Leaves
$route['leaves'] = 'leaves/index';
$route['leaves/export'] = 'leaves/export_csv';
$route['leaves/test-email'] = 'leaves/test_email';
// Leave Requests (Phase 1)
$route['leave/apply'] = 'leave_requests/apply';
$route['leave/my'] = 'leave_requests/my';
// Leave Requests (Phase 2)
$route['leave/team'] = 'leave_requests/team';
$route['leave/approve/(:num)'] = 'leave_requests/approve/$1';
$route['leave/reject/(:num)'] = 'leave_requests/reject/$1';
$route['leave/calendar'] = 'leave_requests/calendar';

// Notifications
$route['notifications'] = 'notifications/index';

// Reports
$route['reports'] = 'reports/index';
$route['reports/export'] = 'reports/export_csv';

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

// DB Manager (MVC + AJAX)
$route['db'] = 'db/index';
$route['db/queries/list'] = 'db/list_queries';
$route['db/queries/save'] = 'db/save_query';
$route['db/queries/update/(:num)'] = 'db/update_query/$1';
$route['db/queries/delete/(:num)'] = 'db/delete_query/$1';
$route['db/queries/export/(:num)'] = 'db/export_saved_query/$1';
$route['db/queries/export-bulk'] = 'db/export_bulk_saved_queries';

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
$route['chats/fetch'] = 'chats/fetch_messages';
$route['chats/add-participants'] = 'chats/add_participants';
$route['chats/remove-participant'] = 'chats/remove_participant';

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

// Reminders
$route['reminders'] = 'reminders/index';
$route['reminders/cron/morning'] = 'reminders/cron_morning';
$route['reminders/cron/night'] = 'reminders/cron_night';
$route['reminders/cron/send-queue'] = 'reminders/send_queue';
$route['reminders/send'] = 'reminders/send';
$route['reminders/cron/send-selected'] = 'reminders/send_selected';
$route['reminders/delete/(:num)'] = 'reminders/delete/$1';
$route['reminders/delete-selected'] = 'reminders/delete_selected';
$route['reminders/templates'] = 'reminders/templates';
$route['reminders/schedules'] = 'reminders/schedules';
$route['reminders/schedules/create'] = 'reminders/schedule_create';
$route['reminders/cron/generate-today'] = 'reminders/cron_generate_today';
$route['reminders/announce'] = 'reminders/announce';
