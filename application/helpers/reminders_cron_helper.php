<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder cron queue + schedule generation (Google Calendar delivery via enqueue).
 */

if (!function_exists('reminders_queue_daily_type')) {
    /**
     * Queue morning or night reminders for all users.
     *
     * @param Reminder_model $model
     * @param string         $type daily_morning|daily_night
     * @return int queued count
     */
    function reminders_queue_daily_type($model, $type)
    {
        $resolved = reminders_resolve_template($model, $type);
        if ($resolved === null) {
            return 0;
        }
        $queued = 0;
        foreach ($model->all_users() as $u) {
            $to = isset($u->email) ? $u->email : '';
            if ($to === '') {
                continue;
            }
            $name = reminders_user_label_from_row($u);
            list($subj, $body) = $model->render_template($resolved['subject'], $resolved['body'], array('name' => $name));
            $model->enqueue(array(
                'user_id' => isset($u->id) ? (int) $u->id : null,
                'email'   => $to,
                'type'    => $type,
                'subject' => $subj,
                'body'    => $body,
                'send_at' => date('Y-m-d H:i:00'),
            ));
            $queued++;
        }
        return $queued;
    }
}

if (!function_exists('reminders_generate_from_schedules')) {
    /**
     * Fan out due schedules → enqueue → Google Calendar (inside enqueue).
     *
     * @param Reminder_model $model
     * @param CI_DB_query_builder $db
     * @return array{queued:int,synced:int,failed:int}
     */
    function reminders_generate_from_schedules($model, $db)
    {
        $CI =& get_instance();
        $CI->load->helper('reminders_google');

        $weekday = (int) date('w');
        $nowTime = date('H:i');
        $due = $model->fetch_due_schedules($weekday, $nowTime);
        $queued = 0;
        $synced = 0;
        $failed = 0;

        foreach ($due as $s) {
            if (isset($s->schedule_type) && $s->schedule_type === 'once' && !empty($s->one_time_at)) {
                $send_at = $s->one_time_at;
            } else {
                $send_at = date('Y-m-d') . ' ' . $s->send_time . ':00';
            }

            $recipients = reminders_schedule_recipients($model, $db, $s);
            $subject = isset($s->subject) ? $s->subject : '';
            $body = (isset($s->body) && $s->body !== '') ? $s->body : $subject;

            foreach ($recipients as $r) {
                $rid = $model->enqueue(array(
                    'user_id' => $r['user_id'],
                    'email'   => $r['email'],
                    'type'    => 'schedule',
                    'subject' => $subject,
                    'body'    => $body,
                    'send_at' => $send_at,
                ));
                $queued++;
                if ($rid > 0) {
                    $row = $model->get($rid);
                    if ($row && isset($row->status) && $row->status === 'sent') {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } else {
                    $failed++;
                }
            }

            $model->mark_schedule_ran_today($s->id);
        }

        return array(
            'queued' => $queued,
            'synced' => $synced,
            'failed' => $failed,
        );
    }
}

if (!function_exists('reminders_send_selected_ids')) {
    /**
     * @param Reminder_model $model
     * @param CI_DB_query_builder $db
     * @param CI_Email $email
     * @param int[] $ids
     * @param string $tpl_code
     * @return array{sent:int,failed:int}
     */
    function reminders_send_selected_ids($model, $db, $email, array $ids, $tpl_code = '')
    {
        $tplSubject = null;
        $tplBody = null;
        if ($tpl_code !== '') {
            $resolved = reminders_resolve_template($model, $tpl_code);
            if ($resolved !== null) {
                $tplSubject = $resolved['subject'];
                $tplBody = $resolved['body'];
            }
        }
        reminders_configure_email();
        $sent = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $q = $db->get_where('reminders', array('id' => (int) $id))->row();
            if (!$q || $q->status === 'sent') {
                continue;
            }
            $subject = $q->subject;
            $body = $q->body;
            if ($tpl_code !== '' && $tplSubject !== null && $tplBody !== null) {
                $name = $q->email;
                if (isset($q->user_id) && (int) $q->user_id > 0) {
                    $contact = reminders_fetch_user_contact($db, (int) $q->user_id);
                    if ($contact['name'] !== '') {
                        $name = $contact['name'];
                    }
                }
                list($subject, $body) = $model->render_template($tplSubject, $tplBody, array('name' => $name));
                $update = array('subject' => $subject, 'body' => $body);
                if (in_array($tpl_code, array('daily_morning', 'daily_night', 'bulk_manual'), true)) {
                    $update['type'] = $tpl_code;
                }
                $db->where('id', (int) $q->id)->update('reminders', $update);
                $q->subject = $subject;
                $q->body = $body;
            }
            if (reminders_send_one($email, $model, $q)) {
                $sent++;
            } else {
                $failed++;
            }
        }
        return array('sent' => $sent, 'failed' => $failed);
    }
}
