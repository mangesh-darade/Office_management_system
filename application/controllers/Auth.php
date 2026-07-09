<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Setting_model', 'settings');
        $this->load->model('Security_audit_model', 'audit');
        $this->load->helper([
            'cookie',
            'password',
            'schema_columns',
            'auth_security',
            'auth_login_attempts',
            'auth_session',
            'auth_response',
            'auth_2fa',
            'auth_email',
        ]);
        // Note: Security is a core class, automatically available as $this->security
        
        // Store intended URL for redirect after login
        if ($this->input->method() === 'get' && $this->uri->uri_string() !== 'auth/login') {
            $current_url = current_url();
            if (auth_should_store_redirect_url($current_url)) {
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

    /**
     * POST/GET login — IP whitelist, rate limit, password verify, optional 2FA, session + remember-me.
     * Supports both form POST and AJAX (returns JSON on X-Requested-With).
     */
    public function login(){
        if ((int)$this->session->userdata('user_id') > 0) {
            redirect('dashboard');
            return;
        }

        $this->load->helper('schema_automation');
        oms_ensure_schemas_on_login_page($this->session);

        if ($this->input->method() !== 'post') {
            $this->load->view('auth/login');
            return;
        }

        $identifier = trim($this->input->post('login'));
        $password = (string)$this->input->post('password');
        $remember = $this->input->post('remember');
        $is_ajax = $this->input->is_ajax_request();
        $ip = auth_client_ip();

        if ($identifier === '' || $password === '') {
            auth_respond_login_error($is_ajax, 'Please enter both email/phone and password');
        }

        if (!auth_ip_whitelist_check($this->settings, $ip)) {
            auth_respond_login_error($is_ajax, 'Access denied. Your IP address is not authorized.');
        }

        $max_attempts = (int)$this->settings->get_setting('security_max_login_attempts', 5);
        $lockout_duration = (int)$this->settings->get_setting('security_lockout_duration', 15);
        $attempt_check = auth_login_attempts_check($this->db, $identifier, $ip, $max_attempts, $lockout_duration);
        if ($attempt_check['locked']) {
            auth_log_failed_login(
                $this->audit,
                $this->settings,
                'login_locked',
                null,
                "IP: {$ip}, Identifier: " . substr($identifier, 0, 3) . '***',
                $ip
            );
            auth_respond_login_error(
                $is_ajax,
                "Too many failed attempts. Account locked for {$attempt_check['minutes']} minutes."
            );
        }

        $user = $this->User_model->get_by_login($identifier);
        if (ENVIRONMENT === 'development') {
            log_message('debug', "Login attempt for identifier: " . substr($identifier, 0, 3) . '***');
        }

        if (!$user) {
            auth_login_attempts_record_failed($this->db, $identifier, $ip, $max_attempts, $lockout_duration);
            auth_log_failed_login(
                $this->audit,
                $this->settings,
                'login_failed',
                null,
                "Invalid identifier: " . substr($identifier, 0, 3) . '***',
                $ip
            );
            $msg = $is_ajax
                ? 'Invalid credentials. Please check your email/phone and password.'
                : 'Invalid credentials';
            auth_respond_login_error($is_ajax, $msg);
        }

        if (!password_verify($password, $user->password_hash)) {
            auth_login_attempts_record_failed($this->db, $identifier, $ip, $max_attempts, $lockout_duration);
            auth_log_failed_login(
                $this->audit,
                $this->settings,
                'login_failed',
                $user->id,
                "Invalid password for user ID: {$user->id}",
                $ip
            );
            $msg = $is_ajax
                ? 'Invalid credentials. Please check your email/phone and password.'
                : 'Invalid credentials';
            auth_respond_login_error($is_ajax, $msg);
        }

        if (!auth_password_not_expired($this->settings, $this->db, $user)) {
            auth_respond_password_expired($is_ajax, $user->id);
        }

        if (isset($user->status) && $user->status !== 'active') {
            $status_msg = 'Your account is ' . ($user->status === 'inactive' ? 'inactive' : 'suspended') . '. Please contact administrator.';
            auth_respond_login_error($is_ajax, $status_msg);
        }

        if (schema_table_has_column($this->db, 'users', 'email_verified')) {
            if (isset($user->email_verified) && (int)$user->email_verified !== 1) {
                auth_respond_login_error($is_ajax, 'Please verify your email address before logging in.');
            }
        }

        auth_login_attempts_clear($this->db, $identifier, $ip);
        $this->session->unset_userdata(['pw_reset_phone','pw_reset_code_hash','pw_reset_expires']);

        if (auth_2fa_required_for_user($this->settings, $this->db, $user)) {
            $this->session->sess_regenerate(TRUE);
            auth_session_set_pending_2fa($this->session, $user->id, $ip);
            $otp_result = auth_2fa_send_otp($this, $this->session, $user);
            if (!$otp_result['success']) {
                auth_session_clear_pending_2fa($this->session);
                auth_respond_login_error($is_ajax, $otp_result['error']);
            }
            auth_respond_2fa_required($is_ajax);
        }

        auth_complete_login($this->session, $user);

        $remember_enabled = ($this->settings->get_setting('security_remember_me', 'no') === 'yes');
        if ($remember && $remember_enabled) {
            auth_remember_me_set($this->db, $user);
        }

        auth_record_login($this->db, $user);
        if ($this->settings->get_setting('security_audit_login', 'no') === 'yes') {
            $this->audit->log('login_success', $user->id, "User logged in successfully", $ip);
        }

        $redirect_url = auth_resolve_post_login_redirect($this->session, $user, $is_ajax);
        auth_respond_login_success($is_ajax, $redirect_url);
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
            $verify = auth_session_verify_2fa_otp($this->session, $code);
            if (!$verify['ok']) {
                $this->session->set_flashdata('error', $verify['error']);
                redirect('auth/verify-2fa');
                return;
            }

            $user = $this->User_model->get($pending_user_id);
            if (!$user) {
                $this->session->set_flashdata('error', 'User not found.');
                redirect('auth/login');
                return;
            }

            auth_session_clear_2fa_otp($this->session);
            auth_session_clear_pending_2fa($this->session);
            auth_complete_login($this->session, $user);

            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            if ($this->settings->get_setting('security_audit_login', 'no') === 'yes') {
                $this->audit->log('2fa_verified', $user->id, "2FA verified successfully", $ip);
            }

            redirect(auth_resolve_post_login_redirect($this->session, $user, false));
            return;
        }
        
        $this->load->view('auth/verify_2fa');
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
        if (!auth_is_gmail_address($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->_json(['ok'=>false,'error'=>'Please enter a valid email address.']);
                return;
            }
            $this->_json(['ok'=>false,'error'=>'Only Gmail addresses are allowed.']);
            return;
        }
        if ($this->User_model->email_exists($email)) {
            $this->_json(['ok'=>false,'error'=>'This email is already registered. Please login instead.']);
            return;
        }

        // Rate limit OTP sends per email+IP (prevents email bombing).
        $ip = auth_client_ip();
        $otp_identifier = 'otp:' . strtolower($email);
        $limit = auth_login_attempts_check($this->db, $otp_identifier, $ip, 5, 15);
        if ($limit['locked']) {
            $this->_json(['ok'=>false,'error'=>'Too many code requests. Please try again in ' . (int)$limit['minutes'] . ' minutes.']);
            return;
        }
        auth_login_attempts_record_failed($this->db, $otp_identifier, $ip, 5, 15);

        $code = auth_generate_numeric_otp();
        $this->load->library('session');
        $this->session->set_userdata([
            'reg_email' => $email,
            'reg_code_hash' => password_hash((string)$code, PASSWORD_DEFAULT),
            'reg_code_expires' => time() + 600,
        ]);

        if (!auth_send_otp_email($this, $email, 'Your verification code', $code, 10)) {
            $this->_json(['ok'=>false,'error'=>'Failed to send verification email.']);
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
        if (!isset($this->db) || !schema_table_has_column($this->db, 'users', 'email_verify_token')) {
            show_error('Email verification is not configured.', 500);
        }
        $user = $this->db->get_where('users', ['email_verify_token' => $token])->row();
        if (!$user) {
            show_error('This verification link is invalid or has already been used.', 400);
        }
        $update = ['email_verify_token' => null];
        if (schema_table_has_column($this->db, 'users', 'email_verified')) {
            $update['email_verified'] = 1;
        }
        if (schema_table_has_column($this->db, 'users', 'email_verified_at')) {
            $update['email_verified_at'] = date('Y-m-d H:i:s');
        }
        // Optional flags: mark as verified for custom columns if present
        if (schema_table_has_column($this->db, 'users', 'is_verified')) {
            $update['is_verified'] = 1;
        }
        if (schema_table_has_column($this->db, 'users', 'is_verified1')) {
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
            if (!isset($this->db) || !schema_table_has_column($this->db, 'users', 'phone')) {
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
            $code = auth_generate_numeric_otp();
            $this->load->library('session');
            $this->session->set_userdata([
                'pw_reset_phone' => $phone,
                'pw_reset_code_hash' => password_hash((string)$code, PASSWORD_DEFAULT),
                'pw_reset_expires' => time() + 600,
            ]);

            if (!auth_send_otp_email($this, $user->email, 'Your password reset OTP', $code, 10)) {
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
            $verify_code = trim((string)$this->input->post('verify_code'));
            // Public registration always creates Staff accounts; ignore any posted role_id.
            $role_id = ROLE_STAFF;

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
            // Email uniqueness based on users table
            if ($this->User_model->email_exists($email)) {
                $this->session->set_flashdata('error', 'Email already exists.');
                redirect('auth/register');
                return;
            }
            // Phone uniqueness (if phone column exists)
            if ($phone !== '' && schema_table_has_column($this->db, 'users', 'phone') && $this->User_model->phone_exists($phone)) {
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
            if (schema_table_has_column($this->db, 'users', 'email_verified')) {
                // Since we verified the code via email, mark as verified
                $data['email_verified'] = 1;
            }
            // Optional flags: mark as verified for custom columns if present
            if (schema_table_has_column($this->db, 'users', 'is_verified')) {
                $data['is_verified'] = 1;
            }
            if (schema_table_has_column($this->db, 'users', 'is_verified1')) {
                $data['is_verified1'] = 1;
            }
            if (schema_table_has_column($this->db, 'users', 'email_verify_token')) {
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
            if (schema_table_has_column($this->db, 'users', 'email_verify_sent_at')) {
                $data['email_verify_sent_at'] = date('Y-m-d H:i:s');
            }
            // Persist phone if column exists
            if ($phone !== '' && schema_table_has_column($this->db, 'users', 'phone')) {
                $data['phone'] = $phone;
            }
            // Derive and persist role string if column exists
            if (schema_table_has_column($this->db, 'users', 'role')) {
                $roleName = 'Staff';
                $data['role'] = $roleName !== '' ? strtolower(str_replace(' ', '_', $roleName)) : '';
            }
            // Attempt to persist name into available schema fields
            if ($full_name !== ''){
                // If single 'name' column exists
                if (schema_table_has_column($this->db, 'users', 'name')) { $data['name'] = $full_name; }
                // Else try first_name/last_name split if present
                else if (schema_table_has_column($this->db, 'users', 'first_name') || schema_table_has_column($this->db, 'users', 'last_name')) {
                    $parts = preg_split('/\s+/', $full_name);
                    $first = isset($parts[0]) ? $parts[0] : '';
                    $last = '';
                    if (count($parts) > 1) { $last = trim(implode(' ', array_slice($parts, 1))); }
                    if (schema_table_has_column($this->db, 'users', 'first_name')) { $data['first_name'] = $first; }
                    if (schema_table_has_column($this->db, 'users', 'last_name')) { $data['last_name'] = $last; }
                    if (schema_table_has_column($this->db, 'users', 'full_name')) { $data['full_name'] = $full_name; }
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
                    $message = '<p>Hello'.($full_name ? ' '.esc_view($full_name) : '').',</p>';
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
            auth_login_attempts_clear($this->db, 'otp:' . strtolower($email), auth_client_ip());
            $this->session->set_flashdata('success', 'Account created successfully! You can now login with your credentials.');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/register');
    }

    /**
     * Shared helper to read roles from roles table (id => name) with fallback defaults.
     * @return array<int,string>
     */
    private function roles(){
        $out = [];
        if (isset($this->db) && $this->db->table_exists('roles')) {
            $this->db->from('roles');
            if (schema_table_has_column($this->db, 'roles', 'is_active')) {
                $this->db->where('is_active', 1);
            }
            if (schema_table_has_column($this->db, 'roles', 'sort_order')) {
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
