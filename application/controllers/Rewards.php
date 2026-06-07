<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rewards extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Reward_model', 'rewards');
        require_controller_access('rewards', true);
    }

    public function index()
    {
        require_module_access(array('rewards'), true);
        $uid = (int) $this->session->userdata('user_id');
        $summary = $this->rewards->get_user_summary($uid);
        $level = $this->rewards->get_level($summary->current_level_code);
        $recent = $this->rewards->list_transactions($uid, 10);
        $this->load->view('rewards/index', array(
            'summary' => $summary,
            'level' => $level,
            'recent' => $recent,
        ));
    }

    public function history()
    {
        require_module_access(array('rewards'), true);
        $uid = (int) $this->session->userdata('user_id');
        $rows = $this->rewards->list_transactions($uid, 100);
        $this->load->view('rewards/history', array('rows' => $rows));
    }

    public function leaderboard()
    {
        require_module_access(array('rewards_leaderboard', 'rewards'), true);
        $period = $this->input->get('period') ?: 'monthly';
        $key = $this->input->get('key') ?: date('Y-m');
        if ($period === 'quarterly') {
            $m = (int) date('n');
            $key = $this->input->get('key') ?: (date('Y') . '-Q' . (string) ceil($m / 3));
        } elseif ($period === 'yearly') {
            $key = $this->input->get('key') ?: date('Y');
        } elseif ($period === 'all_time') {
            $key = 'ALL';
        }
        $rows = $this->rewards->leaderboard($period, $key, null, 50);
        $this->load->view('rewards/leaderboard', array('rows' => $rows, 'period' => $period, 'period_key' => $key));
    }

    public function cheer()
    {
        require_module_access(array('rewards_submit', 'rewards'), true);
        if ($this->input->method() === 'post') {
            $from = (int) $this->session->userdata('user_id');
            $to = (int) $this->input->post('recipient_id');
            $message = trim((string) $this->input->post('message'));
            if ($to <= 0 || $to === $from) {
                $this->session->set_flashdata('error', 'Select a valid colleague.');
                redirect('rewards/cheer');
                return;
            }
            reward_engine_dispatch('peer_cheer_received', array(
                'user_id' => $to,
                'actor_id' => $from,
                'source_module' => 'rewards',
                'source_record_id' => null,
                'reference_label' => $message !== '' ? $message : 'Peer recognition',
                'payload' => array(),
            ));
            reward_engine_dispatch('peer_cheer_sent', array(
                'user_id' => $from,
                'actor_id' => $from,
                'source_module' => 'rewards',
                'source_record_id' => null,
                'reference_label' => $message !== '' ? $message : 'Cheer sent',
                'payload' => array(),
            ));
            $this->session->set_flashdata('success', 'Cheer sent!');
            redirect('rewards');
            return;
        }
        $this->load->view('rewards/cheer', array('users' => $this->db->select('id, name')->where('status', 'active')->where('id !=', (int) $this->session->userdata('user_id'))->order_by('name')->get('users')->result()));
    }

    public function rules()
    {
        require_module_access(array('rewards_rules', 'rewards_admin', 'rewards'), true);
        $rows = $this->rewards->list_rules(false);
        $this->load->view('rewards/rules', array('rows' => $rows));
    }

    public function edit_rule($id = null)
    {
        require_module_access(array('rewards_rules', 'rewards_admin', 'rewards'), true);
        $item = $id ? $this->rewards->get_rule((int) $id) : null;
        if ($this->input->method() === 'post') {
            $data = array(
                'code' => trim((string) $this->input->post('code')),
                'name' => trim((string) $this->input->post('name')),
                'description' => (string) $this->input->post('description'),
                'trigger_event' => trim((string) $this->input->post('trigger_event')),
                'condition_json' => trim((string) $this->input->post('condition_json')),
                'points' => (float) $this->input->post('points'),
                'max_per_day' => $this->input->post('max_per_day') !== '' ? (int) $this->input->post('max_per_day') : null,
                'requires_approval' => (int) $this->input->post('requires_approval'),
                'is_active' => (int) $this->input->post('is_active'),
            );
            $savedId = $this->rewards->save_rule($data, $id ? (int) $id : null);
            $this->rewards->audit('rule', $savedId, $id ? 'updated' : 'created', (int) $this->session->userdata('user_id'));
            $this->session->set_flashdata('success', 'Rule saved.');
            redirect('rewards/rules');
            return;
        }
        $this->load->view('rewards/rule_form', array('item' => $item));
    }

    public function manual_grant()
    {
        require_module_access(array('rewards_manual_grant', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() === 'post') {
            $this->load->library('Reward_engine');
            $this->reward_engine->manual_grant(
                (int) $this->input->post('user_id'),
                (float) $this->input->post('points'),
                trim((string) $this->input->post('label')),
                (int) $this->session->userdata('user_id'),
                trim((string) $this->input->post('notes'))
            );
            $this->session->set_flashdata('success', 'Points granted.');
            redirect('rewards/rules');
            return;
        }
        $this->load->view('rewards/manual_grant', array('users' => $this->db->select('id, name')->where('status', 'active')->order_by('name')->get('users')->result()));
    }

    public function submit_claim()
    {
        require_module_access(array('rewards_submit', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() === 'post') {
            $actor = (int) $this->session->userdata('user_id');
            $ruleCode = trim((string) $this->input->post('rule_code'));
            $rule = $this->rewards->get_rule_by_code($ruleCode);
            if (!$rule || $rule->trigger_event !== 'reward_claim') {
                $this->session->set_flashdata('error', 'Invalid reward activity selected.');
                redirect('rewards/submit-claim');
                return;
            }
            $recipientIds = $this->input->post('user_ids');
            if (!is_array($recipientIds)) {
                $recipientIds = array((int) $this->input->post('user_id'));
            }
            $recipientIds = array_values(array_filter(array_map('intval', $recipientIds)));
            if (empty($recipientIds)) {
                $this->session->set_flashdata('error', 'Select at least one team member.');
                redirect('rewards/submit-claim');
                return;
            }
            $label = trim((string) $this->input->post('reference_label'));
            if ($label === '') {
                $label = $rule->name;
            }
            foreach ($recipientIds as $uid) {
                if ($uid <= 0) {
                    continue;
                }
                reward_engine_claim($ruleCode, array(
                    'user_id' => $uid,
                    'actor_id' => $actor,
                    'source_module' => 'rewards',
                    'source_record_id' => null,
                    'reference_label' => $label,
                ));
            }
            $this->session->set_flashdata('success', 'Claim submitted for approval.');
            redirect('rewards/approvals');
            return;
        }
        $this->load->view('rewards/submit_claim', array(
            'rules' => $this->rewards->list_claim_rules(),
            'users' => $this->db->select('id, name')->where('status', 'active')->order_by('name')->get('users')->result(),
        ));
    }

    public function approvals()
    {
        require_module_access(array('rewards_approve', 'rewards_admin', 'rewards'), true);
        $this->load->view('rewards/approvals', array(
            'rows' => $this->rewards->list_pending_approvals(200),
        ));
    }

    public function approve_claim($id)
    {
        require_module_access(array('rewards_approve', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $comment = trim((string) $this->input->post('comment'));
        $ok = $this->rewards->approve_pending((int) $id, (int) $this->session->userdata('user_id'), $comment);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Claim approved.' : 'Could not approve claim.');
        redirect('rewards/approvals');
    }

    public function reject_claim($id)
    {
        require_module_access(array('rewards_approve', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $comment = trim((string) $this->input->post('comment'));
        $ok = $this->rewards->reject_pending((int) $id, (int) $this->session->userdata('user_id'), $comment);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Claim rejected.' : 'Could not reject claim.');
        redirect('rewards/approvals');
    }

    public function office_closing()
    {
        require_module_access(array('rewards_submit', 'rewards'), true);
        $this->load->helper('engagement_schema');
        engagement_schema_ensure($this->db);
        $uid = (int) $this->session->userdata('user_id');
        if ($this->input->method() === 'post') {
            $date = $this->input->post('checklist_date') ?: date('Y-m-d');
            $notes = trim((string) $this->input->post('notes'));
            $exists = $this->db->where('user_id', $uid)->where('checklist_date', $date)->get('office_closing_submissions')->row();
            if ($exists) {
                $this->session->set_flashdata('error', 'You already submitted the closing checklist for this date.');
                redirect('rewards/office-closing');
                return;
            }
            $this->db->insert('office_closing_submissions', array(
                'user_id' => $uid,
                'checklist_date' => $date,
                'notes' => $notes !== '' ? $notes : null,
            ));
            $subId = (int) $this->db->insert_id();
            reward_engine_dispatch('office_closing_checklist', array(
                'user_id' => $uid,
                'source_module' => 'office_closing',
                'source_record_id' => $subId,
                'reference_label' => 'Office closing ' . $date,
            ));
            $this->session->set_flashdata('success', 'Office closing checklist submitted.');
            redirect('rewards');
            return;
        }
        $this->load->view('rewards/office_closing');
    }
}
