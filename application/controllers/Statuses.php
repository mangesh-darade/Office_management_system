<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statuses extends CI_Controller {
    
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('statuses', true);
        
        $this->load->model('Status_model', 'statuses');
    }
    
    // GET /statuses
    public function index(){
        $type = $this->input->get('type');
        $statuses = $this->statuses->get_all($type, false); // Get all including inactive
        
        $types = ['requirements', 'projects', 'tasks', 'my_works'];
        
        $this->load->view('statuses/index', [
            'statuses' => $statuses,
            'types' => $types,
            'selected_type' => $type ?: null
        ]);
    }
    
    // GET /statuses/create, POST /statuses/create
    public function create(){
        if ($this->input->method() === 'post'){
            $data = [
                'name' => trim($this->input->post('name')),
                'code' => trim($this->input->post('code')),
                'type' => $this->input->post('type'),
                'color' => $this->input->post('color') ?: '#6c757d',
                'icon' => $this->input->post('icon') ?: null,
                'display_order' => $this->input->post('display_order') !== '' ? (int)$this->input->post('display_order') : 0,
                'is_active' => $this->input->post('is_active') ? 1 : 0,
                'description' => $this->input->post('description') ?: null,
            ];
            
            // Check if code already exists for this type
            $existing = $this->statuses->get_by_code($data['code'], $data['type']);
            if ($existing) {
                $this->session->set_flashdata('error', 'Status code already exists for this type.');
                redirect('statuses/create');
                return;
            }
            
            $id = $this->statuses->create($data);
            $this->session->set_flashdata('success', 'Status created successfully');
            redirect('statuses');
            return;
        }
        
        $types = ['requirements', 'projects', 'tasks', 'my_works'];
        $this->load->view('statuses/form', ['action' => 'create', 'types' => $types]);
    }
    
    // GET /statuses/view/{id}
    public function view($id){
        $status = $this->statuses->get_by_id((int)$id);
        if (!$status) show_404();
        $this->load->view('statuses/view', ['status' => $status]);
    }
    
    // GET /statuses/{id} - Alias for view
    public function show($id){
        $this->view($id);
    }
    
    // GET /statuses/{id}/edit, POST /statuses/{id}/edit
    public function edit($id){
        $status = $this->statuses->get_by_id((int)$id);
        if (!$status) show_404();
        
        if ($this->input->method() === 'post'){
            $data = [
                'name' => trim($this->input->post('name')),
                'code' => trim($this->input->post('code')),
                'type' => $this->input->post('type'),
                'color' => $this->input->post('color') ?: '#6c757d',
                'icon' => $this->input->post('icon') ?: null,
                'display_order' => $this->input->post('display_order') !== '' ? (int)$this->input->post('display_order') : 0,
                'is_active' => $this->input->post('is_active') ? 1 : 0,
                'description' => $this->input->post('description') ?: null,
            ];
            
            // Check if code already exists for this type (excluding current record)
            $existing = $this->statuses->get_by_code($data['code'], $data['type']);
            if ($existing && $existing->id != (int)$id) {
                $this->session->set_flashdata('error', 'Status code already exists for this type.');
                redirect('statuses/edit/'.$id);
                return;
            }
            
            $this->statuses->update((int)$id, $data);
            $this->session->set_flashdata('success', 'Status updated successfully');
            redirect('statuses');
            return;
        }
        
        $types = ['requirements', 'projects', 'tasks', 'my_works'];
        $this->load->view('statuses/form', ['action' => 'edit', 'status' => $status, 'types' => $types]);
    }
    
    // POST /statuses/{id}/delete
    public function delete($id){
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        $status = $this->statuses->get_by_id((int)$id);
        if (!$status) show_404();
        
        $this->statuses->delete((int)$id);
        $this->session->set_flashdata('success', 'Status deleted successfully');
        redirect('statuses');
    }
}

