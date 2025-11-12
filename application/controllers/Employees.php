<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employees extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Employee_model');
    }

    // GET /employees
    public function index()
    {
        $q = $this->input->get('q');
        $employees = $this->Employee_model->all(100, 0, $q);
        $data = [ 'employees' => $employees, 'q' => $q ];
        $this->load->view('employees/list', $data);
    }

    // GET /employees/create, POST /employees/create
    public function create()
    {
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
            $payload = [
                'user_id' => (int)$this->input->post('user_id'),
                'emp_code' => trim($this->input->post('emp_code')),
                'first_name' => trim($this->input->post('first_name')),
                'last_name' => trim($this->input->post('last_name')),
                'department' => $dept_name,
                'designation' => $desg_name,
                'reporting_to' => $this->input->post('reporting_to') !== '' ? (int)$this->input->post('reporting_to') : null,
                'employment_type' => $this->input->post('employment_type') ?: 'full_time',
                'join_date' => $this->input->post('join_date') ?: null,
                'phone' => trim($this->input->post('phone')),
            ];
            $id = $this->Employee_model->create($payload);
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
        $this->load->view('employees/form', ['action' => 'create']);
    }

    // GET /employees/{id}
    public function show($id)
    {
        $employee = $this->Employee_model->find((int)$id);
        if (!$employee) show_404();
        $this->load->view('employees/view', ['employee' => $employee]);
    }

    // GET /employees/{id}/edit, POST /employees/{id}/edit
    public function edit($id)
    {
        $employee = $this->Employee_model->find((int)$id);
        if (!$employee) show_404();

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
            $payload = [
                'emp_code' => trim($this->input->post('emp_code')),
                'first_name' => trim($this->input->post('first_name')),
                'last_name' => trim($this->input->post('last_name')),
                'department' => $dept_name,
                'designation' => $desg_name,
                'reporting_to' => $this->input->post('reporting_to') !== '' ? (int)$this->input->post('reporting_to') : null,
                'employment_type' => $this->input->post('employment_type') ?: 'full_time',
                'join_date' => $this->input->post('join_date') ?: null,
                'phone' => trim($this->input->post('phone')),
            ];
            $this->Employee_model->update((int)$id, $payload);
            $this->load->helper('activity');
            $fn = isset($payload['first_name']) ? $payload['first_name'] : '';
            $ln = isset($payload['last_name']) ? $payload['last_name'] : '';
            $name = trim($fn.' '.$ln);
            $desc = $name !== '' ? ('Employee: '.$name) : ('Employee #'.(int)$id);
            log_activity('employees', 'updated', (int)$id, $desc);
            $this->session->set_flashdata('success', 'Employee updated');
            redirect('employees/'.$id);
            return;
        }
        $this->load->view('employees/form', ['action' => 'edit', 'employee' => $employee]);
    }

    // POST /employees/{id}/delete
    public function delete($id)
    {
        $this->Employee_model->delete((int)$id);
        $this->load->helper('activity');
        log_activity('employees', 'deleted', (int)$id, 'Employee deleted');
        $this->session->set_flashdata('success', 'Employee deleted');
        redirect('employees');
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
}
