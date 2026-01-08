<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Departments extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Department_model','departments');
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_schema();
    }

    private function ensure_schema(){
        $this->db->query("CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dept_code VARCHAR(20) UNIQUE NOT NULL,
            dept_name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            manager_id INT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Add deleted_at column if it doesn't exist
        if ($this->db->table_exists('departments') && !$this->db->field_exists('deleted_at', 'departments')) {
            $this->db->query("ALTER TABLE departments ADD COLUMN deleted_at DATETIME NULL AFTER status");
        }
        
        // Handle unique constraint modification safely
        if ($this->db->table_exists('departments')) {
            // Check if the new composite index already exists
            $query = $this->db->query("SHOW INDEX FROM departments WHERE Key_name = 'uq_dept_code_active'");
            if ($query->num_rows() == 0) {
                // New index doesn't exist, need to create it
                // First, try to drop old indexes if they exist
                $indexes = ['dept_code', 'uq_dept_code'];
                foreach ($indexes as $index_name) {
                    $check_query = $this->db->query("SHOW INDEX FROM departments WHERE Key_name = '".$index_name."'");
                    if ($check_query->num_rows() > 0) {
                        try {
                            $this->db->query("ALTER TABLE departments DROP INDEX ".$index_name);
                        } catch (Exception $e) {
                            // Ignore errors, index might not exist
                        }
                    }
                }
                
                // Create the new composite index
                try {
                    $this->db->query("ALTER TABLE departments ADD UNIQUE KEY uq_dept_code_active (dept_code, deleted_at)");
                } catch (Exception $e) {
                    // If this fails, we'll handle it gracefully
                }
            }
        }
    }

    // GET /departments
    public function index(){
        $rows = $this->departments->all();
        // fetch manager emails
        $managers = [];
        if ($this->db->field_exists('manager_id','departments')){
            $ids = [];
            foreach ($rows as $r){ if (!empty($r->manager_id)) $ids[] = (int)$r->manager_id; }
            $ids = array_unique($ids);
            if ($ids){
                $us = $this->db->select('id,email,name')->from('users')->where_in('id',$ids)->get()->result();
                foreach ($us as $u){ $managers[(int)$u->id] = $u; }
            }
        }
        $this->load->view('departments/index', [ 'rows' => $rows, 'managers' => $managers ]);
    }

    // GET/POST /departments/create
    public function create(){
        if ($this->input->method() === 'post'){
            $dept_code = trim((string)$this->input->post('dept_code'));
            $dept_name = trim((string)$this->input->post('dept_name'));
            $description = trim((string)$this->input->post('description'));
            $manager_id = $this->input->post('manager_id') !== '' ? (int)$this->input->post('manager_id') : null;
            
            // Validation
            if (empty($dept_code)) {
                $this->session->set_flashdata('error', 'Department code is required');
                redirect('departments/create'); return;
            }
            if (empty($dept_name)) {
                $this->session->set_flashdata('error', 'Department name is required');
                redirect('departments/create'); return;
            }
            
            // Check for duplicate dept_code
            $existing = $this->departments->find_by_code($dept_code);
            if ($existing) {
                $this->session->set_flashdata('error', 'Department code "'.$dept_code.'" already exists. Please use a different code.');
                redirect('departments/create'); return;
            }
            
            $data = [
                'dept_code' => $dept_code,
                'dept_name' => $dept_name,
                'description' => $description,
                'manager_id' => $manager_id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            try {
                $this->db->insert('departments', $data);
                $id = (int)$this->db->insert_id();
                
                // Log department creation with change tracking
                $this->load->helper('change_tracker');
                $description = 'Department: ' . $data['dept_name'];
                auto_log_insert('departments', 'departments', $id, $data, $description);
                
                $this->session->set_flashdata('success', 'Department created successfully');
                redirect('departments'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error creating department: '.$e->getMessage());
                redirect('departments/create'); return;
            }
        }
        // users for manager select
        $users = $this->db->select('id,email,name')->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        $this->load->view('departments/form', [ 'action' => 'create', 'users' => $users ]);
    }

    // GET/POST /departments/{id}/edit
    public function edit($id){
        $row = $this->departments->find((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $dept_code = trim((string)$this->input->post('dept_code'));
            $dept_name = trim((string)$this->input->post('dept_name'));
            $description = trim((string)$this->input->post('description'));
            $manager_id = $this->input->post('manager_id') !== '' ? (int)$this->input->post('manager_id') : null;
            
            // Validation
            if (empty($dept_code)) {
                $this->session->set_flashdata('error', 'Department code is required');
                redirect('departments/'.$id.'/edit'); return;
            }
            if (empty($dept_name)) {
                $this->session->set_flashdata('error', 'Department name is required');
                redirect('departments/'.$id.'/edit'); return;
            }
            
            // Check for duplicate dept_code (excluding current record)
            $existing = $this->departments->find_by_code($dept_code);
            if ($existing && $existing->id != $id) {
                $this->session->set_flashdata('error', 'Department code "'.$dept_code.'" already exists. Please use a different code.');
                redirect('departments/'.$id.'/edit'); return;
            }
            
            $data = [
                'dept_code' => $dept_code,
                'dept_name' => $dept_name,
                'description' => $description,
                'manager_id' => $manager_id,
            ];
            
            try {
                // Load activity tracking helper
                $this->load->helper('change_tracker');
                
                // Get old data before update
                $old_data = track_changes_before('departments', (int)$id);
                
                $this->departments->update((int)$id, $data);
                
                // Log update with change tracking
                $description = 'Department: ' . $data['dept_name'];
                track_changes_after('departments', 'departments', (int)$id, $old_data, $data, $description);
                
                $this->session->set_flashdata('success', 'Department updated successfully');
                redirect('departments'); return;
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error updating department: '.$e->getMessage());
                redirect('departments/'.$id.'/edit'); return;
            }
        }
        $users = $this->db->select('id,email,name')->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        $this->load->view('departments/form', [ 'action' => 'edit', 'row' => $row, 'users' => $users ]);
    }

    // POST /departments/{id}/delete
    public function delete($id){
        // Load activity tracking helper
        $this->load->helper('change_tracker');
        
        // Get old data before delete
        $old_data = track_changes_before('departments', (int)$id);
        
        $this->departments->soft_delete((int)$id);
        
        // Log deletion
        $description = 'Department removed';
        auto_log_delete('departments', 'departments', (int)$id, $old_data, $description);
        
        $this->session->set_flashdata('success', 'Department removed');
        redirect('departments');
    }
    
    // POST /departments/{id}/restore
    public function restore($id){
        $id = (int)$id;
        
        // Check if department exists
        $dept = $this->departments->find($id);
        if (!$dept) {
            // Try to find in deleted records
            $deleted_depts = $this->departments->deleted_only();
            $found = false;
            foreach ($deleted_depts as $d) {
                if ((int)$d->id === $id) {
                    $found = true;
                    $dept = $d;
                    break;
                }
            }
            
            if (!$found) {
                $this->session->set_flashdata('error', 'Department not found');
                redirect('departments');
            }
        }
        
        // Perform restore
        $result = $this->departments->restore($id);
        if ($result) {
            $this->load->helper('activity');
            log_activity('employees', 'restored', $id, 'Department restored');
            $this->session->set_flashdata('success', 'Department restored successfully');
        } else {
            // Check if it's a code conflict
            $this->db->from('departments');
            $this->db->where('dept_code', $dept->dept_code);
            $this->db->where('status', 'active');
            $this->db->where('id !=', $id);
            $conflict_check = $this->db->get();
            
            if ($conflict_check->num_rows() > 0) {
                $this->session->set_flashdata('error', 'Cannot restore: Another department with code "'.$dept->dept_code.'" already exists. Please delete or modify the conflicting department first.');
            } else {
                $this->session->set_flashdata('error', 'Failed to restore department');
            }
        }
        
        redirect('departments');
    }
}
