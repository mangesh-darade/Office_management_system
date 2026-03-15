<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','permission']);
        $this->load->library('session');
        $this->load->model('Report_model');
        if ($this->db->table_exists('settings')) {
            $this->load->model('Setting_model', 'settings');
        }
        
        // Authentication check
        if (!(int)$this->session->userdata('user_id')) {
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            redirect('auth/login');
        }
        // Permission check - allow access if user has reports OR any specific report sub-permission
        if (function_exists('has_module_access')) {
            $has_any_report = has_module_access('reports')
                || has_module_access('reports_overview')
                || has_module_access('reports_requirements')
                || has_module_access('reports_tasks_assignment')
                || has_module_access('reports_projects_status')
                || has_module_access('reports_leaves')
                || has_module_access('reports_attendance')
                || has_module_access('reports_attendance_employee')
                || has_module_access('reports_daily_activity')
                || has_module_access('daily_activity_report')
                || has_module_access('analytics');
            if (!$has_any_report) {
                show_error('You do not have permission to access Reports.', 403);
            }
        }
    }

    public function index() {
        // Basic aggregates for charts with safe guards if tables are missing
        $task_status = [];
        $projects_progress = [];
        $leaves_monthly = [];
        $leaves_by_status = [];
        $task_by_assignee = [];
        $attendance_recent = [];
        $attendance_late_top = [];

        if ($this->db->table_exists('tasks')) {
            $task_status = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('tasks')->result();
        }
        if ($this->db->table_exists('projects')) {
            $projects_progress = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('projects')->result();
        }
        if ($this->db->table_exists('leave_requests')) {
            $leaves_monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, SUM(days) AS total_days FROM leave_requests WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
            $leaves_by_status = $this->db->select('status, COUNT(*) AS cnt, SUM(days) AS total_days')->from('leave_requests')->group_by('status')->get()->result();
        } elseif ($this->db->table_exists('leaves')) {
            $leaves_monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, COUNT(*) AS total_days FROM leaves WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
            $leaves_by_status = $this->db->select('status, COUNT(*) AS cnt')->from('leaves')->group_by('status')->get()->result();
        }
        if ($this->db->table_exists('tasks')) {
            // Top 10 assignees by number of tasks
            $this->db->select('t.assigned_to, COUNT(*) AS cnt')->from('tasks t')->group_by('t.assigned_to')->order_by('cnt','DESC')->limit(10);
            if ($this->db->table_exists('users')) {
                $this->db->select('u.email');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
                if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                $this->db->join('users u','u.id = t.assigned_to','left');
            }
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                $this->db->join('employees e','e.user_id = t.assigned_to','left');
            }
            $rows = $this->db->get()->result();
            foreach ($rows as $r){
                $label = '';
                if (isset($r->emp_name) && trim((string)$r->emp_name) !== '') { $label = $r->emp_name; }
                else if (isset($r->full_name) && trim((string)$r->full_name) !== '') { $label = $r->full_name; }
                else if (isset($r->name) && trim((string)$r->name) !== '') { $label = $r->name; }
                else if (isset($r->email)) { $label = $r->email; }
                else { $label = ($r->assigned_to ? ('User #'.(int)$r->assigned_to) : 'Unassigned'); }
                $task_by_assignee[] = (object)['label'=>$label,'cnt'=>(int)$r->cnt];
            }
        }
        // Number of days to show in recent attendance chart (dashboard)
        $attendance_days = (int)$this->input->get('att_days');
        if ($attendance_days <= 0) { $attendance_days = 14; }
        if ($attendance_days > 90) { $attendance_days = 90; }

        // Determine group for scoping analytics (admin group sees all, others see own data)
        $currentUserId = (int)$this->session->userdata('user_id');
        $isAdminGroup = function_exists('is_admin_group') && is_admin_group();

        if ($this->db->table_exists('attendance')) {
            // Detect user and date columns
            $fields = $this->db->list_fields('attendance');
            $userCandidates = array('user_id','employee_id','emp_id','staff_id','uid');
            $dateCandidates = array('date','attendance_date','att_date','created_at','checked_at');
            $userCol = $dateCol = null;
            foreach ($userCandidates as $c){ if (in_array($c, $fields, true)) { $userCol = $c; break; } }
            foreach ($dateCandidates as $c){ if (in_array($c, $fields, true)) { $dateCol = $c; break; } }
            if ($dateCol === null && isset($fields[0])) { $dateCol = $fields[0]; }

            // Recent attendance counts (last N days)
            if ($dateCol !== null) {
                $sql = "SELECT DATE(`$dateCol`) AS d, COUNT(*) cnt
                        FROM attendance
                        WHERE `$dateCol` >= DATE_SUB(CURDATE(), INTERVAL ".$attendance_days." DAY)";
                if ($userCol !== null && $currentUserId && !$isAdminGroup) {
                    $sql .= " AND `$userCol` = ".(int)$currentUserId;
                }
                $sql .= " GROUP BY DATE(`$dateCol`) ORDER BY d";
                $attendance_recent = $this->db->query($sql)->result();
            }

            // Late mark summary (top late employees over last 30 days)
            if ($userCol !== null) {
                // Determine check-in column
                $checkInCol = null;
                if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
                elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }

                if ($checkInCol !== null) {
                    // Office start and grace from settings with defaults
                    $officeStart = '09:30';
                    $graceMinutes = 15;
                    if (isset($this->settings)) {
                        try {
                            $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                            if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) { $officeStart = $stVal; }
                            $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                            if (is_numeric($gmVal)) { $graceMinutes = (int)$gmVal; }
                        } catch (Exception $e) { /* ignore */ }
                    }

                    $tBase = strtotime('1970-01-01 '.$officeStart.':00');
                    if ($tBase !== false) {
                        $cutoffTime = date('H:i:s', $tBase + ($graceMinutes * 60));

                        if ($currentUserId && !$isAdminGroup) {
                            // For user group: only show their own late summary
                            $sql = "SELECT `$userCol` AS uid, COUNT(*) AS late_days
                                    FROM attendance
                                    WHERE `$checkInCol` IS NOT NULL
                                      AND TIME(`$checkInCol`) > ?
                                      AND `$dateCol` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                      AND `$userCol` = ?
                                    GROUP BY `$userCol`
                                    LIMIT 1";
                            $attendance_late_top = $this->db->query($sql, [$cutoffTime, $currentUserId])->result();
                        } else {
                            // Admin group: top late employees across org
                            $sql = "SELECT `$userCol` AS uid, COUNT(*) AS late_days
                                    FROM attendance
                                    WHERE `$checkInCol` IS NOT NULL
                                      AND TIME(`$checkInCol`) > ?
                                      AND `$dateCol` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                    GROUP BY `$userCol`
                                    ORDER BY late_days DESC
                                    LIMIT 10";
                            $attendance_late_top = $this->db->query($sql, [$cutoffTime])->result();
                        }

                        // Attach a simple label (prefer employee name, then email, then fallback)
                        $labels = [];
                        if ($this->db->table_exists('users')) {
                            $this->db->select('u.id, u.email');
                            if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
                            if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                                $this->db->join('employees e','e.user_id = u.id','left');
                                if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                                if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
                                if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
                                if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
                            }
                            $users = $this->db->from('users u')->get()->result();
                            foreach ($users as $u) { $labels[(int)$u->id] = $u; }
                        }

                        foreach ($attendance_late_top as $row) {
                            $uid = isset($row->uid) ? (int)$row->uid : 0;
                            $label = isset($labels[$uid]) ? $labels[$uid] : null;
                            $name = '';
                            if ($label) {
                                $empParts = [];
                                if (isset($label->emp_first_name) && trim((string)$label->emp_first_name) !== '') { $empParts[] = trim((string)$label->emp_first_name); }
                                if (isset($label->emp_last_name) && trim((string)$label->emp_last_name) !== '') { $empParts[] = trim((string)$label->emp_last_name); }
                                if (!empty($empParts)) { $name = trim(implode(' ', $empParts)); }
                                elseif (isset($label->emp_full_name) && trim((string)$label->emp_full_name) !== '') { $name = trim((string)$label->emp_full_name); }
                                elseif (isset($label->emp_name) && trim((string)$label->emp_name) !== '') { $name = trim((string)$label->emp_name); }
                                elseif (isset($label->full_name) && trim((string)$label->full_name) !== '') { $name = trim((string)$label->full_name); }
                                elseif (isset($label->name) && trim((string)$label->name) !== '') { $name = trim((string)$label->name); }
                                else { $name = $label->email; }
                            } else {
                                $name = $uid ? ('User #'.$uid) : 'Unknown';
                            }
                            $row->name = $name;
                        }
                    }
                }
            }
        }

        // Derive dynamic date range for recent attendance chart based on selected window
        $attendance_recent_from = date('Y-m-d', strtotime('-'.($attendance_days - 1).' days'));
        $attendance_recent_to   = date('Y-m-d');

        $data = [
            'task_status' => $task_status,
            'projects_progress' => $projects_progress,
            'leaves_monthly' => $leaves_monthly,
            'leaves_by_status' => $leaves_by_status,
            'task_by_assignee' => $task_by_assignee,
            'attendance_recent' => $attendance_recent,
            'attendance_days' => $attendance_days,
            'attendance_recent_from' => $attendance_recent_from,
            'attendance_recent_to' => $attendance_recent_to,
            'attendance_late_top' => $attendance_late_top,
        ];
        $this->load->view('reports/dashboard', $data);
    }

    // GET /reports/requirements
    public function requirements()
    {
        if (!$this->db->table_exists('requirements')) {
            show_error('Requirements table not found', 500);
            return;
        }

        // Get filters from GET parameters
        $filters = [
            'status' => $this->input->get('status'),
            'priority' => $this->input->get('priority'),
            'client_id' => $this->input->get('client_id'),
            'project_id' => $this->input->get('project_id'),
            'search' => $this->input->get('search'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
        ];

        // Build base query with filters
        $this->db->select('r.id, r.title, r.owner_id, r.priority, r.requirement_type, r.budget_estimate, r.expected_delivery_date, r.received_date, r.status as req_status');
        if ($this->db->table_exists('projects')) {
            $this->db->select('p.name AS project_name');
            $this->db->join('projects p','p.id = r.project_id','left');
        }
        if ($this->db->table_exists('clients')) {
            $this->db->select('c.company_name AS client_name');
            $this->db->join('clients c','c.id = r.client_id','left');
        }
        if ($this->db->table_exists('users')) {
            $userSel = ['u.email'];
            if ($this->db->field_exists('full_name','users')) { $userSel[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')) { $userSel[] = 'u.name'; }
            $this->db->select(implode(', ', $userSel));
            $this->db->join('users u','u.id = r.owner_id','left');
        }
        $this->db->from('requirements r');

        // Apply filters
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where('r.priority', $filters['priority']);
        }
        if (!empty($filters['client_id'])) {
            $this->db->where('r.client_id', (int)$filters['client_id']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('r.project_id', (int)$filters['project_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $this->db->group_start()
                ->like('r.title', $search)
                ->or_like('r.req_number', $search)
                ->or_like('p.name', $search)
                ->group_end();
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('r.received_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('r.received_date <=', $filters['date_to']);
        }

        $this->db->order_by('r.created_at','DESC');
        $reqs = $this->db->get()->result();

        // Precompute task counts by requirement_id and status when schema supports relation
        $taskCounts = [];
        if ($this->db->table_exists('tasks') && $this->db->field_exists('requirement_id','tasks')) {
            $rows = $this->db->select('requirement_id, status, COUNT(*) AS cnt')
                             ->from('tasks')
                             ->group_by(['requirement_id','status'])
                             ->get()->result();
            foreach ($rows as $r) {
                $rid = (int)$r->requirement_id;
                $st = (string)$r->status;
                $cnt = (int)$r->cnt;
                if (!isset($taskCounts[$rid])) { $taskCounts[$rid] = []; }
                $taskCounts[$rid][$st] = $cnt;
            }
        }

        // Build final rows with friendly owner name and counts
        $result = [];
        foreach ($reqs as $r){
            $owner = '';
            if (isset($r->full_name) && trim((string)$r->full_name) !== '') { $owner = $r->full_name; }
            elseif (isset($r->name) && trim((string)$r->name) !== '') { $owner = $r->name; }
            elseif (isset($r->email)) { $owner = $r->email; }
            $counts = [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'blocked' => 0,
            ];
            if (isset($taskCounts[(int)$r->id])){
                foreach ($taskCounts[(int)$r->id] as $st=>$cnt){
                    if (isset($counts[$st])) { $counts[$st] = (int)$cnt; }
                }
            }
            $total = array_sum($counts);
            $completion_percentage = $total > 0 ? round(($counts['completed'] / $total) * 100, 1) : 0;
            
            $result[] = (object)[
                'id' => (int)$r->id,
                'title' => (string)$r->title,
                'project_name' => isset($r->project_name)?$r->project_name:'',
                'client_name' => isset($r->client_name)?$r->client_name:'',
                'owner' => $owner,
                'priority' => isset($r->priority)?$r->priority:'medium',
                'requirement_type' => isset($r->requirement_type)?$r->requirement_type:'new_feature',
                'budget_estimate' => isset($r->budget_estimate)?$r->budget_estimate:null,
                'expected_delivery_date' => isset($r->expected_delivery_date)?$r->expected_delivery_date:null,
                'received_date' => isset($r->received_date)?$r->received_date:null,
                'req_status' => isset($r->req_status)?$r->req_status:'received',
                'counts' => $counts,
                'total' => $total,
                'completion_percentage' => $completion_percentage,
            ];
        }

        // Get filter options for dropdowns
        $filter_options = [
            'clients' => [],
            'projects' => [],
            'statuses' => ['received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled'],
            'priorities' => ['low','medium','high','critical']
        ];
        
        if ($this->db->table_exists('clients')) {
            $filter_options['clients'] = $this->db->select('id, company_name')->order_by('company_name')->get('clients')->result();
        }
        if ($this->db->table_exists('projects')) {
            $filter_options['projects'] = $this->db->select('id, name')->order_by('name')->get('projects')->result();
        }

        $this->load->view('reports/requirements', [
            'rows' => $result,
            'filters' => $filters,
            'filter_options' => $filter_options
        ]);
        
        // Handle CSV export
        if ($this->input->get('export') === 'csv') {
            $this->export_requirements_csv($result, $filters);
        }
    }
    
    private function export_requirements_csv($rows, $filters) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="requirements_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Title', 'Type', 'Client', 'Project', 'Owner', 'Priority', 
            'Status', 'Budget', 'Expected Delivery', 'Received Date',
            'Total Tasks', 'Completed', 'In Progress', 'Pending', 'Blocked', 'Completion %'
        ]);
        
        // CSV data
        foreach ($rows as $r) {
            fputcsv($output, [
                $r->id,
                $r->title,
                ucfirst(str_replace('_', ' ', $r->requirement_type)),
                $r->client_name,
                $r->project_name,
                $r->owner,
                ucfirst($r->priority),
                ucfirst(str_replace('_', ' ', $r->req_status)),
                $r->budget_estimate ? '₹' . number_format($r->budget_estimate, 2) : '',
                $r->expected_delivery_date ? date('Y-m-d', strtotime($r->expected_delivery_date)) : '',
                $r->received_date ? date('Y-m-d', strtotime($r->received_date)) : '',
                $r->total,
                $r->counts['completed'],
                $r->counts['in_progress'],
                $r->counts['pending'],
                $r->counts['blocked'],
                $r->completion_percentage . '%'
            ]);
        }
        
        fclose($output);
        exit;
    }

    // GET /reports/export
    public function export_csv()
    {
        $this->load->dbutil();
        // Example combined report: tasks with project and assignee
        $sql = "SELECT t.id, t.title, t.status, p.name AS project, u.email AS assigned_user, t.created_at
                FROM tasks t
                LEFT JOIN projects p ON p.id = t.project_id
                LEFT JOIN users u ON u.id = t.assigned_to
                ORDER BY t.id DESC";
        $query = $this->db->query($sql);
        $out = $this->dbutil->csv_from_result($query);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tasks_'.date('Y-m-d').'.csv"');
        echo $out; exit;
    }

    // GET /reports/tasks-assignment
    public function tasks_assignment()
    {
        // Get filters from GET parameters
        $filters = [
            'project_id' => $this->input->get('project_id'),
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
        ];

        $rows = [];
        if ($this->db->table_exists('tasks')) {
            // Build base query with filters
            $this->db->select('assigned_to, status, COUNT(*) as cnt')->from('tasks');
            
            // Apply filters
            if (!empty($filters['project_id'])) {
                $this->db->where('project_id', (int)$filters['project_id']);
            }
            if (!empty($filters['status'])) {
                $this->db->where('status', $filters['status']);
            }
            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $this->db->group_start()
                    ->like('title', $search)
                    ->or_like('description', $search)
                    ->group_end();
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('created_at >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
            }
            
            $this->db->group_by(['assigned_to','status']);
            $agg = $this->db->get()->result();
            
            $map = [];
            foreach ($agg as $a){
                $uid = (int)$a->assigned_to; $st = (string)$a->status; $cnt = (int)$a->cnt;
                if (!isset($map[$uid])) { $map[$uid] = ['pending'=>0,'in_progress'=>0,'completed'=>0,'blocked'=>0]; }
                if (isset($map[$uid][$st])) { $map[$uid][$st] = $cnt; }
            }
            
            // Get task details per assignee with filters applied
            $titles_map = [];
            $task_details_map = [];
            
            $this->db->select('assigned_to, title, status, project_id, created_at, due_date')->from('tasks');
            
            // Re-apply same filters for task details
            if (!empty($filters['project_id'])) {
                $this->db->where('project_id', (int)$filters['project_id']);
            }
            if (!empty($filters['status'])) {
                $this->db->where('status', $filters['status']);
            }
            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $this->db->group_start()
                    ->like('title', $search)
                    ->or_like('description', $search)
                    ->group_end();
            }
            if (!empty($filters['date_from'])) {
                $this->db->where('created_at >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
            }
            
            $this->db->order_by('id', 'DESC');
            $task_details = $this->db->get()->result();
            
            foreach ($task_details as $task) {
                $uid = (int)$task->assigned_to;
                if (!isset($task_details_map[$uid])) {
                    $task_details_map[$uid] = [];
                }
                $task_details_map[$uid][] = $task;
                
                // Also build concatenated titles for backward compatibility
                if (!isset($titles_map[$uid])) {
                    $titles_map[$uid] = '';
                }
                $titles_map[$uid] = ($titles_map[$uid] ? '; ' : '') . $task->title;
            }
            // Resolve user labels
            $labels = [];
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id, u.email');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
                if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }

                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                    if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
                    if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
                    if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
                    if ($this->db->field_exists('middle_name','employees')) { $this->db->select('e.middle_name AS emp_middle_name'); }
                }

                $users = $this->db->from('users u')->get()->result();
                foreach ($users as $u){ $labels[(int)$u->id] = $u; }
            }
            foreach ($map as $uid=>$counts){
                $label = isset($labels[$uid]) ? $labels[$uid] : null;
                $name = '';
                if ($label){
                    $empParts = [];
                    if (isset($label->emp_first_name) && trim((string)$label->emp_first_name)!=='') { $empParts[] = trim((string)$label->emp_first_name); }
                    if (isset($label->emp_middle_name) && trim((string)$label->emp_middle_name)!=='') { $empParts[] = trim((string)$label->emp_middle_name); }
                    if (isset($label->emp_last_name) && trim((string)$label->emp_last_name)!=='') { $empParts[] = trim((string)$label->emp_last_name); }
                    if (!empty($empParts)) { $name = trim(implode(' ', $empParts)); }
                    else if (isset($label->emp_full_name) && trim((string)$label->emp_full_name)!=='') { $name = trim((string)$label->emp_full_name); }
                    else if (isset($label->emp_name) && trim((string)$label->emp_name)!=='') { $name = trim((string)$label->emp_name); }
                    else if (isset($label->full_name) && trim((string)$label->full_name)!=='') { $name = trim((string)$label->full_name); }
                    else if (isset($label->name) && trim((string)$label->name)!=='') { $name = trim((string)$label->name); }
                    else { $name = $label->email; }
                } else { $name = $uid ? ('User #'.$uid) : 'Unassigned'; }
                $total = array_sum($counts);
                $completion_percentage = $total > 0 ? round(($counts['completed'] / $total) * 100, 1) : 0;
                $titles = isset($titles_map[$uid]) ? $titles_map[$uid] : '';
                $tasks = isset($task_details_map[$uid]) ? $task_details_map[$uid] : [];
                
                $rows[] = (object)[
                    'user_id'=>$uid,
                    'name'=>$name, 
                    'titles'=>$titles,
                    'tasks'=>$tasks,
                    'counts'=>$counts,
                    'total'=>$total,
                    'completion_percentage'=>$completion_percentage
                ];
            }
        }
        
        // Sort by total desc
        usort($rows, function($a,$b){
            if ($b->total == $a->total) return 0;
            return ($b->total < $a->total) ? -1 : 1;
        });
        
        // Get filter options
        $filter_options = [
            'projects' => [],
            'statuses' => ['pending','in_progress','completed','blocked']
        ];
        
        if ($this->db->table_exists('projects')) {
            $filter_options['projects'] = $this->db->select('id, name')->order_by('name')->get('projects')->result();
        }

        $this->load->view('reports/tasks_assignment', [
            'rows'=>$rows,
            'filters'=>$filters,
            'filter_options'=>$filter_options
        ]);
        
        // Handle CSV export
        if ($this->input->get('export') === 'csv') {
            $this->export_tasks_assignment_csv($rows, $filters);
        }
    }
    
    private function export_tasks_assignment_csv($rows, $filters) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tasks_assignment_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Employee', 'Total Tasks', 'Completed', 'In Progress', 'Pending', 'Blocked', 
            'Completion %', 'Task Titles'
        ]);
        
        // CSV data
        foreach ($rows as $r) {
            fputcsv($output, [
                $r->name,
                $r->total,
                $r->counts['completed'],
                $r->counts['in_progress'],
                $r->counts['pending'],
                $r->counts['blocked'],
                $r->completion_percentage . '%',
                $r->titles
            ]);
        }
        
        fclose($output);
        exit;
    }

    // GET /reports/daily-activity
    public function daily_activity()
    {
         $role_id = (int)$this->session->userdata('role_id');
         if ($role_id !== 1 && function_exists('has_module_access') && !has_module_access('daily_activity_report') && !has_module_access('reports_daily_activity') && !has_module_access('reports')) {
             show_error('You do not have permission to access Daily Activity Reports.', 403);
             return;
         }

         // Filters
        $filters = [
            'user_id' => $this->input->get('user_id'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'period' => $this->input->get('period'),
        ];
        
        // Handle Period Shortcuts
        if($filters['period']) {
            switch($filters['period']) {
                case 'daily':
                    $filters['date_from'] = date('Y-m-d');
                    $filters['date_to'] = date('Y-m-d');
                    break;
                case 'weekly':
                    $filters['date_from'] = date('Y-m-d', strtotime('monday this week'));
                    $filters['date_to'] = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'monthly':
                    $filters['date_from'] = date('Y-m-01');
                    $filters['date_to'] = date('Y-m-t');
                    break;
            }
        }
        
        $this->db->select('dl.*, t.title as task_title, u.name as user_name, u.email as user_email');
        $this->db->from('daily_work_logs dl');
        $this->db->join('tasks t', 't.id = dl.task_id', 'left');
        $this->db->join('users u', 'u.id = dl.user_id', 'left');
        
        if (!empty($filters['user_id'])) {
            $this->db->where('dl.user_id', $filters['user_id']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('dl.work_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('dl.work_date <=', $filters['date_to']);
        }
        
        // Scope Check
        $currentUserId = (int)$this->session->userdata('user_id');
        $isAdminGroup = (function_exists('is_admin_group') && is_admin_group()) || (int)$this->session->userdata('role_id') === 1;
        if (!$isAdminGroup) {
             // Non-admins can only see their own report? Or maybe team's?
             // Let's restrict to self for now unless they are a manager/lead (which is covered by is_admin_group mostly)
             // If is_admin_group is true, they usually see all.
             $this->db->where('dl.user_id', $currentUserId);
        }

        // Show latest entries first
        $this->db->order_by('dl.created_at', 'DESC');
        $rows = $this->db->get()->result();

         // Users for filter
        $users = [];
        if ($isAdminGroup) {
             $users = $this->db->select('id, name, email')->from('users')->order_by('name')->get()->result();
        }

        // Build summary stats
        $total_entries = count($rows);
        $unique_users = [];
        $unique_dates = [];
        $unique_tasks = [];
        foreach ($rows as $r) {
            $unique_users[$r->user_id] = true;
            $unique_dates[$r->work_date] = true;
            if (!empty($r->task_id)) { $unique_tasks[$r->task_id] = true; }
        }

        $this->load->view('reports/daily_activity', [
            'rows' => $rows,
            'filters' => $filters,
            'users' => $users,
            'is_admin' => $isAdminGroup,
            'stats' => [
                'total_entries' => $total_entries,
                'unique_users' => count($unique_users),
                'unique_dates' => count($unique_dates),
                'unique_tasks' => count($unique_tasks),
            ]
        ]);
    }

    // GET /reports/projects-status
    public function projects_status()
    {
        // Get filters from GET parameters
        $filters = [
            'status' => $this->input->get('status'),
            'client_id' => $this->input->get('client_id'),
            'project_manager_id' => $this->input->get('project_manager_id'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'search' => $this->input->get('search'),
        ];

        $rows = [];
        $project_details = [];
        $task_stats = [];
        
        // Prefer task-based aggregation per project and status if schema supports it
        if ($this->db->table_exists('tasks') && $this->db->table_exists('projects')) {
            // Get available fields from projects table
            $project_fields = $this->db->list_fields('projects');
            
            // Build base select fields dynamically
            $select_fields = [
                'p.id AS project_id',
                'p.name AS project_name',
                'p.status AS project_status'
            ];
            
            // Add optional fields if they exist
            if (in_array('start_date', $project_fields)) {
                $select_fields[] = 'p.start_date';
            }
            if (in_array('end_date', $project_fields)) {
                $select_fields[] = 'p.end_date';
            }
            if (in_array('budget', $project_fields)) {
                $select_fields[] = 'p.budget';
            }
            if (in_array('client_id', $project_fields)) {
                $select_fields[] = 'p.client_id';
            }
            if (in_array('manager_id', $project_fields)) {
                $select_fields[] = 'p.manager_id';
            }
            
            $select_fields[] = 't.status AS task_status, COUNT(*) AS task_count';
            
            // Build base query with filters
            $this->db->select(implode(', ', $select_fields))
                     ->from('projects p')
                     ->join('tasks t','t.project_id = p.id','left');
            
            // Apply filters
            if (!empty($filters['status'])) {
                $this->db->where('p.status', $filters['status']);
            }
            if (!empty($filters['client_id']) && in_array('client_id', $project_fields)) {
                $this->db->where('p.client_id', (int)$filters['client_id']);
            }
            if (!empty($filters['project_manager_id']) && in_array('manager_id', $project_fields)) {
                $this->db->where('p.manager_id', (int)$filters['project_manager_id']);
            }
            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $this->db->group_start()
                    ->like('p.name', $search)
                    ->or_like('p.description', $search)
                    ->group_end();
            }
            if (!empty($filters['date_from']) && in_array('start_date', $project_fields)) {
                $this->db->where('p.start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to']) && in_array('end_date', $project_fields)) {
                $this->db->where('p.end_date <=', $filters['date_to']);
            }
            
            // Build group by fields dynamically
            $group_fields = ['p.id', 'p.name', 'p.status'];
            if (in_array('start_date', $project_fields)) {
                $group_fields[] = 'p.start_date';
            }
            if (in_array('end_date', $project_fields)) {
                $group_fields[] = 'p.end_date';
            }
            if (in_array('budget', $project_fields)) {
                $group_fields[] = 'p.budget';
            }
            if (in_array('client_id', $project_fields)) {
                $group_fields[] = 'p.client_id';
            }
            if (in_array('manager_id', $project_fields)) {
                $group_fields[] = 'p.manager_id';
            }
            $group_fields[] = 't.status';
            
            $this->db->group_by($group_fields)
                     ->order_by('p.name', 'ASC');
            
            $results = $this->db->get()->result();
            
            // Process results to build project analytics
            $projects_map = [];
            foreach ($results as $r) {
                $project_id = (int)$r->project_id;
                
                if (!isset($projects_map[$project_id])) {
                    $projects_map[$project_id] = (object)[
                        'project_id' => $project_id,
                        'project_name' => $r->project_name,
                        'project_status' => $r->project_status,
                        'start_date' => isset($r->start_date) ? $r->start_date : null,
                        'end_date' => isset($r->end_date) ? $r->end_date : null,
                        'budget' => isset($r->budget) ? $r->budget : null,
                        'client_id' => isset($r->client_id) ? $r->client_id : null,
                        'manager_id' => isset($r->manager_id) ? $r->manager_id : null,
                        'task_counts' => [],
                        'total_tasks' => 0,
                        'completed_tasks' => 0
                    ];
                }
                
                if ($r->task_status) {
                    $projects_map[$project_id]->task_counts[$r->task_status] = (int)$r->task_count;
                    $projects_map[$project_id]->total_tasks += (int)$r->task_count;
                    if ($r->task_status === 'completed') {
                        $projects_map[$project_id]->completed_tasks += (int)$r->task_count;
                    }
                }
            }
            
            // Calculate completion percentages and resolve names
            foreach ($projects_map as $project_id => $project) {
                $completion_percentage = $project->total_tasks > 0 ? 
                    round(($project->completed_tasks / $project->total_tasks) * 100, 1) : 0;
                
                $project->completion_percentage = $completion_percentage;
                $project->client_name = $this->get_client_name($project->client_id);
                $project->manager_name = $this->get_user_name($project->manager_id);
                
                // Calculate days remaining/overdue
                if ($project->end_date) {
                    $end_date = new DateTime($project->end_date);
                    $today = new DateTime();
                    $interval = $today->diff($end_date);
                    $project->days_remaining = $interval->days * ($interval->invert ? -1 : 1);
                    $project->is_overdue = $interval->invert;
                } else {
                    $project->days_remaining = null;
                    $project->is_overdue = false;
                }
                
                $project_details[] = $project;
            }
            
            // Also create status breakdown for charts
            $status_breakdown = [];
            foreach ($project_details as $project) {
                $status = $project->project_status ?: 'unknown';
                if (!isset($status_breakdown[$status])) {
                    $status_breakdown[$status] = 0;
                }
                $status_breakdown[$status]++;
            }
            
            foreach ($status_breakdown as $status => $count) {
                $rows[] = (object)[
                    'project_name' => $status,
                    'status' => $status,
                    'cnt' => $count
                ];
            }
            
        } else if ($this->db->table_exists('projects')) {
            // Fallback: projects grouped by their own status
            $project_fields = $this->db->list_fields('projects');
            
            // Build base select fields dynamically
            $select_fields = [
                'p.id AS project_id',
                'p.name AS project_name',
                'p.status AS project_status'
            ];
            
            // Add optional fields if they exist
            if (in_array('start_date', $project_fields)) {
                $select_fields[] = 'p.start_date';
            }
            if (in_array('end_date', $project_fields)) {
                $select_fields[] = 'p.end_date';
            }
            if (in_array('budget', $project_fields)) {
                $select_fields[] = 'p.budget';
            }
            if (in_array('client_id', $project_fields)) {
                $select_fields[] = 'p.client_id';
            }
            if (in_array('manager_id', $project_fields)) {
                $select_fields[] = 'p.manager_id';
            }
            
            $this->db->select(implode(', ', $select_fields))
                     ->from('projects p');
            
            // Apply filters
            if (!empty($filters['status'])) {
                $this->db->where('p.status', $filters['status']);
            }
            if (!empty($filters['client_id']) && in_array('client_id', $project_fields)) {
                $this->db->where('p.client_id', (int)$filters['client_id']);
            }
            if (!empty($filters['project_manager_id']) && in_array('manager_id', $project_fields)) {
                $this->db->where('p.manager_id', (int)$filters['project_manager_id']);
            }
            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $this->db->group_start()
                    ->like('p.name', $search)
                    ->or_like('p.description', $search)
                    ->group_end();
            }
            if (!empty($filters['date_from']) && in_array('start_date', $project_fields)) {
                $this->db->where('p.start_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to']) && in_array('end_date', $project_fields)) {
                $this->db->where('p.end_date <=', $filters['date_to']);
            }
            
            $projects = $this->db->order_by('p.name', 'ASC')->get()->result();
            
            foreach ($projects as $project) {
                $project->task_counts = [];
                $project->total_tasks = 0;
                $project->completed_tasks = 0;
                $project->completion_percentage = 0;
                $project->client_name = $this->get_client_name(isset($project->client_id) ? $project->client_id : null);
                $project->manager_name = $this->get_user_name(isset($project->manager_id) ? $project->manager_id : null);
                
                // Calculate days remaining/overdue
                if (isset($project->end_date) && $project->end_date) {
                    $end_date = new DateTime($project->end_date);
                    $today = new DateTime();
                    $interval = $today->diff($end_date);
                    $project->days_remaining = $interval->days * ($interval->invert ? -1 : 1);
                    $project->is_overdue = $interval->invert;
                } else {
                    $project->days_remaining = null;
                    $project->is_overdue = false;
                }
                
                $project_details[] = $project;
            }
            
            // Create status breakdown
            $status_breakdown = [];
            foreach ($project_details as $project) {
                $status = $project->project_status ?: 'unknown';
                if (!isset($status_breakdown[$status])) {
                    $status_breakdown[$status] = 0;
                }
                $status_breakdown[$status]++;
            }
            
            foreach ($status_breakdown as $status => $count) {
                $rows[] = (object)[
                    'project_name' => $status,
                    'status' => $status,
                    'cnt' => $count
                ];
            }
        }
        
        // Get filter options
        $filter_options = [
            'clients' => [],
            'managers' => [],
            'statuses' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']
        ];
        
        if ($this->db->table_exists('clients')) {
            $filter_options['clients'] = $this->db->select('id, company_name')->order_by('company_name')->get('clients')->result();
        }
        
        if ($this->db->table_exists('users')) {
            $filter_options['managers'] = $this->db->select('id, email')->order_by('email')->get('users')->result();
        }

        $this->load->view('reports/projects_status', [
            'rows' => $rows,
            'project_details' => $project_details,
            'filters' => $filters,
            'filter_options' => $filter_options
        ]);
        
        // Handle CSV export
        if ($this->input->get('export') === 'csv') {
            $this->export_projects_status_csv($project_details, $rows, $filters);
        }
    }
    
    private function get_client_name($client_id) {
        if (!$client_id || !$this->db->table_exists('clients')) {
            return '—';
        }
        
        $client = $this->db->select('company_name')->from('clients')->where('id', (int)$client_id)->get()->row();
        return $client ? $client->company_name : '—';
    }
    
    private function export_projects_status_csv($project_details, $status_breakdown, $filters) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="projects_status_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Summary section
        fputcsv($output, ['PROJECTS STATUS REPORT - ' . date('Y-m-d')]);
        fputcsv($output, []);
        
        // Status breakdown
        fputcsv($output, ['STATUS BREAKDOWN']);
        fputcsv($output, ['Status', 'Project Count']);
        foreach ($status_breakdown as $status) {
            fputcsv($output, [
                $status->status,
                (int)$status->cnt
            ]);
        }
        
        fputcsv($output, []);
        
        // Detailed projects
        fputcsv($output, ['PROJECT DETAILS']);
        fputcsv($output, ['Project ID', 'Project Name', 'Status', 'Client', 'Manager', 'Start Date', 'End Date', 'Budget', 'Total Tasks', 'Completed Tasks', 'Completion %', 'Days Remaining']);
        foreach ($project_details as $project) {
            fputcsv($output, [
                $project->project_id,
                $project->project_name,
                $project->project_status,
                $project->client_name,
                $project->manager_name,
                $project->start_date,
                $project->end_date,
                $project->budget,
                $project->total_tasks,
                $project->completed_tasks,
                $project->completion_percentage . '%',
                $project->days_remaining !== null ? $project->days_remaining : '—'
            ]);
        }
        
        fclose($output);
        exit;
    }

    // GET /reports/leaves
    public function leaves()
    {
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
                           FROM leave_requests WHERE 1=1 $date_filter GROUP BY ym ORDER BY ym";
            $monthly = $this->db->query($monthly_sql)->result();
            
            // Status breakdown (with filters applied)
            $fields = $this->db->list_fields('leave_requests');
            $select_fields = ['status', 'COUNT(*) AS cnt'];
            
            // Add SUM(days) if days field exists
            if (in_array('days', $fields)) {
                $select_fields[] = 'SUM(days) AS total_days';
            }
            
            $this->db->select(implode(', ', $select_fields))->from('leave_requests');
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
                $leave_types = $this->db->select('DISTINCT(leave_type)')->from('leave_requests')->get()->result();
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
                           FROM leaves WHERE 1=1 $date_filter GROUP BY ym ORDER BY ym";
            $monthly = $this->db->query($monthly_sql)->result();
            
            $this->db->select('status, COUNT(*) AS cnt')->from('leaves');
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
    
    private function get_user_name($user_id) {
        if (!$this->db->table_exists('users')) {
            return 'User #' . $user_id;
        }
        
        $this->db->select('u.email');
        if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
        if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
        
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            $this->db->join('employees e','e.user_id = u.id','left');
            if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
            if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
            if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
            if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
        }
        
        $user = $this->db->from('users u')->where('u.id', (int)$user_id)->get()->row();
        
        if (!$user) {
            return 'User #' . $user_id;
        }
        
        $empParts = [];
        if (isset($user->emp_first_name) && trim((string)$user->emp_first_name) !== '') { $empParts[] = trim((string)$user->emp_first_name); }
        if (isset($user->emp_last_name) && trim((string)$user->emp_last_name) !== '') { $empParts[] = trim((string)$user->emp_last_name); }
        if (!empty($empParts)) { return trim(implode(' ', $empParts)); }
        elseif (isset($user->emp_full_name) && trim((string)$user->emp_full_name) !== '') { return trim((string)$user->emp_full_name); }
        elseif (isset($user->full_name) && trim((string)$user->full_name) !== '') { return trim((string)$user->full_name); }
        elseif (isset($user->name) && trim((string)$user->name) !== '') { return trim((string)$user->name); }
        else { return $user->email; }
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

    // GET /reports/attendance-employee
    public function attendance_employee($user_id = null)
    {
        // Check permission-based access
        $has_access = false;
        if (function_exists('has_module_access')) {
            // Check specific permission for attendance-employee report
            $has_access = has_module_access('reports_attendance_employee');
            // Fallback: check general reports permission if specific one doesn't exist
            if (!$has_access) {
                $has_access = has_module_access('reports');
            }
        }
        
        // If no permission system or no access, check if user is logged in
        if (!$has_access) {
            $user_id_check = (int)$this->session->userdata('user_id');
            if (!$user_id_check) {
                redirect('auth/login');
                return;
            }
            // If permission system exists but user doesn't have access, show error
            if (function_exists('has_module_access')) {
                $this->session->set_flashdata('error', 'You do not have permission to access Employee Attendance Reports.');
                redirect('reports');
                return;
            }
            // Fallback: if no permission system configured, allow Admin/HR (role 1,2) for backward compatibility
        $role_id = (int)$this->session->userdata('role_id');
        if (!in_array($role_id, [1, 2], true)) {
                $this->session->set_flashdata('error', 'You do not have permission to access Employee Attendance Reports.');
                redirect('reports');
            return;
            }
        }

        // Get period filter (daily, weekly, monthly)
        $period = (string)$this->input->get('period');
        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'monthly';
        }

        // Get month/date filter
        $month = (string)$this->input->get('month');
        $date = (string)$this->input->get('date');
        
        // Calculate date range based on period
        $from = null;
        $to = null;
        
        if ($period === 'daily') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $from = $date;
                $to = $date;
            } else {
                $from = date('Y-m-d');
                $to = date('Y-m-d');
                $date = $from;
            }
        } elseif ($period === 'weekly') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // Get week start (Monday) and end (Sunday)
                $dateTs = strtotime($date);
                $dayOfWeek = (int)date('w', $dateTs); // 0=Sunday, 1=Monday, etc.
                $mondayOffset = ($dayOfWeek == 0) ? -6 : (1 - $dayOfWeek);
                $from = date('Y-m-d', strtotime($mondayOffset . ' days', $dateTs));
                $to = date('Y-m-d', strtotime('+6 days', strtotime($from)));
            } else {
                // Default to current week
                $today = date('Y-m-d');
                $dayOfWeek = (int)date('w', strtotime($today));
                $mondayOffset = ($dayOfWeek == 0) ? -6 : (1 - $dayOfWeek);
                $from = date('Y-m-d', strtotime($mondayOffset . ' days', strtotime($today)));
                $to = date('Y-m-d', strtotime('+6 days', strtotime($from)));
                $date = $from;
            }
        } else { // monthly
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $from = $month.'-01'; // Always start from 1st
                $to = date('Y-m-t', strtotime($from));
            } else {
                $month = date('Y-m');
        $from = $month.'-01';
        $to = date('Y-m-t', strtotime($from));
            }
        }
        
        // Limit 'to' date to today if it's in the future
        $today = date('Y-m-d');
        if (strtotime($to) > strtotime($today)) {
            $to = $today;
        }
        
        // Fetch holidays for the period
        $holidays = [];
        $holidayDates = [];
        if ($this->db->table_exists('holidays')) {
            $holidays = $this->db->where('holiday_date >=', $from)
                                 ->where('holiday_date <=', $to)
                                 ->order_by('holiday_date', 'ASC')
                                 ->get('holidays')
                                 ->result();
            foreach ($holidays as $h) {
                $holidayDates[] = $h->holiday_date;
            }
        }

        // Calculate total working days (excluding weekends, future dates, AND holidays) - used in both branches
        $totalWorkingDays = 0;
        $startTs = strtotime($from);
        $endTs = strtotime($to);
        $todayTs = strtotime($today);
        while ($startTs !== false && $startTs <= $endTs) {
            // Only count up to today
            if ($startTs > $todayTs) {
                break;
            }
            $currentDate = date('Y-m-d', $startTs);
            $dayOfWeek = (int)date('w', $startTs); // 0=Sunday, 6=Saturday
            
            // Not Sunday or Saturday AND not a holiday
            if ($dayOfWeek != 0 && $dayOfWeek != 6 && !in_array($currentDate, $holidayDates)) { 
                $totalWorkingDays++;
            }
            $startTs = strtotime('+1 day', $startTs);
        }

        if (!$this->db->table_exists('attendance')) {
            show_error('Attendance table not found', 500);
            return;
        }

        $fields = $this->db->list_fields('attendance');
        $userCandidates = ['user_id','employee_id','emp_id','staff_id','uid'];
        $dateCandidates = ['att_date','date','attendance_date','created_at','checked_at'];
        $statusCandidates = ['status','attendance_status','state'];
        $userCol = $dateCol = $statusCol = null;
        foreach ($userCandidates as $c) { if (in_array($c, $fields, true)) { $userCol = $c; break; } }
        foreach ($dateCandidates as $c) { if (in_array($c, $fields, true)) { $dateCol = $c; break; } }
        foreach ($statusCandidates as $c) { if (in_array($c, $fields, true)) { $statusCol = $c; break; } }
        if ($userCol === null) { $userCol = isset($fields[0]) ? $fields[0] : 'user_id'; }
        if ($dateCol === null) { $dateCol = isset($fields[1]) ? $fields[1] : 'att_date'; }
        if ($statusCol === null) { $statusCol = isset($fields[2]) ? $fields[2] : 'status'; }

        $labels = [];
        if ($this->db->table_exists('users')) {
            $this->db->select('u.id, u.email');
            if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
            if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                $this->db->join('employees e','e.user_id = u.id','left');
                if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
                if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
                if ($this->db->field_exists('middle_name','employees')) { $this->db->select('e.middle_name AS emp_middle_name'); }
                if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
            }
            $users = $this->db->from('users u')->get()->result();
            foreach ($users as $u) { $labels[(int)$u->id] = $u; }
        }

        $getName = function($uid) use ($labels) {
            $label = isset($labels[$uid]) ? $labels[$uid] : null;
            if ($label) {
                $empParts = [];
                if (isset($label->emp_first_name) && trim((string)$label->emp_first_name) !== '') { $empParts[] = trim((string)$label->emp_first_name); }
                if (isset($label->emp_middle_name) && trim((string)$label->emp_middle_name) !== '') { $empParts[] = trim((string)$label->emp_middle_name); }
                if (isset($label->emp_last_name) && trim((string)$label->emp_last_name) !== '') { $empParts[] = trim((string)$label->emp_last_name); }
                if (!empty($empParts)) { return trim(implode(' ', $empParts)); }
                if (isset($label->emp_full_name) && trim((string)$label->emp_full_name) !== '') { return trim((string)$label->emp_full_name); }
                if (isset($label->emp_name) && trim((string)$label->emp_name) !== '') { return trim((string)$label->emp_name); }
                if (isset($label->full_name) && trim((string)$label->full_name) !== '') { return trim((string)$label->full_name); }
                if (isset($label->name) && trim((string)$label->name) !== '') { return trim((string)$label->name); }
                return $label->email;
            }
            return $uid ? ('User #'.$uid) : 'Unknown';
        };

        $user_id = $user_id ? (int)$user_id : 0;

        if ($user_id > 0) {
            // Detect punch-in/check-in column for lateness calculation FIRST
            $fields = $this->db->list_fields('attendance');
            $checkInCol = null;
            if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
            elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }
            
            // Debug: Create sample data for testing if no data exists
            $attendanceCount = $this->db->where($userCol, $user_id)->where("`$dateCol` >=", $from)->where("`$dateCol` <=", $to)->count_all_results('attendance');
            error_log("Attendance count for user $user_id from $from to $to: $attendanceCount");
            error_log("Check-in column detected: " . ($checkInCol ? $checkInCol : 'None'));
            


            // Detect check-out column
            $checkOutCol = null;
            if (in_array('punch_out', $fields, true)) { $checkOutCol = 'punch_out'; }
            elseif (in_array('check_out', $fields, true)) { $checkOutCol = 'check_out'; }

            $selectCols = ["`$dateCol` AS d", "`$statusCol` AS st"];
            if ($checkInCol !== null) {
                $selectCols[] = "`".$checkInCol."` AS cin";
            }
            if ($checkOutCol !== null) {
                $selectCols[] = "`".$checkOutCol."` AS cout";
            }
            // Add location fields if they exist
            if ($this->db->field_exists('checkin_location_name', 'attendance')) {
                $selectCols[] = "`checkin_location_name` AS cin_loc";
            }
            if ($this->db->field_exists('checkout_location_name', 'attendance')) {
                $selectCols[] = "`checkout_location_name` AS cout_loc";
            }
            // Add notes field if it exists
            if ($this->db->field_exists('notes', 'attendance')) {
                $selectCols[] = "`notes` AS notes";
            }

            $this->db->select(implode(', ', $selectCols))
                ->from('attendance')
                ->where($userCol, $user_id)
                ->where("`$dateCol` >=", $from)
                ->where("`$dateCol` <=", $to)
                ->order_by($dateCol, 'ASC');
            $rows = $this->db->get()->result();

            $attMap = [];
            $cinMap = [];
            $coutMap = [];
            $cinLocMap = [];
            $coutLocMap = [];
            $notesMap = [];
            foreach ($rows as $r) {
                $d = isset($r->d) ? (string)$r->d : '';
                if ($d === '') { continue; }
                if (strpos($d, ' ') !== false) { $d = trim(explode(' ', $d)[0]); }
                $attMap[$d] = (string)$r->st;
                if ($checkInCol !== null && isset($r->cin)) {
                    $cinMap[$d] = (string)$r->cin;
                }
                if ($checkOutCol !== null && isset($r->cout)) {
                    $coutMap[$d] = (string)$r->cout;
                }
                if (isset($r->cin_loc) && !empty($r->cin_loc)) {
                    $cinLocMap[$d] = (string)$r->cin_loc;
                }
                if (isset($r->cout_loc) && !empty($r->cout_loc)) {
                    $coutLocMap[$d] = (string)$r->cout_loc;
                }
                if (isset($r->notes) && !empty(trim($r->notes))) {
                    $notesMap[$d] = trim((string)$r->notes);
                }
            }

            $leaveMap = [];
            if ($this->db->table_exists('leave_requests')) {
                // Get leave requests, excluding WFH requests (they are handled via attendance status)
                $lrows = $this->db->select('lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                    ->from('leave_requests lr')
                    ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                    ->where('lr.user_id', $user_id)
                    ->where_in('lr.status', ['lead_approved','hr_approved'])
                    ->where('lr.start_date <=', $to)
                    ->where('lr.end_date >=', $from)
                    ->get()->result();
                foreach ($lrows as $lr) {
                    // Skip WFH requests - they should not appear in leave map
                    $is_wfh = false;
                    if (isset($lr->reason) && strpos($lr->reason, 'WFH:') === 0) {
                        $is_wfh = true;
                    } elseif (isset($lr->type_name) && strtolower(trim($lr->type_name)) === 'work from home') {
                        $is_wfh = true;
                    }
                    if ($is_wfh) {
                        continue; // Skip WFH requests
                    }
                    
                    $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                    $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                    if ($sd === '' || $ed === '') { continue; }
                    $cur = strtotime(max($from, substr($sd, 0, 10)));
                    $endTs = strtotime(min($to, substr($ed, 0, 10)));
                    $txt = 'Leave ('.(string)$lr->status.')';
                    while ($cur !== false && $cur <= $endTs) {
                        $k = date('Y-m-d', $cur);
                        if (!isset($leaveMap[$k])) { $leaveMap[$k] = $txt; }
                        $cur = strtotime('+1 day', $cur);
                    }
                }
            }

            // Resolve office start time and grace period from settings (with safe defaults)
            // Use the same settings as the summary view for consistency
            $officeStart = '09:30';
            $graceMinutes = 15;
            $standardHours = 8.0;
            if (isset($this->settings)) {
                try {
                    $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                    if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) {
                        $officeStart = $stVal;
                    }
                    $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                    if (is_numeric($gmVal)) {
                        $graceMinutes = (int)$gmVal;
                    }
                    // Try both old and new setting key names for backward compatibility
                    $shVal = $this->settings->get_setting('attendance_standard_working_hours');
                    if ($shVal === null || $shVal === '') {
                        $shVal = $this->settings->get_setting('standard_working_hours', $standardHours);
                    }
                    if (is_numeric($shVal)) {
                        $standardHours = (float)$shVal;
                    }
                } catch (Exception $e) {
                    // ignore and use defaults
                }
            }

            // Override with Employee Shift if available
            $this->load->model('Employee_model');
            $this->load->model('Shift_model');
            $employee = $this->Employee_model->get_by_user_id($user_id);
            if ($employee && isset($employee->shift_id)) {
                $shift = $this->Shift_model->get($employee->shift_id);
                if ($shift) {
                    // Use Shift Timings
                    $officeStart = date('H:i', strtotime($shift->start_time));
                    $graceMinutes = (int)$shift->late_grace_period;
                    // Calculate standard hours from shift duration
                    $start_ts = strtotime($shift->start_time);
                    $end_ts = strtotime($shift->end_time);
                    $diff = $end_ts - $start_ts;
                    if ($diff > 0) {
                        $standardHours = round($diff / 3600, 1);
                    }
                }
            }

        // Create holiday map
        $holidayMap = [];
        foreach ($holidays as $h) {
            $holidayMap[$h->holiday_date] = $h->name;
        }

            $days = [];
            $startTs = strtotime($from);
            $endTs = strtotime($to);
            while ($startTs !== false && $startTs <= $endTs) {
                $d = date('Y-m-d', $startTs);
                $dayOfWeek = (int)date('w', $startTs); // 0=Sunday, 6=Saturday
                $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
                
                $raw = isset($attMap[$d]) ? $attMap[$d] : '';
                $st = strtolower(trim($raw));
                $leave = isset($leaveMap[$d]) ? $leaveMap[$d] : '—';
                $holidayName = isset($holidayMap[$d]) ? $holidayMap[$d] : null;
                
                // Determine status: if no attendance record and not on leave and not weekend, mark as absent
                if ($raw === '' && $leave === '—' && !$isWeekend) {
                    if ($holidayName) {
                        $st = 'holiday';
                        $raw = 'holiday';
                    } else {
                        $st = 'absent';
                        $raw = 'absent';
                    }
                }
                
                $labelSt = '—';
                if ($st === 'holiday') { $labelSt = 'Holiday: ' . $holidayName; }
                elseif ($st === 'present') { 
                    $labelSt = 'Present'; 
                    if ($holidayName) { $labelSt .= ' (' . $holidayName . ')'; }
                }
                elseif ($st === 'half_day') { $labelSt = 'Half Day'; }
                elseif ($st === 'work_from_home') { $labelSt = 'Work From Home'; }
                elseif ($st === 'absent') { $labelSt = 'Absent'; }
                elseif ($st !== '') { $labelSt = $raw; }
                elseif ($isWeekend) { $labelSt = 'Weekend'; }

                // Late/On Time label based on check-in time when available
                $lateLabel = '—';
                $lateStatus = ''; // 'late', 'on_time', or ''
                $lateMinutes = 0;
                $graceTimeStr = ''; // Grace time threshold (e.g., "09:45")
                $checkInTime = '—';
                $checkOutTime = '—';
                $checkInLocation = '—';
                $checkOutLocation = '—';
                
                if ($checkInCol !== null && isset($cinMap[$d]) && $st !== '' && $st !== 'absent') {
                    $cinRaw = (string)$cinMap[$d];
                    $cinTime = $cinRaw;
                    if (strpos($cinRaw, ' ') !== false) {
                        $parts = explode(' ', $cinRaw);
                        $cinTime = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                        $checkInTime = (strlen($cinTime) >= 8) ? substr($cinTime, 0, 8) : (substr($cinTime, 0, 5) . ':00');
                    } else {
                        $checkInTime = (strlen($cinTime) >= 8) ? substr($cinTime, 0, 8) : (substr($cinTime, 0, 5) . ':00');
                    }
                    
                    if (preg_match('/^\d{2}:\d{2}/', $cinTime)) {
                        // Display full time for user friendliness
                        $cinDisp = $checkInTime;
                        $officeTs = strtotime($d.' '.$officeStart.':00');
                        $graceTs  = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
                        $cinTs    = strtotime($d.' '.$cinTime);
                        
                        // Calculate grace time threshold for display
                        if ($graceTs !== false) {
                            $graceTimeStr = date('H:i', $graceTs);
                        }
                        
                        if ($graceTs !== false && $cinTs !== false) {
                            if ($cinTs > $graceTs) {
                                $lateMinutes = (int)round(($cinTs - $graceTs) / 60);
                                $lateStatus = 'late';
                                $lateLabel = 'Late: '.$cinDisp.' ('.$lateMinutes.' min after grace)';
                            } else {
                                $lateStatus = 'on_time';
                                // Calculate if within grace period or before office start
                                if ($cinTs >= $officeTs) {
                                    $lateLabel = 'On Time: '.$cinDisp.' (within grace)';
                        } else {
                                    $earlyMinutes = (int)round(($officeTs - $cinTs) / 60);
                                    $lateLabel = 'Early: '.$cinDisp.' ('.$earlyMinutes.' min before)';
                                }
                            }
                        }
                    }
                }
                
                // Get check-out time
                $workedHours = 0;
                $extraHours = 0;
                $workedSeconds = 0;
                $extraSeconds = 0;
                if ($checkOutCol !== null && isset($coutMap[$d])) {
                    $coutRaw = (string)$coutMap[$d];
                    $coutTime = $coutRaw;
                    if (strpos($coutRaw, ' ') !== false) {
                        $parts = explode(' ', $coutRaw);
                        $coutTime = isset($parts[1]) ? trim($parts[1]) : trim($coutRaw);
                    }
                    if (preg_match('/^\d{2}:\d{2}/', $coutTime)) {
                        $checkOutTime = (strlen($coutTime) >= 8) ? substr($coutTime, 0, 8) : (substr($coutTime, 0, 5) . ':00');
                        
                        // Calculate worked hours from check-in to check-out
                        if ($checkInTime !== '—' && preg_match('/^\d{2}:\d{2}/', $checkInTime)) {
                            $cinTs = strtotime($d.' '.$checkInTime);
                            $coutTs = strtotime($d.' '.$checkOutTime);
                            if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                                $workedSeconds = $coutTs - $cinTs;
                                $workedHours = $workedSeconds / 3600; // Convert seconds to hours
                                
                                // Calculate extra hours (worked hours - standard hours)
                                // Only show extra if worked more than standard
                                if ($workedHours > $standardHours) {
                                    $extraHours = $workedHours - $standardHours;
                                    $extraSeconds = $workedSeconds - ($standardHours * 3600);
                                }
                            }
                        }
                    }
                }
                
                // Get check-in location
                if (isset($cinLocMap[$d])) {
                    $checkInLocation = $cinLocMap[$d];
                }
                
                // Get check-out location
                if (isset($coutLocMap[$d])) {
                    $checkOutLocation = $coutLocMap[$d];
                }
                
                // Get notes
                $notes = '—';
                if (isset($notesMap[$d])) {
                    $notes = $notesMap[$d];
                }

                $obj = new stdClass();
                $obj->date = $d;
                $obj->status = $labelSt;
                $obj->leave = $leave;
                $obj->late = $lateLabel;
                $obj->late_status = $lateStatus; // 'late', 'on_time', or ''
                $obj->late_minutes = $lateMinutes;
                $obj->grace_time = $graceTimeStr; // Grace time threshold (e.g., "09:45")
                $obj->check_in_time = $checkInTime;
                $obj->check_out_time = $checkOutTime;
                $obj->check_in_location = $checkInLocation;
                $obj->check_out_location = $checkOutLocation;
                $obj->worked_hours = round($workedHours, 2); // Total hours worked
                $obj->extra_hours = round($extraHours, 2); // Extra hours beyond standard
                $obj->worked_seconds = $workedSeconds;
                $obj->extra_seconds = $extraSeconds;
                $obj->notes = $notes;
                $days[] = $obj;
                $startTs = strtotime('+1 day', $startTs);
            }

            $name = $getName($user_id);
            error_log("Loading view for user: $user_id, name: $name, days count: " . count($days));
            $this->load->view('reports/attendance_employee_detail', [
                'name'=>$name,
                'period'=>$period,
                'month'=>$month,
                'date'=>$date,
                'from'=>$from,
                'to'=>$to,
                'office_start_time'=>$officeStart,
                'grace_minutes'=>$graceMinutes,
                'standard_working_hours'=>$standardHours,
                'days'=>$days,
                'holidays'=>$holidays
            ]);
            return;
        }

        // Get all users/employees first
        $allUsers = [];
        if ($this->db->table_exists('users')) {
            $this->db->select('u.id, u.email');
            if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
            if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
            if ($this->db->field_exists('status','users')) { 
                $this->db->where('u.status', 'active'); // Only active users
            }
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                $this->db->join('employees e','e.user_id = u.id','left');
                if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
                if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
                if ($this->db->field_exists('middle_name','employees')) { $this->db->select('e.middle_name AS emp_middle_name'); }
                if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
            }
            $users = $this->db->from('users u')->get()->result();
            foreach ($users as $u) { 
                $allUsers[(int)$u->id] = $u; 
            }
        }

        $summary = [];
        $rows = $this->db->select("`$userCol` AS uid, `$statusCol` AS st, COUNT(*) AS cnt")
            ->from('attendance')
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->group_by(["`$userCol`","`$statusCol`"])
            ->get()->result();
        foreach ($rows as $r) {
            $uid = (int)$r->uid;
            $st = strtolower(trim((string)$r->st));
            $cnt = (float)$r->cnt;
            if (!isset($summary[$uid])) {
                $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
            }
            if ($st === 'present') { $summary[$uid]['present'] += $cnt; }
            elseif ($st === 'half_day') { $summary[$uid]['half'] += $cnt; }
            elseif ($st === 'work_from_home') { $summary[$uid]['wfh'] += $cnt; }
            elseif ($st === 'absent') { $summary[$uid]['absent'] += $cnt; }
        }

        // Initialize summary for all users (even those without attendance records)
        foreach ($allUsers as $uid => $user) {
            if (!isset($summary[$uid])) {
                $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
            } else {
                if (!isset($summary[$uid]['late_hours'])) { $summary[$uid]['late_hours'] = 0.0; }
                if (!isset($summary[$uid]['extra_hours'])) { $summary[$uid]['extra_hours'] = 0.0; }
                if (!isset($summary[$uid]['on_time'])) { $summary[$uid]['on_time'] = 0.0; }
            }
        }

        // Resolve office start time, grace period, and standard hours from settings
            $officeStart = '09:30';
            $graceMinutes = 15;
        $standardHours = 8.0; // Standard working hours per day
            if (isset($this->settings)) {
                try {
                    $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                    if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) {
                        $officeStart = $stVal;
                    }
                    $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                    if (is_numeric($gmVal)) {
                        $graceMinutes = (int)$gmVal;
                    }
                // Try both old and new setting key names for backward compatibility
                $shVal = $this->settings->get_setting('attendance_standard_working_hours');
                if ($shVal === null || $shVal === '') {
                    $shVal = $this->settings->get_setting('standard_working_hours', $standardHours);
                }
                if (is_numeric($shVal)) {
                    $standardHours = (float)$shVal;
                }
                } catch (Exception $e) {
                    // ignore and use defaults
                }
            }

        // Calculate late hours and extra hours
        $fields = $this->db->list_fields('attendance');
        $checkInCol = null;
        $checkOutCol = null;
        if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
        elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }
        if (in_array('punch_out', $fields, true)) { $checkOutCol = 'punch_out'; }
        elseif (in_array('check_out', $fields, true)) { $checkOutCol = 'check_out'; }

        if ($checkInCol !== null) {
            $selectCols = ["`$userCol` AS uid", "`$dateCol` AS d", "`$checkInCol` AS cin"];
            if ($checkOutCol !== null) {
                $selectCols[] = "`$checkOutCol` AS cout";
            }
            if ($this->db->field_exists('total_hours', 'attendance')) {
                $selectCols[] = "`total_hours` AS th";
            }
            
            $attendanceRows = $this->db->select(implode(', ', $selectCols))
                ->from('attendance')
                ->where("`$dateCol` >=", $from)
                ->where("`$dateCol` <=", $to)
                ->where("`$statusCol` !=", 'absent')
                ->get()->result();

            foreach ($attendanceRows as $row) {
                $uid = (int)$row->uid;
                $attDate = isset($row->d) ? (string)$row->d : '';
                $cinRaw = isset($row->cin) ? (string)$row->cin : '';
                
                if ($attDate === '' || $cinRaw === '') continue;
                
                // Calculate late hours
                $cinTime = $cinRaw;
                if (strpos($cinRaw, ' ') !== false) {
                    $parts = explode(' ', $cinRaw);
                    $cinTime = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                }
                
                if (preg_match('/^\d{2}:\d{2}/', $cinTime)) {
                    $officeTs = strtotime($attDate.' '.$officeStart.':00');
                    $graceTs  = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
                    $cinTs    = strtotime($attDate.' '.$cinTime);
                    
                    if ($officeTs !== false && $cinTs !== false) {
                        if (!isset($summary[$uid])) {
                            $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                        }
                        
                        // Check if check-in is after grace period (late)
                        if ($graceTs !== false && $cinTs > $graceTs) {
                        $summary[$uid]['late'] += 1;
                            // Calculate late hours (time after office start + grace, in hours)
                            $lateSeconds = $cinTs - $graceTs;
                            $lateHours = $lateSeconds / 3600; // Convert to hours
                            $summary[$uid]['late_hours'] += $lateHours;
                        } elseif ($cinTs >= $officeTs) {
                            // Check-in is on time (between office start and grace period)
                            $summary[$uid]['on_time'] += 1;
                        }
                        // If check-in is before office start, it's early (not counted as on_time or late)
                    }
                }
                
                // Calculate extra hours (overtime)
                $workedHours = 0.0;
                if (isset($row->th) && is_numeric($row->th) && $row->th > 0) {
                    $workedHours = (float)$row->th;
                } elseif ($checkOutCol !== null && isset($row->cout) && !empty($row->cout)) {
                    $coutRaw = (string)$row->cout;
                    $coutTime = $coutRaw;
                    if (strpos($coutRaw, ' ') !== false) {
                        $parts = explode(' ', $coutRaw);
                        $coutTime = isset($parts[1]) ? trim($parts[1]) : trim($coutRaw);
                    }
                    // Get check-in time for calculation
                    $cinTimeForCalc = $cinTime;
                    if (strpos($cinRaw, ' ') !== false) {
                        $parts = explode(' ', $cinRaw);
                        $cinTimeForCalc = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                    }
                    if (preg_match('/^\d{2}:\d{2}/', $cinTimeForCalc) && preg_match('/^\d{2}:\d{2}/', $coutTime)) {
                        $cinTs = strtotime($attDate.' '.$cinTimeForCalc);
                        $coutTs = strtotime($attDate.' '.$coutTime);
                        if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                            $workedSeconds = $coutTs - $cinTs;
                            $workedHours = $workedSeconds / 3600; // Convert to hours
                        }
                    }
                }
                
                if ($workedHours > $standardHours) {
                    if (!isset($summary[$uid])) {
                        $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                    }
                    $extraHours = $workedHours - $standardHours;
                    $summary[$uid]['extra_hours'] += $extraHours;
                }
            }
        }

        if ($this->db->table_exists('leave_requests')) {
            // Calculate leave days excluding weekends, future dates, and WFH requests
            $lrows = $this->db->select('lr.user_id, lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                ->from('leave_requests lr')
                ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                ->where_in('lr.status', ['lead_approved','hr_approved'])
                ->where('lr.start_date <=', $to)
                ->where('lr.end_date >=', $from)
                ->get()->result();
            foreach ($lrows as $lr) {
                // Skip WFH requests - they should not be counted as leave
                $is_wfh = false;
                if (isset($lr->reason) && strpos($lr->reason, 'WFH:') === 0) {
                    $is_wfh = true;
                } elseif (isset($lr->type_name) && strtolower(trim($lr->type_name)) === 'work from home') {
                    $is_wfh = true;
                }
                if ($is_wfh) {
                    continue; // Skip WFH requests - they are counted in WFH days, not leave days
                }
                
                $uid = (int)$lr->user_id;
                $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                if ($sd === '' || $ed === '') { continue; }
                
                // Calculate working days in leave period (excluding weekends and future dates)
                $leaveStart = strtotime(max($from, substr($sd, 0, 10)));
                $leaveEnd = strtotime(min($to, substr($ed, 0, 10), $today));
                $leaveWorkingDays = 0;
                $cur = $leaveStart;
                while ($cur !== false && $cur <= $leaveEnd) {
                    $dayOfWeek = (int)date('w', $cur); // 0=Sunday, 6=Saturday
                    if ($dayOfWeek != 0 && $dayOfWeek != 6) { // Not Sunday or Saturday
                        $leaveWorkingDays++;
                    }
                    $cur = strtotime('+1 day', $cur);
                }
                
                if (!isset($summary[$uid])) {
                    $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                }
                $summary[$uid]['leave'] += $leaveWorkingDays;
            }
        }

        // Calculate absent days: total working days - (present + half + wfh + leave)
        // Note: totalWorkingDays already excludes weekends and future dates
        $rowsOut = [];
        foreach ($summary as $uid => $s) {
            // Only include users that exist in allUsers
            if (!isset($allUsers[$uid])) {
                continue;
            }
            
            $totalAttended = $s['present'] + ($s['half'] * 0.5) + $s['wfh'];
            $calculatedAbsent = max(0, $totalWorkingDays - $totalAttended - $s['leave']);
            
            // Use calculated absent if no explicit absent records, otherwise use the higher value
            $finalAbsent = max($s['absent'], $calculatedAbsent);
            
            $o = new stdClass();
            $o->user_id = (int)$uid;
            $o->name = $getName((int)$uid);
            $o->present_days = $s['present'] > 0 ? rtrim(rtrim(number_format($s['present'], 2, '.', ''), '0'), '.') : '0';
            $o->half_days = $s['half'] > 0 ? rtrim(rtrim(number_format($s['half'], 2, '.', ''), '0'), '.') : '0';
            $o->wfh_days = $s['wfh'] > 0 ? rtrim(rtrim(number_format($s['wfh'], 2, '.', ''), '0'), '.') : '0';
            $o->absent_days = $finalAbsent > 0 ? rtrim(rtrim(number_format($finalAbsent, 2, '.', ''), '0'), '.') : '0';
            $o->leave_days = $s['leave'] > 0 ? rtrim(rtrim(number_format($s['leave'], 2, '.', ''), '0'), '.') : '0';
            $o->late_days = $s['late'] > 0 ? rtrim(rtrim(number_format($s['late'], 2, '.', ''), '0'), '.') : '0';
            $o->on_time_days = isset($s['on_time']) && $s['on_time'] > 0 ? rtrim(rtrim(number_format($s['on_time'], 2, '.', ''), '0'), '.') : '0';
            $o->late_hours = isset($s['late_hours']) && $s['late_hours'] > 0 ? rtrim(rtrim(number_format($s['late_hours'], 2, '.', ''), '0'), '.') : '0';
            $o->extra_hours = isset($s['extra_hours']) && $s['extra_hours'] > 0 ? rtrim(rtrim(number_format($s['extra_hours'], 2, '.', ''), '0'), '.') : '0';
            $o->total_working_days = $totalWorkingDays;
            $rowsOut[] = $o;
        }

        usort($rowsOut, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        // Fetch attendance notes for the selected period
        $attendanceNotes = [];
        if ($this->db->field_exists('notes', 'attendance')) {
            $notesQuery = $this->db->select("`$userCol` AS uid, `$dateCol` AS d, `notes`")
                ->from('attendance')
                ->where("`$dateCol` >=", $from)
                ->where("`$dateCol` <=", $to)
                ->where("`notes` IS NOT NULL")
                ->where("`notes` !=", '')
                ->where("TRIM(`notes`) !=", '')
                ->order_by($dateCol, 'ASC')
                ->order_by($userCol, 'ASC')
                ->get();
            
            if ($notesQuery && $notesQuery->num_rows() > 0) {
                foreach ($notesQuery->result() as $noteRow) {
                    $uid = (int)$noteRow->uid;
                    $attDate = isset($noteRow->d) ? (string)$noteRow->d : '';
                    $notes = isset($noteRow->notes) ? trim((string)$noteRow->notes) : '';
                    
                    if ($attDate === '' || $notes === '') continue;
                    
                    // Extract date part if datetime
                    if (strpos($attDate, ' ') !== false) {
                        $attDate = trim(explode(' ', $attDate)[0]);
                    }
                    
                    if (!isset($attendanceNotes[$uid])) {
                        $attendanceNotes[$uid] = [];
                    }
                    if (!isset($attendanceNotes[$uid][$attDate])) {
                        $attendanceNotes[$uid][$attDate] = [];
                    }
                    $attendanceNotes[$uid][$attDate][] = $notes;
                }
            }
        }
        
        // Get settings for display
        $officeStartDisplay = $officeStart;
        $graceMinutesDisplay = $graceMinutes;
        $standardHoursDisplay = $standardHours;
        
        $this->load->view('reports/attendance_employee', [
            'period' => $period,
            'month' => $month,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'total_working_days' => $totalWorkingDays,
            'office_start_time' => $officeStartDisplay,
            'grace_minutes' => $graceMinutesDisplay,
            'standard_working_hours' => $standardHoursDisplay,
            'rows' => $rowsOut,
            'attendance_notes' => $attendanceNotes,
            'getName' => $getName,
            'holidays' => $holidays
        ]);
    }

    // GET /reports/attendance?period=daily|weekly|monthly&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD&department_id=X&export=csv|pdf
    public function attendance()
    {
        $period = $this->input->get('period') ?: 'daily';
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $departmentId = $this->input->get('department_id');
        $export = $this->input->get('export');
        
        // Set default date range if not provided
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }
        
        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
        }
        
        $daily = $weekly = $monthly = [];
        $dailyLate = $weeklyLate = $monthlyLate = [];
        $departments = [];
        
        if ($this->db->table_exists('attendance')) {
            // Detect user, date, and status columns
            $fields = $this->db->list_fields('attendance');
            $userCandidates = array('user_id','employee_id','emp_id','staff_id','uid');
            $dateCandidates = array('date','attendance_date','att_date','created_at','checked_at');
            $statusCandidates = array('status','attendance_status','state');
            $userCol = $dateCol = $statusCol = null;
            foreach ($userCandidates as $c){ if (in_array($c, $fields, true)) { $userCol = $c; break; } }
            foreach ($dateCandidates as $c){ if (in_array($c, $fields, true)) { $dateCol = $c; break; } }
            foreach ($statusCandidates as $c){ if (in_array($c, $fields, true)) { $statusCol = $c; break; } }
            if ($userCol === null) { $userCol = isset($fields[0]) ? $fields[0] : 'user_id'; }
            if ($dateCol === null) { $dateCol = isset($fields[1]) ? $fields[1] : 'date'; }
            if ($statusCol === null) { $statusCol = isset($fields[2]) ? $fields[2] : 'status'; }

            // Get departments for filtering
            if ($this->db->table_exists('departments')) {
                $departments = $this->db->select('id, dept_name as name')->order_by('dept_name')->get('departments')->result();
            }

            // Build label map from users/employees with department info
            $labels = [];
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id, u.email');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name'); }
                if ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')){
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('e.name AS emp_name'); }
                    if ($this->db->field_exists('full_name','employees')) { $this->db->select('e.full_name AS emp_full_name'); }
                    if ($this->db->field_exists('first_name','employees')) { $this->db->select('e.first_name AS emp_first_name'); }
                    if ($this->db->field_exists('middle_name','employees')) { $this->db->select('e.middle_name AS emp_middle_name'); }
                    if ($this->db->field_exists('last_name','employees')) { $this->db->select('e.last_name AS emp_last_name'); }
                    if ($this->db->field_exists('department','employees')) { $this->db->select('e.department'); }
                }
                $users = $this->db->from('users u')->get()->result();
                foreach ($users as $u){ $labels[(int)$u->id] = $u; }
            }

            // Helper to get employee name
            $getName = function($uid) use ($labels) {
                $label = isset($labels[$uid]) ? $labels[$uid] : null;
                if ($label){
                    $empParts = [];
                    if (isset($label->emp_first_name) && trim((string)$label->emp_first_name)!=='') { $empParts[] = trim((string)$label->emp_first_name); }
                    if (isset($label->emp_middle_name) && trim((string)$label->emp_middle_name)!=='') { $empParts[] = trim((string)$label->emp_middle_name); }
                    if (isset($label->emp_last_name) && trim((string)$label->emp_last_name)!=='') { $empParts[] = trim((string)$label->emp_last_name); }
                    if (!empty($empParts)) { return trim(implode(' ', $empParts)); }
                    if (isset($label->emp_full_name) && trim((string)$label->emp_full_name)!=='') { return trim((string)$label->emp_full_name); }
                    if (isset($label->emp_name) && trim((string)$label->emp_name)!=='') { return trim((string)$label->emp_name); }
                    if (isset($label->full_name) && trim((string)$label->full_name)!=='') { return trim((string)$label->full_name); }
                    if (isset($label->name) && trim((string)$label->name)!=='') { return trim((string)$label->name); }
                    return $label->email;
                }
                return $uid ? ('User #'.$uid) : 'Unknown';
            };

            // Build base WHERE conditions
            $whereConditions = "`$dateCol` >= '$startDate' AND `$dateCol` <= '$endDate'";
            if ($departmentId && $departmentId !== 'all') {
                // Get department name from departments table
                $dept = $this->db->select('dept_name')->where('id', (int)$departmentId)->get('departments')->row();
                if ($dept) {
                    $whereConditions .= " AND EXISTS (
                        SELECT 1 FROM employees e 
                        WHERE e.user_id = `$userCol` AND e.department = '".$this->db->escape_str($dept->dept_name)."'
                    )";
                }
            }

            // Aggregate for daily
            $sql = "SELECT `$userCol` AS uid, DATE(`$dateCol`) AS bucket, `$statusCol` AS status, COUNT(*) AS cnt 
                    FROM attendance 
                    WHERE $whereConditions
                    GROUP BY `$userCol`, DATE(`$dateCol`), `$statusCol` 
                    ORDER BY bucket DESC, uid ASC 
                    LIMIT 500";
            $daily = $this->db->query($sql)->result();
            foreach ($daily as &$d){ $d->name = $getName((int)$d->uid); }

            // Aggregate for weekly
            $sql = "SELECT `$userCol` AS uid, YEARWEEK(`$dateCol`) AS bucket, `$statusCol` AS status, COUNT(*) AS cnt 
                    FROM attendance 
                    WHERE $whereConditions
                    GROUP BY `$userCol`, YEARWEEK(`$dateCol`), `$statusCol` 
                    ORDER BY bucket DESC, uid ASC 
                    LIMIT 500";
            $weekly = $this->db->query($sql)->result();
            foreach ($weekly as &$w){ $w->name = $getName((int)$w->uid); }

            // Aggregate for monthly
            $sql = "SELECT `$userCol` AS uid, DATE_FORMAT(`$dateCol`, '%Y-%m') AS bucket, `$statusCol` AS status, COUNT(*) AS cnt 
                    FROM attendance 
                    WHERE $whereConditions
                    GROUP BY `$userCol`, DATE_FORMAT(`$dateCol`, '%Y-%m'), `$statusCol` 
                    ORDER BY bucket DESC, uid ASC 
                    LIMIT 500";
            $monthly = $this->db->query($sql)->result();
            foreach ($monthly as &$m){ $m->name = $getName((int)$m->uid); }

            // Late aggregates (per user & period) when check-in column exists
            $fieldsLate = $this->db->list_fields('attendance');
            $checkInColLate = null;
            if (in_array('punch_in', $fieldsLate, true)) { $checkInColLate = 'punch_in'; }
            elseif (in_array('check_in', $fieldsLate, true)) { $checkInColLate = 'check_in'; }

            if ($checkInColLate !== null) {
                // Read office start and grace from settings with defaults
                $officeStart = '09:30';
                $graceMinutes = 15;
                if (isset($this->settings)) {
                    try {
                        $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                        if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) { $officeStart = $stVal; }
                        $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                        if (is_numeric($gmVal)) { $graceMinutes = (int)$gmVal; }
                    } catch (Exception $e) { /* ignore */ }
                }

                // Compute cutoff time (office start + grace) as HH:MM:SS
                $tBase = strtotime('1970-01-01 '.$officeStart.':00');
                if ($tBase !== false) {
                    $cutoffTime = date('H:i:s', $tBase + ($graceMinutes * 60));

                    // Daily late summary
                    $sql = "SELECT `$userCol` AS uid, DATE(`$dateCol`) AS bucket, COUNT(*) AS late_cnt
                            FROM attendance
                            WHERE $whereConditions AND `$checkInColLate` IS NOT NULL AND TIME(`$checkInColLate`) > ?
                            GROUP BY `$userCol`, DATE(`$dateCol`)
                            ORDER BY bucket DESC, uid ASC
                            LIMIT 500";
                    $dailyLate = $this->db->query($sql, [$cutoffTime])->result();
                    foreach ($dailyLate as &$r) { $r->name = $getName((int)$r->uid); }

                    // Weekly late summary
                    $sql = "SELECT `$userCol` AS uid, YEARWEEK(`$dateCol`) AS bucket, COUNT(*) AS late_cnt
                            FROM attendance
                            WHERE $whereConditions AND `$checkInColLate` IS NOT NULL AND TIME(`$checkInColLate`) > ?
                            GROUP BY `$userCol`, YEARWEEK(`$dateCol`)
                            ORDER BY bucket DESC, uid ASC
                            LIMIT 500";
                    $weeklyLate = $this->db->query($sql, [$cutoffTime])->result();
                    foreach ($weeklyLate as &$r) { $r->name = $getName((int)$r->uid); }

                    // Monthly late summary
                    $sql = "SELECT `$userCol` AS uid, DATE_FORMAT(`$dateCol`, '%Y-%m') AS bucket, COUNT(*) AS late_cnt
                            FROM attendance
                            WHERE $whereConditions AND `$checkInColLate` IS NOT NULL AND TIME(`$checkInColLate`) > ?
                            GROUP BY `$userCol`, DATE_FORMAT(`$dateCol`, '%Y-%m')
                            ORDER BY bucket DESC, uid ASC
                            LIMIT 500";
                    $monthlyLate = $this->db->query($sql, [$cutoffTime])->result();
                    foreach ($monthlyLate as &$r) { $r->name = $getName((int)$r->uid); }
                }
            }
        }
        
        // Handle export requests
        if ($export) {
            return $this->export_attendance_data($period, compact('daily', 'weekly', 'monthly', 'dailyLate', 'weeklyLate', 'monthlyLate'), $export);
        }
        
        $this->load->view('reports/attendance', [
            'period'=>$period,
            'daily'=>$daily,
            'weekly'=>$weekly,
            'monthly'=>$monthly,
            'dailyLate'=>$dailyLate,
            'weeklyLate'=>$weeklyLate,
            'monthlyLate'=>$monthlyLate,
            'departments'=>$departments,
            'selected_department'=>$departmentId,
            'start_date'=>$startDate,
            'end_date'=>$endDate,
        ]);
    }

    // Export attendance data
    private function export_attendance_data($period, $data, $format) {
        $this->load->dbutil();
        
        if ($format === 'csv') {
            // CSV Export
            $filename = 'attendance_report_' . $period . '_' . date('Y-m-d') . '.csv';
            
            // Prepare data based on period
            $exportData = [];
            switch ($period) {
                case 'daily':
                    $exportData = $data['daily'];
                    break;
                case 'weekly':
                    $exportData = $data['weekly'];
                    break;
                case 'monthly':
                    $exportData = $data['monthly'];
                    break;
            }
            
            // Create CSV data
            $csvData = "Employee,Period,Status,Count\n";
            foreach ($exportData as $row) {
                $csvData .= '"' . str_replace('"', '""', $row->name) . '",';
                $csvData .= '"' . $row->bucket . '",';
                $csvData .= '"' . $row->status . '",';
                $csvData .= $row->cnt . "\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csvData;
            exit;
            
        } elseif ($format === 'pdf') {
            // PDF Export (simple HTML to PDF)
            $filename = 'attendance_report_' . $period . '_' . date('Y-m-d') . '.pdf';
            
            $html = '<h2>Attendance Report - ' . ucfirst($period) . '</h2>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<tr><th>Employee</th><th>Period</th><th>Status</th><th>Count</th></tr>';
            
            $exportData = [];
            switch ($period) {
                case 'daily':
                    $exportData = $data['daily'];
                    break;
                case 'weekly':
                    $exportData = $data['weekly'];
                    break;
                case 'monthly':
                    $exportData = $data['monthly'];
                    break;
            }
            
            foreach ($exportData as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row->name) . '</td>';
                $html .= '<td>' . htmlspecialchars($row->bucket) . '</td>';
                $html .= '<td>' . htmlspecialchars($row->status) . '</td>';
                $html .= '<td>' . $row->cnt . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            
            // Simple PDF headers (requires PDF library to be installed)
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // For now, output as HTML with print-friendly styling
            echo '<html><head><style>body{font-family:Arial,sans-serif;}table{width:100%;border-collapse:collapse;}</style></head><body>' . $html . '</body></html>';
            exit;
        }
    }
    
    // Export attendance employee report
    public function export_attendance_employee() {
        // Check permission-based access
        $has_access = false;
        if (function_exists('has_module_access')) {
            // Check specific permission for attendance-employee report
            $has_access = has_module_access('reports_attendance_employee');
            // Fallback: check general reports permission if specific one doesn't exist
            if (!$has_access) {
                $has_access = has_module_access('reports');
            }
        }
        
        // If no permission system or no access, check if user is logged in
        if (!$has_access) {
            $user_id_check = (int)$this->session->userdata('user_id');
            if (!$user_id_check) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'Please login to access this resource.']));
                return;
            }
            // If permission system exists but user doesn't have access, show error
            if (function_exists('has_module_access')) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'You do not have permission to export Employee Attendance Reports.']));
                return;
            }
            // Fallback: if no permission system configured, allow Admin/HR (role 1,2) for backward compatibility
            $role_id = (int)$this->session->userdata('role_id');
            if (!in_array($role_id, [1, 2], true)) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'You do not have permission to export Employee Attendance Reports.']));
                return;
            }
        }
        
        try {
            $format = $this->input->get('export'); // 'excel' or 'pdf'
            $userIdsStr = $this->input->get('user_ids');
            $period = $this->input->get('period') ?: 'monthly';
            $month = $this->input->get('month');
            $date = $this->input->get('date');
            
            // Validate format
            if (!in_array($format, ['excel', 'pdf'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'Invalid export format. Use "excel" or "pdf".']));
                return;
            }
            
            // Validate user IDs
            if (empty($userIdsStr)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'No employees selected for export.']));
                return;
            }
            
            $userIds = array_filter(array_map('intval', explode(',', $userIdsStr)));
            if (empty($userIds)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'Invalid employee selection.']));
                return;
            }
            
            // Calculate date range based on period
            $from = '';
            $to = '';
            if ($period === 'daily' && $date) {
                $from = $date;
                $to = $date;
            } elseif ($period === 'weekly' && $date) {
                $startTs = strtotime($date);
                $dow = (int)date('w', $startTs);
                $mondayOffset = ($dow === 0 ? -6 : 1 - $dow);
                $mondayTs = strtotime("$mondayOffset days", $startTs);
                $sundayTs = strtotime('+6 days', $mondayTs);
                $from = date('Y-m-d', $mondayTs);
                $to = date('Y-m-d', $sundayTs);
            } elseif ($period === 'monthly' && $month) {
                $from = $month . '-01';
                $lastDay = date('Y-m-t', strtotime($from));
                $to = min($lastDay, date('Y-m-d'));
            } else {
                $from = date('Y-m-01');
                $to = date('Y-m-d');
            }
            
            // Check if it's a single user export (detail view) - export daily details
            if (count($userIds) === 1) {
                if ($format === 'excel') {
                    $this->export_attendance_employee_detail_excel($userIds[0], $period, $from, $to, $month, $date);
                } elseif ($format === 'pdf') {
                    $this->export_attendance_employee_detail_pdf($userIds[0], $period, $from, $to, $month, $date);
                }
            } else {
                // Multiple users - export summary
                if ($format === 'excel') {
                    $this->export_attendance_employee_excel($userIds, $period, $from, $to, $month, $date);
                } elseif ($format === 'pdf') {
                    $this->export_attendance_employee_pdf($userIds, $period, $from, $to, $month, $date);
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Export attendance employee error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => 'An error occurred during export: ' . $e->getMessage()]));
        }
    }
    
    private function export_attendance_employee_excel($userIds, $period, $from, $to, $month, $date) {
        try {
            // Get user names
            $users = [];
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name AS name'); }
                elseif ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                else { $this->db->select('u.email AS name'); }
                $this->db->where_in('u.id', $userIds);
                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('COALESCE(e.name, u.full_name, u.name, u.email) AS name'); }
                }
                $users = $this->db->from('users u')->get()->result();
            }
            
            // Get attendance data
            $dateCol = $this->db->field_exists('date', 'attendance') ? 'date' : ($this->db->field_exists('attendance_date', 'attendance') ? 'attendance_date' : 'created_at');
            $userCol = $this->db->field_exists('user_id', 'attendance') ? 'user_id' : ($this->db->field_exists('employee_id', 'attendance') ? 'employee_id' : 'id');
            $statusCol = $this->db->field_exists('status', 'attendance') ? 'status' : 'attendance_status';
            
            $summary = [];
            $rows = $this->db->select("`$userCol` AS uid, `$statusCol` AS st, COUNT(*) AS cnt")
                ->from('attendance')
                ->where("`$dateCol` >=", $from)
                ->where("`$dateCol` <=", $to)
                ->where_in("`$userCol`", $userIds)
                ->group_by(["`$userCol`","`$statusCol`"])
                ->get()->result();
                
            foreach ($rows as $r) {
                $uid = (int)$r->uid;
                $st = strtolower(trim((string)$r->st));
                $cnt = (float)$r->cnt;
                if (!isset($summary[$uid])) {
                    $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                }
                if ($st === 'present') { $summary[$uid]['present'] += $cnt; }
                elseif ($st === 'half_day') { $summary[$uid]['half'] += $cnt; }
                elseif ($st === 'work_from_home') { $summary[$uid]['wfh'] += $cnt; }
                elseif ($st === 'absent') { $summary[$uid]['absent'] += $cnt; }
            }
            
            // Initialize summary for all users (even those without attendance records)
            foreach ($users as $user) {
                $uid = (int)$user->id;
                if (!isset($summary[$uid])) {
                    $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                } else {
                    // Ensure all keys exist
                    if (!isset($summary[$uid]['late_hours'])) { $summary[$uid]['late_hours'] = 0.0; }
                    if (!isset($summary[$uid]['extra_hours'])) { $summary[$uid]['extra_hours'] = 0.0; }
                    if (!isset($summary[$uid]['on_time'])) { $summary[$uid]['on_time'] = 0.0; }
                    if (!isset($summary[$uid]['leave'])) { $summary[$uid]['leave'] = 0.0; }
                }
            }
            
            // Calculate late days, late hours, and extra hours (same logic as UI grid)
            // Get settings
            $officeStart = '09:30';
            $graceMinutes = 15;
            $standardHours = 8.0;
            if (isset($this->settings)) {
                try {
                    $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                    if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) {
                        $officeStart = $stVal;
                    }
                    $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                    if (is_numeric($gmVal)) {
                        $graceMinutes = (int)$gmVal;
                    }
                    $shVal = $this->settings->get_setting('attendance_standard_working_hours');
                    if ($shVal === null || $shVal === '') {
                        $shVal = $this->settings->get_setting('standard_working_hours', $standardHours);
                    }
                    if (is_numeric($shVal)) {
                        $standardHours = (float)$shVal;
                    }
                } catch (Exception $e) {
                    // use defaults
                }
            }
            
            // Get check-in and check-out columns
            $fields = $this->db->list_fields('attendance');
            $checkInCol = null;
            $checkOutCol = null;
            if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
            elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }
            if (in_array('punch_out', $fields, true)) { $checkOutCol = 'punch_out'; }
            elseif (in_array('check_out', $fields, true)) { $checkOutCol = 'check_out'; }
            
            if ($checkInCol !== null) {
                $selectCols = ["`$userCol` AS uid", "`$dateCol` AS d", "`$checkInCol` AS cin"];
                if ($checkOutCol !== null) {
                    $selectCols[] = "`$checkOutCol` AS cout";
                }
                if ($this->db->field_exists('total_hours', 'attendance')) {
                    $selectCols[] = "`total_hours` AS th";
                }
                
                $attendanceRows = $this->db->select(implode(', ', $selectCols))
                    ->from('attendance')
                    ->where("`$dateCol` >=", $from)
                    ->where("`$dateCol` <=", $to)
                    ->where_in("`$userCol`", $userIds)
                    ->where("`$statusCol` !=", 'absent')
                    ->get()->result();
                
                foreach ($attendanceRows as $row) {
                    $uid = (int)$row->uid;
                    $attDate = isset($row->d) ? (string)$row->d : '';
                    $cinRaw = isset($row->cin) ? (string)$row->cin : '';
                    
                    if ($attDate === '' || $cinRaw === '') continue;
                    
                    // Initialize summary if not exists
                    if (!isset($summary[$uid])) {
                        $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                    }
                    
                    // Calculate late days and late hours
                    $cinTime = $cinRaw;
                    if (strpos($cinRaw, ' ') !== false) {
                        $parts = explode(' ', $cinRaw);
                        $cinTime = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                    }
                    
                    if (preg_match('/^\d{2}:\d{2}/', $cinTime)) {
                        $officeTs = strtotime($attDate.' '.$officeStart.':00');
                        $graceTs  = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
                        $cinTs    = strtotime($attDate.' '.$cinTime);
                        
                        if ($officeTs !== false && $cinTs !== false) {
                            // Check if check-in is after grace period (late)
                            if ($graceTs !== false && $cinTs > $graceTs) {
                                $summary[$uid]['late'] += 1;
                                // Calculate late hours (time after office start + grace, in hours)
                                $lateSeconds = $cinTs - $graceTs;
                                $lateHours = $lateSeconds / 3600; // Convert to hours
                                $summary[$uid]['late_hours'] += $lateHours;
                            } elseif ($cinTs >= $officeTs) {
                                // Check-in is on time (between office start and grace period)
                                $summary[$uid]['on_time'] += 1;
                            }
                        }
                    }
                    
                    // Calculate extra hours (overtime)
                    $workedHours = 0.0;
                    if (isset($row->th) && is_numeric($row->th) && $row->th > 0) {
                        $workedHours = (float)$row->th;
                    } elseif ($checkOutCol !== null && isset($row->cout) && !empty($row->cout)) {
                        $coutRaw = (string)$row->cout;
                        $coutTime = $coutRaw;
                        if (strpos($coutRaw, ' ') !== false) {
                            $parts = explode(' ', $coutRaw);
                            $coutTime = isset($parts[1]) ? trim($parts[1]) : trim($coutRaw);
                        }
                        // Get check-in time for calculation
                        $cinTimeForCalc = $cinTime;
                        if (strpos($cinRaw, ' ') !== false) {
                            $parts = explode(' ', $cinRaw);
                            $cinTimeForCalc = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                        }
                        if (preg_match('/^\d{2}:\d{2}/', $cinTimeForCalc) && preg_match('/^\d{2}:\d{2}/', $coutTime)) {
                            $cinTs = strtotime($attDate.' '.$cinTimeForCalc);
                            $coutTs = strtotime($attDate.' '.$coutTime);
                            if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                                $workedSeconds = $coutTs - $cinTs;
                                $workedHours = $workedSeconds / 3600; // Convert to hours
                            }
                        }
                    }
                    
                    if ($workedHours > $standardHours) {
                        $extraHours = $workedHours - $standardHours;
                        $summary[$uid]['extra_hours'] += $extraHours;
                    }
                }
            }
            
            // Calculate leave days from leave_requests table
            if ($this->db->table_exists('leave_requests')) {
                $today = date('Y-m-d');
                $lrows = $this->db->select('lr.user_id, lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                    ->from('leave_requests lr')
                    ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                    ->where_in('lr.user_id', $userIds)
                    ->where_in('lr.status', ['lead_approved','hr_approved'])
                    ->where('lr.start_date <=', $to)
                    ->where('lr.end_date >=', $from)
                    ->get()->result();
                    
                foreach ($lrows as $lr) {
                    // Skip WFH requests - they should not be counted as leave
                    $is_wfh = false;
                    if (isset($lr->reason) && strpos($lr->reason, 'WFH:') === 0) {
                        $is_wfh = true;
                    } elseif (isset($lr->type_name) && strtolower(trim($lr->type_name)) === 'work from home') {
                        $is_wfh = true;
                    }
                    if ($is_wfh) {
                        continue; // Skip WFH requests
                    }
                    
                    $uid = (int)$lr->user_id;
                    $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                    $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                    if ($sd === '' || $ed === '') { continue; }
                    
                    // Initialize summary if not exists
                    if (!isset($summary[$uid])) {
                        $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                    }
                    
                    // Calculate working days in leave period (excluding weekends and future dates)
                    $leaveStart = strtotime(max($from, substr($sd, 0, 10)));
                    $leaveEnd = strtotime(min($to, substr($ed, 0, 10), $today));
                    $leaveWorkingDays = 0;
                    $cur = $leaveStart;
                    while ($cur !== false && $cur <= $leaveEnd) {
                        $dayOfWeek = (int)date('w', $cur); // 0=Sunday, 6=Saturday
                        if ($dayOfWeek != 0 && $dayOfWeek != 6) { // Not Sunday or Saturday
                            $leaveWorkingDays++;
                        }
                        $cur = strtotime('+1 day', $cur);
                    }
                    
                    $summary[$uid]['leave'] += $leaveWorkingDays;
                }
            }
            
            // Prepare CSV data (Excel compatible)
            $filename = 'attendance_employee_report_' . $period . '_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, [
                'Employee Name',
                'Employee ID',
                'Period',
                'From',
                'To',
                'Present Days',
                'Half Days',
                'WFH Days',
                'Absent Days',
                'Leave Days',
                'Late Days',
                'On Time Days',
                'Late Hours',
                'Extra Hours'
            ]);
            
            // Data rows
            foreach ($users as $user) {
                $uid = (int)$user->id;
                $name = isset($user->name) ? $user->name : 'Unknown';
                $data = isset($summary[$uid]) ? $summary[$uid] : ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                
                fputcsv($output, [
                    $name,
                    $uid,
                    ucfirst($period),
                    $from,
                    $to,
                    number_format(isset($data['present']) ? (float)$data['present'] : 0.0, 1),
                    number_format(isset($data['half']) ? (float)$data['half'] : 0.0, 1),
                    number_format(isset($data['wfh']) ? (float)$data['wfh'] : 0.0, 1),
                    number_format(isset($data['absent']) ? (float)$data['absent'] : 0.0, 1),
                    number_format(isset($data['leave']) ? (float)$data['leave'] : 0.0, 1),
                    number_format(isset($data['late']) ? (float)$data['late'] : 0.0, 1),
                    number_format(isset($data['on_time']) ? (float)$data['on_time'] : 0.0, 1),
                    number_format(isset($data['late_hours']) ? (float)$data['late_hours'] : 0.0, 2),
                    number_format(isset($data['extra_hours']) ? (float)$data['extra_hours'] : 0.0, 2)
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            log_message('error', 'Export Excel error: ' . $e->getMessage());
            show_error('Error generating Excel export: ' . $e->getMessage(), 500);
        }
    }
    
    private function export_attendance_employee_pdf($userIds, $period, $from, $to, $month, $date) {
        try {
            // Get user names
            $users = [];
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name AS name'); }
                elseif ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                else { $this->db->select('u.email AS name'); }
                $this->db->where_in('u.id', $userIds);
                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('COALESCE(e.name, u.full_name, u.name, u.email) AS name'); }
                }
                $users = $this->db->from('users u')->get()->result();
            }
            
            // Get attendance data (same as Excel)
            $dateCol = $this->db->field_exists('date', 'attendance') ? 'date' : ($this->db->field_exists('attendance_date', 'attendance') ? 'attendance_date' : 'created_at');
            $userCol = $this->db->field_exists('user_id', 'attendance') ? 'user_id' : ($this->db->field_exists('employee_id', 'attendance') ? 'employee_id' : 'id');
            $statusCol = $this->db->field_exists('status', 'attendance') ? 'status' : 'attendance_status';
            
            $summary = [];
            $rows = $this->db->select("`$userCol` AS uid, `$statusCol` AS st, COUNT(*) AS cnt")
                ->from('attendance')
                ->where("`$dateCol` >=", $from)
                ->where("`$dateCol` <=", $to)
                ->where_in("`$userCol`", $userIds)
                ->group_by(["`$userCol`","`$statusCol`"])
                ->get()->result();
                
            foreach ($rows as $r) {
                $uid = (int)$r->uid;
                $st = strtolower(trim((string)$r->st));
                $cnt = (float)$r->cnt;
                if (!isset($summary[$uid])) {
                    $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                }
                if ($st === 'present') { $summary[$uid]['present'] += $cnt; }
                elseif ($st === 'half_day') { $summary[$uid]['half'] += $cnt; }
                elseif ($st === 'work_from_home') { $summary[$uid]['wfh'] += $cnt; }
                elseif ($st === 'absent') { $summary[$uid]['absent'] += $cnt; }
            }
            
            // Initialize summary for all users (even those without attendance records)
            foreach ($users as $user) {
                $uid = (int)$user->id;
                if (!isset($summary[$uid])) {
                    $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                } else {
                    // Ensure all keys exist
                    if (!isset($summary[$uid]['late_hours'])) { $summary[$uid]['late_hours'] = 0.0; }
                    if (!isset($summary[$uid]['extra_hours'])) { $summary[$uid]['extra_hours'] = 0.0; }
                    if (!isset($summary[$uid]['on_time'])) { $summary[$uid]['on_time'] = 0.0; }
                    if (!isset($summary[$uid]['leave'])) { $summary[$uid]['leave'] = 0.0; }
                }
            }
            
            // Calculate late days, late hours, and extra hours (same logic as UI grid)
            // Get settings
            $officeStart = '09:30';
            $graceMinutes = 15;
            $standardHours = 8.0;
            if (isset($this->settings)) {
                try {
                    $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                    if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) {
                        $officeStart = $stVal;
                    }
                    $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                    if (is_numeric($gmVal)) {
                        $graceMinutes = (int)$gmVal;
                    }
                    $shVal = $this->settings->get_setting('attendance_standard_working_hours');
                    if ($shVal === null || $shVal === '') {
                        $shVal = $this->settings->get_setting('standard_working_hours', $standardHours);
                    }
                    if (is_numeric($shVal)) {
                        $standardHours = (float)$shVal;
                    }
                } catch (Exception $e) {
                    // use defaults
                }
            }
            
            // Get check-in and check-out columns
            $fields = $this->db->list_fields('attendance');
            $checkInCol = null;
            $checkOutCol = null;
            if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
            elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }
            if (in_array('punch_out', $fields, true)) { $checkOutCol = 'punch_out'; }
            elseif (in_array('check_out', $fields, true)) { $checkOutCol = 'check_out'; }
            
            if ($checkInCol !== null) {
                $selectCols = ["`$userCol` AS uid", "`$dateCol` AS d", "`$checkInCol` AS cin"];
                if ($checkOutCol !== null) {
                    $selectCols[] = "`$checkOutCol` AS cout";
                }
                if ($this->db->field_exists('total_hours', 'attendance')) {
                    $selectCols[] = "`total_hours` AS th";
                }
                
                $attendanceRows = $this->db->select(implode(', ', $selectCols))
                    ->from('attendance')
                    ->where("`$dateCol` >=", $from)
                    ->where("`$dateCol` <=", $to)
                    ->where_in("`$userCol`", $userIds)
                    ->where("`$statusCol` !=", 'absent')
                    ->get()->result();
                
                foreach ($attendanceRows as $row) {
                    $uid = (int)$row->uid;
                    $attDate = isset($row->d) ? (string)$row->d : '';
                    $cinRaw = isset($row->cin) ? (string)$row->cin : '';
                    
                    if ($attDate === '' || $cinRaw === '') continue;
                    
                    // Initialize summary if not exists
                    if (!isset($summary[$uid])) {
                        $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                    }
                    
                    // Calculate late days and late hours
                    $cinTime = $cinRaw;
                    if (strpos($cinRaw, ' ') !== false) {
                        $parts = explode(' ', $cinRaw);
                        $cinTime = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                    }
                    
                    if (preg_match('/^\d{2}:\d{2}/', $cinTime)) {
                        $officeTs = strtotime($attDate.' '.$officeStart.':00');
                        $graceTs  = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
                        $cinTs    = strtotime($attDate.' '.$cinTime);
                        
                        if ($officeTs !== false && $cinTs !== false) {
                            // Check if check-in is after grace period (late)
                            if ($graceTs !== false && $cinTs > $graceTs) {
                                $summary[$uid]['late'] += 1;
                                // Calculate late hours (time after office start + grace, in hours)
                                $lateSeconds = $cinTs - $graceTs;
                                $lateHours = $lateSeconds / 3600; // Convert to hours
                                $summary[$uid]['late_hours'] += $lateHours;
                            } elseif ($cinTs >= $officeTs) {
                                // Check-in is on time (between office start and grace period)
                                $summary[$uid]['on_time'] += 1;
                            }
                        }
                    }
                    
                    // Calculate extra hours (overtime)
                    $workedHours = 0.0;
                    if (isset($row->th) && is_numeric($row->th) && $row->th > 0) {
                        $workedHours = (float)$row->th;
                    } elseif ($checkOutCol !== null && isset($row->cout) && !empty($row->cout)) {
                        $coutRaw = (string)$row->cout;
                        $coutTime = $coutRaw;
                        if (strpos($coutRaw, ' ') !== false) {
                            $parts = explode(' ', $coutRaw);
                            $coutTime = isset($parts[1]) ? trim($parts[1]) : trim($coutRaw);
                        }
                        // Get check-in time for calculation
                        $cinTimeForCalc = $cinTime;
                        if (strpos($cinRaw, ' ') !== false) {
                            $parts = explode(' ', $cinRaw);
                            $cinTimeForCalc = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                        }
                        if (preg_match('/^\d{2}:\d{2}/', $cinTimeForCalc) && preg_match('/^\d{2}:\d{2}/', $coutTime)) {
                            $cinTs = strtotime($attDate.' '.$cinTimeForCalc);
                            $coutTs = strtotime($attDate.' '.$coutTime);
                            if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                                $workedSeconds = $coutTs - $cinTs;
                                $workedHours = $workedSeconds / 3600; // Convert to hours
                            }
                        }
                    }
                    
                    if ($workedHours > $standardHours) {
                        $extraHours = $workedHours - $standardHours;
                        $summary[$uid]['extra_hours'] += $extraHours;
                    }
                }
            }
            
            // Calculate leave days from leave_requests table
            if ($this->db->table_exists('leave_requests')) {
                $today = date('Y-m-d');
                $lrows = $this->db->select('lr.user_id, lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                    ->from('leave_requests lr')
                    ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                    ->where_in('lr.user_id', $userIds)
                    ->where_in('lr.status', ['lead_approved','hr_approved'])
                    ->where('lr.start_date <=', $to)
                    ->where('lr.end_date >=', $from)
                    ->get()->result();
                    
                foreach ($lrows as $lr) {
                    // Skip WFH requests - they should not be counted as leave
                    $is_wfh = false;
                    if (isset($lr->reason) && strpos($lr->reason, 'WFH:') === 0) {
                        $is_wfh = true;
                    } elseif (isset($lr->type_name) && strtolower(trim($lr->type_name)) === 'work from home') {
                        $is_wfh = true;
                    }
                    if ($is_wfh) {
                        continue; // Skip WFH requests
                    }
                    
                    $uid = (int)$lr->user_id;
                    $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                    $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                    if ($sd === '' || $ed === '') { continue; }
                    
                    // Initialize summary if not exists
                    if (!isset($summary[$uid])) {
                        $summary[$uid] = ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
                    }
                    
                    // Calculate working days in leave period (excluding weekends and future dates)
                    $leaveStart = strtotime(max($from, substr($sd, 0, 10)));
                    $leaveEnd = strtotime(min($to, substr($ed, 0, 10), $today));
                    $leaveWorkingDays = 0;
                    $cur = $leaveStart;
                    while ($cur !== false && $cur <= $leaveEnd) {
                        $dayOfWeek = (int)date('w', $cur); // 0=Sunday, 6=Saturday
                        if ($dayOfWeek != 0 && $dayOfWeek != 6) { // Not Sunday or Saturday
                            $leaveWorkingDays++;
                        }
                        $cur = strtotime('+1 day', $cur);
                    }
                    
                    $summary[$uid]['leave'] += $leaveWorkingDays;
                }
            }
            
            $html = $this->generate_attendance_pdf_html($users, $summary, $period, $from, $to, $month, $date);
            
            // Try to use DomPDF if available
            if (class_exists('\\Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                
                $filename = 'attendance_employee_report_' . $period . '_' . date('Y-m-d') . '.pdf';
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $dompdf->output();
                exit;
            } else {
                // Fallback to HTML with print styling
                $filename = 'attendance_employee_report_' . $period . '_' . date('Y-m-d') . '.html';
                header('Content-Type: text/html; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $html;
                exit;
            }
        } catch (Exception $e) {
            log_message('error', 'Export PDF error: ' . $e->getMessage());
            show_error('Error generating PDF export: ' . $e->getMessage(), 500);
        }
    }
    
    private function generate_attendance_pdf_html($users, $summary, $period, $from, $to, $month, $date) {
        $periodLabel = $period === 'daily' ? ($date ?: date('Y-m-d')) : ($period === 'weekly' ? ($from . ' to ' . $to) : ($month ?: date('Y-m')));
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 10px; }
        h1 { color: #2563eb; margin-bottom: 10px; }
        h2 { color: #64748b; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2563eb; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }
        td { padding: 6px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .header-info { margin-bottom: 15px; padding: 10px; background-color: #f1f5f9; border-radius: 4px; }
        .header-info p { margin: 3px 0; }
    </style>
</head>
<body>
    <h1>Employee Attendance Report</h1>
    <div class="header-info">
        <p><strong>Period:</strong> ' . ucfirst($period) . '</p>
        <p><strong>Date Range:</strong> ' . htmlspecialchars($from) . ' to ' . htmlspecialchars($to) . '</p>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>ID</th>
                <th>Present</th>
                <th>Half</th>
                <th>WFH</th>
                <th>Absent</th>
                <th>Leave</th>
                <th>Late Days</th>
                <th>On Time</th>
                <th>Late Hours</th>
                <th>Extra Hours</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($users as $user) {
            $uid = (int)$user->id;
            $name = isset($user->name) ? htmlspecialchars($user->name) : 'Unknown';
            $data = isset($summary[$uid]) ? $summary[$uid] : ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];
            
            $html .= '<tr>
                <td>' . $name . '</td>
                <td>' . $uid . '</td>
                <td>' . number_format($data['present'], 1) . '</td>
                <td>' . number_format($data['half'], 1) . '</td>
                <td>' . number_format($data['wfh'], 1) . '</td>
                <td>' . number_format($data['absent'], 1) . '</td>
                <td>' . number_format($data['leave'], 1) . '</td>
                <td>' . number_format(isset($data['late']) ? (float)$data['late'] : 0.0, 1) . '</td>
                <td>' . number_format(isset($data['on_time']) ? (float)$data['on_time'] : 0.0, 1) . '</td>
                <td>' . number_format(isset($data['late_hours']) ? (float)$data['late_hours'] : 0.0, 2) . '</td>
                <td>' . number_format(isset($data['extra_hours']) ? (float)$data['extra_hours'] : 0.0, 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
</body>
</html>';
        
        return $html;
    }
    
    // Helper method to generate daily details data (reused from attendance_employee method)
    private function generate_daily_details_data($user_id, $from, $to) {
        // Detect columns
        $fields = $this->db->list_fields('attendance');
        $userCandidates = ['user_id','employee_id','emp_id','staff_id','uid'];
        $dateCandidates = ['att_date','date','attendance_date','created_at','checked_at'];
        $statusCandidates = ['status','attendance_status','state'];
        $userCol = $dateCol = $statusCol = null;
        foreach ($userCandidates as $c) { if (in_array($c, $fields, true)) { $userCol = $c; break; } }
        foreach ($dateCandidates as $c) { if (in_array($c, $fields, true)) { $dateCol = $c; break; } }
        foreach ($statusCandidates as $c) { if (in_array($c, $fields, true)) { $statusCol = $c; break; } }
        if ($userCol === null) { $userCol = isset($fields[0]) ? $fields[0] : 'user_id'; }
        if ($dateCol === null) { $dateCol = isset($fields[1]) ? $fields[1] : 'att_date'; }
        if ($statusCol === null) { $statusCol = isset($fields[2]) ? $fields[2] : 'status'; }
        
        // Detect check-in/out columns
        $checkInCol = null;
        $checkOutCol = null;
        if (in_array('punch_in', $fields, true)) { $checkInCol = 'punch_in'; }
        elseif (in_array('check_in', $fields, true)) { $checkInCol = 'check_in'; }
        if (in_array('punch_out', $fields, true)) { $checkOutCol = 'punch_out'; }
        elseif (in_array('check_out', $fields, true)) { $checkOutCol = 'check_out'; }
        
        $selectCols = ["`$dateCol` AS d", "`$statusCol` AS st"];
        if ($checkInCol !== null) { $selectCols[] = "`".$checkInCol."` AS cin"; }
        if ($checkOutCol !== null) { $selectCols[] = "`".$checkOutCol."` AS cout"; }
        if ($this->db->field_exists('checkin_location_name', 'attendance')) {
            $selectCols[] = "`checkin_location_name` AS cin_loc";
        }
        if ($this->db->field_exists('checkout_location_name', 'attendance')) {
            $selectCols[] = "`checkout_location_name` AS cout_loc";
        }
        if ($this->db->field_exists('notes', 'attendance')) {
            $selectCols[] = "`notes` AS notes";
        }
        
        $this->db->select(implode(', ', $selectCols))
            ->from('attendance')
            ->where($userCol, $user_id)
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->order_by($dateCol, 'ASC');
        $rows = $this->db->get()->result();
        
        $attMap = []; $cinMap = []; $coutMap = []; $cinLocMap = []; $coutLocMap = []; $notesMap = [];
        foreach ($rows as $r) {
            $d = isset($r->d) ? (string)$r->d : '';
            if ($d === '') { continue; }
            if (strpos($d, ' ') !== false) { $d = trim(explode(' ', $d)[0]); }
            $attMap[$d] = (string)$r->st;
            if ($checkInCol !== null && isset($r->cin)) { $cinMap[$d] = (string)$r->cin; }
            if ($checkOutCol !== null && isset($r->cout)) { $coutMap[$d] = (string)$r->cout; }
            if (isset($r->cin_loc) && !empty($r->cin_loc)) { $cinLocMap[$d] = (string)$r->cin_loc; }
            if (isset($r->cout_loc) && !empty($r->cout_loc)) { $coutLocMap[$d] = (string)$r->cout_loc; }
            if (isset($r->notes) && !empty(trim($r->notes))) { $notesMap[$d] = trim((string)$r->notes); }
        }
        
        // Get leave map
        $leaveMap = [];
        if ($this->db->table_exists('leave_requests')) {
            $lrows = $this->db->select('start_date, end_date, status')
                ->from('leave_requests')
                ->where('user_id', $user_id)
                ->where_in('status', ['lead_approved','hr_approved'])
                ->where('start_date <=', $to)
                ->where('end_date >=', $from)
                ->get()->result();
            foreach ($lrows as $lr) {
                $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                if ($sd === '' || $ed === '') { continue; }
                $cur = strtotime(max($from, substr($sd, 0, 10)));
                $endTs = strtotime(min($to, substr($ed, 0, 10)));
                $txt = 'Leave ('.(string)$lr->status.')';
                while ($cur !== false && $cur <= $endTs) {
                    $k = date('Y-m-d', $cur);
                    if (!isset($leaveMap[$k])) { $leaveMap[$k] = $txt; }
                    $cur = strtotime('+1 day', $cur);
                }
            }
        }
        
        // Get settings
        $officeStart = '09:30';
        $graceMinutes = 15;
        $standardHours = 8.0;
        if (isset($this->settings)) {
            try {
                $stVal = $this->settings->get_setting('attendance_start_time', $officeStart);
                if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) { $officeStart = $stVal; }
                $gmVal = $this->settings->get_setting('attendance_grace_minutes', $graceMinutes);
                if (is_numeric($gmVal)) { $graceMinutes = (int)$gmVal; }
                $shVal = $this->settings->get_setting('attendance_standard_working_hours');
                if ($shVal === null || $shVal === '') {
                    $shVal = $this->settings->get_setting('standard_working_hours', $standardHours);
                }
                if (is_numeric($shVal)) { $standardHours = (float)$shVal; }
            } catch (Exception $e) { }
        }
        
        // Generate days array
        $days = [];
        $startTs = strtotime($from);
        $endTs = strtotime($to);
        while ($startTs !== false && $startTs <= $endTs) {
            $d = date('Y-m-d', $startTs);
            $dayOfWeek = (int)date('w', $startTs);
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            
            $raw = isset($attMap[$d]) ? $attMap[$d] : '';
            $st = strtolower(trim($raw));
            $leave = isset($leaveMap[$d]) ? $leaveMap[$d] : '—';
            
            if ($raw === '' && $leave === '—' && !$isWeekend) {
                $st = 'absent';
                $raw = 'absent';
            }
            
            $labelSt = '—';
            if ($st === 'present') { $labelSt = 'Present'; }
            elseif ($st === 'half_day') { $labelSt = 'Half Day'; }
            elseif ($st === 'work_from_home') { $labelSt = 'Work From Home'; }
            elseif ($st === 'absent') { $labelSt = 'Absent'; }
            elseif ($st !== '') { $labelSt = $raw; }
            elseif ($isWeekend) { $labelSt = 'Weekend'; }
            
            $lateLabel = '—';
            $lateStatus = '';
            $lateMinutes = 0;
            $checkInTime = '—';
            $checkOutTime = '—';
            $checkInLocation = '—';
            $checkOutLocation = '—';
            
            if ($checkInCol !== null && isset($cinMap[$d]) && $st !== '' && $st !== 'absent') {
                $cinRaw = (string)$cinMap[$d];
                $cinTime = $cinRaw;
                if (strpos($cinRaw, ' ') !== false) {
                    $parts = explode(' ', $cinRaw);
                    $cinTime = isset($parts[1]) ? trim($parts[1]) : trim($cinRaw);
                    $checkInTime = (strlen($cinTime) >= 8) ? substr($cinTime, 0, 8) : (substr($cinTime, 0, 5) . ':00');
                } else {
                    $checkInTime = (strlen($cinTime) >= 8) ? substr($cinTime, 0, 8) : (substr($cinTime, 0, 5) . ':00');
                }
                
                if (preg_match('/^\d{2}:\d{2}/', $cinTime)) {
                    $officeTs = strtotime($d.' '.$officeStart.':00');
                    $graceTs  = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
                    $cinTs    = strtotime($d.' '.$checkInTime);
                    
                    if ($graceTs !== false && $cinTs !== false) {
                        if ($cinTs > $graceTs) {
                            $lateMinutes = (int)round(($cinTs - $graceTs) / 60);
                            $lateStatus = 'late';
                            $lateLabel = 'Late: '.$checkInTime.' ('.$lateMinutes.' min)';
                        } else {
                            $lateStatus = 'on_time';
                            $lateLabel = 'On Time: '.$checkInTime;
                        }
                    }
                }
            }
            
            $workedHours = 0;
            $extraHours = 0;
            $workedSeconds = 0;
            $extraSeconds = 0;
            if ($checkOutCol !== null && isset($coutMap[$d])) {
                $coutRaw = (string)$coutMap[$d];
                $coutTime = $coutRaw;
                if (strpos($coutRaw, ' ') !== false) {
                    $parts = explode(' ', $coutRaw);
                    $coutTime = isset($parts[1]) ? trim($parts[1]) : trim($coutRaw);
                }
                if (preg_match('/^\d{2}:\d{2}/', $coutTime)) {
                    $checkOutTime = (strlen($coutTime) >= 8) ? substr($coutTime, 0, 8) : (substr($coutTime, 0, 5) . ':00');
                    
                    if ($checkInTime !== '—' && preg_match('/^\d{2}:\d{2}/', $checkInTime)) {
                        $cinTs = strtotime($d.' '.$checkInTime);
                        $coutTs = strtotime($d.' '.$checkOutTime);
                        if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                            $workedSeconds = $coutTs - $cinTs;
                            $workedHours = $workedSeconds / 3600;
                            if ($workedHours > $standardHours) {
                                $extraHours = $workedHours - $standardHours;
                                $extraSeconds = $workedSeconds - ($standardHours * 3600);
                            }
                        }
                    }
                }
            }
            
            if (isset($cinLocMap[$d])) { $checkInLocation = $cinLocMap[$d]; }
            if (isset($coutLocMap[$d])) { $checkOutLocation = $coutLocMap[$d]; }
            $notes = isset($notesMap[$d]) ? $notesMap[$d] : '—';
            
            $obj = new stdClass();
            $obj->date = $d;
            $obj->status = $labelSt;
            $obj->leave = $leave;
            $obj->late = $lateLabel;
            $obj->late_status = $lateStatus;
            $obj->check_in_time = $checkInTime;
            $obj->check_out_time = $checkOutTime;
            $obj->check_in_location = $checkInLocation;
            $obj->check_out_location = $checkOutLocation;
            $obj->worked_hours = round($workedHours, 2);
            $obj->extra_hours = round($extraHours, 2);
            $obj->worked_seconds = $workedSeconds;
            $obj->extra_seconds = $extraSeconds;
            $obj->notes = $notes;
            $days[] = $obj;
            $startTs = strtotime('+1 day', $startTs);
        }
        
        return $days;
    }
    
    // Export daily details to Excel
    private function export_attendance_employee_detail_excel($user_id, $period, $from, $to, $month, $date) {
        try {
            // Get user name
            $userName = 'Unknown';
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name AS name'); }
                elseif ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                else { $this->db->select('u.email AS name'); }
                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('COALESCE(e.name, u.full_name, u.name, u.email) AS name'); }
                }
                $user = $this->db->from('users u')->where('u.id', $user_id)->get()->row();
                if ($user) { $userName = isset($user->name) ? $user->name : 'Unknown'; }
            }
            
            // Generate daily details
            $days = $this->generate_daily_details_data($user_id, $from, $to);
            
            // Prepare Excel data (using HTML format to support colors)
            $filename = 'attendance_detail_' . $userName . '_' . $period . '_' . date('Y-m-d') . '.xls';
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Calculate stats
            $stats = ['present'=>0, 'late'=>0, 'on_time'=>0, 'absent'=>0, 'wfh'=>0, 'leave'=>0, 'total_worked_secs'=>0, 'total_extra_secs'=>0];
            foreach ($days as $day) {
                if (strtolower($day->status) === 'present') $stats['present']++;
                if (isset($day->late) && strpos(strtolower($day->late), 'late') === 0) $stats['late']++;
                if (isset($day->late_status) && $day->late_status === 'on_time') $stats['on_time']++;
                if (strtolower($day->status) === 'absent') $stats['absent']++;
                if (strtolower($day->status) === 'work from home') $stats['wfh']++;
                if ($day->leave !== '—' && $day->leave !== '') $stats['leave']++;
                $stats['total_worked_secs'] += isset($day->worked_seconds) ? (int)$day->worked_seconds : 0;
                $stats['total_extra_secs'] += isset($day->extra_seconds) ? (int)$day->extra_seconds : 0;
            }

            $html = '<html><head><meta charset="UTF-8"><style>
                table { border-collapse: collapse; width: 100%; border: 1px solid #ddd; }
                th, td { padding: 5px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f1f5f9; }
            </style></head><body>';
            
            $html .= '<h3>Employee Attendance Detail Report</h3>';
            $html .= '<p><strong>User:</strong> ' . htmlspecialchars($userName) . '<br>';
            $html .= '<strong>Period:</strong> ' . htmlspecialchars($period) . '<br>';
            $html .= '<strong>Present:</strong> ' . $stats['present'] . ' | <strong>Late:</strong> ' . $stats['late'] . ' | <strong>On Time:</strong> ' . $stats['on_time'] . ' | <strong>Absent:</strong> ' . $stats['absent'] . ' | <strong>WFH:</strong> ' . $stats['wfh'] . ' | <strong>Leave:</strong> ' . $stats['leave'] . '<br>';
            $html .= '<strong>Total Worked:</strong> ' . sprintf('%02d:%02d:%02d', floor($stats['total_worked_secs']/3600), floor(($stats['total_worked_secs']%3600)/60), $stats['total_worked_secs']%60) . ' | <strong>Total Extra:</strong> ' . sprintf('%02d:%02d:%02d', floor($stats['total_extra_secs']/3600), floor(($stats['total_extra_secs']%3600)/60), $stats['total_extra_secs']%60) . '</p>';

            $html .= '<table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Check-In Location</th>
                        <th>Check-Out Location</th>
                        <th>Late/On Time</th>
                        <th>Worked Hours</th>
                        <th>Extra Hours</th>
                        <th>Leave</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>';
            
            // Data rows
            foreach ($days as $day) {
                $ws = isset($day->worked_seconds) ? (int)$day->worked_seconds : 0;
                $es = isset($day->extra_seconds) ? (int)$day->extra_seconds : 0;
                
                $isLate = (isset($day->late_status) && $day->late_status === 'late');
                $lateStyle = $isLate ? ' style="color: red; font-weight: bold;"' : '';
                
                $html .= '<tr>
                    <td' . $lateStyle . '>' . htmlspecialchars($day->date) . '</td>
                    <td>' . htmlspecialchars($day->status) . '</td>
                    <td>' . htmlspecialchars($day->check_in_time !== '—' ? $day->check_in_time : '') . '</td>
                    <td>' . htmlspecialchars($day->check_out_time !== '—' ? $day->check_out_time : '') . '</td>
                    <td>' . htmlspecialchars($day->check_in_location !== '—' ? $day->check_in_location : '') . '</td>
                    <td>' . htmlspecialchars($day->check_out_location !== '—' ? $day->check_out_location : '') . '</td>
                    <td' . $lateStyle . '>' . htmlspecialchars($day->late !== '—' ? $day->late : '') . '</td>
                    <td>' . ($ws > 0 ? sprintf('%02d:%02d:%02d', floor($ws/3600), floor(($ws%3600)/60), $ws%60) : '') . '</td>
                    <td>' . ($es > 0 ? sprintf('%02d:%02d:%02d', floor($es/3600), floor(($es%3600)/60), $es%60) : '') . '</td>
                    <td>' . htmlspecialchars($day->leave !== '—' ? $day->leave : '') . '</td>
                    <td>' . htmlspecialchars($day->notes !== '—' ? $day->notes : '') . '</td>
                </tr>';
            }
            $html .= '</tbody></table></body></html>';
            
            echo chr(0xEF).chr(0xBB).chr(0xBF) . $html;
            exit;
        } catch (Exception $e) {
            log_message('error', 'Export Detail Excel error: ' . $e->getMessage());
            show_error('Error generating Excel export: ' . $e->getMessage(), 500);
        }
    }
    
    // Export daily details to PDF
    private function export_attendance_employee_detail_pdf($user_id, $period, $from, $to, $month, $date) {
        try {
            // Get user name
            $userName = 'Unknown';
            if ($this->db->table_exists('users')) {
                $this->db->select('u.id');
                if ($this->db->field_exists('full_name','users')) { $this->db->select('u.full_name AS name'); }
                elseif ($this->db->field_exists('name','users')) { $this->db->select('u.name'); }
                else { $this->db->select('u.email AS name'); }
                if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                    $this->db->join('employees e','e.user_id = u.id','left');
                    if ($this->db->field_exists('name','employees')) { $this->db->select('COALESCE(e.name, u.full_name, u.name, u.email) AS name'); }
                }
                $user = $this->db->from('users u')->where('u.id', $user_id)->get()->row();
                if ($user) { $userName = isset($user->name) ? $user->name : 'Unknown'; }
            }
            
            // Generate daily details
            $days = $this->generate_daily_details_data($user_id, $from, $to);
            
            $periodLabel = $period === 'daily' ? ($date ?: date('Y-m-d')) : ($period === 'weekly' ? ($from . ' to ' . $to) : ($month ?: date('Y-m')));
            
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Detail Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 9px; }
        h1 { color: #2563eb; margin-bottom: 10px; font-size: 16px; }
        h2 { color: #64748b; margin-bottom: 15px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2563eb; color: white; padding: 6px; text-align: left; border: 1px solid #ddd; font-size: 8px; }
        td { padding: 4px; border: 1px solid #ddd; font-size: 8px; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .header-info { margin-bottom: 15px; padding: 10px; background-color: #f1f5f9; border-radius: 4px; }
        .header-info p { margin: 3px 0; }
    </style>
</head>
<body>
    <h1>Employee Attendance Detail Report</h1>
    <div class="header-info">
        <p><strong>Employee:</strong> ' . htmlspecialchars($userName) . ' (ID: ' . $user_id . ')</p>
        <p><strong>Period:</strong> ' . ucfirst($period) . ' - ' . htmlspecialchars($periodLabel) . '</p>
        <p><strong>Date Range:</strong> ' . htmlspecialchars($from) . ' to ' . htmlspecialchars($to) . '</p>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
    </div>';
            // Calculate stats
            $stats = ['present'=>0, 'late'=>0, 'on_time'=>0, 'absent'=>0, 'wfh'=>0, 'leave'=>0, 'total_worked_secs'=>0, 'total_extra_secs'=>0];
            foreach ($days as $day) {
                if (strtolower($day->status) === 'present') $stats['present']++;
                if (isset($day->late) && strpos(strtolower($day->late), 'late') === 0) $stats['late']++;
                if (isset($day->late_status) && $day->late_status === 'on_time') $stats['on_time']++;
                if (strtolower($day->status) === 'absent') $stats['absent']++;
                if (strtolower($day->status) === 'work from home') $stats['wfh']++;
                if ($day->leave !== '—' && $day->leave !== '') $stats['leave']++;
                $stats['total_worked_secs'] += isset($day->worked_seconds) ? (int)$day->worked_seconds : 0;
                $stats['total_extra_secs'] += isset($day->extra_seconds) ? (int)$day->extra_seconds : 0;
            }

            $html .= '
            <div style="margin-bottom:15px; background: #fff; padding: 10px; border: 1px solid #ddd;">
                <h4 style="margin-top:0;">Summary</h4>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none;"><strong>Present:</strong> ' . $stats['present'] . '</td>
                        <td style="border: none;"><strong>On Time:</strong> ' . $stats['on_time'] . '</td>
                        <td style="border: none;"><strong>Late:</strong> ' . $stats['late'] . '</td>
                        <td style="border: none;"><strong>Absent:</strong> ' . $stats['absent'] . '</td>
                        <td style="border: none;"><strong>WFH:</strong> ' . $stats['wfh'] . '</td>
                        <td style="border: none;"><strong>Leave:</strong> ' . $stats['leave'] . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="border: none;"><strong>Total Worked:</strong> ' . sprintf('%02d:%02d:%02d', floor($stats['total_worked_secs']/3600), floor(($stats['total_worked_secs']%3600)/60), $stats['total_worked_secs']%60) . '</td>
                        <td colspan="3" style="border: none;"><strong>Total Extra:</strong> ' . sprintf('%02d:%02d:%02d', floor($stats['total_extra_secs']/3600), floor(($stats['total_extra_secs']%3600)/60), $stats['total_extra_secs']%60) . '</td>
                    </tr>
                </table>
            </div>';

            $html .= '<table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Check-In Location</th>
                <th>Check-Out Location</th>
                <th>Late/On Time</th>
                <th>Worked Hours</th>
                <th>Extra Hours</th>
                <th>Leave</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';
            
            foreach ($days as $day) {
                $ws = isset($day->worked_seconds) ? (int)$day->worked_seconds : 0;
                $es = isset($day->extra_seconds) ? (int)$day->extra_seconds : 0;
                
                $isLate = (isset($day->late_status) && $day->late_status === 'late');
                $lateStyle = $isLate ? ' style="color: red; font-weight: bold;"' : '';
                
                $html .= '<tr>
                    <td' . $lateStyle . '>' . htmlspecialchars($day->date) . '</td>
                    <td>' . htmlspecialchars($day->status) . '</td>
                    <td>' . htmlspecialchars($day->check_in_time !== '—' ? $day->check_in_time : '') . '</td>
                    <td>' . htmlspecialchars($day->check_out_time !== '—' ? $day->check_out_time : '') . '</td>
                    <td>' . htmlspecialchars($day->check_in_location !== '—' ? (strlen($day->check_in_location) > 30 ? substr($day->check_in_location, 0, 30) . '...' : $day->check_in_location) : '') . '</td>
                    <td>' . htmlspecialchars($day->check_out_location !== '—' ? (strlen($day->check_out_location) > 30 ? substr($day->check_out_location, 0, 30) . '...' : $day->check_out_location) : '') . '</td>
                    <td' . $lateStyle . '>' . htmlspecialchars($day->late !== '—' ? $day->late : '') . '</td>
                    <td>' . ($ws > 0 ? sprintf('%02d:%02d:%02d', floor($ws/3600), floor(($ws%3600)/60), $ws%60) : '') . '</td>
                    <td>' . ($es > 0 ? sprintf('%02d:%02d:%02d', floor($es/3600), floor(($es%3600)/60), $es%60) : '') . '</td>
                    <td>' . htmlspecialchars($day->leave !== '—' ? $day->leave : '') . '</td>
                    <td>' . htmlspecialchars($day->notes !== '—' ? (strlen($day->notes) > 50 ? substr($day->notes, 0, 50) . '...' : $day->notes) : '') . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
    </table>
</body>
</html>';
            
            // Try to use DomPDF if available
            if (class_exists('\\Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                
                $filename = 'attendance_detail_' . $userName . '_' . $period . '_' . date('Y-m-d') . '.pdf';
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $dompdf->output();
                exit;
            } else {
                // Fallback to HTML
                $filename = 'attendance_detail_' . $userName . '_' . $period . '_' . date('Y-m-d') . '.html';
                header('Content-Type: text/html; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $html;
                exit;
            }
        } catch (Exception $e) {
            log_message('error', 'Export Detail PDF error: ' . $e->getMessage());
            show_error('Error generating PDF export: ' . $e->getMessage(), 500);
        }
    }

    // ── Payroll Report ────────────────────────────────────────────────────────
    public function payroll() {
        if (function_exists('has_module_access') && !has_module_access('reports') && !has_module_access('reports_payroll')) {
            show_error('Access denied.', 403);
        }

        $month      = $this->input->get('month') ? $this->input->get('month') : date('Y-m');
        $department = $this->input->get('department') ? $this->input->get('department') : '';

        $payslips = [];
        $summary  = ['total_gross' => 0, 'total_deductions' => 0, 'total_net' => 0, 'count' => 0];

        if ($this->db->table_exists('payslips')) {
            $this->db->select('ps.*, u.name as employee_name, u.email as employee_email, e.department');
            $this->db->from('payslips ps');
            $this->db->join('users u', 'u.id = ps.employee_id', 'left');
            $this->db->join('employees e', 'e.user_id = ps.employee_id', 'left');
            $this->db->like('ps.pay_period', $month);
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
            $rows = $this->db->select('DISTINCT department')->where('department IS NOT NULL')->where('department !=', '')->get('employees')->result();
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
        if (function_exists('has_module_access') && !has_module_access('reports') && !has_module_access('reports_expenses')) {
            show_error('Access denied.', 403);
        }

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
            $this->db->join('users u', 'u.id = ex.employee_id', 'left');
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
}
