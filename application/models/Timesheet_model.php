<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheet_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database();
        $this->load->helper(array('schema_columns', 'hierarchy_filter', 'timesheet_schema'));
        timesheet_schema_ensure($this->db);
    }

    public function get_user_timesheet($user_id, $week_start){
        $visible = get_accessible_hierarchy_user_ids();
        if (!empty($visible) && !in_array((int)$user_id, $visible, true)) {
            return [null, []];
        }
        $week_end = date('Y-m-d', strtotime($week_start.' +6 days'));
        $row = $this->db->get_where('timesheets', ['user_id'=>(int)$user_id, 'week_start_date'=>$week_start])->row();
        if (!$row){
            $row = (object)[
                'id' => null,
                'user_id' => (int)$user_id,
                'week_start_date' => $week_start,
                'week_end_date' => $week_end,
                'total_hours' => 0,
                'status' => 'draft',
            ];
        }
        $entries = [];
        if (!empty($row->id)){
            $entries = $this->db->get_where('timesheet_entries', ['timesheet_id'=>(int)$row->id])->result();
        }
        return [$row, $entries];
    }

    public function create_timesheet($data){
        $this->db->insert('timesheets', $data);
        return (int)$this->db->insert_id();
    }

    public function add_entry($timesheet_id, $entry){
        $entry['timesheet_id'] = (int)$timesheet_id;
        // Only include 'billable' if the column exists in timesheet_entries
        if (schema_table_has_column($this->db, 'timesheet_entries', 'billable')) {
            if (!isset($entry['billable'])) {
                $entry['billable'] = 1; // Default to billable
            }
        } else {
            // Column missing (migration not run) — remove it to prevent SQL error
            unset($entry['billable']);
        }
        $this->db->insert('timesheet_entries', $entry);
        $entry_id = (int)$this->db->insert_id();
        
        // Update total hours in timesheet
        $this->update_timesheet_total_hours($timesheet_id);
        
        return $entry_id;
    }
    
    /**
     * Update total hours for a timesheet
     */
    public function update_timesheet_total_hours($timesheet_id){
        $result = $this->db->select_sum('hours')
                           ->where('timesheet_id', (int)$timesheet_id)
                           ->get('timesheet_entries')
                           ->row();
        $total_hours = $result && $result->hours ? (float)$result->hours : 0.00;
        
        $this->db->where('id', (int)$timesheet_id)
                 ->update('timesheets', ['total_hours' => $total_hours]);
        
        return $total_hours;
    }

    public function submit_timesheet($id){
        $this->db->where('id',(int)$id)->update('timesheets',[ 'status'=>'submitted', 'submitted_at'=>date('Y-m-d H:i:s') ]);
        return $this->db->affected_rows() >= 0;
    }

    public function approve_reject($id, $status, $approved_by, $comments){
        $data = [ 'status'=>$status, 'approved_by'=>(int)$approved_by, 'approved_at'=>date('Y-m-d H:i:s'), 'comments'=>$comments ];
        $this->db->where('id',(int)$id)->update('timesheets', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function get_pending_approvals($manager_id){
        // team members linked by employees.reporting_to
        $this->db->from('timesheets t')
                 ->join('employees e', 'e.user_id = t.user_id', 'left')
                 ->where('t.status', 'submitted')
                 ->where('e.reporting_to', (int)$manager_id)
                 ->order_by('t.week_start_date','DESC');
        return $this->db->get()->result();
    }

    public function report_monthly_hours($year, $month){
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $this->db->select('u.email, SUM(te.hours) AS hours', false)
            ->from('timesheet_entries te')
            ->join('timesheets ts', 'ts.id = te.timesheet_id')
            ->join('users u', 'u.id = ts.user_id')
            ->where('te.work_date >=', $start)
            ->where('te.work_date <=', $end);
        apply_role_hierarchy_filter($this->db, 'ts.user_id');
        $this->db->group_by('u.id, u.email')->order_by('hours', 'DESC');
        return $this->db->get()->result();
    }
    
    /**
     * Get billable vs non-billable hours for a timesheet
     */
    public function get_billable_hours($timesheet_id){
        // Guard: return zeroes if billable column hasn't been added by migration yet
        if (!schema_table_has_column($this->db, 'timesheet_entries', 'billable')) {
            $result = $this->db->select_sum('hours')
                               ->where('timesheet_id', (int)$timesheet_id)
                               ->get('timesheet_entries')
                               ->row();
            $total = $result && $result->hours ? (float)$result->hours : 0.00;
            return ['billable' => $total, 'non_billable' => 0.00, 'total' => $total];
        }
        $result = $this->db->select('SUM(CASE WHEN billable = 1 THEN hours ELSE 0 END) as billable_hours,
                                     SUM(CASE WHEN billable = 0 THEN hours ELSE 0 END) as non_billable_hours,
                                     SUM(hours) as total_hours')
                           ->where('timesheet_id', (int)$timesheet_id)
                           ->get('timesheet_entries')
                           ->row();
        
        return [
            'billable'     => $result ? (float)$result->billable_hours : 0.00,
            'non_billable' => $result ? (float)$result->non_billable_hours : 0.00,
            'total'        => $result ? (float)$result->total_hours : 0.00
        ];
    }
    
    /**
     * Get task time tracking - hours logged per task
     */
    public function get_task_time_tracking($task_id = null, $start_date = null, $end_date = null){
        $this->db->select('t.id as task_id, t.title as task_title, 
                          p.id as project_id, p.name as project_name,
                          u.id as user_id, u.email as user_email,
                          SUM(te.hours) as total_hours,
                          ' . timesheet_billable_hours_sql($this->db, 'te') . ',
                          COUNT(te.id) as entry_count', false)
                 ->from('timesheet_entries te')
                 ->join('timesheets ts', 'ts.id = te.timesheet_id', 'left')
                 ->join('users u', 'u.id = ts.user_id', 'left')
                 ->join('tasks t', 't.id = te.task_id', 'left')
                 ->join('projects p', 'p.id = te.project_id', 'left')
                 ->where('te.task_id IS NOT NULL');
        
        if ($task_id) {
            $this->db->where('te.task_id', (int)$task_id);
        }
        
        if ($start_date) {
            $this->db->where('te.work_date >=', $start_date);
        }
        
        if ($end_date) {
            $this->db->where('te.work_date <=', $end_date);
        }
        apply_role_hierarchy_filter($this->db, 'ts.user_id');
        
        $this->db->group_by('te.task_id, te.project_id, ts.user_id')
                 ->order_by('total_hours', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get project-wise timesheet analytics
     */
    public function get_project_analytics($project_id = null, $start_date = null, $end_date = null){
        $this->db->select('p.id as project_id, p.name as project_name,
                          SUM(te.hours) as total_hours,
                          ' . timesheet_billable_hours_sql($this->db, 'te') . ',
                          COUNT(DISTINCT ts.user_id) as user_count,
                          COUNT(te.id) as entry_count', false)
                 ->from('timesheet_entries te')
                 ->join('timesheets ts', 'ts.id = te.timesheet_id', 'left')
                 ->join('projects p', 'p.id = te.project_id', 'left')
                 ->where('te.project_id IS NOT NULL');
        
        if ($project_id) {
            $this->db->where('te.project_id', (int)$project_id);
        }
        
        if ($start_date) {
            $this->db->where('te.work_date >=', $start_date);
        }
        
        if ($end_date) {
            $this->db->where('te.work_date <=', $end_date);
        }
        apply_role_hierarchy_filter($this->db, 'ts.user_id');
        
        $this->db->group_by('te.project_id')
                 ->order_by('total_hours', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get user-wise timesheet analytics
     */
    public function get_user_analytics($user_id = null, $start_date = null, $end_date = null){
        $this->db->select('u.id as user_id, u.email as user_email,
                          SUM(te.hours) as total_hours,
                          ' . timesheet_billable_hours_sql($this->db, 'te') . ',
                          COUNT(DISTINCT te.project_id) as project_count,
                          COUNT(te.id) as entry_count,
                          AVG(te.hours) as avg_hours_per_entry', false)
                 ->from('timesheet_entries te')
                 ->join('timesheets ts', 'ts.id = te.timesheet_id', 'left')
                 ->join('users u', 'u.id = ts.user_id', 'left');
        
        if ($user_id) {
            $this->db->where('ts.user_id', (int)$user_id);
        }
        
        if ($start_date) {
            $this->db->where('te.work_date >=', $start_date);
        }
        
        if ($end_date) {
            $this->db->where('te.work_date <=', $end_date);
        }
        apply_role_hierarchy_filter($this->db, 'ts.user_id');
        
        $this->db->group_by('ts.user_id')
                 ->order_by('total_hours', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get timesheet entries with task and project details
     */
    public function get_entries_with_details($timesheet_id){
        $this->db->select('te.*, t.title as task_title, p.name as project_name')
                 ->from('timesheet_entries te')
                 ->join('tasks t', 't.id = te.task_id', 'left')
                 ->join('projects p', 'p.id = te.project_id', 'left')
                 ->where('te.timesheet_id', (int)$timesheet_id)
                 ->order_by('te.work_date', 'ASC')
                 ->order_by('te.id', 'ASC');
        
        return $this->db->get()->result();
    }
}
