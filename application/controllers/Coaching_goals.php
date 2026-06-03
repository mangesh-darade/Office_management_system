<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_goals extends Coaching_Controller {

    protected $coaching_permission = 'coaching_goals';

    public function index()
    {
        $client_id = (int) $this->input->get('client_id');
        $clients = $this->coaching->clients_all();
        $goals = [];
        $homework = [];
        if ($client_id) {
            $goals = $this->coaching->goals_for_client($client_id);
            $homework = $this->coaching->homework_for_client($client_id);
        }
        $this->load->view('coaching/goals/index', compact('clients', 'client_id', 'goals', 'homework'));
    }

    public function save_goal()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $client_id = (int) $this->input->post('coaching_client_id');
        $data = [
            'coaching_client_id' => $client_id,
            'title' => trim((string) $this->input->post('title')),
            'description' => trim((string) $this->input->post('description')),
            'target_date' => $this->input->post('target_date') ?: null,
            'progress_pct' => min(100, max(0, (int) $this->input->post('progress_pct'))),
            'status' => $this->input->post('status') ?: 'active',
        ];
        $this->coaching->goal_save($data, $id ?: null);
        $this->session->set_flashdata('success', 'Goal saved.');
        redirect('coaching-goals?client_id=' . $client_id);
    }

    public function save_homework()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $client_id = (int) $this->input->post('coaching_client_id');
        $data = [
            'coaching_client_id' => $client_id,
            'session_id' => $this->input->post('session_id') ? (int) $this->input->post('session_id') : null,
            'title' => trim((string) $this->input->post('title')),
            'description' => trim((string) $this->input->post('description')),
            'due_date' => $this->input->post('due_date') ?: null,
            'status' => $this->input->post('status') === 'done' ? 'done' : 'pending',
        ];
        $this->coaching->homework_save($data, $id ?: null);
        $this->session->set_flashdata('success', 'Homework saved.');
        redirect('coaching-goals?client_id=' . $client_id);
    }
}
