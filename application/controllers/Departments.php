<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Org_structure_trait.php';

class Departments extends CI_Controller {
    use Org_structure_trait;

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','schema_columns']);
        $this->load->library(['session']);
        $this->load->model('Department_model','departments');
        
        require_module_access('departments', true);
        
        $this->ensure_schema();
    }

    private function ensure_schema(){
        $this->load->helper('org_schema');
        org_schema_ensure_departments($this->db);
    }

    public function index(){
        $rows = $this->departments->all();
        $managers = [];
        if (schema_table_has_column($this->db, 'departments', 'manager_id')){
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

    public function create(){
        if ($this->input->method() === 'post'){
            $dept_code = trim((string)$this->input->post('dept_code'));
            $dept_name = trim((string)$this->input->post('dept_name'));
            $description = trim((string)$this->input->post('description'));
            $manager_id = $this->input->post('manager_id') !== '' ? (int)$this->input->post('manager_id') : null;
            
            if (empty($dept_code)) {
                $this->session->set_flashdata('error', 'Department code is required');
                redirect('departments/create'); return;
            }
            if (empty($dept_name)) {
                $this->session->set_flashdata('error', 'Department name is required');
                redirect('departments/create'); return;
            }
            
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
                
                $this->load->helper('change_tracker');
                $description = 'Department: ' . $data['dept_name'];
                auto_log_insert('departments', 'departments', $id, $data, $description);
                
                $success_msg = get_notification_message('departments', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('departments'); return;
            } catch (Exception $e) {
                $error_msg = get_notification_message('departments', 'create', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('departments/create'); return;
            }
        }
        $users = $this->db->select('id,email,name')->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        $this->load->view('departments/form', [ 'action' => 'create', 'users' => $users ]);
    }

    public function edit($id){
        $row = $this->departments->find((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $dept_code = trim((string)$this->input->post('dept_code'));
            $dept_name = trim((string)$this->input->post('dept_name'));
            $description = trim((string)$this->input->post('description'));
            $manager_id = $this->input->post('manager_id') !== '' ? (int)$this->input->post('manager_id') : null;
            
            if (empty($dept_code)) {
                $this->session->set_flashdata('error', 'Department code is required');
                redirect('departments/'.$id.'/edit'); return;
            }
            if (empty($dept_name)) {
                $this->session->set_flashdata('error', 'Department name is required');
                redirect('departments/'.$id.'/edit'); return;
            }
            
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
                $this->load->helper('change_tracker');
                $old_data = track_changes_before('departments', (int)$id);
                $this->departments->update((int)$id, $data);
                $description = 'Department: ' . $data['dept_name'];
                track_changes_after('departments', 'departments', (int)$id, $old_data, $data, $description);
                
                $success_msg = get_notification_message('departments', 'update', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('departments'); return;
            } catch (Exception $e) {
                $error_msg = get_notification_message('departments', 'update', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('departments/'.$id.'/edit'); return;
            }
        }
        $users = $this->db->select('id,email,name')->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        $this->load->view('departments/form', [ 'action' => 'edit', 'row' => $row, 'users' => $users ]);
    }

    public function delete($id){
        $this->org_soft_delete_record('departments', 'departments', $this->departments, $id, 'Department removed');
    }
    
    public function restore($id){
        $this->org_restore_by_code('departments', 'departments', $this->departments, $id, 'dept_code', 'Department');
    }
}
