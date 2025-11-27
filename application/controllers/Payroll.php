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
        $allowed = function_exists('has_module_access') && has_module_access('payroll');
        if (!$allowed){
            $role_id = (int)$this->session->userdata('role_id');
            if (!in_array($role_id, [1,2], true)) { show_error('Access Denied', 403); }
        }
        $this->load->model('Payroll_model', 'payroll');
    }

    public function index(){
        redirect('payroll/payslips');
    }

    // Manage salary structures per user
    public function structures(){
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
            ];
            $this->payroll->save_structure($user_id, $data);
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
        $filters = [
            'period' => $this->input->get('period'),
            'user_id' => $this->input->get('user_id'),
        ];
        $rows = $this->payroll->list_payslips($filters);
        $users = $this->payroll->get_user_options();
        $this->load->view('payroll/payslips', ['rows' => $rows, 'filters' => $filters, 'users' => $users]);
    }

    // POST /payroll/send_payslips
    public function send_payslips(){
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
        if (!is_dir($pdfDir)) { @mkdir($pdfDir, 0777, true); }

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
            $company_name = isset($settings->company_name) && $settings->company_name ? $settings->company_name : $settings->company_name;
            
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

            $this->email->clear(true);
            $fromAddr = getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr===''){
                $fromAddr = 'no-reply@example.com';
            }
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
}
