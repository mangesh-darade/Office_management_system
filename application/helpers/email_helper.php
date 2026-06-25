<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Helper Functions
 *
 * Provides functions for sending emails with templates and
 * centralised configuration that reads from Settings > Email.
 */

if (!function_exists('get_system_from_email')) {
    /**
     * Get the "from" email address from settings with config/env fallback.
     *
     * @return string
     */
    function get_system_from_email() {
        $CI =& get_instance();

        // Load settings model if not already loaded
        if (!isset($CI->settings)) {
            $CI->load->model('Setting_model', 'settings');
        }

        $smtp_user = $CI->settings->get_setting('email_smtp_user');
        if ($smtp_user) {
            return trim((string)$smtp_user);
        }

        // Fallback to config/email.php (which may use env vars)
        return trim((string)$CI->config->item('smtp_user'));
    }
}

if (!function_exists('configure_email_from_settings')) {
    /**
     * Initialize CI Email library using values from Settings > Email.
     *
     * This will override the base email config with runtime values from
     * settings: email_smtp_host, email_smtp_port, email_smtp_user,
     * email_smtp_pass, email_smtp_crypto.
     */
    function configure_email_from_settings() {
        $CI =& get_instance();

        // Load dependencies
        if (!isset($CI->settings)) {
            $CI->load->model('Setting_model', 'settings');
        }
        $CI->load->library('email');

        $smtp_user   = $CI->settings->get_setting('email_smtp_user');
        $smtp_pass   = $CI->settings->get_setting('email_smtp_pass');
        $smtp_host   = $CI->settings->get_setting('email_smtp_host', 'smtp.gmail.com');
        $smtp_port   = $CI->settings->get_setting('email_smtp_port', '587');
        $smtp_crypto = $CI->settings->get_setting('email_smtp_crypto', 'tls');

        // Auto-correct crypto based on port if needed
        if ((int)$smtp_port === 465 && $smtp_crypto !== 'ssl') {
            $smtp_crypto = 'ssl';
        } elseif ((int)$smtp_port === 587 && $smtp_crypto !== 'tls') {
            $smtp_crypto = 'tls';
        }

        if ($smtp_user && $smtp_pass) {
            $config = array(
                'protocol'   => 'smtp',
                'smtp_host'  => $smtp_host,
                'smtp_port'  => $smtp_port,
                'smtp_user'  => $smtp_user,
                'smtp_pass'  => $smtp_pass,
                'smtp_crypto'=> $smtp_crypto,
                'mailtype'   => 'html',
                'charset'    => 'utf-8',
                'wordwrap'   => true,
                'newline'    => "\r\n",
                'crlf'       => "\r\n",
            );

            $CI->email->initialize($config);
        }
    }
}

if (!function_exists('send_task_notification')) {
    /**
     * Send task notification email to assigned user
     * 
     * @param string $to Email address of recipient
     * @param string $subject Email subject
     * @param array $task Task details or array of tasks
     * @param string $action Action type (created, updated, status_changed)
     * @return bool Whether email was sent successfully
     */
    function send_task_notification($to, $subject, $task, $action = 'created') {
        $CI =& get_instance();
        configure_email_from_settings();
        $CI->email->clear(TRUE);

        // Configure email
        $from = get_system_from_email();
        $from_name = function_exists('get_company_name') ? get_company_name() : 'Office Management System';
        $CI->email->from($from ?: 'no-reply@example.com', $from_name);
        $CI->email->to($to);
        $CI->email->subject($subject);
        
        // Generate email content
        $message = generate_task_email_template($task, $action);
        $CI->email->message($message);
        
        // Send email
        return $CI->email->send();
    }
}

if (!function_exists('send_multiple_tasks_notification')) {
    /**
     * Send multiple tasks notification email with priority ordering
     * 
     * @param string $to Email address of recipient
     * @param string $subject Email subject
     * @param array $tasks Array of task details
     * @param string $action Action type
     * @return bool Whether email was sent successfully
     */
    function send_multiple_tasks_notification($to, $subject, $tasks, $action = 'created') {
        $CI =& get_instance();
        configure_email_from_settings();
        $CI->email->clear(TRUE);

        // Configure email
        $from = get_system_from_email();
        $from_name = function_exists('get_company_name') ? get_company_name() : 'Office Management System';
        $CI->email->from($from ?: 'no-reply@example.com', $from_name);
        $CI->email->to($to);
        $CI->email->subject($subject);
        
        // Generate email content with multiple tasks
        $message = generate_multiple_tasks_email_template($tasks, $action);
        $CI->email->message($message);
        
        // Send email
        return $CI->email->send();
    }
}

if (!function_exists('generate_task_email_template')) {
    /**
     * Generate HTML email template for task notification
     * 
     * @param array $task Task details or array of tasks
     * @param string $action Action type
     * @return string HTML email content
     */
    function generate_task_email_template($task, $action) {
        // Handle single task or array of tasks
        if (is_array($task) && isset($task[0])) {
            return generate_multiple_tasks_email_template($task, $action);
        }
        
        $base_url = base_url();
        $task_url = $base_url . 'tasks/' . $task->id;
        
        // Status colors
        $status_colors = [
            'pending' => '#6c757d',
            'in_progress' => '#0dcaf0',
            'completed' => '#198754',
            'blocked' => '#dc3545'
        ];
        
        $status_color = isset($status_colors[$task->status]) ? $status_colors[$task->status] : '#6c757d';
        
        // Priority colors
        $priority_colors = [
            'low' => '#198754',
            'medium' => '#ffc107',
            'high' => '#fd7e14',
            'urgent' => '#dc3545'
        ];
        
        $priority_color = isset($priority_colors[$task->priority]) ? $priority_colors[$task->priority] : '#ffc107';
        
        // Priority order for display
        $priority_order = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        $priority_display = isset($priority_order[$task->priority]) ? $priority_order[$task->priority] : 3;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f8f9fa; }
        .task-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid ' . $priority_color . '; }
        .task-title { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 10px; }
        .task-meta { display: flex; gap: 10px; margin: 15px 0; flex-wrap: wrap; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; }
        .section { margin: 20px 0; }
        .label { font-weight: bold; color: #666; }
        .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .description { background: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .priority-indicator { font-weight: bold; text-transform: uppercase; }
        .priority-high { color: #dc3545; }
        .priority-urgent { color: #721c24; }
        .priority-medium { color: #664d03; }
        .priority-low { color: #0f5132; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Task Notification</h1>
        </div>
        
        <div class="content">
            <h2>Task ' . ucfirst($action) . '</h2>
            
            <div class="task-details">
                <div class="task-title">
                    <span class="priority-indicator priority-' . $task->priority . '">[' . strtoupper($task->priority) . ']</span>
                    ' . esc_view($task->title) . '
                </div>
                
                <div class="task-meta">
                    <span class="badge" style="background-color: ' . $status_color . '">
                        Status: ' . ucfirst(str_replace('_', ' ', $task->status)) . '
                    </span>';
                    
        if (isset($task->priority)) {
            $html .= '<span class="badge" style="background-color: ' . $priority_color . '">
                        Priority: ' . ucfirst($task->priority) . '
                    </span>';
        }
        
        $html .= '</div>';
                
        if (!empty($task->description)) {
            // Clean up HTML description for email display
            $clean_description = clean_html_for_email($task->description);
            $html .= '<div class="section">
                <div class="label">Description:</div>
                <div class="description">' . $clean_description . '</div>
            </div>';
        }
        
        if (!empty($task->due_date)) {
            $html .= '<div class="section">
                <div class="label">Due Date:</div>
                <div>' . date('F j, Y', strtotime($task->due_date)) . '</div>
            </div>';
        }
        
        if (!empty($task->project_name)) {
            $html .= '<div class="section">
                <div class="label">Project:</div>
                <div>' . esc_view($task->project_name) . '</div>
            </div>';
        }
        
        $html .= '<div style="margin-top: 30px;">
            <a href="' . $task_url . '" class="button">View Task Details</a>
        </div>
            </div>
            
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

if (!function_exists('generate_multiple_tasks_email_template')) {
    /**
     * Generate HTML email template for multiple tasks with priority ordering
     * 
     * @param array $tasks Array of task details
     * @param string $action Action type
     * @return string HTML email content
     */
    function generate_multiple_tasks_email_template($tasks, $action) {
        $base_url = base_url();
        
        // Status colors
        $status_colors = [
            'pending' => '#6c757d',
            'in_progress' => '#0dcaf0',
            'completed' => '#198754',
            'blocked' => '#dc3545'
        ];
        
        // Priority colors
        $priority_colors = [
            'low' => '#198754',
            'medium' => '#ffc107',
            'high' => '#fd7e14',
            'urgent' => '#dc3545'
        ];
        
        // Priority order for sorting
        $priority_order = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        
        // Sort tasks by priority (high to low)
        usort($tasks, function($a, $b) use ($priority_order) {
            $priority_a = isset($priority_order[$a->priority]) ? $priority_order[$a->priority] : 3;
            $priority_b = isset($priority_order[$b->priority]) ? $priority_order[$b->priority] : 3;
            return $priority_a - $priority_b;
        });
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f8f9fa; }
        .task-item { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .task-item.urgent { border-left-color: #dc3545; }
        .task-item.high { border-left-color: #fd7e14; }
        .task-item.medium { border-left-color: #ffc107; }
        .task-item.low { border-left-color: #198754; }
        .task-title { font-size: 18px; font-weight: bold; color: #007bff; margin-bottom: 8px; }
        .task-meta { display: flex; gap: 8px; margin: 10px 0; flex-wrap: wrap; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; color: white; }
        .priority-indicator { font-weight: bold; text-transform: uppercase; font-size: 11px; }
        .priority-high { color: #dc3545; }
        .priority-urgent { color: #721c24; }
        .priority-medium { color: #664d03; }
        .priority-low { color: #0f5132; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .summary { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Tasks Notification</h1>
        </div>
        
        <div class="content">
            <h2>Tasks ' . ucfirst($action) . '</h2>';
            
        // Summary section
        $urgent_count = 0;
        $high_count = 0;
        $medium_count = 0;
        $low_count = 0;
        
        foreach ($tasks as $task) {
            switch($task->priority) {
                case 'urgent': $urgent_count++; break;
                case 'high': $high_count++; break;
                case 'medium': $medium_count++; break;
                case 'low': $low_count++; break;
            }
        }
        
        $html .= '<div class="summary">
            <h3>Priority Summary:</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <span><strong>Urgent:</strong> ' . $urgent_count . '</span>
                <span><strong>High:</strong> ' . $high_count . '</span>
                <span><strong>Medium:</strong> ' . $medium_count . '</span>
                <span><strong>Low:</strong> ' . $low_count . '</span>
            </div>
            <div style="margin-top: 10px;"><strong>Total Tasks:</strong> ' . count($tasks) . '</div>
        </div>';
        
        // Display tasks in priority order
        foreach ($tasks as $task) {
            $status_color = isset($status_colors[$task->status]) ? $status_colors[$task->status] : '#6c757d';
            $priority_color = isset($priority_colors[$task->priority]) ? $priority_colors[$task->priority] : '#ffc107';
            $task_url = $base_url . 'tasks/' . $task->id;
            
            $html .= '<div class="task-item ' . $task->priority . '">
                <div class="task-title">
                    <span class="priority-indicator priority-' . $task->priority . '">[' . strtoupper($task->priority) . ']</span>
                    ' . esc_view($task->title) . '
                </div>
                
                <div class="task-meta">
                    <span class="badge" style="background-color: ' . $status_color . '">
                        ' . ucfirst(str_replace('_', ' ', $task->status)) . '
                    </span>
                    <span class="badge" style="background-color: ' . $priority_color . '">
                        ' . ucfirst($task->priority) . '
                    </span>';
                    
            if (!empty($task->project_name)) {
                $html .= '<span class="badge" style="background-color: #6c757d;">
                    ' . esc_view($task->project_name) . '
                </span>';
            }
            
            $html .= '</div>';
                
            if (!empty($task->description)) {
                $clean_description = clean_html_for_email($task->description);
                $short_desc = strlen(strip_tags($clean_description)) > 150 ? substr(strip_tags($clean_description), 0, 150) . '...' : $clean_description;
                $html .= '<div style="margin: 10px 0; font-size: 14px; color: #666; line-height: 1.4;">
                    ' . $short_desc . '
                </div>';
            }
            
            if (!empty($task->due_date)) {
                $html .= '<div style="margin: 8px 0; font-size: 13px; color: #666;">
                    <strong>Due:</strong> ' . date('M j, Y', strtotime($task->due_date)) . '
                </div>';
            }
            
            $html .= '<div style="margin-top: 12px;">
                <a href="' . $task_url . '" style="color: #007bff; text-decoration: none; font-size: 13px;">
                    View Details →
                </a>
            </div>
            </div>';
        }
        
        $html .= '<div class="footer">
            <p>This is an automated message from the Office Management System.</p>
            <p>Tasks are displayed in priority order: Urgent → High → Medium → Low</p>
        </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}

if (!function_exists('get_user_email_by_id')) {
    /**
     * Get user email by user ID
     * 
     * @param int $user_id User ID
     * @return string|null Email address or null if not found
     */
    function get_user_email_by_id($user_id) {
        $CI =& get_instance();
        
        $CI->db->select('email');
        $CI->db->where('id', (int)$user_id);
        $user = $CI->db->get('users')->row();
        
        return $user ? $user->email : null;
    }
}

if (!function_exists('get_employee_email_by_task_id')) {
    /**
     * Get employee email for task assignment
     * 
     * @param int $task_id Task ID
     * @return string|null Email address or null if not found
     */
    function get_employee_email_by_task_id($task_id) {
        $CI =& get_instance();
        
        $CI->db->select('u.email');
        $CI->db->from('tasks t');
        $CI->db->join('users u', 'u.id = t.assigned_to', 'left');
        $CI->db->where('t.id', (int)$task_id);
        $result = $CI->db->get()->row();
        
        return $result ? $result->email : null;
    }
}

if (!function_exists('clean_html_for_email')) {
    /**
     * Clean HTML content for email display
     * 
     * @param string $html HTML content to clean
     * @return string Cleaned HTML content suitable for email
     */
    function clean_html_for_email($html) {
        if (empty($html)) {
            return '';
        }
        
        // Remove unwanted style attributes and clean up HTML
        $html = preg_replace('/style="[^"]*"/i', '', $html);
        
        // Remove font-family and font-size styles from span tags
        $html = preg_replace('/<span[^>]*>/i', '<span>', $html);
        
        // Convert to simple paragraphs and line breaks
        $html = preg_replace('/<p[^>]*>/i', '<p>', $html);
        
        // Remove empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        
        // Clean up multiple consecutive spaces
        $html = preg_replace('/\s+/', ' ', $html);
        
        // Ensure proper paragraph spacing
        $html = str_replace('</p><p>', '</p><br><p>', $html);
        
        // Remove any remaining HTML tags that aren't essential
        $allowed_tags = '<p><br><span><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        $html = strip_tags($html, $allowed_tags);
        
        // Clean up any malformed HTML
        $html = trim($html);
        
        return $html;
    }
}

if (!function_exists('get_user_tasks_by_priority')) {
    /**
     * Get all tasks assigned to a user, ordered by priority
     * 
     * @param int $user_id User ID
     * @return array Tasks ordered by priority
     */
    function get_user_tasks_by_priority($user_id) {
        $CI =& get_instance();
        
        $priority_order = "CASE t.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
            ELSE 3 END";
        
        $CI->db->select('t.*, p.name as project_name');
        $CI->db->from('tasks t');
        $CI->db->join('projects p', 'p.id = t.project_id', 'left');
        $CI->db->where('t.assigned_to', (int)$user_id);
        $CI->db->where('t.status !=', 'completed');
        $CI->db->order_by($priority_order, '', false);
        $CI->db->order_by('t.due_date', 'ASC');
        
        return $CI->db->get()->result();
    }
}
if (!function_exists('send_dynamic_email')) {
    function send_dynamic_email($to, $subject, $module, $event_type, $placeholders = []) {
        $CI =& get_instance();
        configure_email_from_settings();

        // Fetch settings
        $CI->db->where('module', $module);
        $CI->db->where('event_type', $event_type);
        $setting = $CI->db->get('email_settings')->row();

        if (!$setting || !$setting->is_enabled) {
            return false;
        }

        $body = $setting->email_template;
        if (!$body) {
            // Fallback content if no template is set
            $body = "Notification for $module - $event_type\n\n";
            foreach ($placeholders as $k => $v) {
                $body .= ucfirst(str_replace('_', ' ', $k)) . ": $v\n";
            }
        } else {
            foreach ($placeholders as $key => $val) {
                // Ensure we handle strings well
                if (is_array($val) || is_object($val)) continue; 
                $body = str_replace("{" . $key . "}", $val, $body);
            }
        }

        // Add auto footer
        $body .= "\n\n(This is an automated message. Please do not reply directly.)";

        $from = get_system_from_email();
        $from_name = function_exists('get_company_name') ? get_company_name() : 'Office Management System';
        
        $CI->email->from($from ?: 'no-reply@company.com', $from_name);
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message(nl2br($body));
        
        return $CI->email->send();
    }
}
