<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    public function index() {
        $rows = [];
        if ($this->db->table_exists('roles')) {
            $this->db->from('roles');
            if ($this->db->field_exists('is_active', 'roles')) {
                $this->db->order_by('is_active', 'DESC');
            }
            if ($this->db->field_exists('sort_order', 'roles')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            $rows = $this->db->get()->result();
        }
        $data = [
            'title' => 'Roles',
            'rows'  => $rows,
        ];
        $this->load->view('roles/index', $data);
    }
}
