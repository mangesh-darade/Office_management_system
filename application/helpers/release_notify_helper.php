<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Release notes email via Reminders queue (same pipeline as Reminders → Send).
 */

if (!function_exists('release_can_send_notes')) {
    function release_can_send_notes()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('releases_send_notes')
            || has_module_access('releases_edit')
            || has_module_access('releases');
    }
}

if (!function_exists('release_email_subject')) {
    function release_email_subject($release)
    {
        $version = isset($release->version) ? trim((string) $release->version) : '';
        $title = isset($release->title) ? trim((string) $release->title) : '';
        $project = isset($release->project_name) ? trim((string) $release->project_name) : '';
        $parts = array('Release notes');
        if ($version !== '') {
            $parts[] = 'v' . $version;
        }
        if ($title !== '') {
            $parts[] = $title;
        }
        if ($project !== '') {
            $parts[] = '(' . $project . ')';
        }
        return implode(' — ', $parts);
    }
}

if (!function_exists('release_email_body')) {
    /**
     * @param object $release
     * @param object[] $notes project_release_notes rows
     * @param string $recipient_name
     */
    function release_email_body($release, array $notes, $recipient_name = '')
    {
        $lines = array();
        if ($recipient_name !== '') {
            $lines[] = 'Hello ' . $recipient_name . ',';
            $lines[] = '';
        }
        $version = isset($release->version) ? trim((string) $release->version) : '';
        $title = isset($release->title) ? trim((string) $release->title) : '';
        $project = isset($release->project_name) ? trim((string) $release->project_name) : '';
        $head = 'Release';
        if ($version !== '') {
            $head .= ' v' . $version;
        }
        if ($title !== '') {
            $head .= ': ' . $title;
        }
        $lines[] = $head;
        if ($project !== '') {
            $lines[] = 'Project: ' . $project;
        }
        if (!empty($release->released_at)) {
            $lines[] = 'Released: ' . date('M j, Y', strtotime($release->released_at));
        } elseif (!empty($release->planned_date)) {
            $lines[] = 'Planned: ' . date('M j, Y', strtotime($release->planned_date));
        }
        if (!empty($release->description)) {
            $lines[] = '';
            $lines[] = trim((string) $release->description);
        }
        $lines[] = '';
        $lines[] = 'What\'s included:';
        if (empty($notes)) {
            $lines[] = '— (no release note points listed)';
        } else {
            foreach ($notes as $n) {
                $text = isset($n->point_text) ? trim((string) $n->point_text) : '';
                if ($text !== '') {
                    $lines[] = '• ' . $text;
                }
            }
        }
        $lines[] = '';
        if (function_exists('get_company_name')) {
            $lines[] = get_company_name();
        }
        return implode("\n", $lines);
    }
}

if (!function_exists('release_send_notes_to_users')) {
    /**
     * Queue and immediately send release notes to selected users via Reminders.
     *
     * @return array{sent:int,skipped:int,failed:int}
     */
    function release_send_notes_to_users($db, $reminder_model, $email, $release, array $notes, array $user_ids, array $from = array())
    {
        $CI =& get_instance();
        $CI->load->helper(array('reminders_user', 'reminders_email'));

        $subject = release_email_subject($release);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $from_email = isset($from['from_email']) ? (string) $from['from_email'] : '';
        $from_name = isset($from['from_name']) ? (string) $from['from_name'] : '';
        $session_uid = (int) $CI->session->userdata('user_id');
        $session_email = (string) $CI->session->userdata('email');
        $resolved = reminders_resolve_sender_defaults($db, $from_email, $from_name, $session_uid, $session_email);
        $from_email = $resolved['from_email'];
        $from_name = $resolved['from_name'];

        foreach ($user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($user_id <= 0) {
                continue;
            }
            $contact = reminders_fetch_user_contact($db, $user_id);
            if ($contact['email'] === '') {
                $skipped++;
                continue;
            }
            $body = release_email_body($release, $notes, $contact['name']);
            list($finalSubject, $finalBody) = reminders_replace_vars(
                $subject,
                $body,
                $contact['name'],
                $contact['email']
            );
            $rid = $reminder_model->enqueue(array(
                'user_id'    => $user_id,
                'email'      => $contact['email'],
                'type'       => 'release_note',
                'subject'    => $finalSubject,
                'body'       => $finalBody,
                'from_email' => $from_email !== '' ? $from_email : null,
                'from_name'  => $from_name !== '' ? $from_name : null,
                'send_at'    => date('Y-m-d H:i:s'),
            ));
            if (!$rid) {
                $failed++;
                continue;
            }
            $row = $db->get_where('reminders', array('id' => (int) $rid))->row();
            if ($row && reminders_send_one($email, $reminder_model, $row)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return array('sent' => $sent, 'skipped' => $skipped, 'failed' => $failed);
    }
}

if (!function_exists('release_parse_note_points_post')) {
    function release_parse_note_points_post($input)
    {
        $raw = $input->post('note_points');
        if (!is_array($raw)) {
            return array();
        }
        $out = array();
        foreach ($raw as $p) {
            $text = trim((string) $p);
            if ($text !== '') {
                $out[] = $text;
            }
        }
        return $out;
    }
}
