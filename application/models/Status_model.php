<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Status_model extends CI_Model {
    
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }
    
    public function ensure_schema(){
        if (!$this->db->table_exists('statuses')){
            $sql = "CREATE TABLE `statuses` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `code` varchar(50) NOT NULL,
                `type` varchar(50) NOT NULL COMMENT 'requirements, projects, tasks',
                `color` varchar(20) DEFAULT '#6c757d',
                `icon` varchar(50) DEFAULT NULL,
                `display_order` int(11) DEFAULT 0,
                `is_active` tinyint(1) DEFAULT 1,
                `description` text,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_status_code_type` (`code`, `type`),
                KEY `idx_type` (`type`),
                KEY `idx_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
            
            // Insert default statuses
            $this->insert_default_statuses();
        }
    }
    
    private function insert_default_statuses(){
        $defaults = [
            // Requirements statuses
            ['name' => 'Received', 'code' => 'received', 'type' => 'requirements', 'color' => '#17a2b8', 'icon' => 'inbox', 'display_order' => 1],
            ['name' => 'Under Review', 'code' => 'under_review', 'type' => 'requirements', 'color' => '#ffc107', 'icon' => 'eye', 'display_order' => 2],
            ['name' => 'Approved', 'code' => 'approved', 'type' => 'requirements', 'color' => '#28a745', 'icon' => 'check-circle', 'display_order' => 3],
            ['name' => 'In Progress', 'code' => 'in_progress', 'type' => 'requirements', 'color' => '#007bff', 'icon' => 'play-circle', 'display_order' => 4],
            ['name' => 'Completed', 'code' => 'completed', 'type' => 'requirements', 'color' => '#28a745', 'icon' => 'check', 'display_order' => 5],
            ['name' => 'On Hold', 'code' => 'on_hold', 'type' => 'requirements', 'color' => '#ffc107', 'icon' => 'pause-circle', 'display_order' => 6],
            ['name' => 'Rejected', 'code' => 'rejected', 'type' => 'requirements', 'color' => '#dc3545', 'icon' => 'x-circle', 'display_order' => 7],
            ['name' => 'Cancelled', 'code' => 'cancelled', 'type' => 'requirements', 'color' => '#6c757d', 'icon' => 'ban', 'display_order' => 8],
            
            // Projects statuses
            ['name' => 'Planned', 'code' => 'planned', 'type' => 'projects', 'color' => '#6c757d', 'icon' => 'calendar', 'display_order' => 1],
            ['name' => 'Active', 'code' => 'active', 'type' => 'projects', 'color' => '#007bff', 'icon' => 'play-circle', 'display_order' => 2],
            ['name' => 'On Hold', 'code' => 'on_hold', 'type' => 'projects', 'color' => '#ffc107', 'icon' => 'pause-circle', 'display_order' => 3],
            ['name' => 'Completed', 'code' => 'completed', 'type' => 'projects', 'color' => '#28a745', 'icon' => 'check', 'display_order' => 4],
            ['name' => 'Cancelled', 'code' => 'cancelled', 'type' => 'projects', 'color' => '#dc3545', 'icon' => 'ban', 'display_order' => 5],
            
            // Tasks statuses
            ['name' => 'Pending', 'code' => 'pending', 'type' => 'tasks', 'color' => '#6c757d', 'icon' => 'clock', 'display_order' => 1],
            ['name' => 'In Progress', 'code' => 'in_progress', 'type' => 'tasks', 'color' => '#007bff', 'icon' => 'play-circle', 'display_order' => 2],
            ['name' => 'Completed', 'code' => 'completed', 'type' => 'tasks', 'color' => '#28a745', 'icon' => 'check', 'display_order' => 3],
            ['name' => 'Blocked', 'code' => 'blocked', 'type' => 'tasks', 'color' => '#dc3545', 'icon' => 'x-circle', 'display_order' => 4],
        ];
        
        foreach ($defaults as $status) {
            $status['created_at'] = date('Y-m-d H:i:s');
            $status['updated_at'] = date('Y-m-d H:i:s');
            $this->db->insert('statuses', $status);
        }
    }
    
    public function get_all($type = null, $active_only = true){
        $this->db->select('*');
        $this->db->from('statuses');
        if ($type) {
            $this->db->where('type', $type);
        }
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('type', 'ASC');
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }
    
    public function get_by_type($type, $active_only = true){
        $this->db->select('*');
        $this->db->from('statuses');
        $this->db->where('type', $type);
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }
    
    public function get_by_id($id){
        return $this->db->where('id', (int)$id)->get('statuses')->row();
    }
    
    public function get_by_code($code, $type){
        return $this->db->where('code', $code)->where('type', $type)->get('statuses')->row();
    }
    
    public function create($data){
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('statuses', $data);
        return $this->db->insert_id();
    }
    
    public function update($id, $data){
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$id)->update('statuses', $data);
        return $this->db->affected_rows();
    }
    
    public function delete($id){
        $this->db->where('id', (int)$id)->delete('statuses');
        return $this->db->affected_rows();
    }
    
    public function get_for_dropdown($type){
        $statuses = $this->get_by_type($type, true);
        $options = [];
        foreach ($statuses as $status) {
            $options[$status->code] = $status->name;
        }
        return $options;
    }
}

