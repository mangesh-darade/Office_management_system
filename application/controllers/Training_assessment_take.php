<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Learner-facing assessment flow: take, AJAX autosave, submit, token result (with optional HMAC sig).
 */
class Training_assessment_take extends CI_Controller
{
    /** @var Training_assessment_model */
    public $ta;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Training_assessment_model', 'ta');
        $this->load->library('session');
        $this->load->library('training_assessment_runtime', null, 'ta_run');
        $this->load->helper(array('url', 'form', 'training', 'permission'));
    }

    private function _resolve_take_security_policy($au)
    {
        // External candidates (no employee account) use strict monitored mode by default.
        if (!$au || (int) $au->user_id < 1) {
            return array('allowed' => true, 'proctoring' => true);
        }

        $sessUid = (int) $this->session->userdata('user_id');
        if ($sessUid < 1 || $sessUid !== (int) $au->user_id) {
            return array('allowed' => false, 'proctoring' => false);
        }

        if (!function_exists('has_module_access')) {
            return array('allowed' => true, 'proctoring' => true);
        }

        $allowWithout = has_module_access('training_take_without_proctoring');
        $allowWith = has_module_access('training_take_with_proctoring')
            || has_module_access('training_assessment_take')
            || has_module_access('training_assessment')
            || has_module_access('training_assessment_manage');

        if (!$allowWith && !$allowWithout) {
            return array('allowed' => false, 'proctoring' => false);
        }

        return array(
            'allowed' => true,
            'proctoring' => !$allowWithout, // "without" permission explicitly disables proctoring.
        );
    }

    public function candidate_profile()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$au || (int) $au->user_id > 0) {
            show_error('Invalid assignment.', 400);
        }
        $name = trim((string) $this->input->post('candidate_name'));
        $email = trim((string) $this->input->post('candidate_email'));
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Please provide valid name and email.');
            redirect('training-assessment/take/' . $token);
            return;
        }
        $this->ta->update_assessment_user((int) $au->id, array(
            'candidate_name' => $name,
            'candidate_email' => $email,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        redirect('training-assessment/take/' . $token);
    }

    public function take_assessment($token)
    {
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$au || $au->assessment_status !== 'active') {
            show_error('This assessment link is invalid or inactive.', 404);
        }
        if ((int) $au->user_id > 0) {
            $sessUid = (int) $this->session->userdata('user_id');
            if (!$sessUid || $sessUid !== (int) $au->user_id) {
                $this->session->set_flashdata('error', 'Please log in with the assigned employee account to take this assessment.');
                redirect('auth/login');
                return;
            }
        } else {
            if (trim((string) $au->candidate_name) === '' || trim((string) $au->candidate_email) === '') {
                $this->load->view('training_assessment/candidate_register', array('token' => $token));
                return;
            }
        }
        $this->session->set_userdata('ta_active_token', $token);
        $takePolicy = $this->_resolve_take_security_policy($au);
        if (empty($takePolicy['allowed'])) {
            show_error('You do not have permission to take this assessment.', 403);
        }

        $max = (int) $au->max_attempts;
        $used = (int) $au->attempts_used;
        $result = $this->ta->get_result_by_au((int) $au->id);
        if ($result && $au->completed_at) {
            $allow = ((int) $au->allow_retake === 1) && ($max === 0 || $used < $max);
            if (!$allow) {
                redirect(training_assessment_signed_result_url($token, $au));
                return;
            }
        }

        if (!$au->started_at) {
            if ($max > 0 && $used >= $max && $result) {
                show_error('Maximum attempts reached.', 403);
            }
        } else {
            if ($au->completed_at && $result) {
                redirect(training_assessment_signed_result_url($token, $au));
                return;
            }
        }

        $this->ta->start_attempt_if_needed($au);
        $au = $this->ta->get_assessment_user_by_token($token);

        $ids = $this->ta->parse_order_ids($au->question_order);
        if (count($ids) < 1) {
            show_error('This assessment has no questions yet.', 503);
        }

        $initialAnswered = array();
        foreach ($ids as $qid) {
            $q = $this->ta->get_question((int) $qid);
            $ans = $this->ta->get_answer((int) $au->id, (int) $qid);
            $initialAnswered[] = $q && $this->ta->is_answer_nonempty($q, $ans);
        }

        $data = array(
            'au' => $au,
            'token' => $token,
            'question_ids' => $ids,
            'total_questions' => count($ids),
            'ends_ts' => strtotime($au->server_ends_at),
            'shuffle_options' => (int) $au->shuffle_options === 1,
            'initial_answered' => $initialAnswered,
            'proctoring_enabled' => !empty($takePolicy['proctoring']),
        );
        $this->load->view('training_assessment/take_assessment', $data);
    }

    public function retake_assessment()
    {
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Use the Retake button from your result page.');
            redirect('training-assessment');
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$au) {
            show_404();
        }
        if ((int) $au->user_id > 0) {
            $sessUid = (int) $this->session->userdata('user_id');
            if (!$sessUid || $sessUid !== (int) $au->user_id) {
                $this->session->set_flashdata('error', 'Please log in with the assigned employee account to retake this assessment.');
                redirect('auth/login');
                return;
            }
        }
        if ((int) $au->allow_retake !== 1) {
            $this->session->set_flashdata('error', 'Retake is not allowed for this assessment.');
            redirect(training_assessment_signed_result_url($token, $au));
            return;
        }
        $max = (int) $au->max_attempts;
        $used = (int) $au->attempts_used;
        if ($max > 0 && $used >= $max) {
            $this->session->set_flashdata('error', 'No attempts remaining for this assessment.');
            redirect(training_assessment_signed_result_url($token, $au));
            return;
        }
        $actor = (int) $this->session->userdata('user_id');
        $this->load->model('Security_audit_model', 'audit');
        $this->audit->log('training_assessment_retake', $actor ? $actor : null, 'AU #' . (int) $au->id);
        $this->ta->delete_answers_for_user((int) $au->id);
        $this->ta->delete_result_for_user((int) $au->id);
        $this->ta->update_assessment_user((int) $au->id, array(
            'started_at' => null,
            'server_ends_at' => null,
            'completed_at' => null,
            'question_order' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        redirect('training-assessment/take/' . $token);
    }

    public function ajax_load_question()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false, 'error' => 'Method'));
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Auth'));
            return;
        }
        if ($au->completed_at) {
            echo json_encode(array('ok' => false, 'error' => 'Completed', 'redirect' => training_assessment_signed_result_url($token, $au)));
            return;
        }
        if ($this->ta->is_time_expired($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Time', 'force_submit' => true));
            return;
        }
        $ids = $this->ta->parse_order_ids($au->question_order);
        $idx = (int) $this->input->post('q_index');
        if ($idx < 0 || $idx >= count($ids)) {
            echo json_encode(array('ok' => false, 'error' => 'Index'));
            return;
        }
        $qid = $ids[$idx];
        $q = $this->ta->get_question($qid);
        if (!$q || (int) $q->assessment_id !== (int) $au->assessment_id) {
            echo json_encode(array('ok' => false, 'error' => 'Question'));
            return;
        }
        $opts = array();
        if ($q->question_type === 'mcq') {
            $opts = $this->ta->get_options((int) $q->id);
            if ((int) $au->shuffle_options === 1 && count($opts) > 1) {
                shuffle($opts);
            }
        }
        $ans = $this->ta->get_answer((int) $au->id, (int) $q->id);
        $html = $this->load->view('training_assessment/partials/question_block', array(
            'q' => $q,
            'opts' => $opts,
            'ans' => $ans,
            'q_index' => $idx,
        ), true);
        echo json_encode(array(
            'ok' => true,
            'html' => $html,
            'q_index' => $idx,
            'total' => count($ids),
            'ends_ts' => strtotime($au->server_ends_at),
            'csrf' => $this->security->get_csrf_hash(),
        ));
    }

    public function ajax_save_answer()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false));
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Auth'));
            return;
        }
        if ($au->completed_at) {
            echo json_encode(array('ok' => false, 'error' => 'Closed', 'redirect' => training_assessment_signed_result_url($token, $au)));
            return;
        }
        if ($this->ta->is_time_expired($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Time', 'force_submit' => true));
            return;
        }
        $qid = (int) $this->input->post('question_id');
        $q = $this->ta->get_question($qid);
        if (!$q || (int) $q->assessment_id !== (int) $au->assessment_id) {
            echo json_encode(array('ok' => false));
            return;
        }
        $fields = array();
        if ($q->question_type === 'mcq') {
            $selCsv = trim((string)$this->input->post('selected_option_ids'));
            $selArr = $this->ta->parse_option_ids($selCsv);
            if (empty($selArr)) {
                $single = (int) $this->input->post('selected_option_id');
                if ($single > 0) {
                    $selArr = array($single);
                }
            }
            $fields['selected_option_ids'] = empty($selArr) ? null : implode(',', $selArr);
            $fields['selected_option_id'] = empty($selArr) ? null : (int) $selArr[0];
            $fields['answer_text'] = null;
            $fields['code_submitted'] = null;
            $fields['execution_output'] = null;
        } elseif ($q->question_type === 'text') {
            $fields['selected_option_id'] = null;
            $fields['answer_text'] = $this->input->post('answer_text');
            $fields['code_submitted'] = null;
            $fields['execution_output'] = null;
        } else {
            $fields['selected_option_id'] = null;
            $fields['answer_text'] = null;
            $fields['code_submitted'] = $this->input->post('code_submitted');
            $fields['execution_output'] = trim((string) $this->input->post('execution_output'));
        }
        $this->ta->upsert_answer((int) $au->id, $qid, $fields);
        echo json_encode(array('ok' => true, 'csrf' => $this->security->get_csrf_hash()));
    }

    public function ajax_run_code()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false));
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Auth'));
            return;
        }
        $lang = strtolower(trim((string) $this->input->post('language')));
        if ($lang === 'js') {
            echo json_encode(array(
                'ok' => true,
                'client_js' => true,
                'message' => 'JavaScript runs in your browser for this preview.',
            ));
            return;
        }
        if ($lang === 'php') {
            echo json_encode(array(
                'ok' => false,
                'error' => 'disabled',
                'message' => 'Server-side PHP execution is disabled for security. Use JavaScript mode for in-browser preview, or run your snippet locally.',
            ));
            return;
        }
        if ($au->completed_at || $this->ta->is_time_expired($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Time', 'force_submit' => true));
            return;
        }
        echo json_encode(array('ok' => false, 'error' => 'unsupported', 'message' => 'Unsupported language.'));
    }

    public function ajax_timer_sync()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false));
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Auth'));
            return;
        }
        $endsTs = $au->server_ends_at ? strtotime($au->server_ends_at) : 0;
        $expired = $this->ta->is_time_expired($au);
        echo json_encode(array(
            'ok' => true,
            'ends_ts' => (int) $endsTs,
            'server_ts' => (int) time(),
            'expired' => (bool) $expired,
            'completed' => (bool) $au->completed_at,
            'csrf' => $this->security->get_csrf_hash(),
        ));
    }

    public function ajax_upload_screenshot()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false, 'error' => 'Method'));
            return;
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Auth'));
            return;
        }
        if ($au->completed_at || $this->ta->is_time_expired($au)) {
            echo json_encode(array('ok' => false, 'error' => 'Closed', 'force_submit' => true));
            return;
        }
        $takePolicy = $this->_resolve_take_security_policy($au);
        if (empty($takePolicy['allowed']) || empty($takePolicy['proctoring'])) {
            echo json_encode(array('ok' => false, 'error' => 'ProctoringDisabled'));
            return;
        }

        $dataUrl = trim((string) $this->input->post('capture_data'));
        if ($dataUrl === '' || strpos($dataUrl, 'data:image/jpeg;base64,') !== 0) {
            echo json_encode(array('ok' => false, 'error' => 'Format'));
            return;
        }
        $base64 = substr($dataUrl, strlen('data:image/jpeg;base64,'));
        $raw = base64_decode($base64, true);
        if ($raw === false || strlen($raw) < 100) {
            echo json_encode(array('ok' => false, 'error' => 'Decode'));
            return;
        }

        $relDir = 'uploads/training_assessment/screenshots/' . (int) $au->id . '/';
        $absDir = FCPATH . $relDir;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0750, true);
        }
        if (!is_dir($absDir)) {
            echo json_encode(array('ok' => false, 'error' => 'Storage'));
            return;
        }

        $rand = substr(md5(uniqid('', true)), 0, 8);
        if (function_exists('random_bytes')) {
            try {
                $rand = bin2hex(random_bytes(4));
            } catch (Exception $e) {
                $rand = substr(md5(uniqid('', true)), 0, 8);
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rand = bin2hex(openssl_random_pseudo_bytes(4));
        }
        $filename = 'ss_' . date('Ymd_His') . '_' . $rand . '.jpg';
        $absPath = $absDir . $filename;
        if (@file_put_contents($absPath, $raw) === false) {
            echo json_encode(array('ok' => false, 'error' => 'Write'));
            return;
        }

        $capturePath = $relDir . $filename;
        $now = date('Y-m-d H:i:s');
        $this->ta->insert_attempt_screenshot(array(
            'assessment_user_id' => (int) $au->id,
            'user_id' => ((int) $au->user_id > 0) ? (int) $au->user_id : null,
            'capture_path' => $capturePath,
            'captured_at' => $now,
            'created_at' => $now,
            'ip_address' => (string) $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
        ));
        echo json_encode(array('ok' => true, 'csrf' => $this->security->get_csrf_hash()));
    }

    public function submit_assessment()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $token = trim((string) $this->input->post('access_token'));
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$this->ta_run->ajax_auth_au($au)) {
            show_error('Unauthorized.', 403);
        }
        if ($au->completed_at) {
            redirect(training_assessment_signed_result_url($token, $au));
            return;
        }
        $this->ta_run->merge_finalize_answer_on_submit($au);
        $this->ta->finalize_result((int) $au->id);
        $au2 = $this->ta->get_assessment_user_by_token($token);
        $this->load->model('Security_audit_model', 'audit');
        $actor = (int) $this->session->userdata('user_id');
        $this->audit->log(
            'training_assessment_submitted',
            $actor ? $actor : null,
            'assessment_user_id=' . (int) $au->id . ' assessment_id=' . (int) $au->assessment_id
        );
        $this->session->set_userdata('ta_active_token', $token);
        $trigger = trim((string) $this->input->post('submission_trigger'));
        if ($trigger === 'time_up' || $trigger === 'server_time') {
            $this->session->set_flashdata('ta_submit_notice', 'Time ran out — your answers were saved and the assessment was submitted automatically.');
        }
        redirect(training_assessment_signed_result_url($token, $au2 ? $au2 : $au));
    }

    public function result_token($token)
    {
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $token = rawurldecode($token);
        $au = $this->ta->get_assessment_user_by_token($token);
        if (!$au) {
            show_404();
        }
        if ($au->completed_at) {
            $sig = $this->input->get('sig');
            if (training_assessment_valid_result_signature($au, $sig)) {
                $this->session->set_userdata('ta_active_token', $token);
                $this->ta_run->render_result($au);
                return;
            }
            if ((string) $this->session->userdata('ta_active_token') === $au->access_token) {
                $this->session->set_userdata('ta_active_token', $token);
                $this->ta_run->render_result($au);
                return;
            }
            $uid = (int) $this->session->userdata('user_id');
            if ($uid > 0 && (int) $au->user_id === $uid) {
                $this->session->set_userdata('ta_active_token', $token);
                $this->ta_run->render_result($au);
                return;
            }
            if ($this->ta_run->can_manage_result($au)) {
                $this->session->set_userdata('ta_active_token', $token);
                $this->ta_run->render_result($au);
                return;
            }
            show_error('This result link is missing a valid signature. Use the link from My assignments or sign in as the assignee.', 403);
        }
        $sessTok = (string) $this->session->userdata('ta_active_token');
        $uid = (int) $this->session->userdata('user_id');
        $this->load->helper('training');
        $allowIncomplete = ($sessTok === $token)
            || ($uid > 0 && (int) $au->user_id === $uid)
            || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        if (!$allowIncomplete) {
            show_error('Access denied.', 403);
        }
        redirect('training-assessment/take/' . rawurlencode($token));
    }
}
