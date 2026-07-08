<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('rewards_automation_attendance_meta')) {
    function rewards_automation_attendance_meta($db)
    {
        $CI =& get_instance();
        if (!function_exists('attendance_punch_has_column')) {
            $CI->load->helper('attendance_punch');
        }
        if (!function_exists('schema_table_has_column')) {
            $CI->load->helper('schema_columns');
        }
        $has = function ($field) use ($db) {
            return attendance_punch_has_column($db, $field);
        };
        $cols = attendance_punch_resolve_time_columns($has);
        $cols['col_date'] = attendance_punch_resolve_date_column($has);

        return $cols;
    }
}

if (!function_exists('rewards_automation_attendance_row_times')) {
    function rewards_automation_attendance_row_times($row, array $cols)
    {
        return attendance_punch_read_existing_times(
            $row,
            $cols['col_in'],
            $cols['col_out'],
            $cols['hasPunchIn'],
            $cols['hasCheckIn'],
            $cols['hasPunchOut'],
            $cols['hasCheckOut']
        );
    }
}

if (!function_exists('rewards_automation_is_workday')) {
    function rewards_automation_is_workday($db, $date)
    {
        if (function_exists('attendance_punch_active_holiday')) {
            $holiday = attendance_punch_active_holiday($db, $date);
            if ($holiday) {
                return false;
            }
        }
        $dow = (int) date('N', strtotime($date));
        return $dow >= 1 && $dow <= 5;
    }
}

if (!function_exists('rewards_automation_get_attendance_row')) {
    function rewards_automation_get_attendance_row($db, $user_id, $date, array $cols)
    {
        return $db->where('user_id', (int) $user_id)
            ->where($cols['col_date'], $date)
            ->limit(1)
            ->get('attendance')
            ->row();
    }
}

if (!function_exists('rewards_automation_active_user_ids')) {
    function rewards_automation_active_user_ids($db)
    {
        if (!$db->table_exists('users')) {
            return array();
        }
        $db->select('id')->from('users');
        if (schema_table_has_column($db, 'users', 'status')) {
            $db->where('status', 'active');
        }
        $rows = $db->get()->result();
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r->id;
        }
        return $ids;
    }
}

if (!function_exists('rewards_automation_user_excused_for_date')) {
    /**
     * Skip attendance penalties when user has approved leave/WFH for the date.
     */
    function rewards_automation_user_excused_for_date($db, $user_id, $date)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || $date === '') {
            return false;
        }

        if ($db->table_exists('leave_requests')) {
            $approved = array('approved', 'lead_approved', 'hr_approved');
            $count = $db->where('user_id', $user_id)
                ->where('start_date <=', $date)
                ->where('end_date >=', $date)
                ->where_in('status', $approved)
                ->count_all_results('leave_requests');
            if ($count > 0) {
                return true;
            }
        }

        if ($db->table_exists('attendance')) {
            $cols = rewards_automation_attendance_meta($db);
            $row = rewards_automation_get_attendance_row($db, $user_id, $date, $cols);
            if ($row && isset($row->status)) {
                $status = strtolower(trim((string) $row->status));
                if (in_array($status, array('work_from_home', 'wfh', 'on_leave', 'leave', 'holiday'), true)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('rewards_automation_after_checkin')) {
    /**
     * Penalize missed checkouts on prior workdays when user checks in today.
     */
    function rewards_automation_after_checkin($db, $user_id, $today = null)
    {
        if (!$db->table_exists('attendance')) {
            return;
        }
        $CI =& get_instance();
        $CI->load->helper(array('rewards', 'attendance_punch', 'schema_columns'));
        $today = $today ?: date('Y-m-d');
        $cols = rewards_automation_attendance_meta($db);

        for ($i = 1; $i <= 7; $i++) {
            $date = date('Y-m-d', strtotime($today . ' -' . $i . ' days'));
            if (!rewards_automation_is_workday($db, $date)) {
                continue;
            }
            rewards_automation_penalize_missed_checkout($db, $user_id, $date, $cols);
        }
    }
}

if (!function_exists('rewards_automation_penalize_missed_checkout')) {
    function rewards_automation_penalize_missed_checkout($db, $user_id, $date, ?array $cols = null)
    {
        if (rewards_automation_user_excused_for_date($db, $user_id, $date)) {
            return;
        }
        $cols = $cols ?: rewards_automation_attendance_meta($db);
        $row = rewards_automation_get_attendance_row($db, $user_id, $date, $cols);
        if (!$row) {
            return;
        }
        $times = rewards_automation_attendance_row_times($row, $cols);
        if ($times['cin'] === '') {
            return;
        }
        $missedCheckout = false;
        if ($times['cout'] === '') {
            $missedCheckout = true;
        } else {
            $checkoutTs = strtotime($times['cout']);
            $cutoffTs = strtotime($date . ' 11:00:00');
            if ($checkoutTs !== false && $cutoffTs !== false && $checkoutTs < $cutoffTs) {
                $missedCheckout = true;
            }
        }
        if (!$missedCheckout) {
            return;
        }
        reward_engine_dispatch('attendance_penalty', array(
            'user_id' => (int) $user_id,
            'source_module' => 'attendance',
            'source_record_id' => (int) $row->id,
            'reference_label' => 'Missed checkout on ' . $date,
            'payload' => array('penalty_type' => 'missed_checkout'),
            'occurred_at' => $date . ' 23:59:59',
            'period_key' => substr($date, 0, 7),
        ));
    }
}

if (!function_exists('rewards_automation_penalize_missed_checkin')) {
    function rewards_automation_penalize_missed_checkin($db, $user_id, $date, ?array $cols = null)
    {
        if (rewards_automation_user_excused_for_date($db, $user_id, $date)) {
            return;
        }
        $cols = $cols ?: rewards_automation_attendance_meta($db);
        $row = rewards_automation_get_attendance_row($db, $user_id, $date, $cols);
        $recordId = $row ? (int) $row->id : (int) crc32('missed_in|' . $user_id . '|' . $date);
        if ($row) {
            $times = rewards_automation_attendance_row_times($row, $cols);
            if ($times['cin'] !== '') {
                return;
            }
        }
        reward_engine_dispatch('attendance_penalty', array(
            'user_id' => (int) $user_id,
            'source_module' => 'attendance',
            'source_record_id' => $recordId,
            'reference_label' => 'Missed check-in on ' . $date,
            'payload' => array('penalty_type' => 'missed_checkin'),
            'occurred_at' => $date . ' 23:59:59',
            'period_key' => substr($date, 0, 7),
        ));
    }
}

if (!function_exists('rewards_automation_daily_attendance_penalties')) {
    /**
     * Cron: penalize yesterday missed check-ins / checkouts for all active users.
     *
     * @return array counts
     */
    function rewards_automation_daily_attendance_penalties($db, $date = null)
    {
        $CI =& get_instance();
        $CI->load->helper(array('rewards', 'attendance_punch', 'schema_columns'));
        $date = $date ?: date('Y-m-d', strtotime('-1 day'));
        if (!rewards_automation_is_workday($db, $date)) {
            return array('missed_checkin' => 0, 'missed_checkout' => 0, 'skipped' => true);
        }
        $cols = rewards_automation_attendance_meta($db);
        $missedIn = 0;
        $missedOut = 0;
        foreach (rewards_automation_active_user_ids($db) as $uid) {
            if (rewards_automation_user_excused_for_date($db, $uid, $date)) {
                continue;
            }
            $row = rewards_automation_get_attendance_row($db, $uid, $date, $cols);
            if (!$row) {
                reward_engine_dispatch('attendance_penalty', array(
                    'user_id' => $uid,
                    'source_module' => 'attendance',
                    'source_record_id' => (int) crc32('missed_in|' . $uid . '|' . $date),
                    'reference_label' => 'Missed check-in on ' . $date,
                    'payload' => array('penalty_type' => 'missed_checkin'),
                    'occurred_at' => $date . ' 23:59:59',
                    'period_key' => substr($date, 0, 7),
                ));
                $missedIn++;
                continue;
            }
            $times = rewards_automation_attendance_row_times($row, $cols);
            if ($times['cin'] === '') {
                rewards_automation_penalize_missed_checkin($db, $uid, $date, $cols);
                $missedIn++;
            } else {
                $checkoutTs = $times['cout'] !== '' ? strtotime($times['cout']) : false;
                $cutoffTs = strtotime($date . ' 11:00:00');
                if ($times['cout'] === '' || ($checkoutTs !== false && $cutoffTs !== false && $checkoutTs < $cutoffTs)) {
                    rewards_automation_penalize_missed_checkout($db, $uid, $date, $cols);
                    $missedOut++;
                }
            }
        }
        return array('missed_checkin' => $missedIn, 'missed_checkout' => $missedOut, 'date' => $date);
    }
}

if (!function_exists('rewards_automation_consistency_monthly')) {
    /**
     * Cron: award monthly consistency bonuses for a calendar month (Y-m).
     *
     * @return array stats
     */
    function rewards_automation_consistency_monthly($db, $yearMonth = null)
    {
        $CI =& get_instance();
        $CI->load->helper(array('rewards', 'schema_columns', 'attendance_punch'));
        $yearMonth = $yearMonth ?: date('Y-m', strtotime('first day of last month'));
        $start = $yearMonth . '-01';
        $end = date('Y-m-t', strtotime($start));
        $cols = rewards_automation_attendance_meta($db);

        $selfUpdates = 0;
        $onTime = 0;
        $noMissedCheckout = 0;

        foreach (rewards_automation_active_user_ids($db) as $uid) {
            $activityDays = 0;
            if ($db->table_exists('daily_work_logs')) {
                $activityDays = (int) $db->select('COUNT(DISTINCT work_date) AS c', false)
                    ->from('daily_work_logs')
                    ->where('user_id', $uid)
                    ->where('work_date >=', $start)
                    ->where('work_date <=', $end)
                    ->get()->row()->c;
            }
            if ($activityDays >= 20) {
                reward_engine_dispatch('consistency_review', array(
                    'user_id' => $uid,
                    'source_module' => 'rewards_cron',
                    'source_record_id' => (int) crc32('self_updates|' . $uid . '|' . $yearMonth),
                    'reference_label' => 'Self updates 20+ days in ' . $yearMonth,
                    'payload' => array('streak_type' => 'self_updates', 'days' => $activityDays),
                    'period_key' => $yearMonth,
                    'occurred_at' => $end . ' 23:59:59',
                ));
                $selfUpdates++;
            }

            $ontimeDays = 0;
            $missedCheckoutDays = 0;
            $daysWithCheckin = 0;
            $workdays = 0;
            $cursor = strtotime($start);
            $endTs = strtotime($end);
            while ($cursor <= $endTs) {
                $d = date('Y-m-d', $cursor);
                if (rewards_automation_is_workday($db, $d)) {
                    $workdays++;
                    if (rewards_automation_user_excused_for_date($db, $uid, $d)) {
                        $cursor = strtotime('+1 day', $cursor);
                        continue;
                    }
                    $row = rewards_automation_get_attendance_row($db, $uid, $d, $cols);
                    if ($row) {
                        $times = rewards_automation_attendance_row_times($row, $cols);
                        $status = isset($row->status) ? (string) $row->status : 'present';
                        if ($times['cin'] !== '') {
                            $daysWithCheckin++;
                        }
                        if ($times['cin'] !== '' && !in_array($status, array('late', 'absent'), true)) {
                            $ontimeDays++;
                        }
                        if ($times['cin'] !== '' && $times['cout'] === '') {
                            $missedCheckoutDays++;
                        }
                    }
                }
                $cursor = strtotime('+1 day', $cursor);
            }

            if ($ontimeDays >= 20) {
                reward_engine_dispatch('consistency_review', array(
                    'user_id' => $uid,
                    'source_module' => 'rewards_cron',
                    'source_record_id' => (int) crc32('on_time|' . $uid . '|' . $yearMonth),
                    'reference_label' => 'On-time 20+ days in ' . $yearMonth,
                    'payload' => array('streak_type' => 'on_time', 'days' => $ontimeDays),
                    'period_key' => $yearMonth,
                    'occurred_at' => $end . ' 23:59:59',
                ));
                $onTime++;
            }

            if ($daysWithCheckin > 0 && $missedCheckoutDays === 0) {
                reward_engine_dispatch('consistency_review', array(
                    'user_id' => $uid,
                    'source_module' => 'rewards_cron',
                    'source_record_id' => (int) crc32('no_missed_co|' . $uid . '|' . $yearMonth),
                    'reference_label' => 'No missed checkout in ' . $yearMonth,
                    'payload' => array('streak_type' => 'no_missed_checkout'),
                    'period_key' => $yearMonth,
                    'occurred_at' => $end . ' 23:59:59',
                ));
                $noMissedCheckout++;
            }
        }

        return array(
            'month' => $yearMonth,
            'self_updates' => $selfUpdates,
            'on_time' => $onTime,
            'no_missed_checkout' => $noMissedCheckout,
        );
    }
}

if (!function_exists('rewards_automation_leave_timely_cutoff')) {
    function rewards_automation_leave_timely_cutoff($db)
    {
        $cutoff = '09:00:00';
        if ($db->table_exists('settings')) {
            $CI =& get_instance();
            if (!function_exists('schema_table_has_column')) {
                $CI->load->helper('schema_columns');
            }

            $key_column = null;
            $value_column = null;
            if (schema_table_has_column($db, 'settings', 'key')) {
                $key_column = 'key';
            } elseif (schema_table_has_column($db, 'settings', 'setting_key')) {
                $key_column = 'setting_key';
            }

            if (schema_table_has_column($db, 'settings', 'value')) {
                $value_column = 'value';
            } elseif (schema_table_has_column($db, 'settings', 'setting_value')) {
                $value_column = 'setting_value';
            }

            if ($key_column !== null && $value_column !== null) {
                $row = $db->select($value_column)
                    ->from('settings')
                    ->where($key_column, 'spl_leave_timely_cutoff')
                    ->limit(1)
                    ->get()
                    ->row();
                if ($row && isset($row->{$value_column}) && trim((string) $row->{$value_column}) !== '') {
                    $cutoff = trim((string) $row->{$value_column});
                }
            }
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $cutoff)) {
            $cutoff .= ':00';
        }
        return $cutoff;
    }
}

if (!function_exists('rewards_automation_leave_is_wfh')) {
    function rewards_automation_leave_is_wfh($db, $leave)
    {
        if (!$leave) {
            return false;
        }
        if (isset($leave->reason) && strpos((string) $leave->reason, 'WFH:') === 0) {
            return true;
        }
        if (!$db->table_exists('leave_types') || empty($leave->type_id)) {
            return false;
        }
        $leave_type = $db->select('name')->from('leave_types')->where('id', (int) $leave->type_id)->limit(1)->get()->row();
        return ($leave_type && strtolower(trim((string) $leave_type->name)) === 'work from home');
    }
}

if (!function_exists('rewards_automation_classify_leave_outcome')) {
    /**
     * @return string preapproved|timely|late_intimation|''
     */
    function rewards_automation_classify_leave_outcome($db, $leave)
    {
        if (!$leave || empty($leave->created_at) || empty($leave->start_date)) {
            return '';
        }
        $createdDate = date('Y-m-d', strtotime((string) $leave->created_at));
        $startDate = (string) $leave->start_date;
        if ($startDate > $createdDate) {
            return 'preapproved';
        }
        if ($startDate === $createdDate) {
            $cutoff = rewards_automation_leave_timely_cutoff($db);
            $createdTime = date('H:i:s', strtotime((string) $leave->created_at));
            if ($createdTime <= $cutoff) {
                return 'timely';
            }
            return 'late_intimation';
        }
        return '';
    }
}

if (!function_exists('rewards_automation_after_daily_activity_saved')) {
    function rewards_automation_after_daily_activity_saved($db, $user_id, $log_id, $work_date)
    {
        $user_id = (int) $user_id;
        $log_id = (int) $log_id;
        if ($user_id <= 0 || $log_id <= 0 || !$db->table_exists('reward_rules')) {
            return;
        }
        $CI =& get_instance();
        $CI->load->helper('rewards');
        if (!function_exists('reward_engine_dispatch')) {
            return;
        }
        $label = 'Daily activity';
        if ($work_date !== '') {
            $label .= ' ' . $work_date;
        }
        reward_engine_dispatch('daily_activity_logged', array(
            'user_id' => $user_id,
            'actor_id' => $user_id,
            'source_module' => 'daily_activity',
            'source_record_id' => $log_id,
            'reference_label' => $label,
            'occurred_at' => date('Y-m-d H:i:s'),
            'payload' => array(),
        ));
    }
}

if (!function_exists('rewards_automation_on_leave_approved')) {
    function rewards_automation_on_leave_approved($db, $leave_id, $approved_by = 0)
    {
        $leave_id = (int) $leave_id;
        if ($leave_id <= 0 || !$db->table_exists('leave_requests') || !$db->table_exists('reward_rules')) {
            return;
        }
        $leave = $db->where('id', $leave_id)->limit(1)->get('leave_requests')->row();
        if (!$leave) {
            return;
        }
        $approved_statuses = array('approved', 'lead_approved', 'hr_approved');
        if (!in_array((string) $leave->status, $approved_statuses, true)) {
            return;
        }
        $outcome = rewards_automation_classify_leave_outcome($db, $leave);
        if ($outcome === '') {
            return;
        }
        $CI =& get_instance();
        $CI->load->helper('rewards');
        if (!function_exists('reward_engine_dispatch')) {
            return;
        }
        $user_id = (int) $leave->user_id;
        $actor_id = (int) $approved_by > 0 ? (int) $approved_by : $user_id;
        $is_wfh = rewards_automation_leave_is_wfh($db, $leave);
        $type_label = $is_wfh ? 'WFH' : 'Leave';
        $base = array(
            'user_id' => $user_id,
            'actor_id' => $actor_id,
            'source_module' => 'leave_requests',
            'source_record_id' => $leave_id,
            'occurred_at' => date('Y-m-d H:i:s'),
            'period_key' => substr((string) $leave->start_date, 0, 7),
        );
        if ($outcome === 'preapproved') {
            reward_engine_dispatch('leave_approved', array_merge($base, array(
                'reference_label' => 'Preapproved ' . $type_label . ' ' . $leave->start_date,
                'payload' => array('leave_outcome' => 'preapproved'),
            )));
            return;
        }
        if ($outcome === 'timely') {
            reward_engine_dispatch('leave_approved', array_merge($base, array(
                'reference_label' => 'Timely ' . $type_label . ' intimation ' . $leave->start_date,
                'payload' => array('leave_outcome' => 'timely'),
            )));
        }
    }
}

if (!function_exists('rewards_automation_on_leave_rejected')) {
    function rewards_automation_on_leave_rejected($db, $leave_id, $rejected_by = 0)
    {
        $leave_id = (int) $leave_id;
        if ($leave_id <= 0 || !$db->table_exists('leave_requests') || !$db->table_exists('reward_rules')) {
            return;
        }
        $leave = $db->where('id', $leave_id)->limit(1)->get('leave_requests')->row();
        if (!$leave || (string) $leave->status !== 'rejected') {
            return;
        }
        $outcome = rewards_automation_classify_leave_outcome($db, $leave);
        $CI =& get_instance();
        $CI->load->helper('rewards');
        if (!function_exists('reward_engine_dispatch')) {
            return;
        }
        $user_id = (int) $leave->user_id;
        $actor_id = (int) $rejected_by > 0 ? (int) $rejected_by : $user_id;
        $is_wfh = rewards_automation_leave_is_wfh($db, $leave);
        $type_label = $is_wfh ? 'WFH' : 'Leave';
        $base = array(
            'user_id' => $user_id,
            'actor_id' => $actor_id,
            'source_module' => 'leave_requests',
            'source_record_id' => $leave_id,
            'occurred_at' => date('Y-m-d H:i:s'),
            'period_key' => substr((string) $leave->start_date, 0, 7),
        );
        if ($outcome === 'late_intimation') {
            reward_engine_dispatch('leave_penalty', array_merge($base, array(
                'reference_label' => 'Late ' . $type_label . ' intimation rejected ' . $leave->start_date,
                'payload' => array('leave_outcome' => 'late_intimation'),
            )));
            return;
        }
        if ($outcome === 'preapproved') {
            reward_engine_dispatch('leave_penalty', array_merge($base, array(
                'reference_label' => 'Rejected preapproved ' . $type_label . ' request ' . $leave->start_date,
                'payload' => array('leave_outcome' => 'rejected_unapproved'),
            )));
        }
    }
}
