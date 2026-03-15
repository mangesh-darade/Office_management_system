<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permissions extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','activity']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_schema();
        // DB-driven access: rely on permissions table for module 'permissions'
        $this->load->helper('permission');
        $role_id = (int)$this->session->userdata('role_id');
        $allowed = false;

        // Admin (role 1) always has access to Permission Manager to prevent lock-out
        if ($role_id === 1) {
            $allowed = true;
        }

        if (!$allowed && function_exists('has_module_access')) {
            $allowed = has_module_access('permissions');
        }
        if (!$allowed) { show_error('You do not have permission to access this page.', 403); }
    }

    private function ensure_schema()
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        if (!$this->db->table_exists('permissions')) {
            $sql = "CREATE TABLE `permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_id` int(11) NOT NULL,
                `module` varchar(100) NOT NULL,
                `can_access` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_role_module` (`role_id`,`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        } else {
            // Upgrade: convert regular index to UNIQUE if needed (prevents duplicate rows)
            $idx = $this->db->query("SHOW INDEX FROM `permissions` WHERE Key_name = 'idx_role_module'")->result();
            if (!empty($idx)) {
                $this->db->query("ALTER TABLE `permissions` DROP INDEX `idx_role_module`, ADD UNIQUE KEY `uq_role_module` (`role_id`,`module`)");
            } else {
                $uq = $this->db->query("SHOW INDEX FROM `permissions` WHERE Key_name = 'uq_role_module'")->result();
                if (empty($uq)) {
                    $this->db->query("ALTER TABLE `permissions` ADD UNIQUE KEY `uq_role_module` (`role_id`,`module`)");
                }
            }

            // Ensure module column is wide enough (varchar(64) -> varchar(100))
            $col = $this->db->query("SHOW COLUMNS FROM `permissions` LIKE 'module'")->row();
            if ($col && strpos(strtolower($col->Type), 'varchar(64)') !== false) {
                $this->db->query("ALTER TABLE `permissions` MODIFY COLUMN `module` varchar(100) NOT NULL");
            }

            // Migrate renamed keys: assets_mgmt -> assets (preserve existing permission settings)
            $old_assets = $this->db->where('module', 'assets_mgmt')->get('permissions')->result();
            if (!empty($old_assets)) {
                foreach ($old_assets as $row) {
                    $exists = $this->db->where('role_id', (int)$row->role_id)->where('module', 'assets')->get('permissions')->row();
                    if (!$exists) {
                        $this->db->insert('permissions', [
                            'role_id' => (int)$row->role_id,
                            'module' => 'assets',
                            'can_access' => (int)$row->can_access
                        ]);
                    }
                }
                $this->db->where('module', 'assets_mgmt')->delete('permissions');
            }
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

        // Add is_active column if missing
        if ($this->db->table_exists('roles') && !$this->db->field_exists('is_active', 'roles')) {
            $this->db->query("ALTER TABLE `roles` ADD `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `group_type`");
            $this->db->query("UPDATE `roles` SET `is_active` = 1");
        }

        // Add sort_order column if missing
        if ($this->db->table_exists('roles') && !$this->db->field_exists('sort_order', 'roles')) {
            $this->db->query("ALTER TABLE `roles` ADD `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `is_active`");
            $this->db->query("UPDATE `roles` SET `sort_order` = `id`");
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
        // Delegate to Role_model to avoid duplicate query logic
        $this->load->model('Role_model');
        return $this->Role_model->get_all_as_map();
    }


    private function modules()
    {
        // Define hierarchical menu structure with sub-modules
        // Keys must match every has_module_access() call used in controllers and views.
        $menu_structure = [
            'Dashboard' => [
                'icon' => 'bi-speedometer2',
                'modules' => [
                    'dashboard' => 'Dashboard Overview',
                ]
            ],
            'Daily Activity' => [
                'icon' => 'bi-journal-check',
                'modules' => [
                    'daily_activity'        => 'Daily Activity (Full Access)',
                    'daily_activity_add'    => 'Log / Add Activity',
                    'daily_activity_list'   => 'View Activity List',
                    'daily_activity_edit'   => 'Edit Activity Log',
                    'daily_activity_delete' => 'Delete Activity',
                    'daily_activity_export' => 'Export Activity CSV',
                    'daily_activity_report' => 'View Daily Activity Reports',
                ]
            ],
            'User Management' => [
                'icon' => 'bi-people',
                'modules' => [
                    'users'            => 'User Accounts (Full Access)',
                    'users_list'       => 'View User List',
                    'users_add'        => 'Add User',
                    'users_edit'       => 'Edit User',
                    'users_delete'     => 'Delete User',
                    'employees'        => 'Employee Management (Full Access)',
                    'employees_list'   => 'View Employee List',
                    'employees_add'    => 'Add Employee',
                    'employees_edit'   => 'Edit Employee',
                    'employees_delete' => 'Delete Employee',
                    'departments'      => 'Department Management',
                    'designations'     => 'Designation Management',
                    'roles'            => 'Role Management',
                    'profile'          => 'View Own Profile',
                ]
            ],
            'Project Management' => [
                'icon' => 'bi-kanban',
                'modules' => [
                    'projects'             => 'Projects (Full Access)',
                    'projects_list'        => 'View Project List',
                    'projects_add'         => 'Add Project',
                    'projects_edit'        => 'Edit Project',
                    'projects_delete'      => 'Delete Project',
                    'tasks'                => 'Task Management (Full Access)',
                    'tasks_list'           => 'View Task List',
                    'tasks_add'            => 'Add Task',
                    'tasks_edit'           => 'Edit Task',
                    'tasks_delete'         => 'Delete Task',
                    'requirements'         => 'Requirements (Full Access)',
                    'requirements_list'    => 'View Requirements List',
                    'requirements_add'     => 'Add Requirement',
                    'requirements_edit'    => 'Edit Requirement',
                    'requirements_delete'  => 'Delete Requirement',
                    'timesheets'           => 'Timesheets (Full Access)',
                    'timesheets_list'      => 'View Timesheet List',
                    'timesheets_add'       => 'Add Timesheet Entry',
                    'timesheets_edit'      => 'Edit Timesheet Entry',
                    'timesheets_delete'    => 'Delete Timesheet Entry',
                ]
            ],
            'Attendance & Leave' => [
                'icon' => 'bi-calendar-check',
                'modules' => [
                    'shifts'              => 'Shift Management (Full Access)',
                    'shifts_view'         => 'View Shifts',
                    'shifts_manage'       => 'Manage / Edit Shifts',
                    'attendance'          => 'Attendance (Full Access)',
                    'attendance_list'     => 'View Attendance List',
                    'attendance_add'      => 'Mark / Add Attendance',
                    'attendance_edit'     => 'Edit Attendance Record',
                    'attendance_delete'   => 'Delete Attendance Record',
                    'attendance_bulk'     => 'Bulk Attendance Operations',
                    'leave_requests'      => 'Leave Management — Admin View (Full Access)',
                    'leave_team'          => 'View Team Leaves',
                    'leave_calendar'      => 'Leave Calendar',
                    'leave_approve'       => 'Approve / Reject Leaves',
                    'leaves'              => 'My Leaves — Personal Leave Screen',
                    'leaves_list'         => 'View Own Leave List',
                    'leaves_add'          => 'Apply for Leave',
                    'leaves_edit'         => 'Edit Own Leave Request',
                    'leaves_delete'       => 'Delete Own Leave Request',
                    'holidays'            => 'Holiday Management (Full Access)',
                    'holidays_add'        => 'Add Holiday',
                    'holidays_edit'       => 'Edit Holiday',
                    'holidays_delete'     => 'Delete Holiday',
                    'leave_types'         => 'Leave Type Management (Full Access)',
                    'leave_types_add'     => 'Add Leave Type',
                    'leave_types_edit'    => 'Edit Leave Type',
                    'leave_types_delete'  => 'Delete Leave Type',
                ]
            ],
            'Communication' => [
                'icon' => 'bi-chat-dots',
                'modules' => [
                    'chats'                  => 'Chat System (Full Access)',
                    'chats_list'             => 'View Chat List',
                    'chats_add'              => 'Start New Chat / DM',
                    'chatsgrouping'          => 'Create / Manage Chat Groups',
                    'calls'                  => 'Video / Audio Calls',
                    'notifications'          => 'Notifications Center',
                    'announcements'          => 'Announcements (Full Access)',
                    'announcements_list'     => 'View Announcements',
                    'announcements_add'      => 'Create Announcement',
                    'announcements_edit'     => 'Edit Announcement',
                    'announcements_delete'   => 'Delete Announcement',
                ]
            ],
            'Recruitment' => [
                'icon' => 'bi-person-plus',
                'modules' => [
                    'recruitment'              => 'Recruitment (Full Access)',
                    'recruitment_jobs'         => 'Manage Job Postings (Create / Edit / Delete)',
                    'recruitment_candidates'   => 'View & Manage Candidates',
                    'recruitment_interviews'   => 'Schedule Interviews',
                    'recruitment_export'       => 'Export Candidates CSV',
                ]
            ],
            'Performance' => [
                'icon' => 'bi-award',
                'modules' => [
                    'performance'             => 'Performance Appraisals (Full Access)',
                    'performance_create'      => 'Create Appraisal',
                    'performance_view'        => 'View Appraisal Details',
                    'performance_edit'        => 'Edit Appraisal',
                    'performance_delete'      => 'Delete Appraisal',
                    'performance_self_assess' => 'Submit Self-Assessment',
                    'performance_export'      => 'Export Appraisals CSV',
                ]
            ],
            'Business Management' => [
                'icon' => 'bi-briefcase',
                'modules' => [
                    'clients'              => 'Client Management (Full Access)',
                    'clients_list'         => 'View Client List',
                    'clients_add'          => 'Add Client',
                    'clients_edit'         => 'Edit Client',
                    'clients_delete'       => 'Delete Client',
                    'payroll'              => 'Payroll (Full Access)',
                    'payroll_view'         => 'View Own Payslips',
                    'payroll_manage'       => 'Manage Salary Structures & Generate Payslips',
                    'expenses'             => 'Expense Management (Full Access)',
                    'expenses_add'         => 'Create Expense Request',
                    'expenses_edit'        => 'Edit Own Expense',
                    'expenses_delete'      => 'Delete Own Expense',
                    'expenses_approve'     => 'Approve / Reject Expenses',
                    'expenses_reimburse'   => 'Mark Expense as Reimbursed',
                    'expenses_reports'     => 'View Expense Reports',
                    'expenses_categories'  => 'Manage Expense Categories',
                    'expenses_export'      => 'Export Expenses CSV',
                    'assets'               => 'Asset Management (Full Access)',
                    'assets_mgmt'          => 'Asset Management (Legacy Key)',
                    'assets_list'          => 'View Asset List',
                    'assets_add'           => 'Add Asset',
                    'assets_edit'          => 'Edit Asset',
                    'assets_delete'        => 'Delete Asset',
                    'assets_assign'        => 'Assign / Return Assets',
                ]
            ],
            'Reports & Analytics' => [
                'icon' => 'bi-graph-up',
                'modules' => [
                    'analytics'                    => 'AI Analytics Dashboard',
                    'ai'                           => 'AI Assistant',
                    'ai_chat'                      => 'AI Chat',
                    'ai_widget'                    => 'AI Floating Widget',
                    'reports'                      => 'Reports (Full Access)',
                    'reports_overview'             => 'Overview / Dashboard Reports',
                    'reports_requirements'         => 'Requirements Reports',
                    'reports_tasks_assignment'     => 'Task Assignment Reports',
                    'reports_projects_status'      => 'Project Status Reports',
                    'reports_leaves'               => 'Leave Reports',
                    'reports_attendance'           => 'Attendance Reports',
                    'reports_attendance_employee'  => 'Employee Attendance Detail Reports',
                    'reports_daily_activity'       => 'Daily Activity Reports',
                    'reports_payroll'              => 'Payroll Reports',
                    'reports_expenses'             => 'Expenses Reports',
                    'reports_performance'          => 'Performance Reports',
                ]
            ],
            'System Administration' => [
                'icon' => 'bi-gear',
                'modules' => [
                    'settings'         => 'System Settings (General)',
                    'admin'            => 'Admin Access (General Override)',
                    'system_settings'  => 'System Settings Panel (Advanced)',
                    'db'               => 'Database Manager',
                    'migrate'          => 'Database Migrations',
                    'approvals'        => 'Approval Workflows',
                    'reminders'        => 'Reminders (Full Access)',
                    'reminders_list'   => 'View Reminders',
                    'reminders_add'    => 'Add Reminder',
                    'reminders_edit'   => 'Edit Reminder',
                    'reminders_delete' => 'Delete Reminder',
                    'activity'         => 'Activity Log Viewer',
                    'mail'             => 'Mail (SMTP) Configuration',
                    'sendgrid'         => 'SendGrid Email API',
                    'email_settings'   => 'Email Settings & Templates',
                    'whatsapp'         => 'WhatsApp Integration',
                    'permissions'      => 'Permission Manager',
                    'statuses'         => 'Status Management',
                    'api_integrations' => 'API Integrations',
                    'superadmin'       => 'Super Admin Panel',
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
        // Restrict permission writes to admin role only — prevents privilege escalation
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== ROLE_ADMIN) {
            show_error('Only administrators can modify permissions.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('permissions');
            return;
        }

        $roles = $this->roles();
        $modules = $this->modules();
        $perms = $this->input->post('perms'); // perms[role_id][module] = 1

        // Load activity tracking helper
        $this->load->helper('change_tracker');

        // Get old permissions data before update
        $old_permissions = [];
        if ($this->db->table_exists('permissions')) {
            $old_rows = $this->db->get('permissions')->result();
            foreach ($old_rows as $row) {
                $old_permissions[] = [
                    'role_id' => (int)$row->role_id,
                    'module' => $row->module,
                    'can_access' => (int)$row->can_access
                ];
            }
        }

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
        
        $new_permissions = [];
        foreach ($roles as $rid => $rname) {
            foreach ($all_module_keys as $key => $label) {
                $can = (isset($perms[$rid]) && isset($perms[$rid][$key]) && (int)$perms[$rid][$key] === 1) ? 1 : 0;
                $this->db->insert('permissions', [
                    'role_id' => (int)$rid,
                    'module' => $key,
                    'can_access' => $can
                ]);
                $new_permissions[] = [
                    'role_id' => (int)$rid,
                    'module' => $key,
                    'can_access' => $can
                ];
            }
        }
        $this->db->trans_complete();

        // Log bulk permissions update
        $description = 'Permissions matrix updated for ' . count($roles) . ' roles and ' . count($all_module_keys) . ' modules';
        log_activity_with_changes('permissions', 'updated', null, $old_permissions, $new_permissions, $description);

        $this->session->set_flashdata('success', 'Permissions updated successfully.');
        redirect('permissions');
    }
}

