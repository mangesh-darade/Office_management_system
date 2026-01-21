<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designations extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_schema();
        $this->load->model('Designation_model','designations');
    }

    private function ensure_schema(){
        if (!$this->db->table_exists('designations')){
            $sql = "CREATE TABLE `designations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `designation_code` varchar(20) NOT NULL,
                `designation_name` varchar(100) NOT NULL,
                `department_id` int(11) DEFAULT NULL,
                `level` int(11) DEFAULT 1,
                `status` varchar(20) DEFAULT 'active',
                `deleted_at` datetime NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_designation_code` (`designation_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        
        // Add deleted_at column if it doesn't exist
        if ($this->db->table_exists('designations') && !$this->db->field_exists('deleted_at', 'designations')) {
            $this->db->query("ALTER TABLE designations ADD COLUMN deleted_at DATETIME NULL AFTER status");
        }
        
        // Handle unique constraint modification safely
        if ($this->db->table_exists('designations')) {
            // Check if the new composite index already exists
            $query = $this->db->query("SHOW INDEX FROM designations WHERE Key_name = 'uq_designation_code_active'");
            if ($query->num_rows() == 0) {
                // New index doesn't exist, need to create it
                // First, try to drop old indexes if they exist
                $indexes = ['uq_designation_code', 'designation_code'];
                foreach ($indexes as $index_name) {
                    $check_query = $this->db->query("SHOW INDEX FROM designations WHERE Key_name = '".$index_name."'");
                    if ($check_query->num_rows() > 0) {
                        try {
                            $this->db->query("ALTER TABLE designations DROP INDEX ".$index_name);
                        } catch (Exception $e) {
                            // Ignore errors, index might not exist
                        }
                    }
                }
                
                // Create the new composite index
                try {
                    $this->db->query("ALTER TABLE designations ADD UNIQUE KEY uq_designation_code_active (designation_code, deleted_at)");
                } catch (Exception $e) {
                    // If this fails, we'll handle it gracefully
                }
            }
        }
    }

    // GET /designations
    public function index(){
        $rows = $this->designations->all();
        // fetch departments map
        $deptMap = [];
        if ($this->db->table_exists('departments')){
            $deps = $this->db->select('id, dept_name')->from('departments')->get()->result();
            foreach ($deps as $d){ $deptMap[(int)$d->id] = $d->dept_name; }
        }
        $this->load->view('designations/index', [ 'rows' => $rows, 'deptMap' => $deptMap ]);
    }

    // GET/POST /designations/create
    public function create(){
        if ($this->input->method() === 'post'){
            $designation_code = trim((string)$this->input->post('designation_code'));
            $designation_name = trim((string)$this->input->post('designation_name'));
            $department_id = $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null;
            $level = (int)($this->input->post('level') ?: 1);
            
            // Validation
            if (empty($designation_code)) {
                $this->session->set_flashdata('error', 'Designation code is required');
                redirect('designations/create'); return;
            }
            if (empty($designation_name)) {
                $this->session->set_flashdata('error', 'Designation name is required');
                redirect('designations/create'); return;
            }
            
            // Check for duplicate designation_code
            $existing = $this->designations->find_by_code($designation_code);
            if ($existing) {
                $this->session->set_flashdata('error', 'Designation code "'.$designation_code.'" already exists. Please use a different code.');
                redirect('designations/create'); return;
            }
            
            $data = [
                'designation_code' => $designation_code,
                'designation_name' => $designation_name,
                'department_id' => $department_id,
                'level' => $level,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            try {
                $this->db->insert('designations', $data);
                $id = (int)$this->db->insert_id();
                
                // Log designation creation with change tracking
                $this->load->helper('change_tracker');
                $description = 'Designation: ' . $data['designation_name'];
                auto_log_insert('designations', 'designations', $id, $data, $description);
                
                $this->load->helper('notification');
                $success_msg = get_notification_message('designations', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('designations'); return;
            } catch (Exception $e) {
                $this->load->helper('notification');
                $error_msg = get_notification_message('designations', 'create', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('designations/create'); return;
            }
        }
        $departments = [];
        if ($this->db->table_exists('departments')){
            $departments = $this->db->select('id, dept_name')->from('departments')->order_by('dept_name','ASC')->get()->result();
        }
        $this->load->view('designations/form', [ 'action' => 'create', 'departments' => $departments ]);
    }

    // GET/POST /designations/{id}/edit
    public function edit($id){
        $row = $this->designations->find((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $designation_code = trim((string)$this->input->post('designation_code'));
            $designation_name = trim((string)$this->input->post('designation_name'));
            $department_id = $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null;
            $level = (int)($this->input->post('level') ?: 1);
            
            // Validation
            if (empty($designation_code)) {
                $this->session->set_flashdata('error', 'Designation code is required');
                redirect('designations/'.$id.'/edit'); return;
            }
            if (empty($designation_name)) {
                $this->session->set_flashdata('error', 'Designation name is required');
                redirect('designations/'.$id.'/edit'); return;
            }
            
            // Check for duplicate designation_code (excluding current record)
            $existing = $this->designations->find_by_code($designation_code);
            if ($existing && $existing->id != $id) {
                $this->session->set_flashdata('error', 'Designation code "'.$designation_code.'" already exists. Please use a different code.');
                redirect('designations/'.$id.'/edit'); return;
            }
            
            $data = [
                'designation_code' => $designation_code,
                'designation_name' => $designation_name,
                'department_id' => $department_id,
                'level' => $level,
            ];
            
            try {
                // Load activity tracking helper
                $this->load->helper('change_tracker');
                
                // Get old data before update
                $old_data = track_changes_before('designations', (int)$id);
                
                $this->designations->update((int)$id, $data);
                
                // Log update with change tracking
                $description = 'Designation: ' . $data['designation_name'];
                track_changes_after('designations', 'designations', (int)$id, $old_data, $data, $description);
                
                $this->load->helper('notification');
                $success_msg = get_notification_message('designations', 'update', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('designations'); return;
            } catch (Exception $e) {
                $this->load->helper('notification');
                $error_msg = get_notification_message('designations', 'update', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('designations/'.$id.'/edit'); return;
            }
        }
        $departments = [];
        if ($this->db->table_exists('departments')){
            $departments = $this->db->select('id, dept_name')->from('departments')->order_by('dept_name','ASC')->get()->result();
        }
        $this->load->view('designations/form', [ 'action' => 'edit', 'row' => $row, 'departments' => $departments ]);
    }

    // POST /designations/{id}/delete
    public function delete($id){
        // Load activity tracking helper
        $this->load->helper('change_tracker');
        
        // Get old data before delete
        $old_data = track_changes_before('designations', (int)$id);
        
        $this->designations->soft_delete((int)$id);
        
        // Log deletion
        $description = 'Designation removed';
        auto_log_delete('designations', 'designations', (int)$id, $old_data, $description);
        
        $this->load->helper('notification');
        $success_msg = get_notification_message('designations', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('designations');
    }
    
    // POST /designations/{id}/restore
    public function restore($id){
        $id = (int)$id;
        
        // Check if designation exists
        $desig = $this->designations->find($id);
        if (!$desig) {
            // Try to find in deleted records
            $deleted_desigs = $this->designations->deleted_only();
            $found = false;
            foreach ($deleted_desigs as $d) {
                if ((int)$d->id === $id) {
                    $found = true;
                    $desig = $d;
                    break;
                }
            }
            
            if (!$found) {
                $this->session->set_flashdata('error', 'Designation not found');
                redirect('designations');
            }
        }
        
        // Perform restore
        $result = $this->designations->restore($id);
        if ($result) {
            $this->load->helper('activity');
            log_activity('designations', 'restored', $id, 'Designation restored');
            $this->load->helper('notification');
            $success_msg = get_notification_message('designations', 'restore', 'success');
            $this->session->set_flashdata('success', $success_msg);
        } else {
            // Check if it's a code conflict
            $this->db->from('designations');
            $this->db->where('designation_code', $desig->designation_code);
            $this->db->where('status', 'active');
            $this->db->where('id !=', $id);
            $conflict_check = $this->db->get();
            
            if ($conflict_check->num_rows() > 0) {
                $this->session->set_flashdata('error', 'Cannot restore: Another designation with code "'.$desig->designation_code.'" already exists. Please delete or modify the conflicting designation first.');
            } else {
                $this->session->set_flashdata('error', 'Failed to restore designation');
            }
        }
        
        redirect('designations');
    }
}

