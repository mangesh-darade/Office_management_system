<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('has_module_access')) {
    function has_module_access($module) {
        $CI =& get_instance();
        if (!$CI || !$CI->session) { return false; }
        $role_id = (int)$CI->session->userdata('role_id');
        if (!$role_id) { return false; }
        if ($role_id === 1) { return true; }
        $controller = strtolower(trim((string)$module));

        // Base mapping as in AuthHook
        $routes_roles = [
            'dashboard' => [1,2,3,4],
            'employees' => [1,2,3,4], // allow; controller enforces ownership
            'projects'  => [1,2,3],
            'tasks'     => [1,2,3,4],
            'attendance'=> [1,2,3,4],
            'leaves'    => [1,2,3,4],
            'notifications' => [1,2,3,4],
            'reports'   => [1,2,3],
            'permissions' => [1],
            'db' => [1],
            'users' => [1],
            'mail'  => [1,2,3,4],
            // Client requirements suite
            'clients' => [1,2,3],
            'requirements' => [1,2,3],
            'client_feedback' => [1,2,3,4],
            'reminders' => [1,2,3,4],
            // Chat-related defaults (can be overridden by DB table)
            'chats' => [1,2,3,4],
            'chats.grouping' => [1,2,3,4],
            'calls' => [1,2,3,4],
            // Legacy key for backward compatibility
            'chat' => [1,2,3,4],
            // New modules
            'departments' => [1,2],
            'designations' => [1,2],
            'timesheets' => [1,2,3,4],
            'announcements' => [1,2,3,4],
            'settings' => [1],
            'activity' => [1,2],
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

