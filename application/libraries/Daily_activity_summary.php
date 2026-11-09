<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Builds the "today's work" summary used by Daily Activity and auto-submits a
 * daily log when the user checks out (see Attendance::punch).
 */
class Daily_activity_summary
{
    protected $CI;
    protected $db;
    protected $load;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
        $this->load = $this->CI->load;
        $this->load->helper(array('schema_columns'));
    }

    private function summary_status_label($status)
    {
        $status = trim((string) $status);
        if ($status === '') {
            return '';
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    /** Make sure daily_work_logs exists with the columns this module needs. */
    public function ensure_log_schema()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (!$this->db->table_exists('daily_work_logs')) {
            $this->db->query("CREATE TABLE `daily_work_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `task_id` int(11) DEFAULT NULL,
                `activity_title` varchar(255) DEFAULT NULL,
                `work_date` date NOT NULL,
                `description` text NOT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'submitted',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        } elseif (!schema_table_has_column($this->db, 'daily_work_logs', 'status')) {
            $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'submitted'");
        }
    }

    /**
     * Auto-submit today's log for a user (called right after check-out).
     * Skips when the user already logged for the day or there is no work to report.
     *
     * @return array status => created|skipped, reason|id
     */
    public function auto_submit($user_id, $work_date, $hours = null)
    {
        $user_id = (int) $user_id;
        $work_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $work_date) ? (string) $work_date : date('Y-m-d');
        if ($user_id <= 0) {
            return array('status' => 'skipped', 'reason' => 'no_user');
        }
        $this->ensure_log_schema();

        // A manual log for the day always wins: never auto-submit on top of what the user wrote.
        $manual = (int) $this->db->where('user_id', $user_id)->where('work_date', $work_date)
            ->where('status !=', 'auto_submitted')->count_all_results('daily_work_logs');
        if ($manual > 0) {
            return array('status' => 'skipped', 'reason' => 'manual_log_exists');
        }
        $auto = (int) $this->db->where('user_id', $user_id)->where('work_date', $work_date)->count_all_results('daily_work_logs');
        if ($auto > 0) {
            return array('status' => 'skipped', 'reason' => 'auto_log_exists');
        }

        $summary = $this->build_today_work_summary($user_id, $work_date);
        if (empty($summary['visible']) || trim((string) $summary['html']) === '') {
            return array('status' => 'skipped', 'reason' => 'no_work');
        }

        $title = $this->build_auto_title($summary, $work_date);
        $task_id = 0;

        $data = array(
            'user_id' => $user_id,
            'task_id' => $task_id > 0 ? $task_id : null,
            'activity_title' => mb_substr($title, 0, 255),
            'work_date' => $work_date,
            'description' => $summary['html'],
            'status' => 'auto_submitted',
            'created_at' => date('Y-m-d H:i:s'),
        );
        if (schema_table_has_column($this->db, 'daily_work_logs', 'hours')) {
            $data['hours'] = $hours !== null ? round((float) $hours, 2) : 0;
        }
        if (schema_table_has_column($this->db, 'daily_work_logs', 'notes')) {
            $data['notes'] = 'Auto-submitted at check-out';
        }
        if (!$this->db->insert('daily_work_logs', $data)) {
            return array('status' => 'skipped', 'reason' => 'db_error');
        }
        $log_id = (int) $this->db->insert_id();

        $this->load->helper('activity');
        if (function_exists('log_activity')) {
            log_activity('daily_activity', 'created', $log_id, 'Daily activity auto-submitted at check-out (log #' . $log_id . ')');
        }
        return array('status' => 'created', 'id' => $log_id);
    }

    /**
     * Open tasks assigned to the user for $work_date
     * (due that day, overdue, or open with no due date).
     *
     * @param int $user_id
     * @param string $work_date Y-m-d
     * @return array
     */
    private function fetch_today_tasks($user_id, $work_date)
    {
        $user_id = (int) $user_id;
        $work_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $work_date) ? (string) $work_date : date('Y-m-d');
        if ($user_id <= 0 || !$this->db->table_exists('tasks')) {
            return array();
        }

        $this->db->select('t.id, t.title', false);
        if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
            $this->db->select('t.due_date', false);
        } else {
            $this->db->select('NULL AS due_date', false);
        }
        if (schema_table_has_column($this->db, 'tasks', 'status')) {
            $this->db->select('t.status', false);
        } else {
            $this->db->select('NULL AS status', false);
        }
        if ($this->db->table_exists('projects')) {
            $this->db->select('p.name AS project_name', false);
        } else {
            $this->db->select('NULL AS project_name', false);
        }
        if ($this->db->table_exists('clients') && $this->db->table_exists('projects')
            && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $this->db->select('cl.company_name AS client_name', false);
        } else {
            $this->db->select('NULL AS client_name', false);
        }
        $this->db->from('tasks t');
        if ($this->db->table_exists('projects')) {
            $this->db->join('projects p', 'p.id = t.project_id', 'left');
            if ($this->db->table_exists('clients') && schema_table_has_column($this->db, 'projects', 'client_id')) {
                $this->db->join('clients cl', 'cl.id = p.client_id', 'left');
            }
        }

        // Prefer assigned work for "today's assigned" summary.
        if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
            $this->db->where('t.assigned_to', $user_id);
        } else {
            $this->db->where('t.created_by', $user_id);
        }

        // Open work (as before) OR any task that had activity today (e.g. completed today).
        $this->db->group_start();
        $this->db->group_start();
        if (schema_table_has_column($this->db, 'tasks', 'status')) {
            $this->db->where_in('t.status', array('pending', 'in_progress'));
        }
        if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
            $this->db->group_start();
            $this->db->where('t.due_date IS NULL', null, false);
            $this->db->or_where('t.due_date <=', $work_date);
            $this->db->group_end();
        }
        $this->db->group_end();
        if ($this->db->table_exists('task_activity')) {
            list($day_start, $day_end) = $this->summary_day_bounds($work_date);
            $this->db->or_where(
                'EXISTS (SELECT 1 FROM task_activity ta WHERE ta.task_id = t.id AND ta.created_at >= '
                . $this->db->escape($day_start) . ' AND ta.created_at <= ' . $this->db->escape($day_end) . ')',
                null,
                false
            );
        }
        $this->db->group_end();
        if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
            $this->db->order_by('t.due_date', 'ASC');
        }
        $this->db->order_by('t.id', 'DESC');
        $this->db->limit(40);
        return $this->db->get()->result();
    }

    /**
     * Open My Works assigned to the user (created_for) for $work_date.
     *
     * @param int $user_id
     * @param string $work_date Y-m-d
     * @return array
     */
    private function fetch_today_my_works($user_id, $work_date)
    {
        $user_id = (int) $user_id;
        $work_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $work_date) ? (string) $work_date : date('Y-m-d');
        if ($user_id <= 0 || !$this->db->table_exists('my_works')) {
            return array();
        }
        $this->load->helper(array('my_works_access', 'my_works_query', 'my_works_status', 'my_works_schema', 'schema_columns'));
        if (function_exists('my_works_schema_ensure')) {
            my_works_schema_ensure($this->db);
        }
        $can_view_all = false;
        $filters = array(
            'status' => '',
            'tag' => '',
            'q' => '',
            'created_for' => 0,
            'created_by' => 0,
            'client_id' => 0,
            'project_id' => 0,
            'work_type' => '',
            'involvement' => 'assigned',
            'urgent_only' => 0,
            'important_only' => 0,
            'overdue_only' => 0,
            'current_user_id' => $user_id,
        );
        $filters = my_works_sanitize_filters($filters, $can_view_all, $user_id);
        $rows = my_works_fetch_rows($this->db, $filters, $can_view_all, $user_id, 100, 0, true);
        $out = array();
        $has_due = schema_table_has_column($this->db, 'my_works', 'due_date');
        foreach ($rows as $row) {
            $due = ($has_due && !empty($row->due_date)) ? (string) $row->due_date : '';
            if ($due !== '' && $due > $work_date) {
                continue;
            }
            $out[] = (object) array(
                'id' => (int) $row->id,
                'title' => isset($row->title) ? (string) $row->title : '',
                'client_name' => !empty($row->client_name) ? (string) $row->client_name : '',
                'project_name' => !empty($row->project_name) ? (string) $row->project_name : '',
                'due_date' => $due,
                'tag' => !empty($row->tag) ? (string) $row->tag : '',
                'task_id' => (schema_table_has_column($this->db, 'my_works', 'task_id') && !empty($row->task_id))
                    ? (int) $row->task_id
                    : 0,
            );
        }
        return $out;
    }

    /**
     * @param string $work_date Y-m-d
     * @return array{0:string,1:string}
     */
    private function summary_day_bounds($work_date)
    {
        $work_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $work_date) ? (string) $work_date : date('Y-m-d');
        return array($work_date . ' 00:00:00', $work_date . ' 23:59:59');
    }

    /**
     * @param string $raw
     * @param int $max
     * @return string
     */
    private function summary_plain_text($raw, $max = 220)
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $raw)));
        if ($text === '') {
            return '';
        }
        $max = (int) $max;
        if ($max > 0 && function_exists('mb_strlen') && mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max - 1) . '…';
        }
        if ($max > 0 && strlen($text) > $max) {
            return substr($text, 0, $max - 1) . '…';
        }
        return $text;
    }

    /**
     * @param string $created_at
     * @return string
     */
    private function summary_time_label($created_at)
    {
        $ts = strtotime((string) $created_at);
        if ($ts === false) {
            return '';
        }
        return date('g:i A', $ts);
    }

    /**
     * Today's task comments + status/activity keyed by task_id.
     *
     * @param array $task_ids
     * @param string $work_date
     * @return array<int, array<int, array<string, string>>>
     */
    private function fetch_today_task_comments(array $task_ids, $work_date)
    {
        $out = array();
        $ids = array();
        foreach ($task_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return $out;
        }
        list($start, $end) = $this->summary_day_bounds($work_date);

        if ($this->db->table_exists('task_comments')) {
            $this->db->select('c.id, c.task_id, c.comment, c.created_at, u.name AS user_name, u.email AS user_email', false);
            $this->db->from('task_comments c');
            $this->db->join('users u', 'u.id = c.user_id', 'left');
            $this->db->where_in('c.task_id', $ids);
            $this->db->where('c.created_at >=', $start);
            $this->db->where('c.created_at <=', $end);
            $this->db->order_by('c.created_at', 'ASC');
            $this->db->order_by('c.id', 'ASC');
            $this->db->limit(200);
            foreach ($this->db->get()->result() as $row) {
                $tid = (int) $row->task_id;
                $text = $this->summary_plain_text(isset($row->comment) ? $row->comment : '', 280);
                if ($text === '') {
                    continue;
                }
                $who = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                if (!isset($out[$tid])) {
                    $out[$tid] = array();
                }
                $out[$tid][] = array(
                    'kind' => 'comment',
                    'time' => $this->summary_time_label($row->created_at),
                    'user' => $who,
                    'text' => $text,
                    'raw' => (string) (isset($row->comment) ? $row->comment : ''),
                    'sort' => (string) $row->created_at . '-c' . (int) $row->id,
                );
            }
        }

        if ($this->db->table_exists('task_activity')) {
            $this->load->model('Task_model');
            $this->db->select('a.id, a.task_id, a.action, a.old_value, a.new_value, a.created_at, u.name AS user_name, u.email AS user_email', false);
            $this->db->from('task_activity a');
            $this->db->join('users u', 'u.id = a.user_id', 'left');
            $this->db->where_in('a.task_id', $ids);
            $this->db->where('a.created_at >=', $start);
            $this->db->where('a.created_at <=', $end);
            $this->db->where_in('a.action', array('status_changed', 'assigned', 'updated', 'created', 'commented'));
            $this->db->order_by('a.created_at', 'ASC');
            $this->db->order_by('a.id', 'ASC');
            $this->db->limit(200);
            foreach ($this->db->get()->result() as $row) {
                $tid = (int) $row->task_id;
                $text = '';
                if (isset($this->CI->Task_model) && method_exists($this->CI->Task_model, 'format_activity_detail')) {
                    $text = trim((string) $this->CI->Task_model->format_activity_detail($row));
                }
                if ($text === '') {
                    $action = trim((string) $row->action);
                    $text = $action !== '' ? str_replace('_', ' ', $action) : 'update';
                }
                $who = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                if (!isset($out[$tid])) {
                    $out[$tid] = array();
                }
                $is_note = ((string) $row->action === 'commented');
                $out[$tid][] = array(
                    'kind' => $is_note ? 'comment' : 'history',
                    'time' => $this->summary_time_label($row->created_at),
                    'user' => $who,
                    'text' => $this->summary_plain_text($text, $is_note ? 280 : 180),
                    'raw' => $is_note ? $text : '',
                    'sort' => (string) $row->created_at . '-a' . (int) $row->id,
                    'is_status' => ((string) $row->action === 'status_changed'),
                );
            }
        }

        foreach ($out as $tid => $entries) {
            usort($entries, function ($a, $b) {
                $sa = isset($a['sort']) ? (string) $a['sort'] : '';
                $sb = isset($b['sort']) ? (string) $b['sort'] : '';
                return strcmp($sa, $sb);
            });
            foreach ($entries as &$entry) {
                unset($entry['sort']);
            }
            unset($entry);
            $out[$tid] = $entries;
        }

        return $out;
    }

    /**
     * Latest status-change note from history entries (e.g. "Pending → In Progress").
     *
     * @param array $history
     * @return string
     */
    private function summary_latest_status_note(array $history)
    {
        $note = '';
        foreach ($history as $entry) {
            // Only real status_changed entries — never due_date / field updates that also use "→".
            if (empty($entry['is_status'])) {
                continue;
            }
            $text = isset($entry['text']) ? trim((string) $entry['text']) : '';
            if ($text === '') {
                continue;
            }
            $note = $text;
        }
        return $note;
    }

    /**
     * Activity title from Tasks + My Works, preferring items with today's status/log changes.
     *
     * @param array $tasks
     * @param array $my_works
     * @param array $task_history_map
     * @param array $mw_history_map
     * @return array{title:string,task_id:int}
     */
    private function build_suggested_activity_title(array $tasks, array $my_works, array $task_history_map, array $mw_history_map)
    {
        $with_change = array();
        $without = array();

        foreach ($tasks as $task) {
            $title = isset($task->title) ? trim((string) $task->title) : '';
            if ($title === '') {
                continue;
            }
            $tid = (int) $task->id;
            $hist = isset($task_history_map[$tid]) ? $task_history_map[$tid] : array();
            $status_note = $this->summary_latest_status_note($hist);
            $entry = array(
                'title' => $title,
                'note' => $status_note,
                'task_id' => $tid,
                'has_change' => ($status_note !== '' || !empty($hist)),
            );
            if ($entry['has_change']) {
                $with_change[] = $entry;
            } else {
                $without[] = $entry;
            }
        }

        foreach ($my_works as $mw) {
            $title = isset($mw->title) ? trim((string) $mw->title) : '';
            if ($title === '') {
                continue;
            }
            $wid = (int) $mw->id;
            $hist = isset($mw_history_map[$wid]) ? $mw_history_map[$wid] : array();
            $status_note = $this->summary_latest_status_note($hist);
            $linked_task = (!empty($mw->task_id)) ? (int) $mw->task_id : 0;
            $entry = array(
                'title' => $title,
                'note' => $status_note,
                'task_id' => $linked_task,
                'has_change' => ($status_note !== '' || !empty($hist)),
            );
            if ($entry['has_change']) {
                $with_change[] = $entry;
            } else {
                $without[] = $entry;
            }
        }

        $ordered = array_merge($with_change, $without);
        if (empty($ordered)) {
            return array('title' => '', 'task_id' => 0);
        }

        $max_parts = 4;
        $parts = array();
        $extra = 0;
        $suggested_task_id = 0;
        foreach ($ordered as $i => $entry) {
            if ($suggested_task_id < 1 && !empty($entry['task_id'])) {
                $suggested_task_id = (int) $entry['task_id'];
            }
            if (count($parts) >= $max_parts) {
                $extra++;
                continue;
            }
            $part = $entry['title'];
            if (!empty($entry['note'])) {
                $part .= ' (' . $entry['note'] . ')';
            }
            $parts[] = $part;
        }
        $title = implode(' · ', $parts);
        if ($extra > 0) {
            $title .= ' +' . $extra . ' more';
        }
        if (function_exists('mb_strlen') && mb_strlen($title) > 250) {
            $title = mb_substr($title, 0, 247) . '…';
        } elseif (strlen($title) > 250) {
            $title = substr($title, 0, 247) . '…';
        }

        return array(
            'title' => $title,
            'task_id' => $suggested_task_id,
        );
    }

    /**
     * Today's My Work comments + activity keyed by work_id.
     *
     * @param array $work_ids
     * @param string $work_date
     * @return array<int, array<int, array<string, string>>>
     */
    private function fetch_today_my_work_history(array $work_ids, $work_date)
    {
        $out = array();
        $ids = array();
        foreach ($work_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return $out;
        }
        list($start, $end) = $this->summary_day_bounds($work_date);

        if ($this->db->table_exists('my_work_comments')) {
            $this->db->select('c.id, c.work_id, c.comment, c.created_at, u.name AS user_name, u.email AS user_email', false);
            $this->db->from('my_work_comments c');
            $this->db->join('users u', 'u.id = c.user_id', 'left');
            $this->db->where_in('c.work_id', $ids);
            $this->db->where('c.created_at >=', $start);
            $this->db->where('c.created_at <=', $end);
            $this->db->order_by('c.created_at', 'ASC');
            $this->db->order_by('c.id', 'ASC');
            $this->db->limit(200);
            foreach ($this->db->get()->result() as $row) {
                $wid = (int) $row->work_id;
                $text = $this->summary_plain_text(isset($row->comment) ? $row->comment : '', 280);
                if ($text === '') {
                    continue;
                }
                $who = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                if (!isset($out[$wid])) {
                    $out[$wid] = array();
                }
                $out[$wid][] = array(
                    'kind' => 'comment',
                    'time' => $this->summary_time_label($row->created_at),
                    'user' => $who,
                    'text' => $text,
                    'raw' => (string) (isset($row->comment) ? $row->comment : ''),
                    'sort' => (string) $row->created_at . '-c' . (int) $row->id,
                );
            }
        }

        if ($this->db->table_exists('my_work_activity')) {
            $this->db->select('a.id, a.work_id, a.action, a.detail, a.created_at, u.name AS user_name, u.email AS user_email', false);
            $this->db->from('my_work_activity a');
            $this->db->join('users u', 'u.id = a.user_id', 'left');
            $this->db->where_in('a.work_id', $ids);
            $this->db->where('a.created_at >=', $start);
            $this->db->where('a.created_at <=', $end);
            $this->db->order_by('a.created_at', 'ASC');
            $this->db->order_by('a.id', 'ASC');
            $this->db->limit(200);
            foreach ($this->db->get()->result() as $row) {
                $wid = (int) $row->work_id;
                $action = trim((string) $row->action);
                $detail = trim((string) $row->detail);
                // Skip noisy duplicate "comment" activity rows when real comment is already shown.
                if ($action === 'comment') {
                    continue;
                }
                if ($action === 'note' && $detail !== '') {
                    $who_n = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                    if (!isset($out[$wid])) {
                        $out[$wid] = array();
                    }
                    $out[$wid][] = array(
                        'kind' => 'comment',
                        'time' => $this->summary_time_label($row->created_at),
                        'user' => $who_n,
                        'text' => $this->summary_plain_text($detail, 280),
                        'raw' => $detail,
                        'sort' => (string) $row->created_at . '-n' . (int) $row->id,
                    );
                    continue;
                }
                $text = $action !== '' ? $action : 'update';
                if ($detail !== '') {
                    $text .= ': ' . $this->summary_plain_text($detail, 180);
                }
                $who = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                if (!isset($out[$wid])) {
                    $out[$wid] = array();
                }
                $is_status = (stripos($action, 'status') !== false);
                $out[$wid][] = array(
                    'kind' => 'history',
                    'time' => $this->summary_time_label($row->created_at),
                    'user' => $who,
                    'text' => $text,
                    'sort' => (string) $row->created_at . '-a' . (int) $row->id,
                    'is_status' => $is_status,
                );
            }
        }

        foreach ($out as $wid => $entries) {
            usort($entries, function ($a, $b) {
                $sa = isset($a['sort']) ? (string) $a['sort'] : '';
                $sb = isset($b['sort']) ? (string) $b['sort'] : '';
                return strcmp($sa, $sb);
            });
            foreach ($entries as &$entry) {
                unset($entry['sort']);
            }
            unset($entry);
            $out[$wid] = $entries;
        }

        return $out;
    }

    /**
     * @param string $due
     * @param string $work_date
     * @return string
     */
    private function summary_due_note($due, $work_date)
    {
        $due = trim((string) $due);
        if ($due === '') {
            return 'assigned';
        }
        if ($due < $work_date) {
            return 'overdue ' . date('j M', strtotime($due));
        }
        if ($due === $work_date) {
            return 'due today';
        }
        return 'due ' . date('j M', strtotime($due));
    }

    /**
     * @param array $entries
     * @return string
     */
    /**
     * Meaningful heading for an auto-submitted log, e.g.
     * "Gulf Pharmacy: 15_July_WhatsApp Offers… +8; ElintOm: ENG_Apparel-One-Software… +1".
     */
    public function build_auto_title(array $summary, $work_date)
    {
        $short = function ($t, $max = 34) {
            $t = trim(preg_replace('/\s+/', ' ', (string) $t), " 	

 \"'");
            return mb_strlen($t) > $max ? rtrim(mb_substr($t, 0, $max - 1)) . '…' : $t;
        };
        $groups = array();
        $items = array_merge(
            isset($summary['tasks']) ? $summary['tasks'] : array(),
            isset($summary['my_works']) ? $summary['my_works'] : array()
        );
        foreach ($this->summary_group_items($items) as $key => $list) {
            foreach ($list as $item) {
                $title = !empty($item['title']) ? $item['title'] : (isset($item['label']) ? $item['label'] : '');
                if ($title !== '') {
                    $groups[$key][] = $title;
                }
            }
        }
        if (empty($groups)) {
            return 'Daily summary - ' . date('j M Y', strtotime($work_date));
        }
        $parts = array();
        $shown_groups = 0;
        foreach ($groups as $name => $titles) {
            if ($shown_groups >= 3) {
                break;
            }
            $shown_groups++;
            $more = count($titles) - 1;
            $parts[] = $short($name, 22) . ': ' . $short($titles[0], 26) . ($more > 0 ? ' +' . $more : '');
        }
        $rest_groups = count($groups) - $shown_groups;
        $title = implode('; ', $parts) . ($rest_groups > 0 ? '; & ' . $rest_groups . ' more ' . ($rest_groups === 1 ? 'client' : 'clients') : '');
        return mb_strlen($title) > 160 ? mb_substr($title, 0, 159) . '…' : $title;
    }

    private function summary_is_done_status($status)
    {
        return in_array(strtolower(trim((string) $status)), array('completed', 'closed', 'done', 'verified', 'resolved', 'released', 'fixed'), true);
    }

    /**
     * Group items by client (fallback project, General). Completed work comes first:
     * inside each group completed items are on top, and groups that have completed
     * work are listed before the rest.
     *
     * @return array name => list of items
     */
    private function summary_group_items(array $items)
    {
        $groups = array();
        foreach ($items as $item) {
            $key = !empty($item['client']) ? $item['client'] : (!empty($item['project']) ? $item['project'] : 'General');
            $groups[$key][] = $item;
        }
        $order = array_keys($groups);
        $done_count = array();
        foreach ($groups as $name => $list) {
            $done = array();
            $open = array();
            foreach ($list as $it) {
                if ($this->summary_is_done_status(isset($it['status_raw']) ? $it['status_raw'] : '')) {
                    $done[] = $it;
                } else {
                    $open[] = $it;
                }
            }
            $groups[$name] = array_merge($done, $open);
            $done_count[$name] = count($done);
        }
        usort($order, function ($a, $b) use ($done_count) {
            $da = $done_count[$a] > 0 ? 1 : 0;
            $db = $done_count[$b] > 0 ? 1 : 0;
            return $db - $da;
        });
        $sorted = array();
        foreach ($order as $name) {
            $sorted[$name] = $groups[$name];
        }
        return $sorted;
    }

    /** Split a note into display steps (one per non-empty line). */
    private function summary_steps_from_text($raw)
    {
        $raw = trim(html_entity_decode(strip_tags(str_replace(array('</p>', '<br>', '<br/>', '<br />', '</li>'), "\n", (string) $raw)), ENT_QUOTES, 'UTF-8'));
        if ($raw === '') {
            return array();
        }
        $steps = array();
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if ($line !== '') {
                $steps[] = mb_strlen($line) > 220 ? mb_substr($line, 0, 217) . '...' : $line;
            }
        }
        return $steps;
    }

    /**
     * Client-wise report:
     *   1. Client:
     *      a. Task title            (green = done, orange = still open)
     *         i. note / status step
     */
    private function render_grouped_html(array $task_items, array $mw_items, array $tpl_lines)
    {
        $groups = $this->summary_group_items(array_merge($task_items, $mw_items));
        $html = '<ol type="1">';
        foreach ($groups as $name => $items) {
            $html .= '<li><u>' . $this->summary_esc($name) . '</u>:<ol type="a">';
            foreach ($items as $item) {
                $title = $item['title'] !== '' ? $item['title'] : $item['label'];
                $is_done = $this->summary_is_done_status($item['status_raw']);
                $color = $is_done ? '#2e7d32' : '#ef6c00';
                $tip = !empty($item['status_label']) ? 'Status: ' . $item['status_label'] : '';
                $html .= '<li><span style="color:' . $color . ';"' . ($tip !== '' ? ' title="' . $this->summary_esc($tip) . '"' : '') . '>' . $this->summary_esc($title) . '</span>';
                $steps = array();
                foreach ((array) $item['history'] as $entry) {
                    if (isset($entry['kind']) && $entry['kind'] === 'comment') {
                        $lines = $this->summary_steps_from_text(isset($entry['raw']) && $entry['raw'] !== '' ? $entry['raw'] : (isset($entry['text']) ? $entry['text'] : ''));
                        foreach ($lines as $line) {
                            $steps[] = $line;
                        }
                    } elseif (!empty($entry['is_status']) && !empty($entry['text'])) {
                        $steps[] = (string) $entry['text'];
                    }
                }
                if (!empty($steps)) {
                    $html .= '<ol type="i">';
                    foreach ($steps as $step) {
                        $html .= '<li><span style="color:' . $color . ';">' . $this->summary_esc($step) . '</span></li>';
                    }
                    $html .= '</ol>';
                }
                $html .= '</li>';
            }
            $html .= '</ol></li>';
        }
        if (!empty($tpl_lines)) {
            $html .= '<li><u>Template tasks</u>:<ol type="a">';
            foreach ($tpl_lines as $line) {
                $html .= '<li>' . $this->summary_esc($line) . '</li>';
            }
            $html .= '</ol></li>';
        }
        $html .= '</ol>';
        return $html;
    }

    private function render_history_description_html(array $entries)
    {
        if (empty($entries)) {
            return '<ul><li><em>No comments / history today</em></li></ul>';
        }
        $html = '<ul>';
        foreach ($entries as $entry) {
            $kind = isset($entry['kind']) && $entry['kind'] === 'comment' ? 'Comment' : 'History';
            $time = isset($entry['time']) ? (string) $entry['time'] : '';
            $user = isset($entry['user']) ? (string) $entry['user'] : '';
            $text = isset($entry['text']) ? (string) $entry['text'] : '';
            $meta = trim($kind . ($time !== '' ? ' · ' . $time : '') . ($user !== '' ? ' · ' . $user : ''));
            $html .= '<li><strong>' . $this->summary_esc($meta) . ':</strong> ' . $this->summary_esc($text) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Active template tasks that match open Task / My Work titles (spawned from templates).
     *
     * @param array $items Objects with title (tasks and/or my_works)
     * @return array
     */
    private function fetch_related_template_tasks(array $items)
    {
        if (!$this->db->table_exists('template_tasks') || empty($items)) {
            return array();
        }
        $this->load->helper(array('my_works_template_tasks_schema', 'schema_columns'));
        if (function_exists('my_works_template_tasks_schema_ensure')) {
            my_works_template_tasks_schema_ensure($this->db);
        }

        $titles = array();
        foreach ($items as $item) {
            $t = isset($item->title) ? strtolower(trim((string) $item->title)) : '';
            if ($t !== '') {
                $titles[$t] = true;
            }
        }
        if (empty($titles)) {
            return array();
        }

        $rows = $this->db->from('template_tasks')
            ->where('is_active', 1)
            ->order_by('team', 'ASC')
            ->order_by('template_type', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->limit(200)
            ->get()
            ->result();

        $out = array();
        foreach ($rows as $row) {
            $key = strtolower(trim((string) $row->title));
            if ($key === '' || !isset($titles[$key])) {
                continue;
            }
            $out[] = (object) array(
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'team' => isset($row->team) ? (string) $row->team : '',
                'template_type' => isset($row->template_type) ? (string) $row->template_type : '',
                'estimate_hours' => isset($row->estimate_hours) ? $row->estimate_hours : null,
            );
        }
        return $out;
    }

    /**
     * @param string $value
     * @return string
     */
    private function summary_esc($value)
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $client
     * @param string $project
     * @param string $title
     * @return string
     */
    private function summary_item_label($client, $project, $title)
    {
        $bits = array();
        $client = trim((string) $client);
        $project = trim((string) $project);
        $title = trim((string) $title);
        if ($client !== '') {
            $bits[] = $client;
        }
        if ($project !== '') {
            $bits[] = $project;
        }
        if ($title !== '') {
            $bits[] = $title;
        }
        return implode(' · ', $bits);
    }

    /**
     * Auto-build a written summary of today's assigned Tasks + My Works + linked Template tasks,
     * including today's comments / history under each item.
     *
     * @param int $user_id
     * @param string $work_date Y-m-d
     * @return array
     */
    public function build_today_work_summary($user_id, $work_date)
    {
        $user_id = (int) $user_id;
        $work_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $work_date) ? (string) $work_date : date('Y-m-d');
        $tasks = $this->fetch_today_tasks($user_id, $work_date);
        $my_works = $this->fetch_today_my_works($user_id, $work_date);
        $templates = $this->fetch_related_template_tasks(array_merge($tasks, $my_works));

        $task_ids = array();
        foreach ($tasks as $task) {
            $task_ids[] = (int) $task->id;
        }
        $mw_ids = array();
        foreach ($my_works as $mw) {
            $mw_ids[] = (int) $mw->id;
        }
        $task_history_map = $this->fetch_today_task_comments($task_ids, $work_date);
        $mw_history_map = $this->fetch_today_my_work_history($mw_ids, $work_date);

        $date_label = date('j M Y', strtotime($work_date));
        $task_items = array();
        foreach ($tasks as $task) {
            $label = $this->summary_item_label(
                !empty($task->client_name) ? $task->client_name : '',
                !empty($task->project_name) ? $task->project_name : '',
                isset($task->title) ? $task->title : ''
            );
            if ($label === '') {
                continue;
            }
            $tid = (int) $task->id;
            $history = isset($task_history_map[$tid]) ? $task_history_map[$tid] : array();
            $task_items[] = array(
                'id' => $tid,
                'client' => !empty($task->client_name) ? trim((string) $task->client_name) : '',
                'project' => !empty($task->project_name) ? trim((string) $task->project_name) : '',
                'title' => isset($task->title) ? trim((string) $task->title) : '',
                'status_raw' => isset($task->status) ? strtolower(trim((string) $task->status)) : '',
                'label' => $label,
                'due_note' => $this->summary_due_note(!empty($task->due_date) ? $task->due_date : '', $work_date),
                'status_label' => $this->summary_status_label(isset($task->status) ? $task->status : ''),
                'history' => $history,
            );
        }

        $mw_items = array();
        foreach ($my_works as $mw) {
            $label = $this->summary_item_label(
                !empty($mw->client_name) ? $mw->client_name : '',
                !empty($mw->project_name) ? $mw->project_name : '',
                isset($mw->title) ? $mw->title : ''
            );
            if ($label === '') {
                continue;
            }
            $wid = (int) $mw->id;
            $history = isset($mw_history_map[$wid]) ? $mw_history_map[$wid] : array();
            $mw_items[] = array(
                'id' => $wid,
                'client' => !empty($mw->client_name) ? trim((string) $mw->client_name) : '',
                'project' => !empty($mw->project_name) ? trim((string) $mw->project_name) : '',
                'title' => isset($mw->title) ? trim((string) $mw->title) : '',
                'status_raw' => isset($mw->status) ? strtolower(trim((string) $mw->status)) : '',
                'label' => $label,
                'due_note' => $this->summary_due_note(!empty($mw->due_date) ? $mw->due_date : '', $work_date),
                'status_label' => $this->summary_status_label(isset($mw->status) ? $mw->status : ''),
                'history' => $history,
            );
        }

        $tpl_lines = array();
        foreach ($templates as $tpl) {
            $bits = array_filter(array(
                isset($tpl->team) ? trim((string) $tpl->team) : '',
                isset($tpl->template_type) ? trim((string) $tpl->template_type) : '',
                isset($tpl->title) ? trim((string) $tpl->title) : '',
            ));
            $label = implode(' / ', $bits);
            if ($label !== '') {
                $tpl_lines[] = $label;
            }
        }

        $has_any = !empty($task_items) || !empty($mw_items) || !empty($tpl_lines);
        $html = $has_any ? $this->render_grouped_html($task_items, $mw_items, $tpl_lines) : '';

        $suggested = $this->build_suggested_activity_title($tasks, $my_works, $task_history_map, $mw_history_map);

        return array(
            'visible' => $has_any,
            'work_date' => $work_date,
            'date_label' => $date_label,
            'tasks' => $task_items,
            'my_works' => $mw_items,
            'templates' => $tpl_lines,
            'html' => $html,
            'counts' => array(
                'tasks' => count($task_items),
                'my_works' => count($mw_items),
                'templates' => count($tpl_lines),
            ),
            'suggested_activity_title' => isset($suggested['title']) ? $suggested['title'] : '',
            'suggested_task_id' => isset($suggested['task_id']) ? (int) $suggested['task_id'] : 0,
        );
    }
}
