<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_clients extends Coaching_Controller {

    protected $coaching_permission = 'coaching_clients';

    public function index()
    {
        $filters = [];
        if ($this->input->get('coach_id')) {
            $filters['coach_id'] = (int) $this->input->get('coach_id');
        }
        $this->load->view('coaching/clients/index', [
            'rows' => $this->coaching->clients_all($filters),
            'coaches' => $this->coaching->coaches_all(),
        ]);
    }

    public function create()
    {
        if ($this->input->method() === 'post') {
            return $this->_save();
        }
        $this->load->view('coaching/clients/form', [
            'row' => null,
            'coaches' => $this->coaching->coaches_all(),
            'assigned' => [],
            'crm_clients' => $this->coaching_crm_client_options(),
            'show_crm_picker' => coaching_crm_available(),
        ]);
    }

    public function edit($id)
    {
        $row = $this->coaching->client_get($id);
        if (!$row) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            return $this->_save((int) $id);
        }
        $this->load->view('coaching/clients/form', [
            'row' => $row,
            'coaches' => $this->coaching->coaches_all(),
            'assigned' => $this->coaching->client_coach_ids((int) $id),
            'crm_clients' => $this->coaching_crm_client_options(),
            'show_crm_picker' => coaching_crm_available(),
        ]);
    }

    public function view($id)
    {
        $row = $this->coaching->client_get($id);
        if (!$row) {
            show_404();
        }
        $this->load->view('coaching/clients/view', [
            'row' => $row,
            'goals' => $this->coaching->goals_for_client((int) $id),
            'homework' => $this->coaching->homework_for_client((int) $id),
            'sessions' => $this->coaching->sessions_list(['client_id' => (int) $id]),
            'invoices' => $this->coaching->invoices_all((int) $id),
        ]);
    }

    private function _save($id = null)
    {
        $full_name = trim((string) $this->input->post('full_name'));
        if ($full_name === '') {
            $this->session->set_flashdata('error', 'Name is required.');
            redirect($id ? 'coaching-clients/edit/' . $id : 'coaching-clients/create');
            return;
        }
        $coach_ids = $this->input->post('coach_ids');
        if (!is_array($coach_ids)) {
            $coach_ids = [];
        }
        $primary = (int) $this->input->post('primary_coach_id');
        $portal = (int) $this->input->post('portal_enabled') === 1;
        $email = trim((string) $this->input->post('email'));

        $data = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => trim((string) $this->input->post('phone')),
            'company' => trim((string) $this->input->post('company')),
            'primary_coach_id' => $primary ?: null,
            'status' => $this->input->post('status') ?: 'active',
            'portal_enabled' => $portal ? 1 : 0,
            'notes' => trim((string) $this->input->post('notes')),
        ];

        if (coaching_crm_available()) {
            $crm_id = (int) $this->input->post('crm_client_id');
            $data['crm_client_id'] = $crm_id > 0 ? $crm_id : null;
        }

        $temp_password = null;
        if ($portal && $email !== '') {
            if (!$id || !$this->coaching->client_get($id)->user_id) {
                $temp_password = bin2hex(random_bytes(4));
                $user_id = $this->coaching->create_portal_user($email, $full_name, $temp_password);
                $data['user_id'] = $user_id;
            }
        }

        if ($id) {
            $this->coaching->client_save($data, $id);
            $client_id = $id;
        } else {
            $client_id = $this->coaching->client_save($data);
        }

        $this->coaching->client_assign_coaches($client_id, $coach_ids, $primary);

        if ($temp_password) {
            $this->session->set_flashdata('success', 'Client saved. Portal password: ' . $temp_password);
        } else {
            $this->session->set_flashdata('success', 'Client saved.');
        }
        redirect('coaching-clients/view/' . $client_id);
    }
}
