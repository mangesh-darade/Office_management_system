<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Releases extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
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
            $this->session->set_flashdata('success', 'Release created.');
            redirect('releases/edit/' . $id);
            return;
        }
        $this->load->view('releases/form', array('action' => 'create', 'item' => null, 'projects' => $this->eng->project_options()));
    }

    public function edit($id)
    {
        require_module_access(array('releases', 'releases_edit'), true);
        $item = $this->eng->get_release((int) $id);
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
            ), (int) $id);

            if ($newStatus === 'released' && $oldStatus !== 'released') {
                reward_engine_dispatch('release_completed', array(
                    'user_id' => (int) $item->created_by,
                    'source_module' => 'releases',
                    'source_record_id' => (int) $id,
                    'reference_label' => 'Release ' . $item->version . ': ' . $item->title,
                    'payload' => array(),
                ));
            }

            $this->session->set_flashdata('success', 'Release updated.');
            redirect('releases');
            return;
        }
        $this->load->view('releases/form', array('action' => 'edit', 'item' => $item, 'projects' => $this->eng->project_options()));
    }
}
