<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('permissions_module_meta')) {
    /**
     * Normalize a permission row from Permissions.php (string label or array with tag).
     *
     * @param string|array $def
     * @return array{label:string,tag:string}
     */
    function permissions_module_meta($def)
    {
        if (is_array($def)) {
            return array(
                'label' => isset($def['label']) ? (string) $def['label'] : '',
                'tag'   => isset($def['tag']) ? (string) $def['tag'] : '',
            );
        }
        return array(
            'label' => (string) $def,
            'tag'   => '',
        );
    }
}

if (!function_exists('permissions_module_tag_class')) {
    function permissions_module_tag_class($tag)
    {
        $tag = strtolower(trim((string) $tag));
        if ($tag === 'screen') {
            return 'bg-info text-dark';
        }
        if ($tag === 'action') {
            return 'bg-warning text-dark';
        }
        if ($tag === 'full') {
            return 'bg-primary';
        }
        return 'bg-secondary';
    }
}

if (!function_exists('has_module_access')) {
    function has_module_access($module) {
        static $cache = null;
        static $admin_group_cache = null;

        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }

        // Role 1 (Super Admin) always has full access to prevent lock-out
        if ($role_id === 1) { return true; }

        $key = strtolower(trim((string)$module));

        // Legacy controller keys → grant when parent module is allowed
        if ($key === 'assets_mgmt') {
            if (has_module_access('assets') || has_module_access('assets_manage')) {
                return true;
            }
        }

        // Build permission cache once per request
        if ($cache === null) {
            $cache = array();
            if (isset($CI->db) && $CI->db && $CI->db->table_exists('permissions')) {
                $perms = $CI->db->get('permissions')->result();
                foreach ($perms as $p) {
                    $mod = strtolower(trim((string)$p->module));
                    if ($mod === '') { continue; }
                    if (!isset($cache[$mod])) { $cache[$mod] = array(); }
                    if ((int)$p->can_access === 1) { $cache[$mod][] = (int)$p->role_id; }
                }
                foreach ($cache as $mod => $roles) {
                    $cache[$mod] = array_values(array_unique($roles));
                }
            }
        }

        if (empty($cache) || !isset($cache[$key])) { return false; }
        return in_array($role_id, $cache[$key], true);
    }
}

if (!function_exists('require_module_access')) {
    function require_module_access($module, $redirect_to_dashboard = true) {
        $CI =& get_instance();
        
        // Check if user is logged in
        $user_id = $CI->session->userdata('user_id');
        if (!$user_id) {
            if ($redirect_to_dashboard) {
                redirect('auth/login');
            } else {
                show_error('Please login to access this page.', 401);
            }
            return false;
        }
        
        // Check module access (supports array of modules)
        $has_access = false;
        if (is_array($module)) {
            foreach ($module as $m) {
                if (has_module_access($m)) {
                    $has_access = true;
                    break;
                }
            }
        } else {
            $has_access = has_module_access($module);
        }

        if (!$has_access) {
            if ($redirect_to_dashboard) {
                // Set flash message to inform user
                $module_name = is_array($module) ? implode(' or ', $module) : $module;
                $CI->session->set_flashdata('access_denied', 'You do not have permission to access the ' . ucfirst($module_name) . ' module.');
                redirect('dashboard');
            } else {
                // Use show_error which will trigger our custom 403 page
                show_error('You do not have permission to access this page.', 403);
            }
            exit;
        }
        
        return true;
    }
}

if (!function_exists('get_dashboard_module_groups')) {
    /**
     * Parent dashboard module keys mapped to granular permission keys.
     * Keeps stat cards and counters aligned with sidebar / has_module_access checks.
     */
    function get_dashboard_module_groups() {
        return [
            'employees' => [
                'employees', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete',
                'employees_view', 'employees_view_all', 'employees_edit_all', 'employees_delete_all',
                'employees_documents', 'employees_import',
            ],
            'projects' => [
                'projects', 'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                'projects_view_all', 'projects_import', 'projects_matrix',
            ],
            'tasks' => [
                'tasks', 'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
            ],
            'attendance' => [
                'attendance', 'attendance_list', 'attendance_add', 'attendance_edit',
                'attendance_delete', 'attendance_view_all', 'attendance_bulk', 'attendance_export',
            ],
            'leaves' => [
                'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
                'leave_requests', 'leave_team', 'leave_calendar', 'leave_approve',
            ],
            'reports' => [
                'reports', 'analytics', 'reports_overview', 'reports_requirements',
                'reports_tasks_assignment', 'reports_projects_status', 'reports_defects', 'reports_leaves',
                'reports_attendance', 'reports_attendance_employee', 'reports_daily_activity',
                'daily_activity_report', 'reports_payroll', 'reports_expenses', 'reports_performance',
            ],
            'my_works' => [
                'my_works', 'my_works_list', 'my_works_add', 'my_works_edit', 'my_works_delete',
                'my_works_view_all', 'my_works_export',
            ],
            'coaching' => [
                'coaching', 'coaching_coaches', 'coaching_clients', 'coaching_sessions',
                'coaching_goals', 'coaching_leads', 'coaching_billing', 'coaching_reports',
                'coaching_whatsapp_crm', 'coaching_resources', 'coaching_admin', 'coaching_portal',
            ],
            'meals' => [
                'meals_order', 'meals_calendar', 'meals_provider', 'meals_settings',
                'meals_history', 'meals_all_orders',
            ],
            'defects' => [
                'defects', 'defects_list', 'defects_add', 'defects_edit', 'defects_delete',
                'defects_view', 'defects_export',
            ],
            'releases' => [
                'releases', 'releases_list', 'releases_add', 'releases_edit', 'releases_delete',
                'releases_view', 'releases_send_notes', 'releases_export',
            ],
            'spl' => [
                'spl', 'spl_my_reward', 'spl_submit', 'spl_approve', 'spl_rules', 'spl_groups', 'spl_groups_manage',
                'rewards', 'rewards_submit', 'rewards_rules', 'rewards_admin', 'rewards_approve', 'rewards_leaderboard',
            ],
            'chats' => ['chats', 'chats_list', 'chats_add', 'chatsgrouping', 'calls'],
            'settings' => [
                'settings', 'holidays', 'holidays_add', 'holidays_edit', 'holidays_delete',
                'email_settings', 'system_settings', 'mail', 'sendgrid', 'api_integrations',
            ],
            'announcements' => [
                'announcements', 'announcements_list', 'announcements_add', 'announcements_edit',
                'announcements_delete', 'announcements_manage',
            ],
            'users' => ['users', 'users_list', 'users_add', 'users_edit', 'users_delete', 'users_view', 'users_view_all'],
            'clients' => ['clients', 'clients_list', 'clients_add', 'clients_edit', 'clients_delete'],
            'recruitment' => [
                'recruitment', 'recruitment_jobs', 'recruitment_candidates', 'recruitment_interviews',
                'recruitment_export', 'recruitment_add', 'recruitment_delete',
            ],
            'performance' => [
                'performance', 'performance_create', 'performance_view', 'performance_edit',
                'performance_delete', 'performance_self_assess', 'performance_export',
            ],
            'training_lms' => [
                'training_lms', 'training_lms_manage',
                'training_screen_tl_hub', 'training_screen_tl_module', 'training_screen_tl_assignment',
            ],
            'training_assessment' => [
                'training_assessment', 'training_assessment_manage', 'training_assessment_take',
                'training_screen_ta_dashboard', 'training_screen_ta_create', 'training_screen_ta_import',
                'training_screen_ta_report', 'training_screen_ta_submissions',
                'training_screen_ta_team_progress', 'training_screen_ta_my_tests',
            ],
            'requirements' => [
                'requirements', 'requirements_list', 'requirements_add', 'requirements_edit',
                'requirements_delete', 'requirements_view', 'requirements_board', 'requirements_calendar',
                'requirements_export', 'requirements_delete_all',
            ],
            'timesheets' => ['timesheets', 'timesheets_list', 'timesheets_add', 'timesheets_edit', 'timesheets_delete'],
            'payroll' => ['payroll', 'payroll_list', 'payroll_add', 'payroll_edit', 'payroll_delete', 'payroll_export'],
            'expenses' => ['expenses', 'expenses_list', 'expenses_add', 'expenses_edit', 'expenses_delete', 'expenses_approve'],
            'daily_activity' => ['daily_activity', 'daily_activity_list', 'daily_activity_add', 'reports_daily_activity'],
        ];
    }
}

if (!function_exists('normalize_accessible_modules_for_dashboard')) {
    function normalize_accessible_modules_for_dashboard(array $modules) {
        $normalized = array_values(array_unique(array_filter(array_map(function ($m) {
            return strtolower(trim((string)$m));
        }, $modules))));

        foreach (get_dashboard_module_groups() as $parent => $aliases) {
            foreach ($aliases as $alias) {
                if (in_array($alias, $normalized, true)) {
                    if (!in_array($parent, $normalized, true)) {
                        $normalized[] = $parent;
                    }
                    break;
                }
            }
        }

        return $normalized;
    }
}

if (!function_exists('dashboard_has_module_access')) {
    function dashboard_has_module_access($parent_module) {
        $parent = strtolower(trim((string)$parent_module));
        if ($parent === '') {
            return false;
        }
        if (has_module_access($parent)) {
            return true;
        }
        $groups = get_dashboard_module_groups();
        if (isset($groups[$parent])) {
            $aliases = array_values(array_diff($groups[$parent], [$parent]));
            if (can_access_any_module($aliases)) {
                return true;
            }
        }
        if (function_exists('get_controller_module_access_map')) {
            $map = get_controller_module_access_map();
            if (isset($map[$parent]) && can_access_any_module($map[$parent])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('repair_invalid_permission_rows')) {
    function repair_invalid_permission_rows() {
        static $done = false;
        if ($done) { return; }
        $done = true;
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('permissions')) {
            return;
        }
        $CI->db->where("(module IS NULL OR TRIM(module) = '')", null, false)->delete('permissions');
    }
}

if (!function_exists('seed_coaching_defaults_if_needed')) {
    /**
     * Idempotent: Coaching Client role (5) + default coaching permissions for Admin/Manager/Lead.
     */
    function seed_coaching_defaults_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }
        if (!$CI->db) {
            return;
        }

        if ($CI->db->table_exists('roles')) {
            $role_id = 5;
            $exists = $CI->db->where('id', $role_id)->get('roles')->row();
            if (!$exists) {
                $row = array(
                    'id'   => $role_id,
                    'name' => 'Coaching Client',
                );
                if (schema_table_has_column($CI->db, 'roles', 'group_type')) {
                    $row['group_type'] = 'user';
                }
                if (schema_table_has_column($CI->db, 'roles', 'is_active')) {
                    $row['is_active'] = 1;
                }
                if (schema_table_has_column($CI->db, 'roles', 'sort_order')) {
                    $row['sort_order'] = $role_id;
                }
                $CI->db->insert('roles', $row);
            }
        }

        if (!$CI->db->table_exists('permissions')) {
            return;
        }

        $all_coaching = array(
            'coaching', 'coaching_coaches', 'coaching_clients', 'coaching_sessions',
            'coaching_goals', 'coaching_leads', 'coaching_billing', 'coaching_reports',
            'coaching_whatsapp_crm', 'coaching_resources', 'coaching_admin', 'coaching_portal',
        );

        $matrix = array(
            1 => $all_coaching,
            2 => $all_coaching,
            3 => array(
                'coaching', 'coaching_clients', 'coaching_sessions', 'coaching_goals',
                'coaching_leads', 'coaching_reports', 'coaching_resources',
            ),
            5 => array('coaching_portal'),
        );

        foreach ($matrix as $role_id => $keys) {
            foreach ($keys as $module) {
                $exists = $CI->db
                    ->where('role_id', (int) $role_id)
                    ->where('module', $module)
                    ->limit(1)
                    ->get('permissions')
                    ->row();
                if ($exists) {
                    continue;
                }
                $CI->db->insert('permissions', array(
                    'role_id'    => (int) $role_id,
                    'module'     => $module,
                    'can_access' => 1,
                ));
            }
        }
    }
}

if (!function_exists('seed_guide_permission_if_needed')) {
    /**
     * Grant User Guide to all roles by default (read-only help for every logged-in user).
     */
    function seed_guide_permission_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions') || !$CI->db->table_exists('roles')) {
            return;
        }

        $roles = $CI->db->select('id')->from('roles')->get()->result();
        foreach ($roles as $role) {
            $role_id = (int) $role->id;
            if ($role_id <= 0) {
                continue;
            }
            $exists = $CI->db
                ->where('role_id', $role_id)
                ->where('module', 'guide')
                ->limit(1)
                ->get('permissions')
                ->row();
            if ($exists) {
                continue;
            }
            $CI->db->insert('permissions', [
                'role_id'    => $role_id,
                'module'     => 'guide',
                'can_access' => 1,
            ]);
        }
    }
}

if (!function_exists('get_accessible_modules')) {
    function get_accessible_modules($normalize_for_dashboard = false) {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return []; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return []; }

        repair_invalid_permission_rows();

        $accessible_modules = [];
        if (isset($CI->db) && $CI->db && $CI->db->table_exists('permissions')) {
            $CI->db->select('module');
            $CI->db->where('role_id', $role_id);
            $CI->db->where('can_access', 1);
            $result = $CI->db->get('permissions')->result();
            
            foreach ($result as $row) {
                $accessible_modules[] = strtolower(trim($row->module));
            }
        }

        if ($normalize_for_dashboard) {
            return normalize_accessible_modules_for_dashboard($accessible_modules);
        }

        return $accessible_modules;
    }
}

if (!function_exists('can_access_any_module')) {
    function can_access_any_module($modules = []) {
        if (empty($modules)) { return false; }
        
        foreach ($modules as $module) {
            if (has_module_access($module)) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('get_attendance_export_module_keys')) {
    /**
     * Permission keys that allow exporting attendance from the list screen.
     */
    function get_attendance_export_module_keys() {
        return [
            'attendance_export',
            'attendance',
            'attendance_list',
            'attendance_view_all',
            'reports_attendance',
            'reports_attendance_employee',
        ];
    }
}

if (!function_exists('can_access_attendance_export')) {
    function can_access_attendance_export() {
        if (function_exists('is_admin_group') && is_admin_group()) {
            return true;
        }
        return can_access_any_module(get_attendance_export_module_keys());
    }
}

if (!function_exists('seed_attendance_export_if_needed')) {
    /**
     * Grant attendance_export to roles that already have list/report attendance access.
     */
    function seed_attendance_export_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions')) {
            return;
        }

        $source_keys = get_attendance_export_module_keys();
        $roles = $CI->db
            ->select('DISTINCT role_id', false)
            ->where_in('module', $source_keys)
            ->where('can_access', 1)
            ->get('permissions')
            ->result();

        foreach ($roles as $row) {
            $role_id = (int) $row->role_id;
            if ($role_id <= 0) {
                continue;
            }
            $exists = $CI->db
                ->where('role_id', $role_id)
                ->where('module', 'attendance_export')
                ->limit(1)
                ->get('permissions')
                ->row();
            if ($exists) {
                continue;
            }
            $CI->db->insert('permissions', [
                'role_id'    => $role_id,
                'module'     => 'attendance_export',
                'can_access' => 1,
            ]);
        }
    }
}

if (!function_exists('seed_meals_default_permissions_if_needed')) {
    /**
     * Grant default Office Meals access to all roles; admin screens to admin roles.
     */
    function seed_meals_default_permissions_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions') || !$CI->db->table_exists('roles')) {
            return;
        }
        $CI->load->helper('schema_columns');

        $all_roles = $CI->db->select('id')->from('roles')->get()->result();
        $staff_keys = array('meals_order');
        $admin_keys = array(
            'meals_calendar', 'meals_provider', 'meals_settings', 'meals_history', 'meals_all_orders',
        );

        $admin_role_ids = array();
        if (schema_table_has_column($CI->db, 'roles', 'group_type')) {
            $rows = $CI->db->select('id')->from('roles')->where('group_type', 'admin')->get()->result();
            foreach ($rows as $r) {
                $admin_role_ids[(int) $r->id] = true;
            }
        }
        if (empty($admin_role_ids)) {
            foreach (array(1, 2, 3) as $rid) {
                $admin_role_ids[$rid] = true;
            }
        }

        foreach ($all_roles as $role) {
            $role_id = (int) $role->id;
            if ($role_id <= 0) {
                continue;
            }
            $keys = $staff_keys;
            if (isset($admin_role_ids[$role_id])) {
                $keys = array_merge($keys, $admin_keys);
            }
            foreach ($keys as $module) {
                $exists = $CI->db
                    ->where('role_id', $role_id)
                    ->where('module', $module)
                    ->limit(1)
                    ->get('permissions')
                    ->row();
                if ($exists) {
                    continue;
                }
                $CI->db->insert('permissions', array(
                    'role_id'    => $role_id,
                    'module'     => $module,
                    'can_access' => 1,
                ));
            }
        }
    }
}

if (!function_exists('seed_subscription_builder_permissions_if_needed')) {
    /**
     * Grant Subscription Builder to admin roles by default.
     */
    function seed_subscription_builder_permissions_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions') || !$CI->db->table_exists('roles')) {
            return;
        }
        $CI->load->helper('schema_columns');

        $admin_role_ids = array();
        if (schema_table_has_column($CI->db, 'roles', 'group_type')) {
            $rows = $CI->db->select('id')->from('roles')->where('group_type', 'admin')->get()->result();
            foreach ($rows as $r) {
                $admin_role_ids[(int) $r->id] = true;
            }
        }
        if (empty($admin_role_ids)) {
            foreach (array(1, 2, 3) as $rid) {
                $admin_role_ids[$rid] = true;
            }
        }

        $keys = array(
            'subscription_builder', 'subscription_builder_list',
            'elintom_proposals', 'elintom_proposals_list',
            'eba_platform', 'eba_platform_list',
        );
        foreach ($admin_role_ids as $role_id => $_) {
            foreach ($keys as $module) {
                $exists = $CI->db
                    ->where('role_id', (int) $role_id)
                    ->where('module', $module)
                    ->limit(1)
                    ->get('permissions')
                    ->row();
                if ($exists) {
                    continue;
                }
                $CI->db->insert('permissions', array(
                    'role_id'    => (int) $role_id,
                    'module'     => $module,
                    'can_access' => 1,
                ));
            }
        }
    }
}

if (!function_exists('seed_project_extensions_permissions_if_needed')) {
    /**
     * Grant Releases (admin) and Defects (all staff report, admin full) under Project menu.
     */
    function seed_project_extensions_permissions_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions') || !$CI->db->table_exists('roles')) {
            return;
        }
        $CI->load->helper('schema_columns');

        $all_roles = $CI->db->select('id')->from('roles')->get()->result();
        $staff_defect_keys = array('defects_list', 'defects_add', 'defects_view', 'defects_export');
        $admin_keys = array(
            'releases', 'releases_list', 'releases_view', 'releases_add', 'releases_edit',
            'releases_delete', 'releases_send_notes', 'releases_export',
            'defects', 'defects_edit', 'defects_delete',
        );

        $admin_role_ids = array();
        if (schema_table_has_column($CI->db, 'roles', 'group_type')) {
            $rows = $CI->db->select('id')->from('roles')->where('group_type', 'admin')->get()->result();
            foreach ($rows as $r) {
                $admin_role_ids[(int) $r->id] = true;
            }
        }
        if (empty($admin_role_ids)) {
            foreach (array(1, 2, 3) as $rid) {
                $admin_role_ids[$rid] = true;
            }
        }

        foreach ($all_roles as $role) {
            $role_id = (int) $role->id;
            if ($role_id <= 0) {
                continue;
            }
            $keys = $staff_defect_keys;
            if (isset($admin_role_ids[$role_id])) {
                $keys = array_merge($keys, $admin_keys);
            }
            foreach ($keys as $module) {
                $exists = $CI->db
                    ->where('role_id', $role_id)
                    ->where('module', $module)
                    ->limit(1)
                    ->get('permissions')
                    ->row();
                if ($exists) {
                    continue;
                }
                $CI->db->insert('permissions', array(
                    'role_id'    => $role_id,
                    'module'     => $module,
                    'can_access' => 1,
                ));
            }
        }
    }
}

if (!function_exists('get_controller_module_access_map')) {
    /**
     * Single source of truth for route-level RBAC (AuthHook + controller constructors).
     * Access is granted when the role has ANY listed key.
     */
    function get_controller_module_access_map() {
        return [
            'users' => ['users', 'users_list', 'users_add', 'users_edit', 'users_delete', 'users_view', 'users_view_all'],
            'employees' => [
                'employees', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete',
                'employees_view', 'employees_view_all', 'employees_edit_all', 'employees_delete_all',
                'employees_documents', 'employees_delete_document', 'employees_import',
            ],
            'roles' => ['roles', 'permissions'],
            'permissions' => ['permissions'],
            'departments' => ['departments'],
            'designations' => ['designations'],
            'attendance' => [
                'attendance', 'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete',
                'attendance_bulk', 'attendance_view_all', 'attendance_export',
                'reports_attendance', 'reports_attendance_employee',
            ],
            'leave_requests' => [
                'leave_requests', 'leave_team', 'leave_approve', 'leave_calendar', 'leave_view_all',
                'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
            ],
            'leaves' => ['leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete', 'leave_requests'],
            'shifts' => ['shifts', 'shifts_view', 'shifts_manage'],
            'projects' => [
                'projects', 'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                'projects_view_all', 'projects_import', 'projects_matrix',
            ],
            'tasks' => [
                'tasks', 'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
                'tasks_manage', 'tasks_view_all', 'tasks_delete_all', 'tasks_import',
            ],
            'my_works' => [
                'my_works', 'my_works_list', 'my_works_add', 'my_works_edit', 'my_works_delete',
                'my_works_view_all', 'my_works_export',
            ],
            'spl' => array(
                'spl', 'spl_my_reward', 'spl_submit', 'spl_approve', 'spl_rules', 'spl_groups', 'spl_groups_manage',
                'rewards', 'rewards_submit', 'rewards_rules', 'rewards_admin', 'rewards_approve', 'rewards_leaderboard',
            ),
            'rewards' => array(
                'spl', 'spl_my_reward', 'spl_submit', 'spl_approve', 'spl_rules', 'spl_groups', 'spl_groups_manage',
                'rewards', 'rewards_submit', 'rewards_rules', 'rewards_admin', 'rewards_approve', 'rewards_leaderboard',
            ),
            'requirements' => [
                'requirements', 'requirements_list', 'requirements_add', 'requirements_edit',
                'requirements_delete', 'requirements_view', 'requirements_board', 'requirements_calendar',
                'requirements_export', 'requirements_delete_all',
            ],
            'timesheets' => ['timesheets', 'timesheets_list', 'timesheets_add', 'timesheets_edit', 'timesheets_delete'],
            'chats' => ['chats', 'chats_list', 'chats_add', 'chatsgrouping'],
            'calls' => ['calls', 'chats'],
            'meetings' => ['calls', 'chats'],
            'announcements' => [
                'announcements', 'announcements_list', 'announcements_add', 'announcements_edit',
                'announcements_delete', 'announcements_manage',
            ],
            'recruitment' => [
                'recruitment', 'recruitment_jobs', 'recruitment_candidates', 'recruitment_interviews',
                'recruitment_export', 'recruitment_add', 'recruitment_delete',
            ],
            'performance' => [
                'performance', 'performance_create', 'performance_view', 'performance_edit',
                'performance_delete', 'performance_self_assess', 'performance_export',
            ],
            'training_assessment' => [
                'training_assessment', 'training_assessment_manage', 'training_assessment_take',
                'training_screen_ta_dashboard', 'training_screen_ta_create', 'training_screen_ta_import',
                'training_screen_ta_question_import', 'training_screen_ta_report', 'training_screen_ta_submissions',
                'training_screen_ta_team_progress', 'training_screen_ta_my_tests',
            ],
            'training_assessment_take' => [
                'training_assessment', 'training_assessment_manage', 'training_assessment_take',
                'training_take_with_proctoring', 'training_take_without_proctoring',
                'training_screen_ta_my_tests',
            ],
            'training_lms' => [
                'training_lms', 'training_lms_manage',
                'training_screen_tl_hub', 'training_screen_tl_module', 'training_screen_tl_assignment',
            ],
            'training_lms_admin' => [
                'training_lms_manage',
                'training_screen_lms_admin', 'training_screen_lms_submissions', 'training_screen_lms_office_csv',
            ],
            'external_training' => [
                'external_training', 'external_training_watch',
                'external_training_list', 'external_training_add',
                'external_training_edit', 'external_training_delete',
            ],
            'training_import' => [
                'training_assessment', 'training_assessment_manage',
                'training_screen_ta_import', 'training_lms_manage',
                'training_screen_lms_office_csv',
            ],
            'coaching' => [
                'coaching', 'coaching_coaches', 'coaching_clients', 'coaching_sessions',
                'coaching_goals', 'coaching_leads', 'coaching_billing', 'coaching_reports',
                'coaching_whatsapp_crm', 'coaching_resources', 'coaching_admin',
            ],
            'coaching_admin' => ['coaching_admin', 'coaching'],
            'coaching_billing' => ['coaching_billing', 'coaching'],
            'coaching_clients' => ['coaching_clients', 'coaching'],
            'coaching_coaches' => ['coaching_coaches', 'coaching'],
            'coaching_goals' => ['coaching_goals', 'coaching'],
            'coaching_leads' => ['coaching_leads', 'coaching'],
            'coaching_payments' => ['coaching_billing', 'coaching', 'coaching_portal'],
            'coaching_reports' => ['coaching_reports', 'coaching'],
            'coaching_resources' => ['coaching_resources', 'coaching'],
            'coaching_sessions' => ['coaching_sessions', 'coaching'],
            'coaching_whatsapp_crm' => ['coaching_whatsapp_crm', 'coaching'],
            'clients' => [
                'clients', 'clients_list', 'clients_add', 'clients_edit', 'clients_delete',
                'clients_view', 'clients_export',
            ],
            'subscription_builder' => [
                'subscription_builder', 'subscription_builder_list',
            ],
            'elintom_proposals' => [
                'elintom_proposals', 'elintom_proposals_list',
            ],
            'eba_platform' => [
                'eba_platform', 'eba_platform_list',
            ],
            'payroll' => ['payroll', 'payroll_view', 'payroll_manage'],
            'expenses' => [
                'expenses', 'expenses_add', 'expenses_edit', 'expenses_delete', 'expenses_approve',
                'expenses_reimburse', 'expenses_reports', 'expenses_categories', 'expenses_export',
            ],
            'assets' => [
                'assets', 'assets_mgmt', 'assets_manage', 'assets_list', 'assets_add', 'assets_edit',
                'assets_delete', 'assets_assign',
            ],
            'reports' => [
                'reports', 'reports_overview', 'reports_requirements', 'reports_tasks_assignment',
                'reports_projects_status', 'reports_defects', 'reports_leaves', 'reports_attendance',
                'reports_attendance_employee', 'reports_daily_activity', 'daily_activity_report', 'analytics',
                'reports_payroll', 'reports_expenses', 'reports_performance',
            ],
            'reports_attendance' => [
                'reports', 'reports_overview', 'reports_attendance', 'reports_attendance_employee',
            ],
            'reports_projects' => [
                'reports', 'reports_overview', 'reports_requirements', 'reports_tasks_assignment',
                'reports_projects_status', 'reports_defects', 'reports_daily_activity', 'daily_activity_report',
            ],
            'reports_hr' => [
                'reports', 'reports_overview', 'reports_leaves', 'reports_payroll',
                'reports_expenses', 'reports_performance',
            ],
            'analytics' => ['analytics', 'reports'],
            'ai_chat' => ['ai', 'ai_chat', 'ai_widget'],
            'daily_activity' => [
                'daily_activity', 'daily_activity_add', 'daily_activity_list', 'daily_activity_edit',
                'daily_activity_export', 'daily_activity_report', 'daily_activity_delete',
                'daily_activity_view_all', 'daily_activity_edit_all', 'daily_activity_delete_all', 'activity',
            ],
            'settings' => [
                'settings', 'holidays', 'holidays_add', 'holidays_edit', 'holidays_delete',
                'leave_types', 'leave_types_add', 'leave_types_edit', 'leave_types_delete', 'types', 'admin',
            ],
            'email_settings' => ['email_settings', 'settings', 'admin'],
            'system_settings' => ['system_settings', 'settings', 'admin'],
            'mail' => ['mail', 'settings', 'admin'],
            'sendgrid' => ['sendgrid', 'email_settings', 'settings', 'admin'],
            'whatsapp' => ['whatsapp'],
            'reminders' => ['reminders', 'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete'],
            'notifications' => ['notifications'],
            'approvals' => ['approvals'],
            'activity' => ['activity'],
            'statuses' => ['statuses'],
            'types' => ['types'],
            'db' => ['db', 'db_admin'],
            'api_integrations' => ['api_integrations', 'settings', 'admin'],
            'superadmin' => ['superadmin'],
            'guide' => ['guide'],
            'lead_mapping' => ['lead_mapping'],
            'releases' => [
                'releases', 'releases_list', 'releases_add', 'releases_edit', 'releases_delete',
                'releases_view', 'releases_send_notes', 'releases_export',
            ],
            'defects' => [
                'defects', 'defects_list', 'defects_add', 'defects_edit',
                'defects_delete', 'defects_view', 'defects_export',
            ],
            'meals' => [
                'meals_order', 'meals_calendar', 'meals_provider',
                'meals_settings', 'meals_history', 'meals_all_orders',
            ],
            'dashboard' => ['dashboard'],
        ];
    }
}

if (!function_exists('get_controller_module_access_keys')) {
    function get_controller_module_access_keys($controller) {
        $controller = strtolower(trim((string) $controller));
        $map = get_controller_module_access_map();
        return isset($map[$controller]) ? $map[$controller] : [$controller];
    }
}

if (!function_exists('require_controller_access')) {
    function require_controller_access($controller, $redirect_to_dashboard = true) {
        return require_module_access(
            get_controller_module_access_keys($controller),
            $redirect_to_dashboard
        );
    }
}

if (!function_exists('can_access_module_with_parent')) {
    /** True when role has the specific key or the module's full-access parent key. */
    function can_access_module_with_parent($module_key, $parent_key) {
        return has_module_access($module_key) || has_module_access($parent_key);
    }
}

if (!function_exists('add_permission')) {
    /**
     * Add a permission to the permissions table
     * 
     * @param string $module Module name
     * @param int|array $role_ids Role ID(s) to grant access to
     * @param bool $can_access Whether access is granted
     * @return bool Success status
     */
    function add_permission($module, $role_ids, $can_access = true) {
        $CI =& get_instance();
        
        if (!isset($CI->db) || !$CI->db->table_exists('permissions')) {
            return false;
        }
        
        if (!is_array($role_ids)) {
            $role_ids = array($role_ids);
        }
        
        foreach ($role_ids as $role_id) {
            $CI->db->replace('permissions', array(
                'module' => strtolower(trim($module)),
                'role_id' => (int)$role_id,
                'can_access' => $can_access ? 1 : 0
            ));
        }
        
        return true;
    }
}

if (!function_exists('remove_permission')) {
    /**
     * Remove a permission from the permissions table
     * 
     * @param string $module Module name
     * @param int|array $role_ids Role ID(s) to remove access from
     * @return bool Success status
     */
    function remove_permission($module, $role_ids) {
        $CI =& get_instance();
        
        if (!isset($CI->db) || !$CI->db->table_exists('permissions')) {
            return false;
        }
        
        if (!is_array($role_ids)) {
            $role_ids = array($role_ids);
        }
        
        foreach ($role_ids as $role_id) {
            $CI->db->where('module', strtolower(trim($module)));
            $CI->db->where('role_id', (int)$role_id);
            $CI->db->delete('permissions');
        }
        
        return true;
    }
}

if (!function_exists('is_admin_group')) {
    function is_admin_group() {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }

        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('roles') || !schema_table_has_column($CI->db, 'roles', 'group_type')) {
            return in_array($role_id, [1, 2, 3], true);
        }

        // Use a standalone query so we don't interfere with any in-progress query builder chains
        $row = $CI->db->query("SELECT group_type FROM roles WHERE id = ? LIMIT 1", [$role_id])->row();
        $group = $row ? strtolower(trim((string)$row->group_type)) : '';
        return $group === 'admin';
    }
}

if (!function_exists('is_user_group')) {
    function is_user_group() {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }

        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('roles') || !schema_table_has_column($CI->db, 'roles', 'group_type')) {
            return $role_id === 4;
        }

        // Use a standalone query so we don't interfere with any in-progress query builder chains
        $row = $CI->db->query("SELECT group_type FROM roles WHERE id = ? LIMIT 1", [$role_id])->row();
        $group = $row ? strtolower(trim((string)$row->group_type)) : '';
        return $group === 'user';
    }
}

if (!function_exists('has_role')) {
    /**
     * True when the session role matches a role slug (admin, manager, lead, staff).
     * Uses ROLE_* constants when defined, then falls back to roles.name.
     *
     * @param string   $role_slug e.g. manager, lead
     * @param int|null $role_id   Optional role id (defaults to session role_id)
     */
    function has_role($role_slug, $role_id = null)
    {
        $CI =& get_instance();
        if (!$CI || !$CI->session) {
            return false;
        }
        $role_id = $role_id === null ? (int) $CI->session->userdata('role_id') : (int) $role_id;
        if ($role_id <= 0) {
            return false;
        }

        $slug = strtolower(trim((string) $role_slug));
        if ($slug === '') {
            return false;
        }

        $constant_map = array(
            'admin'   => 'ROLE_ADMIN',
            'manager' => 'ROLE_MANAGER',
            'lead'    => 'ROLE_LEAD',
            'staff'   => 'ROLE_STAFF',
            'user'    => 'ROLE_USER',
        );
        if (isset($constant_map[$slug]) && defined($constant_map[$slug])) {
            if ($role_id === (int) constant($constant_map[$slug])) {
                return true;
            }
        }

        if (!function_exists('get_role_name_by_id')) {
            $CI->load->helper('hierarchy_filter');
        }
        return get_role_name_by_id($role_id) === $slug;
    }
}
