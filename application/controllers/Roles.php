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
        $groupType = strtolower(trim((string)$this->input->post('group_type', true)));
        if ($groupType !== 'admin' && $groupType !== 'user') {
            $groupType = 'user';
        }
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
        if ($this->db->field_exists('group_type', 'roles')) {
            $data['group_type'] = $groupType;
        }
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
        if (!$this->db->table_exists('roles')) {
            $sql = "CREATE TABLE `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `group_type` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        if ($this->db->table_exists('roles') && !$this->db->field_exists('group_type', 'roles')) {
            $this->db->query("ALTER TABLE `roles` ADD `group_type` varchar(50) DEFAULT NULL AFTER `name`");
        }

        if ($this->db->table_exists('roles')) {
            $count = $this->db->count_all('roles');
            if ((int)$count === 0) {
                $defaults = [
                    1 => ['name' => 'Admin',   'group_type' => 'admin'],
                    2 => ['name' => 'Manager', 'group_type' => 'admin'],
                    3 => ['name' => 'Lead',    'group_type' => 'admin'],
                    4 => ['name' => 'Staff',   'group_type' => 'user'],
                ];
                foreach ($defaults as $id => $cfg) {
                    $row = [
                        'id'         => (int)$id,
                        'name'       => $cfg['name'],
                        'group_type' => $cfg['group_type'],
                        'is_active'  => 1,
                        'sort_order' => (int)$id,
                    ];
                    $this->db->insert('roles', $row);
                }
            } else {
                if ($this->db->field_exists('group_type', 'roles')) {
                    $this->db->where_in('id', [1, 2, 3]);
                    $this->db->where("(group_type IS NULL OR group_type = '')", null, false);
                    $this->db->update('roles', ['group_type' => 'admin']);

                    $this->db->where('id', 4);
                    $this->db->where("(group_type IS NULL OR group_type = '')", null, false);
                    $this->db->update('roles', ['group_type' => 'user']);
                }
            }
        }
    }
}
