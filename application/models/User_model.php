<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    private $table = 'users';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        if ($this->db->table_exists($this->table)){
            if (!$this->db->field_exists('notify_attendance', $this->table)){
                $this->db->query("ALTER TABLE `".$this->table."` ADD `notify_attendance` TINYINT(1) NOT NULL DEFAULT 1");
            }
        }
    }

    public function get_by_email($email){
        return $this->db->get_where($this->table, ['email' => $email])->row();
    }

    public function get_by_phone($phone){
        if (!$this->db->field_exists('phone', $this->table)){
            return null;
        }
        return $this->db->get_where($this->table, ['phone' => $phone])->row();
    }

    /**
     * Fetch user for authentication using email or phone number.
     * Checks both fields to support login with either identifier.
     */
    public function get_by_login($identifier){
        $this->db->from($this->table);
        
        // Check if identifier looks like an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->db->where('email', $identifier);
        } else {
            // For non-email, check both phone and email fields
            $this->db->group_start();
            if ($this->db->field_exists('phone', $this->table)) {
                $this->db->where('phone', $identifier);
            }
            $this->db->or_where('email', $identifier);
            $this->db->group_end();
        }
        
        return $this->db->get()->row();
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
     * Optionally restrict to specific role IDs when $roleIds is a non-empty array.
     */
    public function list_users($q = '', $limit = 250, $roleIds = null, $userId = null){
        $this->db->from($this->table);
        // Hide soft-deleted users from the grid if status column exists
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('status !=', 'inactive');
        }
        if ($userId !== null){
            $this->db->where('id', (int)$userId);
        }
        // Optional role-based filter (used to scope list by group type)
        if (is_array($roleIds) && !empty($roleIds) && $this->db->field_exists('role_id', $this->table)){
            $roleIds = array_map('intval', $roleIds);
            $this->db->where_in('role_id', $roleIds);
        }
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

    public function email_exists($email, $exclude_id = null){
        $this->db->from($this->table);
        $this->db->where('email', $email);
        if ($exclude_id !== null){
            $this->db->where('id !=', (int)$exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }

    public function phone_exists($phone, $exclude_id = null){
        if (!$this->db->field_exists('phone', $this->table)){
            return false;
        }
        $this->db->from($this->table);
        $this->db->where('phone', $phone);
        if ($exclude_id !== null){
            $this->db->where('id !=', (int)$exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }

    public function update($id, $data){
        if (isset($data['id'])) unset($data['id']);
        $this->db->where('id', (int)$id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id){
        // Prefer soft delete to avoid FK constraint errors
        if ($this->db->field_exists('status', $this->table)){
            $this->db->where('id', (int)$id);
            return $this->db->update($this->table, array('status' => 'inactive'));
        }
        // If no status column, do not hard-delete to avoid FK crashes
        return false;
    }
}
