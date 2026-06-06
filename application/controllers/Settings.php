<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','upload','email']);
        $this->load->helper(['url','form','activity','permission','schema_columns','types']);
        $this->load->model('Setting_model','settings');
        $this->load->model('Leave_type_model','leave_types');
        $this->load->model('Holiday_model','holidays');
        $this->load->model('Type_model','module_types');
        
        // RBAC Audit: Centralized module access check
        require_module_access(['settings', 'leave_types', 'holidays', 'types'], true);
        
        $this->ensure_leave_types_schema();
        $this->ensure_holidays_schema();
    }

    private function ensure_leave_types_schema(){
        // Add status and deleted_at columns if they don't exist
        if ($this->db->table_exists('leave_types')) {
            if (!schema_table_has_column($this->db, 'leave_types', 'status')) {
                $this->db->query("ALTER TABLE leave_types ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER is_paid");
            }
            if (!schema_table_has_column($this->db, 'leave_types', 'deleted_at')) {
                $this->db->query("ALTER TABLE leave_types ADD COLUMN deleted_at DATETIME NULL AFTER status");
            }
            // Update existing records to active if status is null
            $this->db->query("UPDATE leave_types SET status = 'active' WHERE status IS NULL");
        }
    }

    /**
     * Ensure holidays table exists with required columns.
     * This is used by Settings > Holidays and by leave/attendance helpers.
     */
    private function ensure_holidays_schema(){
        if (!$this->db->table_exists('holidays')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS holidays (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    holiday_date DATE NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    UNIQUE KEY uq_holidays_date (holiday_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // Ensure required columns exist
            if (!schema_table_has_column($this->db, 'holidays', 'status')) {
                $this->db->query("ALTER TABLE holidays ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER name");
            }
            if (!schema_table_has_column($this->db, 'holidays', 'created_at')) {
                $this->db->query("ALTER TABLE holidays ADD COLUMN created_at DATETIME NULL AFTER status");
            }
            if (!schema_table_has_column($this->db, 'holidays', 'updated_at')) {
                $this->db->query("ALTER TABLE holidays ADD COLUMN updated_at DATETIME NULL AFTER created_at");
            }
        }
    }

    // GET /settings
    public function index(){
        require_module_access('settings', true);
        $all = $this->settings->get_all_settings();
        
        // Get all active users for HR dropdown
        $this->db->select('u.id, u.name, u.email');
        $this->db->from('users u');
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('u.status', 'active');
        }
        $this->db->order_by('u.name', 'ASC');
        $all_users = $this->db->get()->result();
        
        $this->load->view('settings/index', [
            'settings' => $all,
            'all_users' => $all_users
        ]);
    }

    // POST /settings/update
    public function update(){
        require_module_access('settings', true);
        if ($this->input->method() !== 'post') { show_404(); }
        $data = $this->input->post();
        $form_section = isset($data['form_section']) ? (string)$data['form_section'] : '';
        
        // Load activity tracking helpers
        $this->load->helper(['activity','change_tracker']);
        
        // Handle weekend checkboxes only when attendance section is saved
        if ($form_section === 'attendance') {
            if (isset($data['attendance_weekends']) && is_array($data['attendance_weekends'])) {
                $data['attendance_weekends'] = implode(',', $data['attendance_weekends']);
            } elseif (!isset($data['attendance_weekends'])) {
                $data['attendance_weekends'] = '';
            }
        }
        
        // Handle checkbox values for switches, scoped per form section
        $checkbox_fields = [];
        switch ($form_section) {
            case 'attendance':
                $checkbox_fields = [
                    'attendance_auto_capture',
                    'attendance_auto_submit',
                    'attendance_late_mark_notification',
                    'attendance_show_notifications',
                    'attendance_face_verification_required',
                ];
                break;
            case 'leave':
                $checkbox_fields = ['leave_carry_forward'];
                break;
            case 'notify_basic':
                $checkbox_fields = ['notify_in_app', 'notify_email'];
                break;
            case 'general_system':
                $checkbox_fields = [
                    'system_maintenance_mode',
                    'system_enable_debug_mode',
                ];
                // Add all module maintenance checkboxes dynamically
                $modules_list = ['dashboard', 'employees', 'users', 'projects', 'tasks', 'attendance', 'leaves', 'departments', 'designations', 'clients', 'assets', 'announcements', 'chats', 'calls', 'timesheets', 'reports', 'settings', 'reminders', 'activity', 'permissions', 'payroll'];
                foreach ($modules_list as $module) {
                    $checkbox_fields[] = 'system_maintenance_module_' . $module;
                }
                break;
            case 'general_location':
                $checkbox_fields = ['system_enable_location_strict'];
                break;
            case 'security_password':
                $checkbox_fields = [
                    'security_require_uppercase',
                    'security_require_lowercase',
                    'security_require_number',
                    'security_require_special',
                ];
                break;
            case 'security_session':
                $checkbox_fields = [
                    'security_single_session',
                    'security_remember_me',
                ];
                break;
            case 'security_2fa':
                $checkbox_fields = [
                    'security_enable_2fa',
                    'security_2fa_required_admin',
                ];
                break;
            case 'security_ip':
                $checkbox_fields = [
                    'security_enable_ip_whitelist',
                    'security_log_failed_attempts',
                ];
                break;
            case 'security_audit':
                $checkbox_fields = [
                    'security_audit_login',
                    'security_audit_settings',
                    'security_audit_data',
                ];
                break;
            case 'ai_integration':
                $checkbox_fields = [
                    'ai_gemini_enabled',
                    'ai_openai_enabled',
                    'ai_openrouter_enabled',
                    'ai_huggingface_enabled',
                    'ai_azure_speech_enabled'
                ];
                
                // Handle custom AI providers array
                if (isset($data['ai_custom_providers']) && is_array($data['ai_custom_providers'])) {
                    // Clean and validate custom providers
                    $cleaned_providers = [];
                    foreach ($data['ai_custom_providers'] as $index => $provider) {
                        if (isset($provider['name']) && !empty(trim($provider['name']))) {
                            $cleaned_providers[] = [
                                'name' => trim($provider['name']),
                                'key' => isset($provider['key']) ? trim($provider['key']) : '',
                                'enabled' => (isset($provider['enabled']) && $provider['enabled'] == '1') ? '1' : '0'
                            ];
                        }
                    }
                    $data['ai_custom_providers'] = $cleaned_providers;
                } else {
                    $data['ai_custom_providers'] = [];
                }
                
                // Basic validation: Warn if provider is enabled but API key is empty (non-blocking)
                $providers_to_check = [
                    'ai_gemini' => 'Google Gemini',
                    'ai_openai' => 'OpenAI',
                    'ai_openrouter' => 'OpenRouter',
                    'ai_huggingface' => 'Hugging Face',
                    'ai_azure_speech' => 'Azure Speech'
                ];
                
                $warnings = [];
                foreach ($providers_to_check as $prefix => $name) {
                    $enabled_key = $prefix . '_enabled';
                    $api_key_key = $prefix . '_api_key';
                    
                    if (isset($data[$enabled_key]) && $data[$enabled_key] === 'yes') {
                        $api_key = isset($data[$api_key_key]) ? trim($data[$api_key_key]) : '';
                        if (empty($api_key)) {
                            $warnings[] = "$name is enabled but API key is missing. It may not work properly.";
                        }
                    }
                }
                
                // Store warnings in flashdata if any (non-blocking, just informational)
                if (!empty($warnings)) {
                    $existing_warning = $this->session->flashdata('warning');
                    $warning_msg = implode(' ', $warnings);
                    if ($existing_warning) {
                        $warning_msg = $existing_warning . ' ' . $warning_msg;
                    }
                    $this->session->set_flashdata('warning', $warning_msg);
                }
                break;
            default:
                // Sections like company, email, notification messages, general display/HR
                // don't have switches that need defaulting here
                $checkbox_fields = [];
                break;
        }
        
        foreach ($checkbox_fields as $field) {
            $data[$field] = isset($data[$field]) ? $data[$field] : 'no';
        }

        // Keep legacy security keys in sync with settings UI field names.
        if ($form_section === 'security_2fa')
        {
            $data['security_2fa_enabled'] = isset($data['security_enable_2fa']) ? $data['security_enable_2fa'] : 'no';
        }
        if ($form_section === 'security_ip')
        {
            $data['security_ip_whitelist_enabled'] = isset($data['security_enable_ip_whitelist']) ? $data['security_enable_ip_whitelist'] : 'no';
        }
        
        // Get old settings before update and track changes
        $old_settings = [];
        $changed_settings = [];
        foreach ($data as $k=>$v){
            // Only allow known prefixes (including notification_ and system_)
            if (preg_match('/^(company_|attendance_|leave_|email_|notify_|security_|notification_|system_|ai_)/', $k)){
                $old_value = $this->settings->get_setting($k);
                $old_settings[$k] = $old_value;
                $new_value = is_array($v) ? json_encode($v) : $v;
                
                if ($old_value !== $new_value) {
                    $changed_settings[$k] = [
                        'before' => $old_value,
                        'after' => $new_value
                    ];
                }
                
                $this->settings->set_setting($k, $new_value);
            }
        }
        
        // Log settings update if any changes were made
        if (!empty($changed_settings)) {
            $description = 'Settings updated: ' . count($changed_settings) . ' setting(s) changed';
            log_activity_with_changes('settings', 'updated', null, $old_settings, $data, $description);
        }
        
        $success_msg = get_notification_message('settings', 'update', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('settings');
    }

    // POST /settings/remove-logo
    public function remove_logo(){
        require_module_access('settings', true);
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
        require_module_access('settings', true);
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK){
            $this->session->set_flashdata('error', 'Upload a valid logo file.');
            redirect('settings'); return;
        }
        $upload_path = FCPATH.'uploads/settings/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
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
        require_module_access('settings', true);
        $to = trim((string)$this->input->post('to')) ?: (string)$this->session->userdata('email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)){
            $this->session->set_flashdata('error', 'Provide a valid email address');
            redirect('settings'); return;
        }
        
        // Load email helper and configure from settings
        $this->load->helper('email');
        configure_email_from_settings();
        
        $this->email->clear(true);
        $this->email->to($to);
        $this->email->from(get_system_from_email(), get_company_name());
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

    // GET /settings/holidays
    public function holidays(){
        require_module_access(['holidays', 'settings'], true);
        $rows = $this->holidays->all();
        $this->load->view('settings/holidays/index', [
            'rows' => $rows
        ]);
    }

    // GET/POST /settings/holidays/create
    public function holidays_create(){
        require_module_access(['holidays_add', 'holidays', 'settings'], true);
        if ($this->input->method() === 'post'){
            $date = trim((string)$this->input->post('holiday_date'));
            $name = trim((string)$this->input->post('name'));
            $status = $this->input->post('status') === 'inactive' ? 'inactive' : 'active';

            if (empty($date) || empty($name)) {
                $this->session->set_flashdata('error', 'Holiday date and name are required.');
                redirect('settings/holidays/create'); return;
            }

            // Validate date format
            $d = date_create_from_format('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $this->session->set_flashdata('error', 'Invalid date format. Use YYYY-MM-DD.');
                redirect('settings/holidays/create'); return;
            }

            // Prevent duplicate dates
            $existing = $this->holidays->find_by_date($date);
            if ($existing) {
                $this->session->set_flashdata('error', 'A holiday already exists on '.$date.'.');
                redirect('settings/holidays/create'); return;
            }

            $data = [
                'holiday_date' => $date,
                'name' => $name,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            try {
                $id = $this->holidays->create($data);
                $this->load->helper('activity');
                log_activity('holidays', 'created', $id, 'Holiday: '.$data['name'].' ('.$data['holiday_date'].')');
                $this->session->set_flashdata('success', 'Holiday created successfully.');
                redirect('settings/holidays'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error creating holiday: '.$e->getMessage());
                redirect('settings/holidays/create'); return;
            }
        }
        $this->load->view('settings/holidays/form', ['action' => 'create']);
    }

    // GET/POST /settings/holidays/{id}/edit
    public function holidays_edit($id){
        require_module_access(['holidays_edit', 'holidays', 'settings'], true);
        $row = $this->holidays->find((int)$id);
        if (!$row) { show_404(); }

        if ($this->input->method() === 'post'){
            $date = trim((string)$this->input->post('holiday_date'));
            $name = trim((string)$this->input->post('name'));
            $status = $this->input->post('status') === 'inactive' ? 'inactive' : 'active';

            if (empty($date) || empty($name)) {
                $this->session->set_flashdata('error', 'Holiday date and name are required.');
                redirect('settings/holidays/'.$id.'/edit'); return;
            }

            $d = date_create_from_format('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $this->session->set_flashdata('error', 'Invalid date format. Use YYYY-MM-DD.');
                redirect('settings/holidays/'.$id.'/edit'); return;
            }

            // Check for duplicate date on other records
            $existing = $this->holidays->find_by_date($date);
            if ($existing && (int)$existing->id !== (int)$id) {
                $this->session->set_flashdata('error', 'Another holiday already exists on '.$date.'.');
                redirect('settings/holidays/'.$id.'/edit'); return;
            }

            $data = [
                'holiday_date' => $date,
                'name' => $name,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            try {
                $this->holidays->update((int)$id, $data);
                $this->load->helper('activity');
                log_activity('holidays', 'updated', (int)$id, 'Holiday: '.$data['name'].' ('.$data['holiday_date'].')');
                $this->session->set_flashdata('success', 'Holiday updated successfully.');
                redirect('settings/holidays'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error updating holiday: '.$e->getMessage());
                redirect('settings/holidays/'.$id.'/edit'); return;
            }
        }
        $this->load->view('settings/holidays/form', ['action' => 'edit', 'row' => $row]);
    }

    // POST /settings/holidays/{id}/delete
    public function holidays_delete($id){
        require_module_access(['holidays_delete', 'holidays', 'settings'], true);
        $row = $this->holidays->find((int)$id);
        if (!$row) {
            $this->session->set_flashdata('error', 'Holiday not found.');
            redirect('settings/holidays'); return;
        }

        // Soft delete: mark as inactive instead of removing row
        try {
            $this->holidays->update((int)$id, [
                'status' => 'inactive',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $this->load->helper('activity');
            log_activity('holidays', 'deleted', (int)$id, 'Holiday deactivated: '.$row->name.' ('.$row->holiday_date.')');
            $this->session->set_flashdata('success', 'Holiday deactivated successfully.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error deactivating holiday: '.$e->getMessage());
        }

        redirect('settings/holidays');
    }

    // GET /settings/leave-types
    public function leave_types(){
        require_module_access(['leave_types', 'settings'], true);
        $show_deleted = $this->input->get('show_deleted');
        $rows = $show_deleted ? $this->leave_types->deleted_only() : $this->leave_types->all();
        $this->load->view('settings/leave_types/index', [
            'rows' => $rows,
            'show_deleted' => $show_deleted
        ]);
    }

    // GET/POST /settings/leave-types/create
    public function leave_types_create(){
        require_module_access(['leave_types_add', 'leave_types', 'settings'], true);
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
        require_module_access(['leave_types_edit', 'leave_types', 'settings'], true);
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
        require_module_access(['leave_types_delete', 'leave_types', 'settings'], true);
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
        require_module_access(['leave_types_edit', 'leave_types', 'settings'], true);
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

    // GET /settings/types
    public function module_types()
    {
        require_module_access(['types', 'settings'], true);
        $module = trim((string) $this->input->get('module'));
        $types = $this->module_types->get_all($module !== '' ? $module : null, false);
        $this->load->view('settings/module_types/index', array(
            'types'           => $types,
            'modules'         => $this->module_types->registry_modules(),
            'selected_module' => $module !== '' ? $module : null,
        ));
    }

    // GET/POST /settings/types/create
    public function module_types_create()
    {
        require_module_access(['types', 'settings'], true);
        if ($this->input->method() === 'post') {
            $data = array(
                'name'          => trim((string) $this->input->post('name')),
                'code'          => trim((string) $this->input->post('code')),
                'module'        => trim((string) $this->input->post('module')),
                'display_order' => $this->input->post('display_order') !== '' ? (int) $this->input->post('display_order') : 0,
                'is_active'     => $this->input->post('is_active') ? 1 : 0,
                'description'   => trim((string) $this->input->post('description')) ?: null,
            );
            if ($data['name'] === '' || $data['code'] === '' || $data['module'] === '') {
                $this->session->set_flashdata('error', 'Name, code, and module are required.');
                redirect('settings/types/create');
                return;
            }
            $existing = $this->module_types->get_by_code($data['code'], $data['module']);
            if ($existing) {
                $this->session->set_flashdata('error', 'Type code already exists for this module.');
                redirect('settings/types/create');
                return;
            }
            $id = $this->module_types->create($data);
            if (function_exists('log_activity')) {
                log_activity('module_types', 'created', (int) $id, 'Type: ' . $data['name']);
            }
            $this->session->set_flashdata('success', 'Type created successfully.');
            redirect('settings/types');
            return;
        }
        $this->load->view('settings/module_types/form', array(
            'action'  => 'create',
            'modules' => $this->module_types->registry_modules(),
        ));
    }

    // GET/POST /settings/types/{id}/edit
    public function module_types_edit($id)
    {
        require_module_access(['types', 'settings'], true);
        $type = $this->module_types->get_by_id((int) $id);
        if (!$type) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $data = array(
                'name'          => trim((string) $this->input->post('name')),
                'code'          => trim((string) $this->input->post('code')),
                'module'        => trim((string) $this->input->post('module')),
                'display_order' => $this->input->post('display_order') !== '' ? (int) $this->input->post('display_order') : 0,
                'is_active'     => $this->input->post('is_active') ? 1 : 0,
                'description'   => trim((string) $this->input->post('description')) ?: null,
            );
            $existing = $this->module_types->get_by_code($data['code'], $data['module']);
            if ($existing && (int) $existing->id !== (int) $id) {
                $this->session->set_flashdata('error', 'Type code already exists for this module.');
                redirect('settings/types/' . (int) $id . '/edit');
                return;
            }
            $this->module_types->update((int) $id, $data);
            if (function_exists('log_activity')) {
                log_activity('module_types', 'updated', (int) $id, 'Type: ' . $data['name']);
            }
            $this->session->set_flashdata('success', 'Type updated successfully.');
            redirect('settings/types');
            return;
        }
        $this->load->view('settings/module_types/form', array(
            'action'  => 'edit',
            'type'    => $type,
            'modules' => $this->module_types->registry_modules(),
        ));
    }

    // POST /settings/types/{id}/delete
    public function module_types_delete($id)
    {
        require_module_access(['types', 'settings'], true);
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $type = $this->module_types->get_by_id((int) $id);
        if (!$type) {
            show_404();
        }
        $this->module_types->delete((int) $id);
        if (function_exists('log_activity')) {
            log_activity('module_types', 'deleted', (int) $id, 'Type removed');
        }
        $this->session->set_flashdata('success', 'Type deleted successfully.');
        redirect('settings/types');
    }
}
