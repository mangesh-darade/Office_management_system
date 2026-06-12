<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Enhanced Email Helper with Settings Integration
 * 
 * Provides functions for sending emails with notification settings
 */

if (!function_exists('ensure_email_settings_schema')) {
    /**
     * Ensure email_settings and user_email_preferences tables exist
     * Creates them if they don't exist
     */
    function ensure_email_settings_schema() {
        $CI =& get_instance();
        
        // Disable error reporting temporarily to handle "table already exists" gracefully
        $prev_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;
        
        // Create email_settings table if it doesn't exist
        if (!$CI->db->table_exists('email_settings')) {
            $sql = "CREATE TABLE IF NOT EXISTS `email_settings` (
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
            $CI->db->query($sql);
            
            // Insert default email settings only if they don't already exist
            // Check if any settings exist first
            $existing = $CI->db->get('email_settings')->result();
            if (empty($existing)) {
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
                ];
                
                foreach ($default_settings as $setting) {
                    // Use INSERT IGNORE to avoid duplicate key errors
                    $CI->db->where('module', $setting['module']);
                    $CI->db->where('event_type', $setting['event_type']);
                    $exists = $CI->db->get('email_settings')->row();
                    
                    if (!$exists) {
                        $CI->db->insert('email_settings', $setting);
                    }
                }
            }
        }
        
        // Create user_email_preferences table if it doesn't exist
        if (!$CI->db->table_exists('user_email_preferences')) {
            $sql = "CREATE TABLE IF NOT EXISTS `user_email_preferences` (
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
            $CI->db->query($sql);
        }
        
        // Restore previous debug setting
        $CI->db->db_debug = $prev_debug;
    }
}

if (!function_exists('send_notification_with_settings')) {
    /**
     * Send email notification based on system settings
     * 
     * @param string $module Module name
     * @param string $event_type Event type
     * @param array $data Data for email template
     * @param int $related_user_id User ID related to the event
     * @return bool Whether email was sent successfully
     */
    function send_notification_with_settings($module, $event_type, $data, $related_user_id = null) {
        $CI =& get_instance();
        
        // Ensure tables exist before querying
        ensure_email_settings_schema();
        
        // Check if email is enabled for this module/event
        $setting = $CI->db->where('module', $module)
                          ->where('event_type', $event_type)
                          ->where('is_enabled', 1)
                          ->get('email_settings')
                          ->row();
        
        if (!$setting) {
            return false; // Email disabled for this event
        }
        
        // Get recipients based on setting
        $recipients = get_email_recipients($setting, $data, $related_user_id);
        
        if (empty($recipients)) {
            return false; // No recipients found
        }
        
        // Check user preferences for each recipient
        $final_recipients = [];
        foreach ($recipients as $recipient) {
            if (check_user_email_preference($recipient['user_id'], $module, $event_type)) {
                $final_recipients[] = $recipient;
            }
        }
        
        if (empty($final_recipients)) {
            return false; // All recipients have disabled this notification
        }
        
        // Send emails
        $success_count = 0;
        foreach ($final_recipients as $recipient) {
            $subject = generate_email_subject($module, $event_type, $data);
            $template = generate_module_email_template($module, $event_type, $data);

            // Ensure email is configured from Settings > Email
            if (!function_exists('configure_email_from_settings')) {
                $CI->load->helper('email');
            }
            configure_email_from_settings();

            $from = function_exists('get_system_from_email') ? get_system_from_email() : $CI->config->item('smtp_user');
            $from_name = function_exists('get_company_name') ? get_company_name() : 'Office Management System';

            $CI->email->to($recipient['email']);
            $CI->email->from($from ?: 'no-reply@example.com', $from_name);
            $CI->email->subject($subject);
            $CI->email->message($template);
            
            if ($CI->email->send()) {
                $success_count++;
                log_message('info', "Email notification sent to {$recipient['email']} for {$module}:{$event_type}");
            } else {
                log_message('error', "Failed to send email notification to {$recipient['email']} for {$module}:{$event_type}");
            }
        }
        
        return $success_count > 0;
    }
}

if (!function_exists('get_email_recipients')) {
    /**
     * Get email recipients based on settings
     * 
     * @param object $setting Email setting
     * @param array $data Event data
     * @param int $related_user_id Related user ID
     * @return array Array of recipients with email and user_id
     */
    function get_email_recipients($setting, $data, $related_user_id = null) {
        $CI =& get_instance();
        $recipients = [];
        
        switch ($setting->recipient_type) {
            case 'assignee':
                if (isset($data->assigned_to) && $data->assigned_to) {
                    $email = get_user_email_by_id($data->assigned_to);
                    if ($email) {
                        $recipients[] = ['user_id' => $data->assigned_to, 'email' => $email];
                    }
                }
                break;
                
            case 'self':
            case 'new_member':
                if ($related_user_id) {
                    $email = get_user_email_by_id($related_user_id);
                    if ($email) {
                        $recipients[] = ['user_id' => $related_user_id, 'email' => $email];
                    }
                }
                break;
                
            case 'admin':
                $admins = $CI->db->where('role_id', 1)->get('users')->result();
                foreach ($admins as $admin) {
                    $recipients[] = ['user_id' => $admin->id, 'email' => $admin->email];
                }
                break;
                
            case 'manager':
                // Get managers of the user's department
                if ($related_user_id) {
                    $CI->db->select('u.id, u.email');
                    $CI->db->from('users u');
                    $CI->db->join('employees e', 'e.user_id = u.id', 'left');
                    $CI->db->join('departments d', 'd.id = e.department_id', 'left');
                    $CI->db->where('u.role_id', 2); // Manager role
                    $CI->db->where('d.id', '(SELECT department_id FROM employees WHERE user_id = ' . (int)$related_user_id . ')', false);
                    $managers = $CI->db->get()->result();
                    foreach ($managers as $manager) {
                        $recipients[] = ['user_id' => $manager->id, 'email' => $manager->email];
                    }
                }
                break;
                
            case 'members':
                if (isset($data->project_id) && $data->project_id) {
                    $CI->db->select('u.id, u.email');
                    $CI->db->from('project_members pm');
                    $CI->db->join('users u', 'u.id = pm.user_id', 'inner');
                    $CI->db->where('pm.project_id', $data->project_id);
                    $members = $CI->db->get()->result();
                    foreach ($members as $member) {
                        $recipients[] = ['user_id' => $member->id, 'email' => $member->email];
                    }
                }
                break;
                
            case 'target_roles':
                if (isset($data->target_roles) && $data->target_roles) {
                    $roles = explode(',', $data->target_roles);
                    $CI->db->where_in('role_id', $roles);
                    $users = $CI->db->get('users')->result();
                    foreach ($users as $user) {
                        $recipients[] = ['user_id' => $user->id, 'email' => $user->email];
                    }
                }
                break;
                
            case 'custom':
                if ($setting->custom_recipients) {
                    $emails = explode(',', $setting->custom_recipients);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = ['user_id' => 0, 'email' => $email];
                        }
                    }
                }
                break;
        }
        
        return $recipients;
    }
}

if (!function_exists('check_user_email_preference')) {
    /**
     * Check if user has enabled email for specific module/event
     * 
     * @param int $user_id User ID
     * @param string $module Module name
     * @param string $event_type Event type
     * @return bool Whether user has enabled this notification
     */
    function check_user_email_preference($user_id, $module, $event_type) {
        $CI =& get_instance();
        
        // Ensure tables exist before querying
        ensure_email_settings_schema();
        
        $preference = $CI->db->where('user_id', (int)$user_id)
                           ->where('module', $module)
                           ->where('event_type', $event_type)
                           ->get('user_email_preferences')
                           ->row();
        
        // If no preference set, default to enabled
        if (!$preference) {
            return true;
        }
        
        return (bool)$preference->is_enabled;
    }
}

if (!function_exists('generate_email_subject')) {
    /**
     * Generate email subject based on module and event
     * 
     * @param string $module Module name
     * @param string $event_type Event type
     * @param array $data Event data
     * @return string Generated subject
     */
    function generate_email_subject($module, $event_type, $data) {
        $subjects = [
            'tasks' => [
                'created' => 'New Task Assigned: {title}',
                'updated' => 'Task Updated: {title}',
                'status_changed' => 'Task Status Changed: {title}',
                'comment_added' => 'New Comment on Task: {title}',
                'daily_summary' => 'Daily Task Summary - {count} Tasks'
            ],
            'projects' => [
                'created' => 'New Project Created: {title}',
                'updated' => 'Project Updated: {title}',
                'member_added' => 'Added to Project: {title}',
                'status_changed' => 'Project Status Changed: {title}'
            ],
            'leave_requests' => [
                'submitted' => 'Leave Request Submitted: {type}',
                'approved' => 'Leave Request Approved: {type}',
                'rejected' => 'Leave Request Rejected: {type}',
                'cancelled' => 'Leave Request Cancelled: {type}'
            ],
            'attendance' => [
                'check_in' => 'Attendance Check-in Recorded',
                'check_out' => 'Attendance Check-out Recorded',
                'absent' => 'Absent Marked for {date}',
                'daily_summary' => 'Daily Attendance Summary - {date}'
            ],
            'announcements' => [
                'published' => 'New Announcement: {title}',
                'updated' => 'Announcement Updated: {title}'
            ],
            'employees' => [
                'created' => 'New Employee Added: {name}',
                'updated' => 'Employee Profile Updated: {name}',
                'deleted' => 'Employee Record Deleted: {name}'
            ],
            'timesheets' => [
                'submitted' => 'Timesheet Submitted: {week}',
                'approved' => 'Timesheet Approved: {week}',
                'rejected' => 'Timesheet Rejected: {week}',
                'weekly_summary' => 'Weekly Timesheet Summary - {week}'
            ],
            'payroll' => [
                'generated' => 'Payroll Generated: {period}',
                'updated' => 'Payroll Updated: {period}',
                'disbursed' => 'Salary Disbursed: {period}'
            ]
        ];
        
        $template = isset($subjects[$module][$event_type]) ? $subjects[$module][$event_type] : '{module} - {event_type}';
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }
        
        return $template;
    }
}

if (!function_exists('generate_module_email_template')) {
    /**
     * Generate email template for specific module and event
     * 
     * @param string $module Module name
     * @param string $event_type Event type
     * @param array $data Event data
     * @return string HTML email template
     */
    function generate_module_email_template($module, $event_type, $data) {
        $base_url = base_url();
        
        // Use existing task template for tasks
        if ($module === 'tasks') {
            return generate_task_email_template($data, $event_type);
        }
        
        // Generic template for other modules
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . ucfirst($module) . ' Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f8f9fa; }
        .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .label { font-weight: bold; color: #666; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 ' . ucfirst($module) . ' Notification</h1>
        </div>
        
        <div class="content">
            <h2>' . ucfirst(str_replace('_', ' ', $event_type)) . '</h2>
            
            <div class="details">
                <div class="label">Details:</div>';
        
        // Add data fields
        foreach ($data as $key => $value) {
            if ($key !== 'id' && !empty($value) && is_string($value)) {
                $label = ucfirst(str_replace('_', ' ', $key));
                $display_value = strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value;
                $html .= '<div class="mb-2">
                    <span class="label">' . htmlspecialchars($label) . ':</span> 
                    ' . htmlspecialchars($display_value) . '
                </div>';
            }
        }
        
        $html .= '</div>
            
            <div class="footer">
                <p>This is an automated message from the Office Management System.</p>
                <p>If you have any questions, please contact your administrator.</p>
            </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// Include all existing email helper functions
require_once(APPPATH . 'helpers/email_helper.php');
