<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('has_module_access')) {
    function has_module_access($module) {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }
        $controller = strtolower(trim((string)$module));

        // Base mapping as in AuthHook
        $routes_roles = [
            'dashboard' => [1,2,3,4],
            'employees' => [1,2],
            'projects'  => [1,2,3],
            'tasks'     => [1,2,3,4],
            'attendance'=> [1,2,3,4],
            'leaves'    => [1,2,3,4],
            'notifications' => [1,2,3,4],
            'reports'   => [1,2,3],
            'permissions' => [2,3],
            // Chat-related defaults (can be overridden by DB table)
            'chats' => [1,2,3,4],
            'chats.grouping' => [1,2,3,4],
            'calls' => [1,2,3,4],
            // Legacy key for backward compatibility
            'chat' => [1,2,3,4],
        ];

        // Override with DB permissions if available
        if (isset($CI->db) && $CI->db && $CI->db->table_exists('permissions')) {
            $perms = $CI->db->get('permissions')->result();
            $db_map = [];
            foreach ($perms as $p) {
                $mod = strtolower($p->module);
                if (!isset($db_map[$mod])) { $db_map[$mod] = []; }
                if ((int)$p->can_access === 1) { $db_map[$mod][] = (int)$p->role_id; }
            }
            foreach ($db_map as $mod => $allowed_roles) {
                if (!empty($allowed_roles)) {
                    $routes_roles[$mod] = array_values(array_unique($allowed_roles));
                }
            }
        }

        if (!isset($routes_roles[$controller])) { return false; }
        return in_array($role_id, $routes_roles[$controller], true);
    }
}
