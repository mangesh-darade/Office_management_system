<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthHook {
    public function check()
    {
        $CI =& get_instance();
        // Ensure local timezone is used for all date()/time() calls
        // Change 'Asia/Kolkata' if your organization uses a different default
        try { @date_default_timezone_set('Asia/Kolkata'); } catch (Exception $e) {}
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
            // email verification & code sending must be usable before login
            'auth/send-verify-code',
            'auth/verify',
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

        // Route-level RBAC: build controller -> allowed roles map from permissions table (DB-driven)
        $routes_roles = [];

        if (!isset($CI->db)) { $CI->load->database(); }
        if ($CI->db && $CI->db->table_exists('permissions')) {
            $perms = $CI->db->get('permissions')->result();
            // Expect columns: role_id (int), module (varchar), can_access (tinyint)
            foreach ($perms as $p) {
                $module = strtolower(trim((string)$p->module));
                if ($module === '') { continue; }
                if (!isset($routes_roles[$module])) { $routes_roles[$module] = []; }
                if ((int)$p->can_access === 1) { $routes_roles[$module][] = (int)$p->role_id; }
            }
            // Normalize unique role ids per module
            foreach ($routes_roles as $m => $roles) {
                $routes_roles[$m] = array_values(array_unique($roles));
            }
        }
        // If no permissions configured, do not block any route here
        if (empty($routes_roles)) { return; }

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
