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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
                $this->load->helper('activity');
                log_activity('employees', 'created', $id, 'Department: '.$data['dept_name']);
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
                $this->departments->update((int)$id, $data);
                $this->load->helper('activity');
                log_activity('employees', 'updated', (int)$id, 'Department: '.$data['dept_name']);
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
        $this->departments->soft_delete((int)$id);
        $this->load->helper('activity');
        log_activity('employees', 'deleted', (int)$id, 'Department deleted');
        $this->session->set_flashdata('success', 'Department removed');
        redirect('departments');
    }
}
