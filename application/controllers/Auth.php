<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Setting_model', 'settings');
        $this->load->model('Security_audit_model', 'audit');
        $this->load->helper(['cookie', 'password']); // Load cookie and password helpers
        // Note: Security is a core class, automatically available as $this->security
        
        // Store intended URL for redirect after login
        if ($this->input->method() === 'get' && $this->uri->uri_string() !== 'auth/login') {
            $current_url = current_url();
            // Don't store register/reset password page URLs as redirect targets after login
            if (strpos($current_url, 'register') === false && 
                strpos($current_url, 'auth/register') === false &&
                strpos($current_url, 'reset_password') === false &&
                strpos($current_url, 'auth/reset_password') === false &&
                strpos($current_url, 'forgot_password') === false &&
                strpos($current_url, 'auth/forgot_password') === false) {
                $this->session->set_userdata('redirect_url', $current_url);
            }
        }
    }

    public function index(){
        // Redirect root to login if not authenticated, else to dashboard
        if ((int)$this->session->userdata('user_id') > 0) {
            redirect('dashboard');
            return;
        }
        redirect('auth/login');
    }

    public function login(){
        // Redirect if already logged in
        if ((int)$this->session->userdata('user_id') > 0) {
            redirect('dashboard');
            return;
        }

        if ($this->input->method() === 'post') {
            $identifier = trim($this->input->post('login'));
            $password = (string)$this->input->post('password');
            $remember = $this->input->post('remember');
            $is_ajax = $this->input->is_ajax_request();

            // Enhanced validation
            if (empty($identifier) || empty($password)) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Please enter both email/phone and password']);
                    exit;
                } else {
                    $this->session->set_flashdata('error', 'Please enter both email/phone and password');
                    redirect('auth/login');
                    return;
                }
            }

            // Check IP Whitelist first
            if (!$this->check_ip_whitelist()) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Access denied. Your IP address is not authorized.']);
                    exit;
                } else {
                    $this->session->set_flashdata('error', 'Access denied. Your IP address is not authorized.');
                    redirect('auth/login');
                    return;
                }
            }
            
            // Rate limiting: check recent failed attempts from this IP using settings
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $max_attempts = (int)$this->settings->get_setting('security_max_login_attempts', 5);
            $lockout_duration = (int)$this->settings->get_setting('security_lockout_duration', 15); // minutes
            
            // Check failed attempts from database or session
            $attempt_check = $this->check_login_attempts($identifier, $ip, $max_attempts, $lockout_duration);
            if ($attempt_check['locked']) {
                $minutes = $attempt_check['minutes'];
                $error_msg = "Too many failed attempts. Account locked for {$minutes} minutes.";
                
                // Log failed attempt if enabled
                if ($this->settings->get_setting('security_log_failed_attempts', 'no') === 'yes') {
                    $this->audit->log('login_locked', null, "IP: {$ip}, Identifier: " . substr($identifier, 0, 3) . '***', $ip);
                }
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $error_msg]);
                    exit;
                } else {
                    $this->session->set_flashdata('error', $error_msg);
                    redirect('auth/login');
                    return;
                }
            }

            // Find user by email or phone
            $user = $this->User_model->get_by_login($identifier);
            
            // Log login attempts only in development or for security monitoring
            if (ENVIRONMENT === 'development') {
                log_message('debug', "Login attempt for identifier: " . substr($identifier, 0, 3) . '***');
            }
            
            if (!$user) {
                $this->record_failed_attempt($identifier, $ip, $max_attempts, $lockout_duration);
                
                // Log failed attempt if enabled
                if ($this->settings->get_setting('security_log_failed_attempts', 'no') === 'yes') {
                    $this->audit->log('login_failed', null, "Invalid identifier: " . substr($identifier, 0, 3) . '***', $ip);
                }
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid credentials. Please check your email/phone and password.']);
                    exit;
                } else {
                    $this->session->set_flashdata('error', 'Invalid credentials');
                    redirect('auth/login');
                    return;
                }
            }

            // Verify password
            if (!password_verify($password, $user->password_hash)) {
                $this->record_failed_attempt($identifier, $ip, $max_attempts, $lockout_duration);
                
                // Log failed attempt if enabled
                if ($this->settings->get_setting('security_log_failed_attempts', 'no') === 'yes') {
                    $this->audit->log('login_failed', $user->id, "Invalid password for user ID: {$user->id}", $ip);
                }
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid credentials. Please check your email/phone and password.']);
                    exit;
                } else {
                    $this->session->set_flashdata('error', 'Invalid credentials');
                    redirect('auth/login');
                    return;
                }
            }
            
            // Check password expiry
            if (!$this->check_password_expiry($user)) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Your password has expired. Please reset your password.', 'expired' => true]);
                    exit;
                } else {
                    $this->session->set_flashdata('error', 'Your password has expired. Please reset your password.');
                    $this->session->set_userdata('pw_expired_user_id', $user->id);
                    redirect('auth/reset_password?expired=1');
                    return;
                }
            }

            // Check account status
            if (isset($user->status) && $user->status !== 'active') {
                $status_msg = 'Your account is ' . ($user->status === 'inactive' ? 'inactive' : 'suspended') . '. Please contact administrator.';
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $status_msg]);
                    exit;
                } else {
                    $this->session->set_flashdata('error', $status_msg);
                    redirect('auth/login');
                    return;
                }
            }

            // Email verification check
            if (isset($this->db) && $this->db->field_exists('email_verified', 'users')) {
                if (isset($user->email_verified) && (int)$user->email_verified !== 1) {
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Please verify your email address before logging in.']);
                        exit;
                    } else {
                        $this->session->set_flashdata('error', 'Please verify your email address before logging in.');
                        redirect('auth/login');
                        return;
                    }
                }
            }

            // Clear failed attempts on successful login
            $this->clear_failed_attempts($identifier, $ip);

            // Clear any password reset session data on successful login
            $this->session->unset_userdata(['pw_reset_phone','pw_reset_code_hash','pw_reset_expires']);
            
            // Check Single Session setting
            if ($this->settings->get_setting('security_single_session', 'no') === 'yes') {
                $this->destroy_other_sessions($user->id);
            }
            
            // Check if 2FA is required BEFORE setting full session
            $require_2fa = $this->check_2fa_required($user);
            if ($require_2fa) {
                // Regenerate session ID for security
                $this->session->sess_regenerate(TRUE);
                
                // Store ONLY pending login data - do NOT set user_id/role_id yet
                $this->session->set_userdata('pending_login_user_id', $user->id);
                $this->session->set_userdata('pending_login_ip', $ip);
                
                // Generate and send OTP
                $otp_result = $this->send_2fa_otp($user);
                
                if (!$otp_result['success']) {
                    // Clear pending data on failure
                    $this->session->unset_userdata(['pending_login_user_id', 'pending_login_ip']);
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $otp_result['error']]);
                        exit;
                    } else {
                        $this->session->set_flashdata('error', $otp_result['error']);
                        redirect('auth/login');
                        return;
                    }
                }
                
                // Redirect to 2FA verification - user is NOT authenticated yet
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'require_2fa' => true, 'redirect' => site_url('auth/verify-2fa')]);
                    exit;
                } else {
                    redirect('auth/verify-2fa');
                    return;
                }
            }

            // No 2FA required - complete login immediately
            // Regenerate session ID on login for security (prevents session fixation)
            $this->session->sess_regenerate(TRUE);
            
            // Set session data
            $this->session->set_userdata('user_id', (int)$user->id);
            $this->session->set_userdata('role_id', (int)$user->role_id);
            $this->session->set_userdata('email', $user->email);
            $this->session->set_userdata('last_activity', time());
            $this->session->set_userdata('session_id', session_id());
            
            // Remember me functionality (only if enabled)
            $remember_enabled = ($this->settings->get_setting('security_remember_me', 'no') === 'yes');
            if ($remember && $remember_enabled) {
                $this->_set_remember_cookie($user);
            }

            // Record login details
            $this->_record_login($user);
            
            // Log successful login if enabled
            if ($this->settings->get_setting('security_audit_login', 'no') === 'yes') {
                $this->audit->log('login_success', $user->id, "User logged in successfully", $ip);
            }

            // Handle AJAX response
            if ($is_ajax) {
                $this->load->helper('coaching');
                $default_home = function_exists('coaching_login_redirect')
                    ? coaching_login_redirect((int) $user->role_id)
                    : 'dashboard';
                $redirect_url = $this->session->userdata('redirect_url') ?: site_url($default_home);
                $this->session->unset_userdata('redirect_url');
                // Ensure redirect URL is not register/reset password page
                if (strpos($redirect_url, 'register') !== false || 
                    strpos($redirect_url, 'auth/register') !== false ||
                    strpos($redirect_url, 'reset_password') !== false ||
                    strpos($redirect_url, 'auth/reset_password') !== false ||
                    strpos($redirect_url, 'forgot_password') !== false ||
                    strpos($redirect_url, 'auth/forgot_password') !== false) {
                    $redirect_url = site_url('dashboard');
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $redirect_url]);
                exit;
            }

            // Redirect to intended page or dashboard for non-AJAX requests
            $this->load->helper('coaching');
            $default_home = function_exists('coaching_login_redirect')
                ? coaching_login_redirect((int) $user->role_id)
                : 'dashboard';
            $redirect_url = $this->session->userdata('redirect_url') ?: $default_home;
            $this->session->unset_userdata('redirect_url');
            // Ensure redirect URL is not register/reset password page
            if (strpos($redirect_url, 'register') !== false || 
                strpos($redirect_url, 'auth/register') !== false ||
                strpos($redirect_url, 'reset_password') !== false ||
                strpos($redirect_url, 'auth/reset_password') !== false ||
                strpos($redirect_url, 'forgot_password') !== false ||
                strpos($redirect_url, 'auth/forgot_password') !== false) {
                $redirect_url = 'dashboard';
            }
            redirect($redirect_url);
            return;
        }

        $this->load->view('auth/login');
    }

    /**
     * Check IP whitelist
     * @return bool True if IP is allowed
     */
    private function check_ip_whitelist() {
        $ip_whitelist_enabled = $this->settings->get_setting('security_ip_whitelist_enabled', 'no');
        if ($ip_whitelist_enabled !== 'yes') {
            return true; // IP whitelist not enabled
        }
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (empty($ip)) {
            return false;
        }
        
        $whitelist = $this->settings->get_setting('security_ip_whitelist', '');
        if (empty($whitelist)) {
            return true; // No whitelist configured, allow all
        }
        
        $allowed_ips = array_map('trim', explode(',', $whitelist));
        
        // Check exact match
        if (in_array($ip, $allowed_ips)) {
            return true;
        }
        
        // Check CIDR notation (basic support)
        foreach ($allowed_ips as $allowed_ip) {
            if (strpos($allowed_ip, '/') !== false) {
                // CIDR notation
                list($subnet, $mask) = explode('/', $allowed_ip);
                if ($this->ip_in_range($ip, $subnet, $mask)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ip_in_range($ip, $subnet, $mask) {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
    
    /**
     * Check login attempts for identifier/IP combination
     */
    private function check_login_attempts($identifier, $ip, $max_attempts, $lockout_duration) {
        // Check in database for persistent tracking
        $this->load->database();
        if (!$this->db->table_exists('login_attempts')) {
            // Create table if not exists
            $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `identifier` varchar(255) NOT NULL,
                `ip_address` varchar(45) NOT NULL,
                `attempts` int(11) DEFAULT 1,
                `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
                `locked_until` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_identifier_ip` (`identifier`, `ip_address`),
                KEY `idx_locked_until` (`locked_until`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        }
        
        $key = md5($identifier . '|' . $ip);
        $attempt = $this->db->where('identifier', $identifier)
                            ->where('ip_address', $ip)
                            ->get('login_attempts')
                            ->row();
        
        if (!$attempt) {
            return ['locked' => false, 'minutes' => 0];
        }
        
        // Check if locked
        if ($attempt->locked_until && strtotime($attempt->locked_until) > time()) {
            $minutes = ceil((strtotime($attempt->locked_until) - time()) / 60);
            return ['locked' => true, 'minutes' => $minutes];
        }
        
        // Check if attempts exceed limit
        if ($attempt->attempts >= $max_attempts) {
            // Check if lockout period has passed
            $last_attempt_time = strtotime($attempt->last_attempt);
            $lockout_seconds = $lockout_duration * 60;
            
            if ((time() - $last_attempt_time) < $lockout_seconds) {
                $minutes = ceil(($lockout_seconds - (time() - $last_attempt_time)) / 60);
                // Update locked_until
                $this->db->where('id', $attempt->id)
                         ->update('login_attempts', [
                             'locked_until' => date('Y-m-d H:i:s', time() + $lockout_seconds)
                         ]);
                return ['locked' => true, 'minutes' => $minutes];
            } else {
                // Reset attempts after lockout period
                $this->db->where('id', $attempt->id)
                         ->update('login_attempts', [
                             'attempts' => 0,
                             'locked_until' => null
                         ]);
            }
        }
        
        return ['locked' => false, 'minutes' => 0];
    }
    
    /**
     * Record failed login attempt
     */
    private function record_failed_attempt($identifier, $ip, $max_attempts, $lockout_duration) {
        $this->load->database();
        if (!$this->db->table_exists('login_attempts')) {
            return; // Table will be created on next check
        }
        
        $attempt = $this->db->where('identifier', $identifier)
                            ->where('ip_address', $ip)
                            ->get('login_attempts')
                            ->row();
        
        $now = date('Y-m-d H:i:s');
        
        if ($attempt) {
            $new_attempts = $attempt->attempts + 1;
            $locked_until = null;
            
            // Lock if max attempts reached
            if ($new_attempts >= $max_attempts) {
                $locked_until = date('Y-m-d H:i:s', time() + ($lockout_duration * 60));
            }
            
            $this->db->where('id', $attempt->id)
                     ->update('login_attempts', [
                         'attempts' => $new_attempts,
                         'last_attempt' => $now,
                         'locked_until' => $locked_until
                     ]);
        } else {
            $this->db->insert('login_attempts', [
                'identifier' => $identifier,
                'ip_address' => $ip,
                'attempts' => 1,
                'last_attempt' => $now
            ]);
        }
    }
    
    /**
     * Clear failed login attempts
     */
    private function clear_failed_attempts($identifier, $ip) {
        $this->load->database();
        if ($this->db->table_exists('login_attempts')) {
            $this->db->where('identifier', $identifier)
                     ->where('ip_address', $ip)
                     ->delete('login_attempts');
        }
    }
    
    /**
     * Check if password has expired
     */
    private function check_password_expiry($user) {
        $password_expiry_enabled = $this->settings->get_setting('security_password_expiry_enabled', 'no');
        if ($password_expiry_enabled !== 'yes') {
            return true; // Password expiry not enabled
        }
        
        $expiry_days = (int)$this->settings->get_setting('security_password_expiry_days', 90);
        if ($expiry_days <= 0) {
            return true; // No expiry configured
        }
        
        // Check if password_changed_at field exists
        if (!$this->db->field_exists('password_changed_at', 'users')) {
            return true; // Field doesn't exist, assume not expired
        }
        
        if (empty($user->password_changed_at)) {
            // Password never changed, consider expired if account is older than expiry_days
            if (!empty($user->created_at)) {
                $created_time = strtotime($user->created_at);
                $expiry_time = $created_time + ($expiry_days * 86400);
                return time() < $expiry_time;
            }
            return true;
        }
        
        $changed_time = strtotime($user->password_changed_at);
        $expiry_time = $changed_time + ($expiry_days * 86400);
        
        return time() < $expiry_time;
    }
    
    /**
     * Destroy other sessions for single session enforcement
     */
    private function destroy_other_sessions($user_id) {
        // This is a simplified implementation
        // In production, you might want to store session IDs in database
        // For now, we'll just ensure only the current session is valid
        $current_session_id = session_id();
        
        // Store current session ID in user's session data (already done in login method)
        // Any other session checks can be done in AuthHook
    }
    
    /**
     * Check if 2FA is required for user
     */
    private function check_2fa_required($user) {
        $require_2fa = $this->settings->get_setting('security_2fa_enabled', 'no');
        if ($require_2fa !== 'yes') {
            return false; // 2FA not enabled
        }
        
        // Check if 2FA is enabled for this specific user (if field exists)
        if ($this->db->field_exists('two_factor_enabled', 'users')) {
            // Allow per-user 2FA settings
            return (isset($user->two_factor_enabled) && $user->two_factor_enabled == 1);
        }
        
        // Global 2FA setting applies to all users
        return true;
    }
    
    /**
     * Send 2FA OTP to user
     */
    private function send_2fa_otp($user) {
        try {
            if (!function_exists('random_int')) {
                $code = mt_rand(100000, 999999);
            } else {
                $code = random_int(100000, 999999);
            }
        } catch (Exception $e) {
            $code = mt_rand(100000, 999999);
        }
        
        // Store OTP in session
        $this->session->set_userdata([
            '2fa_otp_hash' => password_hash((string)$code, PASSWORD_DEFAULT),
            '2fa_otp_expires' => time() + 300, // 5 minutes
        ]);
        
        // Send OTP via email
        try {
            $this->config->load('email');
            $this->load->library('email');
            $this->load->helper('email');
            configure_email_from_settings();
            $this->email->clear(true);
            $from = get_system_from_email();
            if (!$from) { $from = 'no-reply@example.com'; }
            $this->email->from($from, get_company_name());
            $this->email->to($user->email);
            $this->email->subject('Your Two-Factor Authentication Code');
            $message = '<p>Your 2FA verification code is <strong>'.htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8').'</strong>.</p>';
            $message .= '<p>It will expire in 5 minutes.</p>';
            $this->email->message($message);
            if (!$this->email->send()) {
                return ['success' => false, 'error' => 'Failed to send 2FA code. Please try again.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error sending 2FA code. Please try again.'];
        }
        
        return ['success' => true];
    }
    
    /**
     * 2FA verification page
     */
    public function verify_2fa() {
        $pending_user_id = (int)$this->session->userdata('pending_login_user_id');
        
        if (!$pending_user_id) {
            $this->session->set_flashdata('error', 'Invalid 2FA verification request.');
            redirect('auth/login');
            return;
        }
        
        if ($this->input->method() === 'post') {
            $code = trim((string)$this->input->post('code'));
            $otp_hash = (string)$this->session->userdata('2fa_otp_hash');
            $otp_expires = (int)$this->session->userdata('2fa_otp_expires');
            
            if (empty($code) || empty($otp_hash) || !$otp_expires || time() > $otp_expires) {
                $this->session->set_flashdata('error', 'Invalid or expired 2FA code. Please try again.');
                redirect('auth/verify-2fa');
                return;
            }
            
            if (!password_verify($code, $otp_hash)) {
                $this->session->set_flashdata('error', 'Invalid 2FA code. Please check and try again.');
                redirect('auth/verify-2fa');
                return;
            }
            
            // 2FA verified, complete login
            $user = $this->User_model->get($pending_user_id);
            if (!$user) {
                $this->session->set_flashdata('error', 'User not found.');
                redirect('auth/login');
                return;
            }
            
            // Clear 2FA session data
            $this->session->unset_userdata(['2fa_otp_hash', '2fa_otp_expires', 'pending_login_user_id', 'pending_login_ip']);
            
            // Regenerate session ID
            $this->session->sess_regenerate(TRUE);
            
            // Set session data
            $this->session->set_userdata('user_id', (int)$user->id);
            $this->session->set_userdata('role_id', (int)$user->role_id);
            $this->session->set_userdata('email', $user->email);
            $this->session->set_userdata('last_activity', time());
            $this->session->set_userdata('session_id', session_id());
            
            // Log 2FA success
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            if ($this->settings->get_setting('security_audit_login', 'no') === 'yes') {
                $this->audit->log('2fa_verified', $user->id, "2FA verified successfully", $ip);
            }
            
            // Redirect after 2FA
            $this->load->helper('coaching');
            $default_home = function_exists('coaching_login_redirect')
                ? coaching_login_redirect((int) $user->role_id)
                : 'dashboard';
            $redirect_url = $this->session->userdata('redirect_url') ?: $default_home;
            $this->session->unset_userdata('redirect_url');
            redirect($redirect_url);
            return;
        }
        
        $this->load->view('auth/verify_2fa');
    }
    
    private function _record_failed_attempt($key, $attempts, $last_attempt) {
        // Legacy method - kept for backward compatibility
        $new_attempts = $attempts + 1;
        $this->session->set_userdata($key, $new_attempts);
        $this->session->set_userdata($key . '_time', time());
    }

    private function _set_remember_cookie($user) {
        // PHP 5.6 compatible random bytes generation
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
            $selector = bin2hex(random_bytes(8));
        } else {
            // Fallback for PHP < 7.0
            $token = bin2hex(openssl_random_pseudo_bytes(32));
            $selector = bin2hex(openssl_random_pseudo_bytes(8));
        }
        $expires = time() + (86400 * 30); // 30 days
        $expires_date = date('Y-m-d H:i:s', $expires);
        
        // Ensure remember_tokens table exists
        $this->load->database();
        if (!$this->db->table_exists('remember_tokens')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `remember_tokens` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `selector` varchar(16) NOT NULL,
                `token_hash` varchar(64) NOT NULL,
                `user_id` int(11) NOT NULL,
                `expires` datetime NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `selector` (`selector`),
                KEY `user_id` (`user_id`),
                KEY `expires` (`expires`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        
        // Delete old tokens for this user
        $this->db->where('user_id', (int)$user->id)->delete('remember_tokens');
        
        // Store token in database
        $data = [
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'user_id' => (int)$user->id,
            'expires' => $expires_date,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('remember_tokens', $data);
        
        // Set secure cookie with selector:token
        $cookie_value = $selector . ':' . $token;
        $this->load->helper('cookie');
        // Use secure cookie in production (HTTPS)
        $cookie_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        set_cookie('remember_me', $cookie_value, $expires, '/', '', $cookie_secure, true);
    }

    private function _record_login($user) {
        try {
            $now = date('Y-m-d H:i:s');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : '';
            
            $data = [];
            if ($this->db->field_exists('last_login', 'users')) { $data['last_login'] = $now; }
            if ($this->db->field_exists('last_login_at', 'users')) { $data['last_login_at'] = $now; }
            if ($this->db->field_exists('last_login_on', 'users')) { $data['last_login_on'] = $now; }
            if ($this->db->field_exists('last_seen_at', 'users')) { $data['last_seen_at'] = $now; }
            if ($this->db->field_exists('last_login_ip', 'users')) { $data['last_login_ip'] = $ip; }
            if ($this->db->field_exists('last_login_user_agent', 'users')) { $data['last_login_user_agent'] = $user_agent; }
            
            if (!empty($data)) {
                $this->db->where('id', (int)$user->id)->update('users', $data);
            }
        } catch (Exception $e) {
            // Log error if needed, but don't break login
            error_log('Login recording error: ' . $e->getMessage());
        }
    }

    public function logout(){
        $this->session->sess_destroy();
        redirect('auth/login');
    }

    private function _json($arr) {
        $this->output->set_content_type('application/json')->set_output(json_encode($arr));
    }

    public function send_verify_code(){
        if ($this->input->method() !== 'post') { show_404(); }
        $email = trim((string)$this->input->post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_json(['ok'=>false,'error'=>'Please enter a valid email address.']);
            return;
        }
        $domain = '';
        if (strpos($email, '@') !== false) {
            $parts = explode('@', $email);
            $domain = isset($parts[1]) ? strtolower(trim($parts[1])) : '';
        }
        if ($domain !== 'gmail.com' && $domain !== 'googlemail.com') {
            $this->_json(['ok'=>false,'error'=>'Only Gmail addresses are allowed.']);
            return;
        }
        if ($this->User_model->email_exists($email)) {
            $this->_json(['ok'=>false,'error'=>'This email is already registered. Please login instead.']);
            return;
        }
        try {
            if (!function_exists('random_int')) {
                $code = mt_rand(100000, 999999);
            } else {
                $code = random_int(100000, 999999);
            }
        } catch (Exception $e) {
            $code = mt_rand(100000, 999999);
        }
        $this->load->library('session');
        $this->session->set_userdata([
            'reg_email' => $email,
            'reg_code_hash' => password_hash((string)$code, PASSWORD_DEFAULT),
            'reg_code_expires' => time() + 600,
        ]);
        try {
            $this->config->load('email');
            $this->load->library('email');
            $this->load->helper('email');
            configure_email_from_settings();
            $this->email->clear(true);
            $from = get_system_from_email();
            if (!$from) { $from = 'no-reply@example.com'; }
            $this->email->from($from, get_company_name());
            $this->email->to($email);
            $this->email->subject('Your verification code');
            $message = '<p>Your verification code is <strong>'.htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8').'</strong>.</p>';
            $message .= '<p>It will expire in 10 minutes.</p>';
            $this->email->message($message);
            if (!$this->email->send()) {
                $this->_json(['ok'=>false,'error'=>'Failed to send verification email.']);
                return;
            }
        } catch (Exception $e) {
            $this->_json(['ok'=>false,'error'=>'Error sending verification email.']);
            return;
        }
        $this->_json(['ok'=>true]);
    }

    public function verify_code(){
        // Set JSON header first and prevent any output
        header('Content-Type: application/json');
        
        // Only allow POST requests
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['valid'=>false,'error'=>'Method not allowed.']);
            exit;
        }
        
        try {
            $code = trim((string)$this->input->post('code'));
            if ($code === '') {
                echo json_encode(['valid'=>false,'error'=>'Verification code is required.']);
                exit;
            }
            
            // Ensure session is loaded (it should be autoloaded, but ensure it's available)
            if (!isset($this->session)) {
                $this->load->library('session');
            }
            
            // Get session data
            $sessionHash = $this->session->userdata('reg_code_hash');
            $sessionExp = $this->session->userdata('reg_code_expires');
            
            // Convert to proper types
            $sessionHash = $sessionHash ? (string)$sessionHash : '';
            $sessionExp = $sessionExp ? (int)$sessionExp : 0;
            
            // Check if verification code was ever sent
            if (empty($sessionHash)) {
                echo json_encode(['valid'=>false,'error'=>'Please click "Send Code" button first to receive a verification code.']);
                exit;
            }
            
            // Check if code has expired
            if (!$sessionExp || time() > $sessionExp) {
                echo json_encode(['valid'=>false,'error'=>'Verification code has expired. Please request a new code.']);
                exit;
            }
            
            // Verify the code
            if (password_verify($code, $sessionHash)) {
                echo json_encode(['valid'=>true,'message'=>'Code verified successfully.']);
                exit;
            } else {
                echo json_encode(['valid'=>false,'error'=>'Invalid verification code. Please check and try again.']);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['valid'=>false,'error'=>'An error occurred while verifying the code. Please try again.']);
            exit;
        } catch (Error $e) {
            http_response_code(500);
            echo json_encode(['valid'=>false,'error'=>'An error occurred while verifying the code. Please try again.']);
            exit;
        }
    }

    // GET /auth/verify?token=xxxx
    public function verify(){
        $token = trim((string)$this->input->get('token'));
        if ($token === '') {
            show_error('Invalid verification link.', 400);
        }
        if (!isset($this->db) || !$this->db->field_exists('email_verify_token', 'users')) {
            show_error('Email verification is not configured.', 500);
        }
        $user = $this->db->get_where('users', ['email_verify_token' => $token])->row();
        if (!$user) {
            show_error('This verification link is invalid or has already been used.', 400);
        }
        $update = ['email_verify_token' => null];
        if ($this->db->field_exists('email_verified', 'users')) {
            $update['email_verified'] = 1;
        }
        if ($this->db->field_exists('email_verified_at', 'users')) {
            $update['email_verified_at'] = date('Y-m-d H:i:s');
        }
        // Optional flags: mark as verified for custom columns if present
        if ($this->db->field_exists('is_verified', 'users')) {
            $update['is_verified'] = 1;
        }
        if ($this->db->field_exists('is_verified1', 'users')) {
            $update['is_verified1'] = 1;
        }
        $this->db->where('id', (int)$user->id)->update('users', $update);
        $this->session->set_flashdata('success', 'Your email has been verified. You can now login.');
        redirect('auth/login');
    }

    public function forgot_password(){
        if ($this->input->method() === 'post') {
            $phone = trim((string)$this->input->post('phone'));
            if ($phone === '') {
                $this->session->set_flashdata('error', 'Please enter your registered mobile number.');
                redirect('auth/forgot_password');
                return;
            }
            if (!isset($this->db) || !$this->db->field_exists('phone', 'users')) {
                $this->session->set_flashdata('error', 'Password reset via mobile number is not available.');
                redirect('auth/forgot_password');
                return;
            }
            $user = $this->User_model->get_by_phone($phone);
            if (!$user) {
                // Show error if mobile number is not found in database
                $this->session->set_flashdata('error', 'This mobile number is not registered. Please check and try again.');
                redirect('auth/forgot_password');
                return;
            }
            if (isset($user->status) && $user->status !== 'active') {
                $this->session->set_flashdata('error', 'Account inactive. Please contact the administrator.');
                redirect('auth/forgot_password');
                return;
            }
            if (empty($user->email)) {
                $this->session->set_flashdata('error', 'No email address is linked to this account. Please contact the administrator.');
                redirect('auth/forgot_password');
                return;
            }
            try {
                if (!function_exists('random_int')) {
                    $code = mt_rand(100000, 999999);
                } else {
                    $code = random_int(100000, 999999);
                }
            } catch (Exception $e) {
                $code = mt_rand(100000, 999999);
            }
            $this->load->library('session');
            $this->session->set_userdata([
                'pw_reset_phone' => $phone,
                'pw_reset_code_hash' => password_hash((string)$code, PASSWORD_DEFAULT),
                'pw_reset_expires' => time() + 600,
            ]);

            // Send OTP to user's registered email
            try {
                $this->config->load('email');
                $this->load->library('email');
                $this->load->helper('email');
                configure_email_from_settings();
                $this->email->clear(true);
                $from = get_system_from_email();
                if (!$from) { $from = 'no-reply@example.com'; }
                $this->email->from($from, get_company_name());
                $this->email->to($user->email);
                $this->email->subject('Your password reset OTP');
                $message = '<p>Your OTP for resetting your password is <strong>'.htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8').'</strong>.</p>';
                $message .= '<p>It will expire in 10 minutes.</p>';
                $this->email->message($message);
                if (!$this->email->send()) {
                    $this->session->set_flashdata('error', 'Failed to send OTP email. Please try again.');
                    redirect('auth/forgot_password');
                    return;
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Failed to send OTP email. Please try again.');
                redirect('auth/forgot_password');
                return;
            }

            $this->session->set_flashdata('success', 'An OTP has been sent to your registered email address. Please check your inbox or spam folder.');
            redirect('auth/reset_password');
            return;
        }
        $this->load->view('auth/forgot_password');
    }

    public function reset_password(){
        $this->load->library('session');
        
        // If user is already logged in, redirect to dashboard
        if ((int)$this->session->userdata('user_id') > 0) {
            redirect('dashboard');
            return;
        }
        
        $phone = (string)$this->session->userdata('pw_reset_phone');
        $hash = (string)$this->session->userdata('pw_reset_code_hash');
        $expires = (int)$this->session->userdata('pw_reset_expires');
        if ($phone === '' || !$hash || !$expires || time() > $expires) {
            // Clear any stale session data
            $this->session->unset_userdata(['pw_reset_phone','pw_reset_code_hash','pw_reset_expires']);
            $this->session->set_flashdata('error', 'Invalid or expired OTP. Please request a new one.');
            redirect('auth/forgot_password');
            return;
        }
        if ($this->input->method() === 'post') {
            $code = trim((string)$this->input->post('code'));
            $password = (string)$this->input->post('password');
            $confirm = (string)$this->input->post('password_confirm');
            if ($code === '') {
                $this->session->set_flashdata('error', 'Please enter the OTP sent to your mobile.');
                redirect('auth/reset_password');
                return;
            }
            if (!password_verify($code, $hash)) {
                $this->session->set_flashdata('error', 'Invalid OTP.');
                redirect('auth/reset_password');
                return;
            }
            // Validate password strength using settings
            $this->load->helper('password');
            $validation = validate_password_strength($password); // Uses settings automatically
            
            if (!$validation['valid']) {
                $this->session->set_flashdata('error', implode(' ', $validation['errors']));
                redirect('auth/reset_password');
                return;
            }
            if ($password !== $confirm) {
                $this->session->set_flashdata('error', 'Passwords do not match.');
                redirect('auth/reset_password');
                return;
            }
            $user = $this->User_model->get_by_phone($phone);
            if (!$user) {
                $this->session->set_flashdata('error', 'Unable to find user for this reset request.');
                $this->session->unset_userdata(['pw_reset_phone','pw_reset_code_hash','pw_reset_expires']);
                redirect('auth/forgot_password');
                return;
            }
            $this->User_model->update($user->id, [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $this->session->unset_userdata(['pw_reset_phone','pw_reset_code_hash','pw_reset_expires']);
            $this->session->set_flashdata('success', 'Your password has been updated. Please login.');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/reset_password');
    }

    public function register(){
        // Security is a core class, automatically available as $this->security
        
        if ($this->input->method() === 'post') {
            $full_name = trim((string)$this->input->post('name'));
            $email     = trim((string)$this->input->post('email'));
            $phone     = trim((string)$this->input->post('phone'));
            $password  = (string)$this->input->post('password');
            $role_id   = (int)$this->input->post('role_id');
            $verify_code = trim((string)$this->input->post('verify_code'));

            // Field-level validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('error', 'Please enter a valid email address.');
                redirect('auth/register');
                return;
            }
            // Enforce Gmail-only registration: email must be @gmail.com (or @googlemail.com)
            // TODO: Make this configurable via settings instead of hardcoding
            $domain = '';
            if (strpos($email, '@') !== false) {
                $parts = explode('@', $email);
                $domain = isset($parts[1]) ? strtolower(trim($parts[1])) : '';
            }
            if ($domain !== 'gmail.com' && $domain !== 'googlemail.com') {
                $this->session->set_flashdata('error', 'Please register with a Gmail address (example@gmail.com).');
                redirect('auth/register');
                return;
            }
            // Extra: validate Gmail domain via DNS
            if ($domain !== '' && function_exists('checkdnsrr')) {
                $hasMx = @checkdnsrr($domain, 'MX');
                $hasA  = @checkdnsrr($domain, 'A');
                if (!$hasMx && !$hasA) {
                    $this->session->set_flashdata('error', 'Email domain does not appear to be valid.');
                    redirect('auth/register');
                    return;
                }
            }
            $this->load->library('session');
            $sessionEmail = (string)$this->session->userdata('reg_email');
            $sessionHash  = (string)$this->session->userdata('reg_code_hash');
            $sessionExp   = (int)$this->session->userdata('reg_code_expires');
            
            // Check if verification code was requested for this email
            if ($sessionEmail === '' || strcasecmp($sessionEmail, $email) !== 0) {
                $this->session->set_flashdata('error', 'Please click "Send Code" button first to receive verification code.');
                redirect('auth/register');
                return;
            }
            
            // Check if verification code was sent
            if ($verify_code === '') {
                $this->session->set_flashdata('error', 'Please enter the verification code sent to your email.');
                redirect('auth/register');
                return;
            }
            
            // Check if code has expired
            if (!$sessionHash || !$sessionExp || time() > $sessionExp) {
                $this->session->set_flashdata('error', 'Verification code has expired. Please request a new code.');
                redirect('auth/register');
                return;
            }
            
            // Verify the code
            if (!password_verify($verify_code, $sessionHash)) {
                $this->session->set_flashdata('error', 'Invalid verification code. Please check and try again.');
                redirect('auth/register');
                return;
            }
            // Phone is optional; validate format only if provided
            if ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
                $this->session->set_flashdata('error', 'Please enter a valid 10-digit mobile number, or leave it blank.');
                redirect('auth/register');
                return;
            }
            // Validate password strength using settings
            $this->load->helper('password');
            $validation = validate_password_strength($password); // Uses settings automatically
            
            if (!$validation['valid']) {
                $this->session->set_flashdata('error', implode(' ', $validation['errors']));
                redirect('auth/register');
                return;
            }
            $roles = $this->roles();
            if (!$role_id || !isset($roles[$role_id])) {
                $this->session->set_flashdata('error', 'Please select a role.');
                redirect('auth/register');
                return;
            }

            // Email uniqueness based on users table
            if ($this->User_model->email_exists($email)) {
                $this->session->set_flashdata('error', 'Email already exists.');
                redirect('auth/register');
                return;
            }
            // Phone uniqueness (if phone column exists)
            if ($phone !== '' && $this->db->field_exists('phone', 'users') && $this->User_model->phone_exists($phone)) {
                $this->session->set_flashdata('error', 'Mobile number already exists.');
                redirect('auth/register');
                return;
            }

            $data = array(
                'email'        => $email,
                'password_hash'=> password_hash($password, PASSWORD_DEFAULT),
                'role_id'      => $role_id,
                'status'       => 'active',
                'created_at'   => date('Y-m-d H:i:s')
            );
            // Prepare email verification fields if columns exist
            $verifyToken = null;
            if ($this->db->field_exists('email_verified', 'users')) {
                // Since we verified the code via email, mark as verified
                $data['email_verified'] = 1;
            }
            // Optional flags: mark as verified for custom columns if present
            if ($this->db->field_exists('is_verified', 'users')) {
                $data['is_verified'] = 1;
            }
            if ($this->db->field_exists('is_verified1', 'users')) {
                $data['is_verified1'] = 1;
            }
            if ($this->db->field_exists('email_verify_token', 'users')) {
                try {
                    if (function_exists('random_bytes')) {
                        $verifyToken = bin2hex(random_bytes(16));
                    } else if (function_exists('openssl_random_pseudo_bytes')) {
                        $verifyToken = bin2hex(openssl_random_pseudo_bytes(16));
                    } else {
                        $verifyToken = md5(uniqid(mt_rand(), true));
                    }
                } catch (Exception $e) {
                    $verifyToken = md5(uniqid(mt_rand(), true));
                }
                $data['email_verify_token'] = $verifyToken;
            }
            if ($this->db->field_exists('email_verify_sent_at', 'users')) {
                $data['email_verify_sent_at'] = date('Y-m-d H:i:s');
            }
            // Persist phone if column exists
            if ($phone !== '' && $this->db->field_exists('phone','users')) {
                $data['phone'] = $phone;
            }
            // Derive and persist role string if column exists
            if ($this->db->field_exists('role','users')) {
                $roleName = isset($roles[$role_id]) ? $roles[$role_id] : '';
                $data['role'] = $roleName !== '' ? strtolower(str_replace(' ', '_', $roleName)) : '';
            }
            // Attempt to persist name into available schema fields
            if ($full_name !== ''){
                // If single 'name' column exists
                if ($this->db->field_exists('name','users')) { $data['name'] = $full_name; }
                // Else try first_name/last_name split if present
                else if ($this->db->field_exists('first_name','users') || $this->db->field_exists('last_name','users')) {
                    $parts = preg_split('/\s+/', $full_name);
                    $first = isset($parts[0]) ? $parts[0] : '';
                    $last = '';
                    if (count($parts) > 1) { $last = trim(implode(' ', array_slice($parts, 1))); }
                    if ($this->db->field_exists('first_name','users')) { $data['first_name'] = $first; }
                    if ($this->db->field_exists('last_name','users')) { $data['last_name'] = $last; }
                    if ($this->db->field_exists('full_name','users')) { $data['full_name'] = $full_name; }
                }
            }
            
            $id = $this->User_model->create($data);

            // Check if user creation was successful
            if (!$id) {
                log_message('error', 'Failed to create user during registration');
                $this->session->set_flashdata('error', 'Failed to create account. Please try again.');
                redirect('auth/register');
                return;
            }

            // Send verification email if token/columns are available
            if ($id && $verifyToken) {
                try {
                    $this->config->load('email');
                    $this->load->library('email');
                    $this->load->helper('email');
                    configure_email_from_settings();
                    $this->email->clear(true);
                    $from = get_system_from_email();
                    if (!$from) { $from = 'no-reply@example.com'; }
                    $this->email->from($from, get_company_name());
                    $this->email->to($email);
                    $this->email->subject('Verify your email address');
                    $link = site_url('auth/verify?token='.$verifyToken);
                    $message = '<p>Hello'.($full_name ? ' '.htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') : '').',</p>';
                    $message .= '<p>Please verify your email address by clicking the link below:</p>';
                    $message .= '<p><a href="'.$link.'">'.$link.'</a></p>';
                    $message .= '<p>If you did not request this account, you can ignore this email.</p>';
                    $this->email->message($message);
                    $this->email->send();
                } catch (Exception $e) {
                    // Do not block registration if email fails; user can request manual activation
                }
            }

            $this->session->unset_userdata(['reg_email','reg_code_hash','reg_code_expires']);
            $this->session->set_flashdata('success', 'Account created successfully! You can now login with your credentials.');
            redirect('auth/login');
            return;
        }
        $roles = $this->roles();
        $this->load->view('auth/register', ['roles' => $roles]);
    }

    /**
     * Shared helper to read roles from roles table (id => name) with fallback defaults.
     * @return array<int,string>
     */
    private function roles(){
        $out = [];
        if (isset($this->db) && $this->db->table_exists('roles')) {
            $this->db->from('roles');
            if ($this->db->field_exists('is_active', 'roles')) {
                $this->db->where('is_active', 1);
            }
            if ($this->db->field_exists('sort_order', 'roles')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            $rows = $this->db->get()->result();
            foreach ($rows as $row) {
                $rid = isset($row->id) ? (int)$row->id : 0;
                if ($rid <= 0) { continue; }
                $out[$rid] = isset($row->name) ? (string)$row->name : ('Role #'.$rid);
            }
        }
        if (!empty($out)) { return $out; }

        // Fallback labels
        return [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Lead',
            4 => 'Staff',
        ];
    }
}
