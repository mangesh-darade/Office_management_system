<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Helpdesk extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
        require_controller_access('helpdesk', true);
    }

    public function index()
    {
        $uid = (int) $this->session->userdata('user_id');
        $filters = array('status' => trim((string) $this->input->get('status')));
        if (!is_admin_group() && !has_module_access('helpdesk_manage')) {
            $filters['requester_id'] = $uid;
        }
        $rows = $this->eng->list_tickets($filters);
        $this->load->view('helpdesk/index', array('rows' => $rows, 'filters' => $filters, 'can_manage' => has_module_access('helpdesk_manage') || is_admin_group()));
    }

    public function create()
    {
        require_module_access(array('helpdesk', 'helpdesk_manage'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $id = $this->eng->save_ticket(array(
                'ticket_number' => $this->eng->next_ticket_number(),
                'subject' => trim((string) $this->input->post('subject')),
                'description' => trim((string) $this->input->post('description')),
                'priority' => trim((string) $this->input->post('priority')) ?: 'medium',
                'status' => 'open',
                'requester_id' => $uid,
                'assigned_to' => (int) $this->input->post('assigned_to') ?: null,
            ));
            $this->session->set_flashdata('success', 'Ticket created.');
            redirect('helpdesk');
            return;
        }
        $assignees = (has_module_access('helpdesk_manage') || is_admin_group()) ? $this->eng->user_options() : array();
        $this->load->view('helpdesk/form', array('action' => 'create', 'item' => null, 'assignees' => $assignees));
    }

    public function edit($id)
    {
        $item = $this->eng->get_ticket((int) $id);
        if (!$item) {
            show_404();
        }
        $uid = (int) $this->session->userdata('user_id');
        $canManage = has_module_access('helpdesk_manage') || is_admin_group();
        if (!$canManage && (int) $item->requester_id !== $uid) {
            show_error('Access denied.', 403);
        }
        if ($this->input->method() === 'post') {
            $oldStatus = (string) $item->status;
            $newStatus = $canManage ? trim((string) $this->input->post('status')) : $oldStatus;
            $resolvedAt = $item->resolved_at;
            if (in_array($newStatus, array('resolved', 'closed'), true) && !in_array($oldStatus, array('resolved', 'closed'), true)) {
                $resolvedAt = date('Y-m-d H:i:s');
            }
            $data = array(
                'subject' => trim((string) $this->input->post('subject')),
                'description' => trim((string) $this->input->post('description')),
                'priority' => trim((string) $this->input->post('priority')) ?: $item->priority,
                'status' => $newStatus,
                'resolved_at' => $resolvedAt,
            );
            if ($canManage) {
                $data['assigned_to'] = (int) $this->input->post('assigned_to') ?: null;
            }
            $this->eng->save_ticket($data, (int) $id);

            $this->session->set_flashdata('success', 'Ticket updated.');
            redirect('helpdesk');
            return;
        }
        $this->load->view('helpdesk/form', array(
            'action' => 'edit',
            'item' => $item,
            'assignees' => $canManage ? $this->eng->user_options() : array(),
            'can_manage' => $canManage,
        ));
    }
}
