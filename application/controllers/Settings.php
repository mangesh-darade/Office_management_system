<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','upload','email']);
        $this->load->helper(['url','form']);
        $this->load->model('Setting_model','settings');
        $this->load->model('Leave_type_model','leave_types');
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_leave_types_schema();
    }

    private function ensure_leave_types_schema(){
        // Add status and deleted_at columns if they don't exist
        if ($this->db->table_exists('leave_types')) {
            if (!$this->db->field_exists('status', 'leave_types')) {
                $this->db->query("ALTER TABLE leave_types ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER is_paid");
            }
            if (!$this->db->field_exists('deleted_at', 'leave_types')) {
                $this->db->query("ALTER TABLE leave_types ADD COLUMN deleted_at DATETIME NULL AFTER status");
            }
            // Update existing records to active if status is null
            $this->db->query("UPDATE leave_types SET status = 'active' WHERE status IS NULL");
        }
    }

    // GET /settings
    public function index(){
        $all = $this->settings->get_all_settings();
        $this->load->view('settings/index', ['settings' => $all]);
    }

    // POST /settings/update
    public function update(){
        if ($this->input->method() !== 'post') { show_404(); }
        $data = $this->input->post();
        
        // Handle weekend checkboxes
        if (isset($data['attendance_weekends']) && is_array($data['attendance_weekends'])) {
            $data['attendance_weekends'] = implode(',', $data['attendance_weekends']);
        } elseif (!isset($data['attendance_weekends'])) {
            $data['attendance_weekends'] = '';
        }
        
        // Handle checkbox values for switches
        $checkbox_fields = ['leave_carry_forward', 'notify_in_app', 'notify_email'];
        foreach ($checkbox_fields as $field) {
            $data[$field] = isset($data[$field]) ? $data[$field] : 'no';
        }
        
        foreach ($data as $k=>$v){
            // Only allow known prefixes
            if (preg_match('/^(company_|attendance_|leave_|email_|notify_)/', $k)){
                $this->settings->set_setting($k, is_array($v) ? json_encode($v) : $v);
            }
        }
        $this->session->set_flashdata('success', 'Settings saved successfully.');
        redirect('settings');
    }

    // POST /settings/remove-logo
    public function remove_logo(){
        if ($this->input->method() !== 'post') { show_404(); }
        
        // Get current logo path to delete file
        $current_logo = $this->settings->get_setting('company_logo');
        if ($current_logo && file_exists(FCPATH . $current_logo)) {
            unlink(FCPATH . $current_logo);
        }
        
        // Clear logo setting
        $this->settings->set_setting('company_logo', '');
        $this->session->set_flashdata('success', 'Logo removed successfully.');
        redirect('settings');
    }

    // POST /settings/upload-logo
    public function upload_logo(){
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK){
            $this->session->set_flashdata('error', 'Upload a valid logo file.');
            redirect('settings'); return;
        }
        $upload_path = FCPATH.'uploads/settings/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
        $config = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|gif',
            'max_size' => 2048,
            'encrypt_name' => true,
        ];
        $this->upload->initialize($config);
        if ($this->upload->do_upload('logo')){
            $up = $this->upload->data();
            $path = 'uploads/settings/'.$up['file_name'];
            
            // Remove old logo if exists
            $old_logo = $this->settings->get_setting('company_logo');
            if ($old_logo && file_exists(FCPATH . $old_logo)) {
                unlink(FCPATH . $old_logo);
            }
            
            $this->settings->set_setting('company_logo', $path);
            $this->session->set_flashdata('success', 'Logo uploaded successfully.');
        } else {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
        }
        redirect('settings');
    }

    // POST /settings/test-email
    public function test_email(){
        $to = trim((string)$this->input->post('to')) ?: (string)$this->session->userdata('email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)){
            $this->session->set_flashdata('error', 'Provide a valid email address');
            redirect('settings'); return;
        }
        
        // Configure email settings from database
        $smtp_user = $this->settings->get_setting('email_smtp_user');
        $smtp_pass = $this->settings->get_setting('email_smtp_pass');
        $smtp_host = $this->settings->get_setting('email_smtp_host', 'smtp.gmail.com');
        $smtp_port = $this->settings->get_setting('email_smtp_port', '587');
        $smtp_crypto = $this->settings->get_setting('email_smtp_crypto', 'tls');
        
        if ($smtp_user && $smtp_pass) {
            $config = [
                'protocol' => 'smtp',
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_user' => $smtp_user,
                'smtp_pass' => $smtp_pass,
                'smtp_crypto' => $smtp_crypto,
                'mailtype' => 'html',
                'charset' => 'utf-8',
                'wordwrap' => TRUE
            ];
            $this->email->initialize($config);
        }
        
        $this->email->clear(true);
        $this->email->to($to);
        $this->email->from($smtp_user ?: 'noreply@example.com', get_company_name());
        $this->email->subject('Settings: Test Email');
        $this->email->message('<p>This is a test email from ' . get_company_name() . ' settings.</p><p>If you receive this email, your SMTP configuration is working correctly.</p>');
        
        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Test email sent successfully to ' . $to);
        } else {
            $error = $this->email->print_debugger(['headers']);
            $this->session->set_flashdata('error', 'Failed to send test email. Please check your SMTP configuration.');
        }
        redirect('settings');
    }

    // GET /settings/leave-types
    public function leave_types(){
        $show_deleted = $this->input->get('show_deleted');
        $rows = $show_deleted ? $this->leave_types->deleted_only() : $this->leave_types->all();
        $this->load->view('settings/leave_types/index', [
            'rows' => $rows,
            'show_deleted' => $show_deleted
        ]);
    }

    // GET/POST /settings/leave-types/create
    public function leave_types_create(){
        if ($this->input->method() === 'post'){
            $name = trim((string)$this->input->post('name'));
            $description = trim((string)$this->input->post('description'));
            $annual_quota = $this->input->post('annual_quota') !== '' ? (float)$this->input->post('annual_quota') : 0;
            $is_paid = $this->input->post('is_paid') === '1' ? 1 : 0;
            
            // Validation
            if (empty($name)) {
                $this->session->set_flashdata('error', 'Leave type name is required');
                redirect('settings/leave-types/create'); return;
            }
            
            // Check for duplicate name
            $existing = $this->leave_types->find_by_name($name);
            if ($existing) {
                $this->session->set_flashdata('error', 'Leave type "'.$name.'" already exists. Please use a different name.');
                redirect('settings/leave-types/create'); return;
            }
            
            $data = [
                'name' => $name,
                'description' => $description,
                'annual_quota' => $annual_quota,
                'is_paid' => $is_paid,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            try {
                $id = $this->leave_types->create($data);
                $this->load->helper('activity');
                log_activity('leave_types', 'created', $id, 'Leave type: '.$data['name']);
                $this->session->set_flashdata('success', 'Leave type created successfully');
                redirect('settings/leave-types'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error creating leave type: '.$e->getMessage());
                redirect('settings/leave-types/create'); return;
            }
        }
        $this->load->view('settings/leave_types/form', ['action' => 'create']);
    }

    // GET/POST /settings/leave-types/{id}/edit
    public function leave_types_edit($id){
        $row = $this->leave_types->find((int)$id);
        if (!$row) { show_404(); }
        
        if ($this->input->method() === 'post'){
            $name = trim((string)$this->input->post('name'));
            $description = trim((string)$this->input->post('description'));
            $annual_quota = $this->input->post('annual_quota') !== '' ? (float)$this->input->post('annual_quota') : 0;
            $is_paid = $this->input->post('is_paid') === '1' ? 1 : 0;
            
            // Validation
            if (empty($name)) {
                $this->session->set_flashdata('error', 'Leave type name is required');
                redirect('settings/leave-types/'.$id.'/edit'); return;
            }
            
            // Check for duplicate name (excluding current record)
            $existing = $this->leave_types->find_by_name($name);
            if ($existing && $existing->id != $id) {
                $this->session->set_flashdata('error', 'Leave type "'.$name.'" already exists. Please use a different name.');
                redirect('settings/leave-types/'.$id.'/edit'); return;
            }
            
            $data = [
                'name' => $name,
                'description' => $description,
                'annual_quota' => $annual_quota,
                'is_paid' => $is_paid,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            try {
                $this->leave_types->update((int)$id, $data);
                $this->load->helper('activity');
                log_activity('leave_types', 'updated', (int)$id, 'Leave type: '.$data['name']);
                $this->session->set_flashdata('success', 'Leave type updated successfully');
                redirect('settings/leave-types'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error updating leave type: '.$e->getMessage());
                redirect('settings/leave-types/'.$id.'/edit'); return;
            }
        }
        $this->load->view('settings/leave_types/form', ['action' => 'edit', 'row' => $row]);
    }

    // POST /settings/leave-types/{id}/delete
    public function leave_types_delete($id){
        $row = $this->leave_types->find((int)$id);
        if (!$row) { 
            $this->session->set_flashdata('error', 'Leave type not found');
            redirect('settings/leave-types'); return;
        }
        
        // Check if leave type is being used in leave requests
        $in_use = $this->db->where('type_id', (int)$id)
                           ->where('status !=', 'rejected')
                           ->from('leave_requests')
                           ->count_all_results();
        
        if ($in_use > 0) {
            $this->session->set_flashdata('error', 'Cannot delete leave type: It is being used in '.$in_use.' active leave request(s).');
            redirect('settings/leave-types'); return;
        }
        
        $this->leave_types->soft_delete((int)$id);
        $this->load->helper('activity');
        log_activity('leave_types', 'deleted', (int)$id, 'Leave type removed');
        $this->session->set_flashdata('success', 'Leave type removed');
        redirect('settings/leave-types');
    }
    
    // POST /settings/leave-types/{id}/restore
    public function leave_types_restore($id){
        $id = (int)$id;
        
        // Check if leave type exists
        $leave_type = $this->leave_types->find($id);
        if (!$leave_type) {
            // Try to find in deleted records
            $deleted_types = $this->leave_types->deleted_only();
            $found = false;
            foreach ($deleted_types as $lt) {
                if ((int)$lt->id === $id) {
                    $found = true;
                    $leave_type = $lt;
                    break;
                }
            }
            
            if (!$found) {
                $this->session->set_flashdata('error', 'Leave type not found');
                redirect('settings/leave-types');
            }
        }
        
        // Perform restore
        $result = $this->leave_types->restore($id);
        if ($result) {
            $this->load->helper('activity');
            log_activity('leave_types', 'restored', $id, 'Leave type restored');
            $this->session->set_flashdata('success', 'Leave type restored successfully');
        } else {
            // Check if it's a name conflict
            $this->db->from('leave_types');
            $this->db->where('name', $leave_type->name);
            $this->db->where('status', 'active');
            $this->db->where('id !=', $id);
            $conflict_check = $this->db->get();
            
            if ($conflict_check->num_rows() > 0) {
                $this->session->set_flashdata('error', 'Cannot restore: Another leave type with name "'.$leave_type->name.'" already exists. Please delete or modify the conflicting leave type first.');
            } else {
                $this->session->set_flashdata('error', 'Failed to restore leave type');
            }
        }
        
        redirect('settings/leave-types');
    }
}
