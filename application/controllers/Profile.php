<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session']);
        $this->load->helper(['url']);
        $this->load->model(['User_model','Employee_model']);
    }

    public function index()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('login'); return; }
        $user = $this->User_model->get($uid);
        $employee = null;
        if (!empty($user)) {
            $employee = $this->db->where('user_id', $user->id)->get('employees')->row();
        }
        $this->load->view('profile/index', [
            'user' => $user,
            'employee' => $employee
        ]);
    }
}
