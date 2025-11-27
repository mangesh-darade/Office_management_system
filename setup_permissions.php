<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Setup Script
 * 
 * This script sets up the modules table and populates it with all available modules
 * along with default permissions for each user group.
 */

// Include CodeIgniter if not already included
if (!defined('BASEPATH')) {
    // Manually include CodeIgniter if running standalone
    $_SERVER['REQUEST_URI'] = '/setup_permissions';
    include_once 'index.php';
}

class Setup_Permissions extends CI_Controller {
    
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
        echo "<h1>Office Management System - Permission Setup</h1>";
        
        // Create modules table if not exists
        $this->create_modules_table();
        
        // Create permissions table if not exists
        $this->create_permissions_table();
        
        // Setup modules
        $this->setup_modules();
        
        // Setup default permissions
        $this->setup_default_permissions();
        
        echo "<div class='alert alert-success'>Permission setup completed successfully!</div>";
        echo "<a href='" . site_url('permissions') . "' class='btn btn-primary'>Manage Permissions</a>";
        echo "<a href='" . site_url('dashboard') . "' class='btn btn-secondary ms-2'>Dashboard</a>";
    }
    
    private function create_modules_table() {
        if (!$this->db->table_exists('modules')) {
            $sql = "CREATE TABLE `modules` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module_key` varchar(100) NOT NULL,
                `module_label` varchar(200) NOT NULL,
                `description` text DEFAULT NULL,
                `icon` varchar(50) DEFAULT NULL,
                `category` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_module_key` (`module_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
            echo "<p>✓ Created modules table</p>";
        } else {
            echo "<p>✓ Modules table already exists</p>";
        }
    }
    
    private function create_permissions_table() {
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
            echo "<p>✓ Created permissions table</p>";
        } else {
            echo "<p>✓ Permissions table already exists</p>";
        }
    }
    
    private function setup_modules() {
        // Define all available modules with their details
        $modules = [
            // Core Management
            'dashboard' => ['Dashboard', 'Main dashboard and overview', 'bi-grid-3x3-gap', 'core', 1],
            'employees' => ['Employee Management', 'Manage employee records and profiles', 'bi-people', 'hr', 2],
            'users' => ['User Management', 'Manage system user accounts', 'bi-person-lock', 'admin', 3],
            'permissions' => ['Permission Manager', 'Configure role-based permissions', 'bi-shield-lock', 'admin', 4],
            
            // Project Management
            'projects' => ['Project Management', 'Manage projects and milestones', 'bi-kanban', 'projects', 10],
            'tasks' => ['Task Management', 'Manage tasks and assignments', 'bi-check2-square', 'projects', 11],
            'requirements' => ['Requirements', 'Track project requirements', 'bi-clipboard-check', 'projects', 12],
            
            // Time & Attendance
            'attendance' => ['Attendance', 'Track employee attendance', 'bi-clock-history', 'attendance', 20],
            'timesheets' => ['Timesheets', 'Manage timesheet entries', 'bi-stopwatch', 'attendance', 21],
            'leaves' => ['Leave Management', 'Manage leave requests and approvals', 'bi-calendar-x', 'attendance', 22],
            
            // Communication
            'chats' => ['Chat System', 'Internal messaging and chat', 'bi-chat-dots', 'communication', 30],
            'announcements' => ['Announcements', 'Company announcements and notices', 'bi-megaphone', 'communication', 31],
            'calls' => ['Call Management', 'Manage voice/video calls', 'bi-telephone', 'communication', 32],
            
            // Client Management
            'clients' => ['Client Management', 'Manage client information', 'bi-briefcase', 'sales', 40],
            
            // Assets & Resources
            'assets' => ['Asset Management', 'Track company assets', 'bi-box-seam', 'resources', 50],
            
            // Payroll
            'payroll' => ['Payroll', 'Manage payroll and compensation', 'bi-currency-dollar', 'finance', 60],
            
            // Reports
            'reports' => ['Reports', 'Generate various reports', 'bi-file-earmark-bar-graph', 'reports', 70],
            
            // Settings
            'settings' => ['Settings', 'System configuration', 'bi-gear', 'admin', 80],
            'departments' => ['Departments', 'Manage departments', 'bi-building', 'admin', 81],
            'designations' => ['Designations', 'Manage job designations', 'bi-award', 'admin', 82],
            
            // Tools & Utilities
            'reminders' => ['Reminders', 'Set and manage reminders', 'bi-bell', 'tools', 90],
            'activity' => ['Activity Log', 'View system activity logs', 'bi-activity', 'tools', 91],
            'db' => ['Database Tools', 'Database management tools', 'bi-database', 'admin', 99],
        ];
        
        // Clear existing modules
        $this->db->truncate('modules');
        
        // Insert modules
        $sort_order = 1;
        foreach ($modules as $key => $details) {
            $this->db->insert('modules', [
                'module_key' => $key,
                'module_label' => $details[0],
                'description' => $details[1],
                'icon' => $details[2],
                'category' => $details[3],
                'sort_order' => $details[4],
                'is_active' => 1
            ]);
        }
        
        echo "<p>✓ Setup " . count($modules) . " modules</p>";
    }
    
    private function setup_default_permissions() {
        // Define default permissions for each role
        // Role IDs: 1=Admin, 2=Manager, 3=Lead, 4=Staff
        
        $default_permissions = [
            // Admin (Role 1) - Full access
            1 => [
                'dashboard', 'employees', 'users', 'permissions', 'projects', 'tasks', 
                'requirements', 'attendance', 'timesheets', 'leaves', 'chats', 'announcements', 
                'calls', 'clients', 'assets', 'payroll', 'reports', 'settings', 
                'departments', 'designations', 'reminders', 'activity', 'db'
            ],
            
            // Manager (Role 2) - HR/Admin access
            2 => [
                'dashboard', 'employees', 'projects', 'tasks', 'requirements', 
                'attendance', 'timesheets', 'leaves', 'chats', 'announcements', 
                'calls', 'clients', 'assets', 'reports', 'reminders', 'activity'
            ],
            
            // Lead (Role 3) - Team management
            3 => [
                'dashboard', 'employees', 'projects', 'tasks', 'requirements', 
                'attendance', 'timesheets', 'leaves', 'chats', 'announcements', 
                'calls', 'clients', 'reports', 'reminders', 'activity'
            ],
            
            // Staff (Role 4) - Basic access
            4 => [
                'dashboard', 'tasks', 'attendance', 'timesheets', 'leaves', 
                'chats', 'announcements', 'reminders'
            ]
        ];
        
        // Clear existing permissions
        $this->db->truncate('permissions');
        
        // Get all modules
        $modules_result = $this->db->get('modules')->result();
        $all_modules = [];
        foreach ($modules_result as $module) {
            $all_modules[] = strtolower($module->module_key);
        }
        
        // Insert permissions
        foreach ($default_permissions as $role_id => $allowed_modules) {
            foreach ($all_modules as $module) {
                $can_access = in_array($module, $allowed_modules) ? 1 : 0;
                $this->db->insert('permissions', [
                    'role_id' => $role_id,
                    'module' => $module,
                    'can_access' => $can_access
                ]);
            }
        }
        
        echo "<p>✓ Setup default permissions for all roles</p>";
        echo "<p>✓ Admin: " . count($default_permissions[1]) . " modules</p>";
        echo "<p>✓ Manager: " . count($default_permissions[2]) . " modules</p>";
        echo "<p>✓ Lead: " . count($default_permissions[3]) . " modules</p>";
        echo "<p>✓ Staff: " . count($default_permissions[4]) . " modules</p>";
    }
}

// Run the setup
$setup = new Setup_Permissions();
$setup->index();
?>
