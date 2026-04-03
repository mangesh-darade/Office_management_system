<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class External_training extends CI_Controller
{
    private function can_any_external_training()
    {
        if (!function_exists('has_module_access')) {
            return true;
        }
        return has_module_access('external_training')
            || has_module_access('external_training_list')
            || has_module_access('external_training_add')
            || has_module_access('external_training_edit')
            || has_module_access('external_training_delete');
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->model('External_training_model', 'ext');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'permission'));

        // Base gate: user must have at least one external training permission.
        if (function_exists('require_module_access') && !$this->can_any_external_training()) {
            require_module_access('external_training', true);
        }
    }

    public function index()
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_list'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $data['rows'] = $this->ext->all();
        $this->load->view('external_training/index', $data);
    }

    public function create()
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_add'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $data['row'] = null;
        $this->load->view('external_training/form', $data);
    }

    public function edit($id)
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_edit'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $row = $this->ext->get((int) $id);
        if (!$row) {
            show_404();
        }
        $data['row'] = $row;
        $this->load->view('external_training/form', $data);
    }

    public function save()
    {
        $id = (int) $this->input->post('id');
        if (function_exists('require_module_access')) {
            if ($id > 0) {
                require_module_access(array('external_training', 'external_training_edit'), true);
            } else {
                require_module_access(array('external_training', 'external_training_add'), true);
            }
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $name = trim((string) $this->input->post('name'));
        $embed_code = trim((string) $this->input->post('embed_code'));
        $is_active = $this->input->post('is_active') ? 1 : 0;

        if ($name === '' || $embed_code === '') {
            $this->session->set_flashdata('error', 'Name and Embed Code are required.');
            if ($id) {
                redirect('external-training/edit/' . $id);
            } else {
                redirect('external-training/create');
            }
            return;
        }

        $row = array(
            'name' => $name,
            'embed_code' => $embed_code,
            'is_active' => $is_active,
        );

        if ($id) {
            $this->ext->update($id, $row);
            $this->session->set_flashdata('success', 'External training updated.');
        } else {
            $this->ext->insert($row);
            $this->session->set_flashdata('success', 'External training created.');
        }
        redirect('external-training');
    }

    public function delete($id)
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_delete'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->ext->delete((int) $id);
        $this->session->set_flashdata('success', 'External training deleted.');
        redirect('external-training');
    }
}

