<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Security_audit_model extends CI_Model {
    private $table = 'security_audit_log';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }
    
    private function ensure_schema() {
        if (!$this->db->table_exists($this->table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `action` varchar(50) NOT NULL,
                `details` text,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` varchar(500) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_action` (`user_id`, `action`),
                KEY `idx_created` (`created_at`),
                KEY `idx_ip` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        }
    }
    
    /**
     * Log a security event
     * 
     * @param string $action Action name (e.g., 'login_success', 'login_failed', 'settings_changed')
     * @param int|null $user_id User ID (null for anonymous actions)
     * @param string|null $details Additional details
     * @param string|null $ip_address IP address
     * @return bool Success status
     */
    public function log($action, $user_id = null, $details = null, $ip_address = null) {
        $CI =& get_instance();
        
        // Get IP if not provided
        if ($ip_address === null) {
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        }
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
        
        $data = [
            'user_id' => $user_id !== null ? (int)$user_id : null,
            'action' => $action,
            'details' => $details !== null ? (string)$details : null,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Security audit log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean old audit logs based on retention setting
     * 
     * @param int $retention_days Number of days to retain (default: 90)
     * @return int Number of records deleted
     */
    public function clean_old_logs($retention_days = 90) {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        // Get retention setting
        $retention = (int)$CI->settings->get_setting('security_log_retention', $retention_days);
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention} days"));
        
        $this->db->where('created_at <', $cutoff_date);
        $this->db->delete($this->table);
        
        return $this->db->affected_rows();
    }
    
    /**
     * Get audit logs
     * 
     * @param array $filters Filters (user_id, action, date_from, date_to, ip_address)
     * @param int $limit Limit results
     * @param int $offset Offset
     * @return array Audit log records
     */
    public function get_logs($filters = [], $limit = 100, $offset = 0) {
        $this->db->from($this->table);
        
        if (isset($filters['user_id'])) {
            $this->db->where('user_id', (int)$filters['user_id']);
        }
        
        if (isset($filters['action'])) {
            $this->db->where('action', $filters['action']);
        }
        
        if (isset($filters['ip_address'])) {
            $this->db->where('ip_address', $filters['ip_address']);
        }
        
        if (isset($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to']);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result();
    }
}
