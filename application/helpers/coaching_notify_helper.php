<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('coaching_send_mail')) {
    function coaching_send_mail($to, $subject, $html_body)
    {
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $CI =& get_instance();
        $CI->load->helper('email');
        $CI->load->helper('company');
        if (!function_exists('configure_email_from_settings')) {
            return false;
        }
        configure_email_from_settings();
        $CI->email->clear(true);
        $CI->email->from(get_system_from_email(), get_company_name());
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message($html_body);
        return (bool) $CI->email->send();
    }
}

if (!function_exists('coaching_session_email_recipients')) {
    /**
     * @return array{client_email:?string, coach_email:?string, client_name:string, coach_name:string}
     */
    function coaching_session_email_recipients($session_row)
    {
        $CI =& get_instance();
        $out = [
            'client_email' => null,
            'coach_email' => null,
            'client_name' => '',
            'coach_name' => '',
        ];
        $client = $CI->db->get_where('coaching_clients', ['id' => (int) $session_row->coaching_client_id])->row();
        if ($client) {
            $out['client_name'] = $client->full_name;
            $out['client_email'] = $client->email;
            if ($client->user_id) {
                $u = $CI->db->select('email')->get_where('users', ['id' => (int) $client->user_id])->row();
                if ($u && $u->email) {
                    $out['client_email'] = $u->email;
                }
            }
        }
        $coach = $CI->db->select('c.id, u.email, u.name')
            ->from('coaching_coaches c')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where('c.id', (int) $session_row->coach_id)
            ->get()->row();
        if ($coach) {
            $out['coach_email'] = $coach->email;
            $out['coach_name'] = $coach->name;
        }
        return $out;
    }
}

if (!function_exists('coaching_email_session_confirmation')) {
    function coaching_email_session_confirmation($session_id)
    {
        $CI =& get_instance();
        $CI->load->model('Coaching_model', 'coaching');
        $session = $CI->coaching->session_get($session_id);
        if (!$session || $session->status !== 'scheduled') {
            return false;
        }
        $rc = coaching_session_email_recipients($session);
        $when = date('l, d M Y \a\t h:i A', strtotime($session->scheduled_at));
        $link = $session->meeting_link ? '<p><a href="' . esc_view($session->meeting_link) . '">Join meeting</a></p>' : '';
        $body = '<p>Your coaching session <strong>' . esc_view($session->title) . '</strong> is scheduled for <strong>' . $when . '</strong>.</p>' . $link;
        if ($session->notes_client) {
            $body .= '<p>' . nl2br(esc_view($session->notes_client)) . '</p>';
        }
        $sent = false;
        if ($rc['client_email']) {
            $sent = coaching_send_mail(
                $rc['client_email'],
                'Session confirmed: ' . $session->title,
                '<p>Hi ' . esc_view($rc['client_name']) . ',</p>' . $body
            ) || $sent;
        }
        if ($rc['coach_email']) {
            $sent = coaching_send_mail(
                $rc['coach_email'],
                'Session scheduled with ' . $rc['client_name'],
                '<p>Hi ' . esc_view($rc['coach_name']) . ',</p><p>Session with <strong>' . esc_view($rc['client_name']) . '</strong> on <strong>' . $when . '</strong>.</p>' . $link
            ) || $sent;
        }
        return $sent;
    }
}

if (!function_exists('coaching_email_session_reminder')) {
    /**
     * @param string $type '24h'|'1h'
     */
    function coaching_email_session_reminder($session_id, $type = '24h')
    {
        $CI =& get_instance();
        $CI->load->model('Coaching_model', 'coaching');
        $session = $CI->coaching->session_get($session_id);
        if (!$session || $session->status !== 'scheduled') {
            return false;
        }
        $rc = coaching_session_email_recipients($session);
        $when = date('l, d M Y \a\t h:i A', strtotime($session->scheduled_at));
        $label = $type === '1h' ? 'in 1 hour' : 'tomorrow';
        $subject = 'Reminder: ' . $session->title . ' ' . $label;
        $body = '<p>This is a reminder that your session <strong>' . esc_view($session->title) . '</strong> is ' . $label . ' (<strong>' . $when . '</strong>).</p>';
        if ($session->meeting_link) {
            $body .= '<p><a href="' . esc_view($session->meeting_link) . '">Join meeting</a></p>';
        }
        $sent = false;
        if ($rc['client_email']) {
            $sent = coaching_send_mail($rc['client_email'], $subject, '<p>Hi ' . esc_view($rc['client_name']) . ',</p>' . $body) || $sent;
        }
        if ($rc['coach_email']) {
            $sent = coaching_send_mail($rc['coach_email'], $subject, '<p>Hi ' . esc_view($rc['coach_name']) . ',</p>' . $body) || $sent;
        }
        return $sent;
    }
}
