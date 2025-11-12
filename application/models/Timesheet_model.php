<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheet_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function get_user_timesheet($user_id, $week_start){
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
        $this->db->insert('timesheet_entries', $entry);
        return (int)$this->db->insert_id();
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
        $sql = "SELECT u.email, SUM(te.hours) AS hours
                FROM timesheet_entries te
                JOIN timesheets ts ON ts.id = te.timesheet_id
                JOIN users u ON u.id = ts.user_id
                WHERE te.work_date BETWEEN ? AND ?
                GROUP BY u.id, u.email
                ORDER BY hours DESC";
        return $this->db->query($sql, [$start, $end])->result();
    }
}
