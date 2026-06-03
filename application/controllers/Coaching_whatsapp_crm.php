<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_whatsapp_crm extends Coaching_Controller {

    protected $coaching_permission = 'coaching_whatsapp_crm';

    public function index()
    {
        $this->load->view('coaching/whatsapp/index', [
            'enquiries' => $this->coaching->enquiries_all(),
            'broadcasts' => $this->coaching->broadcasts_all(),
        ]);
    }

    public function save_enquiry()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $this->coaching->enquiry_save([
            'phone' => trim((string) $this->input->post('phone')),
            'contact_name' => trim((string) $this->input->post('contact_name')),
            'message' => trim((string) $this->input->post('message')),
            'status' => $this->input->post('status') ?: 'open',
            'assigned_user_id' => $this->input->post('assigned_user_id') ? (int) $this->input->post('assigned_user_id') : null,
        ], $id ?: null);
        $this->session->set_flashdata('success', 'Enquiry saved.');
        redirect('coaching-whatsapp-crm');
    }

    public function broadcast()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->helper('api_integration');
        $message = trim((string) $this->input->post('message'));
        $audience = $this->input->post('audience') ?: 'all_clients';
        $phones = array();
        if ($audience === 'all_clients') {
            $rows = $this->db->select('phone')->from('coaching_clients')->where('phone !=', '')->where('phone IS NOT NULL', null, false)->get()->result();
            foreach ($rows as $r) {
                if ($r->phone) {
                    $phones[] = $r->phone;
                }
            }
        }
        $sent = 0;
        foreach (array_unique($phones) as $phone) {
            $result = send_whatsapp_message($phone, $message);
            if (!empty($result['success'])) {
                $sent++;
            }
        }
        $this->coaching->broadcast_save([
            'name' => trim((string) $this->input->post('name')) ?: 'Broadcast ' . date('Y-m-d H:i'),
            'audience_filter' => json_encode(array('audience' => $audience)),
            'message' => $message,
            'sent_count' => $sent,
            'sent_at' => date('Y-m-d H:i:s'),
            'created_by' => (int) $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Broadcast sent to ' . $sent . ' contacts (requires Twilio config).');
        redirect('coaching-whatsapp-crm');
    }
}
