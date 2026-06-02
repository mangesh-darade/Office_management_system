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

        // Normalize index.php prefix
        $uri = preg_replace('#^index\.php/#','', $uri);

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
                        echo '<div class="container mt-5"><div class="alert alert-warning text-center"><h4><i class="bi bi-gear-fill me-2"></i>System Under Maintenance</h4><p class="mb-0">' . htmlspecialchars($maintenance_message) . '</p></div></div>';
                        $CI->load->view('partials/footer');
                        exit(0);
                    } catch (Exception $e2) {
                        // Ultimate fallback - simple HTML
                        if (!headers_sent()) {
                            header('Content-Type: text/html; charset=utf-8');
                            http_response_code(503);
                        }
                        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Maintenance</title><style>body{margin:0;padding:50px;font-family:Arial;text-align:center;background:#f0f0f0}h1{color:#667eea}p{font-size:1.2rem}</style></head><body><h1>🔧 System Under Maintenance</h1><p>' . htmlspecialchars($maintenance_message) . '</p></body></html>';
                        exit(0);
                    }
                }
            }
        }
        
        // Check IP whitelist for authenticated users
        $ip_whitelist_enabled = $CI->settings->get_setting('security_ip_whitelist_enabled', 'no');
        if ($ip_whitelist_enabled === 'yes') {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
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
        ];
        if (in_array($controller, $always_allowed_controllers, true)) { return; }

        // Map controller names to their permission module keys.
        // A controller may map to multiple keys — access is granted if ANY key is enabled for this role.
        $controller_module_map = [
            // controller_name  => [module_keys_to_check]
            'users'             => ['users', 'users_list', 'users_add', 'users_edit', 'users_delete'],
            'employees'         => ['employees', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete'],
            'roles'             => ['roles', 'permissions'],
            'permissions'       => ['permissions'],
            'departments'       => ['departments'],
            'designations'      => ['designations'],
            'attendance'        => ['attendance', 'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete', 'attendance_bulk'],
            'leave_requests'    => ['leave_requests', 'leave_team', 'leave_approve', 'leave_calendar'],
            'leaves'            => ['leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete', 'leave_requests'],
            'shifts'            => ['shifts', 'shifts_view', 'shifts_manage'],
            'projects'          => ['projects', 'projects_list', 'projects_add', 'projects_edit', 'projects_delete'],
            'tasks'             => ['tasks', 'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete'],
            'my_works'          => ['my_works', 'my_works_list', 'my_works_add', 'my_works_edit', 'my_works_delete', 'my_works_view_all', 'my_works_export'],
            'requirements'      => ['requirements', 'requirements_list', 'requirements_add', 'requirements_edit', 'requirements_delete'],
            'timesheets'        => ['timesheets', 'timesheets_list', 'timesheets_add', 'timesheets_edit', 'timesheets_delete'],
            'chats'             => ['chats', 'chats_list', 'chats_add', 'chatsgrouping'],
            'calls'             => ['calls', 'chats'],
            'announcements'     => ['announcements', 'announcements_list', 'announcements_add', 'announcements_edit', 'announcements_delete'],
            'recruitment'       => ['recruitment', 'recruitment_jobs', 'recruitment_candidates', 'recruitment_interviews'],
            'performance'       => ['performance', 'performance_create', 'performance_view', 'performance_edit', 'performance_delete'],
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
            'clients'           => ['clients', 'clients_list', 'clients_add', 'clients_edit', 'clients_delete'],
            'payroll'           => ['payroll', 'payroll_view', 'payroll_manage'],
            'expenses'          => ['expenses', 'expenses_add', 'expenses_edit', 'expenses_delete', 'expenses_approve', 'expenses_reimburse', 'expenses_reports', 'expenses_categories'],
            'assets'            => ['assets', 'assets_mgmt', 'assets_list', 'assets_add', 'assets_edit', 'assets_delete'],
            'reports'           => ['reports', 'reports_overview', 'reports_requirements', 'reports_tasks_assignment', 'reports_projects_status', 'reports_leaves', 'reports_attendance', 'reports_attendance_employee', 'reports_daily_activity'],
            'analytics'         => ['analytics'],
            'ai_chat'           => ['ai', 'ai_chat', 'ai_widget'],
            'daily_activity'    => ['daily_activity', 'daily_activity_add', 'daily_activity_list', 'daily_activity_report', 'daily_activity_delete'],
            'settings'          => ['settings', 'holidays', 'leave_types', 'admin'],
            'email_settings'    => ['email_settings', 'settings', 'admin'],
            'system_settings'   => ['system_settings', 'settings', 'admin'],
            'mail'              => ['mail', 'settings', 'admin'],
            'sendgrid'          => ['sendgrid', 'email_settings', 'settings', 'admin'],
            'whatsapp'          => ['whatsapp'],
            'reminders'         => ['reminders', 'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete'],
            'notifications'     => ['notifications'],
            'approvals'         => ['approvals'],
            'activity'          => ['activity'],
            'statuses'          => ['statuses'],
            'db'                => ['db'],
            'api_integrations'  => ['api_integrations', 'settings', 'admin'],
            'superadmin'        => ['superadmin'],
            'roles'             => ['roles', 'permissions'],
        ];

        // Get the list of module keys to check for this controller
        $keys_to_check = isset($controller_module_map[$controller])
            ? $controller_module_map[$controller]
            : [$controller]; // fallback: use controller name as key

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
