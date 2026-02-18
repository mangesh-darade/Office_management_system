<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_requests extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','email']);
        $this->load->helper(['url','form','workday','group_filter','company','permission']);
        $this->load->model('Leave_request_model','leaves');
        // Require login
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        // Require leave_requests permission when permission system is in use
        if (function_exists('has_module_access') && !has_module_access('leave_requests')) {
            show_error('You do not have permission to access Leave Management.', 403);
        }
    }

    // GET/POST /leave/apply
    public function apply(){
        $user_id = (int)$this->session->userdata('user_id');
        // Read leave types (only active ones)
        $this->db->order_by('name','ASC');
        if ($this->db->field_exists('status', 'leave_types')) {
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
            
            // Check if selected leave type is "Work From Home"
            $leave_type = $this->db->select('name')->from('leave_types')->where('id', $type_id)->get()->row();
            $is_wfh = false;
            if ($leave_type && strtolower(trim($leave_type->name)) === 'work from home') {
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
                        $this->load->helper('notification');
                        $error_msg = get_notification_message('leave_requests', 'insufficient_balance', 'error', ['available' => (float)$bal->available]);
                        $this->session->set_flashdata('error', $error_msg);
                        redirect('leave/apply');
                        return;
                    }
                }

                // Use Lead as approver (already validated above)
                $approver_id = $selected_lead_id;

                // Ensure manager_id column exists in leave_requests table
                if ($this->db->table_exists('leave_requests') && !$this->db->field_exists('manager_id', 'leave_requests')) {
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
                    $this->load->helper('notification');
                    $error_msg = get_notification_message('leave_requests', 'create', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                    redirect('leave/apply');
                    return;
                }

                // Send email notifications after all leave requests are created
                if (!empty($leave_ids)) {
                    try {
                    $this->_notify_leave_applied($leave_ids, $user_id, $type_id, $selected_lead_id, $selected_admin_id);
                    } catch (Exception $e) {
                        log_message('error', 'Leave notification error: ' . $e->getMessage());
                        // Don't fail the request if notification fails
                    }
                }

                $this->load->helper('notification');
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
            if ($this->db->table_exists('leave_requests') && !$this->db->field_exists('manager_id', 'leave_requests')) {
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
                $this->_notify_leave_applied([$id], $user_id, $type_id, $selected_lead_id, $selected_admin_id);
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
        if ($this->db->field_exists('status', 'users')) {
            $this->db->where('u.status', 'active');
        }
        $this->db->order_by('u.name', 'ASC');
        $leads = $this->db->get()->result();

        // Get Admin users (role_id = 1)
        $this->db->select('u.id, u.name, u.email');
        $this->db->from('users u');
        $this->db->where('u.role_id', 1); // Admin role
        if ($this->db->field_exists('status', 'users')) {
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
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $has_team_perm = function_exists('has_module_access') && has_module_access('leave_team');
        if (!in_array($role_id, [1,2,3], true) && !$has_team_perm) { show_error('Forbidden', 403); }

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

        // Select query with lead information
        $this->db->select('lr.*, lr.user_id, lt.name AS type_name, 
                          u.email AS user_email, u.name AS user_name, u.role_id AS user_role_id,
                          e.department AS emp_department, e.first_name AS emp_first_name, e.last_name AS emp_last_name,
                          (SELECT la.remarks FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS latest_remarks')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->join('users u', 'u.id = lr.user_id', 'left') // Applied user
                 ->join('employees e', 'e.user_id = lr.user_id', 'left'); // Applied user employee info
        
        // For Manager role, add department join if needed
        if ($role_id === 2 && $this->db->table_exists('departments')) {
            $this->db->join('departments d', 'd.dept_name = e.department AND d.status = "active"', 'left');
        }
        
        // Build where conditions based on role
        if ($role_id === 1) {
            // Admin: Always show all leave requests
        } elseif ($role_id === 3) {
            // Lead: Show leaves where they are actionable OR they are the assigned legacy lead
            $this->db->group_start();
            if (!empty($actionable_ids)) {
                 $this->db->where_in('lr.id', $actionable_ids);
            }
            $this->db->or_where('lr.current_approver_id', $user_id); // Legacy
            $this->db->or_where('u.role_id', 1); // View admin leaves
            $this->db->group_end();
        } elseif ($role_id === 2) {
            // Manager: Show actionable OR department leaves
            $conditions = [];
            
            if (!empty($actionable_ids)) {
                $conditions[] = 'lr.id IN (' . implode(',', $actionable_ids) . ')';
            }

            // Leaves assigned to this manager (if manager_id field exists)
            if ($this->db->field_exists('manager_id', 'leave_requests')) {
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

        // Inject actionable flag and approval_request_id
        foreach ($rows as &$r) {
            $r->is_actionable = in_array((int)$r->id, $actionable_ids);
            $r->approval_request_id = isset($approval_request_map[$r->id]) ? $approval_request_map[$r->id] : null;
        }

        $this->load->view('leave_requests/team', [
            'rows' => $rows,
            'filters' => ['status'=>$status,'from'=>$from,'to'=>$to],
            'is_admin' => ($role_id === 1),
        ]);
    }

    // POST /leave/approve/{id}
    public function approve($id){
        $role_id = (int)$this->session->userdata('role_id');
        $has_approve_perm = function_exists('has_module_access') && has_module_access('leave_approve');
        if (!in_array($role_id, [1,2,3], true) && !$has_approve_perm) { show_error('Forbidden', 403); }
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');

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
                $this->_notify_leave_change($id, 'approved', $comments);
            } elseif ($result === 'pending_next_approval') {
                // Moved to next step
                // Ideally update 'current_approver_id' but complex to find who that is without logic
                $this->leaves->add_approval_log($id, 'step_approved', $comments, $approved_by);
            }
        } else {
            // Legacy Logic
            $ok = $this->leaves->approve_reject_leave($id, 'lead_approved', $comments, $approved_by);
            $this->_notify_leave_change($id, 'approved', $comments);
        }

        $this->load->helper('notification');
        $success_msg = get_notification_message('leave_requests', 'approve', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    // POST /leave/reject/{id}
    public function reject($id){
        $role_id = (int)$this->session->userdata('role_id');
        $has_approve_perm = function_exists('has_module_access') && has_module_access('leave_approve');
        if (!in_array($role_id, [1,2,3], true) && !$has_approve_perm) { show_error('Forbidden', 403); }
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');

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
            $this->_notify_leave_change($id, 'rejected', $comments);

        } else {
            // Legacy Logic
            $ok = $this->leaves->approve_reject_leave($id, 'rejected', $comments, $approved_by);
            $this->_notify_leave_change($id, 'rejected', $comments);
        }

        $this->load->helper('notification');
        $success_msg = get_notification_message('leave_requests', 'reject', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    // GET /leave/calendar
    public function calendar(){
        $role_id = (int)$this->session->userdata('role_id');
        $has_calendar_perm = function_exists('has_module_access') && has_module_access('leave_calendar');
        if (!in_array($role_id, [1,2,3], true) && !$has_calendar_perm) { show_error('Forbidden', 403); }
        $user_id = (int)$this->session->userdata('user_id');

        $ym = $this->input->get('month'); // format YYYY-MM
        if (!$ym) { $ym = date('Y-m'); }
        $from = $ym.'-01';
        $to = date('Y-m-t', strtotime($from));

        // Admins (role_id = 1) see all leave requests; department managers see only their department employees
        $restrict_to_team = ($role_id !== 1);

        $this->db->select('lr.*, u.email AS user_email, lt.name AS type_name, e.department AS emp_department')
                 ->from('leave_requests lr')
                 ->join('users u','u.id = lr.user_id','left')
                 ->join('leave_types lt','lt.id = lr.type_id','left')
                 ->join('employees e', 'e.user_id = lr.user_id', 'left')
                 ->where('lr.start_date <=', $to)
                 ->where('lr.end_date >=', $from);

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
        $role_id = (int)$this->session->userdata('role_id');
        $has_edit_perm = function_exists('has_module_access') && has_module_access('leaves_edit');
        if ($role_id !== 1 && !$has_edit_perm) { show_error('Forbidden - Admin only', 403); }
        
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
            $this->load->helper('notification');
            $success_msg = get_notification_message('leave_requests', 'update', 'success');
            $this->session->set_flashdata('success', $success_msg);
            redirect('leave/team');
            return;
        }
        
        // Load leave types for dropdown (only active ones)
        $this->db->order_by('name','ASC');
        if ($this->db->field_exists('status', 'leave_types')) {
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
        $role_id = (int)$this->session->userdata('role_id');
        $has_delete_perm = function_exists('has_module_access') && has_module_access('leaves_delete');
        if ($role_id !== 1 && !$has_delete_perm) { show_error('Forbidden - Admin only', 403); }
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
            if ($leave_type && strtolower(trim($leave_type->name)) === 'work from home') {
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
            if (!$this->db->field_exists($dateCol, 'attendance')) {
                $dateCol = 'date';
            }
            if ($this->db->field_exists($statusCol, 'attendance')) {
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
        $this->load->helper('notification');
        $success_msg = get_notification_message('leave_requests', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('leave/team');
    }

    /**
     * Send email notification when leave is applied
     * Sends to selected Lead and Admin
     * Consolidates multiple leave requests into a single email
     */
    private function _notify_leave_applied($leave_ids, $user_id, $type_id, $selected_lead_id, $selected_admin_id){
        if (empty($leave_ids)) return;
        
        // Debug: Log email consolidation
        error_log('Leave notification: Processing ' . count($leave_ids) . ' leave requests in single email');
        
        try {
            // Get leave request details
            $this->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                     ->from('leave_requests lr')
                     ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                     ->join('users u', 'u.id = lr.user_id', 'left')
                     ->where_in('lr.id', $leave_ids)
                     ->order_by('lr.start_date', 'ASC');
            $leaves = $this->db->get()->result();
            
            if (empty($leaves)) return;
            
            $first_leave = $leaves[0];
            $employee_name = !empty($first_leave->user_name) ? $first_leave->user_name : $first_leave->user_email;
            $leave_type = $first_leave->type_name;
            
            // Check if this is a WFH request
            $is_wfh = false;
            if (isset($first_leave->reason) && strpos($first_leave->reason, 'WFH:') === 0) {
                $is_wfh = true;
            } elseif (isset($leave_type) && strtolower(trim($leave_type)) === 'work from home') {
                $is_wfh = true;
            }
            
            // Build consolidated leave details
            $leave_details = [];
            $total_days = 0;
            foreach ($leaves as $l) {
                $date_str = '';
                if ($l->start_date === $l->end_date) {
                    $date_str = date('M d, Y', strtotime($l->start_date));
                } else {
                    $date_str = date('M d', strtotime($l->start_date)) . ' - ' . date('M d, Y', strtotime($l->end_date));
                }
                
                $leave_details[] = [
                    'date' => $date_str,
                    'days' => (float)$l->days,
                    'reason' => !empty($l->reason) ? $l->reason : 'No reason provided'
                ];
                $total_days += (float)$l->days;
            }
            
            $reason = !empty($first_leave->reason) ? $first_leave->reason : 'No reason provided';
            $request_count = count($leaves);
            
            // Get selected Lead email
            $lead_email = null;
            $lead_name = null;
            if ($selected_lead_id) {
                $lead = $this->db->select('email, name')->from('users')->where('id', $selected_lead_id)->get()->row();
                if ($lead && !empty($lead->email)) {
                    $lead_email = $lead->email;
                    $lead_name = !empty($lead->name) ? $lead->name : $lead->email;
                }
            }
            
            // Get selected Admin email
            $admin_email = null;
            $admin_name = null;
            if ($selected_admin_id) {
                $admin = $this->db->select('email, name')->from('users')->where('id', $selected_admin_id)->get()->row();
                if ($admin && !empty($admin->email)) {
                    $admin_email = $admin->email;
                    $admin_name = !empty($admin->name) ? $admin->name : $admin->email;
                }
            }
            
            // Load email helper and configure from settings
            $this->load->helper('email');
            configure_email_from_settings();
            
            $fromAddr = get_system_from_email();
            $fromName = get_company_name();
            
            // Build consolidated email content
            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            $subject = 'New ' . $request_type . ' - ' . $employee_name;
            if ($request_count > 1) {
                $subject .= ' (' . $request_count . ' requests)';
            }
            
            $message = '<html><body>';
            $message .= '<h3>New ' . htmlspecialchars($request_type) . '</h3>';
            $message .= '<p><strong>Employee:</strong> ' . htmlspecialchars($employee_name) . ' (' . htmlspecialchars($first_leave->user_email) . ')</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . htmlspecialchars($leave_type) . '</p>';
            $message .= '<p><strong>Total Requests:</strong> ' . $request_count . '</p>';
            $message .= '<p><strong>Total Days:</strong> ' . number_format($total_days, 1) . '</p>';
            
            // Add detailed leave/WFH breakdown
            $message .= '<h4>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</h4>';
            $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $message .= '<tr style="background-color: #f2f2f2;"><th>Date(s)</th><th>Days</th><th>Reason</th></tr>';
            
            foreach ($leave_details as $detail) {
                $message .= '<tr>';
                $message .= '<td>' . htmlspecialchars($detail['date']) . '</td>';
                $message .= '<td>' . number_format($detail['days'], 1) . '</td>';
                $message .= '<td>' . htmlspecialchars($detail['reason']) . '</td>';
                $message .= '</tr>';
            }
            $message .= '</table>';
            
            $message .= '<p><strong>Status:</strong> Pending Approval</p>';
            $message .= '<p>Please review and approve/reject this ' . strtolower($request_type) . ' from the <a href="' . site_url('leave/team') . '">Team Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';
            
            // Send a SINGLE email:
            // - Lead is always in "To" (if configured)
            // - Manager/Admin is always in "CC" (if configured and different from lead)
            $primary_to = null;
            $cc_list = [];
            
            if ($lead_email) {
                $primary_to = $lead_email;
                if ($admin_email && $admin_email !== $lead_email) {
                    $cc_list[] = $admin_email;
                }
            } elseif ($admin_email) {
                // Fallback: no lead, send directly to admin
                $primary_to = $admin_email;
            }
            
            if ($primary_to) {
                error_log('Leave notification: Sending consolidated email. To: ' . $primary_to . ', CC: ' . implode(',', $cc_list));
                $this->email->clear(true);
                $this->email->from($fromAddr, $fromName);
                $this->email->to($primary_to);
                if (!empty($cc_list)) {
                    $this->email->cc($cc_list);
                }
                $this->email->subject($subject);
                $this->email->message($message);
                @$this->email->send();
            }
            
        } catch (Exception $e) {
            // Log error but don't break the application
            error_log('Leave notification email failed: ' . $e->getMessage());
        }
    }

    /**
     * Send email notification when leave is approved or rejected
     * Sends to the employee who applied for leave, Lead, Manager, and HR
     */
    private function _notify_leave_change($leave_id, $status, $comments){
        // Load settings model to get HR user ID
        $this->load->model('Setting_model', 'settings');
        
        // Fetch requester email and leave details
        $row = $this->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                        ->from('leave_requests lr')
                        ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                        ->join('users u','u.id = lr.user_id','left')
                        ->where('lr.id', (int)$leave_id)->get()->row();
        if (!$row || empty($row->user_email)) return;
        
        // Get Lead and Manager from leave request (they were selected during application)
        $lead_id = null;
        $manager_id = null;
        
        // Get Lead from current_approver_id (Lead is stored as approver)
        if (!empty($row->current_approver_id)) {
            $lead_id = (int)$row->current_approver_id;
        }
        
        // Get Manager from manager_id field (stored during application)
        if (!empty($row->manager_id)) {
            $manager_id = (int)$row->manager_id;
        }
        
        // Get HR user ID from settings
        $hr_user_id = $this->settings->get_setting('leave_hr_user_id');
        $hr_user_id = !empty($hr_user_id) ? (int)$hr_user_id : null;
        
        // Get email addresses for Lead, Manager, and HR
        $lead_email = null;
        $lead_name = null;
        if ($lead_id) {
            $lead = $this->db->select('email, name')->from('users')->where('id', $lead_id)->get()->row();
            if ($lead && !empty($lead->email)) {
                $lead_email = $lead->email;
                $lead_name = !empty($lead->name) ? $lead->name : $lead->email;
            }
        }
        
        $manager_email = null;
        $manager_name = null;
        if ($manager_id) {
            $manager = $this->db->select('email, name')->from('users')->where('id', $manager_id)->get()->row();
            if ($manager && !empty($manager->email)) {
                $manager_email = $manager->email;
                $manager_name = !empty($manager->name) ? $manager->name : $manager->email;
            }
        }
        
        $hr_email = null;
        $hr_name = null;
        if ($hr_user_id) {
            $hr = $this->db->select('email, name')->from('users')->where('id', $hr_user_id)->get()->row();
            if ($hr && !empty($hr->email)) {
                $hr_email = $hr->email;
                $hr_name = !empty($hr->name) ? $hr->name : $hr->email;
            }
        }
        
        // Check if this is a WFH request
        $is_wfh = false;
        if (isset($row->reason) && strpos($row->reason, 'WFH:') === 0) {
            $is_wfh = true;
        } elseif (isset($row->type_name) && strtolower(trim($row->type_name)) === 'work from home') {
            $is_wfh = true;
        }
        
        // Best-effort email
        try {
            // Load email helper and configure from settings
            $this->load->helper('email');
            configure_email_from_settings();
            
            $fromAddr = get_system_from_email();
            $fromName = get_company_name();
            
            $this->email->clear(true);
            $this->email->from($fromAddr, $fromName);
            $this->email->to($row->user_email);
            
            // Build date string
            if ($row->start_date === $row->end_date) {
                $date_string = date('M d, Y', strtotime($row->start_date));
            } else {
                $date_string = date('M d', strtotime($row->start_date)) . ' - ' . date('M d, Y', strtotime($row->end_date));
            }
            
            $status_text = ($status === 'rejected') ? 'Rejected' : 'Approved';
            $status_color = ($status === 'rejected') ? '#dc3545' : '#28a745';
            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            
            $subject = $request_type . ' ' . $status_text . ' - ' . $date_string;
            $message = '<html><body>';
            $message .= '<h3 style="color: ' . $status_color . ';">' . htmlspecialchars($request_type) . ' ' . $status_text . '</h3>';
            $message .= '<p>Dear ' . htmlspecialchars(!empty($row->user_name) ? $row->user_name : $row->user_email) . ',</p>';
            $message .= '<p>Your ' . strtolower($request_type) . ' has been <strong style="color: ' . $status_color . ';">' . $status_text . '</strong>.</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</strong></p>';
            $message .= '<ul>';
            $message .= '<li><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . htmlspecialchars($row->type_name) . '</li>';
            $message .= '<li><strong>Date(s):</strong> ' . htmlspecialchars($date_string) . '</li>';
            $message .= '<li><strong>Days:</strong> ' . number_format((float)$row->days, 1) . '</li>';
            $message .= '<li><strong>Status:</strong> ' . htmlspecialchars($status_text) . '</li>';
            $message .= '</ul>';
            if ($comments) {
                $message .= '<p><strong>Comments:</strong></p>';
                $message .= '<p>' . nl2br(htmlspecialchars($comments)) . '</p>';
            }
            $message .= '<p>You can view your ' . strtolower($request_type) . 's from the <a href="' . site_url('leave/my') . '">My Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';
            
            // Collect all unique recipients to avoid duplicate emails
            // Employee is always the primary recipient
            $all_recipients = [];
            $all_recipients[$row->user_email] = ['email' => $row->user_email, 'name' => !empty($row->user_name) ? $row->user_name : $row->user_email, 'type' => 'employee'];
            
            // Add Lead, Manager, HR (avoid duplicates)
            if ($lead_email && !isset($all_recipients[$lead_email])) {
                $all_recipients[$lead_email] = ['email' => $lead_email, 'name' => $lead_name, 'type' => 'lead'];
            }
            if ($manager_email && !isset($all_recipients[$manager_email])) {
                $all_recipients[$manager_email] = ['email' => $manager_email, 'name' => $manager_name, 'type' => 'manager'];
            }
            if ($hr_email && !isset($all_recipients[$hr_email])) {
                $all_recipients[$hr_email] = ['email' => $hr_email, 'name' => $hr_name, 'type' => 'hr'];
            }
            
            // Send ONE email to employee (primary recipient)
            // Add Lead, Manager, HR in CC if they are different from employee
            $cc_list = [];
            foreach ($all_recipients as $email => $recipient) {
                if ($email !== $row->user_email) {
                    $cc_list[] = $email;
                }
            }
            
            $this->email->subject($subject);
            $this->email->message($message);
            if (!empty($cc_list)) {
                $this->email->cc($cc_list);
            }
            
            // Log before sending
            log_message('info', 'Sending leave status email. To: ' . $row->user_email . ', CC: ' . implode(',', $cc_list));
            
            $sent = $this->email->send();
            if ($sent) {
                log_message('info', 'Leave status email sent successfully to ' . $row->user_email);
            } else {
                log_message('error', 'Failed to send leave status email to ' . $row->user_email);
                log_message('error', 'Email Debug: ' . $this->email->print_debugger());
            }
        } catch (Exception $e) {
            // Silently fail - don't break the approval process
            log_message('error', 'Leave approval email notification failed: ' . $e->getMessage());
        } catch (Error $e) {
            log_message('error', 'Leave approval email notification error: ' . $e->getMessage());
        }
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
                'title' => htmlspecialchars($task->title),
                'description' => !empty($task->description) ? htmlspecialchars($task->description) : 'No description',
                'project_name' => !empty($task->project_name) ? htmlspecialchars($task->project_name) : 'No Project',
                'status' => htmlspecialchars($task->status),
                'priority' => htmlspecialchars($task->priority),
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
