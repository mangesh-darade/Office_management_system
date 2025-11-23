<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_model extends CI_Model
{
    private $table = 'employees';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        if (!$this->db->table_exists($this->table)) {
            return;
        }
        $fields = $this->db->list_fields($this->table);
        $addCol = function($name, $sqlPart) use ($fields){
            if (!in_array($name, $fields, true)){
                $this->db->query("ALTER TABLE `{$this->table}` ADD ".$sqlPart);
            }
        };
        $addCol('location', "`location` varchar(120) DEFAULT NULL AFTER `department`");
        $addCol('bank_name', "`bank_name` varchar(190) DEFAULT NULL AFTER `phone`");
        $addCol('bank_ac_no', "`bank_ac_no` varchar(50) DEFAULT NULL AFTER `bank_name`");
        $addCol('pan_no', "`pan_no` varchar(20) DEFAULT NULL AFTER `bank_ac_no`");
    }

    public function all($limit = 50, $offset = 0, $search = null)
    {
        $this->db->select('e.*, u.email, u.name as user_name, u.role_id');
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        if ($search) {
            $this->db->group_start()
                ->like('e.emp_code', $search)
                ->or_like('e.first_name', $search)
                ->or_like('e.last_name', $search)
                ->or_like('u.email', $search)
            ->group_end();
        }
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_all($search = null)
    {
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        if ($search) {
            $this->db->group_start()
                ->like('e.emp_code', $search)
                ->or_like('e.first_name', $search)
                ->or_like('e.last_name', $search)
                ->or_like('u.email', $search)
            ->group_end();
        }
        return $this->db->count_all_results();
    }

    public function find($id)
    {
        $this->db->select('e.*, u.email, u.name as user_name, u.role_id');
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        $this->db->where('e.id', $id);
        return $this->db->get()->row();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }
}
