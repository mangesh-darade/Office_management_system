<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    /**
     * Attendance summary report.
     * Returns daily punch-in/out counts for the given date range.
     *
     * @param string $from  Y-m-d
     * @param string $to    Y-m-d
     * @param int|null $user_id  Filter by user (null = all)
     * @return array
     */
    public function get_attendance_summary($from, $to, $user_id = null)
    {
        $date_col = $this->db->field_exists('att_date', 'attendance') ? 'att_date' : 'date';
        $this->db->select([
            "DATE(`{$date_col}`) AS report_date",
            'COUNT(*) AS total',
            "SUM(CASE WHEN status = 'present'     THEN 1 ELSE 0 END) AS present",
            "SUM(CASE WHEN status = 'late'        THEN 1 ELSE 0 END) AS late",
            "SUM(CASE WHEN status = 'early_leave' THEN 1 ELSE 0 END) AS early_leave",
            "SUM(CASE WHEN status = 'absent'      THEN 1 ELSE 0 END) AS absent",
        ]);
        $this->db->from('attendance');
        $this->db->where("{$date_col} >=", $from);
        $this->db->where("{$date_col} <=", $to);
        if ($user_id !== null) {
            $this->db->where('user_id', (int)$user_id);
        }
        $this->db->group_by("DATE(`{$date_col}`)");
        $this->db->order_by("report_date", 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Task assignment report.
     * Returns tasks with assignee and project details.
     *
     * @param array $filters  ['project_id', 'status', 'assigned_to', 'from', 'to']
     * @return array
     */
    public function get_task_assignment_report($filters = [])
    {
        $this->db->select('t.id, t.title, t.status, t.priority, t.due_date, t.created_at,
                           u.name AS assigned_to_name, u.email AS assigned_to_email,
                           p.name AS project_name');
        $this->db->from('tasks t');
        $this->db->join('users u',    'u.id = t.assigned_to',  'left');
        $this->db->join('projects p', 'p.id = t.project_id',   'left');
        if (!empty($filters['project_id'])) {
            $this->db->where('t.project_id', (int)$filters['project_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->where('t.assigned_to', (int)$filters['assigned_to']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('t.created_at >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('t.created_at <=', $filters['to'] . ' 23:59:59');
        }
        $this->db->order_by('t.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Leave report.
     * Returns leave requests with employee and type details.
     *
     * @param array $filters  ['user_id', 'status', 'leave_type_id', 'from', 'to']
     * @return array
     */
    public function get_leave_report($filters = [])
    {
        $this->db->select('lr.id, lr.start_date, lr.end_date, lr.days, lr.status,
                           lr.reason, lr.created_at,
                           u.name AS employee_name, u.email AS employee_email,
                           lt.name AS leave_type');
        $this->db->from('leave_requests lr');
        $this->db->join('users u',       'u.id = lr.user_id',       'left');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        if (!empty($filters['user_id'])) {
            $this->db->where('lr.user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('lr.status', $filters['status']);
        }
        if (!empty($filters['leave_type_id'])) {
            $this->db->where('lr.leave_type_id', (int)$filters['leave_type_id']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('lr.start_date >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('lr.end_date <=', $filters['to']);
        }
        $this->db->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Project status report.
     * Returns projects grouped by status with task counts.
     *
     * @param array $filters  ['status', 'client_id']
     * @return array
     */
    public function get_projects_status_report($filters = [])
    {
        $this->db->select('p.id, p.name, p.status, p.start_date, p.end_date,
                           c.name AS client_name,
                           COUNT(t.id) AS task_count,
                           SUM(CASE WHEN t.status = "completed" THEN 1 ELSE 0 END) AS completed_tasks');
        $this->db->from('projects p');
        $this->db->join('clients c', 'c.id = p.client_id', 'left');
        $this->db->join('tasks t',   't.project_id = p.id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
        if (!empty($filters['client_id'])) {
            $this->db->where('p.client_id', (int)$filters['client_id']);
        }
        $this->db->group_by('p.id');
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Daily activity report.
     *
     * @param array $filters  ['user_id', 'from', 'to']
     * @return array
     */
    public function get_daily_activity_report($filters = [])
    {
        $this->db->select('d.*, u.name AS user_name, u.email AS user_email');
        $this->db->from('daily_work_logs d');
        $this->db->join('users u', 'u.id = d.user_id', 'left');
        if (!empty($filters['user_id'])) {
            $this->db->where('d.user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('d.log_date >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('d.log_date <=', $filters['to']);
        }
        $this->db->order_by('d.log_date', 'DESC');
        return $this->db->get()->result();
    }
}
