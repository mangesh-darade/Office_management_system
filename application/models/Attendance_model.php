<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_model extends CI_Model {
    private $table = 'attendance';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        if ($this->db->table_exists($this->table)){
            $fields = $this->db->list_fields($this->table);
            if (!in_array('location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `location_name` VARCHAR(255) NULL");
            }
            // Add check-in location fields
            if (!in_array('checkin_lat', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_lat` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkin_lng', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_lng` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkin_location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_location_name` VARCHAR(255) NULL");
            }
            // Add check-out location fields
            if (!in_array('checkout_lat', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_lat` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkout_lng', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_lng` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkout_location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_location_name` VARCHAR(255) NULL");
            }
            if (!in_array('shift_id', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `shift_id` INT(11) NULL DEFAULT NULL");
            }
            if (!in_array('status', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `status` ENUM('present', 'absent', 'late', 'early_leave', 'half_day') DEFAULT 'present'");
            }
        }
    }
}
