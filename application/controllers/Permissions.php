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
        $this->ensure_schema();
        // DB-driven access: rely on permissions table for module 'permissions'
        $this->load->helper('permission');
        $role_id = (int)$this->session->userdata('role_id');
        $allowed = false;
        if (function_exists('has_module_access')) {
            $allowed = has_module_access('permissions');
        }
        // Fallback: if no permissions row exists yet for 'permissions', allow Admin (role 1)
        if (!$allowed) {
            $hasPermRow = false;
            if ($this->db->table_exists('permissions')) {
                $this->db->where('module', 'permissions');
                $hasPermRow = ($this->db->count_all_results('permissions') > 0);
            }
            if (!$hasPermRow && $role_id === 1) {
                $allowed = true;
            }
        }
        if (!$allowed) { show_error('You do not have permission to access this page.', 403); }
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

        // Ensure a simple roles table exists so role labels and groups can be managed from DB.
        // IMPORTANT: IDs must stay consistent with existing usage: 1=Admin, 2=Manager/HR, 3=Lead, 4=Staff.
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

        // Add group_type column if missing on existing roles table
        if ($this->db->table_exists('roles') && !$this->db->field_exists('group_type', 'roles')) {
            $this->db->query("ALTER TABLE `roles` ADD `group_type` varchar(50) DEFAULT NULL AFTER `name`");
        }

        // Seed default roles only if table is empty (and respect existing schema)
        if ($this->db->table_exists('roles')) {
            $count = $this->db->count_all('roles');
            if ((int)$count === 0) {
                $defaults = [
                    1 => ['name' => 'Admin',   'group_type' => 'admin'],
                    2 => ['name' => 'Manager', 'group_type' => 'admin'],
                    3 => ['name' => 'Lead',    'group_type' => 'admin'],
                    4 => ['name' => 'Staff',   'group_type' => 'user'],
                ];
                $hasActive = $this->db->field_exists('is_active', 'roles');
                $hasSort   = $this->db->field_exists('sort_order', 'roles');

                foreach ($defaults as $id => $cfg) {
                    $row = [
                        'id'         => (int)$id,
                        'name'       => $cfg['name'],
                        'group_type' => $cfg['group_type'],
                    ];
                    if ($hasActive) { $row['is_active'] = 1; }
                    if ($hasSort)   { $row['sort_order'] = (int)$id; }
                    $this->db->insert('roles', $row);
                }
            } else {
                // Backfill group_type for known default IDs if missing
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
        // Define hierarchical menu structure with sub-modules
        $menu_structure = [
            'Dashboard' => [
                'icon' => 'bi-speedometer2',
                'modules' => [
                    'dashboard' => 'Dashboard Overview'
                ]
            ],
            'User Management' => [
                'icon' => 'bi-people',
                'modules' => [
                    'users' => 'User Accounts',
                    'users_list' => 'User List',
                    'users_add' => 'Add User',
                    'users_edit' => 'Edit User',
                    'users_delete' => 'Delete User',
                    'employees' => 'Employee Management',
                    'employees_list' => 'Employee List',
                    'employees_add' => 'Add Employee',
                    'employees_edit' => 'Edit Employee',
                    'employees_delete' => 'Delete Employee',
                    'departments' => 'Departments',
                    'designations' => 'Designations',
                    'permissions' => 'Permission Manager'
                ]
            ],
            'Project Management' => [
                'icon' => 'bi-kanban',
                'modules' => [
                    'projects' => 'Projects',
                    'projects_list' => 'Project List',
                    'projects_add' => 'Add Project',
                    'projects_edit' => 'Edit Project',
                    'projects_delete' => 'Delete Project',
                    'tasks' => 'Task Management',
                    'tasks_list' => 'Task List',
                    'tasks_add' => 'Add Task',
                    'tasks_edit' => 'Edit Task',
                    'tasks_delete' => 'Delete Task',
                    'requirements' => 'Requirements',
                    'requirements_list' => 'Requirements List',
                    'requirements_add' => 'Add Requirement',
                    'requirements_edit' => 'Edit Requirement',
                    'requirements_delete' => 'Delete Requirement',
                    'timesheets' => 'Timesheets',
                    'timesheets_list' => 'Timesheet List',
                    'timesheets_add' => 'Add Timesheet',
                    'timesheets_edit' => 'Edit Timesheet',
                    'timesheets_delete' => 'Delete Timesheet'
                ]
            ],
            'Attendance & Leave' => [
                'icon' => 'bi-calendar-check',
                'modules' => [
                    'attendance' => 'Attendance',
                    'attendance_list' => 'Attendance List',
                    'attendance_add' => 'Mark Attendance',
                    'attendance_edit' => 'Edit Attendance',
                    'attendance_delete' => 'Delete Attendance',
                    'attendance_bulk' => 'Bulk Operations',
                    'leave_requests' => 'Leave Management',
                    'leaves_list' => 'Leave List',
                    'leaves_add' => 'Apply Leave',
                    'leaves_edit' => 'Edit Leave',
                    'leaves_delete' => 'Delete Leave'
                ]
            ],
            'Communication' => [
                'icon' => 'bi-chat-dots',
                'modules' => [
                    'chats' => 'Chat System',
                    'chats_list' => 'Chat List',
                    'chats_add' => 'Start Chat',
                    'announcements' => 'Announcements',
                    'announcements_list' => 'Announcement List',
                    'announcements_add' => 'Add Announcement',
                    'announcements_edit' => 'Edit Announcement',
                    'announcements_delete' => 'Delete Announcement',
                    'calls' => 'Call System'
                ]
            ],
            'Business Management' => [
                'icon' => 'bi-briefcase',
                'modules' => [
                    'clients' => 'Client Management',
                    'clients_list' => 'Client List',
                    'clients_add' => 'Add Client',
                    'clients_edit' => 'Edit Client',
                    'clients_delete' => 'Delete Client',
                    'payroll' => 'Payroll',
                    'assets_mgmt' => 'Asset Management',
                    'assets_list' => 'Asset List',
                    'assets_add' => 'Add Asset',
                    'assets_edit' => 'Edit Asset',
                    'assets_delete' => 'Delete Asset'
                ]
            ],
            'Reports & Analytics' => [
                'icon' => 'bi-graph-up',
                'modules' => [
                    'reports' => 'Reports',
                    'reports_overview' => 'Overview Reports',
                    'reports_requirements' => 'Requirements Reports',
                    'reports_tasks_assignment' => 'Task Assignment Reports',
                    'reports_projects_status' => 'Project Status Reports',
                    'reports_leaves' => 'Leave Reports',
                    'reports_attendance' => 'Attendance Reports',
                    'reports_attendance_employee' => 'Employee Attendance Reports'
                ]
            ],
            'System Administration' => [
                'icon' => 'bi-gear',
                'modules' => [
                    'settings' => 'System Settings',
                    'db' => 'Database Manager',
                    'reminders' => 'Reminders',
                    'reminders_list' => 'Reminder List',
                    'reminders_add' => 'Add Reminder',
                    'reminders_edit' => 'Edit Reminder',
                    'reminders_delete' => 'Delete Reminder',
                    'activity' => 'Activity Log',
                    'mail' => 'Mail Configuration'
                ]
            ]
        ];

        // Get existing modules from database if available
        $db_modules = [];
        if ($this->db->table_exists('modules')) {
            $this->db->from('modules');
            if ($this->db->field_exists('is_active', 'modules')) {
                $this->db->where('is_active', 1);
            }
            $rows = $this->db->get()->result();
            foreach ($rows as $row) {
                $key = strtolower(trim($row->module_key));
                if ($key !== '') {
                    $db_modules[$key] = $row->module_label;
                }
            }
        }

        // Merge database modules with menu structure
        foreach ($menu_structure as $menu_name => &$menu_data) {
            foreach ($menu_data['modules'] as $key => $label) {
                if (isset($db_modules[$key])) {
                    $menu_data['modules'][$key] = $db_modules[$key];
                }
            }
        }

        return $menu_structure;
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
        
        // Collect all module keys from the hierarchical structure
        $all_module_keys = [];
        foreach ($modules as $menu_name => $menu_data) {
            foreach ($menu_data['modules'] as $key => $label) {
                $all_module_keys[$key] = $label;
            }
        }
        
        foreach ($roles as $rid => $rname) {
            foreach ($all_module_keys as $key => $label) {
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

