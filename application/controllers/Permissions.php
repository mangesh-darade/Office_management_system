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
            'dashboard'      => 'Dashboard',
            'employees'      => 'Employees',
            'projects'       => 'Projects',
            'tasks'          => 'Tasks',
            'attendance'     => 'Attendance',
            'leaves'         => 'Leaves',
            'notifications'  => 'Notifications',
            'reports'        => 'Reports',
            'departments'    => 'Departments',
            'designations'   => 'Designations',
            'timesheets'     => 'Timesheets',
            'announcements'  => 'Announcements',
            'settings'       => 'Settings',
            'activity'       => 'Activity Logs',
            'permissions'    => 'Permission Manager',
            // Chat related
            'chats'          => 'Chats',
            'chats.grouping' => 'Chat Grouping',
            'calls'          => 'Calls',
            // Back-compat if previously stored as 'chat'
            'chat'           => 'Chat (legacy key)',
        ];
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

