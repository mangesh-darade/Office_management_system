<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder email send + from-address resolution.
 */

if (!function_exists('reminders_configure_email')) {
    function reminders_configure_email()
    {
        $CI =& get_instance();
        $CI->load->helper('email');
        configure_email_from_settings();
    }
}

if (!function_exists('reminders_smtp_after_enqueue')) {
    /**
     * SMTP-only delivery after enqueue (Settings → Email).
     * Future send_at stays queued for send-queue cron.
     *
     * @param Reminder_model $model
     * @param int            $id
     * @return array{ok:bool,message:string}
     */
    function reminders_smtp_after_enqueue($model, $id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'message' => 'Invalid id.');
        }
        $row = $model->get($id);
        if (!$row) {
            return array('ok' => false, 'message' => 'Reminder not found.');
        }
        if (!empty($row->send_at)) {
            $ts = strtotime((string) $row->send_at);
            if ($ts !== false && $ts > (time() + 45)) {
                return array('ok' => true, 'message' => 'Queued for scheduled SMTP send.');
            }
        }
        $CI =& get_instance();
        if (!isset($CI->email)) {
            $CI->load->library('email');
        }
        $ok = reminders_send_one($CI->email, $model, $row);
        return array(
            'ok' => $ok,
            'message' => $ok ? 'Email sent.' : 'Email send failed. Check SMTP settings.',
        );
    }
}

if (!function_exists('reminders_from_for_row')) {
    /**
     * @param object $row Reminder queue row
     * @return array{0:string,1:string} from address, from name
     */
    function reminders_from_for_row($row)
    {
        if (!function_exists('get_system_from_email')) {
            $CI =& get_instance();
            $CI->load->helper('email');
        }
        $fromAddr = isset($row->from_email) && $row->from_email !== '' ? $row->from_email : get_system_from_email();
        $fromName = isset($row->from_name) && $row->from_name !== '' ? $row->from_name : get_company_name();
        return array($fromAddr, $fromName);
    }
}

if (!function_exists('reminders_send_one')) {
    /**
     * Deliver a reminder via Settings SMTP (real email). Marks sent/error on the queue row.
     *
     * @param CI_Email $email
     * @param Reminder_model $model
     * @param object $row
     * @return bool
     */
    function reminders_send_one($email, $model, $row)
    {
        if (!is_object($row) || empty($row->id)) {
            return false;
        }
        if (empty($row->email) || !filter_var($row->email, FILTER_VALIDATE_EMAIL)) {
            $model->mark_error((int) $row->id);
            return false;
        }
        if (isset($row->status) && $row->status === 'sent') {
            return true;
        }

        reminders_configure_email();
        $CI =& get_instance();
        if (!isset($CI->email)) {
            $CI->load->library('email');
            $email = $CI->email;
        }

        $to = trim((string) $row->email);
        $name = $to;
        if (!empty($row->user_id) && $CI->db->table_exists('users')) {
            $u = $CI->db->select('name, full_name, email')->from('users')->where('id', (int) $row->user_id)->get()->row();
            if ($u) {
                if (!empty($u->name)) {
                    $name = (string) $u->name;
                } elseif (!empty($u->full_name)) {
                    $name = (string) $u->full_name;
                }
            }
        }

        list($subject, $body) = reminders_replace_vars(
            isset($row->subject) ? (string) $row->subject : 'Reminder',
            isset($row->body) ? (string) $row->body : '',
            $name,
            $to
        );
        if (trim($subject) === '') {
            $subject = 'Reminder';
        }
        if (trim(strip_tags($body)) === '') {
            $body = $subject;
        }

        list($fromAddr, $fromName) = reminders_from_for_row($row);
        if ($fromAddr === '') {
            log_message('error', 'Reminder SMTP skipped: from address empty id=' . (int) $row->id);
            $model->mark_error((int) $row->id);
            return false;
        }

        $email->clear(true);
        $email->from($fromAddr, $fromName);
        $email->to($to);
        $email->subject($subject);
        // Allow simple HTML from templates; plain text still works.
        if ($body !== strip_tags($body)) {
            $email->set_mailtype('html');
            $email->message($body);
        } else {
            $email->set_mailtype('html');
            $email->message(nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));
        }

        $ok = false;
        try {
            $ok = (bool) $email->send();
        } catch (Exception $e) {
            log_message('error', 'Reminder SMTP send failed id=' . (int) $row->id . ' | ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            $model->mark_sent((int) $row->id);
            return true;
        }

        log_message('error', 'Reminder SMTP send failed id=' . (int) $row->id);
        $model->mark_error((int) $row->id);
        return false;
    }
}

if (!function_exists('reminders_process_batch')) {
    /**
     * @param object[] $rows
     * @return array{sent:int,failed:int}
     */
    function reminders_process_batch($email, $model, array $rows)
    {
        reminders_configure_email();
        $sent = 0;
        $failed = 0;
        foreach ($rows as $row) {
            if (reminders_send_one($email, $model, $row)) {
                $sent++;
            } else {
                $failed++;
            }
        }
        return array('sent' => $sent, 'failed' => $failed);
    }
}

if (!function_exists('reminders_replace_vars')) {
    function reminders_replace_vars($subject, $body, $name, $email)
    {
        $repl = array($name, $email, date('Y-m-d'), date('H:i'));
        $search = array('{name}', '{email}', '{date}', '{time}');
        return array(
            str_replace($search, $repl, $subject),
            str_replace($search, $repl, $body),
        );
    }
}
