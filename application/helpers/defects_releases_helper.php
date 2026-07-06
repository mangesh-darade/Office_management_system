<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('defects_releases_sees_all_org')) {
    function defects_releases_sees_all_org()
    {
        if (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
            return true;
        }
        if (function_exists('has_module_access') && has_module_access('projects_view_all')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('defects_releases_apply_project_scope')) {
    /**
     * Limit rows to projects the current user belongs to (unless org-wide access).
     *
     * @param CI_DB_query_builder $db
     * @param string              $tableAlias   e.g. d
     * @param string              $projectColumn default project_id
     */
    function defects_releases_apply_project_scope($db, $tableAlias = 'd', $projectColumn = 'project_id')
    {
        if (defects_releases_sees_all_org()) {
            return $db;
        }
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid < 1 || !$CI->db->table_exists('project_members')) {
            $db->where('1 = 0', null, false);
            return $db;
        }
        $col = $tableAlias . '.' . $projectColumn;
        $db->where(
            $col . ' IN (SELECT project_id FROM project_members WHERE user_id = ' . $uid . ')',
            null,
            false
        );
        return $db;
    }
}

if (!function_exists('defects_releases_scoped_project_ids')) {
    function defects_releases_scoped_project_ids()
    {
        $CI =& get_instance();
        if (!$CI->db->table_exists('projects')) {
            return array();
        }
        if (defects_releases_sees_all_org()) {
            $rows = $CI->db->select('id')->get('projects')->result();
            $ids = array();
            foreach ($rows as $r) {
                $ids[] = (int) $r->id;
            }
            return $ids;
        }
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid < 1 || !$CI->db->table_exists('project_members')) {
            return array();
        }
        $rows = $CI->db->select('project_id')
            ->from('project_members')
            ->where('user_id', $uid)
            ->get()
            ->result();
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r->project_id;
        }
        return $ids;
    }
}

if (!function_exists('defect_is_overdue')) {
    function defect_is_overdue($row)
    {
        if (!$row || empty($row->due_date)) {
            return false;
        }
        if (in_array((string) $row->status, array('closed', 'rejected', 'verified'), true)) {
            return false;
        }
        $due = strtotime((string) $row->due_date);
        if (!$due) {
            return false;
        }
        return $due < strtotime(date('Y-m-d'));
    }
}

if (!function_exists('defects_releases_notify_user')) {
    function defects_releases_notify_user($user_id, $title, $message, $module, $related_id, $url)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return;
        }
        $CI =& get_instance();
        if (!$CI->db->table_exists('notifications')) {
            return;
        }
        $CI->load->model('Notification_model', 'defect_release_notif');
        $CI->defect_release_notif->create($user_id, $title, $message, 'info', $module, (int) $related_id, $url);
    }
}

if (!function_exists('defect_notify_assignee')) {
    function defect_notify_assignee($defect_id, $assignee_id, $title, $actor_id)
    {
        $assignee_id = (int) $assignee_id;
        $actor_id = (int) $actor_id;
        $defect_id = (int) $defect_id;
        if ($assignee_id < 1 || $assignee_id === $actor_id) {
            return;
        }
        defects_releases_notify_user(
            $assignee_id,
            'Defect assigned',
            'You were assigned defect: ' . $title,
            'defects',
            $defect_id,
            site_url('defects/view/' . $defect_id)
        );
    }
}

if (!function_exists('defect_notify_status_change')) {
    function defect_notify_status_change($defect_id, $user_id, $title, $old_status, $new_status)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1 || $old_status === $new_status) {
            return;
        }
        defects_releases_notify_user(
            $user_id,
            'Defect status updated',
            $title . ': ' . str_replace('_', ' ', $old_status) . ' → ' . str_replace('_', ' ', $new_status),
            'defects',
            (int) $defect_id,
            site_url('defects/view/' . (int) $defect_id)
        );
    }
}

if (!function_exists('release_notify_status_change')) {
    function release_notify_status_change($release_id, $user_ids, $title, $old_status, $new_status)
    {
        if ($old_status === $new_status || empty($user_ids)) {
            return;
        }
        $msg = $title . ' is now ' . str_replace('_', ' ', $new_status);
        foreach ($user_ids as $uid) {
            defects_releases_notify_user(
                (int) $uid,
                'Release status updated',
                $msg,
                'releases',
                (int) $release_id,
                site_url('releases/view/' . (int) $release_id)
            );
        }
    }
}

if (!function_exists('release_notify_notes_recipients')) {
    function release_notify_notes_recipients(array $user_ids, $release_id, $release_title)
    {
        foreach ($user_ids as $uid) {
            $uid = (int) $uid;
            if ($uid < 1) {
                continue;
            }
            defects_releases_notify_user(
                $uid,
                'Release notes sent',
                'Release notes for: ' . $release_title,
                'releases',
                (int) $release_id,
                site_url('releases/view/' . (int) $release_id)
            );
        }
    }
}

if (!function_exists('defect_handle_uploads')) {
    function defect_handle_uploads($upload_dir)
    {
        $CI =& get_instance();
        if (empty($_FILES['attachments']['name']) || !is_array($_FILES['attachments']['name'])) {
            return array();
        }
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'log', 'zip');
        $max_bytes = 5 * 1024 * 1024;
        $saved = array();
        $count = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES['attachments']['name'][$i]) || (int) $_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            if ((int) $_FILES['attachments']['size'][$i] > $max_bytes) {
                continue;
            }
            $orig = basename((string) $_FILES['attachments']['name'][$i]);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }
            $stored = 'def_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $stored;
            if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $dest)) {
                continue;
            }
            $saved[] = array(
                'original_name' => $orig,
                'stored_name' => $stored,
                'file_size' => (int) $_FILES['attachments']['size'][$i],
            );
        }
        return $saved;
    }
}
