<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('has_module_access')) {
    function has_module_access($module) {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }
        $controller = strtolower(trim((string)$module));

        // Build mapping purely from permissions table (DB-driven)
        $routes_roles = [];
        if (isset($CI->db) && $CI->db && $CI->db->table_exists('permissions')) {
            $perms = $CI->db->get('permissions')->result();
            foreach ($perms as $p) {
                $mod = strtolower(trim((string)$p->module));
                if ($mod === '') { continue; }
                if (!isset($routes_roles[$mod])) { $routes_roles[$mod] = []; }
                if ((int)$p->can_access === 1) { $routes_roles[$mod][] = (int)$p->role_id; }
            }
            foreach ($routes_roles as $mod => $roles) {
                $routes_roles[$mod] = array_values(array_unique($roles));
            }
        }

        if (empty($routes_roles) || !isset($routes_roles[$controller])) { return false; }
        return in_array($role_id, $routes_roles[$controller], true);
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
