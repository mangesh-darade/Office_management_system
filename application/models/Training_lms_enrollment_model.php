<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * training_enrollments: which users are assigned to which modules.
 */
class Training_lms_enrollment_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function schema_ready()
    {
        return $this->db->table_exists('training_enrollments');
    }

    public function user_has_any_enrollment($user_id)
    {
        if (!$this->schema_ready()) {
            return false;
        }
        return $this->db->where('user_id', (int) $user_id)->count_all_results('training_enrollments') > 0;
    }

    /**
     * When true, learners without a row in training_enrollments see no modules (managers always see all).
     * False if the table is empty — enrollment not in use yet, catalogue stays open for everyone.
     */
    public function is_enrollment_gating_active()
    {
        if (!$this->schema_ready()) {
            return false;
        }
        return (int) $this->db->count_all('training_enrollments') > 0;
    }

    public function user_enrolled_in_module($user_id, $module_id)
    {
        if (!$this->schema_ready()) {
            return true;
        }
        return $this->db->where('user_id', (int) $user_id)->where('module_id', (int) $module_id)->count_all_results('training_enrollments') > 0;
    }

    public function list_module_ids_for_user($user_id)
    {
        if (!$this->schema_ready()) {
            return array();
        }
        $q = $this->db->select('module_id')->where('user_id', (int) $user_id)->get('training_enrollments');
        $out = array();
        foreach ($q->result() as $r) {
            $out[] = (int) $r->module_id;
        }
        return $out;
    }

    public function list_rows_for_module($module_id)
    {
        if (!$this->schema_ready()) {
            return array();
        }
        $this->db->select('e.*, u.name AS user_name, u.email AS user_email');
        $this->db->from('training_enrollments e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        $this->db->where('e.module_id', (int) $module_id);
        $this->db->order_by('u.name', 'ASC');
        return $this->db->get()->result();
    }

    public function enroll($user_id, $module_id, $assigned_by, $due_at = null)
    {
        if (!$this->schema_ready() || (int) $user_id < 1 || (int) $module_id < 1) {
            return false;
        }
        if ($this->db->where('user_id', (int) $user_id)->where('module_id', (int) $module_id)->count_all_results('training_enrollments') > 0) {
            return true;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->insert('training_enrollments', array(
            'user_id' => (int) $user_id,
            'module_id' => (int) $module_id,
            'assigned_by' => (int) $assigned_by > 0 ? (int) $assigned_by : null,
            'due_at' => $due_at ? $due_at : null,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        return true;
    }

    public function bulk_enroll_users($module_id, array $user_ids, $assigned_by)
    {
        foreach ($user_ids as $uid) {
            $this->enroll((int) $uid, (int) $module_id, $assigned_by);
        }
    }

    public function delete_enrollment($user_id, $module_id)
    {
        if (!$this->schema_ready()) {
            return;
        }
        $this->db->where('user_id', (int) $user_id)->where('module_id', (int) $module_id)->delete('training_enrollments');
    }
}
