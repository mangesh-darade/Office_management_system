<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_request_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function get_user_leaves($user_id, $filters = []){
        $this->db->select('lr.*, lt.name AS type_name')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->where('lr.user_id', (int)$user_id);
        if (!empty($filters['status'])) {
            $this->db->where('lr.status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $this->db->where('lr.start_date >=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('lr.end_date <=', $filters['end_date']);
        }
        $this->db->order_by('lr.start_date', 'DESC');
        return $this->db->get()->result();
    }

    public function apply_leave($data){
        $this->db->insert('leave_requests', $data);
        return (int)$this->db->insert_id();
    }

    public function get_pending_approvals($manager_id){
        // Placeholder for Phase 2
        $this->db->select('lr.*')
                 ->from('leave_requests lr')
                 ->where('lr.current_approver_id', (int)$manager_id)
                 ->where('lr.status', 'pending')
                 ->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function approve_reject_leave($id, $status, $comments, $approved_by){
        // Placeholder for Phase 2 (multi-level approval)
        $this->db->where('id', (int)$id)->update('leave_requests', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $this->db->insert('leave_approvals', [
            'leave_id' => (int)$id,
            'approver_id' => (int)$approved_by,
            'level' => 'lead',
            'decision' => ($status === 'rejected' ? 'rejected' : 'approved'),
            'remarks' => (string)$comments,
        ]);
        return true;
    }

    public function get_leave_balance($user_id, $leave_type_id){
        $year = (int)date('Y');
        $row = $this->db->get_where('leave_balances', [
            'user_id' => (int)$user_id,
            'type_id' => (int)$leave_type_id,
            'year' => $year,
        ])->row();
        if (!$row) {
            return (object) [
                'opening_balance' => 0,
                'accrued' => 0,
                'used' => 0,
                'closing_balance' => 0,
                'available' => 0,
            ];
        }
        $available = (float)$row->opening_balance + (float)$row->accrued - (float)$row->used;
        return (object) [
            'opening_balance' => (float)$row->opening_balance,
            'accrued' => (float)$row->accrued,
            'used' => (float)$row->used,
            'closing_balance' => (float)$row->closing_balance,
            'available' => $available,
        ];
    }

    public function update_leave_balance($user_id, $leave_type_id, $days){
        $year = (int)date('Y');
        $this->db->set('used', 'used + '.((float)$days), false)
                 ->set('closing_balance', 'opening_balance + accrued - used', false)
                 ->where(['user_id' => (int)$user_id, 'type_id' => (int)$leave_type_id, 'year' => $year])
                 ->update('leave_balances');
        return $this->db->affected_rows() >= 0;
    }
}
