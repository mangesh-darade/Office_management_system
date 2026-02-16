<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_model extends CI_Model {
    
    private $table = 'shifts';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema() {
        if (!$this->db->table_exists($this->table)) {
            // Check if table exists to avoid race conditions
            if (!$this->db->table_exists($this->table)) {
                $sql = "CREATE TABLE `shifts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `start_time` time NOT NULL,
                    `end_time` time NOT NULL,
                    `late_grace_period` int(11) DEFAULT 15 COMMENT 'Minutes allowed after start time',
                    `early_exit_grace_period` int(11) DEFAULT 0 COMMENT 'Minutes allowed before end time',
                    `is_active` tinyint(1) DEFAULT 1,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                $this->db->query($sql);
                
                // Insert a default shift
                $this->db->insert($this->table, [
                    'name' => 'General Shift',
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'late_grace_period' => 15,
                    'early_exit_grace_period' => 0
                ]);
            }
        }
    }

    public function get_all($active_only = false) {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->get($this->table)->result();
    }

    public function get($id) {
        return $this->db->where('id', (int)$id)->get($this->table)->row();
    }

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id) {
        // Soft delete or check dependencies? For now, hard delete but check usage
        // We'll trust the controller to check dependencies
        return $this->db->where('id', (int)$id)->delete($this->table);
    }
}
