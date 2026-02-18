<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Performance_model extends CI_Model {
    private $table = 'performance_appraisals';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        static $done = false;
        if ($done) { return; }
        $done = true;
        if (!$this->db->table_exists($this->table)){
            $sql = "CREATE TABLE `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `employee_id` int(11) NOT NULL,
                `manager_id` int(11) NOT NULL,
                `period` varchar(50) NOT NULL COMMENT 'e.g. Q1 2024',
                `kpi_score` decimal(5,2) DEFAULT 0,
                `rating` int(11) DEFAULT NULL COMMENT '1-5 Stars',
                `comments` text,
                `status` enum('draft','submitted','approved') DEFAULT 'draft',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_emp` (`employee_id`),
                KEY `idx_mgr` (`manager_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    public function get_appraisals($employee_id = null){
        $this->db->select('p.*, e.first_name, e.last_name, u.name as manager_name');
        $this->db->from($this->table.' p');
        $this->db->join('employees e', 'e.id = p.employee_id');
        $this->db->join('users u', 'u.id = p.manager_id', 'left');
        if ($employee_id) { $this->db->where('p.employee_id', (int)$employee_id); }
        return $this->db->order_by('p.created_at', 'DESC')->get()->result();
    }

    public function get_appraisal($id){
        $this->db->select('p.*, e.first_name, e.last_name, u.name as manager_name');
        $this->db->from($this->table.' p');
        $this->db->join('employees e', 'e.id = p.employee_id', 'left');
        $this->db->join('users u', 'u.id = p.manager_id', 'left');
        $this->db->where('p.id', (int)$id);
        return $this->db->get()->row();
    }

    public function save($data, $id = null){
        if ($id){
            $this->db->where('id', (int)$id)->update($this->table, $data);
            return $id;
        }
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data){
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows();
    }

    public function delete($id){
        $this->db->where('id', (int)$id)->delete($this->table);
        return $this->db->affected_rows();
    }
}
