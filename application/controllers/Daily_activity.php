<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daily_activity extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access(['activity', 'daily_activity'], true);
        
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
            if (!$this->db->field_exists('description', 'daily_work_logs')) {
                $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `description` TEXT NOT NULL AFTER `work_date`");
            }
            if (!$this->db->field_exists('task_id', 'daily_work_logs')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `task_id` int(11) DEFAULT NULL AFTER `user_id`");
            }
            if (!$this->db->field_exists('work_date', 'daily_work_logs')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `work_date` date NOT NULL AFTER `task_id`");
            }
            if (!$this->db->field_exists('activity_title', 'daily_work_logs')) {
                 $this->db->query("ALTER TABLE `daily_work_logs` ADD COLUMN `activity_title` varchar(255) DEFAULT NULL AFTER `task_id`");
            }

            // Ensure task_id allows NULL (Fix for error 1048)
            $this->db->query("ALTER TABLE `daily_work_logs` MODIFY `task_id` INT(11) DEFAULT NULL");
        }
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
        $this->db->where('dl.work_date', $date);
        $this->db->order_by('dl.created_at', 'DESC');
        $logs = $this->db->get()->result();

        // Fetch user's tasks for dropdown
        $current_user_id = $this->session->userdata('user_id');
        $is_admin = is_admin_group() || has_module_access('daily_activity_view_all');
        
        $this->db->select('id, title');
        $this->db->from('tasks');
        if (!$is_admin) {
             // Show tasks assigned to user or created by user
            $this->db->group_start();
            $this->db->where('assigned_to', $current_user_id);
            $this->db->or_where('created_by', $current_user_id);
            $this->db->group_end();
        }
        $this->db->where_in('status', ['pending', 'in_progress']); // Only active tasks
        $this->db->order_by('id', 'DESC');
        $tasks = $this->db->get()->result();

        $this->load->view('daily_activity/index', [
            'logs' => $logs,
            'date' => $date,
            'tasks' => $tasks
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
            if (!$is_admin) {
                $this->db->where('dl.user_id', (int)$this->session->userdata('user_id'));
            } elseif (!empty($filters['user_id'])) {
                $this->db->where('dl.user_id', $filters['user_id']);
            }
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
        $this->db->order_by('dl.created_at', 'DESC');
        $this->db->limit($config['per_page'], $offset);
        $logs = $this->db->get()->result();

        // Users for filter (Admin only)
        $users = [];
        if ($is_admin) {
            $users = $this->db->select('id, name, email')->from('users')->order_by('name')->get()->result();
        }

        $this->load->view('daily_activity/list', [
            'logs' => $logs,
            'is_admin' => $is_admin,
            'filters' => $filters,
            'users' => $users
        ]);
    }

    public function save() {
        require_module_access(['daily_activity_add', 'daily_activity'], true);
        if ($this->input->method() !== 'post') { show_404(); }

        $user_id = (int)$this->session->userdata('user_id');
        $description = trim($this->input->post('description', TRUE));
        $activity_title = trim($this->input->post('activity_title'));
        $task_id = (int)$this->input->post('task_id'); // Optional hidden or from datalist match?
        $work_date = $this->input->post('work_date');

        if (empty($description)) {
            $this->session->set_flashdata('error', 'Description is required');
            redirect('daily_activity?date=' . $work_date);
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
            $this->session->set_flashdata('success', 'Activity logged successfully');
        } else {
             $this->session->set_flashdata('error', 'Database Error: Could not save activity.');
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
            $description    = trim($this->input->post('description', TRUE));
            $activity_title = trim($this->input->post('activity_title'));
            $task_id        = (int)$this->input->post('task_id');
            $work_date      = $this->input->post('work_date');

            if (empty($description)) {
                $this->session->set_flashdata('error', 'Description is required.');
                redirect('daily-activity/edit/' . $id);
                return;
            }

            $this->db->where('id', (int)$id)->update('daily_work_logs', [
                'activity_title' => !empty($activity_title) ? $activity_title : null,
                'task_id'        => $task_id > 0 ? $task_id : null,
                'work_date'      => $work_date,
                'description'    => $description,
            ]);
            $this->session->set_flashdata('success', 'Activity updated successfully.');
            redirect('daily-activity?date=' . $work_date);
            return;
        }

        // Fetch tasks for dropdown
        $this->db->select('id, title')->from('tasks');
        if (!$is_admin) {
            $this->db->group_start();
            $this->db->where('assigned_to', $user_id);
            $this->db->or_where('created_by', $user_id);
            $this->db->group_end();
        }
        $tasks = $this->db->order_by('id', 'DESC')->get()->result();

        $this->load->view('daily_activity/edit', [
            'log'   => $log,
            'tasks' => $tasks,
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
        if (!$is_admin) { $this->db->where('dl.user_id', $user_id); }
        $this->db->where('dl.work_date >=', $date_from);
        $this->db->where('dl.work_date <=', $date_to);
        $this->db->order_by('dl.work_date', 'DESC');
        $logs = $this->db->get()->result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="daily_activity_' . $date_from . '_to_' . $date_to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'User', 'Email', 'Activity Title', 'Linked Task', 'Description', 'Logged At']);
        foreach ($logs as $row) {
            fputcsv($out, [
                $row->work_date,
                $row->user_name,
                $row->user_email,
                $row->activity_title,
                $row->task_title,
                strip_tags($row->description),
                $row->created_at,
            ]);
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
                $this->db->where('id', $id)->delete('daily_work_logs');
                $this->session->set_flashdata('success', 'Activity deleted successfully');
            } else {
                 $this->session->set_flashdata('error', 'Permission denied: You can only delete your own activities.');
            }
        } else {
            $this->session->set_flashdata('error', 'Activity not found');
        }
        
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if(strpos($referer, 'list_all') !== false) {
             redirect('daily_activity/list_all');
        } else {
             redirect('daily_activity?date=' . ($log ? $log->work_date : date('Y-m-d')));
        }
    }
}
