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

    private function resolve_activity_title_value($log, array $tasks)
    {
        $title = !empty($log->activity_title) ? trim((string) $log->activity_title) : '';
        if ($title !== '') {
            return $title;
        }
        $task_id = (int) $log->task_id;
        if ($task_id <= 0) {
            return '';
        }
        foreach ($tasks as $task) {
            if ((int) $task->id === $task_id) {
                return (string) $task->title;
            }
        }
        return '';
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

        // Fetch user's tasks for dropdown
        $tasks = $this->fetch_activity_tasks();

        $this->load->view('daily_activity/index', [
            'logs' => $logs,
            'date' => $date,
            'tasks' => $tasks,
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
        $activity_title_value = $this->resolve_activity_title_value($log, $tasks);

        $this->load->view('daily_activity/edit', [
            'log' => $log,
            'tasks' => $tasks,
            'activity_title_value' => $activity_title_value,
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
