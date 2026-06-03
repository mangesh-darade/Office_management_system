<?php defined('BASEPATH') OR exit('No direct script access allowed');

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
                exit;
            }
            return false;
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
            ],
            'projects' => [
                'projects', 'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
            ],
            'tasks' => [
                'tasks', 'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
            ],
            'attendance' => [
                'attendance', 'attendance_list', 'attendance_add', 'attendance_edit',
                'attendance_delete', 'attendance_view_all', 'attendance_bulk',
            ],
            'leaves' => [
                'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
                'leave_requests', 'leave_team', 'leave_calendar', 'leave_approve',
            ],
            'reports' => [
                'reports', 'analytics', 'reports_overview', 'reports_requirements',
                'reports_tasks_assignment', 'reports_projects_status', 'reports_leaves',
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
        if (!isset($groups[$parent])) {
            return false;
        }
        $aliases = array_values(array_diff($groups[$parent], [$parent]));
        return can_access_any_module($aliases);
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
                if ($CI->db->field_exists('group_type', 'roles')) {
                    $row['group_type'] = 'user';
                }
                if ($CI->db->field_exists('is_active', 'roles')) {
                    $row['is_active'] = 1;
                }
                if ($CI->db->field_exists('sort_order', 'roles')) {
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

        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('roles') || !$CI->db->field_exists('group_type', 'roles')) {
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

        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('roles') || !$CI->db->field_exists('group_type', 'roles')) {
            return $role_id === 4;
        }

        // Use a standalone query so we don't interfere with any in-progress query builder chains
        $row = $CI->db->query("SELECT group_type FROM roles WHERE id = ? LIMIT 1", [$role_id])->row();
        $group = $row ? strtolower(trim((string)$row->group_type)) : '';
        return $group === 'user';
    }
}
