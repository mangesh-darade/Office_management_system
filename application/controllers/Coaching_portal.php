<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_portal extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form', 'coaching']);
        $this->load->library('session');
        $this->load->model('Coaching_model', 'coaching');

        $uid = (int) $this->session->userdata('user_id');
        if (!$uid) {
            redirect('auth/login');
            exit;
        }
        $role_id = (int) $this->session->userdata('role_id');
        if ($role_id !== ROLE_COACHING_CLIENT && $role_id !== 1) {
            show_error('Client portal access only.', 403);
            exit;
        }
    }

    private function _client()
    {
        if ((int) $this->session->userdata('role_id') === 1) {
            $cid = (int) $this->input->get('client_id');
            return $cid ? $this->coaching->client_get($cid) : null;
        }
        return $this->coaching->client_by_user((int) $this->session->userdata('user_id'));
    }

    public function index()
    {
        $client = $this->_client();
        if (!$client) {
            show_error('No coaching client profile linked to this account.', 404);
        }
        $cid = (int) $client->id;
        $branding = $this->coaching->branding();
        $pending_installments = [];
        foreach ($this->coaching->invoices_all($cid) as $inv) {
            foreach ($this->coaching->installments_for_invoice((int) $inv->id) as $inst) {
                if ($inst->status === 'pending') {
                    $pending_installments[] = $inst;
                }
            }
        }
        $this->load->view('coaching/portal/dashboard', [
            'client' => $client,
            'branding' => $branding,
            'sessions' => $this->coaching->sessions_list(['client_id' => $cid]),
            'goals' => $this->coaching->goals_for_client($cid),
            'homework' => $this->coaching->homework_for_client($cid),
            'resources' => $this->coaching->resources_list($cid),
            'invoices' => $this->coaching->invoices_all($cid),
            'pending_installments' => $pending_installments,
        ]);
    }

    public function homework_done($id)
    {
        $client = $this->_client();
        if (!$client) {
            show_404();
        }
        $hw = $this->coaching->homework_get($id);
        if (!$hw || (int) $hw->coaching_client_id !== (int) $client->id) {
            show_404();
        }
        $this->coaching->homework_save([
            'status' => 'done',
            'client_notes' => trim((string) $this->input->post('client_notes')),
        ], (int) $id);
        redirect('coaching-portal');
    }
}
