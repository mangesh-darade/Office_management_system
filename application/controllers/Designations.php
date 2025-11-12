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
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_designation_code` (`designation_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
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
            $data = [
                'designation_code' => trim((string)$this->input->post('designation_code')),
                'designation_name' => trim((string)$this->input->post('designation_name')),
                'department_id' => $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null,
                'level' => (int)($this->input->post('level') ?: 1),
                'status' => 'active',
            ];
            $this->db->insert('designations', $data);
            $id = (int)$this->db->insert_id();
            $this->load->helper('activity');
            log_activity('designations', 'created', $id, 'Designation: '.$data['designation_name']);
            $this->session->set_flashdata('success', 'Designation created');
            redirect('designations'); return;
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
            $data = [
                'designation_code' => trim((string)$this->input->post('designation_code')),
                'designation_name' => trim((string)$this->input->post('designation_name')),
                'department_id' => $this->input->post('department_id') !== '' ? (int)$this->input->post('department_id') : null,
                'level' => (int)($this->input->post('level') ?: 1),
            ];
            $this->designations->update((int)$id, $data);
            $this->load->helper('activity');
            log_activity('designations', 'updated', (int)$id, 'Designation: '.$data['designation_name']);
            $this->session->set_flashdata('success', 'Designation updated');
            redirect('designations'); return;
        }
        $departments = [];
        if ($this->db->table_exists('departments')){
            $departments = $this->db->select('id, dept_name')->from('departments')->order_by('dept_name','ASC')->get()->result();
        }
        $this->load->view('designations/form', [ 'action' => 'edit', 'row' => $row, 'departments' => $departments ]);
    }

    // POST /designations/{id}/delete
    public function delete($id){
        $this->designations->soft_delete((int)$id);
        $this->load->helper('activity');
        log_activity('designations', 'deleted', (int)$id, 'Designation deleted');
        $this->session->set_flashdata('success', 'Designation removed');
        redirect('designations');
    }
}

