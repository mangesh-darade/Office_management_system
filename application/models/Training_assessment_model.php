<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Training & Assessment — data access and scoring helpers.
 * Uses ta_* tables when present, else legacy unprefixed names (backward compatible).
 */
class Training_assessment_model extends CI_Model
{
    /** @var array Logical => physical table name */
    public $t = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->t = $this->_resolve_table_map();
        $this->ensure_schema();
    }

    /**
     * Physical table name for joins / external checks (assessments, questions, …).
     *
     * @param string $logical assessments|questions|question_options|assessment_users|user_answers|results
     * @return string
     */
    public function table($logical)
    {
        return isset($this->t[$logical]) ? $this->t[$logical] : $logical;
    }

    /**
     * @return array
     */
    private function _resolve_table_map()
    {
        $legacy = array(
            'assessments' => 'assessments',
            'questions' => 'questions',
            'question_options' => 'question_options',
            'assessment_users' => 'assessment_users',
            'user_answers' => 'user_answers',
            'results' => 'results',
        );
        $prefixed = array(
            'assessments' => 'ta_assessments',
            'questions' => 'ta_questions',
            'question_options' => 'ta_question_options',
            'assessment_users' => 'ta_assessment_users',
            'user_answers' => 'ta_user_answers',
            'results' => 'ta_results',
        );
        // Prefer legacy when present so empty ta_* tables never shadow real data.
        if ($this->db->table_exists('assessments')) {
            return $legacy;
        }
        if ($this->db->table_exists('ta_assessments')) {
            return $prefixed;
        }
        return $legacy;
    }

    /**
     * Add new columns on existing installs (safe to call repeatedly).
     */
    public function ensure_schema()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $tbl = $this->t['assessments'];
        if (!$this->db->table_exists($tbl)) {
            return;
        }
        $fields = $this->db->list_fields($tbl);
        if (!in_array('show_correct_after_submit', $fields, true)) {
            $this->db->query('ALTER TABLE `' . $tbl . '` ADD `show_correct_after_submit` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'Learner sees correct answers on result\' AFTER `allow_retake`');
        }
        $qTbl = $this->t['questions'];
        if ($this->db->table_exists($qTbl)) {
            $qFields = $this->db->list_fields($qTbl);
            if (!in_array('text_keyword_pass_percent', $qFields, true)) {
                $this->db->query('ALTER TABLE `' . $qTbl . '` ADD `text_keyword_pass_percent` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT \'For text keyword scoring: required match %\' AFTER `model_answer`');
            }
        }
        $uaTbl = $this->t['user_answers'];
        if ($this->db->table_exists($uaTbl)) {
            $uaFields = $this->db->list_fields($uaTbl);
            if (!in_array('selected_option_ids', $uaFields, true)) {
                $this->db->query('ALTER TABLE `' . $uaTbl . '` ADD `selected_option_ids` text NULL COMMENT \'CSV of selected option ids for multi-correct MCQ\' AFTER `selected_option_id`');
            }
        }
        if (!$this->db->table_exists('ta_attempt_screenshots')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `ta_attempt_screenshots` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `assessment_user_id` int(11) NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `capture_path` varchar(255) NOT NULL,
                `captured_at` datetime NOT NULL,
                `created_at` datetime NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ta_assessment_user_id` (`assessment_user_id`),
                KEY `idx_ta_user_id` (`user_id`),
                KEY `idx_ta_captured_at` (`captured_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function schema_ready()
    {
        return $this->db->table_exists($this->t['assessments']);
    }

    public function count_questions_for_assessment($assessment_id)
    {
        return (int) $this->db->where('assessment_id', (int) $assessment_id)->count_all_results($this->t['questions']);
    }

    public function list_assessments()
    {
        $tq = $this->t['questions'];
        $ta = $this->t['assessments'];
        $this->db->select('a.*, (SELECT COUNT(*) FROM `' . $tq . '` q WHERE q.assessment_id = a.id) AS question_count', false);
        $this->db->from($ta . ' a');
        $this->db->order_by('a.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Dashboard list with assignment stats and filters.
     *
     * @param string $search          title/description LIKE
     * @param string $status          all|active|inactive
     * @param string $sort            created_desc|created_asc|title_asc|title_desc|questions_desc
     * @param int        $scope_user_id  Legacy: single user id when $scope_user_ids is null.
     * @param int[]|null $scope_user_ids Non–org-admin: only assessments assigned to these user ids.
     */
    public function list_assessments_with_stats($search = '', $status = 'all', $sort = 'created_desc', $scope_user_id = 0, $scope_user_ids = null)
    {
        $tq = $this->t['questions'];
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $this->db->select('a.*', false);
        $this->db->select('(SELECT COUNT(*) FROM `' . $tq . '` q WHERE q.assessment_id = a.id) AS question_count', false);
        $this->db->select('(SELECT COUNT(*) FROM `' . $tau . '` au WHERE au.assessment_id = a.id) AS assigned_count', false);
        $this->db->select('(SELECT COUNT(*) FROM `' . $tau . '` au WHERE au.assessment_id = a.id AND au.completed_at IS NOT NULL) AS completed_count', false);
        $this->db->from($ta . ' a');
        if ($scope_user_ids === null && (int) $scope_user_id > 0) {
            $scope_user_ids = array((int) $scope_user_id);
        }
        if (is_array($scope_user_ids) && !empty($scope_user_ids)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $scope_user_ids), function ($id) {
                return $id > 0;
            })));
            if (!empty($ids)) {
                $this->db->where(
                    'a.id IN (SELECT au2.assessment_id FROM `' . $tau . '` au2 WHERE au2.user_id IN (' . implode(',', $ids) . '))',
                    null,
                    false
                );
            }
        }
        $search = trim((string)$search);
        if ($search !== '') {
            $this->db->group_start()
                ->like('a.title', $search)
                ->or_like('a.description', $search)
            ->group_end();
        }
        if ($status === 'active') {
            $this->db->where('a.status', 'active');
        } elseif ($status === 'inactive') {
            $this->db->where('a.status', 'inactive');
        }
        switch ($sort) {
            case 'created_asc':
                $this->db->order_by('a.created_at', 'ASC');
                break;
            case 'title_asc':
                $this->db->order_by('a.title', 'ASC');
                break;
            case 'title_desc':
                $this->db->order_by('a.title', 'DESC');
                break;
            case 'questions_desc':
                $this->db->order_by('(SELECT COUNT(*) FROM `' . $tq . '` q WHERE q.assessment_id = a.id)', 'DESC', false);
                break;
            default:
                $this->db->order_by('a.created_at', 'DESC');
        }
        return $this->db->get()->result();
    }

    /**
     * True if the learner has entered something scorable for this question.
     */
    public function is_answer_nonempty($question, $answer)
    {
        if (!$question) {
            return false;
        }
        if (!$answer) {
            return false;
        }
        if ($question->question_type === 'mcq') {
            if ((int)$answer->selected_option_id > 0) {
                return true;
            }
            return trim((string)$answer->selected_option_ids) !== '';
        }
        if ($question->question_type === 'text') {
            return trim((string)$answer->answer_text) !== '';
        }
        return trim((string)$answer->code_submitted) !== '' || trim((string)$answer->execution_output) !== '';
    }

    public function assignment_exists_for_employee($assessment_id, $user_id)
    {
        if ((int)$assessment_id < 1 || (int)$user_id < 1) {
            return false;
        }
        $n = $this->db->where('assessment_id', (int)$assessment_id)
            ->where('user_id', (int)$user_id)
            ->count_all_results($this->t['assessment_users']);
        return $n > 0;
    }

    public function assignment_exists_for_candidate_email($assessment_id, $email)
    {
        $email = strtolower(trim((string)$email));
        if ((int)$assessment_id < 1 || $email === '') {
            return false;
        }
        $n = $this->db->where('assessment_id', (int)$assessment_id)
            ->where('user_id IS NULL', null, false)
            ->where('(candidate_email IS NOT NULL AND LOWER(TRIM(candidate_email)) = ' . $this->db->escape($email) . ')', null, false)
            ->count_all_results($this->t['assessment_users']);
        return $n > 0;
    }

    public function max_sort_order($assessment_id)
    {
        $row = $this->db->select_max('sort_order', 'm')
            ->where('assessment_id', (int)$assessment_id)
            ->get($this->t['questions'])
            ->row();
        return $row && isset($row->m) ? (int)$row->m : 0;
    }

    /**
     * Persist question order (ids must belong to assessment).
     */
    public function reorder_questions($assessment_id, array $question_ids_in_order)
    {
        $assessment_id = (int)$assessment_id;
        $valid = $this->db->select('id')->where('assessment_id', $assessment_id)->get($this->t['questions'])->result();
        $validMap = array();
        foreach ($valid as $r) {
            $validMap[(int)$r->id] = true;
        }
        $ord = 0;
        foreach ($question_ids_in_order as $qid) {
            $qid = (int)$qid;
            if ($qid < 1 || !isset($validMap[$qid])) {
                continue;
            }
            $this->db->where('id', $qid)->where('assessment_id', $assessment_id)
                ->update($this->t['questions'], array('sort_order' => $ord, 'updated_at' => date('Y-m-d H:i:s')));
            $ord++;
        }
        return true;
    }

    /**
     * Deep-copy assessment and all questions/options. Returns new assessment id or 0.
     */
    public function duplicate_assessment($source_id, $created_by)
    {
        $src = $this->get_assessment((int)$source_id);
        if (!$src) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $row = array(
            'title' => trim((string)$src->title) . ' (copy)',
            'description' => $src->description,
            'time_limit_minutes' => (int)$src->time_limit_minutes,
            'passing_marks' => (float)$src->passing_marks,
            'randomize_questions' => (int)$src->randomize_questions,
            'shuffle_options' => (int)$src->shuffle_options,
            'max_attempts' => (int)$src->max_attempts,
            'allow_retake' => (int)$src->allow_retake,
            'show_correct_after_submit' => isset($src->show_correct_after_submit) ? (int)$src->show_correct_after_submit : 0,
            'status' => $src->status === 'inactive' ? 'inactive' : 'active',
            'created_by' => (int)$created_by > 0 ? (int)$created_by : null,
            'created_at' => $now,
            'updated_at' => $now,
        );
        $newAid = $this->insert_assessment($row);
        if (!$newAid) {
            return 0;
        }
        $questions = $this->list_questions((int)$source_id);
        foreach ($questions as $q) {
            $qRow = array(
                'assessment_id' => (int)$newAid,
                'question_type' => $q->question_type,
                'question_text' => $q->question_text,
                'points' => (float)$q->points,
                'coding_language' => $q->coding_language,
                'model_answer' => $q->model_answer,
                'text_keyword_pass_percent' => isset($q->text_keyword_pass_percent) ? (float)$q->text_keyword_pass_percent : 50.00,
                'coding_expected_output' => $q->coding_expected_output,
                'sort_order' => (int)$q->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            );
            $newQid = $this->insert_question($qRow);
            if ($q->question_type === 'mcq' && $newQid) {
                $opts = $this->get_options((int)$q->id);
                $optRows = array();
                foreach ($opts as $o) {
                    $optRows[] = array(
                        'question_id' => (int)$newQid,
                        'option_text' => $o->option_text,
                        'is_correct' => (int)$o->is_correct,
                        'sort_order' => (int)$o->sort_order,
                        'created_at' => $now,
                    );
                }
                if (!empty($optRows)) {
                    $this->replace_options((int)$newQid, $optRows);
                }
            }
        }
        return (int)$newAid;
    }

    /**
     * Copy one question (and MCQ options) to the same assessment. Returns new question id or 0.
     */
    public function duplicate_question($question_id)
    {
        $q = $this->get_question((int)$question_id);
        if (!$q) {
            return 0;
        }
        $aid = (int)$q->assessment_id;
        $next = $this->max_sort_order($aid) + 1;
        $now = date('Y-m-d H:i:s');
        $qRow = array(
            'assessment_id' => $aid,
            'question_type' => $q->question_type,
            'question_text' => $q->question_text . "\n\n(copy)",
            'points' => (float)$q->points,
            'coding_language' => $q->coding_language,
            'model_answer' => $q->model_answer,
            'text_keyword_pass_percent' => isset($q->text_keyword_pass_percent) ? (float)$q->text_keyword_pass_percent : 50.00,
            'coding_expected_output' => $q->coding_expected_output,
            'sort_order' => $next,
            'created_at' => $now,
            'updated_at' => $now,
        );
        $newQid = $this->insert_question($qRow);
        if (!$newQid || $q->question_type !== 'mcq') {
            return (int)$newQid;
        }
        $opts = $this->get_options((int)$q->id);
        $optRows = array();
        foreach ($opts as $o) {
            $optRows[] = array(
                'question_id' => (int)$newQid,
                'option_text' => $o->option_text,
                'is_correct' => (int)$o->is_correct,
                'sort_order' => (int)$o->sort_order,
                'created_at' => $now,
            );
        }
        if (!empty($optRows)) {
            $this->replace_options((int)$newQid, $optRows);
        }
        return (int)$newQid;
    }

    public function get_assessment($id)
    {
        return $this->db->where('id', (int)$id)->get($this->t['assessments'])->row();
    }

    public function insert_assessment($data)
    {
        $this->db->insert($this->t['assessments'], $data);
        return $this->db->insert_id();
    }

    public function update_assessment($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->t['assessments'], $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_assessment($id)
    {
        $this->db->where('id', (int)$id)->delete($this->t['assessments']);
        return $this->db->affected_rows() > 0;
    }

    public function list_questions($assessment_id)
    {
        $this->db->where('assessment_id', (int)$assessment_id);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        return $this->db->get($this->t['questions'])->result();
    }

    public function get_question($id)
    {
        return $this->db->where('id', (int)$id)->get($this->t['questions'])->row();
    }

    public function get_options($question_id)
    {
        $this->db->where('question_id', (int)$question_id);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        return $this->db->get($this->t['question_options'])->result();
    }

    public function insert_question($data)
    {
        $this->db->insert($this->t['questions'], $data);
        return $this->db->insert_id();
    }

    public function update_question($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->t['questions'], $data);
        return true;
    }

    public function delete_question($id)
    {
        $this->db->where('id', (int)$id)->delete($this->t['questions']);
        return $this->db->affected_rows() > 0;
    }

    public function replace_options($question_id, $rows)
    {
        $this->db->where('question_id', (int)$question_id)->delete($this->t['question_options']);
        foreach ($rows as $r) {
            $this->db->insert($this->t['question_options'], $r);
        }
        return true;
    }

    public function get_assessment_user_by_token($token)
    {
        $token = trim((string)$token);
        if ($token === '') {
            return null;
        }
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $this->db->select('au.*, a.title AS assessment_title, a.time_limit_minutes, a.passing_marks, a.randomize_questions, a.shuffle_options, a.max_attempts, a.allow_retake, a.status AS assessment_status, ' . $this->_sql_assignee_display_name() . ' AS assignee_user_name', false);
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        $this->db->where('au.access_token', $token);
        return $this->db->get()->row();
    }

    public function get_assessment_user($id)
    {
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $this->db->select('au.*, a.title AS assessment_title, a.time_limit_minutes, a.passing_marks, a.randomize_questions, a.shuffle_options, a.max_attempts, a.allow_retake, a.status AS assessment_status, ' . $this->_sql_assignee_display_name() . ' AS assignee_user_name', false);
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        $this->db->where('au.id', (int)$id);
        return $this->db->get()->row();
    }

    public function insert_assessment_user($data)
    {
        $this->db->insert($this->t['assessment_users'], $data);
        return $this->db->insert_id();
    }

    /**
     * Get existing or create new assignment (token) for an employee.
     */
    public function ensure_user_assignment($assessment_id, $user_id, $assigned_by = null)
    {
        $existing = $this->get_user_assignment_for_assessment($assessment_id, $user_id);
        if ($existing) {
            return $existing;
        }
        $token = bin2hex(openssl_random_pseudo_bytes(8)) . '-' . uniqid();
        $now = date('Y-m-d H:i:s');
        $id = $this->insert_assessment_user(array(
            'assessment_id' => (int) $assessment_id,
            'user_id' => (int) $user_id,
            'access_token' => $token,
            'assigned_by' => $assigned_by ? (int)$assigned_by : null,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        return $this->get_assessment_user($id);
    }

    public function update_assessment_user($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->t['assessment_users'], $data);
        return true;
    }

    public function list_assignments_for_user($user_id)
    {
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $this->db->select('au.*, a.title, a.status AS assessment_status');
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->where('au.user_id', (int)$user_id);
        $this->db->order_by('au.assigned_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Recent assignments for one assessment (admin assign page — links + recipients).
     */
    public function list_assignments_for_assessment($assessment_id, $limit = 50)
    {
        $this->db->select('au.*, u.email AS user_email, ' . $this->_sql_assignee_display_name() . ' AS user_name', false);
        $this->db->from($this->t['assessment_users'] . ' au');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        $this->db->where('au.assessment_id', (int)$assessment_id);
        $this->db->order_by('au.assigned_at', 'DESC');
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    /**
     * Office feed: question bank with LMS topic names (when training_topics links assessment_id).
     *
     * @param int $assessment_id 0 = all
     * @param int $scope_user_id If &gt; 0, only questions for assessments assigned to this user (assessment_users.user_id).
     * @return array
     */
    public function list_office_question_bank_rows($assessment_id = 0, $scope_user_id = 0)
    {
        if (!$this->schema_ready()) {
            return array();
        }
        $hasTopics = $this->db->table_exists('training_topics');
        if ($hasTopics) {
            $topicExpr = "(SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ' | ') FROM training_topics t WHERE t.assessment_id = q.assessment_id)";
        } else {
            $topicExpr = "''";
        }
        $tq = $this->t['questions'];
        $ta = $this->t['assessments'];
        $tau = $this->t['assessment_users'];
        $this->db->select('q.id AS question_id, q.assessment_id, a.title AS assessment_title, ' . $topicExpr . ' AS lms_topic_names, q.question_text, q.question_type, q.points AS question_points, q.sort_order', false);
        $this->db->from($tq . ' q');
        $this->db->join($ta . ' a', 'a.id = q.assessment_id', 'inner');
        $scope_user_id = (int) $scope_user_id;
        if ($scope_user_id > 0) {
            // User scope: only question bank rows for assessments assigned to this user.
            $this->db->where('a.id IN (SELECT au3.assessment_id FROM `' . $tau . '` au3 WHERE au3.user_id = ' . $scope_user_id . ')', null, false);
        }
        if ((int) $assessment_id > 0) {
            $this->db->where('q.assessment_id', (int) $assessment_id);
        }
        $this->db->order_by('q.assessment_id', 'ASC');
        $this->db->order_by('q.sort_order', 'ASC');
        $this->db->order_by('q.id', 'ASC');
        $rows = $this->db->get()->result();
        foreach ($rows as $r) {
            $r->possible_answers = $this->concat_question_options_for_export((int) $r->question_id);
        }
        return $rows;
    }

    /**
     * Office feed: one row per question per completed attempt (has results row).
     *
     * @param int    $assessment_id 0 = all
     * @param string $date_from     Y-m-d or empty
     * @param string $date_to       Y-m-d or empty
     * @param int    $scope_user_id If &gt; 0, only rows for this employee user_id (employee attempts).
     * @return array
     */
    public function list_office_attempt_detail_rows($assessment_id = 0, $date_from = '', $date_to = '', $scope_user_id = 0)
    {
        if (!$this->schema_ready()) {
            return array();
        }
        $hasTopics = $this->db->table_exists('training_topics');
        if ($hasTopics) {
            $topicExpr = "(SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ' | ') FROM training_topics t WHERE t.assessment_id = a.id)";
        } else {
            $topicExpr = "''";
        }
        $this->db->select('a.id AS assessment_id, a.title AS assessment_title, ' . $topicExpr . ' AS lms_topic_names, au.id AS assessment_user_id', false);
        $this->db->select('COALESCE(u.name, au.candidate_name) AS submitted_by_name, COALESCE(u.email, au.candidate_email) AS submitted_by_email', false);
        $this->db->select('r.submitted_at AS assessment_submitted_at, r.score_percent AS overall_score_percent, r.earned_points, r.total_points', false);
        $this->db->select('q.id AS question_id, q.sort_order AS question_sort, q.question_text, q.question_type, q.points AS question_max_points', false);
        $this->db->select('ua.points_earned AS question_points_earned, ua.selected_option_id, ua.selected_option_ids, ua.answer_text, ua.code_submitted, ua.execution_output', false);
        $tua = $this->t['user_answers'];
        $tq = $this->t['questions'];
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $tr = $this->t['results'];
        $this->db->from($tua . ' ua');
        $this->db->join($tq . ' q', 'q.id = ua.question_id', 'inner');
        $this->db->join($tau . ' au', 'au.id = ua.assessment_user_id', 'inner');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join($tr . ' r', 'r.assessment_user_id = au.id', 'inner');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        if ((int) $assessment_id > 0) {
            $this->db->where('a.id', (int) $assessment_id);
        }
        $scope_user_id = (int) $scope_user_id;
        if ($scope_user_id > 0) {
            $this->db->where('au.user_id', $scope_user_id);
        }
        $date_from = trim((string) $date_from);
        $date_to = trim((string) $date_to);
        if ($date_from !== '') {
            $this->db->where('DATE(r.submitted_at) >=', $date_from);
        }
        if ($date_to !== '') {
            $this->db->where('DATE(r.submitted_at) <=', $date_to);
        }
        $this->db->order_by('r.submitted_at', 'DESC');
        $this->db->order_by('au.id', 'ASC');
        $this->db->order_by('q.sort_order', 'ASC');
        $this->db->order_by('q.id', 'ASC');
        $rows = $this->db->get()->result();
        foreach ($rows as $r) {
            $r->learner_answer = $this->format_answer_for_export(
                (string) $r->question_type,
                (int) $r->selected_option_id,
                isset($r->selected_option_ids) ? $r->selected_option_ids : '',
                isset($r->answer_text) ? $r->answer_text : '',
                isset($r->code_submitted) ? $r->code_submitted : '',
                isset($r->execution_output) ? $r->execution_output : ''
            );
        }
        return $rows;
    }

    /**
     * @param int $question_id
     * @return string
     */
    public function concat_question_options_for_export($question_id)
    {
        $opts = $this->get_options((int) $question_id);
        $parts = array();
        foreach ($opts as $o) {
            $mark = ((int) $o->is_correct === 1) ? '[correct] ' : '';
            $parts[] = $mark . $o->option_text;
        }
        return implode(' | ', $parts);
    }

    /**
     * @param string $question_type
     * @param int    $selected_option_id
     * @param string $selected_option_ids_csv
     * @param string $answer_text
     * @param string $code_submitted
     * @param string $execution_output
     * @return string
     */
    public function format_answer_for_export($question_type, $selected_option_id, $selected_option_ids_csv, $answer_text, $code_submitted, $execution_output)
    {
        if ($question_type === 'mcq') {
            $ids = $this->parse_option_ids($selected_option_ids_csv);
            if (empty($ids) && (int)$selected_option_id > 0) {
                $ids = array((int)$selected_option_id);
            }
            if (empty($ids)) {
                return '';
            }
            $labels = array();
            foreach ($ids as $sid) {
                $opt = $this->db->where('id', (int) $sid)->get($this->t['question_options'])->row();
                if ($opt) {
                    $labels[] = (string) $opt->option_text;
                }
            }
            return implode(' | ', $labels);
        }
        if ($question_type === 'text') {
            return (string) $answer_text;
        }
        $code = trim((string) $code_submitted);
        $out = trim((string) $execution_output);
        if ($code === '' && $out === '') {
            return '';
        }
        if ($out !== '') {
            return $code . ( $code !== '' ? "\n--- output ---\n" : '' ) . $out;
        }
        return $code;
    }

    /**
     * @param string|array|null $csv
     * @return int[]
     */
    public function parse_option_ids($csv)
    {
        if (is_array($csv)) {
            $parts = $csv;
        } else {
            $s = trim((string)$csv);
            if ($s === '') {
                return array();
            }
            $parts = explode(',', $s);
        }
        $out = array();
        foreach ($parts as $p) {
            $id = (int)trim((string)$p);
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    /**
     * SQL expression for learner display name (alias au, u). Prefer users.name,
     * then employees first+last name, then email — matches how HR records names.
     *
     * @return string
     */
    private function _sql_assignee_display_name()
    {
        if ($this->db->table_exists('employees')) {
            return "COALESCE(NULLIF(TRIM(u.name), ''), NULLIF((SELECT TRIM(CONCAT(COALESCE(e2.first_name,''), ' ', COALESCE(e2.last_name,''))) FROM employees e2 WHERE e2.user_id = au.user_id LIMIT 1), ''), u.email)";
        }
        return "COALESCE(NULLIF(TRIM(u.name), ''), u.email)";
    }

    /**
     * @param int    $employee_user_id 0 = all employees (ignored when assignee_type is candidate)
     * @param string $assignee_type     all|employee|candidate
     */
    public function list_assignments_for_report($employee_user_id, $date_from, $date_to, $assessment_id = 0, $assignee_type = 'all', $scope_user_ids = null)
    {
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $tr = $this->t['results'];
        $this->db->select('au.*, a.title AS assessment_title, r.score_percent, r.passed, r.submitted_at, ' . $this->_sql_assignee_display_name() . ' AS user_name, u.email AS user_email, au.candidate_name, au.candidate_email', false);
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join($tr . ' r', 'r.assessment_user_id = au.id', 'left');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        if ((int)$assessment_id > 0) {
            $this->db->where('au.assessment_id', (int)$assessment_id);
        }
        $assignee_type = strtolower(trim((string)$assignee_type));
        if ($assignee_type === 'employee') {
            $this->db->where('au.user_id IS NOT NULL', null, false);
            if (is_array($scope_user_ids) && !empty($scope_user_ids)) {
                $this->db->where_in('au.user_id', array_map('intval', $scope_user_ids));
            } elseif ($employee_user_id) {
                $this->db->where('au.user_id', (int)$employee_user_id);
            }
        } elseif ($assignee_type === 'candidate') {
            $this->db->where('au.user_id IS NULL', null, false);
        } elseif (is_array($scope_user_ids) && !empty($scope_user_ids)) {
            $this->db->where_in('au.user_id', array_map('intval', $scope_user_ids));
        } elseif ($employee_user_id) {
            $this->db->where('au.user_id', (int)$employee_user_id);
        }
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(COALESCE(r.submitted_at, au.assigned_at)) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(COALESCE(r.submitted_at, au.assigned_at)) <=', $date_to);
        }
        $this->db->order_by('r.submitted_at', 'DESC');
        $this->db->order_by('au.assigned_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Manager-style view: assignments for many employee user ids (e.g. same department).
     *
     * @param int[] $user_ids
     * @param int   $assessment_id 0 = all
     * @return array
     */
    public function list_assignments_for_user_ids(array $user_ids, $assessment_id = 0)
    {
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));
        if (empty($user_ids)) {
            return array();
        }
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $tr = $this->t['results'];
        $this->db->select('au.*, a.title AS assessment_title, r.score_percent, r.passed, r.submitted_at, ' . $this->_sql_assignee_display_name() . ' AS user_name, u.email AS user_email', false);
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join($tr . ' r', 'r.assessment_user_id = au.id', 'left');
        $this->db->join('users u', 'u.id = au.user_id', 'left');
        $this->db->where_in('au.user_id', $user_ids);
        if ((int) $assessment_id > 0) {
            $this->db->where('au.assessment_id', (int) $assessment_id);
        }
        $this->db->order_by('u.name', 'ASC');
        $this->db->order_by('r.submitted_at', 'DESC');
        $this->db->order_by('au.assigned_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * My assignments with latest result summary for the employee.
     */
    public function list_assignments_for_user_detailed($user_id)
    {
        $tau = $this->t['assessment_users'];
        $ta = $this->t['assessments'];
        $tr = $this->t['results'];
        $this->db->select('au.*, a.title, a.status AS assessment_status, a.max_attempts, a.allow_retake, r.score_percent, r.passed, r.submitted_at AS result_submitted_at');
        $this->db->from($tau . ' au');
        $this->db->join($ta . ' a', 'a.id = au.assessment_id', 'inner');
        $this->db->join($tr . ' r', 'r.assessment_user_id = au.id', 'left');
        $this->db->where('au.user_id', (int)$user_id);
        $this->db->order_by('au.assigned_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Build CSV of question ids (randomized if assessment flag set).
     */
    public function build_question_order($assessment_id, $randomize)
    {
        $qs = $this->list_questions($assessment_id);
        $ids = array();
        foreach ($qs as $q) {
            $ids[] = (int)$q->id;
        }
        if ($randomize && count($ids) > 1) {
            shuffle($ids);
        }
        return implode(',', $ids);
    }

    public function parse_order_ids($csv)
    {
        if ($csv === null || $csv === '') {
            return array();
        }
        $parts = explode(',', $csv);
        $out = array();
        foreach ($parts as $p) {
            $p = (int)trim($p);
            if ($p > 0) {
                $out[] = $p;
            }
        }
        return $out;
    }

    public function start_attempt_if_needed($au)
    {
        if (!$au) {
            return false;
        }
        if ($au->started_at) {
            return true;
        }
        $now = date('Y-m-d H:i:s');
        $mins = (int)$au->time_limit_minutes;
        if ($mins < 1) {
            $mins = 1;
        }
        $ends = date('Y-m-d H:i:s', strtotime('+' . $mins . ' minutes'));
        $order = $this->build_question_order((int)$au->assessment_id, (int)$au->randomize_questions === 1);
        $this->update_assessment_user((int)$au->id, array(
            'started_at' => $now,
            'server_ends_at' => $ends,
            'question_order' => $order,
            'updated_at' => $now,
        ));
        return true;
    }

    public function get_answer($assessment_user_id, $question_id)
    {
        return $this->db->where('assessment_user_id', (int)$assessment_user_id)
            ->where('question_id', (int)$question_id)
            ->get($this->t['user_answers'])->row();
    }

    public function upsert_answer($assessment_user_id, $question_id, $fields)
    {
        $existing = $this->get_answer($assessment_user_id, $question_id);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $fields['updated_at'] = $now;
            $this->db->where('id', (int)$existing->id)->update($this->t['user_answers'], $fields);
            return (int)$existing->id;
        }
        $fields['assessment_user_id'] = (int)$assessment_user_id;
        $fields['question_id'] = (int)$question_id;
        $fields['created_at'] = $now;
        $this->db->insert($this->t['user_answers'], $fields);
        return (int)$this->db->insert_id();
    }

    public function delete_answers_for_user($assessment_user_id)
    {
        $this->db->where('assessment_user_id', (int)$assessment_user_id)->delete($this->t['user_answers']);
    }

    public function delete_result_for_user($assessment_user_id)
    {
        $this->db->where('assessment_user_id', (int)$assessment_user_id)->delete($this->t['results']);
    }

    public function get_result_by_au($assessment_user_id)
    {
        return $this->db->where('assessment_user_id', (int)$assessment_user_id)->get($this->t['results'])->row();
    }

    public function insert_attempt_screenshot($data)
    {
        $this->db->insert('ta_attempt_screenshots', $data);
        return (int) $this->db->insert_id();
    }

    public function list_attempt_screenshots($assessment_user_id)
    {
        return $this->db->from('ta_attempt_screenshots')
            ->where('assessment_user_id', (int) $assessment_user_id)
            ->order_by('captured_at', 'DESC')
            ->order_by('id', 'DESC')
            ->get()
            ->result();
    }

    public function get_attempt_screenshot($id)
    {
        return $this->db->from('ta_attempt_screenshots')
            ->where('id', (int) $id)
            ->get()
            ->row();
    }

    public function delete_attempt_screenshot($id)
    {
        $this->db->where('id', (int) $id)->delete('ta_attempt_screenshots');
        return $this->db->affected_rows() > 0;
    }

    public function list_attempt_screenshots_by_ids($assessment_user_id, array $ids)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });
        if (empty($ids)) {
            return array();
        }
        return $this->db->from('ta_attempt_screenshots')
            ->where('assessment_user_id', (int) $assessment_user_id)
            ->where_in('id', $ids)
            ->get()
            ->result();
    }

    /**
     * Grade all answers and insert/update results row.
     */
    public function finalize_result($assessment_user_id)
    {
        $au = $this->get_assessment_user($assessment_user_id);
        if (!$au) {
            return null;
        }
        $questions = $this->list_questions((int)$au->assessment_id);
        $total = 0.0;
        foreach ($questions as $q) {
            $total += (float)$q->points;
        }
        if ($total <= 0) {
            $total = 1.0;
        }

        $earned = 0.0;
        foreach ($questions as $q) {
            $ans = $this->get_answer($assessment_user_id, (int)$q->id);
            $pts = $this->grade_question($q, $ans);
            $earned += $pts;
            if ($ans) {
                $maxPts = (float) $q->points;
                $this->db->where('id', (int)$ans->id)->update($this->t['user_answers'], array(
                    'points_earned' => $pts,
                    'is_graded_correct' => ($pts >= $maxPts) ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
            }
        }

        $percent = round(($earned / $total) * 100, 2);
        $passing = (float)$au->passing_marks;
        $passed = ($percent >= $passing) ? 1 : 0;

        $duration = null;
        if ($au->started_at) {
            $duration = max(0, time() - strtotime($au->started_at));
        }

        $submitted = date('Y-m-d H:i:s');
        $payload = array(
            'assessment_user_id' => (int)$assessment_user_id,
            'score_percent' => $percent,
            'total_points' => $total,
            'earned_points' => $earned,
            'passed' => $passed,
            'duration_seconds' => $duration,
            'submitted_at' => $submitted,
            'created_at' => $submitted,
        );

        $existing = $this->get_result_by_au($assessment_user_id);
        if ($existing) {
            unset($payload['created_at']);
            $this->db->where('id', (int)$existing->id)->update($this->t['results'], $payload);
        } else {
            $this->db->insert($this->t['results'], $payload);
        }

        $attempts = (int)$au->attempts_used + 1;
        $this->update_assessment_user((int)$au->id, array(
            'completed_at' => $submitted,
            'attempts_used' => $attempts,
            'updated_at' => $submitted,
        ));

        return $this->get_result_by_au($assessment_user_id);
    }

    /**
     * Points earned for one question.
     */
    public function grade_question($question, $answer)
    {
        $max = (float)$question->points;
        if (!$answer) {
            return 0.0;
        }
        if ($question->question_type === 'mcq') {
            $opts = $this->get_options((int)$question->id);
            $correct = array();
            foreach ($opts as $o) {
                if ((int)$o->is_correct === 1) {
                    $correct[(int)$o->id] = true;
                }
            }
            if (empty($correct)) {
                return 0.0;
            }
            $selected = $this->parse_option_ids(isset($answer->selected_option_ids) ? $answer->selected_option_ids : '');
            if (empty($selected) && (int)$answer->selected_option_id > 0) {
                $selected = array((int)$answer->selected_option_id);
            }
            if (empty($selected)) {
                return 0.0;
            }
            $selectedMap = array();
            foreach ($selected as $sid) {
                $selectedMap[(int)$sid] = true;
            }
            if (count($selectedMap) !== count($correct)) {
                return 0.0;
            }
            foreach ($correct as $cid => $_) {
                if (!isset($selectedMap[(int)$cid])) {
                    return 0.0;
                }
            }
            return $max;
        }
        if ($question->question_type === 'text') {
            $text = trim((string)$answer->answer_text);
            if ($text === '') {
                return 0.0;
            }
            $model = trim((string)$question->model_answer);
            if ($model === '') {
                // No rubric: award full points for substantive answer
                return (strlen($text) >= 20) ? $max : ($max * 0.5);
            }
            // Keyword mode:
            // If model_answer contains multiple tokens (comma/newline/semicolon/pipe),
            // treat them as required keywords/phrases and mark correct when >= 50% match.
            $keywords = preg_split('/[\r\n,;|]+/', $model);
            $cleanKeywords = array();
            if (is_array($keywords)) {
                foreach ($keywords as $kw) {
                    $kw = trim((string)$kw);
                    if ($kw !== '') {
                        $cleanKeywords[] = $kw;
                    }
                }
            }
            if (count($cleanKeywords) >= 2) {
                $matched = 0;
                $haystack = strtolower($text);
                foreach ($cleanKeywords as $kw) {
                    $needle = strtolower($kw);
                    $isMatch = false;
                    if (strpos($needle, ' ') !== false) {
                        $isMatch = (stripos($haystack, $needle) !== false);
                    } else {
                        $isMatch = (bool) preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $text);
                    }
                    if ($isMatch) {
                        $matched++;
                    }
                }
                $ratio = $matched / count($cleanKeywords);
                $requiredPercent = isset($question->text_keyword_pass_percent) ? (float)$question->text_keyword_pass_percent : 50.0;
                if ($requiredPercent < 1) {
                    $requiredPercent = 1;
                } elseif ($requiredPercent > 100) {
                    $requiredPercent = 100;
                }
                return (($ratio * 100) >= $requiredPercent) ? $max : 0.0;
            }
            $pct = 0;
            similar_text(strtolower($text), strtolower($model), $pct);
            if ($pct >= 40) {
                return $max;
            }
            if ($pct >= 25) {
                return $max * 0.5;
            }
            return 0.0;
        }
        if ($question->question_type === 'coding') {
            $expected = trim((string)$question->coding_expected_output);
            $out = trim((string)$answer->execution_output);
            if ($expected !== '' && $out !== '' && strcasecmp($expected, $out) === 0) {
                return $max;
            }
            if ($expected === '' && trim((string)$answer->code_submitted) !== '') {
                return $max * 0.5;
            }
            return 0.0;
        }
        return 0.0;
    }

    /**
     * @param int         $assessment_user_id
     * @param string|null $question_order_csv optional attempt order (matches take screen)
     */
    public function list_answers_with_questions($assessment_user_id, $question_order_csv = null)
    {
        $tua = $this->t['user_answers'];
        $tq = $this->t['questions'];
        $this->db->select('ua.*, q.question_text, q.question_type, q.points AS question_points, q.model_answer, q.coding_expected_output, q.coding_language');
        $this->db->from($tua . ' ua');
        $this->db->join($tq . ' q', 'q.id = ua.question_id', 'inner');
        $this->db->where('ua.assessment_user_id', (int)$assessment_user_id);
        $ids = $this->parse_order_ids($question_order_csv);
        if (count($ids) > 0) {
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function ($x) {
                return $x > 0;
            });
            if (count($ids) > 0) {
                $this->db->order_by('FIELD(q.id,' . implode(',', $ids) . ')', '', false);
            } else {
                $this->db->order_by('q.sort_order', 'ASC');
            }
        } else {
            $this->db->order_by('q.sort_order', 'ASC');
        }
        return $this->db->get()->result();
    }

    public function is_time_expired($au)
    {
        if (!$au || !$au->server_ends_at) {
            return false;
        }
        return strtotime($au->server_ends_at) < time();
    }

    /**
     * Employee row in assessment_users for LMS topic linkage (if any).
     *
     * @param int $assessment_id
     * @param int $user_id
     * @return object|null
     */
    public function get_user_assignment_for_assessment($assessment_id, $user_id)
    {
        if ((int) $assessment_id < 1 || (int) $user_id < 1) {
            return null;
        }
        return $this->db->where('assessment_id', (int) $assessment_id)
            ->where('user_id', (int) $user_id)
            ->order_by('id', 'DESC')
            ->get($this->t['assessment_users'])
            ->row();
    }
}
