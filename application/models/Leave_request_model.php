<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_request_model extends CI_Model {
    public function __construct(){
        parent::__construct(); 
        $this->load->database();
        $this->load->helper('hierarchy_filter');
        $this->load->model('Setting_model', 'settings');
        $this->ensure_schema();
    }

    private function ensure_schema(){
        static $done = false;
        if ($done) { return; }
        $done = true;
        if (!$this->db->table_exists('leave_approvals')) {
            $this->db->query("CREATE TABLE `leave_approvals` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `leave_id` int(11) NOT NULL,
                `approver_id` int(11) NOT NULL,
                `level` varchar(32) NOT NULL DEFAULT 'lead',
                `decision` enum('approved','rejected') NOT NULL,
                `remarks` text,
                `decided_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `leave_id` (`leave_id`),
                KEY `approver_id` (`approver_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
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
        apply_role_hierarchy_filter($this->db, 'lr.user_id');
        $this->db->order_by('lr.start_date', 'DESC');
        return $this->db->get()->result();
    }

    public function apply_leave($data){
        $this->db->insert('leave_requests', $data);
        return (int)$this->db->insert_id();
    }

    public function get_pending_approvals($manager_id){
        $this->db->select('lr.*')
                 ->from('leave_requests lr')
                 ->where('lr.status', 'pending')
                 ->order_by('lr.created_at', 'DESC');
        // Only filter by current_approver_id if the column exists
        if ($this->db->field_exists('current_approver_id', 'leave_requests')) {
            $this->db->where('lr.current_approver_id', (int)$manager_id);
        }
        apply_role_hierarchy_filter($this->db, 'lr.user_id');
        return $this->db->get()->result();
    }

    public function update_status($id, $status) {
        $this->db->where('id', (int)$id)->update('leave_requests', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function approve_reject_leave($id, $status, $comments, $approved_by){
        // Placeholder for Phase 2 (multi-level approval)
        $id = (int)$id;
        
        // Start transaction for atomic operations
        $this->db->trans_start();

        // Fetch existing leave row to know previous status and details
        $leave = $this->db->get_where('leave_requests', ['id' => $id])->row();
        if (!$leave) {
            $this->db->trans_rollback();
            return false;
        }
        $old_status = (string)$leave->status;

        // Update leave status
        $this->db->where('id', $id)->update('leave_requests', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Log approval / rejection via dedicated method (avoids duplicate entries)
        $this->add_approval_log($id, (int)$approved_by, ($status === 'rejected' ? 'rejected' : 'approved'), (string)$comments);


        // Check if this is a WFH request (by checking reason prefix or leave type name)
        $is_wfh = false;
        if (isset($leave->reason) && strpos($leave->reason, 'WFH:') === 0) {
            $is_wfh = true;
        } else {
            // Also check leave type name
            $leave_type = $this->db->select('name')->from('leave_types')->where('id', (int)$leave->type_id)->get()->row();
            if ($leave_type && strtolower(trim($leave_type->name)) === 'work from home') {
                $is_wfh = true;
            }
        }
        
        // Automatically deduct balance when moving from pending to an approved state (skip for WFH)
        $approved_statuses = ['lead_approved', 'hr_approved'];
        if (in_array($status, $approved_statuses, true) && $old_status === 'pending') {
            $days = (float)$leave->days;
            if ($days > 0 && !$is_wfh) {
                // Only deduct balance for regular leaves, not WFH
                $this->update_leave_balance((int)$leave->user_id, (int)$leave->type_id, $days);
            }
            
            // For WFH requests, create attendance records with WFH status
            if ($is_wfh && $days > 0) {
                $this->create_wfh_attendance_records((int)$leave->user_id, $leave->start_date, $leave->end_date);
            }
        }
        
        // Handle status change from approved to rejected (restore balance, remove WFH attendance)
        if ($status === 'rejected' && in_array($old_status, $approved_statuses, true)) {
            $days = (float)$leave->days;
            if ($days > 0) {
                if (!$is_wfh) {
                    // Restore balance by subtracting from used (only for regular leaves)
                    $year = (int)date('Y');
                    $balance_row = $this->db->get_where('leave_balances', [
                        'user_id' => (int)$leave->user_id,
                        'type_id' => (int)$leave->type_id,
                        'year' => $year,
                    ])->row();
                    
                    if ($balance_row) {
                        $used = max(0, (float)$balance_row->used - $days);
                        $total = (float)$balance_row->opening_balance + (float)$balance_row->accrued;
                        $closing = $total - $used;
                        if ($closing < 0) {
                            $closing = 0.0;
                        }
                        
                        $this->db->where('id', (int)$balance_row->id)->update('leave_balances', [
                            'used' => $used,
                            'closing_balance' => $closing,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                } else {
                    // Remove WFH attendance records when rejected
                    $this->remove_wfh_attendance_records((int)$leave->user_id, $leave->start_date, $leave->end_date);
                }
            }
        }
        
        // Handle status change from rejected to approved (deduct balance or create WFH records)
        if (in_array($status, $approved_statuses, true) && $old_status === 'rejected') {
            $days = (float)$leave->days;
            if ($days > 0) {
                if (!$is_wfh) {
                    $this->update_leave_balance((int)$leave->user_id, (int)$leave->type_id, $days);
                } else {
                    // Create WFH attendance records
                    $this->create_wfh_attendance_records((int)$leave->user_id, $leave->start_date, $leave->end_date);
                }
            }
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Leave approval/rejection transaction failed for leave ID: ' . $id);
            return false;
        }

        return true;
    }

    public function add_approval_log($leave_id, $decision, $remarks, $approver_id) {
        $this->db->insert('leave_approvals', [
            'leave_id' => (int)$leave_id,
            'approver_id' => (int)$approver_id,
            'level' => 'system', 
            'decision' => $decision,
            'remarks' => $remarks,
            'decided_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->insert_id();
    }
    
    /**
     * Remove WFH attendance records when WFH request is rejected
     */
    private function remove_wfh_attendance_records($user_id, $start_date, $end_date) {
        $dateCol = 'att_date';
        $statusCol = 'status';
        if (!$this->db->field_exists($dateCol, 'attendance')) {
            $dateCol = 'date';
        }
        if (!$this->db->field_exists($statusCol, 'attendance')) {
            return;
        }
        
        // Generate all dates in the range
        $startTs = strtotime($start_date);
        $endTs = strtotime($end_date);
        $dates = [];
        $current = $startTs;
        while ($current !== false && $current <= $endTs) {
            $dateStr = date('Y-m-d', $current);
            $dayOfWeek = (int)date('w', $current);
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $dates[] = $dateStr;
            }
            $current = strtotime('+1 day', $current);
        }
        
        // Delete WFH attendance records for these dates
        if (!empty($dates)) {
            $this->db->where('user_id', $user_id)
                     ->where($statusCol, 'work_from_home')
                     ->where_in($dateCol, $dates)
                     ->delete('attendance');
        }
    }
    
    /**
     * Create attendance records with WFH status for approved WFH requests
     */
    private function create_wfh_attendance_records($user_id, $start_date, $end_date) {
        // Detect date and status column names
        $dateCol = 'att_date';
        $statusCol = 'status';
        if (!$this->db->field_exists($dateCol, 'attendance')) {
            $dateCol = 'date';
        }
        if (!$this->db->field_exists($statusCol, 'attendance')) {
            return; // Can't create records if status column doesn't exist
        }
        
        // Generate all dates in the range
        $startTs = strtotime($start_date);
        $endTs = strtotime($end_date);
        $dates = [];
        $current = $startTs;
        while ($current !== false && $current <= $endTs) {
            $dateStr = date('Y-m-d', $current);
            $dayOfWeek = (int)date('w', $current); // 0=Sunday, 6=Saturday
            // Only create records for weekdays (not weekends)
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $dates[] = $dateStr;
            }
            $current = strtotime('+1 day', $current);
        }
        
        // Create or update attendance records for each date
        foreach ($dates as $date) {
            // Check if record already exists
            $existing = $this->db->where('user_id', $user_id)
                                ->where($dateCol, $date)
                                ->get('attendance')
                                ->row();
            
            if ($existing) {
                // Update existing record to WFH status
                $this->db->where('id', (int)$existing->id)
                         ->update('attendance', array_merge(
                             [$statusCol => 'work_from_home'],
                             $this->db->field_exists('updated_at', 'attendance') ? ['updated_at' => date('Y-m-d H:i:s')] : []
                         ));
            } else {
                // Create new attendance record with WFH status
                $data = [
                    'user_id' => $user_id,
                    $dateCol => $date,
                    $statusCol => 'work_from_home',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                if ($this->db->field_exists('updated_at', 'attendance')) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                }
                
                // Add source field if it exists
                if ($this->db->field_exists('source', 'attendance')) {
                    $data['source'] = 'manual';
                }
                
                $this->db->insert('attendance', $data);
            }
        }
    }

    public function get_leave_balance($user_id, $leave_type_id, $year = null){
        if ($year === null) {
            $year = (int)date('Y');
        }
        $user_id = (int)$user_id;
        $leave_type_id = (int)$leave_type_id;
        $year = (int)$year;

        $row = $this->db->get_where('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
        ])->row();

        // If we already have a balance row, use it directly
        if ($row) {
            $available = (float)$row->opening_balance + (float)$row->accrued - (float)$row->used;
            // Ensure available is not negative
            if ($available < 0) {
                $available = 0.0;
            }
            return (object) [
                'opening_balance' => (float)$row->opening_balance,
                'accrued' => (float)$row->accrued,
                'used' => (float)$row->used,
                'closing_balance' => (float)$row->closing_balance,
                'available' => $available,
            ];
        }
        
        // Auto-initialize balance if it doesn't exist
        $this->initialize_leave_balance($user_id, $leave_type_id, $year);
        
        // Fetch again after initialization
        $row = $this->db->get_where('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
        ])->row();
        
        if ($row) {
            $available = (float)$row->opening_balance + (float)$row->accrued - (float)$row->used;
            if ($available < 0) {
                $available = 0.0;
            }
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

        // Use transaction for data integrity
        $this->db->trans_start();
        
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
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Leave balance update transaction failed for user: ' . $user_id);
                return false;
            }
            
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

        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Leave balance update transaction failed for user: ' . $user_id);
            return false;
        }

        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Calculate leave accrual for a user
     * PHP 5.6+ compatible
     * 
     * @param int $user_id
     * @param int $leave_type_id
     * @param int $year
     * @return float Accrued days
     */
    public function calculate_leave_accrual($user_id, $leave_type_id, $year = null) {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $user_id = (int)$user_id;
        $leave_type_id = (int)$leave_type_id;
        $year = (int)$year;
        
        // Get leave type to check accrual settings
        $type = $this->db->get_where('leave_types', ['id' => $leave_type_id])->row();
        if (!$type) {
            return 0.0;
        }
        
        // Check if accrual is enabled for this leave type
        $accrual_enabled = false;
        if ($this->db->field_exists('accrual_enabled', 'leave_types')) {
            $accrual_enabled = (bool)$type->accrual_enabled;
        }
        
        if (!$accrual_enabled) {
            return 0.0;
        }
        
        // Get employee join date to calculate accrual
        $employee = $this->db->where('user_id', $user_id)->get('employees')->row();
        if (!$employee || empty($employee->join_date)) {
            return 0.0;
        }
        
        // PHP 5.6+ compatible date handling
        try {
            $join_date = new DateTime($employee->join_date);
            $year_start = new DateTime($year . '-01-01');
            $year_end = new DateTime($year . '-12-31');
            
            // If employee joined after year start, calculate from join date
            if ($join_date > $year_start) {
                $start_date = $join_date;
            } else {
                $start_date = $year_start;
            }
            
            // Calculate months worked in the year
            $months_worked = 0;
            if (class_exists('DateInterval')) {
                $diff = $start_date->diff($year_end);
                $months_worked = ($diff->y * 12) + $diff->m;
                // Add partial month if days > 0
                if ($diff->d > 0) {
                    $months_worked += ($diff->d / 30); // Approximate
                }
            } else {
                // Fallback for older PHP
                $start_timestamp = $start_date->getTimestamp();
                $end_timestamp = $year_end->getTimestamp();
                $months_worked = (($end_timestamp - $start_timestamp) / 2592000); // Approximate seconds per month
            }
            
            // Get monthly accrual rate
            $monthly_accrual = 0.0;
            if ($this->db->field_exists('monthly_accrual', 'leave_types') && isset($type->monthly_accrual)) {
                $monthly_accrual = (float)$type->monthly_accrual;
            } elseif (isset($type->annual_quota)) {
                // Calculate monthly from annual quota
                $monthly_accrual = (float)$type->annual_quota / 12;
            }
            
            $accrued = $monthly_accrual * $months_worked;
            
            return round($accrued, 2);
        } catch (Exception $e) {
            log_message('error', 'Leave accrual calculation error: ' . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Initialize leave balance for a user and year
     * PHP 5.6+ compatible
     * 
     * @param int $user_id
     * @param int $leave_type_id
     * @param int $year
     * @return bool
     */
    public function initialize_leave_balance($user_id, $leave_type_id, $year = null) {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $user_id = (int)$user_id;
        $leave_type_id = (int)$leave_type_id;
        $year = (int)$year;
        
        // Check if balance already exists
        $existing = $this->db->get_where('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
        ])->row();
        
        if ($existing) {
            return true; // Already initialized
        }
        
        // Get base allocation
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
        
        // Calculate accrual if enabled
        $accrued = $this->calculate_leave_accrual($user_id, $leave_type_id, $year);
        
        // Use transaction
        $this->db->trans_start();
        
        $this->db->insert('leave_balances', [
            'user_id' => $user_id,
            'type_id' => $leave_type_id,
            'year' => $year,
            'opening_balance' => $base,
            'accrued' => $accrued,
            'used' => 0.0,
            'closing_balance' => $base + $accrued,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Leave balance initialization failed for user: ' . $user_id);
            return false;
        }
        
        return $this->db->affected_rows() > 0;
    }
}
