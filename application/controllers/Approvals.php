<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Approvals extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Approval_model');
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form', 'permission']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('approvals', true);
    }

    public function index() {
        $data['flows'] = $this->Approval_model->get_flows();
        $this->load->view('approvals/index', $data);
    }

    public function create() {
        $this->load->view('approvals/form');
    }

    public function edit($id) {
        $data['flow'] = $this->Approval_model->get_flow($id);
        if (!$data['flow']) show_404();
        $this->load->view('approvals/form', $data);
    }

    public function save() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $id = $this->input->post('id');

        $module = trim((string)$this->input->post('module'));
        $name = trim((string)$this->input->post('name'));

        if ($module === '' || $name === '') {
            $this->session->set_flashdata('error', 'Module and Name are required fields.');
            if ($id) {
                redirect('approvals/edit/' . (int)$id);
            } else {
                redirect('approvals/create');
            }
            return;
        }

        $data = [
            'module' => $module,
            'name' => $name,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        // Process steps from POST arrays
        $steps = [];
        $step_types = $this->input->post('step_type');
        $step_values = $this->input->post('step_value');

        if ($step_types && is_array($step_types)) {
            foreach ($step_types as $key => $type) {
                $steps[] = [
                    'approver_type' => $type,
                    'approver_value' => isset($step_values[$key]) ? $step_values[$key] : null
                ];
            }
        }

        if ($this->Approval_model->save_flow($id, $data, $steps)) {
            $this->session->set_flashdata('success', 'Approval flow saved successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to save approval flow.');
        }

        redirect('approvals');
    }

    /**
     * POST /approvals/delete/{id}
     */
    public function delete($id = null){
        $id = (int)$id;
        if (!$id) { show_404(); }
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }

        // Only admin can delete approval flows
        if ((int)$this->session->userdata('role_id') !== 1 && !(function_exists('is_admin_group') && is_admin_group())) {
            show_error('Access Denied', 403);
        }

        $this->Approval_model->delete_flow($id);
        $this->session->set_flashdata('success', 'Approval flow deleted.');
        redirect('approvals');
    }
}
