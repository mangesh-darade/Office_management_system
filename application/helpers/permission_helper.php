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

