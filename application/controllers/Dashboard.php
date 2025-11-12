<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function index(){
        $user_id = $this->session->userdata('user_id');
        $role_id = $this->session->userdata('role_id');
        if (!$user_id) { redirect('auth/login'); return; }
        $announcements = [];
        $this->load->database();
        if ($this->db->table_exists('announcements')){
            $today = date('Y-m-d');
            $this->db->from('announcements')
                     ->where('status','published')
                     ->group_start()
                        ->where('start_date IS NULL', null, false)
                        ->or_where('start_date <=', $today)
                     ->group_end()
                     ->group_start()
                        ->where('end_date IS NULL', null, false)
                        ->or_where('end_date >=', $today)
                     ->group_end()
                     ->order_by('priority','DESC')
                     ->order_by('id','DESC')
                     ->limit(5);
            $announcements = $this->db->get()->result();
        }
        $this->load->view('dashboard/index', ['role_id' => $role_id, 'announcements' => $announcements]);
    }
}
