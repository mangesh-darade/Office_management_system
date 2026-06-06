<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System settings, role_permissions, user_module_access bootstrap.
 */

if (!function_exists('system_settings_schema_seed_defaults')) {
    function system_settings_schema_seed_defaults($db)
    {
        $default_settings = [
            // General Settings
            ['setting_key' => 'company_name', 'setting_value' => 'Office Management System', 'setting_type' => 'text', 'description' => 'Company name displayed throughout the system', 'category' => 'general', 'is_public' => 1],
            ['setting_key' => 'company_logo', 'setting_value' => '', 'setting_type' => 'file', 'description' => 'Company logo file path', 'category' => 'general', 'is_public' => 1],
            ['setting_key' => 'default_timezone', 'setting_value' => 'Asia/Kolkata', 'setting_type' => 'select', 'description' => 'Default timezone for the system', 'category' => 'general', 'is_public' => 0],
            ['setting_key' => 'date_format', 'setting_value' => 'Y-m-d', 'setting_type' => 'select', 'description' => 'Default date format', 'category' => 'general', 'is_public' => 0],
            ['setting_key' => 'time_format', 'setting_value' => '24h', 'setting_type' => 'select', 'description' => 'Time format (12h or 24h)', 'category' => 'general', 'is_public' => 0],
            
            // Success Screen Settings
            ['setting_key' => 'show_success_screen', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Show success screen after login', 'category' => 'ui', 'is_public' => 0],
            ['setting_key' => 'success_screen_duration', 'setting_value' => '3', 'setting_type' => 'number', 'description' => 'Success screen display duration in seconds', 'category' => 'ui', 'is_public' => 0],
            ['setting_key' => 'success_screen_modules', 'setting_value' => 'dashboard,tasks,projects,attendance', 'setting_type' => 'text', 'description' => 'Modules to show on success screen (comma separated)', 'category' => 'ui', 'is_public' => 0],
            
            // Module Settings
            ['setting_key' => 'enable_module_dashboard', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Dashboard module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_tasks', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Tasks module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_projects', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Projects module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_employees', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Employees module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_attendance', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Attendance module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_leave_requests', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Leave Requests module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_announcements', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Announcements module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_reports', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Reports module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_timesheets', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Timesheets module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_payroll', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable Payroll module', 'category' => 'modules', 'is_public' => 1],
            ['setting_key' => 'enable_module_external_training', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Enable External trainings (system access / success screen)', 'category' => 'modules', 'is_public' => 1],
            
            // Security Settings
            ['setting_key' => 'session_timeout', 'setting_value' => '3600', 'setting_type' => 'number', 'description' => 'Session timeout in seconds', 'category' => 'security', 'is_public' => 0],
            ['setting_key' => 'max_login_attempts', 'setting_value' => '5', 'setting_type' => 'number', 'description' => 'Maximum login attempts before lockout', 'category' => 'security', 'is_public' => 0],
            ['setting_key' => 'password_min_length', 'setting_value' => '6', 'setting_type' => 'number', 'description' => 'Minimum password length', 'category' => 'security', 'is_public' => 0],
            ['setting_key' => 'require_password_change', 'setting_value' => '0', 'setting_type' => 'boolean', 'description' => 'Require password change every X days', 'category' => 'security', 'is_public' => 0],
        ];

        foreach ($default_settings as $setting) {
            $db->where('setting_key', $setting['setting_key']);
            $exists = $db->get('system_settings')->row();
            
            if (!$exists) {
                $db->insert('system_settings', $setting);
            }
        }
    }
}

if (!function_exists('system_settings_schema_seed_role_permissions')) {
    function system_settings_schema_seed_role_permissions($db)
    {
        $modules = [
            'dashboard' => ['view', 'edit'],
            'tasks' => ['view', 'add', 'edit', 'delete', 'list', 'board'],
            'projects' => ['view', 'add', 'edit', 'delete', 'list', 'manage_members'],
            'employees' => ['view', 'add', 'edit', 'delete', 'list', 'documents'],
            'attendance' => ['view', 'add', 'edit', 'delete', 'list', 'report'],
            'leave_requests' => ['view', 'add', 'edit', 'delete', 'list', 'approve', 'reject'],
            'announcements' => ['view', 'add', 'edit', 'delete', 'list', 'publish'],
            'reports' => ['view', 'generate', 'export'],
            'timesheets' => ['view', 'add', 'edit', 'delete', 'list', 'approve', 'reject'],
            'payroll' => ['view', 'add', 'edit', 'delete', 'list', 'generate', 'disburse'],
            'users' => ['view', 'add', 'edit', 'delete', 'list'],
            'settings' => ['view', 'edit'],
            'email_settings' => ['view', 'edit', 'test'],
            'system_settings' => ['view', 'edit'],
            'external_training' => ['view', 'add', 'edit', 'delete', 'list'],
        ];

        // Role-based default permissions
        $role_permissions = [
            1 => [ // Admin - All permissions
                'all' => 'all'
            ],
            2 => [ // Manager
                'dashboard' => ['view', 'edit'],
                'tasks' => ['view', 'add', 'edit', 'list', 'board'],
                'projects' => ['view', 'add', 'edit', 'list', 'manage_members'],
                'employees' => ['view', 'list'],
                'attendance' => ['view', 'add', 'list', 'report'],
                'leave_requests' => ['view', 'list', 'approve', 'reject'],
                'announcements' => ['view', 'list'],
                'reports' => ['view', 'generate'],
                'timesheets' => ['view', 'list', 'approve', 'reject'],
                'payroll' => ['view', 'list'],
                'external_training' => ['view', 'add', 'edit', 'delete', 'list'],
            ],
            3 => [ // Lead
                'dashboard' => ['view', 'edit'],
                'tasks' => ['view', 'add', 'edit', 'list', 'board'],
                'projects' => ['view', 'list'],
                'employees' => ['view', 'list'],
                'attendance' => ['view', 'add', 'list'],
                'leave_requests' => ['view', 'list'],
                'announcements' => ['view', 'list'],
                'timesheets' => ['view', 'add', 'list'],
                'external_training' => ['view', 'list', 'add', 'edit'],
            ],
            4 => [ // Staff
                'dashboard' => ['view'],
                'tasks' => ['view', 'list', 'board'],
                'projects' => ['view', 'list'],
                'employees' => ['view'],
                'attendance' => ['view', 'add', 'list'],
                'leave_requests' => ['view', 'add', 'list'],
                'announcements' => ['view', 'list'],
                'timesheets' => ['view', 'add', 'list'],
                'external_training' => ['view', 'list'],
            ]
        ];

        foreach ($role_permissions as $role_id => $permissions) {
            foreach ($permissions as $module => $module_perms) {
                if ($module === 'all') {
                    // Admin gets all permissions for all modules
                    foreach ($modules as $mod_name => $perms) {
                        foreach ($perms as $perm) {
                            $db->replace('role_permissions', [
                                'role_id' => $role_id,
                                'module' => $mod_name,
                                'permission' => $perm,
                                'is_allowed' => 1
                            ]);
                        }
                    }
                } else {
                    foreach ($module_perms as $perm) {
                        $db->replace('role_permissions', [
                            'role_id' => $role_id,
                            'module' => $module,
                            'permission' => $perm,
                            'is_allowed' => 1
                        ]);
                    }
                }
            }
        }
    }
}

if (!function_exists('system_settings_schema_ensure')) {
    function system_settings_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        // Create system_settings table
        if (!$db->table_exists('system_settings')) {
            $sql = "CREATE TABLE `system_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text DEFAULT NULL,
                `setting_type` varchar(20) DEFAULT 'text',
                `description` text DEFAULT NULL,
                `category` varchar(50) DEFAULT 'general',
                `is_public` tinyint(1) DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_setting_key` (`setting_key`),
                KEY `idx_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Create role_permissions table
        if (!$db->table_exists('role_permissions')) {
            $sql = "CREATE TABLE `role_permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_id` int(11) NOT NULL,
                `module` varchar(50) NOT NULL,
                `permission` varchar(50) NOT NULL,
                `is_allowed` tinyint(1) DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_role_module_permission` (`role_id`, `module`, `permission`),
                KEY `idx_role` (`role_id`),
                KEY `idx_module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Create user_module_access table
        if (!$db->table_exists('user_module_access')) {
            $sql = "CREATE TABLE `user_module_access` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `module` varchar(50) NOT NULL,
                `is_accessible` tinyint(1) DEFAULT 1,
                `custom_permissions` text DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_user_module` (`user_id`, `module`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Insert default settings and permissions
        system_settings_schema_seed_defaults($db);
        system_settings_schema_seed_role_permissions($db);
    }
}
