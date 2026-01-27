<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifications Controller
 * 
 * Handles in-app notifications, push notifications, and notification center
 */
class Notifications extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form']);
        $this->load->library(['session']);
        
        // Check if user is logged in
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
        
        $this->ensure_schema();
    }
    
    /**
     * Ensure notifications table exists
     */
    private function ensure_schema()
    {
        if (!$this->db->table_exists('notifications')) {
            $sql = "CREATE TABLE `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `type` varchar(50) DEFAULT 'info' COMMENT 'info, success, warning, error',
                `module` varchar(100) DEFAULT NULL COMMENT 'tasks, projects, leaves, etc',
                `related_id` int(11) DEFAULT NULL COMMENT 'ID of related entity',
                `action_url` varchar(500) DEFAULT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `read_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_is_read` (`is_read`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        
        // Push notification subscriptions table
        if (!$this->db->table_exists('push_subscriptions')) {
            $sql = "CREATE TABLE `push_subscriptions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `endpoint` text NOT NULL,
                `p256dh_key` varchar(255) NOT NULL,
                `auth_token` varchar(255) NOT NULL,
                `user_agent` varchar(500) DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_user_endpoint` (`user_id`, `endpoint`(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }
    
    /**
     * GET /notifications
     * Notification center - view all notifications
     */
    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        // Get filter
        $filter = $this->input->get('filter') ?: 'all'; // all, unread, read
        
        // Build query
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        $this->db->where('is_deleted', 0);
        
        if ($filter === 'unread') {
            $this->db->where('is_read', 0);
        } elseif ($filter === 'read') {
            $this->db->where('is_read', 1);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(100); // Limit to last 100 notifications
        
        $notifications = $this->db->get()->result();
        
        // Get unread count
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        $this->db->where('is_deleted', 0);
        $unread_count = $this->db->count_all_results('notifications');
        
        $data = [
            'notifications' => $notifications,
            'unread_count' => $unread_count,
            'filter' => $filter
        ];
        
        $this->load->view('notifications/index', $data);
    }
    
    /**
     * GET /notifications/count (AJAX)
     * Get unread notification count
     */
    public function count()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        $this->db->where('is_deleted', 0);
        $count = $this->db->count_all_results('notifications');
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
    }
    
    /**
     * GET /notifications/recent (AJAX)
     * Get recent notifications for dropdown
     */
    public function recent()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        $this->db->where('is_deleted', 0);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(10);
        
        $notifications = $this->db->get()->result();
        
        header('Content-Type: application/json');
        echo json_encode(['notifications' => $notifications]);
    }
    
    /**
     * POST /notifications/{id}/mark-read
     * Mark notification as read
     */
    public function mark_read($id)
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->where('id', (int)$id);
        $this->db->where('user_id', $user_id);
        $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            redirect('notifications');
        }
    }
    
    /**
     * POST /notifications/mark-all-read
     * Mark all notifications as read
     */
    public function mark_all_read()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            $this->session->set_flashdata('success', 'All notifications marked as read.');
            redirect('notifications');
        }
    }
    
    /**
     * POST /notifications/{id}/delete
     * Delete notification
     */
    public function delete($id)
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->where('id', (int)$id);
        $this->db->where('user_id', $user_id);
        $this->db->update('notifications', ['is_deleted' => 1]);
        
        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            $this->session->set_flashdata('success', 'Notification deleted.');
            redirect('notifications');
        }
    }
    
    /**
     * POST /notifications/subscribe-push
     * Subscribe to push notifications
     */
    public function subscribe_push()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $subscription = json_decode($this->input->raw_input_stream, true);
        
        if (!$subscription || !isset($subscription['endpoint'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid subscription']);
            return;
        }
        
        // Store subscription
        $data = [
            'user_id' => $user_id,
            'endpoint' => $subscription['endpoint'],
            'p256dh_key' => isset($subscription['keys']['p256dh']) ? $subscription['keys']['p256dh'] : '',
            'auth_token' => isset($subscription['keys']['auth']) ? $subscription['keys']['auth'] : '',
            'user_agent' => $this->input->user_agent()
        ];
        
        // Use replace to handle duplicates
        $this->db->replace('push_subscriptions', $data);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    /**
     * POST /notifications/unsubscribe-push
     * Unsubscribe from push notifications
     */
    public function unsubscribe_push()
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $subscription = json_decode($this->input->raw_input_stream, true);
        
        if (!$subscription || !isset($subscription['endpoint'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid subscription']);
            return;
        }
        
        $this->db->where('user_id', $user_id);
        $this->db->where('endpoint', $subscription['endpoint']);
        $this->db->delete('push_subscriptions');
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    /**
     * Helper function to create notification
     * Can be called from other controllers
     * 
     * @param int $user_id User ID to notify
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Type: info, success, warning, error
     * @param string $module Module name
     * @param int $related_id Related entity ID
     * @param string $action_url Action URL
     * @return int Notification ID
     */
    public static function create($user_id, $title, $message, $type = 'info', $module = null, $related_id = null, $action_url = null)
    {
        $CI =& get_instance();
        
        $data = [
            'user_id' => (int)$user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'module' => $module,
            'related_id' => $related_id,
            'action_url' => $action_url
        ];
        
        $CI->db->insert('notifications', $data);
        return $CI->db->insert_id();
    }
}
