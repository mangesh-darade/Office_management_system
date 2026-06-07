<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Defects extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Defect_model', 'defects');
        require_controller_access('defects', true);
    }

    public function index()
    {
        require_module_access(array('defects_list', 'defects'), true);
        $filters = array(
            'status' => trim((string) $this->input->get('status')),
            'severity' => trim((string) $this->input->get('severity')),
            'project_id' => (int) $this->input->get('project_id'),
            'assigned_to' => (int) $this->input->get('assigned_to'),
            'q' => trim((string) $this->input->get('q')),
        );
        $rows = $this->defects->list_defects($filters);
        $this->load->view('defects/index', array(
            'rows' => $rows,
            'projects' => $this->defects->project_options(),
            'members' => $this->defects->user_options(),
            'filters' => $filters,
        ));
    }

    public function create()
    {
        require_module_access(array('defects_add', 'defects'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $assigned = $this->input->post('assigned_to');
            $release = $this->input->post('release_id');
            $task = $this->input->post('task_id');
            $id = $this->defects->save_defect(array(
                'defect_number' => $this->defects->next_defect_number(),
                'project_id' => (int) $this->input->post('project_id'),
                'release_id' => ($release !== '' && $release !== null) ? (int) $release : null,
                'task_id' => ($task !== '' && $task !== null) ? (int) $task : null,
                'title' => trim((string) $this->input->post('title')),
                'description' => (string) $this->input->post('description'),
                'steps_to_reproduce' => (string) $this->input->post('steps_to_reproduce'),
                'severity' => trim((string) $this->input->post('severity')) ?: 'medium',
                'priority' => trim((string) $this->input->post('priority')) ?: 'medium',
                'status' => trim((string) $this->input->post('status')) ?: 'open',
                'reported_by' => $uid,
                'assigned_to' => ($assigned !== '' && $assigned !== null) ? (int) $assigned : null,
            ));
            $this->session->set_flashdata('success', 'Defect logged.');
            redirect('defects/view/' . $id);
            return;
        }
        $this->load->view('defects/form', array(
            'action' => 'create',
            'item' => null,
            'projects' => $this->defects->project_options(),
            'releases' => $this->defects->release_options(),
            'tasks' => $this->defects->task_options(),
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
        $this->load->view('defects/view', array('item' => $item));
    }

    public function edit($id)
    {
        require_module_access(array('defects_edit', 'defects'), true);
        $item = $this->defects->get_defect((int) $id);
        if (!$item) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $oldStatus = (string) $item->status;
            $newStatus = trim((string) $this->input->post('status')) ?: $oldStatus;
            $resolvedAt = $item->resolved_at;
            if (in_array($newStatus, array('fixed', 'verified', 'closed'), true) && !in_array($oldStatus, array('fixed', 'verified', 'closed'), true)) {
                $resolvedAt = date('Y-m-d H:i:s');
            } elseif (in_array($oldStatus, array('fixed', 'verified', 'closed'), true) && $newStatus === 'open') {
                $resolvedAt = null;
            }
            $assigned = $this->input->post('assigned_to');
            $release = $this->input->post('release_id');
            $task = $this->input->post('task_id');
            $this->defects->save_defect(array(
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
                'resolved_at' => $resolvedAt,
            ), (int) $id);

            if (in_array($newStatus, array('fixed', 'verified', 'closed'), true) && !in_array($oldStatus, array('fixed', 'verified', 'closed'), true)) {
                $assignee = ($assigned !== '' && $assigned !== null) ? (int) $assigned : (int) $item->assigned_to;
                $severity = trim((string) $this->input->post('severity')) ?: (string) $item->severity;
                if ($assignee > 0 && in_array($severity, array('critical', 'high'), true)) {
                    reward_engine_claim('critical_production_issue', array(
                        'user_id' => $assignee,
                        'actor_id' => (int) $this->session->userdata('user_id'),
                        'source_module' => 'defects',
                        'source_record_id' => (int) $id,
                        'reference_label' => $item->defect_number . ': ' . trim((string) $this->input->post('title')),
                    ));
                }
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
        $this->defects->delete_defect((int) $id);
        $this->session->set_flashdata('success', 'Defect deleted.');
        redirect('defects');
    }
}
