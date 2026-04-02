<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flat rows for office / spreadsheet feeds (training modules, topics, assignments, submissions).
 */
class Training_office_feed_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function lms_ready()
    {
        return $this->db->table_exists('training_modules')
            && $this->db->table_exists('training_topics');
    }

    /**
     * Module Name, Topic, High-Level Details, Prerequisites, Duration (Hours), flags, linked assessment.
     *
     * @return array
     */
    public function feed_module_topic_catalog()
    {
        if (!$this->lms_ready()) {
            return array();
        }
        $atbl = $this->_assessments_physical_table();
        $this->db->select('m.title AS module_name, t.name AS topic_name, t.description AS high_level_details, t.prerequisites, t.duration_hours, t.has_assignment, t.has_assessment, t.assessment_id', false);
        if ($atbl !== '') {
            $this->db->select('a.title AS linked_assessment_title', false);
        } else {
            $this->db->select("'' AS linked_assessment_title", false);
        }
        $this->db->from('training_topics t');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        if ($atbl !== '') {
            $this->db->join($atbl . ' a', 'a.id = t.assessment_id', 'left');
        }
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('m.id', 'ASC');
        $this->db->order_by('t.sort_order', 'ASC');
        $this->db->order_by('t.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * @return string
     */
    private function _assessments_physical_table()
    {
        if ($this->db->table_exists('assessments')) {
            return 'assessments';
        }
        if ($this->db->table_exists('ta_assessments')) {
            return 'ta_assessments';
        }
        return '';
    }

    /**
     * Topic (with module), Assignment Name, Assignment Details.
     *
     * @return array
     */
    public function feed_assignment_definitions()
    {
        if (!$this->lms_ready() || !$this->db->table_exists('assignments')) {
            return array();
        }
        $this->db->select('m.title AS module_name, t.name AS topic_name, asn.name AS assignment_name, asn.details AS assignment_details, asn.max_submissions', false);
        $this->db->from('assignments asn');
        $this->db->join('training_topics t', 't.id = asn.topic_id', 'inner');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('t.sort_order', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * All LMS assignment submissions with topic / assignee / score / status.
     *
     * @return array
     */
    public function feed_assignment_submissions()
    {
        if (!$this->lms_ready() || !$this->db->table_exists('assignment_submissions')) {
            return array();
        }
        $this->db->select('s.id AS submission_id, t.id AS topic_id, m.title AS module_name, t.name AS topic_name, asn.name AS assignment_name, u.name AS submitted_by_name, u.email AS submitted_by_email, s.submitted_at, s.original_filename AS attachment_filename, s.stored_filename, s.score AS assignment_score, grader.name AS assessed_by_name, s.status, s.feedback', false);
        $this->db->from('assignment_submissions s');
        $this->db->join('assignments asn', 'asn.id = s.assignment_id', 'inner');
        $this->db->join('training_topics t', 't.id = asn.topic_id', 'inner');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        $this->db->join('users grader', 'grader.id = s.assessed_by', 'left');
        $this->db->order_by('s.submitted_at', 'DESC');
        $this->db->order_by('s.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Import or update per-topic file assignments from a UTF-8 CSV (with or without BOM).
     * Header row (recommended): Module Name, Topic, Assignment Name, Assignment Details, Max submissions (0=unlimited)
     * Without header: same column order; max submissions column optional (defaults 0 = unlimited).
     *
     * @param string $path Absolute path to uploaded temp file
     * @return array{imported:int,skipped:int,errors:string[]}
     */
    public function import_lms_assignments_from_csv_path($path)
    {
        $out = array('imported' => 0, 'skipped' => 0, 'errors' => array());
        if (!$this->lms_ready() || !$this->db->table_exists('assignments')) {
            $out['errors'][] = 'LMS assignment tables are not installed.';
            return $out;
        }
        if (!is_readable($path)) {
            $out['errors'][] = 'Could not read the uploaded file.';
            return $out;
        }
        $fh = fopen($path, 'r');
        if (!$fh) {
            $out['errors'][] = 'Could not open the uploaded file.';
            return $out;
        }
        $first = fgetcsv($fh);
        if ($first === false || $first === null) {
            fclose($fh);
            $out['errors'][] = 'The CSV file is empty.';
            return $out;
        }
        $first[0] = isset($first[0]) ? preg_replace('/^\xEF\xBB\xBF/', '', $first[0]) : '';
        $map = $this->_assignment_import_map_headers($first);
        if ($map === null) {
            $map = array('module' => 0, 'topic' => 1, 'name' => 2, 'details' => 3, 'max' => 4);
            $line = 1;
            $row = $first;
        } else {
            $line = 2;
            $row = fgetcsv($fh);
        }
        $now = date('Y-m-d H:i:s');
        while ($row !== false) {
            if ($this->_assignment_import_row_empty($row)) {
                $row = fgetcsv($fh);
                $line++;
                continue;
            }
            $modTitle = isset($row[$map['module']]) ? trim((string) $row[$map['module']]) : '';
            $topicName = isset($row[$map['topic']]) ? trim((string) $row[$map['topic']]) : '';
            $aName = isset($row[$map['name']]) ? trim((string) $row[$map['name']]) : '';
            $aDetails = isset($row[$map['details']]) ? trim((string) $row[$map['details']]) : '';
            $maxSub = 0;
            if (isset($map['max']) && isset($row[$map['max']]) && trim((string) $row[$map['max']]) !== '') {
                $maxSub = max(0, (int) $row[$map['max']]);
            }
            if ($modTitle === '' || $topicName === '') {
                $out['errors'][] = 'Line ' . $line . ': Module Name and Topic are required.';
                $out['skipped']++;
                $row = fgetcsv($fh);
                $line++;
                continue;
            }
            if ($aName === '') {
                $aName = $topicName . ' — Assignment';
            }
            $topic_id = $this->_resolve_topic_id_for_import($modTitle, $topicName);
            if (!$topic_id) {
                $out['errors'][] = 'Line ' . $line . ': No topic found for module "' . $modTitle . '" / topic "' . $topicName . '".';
                $out['skipped']++;
                $row = fgetcsv($fh);
                $line++;
                continue;
            }
            $this->db->where('id', (int) $topic_id)->update('training_topics', array(
                'has_assignment' => 1,
                'updated_at' => $now,
            ));
            $existing = $this->db->where('topic_id', (int) $topic_id)->get('assignments')->row();
            if ($existing) {
                $this->db->where('id', (int) $existing->id)->update('assignments', array(
                    'name' => $aName,
                    'details' => $aDetails,
                    'max_submissions' => $maxSub,
                    'updated_at' => $now,
                ));
            } else {
                $this->db->insert('assignments', array(
                    'topic_id' => (int) $topic_id,
                    'name' => $aName,
                    'details' => $aDetails,
                    'max_submissions' => $maxSub,
                    'created_at' => $now,
                    'updated_at' => $now,
                ));
            }
            $out['imported']++;
            $row = fgetcsv($fh);
            $line++;
        }
        fclose($fh);
        return $out;
    }

    /**
     * @param string[] $firstRow
     * @return array<string,int>|null
     */
    private function _assignment_import_map_headers($firstRow)
    {
        $norm = array();
        foreach ($firstRow as $i => $cell) {
            $norm[$i] = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell)));
        }
        if (!isset($norm[0]) || $norm[0] !== 'module name') {
            return null;
        }
        $map = array();
        foreach ($norm as $i => $h) {
            if ($h === 'module name') {
                $map['module'] = $i;
            } elseif ($h === 'topic') {
                $map['topic'] = $i;
            } elseif ($h === 'assignment name') {
                $map['name'] = $i;
            } elseif ($h === 'assignment details') {
                $map['details'] = $i;
            } elseif ($h === 'max submissions (0=unlimited)' || $h === 'max submissions') {
                $map['max'] = $i;
            }
        }
        if (!isset($map['module'], $map['topic'], $map['name'], $map['details'])) {
            return null;
        }
        return $map;
    }

    /**
     * @param string[]|false $row
     */
    private function _assignment_import_row_empty($row)
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
     * @param string $moduleTitle
     * @param string $topicName
     * @return int|null topic id
     */
    private function _resolve_topic_id_for_import($moduleTitle, $topicName)
    {
        $this->db->from('training_modules');
        $this->db->where('title', $moduleTitle);
        $m = $this->db->get()->row();
        if (!$m) {
            $this->db->from('training_modules');
            $this->db->where('LOWER(TRIM(title)) = ' . $this->db->escape(strtolower($moduleTitle)), null, false);
            $m = $this->db->get()->row();
        }
        if (!$m) {
            return null;
        }
        $mid = (int) $m->id;
        $this->db->from('training_topics');
        $this->db->where('module_id', $mid);
        $this->db->where('name', $topicName);
        $t = $this->db->get()->row();
        if (!$t) {
            $this->db->from('training_topics');
            $this->db->where('module_id', $mid);
            $this->db->where('LOWER(TRIM(name)) = ' . $this->db->escape(strtolower($topicName)), null, false);
            $t = $this->db->get()->row();
        }
        return $t ? (int) $t->id : null;
    }
}
