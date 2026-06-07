<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Events extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
        require_controller_access('events', true);
    }

    public function index()
    {
        $rows = $this->eng->list_events($this->input->get('upcoming') === '1');
        $this->load->view('events/index', array('rows' => $rows));
    }

    public function create()
    {
        require_module_access(array('events', 'events_add'), true);
        if ($this->input->method() === 'post') {
            $this->eng->save_event(array(
                'title' => trim((string) $this->input->post('title')),
                'description' => (string) $this->input->post('description'),
                'location' => trim((string) $this->input->post('location')),
                'start_at' => str_replace('T', ' ', (string) $this->input->post('start_at')),
                'end_at' => $this->input->post('end_at') ? str_replace('T', ' ', (string) $this->input->post('end_at')) : null,
                'organizer_id' => (int) $this->session->userdata('user_id'),
                'is_active' => 1,
            ));
            $this->session->set_flashdata('success', 'Event created.');
            redirect('events');
            return;
        }
        $this->load->view('events/form', array('action' => 'create', 'item' => null));
    }

    public function edit($id)
    {
        require_module_access(array('events', 'events_edit'), true);
        $item = $this->eng->get_event((int) $id);
        if (!$item) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $this->eng->save_event(array(
                'title' => trim((string) $this->input->post('title')),
                'description' => (string) $this->input->post('description'),
                'location' => trim((string) $this->input->post('location')),
                'start_at' => str_replace('T', ' ', (string) $this->input->post('start_at')),
                'end_at' => $this->input->post('end_at') ? str_replace('T', ' ', (string) $this->input->post('end_at')) : null,
                'is_active' => (int) $this->input->post('is_active'),
            ), (int) $id);
            $this->session->set_flashdata('success', 'Event updated.');
            redirect('events');
            return;
        }
        $this->load->view('events/form', array('action' => 'edit', 'item' => $item));
    }
}
