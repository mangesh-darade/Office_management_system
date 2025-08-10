<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    private $table = 'users';

    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function get_by_email($email){
        return $this->db->get_where($this->table, ['email' => $email])->row();
    }

    // Backwards compat
    public function get($id){
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }

    // Preferred name in controllers
    public function find($id){ return $this->get($id); }

    public function create($data){
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    // Preferred name in controllers
    public function insert($data){ return $this->create($data) > 0; }

    public function count_all(){
        return $this->db->count_all($this->table);
    }

    /**
     * List users with optional simple search by name/email. Limit default 250.
     */
    public function list_users($q = '', $limit = 250){
        $this->db->from($this->table);
        if ($q !== ''){
            $this->db->group_start();
            $this->db->like('name', $q);
            $this->db->or_like('email', $q);
            $this->db->group_end();
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    public function update($id, $data){
        if (isset($data['id'])) unset($data['id']);
        $this->db->where('id', (int)$id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id){
        $this->db->where('id', (int)$id);
        return $this->db->delete($this->table);
    }
}
