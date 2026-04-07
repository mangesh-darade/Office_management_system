<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Training & Assessment module — assessments, questions, assignments, reports, manager views.
 * Anonymous take / submit / AJAX live in {@see Training_assessment_take}.
 */
class Training_assessment extends CI_Controller
{
    /** @var Training_assessment_model */
    public $ta;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Training_assessment_model', 'ta');
        $this->load->library('training_assessment_runtime', null, 'ta_run');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'permission', 'training'));

        $method = $this->router->fetch_method();

        if ($this->_ta_is_take_only_learner()) {
            if ($method === 'certificate') {
                if (!(int) $this->session->userdata('user_id')) {
                    redirect('auth/login');
                }
                return;
            }
            return;
        }

        if (!training_ta_has_any_admin_screen()) {
            $this->session->set_flashdata('access_denied', 'You do not have access to Training & Assessment.');
            redirect('dashboard');
        }
    }

    private function _ta_require_screen($granular_key)
    {
        if (!training_ta_can_screen($granular_key)) {
            show_error('Access denied.', 403);
        }
    }

    private function _ta_can_import_questions()
    {
        return training_ta_can_screen('training_screen_ta_create')
            || training_ta_can_screen('training_screen_ta_question_import');
    }

    private function _ta_require_question_import()
    {
        if (!$this->_ta_can_import_questions()) {
            show_error('Access denied.', 403);
        }
    }

    private function _ta_require_manage_core()
    {
        if (!training_ta_admin_broad()) {
            show_error('Access denied.', 403);
        }
    }

    /**
     * Logged-in user has only training_assessment_take (no manage / full module).
     */
    private function _ta_is_take_only_learner()
    {
        $uid = (int) $this->session->userdata('user_id');
        if (!$uid || !function_exists('has_module_access')) {
            return false;
        }
        if ((int) $this->session->userdata('role_id') === 1) {
            return false;
        }
        return (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests'))
            && !has_module_access('training_assessment')
            && !has_module_access('training_assessment_manage')
            && !training_ta_has_any_admin_screen();
    }

    public function index()
    {
        redirect('training_assessment/dashboard');
    }

    public function dashboard()
    {
        $canDashboard = training_ta_can_screen('training_screen_ta_dashboard')
            || (function_exists('has_module_access') && (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests')));
        if (!$canDashboard) {
            show_error('Access denied.', 403);
        }
        if (!$this->ta->schema_ready()) {
            $this->load->view('partials/header', array('title' => 'Training & Assessment'));
            echo '<div class="container py-5"><div class="alert alert-warning">Database tables are not installed. Run <code>database/training_assessment_module.sql</code> on your database.</div></div>';
            $this->load->view('partials/footer');
            return;
        }
        $search = trim((string)$this->input->get('q'));
        $status = $this->input->get('status');
        $status = in_array($status, array('all', 'active', 'inactive'), true) ? $status : 'all';
        $sort = $this->input->get('sort');
        $allowedSort = array('created_desc', 'created_asc', 'title_asc', 'title_desc', 'questions_desc');
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_desc';
        }
        $this->load->helper('training');
        $uid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        if (!$isBroad && $uid < 1) {
            redirect('auth/login');
            return;
        }
        $scope = ($isBroad || $uid < 1) ? 0 : $uid;
        $data['assessments'] = $this->ta->list_assessments_with_stats($search, $status, $sort, $scope);
        $data['dashboard_scope_limited'] = !$isBroad && $uid > 0;
        $data['filter_q'] = $search;
        $data['filter_status'] = $status;
        $data['filter_sort'] = $sort;
        $data['stats_total_assigned'] = 0;
        $data['stats_total_completed'] = 0;
        foreach ($data['assessments'] as $row) {
            $data['stats_total_assigned'] += (int)$row->assigned_count;
            $data['stats_total_completed'] += (int)$row->completed_count;
        }
        $data['stats_total_pending'] = max(0, $data['stats_total_assigned'] - $data['stats_total_completed']);
        $data['ta_can_create'] = function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_create');
        $data['ta_can_import'] = function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_import');
        $data['ta_can_manage_core'] = function_exists('training_ta_admin_broad') && training_ta_admin_broad();
        $this->load->view('training_assessment/dashboard', $data);
    }

    public function create_assessment($id = null)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $data['assessment'] = null;
        if ($id !== null && $id !== '') {
            $data['assessment'] = $this->ta->get_assessment((int)$id);
            if (!$data['assessment']) {
                show_404();
            }
        }
        $this->load->view('training_assessment/create_assessment', $data);
    }

    public function save_assessment()
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int)$this->input->post('id');
        $title = trim((string)$this->input->post('title'));
        if ($title === '') {
            $this->session->set_flashdata('error', 'Title is required.');
            redirect($id ? 'training-assessment/edit/' . $id : 'training-assessment/create');
            return;
        }
        $row = array(
            'title' => $title,
            'description' => trim((string)$this->input->post('description')),
            'time_limit_minutes' => max(1, (int)$this->input->post('time_limit_minutes')),
            'passing_marks' => min(100, max(0, (float)$this->input->post('passing_marks'))),
            'randomize_questions' => $this->input->post('randomize_questions') ? 1 : 0,
            'shuffle_options' => $this->input->post('shuffle_options') ? 1 : 0,
            'max_attempts' => max(0, (int)$this->input->post('max_attempts')),
            'allow_retake' => $this->input->post('allow_retake') ? 1 : 0,
            'show_correct_after_submit' => $this->input->post('show_correct_after_submit') ? 1 : 0,
            'status' => $this->input->post('status') === 'inactive' ? 'inactive' : 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($id) {
            $this->ta->update_assessment($id, $row);
            $this->session->set_flashdata('success', 'Assessment updated.');
            redirect('training-assessment/questions/' . $id);
            return;
        }
        $row['created_by'] = (int)$this->session->userdata('user_id');
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = $this->ta->insert_assessment($row);
        $this->session->set_flashdata('success', 'Assessment created. Add questions next.');
        redirect('training-assessment/questions/' . $newId);
    }

    public function delete_assessment($id)
    {
        $this->_ta_require_manage_core();
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->ta->delete_assessment((int)$id);
        $this->session->set_flashdata('success', 'Assessment deleted.');
        redirect('training-assessment');
    }

    /**
     * POST: duplicate assessment (questions + options).
     */
    public function duplicate_assessment($id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $newId = $this->ta->duplicate_assessment((int)$id, (int)$this->session->userdata('user_id'));
        if (!$newId) {
            $this->session->set_flashdata('error', 'Could not duplicate assessment.');
            redirect('training-assessment');
            return;
        }
        $this->session->set_flashdata('success', 'Assessment duplicated. You can edit the copy below.');
        redirect('training-assessment/edit/' . $newId);
    }

    /**
     * POST: duplicate one question within its assessment.
     */
    public function duplicate_question($question_id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $q = $this->ta->get_question((int)$question_id);
        if (!$q) {
            show_404();
        }
        $newId = $this->ta->duplicate_question((int)$question_id);
        if (!$newId) {
            $this->session->set_flashdata('error', 'Could not duplicate question.');
            redirect('training-assessment/questions/' . (int)$q->assessment_id);
            return;
        }
        $this->session->set_flashdata('success', 'Question duplicated.');
        redirect('training-assessment/questions/' . (int)$q->assessment_id);
    }

    /**
     * POST JSON: reorder questions (ids in order for one assessment).
     */
    public function reorder_questions()
    {
        $this->_ta_require_screen('training_screen_ta_create');
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            echo json_encode(array('ok' => false));
            return;
        }
        if (!$this->ta->schema_ready()) {
            echo json_encode(array('ok' => false, 'error' => 'schema'));
            return;
        }
        $assessment_id = (int)$this->input->post('assessment_id');
        $raw = $this->input->post('order');
        if (!is_array($raw)) {
            $raw = json_decode((string)$this->input->post('order_json'), true);
        }
        if (!is_array($raw) || $assessment_id < 1) {
            echo json_encode(array('ok' => false, 'error' => 'input'));
            return;
        }
        $ids = array();
        foreach ($raw as $x) {
            $ids[] = (int)$x;
        }
        $this->ta->reorder_questions($assessment_id, $ids);
        echo json_encode(array('ok' => true, 'csrf' => $this->security->get_csrf_hash()));
    }

    /**
     * Read-only candidate preview (admin).
     */
    public function preview_assessment($assessment_id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $a = $this->ta->get_assessment((int)$assessment_id);
        if (!$a) {
            show_404();
        }
        $data['assessment'] = $a;
        $data['questions'] = $this->ta->list_questions((int)$assessment_id);
        $data['options_by_qid'] = array();
        foreach ($data['questions'] as $q) {
            if ($q->question_type === 'mcq') {
                $data['options_by_qid'][(int)$q->id] = $this->ta->get_options((int)$q->id);
            }
        }
        $this->load->view('training_assessment/preview_assessment', $data);
    }

    /**
     * Import one assessment + questions from a UTF-8 CSV (see samples/training_assessment_import_sample.csv).
     */
    public function import_assessment()
    {
        $this->_ta_require_screen('training_screen_ta_import');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $samplePath = FCPATH . 'samples/training_assessment_import_sample.csv';
        $data['sample_exists'] = is_file($samplePath);
        $this->load->view('training_assessment/import_assessment', $data);
    }

    /**
     * Download the bundled sample CSV (same columns as the importer expects).
     */
    public function import_sample_csv()
    {
        $this->_ta_require_screen('training_screen_ta_import');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $path = FCPATH . 'samples/training_assessment_import_sample.csv';
        if (!is_file($path)) {
            show_error('Sample file is missing on the server.', 404);
        }
        $name = 'training_assessment_import_sample.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    /**
     * POST: csv_file upload → create assessment and all questions in a transaction.
     */
    public function import_process()
    {
        $this->_ta_require_screen('training_screen_ta_import');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Please choose a CSV file to upload.');
            redirect('training-assessment/import');
            return;
        }
        $err = $_FILES['csv_file']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'Upload failed (code ' . (int)$err . ').');
            redirect('training-assessment/import');
            return;
        }
        if (!empty($_FILES['csv_file']['size']) && (int)$_FILES['csv_file']['size'] > 2097152) {
            $this->session->set_flashdata('error', 'File too large (max 2 MB).');
            redirect('training-assessment/import');
            return;
        }
        $parsed = $this->ta_run->parse_assessment_csv($_FILES['csv_file']['tmp_name']);
        if (!empty($parsed['fatal'])) {
            $this->session->set_flashdata('error', $parsed['fatal']);
            redirect('training-assessment/import');
            return;
        }
        $result = $this->ta_run->import_assessment_rows($parsed['rows'], (int) $this->session->userdata('user_id'));
        if (!empty($result['errors'])) {
            $this->session->set_flashdata('error', implode(' ', $result['errors']));
            redirect('training-assessment/import');
            return;
        }
        $this->session->set_flashdata('success', 'Imported assessment #' . (int)$result['assessment_id'] . ' with ' . (int)$result['question_count'] . ' question(s).');
        redirect('training-assessment/questions/' . (int)$result['assessment_id']);
    }

    public function question_list($assessment_id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $a = $this->ta->get_assessment((int)$assessment_id);
        if (!$a) {
            show_404();
        }
        $data['assessment'] = $a;
        $data['questions'] = $this->ta->list_questions((int)$assessment_id);
        $data['ta_can_question_import'] = $this->_ta_can_import_questions();
        $this->load->view('training_assessment/question_list', $data);
    }

    public function add_question($assessment_id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $a = $this->ta->get_assessment((int)$assessment_id);
        if (!$a) {
            show_404();
        }
        $data['assessment'] = $a;
        $data['question'] = null;
        $data['options'] = array();
        $this->load->view('training_assessment/add_question', $data);
    }

    public function edit_question($question_id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $q = $this->ta->get_question((int)$question_id);
        if (!$q) {
            show_404();
        }
        $a = $this->ta->get_assessment((int)$q->assessment_id);
        if (!$a) {
            show_404();
        }
        $data['assessment'] = $a;
        $data['question'] = $q;
        $data['options'] = $this->ta->get_options((int)$question_id);
        $this->load->view('training_assessment/add_question', $data);
    }

    public function save_question()
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $assessment_id = (int)$this->input->post('assessment_id');
        $qid = (int)$this->input->post('question_id');
        $type = $this->input->post('question_type');
        if (!in_array($type, array('mcq', 'text', 'coding'), true)) {
            $type = 'mcq';
        }
        $text = trim((string)$this->input->post('question_text'));
        if ($text === '') {
            $this->session->set_flashdata('error', 'Question text is required.');
            redirect('training-assessment/questions/' . $assessment_id);
            return;
        }
        $points = max(0.01, (float)$this->input->post('points'));
        $now = date('Y-m-d H:i:s');
        $base = array(
            'assessment_id' => $assessment_id,
            'question_type' => $type,
            'question_text' => $text,
            'points' => $points,
            'coding_language' => null,
            'model_answer' => null,
            'text_keyword_pass_percent' => 50.00,
            'coding_expected_output' => null,
            'updated_at' => $now,
        );
        if ($type === 'text') {
            $base['model_answer'] = trim((string)$this->input->post('model_answer'));
            $base['text_keyword_pass_percent'] = min(100, max(1, (float)$this->input->post('text_keyword_pass_percent')));
        }
        if ($type === 'coding') {
            $lang = strtolower(trim((string)$this->input->post('coding_language')));
            $base['coding_language'] = ($lang === 'js') ? 'js' : 'php';
            $base['coding_expected_output'] = trim((string)$this->input->post('coding_expected_output'));
        }
        if ($qid) {
            $this->ta->update_question($qid, $base);
            $newQid = $qid;
        } else {
            $base['sort_order'] = (int)$this->input->post('sort_order');
            if ($base['sort_order'] < 0) {
                $base['sort_order'] = 0;
            }
            $base['created_at'] = $now;
            $newQid = $this->ta->insert_question($base);
        }
        if ($type === 'mcq') {
            $opts = $this->input->post('option_text');
            $correctRaw = $this->input->post('correct_indexes');
            $correctMap = array();
            if (is_array($correctRaw)) {
                foreach ($correctRaw as $ci) {
                    $correctMap[(int)$ci] = true;
                }
            }
            $rows = array();
            if (is_array($opts)) {
                $i = 0;
                foreach ($opts as $t) {
                    $t = trim((string)$t);
                    if ($t === '') {
                        $i++;
                        continue;
                    }
                    $rows[] = array(
                        'question_id' => $newQid,
                        'option_text' => $t,
                        'is_correct' => isset($correctMap[$i]) ? 1 : 0,
                        'sort_order' => $i,
                        'created_at' => $now,
                    );
                    $i++;
                }
            }
            if (count($rows) < 2) {
                $this->session->set_flashdata('error', 'MCQ needs at least two options.');
                redirect('training-assessment/question/add/' . $assessment_id);
                return;
            }
            $hasCorrect = false;
            foreach ($rows as $rr) {
                if ((int)$rr['is_correct'] === 1) {
                    $hasCorrect = true;
                    break;
                }
            }
            if (!$hasCorrect) {
                $this->session->set_flashdata('error', 'Please mark at least one correct option for MCQ.');
                redirect('training-assessment/question/add/' . $assessment_id);
                return;
            }
            $this->ta->replace_options($newQid, $rows);
        }
        $this->session->set_flashdata('success', 'Question saved.');
        redirect('training-assessment/questions/' . $assessment_id);
    }

    public function import_questions_sample_csv()
    {
        $this->_ta_require_question_import();
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $filename = 'training_assessment_questions_sample.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('question_type', 'question_text', 'option_1', 'option_2', 'option_3', 'option_4', 'correct_options'));
        fputcsv($out, array('mcq', 'What is PHP?', 'Language', 'Database', 'Server', 'Browser', '1'));
        fputcsv($out, array('mcq', 'Select web technologies', 'HTML', 'CSS', 'MySQL', 'Bootstrap', '1|2|4'));
        fputcsv($out, array('text', 'Explain MVC architecture in short.', '', '', '', '', ''));
        fputcsv($out, array('coding', 'Reverse a string input and print output.', '', '', '', '', ''));
        fclose($out);
        exit;
    }

    public function import_questions_process($assessment_id)
    {
        $this->_ta_require_question_import();
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $assessment_id = (int) $assessment_id;
        $a = $this->ta->get_assessment($assessment_id);
        if (!$a) {
            show_404();
        }
        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Please choose a CSV file.');
            redirect('training-assessment/questions/' . $assessment_id);
            return;
        }

        $handle = @fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $this->session->set_flashdata('error', 'Unable to read CSV file.');
            redirect('training-assessment/questions/' . $assessment_id);
            return;
        }

        $rows = array();
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $has = false;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $has = true;
                    break;
                }
            }
            if ($has) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (empty($rows)) {
            $this->session->set_flashdata('error', 'CSV file is empty.');
            redirect('training-assessment/questions/' . $assessment_id);
            return;
        }

        $header = array_map('trim', $rows[0]);
        $headerMap = array();
        foreach ($header as $idx => $title) {
            $key = strtolower((string) $title);
            // Handle UTF-8 BOM + loose separators from Excel-edited CSV files.
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $key = trim((string) $key);
            $key = str_replace(array('-', ' '), '_', $key);
            $key = preg_replace('/_+/', '_', $key);
            $headerMap[$key] = $idx;
        }

        $requiredAliases = array(
            'question_type' => array('question_type', 'questiontype', 'type'),
            'question_text' => array('question_text', 'question', 'questiontitle', 'question_name'),
        );
        $resolved = array();
        foreach ($requiredAliases as $canonical => $aliases) {
            $resolved[$canonical] = null;
            foreach ($aliases as $alias) {
                if (isset($headerMap[$alias])) {
                    $resolved[$canonical] = $headerMap[$alias];
                    break;
                }
            }
            if ($resolved[$canonical] === null) {
                $this->session->set_flashdata('error', 'Invalid CSV headers. Download sample CSV and try again.');
                redirect('training-assessment/questions/' . $assessment_id);
                return;
            }
        }

        $now = date('Y-m-d H:i:s');
        $imported = 0;
        $skipped = 0;
        $errors = array();
        for ($i = 1; $i < count($rows); $i++) {
            $lineNo = $i + 1;
            $current = $rows[$i];
            $type = isset($current[$resolved['question_type']]) ? strtolower(trim((string) $current[$resolved['question_type']])) : 'mcq';
            if (!in_array($type, array('mcq', 'text', 'coding'), true)) {
                $type = 'mcq';
            }
            $text = isset($current[$resolved['question_text']]) ? trim((string) $current[$resolved['question_text']]) : '';
            if ($text === '') {
                $skipped++;
                $errors[] = 'Line ' . $lineNo . ': question_text is empty';
                continue;
            }
            $points = (isset($headerMap['points']) && isset($current[$headerMap['points']])) ? (float) $current[$headerMap['points']] : 1;
            if ($points <= 0) {
                $points = 1;
            }

            $qRow = array(
                'assessment_id' => $assessment_id,
                'question_type' => $type,
                'question_text' => $text,
                'points' => $points,
                'coding_language' => 'php',
                'model_answer' => null,
                'text_keyword_pass_percent' => 50.00,
                'coding_expected_output' => null,
                'sort_order' => $this->ta->max_sort_order($assessment_id) + 1,
                'created_at' => $now,
                'updated_at' => $now,
            );
            $qid = (int) $this->ta->insert_question($qRow);
            if ($qid < 1) {
                $skipped++;
                $errors[] = 'Line ' . $lineNo . ': failed to create question';
                continue;
            }

            if ($type === 'mcq') {
                $optRows = array();
                $correctRaw = isset($headerMap['correct_options']) && isset($current[$headerMap['correct_options']]) ? trim((string) $current[$headerMap['correct_options']]) : '1';
                $correctParts = preg_split('/[\|,]+/', $correctRaw);
                $correctMap = array();
                if (is_array($correctParts)) {
                    foreach ($correctParts as $cp) {
                        $idx = (int) trim((string) $cp);
                        if ($idx > 0) {
                            $correctMap[$idx - 1] = true;
                        }
                    }
                }
                for ($j = 1; $j <= 6; $j++) {
                    $col = 'option_' . $j;
                    if (!isset($headerMap[$col])) {
                        continue;
                    }
                    $ov = isset($current[$headerMap[$col]]) ? trim((string) $current[$headerMap[$col]]) : '';
                    if ($ov === '') {
                        continue;
                    }
                    $optRows[] = array(
                        'question_id' => $qid,
                        'option_text' => $ov,
                        'is_correct' => isset($correctMap[$j - 1]) ? 1 : 0,
                        'sort_order' => $j - 1,
                        'created_at' => $now,
                    );
                }
                if (count($optRows) >= 2) {
                    $hasCorrect = false;
                    foreach ($optRows as $orow) {
                        if ((int) $orow['is_correct'] === 1) {
                            $hasCorrect = true;
                            break;
                        }
                    }
                    if (!$hasCorrect) {
                        $optRows[0]['is_correct'] = 1;
                    }
                    $this->ta->replace_options($qid, $optRows);
                } else {
                    $this->ta->delete_question($qid);
                    $skipped++;
                    $errors[] = 'Line ' . $lineNo . ': MCQ needs at least two non-empty options';
                    continue;
                }
            }
            $imported++;
        }

        if ($imported < 1) {
            $this->session->set_flashdata('error', 'No valid questions imported from CSV.');
            redirect('training-assessment/questions/' . $assessment_id);
            return;
        }
        $summary = 'Import complete: total ' . max(0, count($rows) - 1) . ', imported ' . $imported . ', skipped ' . $skipped . '.';
        $this->session->set_flashdata('success', $summary);
        if (!empty($errors)) {
            $maxShow = 10;
            $shown = array_slice($errors, 0, $maxShow);
            $more = count($errors) - count($shown);
            $msg = implode(' | ', $shown);
            if ($more > 0) {
                $msg .= ' | +' . $more . ' more row error(s)';
            }
            $this->session->set_flashdata('error', $msg);
        }
        redirect('training-assessment/questions/' . $assessment_id);
    }

    public function import_questions_dashboard_process()
    {
        $this->_ta_require_question_import();
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $assessment_id = (int) $this->input->post('assessment_id');
        if ($assessment_id < 1) {
            $this->session->set_flashdata('error', 'Please select an assessment.');
            redirect('training-assessment');
            return;
        }
        $this->import_questions_process($assessment_id);
    }

    public function delete_question($id)
    {
        $this->_ta_require_screen('training_screen_ta_create');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $q = $this->ta->get_question((int)$id);
        if (!$q) {
            show_404();
        }
        $aid = (int)$q->assessment_id;
        $this->ta->delete_question((int)$id);
        $this->session->set_flashdata('success', 'Question removed.');
        redirect('training-assessment/questions/' . $aid);
    }

    public function assign($assessment_id)
    {
        $this->_ta_require_manage_core();
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $a = $this->ta->get_assessment((int)$assessment_id);
        if (!$a) {
            show_404();
        }
        $questionCount = $this->ta->count_questions_for_assessment((int) $assessment_id);

        if ($this->input->method() === 'post') {
            if ($questionCount < 1) {
                $this->session->set_flashdata('error', 'Add at least one question before assigning this assessment.');
                redirect('training-assessment/questions/' . $assessment_id);
                return;
            }
            $uid = (int)$this->session->userdata('user_id');
            $mode = (string)$this->input->post('assign_mode');

            if ($mode === 'bulk_department') {
                $dept = trim((string)$this->input->post('department'));
                if ($dept === '') {
                    $this->session->set_flashdata('error', 'Select a department.');
                    redirect('training-assessment/assign/' . $assessment_id);
                    return;
                }
                $userIds = $this->ta_run->user_ids_for_department($dept);
                $created = 0;
                $skipped = 0;
                $notifyIds = array();
                foreach ($userIds as $empUserId) {
                    if ($this->ta->assignment_exists_for_employee((int)$assessment_id, $empUserId)) {
                        $skipped++;
                        continue;
                    }
                    $this->ta->insert_assessment_user(array(
                        'assessment_id' => (int)$assessment_id,
                        'user_id' => $empUserId,
                        'candidate_name' => null,
                        'candidate_email' => null,
                        'access_token' => $this->ta_run->generate_token(),
                        'assigned_by' => $uid,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ));
                    $notifyIds[] = $empUserId;
                    $created++;
                }
                if (!empty($notifyIds)) {
                    $this->load->model('Notification_model');
                    $this->Notification_model->create_bulk(
                        $notifyIds,
                        'Assessment assigned: ' . $a->title,
                        'You have a new assessment. Open My assignments to start.',
                        'info',
                        'training_assessment',
                        (int) $assessment_id,
                        site_url('training-assessment/my-assignments')
                    );
                }
                $this->load->model('Security_audit_model', 'audit');
                $this->audit->log('training_assessment_assign', $uid, 'bulk_department assessment_id=' . (int) $assessment_id . ' department=' . $dept . ' created=' . $created);
                $this->session->set_flashdata('success', 'Bulk assign: ' . $created . ' new assignment(s). Skipped ' . $skipped . ' duplicate(s). Invitation emails are not sent in bulk — copy links from the table below.');
                redirect('training-assessment/assign/' . $assessment_id);
                return;
            }

            if ($mode === 'bulk_csv') {
                $raw = trim((string)$this->input->post('bulk_emails'));
                $lines = preg_split('/\r\n|\r|\n/', $raw);
                $created = 0;
                $skipped = 0;
                $invalid = 0;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $parsed = $this->ta_run->parse_bulk_candidate_line($line);
                    if (!$parsed) {
                        $invalid++;
                        continue;
                    }
                    $emailNorm = strtolower($parsed['email']);
                    if ($this->ta->assignment_exists_for_candidate_email((int)$assessment_id, $emailNorm)) {
                        $skipped++;
                        continue;
                    }
                    $this->ta->insert_assessment_user(array(
                        'assessment_id' => (int)$assessment_id,
                        'user_id' => null,
                        'candidate_name' => $parsed['name'],
                        'candidate_email' => $parsed['email'],
                        'access_token' => $this->ta_run->generate_token(),
                        'assigned_by' => $uid,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ));
                    $created++;
                }
                $msg = 'Bulk candidates: ' . $created . ' created.';
                if ($skipped) {
                    $msg .= ' Skipped ' . $skipped . ' duplicate email(s).';
                }
                if ($invalid) {
                    $msg .= ' ' . $invalid . ' line(s) could not be parsed.';
                }
                $msg .= ' Emails are not sent automatically in bulk — share links from the list.';
                $this->load->model('Security_audit_model', 'audit');
                $this->audit->log('training_assessment_assign', $uid, 'bulk_csv assessment_id=' . (int) $assessment_id . ' created=' . $created);
                $this->session->set_flashdata('success', $msg);
                redirect('training-assessment/assign/' . $assessment_id);
                return;
            }

            $token = $this->ta_run->generate_token();
            $recipientEmail = '';
            $recipientName = '';
            $empUserId = 0;
            if ($mode === 'employee') {
                $empUserId = (int)$this->input->post('user_id');
                if ($empUserId < 1) {
                    $this->session->set_flashdata('error', 'Select an employee.');
                    redirect('training-assessment/assign/' . $assessment_id);
                    return;
                }
                if ($this->ta->assignment_exists_for_employee((int)$assessment_id, $empUserId)) {
                    $this->session->set_flashdata('error', 'This employee is already assigned to this assessment. Remove the old assignment from the database or use the existing link from the table below.');
                    redirect('training-assessment/assign/' . $assessment_id);
                    return;
                }
                $this->ta->insert_assessment_user(array(
                    'assessment_id' => (int)$assessment_id,
                    'user_id' => $empUserId,
                    'candidate_name' => null,
                    'candidate_email' => null,
                    'access_token' => $token,
                    'assigned_by' => $uid,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ));
                $ur = $this->db->select('email, name')->from('users')->where('id', $empUserId)->limit(1)->get()->row();
                if ($ur) {
                    $recipientEmail = trim((string)$ur->email);
                    $recipientName = trim((string)$ur->name) !== '' ? trim((string)$ur->name) : 'Employee';
                }
            } else {
                $name = trim((string)$this->input->post('candidate_name'));
                $email = trim((string)$this->input->post('candidate_email'));
                if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->session->set_flashdata('error', 'Valid candidate name and email are required.');
                    redirect('training-assessment/assign/' . $assessment_id);
                    return;
                }
                if ($this->ta->assignment_exists_for_candidate_email((int)$assessment_id, strtolower($email))) {
                    $this->session->set_flashdata('error', 'A candidate with this email is already assigned. Use the link in the table below or use a different email.');
                    redirect('training-assessment/assign/' . $assessment_id);
                    return;
                }
                $this->ta->insert_assessment_user(array(
                    'assessment_id' => (int)$assessment_id,
                    'user_id' => null,
                    'candidate_name' => $name,
                    'candidate_email' => $email,
                    'access_token' => $token,
                    'assigned_by' => $uid,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ));
                $recipientEmail = $email;
                $recipientName = $name;
            }
            if ($mode === 'employee') {
                $this->load->model('Notification_model');
                $this->Notification_model->create(
                    (int) $empUserId,
                    'Assessment assigned: ' . $a->title,
                    'You have a new assessment. Open My assignments or use the take link from email.',
                    'info',
                    'training_assessment',
                    (int) $assessment_id,
                    site_url('training-assessment/my-assignments')
                );
            }
            $this->load->model('Security_audit_model', 'audit');
            $this->audit->log(
                'training_assessment_assign',
                $uid,
                $mode . ' assessment_id=' . (int) $assessment_id . ($mode === 'employee' ? ' assignee_user_id=' . (int) $empUserId : '')
            );
            $linkUrl = site_url('training-assessment/take/' . rawurlencode($token));
            $this->session->set_flashdata('ta_assign_link', $linkUrl);
            $this->session->set_flashdata('ta_assign_email_to', $recipientEmail);
            $mailRes = $this->ta_run->send_assessment_invite_email($recipientEmail, $recipientName, $a->title, $linkUrl);
            if (!empty($mailRes['ok'])) {
                $this->session->set_flashdata('ta_assign_email', 'sent');
                $this->session->set_flashdata('success', 'Assignment created and invitation email sent to ' . $recipientEmail . '.');
            } elseif (isset($mailRes['reason']) && $mailRes['reason'] === 'not_configured') {
                $this->session->set_flashdata('ta_assign_email', 'skipped');
                $this->session->set_flashdata('success', 'Assignment created. Email was not sent — configure SMTP under Settings → Email. Use the link below to share manually.');
            } elseif (isset($mailRes['reason']) && $mailRes['reason'] === 'invalid') {
                $this->session->set_flashdata('ta_assign_email', 'no_address');
                $this->session->set_flashdata('success', 'Assignment created. No valid email was found for this employee — share the link below manually.');
            } else {
                $this->session->set_flashdata('ta_assign_email', 'failed');
                $this->session->set_flashdata('success', 'Assignment created, but the email could not be sent. Copy the link below and send it manually.');
            }
            redirect('training-assessment/assign/' . $assessment_id);
            return;
        }

        $this->load->model('User_model');
        $data['assessment'] = $a;
        $data['assign_users'] = $this->User_model->list_for_training_assign_dropdown();
        $data['assignments'] = $this->ta->list_assignments_for_assessment((int)$assessment_id);
        $data['question_count'] = $questionCount;
        $data['departments'] = $this->ta_run->distinct_departments();
        $this->load->view('training_assessment/assign', $data);
    }

    public function report()
    {
        $this->_ta_require_screen('training_screen_ta_report');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $this->load->helper('training');
        $sessUid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        $emp = (int) $this->input->get('employee_user_id');
        if (!$isBroad && $sessUid > 0) {
            $emp = $sessUid;
        }
        $from = $this->input->get('date_from');
        $to = $this->input->get('date_to');
        $assessmentId = (int)$this->input->get('assessment_id');
        $assigneeType = $this->input->get('assignee_type');
        $assigneeType = in_array($assigneeType, array('all', 'employee', 'candidate'), true) ? $assigneeType : 'all';
        if (!$isBroad && $sessUid > 0) {
            $assigneeType = 'employee';
        }
        $this->load->model('Employee_model');
        $data['employees'] = $this->Employee_model->all(10000, 0, '', array());
        $assessmentScopeUid = (!$isBroad && $sessUid > 0) ? $sessUid : 0;
        $data['assessments'] = $this->ta->list_assessments_with_stats('', 'all', 'title_asc', $assessmentScopeUid);
        $data['rows'] = $this->ta->list_assignments_for_report($emp, $from, $to, $assessmentId, $assigneeType);
        $data['report_scope_all'] = $isBroad;
        $data['filter_employee'] = $emp;
        $data['filter_from'] = $from;
        $data['filter_to'] = $to;
        $data['filter_assessment_id'] = $assessmentId;
        $data['filter_assignee_type'] = $assigneeType;
        $this->load->view('training_assessment/report', $data);
    }

    /**
     * GET: same filters as report, CSV download.
     */
    public function report_export()
    {
        $this->_ta_require_screen('training_screen_ta_report');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $this->load->helper('training');
        $sessUid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        $emp = (int) $this->input->get('employee_user_id');
        if (!$isBroad && $sessUid > 0) {
            $emp = $sessUid;
        }
        $from = $this->input->get('date_from');
        $to = $this->input->get('date_to');
        $assessmentId = (int)$this->input->get('assessment_id');
        $assigneeType = $this->input->get('assignee_type');
        $assigneeType = in_array($assigneeType, array('all', 'employee', 'candidate'), true) ? $assigneeType : 'all';
        if (!$isBroad && $sessUid > 0) {
            $assigneeType = 'employee';
        }
        $rows = $this->ta->list_assignments_for_report($emp, $from, $to, $assessmentId, $assigneeType);
        $filename = 'training_assessment_report_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Assessment', 'Assignee type', 'Assignee name', 'Email', 'Score %', 'Pass', 'Submitted', 'Assigned'));
        foreach ($rows as $r) {
            $type = !empty($r->user_id) ? 'employee' : 'candidate';
            $name = !empty($r->user_name) ? $r->user_name : (string)$r->candidate_name;
            $email = !empty($r->user_email) ? $r->user_email : (string)$r->candidate_email;
            fputcsv($out, array(
                (string)$r->assessment_title,
                $type,
                $name,
                $email,
                $r->score_percent !== null ? number_format((float)$r->score_percent, 2) : '',
                $r->passed === null ? 'pending' : ((int)$r->passed === 1 ? 'pass' : 'fail'),
                $r->submitted_at ? (string)$r->submitted_at : '',
                (string)$r->assigned_at,
            ));
        }
        fclose($out);
        exit;
    }

    /**
     * Post-submit assessment listing screen:
     * - admin scope: all submitted attempts
     * - user scope: own submitted attempts only
     */
    public function submissions()
    {
        $this->_ta_require_screen('training_screen_ta_submissions');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $this->load->helper('training');
        $sessUid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        $emp = $isBroad ? 0 : $sessUid;
        $assessmentId = (int) $this->input->get('assessment_id');
        $assigneeType = $isBroad ? 'all' : 'employee';
        $rows = $this->ta->list_assignments_for_report($emp, '', '', $assessmentId, $assigneeType);
        $submitted = array();
        foreach ($rows as $r) {
            if (!empty($r->submitted_at) || !empty($r->completed_at)) {
                $submitted[] = $r;
            }
        }
        $data = array(
            'rows' => $submitted,
            'show_all_submissions' => $isBroad,
            'can_review_submissions' => $isBroad,
        );
        $this->load->view('training_assessment/submissions', $data);
    }

    /**
     * Office feed: Topic (LMS-linked), Question, Possible Answers, Type, Assessment id/title.
     * GET assessment_id (optional, 0 = all).
     */
    public function office_export_questions()
    {
        $this->_ta_require_screen('training_screen_ta_report');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $this->load->helper('training');
        $sessUid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        $scope = ($isBroad || $sessUid < 1) ? 0 : $sessUid;
        $aid = (int) $this->input->get('assessment_id');
        $rows = $this->ta->list_office_question_bank_rows($aid, $scope);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="assessment_question_bank_' . date('Y-m-d_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array(
            'Assessment Id',
            'Assessment Title',
            'LMS Topic (linked)',
            'Question',
            'Possible Answers',
            'Type',
            'Points',
            'Question Sort',
        ));
        foreach ($rows as $r) {
            fputcsv($out, array(
                (string) (int) $r->assessment_id,
                (string) $r->assessment_title,
                isset($r->lms_topic_names) ? (string) $r->lms_topic_names : '',
                (string) $r->question_text,
                isset($r->possible_answers) ? (string) $r->possible_answers : '',
                (string) $r->question_type,
                $r->question_points !== null ? (string) $r->question_points : '',
                isset($r->sort_order) ? (string) (int) $r->sort_order : '',
            ));
        }
        fclose($out);
        exit;
    }

    /**
     * Office feed: one row per question per completed attempt (with overall score and learner answer).
     * GET assessment_id, date_from, date_to (optional; same meaning as report).
     */
    public function office_export_attempt_detail()
    {
        $this->_ta_require_screen('training_screen_ta_report');
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $this->load->helper('training');
        $sessUid = (int) $this->session->userdata('user_id');
        $isBroad = ((int) $this->session->userdata('role_id') === 1) || (function_exists('training_ta_admin_broad') && training_ta_admin_broad());
        $scope = ($isBroad || $sessUid < 1) ? 0 : $sessUid;
        $aid = (int) $this->input->get('assessment_id');
        $from = $this->input->get('date_from');
        $to = $this->input->get('date_to');
        $rows = $this->ta->list_office_attempt_detail_rows($aid, $from, $to, $scope);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="assessment_attempt_detail_' . date('Y-m-d_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array(
            'Assessment Id',
            'Assessment Title',
            'LMS Topic (linked)',
            'Assessment User Id',
            'Submitted By',
            'Submitted By Email',
            'Assessment Submitted At',
            'Overall Score %',
            'Earned Points',
            'Total Points',
            'Question Sort',
            'Question',
            'Question Type',
            'Question Max Points',
            'Question Points Earned',
            'Learner Answer',
        ));
        foreach ($rows as $r) {
            fputcsv($out, array(
                (string) (int) $r->assessment_id,
                (string) $r->assessment_title,
                isset($r->lms_topic_names) ? (string) $r->lms_topic_names : '',
                (string) (int) $r->assessment_user_id,
                (string) $r->submitted_by_name,
                (string) $r->submitted_by_email,
                $r->assessment_submitted_at ? (string) $r->assessment_submitted_at : '',
                $r->overall_score_percent !== null ? (string) $r->overall_score_percent : '',
                isset($r->earned_points) ? (string) $r->earned_points : '',
                isset($r->total_points) ? (string) $r->total_points : '',
                isset($r->question_sort) ? (string) (int) $r->question_sort : '',
                (string) $r->question_text,
                (string) $r->question_type,
                isset($r->question_max_points) ? (string) $r->question_max_points : '',
                isset($r->question_points_earned) ? (string) $r->question_points_earned : '',
                isset($r->learner_answer) ? (string) $r->learner_answer : '',
            ));
        }
        fclose($out);
        exit;
    }



    public function result($assessment_user_id)
    {
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $au = $this->ta->get_assessment_user((int) $assessment_user_id);
        if (!$au) {
            show_404();
        }
        if (!$this->ta_run->can_manage_result($au)) {
            show_error('Access denied.', 403);
        }
        $this->ta_run->render_result($au);
    }

    // my_assignments and team_progress screens have been removed intentionally.

    public function certificate($assessment_user_id)
    {
        if (!$this->ta->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        $au = $this->ta->get_assessment_user((int) $assessment_user_id);
        if (!$au) {
            show_404();
        }
        if (!$this->ta_run->can_manage_result($au)) {
            show_error('Access denied.', 403);
        }
        $result = $this->ta->get_result_by_au((int) $au->id);
        if (!$result || empty($au->completed_at)) {
            show_error('No completed result for this assignment.', 404);
        }
        $data['au'] = $au;
        $data['result'] = $result;
        $data['assessment'] = $this->ta->get_assessment((int) $au->assessment_id);
        $this->load->view('training_assessment/certificate', $data);
    }
}
