<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared logic for Training_assessment + Training_assessment_take (results, import, assign helpers).
 */
class Training_assessment_runtime
{
    /** @var CI_Controller */
    public $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        if (!isset($this->CI->ta)) {
            $this->CI->load->model('Training_assessment_model', 'ta');
        }
    }

    /**
     * @param object $au assessment_users row (+ joins)
     */
    public function render_result($au)
    {
        $ta = $this->CI->ta;
        $result = $ta->get_result_by_au((int) $au->id);
        $details = $ta->list_answers_with_questions((int) $au->id, isset($au->question_order) ? $au->question_order : null);
        $assessment = $ta->get_assessment((int) $au->assessment_id);
        $showCorrect = $assessment && isset($assessment->show_correct_after_submit) && (int) $assessment->show_correct_after_submit === 1;
        if ($showCorrect && !empty($details)) {
            foreach ($details as $d) {
                $d->ta_your_answer = '';
                $d->ta_correct_summary = '';
                if ($d->question_type === 'mcq') {
                    $opts = $ta->get_options((int) $d->question_id);
                    foreach ($opts as $o) {
                        if ((int) $d->selected_option_id === (int) $o->id) {
                            $d->ta_your_answer = $o->option_text;
                        }
                        if ((int) $o->is_correct === 1) {
                            $d->ta_correct_summary = $o->option_text;
                        }
                    }
                } elseif ($d->question_type === 'text') {
                    $d->ta_your_answer = trim((string) $d->answer_text);
                    $d->ta_correct_summary = trim((string) $d->model_answer) !== '' ? $d->model_answer : '(No model answer — scoring used length / similarity.)';
                } else {
                    $d->ta_your_answer = trim((string) $d->code_submitted);
                    if (trim((string) $d->execution_output) !== '') {
                        $d->ta_your_answer .= ($d->ta_your_answer !== '' ? "\n\nOutput:\n" : "Output:\n") . trim((string) $d->execution_output);
                    }
                    $d->ta_correct_summary = trim((string) $d->coding_expected_output) !== '' ? ('Expected output: ' . $d->coding_expected_output) : '(No fixed expected output.)';
                }
            }
        }
        $uid = (int) $this->CI->session->userdata('user_id');
        $sessTok = (string) $this->CI->session->userdata('ta_active_token');
        $is_assignee_or_token = ($sessTok === $au->access_token)
            || ($uid > 0 && (int) $au->user_id === $uid);
        $viewer_ok = $is_assignee_or_token || $this->is_training_ta_org_viewer($au);
        $max = (int) $au->max_attempts;
        $used = (int) $au->attempts_used;
        $can_retake = ((int) $au->allow_retake === 1) && $result && ($max === 0 || $used < $max);
        $data = array(
            'au' => $au,
            'result' => $result,
            'details' => $details,
            'show_retake' => $is_assignee_or_token && $can_retake,
            'show_correct' => $showCorrect,
        );
        $this->CI->load->view('training_assessment/result', $data);
    }

    public function can_manage_result($au)
    {
        if ($this->is_training_ta_org_viewer($au)) {
            return true;
        }
        $uid = (int) $this->CI->session->userdata('user_id');
        return $uid && (int) $au->user_id === $uid;
    }

    /**
     * Broad admins or same-department team lead (training_screen_ta_team_progress) may view others' results.
     * Intentionally excludes training_screen_ta_report alone — report uses signed links or scoped rows.
     *
     * @param object $au assessment_users row
     */
    public function is_training_ta_org_viewer($au)
    {
        if (function_exists('training_ta_admin_broad') && training_ta_admin_broad()) {
            return true;
        }
        return $this->can_team_lead_view_assignee($au);
    }

    /**
     * Team progress screen: same department as assignee (employee attempts only).
     *
     * @param object $au assessment_users row
     */
    public function can_team_lead_view_assignee($au)
    {
        if (!function_exists('has_module_access') || !has_module_access('training_screen_ta_team_progress')) {
            return false;
        }
        $assigneeUid = (int) $au->user_id;
        if ($assigneeUid < 1) {
            return false;
        }
        $viewerUid = (int) $this->CI->session->userdata('user_id');
        if ($viewerUid < 1) {
            return false;
        }
        if ($viewerUid === $assigneeUid) {
            return true;
        }
        if (!$this->CI->db->table_exists('employees')) {
            return false;
        }
        $this->CI->load->model('Employee_model', 'ta_emp_viewer');
        $v = $this->CI->ta_emp_viewer->get_by_user_id($viewerUid);
        $a = $this->CI->ta_emp_viewer->get_by_user_id($assigneeUid);
        if (!$v || !$a) {
            return false;
        }
        $dv = isset($v->department) ? trim((string) $v->department) : '';
        $da = isset($a->department) ? trim((string) $a->department) : '';
        if ($dv === '' || $da === '') {
            return false;
        }
        return strcasecmp($dv, $da) === 0;
    }

    /**
     * @deprecated Use is_training_ta_org_viewer() or can_team_lead_view_assignee() — report is no longer blanket-privileged.
     */
    public function is_privileged_viewer()
    {
        $rid = (int) $this->CI->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('training_assessment')
            || has_module_access('training_assessment_manage')
            || has_module_access('training_screen_ta_team_progress');
    }

    public function merge_finalize_answer_on_submit($au)
    {
        $ta = $this->CI->ta;
        $fq = (int) $this->CI->input->post('finalize_question_id');
        if ($fq < 1) {
            return;
        }
        $q = $ta->get_question($fq);
        if (!$q || (int) $q->assessment_id !== (int) $au->assessment_id) {
            return;
        }
        $fields = array();
        if ($q->question_type === 'mcq') {
            $fields['selected_option_id'] = (int) $this->CI->input->post('finalize_selected_option_id') ?: null;
            $fields['answer_text'] = null;
            $fields['code_submitted'] = null;
            $fields['execution_output'] = null;
        } elseif ($q->question_type === 'text') {
            $fields['selected_option_id'] = null;
            $fields['answer_text'] = $this->CI->input->post('finalize_answer_text');
            $fields['code_submitted'] = null;
            $fields['execution_output'] = null;
        } else {
            $fields['selected_option_id'] = null;
            $fields['answer_text'] = null;
            $fields['code_submitted'] = $this->CI->input->post('finalize_code_submitted');
            $fields['execution_output'] = trim((string) $this->CI->input->post('finalize_execution_output'));
        }
        $ta->upsert_answer((int) $au->id, $fq, $fields);
    }

    public function ajax_auth_au($au)
    {
        if (!$au || $au->assessment_status !== 'active') {
            return false;
        }
        if ((int) $au->user_id > 0) {
            return (int) $this->CI->session->userdata('user_id') === (int) $au->user_id;
        }
        return true;
    }

    public function generate_token()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(16));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes(16));
        }
        return md5(uniqid(mt_rand(), true));
    }

    /**
     * @return array{ok:bool,reason?:string}
     */
    public function send_assessment_invite_email($toEmail, $recipientName, $assessmentTitle, $linkUrl)
    {
        $toEmail = trim((string) $toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'reason' => 'invalid');
        }
        $this->CI->load->helper('email');
        if (!isset($this->CI->settings)) {
            $this->CI->load->model('Setting_model', 'settings');
        }
        $smtpUser = $this->CI->settings->get_setting('email_smtp_user');
        $smtpPass = $this->CI->settings->get_setting('email_smtp_pass');
        if (!$smtpUser || !$smtpPass) {
            return array('ok' => false, 'reason' => 'not_configured');
        }
        $this->CI->load->library('email');
        configure_email_from_settings();
        $from = get_system_from_email();
        $fromName = function_exists('get_company_name') ? get_company_name() : 'Office Management System';
        $subject = 'Assessment invitation: ' . $assessmentTitle;
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($assessmentTitle, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8');
        $html = '<p>Hello ' . $safeName . ',</p>'
            . '<p>You have been invited to complete this assessment:</p>'
            . '<p><strong>' . $safeTitle . '</strong></p>'
            . '<p><a href="' . $safeLink . '" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;">Open assessment</a></p>'
            . '<p>Or copy this link into your browser:</p>'
            . '<p style="word-break:break-all;font-family:monospace;font-size:13px">' . $safeLink . '</p>'
            . '<p style="color:#666;font-size:12px">This is an automated message.</p>';
        $this->CI->email->clear(true);
        $this->CI->email->from($from ? $from : 'no-reply@example.com', $fromName);
        $this->CI->email->to($toEmail);
        $this->CI->email->subject($subject);
        $this->CI->email->message($html);
        $sent = $this->CI->email->send();
        if (!$sent) {
            log_message('error', 'Training assessment invite email failed for ' . $toEmail);
            return array('ok' => false, 'reason' => 'send_failed');
        }
        return array('ok' => true);
    }

    public function csv_header_key($h)
    {
        $h = trim((string) $h);
        if (strncmp($h, "\xEF\xBB\xBF", 3) === 0) {
            $h = substr($h, 3);
        }
        $h = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $h));
        $h = trim($h, '_');
        return $h;
    }

    public function csv_row_aliases($row)
    {
        $aliases = array(
            'title' => 'assessment_title',
            'assessment_name' => 'assessment_title',
            'name' => 'assessment_title',
            'description' => 'assessment_description',
            'question' => 'question_text',
            'body' => 'question_text',
            'type' => 'question_type',
            'correct' => 'correct_option',
            'correct_index' => 'correct_option',
            'expected_output' => 'coding_expected_output',
        );
        foreach ($aliases as $from => $to) {
            if ((!isset($row[$to]) || trim((string) $row[$to]) === '') && isset($row[$from]) && trim((string) $row[$from]) !== '') {
                $row[$to] = $row[$from];
            }
        }
        for ($i = 1; $i <= 6; $i++) {
            $alt = 'option_' . $i;
            $can = 'opt' . $i;
            if ((!isset($row[$can]) || trim((string) $row[$can]) === '') && isset($row[$alt])) {
                $row[$can] = $row[$alt];
            }
        }
        return $row;
    }

    public function csv_bool($v, $default = 0)
    {
        $s = strtolower(trim((string) $v));
        if ($s === '') {
            return (int) $default;
        }
        if (in_array($s, array('1', 'yes', 'y', 'true', 'on'), true)) {
            return 1;
        }
        return 0;
    }

    /**
     * @return array{fatal?:string,rows:array}
     */
    public function parse_assessment_csv($path)
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) {
            return array('fatal' => 'Could not read the uploaded file.', 'rows' => array());
        }
        $headerParts = fgetcsv($fh);
        if ($headerParts === false || empty($headerParts)) {
            fclose($fh);
            return array('fatal' => 'CSV is empty or missing a header row.', 'rows' => array());
        }
        if (isset($headerParts[0]) && is_string($headerParts[0]) && strncmp($headerParts[0], "\xEF\xBB\xBF", 3) === 0) {
            $headerParts[0] = substr($headerParts[0], 3);
        }
        $keys = array();
        foreach ($headerParts as $h) {
            $keys[] = $this->csv_header_key($h);
        }
        if (count($keys) < 3) {
            fclose($fh);
            return array('fatal' => 'CSV header row is invalid.', 'rows' => array());
        }
        $rows = array();
        $lineNum = 2;
        while (($cells = fgetcsv($fh)) !== false) {
            $allEmpty = true;
            foreach ($cells as $c) {
                if (trim((string) $c) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                $lineNum++;
                continue;
            }
            $assoc = array();
            foreach ($keys as $i => $k) {
                if ($k === '') {
                    continue;
                }
                $assoc[$k] = isset($cells[$i]) ? $cells[$i] : '';
            }
            $assoc = $this->csv_row_aliases($assoc);
            $rows[] = array('line' => $lineNum, 'data' => $assoc);
            $lineNum++;
        }
        fclose($fh);
        return array('rows' => $rows);
    }

    /**
     * @return array{assessment_id?:int,question_count?:int,errors:array}
     */
    public function import_assessment_rows($rows, $user_id)
    {
        $ta = $this->CI->ta;
        $errors = array();
        if (empty($rows)) {
            $errors[] = 'No data rows found after the header.';
            return array('errors' => $errors);
        }
        $first = $rows[0]['data'];
        $title = trim(isset($first['assessment_title']) ? $first['assessment_title'] : '');
        if ($title === '') {
            $errors[] = 'Row ' . (int) $rows[0]['line'] . ': assessment_title is required on the first data row.';
            return array('errors' => $errors);
        }
        $now = date('Y-m-d H:i:s');
        $assessmentData = array(
            'title' => $title,
            'description' => trim(isset($first['assessment_description']) ? $first['assessment_description'] : ''),
            'time_limit_minutes' => max(1, (int)(isset($first['time_limit_minutes']) && $first['time_limit_minutes'] !== '' ? $first['time_limit_minutes'] : 30)),
            'passing_marks' => min(100, max(0, (float)(isset($first['passing_marks']) && $first['passing_marks'] !== '' ? $first['passing_marks'] : 60))),
            'randomize_questions' => $this->csv_bool(isset($first['randomize_questions']) ? $first['randomize_questions'] : '', 0),
            'shuffle_options' => $this->csv_bool(isset($first['shuffle_options']) ? $first['shuffle_options'] : '', 0),
            'max_attempts' => max(0, (int)(isset($first['max_attempts']) && $first['max_attempts'] !== '' ? $first['max_attempts'] : 1)),
            'allow_retake' => $this->csv_bool(isset($first['allow_retake']) ? $first['allow_retake'] : '', 0),
            'show_correct_after_submit' => $this->csv_bool(isset($first['show_correct_after_submit']) ? $first['show_correct_after_submit'] : '', 0),
            'status' => (isset($first['status']) && strtolower(trim($first['status'])) === 'inactive') ? 'inactive' : 'active',
            'created_by' => $user_id > 0 ? $user_id : null,
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->CI->db->trans_start();
        $aId = $ta->insert_assessment($assessmentData);
        if (!$aId) {
            $this->CI->db->trans_rollback();
            $errors[] = 'Could not create assessment record.';
            return array('errors' => $errors);
        }

        $qCount = 0;
        $autoSort = 0;
        foreach ($rows as $r) {
            $d = $r['data'];
            $line = (int) $r['line'];
            $qtext = trim(isset($d['question_text']) ? $d['question_text'] : '');
            if ($qtext === '') {
                continue;
            }
            $type = strtolower(trim(isset($d['question_type']) ? $d['question_type'] : ''));
            if (!in_array($type, array('mcq', 'text', 'coding'), true)) {
                $this->CI->db->trans_rollback();
                $errors[] = 'Row ' . $line . ': question_type must be mcq, text, or coding.';
                return array('errors' => $errors);
            }
            $points = isset($d['points']) && $d['points'] !== '' ? (float) $d['points'] : 1.0;
            if ($points < 0.01) {
                $points = 0.01;
            }
            if (isset($d['sort_order']) && trim((string) $d['sort_order']) !== '') {
                $autoSort = (int) $d['sort_order'];
            } else {
                $autoSort++;
            }

            $qRow = array(
                'assessment_id' => (int) $aId,
                'question_type' => $type,
                'question_text' => $qtext,
                'points' => $points,
                'coding_language' => null,
                'model_answer' => null,
                'coding_expected_output' => null,
                'sort_order' => $autoSort,
                'created_at' => $now,
                'updated_at' => $now,
            );
            if ($type === 'text') {
                $qRow['model_answer'] = trim(isset($d['model_answer']) ? $d['model_answer'] : '');
            }
            if ($type === 'coding') {
                $lang = strtolower(trim(isset($d['coding_language']) ? $d['coding_language'] : 'php'));
                $qRow['coding_language'] = ($lang === 'js') ? 'js' : 'php';
                $qRow['coding_expected_output'] = trim(isset($d['coding_expected_output']) ? $d['coding_expected_output'] : '');
            }

            $qid = $ta->insert_question($qRow);
            if (!$qid) {
                $this->CI->db->trans_rollback();
                $errors[] = 'Row ' . $line . ': failed to save question.';
                return array('errors' => $errors);
            }

            if ($type === 'mcq') {
                $opts = array();
                for ($i = 1; $i <= 6; $i++) {
                    $k = 'opt' . $i;
                    $t = isset($d[$k]) ? trim((string) $d[$k]) : '';
                    if ($t !== '') {
                        $opts[] = $t;
                    }
                }
                if (count($opts) < 2) {
                    $this->CI->db->trans_rollback();
                    $errors[] = 'Row ' . $line . ': MCQ needs at least two non-empty options (opt1, opt2, …).';
                    return array('errors' => $errors);
                }
                $correctRaw = isset($d['correct_option']) ? trim((string) $d['correct_option']) : '1';
                $correctIdx = (int) $correctRaw;
                if ($correctIdx < 1) {
                    $correctIdx = 1;
                }
                if ($correctIdx > count($opts)) {
                    $this->CI->db->trans_rollback();
                    $errors[] = 'Row ' . $line . ': correct_option must be between 1 and ' . count($opts) . ' (matching non-empty options in order).';
                    return array('errors' => $errors);
                }
                $optRows = array();
                $j = 0;
                foreach ($opts as $txt) {
                    $optRows[] = array(
                        'question_id' => (int) $qid,
                        'option_text' => $txt,
                        'is_correct' => ($j === ($correctIdx - 1)) ? 1 : 0,
                        'sort_order' => $j,
                        'created_at' => $now,
                    );
                    $j++;
                }
                $ta->replace_options((int) $qid, $optRows);
            }
            $qCount++;
        }

        if ($qCount < 1) {
            $this->CI->db->trans_rollback();
            $errors[] = 'No questions with question_text were found.';
            return array('errors' => $errors);
        }

        $this->CI->db->trans_complete();
        if ($this->CI->db->trans_status() === false) {
            $errors[] = 'Database transaction failed.';
            return array('errors' => $errors);
        }
        return array('assessment_id' => (int) $aId, 'question_count' => $qCount, 'errors' => array());
    }

    /**
     * @return int[]
     */
    public function user_ids_for_department($department)
    {
        $department = trim((string) $department);
        if ($department === '' || !$this->CI->db->table_exists('employees')) {
            return array();
        }
        $q = $this->CI->db->select('user_id')
            ->from('employees')
            ->where('department', $department)
            ->where('user_id IS NOT NULL', null, false)
            ->where('user_id >', 0)
            ->get();
        $out = array();
        foreach ($q->result() as $r) {
            $out[] = (int) $r->user_id;
        }
        return array_values(array_unique($out));
    }

    /**
     * @return string[]
     */
    public function distinct_departments()
    {
        if (!$this->CI->db->table_exists('employees')) {
            return array();
        }
        $q = $this->CI->db->distinct()
            ->select('department')
            ->from('employees')
            ->where('department IS NOT NULL', null, false)
            ->where('department !=', '')
            ->order_by('department', 'ASC')
            ->get();
        $out = array();
        foreach ($q->result() as $r) {
            $d = trim((string) $r->department);
            if ($d !== '') {
                $out[] = $d;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @return array{name:string,email:string}|null
     */
    public function parse_bulk_candidate_line($line)
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }
        if (preg_match('/^(.+?)\s*[;,]\s*([^\s;,]+@[^\s;,]+)$/u', $line, $m)) {
            $name = trim($m[1], " \t\"'");
            $email = trim($m[2]);
        } else {
            $email = $line;
            $name = 'Candidate';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return array('name' => $name !== '' ? $name : 'Candidate', 'email' => $email);
    }
}
