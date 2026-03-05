<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Performance extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Performance_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'permission']);
        if (!$this->session->userdata('user_id')) { redirect('auth/login'); }
        $role_id = (int)$this->session->userdata('role_id');
        // Super Admin (role_id 1) can always access; other roles must have explicit module permission
        $is_superadmin = ($role_id === 1);
        $has_perf = function_exists('has_module_access') && (
            has_module_access('performance') || has_module_access('performance_create') ||
            has_module_access('performance_view') || has_module_access('performance_edit') ||
            has_module_access('performance_delete')
        );
        if (!$is_superadmin && !$has_perf) {
            show_error('You do not have permission to access Performance Appraisals.', 403);
        }
    }

    public function index(){
        $data['appraisals'] = $this->Performance_model->get_appraisals();
        $this->load->view('performance/index', $data);
    }

    public function create(){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && has_module_access('performance_create'))) {
            show_error('You do not have permission to create appraisals.', 403);
        }
        if ($this->input->method() === 'post'){
            $data = [
                'employee_id' => $this->input->post('employee_id'),
                'manager_id' => $this->session->userdata('user_id'),
                'period' => $this->input->post('period'),
                'kpi_score' => $this->input->post('kpi_score'),
                'rating' => $this->input->post('rating'),
                'comments' => $this->input->post('comments'),
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->Performance_model->save($data);
            $this->session->set_flashdata('success', 'Appraisal submitted.');
            redirect('performance');
        }
        $this->load->model('Employee_model');
        $data['employees'] = $this->Employee_model->all(100, 0, '', []);
        $this->load->view('performance/create', $data);
    }

    public function view($id){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && (has_module_access('performance_view') || has_module_access('performance')))) {
            show_error('You do not have permission to view appraisals.', 403);
        }
        $appraisal = $this->Performance_model->get_appraisal((int)$id);
        if (!$appraisal) { show_404(); }
        $this->load->view('performance/view', ['appraisal' => $appraisal]);
    }

    public function edit($id){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && has_module_access('performance_edit'))) {
            show_error('You do not have permission to edit appraisals.', 403);
        }
        $appraisal = $this->Performance_model->get_appraisal((int)$id);
        if (!$appraisal) { show_404(); }
        if ($this->input->method() === 'post'){
            $data = [
                'kpi_score' => $this->input->post('kpi_score'),
                'rating' => $this->input->post('rating'),
                'comments' => $this->input->post('comments'),
                'period' => $this->input->post('period'),
                'status' => $this->input->post('status') ? $this->input->post('status') : 'draft',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->Performance_model->update((int)$id, $data);
            $this->session->set_flashdata('success', 'Appraisal updated.');
            redirect('performance');
        }
        $this->load->model('Employee_model');
        $data['employees'] = $this->Employee_model->all(100, 0, '', []);
        $data['appraisal'] = $appraisal;
        $this->load->view('performance/edit', $data);
    }

    public function delete($id){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && has_module_access('performance_delete'))) {
            show_error('You do not have permission to delete appraisals.', 403);
        }
        if ($this->input->method() !== 'post') { show_404(); }
        $appraisal = $this->Performance_model->get_appraisal((int)$id);
        if (!$appraisal) { show_404(); }
        $this->Performance_model->delete((int)$id);
        $this->session->set_flashdata('success', 'Appraisal deleted.');
        redirect('performance');
    }
}
