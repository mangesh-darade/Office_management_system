<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Schema_columns_trait.php';

class Api_integration_model extends CI_Model {
    use Schema_columns_trait;
    private $table = 'api_integrations';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('hierarchy_filter');
        $this->ensure_schema();
    }
    
    public function ensure_schema() {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $this->load->helper('schema_columns');
        schema_safe_create_table($this->db, $this->table, "CREATE TABLE `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `service_type` varchar(50) NOT NULL COMMENT 'sendgrid, whatsapp, smtp',
                `service_name` varchar(100) NOT NULL,
                `account_id` varchar(255) DEFAULT NULL COMMENT 'Account SID, API Key, etc.',
                `auth_token` text DEFAULT NULL COMMENT 'Auth Token, API Secret, Password',
                `from_email` varchar(255) DEFAULT NULL COMMENT 'For email services',
                `from_name` varchar(255) DEFAULT NULL COMMENT 'For email services',
                `from_number` varchar(50) DEFAULT NULL COMMENT 'For WhatsApp/SMS',
                `content_sid` varchar(255) DEFAULT NULL COMMENT 'Twilio Content Template SID',
                `is_active` tinyint(1) DEFAULT 1,
                `is_default` tinyint(1) DEFAULT 0 COMMENT 'Default service for this type',
                `notes` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_service_type` (`service_type`),
                KEY `idx_is_active` (`is_active`),
                KEY `idx_is_default` (`is_default`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        schema_safe_add_column($this->db, $this->table, 'created_by', "ALTER TABLE `{$this->table}` ADD `created_by` int(11) DEFAULT NULL");
        if ($this->db->table_exists($this->table) && schema_table_has_column($this->db, $this->table, 'created_by')) {
            $idx = $this->db->query("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'idx_created_by'")->row();
            if (!$idx) {
                $this->db->query("ALTER TABLE `{$this->table}` ADD KEY `idx_created_by` (`created_by`)");
            }
        }
    }
    
    public function get_all($service_type = null) {
        if ($service_type) {
            $this->db->where('service_type', $service_type);
        }
        if ($this->has_column('created_by')) {
            apply_role_hierarchy_filter($this->db, 'created_by');
        }
        $this->db->order_by('service_type', 'ASC');
        $this->db->order_by('is_default', 'DESC');
        $this->db->order_by('service_name', 'ASC');
        return $this->db->get($this->table)->result();
    }
    
    public function get_by_id($id) {
        $this->db->where('id', (int)$id);
        if ($this->has_column('created_by')) {
            apply_role_hierarchy_filter($this->db, 'created_by');
        }
        return $this->db->get($this->table)->row();
    }
    
    public function get_default($service_type) {
        $result = $this->db->where('service_type', $service_type)
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->get($this->table)
            ->row();
        
        // If no default, get first active
        if (!$result) {
            $result = $this->db->where('service_type', $service_type)
                ->where('is_active', 1)
                ->limit(1)
                ->get($this->table)
                ->row();
        }
        
        return $result;
    }
    
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($this->has_column('created_by') && !isset($data['created_by'])) {
            $uid = (int)$this->session->userdata('user_id');
            $data['created_by'] = $uid > 0 ? $uid : null;
        }
        
        // If this is set as default, unset other defaults for this service type
        if (isset($data['is_default']) && $data['is_default'] == 1) {
            $this->db->where('service_type', $data['service_type'])
                ->update($this->table, ['is_default' => 0]);
        }
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // If this is set as default, unset other defaults for this service type
        if (isset($data['is_default']) && $data['is_default'] == 1) {
            $existing = $this->get_by_id($id);
            if ($existing) {
                $this->db->where('service_type', $existing->service_type)
                    ->where('id !=', (int)$id)
                    ->update($this->table, ['is_default' => 0]);
            }
        }
        
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return true;
    }
    
    public function delete($id) {
        return $this->db->where('id', (int)$id)->delete($this->table);
    }
}

