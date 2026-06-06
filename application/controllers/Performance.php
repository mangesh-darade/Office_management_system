<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Performance extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Performance_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'permission']);
        
        // RBAC Audit: Centralized module access check
        require_module_access(['performance', 'performance_create', 'performance_view', 'performance_edit', 'performance_delete', 'performance_self_assess', 'performance_export'], true);
    }

    public function index(){
        $data['appraisals'] = $this->Performance_model->get_appraisals();
        $this->load->view('performance/index', $data);
    }

    public function create(){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && (has_module_access('performance_create') || has_module_access('performance')))) {
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
            return;
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
        if (!$is_superadmin && !(function_exists('has_module_access') && (has_module_access('performance_edit') || has_module_access('performance')))) {
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
            return;
        }
        $this->load->model('Employee_model');
        $data['employees'] = $this->Employee_model->all(100, 0, '', []);
        $data['appraisal'] = $appraisal;
        $this->load->view('performance/edit', $data);
    }

    public function delete($id){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && (has_module_access('performance_delete') || has_module_access('performance')))) {
            show_error('You do not have permission to delete appraisals.', 403);
        }
        if ($this->input->method() !== 'post') { show_404(); }
        $appraisal = $this->Performance_model->get_appraisal((int)$id);
        if (!$appraisal) { show_404(); }
        $this->Performance_model->delete((int)$id);
        $this->session->set_flashdata('success', 'Appraisal deleted.');
        redirect('performance');
    }

    public function export(){
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && !(function_exists('has_module_access') && (has_module_access('performance_export') || has_module_access('performance') || has_module_access('performance_view')))) {
            show_error('Access denied.', 403);
        }
        $appraisals = $this->Performance_model->get_appraisals();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="performance_appraisals_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Employee', 'Period', 'KPI Score', 'Rating', 'Status', 'Comments', 'Created At']);
        foreach ($appraisals as $a) {
            fputcsv($out, [
                $a->id,
                isset($a->employee_name) ? $a->employee_name : $a->employee_id,
                $a->period,
                $a->kpi_score,
                $a->rating,
                $a->status,
                strip_tags($a->comments),
                $a->created_at,
            ]);
        }
        fclose($out);
        exit;
    }

    public function self_assess($id = null){
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);

        if (!$is_superadmin && function_exists('has_module_access') && !has_module_access('performance_self_assess') && !has_module_access('performance') && !has_module_access('performance_view')) {
            show_error('Access denied.', 403);
        }

        if ($this->input->method() === 'post') {
            $appraisal_id = (int)$this->input->post('appraisal_id');
            $appraisal = $this->Performance_model->get_appraisal($appraisal_id);
            if (!$appraisal || (int)$appraisal->employee_id !== $user_id) {
                $this->session->set_flashdata('error', 'Appraisal not found or access denied.');
                redirect('performance');
                return;
            }
            $self_data = [
                'self_rating'   => $this->input->post('self_rating'),
                'self_comments' => $this->input->post('self_comments'),
                'status'        => 'submitted',
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
            $this->Performance_model->update($appraisal_id, $self_data);
            $this->session->set_flashdata('success', 'Self-assessment submitted.');
            redirect('performance');
            return;
        }

        // Show pending appraisals for this employee
        $appraisals = $this->Performance_model->get_appraisals_for_employee($user_id);
        if ($id) {
            $appraisal = $this->Performance_model->get_appraisal((int)$id);
            if (!$appraisal || (int)$appraisal->employee_id !== $user_id) { show_404(); return; }
        } else {
            $appraisal = null;
        }
        $this->load->view('performance/self_assess', [
            'appraisals' => $appraisals,
            'appraisal'  => $appraisal,
        ]);
    }
}
