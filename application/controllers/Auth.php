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
            $email = trim($this->input->post('email'));
            $password = (string)$this->input->post('password');
            $user = $this->User_model->get_by_email($email);
            if ($user && password_verify($password, $user->password_hash)) {
                if (isset($user->status) && $user->status !== 'active') {
                    $this->session->set_flashdata('error', 'Account inactive.');
                    redirect('auth/login');
                    return;
                }
                $this->session->set_userdata('user_id', (int)$user->id);
                $this->session->set_userdata('role_id', (int)$user->role_id);
                $this->session->set_userdata('email', $user->email);
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
            $full_name = trim($this->input->post('name'));
            $email = trim($this->input->post('email'));
            $password = (string)$this->input->post('password');
            $role_id = (int)$this->input->post('role_id');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !$role_id) {
                $this->session->set_flashdata('error', 'Invalid input');
                redirect('auth/register');
                return;
            }
            $exists = $this->User_model->get_by_email($email);
            if ($exists) {
                $this->session->set_flashdata('error', 'Email already registered');
                redirect('auth/register');
                return;
            }
            $data = array(
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $role_id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            );
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
