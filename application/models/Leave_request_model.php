<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_request_model extends CI_Model {
    public function __construct(){
        parent::__construct(); 
        $this->load->database();
        $this->load->model('Setting_model', 'settings');
    }

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
        $id = (int)$id;

        // Fetch existing leave row to know previous status and details
        $leave = $this->db->get_where('leave_requests', ['id' => $id])->row();
        if (!$leave) {
            return false;
        }
        $old_status = (string)$leave->status;

        // Update leave status
        $this->db->where('id', $id)->update('leave_requests', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Log approval / rejection
        $this->db->insert('leave_approvals', [
            'leave_id' => $id,
            'approver_id' => (int)$approved_by,
            'level' => 'lead',
            'decision' => ($status === 'rejected' ? 'rejected' : 'approved'),
            'remarks' => (string)$comments,
        ]);

        // Automatically deduct balance when moving from pending to an approved state
        $approved_statuses = ['lead_approved', 'hr_approved'];
        if (in_array($status, $approved_statuses, true) && $old_status === 'pending') {
            $days = (float)$leave->days;
            if ($days > 0) {
                $this->update_leave_balance((int)$leave->user_id, (int)$leave->type_id, $days);
            }
        }

        return true;
    }

    public function get_leave_balance($user_id, $leave_type_id){
        $year = (int)date('Y');
        $user_id = (int)$user_id;
        $leave_type_id = (int)$leave_type_id;

        $row = $this->db->get_where('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
        ])->row();

        // If we already have a balance row, use it directly
        if ($row) {
            $available = (float)$row->opening_balance + (float)$row->accrued - (float)$row->used;
            return (object) [
                'opening_balance' => (float)$row->opening_balance,
                'accrued' => (float)$row->accrued,
                'used' => (float)$row->used,
                'closing_balance' => (float)$row->closing_balance,
                'available' => $available,
            ];
        }

        // No per-user balance row: base allocation from leave_types.annual_quota
        $base = 0.0;
        $type = $this->db->get_where('leave_types', ['id' => $leave_type_id])->row();
        if ($type && isset($type->annual_quota) && is_numeric($type->annual_quota)) {
            $base = (float)$type->annual_quota;
        }

        // Fallback to global default from settings if type quota is not defined or zero
        if ($base <= 0 && isset($this->settings)) {
            $val = $this->settings->get_setting('leave_default_days', 0);
            if (is_numeric($val)) {
                $base = (float)$val;
            }
        }

        return (object) [
            'opening_balance' => $base,
            'accrued' => 0.0,
            'used' => 0.0,
            'closing_balance' => $base,
            'available' => $base,
        ];
    }

    public function update_leave_balance($user_id, $leave_type_id, $days){
        $year = (int)date('Y');
        $user_id = (int)$user_id;
        $leave_type_id = (int)$leave_type_id;
        $days = (float)$days;

        if ($days <= 0) {
            return true;
        }

        // Try to update existing balance row first
        $row = $this->db->get_where('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
        ])->row();

        if ($row) {
            $used = (float)$row->used + $days;
            $total = (float)$row->opening_balance + (float)$row->accrued;
            $closing = $total - $used;
            if ($closing < 0) {
                $closing = 0.0;
            }

            $this->db->where('id', (int)$row->id)->update('leave_balances', [
                'used' => $used,
                'closing_balance' => $closing,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->db->affected_rows() >= 0;
        }

        // No balance row yet: seed from leave_types.annual_quota or settings default
        $base = 0.0;
        $type = $this->db->get_where('leave_types', ['id' => $leave_type_id])->row();
        if ($type && isset($type->annual_quota) && is_numeric($type->annual_quota)) {
            $base = (float)$type->annual_quota;
        }
        if ($base <= 0 && isset($this->settings)) {
            $val = $this->settings->get_setting('leave_default_days', 0);
            if (is_numeric($val)) {
                $base = (float)$val;
            }
        }

        $opening = $base;
        $used = $days;
        $closing = $opening - $used;
        if ($closing < 0) {
            $closing = 0.0;
        }

        $this->db->insert('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
            'opening_balance' => $opening,
            'accrued' => 0.0,
            'used' => $used,
            'closing_balance' => $closing,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }
}
