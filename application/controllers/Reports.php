<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->model('Report_model');
    }

    public function index() {
        // Basic aggregates for charts with safe guards if tables are missing
        $task_status = [];
        $projects_progress = [];
        $leaves_monthly = [];
        $task_by_assignee = [];
        $attendance_recent = [];

        if ($this->db->table_exists('tasks')) {
            $task_status = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('tasks')->result();
        }
        if ($this->db->table_exists('projects')) {
            $projects_progress = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('projects')->result();
        }
        if ($this->db->table_exists('leave_requests')) {
            $leaves_monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, SUM(days) AS total_days FROM leave_requests WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
        } elseif ($this->db->table_exists('leaves')) {
            $leaves_monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, COUNT(*) AS total_days FROM leaves WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
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
        if ($this->db->table_exists('attendance')) {
            // Detect date column
            $fields = $this->db->list_fields('attendance');
            $dateCandidates = array('date','attendance_date','att_date','created_at','checked_at');
            $dateCol = null; foreach ($dateCandidates as $c){ if (in_array($c, $fields, true)) { $dateCol = $c; break; } }
            if ($dateCol === null) { $dateCol = $fields[0]; }
            $sql = "SELECT DATE(`$dateCol`) AS d, COUNT(*) cnt FROM attendance WHERE `$dateCol` >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(`$dateCol`) ORDER BY d";
            $attendance_recent = $this->db->query($sql)->result();
        }

        $data = [
            'task_status' => $task_status,
            'projects_progress' => $projects_progress,
            'leaves_monthly' => $leaves_monthly,
            'task_by_assignee' => $task_by_assignee,
            'attendance_recent' => $attendance_recent,
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
        // Base requirements with project and owner
        $this->db->select('r.id, r.title, r.owner_id');
        if ($this->db->table_exists('projects')) {
            $this->db->select('p.name AS project_name');
            $this->db->join('projects p','p.id = r.project_id','left');
        }
        if ($this->db->table_exists('users')) {
            $userSel = ['u.email'];
            if ($this->db->field_exists('full_name','users')) { $userSel[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')) { $userSel[] = 'u.name'; }
            $this->db->select(implode(', ', $userSel));
            $this->db->join('users u','u.id = r.owner_id','left');
        }
        $this->db->from('requirements r');
        $this->db->order_by('r.id','DESC');
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
            $result[] = (object)[
                'id' => (int)$r->id,
                'title' => (string)$r->title,
                'project_name' => isset($r->project_name)?$r->project_name:'',
                'owner' => $owner,
                'counts' => $counts,
                'total' => $total,
            ];
        }

        $this->load->view('reports/requirements', ['rows' => $result]);
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
        $rows = [];
        if ($this->db->table_exists('tasks')) {
            // Aggregate tasks per assignee and status
            $this->db->select('assigned_to, status, COUNT(*) as cnt')->from('tasks')->group_by(['assigned_to','status']);
            $agg = $this->db->get()->result();
            $map = [];
            foreach ($agg as $a){
                $uid = (int)$a->assigned_to; $st = (string)$a->status; $cnt = (int)$a->cnt;
                if (!isset($map[$uid])) { $map[$uid] = ['pending'=>0,'in_progress'=>0,'completed'=>0,'blocked'=>0]; }
                if (isset($map[$uid][$st])) { $map[$uid][$st] = $cnt; }
            }
            // Aggregate titles per assignee (concatenated)
            $titles_map = [];
            $qc = $this->db->select("assigned_to, GROUP_CONCAT(title ORDER BY id DESC SEPARATOR '; ') AS titles")
                           ->from('tasks')
                           ->group_by('assigned_to')
                           ->get()->result();
            foreach ($qc as $qrow){ $titles_map[(int)$qrow->assigned_to] = (string)$qrow->titles; }
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
                $titles = isset($titles_map[$uid]) ? $titles_map[$uid] : '';
                $rows[] = (object)['user_id'=>$uid,'name'=>$name,'titles'=>$titles,'counts'=>$counts,'total'=>$total];
            }
        }
        // Sort by total desc
        usort($rows, function($a,$b){
            if ($b->total == $a->total) return 0;
            return ($b->total < $a->total) ? -1 : 1;
        });
        $this->load->view('reports/tasks_assignment', ['rows'=>$rows]);
    }

    // GET /reports/projects-status
    public function projects_status()
    {
        $rows = [];
        // Prefer task-based aggregation per project and status if schema supports it
        if ($this->db->table_exists('tasks') && $this->db->table_exists('projects')) {
            $this->db->select('p.name AS project_name, t.status, COUNT(*) AS cnt')
                     ->from('tasks t')
                     ->join('projects p','p.id = t.project_id','left')
                     ->group_by(['p.name','t.status'])
                     ->order_by('p.name','ASC');
            $rows = $this->db->get()->result();
        } else if ($this->db->table_exists('projects')) {
            // Fallback: projects grouped by their own status
            $rows = $this->db->select('name AS project_name, status, COUNT(*) AS cnt')->from('projects')->group_by(['name','status'])->order_by('name','ASC')->get()->result();
        }
        $this->load->view('reports/projects_status', ['rows'=>$rows]);
    }

    // GET /reports/leaves
    public function leaves()
    {
        $monthly = [];
        $by_status = [];
        if ($this->db->table_exists('leave_requests')) {
            $monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') ym, SUM(days) AS total_days FROM leave_requests WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
            $by_status = $this->db->select('status, COUNT(*) AS cnt, SUM(days) AS total_days')->from('leave_requests')->group_by('status')->get()->result();
        } elseif ($this->db->table_exists('leaves')) {
            $monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') ym, COUNT(*) AS total_days FROM leaves WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
            $by_status = $this->db->select('status, COUNT(*) AS cnt')->from('leaves')->group_by('status')->get()->result();
        }
        $this->load->view('reports/leaves', ['monthly'=>$monthly,'by_status'=>$by_status]);
    }

// GET /reports/attendance?period=daily|weekly|monthly
    public function attendance()
    {
        $period = $this->input->get('period') ?: 'daily';
        $daily = $weekly = $monthly = [];
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

            // Build label map from users/employees
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

            // Aggregate for daily
            $sql = "SELECT `$userCol` AS uid, DATE(`$dateCol`) AS bucket, `$statusCol` AS status, COUNT(*) AS cnt FROM attendance GROUP BY `$userCol`, DATE(`$dateCol`), `$statusCol` ORDER BY bucket DESC, uid ASC LIMIT 100";
            $daily = $this->db->query($sql)->result();
            foreach ($daily as &$d){ $d->name = $getName((int)$d->uid); }

            // Aggregate for weekly
            $sql = "SELECT `$userCol` AS uid, YEARWEEK(`$dateCol`) AS bucket, `$statusCol` AS status, COUNT(*) AS cnt FROM attendance GROUP BY `$userCol`, YEARWEEK(`$dateCol`), `$statusCol` ORDER BY bucket DESC, uid ASC LIMIT 100";
            $weekly = $this->db->query($sql)->result();
            foreach ($weekly as &$w){ $w->name = $getName((int)$w->uid); }

            // Aggregate for monthly
            $sql = "SELECT `$userCol` AS uid, DATE_FORMAT(`$dateCol`, '%Y-%m') AS bucket, `$statusCol` AS status, COUNT(*) AS cnt FROM attendance GROUP BY `$userCol`, DATE_FORMAT(`$dateCol`, '%Y-%m'), `$statusCol` ORDER BY bucket DESC, uid ASC LIMIT 100";
            $monthly = $this->db->query($sql)->result();
            foreach ($monthly as &$m){ $m->name = $getName((int)$m->uid); }
        }
        $this->load->view('reports/attendance', ['period'=>$period, 'daily'=>$daily, 'weekly'=>$weekly, 'monthly'=>$monthly]);
    }
}
