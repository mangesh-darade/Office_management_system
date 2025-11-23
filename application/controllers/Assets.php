<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assets extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->load->model('Asset_model', 'assets');
    }

    private function is_admin_hr(){
        $role_id = (int)$this->session->userdata('role_id');
        return in_array($role_id, [1,2], true);
    }

    // GET /assets
    public function index(){
        if (!$this->is_admin_hr()) {
            redirect('assets-mgmt/my');
            return;
        }
        $rows = $this->assets->all_with_current_owner();
        $this->load->view('assets/index', ['rows' => $rows]);
    }

    // GET/POST /assets/create
    public function create(){
        if (!$this->is_admin_hr()) { show_error('Forbidden', 403); }
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
        if (!$this->is_admin_hr()) { show_error('Forbidden', 403); }
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
        if (!$this->is_admin_hr()) { show_error('Forbidden', 403); }
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
        if (!$this->is_admin_hr()) { show_error('Forbidden', 403); }
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
