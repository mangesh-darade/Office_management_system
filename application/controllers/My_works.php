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
            'my_works', 'my_works_access', 'my_works_query', 'my_works_form',
            'my_works_attachment', 'download',
        ));
        $this->load->library(array('session', 'upload'));
        $this->load->model('My_work_model', 'my_works');
        require_module_access(array('my_works', 'my_works_list', 'my_works_add'), true);
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        $this->load->helper('my_works_schema');
        my_works_schema_ensure($this->db);
        $this->load->model('Type_model', 'module_types');
        $dir = FCPATH . $this->upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
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

    public function index()
    {
        require_module_access(array('my_works_list', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $view_mode = trim((string) $this->input->get('view'));
        if (!in_array($view_mode, array('list', 'board', 'matrix'), true)) {
            $view_mode = 'list';
        }
        $data = $this->_list_view_data($filters, $view_mode);
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

    public function export()
    {
        require_module_access(array('my_works_export', 'my_works'), true);
        $filters = $this->_sanitize_filters($this->_parse_filters());
        $rows = $this->_fetch_rows($filters, null, 0);
        $filename = 'my_works_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('ID', 'Title', 'Type', 'Status', 'Client', 'Project', 'Closing comment', 'URL', 'Tag', 'Due date', 'Urgent', 'Important', 'Created by', 'Created for', 'Updated'));
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
            $id = $this->my_works->insert($payload);
            $this->_save_new_attachments($id, $uploads);
            $this->my_works->log_activity($id, $c['user_id'], 'created', 'Work item created (quick add)');
            my_works_notify_assignee($id, $payload['created_for'], $payload['title'], $payload['created_by']);
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
            $id = $this->my_works->insert($payload);
            $this->_save_new_attachments($id, $uploads);
            $this->my_works->log_activity($id, $this->_current_user_id(), 'created', 'Work item created');
            my_works_notify_assignee($id, $payload['created_for'], $payload['title'], $payload['created_by']);
            $this->_clear_dashboard_cache();
            $this->session->set_flashdata('success', 'Work item created.');
            redirect('my-works/' . (int) $id);
            return;
        }
        $this->load->view('my_works/form', array(
            'action' => 'create',
            'item' => $old ? (object) $old : null,
            'users' => $this->_assignable_users(),
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
            'client_label' => $client_label,
            'project_label' => $project_label,
            'attachments' => my_works_attachments_for_work($this->db, (int) $id),
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
}
