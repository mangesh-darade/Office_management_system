<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified master CSV import — trainings, topics, assignments, assessments, questions.
 * CSV layout matches application/views/training/import.php (row 1 sections, row 2 headers, row 3+ data).
 */
class Training_csv_import
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('Training_assessment_model', 'ta');
        $this->CI->load->model('Training_office_feed_model', 'office_feed');
    }

    /**
     * @param string $type
     * @return bool
     */
    public function type_ready($type)
    {
        $type = trim((string) $type);
        if ($type !== 'all') {
            return false;
        }
        return $this->CI->office_feed->lms_ready()
            && $this->CI->ta->schema_ready()
            && $this->CI->db->table_exists('assignments');
    }

    /**
     * @param string $type
     */
    public function stream_sample($type)
    {
        if (!$this->type_ready($type)) {
            show_404();
        }
        $rows = array(
            array('TRAINING', '', '', 'TOPIC', '', '', '', 'TEST', '', '', '', 'QUESTION', '', '', '', '', '', '', 'ASSIGNMENT', '', '', ''),
            array('Name', 'Description', '', 'Training', 'Name', 'Description', 'Test', 'Name', 'Description', 'Minutes', 'Pass%', 'Test', 'Question', 'A', 'B', 'C', 'D', 'Correct', 'Training', 'Topic', 'Title', 'Description'),
            array('Onboarding', 'New employee program', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            array('', '', '', 'Onboarding', 'Welcome', 'Orientation', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            array('', '', '', '', '', '', '', 'Safety Quiz', 'Safety check', '30', '60', '', '', '', '', '', '', '', '', '', '', ''),
            array('', '', '', '', '', '', '', '', '', '', '', 'Safety Quiz', 'What is PPE?', 'Helmet', 'Shoes', 'Gloves', 'Vest', '1', '', '', '', ''),
            array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Onboarding', 'Welcome', 'Welcome task', 'Complete orientation checklist'),
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="training_master_import_sample.csv"');
        $out = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /**
     * @param string $type
     * @param string $path
     * @param int $user_id
     * @return array{success:bool,message:string,errors:array,counts:array}
     */
    public function process_upload($type, $path, $user_id)
    {
        $out = array(
            'success' => false,
            'message' => '',
            'errors' => array(),
            'counts' => array(
                'training' => 0,
                'topic' => 0,
                'test' => 0,
                'question' => 0,
                'assignment' => 0,
            ),
        );
        if (!$this->type_ready($type)) {
            $out['message'] = 'Database tables not installed.';
            return $out;
        }
        if (!is_readable($path)) {
            $out['message'] = 'Could not read the uploaded file.';
            return $out;
        }
        $fh = fopen($path, 'r');
        if (!$fh) {
            $out['message'] = 'Could not open the uploaded file.';
            return $out;
        }
        $sectionRow = fgetcsv($fh);
        $headerRow = fgetcsv($fh);
        if ($sectionRow === false || $headerRow === false) {
            fclose($fh);
            $out['message'] = 'CSV must include section row and header row.';
            return $out;
        }
        if (isset($sectionRow[0])) {
            $sectionRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $sectionRow[0]);
        }

        $moduleCache = array();
        $topicCache = array();
        $assessmentCache = array();
        $now = date('Y-m-d H:i:s');
        $line = 3;
        $this->CI->db->trans_start();

        while (($row = fgetcsv($fh)) !== false) {
            if ($this->_row_empty($row)) {
                $line++;
                continue;
            }
            $cell = function ($idx) use ($row) {
                return isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            };

            if ($cell(0) !== '') {
                $title = $cell(0);
                $desc = $cell(1);
                $existing = $this->_find_module_id($title, $moduleCache);
                if ($existing) {
                    $this->CI->db->where('id', (int) $existing)->update('training_modules', array(
                        'description' => $desc,
                        'updated_at' => $now,
                    ));
                } else {
                    $this->CI->db->insert('training_modules', array(
                        'title' => $title,
                        'description' => $desc,
                        'status' => 'active',
                        'sort_order' => 0,
                        'created_by' => $user_id > 0 ? $user_id : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                    $moduleCache[strtolower($title)] = (int) $this->CI->db->insert_id();
                }
                $out['counts']['training']++;
            } elseif ($cell(3) !== '' || $cell(4) !== '') {
                $modTitle = $cell(3);
                $topicName = $cell(4);
                $topicDesc = $cell(5);
                $testName = $cell(6);
                if ($modTitle === '' || $topicName === '') {
                    $out['errors'][] = 'Line ' . $line . ': TOPIC requires Training and Name.';
                } else {
                    $moduleId = $this->_find_module_id($modTitle, $moduleCache);
                    if (!$moduleId) {
                        $out['errors'][] = 'Line ' . $line . ': unknown training "' . $modTitle . '".';
                    } else {
                        $assessmentId = null;
                        if ($testName !== '') {
                            $assessmentId = $this->_find_assessment_id($testName, $assessmentCache);
                        }
                        $topicKey = $moduleId . '|' . strtolower($topicName);
                        $topicId = isset($topicCache[$topicKey]) ? $topicCache[$topicKey] : null;
                        if ($topicId) {
                            $this->CI->db->where('id', (int) $topicId)->update('training_topics', array(
                                'description' => $topicDesc,
                                'has_assessment' => $assessmentId ? 1 : 0,
                                'assessment_id' => $assessmentId,
                                'updated_at' => $now,
                            ));
                        } else {
                            $this->CI->db->insert('training_topics', array(
                                'module_id' => (int) $moduleId,
                                'name' => $topicName,
                                'description' => $topicDesc,
                                'duration_hours' => 0,
                                'has_assignment' => 0,
                                'has_assessment' => $assessmentId ? 1 : 0,
                                'assessment_id' => $assessmentId,
                                'sort_order' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ));
                            $topicId = (int) $this->CI->db->insert_id();
                            $topicCache[$topicKey] = $topicId;
                        }
                        $out['counts']['topic']++;
                    }
                }
            } elseif ($cell(7) !== '') {
                $title = $cell(7);
                $desc = $cell(8);
                $minutes = $cell(9) !== '' ? max(1, (int) $cell(9)) : 30;
                $pass = $cell(10) !== '' ? min(100, max(0, (float) $cell(10))) : 60;
                $existingId = $this->_find_assessment_id($title, $assessmentCache);
                if ($existingId) {
                    $this->CI->ta->update_assessment($existingId, array(
                        'description' => $desc,
                        'time_limit_minutes' => $minutes,
                        'passing_marks' => $pass,
                        'updated_at' => $now,
                    ));
                } else {
                    $existingId = $this->CI->ta->insert_assessment(array(
                        'title' => $title,
                        'description' => $desc,
                        'time_limit_minutes' => $minutes,
                        'passing_marks' => $pass,
                        'randomize_questions' => 0,
                        'shuffle_options' => 0,
                        'max_attempts' => 1,
                        'allow_retake' => 0,
                        'show_correct_after_submit' => 0,
                        'status' => 'active',
                        'created_by' => $user_id > 0 ? $user_id : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                    $assessmentCache[strtolower($title)] = (int) $existingId;
                }
                $out['counts']['test']++;
            } elseif ($cell(12) !== '') {
                $testName = $cell(11);
                $qtext = $cell(12);
                if ($testName === '') {
                    $out['errors'][] = 'Line ' . $line . ': QUESTION requires Test name.';
                } else {
                    $assessmentId = $this->_find_assessment_id($testName, $assessmentCache);
                    if (!$assessmentId) {
                        $out['errors'][] = 'Line ' . $line . ': unknown test "' . $testName . '".';
                    } else {
                        $qId = $this->CI->ta->insert_question(array(
                            'assessment_id' => (int) $assessmentId,
                            'question_type' => 'mcq',
                            'question_text' => $qtext,
                            'points' => 1,
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ));
                        $correct = strtoupper($cell(17));
                        $options = array();
                        $labels = array('A' => 13, 'B' => 14, 'C' => 15, 'D' => 16);
                        $sort = 1;
                        foreach ($labels as $label => $idx) {
                            $text = $cell($idx);
                            if ($text === '') {
                                continue;
                            }
                            $isCorrect = 0;
                            if ($correct === (string) $sort || $correct === $label || $correct === (string) ($sort - 1)) {
                                $isCorrect = 1;
                            }
                            $options[] = array(
                                'question_id' => (int) $qId,
                                'option_text' => $text,
                                'is_correct' => $isCorrect,
                                'sort_order' => $sort,
                                'created_at' => $now,
                            );
                            $sort++;
                        }
                        if (!empty($options)) {
                            $this->CI->ta->replace_options((int) $qId, $options);
                        }
                        $out['counts']['question']++;
                    }
                }
            } elseif ($cell(18) !== '' || $cell(20) !== '') {
                $modTitle = $cell(18);
                $topicName = $cell(19);
                $aTitle = $cell(20);
                $aDesc = $cell(21);
                if ($modTitle === '' || $topicName === '') {
                    $out['errors'][] = 'Line ' . $line . ': ASSIGNMENT requires Training and Topic.';
                } else {
                    $topicId = $this->_resolve_topic_id($modTitle, $topicName, $moduleCache, $topicCache);
                    if (!$topicId) {
                        $out['errors'][] = 'Line ' . $line . ': no topic for "' . $modTitle . '" / "' . $topicName . '".';
                    } else {
                        if ($aTitle === '') {
                            $aTitle = $topicName . ' — Assignment';
                        }
                        $this->CI->db->where('id', (int) $topicId)->update('training_topics', array(
                            'has_assignment' => 1,
                            'updated_at' => $now,
                        ));
                        $existing = $this->CI->db->where('topic_id', (int) $topicId)->get('assignments')->row();
                        if ($existing) {
                            $this->CI->db->where('id', (int) $existing->id)->update('assignments', array(
                                'name' => $aTitle,
                                'details' => $aDesc,
                                'updated_at' => $now,
                            ));
                        } else {
                            $this->CI->db->insert('assignments', array(
                                'topic_id' => (int) $topicId,
                                'name' => $aTitle,
                                'details' => $aDesc,
                                'max_submissions' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ));
                        }
                        $out['counts']['assignment']++;
                    }
                }
            }
            $line++;
        }
        fclose($fh);

        $this->CI->db->trans_complete();
        if ($this->CI->db->trans_status() === false) {
            $out['message'] = 'Import failed — database transaction rolled back.';
            return $out;
        }

        $total = array_sum($out['counts']);
        if ($total === 0) {
            $out['message'] = 'No importable rows found (check row 3+).';
            return $out;
        }

        $out['success'] = true;
        $out['message'] = sprintf(
            'Imported: %d training, %d topic, %d test, %d question, %d assignment.',
            $out['counts']['training'],
            $out['counts']['topic'],
            $out['counts']['test'],
            $out['counts']['question'],
            $out['counts']['assignment']
        );
        return $out;
    }

    /**
     * @param array $row
     * @return bool
     */
    private function _row_empty($row)
    {
        if (!is_array($row)) {
            return true;
        }
        foreach ($row as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $title
     * @param array $cache
     * @return int|null
     */
    private function _find_module_id($title, &$cache)
    {
        $key = strtolower(trim($title));
        if ($key === '') {
            return null;
        }
        if (isset($cache[$key])) {
            return (int) $cache[$key];
        }
        $row = $this->CI->db->where('title', $title)->get('training_modules')->row();
        if (!$row) {
            $row = $this->CI->db->where('LOWER(TRIM(title)) = ' . $this->CI->db->escape($key), null, false)->get('training_modules')->row();
        }
        if ($row) {
            $cache[$key] = (int) $row->id;
            return (int) $row->id;
        }
        return null;
    }

    /**
     * @param string $title
     * @param array $cache
     * @return int|null
     */
    private function _find_assessment_id($title, &$cache)
    {
        $key = strtolower(trim($title));
        if ($key === '') {
            return null;
        }
        if (isset($cache[$key])) {
            return (int) $cache[$key];
        }
        $tbl = $this->CI->ta->table('assessments');
        $row = $this->CI->db->where('title', $title)->get($tbl)->row();
        if (!$row) {
            $row = $this->CI->db->where('LOWER(TRIM(title)) = ' . $this->CI->db->escape($key), null, false)->get($tbl)->row();
        }
        if ($row) {
            $cache[$key] = (int) $row->id;
            return (int) $row->id;
        }
        return null;
    }

    /**
     * @param string $modTitle
     * @param string $topicName
     * @param array $moduleCache
     * @param array $topicCache
     * @return int|null
     */
    private function _resolve_topic_id($modTitle, $topicName, &$moduleCache, &$topicCache)
    {
        $moduleId = $this->_find_module_id($modTitle, $moduleCache);
        if (!$moduleId) {
            return null;
        }
        $topicKey = $moduleId . '|' . strtolower($topicName);
        if (isset($topicCache[$topicKey])) {
            return (int) $topicCache[$topicKey];
        }
        $row = $this->CI->db->where('module_id', (int) $moduleId)->where('name', $topicName)->get('training_topics')->row();
        if (!$row) {
            $row = $this->CI->db->where('module_id', (int) $moduleId)
                ->where('LOWER(TRIM(name)) = ' . $this->CI->db->escape(strtolower($topicName)), null, false)
                ->get('training_topics')->row();
        }
        if ($row) {
            $topicCache[$topicKey] = (int) $row->id;
            return (int) $row->id;
        }
        return null;
    }
}
