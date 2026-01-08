<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_logger {
    protected $CI;
    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library(['session']);
    }

    /**
     * Log activity to activity_log table.
     * @param string $module
     * @param string $action
     * @param int $record_id
     * @param string $description
     * @param array $changes Optional array of changes (before/after values)
     */
    public function log($module, $action, $record_id = null, $description = '', $changes = null){
        try {
            $user_id = (int)$this->CI->session->userdata('user_id');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
            
            if (!$this->CI->db->table_exists('activity_log')) { 
                log_message('error', 'Activity_log table does not exist');
                return; 
            }
            
            // Build changes JSON - store description and any change details
            $changes_data = null;
            if ($description || $changes) {
                $changes_array = array();
                if ($description) {
                    $changes_array['description'] = (string)$description;
                }
                if (is_array($changes) && !empty($changes)) {
                    $changes_array['changes'] = $changes;
                }
                // For PHP 5.6 compatibility, use json_encode (JSON column type is supported in MySQL 5.7+)
                // If using older MySQL, the column should be TEXT/LONGTEXT
                $changes_data = json_encode($changes_array);
            }
            
            $this->CI->db->insert('activity_log', [
                'actor_id' => $user_id ? $user_id : null,
                'entity_type' => (string)$module,
                'entity_id' => $record_id ? (int)$record_id : null,
                'action' => (string)$action,
                'changes' => $changes_data,
                'ip_address' => $ip,
                'user_agent' => $agent,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            log_message('error', 'Activity logger error: ' . $e->getMessage());
            // Don't throw exception - logging failures shouldn't break the application
        }
    }
}
