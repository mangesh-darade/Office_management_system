<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Helper
 * 
 * Helper functions for creating and managing notifications
 */

if (!function_exists('create_notification')) {
    /**
     * Create a notification for a user
     * 
     * @param int $user_id User ID to notify
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Type: info, success, warning, error
     * @param string $module Module name (tasks, projects, leaves, etc.)
     * @param int $related_id Related entity ID
     * @param string $action_url Action URL
     * @return int|bool Notification ID or false on failure
     */
    function create_notification($user_id, $title, $message, $type = 'info', $module = null, $related_id = null, $action_url = null)
    {
        $CI =& get_instance();
        
        // Ensure notifications table exists
        if (!$CI->db->table_exists('notifications')) {
            return false;
        }
        
        // Map types to supported ENUM values
        $valid_types = ['task_assigned', 'leave_request', 'leave_status', 'deadline_reminder', 'system'];
        $db_type = in_array($type, $valid_types) ? $type : 'system';
        
        // Pack additional data into payload
        $payload = [
            'original_type' => $type,
            'module' => $module,
            'related_id' => $related_id,
            'action_url' => $action_url
        ];

        $data = [
            'user_id' => (int)$user_id,
            'title' => $title,
            'body' => $message, // Changed from message to body
            'type' => $db_type,
            'payload' => json_encode($payload),
            'channel' => 'in_app',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $CI->db->insert('notifications', $data);
        return $CI->db->insert_id();
    }
}

if (!function_exists('notify_task_assigned')) {
    /**
     * Notify user when task is assigned
     * 
     * @param int $user_id Assignee user ID
     * @param int $task_id Task ID
     * @param string $task_title Task title
     * @param int $assigned_by_id User ID who assigned the task
     * @return int|bool Notification ID or false
     */
    function notify_task_assigned($user_id, $task_id, $task_title, $assigned_by_id)
    {
        $CI =& get_instance();
        
        // Get assigner name
        $assigner = $CI->db->get_where('users', ['id' => $assigned_by_id])->row();
        $assigner_name = $assigner ? $assigner->username : 'Someone';
        
        return create_notification(
            $user_id,
            'New Task Assigned',
            "$assigner_name assigned you a task: $task_title",
            'info',
            'tasks',
            $task_id,
            site_url("tasks/$task_id")
        );
    }
}

if (!function_exists('notify_leave_status')) {
    /**
     * Notify user about leave request status change
     * 
     * @param int $user_id User ID to notify
     * @param int $leave_id Leave request ID
     * @param string $status New status (approved, rejected, pending)
     * @return int|bool Notification ID or false
     */
    function notify_leave_status($user_id, $leave_id, $status)
    {
        $status_messages = [
            'approved' => ['title' => 'Leave Approved', 'type' => 'success', 'message' => 'Your leave request has been approved.'],
            'rejected' => ['title' => 'Leave Rejected', 'type' => 'error', 'message' => 'Your leave request has been rejected.'],
            'pending' => ['title' => 'Leave Pending', 'type' => 'warning', 'message' => 'Your leave request is pending approval.']
        ];
        
        // PHP 5.6 compatible - use isset instead of ??
        $config = isset($status_messages[$status]) ? $status_messages[$status] : $status_messages['pending'];
        
        return create_notification(
            $user_id,
            $config['title'],
            $config['message'],
            $config['type'],
            'leaves',
            $leave_id,
            site_url("leave/my")
        );
    }
}

if (!function_exists('notify_project_update')) {
    /**
     * Notify project members about project update
     * 
     * @param int $project_id Project ID
     * @param string $update_message Update message
     * @param int $exclude_user_id User ID to exclude from notification (usually the updater)
     * @return array Array of notification IDs
     */
    function notify_project_update($project_id, $update_message, $exclude_user_id = null)
    {
        $CI =& get_instance();
        
        // Get project members
        $CI->db->select('user_id');
        $CI->db->from('project_members');
        $CI->db->where('project_id', $project_id);
        if ($exclude_user_id) {
            $CI->db->where('user_id !=', $exclude_user_id);
        }
        $members = $CI->db->get()->result();
        
        $notification_ids = [];
        foreach ($members as $member) {
            $id = create_notification(
                $member->user_id,
                'Project Updated',
                $update_message,
                'info',
                'projects',
                $project_id,
                site_url("projects/$project_id")
            );
            if ($id) {
                $notification_ids[] = $id;
            }
        }
        
        return $notification_ids;
    }
}

if (!function_exists('get_unread_notification_count')) {
    /**
     * Get unread notification count for current user
     * 
     * @param int $user_id User ID (optional, defaults to current user)
     * @return int Unread count
     */
    function get_unread_notification_count($user_id = null)
    {
        $CI =& get_instance();
        
        if (!$user_id) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        
        if (!$user_id || !$CI->db->table_exists('notifications')) {
            return 0;
        }
        
        $CI->db->where('user_id', $user_id);
        $CI->db->where('is_read', 0);
        $CI->db->where('is_deleted', 0);
        return $CI->db->count_all_results('notifications');
    }
}

if (!function_exists('notify_announcement')) {
    /**
     * Notify users about new announcement
     * 
     * @param int $announcement_id Announcement ID
     * @param string $title Announcement title
     * @param array $target_role_ids Array of role IDs to notify
     * @return array Array of notification IDs
     */
    function notify_announcement($announcement_id, $title, $target_role_ids = [])
    {
        $CI =& get_instance();
        
        // Get users with target roles
        if (empty($target_role_ids)) {
            // Notify all users
            $CI->db->select('id');
            $users = $CI->db->get('users')->result();
        } else {
            $CI->db->select('id');
            $CI->db->where_in('role_id', $target_role_ids);
            $users = $CI->db->get('users')->result();
        }
        
        $notification_ids = [];
        foreach ($users as $user) {
            $id = create_notification(
                $user->id,
                'New Announcement',
                $title,
                'info',
                'announcements',
                $announcement_id,
                site_url("announcements/$announcement_id")
            );
            if ($id) {
                $notification_ids[] = $id;
            }
        }
        
        return $notification_ids;
    }
}

if (!function_exists('get_notification_modules_structure')) {
    /**
     * Get notification modules structure for settings page
     * 
     * @return array Module structure with icons and actions
     */
    function get_notification_modules_structure()
    {
        return [
            'tasks' => [
                'label' => 'Tasks',
                'icon' => 'bi-list-check',
                'actions' => ['create', 'update', 'delete', 'assign', 'complete']
            ],
            'projects' => [
                'label' => 'Projects',
                'icon' => 'bi-kanban',
                'actions' => ['create', 'update', 'delete', 'complete']
            ],
            'leaves' => [
                'label' => 'Leave Requests',
                'icon' => 'bi-calendar-x',
                'actions' => ['create', 'approve', 'reject', 'cancel']
            ],
            'attendance' => [
                'label' => 'Attendance',
                'icon' => 'bi-clock-history',
                'actions' => ['checkin', 'checkout', 'late', 'absent']
            ],
            'users' => [
                'label' => 'Users',
                'icon' => 'bi-people',
                'actions' => ['create', 'update', 'delete', 'activate', 'deactivate']
            ],
            'announcements' => [
                'label' => 'Announcements',
                'icon' => 'bi-megaphone',
                'actions' => ['create', 'update', 'delete', 'publish']
            ]
        ];
    }
}

if (!function_exists('get_all_default_notification_messages')) {
    /**
     * Get all default notification messages
     * 
     * @return array Default messages for all modules and actions
     */
    function get_all_default_notification_messages()
    {
        return [
            // Tasks
            'tasks_create_success' => 'Task created successfully',
            'tasks_create_error' => 'Failed to create task',
            'tasks_update_success' => 'Task updated successfully',
            'tasks_update_error' => 'Failed to update task',
            'tasks_delete_success' => 'Task deleted successfully',
            'tasks_delete_error' => 'Failed to delete task',
            'tasks_assign_success' => 'Task assigned successfully',
            'tasks_assign_error' => 'Failed to assign task',
            'tasks_complete_success' => 'Task marked as complete',
            'tasks_complete_error' => 'Failed to complete task',
            
            // Projects
            'projects_create_success' => 'Project created successfully',
            'projects_create_error' => 'Failed to create project',
            'projects_update_success' => 'Project updated successfully',
            'projects_update_error' => 'Failed to update project',
            'projects_delete_success' => 'Project deleted successfully',
            'projects_delete_error' => 'Failed to delete project',
            'projects_complete_success' => 'Project marked as complete',
            'projects_complete_error' => 'Failed to complete project',
            
            // Leave Requests
            'leaves_create_success' => 'Leave request submitted successfully',
            'leaves_create_error' => 'Failed to submit leave request',
            'leaves_approve_success' => 'Leave request approved',
            'leaves_approve_error' => 'Failed to approve leave request',
            'leaves_reject_success' => 'Leave request rejected',
            'leaves_reject_error' => 'Failed to reject leave request',
            'leaves_cancel_success' => 'Leave request cancelled',
            'leaves_cancel_error' => 'Failed to cancel leave request',
            
            // Attendance
            'attendance_checkin_success' => 'Checked in successfully',
            'attendance_checkin_error' => 'Failed to check in',
            'attendance_checkout_success' => 'Checked out successfully',
            'attendance_checkout_error' => 'Failed to check out',
            'attendance_late_success' => 'Late attendance marked',
            'attendance_late_error' => 'Failed to mark late attendance',
            'attendance_absent_success' => 'Absence marked',
            'attendance_absent_error' => 'Failed to mark absence',
            
            // Users
            'users_create_success' => 'User created successfully',
            'users_create_error' => 'Failed to create user',
            'users_update_success' => 'User updated successfully',
            'users_update_error' => 'Failed to update user',
            'users_delete_success' => 'User deleted successfully',
            'users_delete_error' => 'Failed to delete user',
            'users_activate_success' => 'User activated successfully',
            'users_activate_error' => 'Failed to activate user',
            'users_deactivate_success' => 'User deactivated successfully',
            'users_deactivate_error' => 'Failed to deactivate user',
            
            // Announcements
            'announcements_create_success' => 'Announcement created successfully',
            'announcements_create_error' => 'Failed to create announcement',
            'announcements_update_success' => 'Announcement updated successfully',
            'announcements_update_error' => 'Failed to update announcement',
            'announcements_delete_success' => 'Announcement deleted successfully',
            'announcements_delete_error' => 'Failed to delete announcement',
            'announcements_publish_success' => 'Announcement published successfully',
            'announcements_publish_error' => 'Failed to publish announcement'
        ];
    }
}

if (!function_exists('get_notification_message')) {
    /**
     * Get notification message for a specific module and action
     * 
     * @param string $module Module name (tasks, projects, leaves, etc.)
     * @param string $action Action name (create, update, delete, etc.)
     * @param string $type Type (success, error)
     * @return string Notification message
     */
    function get_notification_message($module, $action, $type = 'success')
    {
        $CI =& get_instance();
        
        // Normalize module name (e.g., leave_requests -> leaves)
        if ($module == 'leave_requests') {
            $module = 'leaves';
        }
        
        $key = "{$module}_{$action}_{$type}";
        
        // Try to get from settings if implemented, otherwise use default
        $messages = get_all_default_notification_messages();
        
        return isset($messages[$key]) ? $messages[$key] : ucfirst(str_replace('_', ' ', $key));
    }
}
