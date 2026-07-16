<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync OMS reminders queue → Google Calendar email reminders.
 * Delivery is handled by Google Calendar (no SMTP cron send).
 */

if (!function_exists('reminders_google_sync_row')) {
    /**
     * Create a Google Calendar event for a reminders-table row and mark sent.
     *
     * @param Reminder_model $model
     * @param object         $row
     * @param int            $reminder_minutes Minutes before start (0 = at event time)
     * @return array{ok:bool,message:string}
     */
    function reminders_google_sync_row($model, $row, $reminder_minutes = 0)
    {
        if (!is_object($row) || empty($row->id)) {
            return array('ok' => false, 'message' => 'Invalid reminder row.');
        }
        if (empty($row->email)) {
            $model->mark_error((int) $row->id);
            return array('ok' => false, 'message' => 'Missing email.');
        }
        if (isset($row->status) && $row->status === 'sent') {
            return array('ok' => true, 'message' => 'Already sent.');
        }

        $CI =& get_instance();
        $CI->load->library('google_calendar_lib');

        if (!$CI->google_calendar_lib->is_configured() || !$CI->google_calendar_lib->is_connected()) {
            return array(
                'ok' => false,
                'message' => 'Google Calendar is not connected. Open Settings → Google Calendar Reminders and connect.',
            );
        }

        $when = !empty($row->send_at) ? $row->send_at : date('Y-m-d H:i:s');
        $when_ts = strtotime($when);
        if ($when_ts === false) {
            $when = date('Y-m-d H:i:s');
        } elseif ($when_ts < time() - 60) {
            // Past time: schedule 1 minute from now so Google accepts the event
            $when = date('Y-m-d H:i:s', time() + 60);
        }

        $title = isset($row->subject) ? trim((string) $row->subject) : 'Reminder';
        if ($title === '') {
            $title = 'Reminder';
        }

        $description = isset($row->body) ? trim(strip_tags((string) $row->body)) : '';

        $result = $CI->google_calendar_lib->create_reminder_event(array(
            'email' => trim((string) $row->email),
            'title' => $title,
            'when' => $when,
            'description' => $description,
            'reminder_minutes' => max(0, (int) $reminder_minutes),
            'duration_minutes' => 30,
            'timezone' => 'Asia/Kolkata',
        ));

        if (empty($result['ok'])) {
            log_message('error', 'Reminder Google sync failed for id=' . (int) $row->id);
            $model->mark_error((int) $row->id);
            return array(
                'ok' => false,
                'message' => !empty($result['message']) ? $result['message'] : 'Google Calendar sync failed.',
            );
        }

        $model->mark_sent((int) $row->id);
        return array('ok' => true, 'message' => 'Scheduled via Google Calendar.');
    }
}

if (!function_exists('reminders_google_after_enqueue')) {
    /**
     * Called right after Reminder_model::enqueue insert.
     *
     * @param Reminder_model $model
     * @param int            $id
     * @return array{ok:bool,message:string}
     */
    function reminders_google_after_enqueue($model, $id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'message' => 'Invalid id.');
        }
        $row = $model->get($id);
        if (!$row) {
            return array('ok' => false, 'message' => 'Reminder not found.');
        }
        return reminders_google_sync_row($model, $row, 0);
    }
}

if (!function_exists('reminders_schedule_recipients')) {
    /**
     * Resolve emails for a schedule row/array.
     *
     * @param Reminder_model $model
     * @param CI_DB_query_builder $db
     * @param object|array $schedule
     * @return array<int,array{user_id:?int,email:string}>
     */
    function reminders_schedule_recipients($model, $db, $schedule)
    {
        $audience = is_object($schedule)
            ? (isset($schedule->audience) ? $schedule->audience : 'user')
            : (isset($schedule['audience']) ? $schedule['audience'] : 'user');
        $user_id = is_object($schedule)
            ? (isset($schedule->user_id) ? (int) $schedule->user_id : 0)
            : (isset($schedule['user_id']) ? (int) $schedule['user_id'] : 0);

        $out = array();
        if ($audience === 'all') {
            foreach ($model->all_users() as $u) {
                $email = isset($u->email) ? trim((string) $u->email) : '';
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $out[] = array(
                    'user_id' => isset($u->id) ? (int) $u->id : null,
                    'email' => $email,
                );
            }
            return $out;
        }

        $email = '';
        if ($user_id > 0 && $db->table_exists('users')) {
            $row = $db->select('email')->from('users')->where('id', $user_id)->get()->row();
            if ($row && !empty($row->email)) {
                $email = trim((string) $row->email);
            }
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $out[] = array('user_id' => $user_id, 'email' => $email);
        }
        return $out;
    }
}

if (!function_exists('reminders_schedule_next_send_at')) {
    /**
     * Next (or one-time) send datetime for a schedule.
     *
     * @param object|array $schedule
     * @return string|false Y-m-d H:i:s or false
     */
    function reminders_schedule_next_send_at($schedule)
    {
        $type = is_object($schedule)
            ? (isset($schedule->schedule_type) ? $schedule->schedule_type : 'weekly')
            : (isset($schedule['schedule_type']) ? $schedule['schedule_type'] : 'weekly');
        $one_time = is_object($schedule)
            ? (isset($schedule->one_time_at) ? $schedule->one_time_at : null)
            : (isset($schedule['one_time_at']) ? $schedule['one_time_at'] : null);
        $send_time = is_object($schedule)
            ? (isset($schedule->send_time) ? $schedule->send_time : '09:00')
            : (isset($schedule['send_time']) ? $schedule['send_time'] : '09:00');
        $weekdays = is_object($schedule)
            ? (isset($schedule->weekdays) ? (string) $schedule->weekdays : '')
            : (isset($schedule['weekdays']) ? (string) $schedule['weekdays'] : '');

        if ($type === 'once') {
            if (empty($one_time)) {
                return false;
            }
            $ts = strtotime($one_time);
            return $ts ? date('Y-m-d H:i:s', $ts) : false;
        }

        $parts = preg_split('/\s*,\s*/', trim($weekdays));
        $days = array();
        foreach ($parts as $p) {
            if ($p === '' || !ctype_digit((string) $p)) {
                continue;
            }
            $d = (int) $p;
            if ($d >= 0 && $d <= 6) {
                $days[$d] = true;
            }
        }
        if (empty($days)) {
            return false;
        }

        if (!preg_match('/^\d{1,2}:\d{2}$/', (string) $send_time)) {
            $send_time = '09:00';
        }

        $now = time();
        for ($i = 0; $i < 8; $i++) {
            $ts = strtotime('+' . $i . ' day', $now);
            $w = (int) date('w', $ts);
            if (!isset($days[$w])) {
                continue;
            }
            $candidate = date('Y-m-d', $ts) . ' ' . $send_time . ':00';
            $cts = strtotime($candidate);
            if ($cts !== false && $cts > $now) {
                return date('Y-m-d H:i:s', $cts);
            }
        }
        return false;
    }
}

if (!function_exists('reminders_schedule_push_to_google')) {
    /**
     * Enqueue + Google Calendar sync for a schedule's next occurrence.
     *
     * @param Reminder_model $model
     * @param CI_DB_query_builder $db
     * @param object|array $schedule  Must include id when marking ran
     * @param bool $mark_ran Set last_run_date to occurrence day to avoid duplicate generate
     * @return array{ok:bool,synced:int,failed:int,send_at:?string,message:string}
     */
    function reminders_schedule_push_to_google($model, $db, $schedule, $mark_ran = true)
    {
        $CI =& get_instance();
        $CI->load->library('google_calendar_lib');
        $CI->load->helper('reminders_google');

        if (!$CI->google_calendar_lib->is_configured() || !$CI->google_calendar_lib->is_connected()) {
            return array(
                'ok' => false,
                'synced' => 0,
                'failed' => 0,
                'send_at' => null,
                'message' => 'Google Calendar is not connected. Open Settings → Google Calendar Reminders.',
            );
        }

        $send_at = reminders_schedule_next_send_at($schedule);
        if ($send_at === false) {
            return array(
                'ok' => false,
                'synced' => 0,
                'failed' => 0,
                'send_at' => null,
                'message' => 'Could not resolve next send date/time for this schedule.',
            );
        }

        $subject = is_object($schedule)
            ? (isset($schedule->subject) ? $schedule->subject : '')
            : (isset($schedule['subject']) ? $schedule['subject'] : '');
        $body = is_object($schedule)
            ? (isset($schedule->body) ? $schedule->body : '')
            : (isset($schedule['body']) ? $schedule['body'] : '');
        if ($body === '') {
            $body = $subject;
        }

        $recipients = reminders_schedule_recipients($model, $db, $schedule);
        if (empty($recipients)) {
            return array(
                'ok' => false,
                'synced' => 0,
                'failed' => 0,
                'send_at' => $send_at,
                'message' => 'No valid recipient emails for this schedule.',
            );
        }

        $synced = 0;
        $failed = 0;
        foreach ($recipients as $r) {
            // enqueue() already syncs to Google Calendar and marks sent/error
            $rid = $model->enqueue(array(
                'user_id' => $r['user_id'],
                'email' => $r['email'],
                'type' => 'schedule',
                'subject' => $subject,
                'body' => $body,
                'send_at' => $send_at,
            ));
            if ($rid <= 0) {
                $failed++;
                continue;
            }
            $row = $model->get($rid);
            if ($row && isset($row->status) && $row->status === 'sent') {
                $synced++;
            } else {
                $failed++;
            }
        }

        $schedule_id = is_object($schedule)
            ? (isset($schedule->id) ? (int) $schedule->id : 0)
            : (isset($schedule['id']) ? (int) $schedule['id'] : 0);

        if ($mark_ran && $schedule_id > 0) {
            $occurrence_day = date('Y-m-d', strtotime($send_at));
            $model->db->where('id', $schedule_id)->update('reminder_schedules', array(
                'last_run_date' => $occurrence_day,
            ));
        }

        return array(
            'ok' => ($synced > 0),
            'synced' => $synced,
            'failed' => $failed,
            'send_at' => $send_at,
            'message' => 'Google Calendar: synced ' . $synced . ', failed ' . $failed . ' for ' . $send_at,
        );
    }
}
