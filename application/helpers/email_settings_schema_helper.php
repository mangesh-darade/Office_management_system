<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email settings tables and default notification rows.
 */

if (!function_exists('email_settings_schema_seed_defaults')) {
    function email_settings_schema_seed_defaults($db)
    {
        $default_settings = [
            // Tasks module
            ['module' => 'tasks', 'event_type' => 'created', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'updated', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'status_changed', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'comment_added', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'daily_summary', 'recipient_type' => 'assignee'],
            
            // Projects module
            ['module' => 'projects', 'event_type' => 'created', 'recipient_type' => 'members'],
            ['module' => 'projects', 'event_type' => 'updated', 'recipient_type' => 'members'],
            ['module' => 'projects', 'event_type' => 'member_added', 'recipient_type' => 'new_member'],
            ['module' => 'projects', 'event_type' => 'status_changed', 'recipient_type' => 'members'],

            // Requirements module (SMTP email + link — not Google Calendar)
            ['module' => 'requirements', 'event_type' => 'created', 'recipient_type' => 'assignee'],
            ['module' => 'requirements', 'event_type' => 'updated', 'recipient_type' => 'assignee'],
            ['module' => 'requirements', 'event_type' => 'status_changed', 'recipient_type' => 'assignee'],
            
            // Leave Requests module
            ['module' => 'leave_requests', 'event_type' => 'submitted', 'recipient_type' => 'manager'],
            ['module' => 'leave_requests', 'event_type' => 'approved', 'recipient_type' => 'employee'],
            ['module' => 'leave_requests', 'event_type' => 'rejected', 'recipient_type' => 'employee'],
            ['module' => 'leave_requests', 'event_type' => 'cancelled', 'recipient_type' => 'manager'],
            
            // Attendance module
            ['module' => 'attendance', 'event_type' => 'check_in', 'recipient_type' => 'self'],
            ['module' => 'attendance', 'event_type' => 'check_out', 'recipient_type' => 'self'],
            ['module' => 'attendance', 'event_type' => 'absent', 'recipient_type' => 'manager'],
            ['module' => 'attendance', 'event_type' => 'daily_summary', 'recipient_type' => 'admin'],
            
            // Announcements module
            ['module' => 'announcements', 'event_type' => 'published', 'recipient_type' => 'target_roles'],
            ['module' => 'announcements', 'event_type' => 'updated', 'recipient_type' => 'target_roles'],
            
            // Employees module
            ['module' => 'employees', 'event_type' => 'created', 'recipient_type' => 'admin'],
            ['module' => 'employees', 'event_type' => 'updated', 'recipient_type' => 'self'],
            ['module' => 'employees', 'event_type' => 'deleted', 'recipient_type' => 'admin'],
            
            // Timesheets module
            ['module' => 'timesheets', 'event_type' => 'submitted', 'recipient_type' => 'manager'],
            ['module' => 'timesheets', 'event_type' => 'approved', 'recipient_type' => 'employee'],
            ['module' => 'timesheets', 'event_type' => 'rejected', 'recipient_type' => 'employee'],
            ['module' => 'timesheets', 'event_type' => 'weekly_summary', 'recipient_type' => 'employee'],
            
            // Payroll module
            ['module' => 'payroll', 'event_type' => 'generated', 'recipient_type' => 'employee'],
            ['module' => 'payroll', 'event_type' => 'updated', 'recipient_type' => 'employee'],
            ['module' => 'payroll', 'event_type' => 'disbursed', 'recipient_type' => 'employee'],

            // Recruitment module
            ['module' => 'recruitment', 'event_type' => 'interview_scheduled', 'recipient_type' => 'candidate'],
        ];

        foreach ($default_settings as $setting) {
            $db->where('module', $setting['module']);
            $db->where('event_type', $setting['event_type']);
            $exists = $db->get('email_settings')->row();
            
            if (!$exists) {
                // Initialize default template if missing
                if ($setting['module'] === 'recruitment' && $setting['event_type'] === 'interview_scheduled') {
                    $setting['email_template'] = "Dear {candidate_name},\n\nYour interview for the position of {job_title} has been scheduled.\nDate: {date}\nType: {type}\n\nPlease be available.\n\nBest Regards,\nHR Team";
                }
                $db->insert('email_settings', $setting);
            }
        }
    }
}

if (!function_exists('email_settings_schema_ensure')) {
    function email_settings_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        // Create email_settings table
        if (!$db->table_exists('email_settings')) {
            $sql = "CREATE TABLE `email_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(50) NOT NULL,
                `event_type` varchar(50) NOT NULL,
                `is_enabled` tinyint(1) DEFAULT 1,
                `recipient_type` varchar(20) DEFAULT 'assignee',
                `custom_recipients` text DEFAULT NULL,
                `email_template` text DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_module_event` (`module`, `event_type`),
                KEY `idx_module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Create user_email_preferences table
        if (!$db->table_exists('user_email_preferences')) {
            $sql = "CREATE TABLE `user_email_preferences` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `module` varchar(50) NOT NULL,
                `event_type` varchar(50) NOT NULL,
                `is_enabled` tinyint(1) DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_user_module_event` (`user_id`, `module`, `event_type`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Insert default email settings
        email_settings_schema_seed_defaults($db);
    }
}
