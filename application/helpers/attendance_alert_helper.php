<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('attendance_alert_setting_enabled')) {
    /**
     * @param Setting_model|null $settings
     * @param string             $kind checkin|checkout
     * @return bool
     */
    function attendance_alert_setting_enabled($settings, $kind)
    {
        if (!$settings || !is_object($settings)) {
            return false;
        }
        $key = ($kind === 'checkout')
            ? 'attendance_checkout_alert_enabled'
            : 'attendance_checkin_alert_enabled';
        $val = $settings->get_setting($key, 'no');
        return strtolower(trim((string) $val)) === 'yes';
    }
}

if (!function_exists('attendance_alert_user_enabled')) {
    /**
     * @param object $user users row
     * @param string $kind checkin|checkout
     * @return bool
     */
    function attendance_alert_user_enabled($user, $kind)
    {
        if (!$user) {
            return false;
        }
        $col = ($kind === 'checkout') ? 'google_alert_checkout' : 'google_alert_checkin';
        if (!isset($user->$col)) {
            return false;
        }
        return (int) $user->$col === 1;
    }
}

if (!function_exists('attendance_alert_already_queued')) {
    /**
     * @param CI_DB_query_builder $db
     * @param int                 $user_id
     * @param string              $type
     * @param string              $plan_date Y-m-d
     * @return bool
     */
    function attendance_alert_already_queued($db, $user_id, $type, $plan_date)
    {
        if (!$db->table_exists('reminders')) {
            return false;
        }
        $start = $plan_date . ' 00:00:00';
        $end = $plan_date . ' 23:59:59';
        $n = (int) $db->from('reminders')
            ->where('user_id', (int) $user_id)
            ->where('type', $type)
            ->where('send_at >=', $start)
            ->where('send_at <=', $end)
            ->count_all_results();
        return $n > 0;
    }
}

if (!function_exists('attendance_alert_plan_daily')) {
    /**
     * Queue Google Calendar reminders for check-in / check-out (once per user per day).
     *
     * @param Reminder_model      $model
     * @param CI_DB_query_builder $db
     * @param string|null         $plan_date Y-m-d
     * @return array{checkin:int,checkout:int,skipped:int}
     */
    function attendance_alert_plan_daily($model, $db, $plan_date = null)
    {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        $CI->load->helper(array('reminders_user', 'attendance_punch', 'schema_columns'));

        $plan_date = $plan_date ? $plan_date : date('Y-m-d');
        $stats = array('checkin' => 0, 'checkout' => 0, 'skipped' => 0);

        $checkin_on = attendance_alert_setting_enabled($CI->settings, 'checkin');
        $checkout_on = attendance_alert_setting_enabled($CI->settings, 'checkout');
        if (!$checkin_on && !$checkout_on) {
            return $stats;
        }

        $start_time = trim((string) $CI->settings->get_setting('attendance_start_time', '09:30'));
        $end_time = trim((string) $CI->settings->get_setting('attendance_end_time', '18:30'));
        $checkin_before = max(0, (int) $CI->settings->get_setting('attendance_checkin_alert_minutes_before', '5'));
        $checkout_before = max(0, (int) $CI->settings->get_setting('attendance_checkout_alert_minutes_before', '5'));

        $has_column = function ($field) use ($db) {
            return schema_table_has_column($db, 'attendance', $field);
        };

        $select = array('id', 'email', 'status');
        if (schema_table_has_column($db, 'users', 'google_alert_checkin')) {
            $select[] = 'google_alert_checkin';
        }
        if (schema_table_has_column($db, 'users', 'google_alert_checkout')) {
            $select[] = 'google_alert_checkout';
        }
        foreach (reminders_user_select_fields($db) as $frag) {
            if (strpos($frag, ' AS ') === false && !in_array($frag, $select, true)) {
                $select[] = $frag;
            } elseif (strpos($frag, ' AS ') !== false) {
                $select[] = $frag;
            }
        }

        $users = $db->select(implode(',', $select), false)
            ->from('users')
            ->where('status !=', 'inactive')
            ->get()
            ->result();

        foreach ($users as $u) {
            $uid = isset($u->id) ? (int) $u->id : 0;
            if ($uid <= 0) {
                continue;
            }
            $email = isset($u->email) ? trim((string) $u->email) : '';
            if ($email === '') {
                $stats['skipped']++;
                continue;
            }

            $name = reminders_user_label_from_row($u, $email);
            $today_status = attendance_punch_today_status($db, $has_column, $uid, $plan_date);

            if ($checkin_on && attendance_alert_user_enabled($u, 'checkin') && !$today_status['has_checkin']) {
                $type = 'attendance_checkin';
                if (!attendance_alert_already_queued($db, $uid, $type, $plan_date)) {
                    $send_at = attendance_alert_build_send_at($plan_date, $start_time, $checkin_before);
                    if ($send_at !== '') {
                        $subj = 'Check-in reminder';
                        $body = 'Hi ' . $name . ', please mark your check-in for today.';
                        $model->enqueue(array(
                            'user_id' => $uid,
                            'email'   => $email,
                            'type'    => $type,
                            'subject' => $subj,
                            'body'    => $body,
                            'send_at' => $send_at,
                        ));
                        $stats['checkin']++;
                    }
                }
            }

            if ($checkout_on && attendance_alert_user_enabled($u, 'checkout') && !$today_status['has_checkout']) {
                $type = 'attendance_checkout';
                if (!attendance_alert_already_queued($db, $uid, $type, $plan_date)) {
                    $send_at = attendance_alert_build_send_at($plan_date, $end_time, $checkout_before);
                    if ($send_at !== '') {
                        $subj = 'Check-out reminder';
                        $body = 'Hi ' . $name . ', please mark your check-out for today.';
                        $model->enqueue(array(
                            'user_id' => $uid,
                            'email'   => $email,
                            'type'    => $type,
                            'subject' => $subj,
                            'body'    => $body,
                            'send_at' => $send_at,
                        ));
                        $stats['checkout']++;
                    }
                }
            }
        }

        return $stats;
    }
}

if (!function_exists('attendance_alert_build_send_at')) {
    /**
     * @param string $plan_date Y-m-d
     * @param string $time_hm   HH:MM or HH:MM:SS
     * @param int    $minutes_before
     * @return string datetime or empty if invalid/past
     */
    function attendance_alert_build_send_at($plan_date, $time_hm, $minutes_before)
    {
        $time_hm = substr(trim($time_hm), 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $time_hm)) {
            return '';
        }
        $ts = strtotime($plan_date . ' ' . $time_hm . ':00');
        if ($ts === false) {
            return '';
        }
        if ($minutes_before > 0) {
            $ts -= ($minutes_before * 60);
        }
        if ($ts < time() - 60) {
            $ts = time() + 120;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
