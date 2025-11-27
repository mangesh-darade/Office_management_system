<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Enhanced Permission Setup Script
 * 
 * This script enhances the permission system by adding granular permissions
 * for CRUD operations (Create, Read, Update, Delete) across all modules.
 */

class Enhance_Permissions extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Only admin can run this
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) {
            show_error('Access denied. Admin only.', 403);
        }
    }
    
    public function index() {
        echo "<h1>Office Management System - Enhanced Permission Setup</h1>";
        
        // Add granular permissions
        $this->add_granular_permissions();
        
        // Update default permissions
        $this->update_default_permissions();
        
        echo "<div class='alert alert-success'>Enhanced permission setup completed successfully!</div>";
        echo "<p><strong>Added granular permissions for:</strong></p>";
        echo "<ul>";
        echo "<li>employees_add, employees_edit, employees_delete</li>";
        echo "<li>projects_add, projects_edit, projects_delete</li>";
        echo "<li>tasks_add, tasks_edit, tasks_delete</li>";
        echo "<li>attendance_add, attendance_edit, attendance_delete, attendance_bulk</li>";
        echo "<li>users_add, users_edit, users_delete</li>";
        echo "<li>leaves_add, leaves_edit, leaves_delete</li>";
        echo "<li>clients_add, clients_edit, clients_delete</li>";
        echo "<li>announcements_add, announcements_edit, announcements_delete</li>";
        echo "<li>assets_add, assets_edit, assets_delete</li>";
        echo "<li>reminders_add, reminders_edit, reminders_delete</li>";
        echo "</ul>";
        
        echo "<a href='" . site_url('permissions') . "' class='btn btn-primary'>Manage Permissions</a>";
        echo "<a href='" . site_url('dashboard') . "' class='btn btn-secondary ms-2'>Dashboard</a>";
    }
    
    private function add_granular_permissions() {
        // Define granular permissions for each module
        $granular_modules = [
            // Core Management
            'employees_list' => ['Employee List', 'View employee list', 'bi-people', 'hr'],
            'employees_add' => ['Add Employee', 'Add new employees', 'bi-person-plus', 'hr'],
            'employees_edit' => ['Edit Employee', 'Edit employee records', 'bi-pencil-square', 'hr'],
            'employees_delete' => ['Delete Employee', 'Delete employee records', 'bi-trash', 'hr'],
            
            'users_list' => ['User List', 'View user accounts', 'bi-person-lock', 'admin'],
            'users_add' => ['Add User', 'Create new user accounts', 'bi-person-plus-fill', 'admin'],
            'users_edit' => ['Edit User', 'Edit user accounts', 'bi-person-gear', 'admin'],
            'users_delete' => ['Delete User', 'Delete user accounts', 'bi-person-x', 'admin'],
            
            // Project Management
            'projects_list' => ['Project List', 'View projects', 'bi-kanban', 'projects'],
            'projects_add' => ['Add Project', 'Create new projects', 'bi-plus-square', 'projects'],
            'projects_edit' => ['Edit Project', 'Edit project details', 'bi-pencil-square', 'projects'],
            'projects_delete' => ['Delete Project', 'Delete projects', 'bi-trash', 'projects'],
            
            'tasks_list' => ['Task List', 'View tasks', 'bi-check2-square', 'projects'],
            'tasks_add' => ['Add Task', 'Create new tasks', 'bi-plus-circle', 'projects'],
            'tasks_edit' => ['Edit Task', 'Edit task details', 'bi-pencil-square', 'projects'],
            'tasks_delete' => ['Delete Task', 'Delete tasks', 'bi-trash', 'projects'],
            
            'requirements_list' => ['Requirements List', 'View requirements', 'bi-clipboard-check', 'projects'],
            'requirements_add' => ['Add Requirement', 'Add requirements', 'bi-plus-circle', 'projects'],
            'requirements_edit' => ['Edit Requirement', 'Edit requirements', 'bi-pencil-square', 'projects'],
            'requirements_delete' => ['Delete Requirement', 'Delete requirements', 'bi-trash', 'projects'],
            
            // Time & Attendance
            'attendance_list' => ['Attendance List', 'View attendance records', 'bi-clock-history', 'attendance'],
            'attendance_add' => ['Mark Attendance', 'Mark attendance', 'bi-clock', 'attendance'],
            'attendance_edit' => ['Edit Attendance', 'Edit attendance records', 'bi-pencil-square', 'attendance'],
            'attendance_delete' => ['Delete Attendance', 'Delete attendance records', 'bi-trash', 'attendance'],
            'attendance_bulk' => ['Bulk Operations', 'Bulk attendance operations', 'bi-stack', 'attendance'],
            
            'timesheets_list' => ['Timesheets', 'View timesheets', 'bi-stopwatch', 'attendance'],
            'timesheets_add' => ['Add Timesheet', 'Add timesheet entries', 'bi-plus-circle', 'attendance'],
            'timesheets_edit' => ['Edit Timesheet', 'Edit timesheets', 'bi-pencil-square', 'attendance'],
            'timesheets_delete' => ['Delete Timesheet', 'Delete timesheets', 'bi-trash', 'attendance'],
            
            'leaves_list' => ['Leave List', 'View leave requests', 'bi-calendar-x', 'attendance'],
            'leaves_add' => ['Apply Leave', 'Apply for leave', 'bi-plus-circle', 'attendance'],
            'leaves_edit' => ['Edit Leave', 'Edit leave requests', 'bi-pencil-square', 'attendance'],
            'leaves_delete' => ['Delete Leave', 'Delete leave requests', 'bi-trash', 'attendance'],
            
            // Communication
            'chats_list' => ['Chat List', 'View chat conversations', 'bi-chat-dots', 'communication'],
            'chats_add' => ['Start Chat', 'Start new conversations', 'bi-plus-circle', 'communication'],
            'chats_edit' => ['Edit Chat', 'Edit chat messages', 'bi-pencil-square', 'communication'],
            'chats_delete' => ['Delete Chat', 'Delete conversations', 'bi-trash', 'communication'],
            
            'announcements_list' => ['Announcements', 'View announcements', 'bi-megaphone', 'communication'],
            'announcements_add' => ['Add Announcement', 'Create announcements', 'bi-plus-circle', 'communication'],
            'announcements_edit' => ['Edit Announcement', 'Edit announcements', 'bi-pencil-square', 'communication'],
            'announcements_delete' => ['Delete Announcement', 'Delete announcements', 'bi-trash', 'communication'],
            
            'calls_list' => ['Call History', 'View call history', 'bi-telephone', 'communication'],
            'calls_add' => ['Make Call', 'Initiate calls', 'bi-telephone-plus', 'communication'],
            'calls_edit' => ['Edit Call', 'Edit call records', 'bi-pencil-square', 'communication'],
            'calls_delete' => ['Delete Call', 'Delete call records', 'bi-trash', 'communication'],
            
            // Client Management
            'clients_list' => ['Client List', 'View clients', 'bi-briefcase', 'sales'],
            'clients_add' => ['Add Client', 'Add new clients', 'bi-plus-circle', 'sales'],
            'clients_edit' => ['Edit Client', 'Edit client information', 'bi-pencil-square', 'sales'],
            'clients_delete' => ['Delete Client', 'Delete client records', 'bi-trash', 'sales'],
            
            // Assets & Resources
            'assets_list' => ['Asset List', 'View assets', 'bi-box-seam', 'resources'],
            'assets_add' => ['Add Asset', 'Add new assets', 'bi-plus-circle', 'resources'],
            'assets_edit' => ['Edit Asset', 'Edit asset details', 'bi-pencil-square', 'resources'],
            'assets_delete' => ['Delete Asset', 'Delete assets', 'bi-trash', 'resources'],
            
            // Payroll
            'payroll_list' => ['Payroll List', 'View payroll records', 'bi-currency-dollar', 'finance'],
            'payroll_add' => ['Add Payroll', 'Create payroll entries', 'bi-plus-circle', 'finance'],
            'payroll_edit' => ['Edit Payroll', 'Edit payroll records', 'bi-pencil-square', 'finance'],
            'payroll_delete' => ['Delete Payroll', 'Delete payroll records', 'bi-trash', 'finance'],
            
            // Reports
            'reports_view' => ['View Reports', 'Access reports', 'bi-file-earmark-bar-graph', 'reports'],
            'reports_generate' => ['Generate Reports', 'Generate new reports', 'bi-file-earmark-plus', 'reports'],
            'reports_export' => ['Export Reports', 'Export reports', 'bi-download', 'reports'],
            
            // Settings
            'settings_view' => ['View Settings', 'View system settings', 'bi-gear', 'admin'],
            'settings_edit' => ['Edit Settings', 'Edit system settings', 'bi-gear-fill', 'admin'],
            
            'departments_list' => ['Departments', 'View departments', 'bi-building', 'admin'],
            'departments_add' => ['Add Department', 'Add departments', 'bi-plus-circle', 'admin'],
            'departments_edit' => ['Edit Department', 'Edit departments', 'bi-pencil-square', 'admin'],
            'departments_delete' => ['Delete Department', 'Delete departments', 'bi-trash', 'admin'],
            
            'designations_list' => ['Designations', 'View designations', 'bi-award', 'admin'],
            'designations_add' => ['Add Designation', 'Add designations', 'bi-plus-circle', 'admin'],
            'designations_edit' => ['Edit Designation', 'Edit designations', 'bi-pencil-square', 'admin'],
            'designations_delete' => ['Delete Designation', 'Delete designations', 'bi-trash', 'admin'],
            
            // Tools & Utilities
            'reminders_list' => ['Reminder List', 'View reminders', 'bi-bell', 'tools'],
            'reminders_add' => ['Add Reminder', 'Create reminders', 'bi-plus-circle', 'tools'],
            'reminders_edit' => ['Edit Reminder', 'Edit reminders', 'bi-pencil-square', 'tools'],
            'reminders_delete' => ['Delete Reminder', 'Delete reminders', 'bi-trash', 'tools'],
            
            'activity_view' => ['Activity Log', 'View activity logs', 'bi-activity', 'tools'],
            'activity_delete' => ['Delete Activity', 'Delete activity logs', 'bi-trash', 'tools'],
            
            'db_view' => ['Database Tools', 'View database tools', 'bi-database', 'admin'],
            'db_query' => ['Query Database', 'Run database queries', 'bi-search', 'admin'],
            'db_export' => ['Export Database', 'Export database data', 'bi-download', 'admin'],
        ];
        
        // Insert granular modules
        foreach ($granular_modules as $key => $details) {
            // Check if module already exists
            $existing = $this->db->where('module_key', $key)->get('modules')->row();
            if (!$existing) {
                $this->db->insert('modules', [
                    'module_key' => $key,
                    'module_label' => $details[0],
                    'description' => $details[1],
                    'icon' => $details[2],
                    'category' => $details[3],
                    'sort_order' => 100 + count($granular_modules),
                    'is_active' => 1
                ]);
            }
        }
        
        echo "<p>✓ Added " . count($granular_modules) . " granular permissions</p>";
    }
    
    private function update_default_permissions() {
        // Define enhanced default permissions
        $enhanced_permissions = [
            // Admin (Role 1) - Full access to everything
            1 => [
                // Core Management - Full access
                'dashboard', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete',
                'users_list', 'users_add', 'users_edit', 'users_delete', 'permissions',
                
                // Project Management - Full access
                'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
                'requirements_list', 'requirements_add', 'requirements_edit', 'requirements_delete',
                
                // Time & Attendance - Full access
                'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete', 'attendance_bulk',
                'timesheets_list', 'timesheets_add', 'timesheets_edit', 'timesheets_delete',
                'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
                
                // Communication - Full access
                'chats_list', 'chats_add', 'chats_edit', 'chats_delete',
                'announcements_list', 'announcements_add', 'announcements_edit', 'announcements_delete',
                'calls_list', 'calls_add', 'calls_edit', 'calls_delete',
                
                // Client Management - Full access
                'clients_list', 'clients_add', 'clients_edit', 'clients_delete',
                
                // Assets & Resources - Full access
                'assets_list', 'assets_add', 'assets_edit', 'assets_delete',
                
                // Payroll - Full access
                'payroll_list', 'payroll_add', 'payroll_edit', 'payroll_delete',
                
                // Reports - Full access
                'reports_view', 'reports_generate', 'reports_export',
                
                // Settings - Full access
                'settings_view', 'settings_edit',
                'departments_list', 'departments_add', 'departments_edit', 'departments_delete',
                'designations_list', 'designations_add', 'designations_edit', 'designations_delete',
                
                // Tools & Utilities - Full access
                'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete',
                'activity_view', 'activity_delete',
                'db_view', 'db_query', 'db_export',
            ],
            
            // Manager (Role 2) - HR/Admin access but no system settings
            2 => [
                // Core Management - Can manage employees but not users
                'dashboard', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete',
                
                // Project Management - Full access
                'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
                'requirements_list', 'requirements_add', 'requirements_edit', 'requirements_delete',
                
                // Time & Attendance - Full access
                'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete', 'attendance_bulk',
                'timesheets_list', 'timesheets_add', 'timesheets_edit', 'timesheets_delete',
                'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
                
                // Communication - Full access
                'chats_list', 'chats_add', 'chats_edit', 'chats_delete',
                'announcements_list', 'announcements_add', 'announcements_edit', 'announcements_delete',
                'calls_list', 'calls_add', 'calls_edit', 'calls_delete',
                
                // Client Management - Full access
                'clients_list', 'clients_add', 'clients_edit', 'clients_delete',
                
                // Assets & Resources - Full access
                'assets_list', 'assets_add', 'assets_edit', 'assets_delete',
                
                // Payroll - Full access
                'payroll_list', 'payroll_add', 'payroll_edit', 'payroll_delete',
                
                // Reports - Full access
                'reports_view', 'reports_generate', 'reports_export',
                
                // Tools & Utilities
                'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete',
                'activity_view',
            ],
            
            // Lead (Role 3) - Team management, no HR functions
            3 => [
                // Core Management - Can view employees but not manage
                'dashboard', 'employees_list',
                
                // Project Management - Full access
                'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
                'requirements_list', 'requirements_add', 'requirements_edit', 'requirements_delete',
                
                // Time & Attendance - Can view and manage team attendance
                'attendance_list', 'attendance_add', 'attendance_edit',
                'timesheets_list', 'timesheets_add', 'timesheets_edit',
                'leaves_list', 'leaves_add', 'leaves_edit',
                
                // Communication - Full access
                'chats_list', 'chats_add', 'chats_edit', 'chats_delete',
                'announcements_list', 'announcements_add', 'announcements_edit',
                'calls_list', 'calls_add', 'calls_edit',
                
                // Client Management - Full access
                'clients_list', 'clients_add', 'clients_edit', 'clients_delete',
                
                // Assets - View only
                'assets_list',
                
                // Reports - View and generate
                'reports_view', 'reports_generate',
                
                // Tools & Utilities
                'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete',
                'activity_view',
            ],
            
            // Staff (Role 4) - Basic access, view and edit own data
            4 => [
                // Core Management - View only
                'dashboard', 'employees_list',
                
                // Project Management - Can view and manage assigned tasks
                'projects_list', 'tasks_list', 'tasks_edit',
                'requirements_list',
                
                // Time & Attendance - Can manage own attendance
                'attendance_list', 'attendance_add', 'attendance_edit',
                'timesheets_list', 'timesheets_add', 'timesheets_edit',
                'leaves_list', 'leaves_add', 'leaves_edit',
                
                // Communication - Full access
                'chats_list', 'chats_add', 'chats_edit', 'chats_delete',
                'announcements_list',
                'calls_list', 'calls_add',
                
                // Tools & Utilities
                'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete',
            ]
        ];
        
        // Get all modules
        $modules_result = $this->db->get('modules')->result();
        $all_modules = [];
        foreach ($modules_result as $module) {
            $all_modules[] = strtolower($module->module_key);
        }
        
        // Clear existing permissions
        $this->db->truncate('permissions');
        
        // Insert enhanced permissions
        foreach ($enhanced_permissions as $role_id => $allowed_modules) {
            foreach ($all_modules as $module) {
                $can_access = in_array($module, $allowed_modules) ? 1 : 0;
                $this->db->insert('permissions', [
                    'role_id' => $role_id,
                    'module' => $module,
                    'can_access' => $can_access
                ]);
            }
        }
        
        echo "<p>✓ Updated enhanced permissions for all roles</p>";
        echo "<p>✓ Admin: " . count($enhanced_permissions[1]) . " permissions</p>";
        echo "<p>✓ Manager: " . count($enhanced_permissions[2]) . " permissions</p>";
        echo "<p>✓ Lead: " . count($enhanced_permissions[3]) . " permissions</p>";
        echo "<p>✓ Staff: " . count($enhanced_permissions[4]) . " permissions</p>";
    }
}

// Run the enhancement
$enhance = new Enhance_Permissions();
$enhance->index();
?>
