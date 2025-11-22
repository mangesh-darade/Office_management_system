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
        }
    }
}
