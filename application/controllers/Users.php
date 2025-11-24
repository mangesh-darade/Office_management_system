<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->model('Face_model', 'faces');
        $this->load->helper(['url', 'form', 'permission']);
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
        $roleFilter = null;
        $userIdFilter = null;
        $currentUserId = (int)$this->session->userdata('user_id');
        // For user-group (staff), only show their own record in the list
        if (function_exists('is_user_group') && is_user_group() && $currentUserId > 0) {
            $userIdFilter = $currentUserId;
        }
        $data['rows'] = $this->users->list_users($q, 250, $roleFilter, $userIdFilter);
        $this->load->view('users/index', $data);
    }

    public function create() {
        if (!function_exists('has_module_access') || !has_module_access('users_add')) {
            show_error('You do not have permission to add users.', 403);
        }
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
            'is_edit' => false,
            'roles' => $this->roles(),
        ];
        $this->load->view('users/form', $data);
    }

    public function store() {
        if (!function_exists('has_module_access') || !has_module_access('users_add')) {
            show_error('You do not have permission to add users.', 403);
        }
        $in = $this->_sanitize();
        if (empty($in['name']) || empty($in['email'])) {
            $this->session->set_flashdata('error', 'Name and Email are required.');
            redirect('users/create');
            return;
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Please enter a valid email address.');
            redirect('users/create');
            return;
        }
        // Enforce Gmail-only and verification code similar to auth/register
        $verify_code = trim((string)$this->input->post('verify_code'));
        $domain = '';
        if (strpos($in['email'], '@') !== false) {
            $parts = explode('@', $in['email']);
            $domain = isset($parts[1]) ? strtolower(trim($parts[1])) : '';
        }
        if ($domain !== 'gmail.com' && $domain !== 'googlemail.com') {
            $this->session->set_flashdata('error', 'Please use a Gmail address (example@gmail.com).');
            redirect('users/create');
            return;
        }
        if ($domain !== '' && function_exists('checkdnsrr')) {
            $hasMx = @checkdnsrr($domain, 'MX');
            $hasA  = @checkdnsrr($domain, 'A');
            if (!$hasMx && !$hasA) {
                $this->session->set_flashdata('error', 'Email domain does not appear to be valid.');
                redirect('users/create');
                return;
            }
        }
        $this->load->library('session');
        $sessionEmail = (string)$this->session->userdata('reg_email');
        $sessionHash  = (string)$this->session->userdata('reg_code_hash');
        $sessionExp   = (int)$this->session->userdata('reg_code_expires');
        if ($sessionEmail === '' || strcasecmp($sessionEmail, $in['email']) !== 0) {
            $this->session->set_flashdata('error', 'Please request a verification code for this email first.');
            redirect('users/create');
            return;
        }
        if ($verify_code === '') {
            $this->session->set_flashdata('error', 'Please enter the verification code sent to this Gmail.');
            redirect('users/create');
            return;
        }
        if (!$sessionHash || !$sessionExp || time() > $sessionExp) {
            $this->session->set_flashdata('error', 'Verification code has expired. Please request a new code.');
            redirect('users/create');
            return;
        }
        if (!password_verify($verify_code, $sessionHash)) {
            $this->session->set_flashdata('error', 'Invalid verification code.');
            redirect('users/create');
            return;
        }
        // Validate role and status
        $roles = $this->roles();
        $roleIdPost = $this->input->post('role_id', true);
        $statusPost = $this->input->post('status', true);
        if ($roleIdPost === null || $roleIdPost === '') {
            $this->session->set_flashdata('error', 'Role is required.');
            redirect('users/create');
            return;
        }
        $roleId = (int)$roleIdPost;
        if (!isset($roles[$roleId])) {
            $this->session->set_flashdata('error', 'Please select a valid role.');
            redirect('users/create');
            return;
        }
        if ($statusPost === null || $statusPost === '') {
            $this->session->set_flashdata('error', 'Status is required.');
            redirect('users/create');
            return;
        }
        if (!in_array((string)$statusPost, ['0','1'], true)) {
            $this->session->set_flashdata('error', 'Please select a valid status.');
            redirect('users/create');
            return;
        }
        if ($this->db->field_exists('phone', 'users')) {
            if ($in['phone'] === '') {
                $this->session->set_flashdata('error', 'Mobile number is required.');
                redirect('users/create');
                return;
            }
            if (!preg_match('/^[0-9]{10}$/', $in['phone'])) {
                $this->session->set_flashdata('error', 'Please enter a valid 10-digit mobile number.');
                redirect('users/create');
                return;
            }
        }
        // Enforce unique email and phone at application level
        if ($this->users->email_exists($in['email'])) {
            $this->session->set_flashdata('error', 'Email already exists.');
            redirect('users/create');
            return;
        }
        if ($this->db->field_exists('phone', 'users') && $in['phone'] !== '' && $this->users->phone_exists($in['phone'])) {
            $this->session->set_flashdata('error', 'Mobile number already exists.');
            redirect('users/create');
            return;
        }
        // Prepare data for DB with column-awareness
        $data = $this->_prepare_db_payload($in, true);
        // Handle avatar upload
        $avatarPath = $this->_handle_avatar_upload();
        if ($avatarPath && $this->db->field_exists('avatar', 'users')) { $data['avatar'] = $avatarPath; }
        $ok = $this->users->insert($data);
        if ($ok) {
            $this->session->unset_userdata(['reg_email','reg_code_hash','reg_code_expires']);
        }
        $this->_flash_redirect($ok, 'User created', 'users');
    }

    public function edit($id = null) {
        $id = (int)$id;
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $data = [
            'title' => 'Edit User',
            'row' => $row,
            'is_edit' => true,
            'roles' => $this->roles(),
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
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Please enter a valid email address.');
            redirect('users/edit/'.$id);
            return;
        }
        // Validate role and status
        $roles = $this->roles();
        $roleIdPost = $this->input->post('role_id', true);
        $statusPost = $this->input->post('status', true);
        if ($roleIdPost === null || $roleIdPost === '') {
            $this->session->set_flashdata('error', 'Role is required.');
            redirect('users/edit/'.$id);
            return;
        }
        $roleId = (int)$roleIdPost;
        if (!isset($roles[$roleId])) {
            $this->session->set_flashdata('error', 'Please select a valid role.');
            redirect('users/edit/'.$id);
            return;
        }
        if ($statusPost === null || $statusPost === '') {
            $this->session->set_flashdata('error', 'Status is required.');
            redirect('users/edit/'.$id);
            return;
        }
        if (!in_array((string)$statusPost, ['0','1'], true)) {
            $this->session->set_flashdata('error', 'Please select a valid status.');
            redirect('users/edit/'.$id);
            return;
        }
        if ($this->db->field_exists('phone', 'users')) {
            if ($in['phone'] === '') {
                $this->session->set_flashdata('error', 'Mobile number is required.');
                redirect('users/edit/'.$id);
                return;
            }
            if (!preg_match('/^[0-9]{10}$/', $in['phone'])) {
                $this->session->set_flashdata('error', 'Please enter a valid 10-digit mobile number.');
                redirect('users/edit/'.$id);
                return;
            }
        }
        // Enforce unique email and phone when updating (ignore current user)
        if ($this->users->email_exists($in['email'], $id)) {
            $this->session->set_flashdata('error', 'Email already exists.');
            redirect('users/edit/'.$id);
            return;
        }
        if ($this->db->field_exists('phone', 'users') && $in['phone'] !== '' && $this->users->phone_exists($in['phone'], $id)) {
            $this->session->set_flashdata('error', 'Mobile number already exists.');
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
        $in['role_id'] = (int)($this->input->post('role_id', true) ?: 0);
        // Derive role string from roles table (fallback to lowercase name)
        $roles = $this->roles();
        $roleName = isset($roles[$in['role_id']]) ? $roles[$in['role_id']] : '';
        $in['role'] = $roleName !== '' ? strtolower(str_replace(' ', '_', $roleName)) : '';
        $in['status'] = $this->input->post('status', true) !== null ? $this->input->post('status', true) : 1; // raw; normalize later
        $in['phone'] = trim($this->input->post('phone', true) ?: '');
        $in['is_verified'] = (int)($this->input->post('is_verified', true) !== null ? $this->input->post('is_verified', true) : 0);
        $in['password'] = trim($this->input->post('password') ?: '');
        return $in;
    }

    /**
     * Fetch role labels from roles table when available.
     * Falls back to default mapping if table is missing or empty.
     * @return array<int,string>
     */
    private function roles(){
        $out = [];
        if ($this->db->table_exists('roles')) {
            $this->db->from('roles');
            if ($this->db->field_exists('is_active', 'roles')) {
                $this->db->where('is_active', 1);
            }
            if ($this->db->field_exists('sort_order', 'roles')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            $rows = $this->db->get()->result();

            $filterUserGroupOnly = false;
            if (function_exists('is_user_group') && $this->db->field_exists('group_type', 'roles')) {
                // If the currently logged-in user belongs to user group, hide admin-group roles
                $filterUserGroupOnly = is_user_group();
            }

            foreach ($rows as $row) {
                $rid = isset($row->id) ? (int)$row->id : 0;
                if ($rid <= 0) { continue; }
                if ($filterUserGroupOnly) {
                    $gt = isset($row->group_type) ? strtolower(trim((string)$row->group_type)) : '';
                    if ($gt !== 'user') { continue; }
                }
                $out[$rid] = isset($row->name) ? (string)$row->name : ('Role #'.$rid);
            }
        }
        if (!empty($out)) { return $out; }

        // Fallback labels if roles table not available
        return [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Lead',
            4 => 'Staff',
        ];
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

    // POST /users/save_face (AJAX)
    public function save_face() {
        if ($this->input->method() !== 'post') { show_404(); }
        $this->output->set_content_type('application/json');

        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return $this->output->set_status_header(400)->set_output(json_encode(['ok' => false, 'error' => 'Invalid payload']));
        }

        $currentUserId = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        if (!$currentUserId) {
            return $this->output->set_status_header(401)->set_output(json_encode(['ok' => false, 'error' => 'Unauthorized']));
        }

        $user_id = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
        if ($user_id <= 0) {
            return $this->output->set_status_header(400)->set_output(json_encode(['ok' => false, 'error' => 'Missing user id']));
        }
        // Only admin/HR or the user themself can update face
        if (!in_array($role_id, [1,2], true) && $currentUserId !== $user_id) {
            return $this->output->set_status_header(403)->set_output(json_encode(['ok' => false, 'error' => 'Forbidden']));
        }

        $descriptor = isset($payload['descriptor']) ? (string)$payload['descriptor'] : '';
        $imageData = isset($payload['image']) ? (string)$payload['image'] : '';
        if ($descriptor === '' || $imageData === '') {
            return $this->output->set_status_header(400)->set_output(json_encode(['ok' => false, 'error' => 'Descriptor or image missing']));
        }

        // Decode and store image
        $imagePath = null;
        if (strpos($imageData, 'data:image') === 0) {
            $parts = explode(',', $imageData, 2);
            if (count($parts) === 2) {
                $meta = $parts[0];
                $bin = base64_decode($parts[1]);
                if ($bin !== false) {
                    $ext = 'png';
                    if (strpos($meta, 'jpeg') !== false || strpos($meta, 'jpg') !== false) { $ext = 'jpg'; }
                    elseif (strpos($meta, 'webp') !== false) { $ext = 'webp'; }
                    $dir = FCPATH.'uploads/faces/';
                    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                    $file = 'face_'.$user_id.'_'.time().'.'.$ext;
                    if (@file_put_contents($dir.$file, $bin) !== false) {
                        $imagePath = 'uploads/faces/'.$file;
                    }
                }
            }
        }

        if ($imagePath === null) {
            return $this->output->set_status_header(500)->set_output(json_encode(['ok' => false, 'error' => 'Failed to store image']));
        }

        $this->faces->save_user_face($user_id, $descriptor, $imagePath);
        return $this->output->set_output(json_encode(['ok' => true]));
    }
}
