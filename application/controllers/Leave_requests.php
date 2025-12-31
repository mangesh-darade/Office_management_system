<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_requests extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','email']);
        $this->load->helper(['url','form','workday','group_filter','company']);
        $this->load->model('Leave_request_model','leaves');
        // Require login
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
    }

    // GET/POST /leave/apply
    public function apply(){
        $user_id = (int)$this->session->userdata('user_id');
        // Read leave types
        $types = $this->db->order_by('name','ASC')->get('leave_types')->result();

        if ($this->input->method() === 'post'){
            $type_id = (int)$this->input->post('type_id');
            $mode = $this->input->post('mode');
            $mode = ($mode === 'specific') ? 'specific' : 'range';
            $reason     = trim((string)$this->input->post('reason'));

            if (!$type_id){
                $this->session->set_flashdata('error', 'Please select a leave type.');
                redirect('leave/apply');
                return;
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

                // Check leave balance once for total days
                $bal = $this->leaves->get_leave_balance($user_id, $type_id);
                if ($bal && isset($bal->available) && (float)$bal->available < $days){
                    $this->session->set_flashdata('error', 'Insufficient balance for this leave type. Available: '.(float)$bal->available);
                    redirect('leave/apply');
                    return;
                }

                // Find department manager as approver if available
                $approver_id = null;
                if ($this->db->table_exists('employees') && $this->db->table_exists('departments')){
                    $emp = $this->db->where('user_id', $user_id)->get('employees')->row();
                    if ($emp && !empty($emp->department)) {
                        // Find department by name and get its manager
                        $dept = $this->db->select('manager_id')->from('departments')
                            ->where('dept_name', $emp->department)
                            ->where('status', 'active')
                            ->get()->row();
                        if ($dept && !empty($dept->manager_id)) {
                            $approver_id = (int)$dept->manager_id;
                        }
                    }
                    // Fallback to reporting_to if department manager not found
                    if (!$approver_id && $emp && !empty($emp->reporting_to)) {
                        $approver_id = (int)$emp->reporting_to;
                    }
                }

                $leave_ids = [];
                foreach ($perDateDays as $d => $wd) {
                    $data = [
                        'user_id' => $user_id,
                        'type_id' => $type_id,
                        'start_date' => $d,
                        'end_date' => $d,
                        'days' => $wd,
                        'reason' => $reason,
                        'status' => 'pending',
                        'current_approver_id' => $approver_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $leave_id = $this->leaves->apply_leave($data);
                    if ($leave_id) {
                        $leave_ids[] = $leave_id;
                    }
                }

                // Send email notifications after all leave requests are created
                if (!empty($leave_ids)) {
                    $this->_notify_leave_applied($leave_ids, $user_id, $type_id, $approver_id);
                }

                $this->session->set_flashdata('success', 'Leave request submitted.');
                redirect('leave/my');
                return;
            }

            // Range mode
            $start_date = $this->input->post('start_date');
            $end_date   = $this->input->post('end_date');
            $duration_type = $this->input->post('duration_type');
            $duration_type = ($duration_type === 'half') ? 'half' : 'full';

            // Basic validation
            if (!$start_date || !$end_date){
                $this->session->set_flashdata('error', 'Please select type and date range.');
                redirect('leave/apply');
                return;
            }
            if (strtotime($end_date) < strtotime($start_date)){
                $this->session->set_flashdata('error', 'End date cannot be before start date.');
                redirect('leave/apply');
                return;
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

            // Check leave balance
            $bal = $this->leaves->get_leave_balance($user_id, $type_id);
            if ($bal && isset($bal->available) && (float)$bal->available < $days){
                $this->session->set_flashdata('error', 'Insufficient balance for this leave type. Available: '.(float)$bal->available);
                redirect('leave/apply');
                return;
            }

            // Find department manager as approver if available
            $approver_id = null;
            if ($this->db->table_exists('employees') && $this->db->table_exists('departments')){
                $emp = $this->db->where('user_id', $user_id)->get('employees')->row();
                if ($emp && !empty($emp->department)) {
                    // Find department by name and get its manager
                    $dept = $this->db->select('manager_id')->from('departments')
                        ->where('dept_name', $emp->department)
                        ->where('status', 'active')
                        ->get()->row();
                    if ($dept && !empty($dept->manager_id)) {
                        $approver_id = (int)$dept->manager_id;
                    }
                }
                // Fallback to reporting_to if department manager not found
                if (!$approver_id && $emp && !empty($emp->reporting_to)) {
                    $approver_id = (int)$emp->reporting_to;
                }
            }

            // Insert
            $data = [
                'user_id' => $user_id,
                'type_id' => $type_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
                'current_approver_id' => $approver_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $id = $this->leaves->apply_leave($data);

            // Send email notifications
            if ($id) {
                $this->_notify_leave_applied([$id], $user_id, $type_id, $approver_id);
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
        if (!in_array($role_id, [1,2,3], true)) { show_error('Forbidden', 403); }

        // Admin (role_id = 1) can see all leave requests; department managers see only their department employees
        $restrict_to_team = ($role_id !== 1);

        $this->db->select('lr.*, lr.user_id, lt.name AS type_name, u.email AS user_email, e.department AS emp_department, 
                          (SELECT la.remarks FROM leave_approvals la WHERE la.leave_id = lr.id ORDER BY la.decided_at DESC LIMIT 1) AS latest_remarks')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->join('users u', 'u.id = lr.user_id', 'left')
                 ->join('employees e', 'e.user_id = lr.user_id', 'left');
                 
        if ($restrict_to_team) {
            // Department managers see leaves of employees in their department
            if ($this->db->table_exists('departments')) {
                // Find departments where current user is the manager
                $this->db->join('departments d', 'd.dept_name = e.department AND d.status = "active"', 'left');
                $this->db->where('d.manager_id', $user_id);
                $this->db->where('e.department IS NOT NULL');
            } else {
                // Fallback: show only their own if departments table doesn't exist
                $this->db->where('lr.user_id', $user_id);
            }
        }
        // Optional filters
        $status = trim((string)$this->input->get('status'));
        if ($status !== '') { $this->db->where('lr.status', $status); }
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        if ($from) { $this->db->where('lr.start_date >=', $from); }
        if ($to) { $this->db->where('lr.end_date <=', $to); }
        $this->db->order_by('lr.start_date','DESC');
        $rows = $this->db->get()->result();

        $this->load->view('leave_requests/team', [
            'rows' => $rows,
            'filters' => ['status'=>$status,'from'=>$from,'to'=>$to],
            'is_admin' => ($role_id === 1),
        ]);
    }

    // POST /leave/approve/{id}
    public function approve($id){
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [1,2,3], true)) { show_error('Forbidden', 403); }
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');

        // Update leave and add approval row
        $ok = $this->leaves->approve_reject_leave($id, 'lead_approved', $comments, $approved_by);

        // Email notify requester (best-effort)
        $this->_notify_leave_change($id, 'approved', $comments);

        $this->session->set_flashdata('success', 'Leave approved.');
        redirect('leave/team');
    }

    // POST /leave/reject/{id}
    public function reject($id){
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [1,2,3], true)) { show_error('Forbidden', 403); }
        if ($this->input->method() !== 'post') { show_404(); }
        $id = (int)$id;
        $comments = trim((string)$this->input->post('comments'));
        $approved_by = (int)$this->session->userdata('user_id');

        $ok = $this->leaves->approve_reject_leave($id, 'rejected', $comments, $approved_by);
        $this->_notify_leave_change($id, 'rejected', $comments);
        $this->session->set_flashdata('success', 'Leave rejected.');
        redirect('leave/team');
    }

    // GET /leave/calendar
    public function calendar(){
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [1,2,3], true)) { show_error('Forbidden', 403); }
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

    // GET/POST /leave/edit/{id} - Edit leave request (Admin only)
    public function edit($id){
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) { show_error('Forbidden - Admin only', 403); }
        
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
            $this->session->set_flashdata('success', 'Leave request updated successfully.');
            redirect('leave/team');
            return;
        }
        
        // Load leave types for dropdown
        $types = $this->db->order_by('name','ASC')->get('leave_types')->result();
        $this->load->view('leave_requests/edit', [
            'leave' => $leave,
            'types' => $types,
        ]);
    }
    
    // POST /leave/delete/{id} - Delete leave request (Admin only)
    public function delete($id){
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) { show_error('Forbidden - Admin only', 403); }
        if ($this->input->method() !== 'post') { show_404(); }
        
        $id = (int)$id;
        $leave = $this->db->get_where('leave_requests', ['id' => $id])->row();
        if (!$leave) { show_404(); }
        
        // Delete leave request
        $this->db->where('id', $id)->delete('leave_requests');
        $this->load->helper('activity');
        log_activity('leave_requests', 'deleted', $id, 'Leave request deleted by admin');
        $this->session->set_flashdata('success', 'Leave request deleted successfully.');
        redirect('leave/team');
    }

    /**
     * Send email notification when leave is applied
     * Sends to department manager and admin (with manager in CC)
     */
    private function _notify_leave_applied($leave_ids, $user_id, $type_id, $approver_id){
        if (empty($leave_ids)) return;
        
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
            
            // Build date range string
            $date_ranges = [];
            foreach ($leaves as $l) {
                if ($l->start_date === $l->end_date) {
                    $date_ranges[] = date('M d, Y', strtotime($l->start_date));
                } else {
                    $date_ranges[] = date('M d', strtotime($l->start_date)) . ' - ' . date('M d, Y', strtotime($l->end_date));
                }
            }
            $date_string = implode(', ', $date_ranges);
            
            $total_days = array_sum(array_column($leaves, 'days'));
            $reason = !empty($first_leave->reason) ? $first_leave->reason : 'No reason provided';
            
            // Get manager email
            $manager_email = null;
            $manager_name = null;
            if ($approver_id) {
                $manager = $this->db->select('email, name')->from('users')->where('id', $approver_id)->get()->row();
                if ($manager && !empty($manager->email)) {
                    $manager_email = $manager->email;
                    $manager_name = !empty($manager->name) ? $manager->name : $manager->email;
                }
            }
            
            // Get admin email (role_id = 1)
            $admin = $this->db->select('email, name')->from('users')->where('role_id', 1)->where('status', 'active')->limit(1)->get()->row();
            $admin_email = null;
            $admin_name = null;
            if ($admin && !empty($admin->email)) {
                $admin_email = $admin->email;
                $admin_name = !empty($admin->name) ? $admin->name : $admin->email;
            }
            
            // Initialize email config
            $cfg = array('smtp_timeout'=>10,'mailtype'=>'html','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
            $this->email->initialize($cfg);
            
            $fromAddr = getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
            $fromName = get_company_name();
            
            // Email to Department Manager
            if ($manager_email) {
                $this->email->clear(true);
                $this->email->from($fromAddr, $fromName);
                $this->email->to($manager_email);
                
                // Add admin in CC
                if ($admin_email && $admin_email !== $manager_email) {
                    $this->email->cc($admin_email);
                }
                
                $subject = 'New Leave Request - ' . $employee_name;
                $message = '<html><body>';
                $message .= '<h3>New Leave Request</h3>';
                $message .= '<p><strong>Employee:</strong> ' . htmlspecialchars($employee_name) . ' (' . htmlspecialchars($first_leave->user_email) . ')</p>';
                $message .= '<p><strong>Leave Type:</strong> ' . htmlspecialchars($leave_type) . '</p>';
                $message .= '<p><strong>Date(s):</strong> ' . htmlspecialchars($date_string) . '</p>';
                $message .= '<p><strong>Total Days:</strong> ' . number_format($total_days, 1) . '</p>';
                $message .= '<p><strong>Reason:</strong> ' . nl2br(htmlspecialchars($reason)) . '</p>';
                $message .= '<p><strong>Status:</strong> Pending Approval</p>';
                $message .= '<p>Please review and approve/reject this leave request from the <a href="' . site_url('leave/team') . '">Team Leaves</a> page.</p>';
                $message .= '<p>Thank you.</p>';
                $message .= '</body></html>';
                
                $this->email->subject($subject);
                $this->email->message($message);
                @$this->email->send();
            }
            
            // Email to Admin (if admin is not the manager)
            if ($admin_email && $admin_email !== $manager_email) {
                $this->email->clear(true);
                $this->email->from($fromAddr, $fromName);
                $this->email->to($admin_email);
                
                // Add manager in CC if exists
                if ($manager_email) {
                    $this->email->cc($manager_email);
                }
                
                $subject = 'New Leave Request - ' . $employee_name;
                $message = '<html><body>';
                $message .= '<h3>New Leave Request</h3>';
                $message .= '<p><strong>Employee:</strong> ' . htmlspecialchars($employee_name) . ' (' . htmlspecialchars($first_leave->user_email) . ')</p>';
                $message .= '<p><strong>Leave Type:</strong> ' . htmlspecialchars($leave_type) . '</p>';
                $message .= '<p><strong>Date(s):</strong> ' . htmlspecialchars($date_string) . '</p>';
                $message .= '<p><strong>Total Days:</strong> ' . number_format($total_days, 1) . '</p>';
                $message .= '<p><strong>Reason:</strong> ' . nl2br(htmlspecialchars($reason)) . '</p>';
                $message .= '<p><strong>Status:</strong> Pending Approval</p>';
                if ($manager_name) {
                    $message .= '<p><strong>Assigned Approver:</strong> ' . htmlspecialchars($manager_name) . '</p>';
                }
                $message .= '<p>Please review this leave request from the <a href="' . site_url('leave/team') . '">Team Leaves</a> page.</p>';
                $message .= '<p>Thank you.</p>';
                $message .= '</body></html>';
                
                $this->email->subject($subject);
                $this->email->message($message);
                @$this->email->send();
            }
            
        } catch (Exception $e) {
            // Silently fail - don't break the leave application process
            error_log('Leave application email notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Send email notification when leave is approved or rejected
     * Sends to the employee who applied for leave
     */
    private function _notify_leave_change($leave_id, $status, $comments){
        // Fetch requester email and leave details
        $row = $this->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                        ->from('leave_requests lr')
                        ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                        ->join('users u','u.id = lr.user_id','left')
                        ->where('lr.id', (int)$leave_id)->get()->row();
        if (!$row || empty($row->user_email)) return;
        
        // Best-effort email
        try {
            // Initialize email config
            $cfg = array('smtp_timeout'=>10,'mailtype'=>'html','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
            $this->email->initialize($cfg);
            
            $fromAddr = getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
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
            
            $subject = 'Leave Request ' . $status_text . ' - ' . $date_string;
            $message = '<html><body>';
            $message .= '<h3 style="color: ' . $status_color . ';">Leave Request ' . $status_text . '</h3>';
            $message .= '<p>Dear ' . htmlspecialchars(!empty($row->user_name) ? $row->user_name : $row->user_email) . ',</p>';
            $message .= '<p>Your leave request has been <strong style="color: ' . $status_color . ';">' . $status_text . '</strong>.</p>';
            $message .= '<p><strong>Leave Details:</strong></p>';
            $message .= '<ul>';
            $message .= '<li><strong>Leave Type:</strong> ' . htmlspecialchars($row->type_name) . '</li>';
            $message .= '<li><strong>Date(s):</strong> ' . htmlspecialchars($date_string) . '</li>';
            $message .= '<li><strong>Days:</strong> ' . number_format((float)$row->days, 1) . '</li>';
            $message .= '<li><strong>Status:</strong> ' . htmlspecialchars($status_text) . '</li>';
            $message .= '</ul>';
            if ($comments) {
                $message .= '<p><strong>Comments:</strong></p>';
                $message .= '<p>' . nl2br(htmlspecialchars($comments)) . '</p>';
            }
            $message .= '<p>You can view your leave requests from the <a href="' . site_url('leave/my') . '">My Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';
            
            $this->email->subject($subject);
            $this->email->message($message);
            @$this->email->send();
        } catch (Exception $e) {
            // Silently fail - don't break the approval process
            error_log('Leave approval email notification failed: ' . $e->getMessage());
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
