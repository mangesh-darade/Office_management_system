<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * training_topics
 */
class Training_lms_topic_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function list_by_module($module_id)
    {
        $this->db->where('module_id', (int) $module_id);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        return $this->db->get('training_topics')->result();
    }

    /**
     * Admin list: join assignments and assessments to show titles.
     */
    public function list_by_module_admin($module_id)
    {
        $this->load->helper('training');
        $atbl = training_physical_assessments_table();
        $has_assign = $this->db->table_exists('assignments');

        $this->db->select('t.*');
        if ($has_assign) {
            $this->db->select('asn.name AS assignment_name');
            $this->db->join('assignments asn', 'asn.topic_id = t.id', 'left');
        } else {
            $this->db->select("'' AS assignment_name", false);
        }

        if ($atbl !== '') {
            $this->db->select('ast.title AS assessment_title');
            $this->db->join($atbl . ' ast', 'ast.id = t.assessment_id', 'left');
        } else {
            $this->db->select("'' AS assessment_title", false);
        }

        $this->db->where('t.module_id', (int) $module_id);
        $this->db->order_by('t.sort_order', 'ASC');
        $this->db->order_by('t.id', 'ASC');
        return $this->db->get('training_topics t')->result();
    }

    /**
     * Learner list: join details for card view.
     */
    public function list_by_module_with_details($module_id)
    {
        $this->load->helper('training');
        $atbl = training_physical_assessments_table();
        $has_assign = $this->db->table_exists('assignments');

        $sel = 't.*';
        if ($has_assign) {
            $sel .= ', asn.name AS assignment_display_name';
        } else {
            $sel .= ", '' AS assignment_display_name";
        }

        if ($atbl !== '') {
            $sel .= ', ast.title AS assessment_display_title';
        } else {
            $sel .= ", '' AS assessment_display_title";
        }

        $this->db->select($sel, false);
        $this->db->from('training_topics t');
        if ($has_assign) {
            $this->db->join('assignments asn', 'asn.topic_id = t.id', 'left');
        }
        if ($atbl !== '') {
            $this->db->join($atbl . ' ast', 'ast.id = t.assessment_id', 'left');
        }

        $this->db->where('t.module_id', (int) $module_id);
        $this->db->order_by('t.sort_order', 'ASC');
        $this->db->order_by('t.id', 'ASC');
        return $this->db->get()->result();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->get('training_topics')->row();
    }

    public function get_with_module($id)
    {
        $this->db->select('t.*, m.title AS module_title, m.status AS module_status');
        $this->db->from('training_topics t');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        $this->db->where('t.id', (int) $id);
        return $this->db->get()->row();
    }

    public function insert_row($data)
    {
        $this->db->insert('training_topics', $data);
        return (int) $this->db->insert_id();
    }

    public function update_row($id, $data)
    {
        $this->db->where('id', (int) $id)->update('training_topics', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_row($id)
    {
        $this->db->where('id', (int) $id)->delete('training_topics');
        return $this->db->affected_rows() > 0;
    }

    public function completions_schema_ready()
    {
        return $this->db->table_exists('training_topic_completions');
    }

    public function user_completed_topic($user_id, $topic_id)
    {
        $n = $this->db->where('user_id', (int) $user_id)
            ->where('topic_id', (int) $topic_id)
            ->count_all_results('training_topic_completions');
        return $n > 0;
    }

    public function mark_topic_completed($user_id, $topic_id)
    {
        if ($this->user_completed_topic($user_id, $topic_id)) {
            return true;
        }
        $this->db->insert('training_topic_completions', array(
            'user_id' => (int) $user_id,
            'topic_id' => (int) $topic_id,
            'completed_at' => date('Y-m-d H:i:s')
        ));
        return $this->db->affected_rows() > 0;
    }
}
