<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employees extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','group_filter','permission']);
        $this->load->library(['session']);
        $this->load->model('Employee_model');
        
        // Check module access - redirect to dashboard if not allowed
        require_module_access('employees', true);
    }

    // GET /employees
    public function index()
    {
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Admin sees all, others see department-based
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            // For non-admin users, show department employees or redirect to own profile
            if (can_view_group_data($role_id)) {
                // Managers can see department employees
                $q = $this->input->get('q');
                $employees = $this->Employee_model->all(100, 0, $q, $filters);
                $data = [ 'employees' => $employees, 'q' => $q ];
                $this->load->view('employees/list', $data);
            } else {
                // Regular users redirected to own profile
                $row = $this->db->where('user_id', $user_id)->get('employees')->row();
                if ($row && isset($row->id)) { redirect('employees/'.(int)$row->id); return; }
                // If no employee row, guide the user
                $this->session->set_flashdata('error', 'Your employee profile is not set up yet. Please contact HR.');
                redirect('dashboard');
                return;
            }
        } else {
            // Admin sees all employees
            $q = $this->input->get('q');
            $employees = $this->Employee_model->all(100, 0, $q, []);
            $data = [ 'employees' => $employees, 'q' => $q ];
            $this->load->view('employees/list', $data);
        }
    }

    // GET /employees/create, POST /employees/create
    public function create()
    {
        // Check create permission specifically
        if (!function_exists('has_module_access') || !has_module_access('employees_add')) {
            show_error('You do not have permission to add employees.', 403);
        }
        
        // Only Admin/HR can create employee records
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) { 
            show_error('Forbidden', 403); 
        }
        if ($this->input->method() === 'post') {
            $dept_id = $this->input->post('department_id');
            $desg_id = $this->input->post('designation_id');
            $dept_name = trim($this->input->post('department'));
            $desg_name = trim($this->input->post('designation'));
            if ($dept_id && $this->db->table_exists('departments')){
                $d = $this->db->select('dept_name')->from('departments')->where('id', (int)$dept_id)->get()->row();
                if ($d) { $dept_name = $d->dept_name; }
            }
            if ($desg_id && $this->db->table_exists('designations')){
                $d = $this->db->select('designation_name')->from('designations')->where('id', (int)$desg_id)->get()->row();
                if ($d) { $desg_name = $d->designation_name; }
            }
            $uid_raw = (int)$this->input->post('user_id');
            $uid = $this->find_user_id($uid_raw);
            if (!$uid) {
                $this->session->set_flashdata('error', 'Please select a valid user for this employee.');
                redirect('employees/create');
                return;
            }

            $existing = $this->db->get_where('employees', ['user_id' => $uid])->row();
            if ($existing) {
                $this->session->set_flashdata('error', 'An employee record already exists for the selected user.');
                redirect('employees/'.(int)$existing->id);
                return;
            }
            
            // Auto-generate employee code if blank
            $emp_code = trim($this->input->post('emp_code'));
            if (empty($emp_code)) {
                $emp_code = $this->Employee_model->generate_emp_code();
            } else {
                // Check if code already exists
                if ($this->Employee_model->emp_code_exists($emp_code)) {
                    $this->session->set_flashdata('error', 'Employee Code already exists. Please use a different code or leave it blank to auto-generate.');
                    redirect('employees/create');
                    return;
                }
            }
            
            $payload = [
                'user_id' => $uid,
                'emp_code' => $emp_code,
                'first_name' => trim($this->input->post('first_name')),
                'last_name' => trim($this->input->post('last_name')),
                'department' => $dept_name,
                'designation' => $desg_name,
                'reporting_to' => $this->input->post('reporting_to') !== '' ? (int)$this->input->post('reporting_to') : null,
                'employment_type' => $this->input->post('employment_type') ?: 'full_time',
                'join_date' => $this->input->post('join_date') ?: null,
                'dob' => $this->input->post('dob') ?: null,
                'personal_email' => trim($this->input->post('personal_email')),
                'address' => trim($this->input->post('address')),
                'city' => trim($this->input->post('city')),
                'state' => trim($this->input->post('state')),
                'country' => trim($this->input->post('country')),
                'zipcode' => trim($this->input->post('zipcode')),
                'phone' => trim($this->input->post('phone')),
                'location' => trim($this->input->post('location')),
                'salary_ctc' => $this->input->post('salary_ctc') !== '' ? (float)$this->input->post('salary_ctc') : null,
                'emergency_contact_name' => trim($this->input->post('emergency_contact_name')),
                'emergency_contact_phone' => trim($this->input->post('emergency_contact_phone')),
                'bank_name' => trim($this->input->post('bank_name')),
                'bank_ac_no' => trim($this->input->post('bank_ac_no')),
                'pan_no' => trim($this->input->post('pan_no')),
            ];
            
            // Validate employee data
            $this->load->helper('validation');
            $validation = validate_employee_data($payload);
            if (!$validation['valid']) {
                $this->session->set_flashdata('error', implode(' ', $validation['errors']));
                redirect('employees/create');
                return;
            }
            
            try {
                $id = $this->Employee_model->create($payload);
            } catch (Exception $e) {
                log_message('error', 'Employee creation error: ' . $e->getMessage());
                $this->session->set_flashdata('error', 'Failed to create employee. Please try again.');
                redirect('employees/create');
                return;
            }
            $this->load->helper('activity');
            $fn = isset($payload['first_name']) ? $payload['first_name'] : '';
            $ln = isset($payload['last_name']) ? $payload['last_name'] : '';
            $name = trim($fn.' '.$ln);
            $desc = $name !== '' ? ('Employee: '.$name) : ('Employee code: '.$payload['emp_code']);
            log_activity('employees', 'created', (int)$id, $desc);
            $this->session->set_flashdata('success', 'Employee created');
            redirect('employees/'.$id);
            return;
        }
        $departments = [];
        $designations = [];
        if ($this->db->table_exists('departments')){
            $departments = $this->db->select('id, dept_name')->from('departments')->order_by('dept_name','ASC')->get()->result();
        }
        if ($this->db->table_exists('designations')){
            $designations = $this->db->select('id, designation_name, department_id')->from('designations')->order_by('designation_name','ASC')->get()->result();
        }
        $data = [
            'action' => 'create',
            'users' => $this->get_user_options(),
            'departments' => $departments,
            'designations' => $designations,
            // Pre-generate an employee code to show on the create form
            'generated_emp_code' => $this->Employee_model->generate_emp_code(),
        ];
        $this->load->view('employees/form', $data);
    }

    // GET /employees/{id}
    public function show($id)
    {
        $employee = $this->Employee_model->find((int)$id);
        if (!$employee) show_404();
        // Ownership check: non Admin/HR can view only their own record
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            $user_id = (int)$this->session->userdata('user_id');
            if ((int)$employee->user_id !== $user_id) { show_error('Forbidden', 403); }
        }
            // Check access to sensitive data
            $this->load->helper('validation');
            $role_id = (int)$this->session->userdata('role_id');
            $user_id = (int)$this->session->userdata('user_id');
            $can_view_sensitive = can_access_sensitive_employee_data($role_id, $user_id, $employee);
            
            $this->load->view('employees/view', [
                'employee' => $employee,
                'can_view_sensitive' => $can_view_sensitive
            ]);
    }

    // GET /employees/{id}/edit, POST /employees/{id}/edit
    public function edit($id)
    {
        // Check edit permission specifically
        if (!function_exists('has_module_access') || !has_module_access('employees_edit')) {
            show_error('You do not have permission to edit employees.', 403);
        }
        
        $employee = $this->Employee_model->find((int)$id);
        if (!$employee) show_404();
        // Ownership check: non Admin/HR can edit only their own record
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            $user_id = (int)$this->session->userdata('user_id');
            if ((int)$employee->user_id !== $user_id) { show_error('Forbidden', 403); }
        }

        if ($this->input->method() === 'post') {
            $dept_id = $this->input->post('department_id');
            $desg_id = $this->input->post('designation_id');
            $dept_name = trim($this->input->post('department'));
            $desg_name = trim($this->input->post('designation'));
            if ($dept_id && $this->db->table_exists('departments')){
                $d = $this->db->select('dept_name')->from('departments')->where('id', (int)$dept_id)->get()->row();
                if ($d) { $dept_name = $d->dept_name; }
            }
            if ($desg_id && $this->db->table_exists('designations')){
                $d = $this->db->select('designation_name')->from('designations')->where('id', (int)$desg_id)->get()->row();
                if ($d) { $desg_name = $d->designation_name; }
            }
            // Auto-generate employee code if blank
            $emp_code = trim($this->input->post('emp_code'));
            if (empty($emp_code)) {
                $emp_code = $this->Employee_model->generate_emp_code($employee->emp_code);
            } else {
                // Check if code already exists (excluding current employee)
                if ($this->Employee_model->emp_code_exists($emp_code, (int)$id)) {
                    $this->session->set_flashdata('error', 'Employee Code already exists. Please use a different code or leave it blank to auto-generate.');
                    redirect('employees/'.$id.'/edit');
                    return;
                }
            }
            
            $payload = [
                'emp_code' => $emp_code,
                'first_name' => trim($this->input->post('first_name')),
                'last_name' => trim($this->input->post('last_name')),
                'department' => $dept_name,
                'designation' => $desg_name,
                'reporting_to' => $this->input->post('reporting_to') !== '' ? (int)$this->input->post('reporting_to') : null,
                'employment_type' => $this->input->post('employment_type') ?: 'full_time',
                'join_date' => $this->input->post('join_date') ?: null,
                'dob' => $this->input->post('dob') ?: null,
                'personal_email' => trim($this->input->post('personal_email')),
                'address' => trim($this->input->post('address')),
                'city' => trim($this->input->post('city')),
                'state' => trim($this->input->post('state')),
                'country' => trim($this->input->post('country')),
                'zipcode' => trim($this->input->post('zipcode')),
                'phone' => trim($this->input->post('phone')),
                'location' => trim($this->input->post('location')),
                'salary_ctc' => $this->input->post('salary_ctc') !== '' ? (float)$this->input->post('salary_ctc') : null,
                'emergency_contact_name' => trim($this->input->post('emergency_contact_name')),
                'emergency_contact_phone' => trim($this->input->post('emergency_contact_phone')),
                'bank_name' => trim($this->input->post('bank_name')),
                'bank_ac_no' => trim($this->input->post('bank_ac_no')),
                'pan_no' => trim($this->input->post('pan_no')),
            ];
            // Track changes before update
            $this->load->helper(['activity', 'change_tracker']);
            $old_data = track_changes_before('employees', (int)$id);
            
            $this->Employee_model->update((int)$id, $payload);
            
            // Track changes after update - automatically logs what changed
            $fn = isset($payload['first_name']) ? $payload['first_name'] : '';
            $ln = isset($payload['last_name']) ? $payload['last_name'] : '';
            $name = trim($fn.' '.$ln);
            $desc = $name !== '' ? ('Employee: '.$name) : ('Employee #'.(int)$id);
            track_changes_after('employees', 'employees', (int)$id, $old_data, $payload, $desc);
            $this->session->set_flashdata('success', 'Employee updated');
            redirect('employees/'.$id);
            return;
        }
        $departments = [];
        $designations = [];
        if ($this->db->table_exists('departments')){
            $departments = $this->db->select('id, dept_name')->from('departments')->order_by('dept_name','ASC')->get()->result();
        }
        if ($this->db->table_exists('designations')){
            $designations = $this->db->select('id, designation_name, department_id')->from('designations')->order_by('designation_name','ASC')->get()->result();
        }
        $data = [
            'action' => 'edit',
            'employee' => $employee,
            'users' => $this->get_user_options(),
            'departments' => $departments,
            'designations' => $designations,
        ];
        $this->load->view('employees/form', $data);
    }

    // AJAX: Generate employee code
    // GET /employees/generate-emp-code
    public function generate_emp_code()
    {
        $this->output->set_content_type('application/json');
        
        $exclude_code = $this->input->get('exclude');
        $exclude_id = $this->input->get('exclude_id');
        
        try {
            // If exclude_id provided, use that employee's current code as exclude
            if ($exclude_id) {
                $employee = $this->Employee_model->find((int)$exclude_id);
                if ($employee && $employee->emp_code) {
                    $exclude_code = $employee->emp_code;
                }
            }
            
            $code = $this->Employee_model->generate_emp_code($exclude_code);
            
            $this->output->set_output(json_encode([
                'success' => true,
                'code' => $code
            ]));
        } catch (Exception $e) {
            log_message('error', 'Employee code generation error: ' . $e->getMessage());
            $this->output->set_output(json_encode([
                'success' => false,
                'error' => 'Failed to generate employee code.'
            ]));
        }
    }

    // POST /employees/{id}/delete
    public function delete($id)
    {
        // Check delete permission specifically
        if (!function_exists('has_module_access') || !has_module_access('employees_delete')) {
            show_error('You do not have permission to delete employees.', 403);
        }
        
        $this->Employee_model->delete((int)$id);
        $this->load->helper('activity');
        log_activity('employees', 'deleted', (int)$id, 'Employee deleted');
        $this->session->set_flashdata('success', 'Employee deleted');
        redirect('employees');
    }

    public function documents($id)
    {
        $employee = $this->Employee_model->find((int)$id);
        if (!$employee) show_404();
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            $user_id = (int)$this->session->userdata('user_id');
            if ((int)$employee->user_id !== $user_id) { show_error('Forbidden', 403); }
        }

        if ($this->input->method() === 'post') {
            try {
                // Validate file upload using secure helper
                $this->load->helper('upload');
                $document_file = isset($_FILES['document']) ? $_FILES['document'] : null;
                $validation = validate_uploaded_file($document_file, [
                    'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip'],
                    'max_size' => 10485760, // 10MB
                    'required' => true,
                    'check_mime' => true,
                    'check_content' => true
                ]);
                
                if (!$validation['valid']) {
                    $this->session->set_flashdata('error', implode(' ', $validation['errors']));
                    redirect('employees/'.$id.'/documents');
                    return;
                }
                
                $file_info = $validation['file_info'];
                
                // Create upload directory with secure permissions
                $upload_path = FCPATH.'uploads/employees/';
                if (!is_dir($upload_path)) {
                    if (!@mkdir($upload_path, 0755, true)) {
                        $this->session->set_flashdata('error', 'Unable to create upload directory.');
                        redirect('employees/'.$id.'/documents');
                        return;
                    }
                }
                
                // Generate secure filename
                $extension = $file_info['extension'];
                $secure_filename = bin2hex(random_bytes(16)) . '.' . $extension;
                $target_path = $upload_path . $secure_filename;
                
                // Move uploaded file
                if (!move_uploaded_file($file_info['tmp_name'], $target_path)) {
                    $this->session->set_flashdata('error', 'Failed to save uploaded file.');
                    redirect('employees/'.$id.'/documents');
                    return;
                }
                
                // Sanitize original filename
                $originalName = sanitize_filename($file_info['name']);
                
                // Save document record
                $doc_type = trim($this->input->post('doc_type'));
                $this->Employee_model->add_document([
                    'employee_id' => (int)$id,
                    'doc_type' => $doc_type !== '' ? $doc_type : null,
                    'original_name' => $originalName,
                    'file_name' => $secure_filename,
                    'file_path' => 'uploads/employees/'.$secure_filename,
                    'file_size' => $file_info['size'],
                    'file_type' => $file_info['type'],
                    'uploaded_by' => (int)$this->session->userdata('user_id'),
                    'uploaded_at' => date('Y-m-d H:i:s'),
                ]);
                
                // Log activity
                $this->load->helper('activity');
                log_activity('employees', 'document_uploaded', (int)$id, 'Document: ' . $originalName);
                
                $this->session->set_flashdata('success', 'Document uploaded successfully');
                redirect('employees/'.$id.'/documents');
                return;
            } catch (Exception $e) {
                log_message('error', 'Document upload error: ' . $e->getMessage());
                $this->session->set_flashdata('error', 'An error occurred while uploading the document.');
                redirect('employees/'.$id.'/documents');
                return;
            }
        }

        $documents = $this->Employee_model->get_documents((int)$id);
        $this->load->view('employees/documents', ['employee' => $employee, 'documents' => $documents]);
    }

    public function download_document($id)
    {
        $doc = $this->Employee_model->get_document((int)$id);
        if (!$doc) { show_404(); }
        $employee = $this->Employee_model->find((int)$doc->employee_id);
        if (!$employee) { show_404(); }
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            $user_id = (int)$this->session->userdata('user_id');
            if ((int)$employee->user_id !== $user_id) { show_error('Forbidden', 403); }
        }
        $path = FCPATH.$doc->file_path;
        if (!is_file($path)) { show_404(); }
        $this->load->helper('download');
        $name = isset($doc->original_name) && $doc->original_name !== '' ? $doc->original_name : basename($path);
        $data = file_get_contents($path);
        force_download($name, $data);
    }

    public function delete_document($id)
    {
        // Check delete permission specifically
        if (!function_exists('has_module_access') || !has_module_access('employees_delete')) {
            show_error('You do not have permission to delete employee documents.', 403);
        }
        
        $doc = $this->Employee_model->get_document((int)$id);
        if (!$doc) { show_404(); }
        $employee = $this->Employee_model->find((int)$doc->employee_id);
        if (!$employee) { show_404(); }
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) { show_error('Forbidden', 403); }
        $path = FCPATH.$doc->file_path;
        $ok = $this->Employee_model->delete_document((int)$id);
        if ($ok && $doc->file_path && is_file($path)) {
            @unlink($path);
        }
        $this->session->set_flashdata('success', 'Document deleted');
        redirect('employees/'.$employee->id.'/documents');
    }

    // GET/POST /employees/import
    public function import()
    {
        if ($this->input->method() === 'post') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->session->set_flashdata('error', 'Please upload a valid CSV file');
                redirect('employees/import');
                return;
            }
            $path = $_FILES['file']['tmp_name'];
            $handle = fopen($path, 'r');
            if (!$handle) {
                $this->session->set_flashdata('error', 'Unable to read uploaded file');
                redirect('employees/import');
                return;
            }
            $header = fgetcsv($handle);
            if (!$header) { fclose($handle); $this->session->set_flashdata('error', 'CSV is empty'); redirect('employees/import'); return; }
            // Expected columns (case-insensitive): emp_code, first_name, last_name, email, department, designation, phone, join_date
            $map = [];
            foreach ($header as $i => $col) { $map[strtolower(trim($col))] = $i; }
            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = [
                    'emp_code' => (isset($map['emp_code']) && isset($data[$map['emp_code']])) ? $data[$map['emp_code']] : null,
                    'first_name' => (isset($map['first_name']) && isset($data[$map['first_name']])) ? $data[$map['first_name']] : null,
                    'last_name' => (isset($map['last_name']) && isset($data[$map['last_name']])) ? $data[$map['last_name']] : null,
                    'email' => (isset($map['email']) && isset($data[$map['email']])) ? $data[$map['email']] : null,
                    'department' => (isset($map['department']) && isset($data[$map['department']])) ? $data[$map['department']] : null,
                    'designation' => (isset($map['designation']) && isset($data[$map['designation']])) ? $data[$map['designation']] : null,
                    'phone' => (isset($map['phone']) && isset($data[$map['phone']])) ? $data[$map['phone']] : null,
                    'join_date' => (isset($map['join_date']) && isset($data[$map['join_date']])) ? $data[$map['join_date']] : null,
                ];
            }
            fclose($handle);
            $inserted = 0;
            foreach ($rows as $r) {
                if (!empty($r['emp_code']) && !empty($r['first_name'])) {
                    $this->Employee_model->create($r);
                    $inserted++;
                }
            }
            $this->load->helper('activity');
            log_activity('employees', 'created', null, 'Imported '.$inserted.' employees');
            $this->session->set_flashdata('success', "Imported $inserted employees");
            redirect('employees');
            return;
        }
        $this->load->view('employees/import');
    }

    public function user_meta($user_id = null)
    {
        $user_id = (int)$user_id;
        $this->output->set_content_type('application/json');
        if ($user_id <= 0) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'error' => 'Invalid user.']);
            return;
        }

        $tbl = null;
        if ($this->db->table_exists('users')) { $tbl = 'users'; }
        elseif ($this->db->table_exists('sma_users')) { $tbl = 'sma_users'; }
        if (!$tbl) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'error' => 'User table not found.']);
            return;
        }

        $fields = $this->db->list_fields($tbl);
        $has = function($f) use ($fields) { return in_array($f, $fields, true); };
        $select = ['id'];
        foreach (['first_name','last_name','name','phone','email'] as $f) {
            if ($has($f)) { $select[] = $f; }
        }
        $row = $this->db->select(implode(', ', $select))
            ->from($tbl)
            ->where('id', $user_id)
            ->limit(1)
            ->get()
            ->row();
        if (!$row) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'error' => 'User not found.']);
            return;
        }

        $firstName = '';
        $lastName = '';
        if (isset($row->first_name) && $row->first_name !== '') {
            $firstName = (string)$row->first_name;
            if (isset($row->last_name)) {
                $lastName = (string)$row->last_name;
            }
        } elseif (isset($row->name) && trim($row->name) !== '') {
            $parts = preg_split('/\s+/', trim((string)$row->name), 2);
            if (isset($parts[0])) { $firstName = (string)$parts[0]; }
            if (isset($parts[1])) { $lastName = (string)$parts[1]; }
        }

        $phone = '';
        if (isset($row->phone) && $row->phone !== '') {
            $phone = (string)$row->phone;
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'department' => '',
            'designation' => '',
        ];

        echo json_encode(['success' => true, 'data' => $data]);
    }

    // Build a list of users for the employee-user link dropdown
    private function get_user_options(){
        $opts = [];
        try {
            // Detect table name
            $tbl = null;
            if ($this->db->table_exists('users')) { $tbl = 'users'; }
            elseif ($this->db->table_exists('sma_users')) { $tbl = 'sma_users'; }
            if (!$tbl) { return $opts; }

            // Detect available fields
            $fields = $this->db->list_fields($tbl);
            $has = function($f) use ($fields){ return in_array($f, $fields, true); };
            $select = ['id'];
            if ($has('first_name')) $select[] = 'first_name';
            if ($has('last_name')) $select[] = 'last_name';
            if ($has('username')) $select[] = 'username';
            if ($has('name')) $select[] = 'name';
            if ($has('email')) $select[] = 'email';
            // Fallback select if only id exists
            $selStr = implode(', ', $select);
            // Order by a sensible existing column
            $orderCol = $has('first_name') ? 'first_name' : ($has('name') ? 'name' : ($has('username') ? 'username' : ($has('email') ? 'email' : 'id')));

            $rows = $this->db->select($selStr)
                             ->from($tbl)
                             ->order_by($orderCol, 'ASC')
                             ->limit(500)
                             ->get()
                             ->result();
            foreach ($rows as $r){
                $label = '';
                $nameParts = [];
                if (isset($r->first_name) && $r->first_name !== '') $nameParts[] = $r->first_name;
                if (isset($r->last_name) && $r->last_name !== '') $nameParts[] = $r->last_name;
                if (!empty($nameParts)) { $label = implode(' ', $nameParts); }
                elseif (isset($r->name) && $r->name !== '') { $label = $r->name; }
                elseif (isset($r->username) && $r->username !== '') { $label = $r->username; }
                elseif (isset($r->email) && $r->email !== '') { $label = $r->email; }
                else { $label = 'User #'.(int)$r->id; }
                if (isset($r->email) && $r->email !== '' && strpos($label, $r->email) === false) { $label .= ' <'.$r->email.'>'; }
                $opts[] = ['id' => (int)$r->id, 'label' => $label];
            }
        } catch (Exception $e) { /* ignore */ }
        return $opts;
    }

    private function find_user_id($id){
        $id = (int)$id;
        if ($id <= 0) { return null; }
        $tbl = null;
        if ($this->db->table_exists('users')) { $tbl = 'users'; }
        elseif ($this->db->table_exists('sma_users')) { $tbl = 'sma_users'; }
        if (!$tbl) { return null; }
        $row = $this->db->select('id')->from($tbl)->where('id', $id)->limit(1)->get()->row();
        return $row ? (int)$row->id : null;
    }
}
