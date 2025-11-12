<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Project_model extends CI_Model {
    private $table = 'projects';
    public function __construct(){ parent::__construct(); $this->load->database(); }
    public function all(){ return $this->db->order_by('id','DESC')->get($this->table)->result(); }

    // Members
    public function get_project_members($project_id){
        $this->db->select('pm.user_id, pm.role, u.email, u.name')
                 ->from('project_members pm')
                 ->join('users u', 'u.id = pm.user_id', 'left')
                 ->where('pm.project_id', (int)$project_id)
                 ->order_by('u.email','ASC');
        return $this->db->get()->result();
    }

    public function check_user_is_member($project_id, $user_id){
        return (bool)$this->db->get_where('project_members', [
            'project_id' => (int)$project_id,
            'user_id' => (int)$user_id,
        ])->row();
    }

    public function add_member($project_id, $user_id, $role){
        if ($this->check_user_is_member($project_id, $user_id)) return true;
        $this->db->insert('project_members', [
            'project_id' => (int)$project_id,
            'user_id' => (int)$user_id,
            'role' => $role ?: 'member',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function remove_member($project_id, $user_id){
        $this->db->where(['project_id' => (int)$project_id, 'user_id' => (int)$user_id])
                 ->delete('project_members');
        return $this->db->affected_rows() > 0;
    }

    public function update_member_role($project_id, $user_id, $role){
        $this->db->where(['project_id' => (int)$project_id, 'user_id' => (int)$user_id])
                 ->update('project_members', ['role' => $role]);
        return $this->db->affected_rows() >= 0;
    }
}
