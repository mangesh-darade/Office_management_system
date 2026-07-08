<?php

defined('BASEPATH') OR exit('No direct script access allowed');



class Releases extends CI_Controller

{

    public function __construct()

    {

        parent::__construct();

        $this->load->database();

        $this->load->helper(array('url', 'form', 'permission', 'release_notify', 'change_tracker', 'activity', 'defects_releases'));

        $this->load->library(array('session', 'email', 'pagination'));

        $this->load->model('Engagement_model', 'eng');

        $this->load->model('Defect_model', 'defects');

        require_controller_access('releases', true);

    }



    public function index($offset = 0)

    {

        require_module_access(array('releases_list', 'releases'), true);

        $filters = array(

            'status' => trim((string) $this->input->get('status')),

            'project_id' => (int) $this->input->get('project_id'),

        );

        $per_page = 25;

        $total = $this->eng->count_releases($filters);

        $config = array(

            'base_url' => site_url('releases/index'),

            'total_rows' => $total,

            'per_page' => $per_page,

            'uri_segment' => 3,

            'reuse_query_string' => true,

            'full_tag_open' => '<nav><ul class="pagination pagination-sm justify-content-center mb-0">',

            'full_tag_close' => '</ul></nav>',

            'attributes' => array('class' => 'page-link'),

        );

        $this->pagination->initialize($config);

        $rows = $this->eng->list_releases($filters, $per_page, (int) $offset);

        $this->load->view('releases/index', array(

            'rows' => $rows,

            'projects' => $this->eng->project_options(),

            'filters' => $filters,

            'pagination_links' => $this->pagination->create_links(),

            'total' => $total,

        ));

    }



    public function export()

    {

        require_module_access(array('releases_export', 'releases'), true);

        $filters = array(

            'status' => trim((string) $this->input->get('status')),

            'project_id' => (int) $this->input->get('project_id'),

        );

        $rows = $this->eng->list_releases($filters);

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename="releases_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, array('Version', 'Title', 'Project', 'Status', 'Planned Date', 'Released At', 'Notes Sent'), ',', '"', '\\');

        foreach ($rows as $r) {

            fputcsv($out, array(

                $r->version,

                $r->title,

                $r->project_name,

                $r->status,

                $r->planned_date,

                $r->released_at,

                isset($r->notes_sent_at) ? $r->notes_sent_at : '',

            ), ',', '"', '\\');

        }

        fclose($out);

        exit;

    }



    public function import()

    {

        require_module_access(array('releases_add', 'releases'), true);

        if ($this->input->method() === 'post') {

            $uid = (int) $this->session->userdata('user_id');

            if ($uid < 1) {

                redirect('auth/login');

                return;

            }

            $this->load->helper(array('csv_import', 'module_status', 'change_tracker', 'activity'));

            $opened = csv_import_open('file');

            if (!$opened['ok']) {

                csv_import_fail_redirect($opened['error'], 'releases/import');

                return;

            }

            $columns = csv_import_require_columns($opened['map'], array('version', 'title'), array(array('project_name', 'project', 'project_id')));

            if (!$columns['ok']) {

                fclose($opened['handle']);

                csv_import_fail_redirect($columns['error'], 'releases/import');

                return;

            }

            $inserted = 0;

            $skipped = 0;

            $row_errors = array();

            $project_cache = array();

            $line = 1;

            $allowed_status = array('planned', 'in_progress', 'released', 'cancelled');

            $prev_debug = $this->db->db_debug;

            $this->db->db_debug = false;

            while (($row = fgetcsv($opened['handle'])) !== false) {

                $line++;

                $version = csv_import_get($opened['map'], $row, 'version');

                $title = csv_import_get($opened['map'], $row, 'title');

                if ($version === '' || $title === '') {

                    $skipped++;

                    csv_import_add_row_error($row_errors, $line, 'Missing version or title.');

                    continue;

                }

                $project_id = csv_import_resolve_project_id($this->db, $opened['map'], $row, $project_cache);

                if ($project_id <= 0) {

                    $skipped++;

                    csv_import_add_row_error($row_errors, $line, 'Unknown project name or code.');

                    continue;

                }

                if ($this->eng->version_exists($project_id, $version)) {

                    $skipped++;

                    csv_import_add_row_error($row_errors, $line, 'Version already exists for this project.');

                    continue;

                }

                $status = csv_import_validate_enum(

                    csv_import_get($opened['map'], $row, 'status', 'planned'),

                    $allowed_status,

                    'planned',

                    $row_errors,

                    $line,

                    'status'

                );

                if ($status === false) {

                    $skipped++;

                    continue;

                }

                $planned_raw = csv_import_get($opened['map'], $row, 'planned_date', '');

                $planned_date = null;

                if ($planned_raw !== '') {

                    $planned_ts = strtotime($planned_raw);

                    if ($planned_ts) {

                        $planned_date = date('Y-m-d', $planned_ts);

                    }

                }

                $id = $this->eng->save_release(array(

                    'project_id' => $project_id,

                    'version' => $version,

                    'title' => $title,

                    'description' => csv_import_get($opened['map'], $row, 'description', null) ?: null,

                    'planned_date' => $planned_date,

                    'status' => $status,

                    'created_by' => $uid,

                ));

                if ($id) {

                    $inserted++;

                    auto_log_insert('releases', 'project_releases', $id, array('version' => $version), 'Release: ' . $version);

                } else {

                    $skipped++;

                    $db_error = $this->db->error();

                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';

                    csv_import_add_row_error($row_errors, $line, $reason);

                    log_message('error', 'Release import error: ' . $reason);

                }

            }

            $this->db->db_debug = $prev_debug;

            fclose($opened['handle']);

            csv_import_finish($inserted, $skipped, $row_errors, 'releases', 'releases', 'releases/import');

            return;

        }

        $this->load->view('releases/import');

    }



    public function view($id)

    {

        require_module_access(array('releases_view', 'releases_list', 'releases'), true);

        $item = $this->eng->get_release((int) $id);

        if (!$item) {

            show_404();

        }

        $release = $this->eng->get_release_with_project((int) $id);

        $notes = $this->eng->list_release_notes((int) $id);

        $related = $this->defects->list_by_release((int) $id);

        $this->load->view('releases/view', array(

            'item' => $release ? $release : $item,

            'note_points' => $notes,

            'related_defects' => $related,

        ));

    }



    public function create()

    {

        require_module_access(array('releases', 'releases_add'), true);

        if ($this->input->method() === 'post') {

            $uid = (int) $this->session->userdata('user_id');

            $project_id = (int) $this->input->post('project_id');

            $version = trim((string) $this->input->post('version'));

            if ($this->eng->version_exists($project_id, $version)) {

                $this->session->set_flashdata('error', 'This version already exists for the selected project.');

                redirect('releases/create');

                return;

            }

            $this->load->helper('module_status');

            $releaseStatus = module_status_sanitize(trim((string) $this->input->post('status')), 'releases', 'planned');

            if ($releaseStatus === false) {

                $this->session->set_flashdata('error', 'Invalid release status selected.');

                redirect('releases/create');

                return;

            }

            $id = $this->eng->save_release(array(

                'project_id' => $project_id,

                'version' => $version,

                'title' => trim((string) $this->input->post('title')),

                'description' => (string) $this->input->post('description'),

                'planned_date' => $this->input->post('planned_date') ?: null,

                'status' => $releaseStatus,

                'created_by' => $uid,

            ));

            $this->eng->save_release_notes($id, release_parse_note_points_post($this->input));

            auto_log_insert('releases', 'project_releases', $id, array('version' => $version), 'Release: ' . $version);

            $this->session->set_flashdata('success', 'Release created.');

            redirect('releases/view/' . $id);

            return;

        }

        $data = $this->form_view_data('create', null);

        $preselect_project = (int) $this->input->get('project_id');

        if ($preselect_project > 0) {

            $data['item'] = (object) array('project_id' => $preselect_project);

        }

        $this->load->view('releases/form', $data);

    }



    public function edit($id)

    {

        require_module_access(array('releases', 'releases_edit'), true);

        $id = (int) $id;

        $item = $this->eng->get_release($id);

        if (!$item) {

            show_404();

        }

        if ($this->input->method() === 'post') {

            $oldStatus = (string) $item->status;

            $newStatus = trim((string) $this->input->post('status')) ?: $oldStatus;

            $this->load->helper('module_status');

            $newStatus = module_status_sanitize($newStatus, 'releases', $oldStatus);

            if ($newStatus === false) {

                $this->session->set_flashdata('error', 'Invalid release status selected.');

                redirect('releases/edit/' . $id);

                return;

            }

            $project_id = (int) $this->input->post('project_id');

            $version = trim((string) $this->input->post('version'));

            if ($this->eng->version_exists($project_id, $version, $id)) {

                $this->session->set_flashdata('error', 'This version already exists for the selected project.');

                redirect('releases/edit/' . $id);

                return;

            }

            $releasedAt = $item->released_at;

            if ($newStatus === 'released' && $oldStatus !== 'released') {

                $releasedAt = date('Y-m-d H:i:s');

            }

            $old_data = track_changes_before('project_releases', $id);

            $payload = array(

                'project_id' => $project_id,

                'version' => $version,

                'title' => trim((string) $this->input->post('title')),

                'description' => (string) $this->input->post('description'),

                'planned_date' => $this->input->post('planned_date') ?: null,

                'status' => $newStatus,

                'released_at' => $releasedAt,

            );

            $this->eng->save_release($payload, $id);

            $this->eng->save_release_notes($id, release_parse_note_points_post($this->input));

            track_changes_after('releases', 'project_releases', $id, $old_data, $payload, 'Release: ' . $version);



            if ($newStatus !== $oldStatus) {

                $member_ids = $this->_project_member_user_ids($project_id);

                release_notify_status_change($id, $member_ids, $payload['title'], $oldStatus, $newStatus);

            }



            if ($this->input->post('send_notes_after_save') === '1' && release_can_send_notes()) {

                $user_ids = $this->input->post('user_ids');

                if (is_array($user_ids) && !empty($user_ids)) {

                    $this->dispatch_release_notes($id, $user_ids);

                    redirect('releases/view/' . $id);

                    return;

                }

            }



            $this->session->set_flashdata('success', 'Release updated.');

            redirect('releases/view/' . $id);

            return;

        }

        $this->load->view('releases/form', $this->form_view_data('edit', $item));

    }



    public function add_all_fixed($id)

    {

        require_module_access(array('releases', 'releases_edit'), true);

        if ($this->input->method() !== 'post') {

            show_error('Invalid request', 405);

        }

        $id = (int) $id;

        $item = $this->eng->get_release($id);

        if (!$item) {

            show_404();

        }

        $fixed = $this->defects->list_fixed_by_release($id, (int) $item->project_id);

        $existing = $this->eng->list_release_notes($id);

        $existing_text = array();

        foreach ($existing as $n) {

            $existing_text[(string) $n->point_text] = true;

        }

        $points = array();

        foreach ($existing as $n) {

            $points[] = (string) $n->point_text;

        }

        $added = 0;

        foreach ($fixed as $d) {

            $line = trim((string) $d->defect_number . ': ' . (string) $d->title);

            if ($line === ': ' || isset($existing_text[$line])) {

                continue;

            }

            $points[] = $line;

            $existing_text[$line] = true;

            $added++;

        }

        $this->eng->save_release_notes($id, $points);

        log_activity('releases', 'updated', $id, 'Added ' . $added . ' fixed defect(s) to release notes');

        $this->session->set_flashdata('success', $added > 0 ? ('Added ' . $added . ' fixed defect(s) to release notes.') : 'No new fixed defects to add.');

        redirect('releases/edit/' . $id);

    }



    public function delete($id)

    {

        require_module_access(array('releases_delete', 'releases'), true);

        if ($this->input->method() !== 'post') {

            show_error('Invalid request', 405);

        }

        $id = (int) $id;

        $item = $this->eng->get_release($id);

        if (!$item) {

            show_404();

        }

        auto_log_delete('releases', 'project_releases', $id, (array) $item, 'Release deleted: ' . $item->version);

        $this->eng->delete_release($id);

        $this->session->set_flashdata('success', 'Release deleted.');

        redirect('releases');

    }



    public function send_notes($id)

    {

        require_module_access(array('releases', 'releases_edit', 'releases_send_notes'), true);

        if ($this->input->method() !== 'post') {

            show_404();

        }

        $id = (int) $id;

        $item = $this->eng->get_release($id);

        if (!$item) {

            show_404();

        }

        $user_ids = $this->input->post('user_ids');

        if (!is_array($user_ids) || empty($user_ids)) {

            $this->session->set_flashdata('error', 'Select at least one recipient.');

            redirect('releases/edit/' . $id);

            return;

        }

        $posted_points = release_parse_note_points_post($this->input);

        if (!empty($posted_points)) {

            $this->eng->save_release_notes($id, $posted_points);

        }

        $this->dispatch_release_notes($id, $user_ids);

        redirect('releases/edit/' . $id);

    }



    private function form_view_data($action, $item)

    {

        $data = array(

            'action' => $action,

            'item' => $item,

            'projects' => $this->eng->project_options(),

            'note_points' => array(),

            'related_defects' => array(),

            'fixed_defects' => array(),

            'users' => array(),

            'can_send_notes' => release_can_send_notes(),

        );

        if ($item) {

            $notes = $this->eng->list_release_notes((int) $item->id);

            foreach ($notes as $n) {

                $data['note_points'][] = (string) $n->point_text;

            }

            $data['related_defects'] = $this->defects->list_by_release((int) $item->id);

            $data['fixed_defects'] = $this->defects->list_fixed_by_release((int) $item->id, (int) $item->project_id);

        }

        if ($data['can_send_notes']) {

            $this->load->model('Reminder_model', 'reminders');

            $this->reminders->ensure_schema();

            $data['users'] = $this->reminders->all_users();

        }

        return $data;

    }



    private function _project_member_user_ids($project_id)

    {

        if (!$this->db->table_exists('project_members')) {

            return array();

        }

        $rows = $this->db->select('user_id')->from('project_members')->where('project_id', (int) $project_id)->get()->result();

        $ids = array();

        foreach ($rows as $r) {

            $ids[] = (int) $r->user_id;

        }

        return $ids;

    }



    private function dispatch_release_notes($release_id, array $user_ids)

    {

        $release = $this->eng->get_release_with_project((int) $release_id);

        if (!$release) {

            $this->session->set_flashdata('error', 'Release not found.');

            return;

        }

        $notes = $this->eng->list_release_notes((int) $release_id);

        if (empty($notes)) {

            $this->session->set_flashdata('error', 'Add at least one release note point before sending email.');

            return;

        }

        $this->load->model('Reminder_model', 'reminders');

        $this->reminders->ensure_schema();

        $role_id = (int) $this->session->userdata('role_id');

        $this->load->helper('reminders_user');

        $from = reminders_admin_from_post($role_id, $this->input);

        $result = release_send_notes_to_users(

            $this->db,

            $this->reminders,

            $this->email,

            $release,

            $notes,

            $user_ids,

            $from

        );

        if ($result['sent'] > 0) {

            $this->eng->mark_release_notes_sent((int) $release_id);

            release_notify_notes_recipients($user_ids, (int) $release_id, (string) $release->title);

        }

        $msg = 'Release notes sent to ' . $result['sent'] . ' recipient(s).';

        if ($result['skipped'] > 0) {

            $msg .= ' Skipped ' . $result['skipped'] . ' (no email).';

        }

        if ($result['failed'] > 0) {

            $msg .= ' Failed: ' . $result['failed'] . '.';

        }

        if ($result['sent'] > 0) {

            $this->session->set_flashdata('success', $msg);

        } else {

            $this->session->set_flashdata('error', $msg);

        }

    }

}


