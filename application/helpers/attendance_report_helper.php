<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared helpers for attendance report controllers (date ranges, holidays, column resolution).
 */

if (!function_exists('attendance_report_normalize_period')) {
    /**
     * @param string $period
     * @return string daily|weekly|monthly
     */
    function attendance_report_normalize_period($period)
    {
        return in_array($period, array('daily', 'weekly', 'monthly'), true) ? $period : 'monthly';
    }
}

if (!function_exists('attendance_report_date_range_view')) {
    /**
     * Date range for attendance_employee page (matches legacy Reports_attendance logic).
     *
     * @param string $period
     * @param string $month YYYY-MM
     * @param string $date  YYYY-MM-DD
     * @return array{period:string,month:string,date:string,from:string,to:string,today:string}
     */
    function attendance_report_date_range_view($period, $month, $date)
    {
        $period = attendance_report_normalize_period($period);
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
                $dateTs = strtotime($date);
                $dayOfWeek = (int) date('w', $dateTs);
                $mondayOffset = ($dayOfWeek === 0) ? -6 : (1 - $dayOfWeek);
                $from = date('Y-m-d', strtotime($mondayOffset . ' days', $dateTs));
                $to = date('Y-m-d', strtotime('+6 days', strtotime($from)));
            } else {
                $today = date('Y-m-d');
                $dayOfWeek = (int) date('w', strtotime($today));
                $mondayOffset = ($dayOfWeek === 0) ? -6 : (1 - $dayOfWeek);
                $from = date('Y-m-d', strtotime($mondayOffset . ' days', strtotime($today)));
                $to = date('Y-m-d', strtotime('+6 days', strtotime($from)));
                $date = $from;
            }
        } else {
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $from = $month . '-01';
                $to = date('Y-m-t', strtotime($from));
            } else {
                $month = date('Y-m');
                $from = $month . '-01';
                $to = date('Y-m-t', strtotime($from));
            }
        }

        $today = date('Y-m-d');
        if (strtotime($to) > strtotime($today)) {
            $to = $today;
        }

        return array(
            'period' => $period,
            'month'  => $month,
            'date'   => $date,
            'from'   => $from,
            'to'     => $to,
            'today'  => $today,
        );
    }
}

if (!function_exists('attendance_report_date_range_export')) {
    /**
     * Date range for export_attendance_employee (legacy export branch logic).
     *
     * @param string $period
     * @param string|null $month
     * @param string|null $date
     * @return array{from:string,to:string}
     */
    function attendance_report_date_range_export($period, $month, $date)
    {
        $from = '';
        $to = '';

        if ($period === 'daily' && $date) {
            $from = $date;
            $to = $date;
        } elseif ($period === 'weekly' && $date) {
            $startTs = strtotime($date);
            $dow = (int) date('w', $startTs);
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

        return array('from' => $from, 'to' => $to);
    }
}

if (!function_exists('attendance_report_holidays_for_range')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $from
     * @param string $to
     * @return array{holidays:array,holiday_dates:array}
     */
    function attendance_report_holidays_for_range($db, $from, $to)
    {
        $holidays = array();
        $holidayDates = array();

        if ($db->table_exists('holidays')) {
            $holidays = $db->where('holiday_date >=', $from)
                ->where('holiday_date <=', $to)
                ->order_by('holiday_date', 'ASC')
                ->get('holidays')
                ->result();
            foreach ($holidays as $h) {
                $holidayDates[] = $h->holiday_date;
            }
        }

        return array('holidays' => $holidays, 'holiday_dates' => $holidayDates);
    }
}

if (!function_exists('attendance_report_count_working_days')) {
    /**
     * Count Mon–Fri days in range up to today, excluding holidays.
     *
     * @param string $from
     * @param string $to
     * @param array  $holidayDates
     * @param string|null $today
     * @return int
     */
    function attendance_report_count_working_days($from, $to, array $holidayDates, $today = null)
    {
        $today = $today ?: date('Y-m-d');
        $total = 0;
        $startTs = strtotime($from);
        $endTs = strtotime($to);
        $todayTs = strtotime($today);

        while ($startTs !== false && $startTs <= $endTs) {
            if ($startTs > $todayTs) {
                break;
            }
            $currentDate = date('Y-m-d', $startTs);
            $dayOfWeek = (int) date('w', $startTs);
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6 && !in_array($currentDate, $holidayDates, true)) {
                $total++;
            }
            $startTs = strtotime('+1 day', $startTs);
        }

        return $total;
    }
}

if (!function_exists('attendance_report_resolve_columns')) {
    /**
     * Resolve legacy attendance column names.
     *
     * @param CI_DB_query_builder $db
     * @return array{fields:array,user_col:string,date_col:string,status_col:string}
     */
    function attendance_report_resolve_columns($db)
    {
        $fields = $db->list_fields('attendance');
        $userCandidates = array('user_id', 'employee_id', 'emp_id', 'staff_id', 'uid');
        $dateCandidates = array('att_date', 'date', 'attendance_date', 'created_at', 'checked_at');
        $statusCandidates = array('status', 'attendance_status', 'state');
        $userCol = $dateCol = $statusCol = null;

        foreach ($userCandidates as $c) {
            if (in_array($c, $fields, true)) {
                $userCol = $c;
                break;
            }
        }
        foreach ($dateCandidates as $c) {
            if (in_array($c, $fields, true)) {
                $dateCol = $c;
                break;
            }
        }
        foreach ($statusCandidates as $c) {
            if (in_array($c, $fields, true)) {
                $statusCol = $c;
                break;
            }
        }

        if ($userCol === null) {
            $userCol = isset($fields[0]) ? $fields[0] : 'user_id';
        }
        if ($dateCol === null) {
            $dateCol = isset($fields[1]) ? $fields[1] : 'att_date';
        }
        if ($statusCol === null) {
            $statusCol = isset($fields[2]) ? $fields[2] : 'status';
        }

        return array(
            'fields'     => $fields,
            'user_col'   => $userCol,
            'date_col'   => $dateCol,
            'status_col' => $statusCol,
        );
    }
}

if (!function_exists('attendance_report_period_label')) {
    /**
     * @param string $period
     * @param string $from
     * @param string $to
     * @param string $month
     * @param string $date
     * @return string
     */
    function attendance_report_period_label($period, $from, $to, $month, $date)
    {
        if ($period === 'daily') {
            return $date ?: date('Y-m-d');
        }
        if ($period === 'weekly') {
            return $from . ' to ' . $to;
        }

        return $month ?: date('Y-m');
    }
}

if (!function_exists('attendance_report_is_wfh_leave_row')) {
    /**
     * @param object $leaveRow
     * @return bool
     */
    function attendance_report_is_wfh_leave_row($leaveRow)
    {
        if (isset($leaveRow->reason) && strpos($leaveRow->reason, 'WFH:') === 0) {
            return true;
        }
        if (isset($leaveRow->type_name) && strtolower(trim($leaveRow->type_name)) === 'work from home') {
            return true;
        }

        return false;
    }
}

if (!function_exists('attendance_report_empty_summary_row')) {
    /**
     * @return array<string,float>
     */
    function attendance_report_empty_summary_row()
    {
        return array(
            'present'     => 0.0,
            'half'        => 0.0,
            'wfh'         => 0.0,
            'absent'      => 0.0,
            'leave'       => 0.0,
            'holiday'     => 0.0,
            'late'        => 0.0,
            'on_time'     => 0.0,
            'late_hours'  => 0.0,
            'extra_hours' => 0.0,
        );
    }
}

if (!function_exists('attendance_report_summary_row_has_data')) {
    function attendance_report_summary_row_has_data($row)
    {
        if (!$row) {
            return false;
        }

        return (float) $row->present_days > 0
            || (float) $row->half_days > 0
            || (float) $row->wfh_days > 0
            || (float) $row->absent_days > 0
            || (float) $row->leave_days > 0
            || (isset($row->holiday_days) && (float) $row->holiday_days > 0)
            || (isset($row->on_time_days) && (float) $row->on_time_days > 0)
            || (float) $row->late_days > 0
            || (isset($row->late_hours_decimal) && (float) $row->late_hours_decimal > 0)
            || (isset($row->extra_hours_decimal) && (float) $row->extra_hours_decimal > 0)
            || (isset($row->net_hours_decimal) && (float) $row->net_hours_decimal !== 0.0);
    }
}

if (!function_exists('attendance_report_load_user_timing_map')) {
    /**
     * Per-user office timing from employee shift, falling back to global settings.
     *
     * @param CI_DB_query_builder $db
     * @param array<int> $userIds
     * @param object|null $settings
     * @return array<int,array{office_start:string,office_end:string,grace_minutes:int,standard_hours:float}>
     */
    function attendance_report_load_user_timing_map($db, array $userIds, $settings = null)
    {
        $default = attendance_report_get_timing_settings($settings);
        $map = array();
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));

        foreach ($userIds as $uid) {
            $map[$uid] = $default;
        }

        if (empty($userIds) || !$db->table_exists('employees') || !$db->table_exists('shifts')) {
            return $map;
        }

        $employees = $db->select('e.user_id, e.shift_id')
            ->from('employees e')
            ->where_in('e.user_id', $userIds)
            ->get()
            ->result();

        $shiftIds = array();
        foreach ($employees as $emp) {
            if (!empty($emp->shift_id)) {
                $shiftIds[] = (int) $emp->shift_id;
            }
        }
        $shiftIds = array_values(array_unique(array_filter($shiftIds)));
        $shifts = array();
        if (!empty($shiftIds)) {
            $shiftRows = $db->where_in('id', $shiftIds)->get('shifts')->result();
            foreach ($shiftRows as $shiftRow) {
                $shifts[(int) $shiftRow->id] = $shiftRow;
            }
        }

        foreach ($employees as $emp) {
            $uid = (int) $emp->user_id;
            $shiftId = isset($emp->shift_id) ? (int) $emp->shift_id : 0;
            if ($shiftId <= 0 || !isset($shifts[$shiftId])) {
                continue;
            }
            $shift = $shifts[$shiftId];
            $officeStart = date('H:i', strtotime($shift->start_time));
            $officeEnd = date('H:i', strtotime($shift->end_time));
            $graceMinutes = (int) $shift->late_grace_period;
            $startTs = strtotime($shift->start_time);
            $endTs = strtotime($shift->end_time);
            $diff = $endTs - $startTs;
            $standardHours = ($diff > 0) ? round($diff / 3600, 1) : (float) $default['standard_hours'];
            $map[$uid] = array(
                'office_start'   => $officeStart,
                'office_end'     => $officeEnd,
                'grace_minutes'  => $graceMinutes,
                'standard_hours' => $standardHours,
            );
        }

        return $map;
    }
}

if (!function_exists('attendance_report_is_valid_punch_time')) {
    function attendance_report_is_valid_punch_time($raw)
    {
        $raw = trim((string) $raw);
        $empty = array('', '00:00:00', '0000-00-00 00:00:00');

        return !in_array($raw, $empty, true);
    }
}

if (!function_exists('attendance_report_extract_time_part')) {
    function attendance_report_extract_time_part($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        if (strpos($raw, ' ') !== false) {
            $parts = explode(' ', $raw);
            return isset($parts[1]) ? trim($parts[1]) : trim($raw);
        }

        return $raw;
    }
}

if (!function_exists('attendance_report_format_hours_hhmm')) {
    /**
     * @param float|int|string $hours Decimal hours
     * @return string HH:MM
     */
    function attendance_report_format_hours_hhmm($hours)
    {
        $totalMinutes = (int) round(max(0, (float) $hours) * 60);

        return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }
}

if (!function_exists('attendance_report_format_hours_hhmm_signed')) {
    /**
     * Signed HH:MM for net balance (negative = time deficit).
     *
     * @param float|int|string $hours
     * @return string e.g. -09:00, 02:30, 00:00
     */
    function attendance_report_format_hours_hhmm_signed($hours)
    {
        $hours = (float) $hours;
        $sign = '';
        if ($hours < 0) {
            $sign = '-';
            $hours = abs($hours);
        }
        $totalMinutes = (int) round($hours * 60);

        return $sign . sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }
}

if (!function_exists('attendance_report_compute_net_hours')) {
    /**
     * Net time balance: extra work minus late time.
     * Positive = surplus, negative = deficit (still owes time).
     */
    function attendance_report_compute_net_hours($extraHours, $lateHours)
    {
        return (float) $extraHours - (float) $lateHours;
    }
}

if (!function_exists('attendance_report_format_day_count')) {
    function attendance_report_format_day_count($value)
    {
        $value = (float) $value;
        if ($value <= 0) {
            return '0';
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}

if (!function_exists('attendance_report_get_timing_settings')) {
    /**
     * @param object|null $settings Setting_model instance
     * @return array{office_start:string,office_end:string,grace_minutes:int,standard_hours:float}
     */
    function attendance_report_get_timing_settings($settings = null)
    {
        $officeStart = '09:30';
        $officeEnd = '18:30';
        $graceMinutes = 15;
        $standardHours = 8.0;

        if ($settings) {
            try {
                $stVal = $settings->get_setting('attendance_start_time', $officeStart);
                if (is_string($stVal) && preg_match('/^\d{1,2}:\d{2}$/', $stVal)) {
                    $officeStart = $stVal;
                }
                $endVal = $settings->get_setting('attendance_end_time', $officeEnd);
                if (is_string($endVal) && preg_match('/^\d{1,2}:\d{2}$/', $endVal)) {
                    $officeEnd = $endVal;
                }
                $gmVal = $settings->get_setting('attendance_grace_minutes', $graceMinutes);
                if (is_numeric($gmVal)) {
                    $graceMinutes = (int) $gmVal;
                }
                $shVal = $settings->get_setting('attendance_standard_working_hours');
                if ($shVal === null || $shVal === '') {
                    $shVal = $settings->get_setting('standard_working_hours', $standardHours);
                }
                if (is_numeric($shVal)) {
                    $standardHours = (float) $shVal;
                }
            } catch (Exception $e) {
                // keep defaults
            }
        }

        return array(
            'office_start'    => $officeStart,
            'office_end'      => $officeEnd,
            'grace_minutes'   => $graceMinutes,
            'standard_hours'  => $standardHours,
        );
    }
}

if (!function_exists('attendance_report_is_working_day_date')) {
    function attendance_report_is_working_day_date($dateStr, array $holidayDates, $today)
    {
        $ts = strtotime((string) $dateStr);
        if ($ts === false || $dateStr > $today) {
            return false;
        }
        $dayOfWeek = (int) date('w', $ts);

        return $dayOfWeek !== 0
            && $dayOfWeek !== 6
            && !in_array($dateStr, $holidayDates, true);
    }
}

if (!function_exists('attendance_report_iterate_working_days')) {
    /**
     * @return array<int,string>
     */
    function attendance_report_iterate_working_days($from, $to, array $holidayDates, $today)
    {
        $days = array();
        $startTs = strtotime($from);
        $endTs = strtotime($to);

        while ($startTs !== false && $startTs <= $endTs) {
            $currentDate = date('Y-m-d', $startTs);
            if (attendance_report_is_working_day_date($currentDate, $holidayDates, $today)) {
                $days[] = $currentDate;
            }
            $startTs = strtotime('+1 day', $startTs);
        }

        return $days;
    }
}

if (!function_exists('attendance_report_mark_leave_days_for_user')) {
    /**
     * @param array<string,bool> $leaveDays
     * @param array<string,bool> $wfhDays
     */
    function attendance_report_mark_leave_days_for_user(
        array &$leaveDays,
        array &$wfhDays,
        $uid,
        $leaveStart,
        $leaveEnd,
        $from,
        $to,
        $today,
        array $holidayDates
    ) {
        $spanStart = max($from, substr((string) $leaveStart, 0, 10));
        $spanEnd = min($to, substr((string) $leaveEnd, 0, 10), $today);
        $cur = strtotime($spanStart);
        $endTs = strtotime($spanEnd);

        while ($cur !== false && $cur <= $endTs) {
            $dateKey = date('Y-m-d', $cur);
            if (attendance_report_is_working_day_date($dateKey, $holidayDates, $today)) {
                $leaveDays[$dateKey] = true;
            }
            $cur = strtotime('+1 day', $cur);
        }
    }
}

if (!function_exists('attendance_report_mark_wfh_days_for_user')) {
    /**
     * @param array<string,bool> $wfhDays
     */
    function attendance_report_mark_wfh_days_for_user(
        array &$wfhDays,
        $uid,
        $leaveStart,
        $leaveEnd,
        $from,
        $to,
        $today,
        array $holidayDates
    ) {
        $spanStart = max($from, substr((string) $leaveStart, 0, 10));
        $spanEnd = min($to, substr((string) $leaveEnd, 0, 10), $today);
        $cur = strtotime($spanStart);
        $endTs = strtotime($spanEnd);

        while ($cur !== false && $cur <= $endTs) {
            $dateKey = date('Y-m-d', $cur);
            if (attendance_report_is_working_day_date($dateKey, $holidayDates, $today)) {
                $wfhDays[$dateKey] = true;
            }
            $cur = strtotime('+1 day', $cur);
        }
    }
}

if (!function_exists('attendance_report_compute_punch_timing')) {
    /**
     * Late/on-time day counts and hour totals from punch data.
     *
     * @return array{late:int,on_time:int,late_hours:float,extra_hours:float}
     */
    function attendance_report_compute_punch_timing(
        $attDate,
        $cinRaw,
        $coutRaw,
        $totalHours,
        $officeStart,
        $graceMinutes,
        $standardHours,
        $officeEnd = null
    ) {
        $result = array(
            'late'        => 0,
            'on_time'     => 0,
            'late_hours'  => 0.0,
            'extra_hours' => 0.0,
        );

        if (!attendance_report_is_valid_punch_time($cinRaw)) {
            return $result;
        }

        $cinTime = attendance_report_extract_time_part($cinRaw);
        if (!preg_match('/^\d{1,2}:\d{2}/', $cinTime)) {
            return $result;
        }

        if ($officeEnd === null || $officeEnd === '') {
            $officeEnd = '18:30';
        }

        $officeTs = strtotime($attDate . ' ' . $officeStart . ':00');
        $graceTs = $officeTs !== false ? $officeTs + ((int) $graceMinutes * 60) : false;
        $officeEndTs = strtotime($attDate . ' ' . $officeEnd . ':00');
        $cinTs = strtotime($attDate . ' ' . $cinTime);

        $checkInOnTime = ($graceTs !== false && $cinTs !== false && $cinTs <= $graceTs);
        if ($graceTs !== false && $cinTs !== false && $cinTs > $graceTs) {
            $result['late'] = 1;
            $result['late_hours'] = ($cinTs - $graceTs) / 3600;
        }

        $checkOutOnTime = false;
        if (attendance_report_is_valid_punch_time($coutRaw)) {
            $coutTime = attendance_report_extract_time_part($coutRaw);
            if (preg_match('/^\d{1,2}:\d{2}/', $coutTime)) {
                $coutTs = strtotime($attDate . ' ' . $coutTime);
                if ($coutTs !== false && $officeEndTs !== false && $coutTs >= $officeEndTs) {
                    $checkOutOnTime = true;
                }
            }
        }

        // On time = check-in on/before grace AND check-out on/after office end.
        if ($checkInOnTime && $checkOutOnTime) {
            $result['on_time'] = 1;
        }

        $workedHours = 0.0;
        if ($totalHours !== null && $totalHours !== '' && is_numeric($totalHours) && (float) $totalHours > 0) {
            $workedHours = (float) $totalHours;
        } elseif (attendance_report_is_valid_punch_time($coutRaw)) {
            $coutTime = attendance_report_extract_time_part($coutRaw);
            if (preg_match('/^\d{1,2}:\d{2}/', $coutTime)) {
                $coutTs = strtotime($attDate . ' ' . $coutTime);
                if ($cinTs !== false && $coutTs !== false && $coutTs > $cinTs) {
                    $workedHours = ($coutTs - $cinTs) / 3600;
                }
            }
        }

        if (attendance_report_is_valid_punch_time($coutRaw)) {
            $coutTime = attendance_report_extract_time_part($coutRaw);
            if (preg_match('/^\d{1,2}:\d{2}/', $coutTime)) {
                $coutTs = strtotime($attDate . ' ' . $coutTime);
                if ($coutTs !== false && $officeEndTs !== false && $coutTs > $officeEndTs) {
                    $result['extra_hours'] = ($coutTs - $officeEndTs) / 3600;
                }
            }
        }

        return $result;
    }
}

if (!function_exists('attendance_report_format_clock_display')) {
    function attendance_report_format_clock_display($raw)
    {
        if (!attendance_report_is_valid_punch_time($raw)) {
            return '—';
        }
        $time = attendance_report_extract_time_part($raw);
        if (!preg_match('/^\d{1,2}:\d{2}/', $time)) {
            return '—';
        }

        return (strlen($time) >= 8) ? substr($time, 0, 8) : (substr($time, 0, 5) . ':00');
    }
}

if (!function_exists('attendance_report_resolve_late_display')) {
    /**
     * Human-readable late/on-time status for detail rows.
     *
     * @param array $timing from attendance_report_get_timing_settings()
     * @return array{late_status:string,late_label:string,late_minutes:int,grace_time:string,metrics:array}
     */
    function attendance_report_resolve_late_display($attDate, $cinRaw, $coutRaw, $totalHours, array $timing)
    {
        $officeStart = $timing['office_start'];
        $officeEnd = isset($timing['office_end']) ? $timing['office_end'] : '18:30';
        $graceMinutes = (int) $timing['grace_minutes'];
        $standardHours = (float) $timing['standard_hours'];

        $checkInTime = attendance_report_format_clock_display($cinRaw);
        $checkOutTime = attendance_report_format_clock_display($coutRaw);
        $metrics = attendance_report_compute_punch_timing(
            $attDate,
            $cinRaw,
            $coutRaw,
            $totalHours,
            $officeStart,
            $graceMinutes,
            $standardHours,
            $officeEnd
        );

        $graceTimeStr = '';
        $officeTs = strtotime($attDate . ' ' . $officeStart . ':00');
        if ($officeTs !== false) {
            $graceTimeStr = date('H:i', $officeTs + ($graceMinutes * 60));
        }

        $lateStatus = '';
        $lateLabel = '—';
        $lateMinutes = 0;

        if ($metrics['late'] === 1) {
            $lateStatus = 'late';
            $lateMinutes = (int) round($metrics['late_hours'] * 60);
            $lateLabel = 'Late: ' . $checkInTime . ' (' . $lateMinutes . ' min after ' . $graceTimeStr . ')';
        } elseif ($metrics['on_time'] === 1) {
            $lateStatus = 'on_time';
            $lateLabel = 'On Time: in by ' . $graceTimeStr . ', out from ' . $officeEnd;
        } elseif ($checkInTime !== '—') {
            $cinTime = attendance_report_extract_time_part($cinRaw);
            $cinTs = strtotime($attDate . ' ' . $cinTime);
            $graceTs = $officeTs !== false ? $officeTs + ($graceMinutes * 60) : false;
            if ($graceTs !== false && $cinTs !== false && $cinTs <= $graceTs) {
                if ($checkOutTime === '—') {
                    $lateLabel = 'Missing check-out (required from ' . $officeEnd . ')';
                } else {
                    $lateLabel = 'Early checkout: left before ' . $officeEnd;
                }
            }
        }

        return array(
            'late_status'  => $lateStatus,
            'late_label'   => $lateLabel,
            'late_minutes' => $lateMinutes,
            'grace_time'   => $graceTimeStr,
            'metrics'      => $metrics,
        );
    }
}

if (!function_exists('attendance_report_build_employee_summaries')) {
    /**
     * Day-by-day attendance summary aligned with the employee detail report.
     *
     * @param CI_DB_query_builder $db
     * @param array<int> $userIds
     * @param string $from
     * @param string $to
     * @param string $today
     * @param array $holidayDates
     * @param array $cols from attendance_report_resolve_columns()
     * @param object|null $settings
     * @return array{summaries:array,timing:array}
     */
    function attendance_report_build_employee_summaries(
        $db,
        array $userIds,
        $from,
        $to,
        $today,
        array $holidayDates,
        array $cols,
        $settings = null
    ) {
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));
        $summaries = array();
        foreach ($userIds as $uid) {
            $summaries[$uid] = attendance_report_empty_summary_row();
        }

        if (empty($userIds) || !$db->table_exists('attendance')) {
            return array(
                'summaries' => $summaries,
                'timing'    => attendance_report_get_timing_settings($settings),
            );
        }

        $userCol = $cols['user_col'];
        $dateCol = $cols['date_col'];
        $statusCol = $cols['status_col'];
        $fields = $cols['fields'];
        $timing = attendance_report_get_timing_settings($settings);
        $timingByUser = attendance_report_load_user_timing_map($db, $userIds, $settings);

        $checkInCol = null;
        $checkOutCol = null;
        if (in_array('punch_in', $fields, true)) {
            $checkInCol = 'punch_in';
        } elseif (in_array('check_in', $fields, true)) {
            $checkInCol = 'check_in';
        }
        if (in_array('punch_out', $fields, true)) {
            $checkOutCol = 'punch_out';
        } elseif (in_array('check_out', $fields, true)) {
            $checkOutCol = 'check_out';
        }
        $hasTotalHours = in_array('total_hours', $fields, true);

        $selectCols = array(
            "`$userCol` AS uid",
            "`$dateCol` AS d",
            "`$statusCol` AS st",
        );
        if ($checkInCol !== null) {
            $selectCols[] = "`$checkInCol` AS cin";
        }
        if ($checkOutCol !== null) {
            $selectCols[] = "`$checkOutCol` AS cout";
        }
        if ($hasTotalHours) {
            $selectCols[] = '`total_hours` AS th';
        }

        $db->select(implode(', ', $selectCols))
            ->from('attendance')
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->where_in("`$userCol`", $userIds)
            ->order_by($dateCol, 'ASC');
        apply_role_hierarchy_filter($db, $userCol);
        $attendanceRows = $db->get()->result();

        $attByUserDate = array();
        foreach ($attendanceRows as $row) {
            $uid = (int) $row->uid;
            $attDate = isset($row->d) ? (string) $row->d : '';
            if ($attDate === '') {
                continue;
            }
            if (strpos($attDate, ' ') !== false) {
                $attDate = trim(explode(' ', $attDate)[0]);
            }

            $cin = isset($row->cin) ? $row->cin : '';
            $cout = isset($row->cout) ? $row->cout : '';
            $status = isset($row->st) ? (string) $row->st : '';
            $hasPunch = attendance_report_is_valid_punch_time($cin) || attendance_report_is_valid_punch_time($cout);

            if (!isset($attByUserDate[$uid][$attDate])) {
                $attByUserDate[$uid][$attDate] = $row;
                continue;
            }

            $existing = $attByUserDate[$uid][$attDate];
            $existingStatus = isset($existing->st) ? trim((string) $existing->st) : '';
            $existingHasPunch = attendance_report_is_valid_punch_time(isset($existing->cin) ? $existing->cin : '')
                || attendance_report_is_valid_punch_time(isset($existing->cout) ? $existing->cout : '');

            // Prefer a row with meaningful status; otherwise prefer row with punch times.
            if ($existingStatus === '' && ($status !== '' || $hasPunch)) {
                $attByUserDate[$uid][$attDate] = $row;
            } elseif ($existingStatus !== '' && $status === '' && !$existingHasPunch && $hasPunch) {
                $attByUserDate[$uid][$attDate] = $row;
            }
        }

        $leaveDaysByUser = array();
        $wfhDaysByUser = array();
        if ($db->table_exists('leave_requests')) {
            $leaveRows = $db->select('lr.user_id, lr.start_date, lr.end_date, lr.reason, lt.name AS type_name')
                ->from('leave_requests lr')
                ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                ->where_in('lr.user_id', $userIds)
                ->where_in('lr.status', array('lead_approved', 'hr_approved'))
                ->where('lr.start_date <=', $to)
                ->where('lr.end_date >=', $from)
                ->get()
                ->result();

            foreach ($leaveRows as $leaveRow) {
                $uid = (int) $leaveRow->user_id;
                if (!isset($leaveDaysByUser[$uid])) {
                    $leaveDaysByUser[$uid] = array();
                }
                if (!isset($wfhDaysByUser[$uid])) {
                    $wfhDaysByUser[$uid] = array();
                }

                if (attendance_report_is_wfh_leave_row($leaveRow)) {
                    attendance_report_mark_wfh_days_for_user(
                        $wfhDaysByUser[$uid],
                        $uid,
                        $leaveRow->start_date,
                        $leaveRow->end_date,
                        $from,
                        $to,
                        $today,
                        $holidayDates
                    );
                } else {
                    attendance_report_mark_leave_days_for_user(
                        $leaveDaysByUser[$uid],
                        $wfhDaysByUser[$uid],
                        $uid,
                        $leaveRow->start_date,
                        $leaveRow->end_date,
                        $from,
                        $to,
                        $today,
                        $holidayDates
                    );
                }
            }
        }

        $workingDays = attendance_report_iterate_working_days($from, $to, $holidayDates, $today);

        foreach ($userIds as $uid) {
            $leaveDays = isset($leaveDaysByUser[$uid]) ? $leaveDaysByUser[$uid] : array();
            $wfhDays = isset($wfhDaysByUser[$uid]) ? $wfhDaysByUser[$uid] : array();
            $userAttendance = isset($attByUserDate[$uid]) ? $attByUserDate[$uid] : array();
            $userTiming = isset($timingByUser[$uid]) ? $timingByUser[$uid] : $timing;

            foreach ($workingDays as $day) {
                $row = isset($userAttendance[$day]) ? $userAttendance[$day] : null;
                $rawStatus = ($row && isset($row->st)) ? trim((string) $row->st) : '';
                $cinRaw = ($row && isset($row->cin)) ? $row->cin : '';
                $coutRaw = ($row && isset($row->cout)) ? $row->cout : '';
                $totalHours = ($row && isset($row->th)) ? $row->th : null;
                $hasIn = attendance_report_is_valid_punch_time($cinRaw);
                $hasOut = attendance_report_is_valid_punch_time($coutRaw);
                $statusKey = normalize_attendance_status_key($rawStatus);
                $isHoliday = in_array($day, $holidayDates, true);

                if (!empty($leaveDays[$day])) {
                    $summaries[$uid]['leave'] += 1;
                    continue;
                }

                if (!empty($wfhDays[$day]) || $statusKey === 'work_from_home') {
                    $summaries[$uid]['wfh'] += 1;
                    continue;
                }

                if ($statusKey === 'half_day' || $statusKey === 'early_leave') {
                    $summaries[$uid]['half'] += 1;
                    continue;
                }

                if ($statusKey === 'holiday' || ($isHoliday && !$hasIn && !$hasOut && $rawStatus === '')) {
                    $summaries[$uid]['holiday'] += 1;
                    continue;
                }

                $isOfficePresent = $hasIn || $hasOut
                    || in_array($statusKey, array('present', 'late'), true);

                if ($statusKey === 'absent' && ($hasIn || $hasOut)) {
                    $isOfficePresent = true;
                }

                if ($isOfficePresent) {
                    $summaries[$uid]['present'] += 1;

                    $timingMetrics = attendance_report_compute_punch_timing(
                        $day,
                        $cinRaw,
                        $coutRaw,
                        $totalHours,
                        $userTiming['office_start'],
                        $userTiming['grace_minutes'],
                        $userTiming['standard_hours'],
                        $userTiming['office_end']
                    );
                    $summaries[$uid]['late'] += $timingMetrics['late'];
                    $summaries[$uid]['on_time'] += $timingMetrics['on_time'];
                    $summaries[$uid]['late_hours'] += $timingMetrics['late_hours'];
                    $summaries[$uid]['extra_hours'] += $timingMetrics['extra_hours'];
                    continue;
                }

                if ($statusKey === 'absent' || $rawStatus === '') {
                    $summaries[$uid]['absent'] += 1;
                }
            }
        }

        return array(
            'summaries' => $summaries,
            'timing'    => $timing,
        );
    }
}

if (!function_exists('attendance_report_user_display_name')) {
    /**
     * Resolve display name from a user/employee label row (Reports user query).
     */
    function attendance_report_user_display_name($label, $uid = 0)
    {
        if ($label) {
            $empParts = array();
            if (isset($label->emp_first_name) && trim((string) $label->emp_first_name) !== '') {
                $empParts[] = trim((string) $label->emp_first_name);
            }
            if (isset($label->emp_middle_name) && trim((string) $label->emp_middle_name) !== '') {
                $empParts[] = trim((string) $label->emp_middle_name);
            }
            if (isset($label->emp_last_name) && trim((string) $label->emp_last_name) !== '') {
                $empParts[] = trim((string) $label->emp_last_name);
            }
            if (!empty($empParts)) {
                return trim(implode(' ', $empParts));
            }
            if (isset($label->emp_full_name) && trim((string) $label->emp_full_name) !== '') {
                return trim((string) $label->emp_full_name);
            }
            if (isset($label->emp_name) && trim((string) $label->emp_name) !== '') {
                return trim((string) $label->emp_name);
            }
            if (isset($label->full_name) && trim((string) $label->full_name) !== '') {
                return trim((string) $label->full_name);
            }
            if (isset($label->name) && trim((string) $label->name) !== '') {
                return trim((string) $label->name);
            }
            if (isset($label->email)) {
                return $label->email;
            }
        }

        return $uid ? ('User #' . (int) $uid) : 'Unknown';
    }
}

if (!function_exists('attendance_report_build_export_summaries')) {
    function attendance_report_build_export_summaries($db, array $userIds, $from, $to, $settings = null)
    {
        $holidayData = attendance_report_holidays_for_range($db, $from, $to);
        $cols = attendance_report_resolve_columns($db);
        $built = attendance_report_build_employee_summaries(
            $db,
            $userIds,
            $from,
            $to,
            date('Y-m-d'),
            $holidayData['holiday_dates'],
            $cols,
            $settings
        );

        return $built['summaries'];
    }
}

if (!function_exists('attendance_report_fetch_notes_map')) {
    /**
     * @return array<int,array<string,array<int,string>>>
     */
    function attendance_report_fetch_notes_map($db, $userCol, $dateCol, $from, $to)
    {
        $attendanceNotes = array();
        if (!schema_table_has_column($db, 'attendance', 'notes')) {
            return $attendanceNotes;
        }

        $db->select("`$userCol` AS uid, `$dateCol` AS d, `notes`")
            ->from('attendance')
            ->where("`$dateCol` >=", $from)
            ->where("`$dateCol` <=", $to)
            ->where('`notes` IS NOT NULL', null, false)
            ->where('`notes` !=', '')
            ->where('TRIM(`notes`) !=', '')
            ->order_by($dateCol, 'ASC')
            ->order_by($userCol, 'ASC');
        apply_role_hierarchy_filter($db, $userCol);
        $notesQuery = $db->get();

        if ($notesQuery && $notesQuery->num_rows() > 0) {
            foreach ($notesQuery->result() as $noteRow) {
                $uid = (int) $noteRow->uid;
                $attDate = isset($noteRow->d) ? (string) $noteRow->d : '';
                $notes = isset($noteRow->notes) ? trim((string) $noteRow->notes) : '';
                if ($attDate === '' || $notes === '') {
                    continue;
                }
                if (strpos($attDate, ' ') !== false) {
                    $attDate = trim(explode(' ', $attDate)[0]);
                }
                if (!isset($attendanceNotes[$uid])) {
                    $attendanceNotes[$uid] = array();
                }
                if (!isset($attendanceNotes[$uid][$attDate])) {
                    $attendanceNotes[$uid][$attDate] = array();
                }
                $attendanceNotes[$uid][$attDate][] = $notes;
            }
        }

        return $attendanceNotes;
    }
}

if (!function_exists('attendance_report_summary_output_rows')) {
    function attendance_report_summary_output_rows(array $allUsers, array $summary, callable $getName, $totalWorkingDays)
    {
        $rowsOut = array();
        foreach ($allUsers as $uid => $user) {
            $s = isset($summary[$uid]) ? $summary[$uid] : attendance_report_empty_summary_row();
            $o = new stdClass();
            $o->user_id = (int) $uid;
            $o->name = $getName((int) $uid);
            $o->present_days = attendance_report_format_day_count($s['present']);
            $o->half_days = attendance_report_format_day_count($s['half']);
            $o->wfh_days = attendance_report_format_day_count($s['wfh']);
            $o->absent_days = attendance_report_format_day_count($s['absent']);
            $o->leave_days = attendance_report_format_day_count($s['leave']);
            $o->holiday_days = attendance_report_format_day_count(isset($s['holiday']) ? $s['holiday'] : 0);
            $o->late_days = attendance_report_format_day_count($s['late']);
            $o->on_time_days = attendance_report_format_day_count($s['on_time']);
            $o->late_hours = attendance_report_format_hours_hhmm($s['late_hours']);
            $o->extra_hours = attendance_report_format_hours_hhmm($s['extra_hours']);
            $o->late_hours_decimal = (float) $s['late_hours'];
            $o->extra_hours_decimal = (float) $s['extra_hours'];
            $netDecimal = attendance_report_compute_net_hours($s['extra_hours'], $s['late_hours']);
            $o->net_hours = attendance_report_format_hours_hhmm_signed($netDecimal);
            $o->net_hours_decimal = $netDecimal;
            $o->total_working_days = $totalWorkingDays;
            $rowsOut[] = $o;
        }
        usort($rowsOut, function ($a, $b) {
            $lateA = (float) $a->late_days;
            $lateB = (float) $b->late_days;
            if ($lateA !== $lateB) {
                return ($lateB <=> $lateA);
            }

            return strcmp($a->name, $b->name);
        });

        return $rowsOut;
    }
}
