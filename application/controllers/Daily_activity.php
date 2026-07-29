<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daily_activity extends CI_Controller {

    private $upload_dir = 'uploads/daily_activity/';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission', 'hierarchy_filter', 'schema_columns', 'view_output', 'download', 'daily_activity_attachment']);
        $this->load->library(['session', 'upload']);
        
        // RBAC Audit: Centralized module access check
        require_controller_access('daily_activity', true);
        
        $this->ensure_table();
    }
    
    private function ensure_table() {
        static $done = false;
        if ($done) { return; }
        $done = true;
        if (!$this->db->table_exists('daily_work_logs')) {
            $this->db->query("CREATE TABLE `daily_work_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `task_id` int(11) DEFAULT NULL,
                `activity_title` varchar(255) DEFAULT NULL,
                `work_date` date NOT NULL,
                `description` text NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        } else {
            // Check for missing columns in existing table
            if (!schema_table_has_column($this->db, 'daily_work_logs', 'description')) {
                $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `description` TEXT NOT NULL AFTER `work_date`");
            }
            if (!schema_table_has_column($this->db, 'daily_work_logs', 'task_id')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `task_id` int(11) DEFAULT NULL AFTER `user_id`");
            }
            if (!schema_table_has_column($this->db, 'daily_work_logs', 'work_date')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `work_date` date NOT NULL AFTER `task_id`");
            }
            if (!schema_table_has_column($this->db, 'daily_work_logs', 'activity_title')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `activity_title` varchar(255) DEFAULT NULL AFTER `task_id`");
            }

            // Ensure task_id allows NULL (Fix for error 1048)
            $this->db->query("ALTER TABLE `daily_work_logs` MODIFY `task_id` INT(11) DEFAULT NULL");
        }
        daily_activity_attachments_schema_ensure($this->db);
        $dir = FCPATH . $this->upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private function sanitize_activity_description($raw)
    {
        return sanitize_html_output(trim((string) $raw));
    }

    private function can_access_log($log)
    {
        if (!$log) {
            return false;
        }
        $user_id = (int) $this->session->userdata('user_id');
        if ((int) $log->user_id === $user_id) {
            return true;
        }
        if (is_admin_group()
            || has_module_access('daily_activity_view_all')
            || has_module_access('daily_activity_edit_all')
            || has_module_access('daily_activity_delete_all')) {
            return true;
        }
        // Same hierarchy scope as list/index (managers can open team logs).
        $this->db->from('daily_work_logs');
        $this->db->where('id', (int) $log->id);
        apply_role_hierarchy_filter($this->db, 'user_id');
        return (int) $this->db->count_all_results() > 0;
    }

    private function save_new_attachments($log_id, array $uploads)
    {
        $log_id = (int) $log_id;
        if ($log_id < 1 || empty($uploads)) {
            return;
        }
        $sort = daily_activity_attachment_max_sort($this->db, $log_id);
        foreach ($uploads as $upload) {
            $sort++;
            daily_activity_attachment_insert(
                $this->db,
                $log_id,
                $upload['original'],
                $upload['stored'],
                isset($upload['size']) ? (int) $upload['size'] : 0,
                $sort,
                isset($upload['mime']) ? $upload['mime'] : null
            );
        }
    }

    private function process_remove_attachments($log_id)
    {
        $log_id = (int) $log_id;
        $remove_ids = $this->input->post('remove_attachments');
        if (!is_array($remove_ids) || $log_id < 1) {
            return;
        }
        foreach ($remove_ids as $raw_id) {
            $att_id = (int) $raw_id;
            if ($att_id < 1) {
                continue;
            }
            $att = daily_activity_attachment_find($this->db, $log_id, $att_id);
            if ($att) {
                daily_activity_delete_attachment_file($att->stored_name);
                daily_activity_attachment_delete_row($this->db, $log_id, $att_id);
            }
        }
    }

    private function serve_attachment($att, $inline = false)
    {
        if (!$att || empty($att->stored_name)) {
            show_404();
        }
        $stored = (string) $att->stored_name;
        if (strpos($stored, '..') !== false || strpos($stored, '/') !== false || strpos($stored, '\\') !== false) {
            show_404();
        }
        $path = FCPATH . $this->upload_dir . $stored;
        if (!is_file($path)) {
            show_error('File not found.', 404);
        }
        $name = !empty($att->original_name) ? (string) $att->original_name : $stored;
        if (!$inline) {
            force_download($name, file_get_contents($path));
            return;
        }
        $kind = daily_activity_attachment_kind($name, $stored);
        if (!in_array($kind, array('video', 'image', 'audio'), true)) {
            return false;
        }
        $mime = !empty($att->mime_type) ? (string) $att->mime_type : daily_activity_attachment_mime_type($name);
        $size = filesize($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Content-Disposition: inline; filename="' . basename($name) . '"');
        readfile($path);
        exit;
    }

    private function attachments_map_for_logs($logs)
    {
        $ids = array();
        foreach ((array) $logs as $log) {
            if (!empty($log->id)) {
                $ids[] = (int) $log->id;
            }
        }
        return daily_activity_attachments_bulk_map($this->db, $ids);
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

        if (schema_table_has_column($this->db, 'tasks', 'status')) {
            $this->db->where_in('t.status', array('pending', 'in_progress'));
        }
        if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
            $this->db->group_start();
            $this->db->where('t.due_date IS NULL', null, false);
            $this->db->or_where('t.due_date <=', $work_date);
            $this->db->group_end();
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
            $this->db->where_in('a.action', array('status_changed', 'assigned', 'updated', 'created'));
            $this->db->order_by('a.created_at', 'ASC');
            $this->db->order_by('a.id', 'ASC');
            $this->db->limit(200);
            foreach ($this->db->get()->result() as $row) {
                $tid = (int) $row->task_id;
                $text = '';
                if (isset($this->Task_model) && method_exists($this->Task_model, 'format_activity_detail')) {
                    $text = trim((string) $this->Task_model->format_activity_detail($row));
                }
                if ($text === '') {
                    $action = trim((string) $row->action);
                    $text = $action !== '' ? str_replace('_', ' ', $action) : 'update';
                }
                $who = !empty($row->user_name) ? (string) $row->user_name : (!empty($row->user_email) ? (string) $row->user_email : 'User');
                if (!isset($out[$tid])) {
                    $out[$tid] = array();
                }
                $out[$tid][] = array(
                    'kind' => 'history',
                    'time' => $this->summary_time_label($row->created_at),
                    'user' => $who,
                    'text' => $this->summary_plain_text($text, 180),
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
    private function build_today_work_summary($user_id, $work_date)
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
                'label' => $label,
                'due_note' => $this->summary_due_note(!empty($task->due_date) ? $task->due_date : '', $work_date),
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
                'label' => $label,
                'due_note' => $this->summary_due_note(!empty($mw->due_date) ? $mw->due_date : '', $work_date),
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
        $html = '';
        if ($has_any) {
            $html .= '<p><strong>Today\'s assigned work — ' . $this->summary_esc($date_label) . '</strong></p>';
            if (!empty($task_items)) {
                $html .= '<p><strong>Tasks (' . count($task_items) . ')</strong></p>';
                foreach ($task_items as $item) {
                    $html .= '<p><strong>' . $this->summary_esc($item['label']) . '</strong>';
                    if (!empty($item['due_note'])) {
                        $html .= ' <em>(' . $this->summary_esc($item['due_note']) . ')</em>';
                    }
                    $html .= '</p>';
                    $html .= $this->render_history_description_html($item['history']);
                }
            }
            if (!empty($mw_items)) {
                $html .= '<p><strong>My Works (' . count($mw_items) . ')</strong></p>';
                foreach ($mw_items as $item) {
                    $html .= '<p><strong>' . $this->summary_esc($item['label']) . '</strong>';
                    if (!empty($item['due_note'])) {
                        $html .= ' <em>(' . $this->summary_esc($item['due_note']) . ')</em>';
                    }
                    $html .= '</p>';
                    $html .= $this->render_history_description_html($item['history']);
                }
            }
            if (!empty($tpl_lines)) {
                $html .= '<p><strong>Template tasks (' . count($tpl_lines) . ')</strong></p><ul>';
                foreach ($tpl_lines as $line) {
                    $html .= '<li>' . $this->summary_esc($line) . '</li>';
                }
                $html .= '</ul>';
            }
        }

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

    private function fetch_activity_tasks($linked_task_id = 0)
    {
        $this->db->select('id, title');
        $this->db->from('tasks');
        apply_role_hierarchy_filter($this->db, 'created_by');
        $this->db->where_in('status', array('pending', 'in_progress'));
        $this->db->order_by('id', 'DESC');
        $tasks = $this->db->get()->result();

        $linked_task_id = (int) $linked_task_id;
        if ($linked_task_id > 0) {
            $linked = $this->db->select('id, title')->from('tasks')->where('id', $linked_task_id)->get()->row();
            if ($linked) {
                $found = false;
                foreach ($tasks as $task) {
                    if ((int) $task->id === (int) $linked->id) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    array_unshift($tasks, $linked);
                }
            }
        }

        return $tasks;
    }

    private function resolve_activity_title_value($log)
    {
        $title = !empty($log->activity_title) ? trim((string) $log->activity_title) : '';
        if ($title !== '') {
            return $title;
        }
        $task_id = (int) $log->task_id;
        if ($task_id <= 0 || !$this->db->table_exists('tasks')) {
            return '';
        }
        $row = $this->db->select('title')->from('tasks')->where('id', $task_id)->limit(1)->get()->row();
        return $row && !empty($row->title) ? (string) $row->title : '';
    }

    public function index() {
        require_module_access(['daily_activity_add', 'daily_activity'], true);
        $user_id = (int)$this->session->userdata('user_id');
        $date = $this->input->get('date') ? $this->input->get('date') : date('Y-m-d');
        
        // Fetch logs for the selected date
        $this->db->select('dl.*, t.title as task_title');
        $this->db->from('daily_work_logs dl');
        $this->db->join('tasks t', 't.id = dl.task_id', 'left');
        $this->db->where('dl.user_id', $user_id);
        apply_role_hierarchy_filter($this->db, 'dl.user_id');
        $this->db->where('dl.work_date', $date);
        $this->db->order_by('dl.created_at', 'DESC');
        $logs = $this->db->get()->result();

        $tasks = $this->fetch_activity_tasks();
        $today_summary = $this->build_today_work_summary($user_id, $date);

        $this->load->view('daily_activity/index', [
            'logs' => $logs,
            'date' => $date,
            'tasks' => $tasks,
            'today_summary' => $today_summary,
            'attachments_map' => $this->attachments_map_for_logs($logs),
        ]);
    }
    
    public function list_all($offset = 0) {
        require_module_access(['daily_activity_list', 'daily_activity'], true);
        $this->load->library('pagination');
        
        // --- Filter Logic ---
        $filters = [
            'period' => $this->input->get('period'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'user_id' => $this->input->get('user_id') // Admin might want to filter by user in main list too if allowed
        ];

        // Period Shortcuts
        if($filters['period']) {
            $today = date('Y-m-d');
            if($filters['period'] == 'daily') {
                $filters['date_from'] = $today;
                $filters['date_to'] = $today;
            } elseif($filters['period'] == 'weekly') {
                // Current week (Mon-Sun or Sun-Sat). Let's use Monday start.
                $filters['date_from'] = date('Y-m-d', strtotime('monday this week'));
                $filters['date_to'] = date('Y-m-d', strtotime('sunday this week'));
            } elseif($filters['period'] == 'monthly') {
                $filters['date_from'] = date('Y-m-01');
                $filters['date_to'] = date('Y-m-t');
            }
        }
        
        // --- Build Query ---
        // Role check
        $is_admin = is_admin_group() || has_module_access('daily_activity_view_all');

        // Helper closure to apply shared WHERE conditions
        $apply_filters = function() use ($filters, $is_admin) {
            if (!empty($filters['user_id'])) {
                $this->db->where('dl.user_id', $filters['user_id']);
            }
            apply_role_hierarchy_filter($this->db, 'dl.user_id');
            if (!empty($filters['date_from'])) {
                $this->db->where('dl.work_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('dl.work_date <=', $filters['date_to']);
            }
        };

        // Get Count (separate query to avoid state leakage)
        $this->db->from('daily_work_logs dl');
        $this->db->join('tasks t', 't.id = dl.task_id', 'left');
        $this->db->join('users u', 'u.id = dl.user_id', 'left');
        $apply_filters();
        $total_rows = $this->db->count_all_results();

        // --- Pagination ---
        $config['base_url'] = site_url('daily-activity/list');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = 20;
        $config['uri_segment'] = 3;
        $config['reuse_query_string'] = TRUE;
        
        // Styles
        $config['full_tag_open'] = '<nav><ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
        $config['cur_tag_close'] = '</span></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);

        // --- Fetch Data (fresh query to avoid state leakage from count) ---
        $this->db->select('dl.*, t.title as task_title, u.name as user_name, u.email as user_email');
        $this->db->from('daily_work_logs dl');
        $this->db->join('tasks t', 't.id = dl.task_id', 'left');
        $this->db->join('users u', 'u.id = dl.user_id', 'left');
        $apply_filters();
        // Newest activity first (work day, then when logged, then id).
        $this->db->order_by('dl.work_date', 'DESC');
        $this->db->order_by('dl.created_at', 'DESC');
        $this->db->order_by('dl.id', 'DESC');
        $this->db->limit($config['per_page'], $offset);
        $logs = $this->db->get()->result();

        // Users for filter (Admin only)
        $users = [];
        if ($is_admin) {
            $this->db->select('id, name, email')->from('users');
            apply_role_hierarchy_filter($this->db, 'id');
            $users = $this->db->order_by('name')->get()->result();
        }

        $this->load->view('daily_activity/list', [
            'logs' => $logs,
            'is_admin' => $is_admin,
            'filters' => $filters,
            'users' => $users,
            'attachments_map' => $this->attachments_map_for_logs($logs),
        ]);
    }

    public function save() {
        require_module_access(['daily_activity_add', 'daily_activity'], true);
        if ($this->input->method() !== 'post') { show_404(); }

        $user_id = (int)$this->session->userdata('user_id');
        $description = $this->sanitize_activity_description($this->input->post('description'));
        $activity_title = trim($this->input->post('activity_title'));
        $task_id = (int)$this->input->post('task_id');
        $work_date = $this->input->post('work_date');

        if ($description === '') {
            $this->session->set_flashdata('error', 'Description is required');
            redirect('daily-activity?date=' . $work_date);
            return;
        }

        $uploads = daily_activity_handle_uploads();
        if ($uploads === false) {
            redirect('daily-activity?date=' . $work_date);
            return;
        }

        $data = [
            'user_id' => $user_id,
            'task_id' => $task_id > 0 ? $task_id : null,
            'activity_title' => !empty($activity_title) ? $activity_title : null,
            'work_date' => $work_date,
            'description' => $description, // Storing HTML content
            'created_at' => date('Y-m-d H:i:s')
        ];

        if($this->db->insert('daily_work_logs', $data)) {
            $log_id = (int) $this->db->insert_id();
            $this->save_new_attachments($log_id, $uploads);
            $this->load->helper('rewards_automation');
            if (function_exists('rewards_automation_after_daily_activity_saved')) {
                rewards_automation_after_daily_activity_saved($this->db, $user_id, $log_id, $work_date);
            }
            $this->load->helper('activity');
            if (function_exists('log_activity')) {
                $log_label = $activity_title !== '' ? $activity_title : ('Work date ' . $work_date);
                log_activity('daily_activity', 'created', $log_id, 'Daily activity created (log #' . $log_id . '): ' . $log_label);
            }
            $this->session->set_flashdata('success', 'Activity logged successfully. Reward points (if any) are pending admin approval.');
        } else {
             $this->session->set_flashdata('error', 'Database Error: Could not save activity.');
             foreach ($uploads as $u) {
                 daily_activity_delete_attachment_file($u['stored']);
             }
        }
        
        redirect('daily-activity?date=' . $work_date);
    }

    public function edit($id) {
        require_module_access(['daily_activity_edit', 'daily_activity'], true);
        $user_id  = (int)$this->session->userdata('user_id');
        $is_admin = is_admin_group() || has_module_access('daily_activity_edit_all');

        $log = $this->db->get_where('daily_work_logs', ['id' => (int)$id])->row();
        if (!$log) { show_404(); return; }

        // Only owner or admin can edit
        if ($log->user_id != $user_id && !$is_admin) {
            $this->session->set_flashdata('error', 'You can only edit your own activities.');
            redirect('daily-activity/list');
            return;
        }

        if ($this->input->method() === 'post') {
            if ((int)$log->user_id !== $user_id) {
                require_module_access(['daily_activity_edit', 'daily_activity'], true);
            }
            $description    = $this->sanitize_activity_description($this->input->post('description'));
            $activity_title = trim($this->input->post('activity_title'));
            $task_id        = (int)$this->input->post('task_id');
            $work_date      = $this->input->post('work_date');

            if ($description === '') {
                $this->session->set_flashdata('error', 'Description is required.');
                redirect('daily-activity/edit/' . $id);
                return;
            }

            $uploads = daily_activity_handle_uploads();
            if ($uploads === false) {
                redirect('daily-activity/edit/' . $id);
                return;
            }

            $this->db->where('id', (int)$id)->update('daily_work_logs', [
                'activity_title' => !empty($activity_title) ? $activity_title : null,
                'task_id'        => $task_id > 0 ? $task_id : null,
                'work_date'      => $work_date,
                'description'    => $description,
            ]);
            $this->process_remove_attachments((int) $id);
            $this->save_new_attachments((int) $id, $uploads);
            $this->load->helper('activity');
            if (function_exists('log_activity_with_changes')) {
                log_activity_with_changes(
                    'daily_activity',
                    'updated',
                    (int) $id,
                    array(
                        'activity_title' => $log->activity_title,
                        'task_id' => $log->task_id,
                        'work_date' => $log->work_date,
                    ),
                    array(
                        'activity_title' => !empty($activity_title) ? $activity_title : null,
                        'task_id' => $task_id > 0 ? $task_id : null,
                        'work_date' => $work_date,
                    ),
                    'Daily activity updated (log #' . (int) $id . ')'
                );
            }
            $this->session->set_flashdata('success', 'Activity updated successfully.');
            redirect('daily-activity?date=' . $work_date);
            return;
        }

        $tasks = $this->fetch_activity_tasks((int) $log->task_id);
        $activity_title_value = $this->resolve_activity_title_value($log);
        $summary_user_id = (int) $log->user_id > 0 ? (int) $log->user_id : (int) $this->session->userdata('user_id');
        $today_summary = $this->build_today_work_summary($summary_user_id, (string) $log->work_date);

        $this->load->view('daily_activity/edit', [
            'log' => $log,
            'tasks' => $tasks,
            'activity_title_value' => $activity_title_value,
            'today_summary' => $today_summary,
            'attachments' => daily_activity_attachments_for_log($this->db, (int) $log->id),
        ]);
    }

    public function export() {
        require_module_access(['daily_activity_export', 'daily_activity_report', 'daily_activity'], true);
        $user_id   = (int)$this->session->userdata('user_id');
        $is_admin = is_admin_group() || has_module_access('daily_activity_view_all');

        $date_from = $this->input->get('date_from') ? $this->input->get('date_from') : date('Y-m-01');
        $date_to   = $this->input->get('date_to')   ? $this->input->get('date_to')   : date('Y-m-d');
        $user_id   = (int)$this->session->userdata('user_id');

        $this->db->select('dl.work_date, dl.activity_title, dl.description, t.title as task_title, u.name as user_name, u.email as user_email, dl.created_at');
        $this->db->from('daily_work_logs dl');
        $this->db->join('tasks t', 't.id = dl.task_id', 'left');
        $this->db->join('users u', 'u.id = dl.user_id', 'left');
        apply_role_hierarchy_filter($this->db, 'dl.user_id');
        $this->db->where('dl.work_date >=', $date_from);
        $this->db->where('dl.work_date <=', $date_to);
        $this->db->order_by('dl.work_date', 'DESC');
        $this->db->order_by('dl.created_at', 'DESC');
        $this->db->order_by('dl.id', 'DESC');
        $logs = $this->db->get()->result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="daily_activity_' . $date_from . '_to_' . $date_to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Date', 'User', 'Email', 'Activity Title', 'Linked Task', 'Description', 'Logged At'), ',', '"', '\\');
        foreach ($logs as $row) {
            fputcsv($out, array(
                $row->work_date,
                $row->user_name,
                $row->user_email,
                $row->activity_title,
                $row->task_title,
                strip_tags($row->description),
                $row->created_at,
            ), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    public function delete($id) {
        $user_id = (int)$this->session->userdata('user_id');
        $is_admin = is_admin_group() || has_module_access('daily_activity_delete_all');

        $log = $this->db->get_where('daily_work_logs', ['id' => $id])->row();
        if (!$log) {
            $this->session->set_flashdata('error', 'Activity not found');
            redirect('daily-activity');
            return;
        }

        if ((int)$log->user_id !== $user_id && !$is_admin) {
            require_module_access(['daily_activity_delete', 'daily_activity'], true);
        }
        
        if ($log) {
            if ($log->user_id == $user_id || $is_admin) {
                $this->load->helper('activity');
                if (function_exists('log_activity_with_changes')) {
                    log_activity_with_changes(
                        'daily_activity',
                        'deleted',
                        (int) $id,
                        array(
                            'user_id' => (int) $log->user_id,
                            'activity_title' => $log->activity_title,
                            'work_date' => $log->work_date,
                        ),
                        null,
                        'Daily activity deleted (log #' . (int) $id . ')'
                    );
                }
                daily_activity_delete_all_attachments($this->db, (int) $id);
                $this->db->where('id', $id)->delete('daily_work_logs');
                $this->session->set_flashdata('success', 'Activity deleted successfully');
            } else {
                 $this->session->set_flashdata('error', 'Permission denied: You can only delete your own activities.');
            }
        } else {
            $this->session->set_flashdata('error', 'Activity not found');
        }
        
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        if (strpos($referer, 'list') !== false) {
             redirect('daily-activity/list');
        } else {
             redirect('daily-activity?date=' . ($log ? $log->work_date : date('Y-m-d')));
        }
    }

    public function attachment_download($id, $attachment_id)
    {
        require_module_access(array('daily_activity_list', 'daily_activity', 'daily_activity_add'), true);
        $log = $this->db->get_where('daily_work_logs', array('id' => (int) $id))->row();
        if (!$log || !$this->can_access_log($log)) {
            show_error('Access denied.', 403);
        }
        $att = daily_activity_attachment_find($this->db, (int) $id, (int) $attachment_id);
        if (!$att) {
            show_404();
        }
        $this->serve_attachment($att, false);
    }

    public function attachment_preview($id, $attachment_id)
    {
        require_module_access(array('daily_activity_list', 'daily_activity', 'daily_activity_add'), true);
        $log = $this->db->get_where('daily_work_logs', array('id' => (int) $id))->row();
        if (!$log || !$this->can_access_log($log)) {
            show_error('Access denied.', 403);
        }
        $att = daily_activity_attachment_find($this->db, (int) $id, (int) $attachment_id);
        if (!$att) {
            show_404();
        }
        if ($this->serve_attachment($att, true) === false) {
            redirect('daily-activity/' . (int) $id . '/attachment/' . (int) $attachment_id . '/download');
        }
    }

    /**
     * Summernote picture upload — JSON { url }.
     */
    public function upload_image()
    {
        require_module_access(array('daily_activity_add', 'daily_activity_edit', 'daily_activity'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $result = daily_activity_handle_image_upload('file');
        $this->output->set_content_type('application/json');
        if ($result === false) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array(
                'status' => 'error',
                'message' => 'Image upload failed. Use JPG, PNG, GIF, or WebP.',
            )));
            return;
        }
        $this->output->set_output(json_encode(array(
            'status' => 'success',
            'url' => $result['url'],
        )));
    }
}
