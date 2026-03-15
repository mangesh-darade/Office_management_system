<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {
    private $table = 'roles';

    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function get($id){
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }

    /**
     * Returns an associative array of [id => name] for all active roles.
     * Falls back to default mapping if the table is missing or empty.
     *
     * @return array<int,string>
     */
    public function get_all_as_map()
    {
        $out = [];
        if ($this->db->table_exists($this->table)) {
            $this->db->from($this->table);
            if ($this->db->field_exists('is_active', $this->table)) {
                $this->db->where('is_active', 1);
            }
            if ($this->db->field_exists('sort_order', $this->table)) {
                $this->db->order_by('sort_order', 'ASC');
            }
            $this->db->order_by('id', 'ASC');
            foreach ($this->db->get()->result() as $row) {
                $rid = (int)$row->id;
                if ($rid > 0) { $out[$rid] = $row->name; }
            }
        }
        return !empty($out) ? $out : [
            ROLE_ADMIN   => 'Admin',
            ROLE_MANAGER => 'Manager',
            ROLE_LEAD    => 'Lead',
            ROLE_STAFF   => 'Staff',
        ];
    }

    /**
     * Get all role rows (full objects).
     */
    public function get_all()
    {
        if (!$this->db->table_exists($this->table)) { return []; }
        $this->db->from($this->table);
        if ($this->db->field_exists('is_active', $this->table)) {
            $this->db->order_by('is_active', 'DESC');
        }
        if ($this->db->field_exists('sort_order', $this->table)) {
            $this->db->order_by('sort_order', 'ASC');
        }
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', (int)$id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }
}
