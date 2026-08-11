<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task_model extends CI_Model {
    private $table = 'tasks';
    public function __construct(){ parent::__construct(); $this->load->database();
        $this->load->helper('schema_columns'); }
    public function all(){ return $this->db->order_by('id','DESC')->get($this->table)->result(); }

    // Comments
    public function get_task_comments($task_id){
        $sel = ['c.*', 'u.email'];
        if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'u.name'; }
        $this->db->select(implode(', ', $sel))
                 ->from('task_comments c')
                 ->join('users u', 'u.id = c.user_id', 'left')
                 ->where('c.task_id', (int)$task_id)
                 ->order_by('c.created_at','DESC');
        return $this->db->get()->result();
    }

    public function add_comment($task_id, $user_id, $comment){
        $this->db->insert('task_comments', [
            'task_id' => (int)$task_id,
            'user_id' => (int)$user_id,
            'comment' => (string)$comment,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return (int)$this->db->insert_id();
    }

    public function delete_comment($comment_id, $user_id){
        // Allow owner to delete
        $this->db->where(['id' => (int)$comment_id, 'user_id' => (int)$user_id])->delete('task_comments');
        return $this->db->affected_rows() > 0;
    }

    public function log_activity($task_id, $user_id, $action, $old_value = null, $new_value = null)
    {
        if (!$this->db->table_exists('task_activity')) {
            return 0;
        }
        $allowed = array('created', 'updated', 'status_changed', 'assigned', 'commented', 'attachment_added', 'note');
        $action = (string) $action;
        if (!in_array($action, $allowed, true)) {
            $action = 'updated';
        }
        $old_json = null;
        $new_json = null;
        if ($old_value !== null) {
            $old_json = is_string($old_value) ? $old_value : json_encode($old_value);
        }
        if ($new_value !== null) {
            $new_json = is_string($new_value) ? $new_value : json_encode($new_value);
        }
        $this->db->insert('task_activity', array(
            'task_id' => (int) $task_id,
            'user_id' => (int) $user_id > 0 ? (int) $user_id : null,
            'action' => $action,
            'old_value' => $old_json,
            'new_value' => $new_json,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_activity($task_id, $limit = 50)
    {
        if (!$this->db->table_exists('task_activity')) {
            return array();
        }
        $select = array('a.*', 'u.email AS user_email');
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $select[] = 'u.name AS user_name';
        }
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $select[] = 'u.full_name AS user_full_name';
        }
        $this->db->select(implode(', ', $select), false);
        $this->db->from('task_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.task_id', (int) $task_id);
        $this->db->order_by('a.id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    /**
     * History timeline for Defects-style UI: activity + legacy comments as notes.
     *
     * @param int $task_id
     * @return array
     */
    public function list_history($task_id)
    {
        $task_id = (int) $task_id;
        $rows = array();

        foreach ($this->list_activity($task_id, 200) as $a) {
            $action = isset($a->action) ? (string) $a->action : 'updated';
            // Legacy comments are merged below; skip duplicate "commented" activity rows.
            if ($action === 'commented') {
                continue;
            }
            $name = '';
            if (!empty($a->user_name)) {
                $name = (string) $a->user_name;
            } elseif (!empty($a->user_full_name)) {
                $name = (string) $a->user_full_name;
            } elseif (!empty($a->user_email)) {
                $name = (string) $a->user_email;
            }
            $detail = $this->format_activity_detail($a);
            $rows[] = (object) array(
                'id' => (int) $a->id,
                'source' => 'activity',
                'action' => $action,
                'detail' => $detail,
                'user_name' => $name,
                'user_id' => isset($a->user_id) ? (int) $a->user_id : 0,
                'created_at' => (string) $a->created_at,
                'sort_ts' => strtotime((string) $a->created_at) ?: 0,
            );
        }

        foreach ($this->get_task_comments($task_id) as $c) {
            $name = '';
            if (isset($c->name) && trim((string) $c->name) !== '') {
                $name = (string) $c->name;
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

    public function delete_activity_for_task($task_id)
    {
        if (!$this->db->table_exists('task_activity')) {
            return false;
        }
        $this->db->where('task_id', (int) $task_id)->delete('task_activity');
        return true;
    }

    public function format_activity_detail($row)
    {
        if (!$row) {
            return '';
        }
        $old = $this->_decode_activity_json(isset($row->old_value) ? $row->old_value : null);
        $new = $this->_decode_activity_json(isset($row->new_value) ? $row->new_value : null);
        $action = isset($row->action) ? (string) $row->action : '';

        if (!empty($new['detail'])) {
            return (string) $new['detail'];
        }

        if ($action === 'status_changed') {
            $from = $this->_activity_value_label(isset($old['status']) ? $old['status'] : '');
            $to = $this->_activity_value_label(isset($new['status']) ? $new['status'] : '');
            if ($from !== '' || $to !== '') {
                return $from . ' → ' . $to;
            }
        }

        if ($action === 'assigned') {
            $from = isset($old['assignee']) ? (string) $old['assignee'] : '';
            $to = isset($new['assignee']) ? (string) $new['assignee'] : '';
            if ($from !== '' || $to !== '') {
                return ($from !== '' ? $from : 'Unassigned') . ' → ' . ($to !== '' ? $to : 'Unassigned');
            }
        }

        if ($action === 'commented' && !empty($new['comment'])) {
            return (string) $new['comment'];
        }

        if ($action === 'note' && !empty($new['comment'])) {
            return (string) $new['comment'];
        }

        if ($action === 'created') {
            return 'Task created';
        }

        if ($action === 'attachment_added' && !empty($new['file'])) {
            return (string) $new['file'];
        }

        if (!empty($new['field']) && (isset($new['from']) || isset($new['to']))) {
            $label = ucwords(str_replace('_', ' ', (string) $new['field']));
            $from = isset($new['from']) ? (string) $new['from'] : '';
            $to = isset($new['to']) ? (string) $new['to'] : '';
            if ($from === '' && $to === '') {
                return $label . ' updated';
            }
            return $label . ': ' . ($from !== '' ? $from : '—') . ' → ' . ($to !== '' ? $to : '—');
        }

        return '';
    }

    public function log_task_changes($task_id, $user_id, $old_row, $new_data, $old_assignee_ids = array(), $new_assignee_ids = array())
    {
        $task_id = (int) $task_id;
        $user_id = (int) $user_id;
        if ($task_id < 1 || !$this->db->table_exists('task_activity')) {
            return;
        }

        $old = is_array($old_row) ? $old_row : (array) $old_row;
        $fields = array(
            'status' => 'status',
            'title' => 'title',
            'description' => 'description',
            'priority' => 'priority',
            'due_date' => 'due_date',
            'start_date' => 'start_date',
            'estimate_hours' => 'estimate_hours',
            'project_id' => 'project',
            'requirement_id' => 'requirement',
            'reference_url' => 'reference',
        );

        foreach ($fields as $field => $label) {
            if (!array_key_exists($field, $new_data)) {
                continue;
            }
            $old_val = isset($old[$field]) ? $old[$field] : null;
            $new_val = $new_data[$field];
            if ($this->_activity_values_equal($old_val, $new_val)) {
                continue;
            }
            if ($field === 'description') {
                $old_display = $this->_activity_scalar_display($old_val);
                $new_display = $this->_activity_scalar_display($new_val);
                if (mb_strlen($old_display) > 120) {
                    $old_display = mb_substr($old_display, 0, 120) . '…';
                }
                if (mb_strlen($new_display) > 120) {
                    $new_display = mb_substr($new_display, 0, 120) . '…';
                }
                $this->log_activity($task_id, $user_id, 'updated', null, array(
                    'field' => $label,
                    'from' => $old_display,
                    'to' => $new_display,
                ));
                continue;
            }
            if ($field === 'status') {
                $this->log_activity($task_id, $user_id, 'status_changed', array(
                    'status' => (string) $old_val,
                ), array(
                    'status' => (string) $new_val,
                ));
                continue;
            }
            $this->log_activity($task_id, $user_id, 'updated', null, array(
                'field' => $label,
                'from' => $this->_activity_scalar_display($old_val),
                'to' => $this->_activity_scalar_display($new_val),
            ));
        }

        $old_ids = array_values(array_unique(array_map('intval', (array) $old_assignee_ids)));
        $new_ids = array_values(array_unique(array_map('intval', (array) $new_assignee_ids)));
        sort($old_ids);
        sort($new_ids);
        if ($old_ids !== $new_ids) {
            $this->log_activity($task_id, $user_id, 'assigned', array(
                'assignee' => $this->_activity_assignee_summary($old_ids),
            ), array(
                'assignee' => $this->_activity_assignee_summary($new_ids),
            ));
        }
    }

    private function _decode_activity_json($raw)
    {
        if ($raw === null || $raw === '') {
            return array();
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function _activity_value_label($status)
    {
        $status = trim((string) $status);
        if ($status === '') {
            return '';
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    private function _activity_scalar_display($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value) && (string) (int) $value === (string) $value) {
            return (string) (int) $value;
        }
        return trim((string) $value);
    }

    private function _activity_values_equal($a, $b)
    {
        return $this->_activity_scalar_display($a) === $this->_activity_scalar_display($b);
    }

    private function _activity_assignee_summary($user_ids)
    {
        $user_ids = array_values(array_filter(array_map('intval', (array) $user_ids)));
        if (empty($user_ids)) {
            return '';
        }
        if (count($user_ids) === 1) {
            return $this->_activity_user_label($user_ids[0]);
        }
        $labels = array();
        foreach ($user_ids as $uid) {
            $labels[] = $this->_activity_user_label($uid);
        }
        return implode(', ', $labels);
    }

    private function _activity_user_label($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return '';
        }
        $select = array('email');
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $select[] = 'name';
        }
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $select[] = 'full_name';
        }
        $user = $this->db->select(implode(', ', $select))
            ->from('users')
            ->where('id', $user_id)
            ->get()
            ->row();
        if (!$user) {
            return 'User #' . $user_id;
        }
        if (!empty($user->name)) {
            return (string) $user->name;
        }
        if (!empty($user->full_name)) {
            return (string) $user->full_name;
        }
        if (!empty($user->email)) {
            return (string) $user->email;
        }
        return 'User #' . $user_id;
    }
}
