<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder cron queue + schedule generation.
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
     * @param Reminder_model $model
     * @param CI_DB_query_builder $db
     * @return int
     */
    function reminders_generate_from_schedules($model, $db)
    {
        $weekday = (int) date('w');
        $nowTime = date('H:i');
        $due = $model->fetch_due_schedules($weekday, $nowTime);
        $queued = 0;
        foreach ($due as $s) {
            if (isset($s->schedule_type) && $s->schedule_type === 'once' && isset($s->one_time_at) && $s->one_time_at) {
                $sendAt = $s->one_time_at;
            } else {
                $sendAt = date('Y-m-d') . ' ' . $s->send_time . ':00';
            }
            if ($s->audience === 'all') {
                foreach ($model->all_users() as $u) {
                    $to = isset($u->email) ? $u->email : '';
                    if ($to === '') {
                        continue;
                    }
                    $model->enqueue(array(
                        'user_id' => isset($u->id) ? (int) $u->id : null,
                        'email'   => $to,
                        'type'    => 'schedule',
                        'subject' => $s->subject,
                        'body'    => $s->body !== '' ? $s->body : $s->subject,
                        'send_at' => $sendAt,
                    ));
                    $queued++;
                }
            } else {
                $email = '';
                if ($db->table_exists('users')) {
                    $row = $db->select('email')->from('users')->where('id', (int) $s->user_id)->get()->row();
                    if ($row && isset($row->email)) {
                        $email = $row->email;
                    }
                }
                if ($email !== '') {
                    $model->enqueue(array(
                        'user_id' => (int) $s->user_id,
                        'email'   => $email,
                        'type'    => 'schedule',
                        'subject' => $s->subject,
                        'body'    => $s->body !== '' ? $s->body : $s->subject,
                        'send_at' => $sendAt,
                    ));
                    $queued++;
                }
            }
            $model->mark_schedule_ran_today($s->id);
        }
        return $queued;
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
