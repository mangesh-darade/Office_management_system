<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirement_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function count_requirements($filters = []){
        $this->apply_filters($filters);
        return $this->db->count_all_results('requirements');
    }

    public function get_requirements($filters = [], $limit = null, $offset = 0){
        $this->db->from('requirements r');
        // joins
        if ($this->db->table_exists('clients')){ $this->db->join('clients c','c.id = r.client_id','left'); $this->db->select('r.*, c.company_name AS client_name'); }
        else { $this->db->select('r.*'); }
        if ($this->db->table_exists('users')){
            $sel = [];
            // Assigned to name
            if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $sel[] = "CONCAT(u.first_name,' ',u.last_name) AS assigned_to_name"; }
            else if ($this->db->field_exists('name','users')) { $sel[] = "u.name AS assigned_to_name"; }
            else { $sel[] = "u.email AS assigned_to_name"; }
            // Owner name
            if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $sel[] = "CONCAT(ow.first_name,' ',ow.last_name) AS owner_name"; }
            else if ($this->db->field_exists('name','users')) { $sel[] = "ow.name AS owner_name"; }
            else { $sel[] = "ow.email AS owner_name"; }
            $this->db->join('users u','u.id = r.assigned_to','left');
            $this->db->join('users ow','ow.id = r.owner_id','left');
            $this->db->select(implode(', ', $sel), false);
        }
        $this->apply_filters($filters, 'r');
        $this->db->order_by('r.created_at','DESC');
        if ($limit !== null){ $this->db->limit((int)$limit, (int)$offset); }
        return $this->db->get()->result();
    }

    private function apply_filters($filters, $alias = null){
        $t = $alias ? $alias.'.' : '';
        if (!empty($filters['status'])){ $this->db->where($t.'status', $filters['status']); }
        if (!empty($filters['priority'])){ $this->db->where($t.'priority', $filters['priority']); }
        if (!empty($filters['client_id'])){ $this->db->where($t.'client_id', (int)$filters['client_id']); }
        if (!empty($filters['assigned_to'])){ $this->db->where($t.'assigned_to', (int)$filters['assigned_to']); }
        if (!empty($filters['search'])){
            $q = trim((string)$filters['search']);
            $this->db->group_start()
                ->like($t.'title', $q)
                ->or_like($t.'req_number', $q)
            ->group_end();
        }
    }

    public function get_requirement($id){
        $this->db->from('requirements r')->where('r.id',(int)$id);
        $this->db->select('r.*');
        if ($this->db->table_exists('clients')){ $this->db->join('clients c','c.id=r.client_id','left'); $this->db->select('c.company_name AS client_name'); }
        if ($this->db->table_exists('users')){
            // Assigned
            $this->db->join('users u','u.id=r.assigned_to','left');
            if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $this->db->select("CONCAT(u.first_name,' ',u.last_name) AS assigned_to_name", false); }
            else if ($this->db->field_exists('name','users')) { $this->db->select("u.name AS assigned_to_name", false); }
            else { $this->db->select("u.email AS assigned_to_name", false); }
            // Owner
            $this->db->join('users ow','ow.id=r.owner_id','left');
            if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $this->db->select("CONCAT(ow.first_name,' ',ow.last_name) AS owner_name", false); }
            else if ($this->db->field_exists('name','users')) { $this->db->select("ow.name AS owner_name", false); }
            else { $this->db->select("ow.email AS owner_name", false); }
        }
        return $this->db->get()->row();
    }

    public function update_requirement($id, $data){
        if (isset($data['id'])) unset($data['id']);
        $this->db->where('id', (int)$id);
        return $this->db->update('requirements', $data);
    }

    public function create_requirement($data){
        $this->db->insert('requirements', $data);
        return (int)$this->db->insert_id();
    }

    public function add_attachment($data){
        $this->db->insert('requirement_attachments', $data);
        return (int)$this->db->insert_id();
    }

    public function get_attachments($requirement_id){
        return $this->db->where('requirement_id',(int)$requirement_id)->order_by('uploaded_at','DESC')->get('requirement_attachments')->result();
    }

    public function get_clients_for_filter(){
        if (!$this->db->table_exists('clients')){ return []; }
        return $this->db->select('id, company_name')->from('clients')->order_by('company_name','ASC')->get()->result();
    }

    public function get_team_members(){
        if (!$this->db->table_exists('users')){ return []; }
        $sel = ['id','email'];
        if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
        if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
        if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')) { $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
        return $this->db->select(implode(',', $sel), false)->from('users')->order_by('email','ASC')->get()->result();
    }

    // Versioning
    public function next_version_no($requirement_id){
        if (!$this->db->table_exists('requirement_versions')){ return 1; }
        $row = $this->db->select('MAX(version_no) AS mx', false)->from('requirement_versions')->where('requirement_id', (int)$requirement_id)->get()->row();
        $mx = ($row && isset($row->mx)) ? (int)$row->mx : 0;
        return $mx + 1;
    }

    public function create_version($requirement_id, $version_no, $data){
        if (!$this->db->table_exists('requirement_versions')){ return false; }
        $row = [
            'requirement_id' => (int)$requirement_id,
            'version_no' => (int)$version_no,
            'title' => isset($data['title']) ? $data['title'] : '',
            'description' => isset($data['description']) ? $data['description'] : null,
            'requirement_type' => isset($data['requirement_type']) ? $data['requirement_type'] : null,
            'priority' => isset($data['priority']) ? $data['priority'] : null,
            'status' => isset($data['status']) ? $data['status'] : null,
            'budget_estimate' => isset($data['budget_estimate']) ? $data['budget_estimate'] : null,
            'expected_delivery_date' => isset($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null,
            'received_date' => isset($data['received_date']) ? $data['received_date'] : null,
            'assigned_to' => isset($data['assigned_to']) ? $data['assigned_to'] : null,
            'created_by' => isset($data['created_by']) ? $data['created_by'] : null,
            'created_at' => isset($data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('requirement_versions', $row);
    }

    public function get_versions($requirement_id, $type = null){
        if (!$this->db->table_exists('requirement_versions')){ return []; }
        $this->db->where('requirement_id', (int)$requirement_id);
        if ($type !== null && $type !== ''){ $this->db->where('requirement_type', $type); }
        return $this->db->order_by('version_no','DESC')->get('requirement_versions')->result();
    }

    public function get_version_by_id($version_id){
        if (!$this->db->table_exists('requirement_versions')){ return null; }
        return $this->db->get_where('requirement_versions', ['id' => (int)$version_id])->row();
    }

    public function get_previous_version($requirement_id, $version_no){
        if (!$this->db->table_exists('requirement_versions')){ return null; }
        return $this->db->where('requirement_id', (int)$requirement_id)
                        ->where('version_no <', (int)$version_no)
                        ->order_by('version_no','DESC')
                        ->limit(1)
                        ->get('requirement_versions')->row();
    }
}
