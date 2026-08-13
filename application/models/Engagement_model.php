<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Engagement_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('engagement_schema', 'schema_columns', 'defects_releases'));
        engagement_schema_ensure($this->db);
    }

    private function _apply_release_filters($filters = array())
    {
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('r.is_deleted', 0);
        }
        defects_releases_apply_project_scope($this->db, 'r', 'project_id');
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('r.project_id', (int) $filters['project_id']);
        }
    }

    public function count_releases($filters = array())
    {
        $this->db->from('project_releases r');
        $this->_apply_release_filters($filters);
        return (int) $this->db->count_all_results();
    }

    public function list_releases($filters = array(), $limit = null, $offset = 0)
    {
        $this->db->select('r.*, p.name AS project_name, u.name AS creator_name');
        $this->db->from('project_releases r');
        $this->db->join('projects p', 'p.id = r.project_id', 'left');
        $this->db->join('users u', 'u.id = r.created_by', 'left');
        $this->_apply_release_filters($filters);
        $this->db->order_by('r.id', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get()->result();
    }

    public function get_release($id, $include_deleted = false)
    {
        $this->db->where('id', (int) $id);
        if (!$include_deleted && schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('is_deleted', 0);
        }
        $row = $this->db->get('project_releases')->row();
        if (!$row) {
            return null;
        }
        if (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (!in_array((int) $row->project_id, $ids, true)) {
                return null;
            }
        }
        return $row;
    }

    public function version_exists($project_id, $version, $exclude_id = null)
    {
        $this->db->where('project_id', (int) $project_id);
        $this->db->where('version', (string) $version);
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('is_deleted', 0);
        }
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }
        return $this->db->count_all_results('project_releases') > 0;
    }

    public function delete_release($id)
    {
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            return $this->db->where('id', (int) $id)->update('project_releases', array(
                'is_deleted' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $this->db->where('id', (int) $id)->delete('project_releases');
    }

    public function dashboard_counts()
    {
        if (!$this->db->table_exists('project_releases')) {
            return array('upcoming' => 0);
        }
        $this->db->from('project_releases r');
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('r.is_deleted', 0);
        }
        defects_releases_apply_project_scope($this->db, 'r', 'project_id');
        $this->db->where_in('r.status', array('planned', 'in_progress'));
        $this->db->where('r.planned_date IS NOT NULL', null, false);
        $this->db->where('r.planned_date >=', date('Y-m-d'));
        $this->db->where('r.planned_date <=', date('Y-m-d', strtotime('+14 days')));
        return array('upcoming' => (int) $this->db->count_all_results());
    }

    public function save_release($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('project_releases', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('project_releases', $data);
        return (int) $this->db->insert_id();
    }

    public function list_release_notes($release_id)
    {
        if (!$this->db->table_exists('project_release_notes')) {
            return array();
        }
        return $this->db->where('release_id', (int) $release_id)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get('project_release_notes')
            ->result();
    }

    public function save_release_notes($release_id, array $points)
    {
        if (!$this->db->table_exists('project_release_notes')) {
            return;
        }
        $release_id = (int) $release_id;
        $this->db->where('release_id', $release_id)->delete('project_release_notes');
        $order = 0;
        foreach ($points as $point) {
            $text = trim((string) $point);
            if ($text === '') {
                continue;
            }
            $this->db->insert('project_release_notes', array(
                'release_id' => $release_id,
                'sort_order' => $order++,
                'point_text' => mb_substr($text, 0, 500),
                'source_type' => 'manual',
                'source_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }
    }

    public function mark_release_notes_sent($release_id)
    {
        if (!$this->db->table_exists('project_releases')) {
            return;
        }
        if (!schema_table_has_column($this->db, 'project_releases', 'notes_sent_at')) {
            return;
        }
        $this->db->where('id', (int) $release_id)->update('project_releases', array(
            'notes_sent_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function get_release_with_project($id)
    {
        $this->db->select('r.*, p.name AS project_name');
        $this->db->from('project_releases r');
        $this->db->join('projects p', 'p.id = r.project_id', 'left');
        $this->db->where('r.id', (int) $id);
        return $this->db->get()->row();
    }

    public function list_kb($filters = array())
    {
        $this->db->select('k.*, u.name AS author_name');
        $this->db->from('kb_articles k');
        $this->db->join('users u', 'u.id = k.author_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('k.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('k.title', $q)
                ->or_like('k.summary', $q)
                ->group_end();
        }
        $this->db->order_by('k.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_kb($id)
    {
        return $this->db->where('id', (int) $id)->get('kb_articles')->row();
    }

    public function save_kb($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('kb_articles', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('kb_articles', $data);
        return (int) $this->db->insert_id();
    }

    public function list_tickets($filters = array())
    {
        $this->db->select('t.*, req.name AS requester_name, asn.name AS assignee_name');
        $this->db->from('helpdesk_tickets t');
        $this->db->join('users req', 'req.id = t.requester_id', 'left');
        $this->db->join('users asn', 'asn.id = t.assigned_to', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['requester_id'])) {
            $this->db->where('t.requester_id', (int) $filters['requester_id']);
        }
        $this->db->order_by('t.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_ticket($id)
    {
        return $this->db->where('id', (int) $id)->get('helpdesk_tickets')->row();
    }

    public function next_ticket_number()
    {
        $prefix = 'TKT-' . date('Ym') . '-';
        $this->db->like('ticket_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('helpdesk_tickets', 1)->row();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->ticket_number, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function save_ticket($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('helpdesk_tickets', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('helpdesk_tickets', $data);
        return (int) $this->db->insert_id();
    }

    public function list_events($upcoming_only = false)
    {
        $this->db->select('e.*, u.name AS organizer_name');
        $this->db->from('company_events e');
        $this->db->join('users u', 'u.id = e.organizer_id', 'left');
        if ($upcoming_only) {
            $this->db->where('e.start_at >=', date('Y-m-d H:i:s'));
        }
        $this->db->where('e.is_active', 1);
        $this->db->order_by('e.start_at', 'ASC');
        return $this->db->get()->result();
    }

    public function get_event($id)
    {
        return $this->db->where('id', (int) $id)->get('company_events')->row();
    }

    public function save_event($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('company_events', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('company_events', $data);
        return (int) $this->db->insert_id();
    }

    public function list_certifications($filters = array())
    {
        $this->db->select('c.*, u.name AS user_name');
        $this->db->from('employee_certifications c');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('c.status', $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('c.user_id', (int) $filters['user_id']);
        }
        $this->db->order_by('c.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_certification($id)
    {
        return $this->db->where('id', (int) $id)->get('employee_certifications')->row();
    }

    public function save_certification($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('employee_certifications', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('employee_certifications', $data);
        return (int) $this->db->insert_id();
    }

    public function list_feedback($filters = array())
    {
        $this->db->select('f.*, u.name AS submitter_name, c.company_name AS client_name, p.name AS project_name');
        $this->db->from('customer_feedback_entries f');
        $this->db->join('users u', 'u.id = f.submitted_by', 'left');
        $this->db->join('clients c', 'c.id = f.client_id', 'left');
        $this->db->join('projects p', 'p.id = f.project_id', 'left');
        if (!empty($filters['min_rating'])) {
            $this->db->where('f.rating >=', (int) $filters['min_rating']);
        }
        $this->db->order_by('f.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_feedback($id)
    {
        return $this->db->where('id', (int) $id)->get('customer_feedback_entries')->row();
    }

    public function save_feedback($data, $id = null)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('customer_feedback_entries', $data);
        return (int) $this->db->insert_id();
    }

    public function project_options()
    {
        if (!$this->db->table_exists('projects')) {
            return array();
        }
        if (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (empty($ids)) {
                return array();
            }
            $this->db->where_in('id', $ids);
        }
        return $this->db->select('id, name')->order_by('name')->get('projects')->result();
    }

    public function client_options()
    {
        if (!$this->db->table_exists('clients')) {
            return array();
        }
        return $this->db->select('id, company_name')->from('clients')->order_by('company_name', 'ASC')->get()->result();
    }

    public function user_options()
    {
        $this->db->select('id, name')->from('users');
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('status', 'active');
        }
        return $this->db->order_by('name')->get()->result();
    }

    public function log_activity($release_id, $user_id, $action, $detail = '')
    {
        if (!$this->db->table_exists('project_release_activity')) {
            return 0;
        }
        $this->db->insert('project_release_activity', array(
            'release_id' => (int) $release_id,
            'user_id' => (int) $user_id > 0 ? (int) $user_id : null,
            'action' => substr((string) $action, 0, 50),
            'detail' => $detail !== '' ? (string) $detail : null,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_activity($release_id)
    {
        if (!$this->db->table_exists('project_release_activity')) {
            return array();
        }
        $this->db->select('a.*, u.name AS user_name');
        $this->db->from('project_release_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.release_id', (int) $release_id);
        $this->db->order_by('a.id', 'DESC');
        return $this->db->get()->result();
    }

    public function list_history($release_id)
    {
        $rows = array();
        foreach ($this->list_activity((int) $release_id) as $a) {
            $rows[] = (object) array(
                'id' => (int) $a->id,
                'source' => 'activity',
                'action' => (string) $a->action,
                'detail' => isset($a->detail) ? (string) $a->detail : '',
                'user_name' => isset($a->user_name) ? (string) $a->user_name : '',
                'user_id' => isset($a->user_id) ? (int) $a->user_id : 0,
                'created_at' => (string) $a->created_at,
            );
        }
        return $rows;
    }

    public function build_change_details($old, array $new)
    {
        if (!$old) {
            return array();
        }
        $labels = array(
            'project_id' => 'Project',
            'version' => 'Version',
            'title' => 'Title',
            'description' => 'Description',
            'planned_date' => 'Planned date',
            'status' => 'Status',
            'released_at' => 'Released at',
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
            $lines[] = $label . ': ' . ($before !== '' ? $before : '—') . ' → ' . ($after !== '' ? $after : '—');
        }
        return $lines;
    }
}
