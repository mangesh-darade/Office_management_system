<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My_works extends CI_Controller
{
    private $upload_dir = 'uploads/my_works/';
    /** Max rows loaded for list/board (filters + scope applied). DataTables paginates client-side on list view. */
    private $list_cap = 2000;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'hierarchy_filter', 'data_scope', 'my_works', 'download'));
        $this->load->library(array('session', 'upload'));
        $this->load->model('My_work_model', 'my_works');
        require_module_access(array('my_works', 'my_works_list'), true);
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        if (!$this->db->table_exists('my_works')) {
            $this->db->query("CREATE TABLE `my_works` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `details` text DEFAULT NULL,
                `tag` varchar(255) DEFAULT NULL,
                `url` varchar(500) DEFAULT NULL,
                `attachment_original` varchar(255) DEFAULT NULL,
                `attachment_stored` varchar(255) DEFAULT NULL,
                `created_by` int(11) unsigned NOT NULL,
                `created_for` int(11) unsigned NOT NULL,
                `status` enum('new','in_progress','closed') NOT NULL DEFAULT 'new',
                `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
                `is_important` tinyint(1) NOT NULL DEFAULT 0,
                `due_date` date DEFAULT NULL,
                `task_id` int(11) unsigned DEFAULT NULL,
                `closed_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_my_works_created_by` (`created_by`),
                KEY `idx_my_works_created_for` (`created_for`),
                KEY `idx_my_works_status` (`status`),
                KEY `idx_my_works_due` (`due_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!$this->db->field_exists('due_date', 'my_works')) {
                $this->db->query('ALTER TABLE `my_works` ADD `due_date` date DEFAULT NULL AFTER `is_important`');
            }
            if (!$this->db->field_exists('task_id', 'my_works')) {
                $this->db->query('ALTER TABLE `my_works` ADD `task_id` int(11) unsigned DEFAULT NULL AFTER `due_date`');
            }
            if ($this->db->field_exists('tag', 'my_works')) {
                $this->db->query('ALTER TABLE `my_works` MODIFY `tag` varchar(255) DEFAULT NULL');
            }
        }
        if (!$this->db->table_exists('my_work_activity')) {
            $this->db->query("CREATE TABLE `my_work_activity` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `action` varchar(50) NOT NULL,
                `detail` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mwa_work` (`work_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->table_exists('my_work_comments')) {
            $this->db->query("CREATE TABLE `my_work_comments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `comment` text NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mwc_work` (`work_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $dir = FCPATH . $this->upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private function _current_user_id()
    {
        return (int) $this->session->userdata('user_id');
    }

    private function _can_view_all()
    {
        return my_works_sees_all_org_data();
    }

    /** Personal list scope: created by me OR assigned to me. Admin/view-all: no filter. */
    private function _apply_list_scope()
    {
        if ($this->_can_view_all()) {
            return;
        }
        $uid = $this->_current_user_id();
        if ($uid < 1) {
            $this->db->where('1 = 0', null, false);
            return;
        }
        $this->db->group_start();
        $this->db->where('w.created_by', $uid);
        $this->db->or_where('w.created_for', $uid);
        $this->db->group_end();
    }

    private function _user_can_access($work)
    {
        if (!$work) {
            return false;
        }
        if ($this->_can_view_all()) {
            return true;
        }
        $uid = $this->_current_user_id();
        if ($uid < 1) {
            return false;
        }
        return ((int) $work->created_by === $uid) || ((int) $work->created_for === $uid);
    }

    private function _require_access($work)
    {
        if (!$this->_user_can_access($work)) {
            show_error('You do not have permission to access this work item.', 403);
        }
    }

    private function _assignable_user_ids()
    {
        if ($this->_can_view_all()) {
            return null;
        }
        $uid = $this->_current_user_id();
        $role_id = (int) $this->session->userdata('role_id');
        $allowed = get_accessible_hierarchy_user_ids($uid, $role_id);
        if (empty($allowed)) {
            return array($uid);
        }
        if (!in_array($uid, $allowed, true)) {
            $allowed[] = $uid;
        }
        return array_values(array_unique(array_map('intval', $allowed)));
    }

    private function _user_in_assign_scope($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }
        if ($this->_can_view_all()) {
            return true;
        }
        if ($user_id === $this->_current_user_id()) {
            return true;
        }
        $allowed = $this->_assignable_user_ids();
        return is_array($allowed) && in_array($user_id, $allowed, true);
    }

    private function _user_options()
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }
        $this->db->select('id, name, email');
        $this->db->from('users');
        if ($this->db->field_exists('status', 'users')) {
            $this->db->where('status', 'active');
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    private function _assignable_users()
    {
        $users = $this->_user_options();
        $allowed = $this->_assignable_user_ids();
        if ($allowed === null) {
            return $users;
        }
        $out = array();
        foreach ($users as $u) {
            if (in_array((int) $u->id, $allowed, true)) {
                $out[] = $u;
            }
        }
        return $out;
    }

    private function _filter_users_for_dropdown()
    {
        return $this->_can_view_all() ? $this->_user_options() : $this->_assignable_users();
    }

    private function _scope_context()
    {
        return my_works_scope_context($this->_can_view_all(), array($this->_current_user_id()));
    }

    private function _parse_filters()
    {
        return array(
            'status' => trim((string) $this->input->get('status')),
            'tag' => trim((string) $this->input->get('tag')),
            'q' => trim((string) $this->input->get('q')),
            'created_for' => (int) $this->input->get('created_for'),
            'created_by' => (int) $this->input->get('created_by'),
            'involvement' => trim((string) $this->input->get('involvement')),
            'urgent_only' => $this->input->get('urgent_only') ? 1 : 0,
            'important_only' => $this->input->get('important_only') ? 1 : 0,
            'overdue_only' => $this->input->get('overdue_only') ? 1 : 0,
            'current_user_id' => $this->_current_user_id(),
        );
    }

    private function _sanitize_filters(array $filters)
    {
        if (!$this->_can_view_all()) {
            if ($filters['created_for'] > 0 && (int) $filters['created_for'] !== $this->_current_user_id()) {
                $filters['created_for'] = 0;
            }
            $filters['created_by'] = 0;
        }
        if (!in_array($filters['involvement'], array('all', 'created', 'assigned'), true)) {
            $filters['involvement'] = 'all';
        }
        return $filters;
    }

    private function _apply_filters_to_query(array $filters, $include_status = true)
    {
        if ($include_status && $filters['status'] !== '' && in_array($filters['status'], array('new', 'in_progress', 'closed'), true)) {
            $this->db->where('w.status', $filters['status']);
        }
        if ($filters['tag'] !== '') {
            $this->db->like('w.tag', $filters['tag']);
        }
        if ($filters['created_for'] > 0) {
            $this->db->where('w.created_for', $filters['created_for']);
        }
        if ($filters['created_by'] > 0) {
            $this->db->where('w.created_by', $filters['created_by']);
        }
        if ($filters['involvement'] === 'created') {
            $this->db->where('w.created_by', $filters['current_user_id']);
        } elseif ($filters['involvement'] === 'assigned') {
            $this->db->where('w.created_for', $filters['current_user_id']);
        }
        if ($filters['q'] !== '') {
            $this->db->group_start()
                ->like('w.title', $filters['q'])
                ->or_like('w.details', $filters['q'])
                ->or_like('w.tag', $filters['q'])
            ->group_end();
        }
        if ($filters['urgent_only']) {
            $this->db->where('w.is_urgent', 1);
        }
        if ($filters['important_only']) {
            $this->db->where('w.is_important', 1);
        }
        if ($filters['overdue_only'] && $this->db->field_exists('due_date', 'my_works')) {
            $this->db->where('w.due_date IS NOT NULL', null, false);
            $this->db->where('w.due_date <', date('Y-m-d'));
            $this->db->where('w.status !=', 'closed');
        }
    }

    private function _count_rows(array $filters)
    {
        $this->db->from('my_works w');
        $this->_apply_list_scope();
        $this->_apply_filters_to_query($filters, true);
        return (int) $this->db->count_all_results();
    }

    private function _fetch_rows(array $filters, $limit = null, $offset = 0)
    {
        $this->db->select('w.*, cb.name AS created_by_name, cb.email AS created_by_email, cf.name AS created_for_name, cf.email AS created_for_email', false);
        if ($this->db->table_exists('tasks') && $this->db->field_exists('task_id', 'my_works')) {
            $this->db->select('t.title AS linked_task_title', false);
        }
        $this->db->from('my_works w');
        $this->db->join('users cb', 'cb.id = w.created_by', 'left');
        $this->db->join('users cf', 'cf.id = w.created_for', 'left');
        if ($this->db->table_exists('tasks') && $this->db->field_exists('task_id', 'my_works')) {
            $this->db->join('tasks t', 't.id = w.task_id', 'left');
        }
        $this->_apply_list_scope();
        $this->_apply_filters_to_query($filters, true);
        $this->db->order_by('w.is_urgent', 'DESC');
        $this->db->order_by('w.is_important', 'DESC');
        if ($this->db->field_exists('due_date', 'my_works')) {
            $this->db->order_by('w.due_date', 'ASC');
        }
        $this->db->order_by('w.updated_at', 'DESC');
        $this->db->order_by('w.id', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get()->result();
    }

    private function _fetch_stats(array $filters)
    {
        $stats = array('total' => 0, 'new' => 0, 'in_progress' => 0, 'closed' => 0, 'urgent' => 0, 'overdue' => 0, 'assigned_to_me' => 0);
        $uid = $filters['current_user_id'];
        foreach (array('new', 'in_progress', 'closed') as $st) {
            $this->db->from('my_works w');
            $this->_apply_list_scope();
            $tmp = $filters;
            $tmp['status'] = '';
            $this->_apply_filters_to_query($tmp, false);
            $this->db->where('w.status', $st);
            $stats[$st] = (int) $this->db->count_all_results();
        }
        $this->db->from('my_works w');
        $this->_apply_list_scope();
        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        $this->_apply_filters_to_query($tmp, false);
        $stats['total'] = (int) $this->db->count_all_results();

        $this->db->from('my_works w');
        $this->_apply_list_scope();
        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        $this->_apply_filters_to_query($tmp, false);
        $this->db->where('w.is_urgent', 1);
        $this->db->where('w.status !=', 'closed');
        $stats['urgent'] = (int) $this->db->count_all_results();

        if ($this->db->field_exists('due_date', 'my_works')) {
            $this->db->from('my_works w');
            $this->_apply_list_scope();
            $tmp = $filters;
            $tmp['status'] = '';
            $tmp['overdue_only'] = 0;
            $this->_apply_filters_to_query($tmp, false);
            $this->db->where('w.due_date IS NOT NULL', null, false);
            $this->db->where('w.due_date <', date('Y-m-d'));
            $this->db->where('w.status !=', 'closed');
            $stats['overdue'] = (int) $this->db->count_all_results();
        }

        if ($uid > 0) {
            $this->db->from('my_works w');
            $this->_apply_list_scope();
            $this->db->where('w.created_for', $uid);
            $this->db->where('w.status !=', 'closed');
            $stats['assigned_to_me'] = (int) $this->db->count_all_results();
        }
        return $stats;
    }

    private function _list_view_data(array $filters, $view_mode)
    {
        $total = $this->_count_rows($filters);
        $rows = $this->_fetch_rows($filters, $this->list_cap, 0);
        $stats = $this->_fetch_stats($filters);
        $columns = array('new' => array(), 'in_progress' => array(), 'closed' => array());
        foreach ($rows as $row) {
            $st = isset($row->status) ? (string) $row->status : 'new';
            if (!isset($columns[$st])) {
                $st = 'new';
            }
            $columns[$st][] = $row;
        }
        return array(
            'rows' => $rows,
            'filters' => $filters,
            'stats' => $stats,
            'columns' => $columns,
            'total_rows' => $total,
            'list_capped' => ($total > count($rows)),
            'list_shown_count' => count($rows),
            'tags' => $this->my_works->distinct_tags_scoped(array($this, '_apply_list_scope')),
            'users' => $this->_filter_users_for_dropdown(),
            'can_view_all' => $this->_can_view_all(),
            'can_filter_users' => $this->_can_view_all(),
            'can_export' => function_exists('has_module_access') && (has_module_access('my_works_export') || has_module_access('my_works')),
            'scope' => $this->_scope_context(),
            'view_mode' => $view_mode,
            'can_add' => function_exists('has_module_access') && (has_module_access('my_works_add') || has_module_access('my_works')),
            'can_quick_edit' => function_exists('has_module_access') && (has_module_access('my_works_edit') || has_module_access('my_works')),
        );
    }

    private function _clear_dashboard_cache()
    {
        if (function_exists('clear_dashboard_cache')) {
            clear_dashboard_cache($this->_current_user_id(), (int) $this->session->userdata('role_id'));
        }
    }

    private function _handle_upload($existing = null)
    {
        if (empty($_FILES['attachment']['name'])) {
            return array(null, null);
        }
        $dir = FCPATH . $this->upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $config = array(
            'upload_path' => $dir,
            'allowed_types' => 'gif|jpg|jpeg|png|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|csv',
            'max_size' => 10240,
            'encrypt_name' => true,
        );
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('attachment')) {
            $this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
            return false;
        }
        $data = $this->upload->data();
        if ($existing && !empty($existing->attachment_stored)) {
            $old = FCPATH . $this->upload_dir . $existing->attachment_stored;
            if (is_file($old)) {
                @unlink($old);
            }
        }
        return array($data['orig_name'], $data['file_name']);
    }

    private function _validate_created_for($created_for)
    {
        $created_for = (int) $created_for;
        $uid = $this->_current_user_id();
        if ($created_for <= 0) {
            return $uid > 0 ? $uid : false;
        }
        if (!$this->_user_in_assign_scope($created_for)) {
            $this->session->set_flashdata('error', 'You cannot assign work to that user.');
            return false;
        }
        return $created_for;
    }

    private function _validate_payload($is_edit = false, $existing = null)
    {
        $title = trim((string) $this->input->post('title'));
        if ($title === '') {
            $this->session->set_flashdata('error', 'Task title is required.');
            return false;
        }
        $created_for = $this->_validate_created_for((int) $this->input->post('created_for'));
        if ($created_for === false) {
            return false;
        }
        $status = trim((string) $this->input->post('status'));
        if (!in_array($status, array('new', 'in_progress', 'closed'), true)) {
            $status = 'new';
        }
        $url = trim((string) $this->input->post('url'));
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
            return false;
        }
        $due = trim((string) $this->input->post('due_date'));
        $due_date = null;
        if ($due !== '') {
            $parts = explode('-', $due);
            if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                $due_date = $due;
            }
        }
        $task_id = (int) $this->input->post('task_id');
        if ($task_id <= 0) {
            $task_id = null;
        }
        return array(
            'title' => $title,
            'details' => trim((string) $this->input->post('details')),
            'tag' => my_works_normalize_tags($this->input->post('tag')),
            'url' => $url !== '' ? $url : null,
            'created_for' => $created_for,
            'status' => $status,
            'is_urgent' => $this->input->post('is_urgent') ? 1 : 0,
            'is_important' => $this->input->post('is_important') ? 1 : 0,
            'due_date' => $due_date,
            'task_id' => $task_id,
        );
    }

    private function _flash_form_old()
    {
        $this->session->set_flashdata('mw_form_old', array(
            'title' => $this->input->post('title'),
            'details' => $this->input->post('details'),
            'tag' => $this->input->post('tag'),
            'url' => $this->input->post('url'),
            'created_for' => $this->input->post('created_for'),
            'status' => $this->input->post('status'),
            'due_date' => $this->input->post('due_date'),
            'task_id' => $this->input->post('task_id'),
            'is_urgent' => $this->input->post('is_urgent'),
            'is_important' => $this->input->post('is_important'),
        ));
    }

    public function index()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $view_mode = trim((string) $this->input->get('view'));
        if (!in_array($view_mode, array('list', 'board'), true)) {
            $view_mode = 'list';
        }
        $data = $this->_list_view_data($filters, $view_mode);
        $this->load->view($view_mode === 'board' ? 'my_works/board' : 'my_works/list', $data);
    }

    public function export()
    {
        require_module_access(array('my_works_export', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $rows = $this->_fetch_rows($filters, null, 0);
        $filename = 'my_works_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('ID', 'Title', 'Status', 'Tag', 'Due date', 'Urgent', 'Important', 'Created by', 'Created for', 'Updated'));
        $labels = my_works_status_labels();
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r->id,
                $r->title,
                isset($labels[$r->status]) ? $labels[$r->status] : $r->status,
                $r->tag,
                isset($r->due_date) ? $r->due_date : '',
                (int) $r->is_urgent ? 'Yes' : 'No',
                (int) $r->is_important ? 'Yes' : 'No',
                my_works_user_label($r->created_by_name, $r->created_by_email, $r->created_by),
                my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for),
                $r->updated_at,
            ));
        }
        fclose($out);
        exit;
    }

    public function create()
    {
        require_module_access(array('my_works_add', 'my_works'), true);
        $old = $this->session->flashdata('mw_form_old');
        if ($this->input->method() === 'post') {
            $payload = $this->_validate_payload();
            if ($payload === false) {
                $this->_flash_form_old();
                redirect('my-works/create');
                return;
            }
            $upload = $this->_handle_upload();
            if ($upload === false) {
                $this->_flash_form_old();
                redirect('my-works/create');
                return;
            }
            list($orig, $stored) = $upload;
            $payload['created_by'] = $this->_current_user_id();
            $payload['attachment_original'] = $orig;
            $payload['attachment_stored'] = $stored;
            $id = $this->my_works->insert($payload);
            $this->my_works->log_activity($id, $this->_current_user_id(), 'created', 'Work item created');
            my_works_notify_assignee($id, $payload['created_for'], $payload['title'], $payload['created_by']);
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item created.');
            redirect('my-works/' . $id);
            return;
        }
        $this->load->view('my_works/form', array(
            'action' => 'create',
            'item' => $old ? (object) $old : null,
            'users' => $this->_assignable_users(),
            'tags' => $this->my_works->distinct_tags_scoped(array($this, '_apply_list_scope')),
            'scope' => $this->_scope_context(),
            'tasks' => $this->_tasks_for_link(),
        ));
    }

    public function show($id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $this->db->select('name, email')->from('users')->where('id', (int) $item->created_by);
        $creator = $this->db->get()->row();
        $this->db->select('name, email')->from('users')->where('id', (int) $item->created_for);
        $assignee = $this->db->get()->row();
        $uid = $this->_current_user_id();
        $this->load->view('my_works/view', array(
            'item' => $item,
            'creator' => $creator,
            'assignee' => $assignee,
            'can_edit' => $this->_can_edit_full($item),
            'can_update_status' => $this->_can_update_status($item),
            'can_delete' => $this->_can_delete($item),
            'can_comment' => $this->_user_can_access($item),
            'is_assignee' => ((int) $item->created_for === $uid),
            'is_creator' => ((int) $item->created_by === $uid),
            'scope' => $this->_scope_context(),
            'comments' => $this->my_works->list_comments((int) $id),
            'activity' => $this->my_works->list_activity((int) $id),
            'linked_task' => $this->_linked_task($item),
        ));
    }

    public function edit($id)
    {
        require_module_access(array('my_works_edit', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        if (!$this->_can_edit_full($item)) {
            show_error('You do not have permission to edit this work item.', 403);
        }
        if ($this->input->method() === 'post') {
            $payload = $this->_validate_payload(true, $item);
            if ($payload === false) {
                redirect('my-works/' . (int) $id . '/edit');
                return;
            }
            $upload = $this->_handle_upload($item);
            if ($upload === false) {
                redirect('my-works/' . (int) $id . '/edit');
                return;
            }
            list($orig, $stored) = $upload;
            if ($orig !== null) {
                $payload['attachment_original'] = $orig;
                $payload['attachment_stored'] = $stored;
            }
            if ($this->input->post('remove_attachment')) {
                if (!empty($item->attachment_stored)) {
                    $path = FCPATH . $this->upload_dir . $item->attachment_stored;
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                $payload['attachment_original'] = null;
                $payload['attachment_stored'] = null;
            }
            $prev_for = (int) $item->created_for;
            $prev_status = (string) $item->status;
            $this->my_works->update((int) $id, $payload);
            if ($prev_status !== $payload['status']) {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'status', $prev_status . ' → ' . $payload['status']);
            }
            if ($prev_for !== (int) $payload['created_for']) {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'reassigned', 'Assignee changed');
                my_works_notify_assignee((int) $id, (int) $payload['created_for'], $payload['title'], $this->_current_user_id());
            } else {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'updated', 'Details updated');
            }
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item updated.');
            redirect('my-works/' . (int) $id);
            return;
        }
        $this->load->view('my_works/form', array(
            'action' => 'edit',
            'item' => $item,
            'users' => $this->_assignable_users(),
            'tags' => $this->my_works->distinct_tags_scoped(array($this, '_apply_list_scope')),
            'scope' => $this->_scope_context(),
            'tasks' => $this->_tasks_for_link(),
        ));
    }

    public function add_comment($id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $comment = trim((string) $this->input->post('comment'));
        if ($comment === '') {
            $this->session->set_flashdata('error', 'Comment cannot be empty.');
            redirect('my-works/' . (int) $id);
            return;
        }
        $this->my_works->add_comment((int) $id, $this->_current_user_id(), $comment);
        $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'comment', 'Added a comment');
        $this->session->set_flashdata('success', 'Comment added.');
        redirect('my-works/' . (int) $id);
    }

    public function delete($id)
    {
        require_module_access(array('my_works_delete', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        if (!$this->_can_delete($item)) {
            show_error('You do not have permission to delete this work item.', 403);
        }
        if (!empty($item->attachment_stored)) {
            $path = FCPATH . $this->upload_dir . $item->attachment_stored;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->my_works->delete((int) $id);
        $this->_clear_dashboard_cache();
        $this->session->set_flashdata('success', 'Work item deleted.');
        redirect('my-works');
    }

    public function download($id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item || empty($item->attachment_stored)) {
            show_404();
        }
        $this->_require_access($item);
        $path = FCPATH . $this->upload_dir . $item->attachment_stored;
        if (!is_file($path)) {
            show_error('File not found.', 404);
        }
        force_download($item->attachment_original ? $item->attachment_original : $item->attachment_stored, file_get_contents($path));
    }

    public function update_status()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $status = trim((string) $this->input->post('status'));
        if (!in_array($status, array('new', 'in_progress', 'closed'), true)) {
            show_error('Invalid status.', 400);
        }
        $item = $this->my_works->find($id);
        if (!$item) {
            show_404();
        }
        if (!$this->_can_update_status($item)) {
            show_error('Access denied.', 403);
        }
        $prev = (string) $item->status;
        $this->my_works->update($id, array('status' => $status));
        if ($prev !== $status) {
            $this->my_works->log_activity($id, $this->_current_user_id(), 'status', $prev . ' → ' . $status);
        }
        $this->_clear_dashboard_cache();
        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')->set_output(json_encode(array('ok' => true, 'status' => $status)));
            return;
        }
        $this->session->set_flashdata('success', 'Status updated.');
        $redirect = my_works_safe_redirect($this->input->post('redirect'), 'my-works/' . $id);
        redirect($redirect);
    }

    private function _tasks_for_link()
    {
        if (!$this->db->table_exists('tasks')) {
            return array();
        }
        $uid = $this->_current_user_id();
        $this->db->select('id, title');
        $this->db->from('tasks');
        if (!$this->_can_view_all() && $uid > 0) {
            $this->db->group_start();
            $this->db->where('assigned_to', $uid);
            $this->db->or_where('created_by', $uid);
            $this->db->group_end();
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit(200);
        return $this->db->get()->result();
    }

    private function _linked_task($item)
    {
        if (empty($item->task_id) || !$this->db->table_exists('tasks')) {
            return null;
        }
        return $this->db->get_where('tasks', array('id' => (int) $item->task_id))->row();
    }

    private function _can_edit_full($item)
    {
        if (!function_exists('has_module_access') || !(has_module_access('my_works_edit') || has_module_access('my_works'))) {
            return false;
        }
        return $this->_user_can_access($item);
    }

    private function _can_update_status($item)
    {
        if ($this->_can_edit_full($item)) {
            return true;
        }
        return $this->_current_user_id() > 0 && (int) $item->created_for === $this->_current_user_id();
    }

    private function _can_delete($item)
    {
        if (!function_exists('has_module_access') || !(has_module_access('my_works_delete') || has_module_access('my_works'))) {
            return false;
        }
        if ($this->_can_view_all()) {
            return true;
        }
        return (int) $item->created_by === $this->_current_user_id();
    }
}
