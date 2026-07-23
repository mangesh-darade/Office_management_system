<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_requests extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','email']);
        $this->load->helper(['url','form','workday','group_filter','hierarchy_filter','company','permission','schema_columns','leave_requests_notify']);
        $this->load->model('Leave_request_model','leaves');
        
        // RBAC Audit: Centralized module access check
        require_module_access(['leave_requests', 'leave_team', 'leave_approve', 'leave_calendar', 'leave_view_all', 'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete'], true);
    }

    // GET/POST /leave/apply
    public function apply(){
        require_module_access(['leaves_add', 'leave_requests', 'leaves'], true);
        $user_id = (int)$this->session->userdata('user_id');
        // Read leave types (only active ones)
        $this->db->order_by('name','ASC');
        if (schema_table_has_column($this->db, 'leave_types', 'status')) {
            $this->db->where('status', STATUS_ACTIVE);
        }
        $types = $this->db->get('leave_types')->result();

        if ($this->input->method() === 'post'){
            $type_id = (int)$this->input->post('type_id');
            $mode = $this->input->post('mode');
            $mode = ($mode === 'specific') ? 'specific' : 'range';
            $reason = trim((string)$this->input->post('reason'));

            if (!$type_id){
                $this->session->set_flashdata('error', 'Please select a leave type.');
                redirect('leave/apply');
                return;
            }
            
            // Validate Lead selection (Admin is optional)
            $selected_lead_id = $this->input->post('selected_lead_id') ? (int)$this->input->post('selected_lead_id') : null;
            $selected_admin_id = $this->input->post('selected_admin_id') ? (int)$this->input->post('selected_admin_id') : null;
            
            if (!$selected_lead_id) {
                $this->session->set_flashdata('error', 'Please select a Lead.');
                redirect('leave/apply');
                return;
            }
            
            // Check if selected leave type is "Work From Home" / WFH
            $leave_type = $this->db->select('name')->from('leave_types')->where('id', $type_id)->get()->row();
            $is_wfh = false;
            if ($leave_type && leave_type_name_is_wfh($leave_type->name)) {
                $is_wfh = true;
                // Prefix reason with WFH marker
                $reason = 'WFH: ' . ($reason ? $reason : 'Work From Home Request');
            }

            if ($mode === 'specific') {
                $dates_post = $this->input->post('dates');
                if (!is_array($dates_post)) { $dates_post = []; }
                $unique = [];
                foreach ($dates_post as $d) {
                    $d = trim((string)$d);
                    if ($d === '') continue;
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;
                    $unique[$d] = $d;
                }
                if (empty($unique)) {
                    $this->session->set_flashdata('error', 'Please select at least one date.');
                    redirect('leave/apply');
                    return;
                }

                // Check for existing leave requests on selected dates
                $existing_dates = [];
                if (!empty($unique)) {
                    $existing_leaves = $this->db->select('start_date, end_date, status')
                                              ->from('leave_requests')
                                              ->where('user_id', $user_id)
                                              ->where('status !=', 'rejected') // Don't check rejected leaves
                                              ->where_in('start_date', array_keys($unique))
                                              ->get()
                                              ->result();
                    
                    foreach ($existing_leaves as $existing) {
                        $existing_dates[] = $existing->start_date;
                    }
                }
                
                if (!empty($existing_dates)) {
                    $date_list = implode(', ', array_map(function($date) {
                        return date('M d, Y', strtotime($date));
                    }, $existing_dates));
                    $this->session->set_flashdata('error', 'You already have leave requests for the following dates: ' . $date_list . '. Please select different dates or check your existing requests.');
                    redirect('leave/apply');
                    return;
                }

                $perDateDays = [];
                $days = 0.0;
                foreach ($unique as $d){
                    $wd = (float) workdays_between($d, $d);
                    if ($wd <= 0) { continue; }
                    $perDateDays[$d] = $wd;
                    $days += $wd;
                }
                if ($days <= 0){
                    $this->session->set_flashdata('error', 'Selected dates contain no working days.');
                    redirect('leave/apply');
                    return;
                }

                // Check leave balance only if not WFH
                if (!$is_wfh) {
                    $bal = $this->leaves->get_leave_balance($user_id, $type_id);
                    if ($bal && isset($bal->available) && (float)$bal->available < $days){
                        $error_msg = get_notification_message('leave_requests', 'insufficient_balance', 'error', ['available' => (float)$bal->available]);
                        $this->session->set_flashdata('error', $error_msg);
                        redirect('leave/apply');
                        return;
                    }
                }

                // Use Lead as approver (already validated above)
                $approver_id = $selected_lead_id;

                // Ensure manager_id column exists in leave_requests table
                if ($this->db->table_exists('leave_requests') && !schema_table_has_column($this->db, 'leave_requests', 'manager_id')) {
                    $this->db->query("ALTER TABLE leave_requests ADD COLUMN manager_id BIGINT UNSIGNED NULL AFTER current_approver_id");
                }

                // Use transaction for data integrity
                $this->db->trans_start();

                $leave_ids = [];
                $this->load->model('Approval_model');

                foreach ($perDateDays as $d => $wd) {
                    $data = [
                        'user_id' => $user_id,
                        'type_id' => $type_id,
                        'start_date' => $d,
                        'end_date' => $d,
                        'days' => $wd,
                        'reason' => $reason,
                        'status' => 'pending',
                        'current_approver_id' => $approver_id, // Legacy field, usage may change
                        'manager_id' => $selected_admin_id, // Store manager/admin ID
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $leave_id = $this->leaves->apply_leave($data);
                    
                    if ($leave_id) {
                        $leave_ids[] = $leave_id;
                        
                        // Initiate Approval Workflow
                        $approval = $this->Approval_model->initiate_approval('leave', $leave_id, $user_id);
                        
                        // If auto-approved (no flow linked) or first step defined
                        if ($approval && isset($approval['status'])) {
                            if ($approval['status'] === 'approved') {
                                // Auto-approve logic
                                $this->leaves->update_status($leave_id, 'approved'); 
                            }
                            // If pending, the Approval_model created a request. 
                            // Only update 'current_approver_id' in leave table if we want to sync it. 
                            // For now, let's keep status as 'pending'.
                        }
                    } else {
                        // Rollback on failure
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Failed to create leave request. Please try again.');
                        redirect('leave/apply');
                        return;
                    }
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    log_message('error', 'Leave application transaction failed for user: ' . $user_id);
                    $error_msg = get_notification_message('leave_requests', 'create', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                    redirect('leave/apply');
                    return;
                }

                // Send email notifications after all leave requests are created
                if (!empty($leave_ids)) {
                    try {
                    leave_requests_notify_applied($leave_ids, $user_id, $type_id, $selected_lead_id, $selected_admin_id);
                    } catch (Exception $e) {
                        log_message('error', 'Leave notification error: ' . $e->getMessage());
                        // Don't fail the request if notification fails
                    }
                }

                $success_msg = get_notification_message('leave_requests', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('leave/my');
                return;
            }

            // Range mode
            $start_date = $this->input->post('start_date');
            $end_date   = $this->input->post('end_date');
            $duration_type = $this->input->post('duration_type');
            $duration_type = ($duration_type === 'half') ? 'half' : 'full';

            // Enhanced validation using validation helper
            $this->load->helper('validation');
            
            if (!$start_date || !$end_date){
                $this->session->set_flashdata('error', 'Please select type and date range.');
                redirect('leave/apply');
                return;
            }
            
            // Validate date formats
            $start_validation = validate_date($start_date);
            $end_validation = validate_date($end_date);
            
            if (!$start_validation['valid']) {
                $this->session->set_flashdata('error', 'Invalid start date format.');
                redirect('leave/apply');
                return;
            }
            
            if (!$end_validation['valid']) {
                $this->session->set_flashdata('error', 'Invalid end date format.');
                redirect('leave/apply');
                return;
            }
            
            // PHP 5.6+ compatible date comparison
            if (class_exists('DateTime') && $start_validation['date'] instanceof DateTime && $end_validation['date'] instanceof DateTime) {
                if ($end_validation['date'] < $start_validation['date']) {
                    $this->session->set_flashdata('error', 'End date cannot be before start date.');
                    redirect('leave/apply');
                    return;
                }
            } else {
                // Fallback for older PHP
                if (strtotime($end_date) < strtotime($start_date)){
                    $this->session->set_flashdata('error', 'End date cannot be before start date.');
                    redirect('leave/apply');
                    return;
                }
            }

            // Calculate working days
            $days = (float) workdays_between($start_date, $end_date);
            if ($duration_type === 'half') {
                if ($start_date !== $end_date) {
                    $this->session->set_flashdata('error', 'Half-day leave is allowed only for a single date.');
                    redirect('leave/apply');
                    return;
                }
                if ($days > 0) {
                    $days = 0.5;
                }
            }
            if ($days <= 0){
                $this->session->set_flashdata('error', 'Selected range contains no working days.');
                redirect('leave/apply');
                return;
            }

            // Check for overlapping leave requests in the selected date range
            $overlapping_leaves = $this->db->select('start_date, end_date, status')
                                         ->from('leave_requests')
                                         ->where('user_id', $user_id)
                                         ->where('status !=', 'rejected') // Don't check rejected leaves
                                         ->where('(start_date <= ' . $this->db->escape($end_date) . ' AND end_date >= ' . $this->db->escape($start_date) . ')')
                                         ->get()
                                         ->result();
            
            if (!empty($overlapping_leaves)) {
                $conflict_dates = [];
                foreach ($overlapping_leaves as $overlap) {
                    $conflict_dates[] = date('M d, Y', strtotime($overlap->start_date)) . 
                                       ($overlap->start_date !== $overlap->end_date ? 
                                        ' - ' . date('M d, Y', strtotime($overlap->end_date)) : '');
                }
                $conflict_list = implode(', ', $conflict_dates);
                $this->session->set_flashdata('error', 'You already have leave requests that overlap with your selected dates: ' . $conflict_list . '. Please select different dates or check your existing requests.');
                redirect('leave/apply');
                return;
            }

            // Check leave balance only if not WFH
            if (!$is_wfh) {
                $bal = $this->leaves->get_leave_balance($user_id, $type_id);
                if ($bal && isset($bal->available) && (float)$bal->available < $days){
                    $this->session->set_flashdata('error', 'Insufficient balance for this leave type. Available: '.(float)$bal->available);
                    redirect('leave/apply');
                    return;
                }
            }

            // Use Lead as approver (already validated above)
            $approver_id = $selected_lead_id;

            // Ensure manager_id column exists in leave_requests table
            if ($this->db->table_exists('leave_requests') && !schema_table_has_column($this->db, 'leave_requests', 'manager_id')) {
                $this->db->query("ALTER TABLE leave_requests ADD COLUMN manager_id BIGINT UNSIGNED NULL AFTER current_approver_id");
            }

            $data = [
                'user_id' => $user_id,
                'type_id' => $type_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
                'current_approver_id' => $approver_id, // Legacy
                'manager_id' => $selected_admin_id, // Store manager/admin ID
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $id = $this->leaves->apply_leave($data);

            if ($id) {
                // Initiate Approval Workflow
                $this->load->model('Approval_model');
                $approval = $this->Approval_model->initiate_approval('leave', $id, $user_id);
                
                if ($approval && isset($approval['status']) && $approval['status'] === 'approved') {
                    $this->leaves->update_status($id, 'approved');
                }
                
                // Send email notifications
                leave_requests_notify_applied([$id], $user_id, $type_id, $selected_lead_id, $selected_admin_id);
            }

            $this->session->set_flashdata('success', 'Leave request submitted.');
            redirect('leave/my');
            return;
        }

        // Preload balances by type for hinting
        $balances = [];
        foreach ($types as $t){
            $b = $this->leaves->get_leave_balance($user_id, (int)$t->id);
            $balances[(int)$t->id] = $b ? $b->available : 0;
        }

        // Get Lead users (role_id = 3)
        $this->db->select('u.id, u.name, u.email');
        $this->db->from('users u');
        $this->db->where('u.role_id', 3); // Lead role
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('u.status', 'active');
        }
        $this->db->order_by('u.name', 'ASC');
        $leads = $this->db->get()->result();

        // Get Admin users (role_id = 1)
        $this->db->select('u.id, u.name, u.email');
        $this->db->from('users u');
        $this->db->where('u.role_id', 1); // Admin role
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('u.status', 'active');
        }
        $this->db->order_by('u.name', 'ASC');
        $admins = $this->db->get()->result();

        // Get holidays from database
        $holidays = [];
        if ($this->db->table_exists('holidays')) {
            $holiday_rows = $this->db->select('holiday_date')
                                    ->from('holidays')
                                    ->where('status', 'active')
                                    ->get()
                                    ->result();
            foreach ($holiday_rows as $h) {
                $holidays[] = $h->holiday_date;
            }
        }

        // Get weekend settings (default: Saturday=6, Sunday=0)
        $this->load->model('Setting_model', 'settings');
        $weekend_setting = $this->settings->get_setting('attendance_weekends', '0,6');
        $weekend_days = explode(',', $weekend_setting);
        $weekend_days = array_map('trim', $weekend_days);
        $weekend_days = array_map('intval', $weekend_days);

        // Get timezone from settings (default: Asia/Kolkata)
        $timezone = $this->settings->get_setting('company_timezone', 'Asia/Kolkata');
        if (empty($timezone)) {
            $timezone = 'Asia/Kolkata';
        }
        
        // Set timezone and get today's date
        date_default_timezone_set($timezone);
        $today_date = date('Y-m-d');

        $this->load->view('leave_requests/apply', [
            'types' => $types,
            'balances' => $balances,
            'leads' => $leads,
            'admins' => $admins,
            'holidays' => $holidays,
            'weekend_days' => $weekend_days,
            'today_date' => $today_date,
            'timezone' => $timezone,
        ]);
    }

    // GET /leave/my
    public function my(){
        require_module_access(['leaves_list', 'leave_requests', 'leaves'], true);
        $user_id = (int)$this->session->userdata('user_id');
        $filters = [
            'status' => trim((string)$this->input->get('status')),
            'start_date' => $this->input->get('from'),
            'end_date' => $this->input->get('to'),
        ];
        
        // Get leaves with comments from approvals
        $this->db->select('lr.*, lt.name AS type_name, 
                          (SELECT la.remarks FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS comments,
                          (SELECT la.decision FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS decision,
                          (SELECT u.name FROM leave_approvals la JOIN users u ON u.id = la.approver_id WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS approver_name')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->where('lr.user_id', $user_id);
        apply_role_hierarchy_filter($this->db, 'lr.user_id');
        
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
        $rows = $this->db->get()->result();
        
        $this->load->view('leave_requests/my', [
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    // GET/POST /leave/team - List team leaves for managers/leads with approve/reject actions
    public function team(){
        require_module_access(['leave_team', 'leave_requests', 'leaves'], true);

        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');

        // 1. Get Actionable Leaves (where current user is the approver)
        $this->load->model('Approval_model');
        $pending_reqs = $this->Approval_model->get_pending_requests_for_user($user_id);
        $actionable_ids = [];
        $approval_request_map = []; // Map leave_id -> approval_request_id
        foreach ($pending_reqs as $pr) {
            if ($pr->module === 'leave') {
                $actionable_ids[] = (int)$pr->module_record_id;
                $approval_request_map[(int)$pr->module_record_id] = (int)$pr->id;
            }
        }

        // Select query with lead + latest approval comments
        $this->db->select('lr.*, lr.user_id, lt.name AS type_name, 
                          u.email AS user_email, u.name AS user_name, u.role_id AS user_role_id,
                          e.department AS emp_department, e.first_name AS emp_first_name, e.last_name AS emp_last_name,
                          lead_u.name AS lead_name, lead_u.email AS lead_email,
                          (SELECT la.remarks FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS latest_remarks,
                          (SELECT la.decision FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS latest_decision,
                          (SELECT au.name FROM leave_approvals la JOIN users au ON au.id = la.approver_id WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS latest_approver_name')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->join('users u', 'u.id = lr.user_id', 'left') // Applied user
                 ->join('employees e', 'e.user_id = lr.user_id', 'left') // Applied user employee info
                 ->join('users lead_u', 'lead_u.id = lr.current_approver_id', 'left'); // Selected Lead
        apply_role_hierarchy_filter($this->db, 'lr.user_id');
        
        // For Manager role, add department join if needed
        if (has_role('manager') && $this->db->table_exists('departments')) {
            $this->db->join('departments d', 'd.dept_name = e.department AND d.status = "active"', 'left');
        }
        
        // Build where conditions based on role
        if (is_admin_group()) {
            // Admin: Always show all leave requests
        } elseif (has_role('lead')) {
            // Lead: Show leaves where they are actionable OR they are the assigned legacy lead
            $this->db->group_start();
            if (!empty($actionable_ids)) {
                 $this->db->where_in('lr.id', $actionable_ids);
            }
            $this->db->or_where('lr.current_approver_id', $user_id); // Legacy
            $this->db->or_where('u.role_id', 1); // View admin leaves
            $this->db->group_end();
        } elseif (has_role('manager')) {
            // Manager: Show actionable OR department leaves
            $conditions = [];
            
            if (!empty($actionable_ids)) {
                $conditions[] = 'lr.id IN (' . implode(',', $actionable_ids) . ')';
            }

            // Leaves assigned to this manager (if manager_id field exists)
            if (schema_table_has_column($this->db, 'leave_requests', 'manager_id')) {
                $conditions[] = 'lr.manager_id = ' . (int)$user_id;
            }
            
            $conditions[] = 'u.role_id = 1';
            
            if ($this->db->table_exists('departments')) {
                $conditions[] = '(d.manager_id = ' . (int)$user_id . ' AND e.department IS NOT NULL)';
            }
            
            if (!empty($conditions)) {
                $this->db->where('(' . implode(' OR ', $conditions) . ')', null, false);
            }
        }
        
        // Optional filters
        $status = trim((string)$this->input->get('status'));
        if ($status !== '') { $this->db->where('lr.status', $status); }
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        if ($from) { $this->db->where('lr.start_date >=', $from); }
        if ($to) { $this->db->where('lr.end_date <=', $to); }
        
        // Force actionable items to top if status is blank or pending
        $this->db->order_by('lr.status', 'ASC'); // Pending first usually
        $this->db->order_by('lr.start_date','DESC');
        
        $rows = $this->db->get()->result();

        // Attach full approval history (lead / manager / HR comments)
        $approval_by_leave = array();
        if (!empty($rows) && $this->db->table_exists('leave_approvals')) {
            $leave_ids = array();
            foreach ($rows as $row) {
                $leave_ids[] = (int) $row->id;
            }
            $leave_ids = array_values(array_unique(array_filter($leave_ids)));
            if (!empty($leave_ids)) {
                $this->db->select('la.leave_id, la.decision, la.remarks, la.decided_at, la.approver_id, au.name AS approver_name, au.email AS approver_email')
                         ->from('leave_approvals la')
                         ->join('users au', 'au.id = la.approver_id', 'left')
                         ->where_in('la.leave_id', $leave_ids)
                         ->order_by('la.decided_at', 'ASC')
                         ->order_by('la.id', 'ASC');
                foreach ($this->db->get()->result() as $log) {
                    $lid = (int) $log->leave_id;
                    if (!isset($approval_by_leave[$lid])) {
                        $approval_by_leave[$lid] = array();
                    }
                    $remarks = isset($log->remarks) ? trim((string) $log->remarks) : '';
                    $decision = isset($log->decision) ? strtolower(trim((string) $log->decision)) : '';
                    // Ignore legacy bug rows that stored decision word as remarks with no real approver
                    if ((int) $log->approver_id <= 0 && $remarks !== '' && strcasecmp($remarks, $decision) === 0) {
                        continue;
                    }
                    if ($remarks !== '' && $decision !== '' && strcasecmp($remarks, $decision) === 0) {
                        $remarks = '';
                    }
                    $approval_by_leave[$lid][] = (object) array(
                        'decision' => $decision,
                        'remarks' => $remarks,
                        'decided_at' => isset($log->decided_at) ? $log->decided_at : '',
                        'approver_name' => !empty($log->approver_name) ? $log->approver_name : (!empty($log->approver_email) ? $log->approver_email : 'Approver'),
                    );
                }
            }
        }

        // Inject actionable flag, approval_request_id, and comment history
        foreach ($rows as &$r) {
            $r->is_actionable = in_array((int) $r->id, $actionable_ids);
            $r->approval_request_id = isset($approval_request_map[$r->id]) ? $approval_request_map[$r->id] : null;
            $r->approval_history = isset($approval_by_leave[(int) $r->id]) ? $approval_by_leave[(int) $r->id] : array();
        }
        unset($r);

        $this->load->view('leave_requests/team', [
            'rows' => $rows,
            'filters' => ['status'=>$status,'from'=>$from,'to'=>$to],
            'is_admin' => is_admin_group(),
        ]);
    }

    // POST /leave/approve/{id}
    public function approve($id){
        require_module_access(['leave_approve', 'leave_requests', 'leaves'], true);
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');
        $this->load->helper('rewards_automation');

        $this->load->model('Approval_model');
        
        // Check for Workflow
        $approval_req = $this->Approval_model->get_pending_request('leave', $id);
        
        if ($approval_req) {
            // Use Workflow Engine
            // Check if user is allowed to approve this specific request
            if (!$this->Approval_model->can_user_approve($approval_req->id, $approved_by)) {
                $this->session->set_flashdata('error', 'You are not the designated approver for the current step.');
                redirect('leave/team');
                return;
            }

            $result = $this->Approval_model->approve_request($approval_req->id, $approved_by, $comments);
            
            if ($result === 'approved') {
                // Final Approval
                $this->leaves->update_status($id, 'approved');
                $this->leaves->add_approval_log($id, 'approved', $comments, $approved_by); // Keep legacy log
                leave_requests_notify_change($id, 'approved', $comments, $approved_by);
                rewards_automation_on_leave_approved($this->db, $id, $approved_by);
            } elseif ($result === 'pending_next_approval') {
                // Moved to next step — keep leave visible as lead_approved and notify thread
                $this->leaves->update_status($id, 'lead_approved');
                $this->leaves->add_approval_log($id, 'approved', $comments, $approved_by);
                leave_requests_notify_change($id, 'step_approved', $comments, $approved_by);
            }
        } else {
            // Legacy Logic
            $ok = $this->leaves->approve_reject_leave($id, 'lead_approved', $comments, $approved_by);
            leave_requests_notify_change($id, 'approved', $comments, $approved_by);
            rewards_automation_on_leave_approved($this->db, $id, $approved_by);
        }

        $success_msg = get_notification_message('leave_requests', 'approve', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    // POST /leave/reject/{id}
    public function reject($id){
        require_module_access(['leave_approve', 'leave_requests', 'leaves'], true);
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');
        $this->load->helper('rewards_automation');

        $this->load->model('Approval_model');
        
        // Check for Workflow
        $approval_req = $this->Approval_model->get_pending_request('leave', $id);

        if ($approval_req) {
            // Use Workflow Engine
             if (!$this->Approval_model->can_user_approve($approval_req->id, $approved_by)) {
                $this->session->set_flashdata('error', 'You are not the designated approver.');
                redirect('leave/team');
                return;
            }
            
            $this->Approval_model->reject_request($approval_req->id, $approved_by, $comments);
            $this->leaves->update_status($id, 'rejected');
            $this->leaves->add_approval_log($id, 'rejected', $comments, $approved_by);
            leave_requests_notify_change($id, 'rejected', $comments, $approved_by);
            rewards_automation_on_leave_rejected($this->db, $id, $approved_by);

        } else {
            // Legacy Logic
            $ok = $this->leaves->approve_reject_leave($id, 'rejected', $comments, $approved_by);
            leave_requests_notify_change($id, 'rejected', $comments, $approved_by);
            rewards_automation_on_leave_rejected($this->db, $id, $approved_by);
        }

        $success_msg = get_notification_message('leave_requests', 'reject', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    // GET /leave/calendar
    public function calendar(){
        require_module_access(['leave_calendar', 'leave_requests', 'leaves'], true);
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');

        $ym = $this->input->get('month'); // format YYYY-MM
        if (!$ym) { $ym = date('Y-m'); }
        $from = $ym.'-01';
        $to = date('Y-m-t', strtotime($from));

        // Admins see all leave requests; department managers see only their department employees
        $restrict_to_team = !is_admin_group() && !has_module_access('leave_view_all');

        $this->db->select('lr.*, u.email AS user_email, lt.name AS type_name, e.department AS emp_department')
                 ->from('leave_requests lr')
                 ->join('users u','u.id = lr.user_id','left')
                 ->join('leave_types lt','lt.id = lr.type_id','left')
                 ->join('employees e', 'e.user_id = lr.user_id', 'left')
                 ->where('lr.start_date <=', $to)
                 ->where('lr.end_date >=', $from);
        apply_role_hierarchy_filter($this->db, 'lr.user_id');

        if ($restrict_to_team) {
            // Department managers see leaves of employees in their department
            if ($this->db->table_exists('departments')) {
                // Find departments where current user is the manager
                $this->db->join('departments d', 'd.dept_name = e.department AND d.status = "active"', 'left');
                $this->db->where('d.manager_id', $user_id);
                $this->db->where('e.department IS NOT NULL');
            } else {
                // Fallback: show empty set if departments table doesn't exist
                $this->db->where('1=0', null, false);
            }
        }
        $rows = $this->db->get()->result();

        $this->load->view('leave_requests/calendar', [
            'month' => $ym,
            'rows' => $rows,
        ]);
    }

    // GET/POST /leave/edit/{id} - Edit leave request (Admin or with leaves_edit permission)
    public function edit($id){
        require_module_access(['leaves_edit', 'leave_requests', 'leaves'], true);
        
        $id = (int)$id;
        $leave = $this->db->get_where('leave_requests', ['id' => $id])->row();
        if (!$leave) { show_404(); }
        
        if ($this->input->method() === 'post'){
            $type_id = (int)$this->input->post('type_id');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');
            $days = $this->input->post('days') !== '' ? (float)$this->input->post('days') : null;
            $reason = trim((string)$this->input->post('reason'));
            $status = trim((string)$this->input->post('status'));
            $this->load->helper('module_status');
            $status = module_status_sanitize($status, 'leaves', (string) $leave->status);
            if ($status === false) {
                $this->session->set_flashdata('error', 'Invalid leave status selected.');
                redirect('leave/edit/'.$id);
                return;
            }
            
            // Validation
            if (!$type_id || !$start_date || !$end_date) {
                $this->session->set_flashdata('error', 'Please fill all required fields.');
                redirect('leave/edit/'.$id);
                return;
            }
            
            if (strtotime($end_date) < strtotime($start_date)){
                $this->session->set_flashdata('error', 'End date cannot be before start date.');
                redirect('leave/edit/'.$id);
                return;
            }
            
            // Update leave request
            $data = [
                'type_id' => $type_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days,
                'reason' => $reason,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $this->db->where('id', $id)->update('leave_requests', $data);
            $this->load->helper('activity');
            log_activity('leave_requests', 'updated', $id, 'Leave request updated by admin');
            $success_msg = get_notification_message('leave_requests', 'update', 'success');
            $this->session->set_flashdata('success', $success_msg);
            redirect('leave/team');
            return;
        }
        
        // Load leave types for dropdown (only active ones)
        $this->db->order_by('name','ASC');
        if (schema_table_has_column($this->db, 'leave_types', 'status')) {
            $this->db->where('status', STATUS_ACTIVE);
        }
        $types = $this->db->get('leave_types')->result();
        $this->load->view('leave_requests/edit', [
            'leave' => $leave,
            'types' => $types,
        ]);
    }
    
    // POST /leave/delete/{id} - Delete leave request (Admin or with leaves_delete permission)
    public function delete($id){
        require_module_access(['leaves_delete', 'leave_requests', 'leaves'], true);
        if ($this->input->method() !== 'post') { show_404(); }
        
        $id = (int)$id;
        $leave = $this->db->get_where('leave_requests', ['id' => $id])->row();
        if (!$leave) { show_404(); }
        
        // Check if this is a WFH request and if it was approved, remove WFH attendance records
        $is_wfh = false;
        if (isset($leave->reason) && strpos($leave->reason, 'WFH:') === 0) {
            $is_wfh = true;
        } else {
            $leave_type = $this->db->select('name')->from('leave_types')->where('id', (int)$leave->type_id)->get()->row();
            if ($leave_type && leave_type_name_is_wfh($leave_type->name)) {
                $is_wfh = true;
            }
        }
        
        // If WFH was approved, remove attendance records
        if ($is_wfh && in_array($leave->status, ['lead_approved', 'hr_approved'], true)) {
            $this->load->model('Leave_request_model', 'leaves');
            // Use reflection or make method public/protected, or call directly
            // For now, we'll handle it inline
            $dateCol = 'att_date';
            $statusCol = 'status';
            if (!schema_table_has_column($this->db, 'attendance', $dateCol)) {
                $dateCol = 'date';
            }
            if (schema_table_has_column($this->db, 'attendance', $statusCol)) {
                $startTs = strtotime($leave->start_date);
                $endTs = strtotime($leave->end_date);
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
                if (!empty($dates)) {
                    $this->db->where('user_id', (int)$leave->user_id)
                             ->where($statusCol, 'work_from_home')
                             ->where_in($dateCol, $dates)
                             ->delete('attendance');
                }
            }
        }
        
        // Delete leave request
        $this->db->where('id', $id)->delete('leave_requests');
        $this->load->helper('activity');
        log_activity('leave_requests', 'deleted', $id, 'Leave request deleted by admin');
        $success_msg = get_notification_message('leave_requests', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    // AJAX endpoint to get employee tasks (excluding completed)
    public function get_employee_tasks($user_id = null) {
        header('Content-Type: application/json');
        
        $user_id = (int)$user_id;
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        // Check if tasks table exists
        if (!$this->db->table_exists('tasks')) {
            echo json_encode(['success' => false, 'message' => 'Tasks table not found']);
            exit;
        }

        // Fetch tasks for the employee, excluding completed status
        $this->db->select('t.*, p.name AS project_name')
                 ->from('tasks t')
                 ->join('projects p', 'p.id = t.project_id', 'left')
                 ->where('t.assigned_to', $user_id)
                 ->where('t.status !=', 'completed')
                 ->order_by('t.priority', 'DESC')
                 ->order_by('t.due_date', 'ASC')
                 ->order_by('t.created_at', 'DESC');
        
        $tasks = $this->db->get()->result();

        // Format tasks for display
        $formatted_tasks = [];
        foreach ($tasks as $task) {
            $formatted_tasks[] = [
                'id' => (int)$task->id,
                'title' => esc_view($task->title),
                'description' => !empty($task->description) ? esc_view($task->description) : 'No description',
                'project_name' => !empty($task->project_name) ? esc_view($task->project_name) : 'No Project',
                'status' => esc_view($task->status),
                'priority' => esc_view($task->priority),
                'start_date' => $task->start_date ? date('M d, Y', strtotime($task->start_date)) : 'Not set',
                'due_date' => $task->due_date ? date('M d, Y', strtotime($task->due_date)) : 'Not set',
                'estimate_hours' => $task->estimate_hours ? number_format((float)$task->estimate_hours, 1) : 'N/A',
                'actual_hours' => $task->actual_hours ? number_format((float)$task->actual_hours, 1) : 'N/A',
            ];
        }

        echo json_encode([
            'success' => true,
            'tasks' => $formatted_tasks,
            'count' => count($formatted_tasks)
        ]);
        exit;
    }
}
