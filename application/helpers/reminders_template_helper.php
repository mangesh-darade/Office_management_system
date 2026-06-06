<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder template defaults and resolution.
 */

if (!function_exists('reminders_builtin_templates')) {
    function reminders_builtin_templates()
    {
        return array(
            'daily_morning' => array(
                'subject' => 'Good morning! Daily login reminder',
                'body'    => "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.",
            ),
            'daily_night' => array(
                'subject' => 'Good evening! Daily logout reminder',
                'body'    => "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.",
            ),
            'bulk_manual' => array(
                'subject' => 'Bulk message',
                'body'    => "Hello {name}\n\nThis is a bulk message.",
            ),
        );
    }
}

if (!function_exists('reminders_resolve_template')) {
    /**
     * @param Reminder_model $model
     * @param string         $tpl_code
     * @return array{subject:string,body:string}|null
     */
    function reminders_resolve_template($model, $tpl_code)
    {
        $builtins = reminders_builtin_templates();
        if (!isset($builtins[$tpl_code])) {
            return null;
        }
        $tpl = $model->get_template($tpl_code);
        $subject = $builtins[$tpl_code]['subject'];
        $body = $builtins[$tpl_code]['body'];
        if ($tpl && isset($tpl->subject) && $tpl->subject !== '') {
            $subject = $tpl->subject;
        }
        if ($tpl && isset($tpl->body) && $tpl->body !== '') {
            $body = $tpl->body;
        }
        return array('subject' => $subject, 'body' => $body);
    }
}

if (!function_exists('reminders_templates_view_data')) {
    function reminders_templates_view_data($model)
    {
        $builtins = reminders_builtin_templates();
        $m = $model->get_template('daily_morning');
        $n = $model->get_template('daily_night');
        $b = $model->get_template('bulk_manual');
        return array(
            'morning_subject' => ($m && isset($m->subject)) ? $m->subject : $builtins['daily_morning']['subject'],
            'morning_body'    => ($m && isset($m->body)) ? $m->body : $builtins['daily_morning']['body'],
            'night_subject'   => ($n && isset($n->subject)) ? $n->subject : $builtins['daily_night']['subject'],
            'night_body'      => ($n && isset($n->body)) ? $n->body : $builtins['daily_night']['body'],
            'bulk_subject'    => ($b && isset($b->subject)) ? $b->subject : $builtins['bulk_manual']['subject'],
            'bulk_body'       => ($b && isset($b->body)) ? $b->body : $builtins['bulk_manual']['body'],
        );
    }
}
