<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permissions extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        // Admin only
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) { show_error('You do not have permission to access this page.', 403); }
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        if (!$this->db->table_exists('permissions')) {
            $sql = "CREATE TABLE `permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_id` int(11) NOT NULL,
                `module` varchar(100) NOT NULL,
                `can_access` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `idx_role_module` (`role_id`,`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        // Ensure a simple roles table exists so role labels can be managed from DB.
        // IMPORTANT: IDs must stay consistent with existing usage: 1=Admin, 2=Manager/HR, 3=Lead, 4=Staff.
        if (!$this->db->table_exists('roles')) {
            $sql = "CREATE TABLE `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        // Seed default roles only if table is empty (and respect existing schema)
        if ($this->db->table_exists('roles')) {
            $count = $this->db->count_all('roles');
            if ((int)$count === 0) {
                $defaults = [
                    1 => 'Admin',
                    2 => 'Manager',
                    3 => 'Lead',
                    4 => 'Staff',
                ];
                // Detect optional columns on existing roles table
                $hasActive = $this->db->field_exists('is_active', 'roles');
                $hasSort   = $this->db->field_exists('sort_order', 'roles');

                foreach ($defaults as $id => $name) {
                    $row = [
                        'id'   => (int)$id,
                        'name' => $name,
                    ];
                    if ($hasActive) { $row['is_active'] = 1; }
                    if ($hasSort)   { $row['sort_order'] = (int)$id; }
                    $this->db->insert('roles', $row);
                }
            }
        }
    }

    private function roles()
    {
        // Prefer DB-defined roles when available
        $out = [];
        if ($this->db->table_exists('roles')) {
            $this->db->from('roles');
            // Apply is_active filter only if column exists
            if ($this->db->field_exists('is_active', 'roles')) {
                $this->db->where('is_active', 1);
            }
            // Apply sort_order only if column exists
            if ($this->db->field_exists('sort_order', 'roles')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            $rows = $this->db->get()->result();
            foreach ($rows as $row) {
                $rid = (int)$row->id;
                if ($rid <= 0) { continue; }
                $out[$rid] = $row->name;
            }
        }
        if (!empty($out)) {
            return $out;
        }

        // Fallback mapping if roles table is missing or empty
        return [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Lead',
            4 => 'Staff',
        ];
    }


    private function modules()
    {
        $out = [];

        if ($this->db->table_exists('modules')) {
            $this->db->from('modules');
            // Optional: only active modules
            if ($this->db->field_exists('is_active', 'modules')) {
                $this->db->where('is_active', 1);
            }
            // Optional: custom sort order
            if ($this->db->field_exists('sort_order', 'modules')) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('module_label', 'ASC');

            $rows = $this->db->get()->result();
            foreach ($rows as $row) {
                $key = strtolower(trim($row->module_key));
                if ($key === '') { continue; }
                $out[$key] = $row->module_label;
            }
        }

        return $out;
    }


    public function index()
    {
        // Admin-only enforced in __construct

        $existing = [];
        $res = $this->db->get('permissions')->result();
        foreach ($res as $row) {
            $existing[(int)$row->role_id][strtolower($row->module)] = (int)$row->can_access;
        }

        $data = [
            'roles' => $this->roles(),
            'modules' => $this->modules(),
            'existing' => $existing,
        ];
        $this->load->view('permissions/index', $data);
    }

    public function save()
    {
        // Admin-only enforced in __construct

        $roles = $this->roles();
        $modules = $this->modules();
        $perms = $this->input->post('perms'); // perms[role_id][module] = 1

        // Clear and re-insert (simple approach for small matrix)
        $this->db->trans_start();
        $this->db->truncate('permissions');
        foreach ($roles as $rid => $rname) {
            foreach ($modules as $key => $label) {
                $can = (isset($perms[$rid]) && isset($perms[$rid][$key]) && (int)$perms[$rid][$key] === 1) ? 1 : 0;
                $this->db->insert('permissions', [
                    'role_id' => (int)$rid,
                    'module' => $key,
                    'can_access' => $can
                ]);
            }
        }
        $this->db->trans_complete();

        $this->session->set_flashdata('success', 'Permissions updated successfully.');
        redirect('permissions');
    }
}

