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
                'phone' => '',
                'is_verified' => 0,
                'avatar' => '',
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
        // Prepare data for DB with column-awareness
        $data = $this->_prepare_db_payload($in, true);
        // Handle avatar upload
        $avatarPath = $this->_handle_avatar_upload();
        if ($avatarPath && $this->db->field_exists('avatar', 'users')) { $data['avatar'] = $avatarPath; }
        $ok = $this->users->insert($data);
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
        // Prepare data for DB with column-awareness
        $data = $this->_prepare_db_payload($in, false);
        // Handle avatar upload (replace if new file uploaded)
        $avatarPath = $this->_handle_avatar_upload();
        if ($avatarPath && $this->db->field_exists('avatar', 'users')) { $data['avatar'] = $avatarPath; }
        $ok = $this->users->update($id, $data);
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
        $in['role_id'] = (int)($this->input->post('role_id', true) ?: 4);
        // Derive role string from role_id for consistency
        $roleMap = [1=>'admin', 2=>'hr', 3=>'lead', 4=>'employee'];
        $in['role'] = isset($roleMap[$in['role_id']]) ? $roleMap[$in['role_id']] : 'employee';
        $in['status'] = $this->input->post('status', true) !== null ? $this->input->post('status', true) : 1; // raw; normalize later
        $in['phone'] = trim($this->input->post('phone', true) ?: '');
        $in['is_verified'] = (int)($this->input->post('is_verified', true) !== null ? $this->input->post('is_verified', true) : 0);
        $in['password'] = trim($this->input->post('password') ?: '');
        return $in;
    }

    private function _prepare_db_payload($in, $is_create = false){
        // Normalize status depending on DB column type (string vs tinyint)
        $statusVal = $in['status'];
        $useInt = false;
        try {
            $fields = $this->db->field_data('users');
            foreach ($fields as $f) {
                if ($f->name === 'status') { $useInt = in_array(strtolower($f->type), ['tinyint','int','smallint','mediumint','bigint'], true); break; }
            }
        } catch (Exception $e) { /* ignore */ }
        if ($useInt) { $status = (int)$statusVal === 1 ? 1 : 0; }
        else { $status = ((string)$statusVal === '1') ? 'active' : 'inactive'; }

        $data = [
            'name' => $in['name'],
            'email' => $in['email'],
        ];
        if ($this->db->field_exists('role','users')) { $data['role'] = $in['role']; }
        if ($this->db->field_exists('role_id','users')) { $data['role_id'] = (int)$in['role_id']; }
        if ($this->db->field_exists('status','users')) { $data['status'] = $status; }
        if ($this->db->field_exists('phone','users')) { $data['phone'] = $in['phone']; }
        if ($this->db->field_exists('is_verified','users')) { $data['is_verified'] = (int)$in['is_verified']; }
        if (!empty($in['password'])) { $data['password_hash'] = password_hash($in['password'], PASSWORD_DEFAULT); }
        if ($is_create && $this->db->field_exists('created_at','users')) { $data['created_at'] = date('Y-m-d H:i:s'); }
        return $data;
    }

    private function _handle_avatar_upload(){
        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) { return null; }
        if ((int)$_FILES['avatar']['error'] !== UPLOAD_ERR_OK) { return null; }
        $tmp = $_FILES['avatar']['tmp_name'];
        $name = $_FILES['avatar']['name'];
        $type = @mime_content_type($tmp);
        $allowed = ['image/png','image/jpeg','image/gif','image/webp'];
        if ($type && !in_array($type, $allowed, true)) { return null; }
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $dir = FCPATH.'assets/uploads/avatars/';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $file = uniqid('ava_').'.'.strtolower($ext ?: 'jpg');
        $dest = $dir.$file;
        if (@move_uploaded_file($tmp, $dest)) {
            return 'assets/uploads/avatars/'.$file;
        }
        return null;
    }

    private function _flash_redirect($ok, $msg, $to) {
        if ($ok) { $this->session->set_flashdata('success', $msg); }
        else { $this->session->set_flashdata('error', 'Operation failed'); }
        redirect($to);
    }
}
