<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Defect_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('defects_schema', 'schema_columns', 'defects_releases'));
        defects_schema_ensure($this->db);
    }

    private function _apply_defect_filters($filters = array())
    {
        if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            $this->db->where('d.is_deleted', 0);
        }
        defects_releases_apply_project_scope($this->db, 'd', 'project_id');
        if (!empty($filters['status'])) {
            $this->db->where('d.status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $this->db->where('d.severity', $filters['severity']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('d.project_id', (int) $filters['project_id']);
        }
        if (!empty($filters['client_id'])
            && $this->db->table_exists('projects')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $this->db->where('p.client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->where('d.assigned_to', (int) $filters['assigned_to']);
        }
        if (!empty($filters['overdue'])) {
            if (schema_table_has_column($this->db, 'project_defects', 'due_date')) {
                $this->db->where('d.due_date IS NOT NULL', null, false);
                $this->db->where('d.due_date <', date('Y-m-d'));
                $this->db->where_not_in('d.status', array('closed', 'rejected', 'verified'));
            }
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('d.title', $q)
                ->or_like('d.defect_number', $q)
                ->or_like('d.description', $q)
                ->group_end();
        }
    }

    public function count_defects($filters = array())
    {
        $this->db->from('project_defects d');
        $this->db->join('projects p', 'p.id = d.project_id', 'left');
        $this->_apply_defect_filters($filters);
        return (int) $this->db->count_all_results();
    }

    public function list_defects($filters = array(), $limit = null, $offset = 0)
    {
        $select = 'd.*, p.name AS project_name, rep.name AS reporter_name, asn.name AS assignee_name, r.version AS release_version';
        if ($this->db->table_exists('clients')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $select .= ', c.company_name AS client_name, p.client_id AS client_id';
        }
        $this->db->select($select);
        $this->db->from('project_defects d');
        $this->db->join('projects p', 'p.id = d.project_id', 'left');
        if ($this->db->table_exists('clients')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $this->db->join('clients c', 'c.id = p.client_id', 'left');
        }
        $this->db->join('users rep', 'rep.id = d.reported_by', 'left');
        $this->db->join('users asn', 'asn.id = d.assigned_to', 'left');
        $this->db->join('project_releases r', 'r.id = d.release_id', 'left');
        $this->_apply_defect_filters($filters);
        $this->db->order_by('d.id', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        $rows = $this->db->get()->result();
        // List shows all non-deleted (active + inactive); is_active is for UI toggle only.
        $has_active = schema_table_has_column($this->db, 'project_defects', 'is_active');
        foreach ($rows as $r) {
            if (!$has_active || !isset($r->is_active)) {
                $r->is_active = 1;
            } else {
                $r->is_active = (int) $r->is_active;
            }
        }
        return $rows;
    }

    /**
     * Flip project_defects.is_active (not workflow status).
     *
     * @return int|false New is_active (0|1), or false on failure
     */
    public function toggle_active($id)
    {
        if (!schema_table_has_column($this->db, 'project_defects', 'is_active')) {
            return false;
        }
        $item = $this->get_defect((int) $id);
        if (!$item) {
            return false;
        }
        $current = isset($item->is_active) ? (int) $item->is_active : 1;
        $next = $current === 1 ? 0 : 1;
        $ok = $this->db->where('id', (int) $id)->update('project_defects', array(
            'is_active' => $next,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if (!$ok) {
            return false;
        }
        return $next;
    }

    public function get_defect($id, $include_deleted = false)
    {
        $this->db->select('d.*, p.name AS project_name, rep.name AS reporter_name, asn.name AS assignee_name, ver.name AS verifier_name, r.version AS release_version, r.title AS release_title, t.title AS task_title');
        if ($this->db->table_exists('clients')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $this->db->select('c.company_name AS client_name, p.client_id AS client_id', false);
        }
        $this->db->from('project_defects d');
        $this->db->join('projects p', 'p.id = d.project_id', 'left');
        if ($this->db->table_exists('clients')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $this->db->join('clients c', 'c.id = p.client_id', 'left');
        }
        $this->db->join('users rep', 'rep.id = d.reported_by', 'left');
        $this->db->join('users asn', 'asn.id = d.assigned_to', 'left');
        $this->db->join('users ver', 'ver.id = d.verified_by', 'left');
        $this->db->join('project_releases r', 'r.id = d.release_id', 'left');
        $this->db->join('tasks t', 't.id = d.task_id', 'left');
        $this->db->where('d.id', (int) $id);
        if (!$include_deleted && schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            $this->db->where('d.is_deleted', 0);
        }
        $row = $this->db->get()->row();
        if (!$row) {
            return null;
        }
        if (!defects_releases_sees_all_org()) {
            $pid = (int) $row->project_id;
            if ($pid > 0) {
                $ids = defects_releases_scoped_project_ids();
                if (!in_array($pid, $ids, true)) {
                    return null;
                }
            }
        }
        return $row;
    }

    public function next_defect_number()
    {
        $prefix = 'DEF-' . date('Ym') . '-';
        $this->db->like('defect_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('project_defects', 1)->row();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->defect_number, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function save_defect($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('project_defects', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('project_defects', $data);
        return (int) $this->db->insert_id();
    }

    public function delete_defect($id)
    {
        if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            return $this->db->where('id', (int) $id)->update('project_defects', array(
                'is_deleted' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $this->db->where('id', (int) $id)->delete('project_defects');
    }

    public function log_activity($defect_id, $user_id, $action, $detail = '')
    {
        if (!$this->db->table_exists('project_defect_activity')) {
            return;
        }
        $this->db->insert('project_defect_activity', array(
            'defect_id' => (int) $defect_id,
            'user_id' => (int) $user_id,
            'action' => (string) $action,
            'detail' => $detail !== '' ? (string) $detail : null,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function list_activity($defect_id)
    {
        if (!$this->db->table_exists('project_defect_activity')) {
            return array();
        }
        $this->db->select('a.*, u.name AS user_name');
        $this->db->from('project_defect_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.defect_id', (int) $defect_id);
        $this->db->order_by('a.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Full history timeline: activity log + legacy comments (as note rows).
     *
     * @return array
     */
    public function list_history($defect_id)
    {
        $defect_id = (int) $defect_id;
        $rows = array();

        foreach ($this->list_activity($defect_id) as $a) {
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

        foreach ($this->list_comments($defect_id) as $c) {
            $rows[] = (object) array(
                'id' => (int) $c->id,
                'source' => 'comment',
                'action' => 'note',
                'detail' => isset($c->comment) ? (string) $c->comment : '',
                'user_name' => isset($c->user_name) ? (string) $c->user_name : '',
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

    /**
     * Build human-readable change lines for history.
     *
     * @param object $old
     * @param array  $new
     * @return array
     */
    public function build_change_details($old, array $new)
    {
        if (!$old) {
            return array();
        }
        $labels = array(
            'title' => 'Title',
            'project_id' => 'Project',
            'release_id' => 'Release',
            'task_id' => 'Task',
            'severity' => 'Severity',
            'priority' => 'Priority',
            'status' => 'Status',
            'assigned_to' => 'Assignee',
            'due_date' => 'Due date',
            'description' => 'Description',
            'steps_to_reproduce' => 'Steps to reproduce',
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
            if (in_array($key, array('description', 'steps_to_reproduce'), true)) {
                $lines[] = $label . ': updated';
                continue;
            }
            $before_disp = $this->_history_value_label($key, $before);
            $after_disp = $this->_history_value_label($key, $after);
            $lines[] = $label . ': ' . $before_disp . ' → ' . $after_disp;
        }
        return $lines;
    }

    /**
     * Human label for history values (IDs → names where possible).
     *
     * @param string $field
     * @param string $value
     * @return string
     */
    private function _history_value_label($field, $value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0') {
            return '—';
        }
        if (in_array($field, array('status', 'severity', 'priority'), true)) {
            return ucfirst(str_replace('_', ' ', $value));
        }
        if ($field === 'assigned_to' && ctype_digit($value)) {
            $u = $this->db->select('name')->where('id', (int) $value)->get('users', 1)->row();
            return ($u && $u->name !== '') ? (string) $u->name : ('#' . $value);
        }
        if ($field === 'project_id' && ctype_digit($value) && $this->db->table_exists('projects')) {
            $p = $this->db->select('name')->where('id', (int) $value)->get('projects', 1)->row();
            return ($p && $p->name !== '') ? (string) $p->name : ('#' . $value);
        }
        if ($field === 'release_id' && ctype_digit($value) && $this->db->table_exists('project_releases')) {
            $r = $this->db->select('version, title')->where('id', (int) $value)->get('project_releases', 1)->row();
            if ($r) {
                return trim((string) $r->version . ' — ' . (string) $r->title);
            }
            return '#' . $value;
        }
        if ($field === 'task_id' && ctype_digit($value) && $this->db->table_exists('tasks')) {
            $t = $this->db->select('title')->where('id', (int) $value)->get('tasks', 1)->row();
            return ($t && $t->title !== '') ? (string) $t->title : ('#' . $value);
        }
        return $value;
    }

    /**
     * Resolve user display name for history logs.
     *
     * @param int $user_id
     * @return string
     */
    public function user_display_name($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return 'Unassigned';
        }
        $u = $this->db->select('name')->where('id', $user_id)->get('users', 1)->row();
        return ($u && $u->name !== '') ? (string) $u->name : ('#' . $user_id);
    }

    public function add_comment($defect_id, $user_id, $comment)
    {
        if (!$this->db->table_exists('project_defect_comments')) {
            return false;
        }
        $comment = trim((string) $comment);
        if ($comment === '') {
            return false;
        }
        $this->db->insert('project_defect_comments', array(
            'defect_id' => (int) $defect_id,
            'user_id' => (int) $user_id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_comments($defect_id)
    {
        if (!$this->db->table_exists('project_defect_comments')) {
            return array();
        }
        $this->db->select('c.*, u.name AS user_name');
        $this->db->from('project_defect_comments c');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        $this->db->where('c.defect_id', (int) $defect_id);
        $this->db->order_by('c.id', 'ASC');
        return $this->db->get()->result();
    }

    public function save_attachments($defect_id, $user_id, array $uploads)
    {
        if (!$this->db->table_exists('project_defect_attachments') || empty($uploads)) {
            return;
        }
        foreach ($uploads as $u) {
            $this->db->insert('project_defect_attachments', array(
                'defect_id' => (int) $defect_id,
                'original_name' => (string) $u['original_name'],
                'stored_name' => (string) $u['stored_name'],
                'file_size' => (int) $u['file_size'],
                'uploaded_by' => (int) $user_id,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }
    }

    public function list_attachments($defect_id)
    {
        if (!$this->db->table_exists('project_defect_attachments')) {
            return array();
        }
        return $this->db->where('defect_id', (int) $defect_id)
            ->order_by('id', 'ASC')
            ->get('project_defect_attachments')
            ->result();
    }

    public function get_attachment($defect_id, $attachment_id)
    {
        if (!$this->db->table_exists('project_defect_attachments')) {
            return null;
        }
        return $this->db->where('id', (int) $attachment_id)
            ->where('defect_id', (int) $defect_id)
            ->get('project_defect_attachments')
            ->row();
    }

    public function project_options()
    {
        if (!$this->db->table_exists('projects')) {
            return array();
        }
        $select = 'id, name';
        $has_client = schema_table_has_column($this->db, 'projects', 'client_id');
        if ($has_client) {
            $select .= ', client_id';
        }
        if (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (empty($ids)) {
                return array();
            }
            $this->db->where_in('id', $ids);
        }
        return $this->db->select($select)->order_by('name')->get('projects')->result();
    }

    public function client_options()
    {
        if (!$this->db->table_exists('clients')) {
            return array();
        }
        $this->db->select('id, company_name')->from('clients');
        if (schema_table_has_column($this->db, 'clients', 'is_deleted')) {
            $this->db->where('is_deleted', 0);
        }
        return $this->db->order_by('company_name', 'ASC')->get()->result();
    }

    public function project_client_id($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1 || !$this->db->table_exists('projects')
            || !schema_table_has_column($this->db, 'projects', 'client_id')) {
            return 0;
        }
        $row = $this->db->select('client_id')->where('id', $project_id)->get('projects', 1)->row();
        return ($row && !empty($row->client_id)) ? (int) $row->client_id : 0;
    }

    public function is_project_accessible($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1 || !$this->db->table_exists('projects')) {
            return false;
        }
        if (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (!in_array($project_id, $ids, true)) {
                return false;
            }
        }
        $row = $this->db->select('id')->where('id', $project_id)->get('projects', 1)->row();
        return (bool) $row;
    }

    public function is_user_assignable($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1 || !$this->db->table_exists('users')) {
            return false;
        }
        $this->db->select('id')->from('users')->where('id', $user_id);
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('status', 'active');
        }
        return (bool) $this->db->limit(1)->get()->row();
    }

    public function release_belongs_to_project($release_id, $project_id)
    {
        $release_id = (int) $release_id;
        $project_id = (int) $project_id;
        if ($release_id < 1 || $project_id < 1 || !$this->db->table_exists('project_releases')) {
            return false;
        }
        $this->db->select('id')->from('project_releases')
            ->where('id', $release_id)
            ->where('project_id', $project_id);
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('is_deleted', 0);
        }
        return (bool) $this->db->limit(1)->get()->row();
    }

    public function task_belongs_to_project($task_id, $project_id)
    {
        $task_id = (int) $task_id;
        $project_id = (int) $project_id;
        if ($task_id < 1 || $project_id < 1 || !$this->db->table_exists('tasks')) {
            return false;
        }
        $row = $this->db->select('id')->from('tasks')
            ->where('id', $task_id)
            ->where('project_id', $project_id)
            ->limit(1)
            ->get()
            ->row();
        return (bool) $row;
    }

    public function release_options($project_id = null)
    {
        if (!$this->db->table_exists('project_releases')) {
            return array();
        }
        $this->db->select('id, version, title, project_id')->from('project_releases');
        if (schema_table_has_column($this->db, 'project_releases', 'is_deleted')) {
            $this->db->where('is_deleted', 0);
        }
        if ($project_id) {
            $this->db->where('project_id', (int) $project_id);
        } elseif (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (empty($ids)) {
                return array();
            }
            $this->db->where_in('project_id', $ids);
        }
        return $this->db->order_by('id', 'DESC')->get()->result();
    }

    public function list_by_release($release_id)
    {
        if (!$this->db->table_exists('project_defects')) {
            return array();
        }
        $this->db->select('d.id, d.defect_number, d.title, d.status, d.severity');
        $this->db->from('project_defects d');
        $this->db->where('d.release_id', (int) $release_id);
        if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            $this->db->where('d.is_deleted', 0);
        }
        $this->db->order_by('d.id', 'ASC');
        return $this->db->get()->result();
    }

    public function list_fixed_by_release($release_id, $project_id = null)
    {
        if (!$this->db->table_exists('project_defects')) {
            return array();
        }
        $this->db->select('d.id, d.defect_number, d.title, d.status, d.severity, d.release_id');
        $this->db->from('project_defects d');
        if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            $this->db->where('d.is_deleted', 0);
        }
        $this->db->where_in('d.status', array('fixed', 'verified', 'closed'));
        $this->db->group_start();
        $this->db->where('d.release_id', (int) $release_id);
        if ($project_id) {
            $this->db->or_group_start();
            $this->db->where('d.project_id', (int) $project_id);
            $this->db->where('d.release_id IS NULL', null, false);
            $this->db->group_end();
        }
        $this->db->group_end();
        $this->db->order_by('d.id', 'ASC');
        return $this->db->get()->result();
    }

    public function task_options($project_id = null)
    {
        if (!$this->db->table_exists('tasks')) {
            return array();
        }
        $this->db->select('id, title, project_id')->from('tasks');
        if ($project_id) {
            $this->db->where('project_id', (int) $project_id);
        } elseif (!defects_releases_sees_all_org()) {
            $ids = defects_releases_scoped_project_ids();
            if (empty($ids)) {
                return array();
            }
            $this->db->where_in('project_id', $ids);
        }
        return $this->db->order_by('id', 'DESC')->limit(200)->get()->result();
    }

    public function user_options()
    {
        $this->db->select('id, name')->from('users');
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('status', 'active');
        }
        return $this->db->order_by('name')->get()->result();
    }

    public function dashboard_counts($user_id)
    {
        $filters = array();
        $open = 0;
        $overdue = 0;
        if (!$this->db->table_exists('project_defects')) {
            return array('open' => 0, 'overdue' => 0);
        }
        $this->db->from('project_defects d');
        if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
            $this->db->where('d.is_deleted', 0);
        }
        defects_releases_apply_project_scope($this->db, 'd', 'project_id');
        $this->db->where_in('d.status', array('open', 'in_progress'));
        $open = (int) $this->db->count_all_results();

        if (schema_table_has_column($this->db, 'project_defects', 'due_date')) {
            $this->db->from('project_defects d');
            if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
                $this->db->where('d.is_deleted', 0);
            }
            defects_releases_apply_project_scope($this->db, 'd', 'project_id');
            $this->db->where('d.due_date IS NOT NULL', null, false);
            $this->db->where('d.due_date <', date('Y-m-d'));
            $this->db->where_not_in('d.status', array('closed', 'rejected', 'verified'));
            $overdue = (int) $this->db->count_all_results();
        }
        return array('open' => $open, 'overdue' => $overdue);
    }
}
