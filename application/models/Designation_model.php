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

    public function find($id){ 
        $this->db->from($this->table);
        $this->db->where('id', (int)$id);
        // Filter out soft-deleted records
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('status !=', 'inactive');
        }
        return $this->db->get()->row(); 
    }

    public function find_by_code($code){ 
        $this->db->from($this->table);
        $this->db->where('designation_code', $code);
        // Filter out soft-deleted records
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('status !=', 'inactive');
        }
        return $this->db->get()->row(); 
    }

    public function create($data){
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update($id, $data){
        $this->db->where('id',(int)$id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function soft_delete($id){
        if ($this->db->field_exists('status', $this->table) && $this->db->field_exists('deleted_at', $this->table)){
            $this->db->where('id',(int)$id)->update($this->table, [
                'status' => 'inactive',
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
        } elseif ($this->db->field_exists('status', $this->table)){
            $this->db->where('id',(int)$id)->update($this->table, ['status'=>'inactive']);
        } else {
            $this->db->where('id',(int)$id)->delete($this->table);
        }
        return true;
    }
    
    public function restore($id){
        // First check if record exists
        $this->db->from($this->table);
        $this->db->where('id', (int)$id);
        $query = $this->db->get();
        
        if ($query->num_rows() == 0) {
            return false; // Record doesn't exist
        }
        
        $record = $query->row();
        
        // Check if we have the required fields
        if ($this->db->field_exists('status', $this->table) && $this->db->field_exists('deleted_at', $this->table)){
            // Check if there's already an active record with the same code
            $this->db->from($this->table);
            $this->db->where('designation_code', $record->designation_code);
            $this->db->where('status', 'active');
            $this->db->where('id !=', (int)$id);
            $conflict_check = $this->db->get();
            
            if ($conflict_check->num_rows() > 0) {
                // There's already an active record with the same code
                // We can't restore this one due to unique constraint
                return false;
            }
            
            // Safe to restore
            $this->db->where('id',(int)$id)->update($this->table, [
                'status' => 'active',
                'deleted_at' => NULL
            ]);
            return $this->db->affected_rows() > 0;
        } elseif ($this->db->field_exists('status', $this->table)){
            $this->db->where('id',(int)$id)->update($this->table, ['status'=>'active']);
            return $this->db->affected_rows() > 0;
        }
        
        return false;
    }
    
    public function all_with_deleted($include_deleted = false){
        $this->db->from($this->table);
        if (!$include_deleted && $this->db->field_exists('status', $this->table)){
            $this->db->where('status !=', 'inactive');
        }
        $this->db->order_by('level','ASC');
        $this->db->order_by('designation_name','ASC');
        return $this->db->get()->result();
    }
    
    public function deleted_only(){
        $this->db->from($this->table);
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('status', 'inactive');
        }
        $this->db->order_by('level','ASC');
        $this->db->order_by('designation_name','ASC');
        return $this->db->get()->result();
    }
}
