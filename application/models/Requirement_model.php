<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirement_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database();
        $this->load->helper('schema_columns'); $this->load->helper('hierarchy_filter'); }

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
            if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')){ $sel[] = "CONCAT(u.first_name,' ',u.last_name) AS assigned_to_name"; }
            else if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = "u.name AS assigned_to_name"; }
            else { $sel[] = "u.email AS assigned_to_name"; }
            // Owner name
            if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')){ $sel[] = "CONCAT(ow.first_name,' ',ow.last_name) AS owner_name"; }
            else if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = "ow.name AS owner_name"; }
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
        if (!empty($filters['requirement_type'])){ $this->db->where($t.'requirement_type', $filters['requirement_type']); }
        if (!empty($filters['client_id'])){ $this->db->where($t.'client_id', (int)$filters['client_id']); }
        if (!empty($filters['project_id'])){ $this->db->where($t.'project_id', (int)$filters['project_id']); }
        if (!empty($filters['assigned_to'])){ $this->db->where($t.'assigned_to', (int)$filters['assigned_to']); }
        if (!empty($filters['search'])){
            $q = trim((string)$filters['search']);
            $this->db->group_start()
                ->like($t.'title', $q)
                ->or_like($t.'req_number', $q)
            ->group_end();
        }
        if ($alias) {
            apply_role_hierarchy_filter($this->db, $t.'created_by');
        } else {
            apply_role_hierarchy_filter($this->db, 'created_by');
        }
    }

    public function get_requirement($id){
        $this->db->from('requirements r')->where('r.id',(int)$id);
        $this->db->select('r.*');
        if ($this->db->table_exists('clients')){ $this->db->join('clients c','c.id=r.client_id','left'); $this->db->select('c.company_name AS client_name'); }
        if ($this->db->table_exists('projects')) {
            $this->db->join('projects p', 'p.id = r.project_id', 'left');
            $this->db->select('p.name AS project_name');
        }
        if ($this->db->table_exists('users')){
            // Assigned
            $this->db->join('users u','u.id=r.assigned_to','left');
            if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')){ $this->db->select("CONCAT(u.first_name,' ',u.last_name) AS assigned_to_name", false); }
            else if (schema_table_has_column($this->db, 'users', 'name')) { $this->db->select("u.name AS assigned_to_name", false); }
            else { $this->db->select("u.email AS assigned_to_name", false); }
            // Owner
            $this->db->join('users ow','ow.id=r.owner_id','left');
            if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')){ $this->db->select("CONCAT(ow.first_name,' ',ow.last_name) AS owner_name", false); }
            else if (schema_table_has_column($this->db, 'users', 'name')) { $this->db->select("ow.name AS owner_name", false); }
            else { $this->db->select("ow.email AS owner_name", false); }
        }
        apply_role_hierarchy_filter($this->db, 'r.created_by');
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
        if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'full_name'; }
        if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'name'; }
        if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')) { $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
        $this->db->select(implode(',', $sel), false)->from('users');
        apply_role_hierarchy_filter($this->db, 'id');
        return $this->db->order_by('email','ASC')->get()->result();
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
        if (schema_table_has_column($this->db, 'requirement_versions', 'reference_url')) {
            $row['reference_url'] = isset($data['reference_url']) ? $data['reference_url'] : null;
        }
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

    // Comments
    public function get_requirement_comments($requirement_id){
        if (!$this->db->table_exists('requirement_comments')){ return []; }
        $sel = ['c.*', 'u.email'];
        if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'u.name'; }
        if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'u.full_name'; }
        $this->db->select(implode(', ', $sel))
                 ->from('requirement_comments c')
                 ->join('users u', 'u.id = c.user_id', 'left')
                 ->where('c.requirement_id', (int)$requirement_id)
                 ->order_by('c.created_at','DESC');
        return $this->db->get()->result();
    }

    public function add_comment($requirement_id, $user_id, $comment){
        if (!$this->db->table_exists('requirement_comments')){ return false; }
        $this->db->insert('requirement_comments', [
            'requirement_id' => (int)$requirement_id,
            'user_id' => (int)$user_id,
            'comment' => (string)$comment,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return (int)$this->db->insert_id();
    }

    public function delete_comment($comment_id, $user_id){
        if (!$this->db->table_exists('requirement_comments')){ return false; }
        // Allow owner to delete
        $this->db->where(['id' => (int)$comment_id, 'user_id' => (int)$user_id])->delete('requirement_comments');
        return $this->db->affected_rows() > 0;
    }

    public function log_activity($requirement_id, $user_id, $action, $detail = '')
    {
        if (!$this->db->table_exists('requirement_activity')) {
            return;
        }
        $this->db->insert('requirement_activity', array(
            'requirement_id' => (int) $requirement_id,
            'user_id' => (int) $user_id,
            'action' => (string) $action,
            'detail' => $detail !== '' ? (string) $detail : null,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function list_activity($requirement_id)
    {
        if (!$this->db->table_exists('requirement_activity')) {
            return array();
        }
        $this->db->select('a.*, u.name AS user_name');
        $this->db->from('requirement_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.requirement_id', (int) $requirement_id);
        $this->db->order_by('a.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * History timeline: activity + legacy comments as notes.
     *
     * @return array
     */
    public function list_history($requirement_id)
    {
        $requirement_id = (int) $requirement_id;
        $rows = array();

        foreach ($this->list_activity($requirement_id) as $a) {
            $rows[] = (object) array(
                'id' => (int) $a->id,
                'source' => 'activity',
                'action' => (string) $a->action,
                'detail' => isset($a->detail) ? (string) $a->detail : '',
                'user_name' => isset($a->user_name) ? (string) $a->user_name : '',
                'user_id' => (int) $a->user_id,
                'created_at' => (string) $a->created_at,
                'sort_ts' => strtotime((string) $a->created_at) ?: 0,
            );
        }

        foreach ($this->get_requirement_comments($requirement_id) as $c) {
            $name = '';
            if (isset($c->name) && trim((string) $c->name) !== '') {
                $name = (string) $c->name;
            } elseif (isset($c->full_name) && trim((string) $c->full_name) !== '') {
                $name = (string) $c->full_name;
            } elseif (isset($c->email)) {
                $name = (string) $c->email;
            }
            $rows[] = (object) array(
                'id' => (int) $c->id,
                'source' => 'comment',
                'action' => 'note',
                'detail' => isset($c->comment) ? (string) $c->comment : '',
                'user_name' => $name,
                'user_id' => (int) $c->user_id,
                'created_at' => (string) $c->created_at,
                'sort_ts' => strtotime((string) $c->created_at) ?: 0,
            );
        }

        usort($rows, function ($a, $b) {
            if ($a->sort_ts === $b->sort_ts) {
                return $b->id - $a->id;
            }
            return ($a->sort_ts < $b->sort_ts) ? 1 : -1;
        });

        return $rows;
    }

    public function build_change_details($old, array $new)
    {
        if (!$old) {
            return array();
        }
        $labels = array(
            'title' => 'Title',
            'client_id' => 'Client',
            'project_id' => 'Project',
            'requirement_type' => 'Type',
            'priority' => 'Priority',
            'status' => 'Status',
            'expected_delivery_date' => 'Expected delivery',
            'received_date' => 'Received date',
            'owner_id' => 'Owner',
            'assigned_to' => 'Assignee',
            'description' => 'Description',
            'reference_url' => 'URL / Link',
        );
        $lines = array();
        foreach ($labels as $key => $label) {
            if (!array_key_exists($key, $new)) {
                continue;
            }
            $before = isset($old->$key) ? (string) $old->$key : '';
            $after = $new[$key] === null ? '' : (string) $new[$key];
            if ($before === $after) {
                continue;
            }
            if ($key === 'description') {
                $lines[] = $label . ': updated';
                continue;
            }
            $before_disp = $this->_history_value_label($key, $before);
            $after_disp = $this->_history_value_label($key, $after);
            $lines[] = $label . ': ' . $before_disp . ' → ' . $after_disp;
        }
        return $lines;
    }

    private function _history_value_label($field, $value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0') {
            return '—';
        }
        if (in_array($field, array('status', 'priority', 'requirement_type'), true)) {
            return ucfirst(str_replace('_', ' ', $value));
        }
        if (in_array($field, array('assigned_to', 'owner_id'), true) && ctype_digit($value)) {
            return $this->user_display_name((int) $value);
        }
        if ($field === 'client_id' && ctype_digit($value) && $this->db->table_exists('clients')) {
            $c = $this->db->select('company_name')->where('id', (int) $value)->get('clients', 1)->row();
            return ($c && $c->company_name !== '') ? (string) $c->company_name : ('#' . $value);
        }
        if ($field === 'project_id' && ctype_digit($value) && $this->db->table_exists('projects')) {
            $p = $this->db->select('name')->where('id', (int) $value)->get('projects', 1)->row();
            return ($p && $p->name !== '') ? (string) $p->name : ('#' . $value);
        }
        return $value;
    }

    public function user_display_name($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return 'Unassigned';
        }
        $u = $this->db->select('name')->where('id', $user_id)->get('users', 1)->row();
        return ($u && $u->name !== '') ? (string) $u->name : ('#' . $user_id);
    }
}
