<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_sessions extends Coaching_Controller {

    protected $coaching_permission = 'coaching_sessions';

    public function index()
    {
        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-7 days'));
        $to = $this->input->get('to') ?: date('Y-m-d', strtotime('+30 days'));
        $filters = [
            'from' => $from . ' 00:00:00',
            'to' => $to . ' 23:59:59',
        ];
        if ($this->input->get('coach_id')) {
            $filters['coach_id'] = (int) $this->input->get('coach_id');
        }
        $this->load->view('coaching/sessions/index', [
            'rows' => $this->coaching->sessions_list($filters),
            'coaches' => $this->coaching->coaches_all(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function calendar()
    {
        $month = $this->input->get('month') ?: date('Y-m');
        $start = $month . '-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($start));
        $this->load->view('coaching/sessions/calendar', [
            'rows' => $this->coaching->sessions_list(['from' => $start, 'to' => $end]),
            'month' => $month,
        ]);
    }

    public function create()
    {
        if ($this->input->method() === 'post') {
            return $this->_save();
        }
        $this->load->view('coaching/sessions/form', [
            'row' => null,
            'clients' => $this->coaching->clients_all(),
            'coaches' => $this->coaching->coaches_all(),
        ]);
    }

    public function edit($id)
    {
        $row = $this->coaching->session_get($id);
        if (!$row) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            return $this->_save((int) $id);
        }
        $this->load->view('coaching/sessions/form', [
            'row' => $row,
            'clients' => $this->coaching->clients_all(),
            'coaches' => $this->coaching->coaches_all(),
        ]);
    }

    public function delete($id)
    {
        $this->coaching->session_save(['status' => 'cancelled'], (int) $id);
        $this->session->set_flashdata('success', 'Session cancelled.');
        redirect('coaching-sessions');
    }

    private function _save($id = null)
    {
        $scheduled = trim((string) $this->input->post('scheduled_at'));
        $data = [
            'coaching_client_id' => (int) $this->input->post('coaching_client_id'),
            'coach_id' => (int) $this->input->post('coach_id'),
            'title' => trim((string) $this->input->post('title')) ?: 'Review Session',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($scheduled)),
            'duration_minutes' => (int) $this->input->post('duration_minutes') ?: 60,
            'location' => trim((string) $this->input->post('location')),
            'meeting_link' => trim((string) $this->input->post('meeting_link')),
            'status' => $this->input->post('status') ?: 'scheduled',
            'notes_internal' => trim((string) $this->input->post('notes_internal')),
            'notes_client' => trim((string) $this->input->post('notes_client')),
            'homework_summary' => trim((string) $this->input->post('homework_summary')),
        ];
        if (!$id) {
            $data['created_by'] = (int) $this->session->userdata('user_id');
        }
        $session_id = $this->coaching->session_save($data, $id);
        if (!$id && $data['status'] === 'scheduled') {
            $this->load->helper('coaching_notify');
            if (coaching_email_session_confirmation($session_id)) {
                $this->coaching->session_mark_reminder_sent($session_id, 'confirmation_sent');
            }
        }
        $this->session->set_flashdata('success', 'Session saved.' . (!$id ? ' Confirmation email sent if SMTP is configured.' : ''));
        redirect('coaching-sessions');
    }
}
