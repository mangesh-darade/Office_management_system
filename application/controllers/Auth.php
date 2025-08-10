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
            $id = $this->User_model->create([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $role_id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->session->set_flashdata('success', 'Account created. Please login.');
            redirect('auth/login');
            return;
        }
        $this->load->view('auth/register');
    }
}
