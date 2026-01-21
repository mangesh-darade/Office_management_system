<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Message Helper
 * 
 * Provides functions to retrieve and manage notification messages
 * for different modules and actions
 */

/**
 * Get notification message for a specific module, action, and type
 * 
 * @param string $module Module name (e.g., 'employees', 'attendance', 'tasks')
 * @param string $action Action name (e.g., 'create', 'update', 'delete', 'import')
 * @param string $type Message type ('success', 'error', 'info', 'warning')
 * @param array $params Optional parameters for message placeholders (e.g., ['name' => 'John'])
 * @return string Notification message
 */
if (!function_exists('get_notification_message')) {
    function get_notification_message($module, $action, $type = 'success', $params = []) {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        // Build setting key
        $setting_key = "notification_{$module}_{$action}_{$type}";
        
        // Try to get custom message from settings
        $message = $CI->settings->get_setting($setting_key);
        
        // If no custom message, use default
        if (empty($message)) {
            $message = get_default_notification_message($module, $action, $type);
        }
        
        // Replace placeholders with actual values
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }
        
        return $message;
    }
}

/**
 * Get default notification messages
 * 
 * @param string $module Module name
 * @param string $action Action name
 * @param string $type Message type
 * @return string Default message
 */
if (!function_exists('get_default_notification_message')) {
    function get_default_notification_message($module, $action, $type = 'success') {
        $defaults = get_all_default_notification_messages();
        
        $key = "{$module}_{$action}_{$type}";
        
        if (isset($defaults[$key])) {
            return $defaults[$key];
        }
        
        // Fallback generic messages
        if ($type === 'success') {
            return ucfirst($module) . ' ' . $action . ' successfully';
        } elseif ($type === 'error') {
            return 'Failed to ' . $action . ' ' . $module;
        } else {
            return ucfirst($module) . ' ' . $action;
        }
    }
}

/**
 * Get all default notification messages
 * 
 * @return array All default messages
 */
if (!function_exists('get_all_default_notification_messages')) {
    function get_all_default_notification_messages() {
        return [
            // Employees Module
            'employees_create_success' => 'Employee created successfully',
            'employees_create_error' => 'Failed to create employee. Please try again.',
            'employees_update_success' => 'Employee updated successfully',
            'employees_update_error' => 'Failed to update employee. Please try again.',
            'employees_delete_success' => 'Employee deleted successfully',
            'employees_delete_error' => 'Failed to delete employee. Please try again.',
            'employees_import_success' => 'Employees imported successfully',
            'employees_import_error' => 'Failed to import employees. Please check the file format.',
            
            // Attendance Module
            'attendance_create_success' => 'Attendance marked successfully',
            'attendance_create_error' => 'Failed to mark attendance. Please try again.',
            'attendance_update_success' => 'Attendance updated successfully',
            'attendance_update_error' => 'Failed to update attendance. Please try again.',
            'attendance_delete_success' => 'Attendance deleted successfully',
            'attendance_delete_error' => 'Failed to delete attendance. Please try again.',
            'attendance_already_marked' => 'Attendance already marked for today',
            'attendance_location_mismatch' => 'You are not at the required location to mark attendance',
            'attendance_face_verification_failed' => 'Face verification failed. Please try again.',
            
            // Tasks Module
            'tasks_create_success' => 'Task created successfully',
            'tasks_create_error' => 'Failed to create task. Please try again.',
            'tasks_update_success' => 'Task updated successfully',
            'tasks_update_error' => 'Failed to update task. Please try again.',
            'tasks_delete_success' => 'Task deleted successfully',
            'tasks_delete_error' => 'Failed to delete task. Please try again.',
            'tasks_assign_success' => 'Task assigned successfully',
            'tasks_status_update_success' => 'Task status updated successfully',
            
            // Projects Module
            'projects_create_success' => 'Project created successfully',
            'projects_create_error' => 'Failed to create project. Please try again.',
            'projects_update_success' => 'Project updated successfully',
            'projects_update_error' => 'Failed to update project. Please try again.',
            'projects_delete_success' => 'Project deleted successfully',
            'projects_delete_error' => 'Failed to delete project. Please try again.',
            'projects_member_add_success' => 'Member added to project successfully',
            'projects_member_remove_success' => 'Member removed from project successfully',
            
            // Leave Requests Module
            'leave_requests_create_success' => 'Leave request submitted successfully',
            'leave_requests_create_error' => 'Failed to submit leave request. Please try again.',
            'leave_requests_update_success' => 'Leave request updated successfully',
            'leave_requests_update_error' => 'Failed to update leave request. Please try again.',
            'leave_requests_approve_success' => 'Leave request approved successfully',
            'leave_requests_reject_success' => 'Leave request rejected',
            'leave_requests_cancel_success' => 'Leave request cancelled successfully',
            'leave_requests_insufficient_balance' => 'Insufficient leave balance',
            
            // Departments Module
            'departments_create_success' => 'Department created successfully',
            'departments_create_error' => 'Failed to create department. Please try again.',
            'departments_update_success' => 'Department updated successfully',
            'departments_update_error' => 'Failed to update department. Please try again.',
            'departments_delete_success' => 'Department deleted successfully',
            'departments_delete_error' => 'Failed to delete department. Please try again.',
            'departments_restore_success' => 'Department restored successfully',
            
            // Designations Module
            'designations_create_success' => 'Designation created successfully',
            'designations_create_error' => 'Failed to create designation. Please try again.',
            'designations_update_success' => 'Designation updated successfully',
            'designations_update_error' => 'Failed to update designation. Please try again.',
            'designations_delete_success' => 'Designation deleted successfully',
            'designations_delete_error' => 'Failed to delete designation. Please try again.',
            'designations_restore_success' => 'Designation restored successfully',
            
            // Clients Module
            'clients_create_success' => 'Client created successfully',
            'clients_create_error' => 'Failed to create client. Please try again.',
            'clients_update_success' => 'Client updated successfully',
            'clients_update_error' => 'Failed to update client. Please try again.',
            'clients_delete_success' => 'Client deleted successfully',
            'clients_delete_error' => 'Failed to delete client. Please try again.',
            
            // Users Module
            'users_create_success' => 'User created successfully',
            'users_create_error' => 'Failed to create user. Please try again.',
            'users_update_success' => 'User updated successfully',
            'users_update_error' => 'Failed to update user. Please try again.',
            'users_delete_success' => 'User deleted successfully',
            'users_delete_error' => 'Failed to delete user. Please try again.',
            
            // Profile Module
            'profile_update_success' => 'Profile updated successfully',
            'profile_update_error' => 'Failed to update profile. Please try again.',
            'profile_password_change_success' => 'Password changed successfully',
            'profile_password_change_error' => 'Failed to change password. Please check your current password.',
            'profile_avatar_update_success' => 'Profile picture updated successfully',
            'profile_avatar_delete_success' => 'Profile picture removed successfully',
            
            // Settings Module
            'settings_update_success' => 'Settings saved successfully',
            'settings_update_error' => 'Failed to save settings. Please try again.',
            
            // Documents/Files Module
            'documents_upload_success' => 'Document uploaded successfully',
            'documents_upload_error' => 'Failed to upload document. Please try again.',
            'documents_delete_success' => 'Document deleted successfully',
            'documents_delete_error' => 'Failed to delete document. Please try again.',
            'documents_download_error' => 'Failed to download document. File not found.',
            
            // Timesheets Module
            'timesheets_create_success' => 'Timesheet entry created successfully',
            'timesheets_create_error' => 'Failed to create timesheet entry. Please try again.',
            'timesheets_update_success' => 'Timesheet entry updated successfully',
            'timesheets_update_error' => 'Failed to update timesheet entry. Please try again.',
            'timesheets_delete_success' => 'Timesheet entry deleted successfully',
            'timesheets_delete_error' => 'Failed to delete timesheet entry. Please try again.',
            
            // Assets Module
            'assets_create_success' => 'Asset created successfully',
            'assets_create_error' => 'Failed to create asset. Please try again.',
            'assets_update_success' => 'Asset updated successfully',
            'assets_update_error' => 'Failed to update asset. Please try again.',
            'assets_delete_success' => 'Asset deleted successfully',
            'assets_delete_error' => 'Failed to delete asset. Please try again.',
            'assets_assign_success' => 'Asset assigned successfully',
            'assets_return_success' => 'Asset returned successfully',
            
            // Announcements Module
            'announcements_create_success' => 'Announcement created successfully',
            'announcements_create_error' => 'Failed to create announcement. Please try again.',
            'announcements_update_success' => 'Announcement updated successfully',
            'announcements_update_error' => 'Failed to update announcement. Please try again.',
            'announcements_delete_success' => 'Announcement deleted successfully',
            'announcements_delete_error' => 'Failed to delete announcement. Please try again.',
            'announcements_publish_success' => 'Announcement published successfully',
            
            // Chats Module
            'chats_message_sent_success' => 'Message sent successfully',
            'chats_message_sent_error' => 'Failed to send message. Please try again.',
            'chats_delete_success' => 'Message deleted successfully',
            
            // Reports Module
            'reports_generate_success' => 'Report generated successfully',
            'reports_generate_error' => 'Failed to generate report. Please try again.',
            'reports_export_success' => 'Report exported successfully',
            'reports_export_error' => 'Failed to export report. Please try again.',
            
            // Reminders Module
            'reminders_create_success' => 'Reminder created successfully',
            'reminders_create_error' => 'Failed to create reminder. Please try again.',
            'reminders_update_success' => 'Reminder updated successfully',
            'reminders_update_error' => 'Failed to update reminder. Please try again.',
            'reminders_delete_success' => 'Reminder deleted successfully',
            'reminders_delete_error' => 'Failed to delete reminder. Please try again.',
        ];
    }
}

/**
 * Get notification messages for a module
 * 
 * @param string $module Module name
 * @return array Array of messages for the module
 */
if (!function_exists('get_module_notification_messages')) {
    function get_module_notification_messages($module) {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        // Get all notification settings for this module
        $all_settings = $CI->settings->get_all_settings();
        $module_messages = [];
        
        $pattern = "/^notification_{$module}_(.+)_(success|error|info|warning)$/";
        
        foreach ($all_settings as $key => $value) {
            if (preg_match($pattern, $key, $matches)) {
                $action = $matches[1];
                $type = $matches[2];
                
                if (!isset($module_messages[$action])) {
                    $module_messages[$action] = [];
                }
                
                $module_messages[$action][$type] = $value;
            }
        }
        
        return $module_messages;
    }
}

/**
 * Get all modules with their available actions
 * 
 * @return array Module actions structure
 */
if (!function_exists('get_notification_modules_structure')) {
    function get_notification_modules_structure() {
        return [
            'employees' => [
                'label' => 'Employees',
                'actions' => ['create', 'update', 'delete', 'import'],
                'icon' => 'bi-people'
            ],
            'attendance' => [
                'label' => 'Attendance',
                'actions' => ['create', 'update', 'delete', 'already_marked', 'location_mismatch', 'face_verification_failed'],
                'icon' => 'bi-clock-history'
            ],
            'tasks' => [
                'label' => 'Tasks',
                'actions' => ['create', 'update', 'delete', 'assign', 'status_update'],
                'icon' => 'bi-check2-square'
            ],
            'projects' => [
                'label' => 'Projects',
                'actions' => ['create', 'update', 'delete', 'member_add', 'member_remove'],
                'icon' => 'bi-kanban'
            ],
            'leave_requests' => [
                'label' => 'Leave Requests',
                'actions' => ['create', 'update', 'approve', 'reject', 'cancel', 'insufficient_balance'],
                'icon' => 'bi-calendar-x'
            ],
            'departments' => [
                'label' => 'Departments',
                'actions' => ['create', 'update', 'delete', 'restore'],
                'icon' => 'bi-building'
            ],
            'designations' => [
                'label' => 'Designations',
                'actions' => ['create', 'update', 'delete', 'restore'],
                'icon' => 'bi-person-badge'
            ],
            'clients' => [
                'label' => 'Clients',
                'actions' => ['create', 'update', 'delete'],
                'icon' => 'bi-briefcase'
            ],
            'users' => [
                'label' => 'Users',
                'actions' => ['create', 'update', 'delete'],
                'icon' => 'bi-person-lock'
            ],
            'profile' => [
                'label' => 'Profile',
                'actions' => ['update', 'password_change', 'avatar_update', 'avatar_delete'],
                'icon' => 'bi-person-circle'
            ],
            'settings' => [
                'label' => 'Settings',
                'actions' => ['update'],
                'icon' => 'bi-gear'
            ],
            'documents' => [
                'label' => 'Documents',
                'actions' => ['upload', 'delete', 'download_error'],
                'icon' => 'bi-file-earmark'
            ],
            'timesheets' => [
                'label' => 'Timesheets',
                'actions' => ['create', 'update', 'delete'],
                'icon' => 'bi-stopwatch'
            ],
            'assets' => [
                'label' => 'Assets',
                'actions' => ['create', 'update', 'delete', 'assign', 'return'],
                'icon' => 'bi-box-seam'
            ],
            'announcements' => [
                'label' => 'Announcements',
                'actions' => ['create', 'update', 'delete', 'publish'],
                'icon' => 'bi-megaphone'
            ],
            'chats' => [
                'label' => 'Chats',
                'actions' => ['message_sent', 'delete'],
                'icon' => 'bi-chat-dots'
            ],
            'reports' => [
                'label' => 'Reports',
                'actions' => ['generate', 'export'],
                'icon' => 'bi-graph-up'
            ],
            'reminders' => [
                'label' => 'Reminders',
                'actions' => ['create', 'update', 'delete'],
                'icon' => 'bi-bell'
            ],
        ];
    }
}
