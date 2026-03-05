<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shifts extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library(['session', 'form_validation']);
        $this->load->model('Shift_model');

        // Check login
        if (!$this->session->userdata('user_id')) {
            redirect('login');
        }
    }

    public function index() {
        $role_id = (int)$this->session->userdata('role_id');
        // Super Admin (role_id 1) can always view; other roles must have explicit view permission
        $is_superadmin = ($role_id === 1);
        if (
            !$is_superadmin &&
            (!function_exists('has_module_access') ||
             (!has_module_access('shifts') && !has_module_access('shifts_view')))
        ) {
            show_error('Access Denied', 403);
        }

        $data['shifts'] = $this->Shift_model->get_all();
        $this->load->view('shifts/index', $data);
    }

    public function create() {
        $role_id = (int)$this->session->userdata('role_id');
        // Super Admin (role_id 1) can always manage; other roles must have explicit manage permission
        $is_superadmin = ($role_id === 1);
        if (
            !$is_superadmin &&
            (!function_exists('has_module_access') ||
             (!has_module_access('shifts') && !has_module_access('shifts_manage')))
        ) {
            show_error('Access Denied', 403);
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Shift Name', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('start_time', 'Start Time', 'required');
            $this->form_validation->set_rules('end_time', 'End Time', 'required');

            if ($this->form_validation->run()) {
                $data = [
                    'name' => $this->input->post('name'),
                    'start_time' => $this->input->post('start_time'),
                    'end_time' => $this->input->post('end_time'),
                    'late_grace_period' => (int)$this->input->post('late_grace_period'),
                    'early_exit_grace_period' => (int)$this->input->post('early_exit_grace_period'),
                    'is_active' => $this->input->post('is_active') ? 1 : 0
                ];
                
                if ($this->Shift_model->create($data)) {
                    $this->session->set_flashdata('success', 'Shift created successfully.');
                    redirect('shifts');
                } else {
                    $this->session->set_flashdata('error', 'Failed to create shift.');
                }
            }
        }

        $this->load->view('shifts/form', ['action' => 'create']);
    }

    public function edit($id) {
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (
            !$is_superadmin &&
            (!function_exists('has_module_access') ||
             (!has_module_access('shifts') && !has_module_access('shifts_manage')))
        ) {
            show_error('Access Denied', 403);
        }

        $shift = $this->Shift_model->get($id);
        if (!$shift) show_404();

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Shift Name', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('start_time', 'Start Time', 'required');
            $this->form_validation->set_rules('end_time', 'End Time', 'required');

            if ($this->form_validation->run()) {
                $data = [
                    'name' => $this->input->post('name'),
                    'start_time' => $this->input->post('start_time'),
                    'end_time' => $this->input->post('end_time'),
                    'late_grace_period' => (int)$this->input->post('late_grace_period'),
                    'early_exit_grace_period' => (int)$this->input->post('early_exit_grace_period'),
                    'is_active' => $this->input->post('is_active') ? 1 : 0
                ];
                
                if ($this->Shift_model->update($id, $data)) {
                    $this->session->set_flashdata('success', 'Shift updated successfully.');
                    redirect('shifts');
                } else {
                    $this->session->set_flashdata('error', 'Failed to update shift.');
                }
            }
        }

        $this->load->view('shifts/form', ['action' => 'edit', 'shift' => $shift]);
    }

    public function delete($id) {
        if ($this->input->method() !== 'post') { show_404(); }

        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (
            !$is_superadmin &&
            (!function_exists('has_module_access') ||
             (!has_module_access('shifts') && !has_module_access('shifts_manage')))
        ) {
            show_error('Access Denied', 403);
        }
        
        // Prevent deletion of ID 1 (General Shift)
        if ($id == 1) {
            $this->session->set_flashdata('error', 'Cannot delete the default General Shift.');
            redirect('shifts');
        }

        if ($this->Shift_model->delete($id)) {
            $this->session->set_flashdata('success', 'Shift deleted successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete shift.');
        }
        redirect('shifts');
    }
}
