<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permissions extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','activity','permission','schema_columns']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('permissions', true);
        
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        $this->load->helper('permissions_schema');
        permissions_schema_ensure($this->db);
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
                    'dashboard_cheers' => 'Dashboard Card - Cheers',
                    'dashboard_sateri' => 'Dashboard Card - Sateri',
                    'dashboard_srujan' => 'Dashboard Card - Srujan',
                    'dashboard_simpliworks' => 'Dashboard Card - Simpliworks',
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
                    'daily_activity_view_all' => 'View All Daily Activity (Org-wide)',
                    'daily_activity_edit_all' => 'Edit Any Daily Activity Entry',
                    'daily_activity_delete_all' => 'Delete Any Daily Activity Entry',
                ]
            ],
            'Help & User Guide' => [
                'icon' => 'bi-book',
                'modules' => [
                    'guide' => 'User Guide (In-app Help & Videos)',
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
                    'users_view'       => 'View User Detail',
                    'employees'        => 'Employee Management (Full Access)',
                    'employees_list'   => 'View Employee List',
                    'employees_add'    => 'Add Employee',
                    'employees_edit'   => 'Edit Employee',
                    'employees_delete' => 'Delete Employee',
                    'employees_view'   => 'View Employee Detail',
                    'employees_view_all' => 'View All Employees (Org-wide)',
                    'employees_edit_all' => 'Edit Any Employee Record',
                    'employees_delete_all' => 'Delete Any Employee Record',
                    'employees_documents' => 'Manage Employee Documents',
                    'employees_delete_document' => 'Delete Employee Documents',
                    'employees_import' => 'Import Employees (CSV)',
                    'users_view_all'   => 'View All User Accounts (Org-wide)',
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
                    'projects_view_all'    => 'View All Projects (Org-wide)',
                    'projects_import'      => 'Import Projects (CSV)',
                    'projects_matrix'      => 'Project Portfolio Matrix',
                    'tasks'                => 'Task Management (Full Access)',
                    'tasks_list'           => 'View Task List',
                    'tasks_add'            => 'Add Task',
                    'tasks_edit'           => 'Edit Task',
                    'tasks_delete'         => 'Delete Task',
                    'tasks_manage'         => 'Manage All Tasks (Board / Bulk)',
                    'tasks_view_all'       => 'View All Tasks (Org-wide)',
                    'tasks_delete_all'     => 'Delete Any Task',
                    'tasks_import'         => 'Import Tasks (CSV)',
                    'requirements'         => 'Requirements (Full Access)',
                    'requirements_list'    => 'View Requirements List',
                    'requirements_add'     => 'Add Requirement',
                    'requirements_edit'    => 'Edit Requirement',
                    'requirements_delete'  => 'Delete Requirement',
                    'requirements_view'    => 'View Requirement Detail',
                    'requirements_board'   => 'Requirements Board View',
                    'requirements_calendar'=> 'Requirements Calendar',
                    'requirements_export'  => 'Export Requirements',
                    'requirements_delete_all' => 'Delete Any Requirement',
                    'timesheets'           => 'Timesheets (Full Access)',
                    'timesheets_list'      => 'View Timesheet List',
                    'timesheets_add'       => 'Add Timesheet Entry',
                    'timesheets_edit'      => 'Edit Timesheet Entry',
                    'timesheets_delete'    => 'Delete Timesheet Entry',
                    'releases'               => 'Release Management (Full Access)',
                    'releases_add'           => 'Add Release',
                    'releases_edit'          => 'Edit Release',
                    'releases_send_notes'    => 'Send Release Notes (Email)',
                    'releases_export'        => 'Export Releases (CSV)',
                    'defects'                => 'Defect Management (Full Access)',
                    'defects_list'           => 'View Defect List',
                    'defects_add'            => 'Add Defect',
                    'defects_edit'           => 'Edit Defect',
                    'defects_delete'         => 'Delete Defect',
                    'defects_view'           => 'View Defect Detail',
                    'defects_export'         => 'Export Defects (CSV)',
                ]
            ],
            'My Works' => [
                'icon' => 'bi-clipboard2-check',
                'modules' => [
                    'my_works'             => 'My Works (Full Access)',
                    'my_works_list'        => 'View My Works List',
                    'my_works_add'         => 'Add My Work Item',
                    'my_works_edit'        => 'Edit My Work Item',
                    'my_works_delete'      => 'Delete My Work Item',
                    'my_works_view_all'    => 'View All My Works (Admin roles only — data scope)',
                    'my_works_export'      => 'Export My Works (CSV)',
                ]
            ],
            'SPL & Rewards' => [
                'icon' => 'bi-trophy',
                'modules' => [
                    'spl'                  => 'SPL — Full Access (All Screens)',
                    'spl_my_reward'        => 'SPL — My Reward',
                    'spl_submit'           => 'SPL — Submit Activity',
                    'spl_approve'          => 'SPL — Approvals',
                    'spl_rules'            => 'SPL — Rules',
                    'spl_groups'           => 'SPL — View Groups',
                    'spl_groups_manage'    => 'SPL — Manage Groups & Posters',
                    'rewards'              => 'Rewards — Legacy Full Access',
                    'rewards_submit'       => 'Rewards — Legacy Submit',
                    'rewards_rules'        => 'Rewards — Legacy Rules',
                    'rewards_admin'        => 'Rewards — Legacy Admin',
                    'rewards_approve'      => 'Rewards — Legacy Approve',
                    'rewards_leaderboard'  => 'Rewards — Leaderboard',
                ]
            ],
            'Office Meals' => [
                'icon' => 'bi-cup-hot',
                'modules' => [
                    'meals_order' => array(
                        'label' => 'My Orders',
                        'tag'   => 'Screen',
                    ),
                    'meals_calendar' => array(
                        'label' => 'Meal Calendar',
                        'tag'   => 'Screen',
                    ),
                    'meals_provider' => array(
                        'label' => 'Meal Provider',
                        'tag'   => 'Screen',
                    ),
                    'meals_settings' => array(
                        'label' => 'Meal Settings',
                        'tag'   => 'Screen',
                    ),
                    'meals_history' => array(
                        'label' => 'Meal History',
                        'tag'   => 'Screen',
                    ),
                    'meals_all_orders' => array(
                        'label' => 'All meal orders',
                        'tag'   => 'Screen',
                    ),
                ],
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
                    'attendance_view_all' => 'View All Attendance (Org-wide)',
                    'attendance_export'   => 'Export Attendance (Excel / PDF)',
                    'leave_requests'      => 'Leave Management — Admin View (Full Access)',
                    'leave_team'          => 'View Team Leaves',
                    'leave_calendar'      => 'Leave Calendar',
                    'leave_approve'       => 'Approve / Reject Leaves',
                    'leave_view_all'      => 'View All Leave Calendar (Org-wide)',
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
                    'announcements_manage'   => 'Manage All Announcements (Org-wide)',
                ]
            ],
            'Recruitment' => [
                'icon' => 'bi-person-plus',
                'modules' => [
                    'recruitment'              => 'Recruitment (Full Access)',
                    'recruitment_add'          => 'Add Job Posting',
                    'recruitment_delete'       => 'Delete Job Posting',
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
            'Training & Learning' => [
                'icon' => 'bi-mortarboard',
                'modules' => [
                    'external_training'          => 'External trainings (full access)',
                    'external_training_list'     => 'External trainings — list',
                    'external_training_add'      => 'External trainings — add',
                    'external_training_edit'     => 'External trainings — edit',
                    'external_training_delete'   => 'External trainings — delete',
                    'external_training_watch'    => 'External trainings — watch',
                    'training_assessment'        => 'Training assessments (full access)',
                    'training_assessment_manage' => 'Training assessments — manage all',
                    'training_assessment_take'   => 'Training assessments — take tests',
                    'training_lms'               => 'Training LMS (learner full)',
                    'training_lms_manage'        => 'Training LMS — manage catalog & grade',
                    'training_screen_ta_dashboard' => 'Dashboard',
                    'training_screen_ta_create' => 'New assessment',
                    'training_screen_ta_import' => 'Import CSV',
                    'training_screen_ta_question_import' => 'Import Questions + Options',
                    'training_screen_ta_report' => 'Report',
                    'training_screen_ta_submissions' => 'Assessment submissions',
                    'training_screen_ta_team_progress' => 'Team progress / org-scoped result review',
                    'training_screen_ta_my_tests' => 'My assigned tests',
                    'training_take_with_proctoring' => 'Take Assessment (Video + Screenshot Monitoring)',
                    'training_take_without_proctoring' => 'Take Assessment (Without Video/Screenshot Monitoring)',
                    'training_screen_tl_hub' => 'Training hub',
                    'training_screen_tl_module' => 'Module',
                    'training_screen_tl_assignment' => 'Topic file assignments (learner)',
                    'training_screen_lms_admin' => 'LMS admin',
                    'training_screen_lms_submissions' => 'Assignment submissions',
                    'training_screen_lms_office_csv' => 'LMS office CSV import/export',
                ]
            ],
            'Coaching' => [
                'icon' => 'bi-person-hearts',
                'modules' => [
                    'coaching'               => 'Coaching (Full Access)',
                    'coaching_coaches'       => 'Manage Coaches',
                    'coaching_clients'       => 'Manage Coaching Clients',
                    'coaching_sessions'      => 'Manage Sessions',
                    'coaching_goals'         => 'Manage Goals & Homework',
                    'coaching_leads'         => 'Manage Leads',
                    'coaching_billing'       => 'Billing & Installments',
                    'coaching_reports'       => 'Coaching Reports',
                    'coaching_whatsapp_crm'  => 'WhatsApp CRM',
                    'coaching_resources'     => 'Resources & Workshops',
                    'coaching_admin'         => 'Coaching Admin Settings',
                    'coaching_portal'        => 'Client Portal Access',
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
                    'clients_view'         => 'View Client Detail',
                    'clients_export'       => 'Export Clients CSV',
                    'subscription_builder'       => 'Subscription Builder (Full Access)',
                    'subscription_builder_list'  => 'View Subscription Builder Catalog',
                    'elintom_proposals'          => 'ElintOm Proposals (Full Access)',
                    'elintom_proposals_list'     => 'View ElintOm Proposals',
                    'eba_platform'               => 'EBA Platform (Full Access)',
                    'eba_platform_list'          => 'View EBA Platform',
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
                    'assets_mgmt'          => 'Asset Management (Legacy Alias — same as Manage)',
                    'assets_manage'        => 'Manage All Assets (Org-wide)',
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
                    'db_admin'         => 'Database Admin Tools (Advanced DB)',
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
                    'types'            => 'Type Management',
                    'api_integrations' => 'API Integrations',
                    'lead_mapping'     => 'Lead Mapping',
                    'superadmin'       => 'Super Admin Panel',
                ]
            ]
        ];

        // Get existing modules from database if available
        $db_modules = [];
        if ($this->db->table_exists('modules')) {
            $this->db->from('modules');
            if (schema_table_has_column($this->db, 'modules', 'is_active')) {
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
            foreach ($menu_data['modules'] as $key => $def) {
                if (!isset($db_modules[$key])) {
                    continue;
                }
                if (is_array($def)) {
                    $menu_data['modules'][$key]['label'] = $db_modules[$key];
                } else {
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
        // Restrict permission writes — prevents privilege escalation
        require_module_access('permissions', true);

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

        // Update matrix safely (avoid TRUNCATE to preserve transactional safety)
        $this->db->trans_start();
        
        // Collect all module keys from the hierarchical structure
        $all_module_keys = [];
        foreach ($modules as $menu_name => $menu_data) {
            foreach ($menu_data['modules'] as $key => $def) {
                $meta = permissions_module_meta($def);
                $all_module_keys[$key] = $meta['label'];
            }
        }
        
        $new_permissions = [];
        foreach ($roles as $rid => $rname) {
            foreach ($all_module_keys as $key => $label) {
                $can = (isset($perms[$rid]) && isset($perms[$rid][$key]) && (int)$perms[$rid][$key] === 1) ? 1 : 0;
                // Upsert-like behavior on unique (role_id, module)
                $exists = $this->db
                    ->select('id')
                    ->from('permissions')
                    ->where('role_id', (int)$rid)
                    ->where('module', $key)
                    ->limit(1)
                    ->get()
                    ->row();
                if ($exists) {
                    $this->db->where('id', (int)$exists->id)->update('permissions', [
                        'can_access' => $can
                    ]);
                } else {
                    $this->db->insert('permissions', [
                        'role_id' => (int)$rid,
                        'module' => $key,
                        'can_access' => $can
                    ]);
                }

                $new_permissions[] = [
                    'role_id' => (int)$rid,
                    'module' => $key,
                    'can_access' => $can
                ];
            }
        }

        // Cleanup: remove stale modules that no longer exist in the matrix definition.
        if (!empty($all_module_keys)) {
            $this->db->where_not_in('module', array_keys($all_module_keys))->delete('permissions');
        }
        $this->db->trans_complete();

        // Log bulk permissions update
        $description = 'Permissions matrix updated for ' . count($roles) . ' roles and ' . count($all_module_keys) . ' modules';
        log_activity_with_changes('permissions', 'updated', null, $old_permissions, $new_permissions, $description);

        $this->session->set_flashdata('success', 'Permissions updated successfully.');
        redirect('permissions');
    }
}

