<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function index(){
        $user_id = $this->session->userdata('user_id');
        $role_id = $this->session->userdata('role_id');
        if (!$user_id) { redirect('auth/login'); return; }
        $this->load->view('dashboard/index', ['role_id' => $role_id]);
    }
}
