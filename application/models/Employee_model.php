<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_model extends CI_Model
{
    private $table = 'employees';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
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
