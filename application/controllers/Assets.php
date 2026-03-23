<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assets extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access(['assets_mgmt', 'assets_list', 'assets'], true);
        
        $this->load->model('Asset_model', 'assets');
    }

    private function can_manage_assets(){
        return is_admin_group() || has_module_access('assets_manage');
    }

    // GET /assets
    public function index(){
        if (!$this->can_manage_assets()) {
            redirect('assets-mgmt/my');
            return;
        }
        $rows = $this->assets->all_with_current_owner();
        $this->load->view('assets/index', ['rows' => $rows]);
    }

    // GET/POST /assets/create
    public function create(){
        require_module_access(['assets_add', 'assets_mgmt', 'assets'], true);
        if ($this->input->method() === 'post') {
            $data = [
                'name' => trim($this->input->post('name')),
                'category' => trim($this->input->post('category')),
                'brand' => trim($this->input->post('brand')),
                'model' => trim($this->input->post('model')),
                'serial_no' => trim($this->input->post('serial_no')),
                'asset_tag' => trim($this->input->post('asset_tag')),
                'ram' => trim($this->input->post('ram')),
                'hdd' => trim($this->input->post('hdd')),
                'status' => $this->input->post('status') ?: 'in_stock',
                'purchased_on' => $this->input->post('purchased_on') ?: null,
                'notes' => trim($this->input->post('notes')),
            ];
            $id = $this->assets->create($data);
            $this->session->set_flashdata('success', 'Asset created');
            redirect('assets-mgmt');
            return;
        }
        $this->load->view('assets/form', ['action' => 'create', 'row' => null]);
    }

    // GET/POST /assets/edit/{id}
    public function edit($id){
        require_module_access(['assets_edit', 'assets_mgmt', 'assets'], true);
        $id = (int)$id;
        $row = $this->assets->find($id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post') {
            $data = [
                'name' => trim($this->input->post('name')),
                'category' => trim($this->input->post('category')),
                'brand' => trim($this->input->post('brand')),
                'model' => trim($this->input->post('model')),
                'serial_no' => trim($this->input->post('serial_no')),
                'asset_tag' => trim($this->input->post('asset_tag')),
                'status' => $this->input->post('status') ?: 'in_stock',
                'purchased_on' => $this->input->post('purchased_on') ?: null,
                'notes' => trim($this->input->post('notes')),
            ];
            $this->assets->update($id, $data);
            $this->session->set_flashdata('success', 'Asset updated');
            redirect('assets-mgmt');
            return;
        }
        $this->load->view('assets/form', ['action' => 'edit', 'row' => $row]);
    }

    // GET/POST /assets/assign/{id}
    public function assign($id){
        require_module_access(['assets_assign', 'assets_mgmt', 'assets'], true);
        $id = (int)$id;
        $row = $this->assets->find($id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->input->post('user_id');
            $date = $this->input->post('allocated_on') ?: date('Y-m-d');
            if ($user_id <= 0) {
                $this->session->set_flashdata('error', 'Please select a user.');
                redirect('assets-mgmt/assign/'.$id);
                return;
            }
            $this->assets->assign_to_user($id, $user_id, $date, trim($this->input->post('remarks')));
            $this->session->set_flashdata('success', 'Asset assigned');
            redirect('assets-mgmt');
            return;
        }
        $users = $this->assets->get_user_options();
        $current = $this->assets->current_allocation($id);
        $this->load->view('assets/assign', ['row' => $row, 'users' => $users, 'current' => $current]);
    }

    // POST /assets/return/{id}
    public function return_asset($id){
        require_module_access(['assets_assign', 'assets_mgmt', 'assets'], true);
        $id = (int)$id;
        $this->assets->mark_returned($id, date('Y-m-d'));
        $this->session->set_flashdata('success', 'Asset marked as returned');
        redirect('assets-mgmt');
    }

    // GET /assets/my
    public function my(){
        $user_id = (int)$this->session->userdata('user_id');
        $rows = $this->assets->assets_for_user($user_id);
        $this->load->view('assets/my', ['rows' => $rows]);
    }
}
