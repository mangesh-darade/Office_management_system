<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designation_model extends CI_Model {
    private $table = 'designations';
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function all(){
        $this->db->from($this->table);
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('status !=', 'inactive');
        }
        $this->db->order_by('level','ASC');
        $this->db->order_by('designation_name','ASC');
        return $this->db->get()->result();
    }

    public function find($id){ return $this->db->get_where($this->table, ['id'=>(int)$id])->row(); }

    public function create($data){
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update($id, $data){
        $this->db->where('id',(int)$id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function soft_delete($id){
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('id',(int)$id)->update($this->table, ['status'=>'inactive']);
        } else {
            $this->db->where('id',(int)$id)->delete($this->table);
        }
        return true;
    }
}
