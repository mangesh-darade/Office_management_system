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
            'auth/verify-code',
            'auth/verify',
            // password reset (phone + OTP) must be accessible before login
            'auth/forgot_password',
            'auth/reset_password',
            // 2FA verification
            'auth/verify-2fa',
            'auth/verify_2fa',
            'install/schema'
        ];

        // Skip for CLI
        if (is_cli()) return;

        // Allow assets
        if (strpos($uri, 'assets/') === 0) return;

        // Normalize index.php prefix and trailing slashes (Safari/iOS may append '/')
        $uri = preg_replace('#^index\.php/#', '', $uri);
        $uri = trim($uri, '/');

        if (in_array($uri, $public, true)) return;

        // Training assessment: candidates may use a secret link without an account (token in URL / POST).
        if (preg_match('#^training_assessment/(take_assessment|submit_assessment|ajax_save_answer|ajax_load_question|ajax_run_code|ajax_timer_sync|candidate_profile|result_token|retake_assessment)(/|$)#', $uri)) {
            return;
        }
        if (preg_match('#^training_assessment_take/#', $uri)) {
            return;
        }
        // Pretty routes — take, submit, AJAX, candidate, retake, result-token while logged out
        if (preg_match('#^training-assessment/(take|result-token|submit-assessment|ajax-load-question|ajax-save-answer|ajax-run-code|ajax-timer-sync|candidate-profile|retake-assessment)(/|$)#', $uri)) {
            return;
        }

        // Coaching: public webhooks + workshop registration (no login)
        if (preg_match('#^coaching-webhooks/(razorpay|whatsapp-inbound)(/|$)#', $uri)) {
            return;
        }
        if (preg_match('#^coaching-leads/workshop-register(/|$)#', $uri)) {
            return;
        }

        $user_id = $CI->session->userdata('user_id');
        $role_id = (int)$CI->session->userdata('role_id');

        if (!$user_id) {
            redirect('auth/login');
            exit;
        }
        
        // Load settings and audit model
        if (!isset($CI->settings)) {
            $CI->load->model('Setting_model', 'settings');
        }
        if (!isset($CI->audit)) {
            $CI->load->model('Security_audit_model', 'audit');
        }
        
        // Check Maintenance Mode (must check before other validations)
        $maintenance_mode = $CI->settings->get_setting('system_maintenance_mode', 'no');
        
        // Extract controller name from URI or router
        $controller = '';
        if (isset($CI->router) && property_exists($CI->router, 'class')) {
            $controller = strtolower($CI->router->class ?: '');
        }
        
        // Fallback: extract from URI if router class not available or empty
        if (empty($controller) && $uri !== '') {
            $uri_parts = explode('/', trim($uri, '/'));
            $controller = strtolower(isset($uri_parts[0]) ? $uri_parts[0] : '');
        }
        
        // Additional check: if URI contains 'dashboard' specifically
        if (empty($controller) && (strpos($uri, 'dashboard') !== false || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false))) {
            $controller = 'dashboard';
        }
        
        // Check module-specific maintenance first
        $module_maintenance_enabled = false;
        $module_maintenance_message = '';
        
        if (!empty($controller)) {
            $module_maintenance_key = 'system_maintenance_module_' . $controller;
            $module_maintenance_msg_key = 'system_maintenance_module_' . $controller . '_message';
            $module_maintenance_enabled = $CI->settings->get_setting($module_maintenance_key, 'no') === 'yes';
            if ($module_maintenance_enabled) {
                $module_maintenance_message = $CI->settings->get_setting($module_maintenance_msg_key, '');
            }
        }
        
        // Check global maintenance mode or module-specific maintenance
        $is_maintenance = ($maintenance_mode === 'yes') || $module_maintenance_enabled;
        
        if ($is_maintenance) {
            // Only admin role (ROLE_ADMIN = 1) can access during maintenance
            // All other roles (Manager, Lead, Staff) will see maintenance screen
            // Ensure constants are loaded
            if (!defined('ROLE_ADMIN')) {
                define('ROLE_ADMIN', 1);
            }
            $is_admin = ($role_id === ROLE_ADMIN); // Strict check: only ROLE_ADMIN (role_id = 1) is allowed
            if (!$is_admin) {
                // Use module-specific message if available, otherwise use global message
                if ($module_maintenance_enabled && !empty($module_maintenance_message)) {
                    $maintenance_message = $module_maintenance_message;
                } else {
                    $maintenance_message = $CI->settings->get_setting('system_maintenance_message', 'The system is currently under maintenance. Please try again later.');
                }
                
                // Log maintenance access attempt
                if ($CI->settings->get_setting('security_audit_login', 'no') === 'yes') {
                    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                    $module_info = $module_maintenance_enabled ? " (Module: {$controller})" : '';
                    $CI->audit->log('maintenance_mode_blocked', $user_id, "Non-admin user blocked during maintenance{$module_info}", $ip);
                }
                // Get company name for maintenance page
                $company_name = $CI->settings->get_setting('company_name', 'Office Management System');
                
                // Get company email and phone for contact info
                $company_email = $CI->settings->get_setting('company_email', 'admin@example.com');
                $company_phone = $CI->settings->get_setting('company_phone', '+1234567890');
                
                // Get base URL for navigation links
                $base_url = '';
                if (isset($CI->config)) {
                    $base_url = rtrim($CI->config->item('base_url'), '/');
                } else {
                    // Fallback: construct from SERVER variables
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $script = dirname($_SERVER['SCRIPT_NAME']);
                    $base_url = $protocol . '://' . $host . $script;
                }
                
                // DO NOT destroy session - we need it for header/sidebar/footer to work properly
                // The session will remain active so the layout components can access user data
                
                // Set proper headers before any output (if not already sent)
                // Do NOT clear output buffers - CodeIgniter needs them for view rendering
                if (!headers_sent()) {
                    @header('Content-Type: text/html; charset=utf-8');
                    @header('HTTP/1.1 503 Service Unavailable', true, 503);
                    http_response_code(503);
                }
                
                // Load maintenance view with normal layout (header, sidebar, footer)
                try {
                    // Pass variables to view through CodeIgniter's view loader
                    $view_data = [
                        'message' => $maintenance_message,
                        'company_name' => $company_name,
                        'company_email' => $company_email,
                        'company_phone' => $company_phone,
                        'base_url' => $base_url,
                        'with_sidebar' => true,
                        'hide_navbar' => false
                    ];
                    
                    // Load the maintenance view and capture output
                    // CodeIgniter's load->view() buffers output, so we need to capture and echo it
                    ob_start();
                    $CI->load->view('errors/maintenance_inline', $view_data);
                    $output = ob_get_contents();
                    ob_end_clean();
                    
                    // Output the captured content immediately
                    echo $output;
                    
                    // Stop execution after view is loaded
                    exit(0);
                } catch (Exception $e) {
                    // Log the error for debugging - include file and line
                    if (function_exists('log_message')) {
                        $error_msg = 'Maintenance view failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
                        log_message('error', $error_msg);
                        // Also output to error log directly if possible
                        error_log($error_msg);
                    }
                    
                    // If view fails, try fallback with header/footer
                    try {
                        $CI->load->view('partials/header', ['title' => 'System Under Maintenance']);
                        echo '<div class="container mt-5"><div class="alert alert-warning text-center"><h4><i class="bi bi-gear-fill me-2"></i>System Under Maintenance</h4><p class="mb-0">' . htmlspecialchars((string)($maintenance_message ?? ''), ENT_QUOTES, 'UTF-8') . '</p></div></div>';
                        $CI->load->view('partials/footer');
                        exit(0);
                    } catch (Exception $e2) {
                        // Ultimate fallback - simple HTML
                        if (!headers_sent()) {
                            header('Content-Type: text/html; charset=utf-8');
                            http_response_code(503);
                        }
                        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Maintenance</title><style>body{margin:0;padding:50px;font-family:Arial;text-align:center;background:#f0f0f0}h1{color:#667eea}p{font-size:1.2rem}</style></head><body><h1>🔧 System Under Maintenance</h1><p>' . htmlspecialchars((string)($maintenance_message ?? ''), ENT_QUOTES, 'UTF-8') . '</p></body></html>';
                        exit(0);
                    }
                }
            }
        }
        
        // Check IP whitelist for authenticated users (skip Super Admin + self punch — mobile IPs / iCloud Relay)
        $ip_whitelist_enabled = $CI->settings->get_setting('security_enable_ip_whitelist', null);
        if ($ip_whitelist_enabled === null || $ip_whitelist_enabled === '')
        {
            $ip_whitelist_enabled = $CI->settings->get_setting('security_ip_whitelist_enabled', 'no');
        }
        $method = '';
        if (isset($CI->router) && property_exists($CI->router, 'method')) {
            $method = strtolower((string) $CI->router->method);
        }
        $skip_ip_whitelist = ($role_id === 1)
            || ($controller === 'attendance' && $method === 'create');
        if ($ip_whitelist_enabled === 'yes' && !$skip_ip_whitelist) {
            if (!function_exists('auth_client_ip')) {
                $CI->load->helper('auth_security');
            }
            $ip = auth_client_ip();
            $whitelist = $CI->settings->get_setting('security_ip_whitelist', '');
            
            if (!empty($whitelist)) {
                $allowed_ips = array_map('trim', explode(',', $whitelist));
                $allowed = false;
                
                // Check exact match
                if (in_array($ip, $allowed_ips)) {
                    $allowed = true;
                } else {
                    // Check CIDR notation
                    foreach ($allowed_ips as $allowed_ip) {
                        if (strpos($allowed_ip, '/') !== false) {
                            list($subnet, $mask) = explode('/', $allowed_ip);
                            if (self::ip_in_range($ip, $subnet, $mask)) {
                                $allowed = true;
                                break;
                            }
                        }
                    }
                }
                
                if (!$allowed) {
                    // Log access denied
                    if ($CI->settings->get_setting('security_log_login_attempts', 'no') === 'yes') {
                        $CI->audit->log('ip_whitelist_denied', $user_id, "IP {$ip} not in whitelist", $ip);
                    }
                    show_error('Access denied. Your IP address is not authorized.', 403);
                    exit;
                }
            }
        }
        
        // Check session timeout
        $session_timeout_enabled = $CI->settings->get_setting('security_session_timeout_enabled', 'no');
        if ($session_timeout_enabled === 'yes') {
            $timeout_minutes = (int)$CI->settings->get_setting('security_session_timeout', 30);
            $last_activity = $CI->session->userdata('last_activity');
            
            if ($last_activity) {
                $timeout_seconds = $timeout_minutes * 60;
                if ((time() - $last_activity) > $timeout_seconds) {
                    // Session expired
                    if ($CI->settings->get_setting('security_audit_login', 'no') === 'yes') {
                        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                        $CI->audit->log('session_timeout', $user_id, "Session expired after {$timeout_minutes} minutes", $ip);
                    }
                    $CI->session->sess_destroy();
                    redirect('auth/login?timeout=1');
                    exit;
                }
            }
            
            // Update last activity
            $CI->session->set_userdata('last_activity', time());
        }
        
        // Check single session enforcement
        $single_session_enabled = $CI->settings->get_setting('security_single_session', 'no');
        if ($single_session_enabled === 'yes') {
            $stored_session_id = $CI->session->userdata('session_id');
            $current_session_id = session_id();
            
            // If session IDs don't match, destroy session
            if ($stored_session_id && $stored_session_id !== $current_session_id) {
                if ($CI->settings->get_setting('security_audit_login', 'no') === 'yes') {
                    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                    $CI->audit->log('session_conflict', $user_id, "Another session detected", $ip);
                }
                $CI->session->sess_destroy();
                redirect('auth/login?session_conflict=1');
                exit;
            }
        }

        // Admin (role_id 1) always has full access - skip route-level RBAC
        if ($role_id === 1) { return; }

        // Route-level RBAC: check if the user's role has access to the controller
        if (!isset($CI->db)) { $CI->load->database(); }
        if (!$CI->db || !$CI->db->table_exists('permissions')) { return; }

        $perms = $CI->db->get('permissions')->result();
        if (empty($perms)) { return; }

        $controller = '';
        if (isset($CI->router) && property_exists($CI->router, 'class')) {
            $controller = strtolower($CI->router->class ?: '');
        } else if ($uri !== '') {
            $controller = strtolower(explode('/', $uri)[0]);
        }
        if (empty($controller)) { return; }
        $method = '';
        if (isset($CI->router) && property_exists($CI->router, 'method')) {
            $method = strtolower((string)$CI->router->method);
        }

        // Keep controller/method fallback as an additional guard.
        if ($controller === 'attendance' && $method === 'create') { return; }
        // If URI clearly targets self-attendance, do not apply route-level RBAC (delimiter # — escape # inside pattern).
        $uri_lc = strtolower((string) $uri);
        $uri_lc = trim(preg_replace('#/+#', '/', $uri_lc), '/');
        if ($uri_lc === 'attendance/create' || preg_match('~(?:^|/)attendance/create(?:$|[/?#])~', $uri_lc)) {
            return;
        }

        // Controllers that should NEVER be blocked by route-level RBAC
        // (they do their own fine-grained checks, or are always accessible to logged-in users)
        $always_allowed_controllers = [
            'dashboard', 'profile', 'auth', 'errors', 'welcome',
            'cron', 'install', 'migrate', 'short_url', 'test_company',
            'coaching_portal', 'coaching_webhooks', 'push',
        ];
        if (in_array($controller, $always_allowed_controllers, true)) { return; }

        // Map controller names to permission module keys (shared with controller constructors).
        if (!function_exists('get_controller_module_access_keys')) {
            $CI->load->helper('permission');
        }

        $keys_to_check = get_controller_module_access_keys($controller);

        // Build a lookup: module => [role_ids_with_access]
        $perm_map = [];
        foreach ($perms as $p) {
            $mod = strtolower(trim((string)$p->module));
            if ((int)$p->can_access === 1) {
                $perm_map[$mod][] = (int)$p->role_id;
            }
        }

        // Check if ANY of the mapped keys has a rule defined in the DB
        $has_any_rule = false;
        $has_access   = false;
        foreach ($keys_to_check as $key) {
            if (array_key_exists($key, $perm_map)) {
                $has_any_rule = true;
                if (in_array($role_id, $perm_map[$key], true)) {
                    $has_access = true;
                    break;
                }
            }
        }

        // Only block if the controller has permission rules but this role has none of them
        if ($has_any_rule && !$has_access) {
            $CI->session->set_flashdata('access_denied', 'You do not have permission to access this page.');
            redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private static function ip_in_range($ip, $subnet, $mask) {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        
        if ($ip_long === false || $subnet_long === false) {
            return false;
        }
        
        $mask_long = -1 << (32 - (int)$mask);
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
}
