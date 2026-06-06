<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Reports_base.php';

class Reports extends Reports_base {

    public function index() {
        require_module_access(['reports', 'reports_overview', 'analytics'], true);
        // Basic aggregates for charts with safe guards if tables are missing
        $task_status = [];
        $projects_progress = [];
        $leaves_monthly = [];
        $leaves_by_status = [];
        $task_by_assignee = [];
        $attendance_recent = [];
        $attendance_late_top = [];

        if ($this->db->table_exists('tasks')) {
            $this->db->select('status, COUNT(*) as cnt')->from('tasks');
            apply_role_hierarchy_filter($this->db, 'created_by');
            $task_status = $this->db->group_by('status')->get()->result();
        }
        if ($this->db->table_exists('projects')) {
            $this->db->select('status, COUNT(*) as cnt')->from('projects');
            if ($this->schema_has_column('projects', 'created_by')) {
                apply_role_hierarchy_filter($this->db, 'created_by');
            } else if ($this->schema_has_column('projects', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'manager_id');
            }
            $projects_progress = $this->db->group_by('status')->get()->result();
        }
        if ($this->db->table_exists('leave_requests')) {
            $this->db->select("DATE_FORMAT(start_date, '%Y-%m') as ym, SUM(days) AS total_days", false)
                ->from('leave_requests')
                ->where('start_date >=', date('Y-m-d', strtotime('-6 months')));
            apply_role_hierarchy_filter($this->db, 'user_id');
            $leaves_monthly = $this->db->group_by('ym')->order_by('ym')->get()->result();

            $this->db->select('status, COUNT(*) AS cnt, SUM(days) AS total_days')
                ->from('leave_requests');
            apply_role_hierarchy_filter($this->db, 'user_id');
            $leaves_by_status = $this->db->group_by('status')->get()->result();
        } elseif ($this->db->table_exists('leaves')) {
            $leaves_user_filter = hierarchy_sql_user_filter('user_id');
            $leaves_monthly = $this->db->query(
                "SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, COUNT(*) AS total_days FROM leaves WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)" . $leaves_user_filter . " GROUP BY ym ORDER BY ym"
            )->result();
            $this->db->select('status, COUNT(*) AS cnt')->from('leaves');
            apply_role_hierarchy_filter($this->db, 'user_id');
            $leaves_by_status = $this->db->group_by('status')->get()->result();
        }
        if ($this->db->table_exists('tasks')) {
            // Top 10 assignees by number of tasks
            $this->db->select('t.assigned_to, COUNT(*) AS cnt')->from('tasks t')->group_by('t.assigned_to')->order_by('cnt','DESC')->limit(10);
            apply_role_hierarchy_filter($this->db, 't.created_by');
            if ($this->db->table_exists('users')) {
                $this->db->select('u.email');
                if ($this->schema_has_column('users', 'full_name')) { $this->db->select('u.full_name'); }
                if ($this->schema_has_column('users', 'name')) { $this->db->select('u.name'); }
                $this->db->join('users u','u.id = t.assigned_to','left');
            }
            if ($this->db->table_exists('employees') && $this->schema_has_column('employees', 'user_id')) {
                if ($this->schema_has_column('employees', 'name')) { $this->db->select('e.name AS emp_name'); }
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

        // Scope analytics: admin = org-wide, others = logged-in user only
        $currentUserId = (int)$this->session->userdata('user_id');
        $seesAllOrgData = function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data();

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
                if ($userCol !== null && $currentUserId && !$seesAllOrgData) {
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

                        if ($currentUserId && !$seesAllOrgData) {
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
                            $this->apply_user_employee_name_selects('u', 'e');
                            $this->db->from('users u');
                            apply_role_hierarchy_filter($this->db, 'u.id');
                            $users = $this->db->get()->result();
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

    // GET /reports/export
    public function export_csv()
    {
        require_module_access(['reports', 'reports_tasks_assignment'], true);
        $this->load->dbutil();
        $this->load->helper('hierarchy_filter');
        $userFilter = hierarchy_sql_user_filter('t.assigned_to');
        $sql = "SELECT t.id, t.title, t.status, p.name AS project, u.email AS assigned_user, t.created_at
                FROM tasks t
                LEFT JOIN projects p ON p.id = t.project_id
                LEFT JOIN users u ON u.id = t.assigned_to
                WHERE 1=1" . $userFilter . "
                ORDER BY t.id DESC";
        $query = $this->db->query($sql);
        $out = $this->dbutil->csv_from_result($query);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tasks_'.date('Y-m-d').'.csv"');
        echo $out; exit;
    }
}
