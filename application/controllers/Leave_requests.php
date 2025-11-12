<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leave_requests extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','email']);
        $this->load->helper(['url','form','workday']);
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
            $start_date = $this->input->post('start_date');
            $end_date   = $this->input->post('end_date');
            $reason     = trim((string)$this->input->post('reason'));

            // Basic validation
            if (!$type_id || !$start_date || !$end_date){
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

            // Find reporting manager as approver if available
            $approver_id = null;
            if ($this->db->table_exists('employees')){
                $emp = $this->db->where('user_id', $user_id)->get('employees')->row();
                if ($emp && !empty($emp->reporting_to)) { $approver_id = (int)$emp->reporting_to; }
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

            // (Phase 3) notify approver/email - placeholder

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

        $this->load->view('leave_requests/apply', [
            'types' => $types,
            'balances' => $balances,
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
        $rows = $this->leaves->get_user_leaves($user_id, $filters);
        $this->load->view('leave_requests/my', [
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    // GET/POST /leave/team - List team leaves for managers/leads with approve/reject actions
    public function team(){
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [2,3], true)) { show_error('Forbidden', 403); }

        $manager_id = (int)$this->session->userdata('user_id');
        // Find team member user_ids via employees.reporting_to
        $user_ids = [];
        if ($this->db->table_exists('employees')){
            $rows = $this->db->select('user_id')->from('employees')->where('reporting_to', $manager_id)->get()->result();
            foreach ($rows as $r) { if ($r->user_id) { $user_ids[] = (int)$r->user_id; } }
        }

        $this->db->select('lr.*, lt.name AS type_name, u.email AS user_email')
                 ->from('leave_requests lr')
                 ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                 ->join('users u', 'u.id = lr.user_id', 'left');
        if (!empty($user_ids)) {
            $this->db->where_in('lr.user_id', $user_ids);
        } else {
            // No direct reports; show empty set safely
            $this->db->where('1=0', null, false);
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
        ]);
    }

    // POST /leave/approve/{id}
    public function approve($id){
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [2,3], true)) { show_error('Forbidden', 403); }
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
        if (!in_array($role_id, [2,3], true)) { show_error('Forbidden', 403); }
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
        if (!in_array($role_id, [2,3], true)) { show_error('Forbidden', 403); }
        $manager_id = (int)$this->session->userdata('user_id');

        $ym = $this->input->get('month'); // format YYYY-MM
        if (!$ym) { $ym = date('Y-m'); }
        $from = $ym.'-01';
        $to = date('Y-m-t', strtotime($from));

        // Team user ids
        $user_ids = [];
        if ($this->db->table_exists('employees')){
            $rows = $this->db->select('user_id')->from('employees')->where('reporting_to', $manager_id)->get()->result();
            foreach ($rows as $r) { if ($r->user_id) { $user_ids[] = (int)$r->user_id; } }
        }

        $this->db->select('lr.*, u.email AS user_email, lt.name AS type_name')
                 ->from('leave_requests lr')
                 ->join('users u','u.id = lr.user_id','left')
                 ->join('leave_types lt','lt.id = lr.type_id','left')
                 ->where('lr.start_date <=', $to)
                 ->where('lr.end_date >=', $from);
        if (!empty($user_ids)) { $this->db->where_in('lr.user_id', $user_ids); }
        else { $this->db->where('1=0', null, false); }
        $rows = $this->db->get()->result();

        $this->load->view('leave_requests/calendar', [
            'month' => $ym,
            'rows' => $rows,
        ]);
    }

    private function _notify_leave_change($leave_id, $status, $comments){
        // Fetch requester email
        $row = $this->db->select('lr.*, u.email')
                        ->from('leave_requests lr')
                        ->join('users u','u.id = lr.user_id','left')
                        ->where('lr.id', (int)$leave_id)->get()->row();
        if (!$row || empty($row->email)) return;
        // Best-effort email
        try {
            $this->email->clear(true);
            $this->email->to($row->email);
            $this->email->subject('Leave request '.$status);
            $msg = 'Your leave request ('.htmlspecialchars($row->start_date.' to '.$row->end_date).') has been '.$status.'.';
            if ($comments) { $msg .= "\nComments: ".$comments; }
            $this->email->message(nl2br($msg));
            @$this->email->send();
        } catch (Exception $e) { /* ignore */ }
    }
}
