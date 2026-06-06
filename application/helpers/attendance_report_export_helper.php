<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance report export helpers (CSV/PDF/HTML).
 */

if (!function_exists('attendance_report_generate_daily_details')) {
    function attendance_report_generate_daily_details($db, $user_id, $from, $to, $settings = null)
    {
        // Detect columns
        $fields = $db->list_fields('attendance');
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
        
        // Detect all possible check-in/out columns and use row-level fallback.
        $hasPunchIn = in_array('punch_in', $fields, true);
        $hasCheckIn = in_array('check_in', $fields, true);
        $hasPunchOut = in_array('punch_out', $fields, true);
        $hasCheckOut = in_array('check_out', $fields, true);
        
        $selectCols = ["`$dateCol` AS d", "`$statusCol` AS st"];
        if ($hasPunchIn) { $selectCols[] = "`punch_in` AS pin"; }
        if ($hasCheckIn) { $selectCols[] = "`check_in` AS cin"; }
        if ($hasPunchOut) { $selectCols[] = "`punch_out` AS pout"; }
        if ($hasCheckOut) { $selectCols[] = "`check_out` AS cout"; }
        if (schema_table_has_column($db, 'attendance', 'checkin_location_name')) {
            $selectCols[] = "`checkin_location_name` AS cin_loc";
        }
        if (schema_table_has_column($db, 'attendance', 'checkout_location_name')) {
            $selectCols[] = "`checkout_location_name` AS cout_loc";
        }
        if (schema_table_has_column($db, 'attendance', 'notes')) {
            $selectCols[] = "`notes` AS notes";
        }
        
        $db->select(implode(', ', $selectCols))
            ->from('attendance')
            ->where($userCol, $user_id)
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->order_by($dateCol, 'ASC');
        $rows = $db->get()->result();
        
        $attMap = []; $cinMap = []; $coutMap = []; $cinLocMap = []; $coutLocMap = []; $notesMap = [];
        foreach ($rows as $r) {
            $d = isset($r->d) ? (string)$r->d : '';
            if ($d === '') { continue; }
            if (strpos($d, ' ') !== false) { $d = trim(explode(' ', $d)[0]); }
            $attMap[$d] = (string)$r->st;
            $pin = isset($r->pin) ? trim((string)$r->pin) : '';
            $cin = isset($r->cin) ? trim((string)$r->cin) : '';
            $pout = isset($r->pout) ? trim((string)$r->pout) : '';
            $cout = isset($r->cout) ? trim((string)$r->cout) : '';
            $emptyTimes = ['', '00:00:00', '0000-00-00 00:00:00'];
            if (in_array($pin, $emptyTimes, true)) { $pin = ''; }
            if (in_array($cin, $emptyTimes, true)) { $cin = ''; }
            if (in_array($pout, $emptyTimes, true)) { $pout = ''; }
            if (in_array($cout, $emptyTimes, true)) { $cout = ''; }
            $effectiveIn = ($pin !== '') ? $pin : $cin;
            $effectiveOut = ($pout !== '') ? $pout : $cout;
            if ($effectiveIn !== '') { $cinMap[$d] = $effectiveIn; }
            if ($effectiveOut !== '') { $coutMap[$d] = $effectiveOut; }
            if (isset($r->cin_loc) && !empty($r->cin_loc)) { $cinLocMap[$d] = (string)$r->cin_loc; }
            if (isset($r->cout_loc) && !empty($r->cout_loc)) { $coutLocMap[$d] = (string)$r->cout_loc; }
            if (isset($r->notes) && !empty(trim($r->notes))) { $notesMap[$d] = trim((string)$r->notes); }
        }
        
        // Get leave map
        $leaveMap = [];
        if ($db->table_exists('leave_requests')) {
            $lrows = $db->select('start_date, end_date, status')
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
        
        $timing = attendance_report_get_timing_settings($settings);
        $officeStart = $timing['office_start'];
        $officeEnd = $timing['office_end'];
        $graceMinutes = $timing['grace_minutes'];
        $standardHours = $timing['standard_hours'];
        
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
            $hasInTime = isset($cinMap[$d]) && trim((string)$cinMap[$d]) !== '' && trim((string)$cinMap[$d]) !== '00:00:00' && trim((string)$cinMap[$d]) !== '0000-00-00 00:00:00';
            $hasOutTime = isset($coutMap[$d]) && trim((string)$coutMap[$d]) !== '' && trim((string)$coutMap[$d]) !== '00:00:00' && trim((string)$coutMap[$d]) !== '0000-00-00 00:00:00';
            
            if ($raw === '' && $leave === '—' && !$isWeekend) {
                $st = 'absent';
                $raw = 'absent';
            }
            // If day has actual punch times, don't keep it as absent in report.
            if (($hasInTime || $hasOutTime) && $st === 'absent') {
                $st = 'present';
                $raw = 'present';
            }
            
            if ($isWeekend && $st === '') {
                $labelSt = 'Weekend';
            } else {
                $labelSt = attendance_status_display_label($st);
            }
            
            $checkInLocation = '—';
            $checkOutLocation = '—';
            $cinRaw = isset($cinMap[$d]) ? trim((string)$cinMap[$d]) : '';
            $coutRaw = isset($coutMap[$d]) ? trim((string)$coutMap[$d]) : '';
            $checkInTime = attendance_report_format_clock_display($cinRaw);
            $checkOutTime = attendance_report_format_clock_display($coutRaw);

            $lateDisplay = attendance_report_resolve_late_display($d, $cinRaw, $coutRaw, null, $timing);
            $lateLabel = $lateDisplay['late_label'];
            $lateStatus = $lateDisplay['late_status'];
            $lateMinutes = $lateDisplay['late_minutes'];
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
}

if (!function_exists('attendance_report_employee_summary_pdf_html')) {
    function attendance_report_employee_summary_pdf_html($users, $summary, $period, $from, $to, $month, $date)
    {
        $periodLabel = attendance_report_period_label($period, $from, $to, $month, $date);
        
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

            $lateDays  = isset($data['late']) ? (float)$data['late'] : 0.0;
            $lateStyle = $lateDays > 0 ? ' style="color:#dc2626;font-weight:bold;"' : '';
            
            $html .= '<tr>
                <td>' . $name . '</td>
                <td>' . $uid . '</td>
                <td>' . attendance_report_format_day_count($data['present']) . '</td>
                <td>' . attendance_report_format_day_count($data['half']) . '</td>
                <td>' . attendance_report_format_day_count($data['wfh']) . '</td>
                <td>' . attendance_report_format_day_count($data['absent']) . '</td>
                <td>' . attendance_report_format_day_count($data['leave']) . '</td>
                <td' . $lateStyle . '>' . attendance_report_format_day_count($lateDays) . '</td>
                <td>' . attendance_report_format_day_count(isset($data['on_time']) ? $data['on_time'] : 0) . '</td>
                <td>' . attendance_report_format_hours_hhmm(isset($data['late_hours']) ? $data['late_hours'] : 0) . '</td>
                <td>' . attendance_report_format_hours_hhmm(isset($data['extra_hours']) ? $data['extra_hours'] : 0) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
</body>
</html>';
        
        return $html;
    }
}

if (!function_exists('attendance_report_export_period')) {
    function attendance_report_export_period($period, $data, $format)
    {
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
}

if (!function_exists('attendance_report_export_employee_summary_csv')) {
    function attendance_report_export_employee_summary_csv($db, $userIds, $period, $from, $to, $month, $date, $settings, callable $fetchUsers)
    {
        try {
            $users = $fetchUsers($userIds);

            $summary = attendance_report_build_export_summaries($db, $userIds, $from, $to, $settings);

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
                    attendance_report_format_day_count(isset($data['present']) ? $data['present'] : 0),
                    attendance_report_format_day_count(isset($data['half']) ? $data['half'] : 0),
                    attendance_report_format_day_count(isset($data['wfh']) ? $data['wfh'] : 0),
                    attendance_report_format_day_count(isset($data['absent']) ? $data['absent'] : 0),
                    attendance_report_format_day_count(isset($data['leave']) ? $data['leave'] : 0),
                    attendance_report_format_day_count(isset($data['late']) ? $data['late'] : 0),
                    attendance_report_format_day_count(isset($data['on_time']) ? $data['on_time'] : 0),
                    attendance_report_format_hours_hhmm(isset($data['late_hours']) ? $data['late_hours'] : 0),
                    attendance_report_format_hours_hhmm(isset($data['extra_hours']) ? $data['extra_hours'] : 0)
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            log_message('error', 'Export Excel error: ' . $e->getMessage());
            show_error('Error generating Excel export: ' . $e->getMessage(), 500);
        }
    }
}

if (!function_exists('attendance_report_export_employee_summary_pdf')) {
    function attendance_report_export_employee_summary_pdf($db, $userIds, $period, $from, $to, $month, $date, $settings, callable $fetchUsers)
    {
        try {
            $users = $fetchUsers($userIds);

            $summary = attendance_report_build_export_summaries($db, $userIds, $from, $to, $settings);

            $html = attendance_report_employee_summary_pdf_html($users, $summary, $period, $from, $to, $month, $date);
            
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
}

if (!function_exists('attendance_report_export_employee_detail_excel')) {
    function attendance_report_export_employee_detail_excel($db, $user_id, $period, $from, $to, $month, $date, $settings, callable $fetchUserName)
    {
        if (!hierarchy_user_can_access((int)$user_id)) {
            show_error('You do not have permission to export this employee\'s data.', 403);
            return;
        }
        try {
            $userName = $fetchUserName($user_id);

            // Generate daily details
            $days = attendance_report_generate_daily_details($db, $user_id, $from, $to, $settings);
            
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
}

if (!function_exists('attendance_report_export_employee_detail_pdf')) {
    function attendance_report_export_employee_detail_pdf($db, $user_id, $period, $from, $to, $month, $date, $settings, callable $fetchUserName)
    {
        if (!hierarchy_user_can_access((int)$user_id)) {
            show_error('You do not have permission to export this employee\'s data.', 403);
            return;
        }
        try {
            $userName = $fetchUserName($user_id);

            // Generate daily details
            $days = attendance_report_generate_daily_details($db, $user_id, $from, $to, $settings);
            
            $periodLabel = attendance_report_period_label($period, $from, $to, $month, $date);
            
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
}

