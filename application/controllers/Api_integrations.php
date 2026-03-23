<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_integrations extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library(['session']);
        $this->load->model('Api_integration_model', 'api');
        
        // RBAC Audit: Centralized module access check
        require_module_access(['api_integrations', 'settings'], true);
    }
    
    /**
     * GET /api-integrations
     * List all API integrations
     */
    public function index() {
        $integrations = $this->api->get_all();
        $this->load->view('api_integrations/index', ['integrations' => $integrations]);
    }
    
    /**
     * GET /api-integrations/create
     * Show create form
     */
    public function create() {
        $this->load->view('api_integrations/form', ['action' => 'create']);
    }
    
    /**
     * POST /api-integrations/store
     * Store new integration
     */
    public function store() {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        
        $data = [
            'service_type' => $this->input->post('service_type'),
            'service_name' => trim($this->input->post('service_name')),
            'account_id' => trim($this->input->post('account_id')),
            'auth_token' => trim($this->input->post('auth_token')),
            'from_email' => trim($this->input->post('from_email')),
            'from_name' => trim($this->input->post('from_name')),
            'from_number' => trim($this->input->post('from_number')),
            'content_sid' => trim($this->input->post('content_sid')),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'is_default' => $this->input->post('is_default') ? 1 : 0,
            'notes' => trim($this->input->post('notes'))
        ];
        
        // Validation
        if (empty($data['service_type']) || empty($data['service_name'])) {
            $this->session->set_flashdata('error', 'Service type and name are required.');
            redirect('api-integrations/create');
            return;
        }
        
        $id = $this->api->create($data);
        
        if ($id) {
            // Log API integration creation
            $this->load->helper('change_tracker');
            $description = 'API Integration: ' . $data['service_name'] . ' (' . $data['service_type'] . ')';
            auto_log_insert('api_integrations', 'api_integrations', $id, $data, $description);
            
            $this->session->set_flashdata('success', 'API integration created successfully.');
            redirect('api-integrations');
        } else {
            $this->session->set_flashdata('error', 'Failed to create API integration.');
            redirect('api-integrations/create');
        }
    }
    
    /**
     * GET /api-integrations/edit/{id}
     * Show edit form
     */
    public function edit($id) {
        $integration = $this->api->get_by_id($id);
        if (!$integration) {
            show_404();
        }
        $this->load->view('api_integrations/form', ['action' => 'edit', 'integration' => $integration]);
    }
    
    /**
     * POST /api-integrations/update/{id}
     * Update integration
     */
    public function update($id) {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        
        $integration = $this->api->get_by_id($id);
        if (!$integration) {
            show_404();
        }
        
        // Load activity tracking helper
        $this->load->helper('change_tracker');
        
        // Get old data before update
        $old_data = track_changes_before('api_integrations', (int)$id);
        
        $data = [
            'service_type' => $this->input->post('service_type'),
            'service_name' => trim($this->input->post('service_name')),
            'account_id' => trim($this->input->post('account_id')),
            'auth_token' => trim($this->input->post('auth_token')),
            'from_email' => trim($this->input->post('from_email')),
            'from_name' => trim($this->input->post('from_name')),
            'from_number' => trim($this->input->post('from_number')),
            'content_sid' => trim($this->input->post('content_sid')),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'is_default' => $this->input->post('is_default') ? 1 : 0,
            'notes' => trim($this->input->post('notes'))
        ];
        
        // Validation
        if (empty($data['service_type']) || empty($data['service_name'])) {
            $this->session->set_flashdata('error', 'Service type and name are required.');
            redirect('api-integrations/edit/' . $id);
            return;
        }
        
        $this->api->update($id, $data);
        
        // Log update with change tracking
        $description = 'API Integration: ' . $data['service_name'] . ' (' . $data['service_type'] . ')';
        track_changes_after('api_integrations', 'api_integrations', (int)$id, $old_data, $data, $description);
        
        $this->session->set_flashdata('success', 'API integration updated successfully.');
        redirect('api-integrations');
    }
    
    /**
     * GET /api-integrations/delete/{id}
     * Delete integration
     */
    public function delete($id) {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        $integration = $this->api->get_by_id($id);
        if (!$integration) {
            show_404();
        }
        
        // Load activity tracking helper
        $this->load->helper('change_tracker');
        
        // Get old data before delete
        $old_data = track_changes_before('api_integrations', (int)$id);
        
        $this->api->delete($id);
        
        // Log deletion
        $description = 'API Integration deleted: ' . (isset($integration->service_name) ? $integration->service_name : 'ID #' . $id);
        auto_log_delete('api_integrations', 'api_integrations', (int)$id, $old_data, $description);
        
        $this->session->set_flashdata('success', 'API integration deleted successfully.');
        redirect('api-integrations');
    }
}

