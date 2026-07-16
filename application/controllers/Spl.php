<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Spl extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards', 'spl'));
        $this->load->library('session');
        $this->load->model('Reward_model', 'rewards');
        $this->load->model('Spl_model', 'spl');
        require_controller_access('spl', true);
    }

    public function index()
    {
        require_module_access(spl_access_module_keys(), true);
        $embed = (bool) $this->input->get('embed');
        if (!$embed) {
            $params = $_GET;
            unset($params['embed'], $params['parent_tab']);
            if (empty($params['tab'])) {
                $params['tab'] = spl_resolve_default_tab();
            }
            redirect('spl/dashboard?' . http_build_query($params));
            return;
        }

        $uid = (int) $this->session->userdata('user_id');
        $tab = trim((string) $this->input->get('tab'));
        if ($tab === 'categories') {
            $tab = 'rules';
        }
        if ($tab === '') {
            $tab = spl_resolve_default_tab();
        }
        if ($tab === 'my-reward' && !spl_can_my_reward()) {
            $tab = spl_resolve_default_tab();
        }
        if ($tab === 'rules' && !spl_can_manage_rules()) {
            $tab = spl_resolve_default_tab();
        }
        if ($tab === 'levels' && !spl_can_view_levels()) {
            $tab = spl_resolve_default_tab();
        }
        if ($tab === 'activity' && !spl_can_submit()) {
            $tab = spl_resolve_default_tab();
        }
        if ($tab === 'approvals' && !spl_can_approve()) {
            $tab = spl_resolve_default_tab();
        }

        $rules_view = trim((string) $this->input->get('rules_view'));
        if ($this->input->get('tab') === 'categories') {
            $rules_view = 'categories';
        }
        if (!in_array($rules_view, array('rules', 'categories'), true)) {
            $rules_view = 'rules';
        }
        if ($rules_view === 'categories' && !spl_can_manage_categories()) {
            $rules_view = 'rules';
        }

        if (!spl_has_any_index_tab() && !spl_can_view_groups()) {
            show_error('You do not have permission to access SPL.', 403);
            return;
        }

        $approval_view = trim((string) $this->input->get('approval_view'));
        if ($approval_view === '') {
            $approval_view = 'pending';
        }
        if (!in_array($approval_view, array('pending', 'approved', 'rejected'), true)) {
            $approval_view = 'pending';
        }
        if ($tab !== 'approvals') {
            $approval_view = 'pending';
        }

        $this->spl->sync_all_rules_to_all_groups();

        $summary = $this->rewards->get_user_summary($uid);
        $level = $this->rewards->get_level($summary->current_level_code);
        $reward_period = spl_normalize_reward_period($this->input->get('reward_period'));
        $reward_bounds = spl_reward_period_bounds($reward_period);
        $recent = $this->rewards->list_user_activity_feed($uid, 100, $reward_bounds['from'], $reward_bounds['to']);
        $reward_totals = $this->rewards->sum_user_activity_points($uid, $reward_bounds['from'], $reward_bounds['to']);
        $activity_stats = array(
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'other' => 0,
        );
        foreach ($recent as $activity_row) {
            $st = (string) $activity_row->status;
            if ($st === 'pending') {
                $activity_stats['pending']++;
            } elseif ($st === 'approved') {
                $activity_stats['approved']++;
            } elseif ($st === 'rejected') {
                $activity_stats['rejected']++;
            } else {
                $activity_stats['other']++;
            }
        }
        $my_pending_count = (int) $reward_totals['pending_count'];
        $rules = spl_can_manage_rules() ? $this->rewards->list_rules(false) : array();
        $levels = spl_can_view_levels() ? $this->rewards->list_levels(false) : array();
        $categories = $this->spl->list_categories(true);
        $manage_categories = array();
        if (spl_can_manage_categories()) {
            $manage_categories = $this->spl->list_categories(false);
        }
        $pending_approvals = array();
        $approved_approvals = array();
        $rejected_approvals = array();
        $approval_counts = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
        $approval_edit_rules = array();
        if (spl_can_approve()) {
            $approval_counts['pending'] = $this->rewards->count_spl_approvals_by_status('pending');
            $approval_counts['approved'] = $this->rewards->count_spl_approvals_by_status('approved');
            $approval_counts['rejected'] = $this->rewards->count_spl_approvals_by_status('rejected');
            if ($approval_view === 'pending') {
                $pending_approvals = spl_enrich_approval_rows($this->rewards, $this->rewards->list_spl_pending_approvals(200));
            } elseif ($approval_view === 'approved') {
                $approved_approvals = spl_enrich_approval_rows($this->rewards, $this->rewards->list_spl_approval_history('approved', 200));
            } else {
                $rejected_approvals = spl_enrich_approval_rows($this->rewards, $this->rewards->list_spl_approval_history('rejected', 200));
            }
            foreach ($this->rewards->list_rules(true) as $edit_rule) {
                if ((int) $edit_rule->requires_approval !== 1) {
                    continue;
                }
                $approval_edit_rules[] = array(
                    'id' => (int) $edit_rule->id,
                    'code' => (string) $edit_rule->code,
                    'name' => (string) $edit_rule->name,
                    'points' => (float) $edit_rule->points,
                    'category_name' => isset($edit_rule->category_name) ? (string) $edit_rule->category_name : '',
                );
            }
        }

        $this->load->view('spl/index', array(
            'active_tab' => $tab,
            'approval_view' => $approval_view,
            'approval_counts' => $approval_counts,
            'summary' => $summary,
            'level' => $level,
            'recent' => $recent,
            'activity_stats' => $activity_stats,
            'my_pending_count' => $my_pending_count,
            'reward_period' => $reward_period,
            'reward_bounds' => $reward_bounds,
            'reward_totals' => $reward_totals,
            'rules' => $rules,
            'levels' => $levels,
            'categories' => $categories,
            'manage_categories' => $manage_categories,
            'rules_view' => $rules_view,
            'pending_approvals' => $pending_approvals,
            'approved_approvals' => $approved_approvals,
            'rejected_approvals' => $rejected_approvals,
            'approval_edit_rules' => $approval_edit_rules,
            'can_submit' => spl_can_submit(),
            'can_my_reward' => spl_can_my_reward(),
            'can_rules' => spl_can_manage_rules(),
            'can_categories' => spl_can_manage_categories(),
            'can_levels' => spl_can_view_levels(),
            'can_approve' => spl_can_approve(),
            'can_groups' => spl_can_view_groups(),
            'embed' => true,
        ));
    }

    public function dashboard()
    {
        if (!spl_can_access()) {
            show_error('You do not have permission to access SPL.', 403);
            return;
        }

        $embed = (bool) $this->input->get('embed');
        $uid = (int) $this->session->userdata('user_id');
        $raw_tab = trim((string) $this->input->get('tab'));
        if ($raw_tab === 'categories') {
            $raw_tab = 'rules';
        }
        $tab = spl_resolve_unified_tab($raw_tab);
        $reward_period = spl_normalize_reward_period($this->input->get('reward_period') ?: 'week');

        $data = spl_build_dashboard_data($uid, $reward_period);
        $data['active_tab'] = $tab;
        $data['can_submit'] = spl_can_submit();
        $data['can_my_reward'] = spl_can_my_reward();
        $data['can_rules'] = spl_can_manage_rules();
        $data['can_categories'] = spl_can_manage_categories();
        $data['can_levels'] = spl_can_view_levels();
        $data['can_approve'] = spl_can_approve();
        $data['can_groups'] = spl_can_view_groups();
        $data['can_manage'] = spl_can_manage_groups();
        $rules_view = trim((string) $this->input->get('rules_view'));
        if ($this->input->get('tab') === 'categories') {
            $rules_view = 'categories';
        }
        if (!in_array($rules_view, array('rules', 'categories'), true)) {
            $rules_view = 'rules';
        }
        if ($rules_view === 'categories' && !spl_can_manage_categories()) {
            $rules_view = 'rules';
        }
        $data['rules_view'] = $rules_view;
        $data['approval_counts'] = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
        if (spl_can_approve()) {
            $data['approval_counts']['pending'] = $this->rewards->count_spl_approvals_by_status('pending');
            $data['approval_counts']['approved'] = $this->rewards->count_spl_approvals_by_status('approved');
            $data['approval_counts']['rejected'] = $this->rewards->count_spl_approvals_by_status('rejected');
        }

        if ($embed) {
            if ($tab === 'overview') {
                $this->load->view('spl/_dashboard_body', $data);
                return;
            }
            show_404();
            return;
        }

        $this->load->view('spl/unified', $data);
    }

    public function submit_activity()
    {
        require_module_access(array('spl_submit', 'spl', 'rewards_submit', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }

        $actor = (int) $this->session->userdata('user_id');
        $ruleCode = trim((string) $this->input->post('rule_code'));
        $rule = $this->rewards->get_rule_by_code($ruleCode);
        if (!$rule || $rule->trigger_event !== 'reward_claim') {
            $this->session->set_flashdata('error', 'Invalid activity selected.');
            redirect('spl/dashboard?tab=activity');
            return;
        }
        if ((int) $rule->requires_approval !== 1) {
            $this->session->set_flashdata('error', 'This activity is tracked automatically and cannot be submitted manually.');
            redirect('spl/dashboard?tab=activity');
            return;
        }

        $label = spl_sanitize_note_html($this->input->post('reference_label'));
        if ($label === '') {
            $label = $rule->name;
        }

        $max_per_day = ($rule->max_per_day !== null) ? (int) $rule->max_per_day : 0;
        if ($max_per_day > 0) {
            $today_count = $this->rewards->count_rule_awards_today((int) $rule->id, $actor);
            if ($today_count >= $max_per_day) {
                $this->session->set_flashdata(
                    'error',
                    'You already submitted "' . $rule->name . '" today'
                    . ($max_per_day > 1 ? ' (limit ' . $max_per_day . ' per day)' : '')
                    . '. Choose a different activity or try again tomorrow.'
                );
                redirect('spl/dashboard?tab=activity');
                return;
            }
        }

        $txIds = reward_engine_claim($ruleCode, array(
            'user_id' => $actor,
            'actor_id' => $actor,
            'source_module' => 'spl',
            'source_record_id' => null,
            'reference_label' => $label,
            'idempotency_salt' => uniqid('spl_claim_', true),
        ));

        $lastQueueId = 0;
        if (!empty($txIds)) {
            $q = $this->spl->get_latest_queue_for_transaction((int) $txIds[0]);
            if ($q) {
                $lastQueueId = (int) $q->id;
            }
        }

        if ($lastQueueId > 0 && !empty($_FILES['attachment']['name'])) {
            spl_save_evidence_file($lastQueueId, $actor);
        }

        if (empty($txIds)) {
            $this->session->set_flashdata(
                'error',
                'Could not submit activity. It may already be pending for today, or this activity is capped.'
            );
            redirect('spl/dashboard?tab=activity');
            return;
        }

        $this->session->set_flashdata('success', 'Activity submitted for approval. Points will be added after admin approval.');
        $redirectTab = spl_can_my_reward() ? 'my-reward' : 'activity';
        redirect('spl/dashboard?tab=' . $redirectTab);
    }

    public function approve_activity($id = 0)
    {
        require_module_access(array('spl', 'spl_approve', 'rewards_approve', 'rewards_admin'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $id = (int) $id;
        $q = $this->rewards->get_approval_queue($id);
        if (!$q || !$this->rewards->is_approval_managed_source($q->source_module)) {
            $this->session->set_flashdata('error', 'Invalid approval request.');
            redirect('spl/dashboard?tab=approvals&approval_view=pending');
            return;
        }
        $comment = trim((string) $this->input->post('comment'));
        $requested_points_raw = $this->input->post('requested_points');
        $requested_points = null;
        if ($requested_points_raw !== null && $requested_points_raw !== '') {
            if (!is_numeric($requested_points_raw)) {
                $this->session->set_flashdata('error', 'Points must be a valid number.');
                redirect('spl/dashboard?tab=approvals&approval_view=pending');
                return;
            }
            $requested_points = (float) $requested_points_raw;
        }
        $ok = $this->rewards->approve_pending($id, (int) $this->session->userdata('user_id'), $comment, $requested_points);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Activity approved. Points added to user.' : 'Could not approve activity.');
        redirect('spl/dashboard?tab=approvals&approval_view=' . ($ok ? 'approved' : 'pending'));
    }

    public function reject_activity($id = 0)
    {
        require_module_access(array('spl', 'spl_approve', 'rewards_approve', 'rewards_admin'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $id = (int) $id;
        $q = $this->rewards->get_approval_queue($id);
        if (!$q || !$this->rewards->is_approval_managed_source($q->source_module)) {
            $this->session->set_flashdata('error', 'Invalid approval request.');
            redirect('spl/dashboard?tab=approvals&approval_view=pending');
            return;
        }
        $comment = trim((string) $this->input->post('comment'));
        $ok = $this->rewards->reject_pending($id, (int) $this->session->userdata('user_id'), $comment);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Activity rejected.' : 'Could not reject activity.');
        redirect('spl/dashboard?tab=approvals&approval_view=' . ($ok ? 'rejected' : 'pending'));
    }

    public function update_pending_activity($id = 0)
    {
        require_module_access(array('spl', 'spl_approve', 'rewards_approve', 'rewards_admin'), true);
        if ($this->input->method() !== 'post') {
            if ($this->input->is_ajax_request()) {
                $this->_json_error('Invalid request', 405);
                return;
            }
            show_error('Invalid request', 405);
        }
        $id = (int) $id;
        $is_ajax = $this->input->is_ajax_request();
        $q = $this->rewards->get_approval_queue($id);
        if (!$q || !$this->rewards->is_approval_managed_source($q->source_module) || $q->status !== 'pending') {
            if ($is_ajax) {
                $this->_json_error('Invalid pending activity.', 422);
                return;
            }
            $this->session->set_flashdata('error', 'Invalid pending activity.');
            redirect('spl/dashboard?tab=approvals&approval_view=pending');
            return;
        }

        $points_raw = $this->input->post('requested_points');
        if ($points_raw === null || $points_raw === '' || !is_numeric($points_raw)) {
            if ($is_ajax) {
                $this->_json_error('Points must be a valid number.', 422);
                return;
            }
            $this->session->set_flashdata('error', 'Points must be a valid number.');
            redirect('spl/dashboard?tab=approvals&approval_view=pending');
            return;
        }

        $update = array(
            'requested_points' => (float) $points_raw,
        );

        $rule_id_post = $this->input->post('rule_id');
        if ($rule_id_post !== null && $rule_id_post !== '') {
            $rule_id = (int) $rule_id_post;
            if ($rule_id <= 0 || !$this->rewards->get_rule($rule_id)) {
                if ($is_ajax) {
                    $this->_json_error('Invalid activity selected.', 422);
                    return;
                }
                $this->session->set_flashdata('error', 'Invalid activity selected.');
                redirect('spl/dashboard?tab=approvals&approval_view=pending');
                return;
            }
            $update['rule_id'] = $rule_id;
        }

        if ($this->input->post('reference_label') !== null) {
            $label = spl_sanitize_note_html($this->input->post('reference_label'));
            if ($label === '') {
                $rule_for_label = !empty($update['rule_id'])
                    ? $this->rewards->get_rule((int) $update['rule_id'])
                    : (!empty($q->rule_id) ? $this->rewards->get_rule((int) $q->rule_id) : null);
                $label = ($rule_for_label && !empty($rule_for_label->name)) ? (string) $rule_for_label->name : 'Activity';
            }
            $update['reference_label'] = $label;
        }

        $ok = $this->rewards->update_pending_activity($id, $update);
        if ($ok) {
            $this->rewards->audit('approval_queue', $id, 'updated', (int) $this->session->userdata('user_id'));
        }
        if ($is_ajax) {
            if (!$ok) {
                $this->_json_error('Could not update activity.', 500);
                return;
            }
            $this->_json_success(is_array($ok) ? $ok : array('id' => $id));
            return;
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pending activity updated.' : 'Could not update activity.');
        redirect('spl/dashboard?tab=approvals&approval_view=pending');
    }

    public function delete_pending_activity($id = 0)
    {
        require_module_access(array('spl', 'spl_approve', 'rewards_approve', 'rewards_admin'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $id = (int) $id;
        $q = $this->rewards->get_approval_queue($id);
        if (!$q || !$this->rewards->is_approval_managed_source($q->source_module) || $q->status !== 'pending') {
            $this->session->set_flashdata('error', 'Invalid pending activity.');
            redirect('spl/dashboard?tab=approvals&approval_view=pending');
            return;
        }
        $ok = $this->rewards->delete_pending_activity($id);
        if ($ok) {
            $this->rewards->audit('approval_queue', $id, 'deleted', (int) $this->session->userdata('user_id'));
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pending activity deleted.' : 'Could not delete activity.');
        redirect('spl/dashboard?tab=approvals&approval_view=pending');
    }

    public function approvals()
    {
        $view = trim((string) $this->input->get('approval_view'));
        if (!in_array($view, array('pending', 'approved', 'rejected'), true)) {
            $view = 'pending';
        }
        redirect('spl/dashboard?tab=approvals&approval_view=' . $view);
    }

    public function save_rule()
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            $this->_json_error('Invalid request', 405);
        }

        $id = (int) $this->input->post('id');
        $code = trim((string) $this->input->post('code'));
        $name = trim((string) $this->input->post('name'));
        if ($code === '' || $name === '') {
            $this->_json_error('Code and name are required.', 422);
        }

        $data = array(
            'code' => $code,
            'name' => $name,
            'description' => trim((string) $this->input->post('description')),
            'trigger_event' => trim((string) $this->input->post('trigger_event')) ?: 'reward_claim',
            'condition_json' => trim((string) $this->input->post('condition_json')),
            'points' => (float) $this->input->post('points'),
            'category_id' => $this->input->post('category_id') !== '' ? (int) $this->input->post('category_id') : null,
            'max_per_day' => $this->input->post('max_per_day') !== '' ? (int) $this->input->post('max_per_day') : null,
            'requires_approval' => (int) $this->input->post('requires_approval'),
            'is_active' => (int) $this->input->post('is_active'),
        );

        if ($data['trigger_event'] === 'reward_claim' && $data['condition_json'] === '') {
            $data['condition_json'] = json_encode(array('claim_type' => $code));
        }

        $savedId = $this->rewards->save_rule($data, $id > 0 ? $id : null);
        if (!$savedId) {
            $this->_json_error('Could not save rule.', 500);
            return;
        }
        $this->rewards->audit('rule', $savedId, $id > 0 ? 'updated' : 'created', (int) $this->session->userdata('user_id'));
        if ($data['trigger_event'] === 'reward_claim' && (int) $data['is_active'] === 1) {
            $this->spl->sync_all_rules_to_all_groups();
        }
        $this->_json_success(array('id' => $savedId));
    }

    public function save_category()
    {
        require_module_access(array('spl_categories', 'spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Invalid request.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }

        $id = (int) $this->input->post('id');
        $name = trim((string) $this->input->post('name'));
        $code = trim((string) $this->input->post('code'));
        if ($name === '') {
            $this->session->set_flashdata('error', 'Category name is required.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }
        if ($code === '') {
            $code = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
            $code = trim($code, '_');
        }
        if ($code === '') {
            $code = 'category_' . time();
        }

        $existing_code = $this->spl->get_category_by_code($code);
        if ($existing_code && (int) $existing_code->id !== $id) {
            $this->session->set_flashdata('error', 'Category code already exists.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }

        $payload = array(
            'code' => $code,
            'name' => $name,
            'description' => trim((string) $this->input->post('description')),
            'icon_class' => trim((string) $this->input->post('icon_class')) ?: 'bi bi-star',
            'sort_order' => (int) $this->input->post('sort_order'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        $saved = $this->spl->save_category($payload, $id > 0 ? $id : null);
        if (!$saved) {
            $this->session->set_flashdata('error', 'Could not save category.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }
        $this->rewards->audit('category', (int) $saved, $id > 0 ? 'updated' : 'created', (int) $this->session->userdata('user_id'));
        $this->session->set_flashdata('success', 'Category saved.');
        redirect('spl/dashboard?tab=rules&rules_view=categories');
    }

    public function toggle_category($id = 0)
    {
        require_module_access(array('spl_categories', 'spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Invalid request.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }
        $id = (int) $id;
        if ($id <= 0 || !$this->spl->toggle_category($id)) {
            $this->session->set_flashdata('error', 'Could not update category.');
            redirect('spl/dashboard?tab=rules&rules_view=categories');
            return;
        }
        $this->rewards->audit('category', $id, 'toggled', (int) $this->session->userdata('user_id'));
        $this->session->set_flashdata('success', 'Category status updated.');
        redirect('spl/dashboard?tab=rules&rules_view=categories');
    }

    public function save_level()
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            $this->_json_error('Invalid request', 405);
        }

        $id = (int) $this->input->post('id');
        $name = trim((string) $this->input->post('name'));
        if ($id <= 0 || $name === '') {
            $this->_json_error('Level id and name are required.', 422);
        }

        $existing = $this->rewards->get_level_by_id($id);
        if (!$existing) {
            $this->_json_error('Level not found.', 404);
        }

        $max_raw = trim((string) $this->input->post('max_lifetime_points'));
        $data = array(
            'name' => $name,
            'min_lifetime_points' => (float) $this->input->post('min_lifetime_points'),
            'max_lifetime_points' => $max_raw !== '' ? (float) $max_raw : null,
            'badge_color' => trim((string) $this->input->post('badge_color')) ?: $existing->badge_color,
            'is_active' => (int) $this->input->post('is_active'),
        );

        $savedId = $this->rewards->save_level($data, $id);
        $this->rewards->audit('level', $savedId, 'updated', (int) $this->session->userdata('user_id'));
        $this->_json_success(array('id' => $savedId));
    }

    public function rules_sample_csv()
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        $path = FCPATH . 'assets/samples/spl_rules_import_sample.csv';
        if (!is_file($path)) {
            show_404();
            return;
        }
        $this->output
            ->set_content_type('text/csv; charset=utf-8')
            ->set_header('Content-Disposition: attachment; filename="spl_rules_import_sample.csv"')
            ->set_output(file_get_contents($path));
    }

    public function rules_export_csv()
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        $rules = $this->rewards->list_rules(false);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="spl_rules_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('code', 'name', 'category', 'trigger_event', 'points', 'requires_approval', 'is_active', 'description', 'max_per_day'));
        foreach ($rules as $r) {
            fputcsv($out, array(
                (string) $r->code,
                (string) $r->name,
                isset($r->category_name) ? (string) $r->category_name : '',
                (string) $r->trigger_event,
                (float) $r->points,
                (int) $r->requires_approval,
                (int) $r->is_active,
                isset($r->description) ? (string) $r->description : '',
                $r->max_per_day !== null ? (int) $r->max_per_day : '',
            ));
        }
        fclose($out);
        exit;
    }

    public function rules_import()
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }

        $this->load->helper('csv_import');
        $opened = csv_import_open('file');
        if (!$opened['ok']) {
            $this->session->set_flashdata('error', $opened['error']);
            redirect('spl/dashboard?tab=rules');
            return;
        }

        $columns = csv_import_require_columns($opened['map'], array('code', 'name'), array());
        if (!$columns['ok']) {
            fclose($opened['handle']);
            $this->session->set_flashdata('error', $columns['error']);
            redirect('spl/dashboard?tab=rules');
            return;
        }

        $category_map = array();
        foreach ($this->spl->list_categories(false) as $cat) {
            $category_map[strtolower(trim((string) $cat->name))] = (int) $cat->id;
        }

        $actor = (int) $this->session->userdata('user_id');
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $row_errors = array();
        $line = 1;
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        while (($row = fgetcsv($opened['handle'])) !== false) {
            $line++;
            $code = csv_import_get($opened['map'], $row, 'code');
            $name = csv_import_get($opened['map'], $row, 'name');
            if ($code === '' || $name === '') {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Code and name are required.');
                continue;
            }

            $category_id = null;
            $category_name = csv_import_get($opened['map'], $row, 'category', '');
            if ($category_name !== '') {
                $cat_key = strtolower($category_name);
                if (!isset($category_map[$cat_key])) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Unknown category "' . $category_name . '".');
                    continue;
                }
                $category_id = $category_map[$cat_key];
            }

            $trigger_event = csv_import_get($opened['map'], $row, 'trigger_event', 'reward_claim');
            if ($trigger_event === '') {
                $trigger_event = 'reward_claim';
            }

            $points_raw = csv_import_get($opened['map'], $row, 'points', '0');
            if ($points_raw === '' || !is_numeric($points_raw)) {
                $skipped++;
                csv_import_add_row_error($row_errors, $line, 'Points must be a number.');
                continue;
            }

            $requires_approval = $this->_spl_csv_bool(csv_import_get($opened['map'], $row, 'requires_approval', '1'), 1);
            $is_active = $this->_spl_csv_bool(csv_import_get($opened['map'], $row, 'is_active', '1'), 1);
            $max_per_day_raw = csv_import_get($opened['map'], $row, 'max_per_day', '');
            $max_per_day = $max_per_day_raw !== '' ? (int) $max_per_day_raw : null;

            $data = array(
                'code' => $code,
                'name' => $name,
                'description' => csv_import_get($opened['map'], $row, 'description', ''),
                'trigger_event' => $trigger_event,
                'condition_json' => trim((string) csv_import_get($opened['map'], $row, 'condition_json', '')),
                'points' => (float) $points_raw,
                'category_id' => $category_id,
                'max_per_day' => $max_per_day,
                'requires_approval' => $requires_approval,
                'is_active' => $is_active,
            );

            if ($data['trigger_event'] === 'reward_claim' && $data['condition_json'] === '') {
                $data['condition_json'] = json_encode(array('claim_type' => $code));
            }

            $existing = $this->rewards->get_rule_by_code($code);
            $saved_id = $this->rewards->save_rule($data, $existing ? (int) $existing->id : null);
            if ($saved_id <= 0) {
                $skipped++;
                $db_error = $this->db->error();
                $reason = !empty($db_error['message']) ? $db_error['message'] : 'Could not save rule.';
                csv_import_add_row_error($row_errors, $line, $reason);
                continue;
            }

            $this->rewards->audit('rule', $saved_id, $existing ? 'updated' : 'created', $actor);
            if ($existing) {
                $updated++;
            } else {
                $inserted++;
            }
        }

        $this->db->db_debug = $prev_debug;
        fclose($opened['handle']);
        $this->spl->sync_all_rules_to_all_groups();

        $total = $inserted + $updated;
        if ($total === 0) {
            $msg = 'No rules were imported.';
            if (!empty($row_errors)) {
                $msg .= ' ' . implode(' ', array_slice($row_errors, 0, 3));
                if (count($row_errors) > 3) {
                    $msg .= ' (+' . (count($row_errors) - 3) . ' more)';
                }
            } else {
                $msg .= ' Check column headers and row data.';
            }
            $this->session->set_flashdata('error', $msg);
            if (!empty($row_errors)) {
                $this->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
            }
            redirect('spl/dashboard?tab=rules');
            return;
        }

        $msg = 'Imported ' . $inserted . ' new rule(s)';
        if ($updated > 0) {
            $msg .= ', updated ' . $updated;
        }
        if ($skipped > 0) {
            $msg .= '. ' . $skipped . ' row(s) skipped.';
        }
        $this->session->set_flashdata('success', $msg);
        if (!empty($row_errors)) {
            $this->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
        }
        redirect('spl/dashboard?tab=rules');
    }

    public function delete_rule($id = 0)
    {
        require_module_access(array('spl_rules', 'spl', 'rewards_rules', 'rewards_admin', 'rewards'), true);
        if ($this->input->method() !== 'post') {
            $this->_json_error('Invalid request', 405);
        }
        $id = (int) $id;
        if ($id <= 0) {
            $this->_json_error('Invalid rule.', 422);
        }
        $rule = $this->rewards->get_rule($id);
        if (!$rule) {
            $this->_json_error('Rule not found.', 404);
        }
        if (!$this->rewards->delete_rule($id)) {
            $this->_json_error('Could not delete rule.', 500);
        }
        $this->rewards->audit('rule', $id, 'deleted', (int) $this->session->userdata('user_id'), $rule, null);
        $this->spl->sync_all_rules_to_all_groups();
        $this->_json_success(array('id' => $id));
    }

    public function rules_by_category()
    {
        require_module_access(array('spl_submit', 'spl', 'rewards_submit', 'rewards'), true);
        $category_id = (int) $this->input->get('category_id');
        $group_id = (int) $this->input->get('group_id');
        $rows = array();
        if ($group_id > 0) {
            $rows = $this->spl->list_group_rules($group_id);
            if ($category_id > 0) {
                $filtered = array();
                foreach ($rows as $row) {
                    if ((int) $row->category_id === $category_id) {
                        $filtered[] = $row;
                    }
                }
                $rows = $filtered;
            }
        } else {
            $rows = $this->spl->list_claim_rules_by_category($category_id);
        }
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'code' => (string) $r->code,
                'name' => (string) $r->name,
                'points' => (float) $r->points,
                'category_id' => $r->category_id ? (int) $r->category_id : null,
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(array(
            'status' => 'success',
            'data' => $out,
        )));
    }

    public function group_context()
    {
        require_module_access(array('spl_submit', 'spl', 'rewards_submit', 'rewards', 'spl_groups', 'rewards_admin'), true);
        $group_id = (int) $this->input->get('group_id');
        if ($group_id <= 0) {
            $this->_json_error('Invalid group.', 422);
            return;
        }
        $group = $this->spl->get_group($group_id);
        if (!$group) {
            $this->_json_error('Group not found.', 404);
            return;
        }
        $members = array();
        foreach ($this->spl->list_group_members($group_id) as $m) {
            $members[] = array(
                'id' => (int) $m->id,
                'name' => (string) $m->name,
            );
        }
        $rules = array();
        foreach ($this->spl->list_group_rules($group_id) as $r) {
            $rules[] = array(
                'code' => (string) $r->code,
                'name' => (string) $r->name,
                'points' => (float) $r->points,
                'category_id' => $r->category_id ? (int) $r->category_id : null,
            );
        }
        $this->_json_success(array(
            'group' => array(
                'id' => (int) $group->id,
                'name' => (string) $group->name,
            ),
            'members' => $members,
            'rules' => $rules,
        ));
    }

    public function groups()
    {
        require_module_access(array('spl', 'spl_groups', 'spl_groups_manage', 'rewards', 'rewards_admin'), true);
        $embed = (bool) $this->input->get('embed');
        if (!$embed) {
            $params = array('tab' => 'groups', 'reward_period' => 'week');
            $reward_period = $this->input->get('reward_period');
            if ($reward_period !== null && $reward_period !== '') {
                $params['reward_period'] = spl_normalize_reward_period($reward_period);
            }
            redirect('spl/dashboard?' . http_build_query($params));
            return;
        }

        $reward_period = spl_normalize_reward_period($this->input->get('reward_period') ?: 'week');
        $reward_bounds = spl_reward_period_bounds($reward_period);
        $use_period_points = ($reward_period !== 'all');
        $this->spl->sync_all_rules_to_all_groups();
        $boardGroups = $this->spl->list_groups_board(
            false,
            $use_period_points ? $reward_bounds['from'] : null,
            $use_period_points ? $reward_bounds['to'] : null
        );
        $filtered = array();
        foreach ($boardGroups as $g) {
            if (strpos((string) $g->code, 'group_') === 0) {
                $filtered[] = $g;
            }
        }
        if (empty($filtered)) {
            $filtered = $boardGroups;
        }
        $uid = (int) $this->session->userdata('user_id');
        $my_group = null;
        $my_member = null;
        $user_groups = $this->spl->list_groups_for_user($uid, true);
        if (!empty($user_groups)) {
            $my_group_id = (int) $user_groups[0]->id;
            foreach ($filtered as $g) {
                if ((int) $g->id === $my_group_id) {
                    $my_group = $g;
                    if (!empty($g->members)) {
                        foreach ($g->members as $member) {
                            if ((int) $member->id === $uid) {
                                $my_member = $member;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }
        $this->load->view('spl/groups', array(
            'groups' => $filtered,
            'my_group' => $my_group,
            'my_member' => $my_member,
            'current_user_id' => $uid,
            'can_manage' => spl_can_manage_groups(),
            'users' => spl_list_users_for_groups(),
            'reward_period' => $reward_period,
            'reward_bounds' => $reward_bounds,
            'use_period_points' => $use_period_points,
            'embed' => true,
        ));
    }

    public function member($user_id = 0)
    {
        require_module_access(array('spl', 'spl_groups', 'spl_groups_manage', 'spl_my_reward', 'rewards', 'rewards_admin'), true);
        $target_id = (int) $user_id;
        if ($target_id <= 0) {
            show_404();
            return;
        }
        if (!spl_can_view_member_activity($target_id)) {
            show_error('You do not have permission to view this member activity.', 403);
            return;
        }

        $viewer_id = (int) $this->session->userdata('user_id');
        $reward_period = spl_normalize_reward_period($this->input->get('reward_period') ?: 'week');
        $reward_bounds = spl_reward_period_bounds($reward_period);
        $use_period_points = ($reward_period !== 'all');
        $period_from = $use_period_points ? $reward_bounds['from'] : null;
        $period_to = $use_period_points ? $reward_bounds['to'] : null;

        $user = spl_dashboard_user_row($target_id);
        if (!$user) {
            show_404();
            return;
        }

        $summary = $this->rewards->get_user_summary($target_id);
        $level = $this->rewards->get_level($summary->current_level_code);
        $reward_totals = $this->rewards->sum_user_activity_points($target_id, $period_from, $period_to);
        $activities = $this->rewards->list_user_activity_feed($target_id, 200, $period_from, $period_to);
        $activities = spl_enrich_user_activity_rows($this->rewards, $activities);
        $member_groups = $this->spl->list_groups_for_user($target_id, true);

        $from = trim((string) $this->input->get('from'));
        if ($from === 'groups') {
            $back_url = spl_dashboard_url('groups', array('reward_period' => $reward_period));
            $back_label = 'Back to Groups';
        } else {
            $back_url = spl_dashboard_url('overview', array('reward_period' => $reward_period));
            $back_label = 'Back to Dashboard';
        }

        $display_name = !empty($user->display_name) ? (string) $user->display_name : 'User';
        $is_self = ($viewer_id > 0 && $target_id === $viewer_id);

        $this->load->view('spl/member', array(
            'member' => $user,
            'display_name' => $display_name,
            'avatar_url' => spl_user_avatar_url($user),
            'initials' => spl_user_initials($display_name),
            'summary' => $summary,
            'level' => $level,
            'reward_period' => $reward_period,
            'reward_bounds' => $reward_bounds,
            'reward_totals' => $reward_totals,
            'activities' => $activities,
            'member_groups' => $member_groups,
            'back_url' => $back_url,
            'back_label' => $back_label,
            'is_self' => $is_self,
            'current_user_id' => $viewer_id,
        ));
    }

    public function group($id = 0)
    {
        require_module_access(array('spl', 'spl_groups', 'spl_groups_manage', 'rewards', 'rewards_admin'), true);
        $group = $this->spl->get_group((int) $id);
        if (!$group) {
            show_404();
        }
        $this->load->view('spl/group', array(
            'group' => $group,
            'rules' => $this->spl->list_group_rules((int) $group->id),
            'members' => $this->spl->list_group_members((int) $group->id),
            'can_manage' => spl_can_manage_groups(),
            'can_submit' => spl_can_submit(),
            'users' => spl_list_users_for_groups(),
        ));
    }

    public function save_groups_board()
    {
        spl_require_manage_groups();
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }

        $names = $this->input->post('group_name');
        $codes = $this->input->post('group_code');
        $sortOrders = $this->input->post('group_sort');
        $allMemberIds = $this->input->post('member_ids');
        if (!is_array($names)) {
            $names = array();
        }
        if (!is_array($codes)) {
            $codes = array();
        }
        if (!is_array($sortOrders)) {
            $sortOrders = array();
        }
        if (!is_array($allMemberIds)) {
            $allMemberIds = array();
        }

        foreach ($names as $rawId => $name) {
            $id = (int) $rawId;
            $name = trim((string) $name);
            $code = isset($codes[$rawId]) ? trim((string) $codes[$rawId]) : '';
            if ($id <= 0 || $name === '' || $code === '') {
                continue;
            }

            $data = array(
                'name' => $name,
                'code' => $code,
                'sort_order' => isset($sortOrders[$rawId]) ? (int) $sortOrders[$rawId] : 0,
                'is_active' => 1,
            );

            if (!empty($_FILES['group_poster']['name'][$rawId])) {
                $dir = FCPATH . 'uploads/spl/groups/';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $ext = strtolower(pathinfo((string) $_FILES['group_poster']['name'][$rawId], PATHINFO_EXTENSION));
                $allowed = array('png', 'jpg', 'jpeg', 'gif', 'webp');
                if (in_array($ext, $allowed, true)) {
                    $safe = 'group_' . preg_replace('/[^a-z0-9_-]+/i', '_', $code) . '_' . time() . '.' . $ext;
                    $tmp = $_FILES['group_poster']['tmp_name'][$rawId];
                    if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $dir . $safe)) {
                        $data['poster_path'] = 'uploads/spl/groups/' . $safe;
                    }
                }
            }

            $savedId = $this->spl->save_group($data, $id);
            $memberIds = isset($allMemberIds[$rawId]) && is_array($allMemberIds[$rawId]) ? $allMemberIds[$rawId] : array();
            $this->spl->sync_group_members($savedId, $memberIds);
        }

        $this->spl->sync_all_rules_to_all_groups();

        $this->session->set_flashdata('success', 'SPL groups saved.');
        $period = spl_normalize_reward_period($this->input->post('reward_period'));
        redirect(spl_groups_url($period));
    }

    public function save_group()
    {
        spl_require_manage_groups();
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }

        $id = (int) $this->input->post('id');
        $name = trim((string) $this->input->post('name'));
        $code = trim((string) $this->input->post('code'));
        if ($name === '' || $code === '') {
            $this->session->set_flashdata('error', 'Group name and code are required.');
            redirect($id > 0 ? 'spl/groups/' . $id : 'spl/groups');
            return;
        }

        $data = array(
            'name' => $name,
            'code' => $code,
            'description' => trim((string) $this->input->post('description')),
            'sort_order' => (int) $this->input->post('sort_order'),
            'is_active' => (int) $this->input->post('is_active'),
        );

        if (!empty($_FILES['poster']['name'])) {
            $dir = FCPATH . 'uploads/spl/groups/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = strtolower(pathinfo((string) $_FILES['poster']['name'], PATHINFO_EXTENSION));
            $allowed = array('png', 'jpg', 'jpeg', 'gif', 'webp');
            if (in_array($ext, $allowed, true)) {
                $safe = 'group_' . preg_replace('/[^a-z0-9_-]+/i', '_', $code) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $dir . $safe)) {
                    $data['poster_path'] = 'uploads/spl/groups/' . $safe;
                }
            }
        }

        $savedId = $this->spl->save_group($data, $id > 0 ? $id : null);
        $ruleIds = $this->input->post('rule_ids');
        if (is_array($ruleIds)) {
            $this->spl->sync_group_rules($savedId, $ruleIds);
        } else {
            $this->spl->sync_all_rules_to_all_groups();
        }

        $memberIds = $this->input->post('member_ids');
        if (!is_array($memberIds)) {
            $memberIds = array();
        }
        $this->spl->sync_group_members($savedId, $memberIds);

        $this->session->set_flashdata('success', 'SPL group saved.');
        $redirect = trim((string) $this->input->post('redirect'));
        if ($redirect === '' || strpos($redirect, 'spl/') !== 0) {
            $redirect = 'spl/groups/' . $savedId;
        }
        redirect($redirect);
    }

    public function add_group()
    {
        spl_require_manage_groups();
        if ($this->input->method() !== 'post') {
            show_error('Invalid request', 405);
        }
        $next = (int) $this->db->count_all('spl_groups') + 1;
        $savedId = $this->spl->save_group(array(
            'name' => 'Group ' . $next,
            'code' => 'group_' . $next,
            'description' => '',
            'sort_order' => $next,
            'is_active' => 1,
        ), null);
        $this->session->set_flashdata('success', 'Group added.');
        redirect('spl/dashboard?tab=groups');
    }

    public function evidence_download($id = 0)
    {
        $evidence = $this->_resolve_reward_evidence_access((int) $id);
        $path = FCPATH . ltrim((string) $evidence->file_path, '/');
        if (!is_file($path)) {
            show_404();
            return;
        }
        $this->load->helper('download');
        $name = !empty($evidence->file_name) ? (string) $evidence->file_name : basename($path);
        force_download($name, file_get_contents($path));
    }

    public function evidence_preview($id = 0)
    {
        $evidence = $this->_resolve_reward_evidence_access((int) $id);
        $path = FCPATH . ltrim((string) $evidence->file_path, '/');
        if (!is_file($path)) {
            show_404();
            return;
        }
        $name = !empty($evidence->file_name) ? (string) $evidence->file_name : basename($path);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mimeMap = array(
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $mime = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . rawurlencode($name) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    private function _resolve_reward_evidence_access($evidence_id)
    {
        require_module_access(array('spl', 'spl_groups', 'spl_groups_manage', 'spl_my_reward', 'rewards', 'rewards_admin'), true);
        if ($evidence_id <= 0) {
            show_404();
            exit;
        }
        $evidence = $this->rewards->get_evidence($evidence_id);
        if (!$evidence || empty($evidence->file_path)) {
            show_404();
            exit;
        }
        $queue = $this->rewards->get_approval_queue((int) $evidence->approval_queue_id);
        if (!$queue) {
            show_404();
            exit;
        }
        if (!spl_can_view_member_activity((int) $queue->user_id)) {
            show_error('You do not have permission to access this attachment.', 403);
            exit;
        }
        return $evidence;
    }

    private function _spl_csv_bool($value, $default = 1)
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return (int) $default;
        }
        if (in_array($value, array('1', 'yes', 'y', 'true', 'on'), true)) {
            return 1;
        }
        if (in_array($value, array('0', 'no', 'n', 'false', 'off'), true)) {
            return 0;
        }
        return (int) $default;
    }

    private function _json_success($data = array())
    {
        $payload = array(
            'status' => 'success',
            'data' => $data,
        );
        if (isset($this->security)) {
            $payload['csrfHash'] = $this->security->get_csrf_hash();
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    private function _json_error($message, $http = 400)
    {
        $this->output->set_status_header((int) $http);
        $payload = array(
            'status' => 'error',
            'message' => (string) $message,
        );
        if (isset($this->security)) {
            $payload['csrfHash'] = $this->security->get_csrf_hash();
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }
}
