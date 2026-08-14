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

        $data = $this->_collect_form_data(false);
        if (empty($data['service_type']) || empty($data['service_name'])) {
            $this->session->set_flashdata('error', 'Service type and name are required.');
            redirect('api-integrations/create');
            return;
        }

        $existing = $this->api->get_first_by_service_type($data['service_type']);
        if ($existing) {
            if (empty($data['auth_token'])) {
                unset($data['auth_token']);
            }
            $this->load->helper('change_tracker');
            $old_data = track_changes_before('api_integrations', (int) $existing->id);
            $ok = $this->api->update($existing->id, $data);
            if (!$ok) {
                $this->session->set_flashdata('error', $this->api->last_error());
                redirect('api-integrations/create');
                return;
            }
            track_changes_after('api_integrations', 'api_integrations', (int) $existing->id, $old_data, $data, 'API Integration: ' . $data['service_name']);
            $this->_flash_after_whatsapp_save((int) $existing->id, $data['service_type'], 'Saved. Updated the existing ' . $data['service_type'] . ' integration.');
            redirect('api-integrations');
            return;
        }

        $id = $this->api->create($data);
        if ($id) {
            $this->load->helper('change_tracker');
            $description = 'API Integration: ' . $data['service_name'] . ' (' . $data['service_type'] . ')';
            auto_log_insert('api_integrations', 'api_integrations', $id, $data, $description);
            $this->_flash_after_whatsapp_save((int) $id, $data['service_type'], 'API integration created successfully.');
            redirect('api-integrations');
            return;
        }

        $this->session->set_flashdata('error', $this->api->last_error());
        redirect('api-integrations/create');
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
        
        $data = $this->_collect_form_data(true);

        if (empty($data['service_type']) || empty($data['service_name'])) {
            $this->session->set_flashdata('error', 'Service type and name are required.');
            redirect('api-integrations/edit/' . $id);
            return;
        }
        
        $ok = $this->api->update($id, $data);
        if (!$ok) {
            $this->session->set_flashdata('error', $this->api->last_error());
            redirect('api-integrations/edit/' . $id);
            return;
        }
        
        // Log update with change tracking
        $description = 'API Integration: ' . $data['service_name'] . ' (' . $data['service_type'] . ')';
        track_changes_after('api_integrations', 'api_integrations', (int)$id, $old_data, $data, $description);
        
        $this->_flash_after_whatsapp_save((int) $id, $data['service_type'], 'API integration updated successfully.');
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

    /**
     * POST /api-integrations/test-whatsapp
     */
    public function test_whatsapp()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $d = diagnose_whatsapp_connection($id > 0 ? $id : null);
        $msg = format_whatsapp_diagnose_message($d);
        if (!empty($d['ok'])) {
            if (!empty($d['error'])) {
                $this->session->set_flashdata('warning', $msg);
            } else {
                $this->session->set_flashdata('success', $msg);
            }
        } else {
            $this->session->set_flashdata('error', $msg);
        }
        if ($id > 0) {
            redirect('api-integrations/edit/' . $id);
            return;
        }
        redirect('api-integrations');
    }

    private function _flash_after_whatsapp_save($integration_id, $service_type, $saved_msg)
    {
        if ($service_type !== 'whatsapp') {
            $this->session->set_flashdata('success', $saved_msg);
            return;
        }
        $d = diagnose_whatsapp_connection((int) $integration_id);
        $diag = format_whatsapp_diagnose_message($d);
        if (!empty($d['ok'])) {
            if (!empty($d['error'])) {
                $this->session->set_flashdata('success', $saved_msg);
                $this->session->set_flashdata('warning', $diag);
                return;
            }
            $this->session->set_flashdata('success', $saved_msg . ' ' . $diag);
            return;
        }
        $this->session->set_flashdata('success', $saved_msg);
        $this->session->set_flashdata('error', $diag);
    }

    private function _post_str($key)
    {
        $v = $this->input->post($key);
        if ($v === false || $v === null) {
            return '';
        }
        return trim((string) $v);
    }

    private function _collect_form_data($is_update)
    {
        $type = $this->_post_str('service_type');
        $from_name = $this->_post_str('from_name');
        if ($type === 'whatsapp') {
            $from_name = $this->_post_str('from_name_wa');
        } elseif ($type === 'jitsi') {
            $jitsi = $this->_post_str('jitsi_app_id');
            if ($jitsi !== '') {
                $from_name = $jitsi;
            }
        }
        $data = array(
            'service_type' => $type,
            'service_name' => $this->_post_str('service_name'),
            'account_id' => $this->_post_str('account_id'),
            'auth_token' => $this->_post_str('auth_token'),
            'from_email' => $this->_post_str('from_email'),
            'from_name' => $from_name,
            'from_number' => $this->_post_str('from_number'),
            'content_sid' => $this->_post_str('content_sid'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'is_default' => $this->input->post('is_default') ? 1 : 0,
            'notes' => $this->_resolve_notes(),
        );
        $data = $this->_merge_whatsapp_secret_fields($data);
        return $this->_normalize_auth_token_field($data, $is_update);
    }

    private function _merge_whatsapp_secret_fields($data)
    {
        if ($data['service_type'] !== 'whatsapp') {
            return $data;
        }
        $verify = $this->_post_str('webhook_verify_token');
        if ($verify !== '') {
            $data['webhook_verify_token'] = $verify;
        }
        $secret = $this->_post_str('app_secret');
        if ($secret !== '') {
            $data['app_secret'] = $secret;
        }
        return $data;
    }

    private function _normalize_auth_token_field($data, $is_update)
    {
        if (!isset($data['auth_token'])) {
            return $data;
        }
        $token = normalize_meta_access_token($data['auth_token']);
        if ($token === '') {
            if ($is_update) {
                unset($data['auth_token']);
            } else {
                $data['auth_token'] = '';
            }
            return $data;
        }
        $data['auth_token'] = $token;
        return $data;
    }

    private function _resolve_notes() {
        $service_type = $this->_post_str('service_type');
        if ($service_type === 'jitsi') {
            $json_notes = $this->_post_str('jitsi_notes_json');
            if ($json_notes !== '') {
                return $json_notes;
            }
        }
        return $this->_post_str('notes');
    }
}

