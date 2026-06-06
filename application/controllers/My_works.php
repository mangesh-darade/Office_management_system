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
            'my_works', 'my_works_access', 'my_works_query', 'my_works_form', 'download',
        ));
        $this->load->library(array('session', 'upload'));
        $this->load->model('My_work_model', 'my_works');
        require_module_access(array('my_works', 'my_works_list'), true);
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

    private function _handle_upload($existing = null)
    {
        return my_works_handle_upload($this->upload_dir, $existing);
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
            'clients' => my_works_clients_for_dropdown($this->db),
            'projects' => my_works_projects_for_dropdown($this->db),
            'projects_have_client' => schema_table_has_column($this->db, 'projects', 'client_id'),
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
}
