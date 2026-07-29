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
        $this->load->helper('schema_columns');
        $this->load->helper(array(
            'url', 'form', 'permission', 'hierarchy_filter', 'data_scope',
            'my_works', 'my_works_status', 'my_works_access', 'my_works_query', 'my_works_form',
            'my_works_attachment', 'my_works_daily_pulse', 'download', 'estimate_hours', 'multi_assignee',
        ));
        $this->load->library(array('session', 'upload'));
        $this->load->model('My_work_model', 'my_works');
        $this->load->model('Template_task_model', 'template_tasks');
        require_module_access(array('my_works', 'my_works_list', 'my_works_add'), true);
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        $this->load->helper('my_works_schema');
        my_works_schema_ensure($this->db);
        multi_assignees_ensure_schema($this->db);
        $this->load->model('Type_model', 'module_types');
        $dir = FCPATH . $this->upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * True when the request is inside Second Brain embed panes.
     *
     * @return bool
     */
    private function _wants_embed()
    {
        $v = $this->input->get_post('embed');
        return ($v === '1' || $v === 1 || $v === true || $v === 'true');
    }

    /**
     * Redirect while keeping embed=1 (+ parent_tab) for unified tab AJAX.
     * Accepts CI-relative URIs, absolute paths, or full site URLs without doubling base_url.
     *
     * @param string $uri
     * @return void
     */
    private function _redirect_with_embed($uri)
    {
        $uri = trim((string) $uri);
        if ($uri === '') {
            $uri = 'my-works';
        }

        $parts = parse_url($uri);
        $path = '';
        $query = array();
        if (is_array($parts)) {
            if (isset($parts['path'])) {
                $path = (string) $parts['path'];
            } elseif (!preg_match('#^https?://#i', $uri)) {
                $qpos = strpos($uri, '?');
                $path = $qpos === false ? $uri : substr($uri, 0, $qpos);
            }
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }
        } else {
            $path = $uri;
        }

        $path = $this->_uri_relative_to_app($path);
        if ($path === '') {
            $path = 'my-works';
        }

        if ($this->_wants_embed()) {
            $query['embed'] = '1';
            $parent = trim((string) $this->input->get_post('parent_tab'));
            if ($parent !== '') {
                $query['parent_tab'] = $parent;
            }
        }

        if (!empty($query)) {
            $path .= (strpos($path, '?') === false ? '?' : '&') . http_build_query($query);
        }
        redirect($path);
    }

    /**
     * Strip install subdirectory / leading slash so redirect()/site_url() do not double base_url.
     *
     * @param string $path
     * @return string
     */
    private function _uri_relative_to_app($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $base_path = parse_url(base_url(), PHP_URL_PATH);
        $base_path = is_string($base_path) ? rtrim($base_path, '/') : '';
        if ($base_path !== '' && $base_path !== '/') {
            if ($path === $base_path) {
                return '';
            }
            if (strpos($path, $base_path . '/') === 0) {
                $path = substr($path, strlen($base_path) + 1);
            }
        }

        return ltrim($path, '/');
    }

    /** @return array{user_id:int,role_id:int,can_view_all:bool} */
    private function _ctx()
    {
        return array(
            'user_id'      => (int) $this->session->userdata('user_id'),
            'role_id'      => (int) $this->session->userdata('role_id'),
            'can_view_all' => my_works_sees_all_org_data(),
        );
    }

    private function _current_user_id()
    {
        return $this->_ctx()['user_id'];
    }

    private function _can_view_all()
    {
        return $this->_ctx()['can_view_all'];
    }

    /** Personal list scope callback for My_work_model::distinct_tags_scoped(). */
    private function _apply_list_scope()
    {
        $c = $this->_ctx();
        my_works_apply_list_scope($this->db, $c['can_view_all'], $c['user_id']);
    }

    private function _user_can_access($work)
    {
        $c = $this->_ctx();
        return my_works_user_can_access($work, $c['can_view_all'], $c['user_id']);
    }

    private function _require_access($work)
    {
        $c = $this->_ctx();
        my_works_require_access($work, $c['can_view_all'], $c['user_id']);
    }

    private function _assignable_users()
    {
        $c = $this->_ctx();
        return my_works_assignable_users($this->db, $c['can_view_all'], $c['user_id'], $c['role_id']);
    }

    private function _scope_context()
    {
        $c = $this->_ctx();
        return my_works_scope_context($c['can_view_all'], array($c['user_id']));
    }

    private function _parse_filters()
    {
        return my_works_parse_filters($this->input, $this->_current_user_id());
    }

    private function _sanitize_filters(array $filters)
    {
        $c = $this->_ctx();
        return my_works_sanitize_filters($filters, $c['can_view_all'], $c['user_id']);
    }

    private function _fetch_rows(array $filters, $limit = null, $offset = 0)
    {
        $c = $this->_ctx();
        return my_works_fetch_rows($this->db, $filters, $c['can_view_all'], $c['user_id'], $limit, $offset);
    }

    private function _list_view_data(array $filters, $view_mode)
    {
        $c = $this->_ctx();
        return my_works_list_view_data(
            $this->db,
            $this->my_works,
            $filters,
            $view_mode,
            $this->list_cap,
            $c['can_view_all'],
            $c['user_id'],
            $c['role_id'],
            array($this, '_apply_list_scope')
        );
    }

    /**
     * Overview / lane focus: my_works rows + project-linked Tasks module rows.
     *
     * @param array $rows
     * @param array $filters
     * @return array
     */
    private function _overview_rows_with_project_tasks(array $rows, array $filters)
    {
        $c = $this->_ctx();
        $task_rows = my_works_fetch_overview_project_tasks(
            $this->db,
            $filters,
            $c['can_view_all'],
            $c['user_id'],
            $this->list_cap
        );
        if (empty($task_rows)) {
            return $rows;
        }
        return array_merge($rows, $task_rows);
    }

    private function _clear_dashboard_cache()
    {
        $c = $this->_ctx();
        my_works_clear_dashboard_cache($c['user_id'], $c['role_id']);
    }

    private function _handle_uploads()
    {
        return my_works_handle_uploads($this->upload_dir);
    }

    private function _save_new_attachments($work_id, array $uploads)
    {
        $work_id = (int) $work_id;
        if ($work_id < 1 || empty($uploads)) {
            return;
        }
        $sort = $this->my_works->max_attachment_sort($work_id);
        foreach ($uploads as $upload) {
            $sort++;
            $this->my_works->insert_attachment(
                $work_id,
                $upload['original'],
                $upload['stored'],
                isset($upload['size']) ? (int) $upload['size'] : 0,
                $sort
            );
        }
        my_works_sync_legacy_attachment_columns($this->db, $work_id);
    }

    private function _process_remove_attachments($work_id)
    {
        $work_id = (int) $work_id;
        $remove_ids = $this->input->post('remove_attachments');
        if (!is_array($remove_ids) || $work_id < 1) {
            if ($this->input->post('remove_attachment')) {
                $item = $this->my_works->find($work_id);
                if ($item) {
                    $atts = $this->my_works->list_attachments($work_id);
                    foreach ($atts as $att) {
                        my_works_delete_attachment_file($att->stored_name);
                        $this->my_works->delete_attachment($work_id, (int) $att->id);
                    }
                    if (empty($atts) && !empty($item->attachment_stored)) {
                        my_works_delete_attachment_file($item->attachment_stored);
                    }
                    my_works_sync_legacy_attachment_columns($this->db, $work_id);
                }
            }
            return;
        }
        foreach ($remove_ids as $raw_id) {
            $att_id = (int) $raw_id;
            if ($att_id < 1) {
                continue;
            }
            $att = $this->my_works->find_attachment($work_id, $att_id);
            if ($att) {
                my_works_delete_attachment_file($att->stored_name);
                $this->my_works->delete_attachment($work_id, $att_id);
            }
        }
        my_works_sync_legacy_attachment_columns($this->db, $work_id);
    }

    private function _delete_all_attachment_files($work_id, $item = null)
    {
        $work_id = (int) $work_id;
        $atts = $this->my_works->list_attachments($work_id);
        foreach ($atts as $att) {
            my_works_delete_attachment_file($att->stored_name);
        }
        if ($item && !empty($item->attachment_stored)) {
            my_works_delete_attachment_file($item->attachment_stored);
        }
    }

    private function _resolve_legacy_attachment($work_id, $item)
    {
        $atts = my_works_attachments_for_work($this->db, (int) $work_id);
        if (!empty($atts)) {
            return $this->my_works->find_attachment((int) $work_id, (int) $atts[0]['id']);
        }
        if ($item && !empty($item->attachment_stored)) {
            return (object) array(
                'id'            => 0,
                'work_id'       => (int) $work_id,
                'original_name' => $item->attachment_original,
                'stored_name'   => $item->attachment_stored,
                'file_size'     => 0,
            );
        }
        return null;
    }

    private function _serve_attachment($att, $inline = false)
    {
        if (!$att || empty($att->stored_name)) {
            show_404();
        }
        $path = FCPATH . $this->upload_dir . $att->stored_name;
        if (!is_file($path)) {
            show_error('File not found.', 404);
        }
        $name = !empty($att->original_name) ? (string) $att->original_name : (string) $att->stored_name;
        if (!$inline) {
            force_download($name, file_get_contents($path));
            return;
        }
        $kind = my_works_attachment_kind($name, (string) $att->stored_name);
        if (!in_array($kind, array('video', 'image', 'audio'), true)) {
            return false;
        }
        $mime = my_works_attachment_mime_type($name);
        $size = filesize($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Accept-Ranges: bytes');
        header('Content-Disposition: inline; filename="' . basename($name) . '"');
        if ($kind === 'video' || $kind === 'audio') {
            $range = isset($_SERVER['HTTP_RANGE']) ? (string) $_SERVER['HTTP_RANGE'] : '';
            if ($range !== '' && preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
                $start = (int) $m[1];
                $end = ($m[2] !== '') ? (int) $m[2] : ($size - 1);
                if ($start <= $end && $end < $size) {
                    $length = $end - $start + 1;
                    header('HTTP/1.1 206 Partial Content');
                    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
                    header('Content-Length: ' . $length);
                    $fp = fopen($path, 'rb');
                    if ($fp) {
                        fseek($fp, $start);
                        $remaining = $length;
                        while ($remaining > 0 && !feof($fp)) {
                            $chunk = fread($fp, min(8192, $remaining));
                            if ($chunk === false) {
                                break;
                            }
                            echo $chunk;
                            $remaining -= strlen($chunk);
                        }
                        fclose($fp);
                    }
                    exit;
                }
            }
        }
        readfile($path);
        exit;
    }

    private function _validate_payload($is_edit = false, $existing = null)
    {
        $c = $this->_ctx();
        return my_works_validate_payload($is_edit, $existing, $c['can_view_all'], $c['user_id'], $c['role_id']);
    }

    private function _flash_form_old()
    {
        my_works_flash_form_old();
    }

    private function _can_edit_full($item)
    {
        $c = $this->_ctx();
        return my_works_can_edit_full($item, $c['can_view_all'], $c['user_id']);
    }

    private function _can_update_status($item)
    {
        $c = $this->_ctx();
        return my_works_can_update_status($item, $c['can_view_all'], $c['user_id']);
    }

    private function _can_delete($item)
    {
        $c = $this->_ctx();
        return my_works_can_delete($item, $c['can_view_all'], $c['user_id']);
    }

    public function daily_pulse()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $c = $this->_ctx();
        $pulse = my_works_build_daily_pulse($this->db, $c['user_id'], $c['can_view_all'], $c['role_id'], array(
            'reward_period' => $this->input->get('reward_period'),
            'score_from'    => $this->input->get('score_from'),
            'score_to'      => $this->input->get('score_to'),
        ));
        $this->load->view('my_works/daily_pulse', array(
            'embed' => (bool) $this->input->get('embed'),
            'pulse' => $pulse,
            'pulse_date' => date('Y-m-d'),
        ));
    }

    public function index()
    {
        $embed = (bool)$this->input->get('embed');
        if (!$embed) {
            $this->load->helper('my_works');
            $this->load->view('my_works/unified', [
                'active_tab' => $this->input->get('tab') ?: 'overview',
                'complete_view_on' => dashboard_parse_complete_view($this->input) === 'only',
            ]);
            return;
        }

        require_module_access(array('my_works_list', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $view_mode = trim((string) $this->input->get('view'));
        if ($view_mode === 'dashboard') {
            $view_mode = 'overview';
        }
        if (!in_array($view_mode, array('overview', 'hub', 'list', 'board', 'matrix'), true)) {
            $view_mode = 'overview';
        }
        $data = $this->_list_view_data($filters, $view_mode);
        if ($view_mode === 'overview') {
            // Overview lanes are for open planning work only — never list Closed/Complete.
            // Project section also includes Tasks-module rows linked to a project.
            $rows = $this->_overview_rows_with_project_tasks($data['rows'], $filters);
            $dash = my_works_build_dashboard_sections($rows, true);
            $data['dashboard_sections'] = $dash['sections'];
            $data['dashboard_counts'] = $dash['counts'];
            $task_ids = array();
            foreach ($rows as $row) {
                if (!empty($row->item_source) && $row->item_source === 'tasks' && !empty($row->id)) {
                    $task_ids[] = (int) $row->id;
                }
            }
            $data['task_assignee_names_map'] = !empty($task_ids)
                ? multi_assignees_names_map('task_assignees', 'task_id', $task_ids)
                : array();
            $this->load->view('my_works/overview', $data);
            return;
        }
        if ($view_mode === 'hub') {
            $c = $this->_ctx();
            $data['feed'] = my_works_fetch_recent_feed($this->db, $c['can_view_all'], $c['user_id'], 40);
            $overview_items = array();
            $assignee_names_map = isset($data['assignee_names_map']) ? $data['assignee_names_map'] : array();
            foreach ($data['rows'] as $row) {
                $wid = (int) $row->id;
                $extra = (isset($assignee_names_map[$wid]) && is_array($assignee_names_map[$wid]))
                    ? $assignee_names_map[$wid]
                    : array();
                $overview_items[$wid] = my_works_overview_item_payload($row, $data['attachments_map'], $extra);
            }
            $data['overview_items'] = $overview_items;
            $data['can_edit'] = function_exists('has_module_access') && (has_module_access('my_works_edit') || has_module_access('my_works'));
            $this->load->view('my_works/overview_hub', $data);
            return;
        }
        if ($view_mode === 'board') {
            $this->load->view('my_works/board', $data);
            return;
        }
        if ($view_mode === 'matrix') {
            $this->load->view('my_works/matrix', $data);
            return;
        }
        $this->load->view('my_works/list', $data);
    }

    private function _lane_focus_view($lane_key)
    {
        if (!my_works_dashboard_lane_is_valid($lane_key)) {
            show_404();
        }
        require_module_access(array('my_works_list', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $data = $this->_list_view_data($filters, 'overview');
        // Match overview cart: section=ad_hoc|project keeps the same badge count.
        $section = strtolower(trim((string) $this->input->get('section')));
        if (!in_array($section, array('ad_hoc', 'project'), true)) {
            $section = '';
        }
        $rows = $this->_overview_rows_with_project_tasks($data['rows'], $filters);
        $focus = my_works_build_lane_focus_sections($rows, $lane_key, true, $section);
        $data['dashboard_sections'] = $focus['sections'];
        $data['focus_count'] = $focus['count'];
        $data['lane_key'] = $lane_key;
        $data['focus_section'] = $section;
        $task_ids = array();
        foreach ($rows as $row) {
            if (!empty($row->item_source) && $row->item_source === 'tasks' && !empty($row->id)) {
                $task_ids[] = (int) $row->id;
            }
        }
        $data['task_assignee_names_map'] = !empty($task_ids)
            ? multi_assignees_names_map('task_assignees', 'task_id', $task_ids)
            : array();
        $pages = my_works_dashboard_lane_focus_pages();
        $meta = isset($pages[$lane_key]) ? $pages[$lane_key] : $pages['todays_plan'];
        $labels = my_works_dashboard_lane_labels();
        $lane_label = isset($labels[$lane_key]) ? $labels[$lane_key] : $lane_key;
        $page_title = $meta['page_title'];
        if ($section === 'ad_hoc') {
            $page_title .= ' — Ad hoc';
        } elseif ($section === 'project') {
            $page_title .= ' — Project';
        }
        $data['page_title'] = $page_title;
        $data['body_class'] = $meta['body_class'];
        $data['active_tab'] = $meta['active_tab'];
        $data['lane_label'] = $lane_label;
        $focus_url = site_url($meta['route']);
        if ($section !== '') {
            $focus_url .= '?section=' . rawurlencode($section);
        }
        $data['focus_url'] = $focus_url;
        $this->load->view('my_works/lane_focus', $data);
    }

    public function todays_focus()
    {
        $this->_lane_focus_view('todays_plan');
    }

    public function yesterday()
    {
        $this->_lane_focus_view('yesterday');
    }

    public function future_pipeline()
    {
        $this->_lane_focus_view('future_pipeline');
    }

    public function back_log()
    {
        $this->_lane_focus_view('back_log');
    }

    public function need_discussion()
    {
        $this->_lane_focus_view('need_discussion');
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
        fputcsv($out, array('ID', 'Title', 'Type', 'Status', 'Client', 'Project', 'Closing comment', 'URL', 'Tag', 'Due date', 'Estimate hours', 'Urgent', 'Important', 'Created by', 'Created for', 'Updated'), ',', '"', "\\");
        $labels = my_works_status_labels();
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r->id,
                $r->title,
                !empty($r->work_type) ? my_works_type_label($r->work_type) : '',
                isset($labels[$r->status]) ? $labels[$r->status] : $r->status,
                isset($r->client_name) ? $r->client_name : '',
                isset($r->project_name) ? $r->project_name : '',
                isset($r->closing_comment) ? $r->closing_comment : '',
                isset($r->url) ? $r->url : '',
                $r->tag,
                isset($r->due_date) ? $r->due_date : '',
                isset($r->estimate_hours) && $r->estimate_hours !== null && $r->estimate_hours !== ''
                    ? estimate_hours_display($r->estimate_hours)
                    : '',
                (int) $r->is_urgent ? 'Yes' : 'No',
                (int) $r->is_important ? 'Yes' : 'No',
                my_works_user_label($r->created_by_name, $r->created_by_email, $r->created_by),
                my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for),
                $r->updated_at,
            ), ',', '"', "\\");
        }
        fclose($out);
        exit;
    }

    public function quick_add()
    {
        my_works_require_add_access();
        $c = $this->_ctx();
        $redirect_default = 'my-works';
        if ($this->input->method() === 'post') {
            $payload = my_works_validate_quick_payload($c['can_view_all'], $c['user_id'], $c['role_id']);
            $redirect_path = trim((string) $this->input->post('redirect'));
            if ($redirect_path === '' || strpos($redirect_path, '://') !== false) {
                $redirect_path = $redirect_default;
            }
            if ($payload === false) {
                my_works_flash_quick_add_old();
                redirect('my-works/quick-add?redirect=' . rawurlencode($redirect_path));
                return;
            }
            $uploads = $this->_handle_uploads();
            if ($uploads === false) {
                my_works_flash_quick_add_old();
                redirect('my-works/quick-add?redirect=' . rawurlencode($redirect_path));
                return;
            }
            $payload['created_by'] = $c['user_id'];
            $assignee_ids = isset($payload['_assignee_ids']) && is_array($payload['_assignee_ids'])
                ? $payload['_assignee_ids']
                : array((int) $payload['created_for']);
            unset($payload['_assignee_ids']);
            $id = $this->my_works->insert($payload);
            multi_assignees_sync('my_works_assignees', 'work_id', (int) $id, $assignee_ids);
            $this->_save_new_attachments($id, $uploads);
            $this->my_works->log_activity($id, $c['user_id'], 'created', 'Work item created (quick add)');
            if (function_exists('my_works_notify_assignees')) {
                my_works_notify_assignees($id, $assignee_ids, $payload['title'], $payload['created_by']);
            } else {
                my_works_notify_assignee($id, $payload['created_for'], $payload['title'], $payload['created_by']);
            }
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item added.');
            redirect(my_works_safe_redirect($redirect_path, $redirect_default));
            return;
        }
        $old = $this->session->flashdata('mw_quick_add_old');
        $redirect = trim((string) $this->input->get('redirect'));
        if ($redirect === '' || strpos($redirect, '://') !== false) {
            $redirect = $redirect_default;
        }
        $this->load->view('my_works/quick_add', array(
            'item'   => $old ? (object) $old : null,
            'users'  => $this->_assignable_users(),
            'scope'  => $this->_scope_context(),
            'redirect' => $redirect,
        ));
    }

    public function template_tasks()
    {
        require_module_access(array('my_works_list', 'my_works', 'my_works_add', 'tasks_add', 'tasks'), true);
        $c = $this->_ctx();

        if ($this->input->method() === 'post') {
            require_module_access(array('tasks_add', 'tasks', 'my_works_add', 'my_works'), true);
            $user_id = (int) $c['user_id'];
            if ($user_id < 1) {
                redirect('login');
                return;
            }

            $client_id = (int) $this->input->post('client_id');
            $project_id = (int) $this->input->post('project_id');
            $team = trim((string) $this->input->post('team'));
            $template_type = trim((string) $this->input->post('template_type'));
            $template_id = (int) $this->input->post('template_id');

            if ($project_id < 1) {
                $this->session->set_flashdata('error', 'Please select a project.');
                redirect('my-works/template-tasks');
                return;
            }

            $validated = my_works_validate_client_project($this->db, $client_id, $project_id);
            if ($validated === false) {
                redirect('my-works/template-tasks');
                return;
            }
            if (empty($validated['project_id'])) {
                $this->session->set_flashdata('error', 'Please select a valid project.');
                redirect('my-works/template-tasks');
                return;
            }

            if ($team === '') {
                $this->session->set_flashdata('error', 'Please select a team.');
                redirect('my-works/template-tasks');
                return;
            }
            if ($template_type === '') {
                $this->session->set_flashdata('error', 'Please select a type.');
                redirect('my-works/template-tasks');
                return;
            }
            if ($template_id < 1) {
                $this->session->set_flashdata('error', 'Please select a task.');
                redirect('my-works/template-tasks');
                return;
            }

            $assigned_ids = multi_assignees_normalize_ids($this->input->post('assigned_to'));
            if (empty($assigned_ids)) {
                $assigned_ids = multi_assignees_normalize_ids($this->input->post('created_for'));
            }
            if (empty($assigned_ids)) {
                $assigned_ids = array($user_id);
            }
            $validated_ids = array();
            foreach ($assigned_ids as $aid) {
                $ok = my_works_validate_created_for($aid, $c['can_view_all'], $user_id, $c['role_id']);
                if ($ok === false) {
                    redirect('my-works/template-tasks');
                    return;
                }
                if (!in_array((int) $ok, $validated_ids, true)) {
                    $validated_ids[] = (int) $ok;
                }
            }
            $assigned_to = multi_assignees_primary($validated_ids);
            if (!$assigned_to) {
                $this->session->set_flashdata('error', 'Please select at least one assignee.');
                redirect('my-works/template-tasks');
                return;
            }

            $allowed_status = array('pending', 'in_progress', 'completed', 'blocked');
            $status = trim((string) $this->input->post('status'));
            if ($status === '' || !in_array($status, $allowed_status, true)) {
                $status = 'pending';
            }

            $allowed_priority = array('low', 'medium', 'high', 'urgent');
            $priority = trim((string) $this->input->post('priority'));
            if ($priority === '' || !in_array($priority, $allowed_priority, true)) {
                $priority = 'medium';
            }

            $due_date = null;
            $due = trim((string) $this->input->post('due_date'));
            if ($due !== '') {
                $parts = explode('-', $due);
                if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                    $due_date = $due;
                } else {
                    $this->session->set_flashdata('error', 'Please enter a valid due date.');
                    redirect('my-works/template-tasks');
                    return;
                }
            }

            $description = my_works_sanitize_details_html($this->input->post('description'));

            $template = $this->template_tasks->find($template_id);
            if (!$template || (int) $template->is_active !== 1) {
                $this->session->set_flashdata('error', 'Invalid template task selected.');
                redirect('my-works/template-tasks');
                return;
            }
            if ((string) $template->team !== $team) {
                $this->session->set_flashdata('error', 'Task does not match the selected team.');
                redirect('my-works/template-tasks');
                return;
            }
            if ((string) $template->template_type !== $template_type) {
                $this->session->set_flashdata('error', 'Task does not match the selected type.');
                redirect('my-works/template-tasks');
                return;
            }

            $uploads = $this->_handle_template_task_uploads();
            if ($uploads === false) {
                redirect('my-works/template-tasks');
                return;
            }

            $task_fields = $this->db->list_fields('tasks');
            $data = array(
                'project_id' => (int) $validated['project_id'],
                'title' => (string) $template->title,
                'description' => $description !== '' ? $description : null,
                'assigned_to' => $assigned_to,
                'created_by' => $user_id,
                'status' => $status,
            );
            if (in_array('priority', $task_fields, true)) {
                $data['priority'] = $priority;
            }
            if (in_array('due_date', $task_fields, true)) {
                $data['due_date'] = $due_date;
            }
            if (in_array('estimate_hours', $task_fields, true)) {
                $est = null;
                $posted_est = estimate_hours_parse($this->input->post('estimate_hours'));
                if ($posted_est === false) {
                    $this->session->set_flashdata('error', 'Estimate (hrs) must be a number between 0 and 9999.99.');
                    redirect('my-works/template-tasks');
                    return;
                }
                if ($posted_est !== null) {
                    $est = $posted_est;
                } elseif (isset($template->estimate_hours) && $template->estimate_hours !== null && $template->estimate_hours !== '') {
                    $parsed = estimate_hours_parse($template->estimate_hours);
                    if ($parsed !== false && $parsed !== null) {
                        $est = $parsed;
                    }
                }
                if ($est === null) {
                    $this->session->set_flashdata('error', 'Estimate (hrs) is required (number between 0 and 9999.99).');
                    redirect('my-works/template-tasks');
                    return;
                }
                $data['estimate_hours'] = $est;
            }
            if (in_array('project_ids', $task_fields, true)) {
                $data['project_ids'] = json_encode(array((int) $validated['project_id']));
            }

            $this->db->insert('tasks', $data);
            $id = (int) $this->db->insert_id();
            if ($id < 1) {
                $this->session->set_flashdata('error', 'Task could not be created. Please try again.');
                redirect('my-works/template-tasks');
                return;
            }

            multi_assignees_sync('task_assignees', 'task_id', $id, $validated_ids);

            $this->_save_template_task_attachments($id, $user_id, $uploads);

            $this->load->helper('change_tracker');
            if (function_exists('auto_log_insert')) {
                auto_log_insert('tasks', 'tasks', $id, $data, 'Task: ' . (string) $data['title'] . ' (from template)');
            }

            if (!empty($validated_ids)) {
                $task_details = $this->db->select('t.*, p.name as project_name')
                    ->from('tasks t')
                    ->join('projects p', 'p.id = t.project_id', 'left')
                    ->where('t.id', $id)
                    ->get()->row();
                if ($task_details) {
                    if (!function_exists('send_notification_with_settings')) {
                        $this->load->helper('email_settings');
                    }
                    $this->load->helper('notification');
                    foreach ($validated_ids as $notify_uid) {
                        $notify_uid = (int) $notify_uid;
                        if ($notify_uid < 1) {
                            continue;
                        }
                        if (function_exists('send_notification_with_settings')) {
                            send_notification_with_settings('tasks', 'created', $task_details, $notify_uid);
                        }
                        if (function_exists('create_notification')) {
                            create_notification(
                                $notify_uid,
                                'New task assigned',
                                (string) $data['title'],
                                'info',
                                'tasks',
                                $id,
                                site_url('tasks/' . $id)
                            );
                        }
                    }
                }
            }

            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Task created from template.');
            redirect('tasks/' . $id);
            return;
        }

        $template_rows = $this->template_tasks->all_active();
        $template_payload = array();
        foreach ($template_rows as $row) {
            $est_disp = '';
            if (isset($row->estimate_hours) && $row->estimate_hours !== null && $row->estimate_hours !== '') {
                $est_disp = estimate_hours_display($row->estimate_hours);
            }
            $template_payload[] = array(
                'id' => (int) $row->id,
                'team' => (string) $row->team,
                'template_type' => (string) $row->template_type,
                'title' => (string) $row->title,
                'estimate_hours' => $est_disp,
            );
        }

        $this->load->view('my_works/template_tasks', array(
            'clients' => my_works_clients_for_dropdown($this->db),
            'projects' => my_works_projects_for_dropdown($this->db),
            'projects_have_client' => schema_table_has_column($this->db, 'projects', 'client_id'),
            'users' => $this->_assignable_users(),
            'current_user_id' => (int) $c['user_id'],
            'teams' => $this->template_tasks->distinct_teams(),
            'template_json' => $template_payload,
            'can_import_templates' => function_exists('has_module_access') && (
                has_module_access('my_works_import')
                || has_module_access('my_works_add')
                || has_module_access('my_works')
            ),
            'can_export_templates' => function_exists('has_module_access') && (
                has_module_access('my_works_export')
                || has_module_access('my_works_import')
                || has_module_access('my_works_add')
                || has_module_access('my_works')
            ),
        ));
    }

    /**
     * Persist uploads from Create Template Task into task_attachments.
     *
     * @param int   $task_id
     * @param int   $user_id
     * @param array $uploads
     * @return void
     */
    private function _save_template_task_attachments($task_id, $user_id, array $uploads)
    {
        $task_id = (int) $task_id;
        $user_id = (int) $user_id;
        if ($task_id < 1 || empty($uploads) || !$this->db->table_exists('task_attachments')) {
            return;
        }

        $first_path = null;
        foreach ($uploads as $upload) {
            if (empty($upload['stored'])) {
                continue;
            }
            $file_path = 'uploads/tasks/' . $upload['stored'];
            if ($first_path === null) {
                $first_path = $file_path;
            }
            $this->db->insert('task_attachments', array(
                'task_id' => $task_id,
                'file_name' => isset($upload['original']) ? (string) $upload['original'] : (string) $upload['stored'],
                'file_path' => $file_path,
                'mime_type' => null,
                'size_bytes' => isset($upload['size']) ? (int) $upload['size'] : null,
                'uploaded_by' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }

        if ($first_path !== null && schema_table_has_column($this->db, 'tasks', 'attachment_path')) {
            $this->db->where('id', $task_id)->update('tasks', array('attachment_path' => $first_path));
        }
    }

    /**
     * Upload attachments for template→task create into uploads/tasks/.
     *
     * @return array|false
     */
    private function _handle_template_task_uploads()
    {
        $upload_dir = 'uploads/tasks/';
        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $has_multi = function_exists('my_works_upload_field_has_files') && my_works_upload_field_has_files('attachments');
        $has_legacy = function_exists('my_works_upload_field_has_files') && my_works_upload_field_has_files('attachment');
        if (!$has_multi && !$has_legacy) {
            return array();
        }

        $max_per = function_exists('my_works_max_attachments_per_submit') ? my_works_max_attachments_per_submit() : 5;
        $allowed = function_exists('my_works_upload_allowed_types') ? my_works_upload_allowed_types() : 'gif|jpg|jpeg|png|webp|pdf|doc|docx|xls|xlsx|zip';
        $max_kb = function_exists('my_works_upload_max_kb') ? my_works_upload_max_kb() : 102400;
        $results = array();

        $file_list = array();
        if ($has_legacy && !$has_multi) {
            $file_list[] = array(
                'name' => $_FILES['attachment']['name'],
                'type' => $_FILES['attachment']['type'],
                'tmp_name' => $_FILES['attachment']['tmp_name'],
                'error' => $_FILES['attachment']['error'],
                'size' => $_FILES['attachment']['size'],
            );
        } else {
            $files = $_FILES['attachments'];
            $names = is_array($files['name']) ? $files['name'] : array($files['name']);
            foreach ($names as $i => $name) {
                if (trim((string) $name) === '') {
                    continue;
                }
                $file_list[] = array(
                    'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                    'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                    'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                    'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                    'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
                );
            }
        }

        if (count($file_list) > $max_per) {
            $this->session->set_flashdata('error', 'You can upload up to ' . $max_per . ' files at once.');
            return false;
        }

        $config = array(
            'upload_path' => $dir,
            'allowed_types' => $allowed,
            'max_size' => $max_kb,
            'encrypt_name' => true,
        );

        foreach ($file_list as $file) {
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                $msg = function_exists('my_works_upload_error_message')
                    ? my_works_upload_error_message($file['error'])
                    : 'Upload failed';
                $this->session->set_flashdata('error', $msg . ' (' . $file['name'] . ')');
                foreach ($results as $r) {
                    $path = $dir . $r['stored'];
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                return false;
            }
            $_FILES['mw_tt_task_upload'] = $file;
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('mw_tt_task_upload')) {
                $err = strip_tags($this->upload->display_errors('', ''));
                $this->session->set_flashdata('error', ($err !== '' ? $err : 'Upload failed') . ' (' . $file['name'] . ')');
                foreach ($results as $r) {
                    $path = $dir . $r['stored'];
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                return false;
            }
            $up = $this->upload->data();
            $results[] = array(
                'original' => (string) $file['name'],
                'stored' => (string) $up['file_name'],
                'size' => isset($up['file_size']) ? ((int) $up['file_size'] * 1024) : (int) $file['size'],
            );
        }

        return $results;
    }

    /**
     * Download CSV sample for importing rows into template_tasks catalog.
     */
    public function template_tasks_sample_csv()
    {
        require_module_access(array('my_works_import', 'my_works_add', 'my_works'), true);
        $path = FCPATH . 'assets/samples/template_tasks_import_sample.csv';
        if (!is_file($path)) {
            show_error('Sample CSV file is missing.', 404);
            return;
        }
        $this->load->helper('download');
        force_download('template_tasks_import_sample.csv', file_get_contents($path));
    }

    /**
     * POST CSV import into template_tasks catalog (team / type / title).
     */
    public function template_tasks_import()
    {
        require_module_access(array('my_works_import', 'my_works_add', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            redirect('my-works/template-tasks');
            return;
        }

        $this->load->helper('csv_import');
        $opened = csv_import_open('file');
        if (!$opened['ok']) {
            $this->session->set_flashdata('error', $opened['error']);
            redirect('my-works/template-tasks');
            return;
        }

        $columns = csv_import_require_columns($opened['map'], array('team', 'template_type', 'title'));
        if (!$columns['ok']) {
            fclose($opened['handle']);
            $this->session->set_flashdata('error', $columns['error']);
            redirect('my-works/template-tasks');
            return;
        }

        $inserted = 0;
        $skipped = 0;
        $row_errors = array();
        $line = 1;
        $max_rows = 500;
        $seen_in_file = array();
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        while (($row = fgetcsv($opened['handle'])) !== false) {
            $line++;
            if (($line - 1) > $max_rows) {
                csv_import_add_row_error($row_errors, $line, 'Stopped at ' . $max_rows . ' data rows (max).');
                break;
            }
            if (!is_array($row) || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            $team = csv_import_get($opened['map'], $row, 'team', '');
            $template_type = csv_import_get_any($opened['map'], $row, array('template_type', 'type'), '');
            $title = csv_import_get($opened['map'], $row, 'title', '');

            if ($team === '' && $template_type === '' && $title === '') {
                continue;
            }
            if ($team === '') {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Missing team.');
                continue;
            }
            if ($template_type === '') {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Missing template_type.');
                continue;
            }
            if ($title === '') {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Missing title.');
                continue;
            }
            if (strlen($team) > 100) {
                $team = substr($team, 0, 100);
            }
            if (strlen($template_type) > 150) {
                $template_type = substr($template_type, 0, 150);
            }
            if (strlen($title) > 255) {
                $title = substr($title, 0, 255);
            }

            $dup_key = strtolower($team) . '|' . strtolower($template_type) . '|' . strtolower($title);
            if (isset($seen_in_file[$dup_key])) {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Duplicate row in file.');
                continue;
            }
            $seen_in_file[$dup_key] = true;

            if ($this->template_tasks->exists_combo($team, $template_type, $title)) {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Already exists (same team, type, title).');
                continue;
            }

            $sort_raw = csv_import_get($opened['map'], $row, 'sort_order', '');
            if ($sort_raw !== '' && is_numeric($sort_raw)) {
                $sort_order = max(0, (int) $sort_raw);
            } else {
                $sort_order = $this->template_tasks->next_sort_order($team, $template_type);
            }

            $active_raw = strtolower(csv_import_get_any($opened['map'], $row, array('is_active', 'active'), '1'));
            $is_active = in_array($active_raw, array('0', 'false', 'no', 'n', 'inactive'), true) ? 0 : 1;

            $insert = array(
                'team' => $team,
                'template_type' => $template_type,
                'title' => $title,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
            );
            if (schema_table_has_column($this->db, 'template_tasks', 'estimate_hours')) {
                $est_raw = csv_import_get($opened['map'], $row, 'estimate_hours', '');
                $est = estimate_hours_require($est_raw);
                if ($est === false) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'estimate_hours is required (number 0–9999.99).');
                    continue;
                }
                $insert['estimate_hours'] = $est;
            }

            $id = $this->template_tasks->insert($insert);
            if ($id > 0) {
                $inserted++;
            } else {
                $skipped++;
                $db_error = $this->db->error();
                $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';
                csv_import_add_row_error($row_errors, $line, $reason);
                log_message('error', 'Template tasks import error: ' . $reason);
            }
        }

        $this->db->db_debug = $prev_debug;
        fclose($opened['handle']);

        if ($inserted === 0) {
            $msg = 'No template tasks were imported.';
            if (!empty($row_errors)) {
                $msg .= ' ' . implode(' ', array_slice($row_errors, 0, 3));
            } else {
                $msg .= ' Check column headers and row data.';
            }
            $this->session->set_flashdata('error', $msg);
            if (!empty($row_errors)) {
                $this->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
            }
            redirect('my-works/template-tasks');
            return;
        }

        $msg = 'Imported ' . (int) $inserted . ' template task(s)';
        if ($skipped > 0) {
            $msg .= '. ' . (int) $skipped . ' row(s) skipped.';
        }
        $this->session->set_flashdata('success', $msg);
        if (!empty($row_errors)) {
            $this->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
        }
        redirect('my-works/template-tasks');
    }

    /**
     * Export template_tasks catalog CSV.
     */
    public function template_tasks_export()
    {
        require_module_access(array('my_works_export', 'my_works_import', 'my_works_add', 'my_works'), true);
        $rows = $this->template_tasks->all();
        $filename = 'template_tasks_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('team', 'template_type', 'title', 'estimate_hours', 'sort_order', 'is_active'));
        foreach ($rows as $r) {
            fputcsv($out, array(
                isset($r->team) ? $r->team : '',
                isset($r->template_type) ? $r->template_type : '',
                isset($r->title) ? $r->title : '',
                isset($r->estimate_hours) && $r->estimate_hours !== null && $r->estimate_hours !== ''
                    ? estimate_hours_display($r->estimate_hours)
                    : '',
                isset($r->sort_order) ? (int) $r->sort_order : 0,
                isset($r->is_active) ? (int) $r->is_active : 1,
            ));
        }
        fclose($out);
        exit;
    }

    public function create()
    {
        my_works_require_add_access();
        $old = $this->session->flashdata('mw_form_old');
        if ($this->input->method() === 'post') {
            $payload = $this->_validate_payload();
            if ($payload === false) {
                $this->_flash_form_old();
                redirect('my-works/create');
                return;
            }
            $uploads = $this->_handle_uploads();
            if ($uploads === false) {
                $this->_flash_form_old();
                redirect('my-works/create');
                return;
            }
            $payload['created_by'] = $this->_current_user_id();
            $assignee_ids = isset($payload['_assignee_ids']) && is_array($payload['_assignee_ids'])
                ? $payload['_assignee_ids']
                : array((int) $payload['created_for']);
            unset($payload['_assignee_ids']);
            $id = $this->my_works->insert($payload);
            multi_assignees_sync('my_works_assignees', 'work_id', (int) $id, $assignee_ids);
            $this->_save_new_attachments($id, $uploads);
            $this->my_works->log_activity($id, $this->_current_user_id(), 'created', 'Work item created');
            if (function_exists('my_works_notify_assignees')) {
                my_works_notify_assignees($id, $assignee_ids, $payload['title'], $payload['created_by']);
            } else {
                my_works_notify_assignee($id, $payload['created_for'], $payload['title'], $payload['created_by']);
            }
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item created.');
            redirect('my-works/' . (int) $id);
            return;
        }
        $assigned_user_ids = array();
        if ($old && isset($old['created_for'])) {
            if (is_array($old['created_for'])) {
                $assigned_user_ids = array_map('intval', $old['created_for']);
            } elseif ((int) $old['created_for'] > 0) {
                $assigned_user_ids = array((int) $old['created_for']);
            }
        }
        if (empty($assigned_user_ids)) {
            $assigned_user_ids = array($this->_current_user_id());
        }
        $this->load->view('my_works/form_create', array(
            'item' => $old ? (object) $old : null,
            'users' => $this->_assignable_users(),
            'assigned_user_ids' => $assigned_user_ids,
            'tags' => $this->my_works->distinct_tags_scoped(array($this, '_apply_list_scope')),
            'scope' => $this->_scope_context(),
            'clients' => my_works_clients_for_dropdown($this->db),
            'projects' => my_works_projects_for_dropdown($this->db),
            'projects_have_client' => schema_table_has_column($this->db, 'projects', 'client_id'),
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
        $client_label = '';
        $project_label = '';
        if (!empty($item->client_id) && $this->db->table_exists('clients')) {
            $client_row = $this->db->select('company_name')->from('clients')->where('id', (int) $item->client_id)->get()->row();
            if ($client_row) {
                $client_label = (string) $client_row->company_name;
            }
        }
        if (!empty($item->project_id) && $this->db->table_exists('projects')) {
            $project_row = $this->db->select('name')->from('projects')->where('id', (int) $item->project_id)->get()->row();
            if ($project_row) {
                $project_label = (string) $project_row->name;
            }
        }
        $uid = $this->_current_user_id();
        $is_multi_assignee = multi_assignees_includes_user('my_works_assignees', 'work_id', (int) $id, $uid);
        $assignee_ids = multi_assignees_resolve_for_edit(
            'my_works_assignees',
            'work_id',
            (int) $id,
            isset($item->created_for) ? (int) $item->created_for : 0
        );
        $assignee_names_map = multi_assignees_names_map('my_works_assignees', 'work_id', array((int) $id));
        $this->load->view('my_works/view', array(
            'item' => $item,
            'creator' => $creator,
            'assignee' => $assignee,
            'assigned_user_ids' => $assignee_ids,
            'assignee_extra_names' => isset($assignee_names_map[(int) $id]) ? $assignee_names_map[(int) $id] : array(),
            'can_edit' => $this->_can_edit_full($item),
            'can_update_status' => $this->_can_update_status($item),
            'can_delete' => $this->_can_delete($item),
            'can_comment' => $this->_user_can_access($item),
            'is_assignee' => ((int) $item->created_for === $uid) || $is_multi_assignee,
            'is_creator' => ((int) $item->created_by === $uid),
            'scope' => $this->_scope_context(),
            'comments' => $this->my_works->list_comments((int) $id),
            'activity' => $this->my_works->list_activity((int) $id),
            'client_label' => $client_label,
            'project_label' => $project_label,
            'attachments' => my_works_attachments_for_work($this->db, (int) $id),
            'embed' => $this->_wants_embed(),
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
            $uploads = $this->_handle_uploads();
            if ($uploads === false) {
                redirect('my-works/' . (int) $id . '/edit');
                return;
            }
            $this->_process_remove_attachments((int) $id);
            $this->_save_new_attachments((int) $id, $uploads);
            $prev_for = (int) $item->created_for;
            $prev_status = (string) $item->status;
            $old_assignee_ids = multi_assignees_resolve_for_edit(
                'my_works_assignees',
                'work_id',
                (int) $id,
                $prev_for
            );
            $assignee_ids = isset($payload['_assignee_ids']) && is_array($payload['_assignee_ids'])
                ? $payload['_assignee_ids']
                : array((int) $payload['created_for']);
            unset($payload['_assignee_ids']);
            $this->my_works->update((int) $id, $payload);
            multi_assignees_sync('my_works_assignees', 'work_id', (int) $id, $assignee_ids);
            if ($prev_status !== $payload['status']) {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'status', $prev_status . ' → ' . $payload['status']);
            }
            $newly_added = array_values(array_diff($assignee_ids, $old_assignee_ids));
            if ($prev_for !== (int) $payload['created_for'] || !empty($newly_added)) {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'reassigned', 'Assignee changed');
                $notify_ids = !empty($newly_added) ? $newly_added : array((int) $payload['created_for']);
                if (function_exists('my_works_notify_assignees')) {
                    my_works_notify_assignees((int) $id, $notify_ids, $payload['title'], $this->_current_user_id());
                } else {
                    my_works_notify_assignee((int) $id, (int) $payload['created_for'], $payload['title'], $this->_current_user_id());
                }
            } else {
                $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'updated', 'Details updated');
            }
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item updated.');
            redirect('my-works/' . (int) $id);
            return;
        }
        $assigned_user_ids = multi_assignees_resolve_for_edit(
            'my_works_assignees',
            'work_id',
            (int) $id,
            isset($item->created_for) ? (int) $item->created_for : 0
        );
        $this->load->view('my_works/form', array(
            'action' => 'edit',
            'item' => $item,
            'users' => $this->_assignable_users(),
            'assigned_user_ids' => $assigned_user_ids,
            'tags' => $this->my_works->distinct_tags_scoped(array($this, '_apply_list_scope')),
            'scope' => $this->_scope_context(),
            'clients' => my_works_clients_for_dropdown($this->db),
            'projects' => my_works_projects_for_dropdown($this->db),
            'projects_have_client' => schema_table_has_column($this->db, 'projects', 'client_id'),
            'attachments' => my_works_attachments_for_work($this->db, (int) $id),
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
            $redirect = trim((string) $this->input->post('redirect'));
            if ($redirect !== '' && strpos($redirect, 'my-works') !== false) {
                $this->_redirect_with_embed($redirect);
                return;
            }
            $this->_redirect_with_embed('my-works/' . (int) $id);
            return;
        }
        $uploads = $this->_handle_uploads();
        if ($uploads === false) {
            $redirect = trim((string) $this->input->post('redirect'));
            if ($redirect !== '') {
                $this->_redirect_with_embed($redirect);
            } else {
                $this->_redirect_with_embed('my-works/' . (int) $id);
            }
            return;
        }
        $this->my_works->add_comment((int) $id, $this->_current_user_id(), $comment);
        if (!empty($uploads)) {
            $this->_save_new_attachments((int) $id, $uploads);
            $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'comment', 'Added a comment with attachment(s)');
        } else {
            $this->my_works->log_activity((int) $id, $this->_current_user_id(), 'comment', 'Added a comment');
        }
        $this->_clear_dashboard_cache();
        $this->session->set_flashdata('success', 'Comment added.');
        $redirect = trim((string) $this->input->post('redirect'));
        if ($redirect !== '' && strpos($redirect, 'my-works') !== false) {
            $this->_redirect_with_embed($redirect);
            return;
        }
        $this->_redirect_with_embed('my-works/' . (int) $id);
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
        $this->_delete_all_attachment_files((int) $id, $item);
        $this->my_works->delete((int) $id);
        $this->_clear_dashboard_cache();
        $this->session->set_flashdata('success', 'Work item deleted.');
        redirect('my-works');
    }

    public function download($id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $att = $this->_resolve_legacy_attachment((int) $id, $item);
        if (!$att) {
            show_404();
        }
        $this->_serve_attachment($att, false);
    }

    public function preview($id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $att = $this->_resolve_legacy_attachment((int) $id, $item);
        if (!$att) {
            show_404();
        }
        if ($this->_serve_attachment($att, true) === false) {
            redirect('my-works/' . (int) $id . '/download');
        }
    }

    public function attachment_download($id, $attachment_id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $att = $this->my_works->find_attachment((int) $id, (int) $attachment_id);
        if (!$att) {
            show_404();
        }
        $this->_serve_attachment($att, false);
    }

    public function attachment_preview($id, $attachment_id)
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $item = $this->my_works->find((int) $id);
        if (!$item) {
            show_404();
        }
        $this->_require_access($item);
        $att = $this->my_works->find_attachment((int) $id, (int) $attachment_id);
        if (!$att) {
            show_404();
        }
        if ($this->_serve_attachment($att, true) === false) {
            redirect('my-works/' . (int) $id . '/attachment/' . (int) $attachment_id . '/download');
        }
    }

    public function update_status()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $status = my_works_status_sanitize($this->input->post('status'));
        if (!my_works_status_is_valid($status)) {
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
        $update = array('status' => $status);
        if (my_works_status_is_closed($status) && !my_works_status_is_closed($prev)) {
            if (!function_exists('actual_hours_require')) {
                $this->load->helper('estimate_hours');
            }
            if (schema_table_has_column($this->db, 'my_works', 'actual_hours')) {
                $act = actual_hours_require($this->input->post('actual_hours'));
                if ($act === false) {
                    if ($this->input->is_ajax_request()) {
                        $this->output->set_status_header(422);
                        $this->output->set_content_type('application/json')->set_output(json_encode(array(
                            'ok' => false,
                            'error' => 'Actual (hrs) is required when closing.',
                            'need_actual_hours' => true,
                        )));
                        return;
                    }
                    $this->session->set_flashdata('error', 'Actual (hrs) is required when closing.');
                    redirect(my_works_safe_redirect($this->input->post('redirect'), 'my-works/' . $id));
                    return;
                }
                $update['actual_hours'] = $act;
            }
        }
        $this->my_works->update($id, $update);
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

    public function update_matrix()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $quadrant = trim((string) $this->input->post('quadrant'));
        $flags = my_works_matrix_flags_from_quadrant($quadrant);
        if (!$flags) {
            show_error('Invalid quadrant.', 400);
        }
        $item = $this->my_works->find($id);
        if (!$item) {
            show_404();
        }
        if (!$this->_can_update_status($item)) {
            show_error('Access denied.', 403);
        }
        $prev_q = my_works_matrix_quadrant_for_row($item);
        $this->my_works->update($id, $flags);
        if ($prev_q !== $quadrant) {
            $defs = my_works_matrix_quadrants();
            $from = isset($defs[$prev_q]['label']) ? $defs[$prev_q]['label'] : $prev_q;
            $to = isset($defs[$quadrant]['label']) ? $defs[$quadrant]['label'] : $quadrant;
            $this->my_works->log_activity($id, $this->_current_user_id(), 'priority', $from . ' → ' . $to);
        }
        $this->_clear_dashboard_cache();
        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')->set_output(json_encode(array(
                'ok'       => true,
                'quadrant' => $quadrant,
                'urgent'   => $flags['is_urgent'],
                'important'=> $flags['is_important'],
            )));
            return;
        }
        $this->session->set_flashdata('success', 'Priority updated.');
        redirect('my-works?view=matrix');
    }

    public function update_lane()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $id = (int) $this->input->post('id');
        $lane = trim((string) $this->input->post('lane'));
        if ($id < 1 || !my_works_dashboard_lane_is_valid($lane)) {
            show_error('Invalid lane.', 400);
        }

        $item = $this->my_works->find($id);
        if (!$item) {
            show_404();
        }
        if (!$this->_can_update_status($item)) {
            show_error('Access denied.', 403);
        }

        $this->load->helper('my_works_status');
        if (my_works_row_is_finished($item) && in_array($lane, array('future_pipeline', 'back_log', 'yesterday', 'todays_plan'), true)) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(422);
                $this->output->set_content_type('application/json')->set_output(json_encode(array(
                    'ok'      => false,
                    'message' => 'Completed / Closed tasks cannot be moved into Back Log or Future Pipeline.',
                )));
                return;
            }
            $this->session->set_flashdata('error', 'Completed / Closed tasks cannot be moved into Back Log or Future Pipeline.');
            redirect('my-works?view=overview');
            return;
        }

        $from_lane = my_works_dashboard_lane_for_row($item);
        if ($from_lane === $lane) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')->set_output(json_encode(array(
                    'ok'            => true,
                    'lane'          => $lane,
                    'computed_lane' => $lane,
                )));
                return;
            }
            redirect('my-works?view=overview');
            return;
        }

        $updates = my_works_lane_updates_for_drop($lane, $item);
        if ($updates === false || empty($updates)) {
            show_error('Unable to move item to that lane.', 400);
        }

        $prev_status = isset($item->status) ? (string) $item->status : '';
        $prev_due = isset($item->due_date) ? (string) $item->due_date : '';
        $prev_tag = isset($item->tag) ? (string) $item->tag : '';
        $user_id = $this->_current_user_id();

        $this->my_works->update($id, $updates);

        $labels = my_works_dashboard_lane_labels();
        $from_label = isset($labels[$from_lane]) ? $labels[$from_lane] : $from_lane;
        $to_label = isset($labels[$lane]) ? $labels[$lane] : $lane;
        $this->my_works->log_activity($id, $user_id, 'lane', $from_label . ' → ' . $to_label);

        if (isset($updates['status']) && $prev_status !== (string) $updates['status']) {
            $this->my_works->log_activity($id, $user_id, 'status', $prev_status . ' → ' . $updates['status']);
        }
        if (isset($updates['due_date']) && $prev_due !== (string) $updates['due_date']) {
            $detail = ($prev_due !== '' ? $prev_due : 'none') . ' → ' . $updates['due_date'];
            $this->my_works->log_activity($id, $user_id, 'due_date', $detail);
        }
        if (array_key_exists('tag', $updates)) {
            $new_tag = $updates['tag'] !== null ? (string) $updates['tag'] : '';
            if ($prev_tag !== $new_tag) {
                $tag_detail = ($prev_tag !== '' ? $prev_tag : 'none') . ' → ' . ($new_tag !== '' ? $new_tag : 'none');
                $this->my_works->log_activity($id, $user_id, 'updated', 'Tags: ' . $tag_detail);
            }
        }

        $this->_clear_dashboard_cache();

        $updated = $this->my_works->find($id);
        $computed_lane = $updated ? my_works_dashboard_lane_for_row($updated) : $lane;
        $response = array(
            'ok'            => true,
            'lane'          => $lane,
            'computed_lane' => $computed_lane,
            'status'        => $updated && isset($updated->status) ? (string) $updated->status : '',
            'due_date'      => $updated && isset($updated->due_date) ? (string) $updated->due_date : '',
        );

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $this->session->set_flashdata('success', 'Work item moved to ' . $to_label . '.');
        redirect('my-works?view=overview');
    }
}
