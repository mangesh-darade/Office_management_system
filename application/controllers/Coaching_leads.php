<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_leads extends Coaching_Controller {

    protected $coaching_permission = 'coaching_leads';

    protected function coaching_skip_access()
    {
        return $this->router->fetch_method() === 'workshop_register';
    }

    public function index()
    {
        $this->load->view('coaching/leads/index', [
            'rows' => $this->coaching->leads_all(),
            'coaches' => $this->coaching->coaches_all(),
        ]);
    }

    public function create()
    {
        if ($this->input->method() === 'post') {
            $this->coaching->lead_save([
                'full_name' => trim((string) $this->input->post('full_name')),
                'email' => trim((string) $this->input->post('email')),
                'phone' => trim((string) $this->input->post('phone')),
                'source' => trim((string) $this->input->post('source')),
                'status' => 'new',
                'notes' => trim((string) $this->input->post('notes')),
                'assigned_coach_id' => $this->input->post('assigned_coach_id') ? (int) $this->input->post('assigned_coach_id') : null,
            ]);
            $this->session->set_flashdata('success', 'Lead created.');
            redirect('coaching-leads');
            return;
        }
        $this->load->view('coaching/leads/form', ['row' => null, 'coaches' => $this->coaching->coaches_all()]);
    }

    public function edit($id)
    {
        $row = $this->coaching->lead_get($id);
        if (!$row) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $this->coaching->lead_save([
                'full_name' => trim((string) $this->input->post('full_name')),
                'email' => trim((string) $this->input->post('email')),
                'phone' => trim((string) $this->input->post('phone')),
                'source' => trim((string) $this->input->post('source')),
                'status' => $this->input->post('status'),
                'notes' => trim((string) $this->input->post('notes')),
                'assigned_coach_id' => $this->input->post('assigned_coach_id') ? (int) $this->input->post('assigned_coach_id') : null,
            ], (int) $id);
            $this->session->set_flashdata('success', 'Lead updated.');
            redirect('coaching-leads');
            return;
        }
        $this->load->view('coaching/leads/form', ['row' => $row, 'coaches' => $this->coaching->coaches_all()]);
    }

    public function convert($id)
    {
        $lead = $this->coaching->lead_get($id);
        if (!$lead) {
            show_404();
        }
        $client_id = $this->coaching->client_save([
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'primary_coach_id' => $lead->assigned_coach_id,
            'status' => 'active',
        ]);
        $this->coaching->lead_save(['status' => 'converted', 'converted_client_id' => $client_id], (int) $id);
        $this->session->set_flashdata('success', 'Lead converted to client.');
        redirect('coaching-clients/view/' . $client_id);
    }

    public function workshops()
    {
        $this->load->view('coaching/leads/workshops', ['rows' => $this->coaching->workshops_all()]);
    }

    public function workshop_form($id = null)
    {
        $row = $id ? $this->coaching->workshop_get($id) : null;
        if ($id && !$row) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $data = [
                'title' => trim((string) $this->input->post('title')),
                'description' => trim((string) $this->input->post('description')),
                'workshop_date' => $this->input->post('workshop_date') ? date('Y-m-d H:i:s', strtotime($this->input->post('workshop_date'))) : null,
                'location' => trim((string) $this->input->post('location')),
                'online_link' => trim((string) $this->input->post('online_link')),
                'capacity' => (int) $this->input->post('capacity'),
                'status' => $this->input->post('status') ?: 'draft',
            ];
            $wid = $this->coaching->workshop_save($data, $id ? (int) $id : null);
            $this->session->set_flashdata('success', 'Workshop saved.');
            redirect('coaching-leads/workshops');
            return;
        }
        $this->load->view('coaching/leads/workshop_form', ['row' => $row]);
    }

    /** Public registration — no login */
    public function workshop_register($workshop_id)
    {
        $workshop = $this->coaching->workshop_get($workshop_id);
        if (!$workshop || $workshop->status !== 'published') {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $this->coaching->workshop_register((int) $workshop_id, [
                'full_name' => trim((string) $this->input->post('full_name')),
                'email' => trim((string) $this->input->post('email')),
                'phone' => trim((string) $this->input->post('phone')),
            ]);
            $this->load->view('coaching/leads/register_success', ['workshop' => $workshop]);
            return;
        }
        $this->load->view('coaching/leads/register', ['workshop' => $workshop]);
    }
}
