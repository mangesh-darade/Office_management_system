<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Org_structure_trait.php';

class Designations extends CI_Controller {
    use Org_structure_trait;

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        
        require_module_access('designations', true);
        
        $this->ensure_schema();
        $this->load->model('Designation_model','designations');
    }

    private function ensure_schema(){
        $this->load->helper('org_schema');
        org_schema_ensure_designations($this->db);
    }

    public function index(){
        $rows = $this->designations->all();
        $deptMap = [];
        if ($this->db->table_exists('departments')){
            $deps = $this->db->select('id, dept_name')->from('departments')->get()->result();
            foreach ($deps as $d){ $deptMap[(int)$d->id] = $d->dept_name; }
        }
        $this->load->view('designations/index', [ 'rows' => $rows, 'deptMap' => $deptMap ]);
    }

    public function create(){
        if ($this->input->method() === 'post'){
            $designation_code = trim((string)$this->input->post('designation_code'));
            $designation_name = trim((string)$this->input->post('designation_name'));
            $department_id = $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null;
            $level = (int)($this->input->post('level') ?: 1);
            
            if (empty($designation_code)) {
                $this->session->set_flashdata('error', 'Designation code is required');
                redirect('designations/create'); return;
            }
            if (empty($designation_name)) {
                $this->session->set_flashdata('error', 'Designation name is required');
                redirect('designations/create'); return;
            }
            
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
                
                $this->load->helper('change_tracker');
                $description = 'Designation: ' . $data['designation_name'];
                auto_log_insert('designations', 'designations', $id, $data, $description);
                
                $success_msg = get_notification_message('designations', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('designations'); return;
            } catch (Exception $e) {
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

    public function edit($id){
        $row = $this->designations->find((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $designation_code = trim((string)$this->input->post('designation_code'));
            $designation_name = trim((string)$this->input->post('designation_name'));
            $department_id = $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null;
            $level = (int)($this->input->post('level') ?: 1);
            
            if (empty($designation_code)) {
                $this->session->set_flashdata('error', 'Designation code is required');
                redirect('designations/'.$id.'/edit'); return;
            }
            if (empty($designation_name)) {
                $this->session->set_flashdata('error', 'Designation name is required');
                redirect('designations/'.$id.'/edit'); return;
            }
            
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
                $this->load->helper('change_tracker');
                $old_data = track_changes_before('designations', (int)$id);
                $this->designations->update((int)$id, $data);
                $description = 'Designation: ' . $data['designation_name'];
                track_changes_after('designations', 'designations', (int)$id, $old_data, $data, $description);
                
                $success_msg = get_notification_message('designations', 'update', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('designations'); return;
            } catch (Exception $e) {
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

    public function delete($id){
        $this->org_soft_delete_record('designations', 'designations', $this->designations, $id, 'Designation removed');
    }
    
    public function restore($id){
        $this->org_restore_by_code('designations', 'designations', $this->designations, $id, 'designation_code', 'Designation');
    }
}
