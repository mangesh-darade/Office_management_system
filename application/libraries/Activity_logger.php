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
     */
    public function log($module, $action, $record_id = null, $description = ''){
        $user_id = (int)$this->CI->session->userdata('user_id');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        if (!$this->CI->db->table_exists('activity_log')) { return; }
        $this->CI->db->insert('activity_log', [
            'actor_id' => $user_id ?: null,
            'entity_type' => (string)$module,
            'entity_id' => $record_id ?: null,
            'action' => (string)$action,
            'changes' => null,
            'ip_address' => $ip,
            'user_agent' => $agent,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
