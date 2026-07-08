<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->model('Face_model', 'faces');
        $this->load->model('Employee_model');
        $this->load->model('Shift_model');
        $this->load->helper(['url', 'form', 'permission', 'hierarchy_filter','schema_columns']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_controller_access('users', true);
    }

    public function index() {
        require_module_access(['users_list', 'users'], true);
        $q = trim($this->input->get('q', true) ?: '');
        $userTab = $this->input->get('tab');
        $userTab = ($userTab === 'inactive') ? 'inactive' : 'active';
        $data['title'] = 'Users';
        $data['q'] = $q;
        $data['user_tab'] = $userTab;
        $roleFilter = null;
        $userIdFilter = null;
        $currentUserId = (int)$this->session->userdata('user_id');
        // Admin sees all; others see own record only (unless explicit view-all permission)
        if (!function_exists('data_scope_sees_all_org_data') || !data_scope_sees_all_org_data()) {
            if (!has_module_access('users_view_all') && $currentUserId > 0) {
                $userIdFilter = $currentUserId;
            }
        }
        $rows = $this->users->list_users($q, 250, $roleFilter, $userIdFilter, $userTab);
        
        // Check face registration status for each user
        $this->faces->ensure_schema();
        foreach ($rows as $row) {
            $faceRecord = $this->faces->get_by_user((int)$row->id);
            $row->face_registered = ($faceRecord && !empty($faceRecord->descriptor)) ? true : false;
            $row->face_registered_date = $faceRecord ? $faceRecord->created_at : null;
        }
        
        $data['rows'] = $rows;
        $data['roles'] = $this->roles(); // Pass roles array to view for consistent display
        $this->load->view('users/index', $data);
    }

    public function check_email() {
        $email = trim($this->input->post('email', true));
        header('Content-Type: application/json');
        
        if (empty($email)) {
            echo json_encode(['exists' => false]);
            return;
        }
        
        $exists = $this->users->email_exists($email);
        echo json_encode(['exists' => $exists]);
    }
    
    public function check_phone() {
        $phone = trim($this->input->post('phone', true));
        header('Content-Type: application/json');
        
        if (empty($phone)) {
            echo json_encode(['exists' => false]);
            return;
        }
        
        $exists = $this->users->phone_exists($phone);
        echo json_encode(['exists' => $exists]);
    }

    public function create() {
        require_module_access(['users_add', 'users'], true);
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
                'notify_attendance' => 1,
            ],
            'is_edit' => false,
            'roles' => $this->roles(),
            'shifts' => $this->Shift_model->get_all(true),
        ];
        $this->load->view('users/form', $data);
    }

    public function store() {
        require_module_access(['users_add', 'users'], true);
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
        if (schema_table_has_column($this->db, 'users', 'phone') && $in['phone'] !== '') {
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
        if (schema_table_has_column($this->db, 'users', 'phone') && $in['phone'] !== '' && $this->users->phone_exists($in['phone'])) {
            $this->session->set_flashdata('error', 'Mobile number already exists.');
            redirect('users/create');
            return;
        }
        // Prepare data for DB with column-awareness
        $data = $this->_prepare_db_payload($in, true);
        // Handle avatar upload
        $avatarPath = $this->_handle_avatar_upload();
        if ($avatarPath && schema_table_has_column($this->db, 'users', 'avatar')) { $data['avatar'] = $avatarPath; }
        
        // Auto-track creation
        $this->load->helper('change_tracker');
        
        $ok = $this->users->insert($data);
        if ($ok) {
            $new_id = $this->db->insert_id();
            if ($new_id) {
                $description = 'User: ' . (isset($data['name']) ? $data['name'] : (isset($data['email']) ? $data['email'] : 'User #' . $new_id));
                auto_track_insert('users', $new_id, $data, $description);

                // Handle Shift Assignment (Create Employee Record if needed)
                $shift_id = $this->input->post('shift_id');
                if ($shift_id) {
                    $emp = $this->Employee_model->get_by_user_id($new_id);
                    if (!$emp) {
                        // Create new employee record
                        $nameParts = explode(' ', trim($data['name']), 2);
                        $fname = $nameParts[0];
                        $lname = isset($nameParts[1]) ? $nameParts[1] : '';
                        $empData = [
                            'user_id' => $new_id,
                            'emp_code' => $this->Employee_model->generate_emp_code(),
                            'first_name' => $fname,
                            'last_name' => $lname,
                            'personal_email' => $data['email'],
                            'shift_id' => (int)$shift_id
                        ];
                        $this->Employee_model->create($empData);
                    } else {
                        // Update existing
                        $this->Employee_model->update($emp->id, ['shift_id' => (int)$shift_id]);
                    }
                }
            }
            $this->session->unset_userdata(['reg_email','reg_code_hash','reg_code_expires']);
        }
        if ($ok) {
            $success_msg = get_notification_message('users', 'create', 'success');
            $this->session->set_flashdata('success', $success_msg);
        } else {
            $error_msg = get_notification_message('users', 'create', 'error');
            $this->session->set_flashdata('error', $error_msg);
        }
        redirect('users');
    }

    public function edit($id = null) {
        require_module_access(['users_edit', 'users'], true);
        $id = (int)$id;
        $allowed_ids = get_accessible_hierarchy_user_ids();
        if (!empty($allowed_ids) && !in_array($id, $allowed_ids, true)) { show_error('Forbidden', 403); }
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        
        // Check if face is already registered
        $this->faces->ensure_schema();
        $faceRecord = $this->faces->get_by_user($id);
        $row->face_registered = ($faceRecord && !empty($faceRecord->descriptor)) ? true : false;
        $row->face_registered_date = $faceRecord ? $faceRecord->created_at : null;
        $row->face_image = ($faceRecord && !empty($faceRecord->image_path)) ? $faceRecord->image_path : null;
        
        $data = [
            'title' => 'Edit User',
            'row' => $row,
            'is_edit' => true,
            'roles' => $this->roles(),
            'shifts' => $this->Shift_model->get_all(true),
        ];
        $employee = $this->Employee_model->get_by_user_id($id);
        $data['current_shift_id'] = $employee ? $employee->shift_id : null;
        $this->load->view('users/form', $data);
    }

    public function update($id = null) {
        require_module_access(['users_edit', 'users'], true);
        $id = (int)$id;
        $allowed_ids = get_accessible_hierarchy_user_ids();
        if (!empty($allowed_ids) && !in_array($id, $allowed_ids, true)) { show_error('Forbidden', 403); }
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
        if (schema_table_has_column($this->db, 'users', 'phone') && $in['phone'] !== '') {
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
        if (schema_table_has_column($this->db, 'users', 'phone') && $in['phone'] !== '' && $this->users->phone_exists($in['phone'], $id)) {
            $this->session->set_flashdata('error', 'Mobile number already exists.');
            redirect('users/edit/'.$id);
            return;
        }
        // Validate password strength if password is being changed
        $password = $in['password'];
        if (!empty($password)) {
            $this->load->helper('password');
            if (function_exists('validate_password_strength')) {
                $validation = validate_password_strength($password);
                if (!$validation['valid']) {
                    $this->session->set_flashdata('error', implode(' ', $validation['errors']));
                    redirect('users/edit/'.$id);
                    return;
                }
            }
        }

        // Prepare data for DB with column-awareness
        $data = $this->_prepare_db_payload($in, false);
        // Handle avatar upload (replace if new file uploaded)
        $avatarPath = $this->_handle_avatar_upload();
        if ($avatarPath && schema_table_has_column($this->db, 'users', 'avatar')) { $data['avatar'] = $avatarPath; }
        
        // Auto-track changes before update
        $this->load->helper('change_tracker');
        $old_data = track_changes_before('users', $id);
        
        $ok = $this->users->update($id, $data);
        
        // Auto-track changes after update
        if ($ok && $old_data) {
            $description = 'User: ' . (isset($data['name']) ? $data['name'] : (isset($row->name) ? $row->name : 'User #' . $id));
            track_changes_after('users', 'users', $id, $old_data, $data, $description);
        }

        // Handle Shift Assignment
        if ($ok) {
            $shift_id = $this->input->post('shift_id');
            // Check if shift_id was actually posted (to distinguish from not set)
            if ($this->input->post('shift_id') !== null) {
                $shift_id = (int)$shift_id ?: null; // Handle empty string as null
                $emp = $this->Employee_model->get_by_user_id($id);
                if (!$emp && $shift_id) {
                    // Create new employee record if assigning a shift
                    $nameVal = isset($data['name']) ? $data['name'] : $row->name;
                    $emailVal = isset($data['email']) ? $data['email'] : $row->email;
                    $nameParts = explode(' ', trim($nameVal), 2);
                    $fname = $nameParts[0];
                    $lname = isset($nameParts[1]) ? $nameParts[1] : '';
                    $empData = [
                        'user_id' => $id,
                        'emp_code' => $this->Employee_model->generate_emp_code(),
                        'first_name' => $fname,
                        'last_name' => $lname,
                        'personal_email' => $emailVal,
                        'shift_id' => $shift_id
                    ];
                    $this->Employee_model->create($empData);
                } elseif ($emp) {
                    // Update existing
                    $this->Employee_model->update($emp->id, ['shift_id' => $shift_id]);
                }
            }
        }
        
        if ($ok) {
            $success_msg = get_notification_message('users', 'update', 'success');
            $this->session->set_flashdata('success', $success_msg);
        } else {
            $error_msg = get_notification_message('users', 'update', 'error');
            $this->session->set_flashdata('error', $error_msg);
        }
        redirect('users');
    }

    public function delete($id = null) {
        require_module_access(['users_delete', 'users'], true);
        $this->load->helper('change_tracker');
        $id = (int)$id;
        $allowed_ids = get_accessible_hierarchy_user_ids();
        if (!empty($allowed_ids) && !in_array($id, $allowed_ids, true)) { show_error('Forbidden', 403); }
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        $data = ['title' => 'Delete User', 'row' => $row];
        $this->load->view('users/confirm_delete', $data);
    }

    public function destroy($id = null) {
        // Destructive actions must be POST only
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $id = (int)$id;
        $allowed_ids = get_accessible_hierarchy_user_ids();
        if (!empty($allowed_ids) && !in_array($id, $allowed_ids, true)) { show_error('Forbidden', 403); }
        $row = $this->users->find($id);
        if (!$row) { show_404(); }
        
        // Auto-track deletion
        $this->load->helper('change_tracker');
        $old_data = (array)$row;
        
        $ok = $this->users->delete($id);
        if ($ok) {
            $description = 'User: ' . (isset($row->name) ? $row->name : (isset($row->email) ? $row->email : 'User #' . $id));
            auto_track_delete('users', $id, $old_data, $description);
        }
        
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
        $in['notify_attendance'] = (int)($this->input->post('notify_attendance', true) !== null ? $this->input->post('notify_attendance', true) : 1);
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
            if (schema_table_has_column($this->db, 'roles', 'is_active')) {
                $this->db->where('is_active', 1);
            }
            if (schema_table_has_column($this->db, 'roles', 'sort_order')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            $rows = $this->db->get()->result();

            $filterUserGroupOnly = false;
            if (function_exists('is_user_group') && schema_table_has_column($this->db, 'roles', 'group_type')) {
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
        if (schema_table_has_column($this->db, 'users', 'role')) { $data['role'] = $in['role']; }
        if (schema_table_has_column($this->db, 'users', 'role_id')) { $data['role_id'] = (int)$in['role_id']; }
        if (schema_table_has_column($this->db, 'users', 'status')) { $data['status'] = $status; }
        if (schema_table_has_column($this->db, 'users', 'phone')) { $data['phone'] = $in['phone']; }
        if (schema_table_has_column($this->db, 'users', 'is_verified')) { $data['is_verified'] = (int)$in['is_verified']; }
        if (!empty($in['password'])) { $data['password_hash'] = password_hash($in['password'], PASSWORD_DEFAULT); }
        if ($is_create && schema_table_has_column($this->db, 'users', 'created_at')) { $data['created_at'] = date('Y-m-d H:i:s'); }
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
        if ($ok) { 
            $success_msg = get_notification_message('users', 'delete', 'success');
            $this->session->set_flashdata('success', $success_msg);
        } else { 
            $error_msg = get_notification_message('users', 'delete', 'error');
            $this->session->set_flashdata('error', $error_msg);
        }
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

        // Allow: the user editing their own face, OR anyone with users_edit / users permission
        $user_id = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
        $currentUserId = (int)$this->session->userdata('user_id');
        $is_self = ($currentUserId === $user_id);
        if (!$is_self) {
            require_module_access(['users_edit', 'users'], true);
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
                if ($bin !== false && strlen($bin) <= 2097152) {
                    $ext = 'png';
                    if (strpos($meta, 'jpeg') !== false || strpos($meta, 'jpg') !== false) { $ext = 'jpg'; }
                    elseif (strpos($meta, 'webp') !== false) { $ext = 'webp'; }
                    $dir = FCPATH.'uploads/faces/';
                    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
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

    public function view($id = null) {
        if (!$id || !is_numeric($id)) {
            show_404();
        }
        
        $id = (int)$id;
        $allowed_ids = get_accessible_hierarchy_user_ids();
        if (!empty($allowed_ids) && !in_array($id, $allowed_ids, true)) { show_error('Forbidden', 403); }
        $user = $this->users->get($id);
        
        if (!$user) {
            show_404();
        }
        
        // Check permissions - users can view their own profile, others need users permission
        $currentUserId = (int)$this->session->userdata('user_id');
        if ($currentUserId !== $id && !is_admin_group() && !has_module_access('users_view_all')) {
            require_module_access(['users_list', 'users'], true);
        }
        
        // Get additional user information
        $data['user'] = $user;
        $data['title'] = 'View User: ' . esc_view($user->name);
        $data['roles'] = $this->roles();
        
        // Get employee information if exists
        if ($this->db->table_exists('employees')) {
            $employee = $this->db->where('user_id', $id)->get('employees')->row();
            $data['employee'] = $employee;
        }
        
        // Get face information if exists
        $this->faces->ensure_schema();
        $face = $this->faces->get_by_user($id);
        $data['face'] = $face;
        
        $this->load->view('users/view', $data);
    }
}
