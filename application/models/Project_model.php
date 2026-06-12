<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Schema_columns_trait.php';

class Project_model extends CI_Model {
    use Schema_columns_trait;
    private $table = 'projects';
    public function __construct(){ parent::__construct(); $this->load->database(); $this->load->helper('hierarchy_filter'); }

    public function member_role_options()
    {
        return array('manager', 'lead', 'developer', 'tester', 'viewer', 'member');
    }

    public function sanitize_member_role($role)
    {
        $role = strtolower(trim((string) $role));
        if ($role === '') {
            return 'member';
        }
        return in_array($role, $this->member_role_options(), true) ? $role : 'member';
    }

    public function ensure_member_role_schema()
    {
        if (!$this->db->table_exists('project_members')) {
            return;
        }
        $row = $this->db->query("SHOW COLUMNS FROM `project_members` LIKE 'role'")->row();
        if (!$row || stripos((string) $row->Type, 'manager') !== false) {
            return;
        }
        $this->db->query(
            "ALTER TABLE `project_members` MODIFY `role` "
            . "ENUM('member','lead','viewer','manager','developer','tester') NOT NULL DEFAULT 'member'"
        );
    }
    
    public function all($filters = []){
        $this->db->select('p.*');
        $this->db->from($this->table . ' p');
        
        // Apply group filters for non-admin users
        if (!empty($filters)) {
            if (isset($filters['user_id'])) {
                // Show only projects where user is a member
                $this->db->join('project_members pm', 'pm.project_id = p.id');
                $this->db->where('pm.user_id', $filters['user_id']);
            }
        }
        if ($this->has_column('created_by')) {
            apply_role_hierarchy_filter($this->db, 'p.created_by');
        } else if ($this->has_column('manager_id')) {
            apply_role_hierarchy_filter($this->db, 'p.manager_id');
        }
        
        return $this->db->order_by('p.id','DESC')->group_by('p.id')->get()->result();
    }

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
        $this->ensure_member_role_schema();
        if ($this->check_user_is_member($project_id, $user_id)) return true;
        $this->db->insert('project_members', [
            'project_id' => (int)$project_id,
            'user_id' => (int)$user_id,
            'role' => $this->sanitize_member_role($role),
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
        $this->ensure_member_role_schema();
        $project_id = (int) $project_id;
        $user_id = (int) $user_id;
        $role = $this->sanitize_member_role($role);
        $existing = $this->db->get_where('project_members', array(
            'project_id' => $project_id,
            'user_id' => $user_id,
        ))->row();
        if (!$existing) {
            return false;
        }
        if ((string) $existing->role === $role) {
            return true;
        }
        $this->db->where(array('project_id' => $project_id, 'user_id' => $user_id))
            ->update('project_members', array('role' => $role));
        return $this->db->affected_rows() > 0;
    }
}
