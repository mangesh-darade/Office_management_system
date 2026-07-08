<?php

defined('BASEPATH') OR exit('No direct script access allowed');



class Defects extends CI_Controller

{

    private $upload_dir;



    public function __construct()

    {

        parent::__construct();

        $this->load->database();

        $this->load->helper(array('url', 'form', 'permission', 'change_tracker', 'activity', 'defects_releases'));

        $this->load->library(array('session', 'pagination'));

        $this->load->model('Defect_model', 'defects');

        $this->upload_dir = FCPATH . 'uploads/defects/';

        require_controller_access('defects', true);

    }



    public function index($offset = 0)

    {

        require_module_access(array('defects_list', 'defects'), true);

        $filters = array(

            'status' => trim((string) $this->input->get('status')),

            'severity' => trim((string) $this->input->get('severity')),

            'project_id' => (int) $this->input->get('project_id'),

            'assigned_to' => (int) $this->input->get('assigned_to'),

            'q' => trim((string) $this->input->get('q')),

            'overdue' => $this->input->get('overdue') === '1',

        );

        $per_page = 25;

        $total = $this->defects->count_defects($filters);

        $config = array(

            'base_url' => site_url('defects/index'),

            'total_rows' => $total,

            'per_page' => $per_page,

            'uri_segment' => 3,

            'reuse_query_string' => true,

            'full_tag_open' => '<nav><ul class="pagination pagination-sm justify-content-center mb-0">',

            'full_tag_close' => '</ul></nav>',

            'attributes' => array('class' => 'page-link'),

        );

        $this->pagination->initialize($config);

        $rows = $this->defects->list_defects($filters, $per_page, (int) $offset);

        $this->load->view('defects/index', array(

            'rows' => $rows,

            'projects' => $this->defects->project_options(),

            'members' => $this->defects->user_options(),

            'filters' => $filters,

            'pagination_links' => $this->pagination->create_links(),

            'total' => $total,

        ));

    }



    public function export()

    {

        require_module_access(array('defects_export', 'defects_list', 'defects'), true);

        $filters = array(

            'status' => trim((string) $this->input->get('status')),

            'severity' => trim((string) $this->input->get('severity')),

            'project_id' => (int) $this->input->get('project_id'),

            'assigned_to' => (int) $this->input->get('assigned_to'),

            'q' => trim((string) $this->input->get('q')),

            'overdue' => $this->input->get('overdue') === '1',

        );

        $rows = $this->defects->list_defects($filters);

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename="defects_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, array('Number', 'Title', 'Project', 'Severity', 'Priority', 'Status', 'Assignee', 'Due Date', 'Reporter', 'Created'), ',', '"', '\\');

        foreach ($rows as $r) {

            fputcsv($out, array(

                $r->defect_number,

                $r->title,

                $r->project_name,

                $r->severity,

                $r->priority,

                $r->status,

                $r->assignee_name,

                isset($r->due_date) ? $r->due_date : '',

                $r->reporter_name,

                $r->created_at,

            ), ',', '"', '\\');

        }

        fclose($out);

        exit;

    }



    public function import()

    {

        require_module_access(array('defects_add', 'defects'), true);

        if ($this->input->method() === 'post') {

            $uid = (int) $this->session->userdata('user_id');

            if ($uid < 1) {

                redirect('auth/login');

                return;

            }

            $this->load->helper(array('csv_import', 'change_tracker', 'activity'));

            $opened = csv_import_open('file');

            if (!$opened['ok']) {

                csv_import_fail_redirect($opened['error'], 'defects/import');

                return;

            }

            $columns = csv_import_require_columns($opened['map'], array('title'), array(array('project_name', 'project', 'project_id')));

            if (!$columns['ok']) {

                fclose($opened['handle']);

                csv_import_fail_redirect($columns['error'], 'defects/import');

                return;

            }

            $inserted = 0;

            $skipped = 0;

            $row_errors = array();

            $project_cache = array();

            $line = 1;

            $allowed_status = array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected');

            $allowed_level = array('low', 'medium', 'high', 'critical');

            $prev_debug = $this->db->db_debug;

            $this->db->db_debug = false;

            while (($row = fgetcsv($opened['handle'])) !== false) {

                $line++;

                $title = csv_import_get($opened['map'], $row, 'title');

                if ($title === '') {

                    $skipped++;

                    csv_import_add_row_error($row_errors, $line, 'Missing defect title.');

                    continue;

                }

                $project_id = csv_import_resolve_project_id($this->db, $opened['map'], $row, $project_cache);

                if ($project_id <= 0) {

                    $skipped++;

                    csv_import_add_row_error($row_errors, $line, 'Unknown project name or code.');

                    continue;

                }

                $status = csv_import_validate_enum(

                    csv_import_get($opened['map'], $row, 'status', 'open'),

                    $allowed_status,

                    'open',

                    $row_errors,

                    $line,

                    'status'

                );

                if ($status === false) {

                    $skipped++;

                    continue;

                }

                $severity = csv_import_validate_enum(

                    csv_import_get($opened['map'], $row, 'severity', 'medium'),

                    $allowed_level,

                    'medium',

                    $row_errors,

                    $line,

                    'severity'

                );

                if ($severity === false) {

                    $skipped++;

                    continue;

                }

                $priority = csv_import_validate_enum(

                    csv_import_get($opened['map'], $row, 'priority', 'medium'),

                    $allowed_level,

                    'medium',

                    $row_errors,

                    $line,

                    'priority'

                );

                if ($priority === false) {

                    $skipped++;

                    continue;

                }

                $assigned_to = null;

                $assignee_raw = csv_import_get($opened['map'], $row, 'assigned_to', '');

                if ($assignee_raw !== '') {

                    $assigned_to = (int) $assignee_raw;

                }

                $due_raw = csv_import_get($opened['map'], $row, 'due_date', '');

                $due_date = null;

                if ($due_raw !== '') {

                    $due_ts = strtotime($due_raw);

                    if ($due_ts) {

                        $due_date = date('Y-m-d', $due_ts);

                    }

                }

                $payload = array(

                    'defect_number' => $this->defects->next_defect_number(),

                    'project_id' => $project_id,

                    'release_id' => null,

                    'task_id' => null,

                    'title' => $title,

                    'description' => csv_import_get($opened['map'], $row, 'description', null) ?: null,

                    'steps_to_reproduce' => csv_import_get($opened['map'], $row, 'steps_to_reproduce', null) ?: null,

                    'severity' => $severity,

                    'priority' => $priority,

                    'status' => $status,

                    'assigned_to' => $assigned_to,

                    'due_date' => $due_date,

                    'reported_by' => $uid,

                );

                $id = $this->defects->save_defect($payload);

                if ($id) {

                    $inserted++;

                    $this->defects->log_activity($id, $uid, 'created', 'Imported from CSV');

                    if ($assigned_to) {

                        defect_notify_assignee($id, $assigned_to, $title, $uid);

                    }

                } else {

                    $skipped++;

                    $db_error = $this->db->error();

                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';

                    csv_import_add_row_error($row_errors, $line, $reason);

                    log_message('error', 'Defect import error: ' . $reason);

                }

            }

            $this->db->db_debug = $prev_debug;

            fclose($opened['handle']);

            csv_import_finish($inserted, $skipped, $row_errors, 'defects', 'defects', 'defects/import');

            return;

        }

        $this->load->view('defects/import');

    }



    public function ajax_options($project_id = 0)

    {

        require_module_access(array('defects_add', 'defects_edit', 'defects'), true);

        $project_id = (int) $project_id;

        $releases = $this->defects->release_options($project_id ?: null);

        $tasks = $this->defects->task_options($project_id ?: null);

        $rel_out = array();

        foreach ($releases as $r) {

            $rel_out[] = array(

                'id' => (int) $r->id,

                'label' => $r->version . ' — ' . $r->title,

            );

        }

        $task_out = array();

        foreach ($tasks as $t) {

            $task_out[] = array(

                'id' => (int) $t->id,

                'label' => $t->title,

            );

        }

        $this->output->set_content_type('application/json')->set_output(json_encode(array(

            'status' => 'success',

            'data' => array('releases' => $rel_out, 'tasks' => $task_out),

        )));

    }



    public function create()

    {

        require_module_access(array('defects_add', 'defects'), true);

        if ($this->input->method() === 'post') {

            $uid = (int) $this->session->userdata('user_id');

            $payload = $this->_build_payload($uid);

            if ($payload === false) {

                redirect('defects/create');

                return;

            }

            $payload['defect_number'] = $this->defects->next_defect_number();

            $payload['reported_by'] = $uid;

            $id = $this->defects->save_defect($payload);

            $uploads = defect_handle_uploads($this->upload_dir);

            $this->defects->save_attachments($id, $uid, $uploads);

            auto_log_insert('defects', 'project_defects', $id, $payload, 'Defect: ' . $payload['title']);

            $this->defects->log_activity($id, $uid, 'created', 'Defect logged');

            if (!empty($payload['assigned_to'])) {

                defect_notify_assignee($id, (int) $payload['assigned_to'], $payload['title'], $uid);

            }

            $this->session->set_flashdata('success', 'Defect logged.');

            redirect('defects/view/' . $id);

            return;

        }

        $this->load->view('defects/form', array(

            'action' => 'create',

            'item' => null,

            'projects' => $this->defects->project_options(),

            'releases' => array(),

            'tasks' => array(),

            'members' => $this->defects->user_options(),

        ));

    }



    public function view($id)

    {

        require_module_access(array('defects_view', 'defects_list', 'defects'), true);

        $item = $this->defects->get_defect((int) $id);

        if (!$item) {

            show_404();

        }

        $this->load->view('defects/view', array(

            'item' => $item,

            'comments' => $this->defects->list_comments((int) $id),

            'attachments' => $this->defects->list_attachments((int) $id),

            'activity' => $this->defects->list_activity((int) $id),

            'is_overdue' => defect_is_overdue($item),

        ));

    }



    public function edit($id)

    {

        require_module_access(array('defects_edit', 'defects'), true);

        $item = $this->defects->get_defect((int) $id);

        if (!$item) {

            show_404();

        }

        if ($this->input->method() === 'post') {

            $uid = (int) $this->session->userdata('user_id');

            $old_data = track_changes_before('project_defects', (int) $id);

            $payload = $this->_build_payload($uid, $item);

            if ($payload === false) {

                redirect('defects/edit/' . (int) $id);

                return;

            }

            $this->defects->save_defect($payload, (int) $id);

            $uploads = defect_handle_uploads($this->upload_dir);

            $this->defects->save_attachments((int) $id, $uid, $uploads);

            track_changes_after('defects', 'project_defects', (int) $id, $old_data, $payload, 'Defect: ' . $payload['title']);

            $this->defects->log_activity((int) $id, $uid, 'updated', 'Details updated');

            $old_assignee = (int) $item->assigned_to;

            $new_assignee = isset($payload['assigned_to']) ? (int) $payload['assigned_to'] : 0;

            if ($new_assignee && $new_assignee !== $old_assignee) {

                defect_notify_assignee((int) $id, $new_assignee, $payload['title'], $uid);

                $this->defects->log_activity((int) $id, $uid, 'reassigned', 'Assignee changed');

            }

            if ($payload['status'] !== (string) $item->status) {

                $this->defects->log_activity((int) $id, $uid, 'status', $item->status . ' → ' . $payload['status']);

                $notify_uid = $new_assignee ?: (int) $item->reported_by;

                defect_notify_status_change((int) $id, $notify_uid, $payload['title'], (string) $item->status, $payload['status']);

            }

            $this->session->set_flashdata('success', 'Defect updated.');

            redirect('defects/view/' . (int) $id);

            return;

        }

        $this->load->view('defects/form', array(

            'action' => 'edit',

            'item' => $item,

            'projects' => $this->defects->project_options(),

            'releases' => $this->defects->release_options($item->project_id),

            'tasks' => $this->defects->task_options($item->project_id),

            'members' => $this->defects->user_options(),

        ));

    }



    public function add_comment($id)

    {

        require_module_access(array('defects_view', 'defects_list', 'defects'), true);

        if ($this->input->method() !== 'post') {

            show_error('Invalid request', 405);

        }

        $item = $this->defects->get_defect((int) $id);

        if (!$item) {

            show_404();

        }

        $uid = (int) $this->session->userdata('user_id');

        $comment = trim((string) $this->input->post('comment'));

        if ($comment === '') {

            $this->session->set_flashdata('error', 'Comment cannot be empty.');

            redirect('defects/view/' . (int) $id);

            return;

        }

        $this->defects->add_comment((int) $id, $uid, $comment);

        $this->defects->log_activity((int) $id, $uid, 'comment', 'Comment added');

        log_activity('defects', 'comment', (int) $id, 'Comment on defect: ' . $item->defect_number);

        $this->session->set_flashdata('success', 'Comment added.');

        redirect('defects/view/' . (int) $id);

    }



    public function attachment_download($id, $attachment_id)

    {

        require_module_access(array('defects_view', 'defects_list', 'defects'), true);

        $item = $this->defects->get_defect((int) $id);

        if (!$item) {

            show_404();

        }

        $att = $this->defects->get_attachment((int) $id, (int) $attachment_id);

        if (!$att) {

            show_404();

        }

        $path = $this->upload_dir . $att->stored_name;

        if (!is_file($path)) {

            show_404();

        }

        $this->load->helper('download');

        force_download($att->original_name, file_get_contents($path));

    }



    public function delete($id)

    {

        require_module_access(array('defects_delete', 'defects'), true);

        $item = $this->defects->get_defect((int) $id);

        if (!$item) {

            show_404();

        }

        if ($this->input->method() !== 'post') {

            show_error('Invalid request', 405);

        }

        $uid = (int) $this->session->userdata('user_id');

        auto_log_delete('defects', 'project_defects', (int) $id, (array) $item, 'Defect deleted: ' . $item->defect_number);

        $this->defects->log_activity((int) $id, $uid, 'deleted', 'Defect removed');

        $this->defects->delete_defect((int) $id);

        $this->session->set_flashdata('success', 'Defect deleted.');

        redirect('defects');

    }



    private function _build_payload($uid, $item = null)

    {

        $assigned = $this->input->post('assigned_to');

        $release = $this->input->post('release_id');

        $task = $this->input->post('task_id');

        $oldStatus = $item ? (string) $item->status : 'open';

        $newStatus = trim((string) $this->input->post('status')) ?: $oldStatus;

        $this->load->helper('module_status');

        $newStatus = module_status_sanitize($newStatus, 'defects', $oldStatus);

        if ($newStatus === false) {

            $this->session->set_flashdata('error', 'Invalid defect status selected.');

            return false;

        }

        $resolvedAt = $item ? $item->resolved_at : null;

        $verifiedBy = $item && isset($item->verified_by) ? $item->verified_by : null;

        if (in_array($newStatus, array('fixed', 'verified', 'closed'), true) && !in_array($oldStatus, array('fixed', 'verified', 'closed'), true)) {

            $resolvedAt = date('Y-m-d H:i:s');

        } elseif (in_array($oldStatus, array('fixed', 'verified', 'closed'), true) && $newStatus === 'open') {

            $resolvedAt = null;

        }

        if ($newStatus === 'verified' && $oldStatus !== 'verified') {

            $verifiedBy = $uid;

        }

        $due = trim((string) $this->input->post('due_date'));

        return array(

            'project_id' => (int) $this->input->post('project_id'),

            'release_id' => ($release !== '' && $release !== null) ? (int) $release : null,

            'task_id' => ($task !== '' && $task !== null) ? (int) $task : null,

            'title' => trim((string) $this->input->post('title')),

            'description' => (string) $this->input->post('description'),

            'steps_to_reproduce' => (string) $this->input->post('steps_to_reproduce'),

            'severity' => trim((string) $this->input->post('severity')) ?: 'medium',

            'priority' => trim((string) $this->input->post('priority')) ?: 'medium',

            'status' => $newStatus,

            'assigned_to' => ($assigned !== '' && $assigned !== null) ? (int) $assigned : null,

            'due_date' => $due !== '' ? $due : null,

            'resolved_at' => $resolvedAt,

            'verified_by' => $verifiedBy,

        );

    }

}


