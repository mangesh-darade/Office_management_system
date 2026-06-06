<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Reports_base.php';

/**
 * Attendance report actions (extracted from Reports.php for maintainability).
 */
class Reports_attendance extends Reports_base {

    /**
     * Per-employee attendance report (daily / weekly / monthly).
     */
    public function attendance_employee($user_id = null)
    {
        require_module_access(['reports', 'reports_attendance_employee'], true);

        $period = attendance_report_normalize_period((string) $this->input->get('period'));
        $month = (string) $this->input->get('month');
        $date = (string) $this->input->get('date');

        $range = attendance_report_date_range_view($period, $month, $date);
        $period = $range['period'];
        $month = $range['month'];
        $date = $range['date'];
        $from = $range['from'];
        $to = $range['to'];
        $today = $range['today'];

        $holidayData = attendance_report_holidays_for_range($this->db, $from, $to);
        $holidays = $holidayData['holidays'];
        $holidayDates = $holidayData['holiday_dates'];
        $totalWorkingDays = attendance_report_count_working_days($from, $to, $holidayDates, $today);

        if (!$this->db->table_exists('attendance')) {
            show_error('Attendance table not found', 500);
            return;
        }

        $cols = attendance_report_resolve_columns($this->db);
        $fields = $cols['fields'];
        $userCol = $cols['user_col'];
        $dateCol = $cols['date_col'];
        $statusCol = $cols['status_col'];

        $labels = array();
        if ($this->db->table_exists('users')) {
            $this->db->select('u.id, u.email');
            $this->apply_user_employee_name_selects('u', 'e', array('middle_name' => true));
            $this->db->from('users u');
            apply_role_hierarchy_filter($this->db, 'u.id');
            $users = $this->db->get()->result();
            foreach ($users as $u) {
                $labels[(int) $u->id] = $u;
            }
        }

        $getName = function ($uid) use ($labels) {
            return attendance_report_user_display_name(isset($labels[$uid]) ? $labels[$uid] : null, $uid);
        };

        $user_id = $user_id ? (int) $user_id : 0;

        if ($user_id > 0) {
            $this->_attendance_employee_detail(
                $user_id, $period, $month, $date, $from, $to, $fields,
                $userCol, $dateCol, $statusCol, $getName, $holidays, $totalWorkingDays
            );
            return;
        }

        $this->_attendance_employee_summary(
            $period, $month, $date, $from, $to, $today, $fields,
            $userCol, $dateCol, $statusCol, $labels, $getName, $holidays,
            $holidayDates, $totalWorkingDays
        );
    }

    /**
     * Single-employee daily attendance detail view.
     */
    private function _attendance_employee_detail(
        $user_id, $period, $month, $date, $from, $to, array $fields,
        $userCol, $dateCol, $statusCol, callable $getName, $holidays, $totalWorkingDays
    ) {
        require_hierarchy_user_access($user_id, true);
        $fields = $this->db->list_fields('attendance');
        $hasPunchIn = in_array('punch_in', $fields, true);
        $hasCheckIn = in_array('check_in', $fields, true);
        $hasPunchOut = in_array('punch_out', $fields, true);
        $hasCheckOut = in_array('check_out', $fields, true);

        $selectCols = array("`$dateCol` AS d", "`$statusCol` AS st");
        if ($hasPunchIn) { $selectCols[] = '`punch_in` AS pin'; }
        if ($hasCheckIn) { $selectCols[] = '`check_in` AS cin'; }
        if ($hasPunchOut) { $selectCols[] = '`punch_out` AS pout'; }
        if ($hasCheckOut) { $selectCols[] = '`check_out` AS cout'; }
        if ($this->attendance_table_has_column('checkin_location_name')) {
            $selectCols[] = '`checkin_location_name` AS cin_loc';
        }
        if ($this->attendance_table_has_column('checkout_location_name')) {
            $selectCols[] = '`checkout_location_name` AS cout_loc';
        }
        if ($this->attendance_table_has_column('notes')) {
            $selectCols[] = '`notes` AS notes';
        }

        $this->db->select(implode(', ', $selectCols))
            ->from('attendance')
            ->where($userCol, $user_id)
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->order_by($dateCol, 'ASC');
        $rows = $this->db->get()->result();

        $attMap = array();
        $cinMap = array();
        $coutMap = array();
        $cinLocMap = array();
        $coutLocMap = array();
        $notesMap = array();
        foreach ($rows as $r) {
            $d = isset($r->d) ? (string) $r->d : '';
            if ($d === '') { continue; }
            if (strpos($d, ' ') !== false) { $d = trim(explode(' ', $d)[0]); }
            $attMap[$d] = (string) $r->st;
            $pin = isset($r->pin) ? trim((string) $r->pin) : '';
            $cin = isset($r->cin) ? trim((string) $r->cin) : '';
            $pout = isset($r->pout) ? trim((string) $r->pout) : '';
            $cout = isset($r->cout) ? trim((string) $r->cout) : '';
            $emptyTimes = array('', '00:00:00', '0000-00-00 00:00:00');
            if (in_array($pin, $emptyTimes, true)) { $pin = ''; }
            if (in_array($cin, $emptyTimes, true)) { $cin = ''; }
            if (in_array($pout, $emptyTimes, true)) { $pout = ''; }
            if (in_array($cout, $emptyTimes, true)) { $cout = ''; }
            $effectiveIn = ($pin !== '') ? $pin : $cin;
            $effectiveOut = ($pout !== '') ? $pout : $cout;
            if ($effectiveIn !== '') { $cinMap[$d] = $effectiveIn; }
            if ($effectiveOut !== '') { $coutMap[$d] = $effectiveOut; }
            if (isset($r->cin_loc) && !empty($r->cin_loc)) { $cinLocMap[$d] = (string) $r->cin_loc; }
            if (isset($r->cout_loc) && !empty($r->cout_loc)) { $coutLocMap[$d] = (string) $r->cout_loc; }
            if (isset($r->notes) && !empty(trim($r->notes))) { $notesMap[$d] = trim((string) $r->notes); }
        }

        $leaveMap = array();
        if ($this->db->table_exists('leave_requests')) {
            $lrows = $this->db->select('lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                ->from('leave_requests lr')
                ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                ->where('lr.user_id', $user_id)
                ->where_in('lr.status', array('lead_approved', 'hr_approved'))
                ->where('lr.start_date <=', $to)
                ->where('lr.end_date >=', $from)
                ->get()->result();
            foreach ($lrows as $lr) {
                if (attendance_report_is_wfh_leave_row($lr)) {
                    continue;
                }
                $sd = isset($lr->start_date) ? (string) $lr->start_date : '';
                $ed = isset($lr->end_date) ? (string) $lr->end_date : '';
                if ($sd === '' || $ed === '') { continue; }
                $cur = strtotime(max($from, substr($sd, 0, 10)));
                $endTs = strtotime(min($to, substr($ed, 0, 10)));
                $txt = 'Leave (' . (string) $lr->status . ')';
                while ($cur !== false && $cur <= $endTs) {
                    $k = date('Y-m-d', $cur);
                    if (!isset($leaveMap[$k])) { $leaveMap[$k] = $txt; }
                    $cur = strtotime('+1 day', $cur);
                }
            }
        }

        $timing = attendance_report_get_timing_settings(isset($this->settings) ? $this->settings : null);
        $officeStart = $timing['office_start'];
        $officeEnd = $timing['office_end'];
        $graceMinutes = $timing['grace_minutes'];
        $standardHours = $timing['standard_hours'];

        $this->load->model('Employee_model');
        $this->load->model('Shift_model');
        $employee = $this->Employee_model->get_by_user_id($user_id);
        if ($employee && isset($employee->shift_id)) {
            $shift = $this->Shift_model->get($employee->shift_id);
            if ($shift) {
                $officeStart = date('H:i', strtotime($shift->start_time));
                $officeEnd = date('H:i', strtotime($shift->end_time));
                $graceMinutes = (int) $shift->late_grace_period;
                $start_ts = strtotime($shift->start_time);
                $end_ts = strtotime($shift->end_time);
                $diff = $end_ts - $start_ts;
                if ($diff > 0) {
                    $standardHours = round($diff / 3600, 1);
                }
                $timing['office_start'] = $officeStart;
                $timing['office_end'] = $officeEnd;
                $timing['grace_minutes'] = $graceMinutes;
                $timing['standard_hours'] = $standardHours;
            }
        }

        $holidayMap = array();
        foreach ($holidays as $h) {
            $holidayMap[$h->holiday_date] = $h->name;
        }

        $days = array();
        $startTs = strtotime($from);
        $endTs = strtotime($to);
        while ($startTs !== false && $startTs <= $endTs) {
            $d = date('Y-m-d', $startTs);
            $dayOfWeek = (int) date('w', $startTs);
            $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);

            $raw = isset($attMap[$d]) ? $attMap[$d] : '';
            $st = strtolower(trim($raw));
            $leave = isset($leaveMap[$d]) ? $leaveMap[$d] : '—';
            $holidayName = isset($holidayMap[$d]) ? $holidayMap[$d] : null;
            $hasInTime = isset($cinMap[$d]) && attendance_report_is_valid_punch_time($cinMap[$d]);
            $hasOutTime = isset($coutMap[$d]) && attendance_report_is_valid_punch_time($coutMap[$d]);

            if ($raw === '' && $leave === '—' && !$isWeekend) {
                if ($holidayName) {
                    $st = 'holiday';
                    $raw = 'holiday';
                } else {
                    $st = 'absent';
                    $raw = 'absent';
                }
            }
            if (($hasInTime || $hasOutTime) && $st === 'absent') {
                $st = 'present';
                $raw = 'present';
            }

            if ($isWeekend && $st === '') {
                $labelSt = 'Weekend';
            } elseif ($st === 'holiday' || ($holidayName && $st === 'holiday')) {
                $labelSt = attendance_status_display_label('holiday', $holidayName);
            } else {
                $labelSt = attendance_status_display_label(
                    $st,
                    ($st === 'present' && $holidayName) ? $holidayName : null
                );
            }

            $cinRaw = isset($cinMap[$d]) ? trim((string) $cinMap[$d]) : '';
            $coutRaw = isset($coutMap[$d]) ? trim((string) $coutMap[$d]) : '';
            $checkInTime = attendance_report_format_clock_display($cinRaw);
            $checkOutTime = attendance_report_format_clock_display($coutRaw);
            $lateDisplay = attendance_report_resolve_late_display($d, $cinRaw, $coutRaw, null, $timing);
            $timingMetrics = $lateDisplay['metrics'];

            $workedHours = 0;
            $extraHours = (float) $timingMetrics['extra_hours'];
            $workedSeconds = 0;
            $extraSeconds = 0;
            if ($checkInTime !== '—' && $checkOutTime !== '—') {
                $cinTs = strtotime($d . ' ' . $checkInTime);
                $coutTs = strtotime($d . ' ' . $checkOutTime);
                if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                    $workedSeconds = $coutTs - $cinTs;
                    $workedHours = $workedSeconds / 3600;
                    if ($extraHours > 0) {
                        $extraSeconds = (int) round($extraHours * 3600);
                    }
                }
            }

            $obj = new stdClass();
            $obj->date = $d;
            $obj->status = $labelSt;
            $obj->leave = $leave;
            $obj->late = $lateDisplay['late_label'];
            $obj->late_status = $lateDisplay['late_status'];
            $obj->late_minutes = $lateDisplay['late_minutes'];
            $obj->grace_time = $lateDisplay['grace_time'];
            $obj->check_in_time = $checkInTime;
            $obj->check_out_time = $checkOutTime;
            $obj->check_in_location = isset($cinLocMap[$d]) ? $cinLocMap[$d] : '—';
            $obj->check_out_location = isset($coutLocMap[$d]) ? $coutLocMap[$d] : '—';
            $obj->worked_hours = round($workedHours, 2);
            $obj->extra_hours = round($extraHours, 2);
            $obj->late_hours = ($lateDisplay['late_minutes'] > 0) ? round($lateDisplay['late_minutes'] / 60, 2) : 0.0;
            $obj->worked_seconds = $workedSeconds;
            $obj->extra_seconds = $extraSeconds;
            $obj->notes = isset($notesMap[$d]) ? $notesMap[$d] : '—';
            $days[] = $obj;
            $startTs = strtotime('+1 day', $startTs);
        }

        $this->load->view('reports/attendance_employee_detail', array(
            'name' => $getName($user_id),
            'period' => $period,
            'month' => $month,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'office_start_time' => $officeStart,
            'office_end_time' => $officeEnd,
            'grace_minutes' => $graceMinutes,
            'standard_working_hours' => $standardHours,
            'days' => $days,
            'holidays' => $holidays,
        ));
    }

    /**
     * Multi-employee attendance summary for a period.
     */
    private function _attendance_employee_summary(
        $period, $month, $date, $from, $to, $today, array $fields,
        $userCol, $dateCol, $statusCol, array $labels, callable $getName, $holidays,
        array $holidayDates, $totalWorkingDays
    ) {
        // Get all users/employees first
        $allUsers = [];
        if ($this->db->table_exists('users')) {
            $this->db->select('u.id, u.email');
            $this->apply_user_employee_name_selects('u', 'e', array('middle_name' => true, 'active_only' => true));
            $this->db->from('users u');
            apply_role_hierarchy_filter($this->db, 'u.id');
            $users = $this->db->get()->result();
            foreach ($users as $u) { 
                $allUsers[(int)$u->id] = $u; 
            }
        }

        $cols = array(
            'fields'     => $fields,
            'user_col'   => $userCol,
            'date_col'   => $dateCol,
            'status_col' => $statusCol,
        );
        $settingsModel = isset($this->settings) ? $this->settings : null;
        $built = attendance_report_build_employee_summaries(
            $this->db,
            array_keys($allUsers),
            $from,
            $to,
            $today,
            $holidayDates,
            $cols,
            $settingsModel
        );
        $summary = $built['summaries'];
        $timing = $built['timing'];
        $officeStart = $timing['office_start'];
        $graceMinutes = $timing['grace_minutes'];
        $standardHours = $timing['standard_hours'];

        $rowsOut = attendance_report_summary_output_rows($allUsers, $summary, $getName, $totalWorkingDays);

        $attendanceNotes = attendance_report_fetch_notes_map($this->db, $userCol, $dateCol, $from, $to);
        
        // Get settings for display
        $officeStartDisplay = $officeStart;
        $officeEndDisplay = $timing['office_end'];
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
            'office_end_time' => $officeEndDisplay,
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
        require_module_access(['reports', 'reports_attendance'], true);
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
                $this->apply_user_employee_name_selects('u', 'e', array('middle_name' => true, 'department' => true));
                $this->db->from('users u');
                apply_role_hierarchy_filter($this->db, 'u.id');
                $users = $this->db->get()->result();
                foreach ($users as $u){ $labels[(int)$u->id] = $u; }
            }

            $getName = function ($uid) use ($labels) {
                return attendance_report_user_display_name(isset($labels[$uid]) ? $labels[$uid] : null, $uid);
            };

            // Build base WHERE conditions
            $whereConditions = "`$dateCol` >= '$startDate' AND `$dateCol` <= '$endDate'";
            $whereConditions .= hierarchy_sql_user_filter($userCol);
            if ($departmentId && $departmentId !== 'all' && function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
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
                    LIMIT 5000";
            $daily = $this->db->query($sql)->result();
            foreach ($daily as &$d){ $d->name = $getName((int)$d->uid); }

            // Aggregate for weekly
            $sql = "SELECT `$userCol` AS uid, YEARWEEK(`$dateCol`) AS bucket, `$statusCol` AS status, COUNT(*) AS cnt 
                    FROM attendance 
                    WHERE $whereConditions
                    GROUP BY `$userCol`, YEARWEEK(`$dateCol`), `$statusCol` 
                    ORDER BY bucket DESC, uid ASC 
                    LIMIT 5000";
            $weekly = $this->db->query($sql)->result();
            foreach ($weekly as &$w){ $w->name = $getName((int)$w->uid); }

            // Aggregate for monthly
            $sql = "SELECT `$userCol` AS uid, DATE_FORMAT(`$dateCol`, '%Y-%m') AS bucket, `$statusCol` AS status, COUNT(*) AS cnt 
                    FROM attendance 
                    WHERE $whereConditions
                    GROUP BY `$userCol`, DATE_FORMAT(`$dateCol`, '%Y-%m'), `$statusCol` 
                    ORDER BY bucket DESC, uid ASC 
                    LIMIT 5000";
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
                            LIMIT 5000";
                    $dailyLate = $this->db->query($sql, [$cutoffTime])->result();
                    foreach ($dailyLate as &$r) { $r->name = $getName((int)$r->uid); }

                    // Weekly late summary
                    $sql = "SELECT `$userCol` AS uid, YEARWEEK(`$dateCol`) AS bucket, COUNT(*) AS late_cnt
                            FROM attendance
                            WHERE $whereConditions AND `$checkInColLate` IS NOT NULL AND TIME(`$checkInColLate`) > ?
                            GROUP BY `$userCol`, YEARWEEK(`$dateCol`)
                            ORDER BY bucket DESC, uid ASC
                            LIMIT 5000";
                    $weeklyLate = $this->db->query($sql, [$cutoffTime])->result();
                    foreach ($weeklyLate as &$r) { $r->name = $getName((int)$r->uid); }

                    // Monthly late summary
                    $sql = "SELECT `$userCol` AS uid, DATE_FORMAT(`$dateCol`, '%Y-%m') AS bucket, COUNT(*) AS late_cnt
                            FROM attendance
                            WHERE $whereConditions AND `$checkInColLate` IS NOT NULL AND TIME(`$checkInColLate`) > ?
                            GROUP BY `$userCol`, DATE_FORMAT(`$dateCol`, '%Y-%m')
                            ORDER BY bucket DESC, uid ASC
                            LIMIT 5000";
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


    
    // Export attendance employee report
    public function export_attendance_employee() {
        require_module_access(['reports', 'reports_attendance_employee'], true);
        
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

            foreach ($userIds as $exportUid) {
                if (!hierarchy_user_can_access($exportUid)) {
                    $this->output
                        ->set_status_header(403)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['error' => 'You do not have permission to export data for one or more selected employees.']));
                    return;
                }
            }
            
            $exportRange = attendance_report_date_range_export($period, $month, $date);
            $from = $exportRange['from'];
            $to = $exportRange['to'];
            
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

    private function _fetch_export_users(array $userIds)
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }
        $nameFlags = $this->user_employee_name_flags();
        $nameExpr = $this->user_display_name_sql_expr('u', 'e', $nameFlags);
        $this->db->select("u.id, ($nameExpr) AS name", false);
        $this->db->where_in('u.id', $userIds);
        if ($nameFlags['hasEmpTable']) {
            $this->db->join('employees e', 'e.user_id = u.id', 'left');
        }
        $this->db->from('users u');
        apply_role_hierarchy_filter($this->db, 'u.id');
        return $this->db->get()->result();
    }

    private function _fetch_export_user_name($user_id)
    {
        $users = $this->_fetch_export_users(array((int) $user_id));
        if (empty($users)) {
            return 'Unknown';
        }
        $user = $users[0];
        return isset($user->name) ? $user->name : 'Unknown';
    }

    private function build_attendance_export_summaries(array $userIds, $from, $to)
    {
        return attendance_report_build_export_summaries(
            $this->db,
            $userIds,
            $from,
            $to,
            isset($this->settings) ? $this->settings : null
        );
    }

    private function export_attendance_data($period, $data, $format)
    {
        attendance_report_export_period($period, $data, $format);
    }

    private function export_attendance_employee_excel($userIds, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_summary_csv(
            $this->db, $userIds, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function (array $ids) use ($controller) {
                return $controller->_fetch_export_users($ids);
            }
        );
    }

    private function export_attendance_employee_pdf($userIds, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_summary_pdf(
            $this->db, $userIds, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function (array $ids) use ($controller) {
                return $controller->_fetch_export_users($ids);
            }
        );
    }

    private function export_attendance_employee_detail_excel($user_id, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_detail_excel(
            $this->db, $user_id, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function ($uid) use ($controller) {
                return $controller->_fetch_export_user_name($uid);
            }
        );
    }

    private function export_attendance_employee_detail_pdf($user_id, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_detail_pdf(
            $this->db, $user_id, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function ($uid) use ($controller) {
                return $controller->_fetch_export_user_name($uid);
            }
        );
    }

}
