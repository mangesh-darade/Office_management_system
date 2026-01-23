<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Holiday_model extends CI_Model {
    protected $table = 'holidays';

    public function all(){
        if (!$this->db->table_exists($this->table)) {
            return [];
        }
        return $this->db->order_by('holiday_date', 'ASC')->get($this->table)->result();
    }

    public function find($id){
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        return $this->db->where('id', (int)$id)->get($this->table)->row();
    }

    public function find_by_date($date){
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        return $this->db->where('holiday_date', $date)->limit(1)->get($this->table)->row();
    }

    public function create($data){
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data){
        return $this->db->where('id', (int)$id)->update($this->table, $data);
    }
}

