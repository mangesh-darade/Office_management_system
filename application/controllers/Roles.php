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
        $this->ensure_schema();
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

    public function store() {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $name = trim($this->input->post('name', true) ?: '');
        if ($name === '') {
            $this->session->set_flashdata('error', 'Role name is required.');
            redirect('roles');
            return;
        }
        if (!$this->db->table_exists('roles')) {
            $this->session->set_flashdata('error', 'Roles table is not available.');
            redirect('roles');
            return;
        }
        $exists = $this->db->where('name', $name)->get('roles')->row();
        if ($exists) {
            $this->session->set_flashdata('error', 'Role already exists.');
            redirect('roles');
            return;
        }
        $data = ['name' => $name];
        if ($this->db->field_exists('is_active', 'roles')) {
            $data['is_active'] = 1;
        }
        if ($this->db->field_exists('sort_order', 'roles')) {
            $maxRow = $this->db->select_max('sort_order')->get('roles')->row();
            $next = 1;
            if ($maxRow && isset($maxRow->sort_order)) {
                $next = (int)$maxRow->sort_order + 1;
            }
            $data['sort_order'] = $next;
        }
        $this->db->insert('roles', $data);
        $this->session->set_flashdata('success', 'Role added.');
        redirect('roles');
    }

    private function ensure_schema() {
        if ($this->db->table_exists('roles')) {
            return;
        }
        $sql = "CREATE TABLE `roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `sort_order` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $this->db->query($sql);
        $defaults = [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Lead',
            4 => 'Staff',
        ];
        foreach ($defaults as $id => $name) {
            $row = [
                'id' => (int)$id,
                'name' => $name,
                'is_active' => 1,
                'sort_order' => (int)$id,
            ];
            $this->db->insert('roles', $row);
        }
    }
}
