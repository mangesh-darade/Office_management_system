<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->helper(['url', 'form']);
        $this->load->library(['session']);
        // Basic auth gate: redirect to login if not logged in
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    public function index() {
        $q = trim($this->input->get('q', true) ?: '');
        $data['title'] = 'Users';
        $data['q'] = $q;
        $data['rows'] = $this->users->list_users($q, 250);
        $this->load->view('users/index', $data);
    }

    public function create() {
        $data = [
            'title' => 'Add User',
            'row' => (object)[
                'id' => null,
                'name' => '',
                'email' => '',
                'role' => 'user',
                'status' => 1,
            ],
            'is_edit' => false
        ];
        $this->load->view('users/form', $data);
    }

    public function store() {
        $in = $this->_sanitize();
        if (empty($in['name']) || empty($in['email'])) {
            $this->session->set_flashdata('error', 'Name and Email are required.');
            redirect('users/create');
            return;
        }
        if (!empty($in['password'])) {
            $in['password_hash'] = password_hash($in['password'], PASSWORD_DEFAULT);
        }
        unset($in['password']);
        $ok = $this->users->insert($in);
        $this->_flash_redirect($ok, 'User created', 'users');
    }

    public function edit($id = null) {
        $id = (int)$id;
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $data = [
            'title' => 'Edit User',
            'row' => $row,
            'is_edit' => true
        ];
        $this->load->view('users/form', $data);
    }

    public function update($id = null) {
        $id = (int)$id;
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $in = $this->_sanitize();
        if (empty($in['name']) || empty($in['email'])) {
            $this->session->set_flashdata('error', 'Name and Email are required.');
            redirect('users/edit/'.$id);
            return;
        }
        if (!empty($in['password'])) {
            $in['password_hash'] = password_hash($in['password'], PASSWORD_DEFAULT);
        }
        unset($in['password']);
        $ok = $this->users->update($id, $in);
        $this->_flash_redirect($ok, 'User updated', 'users');
    }

    public function delete($id = null) {
        $id = (int)$id;
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $data = ['title' => 'Delete User', 'row' => $row];
        $this->load->view('users/confirm_delete', $data);
    }

    public function destroy($id = null) {
        $id = (int)$id;
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $ok = $this->users->delete($id);
        $this->_flash_redirect($ok, 'User deleted', 'users');
    }

    private function _sanitize() {
        $in = [];
        $in['name'] = trim($this->input->post('name', true) ?: '');
        $in['email'] = trim($this->input->post('email', true) ?: '');
        $in['role'] = trim($this->input->post('role', true) ?: 'user');
        $in['status'] = (int)($this->input->post('status', true) !== null ? $this->input->post('status', true) : 1);
        $in['password'] = trim($this->input->post('password') ?: '');
        return $in;
    }

    private function _flash_redirect($ok, $msg, $to) {
        if ($ok) { $this->session->set_flashdata('success', $msg); }
        else { $this->session->set_flashdata('error', 'Operation failed'); }
        redirect($to);
    }
}
