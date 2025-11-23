<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('User_model');
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
        if ($this->input->method() === 'post') {
            $identifier = trim($this->input->post('login'));
            $password = (string)$this->input->post('password');
            $user = $this->User_model->get_by_login($identifier);
            if ($user && password_verify($password, $user->password_hash)) {
                if (isset($user->status) && $user->status !== 'active') {
                    $this->session->set_flashdata('error', 'Account inactive.');
                    redirect('auth/login');
                    return;
                }
                // If email verification is enabled, block login until verified
                if (isset($this->db) && $this->db->field_exists('email_verified', 'users')) {
                    if (isset($user->email_verified) && (int)$user->email_verified !== 1) {
                        $this->session->set_flashdata('error', 'Please verify your email address before logging in.');
                        redirect('auth/login');
                        return;
                    }
                }
                $this->session->set_userdata('user_id', (int)$user->id);
                $this->session->set_userdata('role_id', (int)$user->role_id);
                $this->session->set_userdata('email', $user->email);
                // Record last login timestamp and IP if columns exist
                try {
                    $now = date('Y-m-d H:i:s');
                    $ip  = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                    $data = [];
                    if ($this->db->field_exists('last_login', 'users')) { $data['last_login'] = $now; }
                    if ($this->db->field_exists('last_login_at', 'users')) { $data['last_login_at'] = $now; }
                    if ($this->db->field_exists('last_login_on', 'users')) { $data['last_login_on'] = $now; }
                    if ($this->db->field_exists('last_seen_at', 'users')) { $data['last_seen_at'] = $now; }
                    if ($this->db->field_exists('last_login_ip', 'users')) { $data['last_login_ip'] = $ip; }
                    if (!empty($data)) {
                        $this->db->where('id', (int)$user->id)->update('users', $data);
                    }
                } catch (Exception $e) { /* ignore logging errors */ }
                redirect('dashboard');
                return;
            }
            $this->session->set_flashdata('error', 'Invalid credentials');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/login');
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
            $this->email->clear(true);
            $from = $this->config->item('smtp_user');
            if (!$from) { $from = 'no-reply@example.com'; }
            $this->email->from($from, 'OfficeMgmt');
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

    public function register(){
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
            if ($sessionEmail === '' || strcasecmp($sessionEmail, $email) !== 0) {
                $this->session->set_flashdata('error', 'Please request a verification code for this email first.');
                redirect('auth/register');
                return;
            }
            if ($verify_code === '') {
                $this->session->set_flashdata('error', 'Please enter the verification code sent to your Gmail.');
                redirect('auth/register');
                return;
            }
            if (!$sessionHash || !$sessionExp || time() > $sessionExp) {
                $this->session->set_flashdata('error', 'Verification code has expired. Please request a new code.');
                redirect('auth/register');
                return;
            }
            if (!password_verify($verify_code, $sessionHash)) {
                $this->session->set_flashdata('error', 'Invalid verification code.');
                redirect('auth/register');
                return;
            }
            if ($phone === '') {
                $this->session->set_flashdata('error', 'Mobile number is required.');
                redirect('auth/register');
                return;
            }
            if (strlen($password) < 6) {
                $this->session->set_flashdata('error', 'Password must be at least 6 characters.');
                redirect('auth/register');
                return;
            }
            if (!$role_id) {
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
                $data['email_verified'] = 0;
            }
            // Optional flags: start as not verified for custom columns if present
            if ($this->db->field_exists('is_verified', 'users')) {
                $data['is_verified'] = 0;
            }
            if ($this->db->field_exists('is_verified1', 'users')) {
                $data['is_verified1'] = 0;
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
            $role_map = [1=>'admin', 2=>'hr', 3=>'lead', 4=>'employee'];
            if ($this->db->field_exists('role','users')) {
                $data['role'] = isset($role_map[$role_id]) ? $role_map[$role_id] : 'employee';
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

            // Send verification email if token/columns are available
            if ($id && $verifyToken) {
                try {
                    $this->config->load('email');
                    $this->load->library('email');
                    $this->email->clear(true);
                    $from = $this->config->item('smtp_user');
                    if (!$from) { $from = 'no-reply@example.com'; }
                    $this->email->from($from, 'OfficeMgmt');
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
            $this->session->set_flashdata('success', 'Account created. Please check your email for the verification link before logging in.');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/register');
    }
}
