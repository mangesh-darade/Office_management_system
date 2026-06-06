<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('my_works_sees_all_org_data')) {
    /**
     * Org-wide My Works visibility: admin-tier roles only (ROLE_ADMIN, name admin, roles.group_type = admin).
     * Managers/leads/staff always use personal scope (created_by OR created_for = self).
     */
    function my_works_sees_all_org_data()
    {
        return function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data();
    }
}

if (!function_exists('my_works_user_in_personal_scope')) {
    /**
     * Whether a work row is visible to the given user under personal (non-admin) rules.
     */
    function my_works_user_in_personal_scope($work, $user_id)
    {
        if (!$work) {
            return false;
        }
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }
        return ((int) $work->created_by === $user_id) || ((int) $work->created_for === $user_id);
    }
}

if (!function_exists('my_works_type_labels')) {
    function my_works_type_labels()
    {
        $CI =& get_instance();
        $CI->load->helper('types');
        return module_type_options_resolved('my_works');
    }
}

if (!function_exists('my_works_type_label')) {
    function my_works_type_label($key)
    {
        $CI =& get_instance();
        $CI->load->helper('types');
        return module_type_label($key, 'my_works');
    }
}

if (!function_exists('my_works_status_labels')) {
    function my_works_status_labels()
    {
        return array(
            'new'         => 'New',
            'in_progress' => 'In Progress',
            'closed'      => 'Closed',
        );
    }
}

if (!function_exists('my_works_status_colors')) {
    function my_works_status_colors()
    {
        return array(
            'new'         => 'secondary',
            'in_progress' => 'primary',
            'closed'      => 'success',
        );
    }
}

if (!function_exists('my_works_user_label')) {
    function my_works_user_label($name, $email, $user_id = 0)
    {
        $label = trim((string) $name);
        if ($label === '') {
            $label = trim((string) $email);
        }
        if ($label === '' && (int) $user_id > 0) {
            $label = 'User #' . (int) $user_id;
        }
        return $label;
    }
}

if (!function_exists('my_works_format_when')) {
    function my_works_format_when($datetime)
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime((string) $datetime);
        if (!$ts) {
            return (string) $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' hr ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' day ago';
        }
        return date('M j, Y', $ts);
    }
}

if (!function_exists('my_works_row_border_class')) {
    function my_works_row_border_class($row)
    {
        if (!empty($row->is_urgent)) {
            return 'mw-border-urgent';
        }
        if (!empty($row->is_important)) {
            return 'mw-border-important';
        }
        return '';
    }
}

if (!function_exists('my_works_scope_context')) {
    /**
     * Human-readable data scope for list/detail UI (aligned with hierarchy_filter).
     *
     * @param bool  $can_view_all Admin / my_works_view_all
     * @param int[] $allowed_ids  Empty when org-wide
     * @return array
     */
    function my_works_scope_context($can_view_all, $allowed_ids = array())
    {
        $CI =& get_instance();
        $role_id = (int) $CI->session->userdata('role_id');
        $role_name = function_exists('get_role_name_by_id') ? get_role_name_by_id($role_id) : '';

        $ctx = array(
            'sees_all' => (bool) $can_view_all,
            'team_scope' => false,
            'own_only' => false,
            'role_label' => $role_name,
            'message' => '',
            'alert_class' => 'info',
        );

        if ($can_view_all) {
            $ctx['message'] = 'Admin view: all work items across the organization.';
            $ctx['alert_class'] = 'primary';
            return $ctx;
        }

        $ctx['own_only'] = true;
        $ctx['message'] = 'Personal view: only work items you created or that are assigned to you.';
        $ctx['alert_class'] = 'secondary';
        return $ctx;
    }
}

if (!function_exists('my_works_parse_tags')) {
    function my_works_parse_tags($tag_string)
    {
        $tag_string = trim((string) $tag_string);
        if ($tag_string === '') {
            return array();
        }
        $parts = preg_split('/\s*,\s*/', $tag_string);
        $out = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }
        return array_values(array_unique($out));
    }
}

if (!function_exists('my_works_normalize_tags')) {
    function my_works_normalize_tags($tag_string)
    {
        $tags = my_works_parse_tags($tag_string);
        return empty($tags) ? null : implode(', ', $tags);
    }
}

if (!function_exists('my_works_is_overdue')) {
    function my_works_is_overdue($row)
    {
        if (!$row || empty($row->due_date) || (isset($row->status) && $row->status === 'closed')) {
            return false;
        }
        $due = strtotime((string) $row->due_date);
        if (!$due) {
            return false;
        }
        return $due < strtotime(date('Y-m-d'));
    }
}

if (!function_exists('my_works_notify_assignee')) {
    function my_works_notify_assignee($work_id, $assignee_user_id, $title, $creator_user_id)
    {
        $assignee_user_id = (int) $assignee_user_id;
        $creator_user_id = (int) $creator_user_id;
        $work_id = (int) $work_id;
        if ($assignee_user_id < 1 || $assignee_user_id === $creator_user_id) {
            return;
        }
        $CI =& get_instance();
        $url = site_url('my-works/' . $work_id);
        $msg = 'A work item was assigned to you: ' . $title;
        if ($CI->db->table_exists('notifications')) {
            $CI->load->model('Notification_model', 'notif');
            $CI->notif->create($assignee_user_id, 'My Works assignment', $msg, 'info', 'my_works', $work_id, $url);
        }
    }
}

if (!function_exists('my_works_safe_redirect')) {
    function my_works_safe_redirect($url, $fallback = 'my-works')
    {
        $url = trim((string) $url);
        if ($url === '') {
            return site_url($fallback);
        }
        if (strpos($url, '://') !== false) {
            return site_url($fallback);
        }
        if ($url[0] === '/') {
            return site_url(ltrim($url, '/'));
        }
        return site_url($url);
    }
}
