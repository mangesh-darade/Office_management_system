<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder schedule form parsing (create + edit).
 */

if (!function_exists('reminders_parse_schedule_post')) {
    /**
     * @param CI_Input $input
     * @return array{ok:bool,data?:array,error?:string}
     */
    function reminders_parse_schedule_post($input)
    {
        $audience = $input->post('audience');
        $user_id = $input->post('user_id') !== '' ? (int) $input->post('user_id') : null;
        $weekdays_array = $input->post('weekdays');
        $weekdays = is_array($weekdays_array) ? implode(',', $weekdays_array) : '';
        $send_time = trim($input->post('send_time'));
        $schedule_type = $input->post('schedule_type');
        $schedule_type = ($schedule_type === 'once') ? 'once' : 'weekly';
        $one_time_raw = trim($input->post('one_time_at'));
        $subject = trim($input->post('subject'));
        $body = (string) $input->post('body');
        $name = trim($input->post('name'));

        if ($audience !== 'all' && $audience !== 'user') {
            $audience = 'user';
        }
        if ($audience === 'user' && !$user_id) {
            return array('ok' => false, 'error' => 'Please select a user for this schedule.');
        }
        if ($subject === '' || $name === '') {
            return array('ok' => false, 'error' => 'Please fill required fields.');
        }

        $one_time_at = null;
        if ($schedule_type === 'weekly') {
            if ($weekdays === '' || $send_time === '') {
                return array('ok' => false, 'error' => 'Please select weekdays and send time.');
            }
        } else {
            if ($one_time_raw === '') {
                return array('ok' => false, 'error' => 'Please select send date and time.');
            }
            $dt = str_replace('T', ' ', $one_time_raw);
            if (strlen($dt) === 16) {
                $dt .= ':00';
            }
            $one_time_at = $dt;
            $send_time = substr($dt, 11, 5);
            $weekdays = '';
        }

        return array(
            'ok'   => true,
            'data' => array(
                'name'          => $name,
                'audience'      => $audience,
                'user_id'       => $user_id,
                'weekdays'      => $weekdays,
                'schedule_type' => $schedule_type,
                'send_time'     => $send_time,
                'one_time_at'   => $one_time_at,
                'subject'       => $subject,
                'body'          => $body,
            ),
        );
    }
}

if (!function_exists('reminders_parse_csv_import')) {
    /**
     * @return array{ok:bool,handle?:resource,map?:array,error?:string}
     */
    function reminders_parse_csv_import()
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'error' => 'Please upload a valid CSV file');
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            return array('ok' => false, 'error' => 'Unable to read uploaded file');
        }
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return array('ok' => false, 'error' => 'CSV is empty');
        }
        $map = array();
        foreach ($header as $i => $col) {
            $map[strtolower(trim($col))] = $i;
        }
        if (!isset($map['email'])) {
            fclose($handle);
            return array('ok' => false, 'error' => 'CSV must contain an email column');
        }
        return array('ok' => true, 'handle' => $handle, 'map' => $map);
    }
}

if (!function_exists('reminders_queue_csv_rows')) {
    /**
     * @param Reminder_model $model
     * @param resource $handle
     * @param array $map
     * @return int
     */
    function reminders_queue_csv_rows($model, $handle, array $map, $tpl_code, $tplSubject, $tplBody, $from_email, $from_name)
    {
        $queued = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $email = isset($row[$map['email']]) ? trim($row[$map['email']]) : '';
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = $email;
            if (isset($map['name']) && isset($row[$map['name']]) && trim($row[$map['name']]) !== '') {
                $name = trim($row[$map['name']]);
            }
            list($subjRendered, $bodyRendered) = $model->render_template($tplSubject, $tplBody, array('name' => $name));
            $model->enqueue(array(
                'user_id'    => null,
                'email'      => $email,
                'type'       => $tpl_code,
                'subject'    => $subjRendered,
                'body'       => $bodyRendered !== '' ? $bodyRendered : $subjRendered,
                'from_email' => $from_email !== '' ? $from_email : null,
                'from_name'  => $from_name !== '' ? $from_name : null,
                'send_at'    => date('Y-m-d H:i:00'),
            ));
            $queued++;
        }
        fclose($handle);
        return $queued;
    }
}
