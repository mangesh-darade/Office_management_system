<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Certifications extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
        require_controller_access('certifications', true);
    }

    public function index()
    {
        $uid = (int) $this->session->userdata('user_id');
        $filters = array('status' => trim((string) $this->input->get('status')));
        if (!is_admin_group() && !has_module_access('certifications_approve')) {
            $filters['user_id'] = $uid;
        }
        $rows = $this->eng->list_certifications($filters);
        $this->load->view('certifications/index', array(
            'rows' => $rows,
            'can_approve' => has_module_access('certifications_approve') || is_admin_group(),
        ));
    }

    public function create()
    {
        require_module_access(array('certifications'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $targetUser = (int) $this->input->post('user_id') ?: $uid;
            if ($targetUser !== $uid && !has_module_access('certifications_approve') && !is_admin_group()) {
                show_error('Access denied.', 403);
            }
            $this->eng->save_certification(array(
                'user_id' => $targetUser,
                'cert_name' => trim((string) $this->input->post('cert_name')),
                'issuer' => trim((string) $this->input->post('issuer')),
                'certified_on' => $this->input->post('certified_on') ?: null,
                'expires_on' => $this->input->post('expires_on') ?: null,
                'credential_id' => trim((string) $this->input->post('credential_id')),
                'evidence_url' => trim((string) $this->input->post('evidence_url')),
                'status' => 'pending',
                'submitted_by' => $uid,
            ));
            $this->session->set_flashdata('success', 'Certification submitted for approval.');
            redirect('certifications');
            return;
        }
        $this->load->view('certifications/form', array('action' => 'create', 'item' => null));
    }

    public function approve($id)
    {
        require_module_access(array('certifications_approve', 'certifications'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $item = $this->eng->get_certification((int) $id);
        if (!$item || $item->status !== 'pending') {
            show_404();
        }
        $approver = (int) $this->session->userdata('user_id');
        $this->eng->save_certification(array(
            'status' => 'approved',
            'approved_by' => $approver,
            'approved_at' => date('Y-m-d H:i:s'),
            'review_comment' => trim((string) $this->input->post('review_comment')),
        ), (int) $id);

        reward_engine_dispatch('certification_approved', array(
            'user_id' => (int) $item->user_id,
            'source_module' => 'certifications',
            'source_record_id' => (int) $id,
            'reference_label' => $item->cert_name,
            'actor_id' => $approver,
            'payload' => array(),
        ));

        $this->session->set_flashdata('success', 'Certification approved.');
        redirect('certifications');
    }

    public function reject($id)
    {
        require_module_access(array('certifications_approve', 'certifications'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $item = $this->eng->get_certification((int) $id);
        if (!$item || $item->status !== 'pending') {
            show_404();
        }
        $this->eng->save_certification(array(
            'status' => 'rejected',
            'approved_by' => (int) $this->session->userdata('user_id'),
            'approved_at' => date('Y-m-d H:i:s'),
            'review_comment' => trim((string) $this->input->post('review_comment')),
        ), (int) $id);
        $this->session->set_flashdata('success', 'Certification rejected.');
        redirect('certifications');
    }
}
