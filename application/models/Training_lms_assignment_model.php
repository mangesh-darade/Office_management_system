<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * assignments + assignment_submissions (per spec table names)
 */
class Training_lms_assignment_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function schema_ready()
    {
        return $this->db->table_exists('assignments');
    }

    public function get_by_topic($topic_id)
    {
        return $this->db->where('topic_id', (int) $topic_id)->get('assignments')->row();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->get('assignments')->row();
    }

    public function insert_assignment($data)
    {
        $this->db->insert('assignments', $data);
        return (int) $this->db->insert_id();
    }

    public function update_assignment($id, $data)
    {
        $this->db->where('id', (int) $id)->update('assignments', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_for_topic($topic_id)
    {
        $this->db->where('topic_id', (int) $topic_id)->delete('assignments');
    }

    public function list_submissions_for_assignment($assignment_id)
    {
        $this->db->select('s.*, u.name AS user_name, u.email AS user_email');
        $this->db->from('assignment_submissions s');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        $this->db->where('s.assignment_id', (int) $assignment_id);
        $this->db->order_by('s.submitted_at', 'DESC');
        return $this->db->get()->result();
    }

    public function list_submissions_for_user_assignment($assignment_id, $user_id)
    {
        $this->db->where('assignment_id', (int) $assignment_id);
        $this->db->where('user_id', (int) $user_id);
        $this->db->order_by('submitted_at', 'DESC');
        return $this->db->get('assignment_submissions')->result();
    }

    public function get_submission($id)
    {
        return $this->db->where('id', (int) $id)->get('assignment_submissions')->row();
    }

    public function insert_submission($data)
    {
        $this->db->insert('assignment_submissions', $data);
        return (int) $this->db->insert_id();
    }

    public function update_submission($id, $data)
    {
        $this->db->where('id', (int) $id)->update('assignment_submissions', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function count_user_submissions_for_assignment($assignment_id, $user_id)
    {
        return (int) $this->db->where('assignment_id', (int) $assignment_id)
            ->where('user_id', (int) $user_id)
            ->count_all_results('assignment_submissions');
    }
}
