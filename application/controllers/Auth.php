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

    public function register(){
        if ($this->input->method() === 'post') {
            $full_name = trim((string)$this->input->post('name'));
            $email     = trim((string)$this->input->post('email'));
            $phone     = trim((string)$this->input->post('phone'));
            $password  = (string)$this->input->post('password');
            $role_id   = (int)$this->input->post('role_id');

            // Field-level validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('error', 'Please enter a valid email address.');
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
            $this->session->set_flashdata('success', 'Account created. Please login.');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/register');
    }
}
