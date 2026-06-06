<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Reports_base.php';

/**
 * HR reports: leaves, payroll, expenses, performance.
 */
class Reports_hr extends Reports_base {

    // GET /reports/leaves
    public function leaves()
    {
        require_module_access(['reports', 'reports_leaves'], true);
        // Get filters from GET parameters
        $filters = [
            'status' => $this->input->get('status'),
            'user_id' => $this->input->get('user_id'),
            'leave_type' => $this->input->get('leave_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
        ];

        $monthly = [];
        $by_status = [];
        $by_employee = [];
        $recent_leaves = [];
        $leave_types = [];
        
        // Determine which table to use
        $use_leave_requests = $this->db->table_exists('leave_requests');
        $use_leaves = $this->db->table_exists('leaves');
        
        if ($use_leave_requests) {
            // Apply filters to queries
            $this->db->where('1=1'); // Base condition
            
            if (!empty($filters['status'])) {
                $this->db->where('status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $this->db->where('user_id', (int)$filters['user_id']);
            }
            if (!empty($filters['leave_type'])) {
                $this->db->where('leave_type', $filters['leave_type']);
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('end_date <=', $filters['date_to']);
            }
            
            // Monthly trends (with filters applied)
            $date_filter = '';
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                if (!empty($filters['date_from'])) {
                    $date_filter .= " AND start_date >= '" . $filters['date_from'] . "'";
                }
                if (!empty($filters['date_to'])) {
                    $date_filter .= " AND end_date <= '" . $filters['date_to'] . "'";
                }
            } else {
                $date_filter = " AND start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            }
            
            $monthly_sql = "SELECT DATE_FORMAT(start_date, '%Y-%m') ym, SUM(days) AS total_days 
                           FROM leave_requests WHERE 1=1 $date_filter"
                           . hierarchy_sql_user_filter('user_id')
                           . " GROUP BY ym ORDER BY ym";
            $monthly = $this->db->query($monthly_sql)->result();
            
            // Status breakdown (with filters applied)
            $fields = $this->db->list_fields('leave_requests');
            $select_fields = ['status', 'COUNT(*) AS cnt'];
            
            // Add SUM(days) if days field exists
            if (in_array('days', $fields)) {
                $select_fields[] = 'SUM(days) AS total_days';
            }
            
            $this->db->select(implode(', ', $select_fields))->from('leave_requests');
            apply_role_hierarchy_filter($this->db, 'user_id');
            if (!empty($filters['user_id'])) {
                $this->db->where('user_id', (int)$filters['user_id']);
            }
            if (!empty($filters['leave_type']) && in_array('leave_type', $fields)) {
                $this->db->where('leave_type', $filters['leave_type']);
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('end_date <=', $filters['date_to']);
            }
            $by_status = $this->db->group_by('status')->get()->result();
            
            // Employee breakdown
            $fields = $this->db->list_fields('leave_requests');
            $select_fields = ['user_id', 'COUNT(*) AS cnt'];
            
            // Add SUM(days) if days field exists
            if (in_array('days', $fields)) {
                $select_fields[] = 'SUM(days) AS total_days';
            }
            
            $this->db->select(implode(', ', $select_fields))->from('leave_requests');
            apply_role_hierarchy_filter($this->db, 'user_id');
            if (!empty($filters['status'])) {
                $this->db->where('status', $filters['status']);
            }
            if (!empty($filters['leave_type']) && in_array('leave_type', $fields)) {
                $this->db->where('leave_type', $filters['leave_type']);
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('end_date <=', $filters['date_to']);
            }
            $this->db->group_by('user_id')->order_by(isset($fields['total_days']) ? 'total_days' : 'cnt', 'DESC')->limit(10);
            $emp_data = $this->db->get()->result();
            
            // Resolve employee names
            foreach ($emp_data as $emp) {
                $name = $this->get_user_name((int)$emp->user_id);
                $by_employee[] = (object)[
                    'user_id' => (int)$emp->user_id,
                    'name' => $name,
                    'cnt' => (int)$emp->cnt,
                    'total_days' => isset($emp->total_days) ? (float)$emp->total_days : (int)$emp->cnt
                ];
            }
            
            // Recent leaves for detailed view
            $fields = $this->db->list_fields('leave_requests');
            $select_fields = ['id', 'user_id', 'start_date', 'end_date', 'days', 'status'];
            
            // Add leave_type if it exists
            if (in_array('leave_type', $fields)) {
                $select_fields[] = 'leave_type';
            }
            // Add reason if it exists
            if (in_array('reason', $fields)) {
                $select_fields[] = 'reason';
            }
            
            $this->db->select(implode(', ', $select_fields))->from('leave_requests');
            apply_role_hierarchy_filter($this->db, 'user_id');
            if (!empty($filters['status'])) {
                $this->db->where('status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $this->db->where('user_id', (int)$filters['user_id']);
            }
            if (!empty($filters['leave_type']) && in_array('leave_type', $fields)) {
                $this->db->where('leave_type', $filters['leave_type']);
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('end_date <=', $filters['date_to']);
            }
            
            // Order by created_at if it exists, otherwise by start_date
            if (in_array('created_at', $fields)) {
                $this->db->order_by('created_at', 'DESC');
            } else {
                $this->db->order_by('start_date', 'DESC');
            }
            
            $this->db->limit(20);
            $recent_data = $this->db->get()->result();
            
            foreach ($recent_data as $leave) {
                $recent_leaves[] = (object)[
                    'id' => (int)$leave->id,
                    'user_id' => (int)$leave->user_id,
                    'user_name' => $this->get_user_name((int)$leave->user_id),
                    'leave_type' => isset($leave->leave_type) ? $leave->leave_type : 'leave',
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'days' => (float)$leave->days,
                    'status' => $leave->status,
                    'reason' => isset($leave->reason) ? $leave->reason : ''
                ];
            }
            
            // Get distinct leave types only if the column exists
            if (in_array('leave_type', $fields)) {
                $this->db->select('DISTINCT(leave_type)')->from('leave_requests');
                apply_role_hierarchy_filter($this->db, 'user_id');
                $leave_types = $this->db->get()->result();
            } else {
                $leave_types = [];
            }
            
        } elseif ($use_leaves) {
            // Fallback for old leaves table structure
            $date_filter = '';
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                if (!empty($filters['date_from'])) {
                    $date_filter .= " AND start_date >= '" . $filters['date_from'] . "'";
                }
                if (!empty($filters['date_to'])) {
                    $date_filter .= " AND start_date <= '" . $filters['date_to'] . "'";
                }
            } else {
                $date_filter = " AND start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            }
            
            $monthly_sql = "SELECT DATE_FORMAT(start_date, '%Y-%m') ym, COUNT(*) AS total_days 
                           FROM leaves WHERE 1=1 $date_filter"
                           . hierarchy_sql_user_filter('user_id')
                           . " GROUP BY ym ORDER BY ym";
            $monthly = $this->db->query($monthly_sql)->result();
            
            $this->db->select('status, COUNT(*) AS cnt')->from('leaves');
            apply_role_hierarchy_filter($this->db, 'user_id');
            if (!empty($filters['user_id'])) {
                $this->db->where('user_id', (int)$filters['user_id']);
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('start_date <=', $filters['date_to']);
            }
            $by_status = $this->db->group_by('status')->get()->result();
        }
        
        // Get filter options
        $filter_options = [
            'users' => [],
            'statuses' => ['pending', 'lead_approved', 'hr_approved', 'rejected', 'cancelled'],
            'leave_types' => []
        ];
        
        if ($this->db->table_exists('users')) {
            $filter_options['users'] = $this->db->select('id, email')->from('users')->order_by('email')->get()->result();
        }
        
        foreach ($leave_types as $type) {
            $filter_options['leave_types'][] = $type->leave_type;
        }

        $this->load->view('reports/leaves', [
            'monthly' => $monthly,
            'by_status' => $by_status,
            'by_employee' => $by_employee,
            'recent_leaves' => $recent_leaves,
            'filters' => $filters,
            'filter_options' => $filter_options
        ]);
        
        // Handle CSV export
        if ($this->input->get('export') === 'csv') {
            $this->export_leaves_csv($recent_leaves, $by_status, $by_employee, $filters);
        }
    }
    private function export_leaves_csv($recent_leaves, $by_status, $by_employee, $filters) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leaves_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Summary section
        fputcsv($output, ['LEAVES REPORT - ' . date('Y-m-d')]);
        fputcsv($output, []);
        
        // Status breakdown
        fputcsv($output, ['STATUS BREAKDOWN']);
        fputcsv($output, ['Status', 'Requests', 'Total Days']);
        foreach ($by_status as $status) {
            fputcsv($output, [
                $status->status,
                (int)$status->cnt,
                isset($status->total_days) ? (float)$status->total_days : '-'
            ]);
        }
        
        fputcsv($output, []);
        
        // Employee breakdown
        fputcsv($output, ['EMPLOYEE BREAKDOWN']);
        fputcsv($output, ['Employee', 'Requests', 'Total Days']);
        foreach ($by_employee as $emp) {
            fputcsv($output, [
                $emp->name,
                (int)$emp->cnt,
                (float)$emp->total_days
            ]);
        }
        
        fputcsv($output, []);
        
        // Detailed leaves
        fputcsv($output, ['DETAILED LEAVES']);
        fputcsv($output, ['ID', 'Employee', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status', 'Reason']);
        foreach ($recent_leaves as $leave) {
            fputcsv($output, [
                $leave->id,
                $leave->user_name,
                $leave->leave_type,
                $leave->start_date,
                $leave->end_date,
                (float)$leave->days,
                $leave->status,
                $leave->reason
            ]);
        }
        
        fclose($output);
        exit;
    }
    // ── Payroll Report ────────────────────────────────────────────────────────
    public function payroll() {
        require_module_access(['reports', 'reports_payroll'], true);

        $month      = $this->input->get('month') ? $this->input->get('month') : date('Y-m');
        $department = $this->input->get('department') ? $this->input->get('department') : '';

        $payslips = [];
        $summary  = ['total_gross' => 0, 'total_deductions' => 0, 'total_net' => 0, 'count' => 0];

        if ($this->db->table_exists('payslips')) {
            $psCols = payslip_schema_columns($this->db);
            $period_col = $psCols['period_col'];
            $user_col   = $psCols['user_col'];
            $sel = 'ps.*, u.name as employee_name, u.email as employee_email, e.department';
            if ($period_col !== 'pay_period') { $sel .= ', ps.' . $period_col . ' as pay_period'; }
            if (!$psCols['has_gross_salary']) {
                $sel .= ', ps.gross as gross_salary, ps.net as net_salary, ps.deductions as total_deductions';
            }
            $this->db->select($sel);
            $this->db->from('payslips ps');
            $this->db->join('users u', "u.id = ps.{$user_col}", 'left');
            $this->db->join('employees e', "e.user_id = ps.{$user_col}", 'left');
            $this->db->like("ps.{$period_col}", $month);
            if ($department) { $this->db->where('e.department', $department); }
            $this->db->order_by('u.name', 'ASC');
            $payslips = $this->db->get()->result();

            foreach ($payslips as $p) {
                $summary['total_gross']      += isset($p->gross_salary) ? (float)$p->gross_salary : 0;
                $summary['total_deductions'] += isset($p->total_deductions) ? (float)$p->total_deductions : 0;
                $summary['total_net']        += isset($p->net_salary) ? (float)$p->net_salary : 0;
                $summary['count']++;
            }
        }

        $departments = [];
        if ($this->db->table_exists('employees')) {
            $rows = $this->db
                ->distinct()
                ->select('department')
                ->where('department IS NOT NULL')
                ->where('department !=', '')
                ->get('employees')
                ->result();
            foreach ($rows as $r) { $departments[] = $r->department; }
        }

        if ($this->input->get('export') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="payroll_report_' . $month . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Email', 'Department', 'Pay Period', 'Gross Salary', 'Deductions', 'Net Salary', 'Status']);
            foreach ($payslips as $p) {
                fputcsv($out, [
                    $p->employee_name, $p->employee_email, $p->department,
                    isset($p->pay_period) ? $p->pay_period : $month,
                    isset($p->gross_salary) ? $p->gross_salary : 0,
                    isset($p->total_deductions) ? $p->total_deductions : 0,
                    isset($p->net_salary) ? $p->net_salary : 0,
                    isset($p->status) ? $p->status : '',
                ]);
            }
            fclose($out);
            exit;
        }

        $this->load->view('reports/payroll', [
            'payslips'    => $payslips,
            'summary'     => $summary,
            'month'       => $month,
            'department'  => $department,
            'departments' => $departments,
        ]);
    }

    // ── Expenses Report ───────────────────────────────────────────────────────
    public function expenses() {
        require_module_access(['reports', 'reports_expenses'], true);

        $date_from  = $this->input->get('date_from') ? $this->input->get('date_from') : date('Y-m-01');
        $date_to    = $this->input->get('date_to')   ? $this->input->get('date_to')   : date('Y-m-d');
        $status     = $this->input->get('status')    ? $this->input->get('status')    : '';
        $category   = $this->input->get('category')  ? $this->input->get('category')  : '';

        $expenses = [];
        $summary  = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'count' => 0];
        $by_category = [];

        if ($this->db->table_exists('expenses')) {
            $this->db->select('ex.*, u.name as employee_name, u.email as employee_email, ec.name as category_name');
            $this->db->from('expenses ex');
            $this->db->join('users u', 'u.id = ex.user_id', 'left');
            $this->db->join('expense_categories ec', 'ec.id = ex.category_id', 'left');
            $this->db->where('ex.expense_date >=', $date_from);
            $this->db->where('ex.expense_date <=', $date_to);
            if ($status)   { $this->db->where('ex.status', $status); }
            if ($category) { $this->db->where('ex.category_id', (int)$category); }
            $this->db->order_by('ex.expense_date', 'DESC');
            $expenses = $this->db->get()->result();

            foreach ($expenses as $e) {
                $amt = isset($e->amount) ? (float)$e->amount : 0;
                $summary['total'] += $amt;
                $summary['count']++;
                $st = isset($e->status) ? $e->status : '';
                if ($st === 'approved')  { $summary['approved']  += $amt; }
                if ($st === 'pending')   { $summary['pending']   += $amt; }
                if ($st === 'rejected')  { $summary['rejected']  += $amt; }
                $cat = isset($e->category_name) ? $e->category_name : 'Uncategorised';
                if (!isset($by_category[$cat])) { $by_category[$cat] = 0; }
                $by_category[$cat] += $amt;
            }
        }

        $categories = [];
        if ($this->db->table_exists('expense_categories')) {
            $categories = $this->db->order_by('name')->get('expense_categories')->result();
        }

        if ($this->input->get('export') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="expenses_report_' . $date_from . '_to_' . $date_to . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Employee', 'Category', 'Description', 'Amount', 'Status', 'Submitted']);
            foreach ($expenses as $e) {
                fputcsv($out, [
                    isset($e->expense_date) ? $e->expense_date : '',
                    $e->employee_name, $e->category_name,
                    isset($e->description) ? $e->description : '',
                    isset($e->amount) ? $e->amount : 0,
                    isset($e->status) ? $e->status : '',
                    isset($e->created_at) ? $e->created_at : '',
                ]);
            }
            fclose($out);
            exit;
        }

        $this->load->view('reports/expenses', [
            'expenses'    => $expenses,
            'summary'     => $summary,
            'by_category' => $by_category,
            'date_from'   => $date_from,
            'date_to'     => $date_to,
            'status'      => $status,
            'category'    => $category,
            'categories'  => $categories,
        ]);
    }

    // ── Performance Appraisals Report ─────────────────────────────────────────
    public function performance() {
        require_module_access(['reports', 'reports_performance'], true);

        $period     = $this->input->get('period') ? trim($this->input->get('period')) : '';
        $status     = $this->input->get('status') ? trim($this->input->get('status')) : '';
        $department = $this->input->get('department') ? trim($this->input->get('department')) : '';

        $appraisals = [];
        $summary = [
            'count' => 0,
            'avg_kpi' => 0,
            'avg_rating' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
        ];

        if ($this->db->table_exists('performance_appraisals')) {
            $this->db->select('p.*, e.first_name, e.last_name, e.department, u.name as manager_name');
            $this->db->from('performance_appraisals p');
            $this->db->join('employees e', 'e.id = p.employee_id', 'left');
            $this->db->join('users u', 'u.id = p.manager_id', 'left');
            if ($period !== '') {
                $this->db->like('p.period', $period);
            }
            if ($status !== '') {
                $this->db->where('p.status', $status);
            }
            if ($department !== '') {
                $this->db->where('e.department', $department);
            }
            if (function_exists('apply_role_hierarchy_filter') && $this->schema_has_column('performance_appraisals', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'p.manager_id');
            }
            $this->db->order_by('p.created_at', 'DESC');
            $appraisals = $this->db->get()->result();

            $kpi_total = 0;
            $rating_total = 0;
            $rating_count = 0;
            foreach ($appraisals as $row) {
                $summary['count']++;
                $kpi_total += isset($row->kpi_score) ? (float) $row->kpi_score : 0;
                if (isset($row->rating) && $row->rating !== null && $row->rating !== '') {
                    $rating_total += (int) $row->rating;
                    $rating_count++;
                }
                $st = isset($row->status) ? $row->status : 'draft';
                if (isset($summary[$st])) {
                    $summary[$st]++;
                }
            }
            if ($summary['count'] > 0) {
                $summary['avg_kpi'] = round($kpi_total / $summary['count'], 2);
            }
            if ($rating_count > 0) {
                $summary['avg_rating'] = round($rating_total / $rating_count, 2);
            }
        }

        $departments = [];
        if ($this->db->table_exists('employees')) {
            $rows = $this->db
                ->distinct()
                ->select('department')
                ->where('department IS NOT NULL')
                ->where('department !=', '')
                ->get('employees')
                ->result();
            foreach ($rows as $r) {
                $departments[] = $r->department;
            }
        }

        $periods = [];
        if ($this->db->table_exists('performance_appraisals')) {
            $prows = $this->db
                ->distinct()
                ->select('period')
                ->where('period IS NOT NULL')
                ->where('period !=', '')
                ->order_by('period', 'DESC')
                ->get('performance_appraisals')
                ->result();
            foreach ($prows as $pr) {
                $periods[] = $pr->period;
            }
        }

        if ($this->input->get('export') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="performance_report_' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Department', 'Period', 'KPI Score', 'Rating', 'Self Rating', 'Status', 'Manager', 'Updated']);
            foreach ($appraisals as $a) {
                $emp_name = trim((isset($a->first_name) ? $a->first_name : '') . ' ' . (isset($a->last_name) ? $a->last_name : ''));
                fputcsv($out, [
                    $emp_name !== '' ? $emp_name : '—',
                    isset($a->department) ? $a->department : '',
                    isset($a->period) ? $a->period : '',
                    isset($a->kpi_score) ? $a->kpi_score : '',
                    isset($a->rating) ? $a->rating : '',
                    isset($a->self_rating) ? $a->self_rating : '',
                    isset($a->status) ? $a->status : '',
                    isset($a->manager_name) ? $a->manager_name : '',
                    isset($a->updated_at) ? $a->updated_at : (isset($a->created_at) ? $a->created_at : ''),
                ]);
            }
            fclose($out);
            exit;
        }

        $this->load->view('reports/performance', [
            'appraisals'  => $appraisals,
            'summary'     => $summary,
            'period'      => $period,
            'status'      => $status,
            'department'  => $department,
            'departments' => $departments,
            'periods'     => $periods,
        ]);
    }
}
