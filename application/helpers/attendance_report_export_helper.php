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
        
        // Get leave map (skip WFH leave types — those are attendance WFH, not leave)
        $leaveMap = [];
        $wfhMap = [];
        if ($db->table_exists('leave_requests')) {
            $lrows = $db->select('lr.start_date, lr.end_date, lr.status, lr.reason, lt.name AS type_name')
                ->from('leave_requests lr')
                ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                ->where('lr.user_id', $user_id)
                ->where_in('lr.status', ['lead_approved','hr_approved'])
                ->where('lr.start_date <=', $to)
                ->where('lr.end_date >=', $from)
                ->get()->result();
            foreach ($lrows as $lr) {
                $sd = isset($lr->start_date) ? (string)$lr->start_date : '';
                $ed = isset($lr->end_date) ? (string)$lr->end_date : '';
                if ($sd === '' || $ed === '') { continue; }
                $cur = strtotime(max($from, substr($sd, 0, 10)));
                $endTs = strtotime(min($to, substr($ed, 0, 10)));
                $isWfhLeave = attendance_report_is_wfh_leave_row($lr);
                $txt = 'Leave ('.(string)$lr->status.')';
                while ($cur !== false && $cur <= $endTs) {
                    $k = date('Y-m-d', $cur);
                    if ($isWfhLeave) {
                        $wfhMap[$k] = true;
                    } elseif (!isset($leaveMap[$k])) {
                        $leaveMap[$k] = $txt;
                    }
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
            $isWfhDay = !empty($wfhMap[$d]) || (normalize_attendance_status_key($st) === 'work_from_home');
            $hasInTime = isset($cinMap[$d]) && trim((string)$cinMap[$d]) !== '' && trim((string)$cinMap[$d]) !== '00:00:00' && trim((string)$cinMap[$d]) !== '0000-00-00 00:00:00';
            $hasOutTime = isset($coutMap[$d]) && trim((string)$coutMap[$d]) !== '' && trim((string)$coutMap[$d]) !== '00:00:00' && trim((string)$coutMap[$d]) !== '0000-00-00 00:00:00';
            
            if ($raw === '' && $leave === '—' && !$isWeekend && !$isWfhDay) {
                $st = 'absent';
                $raw = 'absent';
            }
            // If day has actual punch times, don't keep it as absent in report.
            if (($hasInTime || $hasOutTime) && $st === 'absent') {
                $st = 'present';
                $raw = 'present';
            }
            if ($isWfhDay && ($st === '' || $st === 'absent' || $st === 'present' || $st === 'late')) {
                $st = 'work_from_home';
                $raw = 'work_from_home';
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
            $lateHoursDec = ($lateMinutes > 0) ? round($lateMinutes / 60, 2) : 0.0;
            $obj->late_hours = $lateHoursDec;
            $obj->net_hours = round(attendance_report_compute_net_hours($obj->extra_hours, $lateHoursDec), 2);
            $obj->net_hours_display = attendance_report_format_hours_hhmm_signed($obj->net_hours);
            $obj->worked_seconds = $workedSeconds;
            $obj->extra_seconds = $extraSeconds;
            $obj->notes = $notes;
            $days[] = $obj;
            $startTs = strtotime('+1 day', $startTs);
        }
        
        return $days;
    }
}

if (!function_exists('attendance_report_format_hours_decimal')) {
    function attendance_report_format_hours_decimal($hours)
    {
        return number_format(max(0, (float) $hours), 2, '.', '');
    }
}

if (!function_exists('attendance_report_format_hours_decimal_signed')) {
    function attendance_report_format_hours_decimal_signed($hours)
    {
        return number_format((float) $hours, 2, '.', '');
    }
}

if (!function_exists('attendance_report_export_timing_calculation_notes')) {
    function attendance_report_export_timing_calculation_notes($officeStart = '', $officeEnd = '', $graceMinutes = '', $standardHours = '')
    {
        $lines = array(
            'Late Days = count of working days where check-in is after office start + grace period.',
            'Late = total late time (HH:MM) after grace period on present days.',
            'Work = total extra time (HH:MM) stayed after official office end.',
            'Total = Work minus Late (HH:MM). Negative values mean time deficit.',
        );
        if ($officeStart !== '' && $officeEnd !== '') {
            $grace = is_numeric($graceMinutes) ? (int) $graceMinutes : 0;
            $graceCutoff = $officeStart;
            $officeTs = strtotime('1970-01-01 ' . $officeStart . ':00');
            if ($officeTs !== false) {
                $graceCutoff = date('H:i', $officeTs + ($grace * 60));
            }
            $lines[] = 'Default office rule: check-in by ' . $graceCutoff . ', check-out from ' . $officeEnd
                . ($standardHours !== '' ? ', standard ' . $standardHours . 'h/day' : '')
                . ' (employee shift overrides when assigned).';
        }

        return $lines;
    }
}

if (!function_exists('attendance_report_grid_summary_headers')) {
    function attendance_report_grid_summary_headers()
    {
        return array(
            'Employee',
            'Present',
            'Half Day',
            'WFH',
            'Absent',
            'On Time',
            'Late Days',
            'Leave',
            'Holiday',
            'Late',
            'Work',
            'Total',
        );
    }
}

if (!function_exists('attendance_report_grid_row_timing_values')) {
  /**
   * @param array<string,string> $row
   * @return array{late_hours_decimal:float,extra_hours_decimal:float,net_hours_decimal:float,late_hours_display:string,extra_hours_display:string,net_hours_display:string}
   */
    function attendance_report_grid_row_timing_values(array $row)
    {
        $lateDec = 0.0;
        if (isset($row['late_hours_decimal']) && $row['late_hours_decimal'] !== '') {
            $lateDec = (float) $row['late_hours_decimal'];
        }
        $extraDec = 0.0;
        if (isset($row['extra_hours_decimal']) && $row['extra_hours_decimal'] !== '') {
            $extraDec = (float) $row['extra_hours_decimal'];
        }
        $netDec = isset($row['net_hours_decimal']) && $row['net_hours_decimal'] !== ''
            ? (float) $row['net_hours_decimal']
            : attendance_report_compute_net_hours($extraDec, $lateDec);

        $lateDisplay = isset($row['late_hours']) ? trim((string) $row['late_hours']) : '00:00';
        $extraDisplay = isset($row['extra_hours']) ? trim((string) $row['extra_hours']) : '00:00';
        $netDisplay = isset($row['net_hours']) ? trim((string) $row['net_hours']) : attendance_report_format_hours_hhmm_signed($netDec);

        return array(
            'late_hours_decimal'   => $lateDec,
            'extra_hours_decimal'  => $extraDec,
            'net_hours_decimal'    => $netDec,
            'late_hours_display'   => $lateDisplay !== '' ? $lateDisplay : '00:00',
            'extra_hours_display'  => $extraDisplay !== '' ? $extraDisplay : '00:00',
            'net_hours_display'    => $netDisplay !== '' ? $netDisplay : attendance_report_format_hours_hhmm_signed(0),
        );
    }
}

if (!function_exists('attendance_report_grid_export_totals')) {
    /**
     * @param array<int,array<string,string>> $gridRows
     * @return array{late_days:float,late_hours_decimal:float,extra_hours_decimal:float,net_hours_decimal:float}
     */
    function attendance_report_grid_export_totals(array $gridRows)
    {
        $totals = array(
            'late_days'            => 0.0,
            'late_hours_decimal'   => 0.0,
            'extra_hours_decimal'  => 0.0,
            'net_hours_decimal'    => 0.0,
        );

        foreach ($gridRows as $row) {
            $timing = attendance_report_grid_row_timing_values($row);
            $totals['late_days'] += (float) (isset($row['late']) ? $row['late'] : 0);
            $totals['late_hours_decimal'] += $timing['late_hours_decimal'];
            $totals['extra_hours_decimal'] += $timing['extra_hours_decimal'];
            $totals['net_hours_decimal'] += $timing['net_hours_decimal'];
        }

        return $totals;
    }
}

if (!function_exists('attendance_report_parse_grid_export_rows')) {
    /**
     * @param string $json
     * @return array<int,array<string,string>>
     */
    function attendance_report_parse_grid_export_rows($json)
    {
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) {
            return array();
        }

        $rows = array();
        foreach ($decoded as $item) {
            if (!is_array($item) || empty($item['user_id'])) {
                continue;
            }
            $rows[] = array(
                'user_id'              => (string) (int) $item['user_id'],
                'name'                 => isset($item['name']) ? trim((string) $item['name']) : '',
                'present'              => isset($item['present']) ? trim((string) $item['present']) : '0',
                'half_day'             => isset($item['half_day']) ? trim((string) $item['half_day']) : '0',
                'wfh'                  => isset($item['wfh']) ? trim((string) $item['wfh']) : '0',
                'absent'               => isset($item['absent']) ? trim((string) $item['absent']) : '0',
                'on_time'              => isset($item['on_time']) ? trim((string) $item['on_time']) : '0',
                'late'                 => isset($item['late']) ? trim((string) $item['late']) : '0',
                'leave'                => isset($item['leave']) ? trim((string) $item['leave']) : '0',
                'holiday'              => isset($item['holiday']) ? trim((string) $item['holiday']) : '0',
                'late_hours'           => isset($item['late_hours']) ? trim((string) $item['late_hours']) : '00:00',
                'extra_hours'          => isset($item['extra_hours']) ? trim((string) $item['extra_hours']) : '00:00',
                'late_hours_decimal'   => isset($item['late_hours_decimal']) ? trim((string) $item['late_hours_decimal']) : '0',
                'extra_hours_decimal'  => isset($item['extra_hours_decimal']) ? trim((string) $item['extra_hours_decimal']) : '0',
                'net_hours'            => isset($item['net_hours']) ? trim((string) $item['net_hours']) : '00:00',
                'net_hours_decimal'    => isset($item['net_hours_decimal']) ? trim((string) $item['net_hours_decimal']) : '0',
            );
        }

        return $rows;
    }
}

if (!function_exists('attendance_report_grid_row_to_csv_line')) {
    function attendance_report_grid_row_to_csv_line(array $row)
    {
        $name = $row['name'];
        if ($name === '') {
            $name = 'User #' . $row['user_id'];
        }
        $timing = attendance_report_grid_row_timing_values($row);

        return array(
            $name . ' (ID: ' . $row['user_id'] . ')',
            $row['present'],
            $row['half_day'],
            $row['wfh'],
            $row['absent'],
            $row['on_time'],
            $row['late'],
            $row['leave'],
            $row['holiday'],
            $timing['late_hours_display'],
            $timing['extra_hours_display'],
            $timing['net_hours_display'],
        );
    }
}

if (!function_exists('attendance_report_export_grid_summary_csv')) {
    function attendance_report_export_grid_summary_csv(
        array $gridRows,
        $period,
        $from,
        $to,
        $sortColumn = '',
        $sortDirection = '',
        $officeStart = '',
        $officeEnd = '',
        $graceMinutes = '',
        $standardHours = ''
    ) {
        $filename = 'attendance_employee_report_' . $period . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, array('Report', 'Employee Attendance Summary'));
        fputcsv($output, array('Period', ucfirst((string) $period)));
        fputcsv($output, array('From', $from));
        fputcsv($output, array('To', $to));
        if ($sortColumn !== '' && $sortDirection !== '') {
            fputcsv($output, array('Sorted By', 'Column ' . $sortColumn . ' (' . $sortDirection . ')'));
        }
        foreach (attendance_report_export_timing_calculation_notes($officeStart, $officeEnd, $graceMinutes, $standardHours) as $note) {
            fputcsv($output, array('Calculation', $note));
        }
        fputcsv($output, array());

        fputcsv($output, attendance_report_grid_summary_headers());

        foreach ($gridRows as $row) {
            fputcsv($output, attendance_report_grid_row_to_csv_line($row));
        }

        $totals = attendance_report_grid_export_totals($gridRows);
        fputcsv($output, array());
        fputcsv($output, array(
            'TOTAL (selected employees)',
            '',
            '',
            '',
            '',
            '',
            attendance_report_format_day_count($totals['late_days']),
            '',
            '',
            attendance_report_format_hours_hhmm($totals['late_hours_decimal']),
            attendance_report_format_hours_hhmm($totals['extra_hours_decimal']),
            attendance_report_format_hours_hhmm_signed($totals['net_hours_decimal']),
        ));

        fclose($output);
        exit;
    }
}

if (!function_exists('attendance_report_grid_summary_pdf_html')) {
    function attendance_report_grid_summary_pdf_html(
        array $gridRows,
        $period,
        $from,
        $to,
        $sortColumn = '',
        $sortDirection = '',
        $officeStart = '',
        $officeEnd = '',
        $graceMinutes = '',
        $standardHours = ''
    ) {
        $headers = attendance_report_grid_summary_headers();
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 9px; }
        h1 { color: #2563eb; margin-bottom: 10px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2563eb; color: white; padding: 6px; text-align: left; border: 1px solid #ddd; font-size: 8px; }
        td { padding: 5px; border: 1px solid #ddd; font-size: 8px; }
        tr:nth-child(even) { background-color: #f8fafc; }
        tfoot td { background-color: #e2e8f0; font-weight: bold; }
        .header-info { margin-bottom: 15px; padding: 10px; background-color: #f1f5f9; border-radius: 4px; font-size: 9px; }
        .header-info p { margin: 3px 0; }
        .late-val { color: #dc2626; font-weight: bold; }
        .extra-val { color: #059669; font-weight: bold; }
        .net-deficit { color: #dc2626; font-weight: bold; }
        .net-surplus { color: #059669; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Employee Attendance Report</h1>
    <div class="header-info">
        <p><strong>Period:</strong> ' . esc_view(ucfirst((string) $period)) . '</p>
        <p><strong>Date Range:</strong> ' . esc_view($from) . ' to ' . esc_view($to) . '</p>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        if ($sortColumn !== '' && $sortDirection !== '') {
            $html .= '<p><strong>Grid Sort:</strong> Column ' . esc_view($sortColumn) . ' (' . esc_view($sortDirection) . ')</p>';
        }
        foreach (attendance_report_export_timing_calculation_notes($officeStart, $officeEnd, $graceMinutes, $standardHours) as $note) {
            $html .= '<p><strong>Calculation:</strong> ' . esc_view($note) . '</p>';
        }
        $html .= '</div>
    <table>
        <thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . esc_view($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($gridRows as $row) {
            $name = $row['name'] !== '' ? esc_view($row['name']) : ('User #' . esc_view($row['user_id']));
            $timing = attendance_report_grid_row_timing_values($row);
            $lateClass = ((float) $row['late'] > 0) ? ' class="late-val"' : '';
            $extraClass = ($timing['extra_hours_decimal'] > 0) ? ' class="extra-val"' : '';
            $netClass = '';
            if ($timing['net_hours_decimal'] < 0) {
                $netClass = ' class="net-deficit"';
            } elseif ($timing['net_hours_decimal'] > 0) {
                $netClass = ' class="net-surplus"';
            }
            $html .= '<tr>
                <td>' . $name . ' (ID: ' . esc_view($row['user_id']) . ')</td>
                <td>' . esc_view($row['present']) . '</td>
                <td>' . esc_view($row['half_day']) . '</td>
                <td>' . esc_view($row['wfh']) . '</td>
                <td>' . esc_view($row['absent']) . '</td>
                <td>' . esc_view($row['on_time']) . '</td>
                <td' . $lateClass . '>' . esc_view($row['late']) . '</td>
                <td>' . esc_view($row['leave']) . '</td>
                <td>' . esc_view($row['holiday']) . '</td>
                <td' . $lateClass . '>' . esc_view($timing['late_hours_display']) . '</td>
                <td' . $extraClass . '>' . esc_view($timing['extra_hours_display']) . '</td>
                <td' . $netClass . '>' . esc_view($timing['net_hours_display']) . '</td>
            </tr>';
        }

        $totals = attendance_report_grid_export_totals($gridRows);
        $html .= '</tbody><tfoot><tr>
            <td>TOTAL (selected)</td>
            <td></td><td></td><td></td><td></td><td></td>
            <td class="late-val">' . esc_view(attendance_report_format_day_count($totals['late_days'])) . '</td>
            <td></td><td></td>
            <td class="late-val">' . esc_view(attendance_report_format_hours_hhmm($totals['late_hours_decimal'])) . '</td>
            <td class="extra-val">' . esc_view(attendance_report_format_hours_hhmm($totals['extra_hours_decimal'])) . '</td>
            <td class="' . ($totals['net_hours_decimal'] < 0 ? 'net-deficit' : ($totals['net_hours_decimal'] > 0 ? 'net-surplus' : '')) . '">' . esc_view(attendance_report_format_hours_hhmm_signed($totals['net_hours_decimal'])) . '</td>
        </tr></tfoot></table></body></html>';

        return $html;
    }
}

if (!function_exists('attendance_report_export_grid_summary_pdf')) {
    function attendance_report_export_grid_summary_pdf(
        array $gridRows,
        $period,
        $from,
        $to,
        $sortColumn = '',
        $sortDirection = '',
        $officeStart = '',
        $officeEnd = '',
        $graceMinutes = '',
        $standardHours = ''
    ) {
        $html = attendance_report_grid_summary_pdf_html(
            $gridRows,
            $period,
            $from,
            $to,
            $sortColumn,
            $sortDirection,
            $officeStart,
            $officeEnd,
            $graceMinutes,
            $standardHours
        );
        $filename = 'attendance_employee_report_' . $period . '_' . date('Y-m-d') . '.pdf';

        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
        echo $html;
        exit;
    }
}

if (!function_exists('attendance_report_build_grid_rows_from_summary')) {
    /**
     * @param array $users
     * @param array $summary
     * @return array<int,array<string,string>>
     */
    function attendance_report_build_grid_rows_from_summary($users, array $summary)
    {
        $gridRows = array();
        foreach ($users as $user) {
            $uid = (int) $user->id;
            $name = isset($user->name) ? $user->name : 'Unknown';
            $data = isset($summary[$uid]) ? $summary[$uid] : array(
                'present' => 0.0, 'half' => 0.0, 'wfh' => 0.0, 'absent' => 0.0,
                'leave' => 0.0, 'holiday' => 0.0, 'late' => 0.0, 'on_time' => 0.0,
                'late_hours' => 0.0, 'extra_hours' => 0.0,
            );
            $gridRows[] = array(
                'user_id'             => (string) $uid,
                'name'                => $name,
                'present'             => attendance_report_format_day_count(isset($data['present']) ? $data['present'] : 0),
                'half_day'            => attendance_report_format_day_count(isset($data['half']) ? $data['half'] : 0),
                'wfh'                 => attendance_report_format_day_count(isset($data['wfh']) ? $data['wfh'] : 0),
                'absent'              => attendance_report_format_day_count(isset($data['absent']) ? $data['absent'] : 0),
                'on_time'             => attendance_report_format_day_count(isset($data['on_time']) ? $data['on_time'] : 0),
                'late'                => attendance_report_format_day_count(isset($data['late']) ? $data['late'] : 0),
                'leave'               => attendance_report_format_day_count(isset($data['leave']) ? $data['leave'] : 0),
                'holiday'             => attendance_report_format_day_count(isset($data['holiday']) ? $data['holiday'] : 0),
                'late_hours'          => attendance_report_format_hours_hhmm(isset($data['late_hours']) ? $data['late_hours'] : 0),
                'extra_hours'         => attendance_report_format_hours_hhmm(isset($data['extra_hours']) ? $data['extra_hours'] : 0),
                'late_hours_decimal'  => attendance_report_format_hours_decimal(isset($data['late_hours']) ? $data['late_hours'] : 0),
                'extra_hours_decimal' => attendance_report_format_hours_decimal(isset($data['extra_hours']) ? $data['extra_hours'] : 0),
                'net_hours'           => attendance_report_format_hours_hhmm_signed(attendance_report_compute_net_hours(
                    isset($data['extra_hours']) ? $data['extra_hours'] : 0,
                    isset($data['late_hours']) ? $data['late_hours'] : 0
                )),
                'net_hours_decimal'   => attendance_report_format_hours_decimal_signed(attendance_report_compute_net_hours(
                    isset($data['extra_hours']) ? $data['extra_hours'] : 0,
                    isset($data['late_hours']) ? $data['late_hours'] : 0
                )),
            );
        }

        return $gridRows;
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
        <p><strong>Date Range:</strong> ' . esc_view($from) . ' to ' . esc_view($to) . '</p>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Note:</strong> Late/on-time uses each employee shift when assigned, otherwise global office settings.</p>
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
                <th>On Time</th>
                <th>Late Days</th>
                <th>Leave</th>
                <th>Holiday</th>
                <th>Late</th>
                <th>Work</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($users as $user) {
            $uid = (int)$user->id;
            $name = isset($user->name) ? esc_view($user->name) : 'Unknown';
            $data = isset($summary[$uid]) ? $summary[$uid] : ['present'=>0.0,'half'=>0.0,'wfh'=>0.0,'absent'=>0.0,'leave'=>0.0,'holiday'=>0.0,'late'=>0.0,'on_time'=>0.0,'late_hours'=>0.0,'extra_hours'=>0.0];

            $lateDays  = isset($data['late']) ? (float)$data['late'] : 0.0;
            $lateStyle = $lateDays > 0 ? ' style="color:#dc2626;font-weight:bold;"' : '';
            $netHours = attendance_report_compute_net_hours(
                isset($data['extra_hours']) ? $data['extra_hours'] : 0,
                isset($data['late_hours']) ? $data['late_hours'] : 0
            );
            $netStyle = $netHours < 0 ? ' style="color:#dc2626;font-weight:bold;"' : ($netHours > 0 ? ' style="color:#059669;font-weight:bold;"' : '');
            
            $html .= '<tr>
                <td>' . $name . '</td>
                <td>' . $uid . '</td>
                <td>' . attendance_report_format_day_count($data['present']) . '</td>
                <td>' . attendance_report_format_day_count($data['half']) . '</td>
                <td>' . attendance_report_format_day_count($data['wfh']) . '</td>
                <td>' . attendance_report_format_day_count($data['absent']) . '</td>
                <td>' . attendance_report_format_day_count(isset($data['on_time']) ? $data['on_time'] : 0) . '</td>
                <td' . $lateStyle . '>' . attendance_report_format_day_count($lateDays) . '</td>
                <td>' . attendance_report_format_day_count($data['leave']) . '</td>
                <td>' . attendance_report_format_day_count(isset($data['holiday']) ? $data['holiday'] : 0) . '</td>
                <td>' . attendance_report_format_hours_hhmm(isset($data['late_hours']) ? $data['late_hours'] : 0) . '</td>
                <td>' . attendance_report_format_hours_hhmm(isset($data['extra_hours']) ? $data['extra_hours'] : 0) . '</td>
                <td' . $netStyle . '>' . attendance_report_format_hours_hhmm_signed($netHours) . '</td>
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
                $html .= '<td>' . esc_view($row->name) . '</td>';
                $html .= '<td>' . esc_view($row->bucket) . '</td>';
                $html .= '<td>' . esc_view($row->status) . '</td>';
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
            
            $exportGridRows = attendance_report_build_grid_rows_from_summary($users, $summary);

            fputcsv($output, array('Report', 'Employee Attendance Summary'));
            fputcsv($output, array('Period', ucfirst($period)));
            fputcsv($output, array('From', $from));
            fputcsv($output, array('To', $to));
            foreach (attendance_report_export_timing_calculation_notes() as $note) {
                fputcsv($output, array('Calculation', $note));
            }
            fputcsv($output, array());

            fputcsv($output, attendance_report_grid_summary_headers());

            foreach ($exportGridRows as $gridRow) {
                fputcsv($output, attendance_report_grid_row_to_csv_line($gridRow));
            }

            $totals = attendance_report_grid_export_totals($exportGridRows);
            fputcsv($output, array());
            fputcsv($output, array(
                'TOTAL (selected employees)',
                '', '', '', '', '',
                attendance_report_format_day_count($totals['late_days']),
                '', '',
                attendance_report_format_hours_hhmm($totals['late_hours_decimal']),
                attendance_report_format_hours_hhmm($totals['extra_hours_decimal']),
                attendance_report_format_hours_hhmm_signed($totals['net_hours_decimal']),
            ));
            
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
            $gridRows = attendance_report_build_grid_rows_from_summary($users, $summary);
            attendance_report_export_grid_summary_pdf($gridRows, $period, $from, $to);
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
            $html .= '<p><strong>User:</strong> ' . esc_view($userName) . '<br>';
            $html .= '<strong>Period:</strong> ' . esc_view($period) . '<br>';
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
                    <td' . $lateStyle . '>' . esc_view($day->date) . '</td>
                    <td>' . esc_view($day->status) . '</td>
                    <td>' . esc_view($day->check_in_time !== '—' ? $day->check_in_time : '') . '</td>
                    <td>' . esc_view($day->check_out_time !== '—' ? $day->check_out_time : '') . '</td>
                    <td>' . esc_view($day->check_in_location !== '—' ? $day->check_in_location : '') . '</td>
                    <td>' . esc_view($day->check_out_location !== '—' ? $day->check_out_location : '') . '</td>
                    <td' . $lateStyle . '>' . esc_view($day->late !== '—' ? $day->late : '') . '</td>
                    <td>' . ($ws > 0 ? sprintf('%02d:%02d:%02d', floor($ws/3600), floor(($ws%3600)/60), $ws%60) : '') . '</td>
                    <td>' . ($es > 0 ? sprintf('%02d:%02d:%02d', floor($es/3600), floor(($es%3600)/60), $es%60) : '') . '</td>
                    <td>' . esc_view($day->leave !== '—' ? $day->leave : '') . '</td>
                    <td>' . esc_view($day->notes !== '—' ? $day->notes : '') . '</td>
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
        <p><strong>Employee:</strong> ' . esc_view($userName) . ' (ID: ' . $user_id . ')</p>
        <p><strong>Period:</strong> ' . ucfirst($period) . ' - ' . esc_view($periodLabel) . '</p>
        <p><strong>Date Range:</strong> ' . esc_view($from) . ' to ' . esc_view($to) . '</p>
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
                    <td' . $lateStyle . '>' . esc_view($day->date) . '</td>
                    <td>' . esc_view($day->status) . '</td>
                    <td>' . esc_view($day->check_in_time !== '—' ? $day->check_in_time : '') . '</td>
                    <td>' . esc_view($day->check_out_time !== '—' ? $day->check_out_time : '') . '</td>
                    <td>' . esc_view($day->check_in_location !== '—' ? (strlen($day->check_in_location) > 30 ? substr($day->check_in_location, 0, 30) . '...' : $day->check_in_location) : '') . '</td>
                    <td>' . esc_view($day->check_out_location !== '—' ? (strlen($day->check_out_location) > 30 ? substr($day->check_out_location, 0, 30) . '...' : $day->check_out_location) : '') . '</td>
                    <td' . $lateStyle . '>' . esc_view($day->late !== '—' ? $day->late : '') . '</td>
                    <td>' . ($ws > 0 ? sprintf('%02d:%02d:%02d', floor($ws/3600), floor(($ws%3600)/60), $ws%60) : '') . '</td>
                    <td>' . ($es > 0 ? sprintf('%02d:%02d:%02d', floor($es/3600), floor(($es%3600)/60), $es%60) : '') . '</td>
                    <td>' . esc_view($day->leave !== '—' ? $day->leave : '') . '</td>
                    <td>' . esc_view($day->notes !== '—' ? (strlen($day->notes) > 50 ? substr($day->notes, 0, 50) . '...' : $day->notes) : '') . '</td>
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

