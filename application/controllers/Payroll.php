<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','workday']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        // Gate via permissions table when available; fallback to Admin/HR
        // Gate via permissions
        $allowed = false;
        if (function_exists('has_module_access')) {
            if (has_module_access('payroll') || has_module_access('payroll_view') || has_module_access('payroll_manage')) {
                $allowed = true;
            }
        }
        if (!$allowed){
            $role_id = (int)$this->session->userdata('role_id');
            if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) { show_error('Access Denied', 403); }
        }
        $this->load->model('Payroll_model', 'payroll');
    }

    public function index(){
        redirect('payroll/payslips');
    }

    // Manage salary structures per user
    public function structures(){
        if (function_exists('has_module_access')) {
            if (!has_module_access('payroll_manage') && !has_module_access('payroll')) {
                // Allow fallback to role-based check within method if module check fails? 
                // Constructor already did a broad check. Here we enforce specific manage capability.
                // But we must respect the role 1/2 fallback from constructor logic for consistency.
                $rid = (int)$this->session->userdata('role_id');
                if (!in_array($rid, [ROLE_ADMIN, ROLE_MANAGER], true)) { show_error('Access Denied', 403); }
            }
        }
        $rows = $this->payroll->get_structures();
        $users = $this->payroll->get_user_options();
        $this->load->view('payroll/structures', ['rows' => $rows, 'users' => $users]);
    }

    // GET/POST /payroll/structure/{user_id}
    public function structure($user_id = null){
        $user_id = (int)$user_id;
        if ($this->input->method() === 'post'){
            $user_id = (int)$this->input->post('user_id');
            if ($user_id <= 0){
                $this->session->set_flashdata('error','Please select an employee.');
                redirect('payroll/structures');
                return;
            }
            $data = [
                'basic' => (float)($this->input->post('basic') ?: 0),
                'hra' => (float)($this->input->post('hra') ?: 0),
                'conveyance_allow' => (float)($this->input->post('conveyance_allow') ?: 0),
                'medical_allow' => (float)($this->input->post('medical_allow') ?: 0),
                'education_allow' => (float)($this->input->post('education_allow') ?: 0),
                'special_allow' => (float)($this->input->post('special_allow') ?: 0),
                'professional_tax' => (float)($this->input->post('professional_tax') ?: 0),
                'tds' => (float)($this->input->post('tds') ?: 0),
                'allowances' => (float)($this->input->post('allowances') ?: 0),
                'deductions' => (float)($this->input->post('deductions') ?: 0),
                'pf_percent' => (float)($this->input->post('pf_percent') ?: 0),
                'esi_percent' => (float)($this->input->post('esi_percent') ?: 0),
            ];
            // Load activity tracking helper
            $this->load->helper('change_tracker');
            
            // Check if structure exists (update) or new (insert)
            $existing = $this->payroll->get_structure($user_id);
            $old_data = $existing ? (array)$existing : null;
            
            $this->payroll->save_structure($user_id, $data);
            
            // Log payroll structure save
            if ($existing) {
                // Update
                $description = 'Payroll structure updated for user ID: ' . $user_id;
                track_changes_after('payroll', 'payroll_structures', $user_id, $old_data, $data, $description);
            } else {
                // Insert
                $description = 'Payroll structure created for user ID: ' . $user_id;
                auto_log_insert('payroll', 'payroll_structures', $user_id, $data, $description);
            }
            
            $this->session->set_flashdata('success','Salary structure saved.');
            redirect('payroll/structures');
            return;
        }
        $users = $this->payroll->get_user_options();
        $row = $user_id ? $this->payroll->get_structure($user_id) : null;
        $this->load->view('payroll/structure_form', ['users' => $users, 'user_id' => $user_id, 'row' => $row]);
    }

    // List payslips
    public function payslips(){
        $current_user_id = (int)$this->session->userdata('user_id');
        $role_id         = (int)$this->session->userdata('role_id');
        $isAdminGroup    = (function_exists('is_admin_group') && is_admin_group())
                           || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);

        $filters = [
            'period'  => $this->input->get('period'),
            'user_id' => $this->input->get('user_id'),
        ];

        // Non-admin users may only see their own payslips
        if (!$isAdminGroup) {
            $filters['user_id'] = $current_user_id;
        }

        $rows  = $this->payroll->list_payslips($filters);
        $users = $isAdminGroup ? $this->payroll->get_user_options() : [];
        $this->load->view('payroll/payslips', [
            'rows'          => $rows,
            'filters'       => $filters,
            'users'         => $users,
            'is_admin_group' => $isAdminGroup,
        ]);
    }

    // POST /payroll/send_payslips
    public function send_payslips(){
        // Only payroll managers may bulk-email payslips
        $allowed = (function_exists('has_module_access') && has_module_access('payroll_manage'))
                   || in_array((int)$this->session->userdata('role_id'), [ROLE_ADMIN, ROLE_MANAGER], true);
        if (!$allowed) {
            show_error('You do not have permission to send payslips.', 403);
        }

        if ($this->input->method() !== 'post'){
            redirect('payroll/payslips');
            return;
        }
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)){
            $response = ['success' => false, 'message' => 'No payslips selected to email.'];
            if ($this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }
            $this->session->set_flashdata('error','No payslips selected to email.');
            redirect('payroll/payslips');
            return;
        }

        $this->load->library('email');
        $cfg = ['smtp_timeout'=>10,'mailtype'=>'text','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8'];
        $this->email->initialize($cfg);

        $this->load->model('Setting_model','settings');
        $settings = $this->settings->get_all_settings();
        $this->load->library('url_shortener');
        $pdfDir = FCPATH.'uploads/payslips/';
        if (!is_dir($pdfDir)) { @mkdir($pdfDir, 0755, true); }

        $sent = 0;
        $failed = 0;

        foreach ($ids as $id){
            $id = (int)$id;
            if ($id <= 0){ continue; }
            $row = $this->payroll->find_payslip($id);
            if (!$row || !isset($row->email) || $row->email===''){
                $failed++;
                continue;
            }
            $to = (string)$row->email;

            $period = isset($row->period) ? (string)$row->period : '';
            $label = $period;
            if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m)){
                $monthNum = (int)$m[2];
                $year = $m[1];
                $monthNames = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                ];
                if (isset($monthNames[$monthNum])){
                    $label = $monthNames[$monthNum].' '.$year;
                }
            }

            $fileType = 'PDF'; // Default, will be updated to HTML if fallback is used
            $pdf_method = '';
            $subject = 'Salary Slip for '.$label;
            
            // Get company name from settings
            $company_name = isset($settings->company_name) ? $settings->company_name : '';
            
            // Create shortened URL for payslip link
            $original_url = site_url('payroll/view/' . $id);
            $short_url = $this->url_shortener->shorten($original_url, 30); // Expires in 30 days
            
            $body = "Hello,\nPlease find your salary slip for " . $label . " attached as a " . $fileType . " document.\n\nYou can also view your payslip online at: " . $short_url . "\n\nThanks & Regards,\n" . $company_name;

            $pdfPath = '';
            $pdfName = '';
            
            // Try dompdf first, then fallback to native PDF generator, then HTML file
            if (class_exists('\\Dompdf\\Dompdf')){
                $pdf_method = 'DomPDF';
                $viewData = [
                    'row' => $row,
                    'settings' => $settings,
                    'hide_navbar' => true,
                    'with_sidebar' => false,
                    'full_width' => true,
                ];
                $html = $this->load->view('payroll/payslip_view', $viewData, true);
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $output = $dompdf->output();
                $pdfName = 'payslip-'.$id.'.pdf';
                $pdfPath = $pdfDir.$pdfName;
                @file_put_contents($pdfPath, $output);
            } elseif (file_exists(APPPATH.'libraries/Working_pdf_generator.php')) {
                $pdf_method = 'Working PDF Generator';
                // Use working PDF generator
                $this->load->library('working_pdf_generator');
                
                // Extract payslip data
                $fn = isset($row->first_name) ? trim((string)$row->first_name) : '';
                $ln = isset($row->last_name) ? trim((string)$row->last_name) : '';
                $empName = trim($fn.' '.$ln);
                if ($empName === '') {
                    $empName = isset($row->name) ? (string)$row->name : (string)$row->email;
                }
                
                $this->working_pdf_generator->setTitle('Payslip - '.$label);
                $this->working_pdf_generator->setAuthor($company_name);
                
                // Header
                $this->working_pdf_generator->addText('Salary Slip', 16, true);
                $this->working_pdf_generator->addText($label, 14);
                $this->working_pdf_generator->addSeparator();
                
                // Employee details
                $this->working_pdf_generator->addText('Employee Details', 14, true);
                $this->working_pdf_generator->addLine('Name:', $empName);
                $this->working_pdf_generator->addLine('Employee Code:', isset($row->emp_code) ? (string)$row->emp_code : '');
                $this->working_pdf_generator->addLine('Department:', isset($row->department) ? (string)$row->department : '');
                $this->working_pdf_generator->addLine('Designation:', isset($row->designation) ? (string)$row->designation : '');
                $this->working_pdf_generator->addSeparator();
                
                // Salary details
                $this->working_pdf_generator->addText('Salary Details', 14, true);
                
                $headers = ['Component', 'Amount'];
                $salary_data = [];
                
                if (isset($row->basic) && $row->basic > 0) {
                    $salary_data[] = ['Basic Salary', number_format((float)$row->basic, 2)];
                }
                if (isset($row->hra) && $row->hra > 0) {
                    $salary_data[] = ['HRA', number_format((float)$row->hra, 2)];
                }
                if (isset($row->conveyance_allow) && $row->conveyance_allow > 0) {
                    $salary_data[] = ['Conveyance Allowance', number_format((float)$row->conveyance_allow, 2)];
                }
                if (isset($row->medical_allow) && $row->medical_allow > 0) {
                    $salary_data[] = ['Medical Allowance', number_format((float)$row->medical_allow, 2)];
                }
                if (isset($row->education_allow) && $row->education_allow > 0) {
                    $salary_data[] = ['Education Allowance', number_format((float)$row->education_allow, 2)];
                }
                if (isset($row->special_allow) && $row->special_allow > 0) {
                    $salary_data[] = ['Special Allowance', number_format((float)$row->special_allow, 2)];
                }
                if (isset($row->allowances) && $row->allowances > 0) {
                    $salary_data[] = ['Other Allowances', number_format((float)$row->allowances, 2)];
                }
                
                // Deductions
                if (isset($row->professional_tax) && $row->professional_tax > 0) {
                    $salary_data[] = ['Professional Tax', number_format((float)$row->professional_tax, 2)];
                }
                if (isset($row->tds) && $row->tds > 0) {
                    $salary_data[] = ['TDS', number_format((float)$row->tds, 2)];
                }
                if (isset($row->pf_amount) && $row->pf_amount > 0) {
                    $salary_data[] = ['Provident Fund', number_format((float)$row->pf_amount, 2)];
                }
                if (isset($row->esi_amount) && $row->esi_amount > 0) {
                    $salary_data[] = ['ESI', number_format((float)$row->esi_amount, 2)];
                }
                if (isset($row->deductions) && $row->deductions > 0) {
                    $salary_data[] = ['Other Deductions', number_format((float)$row->deductions, 2)];
                }
                
                // Total
                if (isset($row->net_salary)) {
                    $salary_data[] = ['Net Salary', number_format((float)$row->net_salary, 2)];
                }
                
                if (!empty($salary_data)) {
                    $this->working_pdf_generator->addTable($headers, $salary_data);
                }
                
                $output = $this->working_pdf_generator->output();
                $pdfName = 'payslip-'.$id.'.pdf';
                $pdfPath = $pdfDir.$pdfName;
                @file_put_contents($pdfPath, $output);
                
            } else {
                // Fallback: Create clean HTML payslip and save as HTML file
                $viewData = [
                    'row' => $row,
                    'settings' => $settings,
                    'hide_navbar' => true,
                    'with_sidebar' => false,
                    'full_width' => true,
                ];
                $html = $this->load->view('payroll/payslip_view', $viewData, true);
                
                // Create clean HTML content for email attachment
                $cleanHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - ' . $label . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { margin: 15px 0; }
        .row { display: flex; justify-content: space-between; margin: 5px 0; }
        .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; border-top: 2px solid #333; }
    </style>
</head>
<body>' . $html . '</body>
</html>';
                
                $pdfName = 'payslip-'.$id.'.html';
                $pdfPath = $pdfDir.$pdfName;
                @file_put_contents($pdfPath, $cleanHtml);
                $fileType = 'HTML';
                // Update email body to reflect HTML file type and include shortened link
                $body = "Hello,\nPlease find your salary slip for " . $label . " attached as an " . $fileType . " document.\n\nYou can also view your payslip online at: " . $short_url . "\n\nThanks & Regards,\n" . $company_name;
            }

            // Load email helper and configure from settings
            $this->load->helper('email');
            configure_email_from_settings();
            
            $this->email->clear(true);
            $fromAddr = get_system_from_email();
            $this->email->from($fromAddr, $company_name);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($body);
            
            // Attach file with correct MIME type
            if ($pdfPath !== '' && is_file($pdfPath)){
                if (pathinfo($pdfName, PATHINFO_EXTENSION) === 'pdf') {
                    $this->email->attach($pdfPath, 'attachment', $pdfName, 'application/pdf');
                } else {
                    // For HTML files, attach as 'text/html'
                    $this->email->attach($pdfPath, 'attachment', $pdfName, 'text/html');
                }
            }

            if ($this->email->send()){
                $sent++;
            } else {
                $failed++;
            }
        }

        $message = 'Payslip emails - Sent: '.$sent.'; Failed: '.$failed.'.';
        
        if ($this->input->is_ajax_request()) {
            $response = [
                'success' => $sent > 0,
                'message' => $message,
                'sent' => $sent,
                'failed' => $failed
            ];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $this->session->set_flashdata('success', $message);
        redirect('payroll/payslips');
    }

    // Generate payslip for one employee and period
    public function generate(){
        if (function_exists('has_module_access')) {
            if (!has_module_access('payroll_manage') && !has_module_access('payroll')) {
                $rid = (int)$this->session->userdata('role_id');
                if (!in_array($rid, [ROLE_ADMIN, ROLE_MANAGER], true)) { show_error('Access Denied', 403); }
            }
        }
        if ($this->input->method() === 'post'){
            $user_id = (int)$this->input->post('user_id');
            $period = trim((string)$this->input->post('period'));
            $remarks = trim((string)$this->input->post('remarks'));
            if ($user_id <= 0 || $period === ''){
                $this->session->set_flashdata('error','Please select employee and month.');
                redirect('payroll/generate');
                return;
            }
            if (!preg_match('/^\d{4}-\d{2}$/', $period)){
                $this->session->set_flashdata('error','Invalid period format. Use YYYY-MM.');
                redirect('payroll/generate');
                return;
            }
            $meta = [
                'pay_mode' => $this->input->post('pay_mode'),
                'bank_name' => $this->input->post('bank_name'),
                'bank_ac_no' => $this->input->post('bank_ac_no'),
                'pan_no' => $this->input->post('pan_no'),
                'location' => $this->input->post('location'),
                'payment_days' => $this->input->post('payment_days'),
                'present_days' => $this->input->post('present_days'),
                'paid_leaves' => $this->input->post('paid_leaves'),
                'leave_without_pay' => $this->input->post('leave_without_pay'),
                'balance_leaves' => $this->input->post('balance_leaves'),
            ];
            $id = $this->payroll->generate_payslip($user_id, $period, $remarks, $meta);
            if (!$id){
                $this->session->set_flashdata('error','Salary structure not found for selected employee.');
                redirect('payroll/generate');
                return;
            }
            $this->session->set_flashdata('success','Payslip generated.');
            redirect('payroll/view/'.$id);
            return;
        }
        $users = $this->payroll->get_user_options();
        $this->load->view('payroll/generate', ['users' => $users]);
    }

    // AJAX: Get default payslip meta for selected employee
    public function employee_meta($user_id = null){
        $user_id = (int)$user_id;
        $this->output->set_content_type('application/json');
        if ($user_id <= 0){
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'error' => 'Invalid employee.']);
            return;
        }

        // Default response structure
        $data = [
            'pay_mode' => '',
            'bank_name' => '',
            'bank_ac_no' => '',
            'pan_no' => '',
            'location' => '',
            'payment_days' => '',
            'present_days' => '',
            'paid_leaves' => '',
            'leave_without_pay' => '',
            'balance_leaves' => '',
        ];

        // Optional month period (YYYY-MM) for attendance/leave summary.
        // If not provided, default to current month so that selecting an employee
        // alone still gives useful attendance/leave data.
        $period = trim((string)$this->input->get('period'));
        if ($period === ''){
            $period = date('Y-m');
        }
        $from = null;
        $to = null;
        if (preg_match('/^(\d{4})-(\d{2})$/', $period)){
            $from = $period.'-01';
            $to = date('Y-m-t', strtotime($from));
        }

        // 1) Try to use latest payslip for bank / PAN / location defaults
        if ($this->db->table_exists('payslips')){
            $row = $this->db
                ->from('payslips')
                ->where('user_id', $user_id)
                ->order_by('period', 'DESC')
                ->limit(1)
                ->get()
                ->row();
            if ($row){
                foreach (['pay_mode','bank_name','bank_ac_no','pan_no','location'] as $field){
                    if (property_exists($row, $field)){
                        $val = $row->$field;
                        if ($val !== null && $val !== ''){
                            $data[$field] = (string)$val;
                        }
                    }
                }
            }
        }

        // 2) Enrich location and bank/PAN details from employees table if available
        if ($this->db->table_exists('employees')){
            $emp = $this->db->get_where('employees', ['user_id' => $user_id])->row();
            if ($emp){
                if ($data['location'] === ''){
                    if (!empty($emp->location))      { $data['location'] = (string)$emp->location; }
                    elseif (!empty($emp->city))      { $data['location'] = (string)$emp->city; }
                    elseif (!empty($emp->state))     { $data['location'] = (string)$emp->state; }
                    elseif (!empty($emp->country))   { $data['location'] = (string)$emp->country; }
                }
                if ($data['bank_name'] === '' && isset($emp->bank_name) && $emp->bank_name !== ''){
                    $data['bank_name'] = (string)$emp->bank_name;
                }
                if ($data['bank_ac_no'] === '' && isset($emp->bank_ac_no) && $emp->bank_ac_no !== ''){
                    $data['bank_ac_no'] = (string)$emp->bank_ac_no;
                }
                if ($data['pan_no'] === '' && isset($emp->pan_no) && $emp->pan_no !== ''){
                    $data['pan_no'] = (string)$emp->pan_no;
                }
            }
        }

        // 3) Attendance + Leave summary for given month
        $presentDays = 0.0;
        $paidLeaves = 0.0;
        $lwp = 0.0;
        $balLeaves = 0.0;

        if ($from !== null && $to !== null){
            // Present days from attendance table (if it exists)
            if ($this->db->table_exists('attendance')){
                $rows = $this->db->select('status, COUNT(*) AS cnt')
                    ->from('attendance')
                    ->where('user_id', $user_id)
                    ->where('att_date >=', $from)
                    ->where('att_date <=', $to)
                    ->group_by('status')
                    ->get()->result();
                foreach ($rows as $r){
                    $cnt = (float)$r->cnt;
                    $st = (string)$r->status;
                    if ($st === 'present' || $st === 'work_from_home'){
                        $presentDays += $cnt;
                    } elseif ($st === 'half_day'){
                        $presentDays += 0.5 * $cnt;
                    }
                }
            }

            // Paid / unpaid leaves from leave_requests + leave_types
            if ($this->db->table_exists('leave_requests') && $this->db->table_exists('leave_types')){
                $rows = $this->db->select('lr.days, lt.is_paid')
                    ->from('leave_requests lr')
                    ->join('leave_types lt','lt.id = lr.type_id','left')
                    ->where('lr.user_id', $user_id)
                    ->where_in('lr.status', ['lead_approved','hr_approved'])
                    ->where('lr.start_date <=', $to)
                    ->where('lr.end_date >=', $from)
                    ->get()->result();
                foreach ($rows as $r){
                    $days = isset($r->days) ? (float)$r->days : 0.0;
                    $isPaid = isset($r->is_paid) ? (int)$r->is_paid : 1;
                    if ($days <= 0){ continue; }
                    if ($isPaid){ $paidLeaves += $days; }
                    else { $lwp += $days; }
                }
            }

            // Leave balance (sum across leave_balances for current year)
            if ($this->db->table_exists('leave_balances')){
                $year = (int)substr($from, 0, 4);
                $row = $this->db->select('SUM(opening_balance + accrued - used) AS available')
                    ->from('leave_balances')
                    ->where('user_id', $user_id)
                    ->where('year', $year)
                    ->get()->row();
                if ($row && $row->available !== null){
                    $balLeaves = (float)$row->available;
                }
            }

            // Payment days = working days in month - unpaid leave
            if (function_exists('workdays_between')){
                $workingDays = (float) workdays_between($from, $to);
                $paymentDays = $workingDays - $lwp;
                if ($paymentDays < 0) { $paymentDays = 0.0; }
                if ($paymentDays > 0){
                    $data['payment_days'] = number_format($paymentDays, 2, '.', '');
                }
            }

            if ($presentDays > 0){
                $data['present_days'] = number_format($presentDays, 2, '.', '');
            }
            if ($paidLeaves > 0){
                $data['paid_leaves'] = number_format($paidLeaves, 2, '.', '');
            }
            if ($lwp > 0){
                $data['leave_without_pay'] = number_format($lwp, 2, '.', '');
            }
            if ($balLeaves > 0){
                $data['balance_leaves'] = number_format($balLeaves, 2, '.', '');
            }
        }

        echo json_encode(['success' => true, 'data' => $data]);
    }

    // View payslip
    public function view($id = null){
        $id = (int)$id;
        $row = $this->payroll->find_payslip($id);
        if (!$row){ show_404(); }
        
        // Ownership check: non-admin users can only view their own payslips
        $current_user_id = (int)$this->session->userdata('user_id');
        $current_role_id = (int)$this->session->userdata('role_id');
        $is_admin = in_array($current_role_id, [1, 2], true);
        if (!$is_admin && (int)$row->user_id !== $current_user_id) {
            show_error('You do not have permission to view this payslip.', 403);
        }
        $this->load->model('Setting_model','settings');
        $settings = $this->settings->get_all_settings();
        // For payslip, render without global navbar/sidebar so print shows only slip
        $data = [
            'row' => $row,
            'settings' => $settings,
            'hide_navbar' => true,
            'with_sidebar' => false,
            'full_width' => true,
        ];
        $this->load->view('payroll/payslip_view', $data);
    }

    /**
     * GET /payroll/export/{id}
     * Export a single payslip as CSV, or all payslips if no id given
     */
    public function export($id = null){
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = in_array($role_id, [1, 2], true);

        if (!$is_admin && !(function_exists('has_module_access') && has_module_access('payroll_manage'))) {
            show_error('Access denied.', 403);
        }

        if ($id) {
            $rows = array();
            $row = $this->payroll->find_payslip((int)$id);
            if ($row) { $rows[] = $row; }
        } else {
            $month = $this->input->get('month') ? $this->input->get('month') : date('Y-m');
            $this->db->select('ps.*, u.name as employee_name, u.email as employee_email');
            $this->db->from('payslips ps');
            $this->db->join('users u', 'u.id = ps.employee_id', 'left');
            if ($month) { $this->db->where('ps.pay_period', $month); }
            $this->db->order_by('ps.pay_period', 'DESC');
            $rows = $this->db->get()->result();
        }

        $filename = $id ? 'payslip_' . $id . '.csv' : 'payroll_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Employee', 'Email', 'Pay Period', 'Basic Salary', 'Allowances', 'Deductions', 'Net Pay', 'Status', 'Generated At']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r->id,
                isset($r->employee_name) ? $r->employee_name : $r->employee_id,
                isset($r->employee_email) ? $r->employee_email : '',
                $r->pay_period,
                isset($r->basic_salary)  ? $r->basic_salary  : '',
                isset($r->allowances)    ? $r->allowances    : '',
                isset($r->deductions)    ? $r->deductions    : '',
                isset($r->net_pay)       ? $r->net_pay       : '',
                $r->status,
                $r->created_at,
            ]);
        }
        fclose($out);
        exit;
    }
}
