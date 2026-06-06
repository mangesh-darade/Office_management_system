<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance notification helpers (late mark + punch emails).
 */

if (!function_exists('attendance_notify_calculate_late_time')) {
    /**
     * @param object $settings Setting_model with get_setting()
     * @param string $checkinDateTime
     * @return array|null
     */
    function attendance_notify_calculate_late_time($settings, $checkinDateTime)
    {
        $start_time = $settings->get_setting('attendance_start_time', '09:30');
        $grace_minutes = (int) $settings->get_setting('attendance_grace_minutes', 15);

        $checkin_timestamp = strtotime($checkinDateTime);
        if ($checkin_timestamp === false) {
            return null;
        }

        $today = date('Y-m-d', $checkin_timestamp);
        $expected_datetime = $today . ' ' . $start_time;
        $expected_timestamp = strtotime($expected_datetime);
        $expected_timestamp = $expected_timestamp + ($grace_minutes * 60);

        if ($checkin_timestamp <= $expected_timestamp) {
            return array(
                'is_late'       => false,
                'hours'         => 0,
                'minutes'       => 0,
                'seconds'       => 0,
                'formatted'     => '0 hours 0 minutes 0 seconds',
                'expected_time' => date('Y-m-d H:i:s', $expected_timestamp),
            );
        }

        $late_seconds = $checkin_timestamp - $expected_timestamp;
        $hours = floor($late_seconds / 3600);
        $minutes = floor(($late_seconds % 3600) / 60);
        $seconds = $late_seconds % 60;

        $formatted_parts = array();
        if ($hours > 0) {
            $formatted_parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0) {
            $formatted_parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        if ($seconds > 0 || empty($formatted_parts)) {
            $formatted_parts[] = $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }

        return array(
            'is_late'       => true,
            'hours'         => $hours,
            'minutes'       => $minutes,
            'seconds'       => $seconds,
            'formatted'     => implode(' ', $formatted_parts),
            'expected_time' => date('Y-m-d H:i:s', $expected_timestamp),
            'late_seconds'  => $late_seconds,
        );
    }
}

if (!function_exists('attendance_notify_load_user')) {
    /**
     * @param CI_DB_query_builder $db
     * @param int $user_id
     * @return object|null
     */
    function attendance_notify_load_user($db, $user_id)
    {
        if (!$db->table_exists('users')) {
            return null;
        }

        $select = array('email');
        if (schema_table_has_column($db, 'users', 'notify_attendance')) {
            $select[] = 'notify_attendance';
        }
        if (schema_table_has_column($db, 'users', 'name')) {
            $select[] = 'name';
        }

        return $db->select(implode(',', $select), false)
            ->from('users')
            ->where('id', (int) $user_id)
            ->get()
            ->row();
    }
}

if (!function_exists('attendance_notify_user_wants_email')) {
    /**
     * @param object $user
     * @return bool
     */
    function attendance_notify_user_wants_email($db, $user)
    {
        if (!$user || !isset($user->email) || $user->email === '') {
            return false;
        }

        if (!schema_table_has_column($db, 'users', 'notify_attendance')) {
            return true;
        }

        $raw = isset($user->notify_attendance) ? $user->notify_attendance : 1;
        if (is_numeric($raw)) {
            return ((int) $raw === 1);
        }
        if (is_string($raw)) {
            return in_array(strtolower(trim((string) $raw)), array('1', 'yes', 'true', 'enabled'), true);
        }

        return true;
    }
}

if (!function_exists('attendance_notify_late_employee_email')) {
    /**
     * @param string $user_name
     * @param string $dateTime
     * @param array $late_info
     * @return array{subject:string,body:string}
     */
    function attendance_notify_late_employee_email($user_name, $dateTime, array $late_info)
    {
        $body = '<html><body>';
        $body .= '<h3 style="color: #dc3545;">Late Mark Notification</h3>';
        $body .= '<p>Hello ' . htmlspecialchars($user_name) . ',</p>';
        $body .= '<p>Your attendance check-in has been recorded at <strong>' . htmlspecialchars($dateTime) . '</strong>.</p>';
        $body .= '<p style="color: #dc3545; font-weight: bold;">You are marked LATE.</p>';
        $body .= '<p><strong>Late Time:</strong></p><ul>';
        $body .= '<li>Hours: ' . $late_info['hours'] . '</li>';
        $body .= '<li>Minutes: ' . $late_info['minutes'] . '</li>';
        $body .= '<li>Seconds: ' . $late_info['seconds'] . '</li>';
        $body .= '</ul>';
        $body .= '<p><strong>Total Late Time:</strong> ' . htmlspecialchars($late_info['formatted']) . '</p>';
        $body .= '<p>Expected start time: ' . htmlspecialchars($late_info['expected_time']) . '</p>';
        $body .= '<p>Thank you.</p></body></html>';

        return array(
            'subject' => 'Late Mark - Attendance Check-in',
            'body'    => $body,
        );
    }
}

if (!function_exists('attendance_notify_regular_punch_email')) {
    /**
     * @param string $user_name
     * @param string $dateTime
     * @param bool $isOut
     * @param array|null $late_info
     * @param bool $late_mark_enabled
     * @return array{subject:string,body:string}
     */
    function attendance_notify_regular_punch_email($user_name, $dateTime, $isOut, $late_info, $late_mark_enabled)
    {
        $subject = $isOut ? 'Attendance checkout recorded' : 'Attendance check-in recorded';
        $body = '<html><body>';
        $body .= '<h3>Attendance ' . ($isOut ? 'Check-out' : 'Check-in') . ' Recorded</h3>';
        $body .= '<p>Hello ' . htmlspecialchars($user_name) . ',</p>';
        $body .= '<p>Your attendance ' . ($isOut ? 'checkout' : 'check-in') . ' has been recorded at <strong>' . htmlspecialchars($dateTime) . '</strong>.</p>';
        if (!$isOut && $late_mark_enabled && $late_info && isset($late_info['is_late']) && $late_info['is_late'] === false) {
            $body .= '<p style="color: #28a745; font-weight: bold;">You are on time. Good job!</p>';
        }
        $body .= '<p>Thank you.</p></body></html>';

        return array('subject' => $subject, 'body' => $body);
    }
}

if (!function_exists('attendance_notify_late_manager_recipients')) {
    /**
     * @param CI_DB_query_builder $db
     * @param object $settings
     * @param int $user_id
     * @return array<int,array{email:string,name:string}>
     */
    function attendance_notify_late_manager_recipients($db, $settings, $user_id)
    {
        $hr_user_id = $settings->get_setting('leave_hr_user_id');
        $hr_user_id = !empty($hr_user_id) ? (int) $hr_user_id : null;

        $manager_id = null;
        if ($db->table_exists('employees')) {
            $emp = $db->where('user_id', $user_id)->get('employees')->row();
            if ($emp && !empty($emp->reporting_to)) {
                $manager_id = (int) $emp->reporting_to;
            }
        }

        $recipients = array();
        if ($hr_user_id && $hr_user_id !== $user_id) {
            $hr = $db->select('email, name')->from('users')->where('id', $hr_user_id)->get()->row();
            if ($hr && !empty($hr->email)) {
                $recipients[] = array(
                    'email' => $hr->email,
                    'name'  => !empty($hr->name) ? $hr->name : $hr->email,
                );
            }
        }
        if ($manager_id && $manager_id !== $hr_user_id && $manager_id !== $user_id) {
            $manager = $db->select('email, name')->from('users')->where('id', $manager_id)->get()->row();
            if ($manager && !empty($manager->email)) {
                $recipients[] = array(
                    'email' => $manager->email,
                    'name'  => !empty($manager->name) ? $manager->name : $manager->email,
                );
            }
        }

        $unique = array();
        foreach ($recipients as $recipient) {
            $email = strtolower(trim($recipient['email']));
            if (!isset($unique[$email])) {
                $unique[$email] = $recipient;
            }
        }

        return array_values($unique);
    }
}

if (!function_exists('attendance_notify_late_manager_email')) {
    /**
     * @param string $user_name
     * @param string $checkin_time
     * @param array $late_info
     * @return array{subject:string,body:string}
     */
    function attendance_notify_late_manager_email($user_name, $checkin_time, array $late_info)
    {
        $body = '<html><body>';
        $body .= '<h3 style="color: #dc3545;">Late Mark Notification</h3>';
        $body .= '<p><strong>Employee:</strong> ' . htmlspecialchars($user_name) . '</p>';
        $body .= '<p><strong>Check-in Time:</strong> ' . htmlspecialchars($checkin_time) . '</p>';
        $body .= '<p style="color: #dc3545; font-weight: bold;">Employee is marked LATE.</p>';
        $body .= '<p><strong>Late Time Details:</strong></p><ul>';
        $body .= '<li>Hours: ' . $late_info['hours'] . '</li>';
        $body .= '<li>Minutes: ' . $late_info['minutes'] . '</li>';
        $body .= '<li>Seconds: ' . $late_info['seconds'] . '</li>';
        $body .= '</ul>';
        $body .= '<p><strong>Total Late Time:</strong> ' . htmlspecialchars($late_info['formatted']) . '</p>';
        $body .= '<p><strong>Expected Check-in Time:</strong> ' . htmlspecialchars($late_info['expected_time']) . '</p>';
        $body .= '<p>Thank you.</p></body></html>';

        return array(
            'subject' => 'Late Mark - ' . htmlspecialchars($user_name) . ' - ' . date('Y-m-d', strtotime($checkin_time)),
            'body'    => $body,
        );
    }
}

if (!function_exists('attendance_notify_send_email')) {
    /**
     * @param object $email CI_Email library
     * @param string $fromAddr
     * @param string $fromName
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $cc
     * @return void
     */
    function attendance_notify_send_email($email, $fromAddr, $fromName, $to, $subject, $body, array $cc = array())
    {
        $email->clear(true);
        $email->from($fromAddr, $fromName);
        $email->to($to);
        if (!empty($cc)) {
            $email->cc($cc);
        }
        $email->subject($subject);
        $email->message($body);
        @$email->send();
    }
}
