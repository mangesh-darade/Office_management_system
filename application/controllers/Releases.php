<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Releases extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards', 'release_notify'));
        $this->load->library(array('session', 'email'));
        $this->load->model('Engagement_model', 'eng');
        $this->load->model('Defect_model', 'defects');
        require_controller_access('releases', true);
    }

    public function index()
    {
        $rows = $this->eng->list_releases(array(
            'status' => trim((string) $this->input->get('status')),
            'project_id' => (int) $this->input->get('project_id'),
        ));
        $this->load->view('releases/index', array(
            'rows' => $rows,
            'projects' => $this->eng->project_options(),
            'filters' => array(
                'status' => $this->input->get('status'),
                'project_id' => (int) $this->input->get('project_id'),
            ),
        ));
    }

    public function create()
    {
        require_module_access(array('releases', 'releases_add'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $id = $this->eng->save_release(array(
                'project_id' => (int) $this->input->post('project_id'),
                'version' => trim((string) $this->input->post('version')),
                'title' => trim((string) $this->input->post('title')),
                'description' => (string) $this->input->post('description'),
                'planned_date' => $this->input->post('planned_date') ?: null,
                'status' => trim((string) $this->input->post('status')) ?: 'planned',
                'created_by' => $uid,
            ));
            $this->eng->save_release_notes($id, release_parse_note_points_post($this->input));
            $this->session->set_flashdata('success', 'Release created.');
            redirect('releases/edit/' . $id);
            return;
        }
        $this->load->view('releases/form', $this->form_view_data('create', null));
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
            $releasedAt = $item->released_at;
            if ($newStatus === 'released' && $oldStatus !== 'released') {
                $releasedAt = date('Y-m-d H:i:s');
            }
            $this->eng->save_release(array(
                'project_id' => (int) $this->input->post('project_id'),
                'version' => trim((string) $this->input->post('version')),
                'title' => trim((string) $this->input->post('title')),
                'description' => (string) $this->input->post('description'),
                'planned_date' => $this->input->post('planned_date') ?: null,
                'status' => $newStatus,
                'released_at' => $releasedAt,
            ), $id);
            $this->eng->save_release_notes($id, release_parse_note_points_post($this->input));

            if ($newStatus === 'released' && $oldStatus !== 'released') {
                reward_engine_dispatch('release_completed', array(
                    'user_id' => (int) $item->created_by,
                    'source_module' => 'releases',
                    'source_record_id' => $id,
                    'reference_label' => 'Release ' . trim((string) $this->input->post('version')) . ': ' . trim((string) $this->input->post('title')),
                    'payload' => array(),
                ));
            }

            if ($this->input->post('send_notes_after_save') === '1' && release_can_send_notes()) {
                $user_ids = $this->input->post('user_ids');
                if (is_array($user_ids) && !empty($user_ids)) {
                    $this->dispatch_release_notes($id, $user_ids);
                    redirect('releases');
                    return;
                }
            }

            $this->session->set_flashdata('success', 'Release updated.');
            redirect('releases');
            return;
        }
        $this->load->view('releases/form', $this->form_view_data('edit', $item));
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
            'users' => array(),
            'can_send_notes' => release_can_send_notes(),
        );
        if ($item) {
            $notes = $this->eng->list_release_notes((int) $item->id);
            foreach ($notes as $n) {
                $data['note_points'][] = (string) $n->point_text;
            }
            $data['related_defects'] = $this->defects->list_by_release((int) $item->id);
        }
        if ($data['can_send_notes']) {
            $this->load->model('Reminder_model', 'reminders');
            $this->reminders->ensure_schema();
            $data['users'] = $this->reminders->all_users();
        }
        return $data;
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
