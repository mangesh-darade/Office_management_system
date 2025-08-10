<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthHook {
    public function check()
    {
        $CI =& get_instance();
        // Determine current URI safely
        $uri = '';
        if (isset($CI->uri) && method_exists($CI->uri, 'uri_string')) {
            $uri = $CI->uri->uri_string();
        } else if (!empty($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri = ltrim($path, '/');
        }

        // Publicly allowed endpoints
        $public = [
            '',
            'welcome',
            // login & register allowed with or without controller prefix
            'auth/login', 'login',
            'auth/register', 'register',
            'install/schema'
        ];

        // Skip for CLI
        if (is_cli()) return;

        // Allow assets
        if (strpos($uri, 'assets/') === 0) return;

        // Normalize index.php prefix
        $uri = preg_replace('#^index\.php/#','', $uri);

        if (in_array($uri, $public, true)) return;

        $user_id = $CI->session->userdata('user_id');
        $role_id = (int)$CI->session->userdata('role_id');

        if (!$user_id) {
            redirect('auth/login');
            exit;
        }

        // Route-level RBAC: map controller to allowed roles (defaults)
        $routes_roles = [
            'dashboard' => [1,2,3,4], // all roles
            'employees' => [1,2],     // admin, hr
            'projects'  => [1,2,3],   // admin, hr, lead
            'tasks'     => [1,2,3,4], // all roles
            'attendance'=> [1,2,3,4], // all roles
            'leaves'    => [1,2,3,4], // all roles
            'notifications' => [1,2,3,4],
            'reports'   => [1,2,3],   // admin, hr, lead
            'permissions' => [2,3],   // HR/Manager (2) and Lead (3)
            'chats'     => [1,2,3,4], // all roles (default)
            'calls'     => [1,2,3,4], // all roles (default)
        ];

        // If a permissions table exists, override defaults with DB-driven permissions
        if (!isset($CI->db)) { $CI->load->database(); }
        if ($CI->db && $CI->db->table_exists('permissions')) {
            $perms = $CI->db->get('permissions')->result();
            // Expect columns: role_id (int), module (varchar), can_access (tinyint)
            $db_map = [];
            foreach ($perms as $p) {
                if (!isset($db_map[$p->module])) { $db_map[$p->module] = []; }
                if ((int)$p->can_access === 1) { $db_map[$p->module][] = (int)$p->role_id; }
            }
            foreach ($db_map as $module => $allowed_roles) {
                if (!empty($allowed_roles)) { $routes_roles[strtolower($module)] = array_values(array_unique($allowed_roles)); }
            }
        }

        // Extract controller from router when available
        $controller = '';
        if (isset($CI->router) && property_exists($CI->router, 'class')) {
            $controller = strtolower($CI->router->class ?: '');
        } else if ($uri !== '') {
            $controller = strtolower(explode('/', $uri)[0]);
        }
        if (isset($routes_roles[$controller])) {
            if (!in_array($role_id, $routes_roles[$controller], true)) {
                show_error('You do not have permission to access this page.', 403);
            }
        }
    }
}
