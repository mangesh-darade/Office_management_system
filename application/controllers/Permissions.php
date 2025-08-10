<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permissions extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
    }

    private function roles()
    {
        // 1=Admin, 2=HR/Manager, 3=Lead, 4=Staff
        return [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Lead',
            4 => 'Staff',
        ];
    }

    private function modules()
    {
        return [
            'dashboard'    => 'Dashboard',
            'employees'    => 'Employees',
            'projects'     => 'Projects',
            'tasks'        => 'Tasks',
            'attendance'   => 'Attendance',
            'leaves'       => 'Leaves',
            'notifications'=> 'Notifications',
            'reports'      => 'Reports',
            'permissions'  => 'Permission Manager',
            // Chat related
            'chats'        => 'Chats',
            'chats.grouping' => 'Chat Grouping',
            'calls'        => 'Calls',
            // Back-compat if previously stored as 'chat'
            'chat'         => 'Chat (legacy key)',
        ];
    }

    public function index()
    {
        // Only allow Manager (2) and Lead (3) as per AuthHook default
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [2,3], true)) {
            show_error('You do not have permission to access this page.', 403);
        }

        if (!$this->db->table_exists('permissions')) {
            $this->session->set_flashdata('error', 'Permissions table does not exist. Please run the provided SQL to create it.');
        }

        $existing = [];
        if ($this->db->table_exists('permissions')) {
            $res = $this->db->get('permissions')->result();
            foreach ($res as $row) {
                $existing[(int)$row->role_id][strtolower($row->module)] = (int)$row->can_access;
            }
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
        // Only allow Manager (2) and Lead (3)
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [2,3], true)) {
            show_error('You do not have permission to perform this action.', 403);
        }

        if (!$this->db->table_exists('permissions')) {
            show_error('Permissions table is missing. Create it first.', 500);
        }

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
