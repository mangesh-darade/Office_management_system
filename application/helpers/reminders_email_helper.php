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
     * Deliver a reminder via Google Calendar (email reminder). SMTP cron path is disabled.
     *
     * @param CI_Email $email Unused (kept for call-site compatibility)
     * @param Reminder_model $model
     * @param object $row
     * @return bool
     */
    function reminders_send_one($email, $model, $row)
    {
        $CI =& get_instance();
        $CI->load->helper('reminders_google');
        $result = reminders_google_sync_row($model, $row, 0);
        return !empty($result['ok']);
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
