<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('normalize_attendance_status_key')) {
    /**
     * Normalize attendance status strings from DB, forms, or legacy data.
     */
    function normalize_attendance_status_key($status) {
        $st = strtolower(trim((string)$status));
        $aliases = [
            'half day'       => 'half_day',
            'wfh'            => 'work_from_home',
            'work from home' => 'work_from_home',
            'early leave'    => 'early_leave',
        ];
        return isset($aliases[$st]) ? $aliases[$st] : $st;
    }
}

if (!function_exists('attendance_status_report_bucket')) {
    /**
     * Map DB status to report summary bucket: present, half, wfh, absent.
     */
    function attendance_status_report_bucket($status) {
        $st = normalize_attendance_status_key($status);
        switch ($st) {
            case 'present':
            case 'late':
                return 'present';
            case 'half_day':
            case 'early_leave':
                return 'half';
            case 'work_from_home':
                return 'wfh';
            case 'absent':
                return 'absent';
            case 'holiday':
                return 'holiday';
            case 'incomplete':
                return 'incomplete';
            default:
                return 'other';
        }
    }
}

if (!function_exists('attendance_status_display_label')) {
    function attendance_status_display_label($status, $holidayName = null) {
        $st = normalize_attendance_status_key($status);
        switch ($st) {
            case 'present':
                $label = 'Present';
                if ($holidayName) {
                    $label .= ' (' . $holidayName . ')';
                }
                return $label;
            case 'late':
                return 'Late';
            case 'early_leave':
                return 'Early Leave';
            case 'half_day':
                return 'Half Day';
            case 'work_from_home':
                return 'Work From Home';
            case 'absent':
                return 'Absent';
            case 'holiday':
                return $holidayName ? ('Holiday: ' . $holidayName) : 'Holiday';
            case 'incomplete':
                return 'Incomplete';
            case '':
                return '—';
            default:
                return ucwords(str_replace('_', ' ', $st));
        }
    }
}

if (!function_exists('apply_attendance_status_to_summary')) {
    function apply_attendance_status_to_summary(array &$summary, $uid, $status, $count) {
        $uid = (int)$uid;
        $cnt = (float)$count;
        if (!isset($summary[$uid])) {
            $summary[$uid] = [
                'present' => 0.0, 'half' => 0.0, 'wfh' => 0.0, 'absent' => 0.0,
                'leave' => 0.0, 'late' => 0.0, 'on_time' => 0.0,
                'late_hours' => 0.0, 'extra_hours' => 0.0,
            ];
        }
        switch (attendance_status_report_bucket($status)) {
            case 'present':
                $summary[$uid]['present'] += $cnt;
                break;
            case 'half':
                $summary[$uid]['half'] += $cnt;
                break;
            case 'wfh':
                $summary[$uid]['wfh'] += $cnt;
                break;
            case 'absent':
                $summary[$uid]['absent'] += $cnt;
                break;
            default:
                break;
        }
    }
}

if (!function_exists('attendance_status_badge_meta')) {
    /**
     * CSS class, icon, and label for report status badges.
     *
     * @return array{class: string, icon: string, label: string}
     */
    function attendance_status_badge_meta($status) {
        $st = normalize_attendance_status_key($status);
        $map = [
            'present'        => ['present', 'bi-check-circle', 'Present'],
            'late'           => ['late', 'bi-alarm', 'Late'],
            'early_leave'    => ['early_leave', 'bi-door-open', 'Early Leave'],
            'half_day'       => ['half_day', 'bi-clock', 'Half Day'],
            'absent'         => ['absent', 'bi-x-circle', 'Absent'],
            'work_from_home' => ['work_from_home', 'bi-house', 'Work From Home'],
            'incomplete'     => ['incomplete', 'bi-hourglass-split', 'Incomplete'],
            'holiday'        => ['holiday', 'bi-calendar-event', 'Holiday'],
        ];
        if (isset($map[$st])) {
            return [
                'class' => $map[$st][0],
                'icon'  => $map[$st][1],
                'label' => $map[$st][2],
            ];
        }
        if (strpos($st, 'holiday') !== false) {
            return ['holiday', 'bi-calendar-event', attendance_status_display_label($status)];
        }
        return [
            'class' => '',
            'icon'  => 'bi-question-circle',
            'label' => $status !== '' ? attendance_status_display_label($status) : 'Unknown',
        ];
    }
}
