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
     * Send a single reminder row; updates status via model.
     *
     * @param CI_Email $email
     * @param Reminder_model $model
     * @param object $row
     * @return bool
     */
    function reminders_send_one($email, $model, $row)
    {
        if (!isset($row->email) || $row->email === '') {
            $model->mark_error($row->id);
            return false;
        }
        list($fromAddr, $fromName) = reminders_from_for_row($row);
        $email->clear(true);
        $email->from($fromAddr, $fromName);
        $email->to($row->email);
        $email->subject($row->subject);
        $email->message($row->body);
        if ($email->send()) {
            $model->mark_sent($row->id);
            return true;
        }
        $model->mark_error($row->id);
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
