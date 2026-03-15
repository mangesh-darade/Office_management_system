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
        
        // Check module access
        if (!has_module_access($module)) {
            if ($redirect_to_dashboard) {
                // Set flash message to inform user
                $CI->session->set_flashdata('access_denied', 'You do not have permission to access the ' . ucfirst($module) . ' module.');
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

if (!function_exists('get_accessible_modules')) {
    function get_accessible_modules() {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return []; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return []; }

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
