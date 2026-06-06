<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminder user contact resolution (shared across send/import/cron flows).
 */

if (!function_exists('reminders_user_select_fields')) {
    /**
     * @param CI_DB_query_builder $db
     * @return string[] SELECT fragments for users row
     */
    function reminders_user_select_fields($db)
    {
        $sel = array('email');
        if (schema_table_has_column($db, 'users', 'full_name')) {
            $sel[] = 'full_name';
        }
        if (schema_table_has_column($db, 'users', 'name')) {
            $sel[] = 'name';
        }
        if (schema_table_has_column($db, 'users', 'first_name') && schema_table_has_column($db, 'users', 'last_name')) {
            $sel[] = "CONCAT(first_name,' ',last_name) AS full_label";
        }
        return $sel;
    }
}

if (!function_exists('reminders_user_label_from_row')) {
    function reminders_user_label_from_row($u, $fallback = '')
    {
        if (!$u) {
            return (string) $fallback;
        }
        if (isset($u->full_label) && $u->full_label !== '') {
            return $u->full_label;
        }
        if (isset($u->full_name) && $u->full_name !== '') {
            return $u->full_name;
        }
        if (isset($u->name) && $u->name !== '') {
            return $u->name;
        }
        if (isset($u->email) && $u->email !== '') {
            return $u->email;
        }
        return (string) $fallback;
    }
}

if (!function_exists('reminders_fetch_user_contact')) {
    /**
     * @return array{email:string,name:string}
     */
    function reminders_fetch_user_contact($db, $user_id)
    {
        $out = array('email' => '', 'name' => '');
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$db->table_exists('users')) {
            return $out;
        }
        $sel = reminders_user_select_fields($db);
        $u = $db->select(implode(',', $sel), false)->from('users')->where('id', $user_id)->get()->row();
        if (!$u) {
            return $out;
        }
        $out['email'] = isset($u->email) ? (string) $u->email : '';
        $out['name'] = reminders_user_label_from_row($u, $out['email']);
        return $out;
    }
}

if (!function_exists('reminders_admin_from_post')) {
    /**
     * From address fields: admins may override; others get empty strings.
     *
     * @return array{from_email:string,from_name:string}
     */
    function reminders_admin_from_post($role_id, $input)
    {
        $is_admin = in_array((int) $role_id, array(1, 2), true);
        if (!$is_admin) {
            return array('from_email' => '', 'from_name' => '');
        }
        return array(
            'from_email' => trim((string) $input->post('from_email')),
            'from_name'  => trim((string) $input->post('from_name')),
        );
    }
}

if (!function_exists('reminders_resolve_sender_defaults')) {
    /**
     * Fill empty from_email/from_name from system or session user.
     *
     * @return array{from_email:string,from_name:string}
     */
    function reminders_resolve_sender_defaults($db, $from_email, $from_name, $session_user_id = 0, $session_email = '')
    {
        if ($from_email === '') {
            if (!function_exists('get_system_from_email')) {
                $CI =& get_instance();
                $CI->load->helper('email');
            }
            $from_email = get_system_from_email();
        }
        if ($from_name === '') {
            if (function_exists('get_company_name')) {
                $from_name = get_company_name();
            }
        }
        $uid = (int) $session_user_id;
        if ($from_name === '' && $uid > 0 && $db->table_exists('users')) {
            $contact = reminders_fetch_user_contact($db, $uid);
            if ($contact['name'] !== '') {
                $from_name = $contact['name'];
            }
        }
        if ($from_email === '' && $session_email !== '') {
            $from_email = (string) $session_email;
        }
        return array('from_email' => $from_email, 'from_name' => $from_name);
    }
}
