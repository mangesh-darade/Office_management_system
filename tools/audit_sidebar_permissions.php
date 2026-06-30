<?php
/**
 * Sidebar vs route-level RBAC audit (read-only).
 * Usage: php tools/audit_sidebar_permissions.php [--json]
 */
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('ENVIRONMENT', 'development');

require_once APPPATH . 'helpers/permission_helper.php';

$db = [];
include APPPATH . 'config/database.php';
$cfg = $db['default'];
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8');

$controller_map = get_controller_module_access_map();

// Parse routes.php: first URI segment => controller class
$route_to_controller = [];
$routes_file = file_get_contents(APPPATH . 'config/routes.php');
if (preg_match_all("/\\\$route\\['([^']+)'\\]\\s*=\\s*'([^']+)'/", $routes_file, $m, PREG_SET_ORDER)) {
    foreach ($m as $row) {
        $pattern = $row[1];
        $target = $row[2];
        if (strpos($pattern, '(') !== false || strpos($pattern, '^') !== false || strpos($pattern, '$') !== false) {
            continue;
        }
        $seg = strtolower(trim($pattern, '/'));
        if ($seg === '' || $seg === 'default_controller' || $seg === '404_override' || $seg === 'translate_uri_dashes') {
            continue;
        }
        $controller = strtolower(explode('/', $target)[0]);
        $route_to_controller[$seg] = $controller;
    }
}

/**
 * Curated sidebar screens (post-cleanup). sidebar_keys = OR list for nav visibility.
 * extra_gate: admin_group | superadmin | is_admin_group_or_key
 */
$sidebar_screens = [
    ['module' => 'Core', 'label' => 'Dashboard', 'route' => 'dashboard', 'sidebar_keys' => [], 'controller' => 'dashboard', 'extra_gate' => null],
    ['module' => 'Core', 'label' => 'My Works', 'route' => 'my-works', 'sidebar_keys' => ['my_works', 'my_works_list'], 'controller' => 'my_works'],
    ['module' => 'Core', 'label' => "Today's Focus", 'route' => 'my-works/todays-focus', 'sidebar_keys' => ['my_works', 'my_works_list'], 'controller' => 'my_works'],
    ['module' => 'Daily Activity', 'label' => 'Daily Activity', 'route' => 'daily-activity', 'sidebar_keys' => ['daily_activity'], 'controller' => 'daily_activity'],
    ['module' => 'Daily Activity', 'label' => 'All Activities', 'route' => 'daily-activity/list', 'sidebar_keys' => ['daily_activity'], 'controller' => 'daily_activity'],
    ['module' => 'Daily Activity', 'label' => 'Export CSV', 'route' => 'daily-activity/export', 'sidebar_keys' => ['daily_activity'], 'controller' => 'daily_activity'],
    ['module' => 'Communication', 'label' => 'Mail (SMTP)', 'route' => 'mail', 'sidebar_keys' => ['mail'], 'controller' => 'mail'],
    ['module' => 'Communication', 'label' => 'SendGrid', 'route' => 'sendgrid', 'sidebar_keys' => ['mail'], 'controller' => 'sendgrid'],
    ['module' => 'Communication', 'label' => 'WhatsApp', 'route' => 'whatsapp', 'sidebar_keys' => ['whatsapp'], 'controller' => 'whatsapp', 'extra_gate' => 'superadmin_or_whatsapp'],
    ['module' => 'HR', 'label' => 'Clients', 'route' => 'clients', 'sidebar_keys' => ['clients'], 'controller' => 'clients'],
    ['module' => 'HR', 'label' => 'Employees', 'route' => 'employees', 'sidebar_keys' => ['employees'], 'controller' => 'employees'],
    ['module' => 'HR', 'label' => 'Chats', 'route' => 'chats/app', 'sidebar_keys' => ['chats'], 'controller' => 'chats'],
    ['module' => 'Recruitment', 'label' => 'Job Openings', 'route' => 'recruitment', 'sidebar_keys' => ['recruitment', 'recruitment_jobs', 'recruitment_candidates'], 'controller' => 'recruitment'],
    ['module' => 'Recruitment', 'label' => 'Candidates', 'route' => 'recruitment/candidates', 'sidebar_keys' => ['recruitment', 'recruitment_jobs', 'recruitment_candidates'], 'controller' => 'recruitment'],
    ['module' => 'Recruitment', 'label' => 'Export CSV', 'route' => 'recruitment/export', 'sidebar_keys' => ['recruitment', 'recruitment_jobs', 'recruitment_candidates'], 'controller' => 'recruitment'],
    ['module' => 'Performance', 'label' => 'All Appraisals', 'route' => 'performance', 'sidebar_keys' => ['performance'], 'controller' => 'performance', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Performance', 'label' => 'Self-Assessment', 'route' => 'performance/self-assess', 'sidebar_keys' => ['performance'], 'controller' => 'performance', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Performance', 'label' => 'Export CSV', 'route' => 'performance/export', 'sidebar_keys' => ['performance'], 'controller' => 'performance', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Dashboard', 'route' => 'coaching', 'sidebar_keys' => ['coaching'], 'controller' => 'coaching', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Clients', 'route' => 'coaching-clients', 'sidebar_keys' => ['coaching_clients'], 'controller' => 'coaching_clients', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Coaches', 'route' => 'coaching-coaches', 'sidebar_keys' => ['coaching_coaches'], 'controller' => 'coaching_coaches', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Sessions', 'route' => 'coaching-sessions', 'sidebar_keys' => ['coaching_sessions'], 'controller' => 'coaching_sessions', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Goals', 'route' => 'coaching-goals', 'sidebar_keys' => ['coaching_goals'], 'controller' => 'coaching_goals', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Leads', 'route' => 'coaching-leads', 'sidebar_keys' => ['coaching_leads'], 'controller' => 'coaching_leads', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Billing', 'route' => 'coaching-billing', 'sidebar_keys' => ['coaching_billing'], 'controller' => 'coaching_billing', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Reports', 'route' => 'coaching-reports', 'sidebar_keys' => ['coaching_reports'], 'controller' => 'coaching_reports', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'WhatsApp CRM', 'route' => 'coaching-whatsapp-crm', 'sidebar_keys' => ['coaching_whatsapp_crm'], 'controller' => 'coaching_whatsapp_crm', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Resources', 'route' => 'coaching-resources', 'sidebar_keys' => ['coaching_resources'], 'controller' => 'coaching_resources', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Coaching', 'label' => 'Admin', 'route' => 'coaching-admin', 'sidebar_keys' => ['coaching_admin'], 'controller' => 'coaching_admin', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'Training', 'label' => 'TA Dashboard', 'route' => 'training-assessment', 'sidebar_keys' => ['training_screen_ta_dashboard', 'training_assessment_take', 'training_screen_ta_my_tests', 'training_assessment', 'training_assessment_manage'], 'controller' => 'training_assessment'],
    ['module' => 'Training', 'label' => 'Import CSV', 'route' => 'training-assessment/import', 'sidebar_keys' => ['training_screen_ta_import', 'training_assessment_manage'], 'controller' => 'training_assessment'],
    ['module' => 'Training', 'label' => 'Report', 'route' => 'training-assessment/report', 'sidebar_keys' => ['training_screen_ta_report', 'training_assessment_manage'], 'controller' => 'training_assessment'],
    ['module' => 'Training', 'label' => 'Submissions', 'route' => 'training-assessment/submissions', 'sidebar_keys' => ['training_screen_ta_submissions'], 'controller' => 'training_assessment'],
    ['module' => 'Training', 'label' => 'External trainings', 'route' => 'external-training', 'sidebar_keys' => ['external_training', 'external_training_watch', 'external_training_list'], 'controller' => 'external_training'],
    ['module' => 'User', 'label' => 'Users', 'route' => 'users', 'sidebar_keys' => ['users', 'users_list', 'users_add', 'users_edit', 'users_delete'], 'controller' => 'users'],
    ['module' => 'User', 'label' => 'Roles', 'route' => 'roles', 'sidebar_keys' => ['roles', 'permissions'], 'controller' => 'roles'],
    ['module' => 'User', 'label' => 'Assets', 'route' => 'assets-mgmt', 'sidebar_keys' => ['assets', 'assets_mgmt'], 'controller' => 'assets'],
    ['module' => 'User', 'label' => 'Attendance', 'route' => 'attendance', 'sidebar_keys' => ['attendance', 'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete'], 'controller' => 'attendance'],
    ['module' => 'User', 'label' => 'Shifts', 'route' => 'shifts', 'sidebar_keys' => ['shifts', 'shifts_view', 'shifts_manage'], 'controller' => 'shifts', 'extra_gate' => 'superadmin_or_key'],
    ['module' => 'User', 'label' => 'Department', 'route' => 'departments', 'sidebar_keys' => ['departments'], 'controller' => 'departments'],
    ['module' => 'User', 'label' => 'Designation', 'route' => 'designations', 'sidebar_keys' => ['designations'], 'controller' => 'designations'],
    ['module' => 'Payroll', 'label' => 'Payslips', 'route' => 'payroll/payslips', 'sidebar_keys' => ['payroll', 'payroll_view', 'payroll_manage'], 'controller' => 'payroll'],
    ['module' => 'Payroll', 'label' => 'Pay Structures', 'route' => 'payroll/structures', 'sidebar_keys' => ['payroll', 'payroll_view', 'payroll_manage'], 'controller' => 'payroll'],
    ['module' => 'Payroll', 'label' => 'Generate Payroll', 'route' => 'payroll/generate', 'sidebar_keys' => ['payroll', 'payroll_view', 'payroll_manage'], 'controller' => 'payroll'],
    ['module' => 'Payroll', 'label' => 'Payroll Report', 'route' => 'reports/payroll', 'sidebar_keys' => ['payroll', 'payroll_view', 'payroll_manage'], 'controller' => 'reports'],
    ['module' => 'Expenses', 'label' => 'My Expenses', 'route' => 'expenses', 'sidebar_keys' => ['expenses', 'expenses_add', 'expenses_edit', 'expenses_delete', 'expenses_approve', 'expenses_reimburse', 'expenses_reports', 'expenses_categories'], 'controller' => 'expenses'],
    ['module' => 'Expenses', 'label' => 'Approvals', 'route' => 'expenses/pending', 'sidebar_keys' => ['expenses_approve'], 'controller' => 'expenses'],
    ['module' => 'Expenses', 'label' => 'Categories', 'route' => 'expenses/categories', 'sidebar_keys' => ['expenses_categories'], 'controller' => 'expenses'],
    ['module' => 'Expenses', 'label' => 'Reports', 'route' => 'expenses/report', 'sidebar_keys' => ['expenses_reports'], 'controller' => 'expenses'],
    ['module' => 'Leave', 'label' => 'Apply Leave', 'route' => 'leave/apply', 'sidebar_keys' => ['leave_requests', 'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete', 'leave_team', 'leave_calendar', 'leave_approve'], 'controller' => 'leave_requests'],
    ['module' => 'Leave', 'label' => 'My Leaves', 'route' => 'leave/my', 'sidebar_keys' => ['leave_requests', 'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete', 'leave_team', 'leave_calendar', 'leave_approve'], 'controller' => 'leave_requests'],
    ['module' => 'Leave', 'label' => 'Team Leaves', 'route' => 'leave/team', 'sidebar_keys' => ['leave_team'], 'controller' => 'leave_requests', 'extra_gate' => 'admin_or_key'],
    ['module' => 'Leave', 'label' => 'Leave Calendar', 'route' => 'leave/calendar', 'sidebar_keys' => ['leave_calendar'], 'controller' => 'leave_requests', 'extra_gate' => 'admin_or_key'],
    ['module' => 'Project', 'label' => 'Projects', 'route' => 'projects', 'sidebar_keys' => ['projects'], 'controller' => 'projects'],
    ['module' => 'Project', 'label' => 'Project Dashboard', 'route' => 'projects/dashboard', 'sidebar_keys' => ['projects', 'projects_list'], 'controller' => 'projects'],
    ['module' => 'Project', 'label' => 'Portfolio Matrix', 'route' => 'projects/matrix', 'sidebar_keys' => ['projects_matrix', 'projects', 'projects_list'], 'controller' => 'projects'],
    ['module' => 'Project', 'label' => 'Requirement', 'route' => 'requirements', 'sidebar_keys' => ['requirements'], 'controller' => 'requirements'],
    ['module' => 'Project', 'label' => 'Task Board', 'route' => 'tasks/board', 'sidebar_keys' => ['tasks'], 'controller' => 'tasks'],
    ['module' => 'Project', 'label' => 'Timesheet', 'route' => 'timesheets', 'sidebar_keys' => ['timesheets'], 'controller' => 'timesheets'],
    ['module' => 'Project', 'label' => 'Monthly Report', 'route' => 'timesheets/report', 'sidebar_keys' => ['timesheets'], 'controller' => 'timesheets'],
    ['module' => 'Project', 'label' => 'Releases', 'route' => 'releases', 'sidebar_keys' => ['releases'], 'controller' => 'releases'],
    ['module' => 'Project', 'label' => 'Defects', 'route' => 'defects', 'sidebar_keys' => ['defects'], 'controller' => 'defects'],
    ['module' => 'Tools', 'label' => 'AI Assistant', 'route' => 'ai_chat', 'sidebar_keys' => ['ai', 'ai_chat'], 'controller' => 'ai_chat'],
    ['module' => 'Tools', 'label' => 'Subscription Builder', 'route' => 'subscription-builder', 'sidebar_keys' => ['subscription_builder', 'subscription_builder_list'], 'controller' => 'subscription_builder'],
    ['module' => 'Tools', 'label' => 'ElintOm Proposals', 'route' => 'elintom-proposals', 'sidebar_keys' => ['elintom_proposals', 'elintom_proposals_list'], 'controller' => 'elintom_proposals'],
    ['module' => 'Engagement', 'label' => 'My Rewards', 'route' => 'rewards', 'sidebar_keys' => ['rewards'], 'controller' => 'rewards'],
    ['module' => 'Engagement', 'label' => 'Leaderboard', 'route' => 'rewards/leaderboard', 'sidebar_keys' => ['rewards'], 'controller' => 'rewards'],
    ['module' => 'Engagement', 'label' => 'Knowledge Base', 'route' => 'knowledge-base', 'sidebar_keys' => ['knowledge_base'], 'controller' => 'knowledge_base'],
    ['module' => 'Engagement', 'label' => 'Helpdesk', 'route' => 'helpdesk', 'sidebar_keys' => ['helpdesk'], 'controller' => 'helpdesk'],
    ['module' => 'Engagement', 'label' => 'Events', 'route' => 'events', 'sidebar_keys' => ['events'], 'controller' => 'events'],
    ['module' => 'Engagement', 'label' => 'Certifications', 'route' => 'certifications', 'sidebar_keys' => ['certifications'], 'controller' => 'certifications'],
    ['module' => 'Engagement', 'label' => 'Customer Feedback', 'route' => 'customer-feedback', 'sidebar_keys' => ['customer_feedback'], 'controller' => 'customer_feedback'],
    ['module' => 'Engagement', 'label' => 'Reward Rules', 'route' => 'rewards/rules', 'sidebar_keys' => ['rewards_rules', 'rewards_admin'], 'controller' => 'rewards'],
    ['module' => 'Core', 'label' => 'Announcements', 'route' => 'announcements', 'sidebar_keys' => ['announcements'], 'controller' => 'announcements'],
    ['module' => 'Core', 'label' => 'Notifications', 'route' => 'notifications', 'sidebar_keys' => ['notifications'], 'controller' => 'notifications'],
    ['module' => 'Reports', 'label' => 'AI Analytics', 'route' => 'analytics', 'sidebar_keys' => ['analytics'], 'controller' => 'analytics'],
    ['module' => 'Reports', 'label' => 'Overview', 'route' => 'reports', 'sidebar_keys' => ['reports_overview', 'reports'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Requirements Report', 'route' => 'reports/requirements', 'sidebar_keys' => ['reports_requirements'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Task Assignment', 'route' => 'reports/tasks-assignment', 'sidebar_keys' => ['reports_tasks_assignment'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Projects by Status', 'route' => 'reports/projects-status', 'sidebar_keys' => ['reports_projects_status'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Leaves Report', 'route' => 'reports/leaves', 'sidebar_keys' => ['reports_leaves'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Attendance Report', 'route' => 'reports/attendance', 'sidebar_keys' => ['reports_attendance'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Employee Attendance', 'route' => 'reports/attendance-employee', 'sidebar_keys' => ['reports_attendance_employee'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Daily Activity Log', 'route' => 'reports/daily-activity', 'sidebar_keys' => ['daily_activity_report', 'reports'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Expenses Report', 'route' => 'reports/expenses', 'sidebar_keys' => ['reports_expenses', 'reports', 'expenses'], 'controller' => 'reports'],
    ['module' => 'Reports', 'label' => 'Performance Report', 'route' => 'reports/performance', 'sidebar_keys' => ['reports_performance', 'reports', 'performance'], 'controller' => 'reports'],
    ['module' => 'Settings', 'label' => 'System Settings', 'route' => 'settings', 'sidebar_keys' => ['settings'], 'controller' => 'settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'User Access', 'route' => 'system-settings/user-access', 'sidebar_keys' => ['system_settings'], 'controller' => 'system_settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Permission Manager', 'route' => 'permissions', 'sidebar_keys' => ['permissions'], 'controller' => 'permissions', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Approval Workflows', 'route' => 'approvals', 'sidebar_keys' => ['approvals'], 'controller' => 'approvals', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Status Management', 'route' => 'statuses', 'sidebar_keys' => ['admin', 'statuses'], 'controller' => 'statuses', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Module Types', 'route' => 'settings/types', 'sidebar_keys' => ['types', 'settings', 'admin'], 'controller' => 'types', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Leave Types', 'route' => 'settings/leave-types', 'sidebar_keys' => ['leave_types', 'settings', 'admin'], 'controller' => 'settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Holidays', 'route' => 'settings/holidays', 'sidebar_keys' => ['holidays', 'settings', 'admin'], 'controller' => 'settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Email Settings', 'route' => 'email-settings', 'sidebar_keys' => ['email_settings'], 'controller' => 'email_settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'API Integrations', 'route' => 'api-integrations', 'sidebar_keys' => ['admin', 'settings'], 'controller' => 'api_integrations', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Lead Mapping', 'route' => 'lead-mapping', 'sidebar_keys' => ['lead_mapping'], 'controller' => 'lead_mapping', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Database Manager', 'route' => 'db', 'sidebar_keys' => ['db'], 'controller' => 'db', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Client DB Panel', 'route' => 'db/clients', 'sidebar_keys' => ['db'], 'controller' => 'db', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Reminders', 'route' => 'reminders', 'sidebar_keys' => ['reminders'], 'controller' => 'reminders', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Activity Log', 'route' => 'activity', 'sidebar_keys' => ['activity'], 'controller' => 'activity', 'extra_gate' => 'admin_group'],
    ['module' => 'Settings', 'label' => 'Subscription Builder Catalog', 'route' => 'settings/subscription-builder', 'sidebar_keys' => ['subscription_builder'], 'controller' => 'settings', 'extra_gate' => 'admin_group'],
    ['module' => 'Core', 'label' => 'Super Admin', 'route' => 'superadmin', 'sidebar_keys' => ['superadmin'], 'controller' => 'superadmin'],
];

function resolve_controller($route, $explicit, $route_to_controller) {
    if ($explicit !== '') {
        return $explicit;
    }
    $seg = strtolower(trim(explode('/', $route)[0], '/'));
    return isset($route_to_controller[$seg]) ? $route_to_controller[$seg] : str_replace('-', '_', $seg);
}

function build_perm_map($mysqli) {
    $map = [];
    $res = $mysqli->query("SELECT role_id, module, can_access FROM permissions WHERE can_access = 1");
    while ($row = $res->fetch_assoc()) {
        $mod = strtolower(trim((string) $row['module']));
        if ($mod === '') {
            continue;
        }
        $map[$mod][] = (int) $row['role_id'];
    }
    foreach ($map as $mod => $roles) {
        $map[$mod] = array_values(array_unique($roles));
    }
    return $map;
}

function role_has_any_key($role_id, $keys, $perm_map) {
    if ($role_id === 1) {
        return true;
    }
    foreach ($keys as $key) {
        $key = strtolower(trim((string) $key));
        if ($key === '') {
            continue;
        }
        if (isset($perm_map[$key]) && in_array($role_id, $perm_map[$key], true)) {
            return true;
        }
    }
    return false;
}

function authhook_route_access($role_id, $controller, $controller_map, $perm_map) {
    if ($role_id === 1) {
        return ['allowed' => true, 'reason' => 'super_admin'];
    }
    $always = ['dashboard', 'profile', 'auth', 'errors', 'welcome', 'cron', 'install', 'migrate', 'short_url', 'test_company', 'coaching_portal', 'coaching_webhooks'];
    if (in_array($controller, $always, true)) {
        return ['allowed' => true, 'reason' => 'always_allowed'];
    }
    $keys = isset($controller_map[$controller]) ? $controller_map[$controller] : [$controller];
    $has_any_rule = false;
    $has_access = false;
    foreach ($keys as $key) {
        if (array_key_exists($key, $perm_map)) {
            $has_any_rule = true;
            if (in_array($role_id, $perm_map[$key], true)) {
                $has_access = true;
                break;
            }
        }
    }
    if (!$has_any_rule) {
        return ['allowed' => true, 'reason' => 'no_db_rule_open'];
    }
    if ($has_access) {
        return ['allowed' => true, 'reason' => 'granted'];
    }
    return ['allowed' => false, 'reason' => 'authhook_blocked', 'keys' => $keys];
}

function keys_overlap($sidebar_keys, $controller_keys) {
    if (empty($sidebar_keys)) {
        return true;
    }
    return count(array_intersect($sidebar_keys, $controller_keys)) > 0;
}

$perm_map = build_perm_map($mysqli);
$roles = [];
$res = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $roles[(int) $row['id']] = $row['name'];
}

$admin_group_roles = [];
$res = $mysqli->query("SELECT id FROM roles WHERE id = 1 OR LOWER(name) LIKE '%admin%'");
while ($row = $res->fetch_assoc()) {
    $admin_group_roles[] = (int) $row['id'];
}

$key_alignment_issues = [];
$role_mismatches = [];
$screens_checked = 0;

foreach ($sidebar_screens as &$screen) {
    $screens_checked++;
    $controller = resolve_controller($screen['route'], $screen['controller'], $route_to_controller);
    $screen['resolved_controller'] = $controller;
    $controller_keys = isset($controller_map[$controller]) ? $controller_map[$controller] : [$controller];
    $screen['controller_keys'] = $controller_keys;

    if (!keys_overlap($screen['sidebar_keys'], $controller_keys) && !empty($screen['sidebar_keys'])) {
        $key_alignment_issues[] = [
            'label' => $screen['label'],
            'route' => $screen['route'],
            'sidebar_keys' => $screen['sidebar_keys'],
            'controller' => $controller,
            'controller_keys' => $controller_keys,
        ];
    }

    foreach ($roles as $role_id => $role_name) {
        if ($role_id === 1) {
            continue;
        }
        $sidebar_visible = role_has_any_key($role_id, $screen['sidebar_keys'], $perm_map);
        if (!empty($screen['sidebar_keys']) && !$sidebar_visible) {
            continue;
        }
        if (empty($screen['sidebar_keys'])) {
            $sidebar_visible = true;
        }

        $route = authhook_route_access($role_id, $controller, $controller_map, $perm_map);

        if ($sidebar_visible && !$route['allowed']) {
            $role_mismatches[] = [
                'type' => 'SIDEBAR_SHOWS_ROUTE_BLOCKS',
                'role_id' => $role_id,
                'role' => $role_name,
                'label' => $screen['label'],
                'route' => $screen['route'],
                'controller' => $controller,
                'sidebar_keys' => $screen['sidebar_keys'],
                'controller_keys' => $route['keys'],
            ];
        }
    }
}
unset($screen);

// Roles with zero sidebar screens but many permissions
$role_screen_counts = [];
foreach ($roles as $role_id => $role_name) {
    if ($role_id === 1) {
        continue;
    }
    $count = 0;
    foreach ($sidebar_screens as $screen) {
        if (empty($screen['sidebar_keys']) || role_has_any_key($role_id, $screen['sidebar_keys'], $perm_map)) {
            $count++;
        }
    }
    $role_screen_counts[$role_id] = ['name' => $role_name, 'visible_screens' => $count];
}

// Permission keys in DB not used in controller map
$all_controller_keys = [];
foreach ($controller_map as $keys) {
    foreach ($keys as $k) {
        $all_controller_keys[$k] = true;
    }
}
$orphan_db_keys = [];
foreach (array_keys($perm_map) as $mod) {
    if (!isset($all_controller_keys[$mod])) {
        $orphan_db_keys[] = $mod;
    }
}
sort($orphan_db_keys);

$report = [
    'generated_at' => date('Y-m-d H:i:s'),
    'screens_checked' => $screens_checked,
    'roles' => $roles,
    'key_alignment_issues' => $key_alignment_issues,
    'role_mismatches' => $role_mismatches,
    'role_screen_counts' => $role_screen_counts,
    'orphan_db_keys_count' => count($orphan_db_keys),
    'orphan_db_keys_sample' => array_slice($orphan_db_keys, 0, 40),
];

$json_out = in_array('--json', $argv ?? [], true);

if ($json_out) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "=== Sidebar Permission Audit ===\n";
echo "Generated: {$report['generated_at']}\n";
echo "Screens checked: {$screens_checked}\n";
echo "Roles: " . count($roles) . "\n\n";

echo "--- Key alignment (sidebar keys vs controller map) ---\n";
if (empty($key_alignment_issues)) {
    echo "OK: No sidebar/controller key mismatches.\n";
} else {
    foreach ($key_alignment_issues as $issue) {
        echo "[MISMATCH] {$issue['label']} ({$issue['route']})\n";
        echo "  sidebar: " . implode(', ', $issue['sidebar_keys']) . "\n";
        echo "  controller {$issue['controller']}: " . implode(', ', $issue['controller_keys']) . "\n";
    }
}
echo "\n";

echo "--- Role-wise: sidebar visible but AuthHook blocks ---\n";
if (empty($role_mismatches)) {
    echo "OK: No sidebar-show / route-block mismatches found.\n";
} else {
    $by_role = [];
    foreach ($role_mismatches as $mm) {
        $by_role[$mm['role']][] = $mm;
    }
    foreach ($by_role as $role_name => $items) {
        echo "\nRole: {$role_name} (" . count($items) . " issues)\n";
        foreach ($items as $item) {
            echo "  - {$item['label']} [{$item['route']}] controller={$item['controller']}\n";
        }
    }
}
echo "\n";

echo "--- Visible sidebar screens per role (non-admin) ---\n";
foreach ($role_screen_counts as $rid => $info) {
    echo "  [{$rid}] {$info['name']}: {$info['visible_screens']} screens\n";
}
echo "\n";

echo "--- DB permission keys not in controller map (sample) ---\n";
echo "Total orphan keys: " . count($orphan_db_keys) . "\n";
if (!empty($orphan_db_keys)) {
    echo implode(', ', array_slice($orphan_db_keys, 0, 30)) . (count($orphan_db_keys) > 30 ? '...' : '') . "\n";
}

$md_path = __DIR__ . '/../docs/PERMISSION_AUDIT_REPORT.md';
$md = "# Permission Audit Report\n\n";
$md .= "Generated: {$report['generated_at']}\n\n";
$md .= "## Summary\n\n";
$md .= "| Metric | Value |\n|--------|-------|\n";
$md .= "| Screens audited | {$screens_checked} |\n";
$md .= "| Roles | " . count($roles) . " |\n";
$md .= "| Key alignment issues | " . count($key_alignment_issues) . " |\n";
$md .= "| Sidebar-show / route-block mismatches | " . count($role_mismatches) . " |\n";
$md .= "| Orphan DB permission keys | " . count($orphan_db_keys) . " |\n\n";

$md .= "## Roles & visible screens\n\n";
$md .= "| Role ID | Role | Visible sidebar screens |\n|---------|------|-------------------------|\n";
foreach ($role_screen_counts as $rid => $info) {
    $md .= "| {$rid} | {$info['name']} | {$info['visible_screens']} |\n";
}
$md .= "\n";

if (!empty($key_alignment_issues)) {
    $md .= "## Key alignment issues\n\n";
    foreach ($key_alignment_issues as $issue) {
        $md .= "### {$issue['label']} (`{$issue['route']}`)\n";
        $md .= "- Sidebar keys: `" . implode('`, `', $issue['sidebar_keys']) . "`\n";
        $md .= "- Controller `{$issue['controller']}` keys: `" . implode('`, `', $issue['controller_keys']) . "`\n\n";
    }
}

if (!empty($role_mismatches)) {
    $md .= "## Sidebar visible but route blocked (by role)\n\n";
    $md .= "User sees link in sidebar but AuthHook redirects to dashboard.\n\n";
    $by_role = [];
    foreach ($role_mismatches as $mm) {
        $by_role[$mm['role']][] = $mm;
    }
    foreach ($by_role as $role_name => $items) {
        $md .= "### {$role_name}\n\n";
        foreach ($items as $item) {
            $md .= "- **{$item['label']}** — `{$item['route']}` (controller: `{$item['controller']}`)\n";
        }
        $md .= "\n";
    }
}

$md .= "## Methodology\n\n";
$md .= "1. Sidebar permission keys (OR logic) compared to `get_controller_module_access_map()`.\n";
$md .= "2. Per role: if sidebar would show link, simulate AuthHook route RBAC (any mapped key granted).\n";
$md .= "3. Role 1 (Super Admin) bypasses all checks.\n";
$md .= "4. Settings admin submenu requires `is_admin_group()` in sidebar — not fully simulated here.\n\n";
$md .= "Re-run: `php tools/audit_sidebar_permissions.php`\n";

file_put_contents($md_path, $md);
echo "\nReport written to docs/PERMISSION_AUDIT_REPORT.md\n";
