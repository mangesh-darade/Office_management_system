<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Reports_base.php';

/**
 * Project/task/requirements reports (extracted from Reports.php).
 */
class Reports_projects extends Reports_base {

    public function requirements()
    {
        require_module_access(['reports', 'reports_requirements'], true);
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
            if ($this->schema_has_column('users', 'full_name')) { $userSel[] = 'u.full_name'; }
            if ($this->schema_has_column('users', 'name')) { $userSel[] = 'u.name'; }
            $this->db->select(implode(', ', $userSel));
            $this->db->join('users u','u.id = r.owner_id','left');
        }
        $this->db->from('requirements r');
        apply_role_hierarchy_filter($this->db, 'r.created_by');

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
        if ($this->db->table_exists('tasks') && $this->schema_has_column('tasks', 'requirement_id')) {
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
                function_exists('module_type_label') ? module_type_label($r->requirement_type, 'requirements') : ucfirst(str_replace('_', ' ', $r->requirement_type)),
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

    // GET /reports/tasks-assignment
    public function tasks_assignment()
    {
        require_module_access(['reports', 'reports_tasks_assignment'], true);
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
            apply_role_hierarchy_filter($this->db, 'assigned_to');
            
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
            apply_role_hierarchy_filter($this->db, 'assigned_to');
            
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
                $this->apply_user_employee_name_selects('u', 'e', array('middle_name' => true));
                $this->db->from('users u');
                apply_role_hierarchy_filter($this->db, 'u.id');
                $users = $this->db->get()->result();
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
         require_module_access(['reports', 'reports_daily_activity', 'daily_activity_report'], true);

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
        apply_role_hierarchy_filter($this->db, 'dl.user_id');
        
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
        $seesAllOrgData = function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data();
        apply_role_hierarchy_filter($this->db, 'dl.user_id', $currentUserId, (int)$this->session->userdata('role_id'));

        // Show latest entries first
        $this->db->order_by('dl.created_at', 'DESC');
        $rows = $this->db->get()->result();

         // Users for filter (admin only)
        $users = [];
        if ($seesAllOrgData) {
             $this->db->select('id, name, email')->from('users');
             apply_role_hierarchy_filter($this->db, 'id');
             $users = $this->db->order_by('name')->get()->result();
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
            'is_admin' => $seesAllOrgData,
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
        require_module_access(['reports', 'reports_projects_status'], true);
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
            if ($this->schema_has_column('projects', 'created_by')) {
                apply_role_hierarchy_filter($this->db, 'p.created_by');
            } else if ($this->schema_has_column('projects', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'p.manager_id');
            }
            
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

            $prefetch_manager_ids = array();
            $prefetch_client_ids = array();
            foreach ($projects_map as $project)
            {
                if (!empty($project->manager_id))
                {
                    $prefetch_manager_ids[] = (int) $project->manager_id;
                }
                if (!empty($project->client_id))
                {
                    $prefetch_client_ids[] = (int) $project->client_id;
                }
            }
            $this->prefetch_user_names($prefetch_manager_ids);
            $this->prefetch_client_names($prefetch_client_ids);
            
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
            if ($this->schema_has_column('projects', 'created_by')) {
                apply_role_hierarchy_filter($this->db, 'p.created_by');
            } else if ($this->schema_has_column('projects', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'p.manager_id');
            }
            
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

            $prefetch_manager_ids = array();
            $prefetch_client_ids = array();
            foreach ($projects as $project)
            {
                if (!empty($project->manager_id))
                {
                    $prefetch_manager_ids[] = (int) $project->manager_id;
                }
                if (!empty($project->client_id))
                {
                    $prefetch_client_ids[] = (int) $project->client_id;
                }
            }
            $this->prefetch_user_names($prefetch_manager_ids);
            $this->prefetch_client_names($prefetch_client_ids);
            
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

    // GET /reports/defects
    public function defects()
    {
        require_module_access(['reports', 'reports_defects'], true);
        if (!$this->db->table_exists('project_defects')) {
            show_error('Defects table not found', 500);
            return;
        }

        $this->load->model('Defect_model', 'report_defects');
        $this->load->helper('defects_releases');

        $filters = array(
            'status' => trim((string) $this->input->get('status')),
            'severity' => trim((string) $this->input->get('severity')),
            'project_id' => (int) $this->input->get('project_id'),
            'overdue' => $this->input->get('overdue') === '1',
            'search' => trim((string) $this->input->get('search')),
        );
        if ($filters['search'] !== '') {
            $filters['q'] = $filters['search'];
        }

        $rows = $this->report_defects->list_defects($filters);

        $by_status = array();
        $by_severity = array();
        $by_project = array();
        $open_count = 0;
        $overdue_count = 0;

        foreach ($rows as $r) {
            $st = (string) $r->status;
            $sev = (string) $r->severity;
            $pid = (int) $r->project_id;
            $pname = isset($r->project_name) && $r->project_name !== '' ? (string) $r->project_name : '—';

            if (!isset($by_status[$st])) {
                $by_status[$st] = 0;
            }
            $by_status[$st]++;

            if (!isset($by_severity[$sev])) {
                $by_severity[$sev] = 0;
            }
            $by_severity[$sev]++;

            if (!isset($by_project[$pid])) {
                $by_project[$pid] = (object) array(
                    'project_id' => $pid,
                    'project_name' => $pname,
                    'total' => 0,
                    'open' => 0,
                    'overdue' => 0,
                    'critical' => 0,
                    'high' => 0,
                );
            }
            $by_project[$pid]->total++;
            if (in_array($st, array('open', 'in_progress'), true)) {
                $by_project[$pid]->open++;
                $open_count++;
            }
            if (function_exists('defect_is_overdue') && defect_is_overdue($r)) {
                $by_project[$pid]->overdue++;
                $overdue_count++;
            }
            if ($sev === 'critical') {
                $by_project[$pid]->critical++;
            }
            if ($sev === 'high') {
                $by_project[$pid]->high++;
            }
        }

        $project_summary = array_values($by_project);
        usort($project_summary, function ($a, $b) {
            if ($b->open === $a->open) {
                return $b->total <=> $a->total;
            }
            return $b->open <=> $a->open;
        });

        $filter_options = array(
            'projects' => $this->report_defects->project_options(),
            'statuses' => array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected'),
            'severities' => array('low', 'medium', 'high', 'critical'),
        );

        if ($this->input->get('export') === 'csv') {
            $this->export_defects_csv($rows);
        }

        $this->load->view('reports/defects', array(
            'rows' => $rows,
            'filters' => $filters,
            'filter_options' => $filter_options,
            'summary' => array(
                'total' => count($rows),
                'open' => $open_count,
                'overdue' => $overdue_count,
                'by_status' => $by_status,
                'by_severity' => $by_severity,
            ),
            'project_summary' => $project_summary,
        ));
    }

    private function export_defects_csv($rows)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="defects_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array(
            'Number', 'Title', 'Project', 'Severity', 'Priority', 'Status',
            'Assignee', 'Due Date', 'Overdue', 'Reporter', 'Created'
        ), ',', '"', '\\');

        foreach ($rows as $r) {
            $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r) ? 'Yes' : 'No';
            fputcsv($output, array(
                isset($r->defect_number) ? $r->defect_number : '',
                isset($r->title) ? $r->title : '',
                isset($r->project_name) ? $r->project_name : '',
                isset($r->severity) ? $r->severity : '',
                isset($r->priority) ? $r->priority : '',
                isset($r->status) ? $r->status : '',
                isset($r->assignee_name) ? $r->assignee_name : '',
                isset($r->due_date) ? $r->due_date : '',
                $overdue,
                isset($r->reporter_name) ? $r->reporter_name : '',
                isset($r->created_at) ? $r->created_at : '',
            ), ',', '"', '\\');
        }

        fclose($output);
        exit;
    }
}
