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

if (!function_exists('my_works_can_add')) {
    /**
     * Create / quick-add: full module, explicit add key, or list access (personal work tracker).
     */
    function my_works_can_add()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('my_works')
            || has_module_access('my_works_add')
            || has_module_access('my_works_list');
    }
}

if (!function_exists('my_works_require_add_access')) {
    function my_works_require_add_access()
    {
        if (!my_works_can_add()) {
            $CI =& get_instance();
            $CI->session->set_flashdata('access_denied', 'You do not have permission to add work items.');
            redirect('my-works');
            exit;
        }
        return true;
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
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        $labels = array();
        foreach (my_works_status_records() as $row) {
            $labels[(string) $row->code] = (string) $row->name;
        }
        if (!empty($labels)) {
            return $labels;
        }
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
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        $colors = array();
        foreach (my_works_status_records() as $row) {
            $colors[(string) $row->code] = my_works_status_bootstrap_class($row->code);
        }
        if (!empty($colors)) {
            return $colors;
        }
        return array(
            'new'         => 'secondary',
            'in_progress' => 'primary',
            'closed'      => 'success',
        );
    }
}

if (!function_exists('my_works_priority_label')) {
    function my_works_priority_label($row)
    {
        if ((int) $row->is_urgent === 1 && (int) $row->is_important === 1) {
            return 'High';
        }
        if ((int) $row->is_urgent === 1 || (int) $row->is_important === 1) {
            return 'Medium';
        }
        return 'Normal';
    }
}

if (!function_exists('my_works_priority_class')) {
    function my_works_priority_class($row)
    {
        if ((int) $row->is_urgent === 1 && (int) $row->is_important === 1) {
            return 'high';
        }
        if ((int) $row->is_urgent === 1 || (int) $row->is_important === 1) {
            return 'medium';
        }
        return 'normal';
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

if (!function_exists('my_works_matrix_quadrants')) {
    function my_works_matrix_quadrants()
    {
        if (!function_exists('apm_quadrants')) {
            $CI =& get_instance();
            $CI->load->helper('action_priority_matrix');
        }
        return apm_quadrants();
    }
}

if (!function_exists('my_works_matrix_high_effort')) {
    function my_works_matrix_high_effort($row)
    {
        if ((int) (isset($row->is_urgent) ? $row->is_urgent : 0) === 1) {
            return true;
        }
        if (isset($row->status) && (string) $row->status === 'in_progress') {
            return true;
        }
        if (!empty($row->task_id)) {
            return true;
        }
        if (!empty($row->attachment_stored)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('my_works_matrix_quadrant_for_row')) {
    function my_works_matrix_quadrant_for_row($row)
    {
        if (!function_exists('apm_quadrant_from_axes')) {
            $CI =& get_instance();
            $CI->load->helper('action_priority_matrix');
        }
        $high_impact = (int) (isset($row->is_important) ? $row->is_important : 0) === 1;
        return apm_quadrant_from_axes($high_impact, my_works_matrix_high_effort($row));
    }
}

if (!function_exists('my_works_build_matrix_columns')) {
    /**
     * @param object[] $rows
     * @param bool $exclude_closed Skip closed items when no status filter is applied
     */
    function my_works_build_matrix_columns(array $rows, $exclude_closed = true)
    {
        $defs = my_works_matrix_quadrants();
        $columns = array();
        foreach (array_keys($defs) as $key) {
            $columns[$key] = array();
        }
        foreach ($rows as $row) {
            if ($exclude_closed && isset($row->status) && (string) $row->status === 'closed') {
                continue;
            }
            $q = my_works_matrix_quadrant_for_row($row);
            if (!isset($columns[$q])) {
                $q = 'fill_ins';
            }
            $columns[$q][] = $row;
        }
        return $columns;
    }
}

if (!function_exists('my_works_matrix_flags_from_quadrant')) {
    function my_works_matrix_flags_from_quadrant($quadrant)
    {
        $map = array(
            'quick_wins'       => array('is_important' => 1, 'is_urgent' => 0),
            'major_projects'   => array('is_important' => 1, 'is_urgent' => 1),
            'fill_ins'         => array('is_important' => 0, 'is_urgent' => 0),
            'hard_slogs'       => array('is_important' => 0, 'is_urgent' => 1),
        );
        $quadrant = (string) $quadrant;
        return isset($map[$quadrant]) ? $map[$quadrant] : null;
    }
}

if (!function_exists('my_works_format_file_size')) {
    function my_works_format_file_size($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes < 1) {
            return '';
        }
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        if ($i === 0) {
            return (int) $n . ' ' . $units[$i];
        }
        return number_format($n, 1) . ' ' . $units[$i];
    }
}

if (!function_exists('my_works_attachment_extension')) {
    function my_works_attachment_extension($filename)
    {
        $filename = strtolower(trim((string) $filename));
        if ($filename === '' || strpos($filename, '.') === false) {
            return '';
        }
        $parts = explode('.', $filename);
        return strtolower((string) end($parts));
    }
}

if (!function_exists('my_works_attachment_kind')) {
    /**
     * @return string video|image|audio|document|archive|file
     */
    function my_works_attachment_kind($original_name, $stored_name = '')
    {
        $ext = my_works_attachment_extension($original_name);
        if ($ext === '' && $stored_name !== '') {
            $ext = my_works_attachment_extension($stored_name);
        }
        if (in_array($ext, array('mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'wmv', 'flv', 'mpeg', 'mpg', '3gp'), true)) {
            return 'video';
        }
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'), true)) {
            return 'image';
        }
        if (in_array($ext, array('mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'), true)) {
            return 'audio';
        }
        if (in_array($ext, array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'), true)) {
            return 'document';
        }
        if (in_array($ext, array('zip', 'rar', '7z', 'tar', 'gz'), true)) {
            return 'archive';
        }
        return 'file';
    }
}

if (!function_exists('my_works_attachment_mime_type')) {
    function my_works_attachment_mime_type($filename)
    {
        $ext = my_works_attachment_extension($filename);
        $map = array(
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska', 'm4v' => 'video/x-m4v',
            'wmv' => 'video/x-ms-wmv', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', '3gp' => 'video/3gpp',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'bmp' => 'image/bmp', 'svg' => 'image/svg+xml',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
            'pdf' => 'application/pdf',
        );
        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }
}

if (!function_exists('my_works_attachment_icon_class')) {
    function my_works_attachment_icon_class($kind)
    {
        $icons = array(
            'video'    => 'bi-file-play-fill',
            'image'    => 'bi-file-image-fill',
            'audio'    => 'bi-file-music-fill',
            'document' => 'bi-file-earmark-text-fill',
            'archive'  => 'bi-file-zip-fill',
            'file'     => 'bi-file-earmark-fill',
        );
        return isset($icons[$kind]) ? $icons[$kind] : $icons['file'];
    }
}

if (!function_exists('my_works_attachment_badge_label')) {
    function my_works_attachment_badge_label($kind)
    {
        $labels = array(
            'video' => 'Video', 'image' => 'Image', 'audio' => 'Audio',
            'document' => 'Document', 'archive' => 'Archive', 'file' => 'File',
        );
        return isset($labels[$kind]) ? $labels[$kind] : 'File';
    }
}

if (!function_exists('my_works_attachment_meta')) {
    /**
     * @param object $row Work row with attachment_original, attachment_stored
     * @return array|null
     */
    function my_works_attachment_meta($row)
    {
        if (!$row || empty($row->id)) {
            return null;
        }
        $CI =& get_instance();
        if ($CI->db->table_exists('my_work_attachments')) {
            $atts = my_works_attachments_for_work($CI->db, (int) $row->id);
            if (!empty($atts)) {
                return $atts[0];
            }
        }
        if (empty($row->attachment_stored)) {
            return null;
        }
        $name = !empty($row->attachment_original) ? (string) $row->attachment_original : (string) $row->attachment_stored;
        $kind = my_works_attachment_kind($name, (string) $row->attachment_stored);
        $size_label = '';
        $stored = (string) $row->attachment_stored;
        if ($stored !== '') {
            $path = FCPATH . 'uploads/my_works/' . $stored;
            if (is_file($path)) {
                $bytes = (int) filesize($path);
                if ($bytes > 0) {
                    $size_label = my_works_format_file_size($bytes);
                }
            }
        }
        return array(
            'name'         => $name,
            'kind'         => $kind,
            'icon'         => my_works_attachment_icon_class($kind),
            'label'        => my_works_attachment_badge_label($kind),
            'download_url' => site_url('my-works/' . (int) $row->id . '/download'),
            'preview_url'  => site_url('my-works/' . (int) $row->id . '/preview'),
            'can_preview'  => in_array($kind, array('video', 'image', 'audio'), true),
            'is_video'     => ($kind === 'video'),
            'size_label'   => $size_label,
        );
    }
}

if (!function_exists('my_works_render_details')) {
    /**
     * Safe HTML output for work item details (rich text from editor).
     */
    function my_works_render_details($details)
    {
        if ($details === null || trim((string) $details) === '') {
            return '';
        }
        $details = (string) $details;
        if (strpos($details, '<') === false) {
            return nl2br(esc_view($details));
        }
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span><div><del><sub><sup><table><thead><tbody><tr><th><td>';
        return strip_tags($details, $allowed);
    }
}

if (!function_exists('my_works_can_view_projects')) {
    function my_works_can_view_projects()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('projects') || has_module_access('projects_list');
    }
}

if (!function_exists('my_works_dashboard_lane_keys')) {
    function my_works_dashboard_lane_keys()
    {
        return array('yesterday', 'todays_plan', 'future_pipeline', 'back_log', 'need_discussion');
    }
}

if (!function_exists('my_works_dashboard_lane_labels')) {
    function my_works_dashboard_lane_labels()
    {
        return array(
            'yesterday'        => 'Yesterday',
            'todays_plan'      => "Today's Plan",
            'future_pipeline'  => 'Future Pipeline',
            'back_log'         => 'Back Log',
            'need_discussion'  => 'Need Discussion',
        );
    }
}

if (!function_exists('my_works_dashboard_lane_is_valid')) {
    function my_works_dashboard_lane_is_valid($lane)
    {
        return in_array((string) $lane, my_works_dashboard_lane_keys(), true);
    }
}

if (!function_exists('my_works_strip_lane_routing_tags')) {
    function my_works_strip_lane_routing_tags($tag_string)
    {
        $tags = my_works_parse_tags($tag_string);
        $out = array();
        foreach ($tags as $tag) {
            $tl = strtolower((string) $tag);
            if (strpos($tl, 'discussion') !== false || strpos($tl, 'postponed') !== false) {
                continue;
            }
            $out[] = $tag;
        }
        return $out;
    }
}

if (!function_exists('my_works_lane_updates_for_drop')) {
    /**
     * Field updates when a work item is dropped onto an overview lane.
     *
     * @return array|false
     */
    function my_works_lane_updates_for_drop($lane, $item)
    {
        if (!my_works_dashboard_lane_is_valid($lane) || !$item) {
            return false;
        }

        $CI =& get_instance();
        $CI->load->helper('my_works_status');

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $backlog_date = date('Y-m-d', strtotime('-3 days'));
        $current_status = isset($item->status) ? strtolower(trim((string) $item->status)) : 'new';
        $tags = my_works_parse_tags(isset($item->tag) ? $item->tag : '');
        $updates = array();

        if ($lane === 'need_discussion') {
            $updates['status'] = 'need_discussion';
            $has_discussion_tag = false;
            foreach ($tags as $tag) {
                if (strpos(strtolower((string) $tag), 'discussion') !== false) {
                    $has_discussion_tag = true;
                    break;
                }
            }
            if (!$has_discussion_tag) {
                $tags[] = 'discussion';
                $updates['tag'] = my_works_normalize_tags(implode(', ', $tags));
            }
            return $updates;
        }

        if (in_array($current_status, array('need_discussion', 'needs_discussion', 'postponed'), true)) {
            $updates['status'] = 'in_progress';
        }

        $tags = my_works_strip_lane_routing_tags(isset($item->tag) ? $item->tag : '');
        $updates['tag'] = empty($tags) ? null : my_works_normalize_tags(implode(', ', $tags));

        if ($lane === 'future_pipeline') {
            $updates['status'] = 'postponed';
            $due = !empty($item->due_date) ? (string) $item->due_date : '';
            if ($due === '' || $due <= $today) {
                $updates['due_date'] = $tomorrow;
            }
            return $updates;
        }

        if ($lane === 'todays_plan') {
            $updates['due_date'] = $today;
            return $updates;
        }

        if ($lane === 'yesterday') {
            $updates['due_date'] = $yesterday;
            return $updates;
        }

        if ($lane === 'back_log') {
            $updates['due_date'] = $backlog_date;
            return $updates;
        }

        return false;
    }
}

if (!function_exists('my_works_dashboard_lane_for_row')) {
    /**
     * Overview column placement: status lanes first, then due-date lanes.
     */
    function my_works_dashboard_lane_for_row($row)
    {
        $CI =& get_instance();
        $CI->load->helper('my_works_status');

        $st = isset($row->status) ? strtolower(trim((string) $row->status)) : '';
        if ($st === 'need_discussion' || $st === 'needs_discussion') {
            return 'need_discussion';
        }
        if ($st === 'postponed') {
            return 'future_pipeline';
        }

        $tags = my_works_parse_tags(isset($row->tag) ? $row->tag : '');
        foreach ($tags as $tag) {
            $tl = strtolower((string) $tag);
            if (strpos($tl, 'discussion') !== false) {
                return 'need_discussion';
            }
            if (strpos($tl, 'postponed') !== false) {
                return 'future_pipeline';
            }
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $due = !empty($row->due_date) ? (string) $row->due_date : '';
        if ($due !== '') {
            if ($due > $today) {
                return 'future_pipeline';
            }
            if ($due === $today) {
                return 'todays_plan';
            }
            if ($due === $yesterday) {
                return 'yesterday';
            }
            return 'back_log';
        }

        // No due date: treat as recent/last-updated bucket (Yesterday lane).
        return 'yesterday';
    }
}

if (!function_exists('my_works_build_dashboard_sections')) {
    /**
     * @return array{ad_hoc: array<string, array>, project: array<string, array>, counts: array<string, int>}
     */
    function my_works_build_dashboard_sections(array $rows, $exclude_closed = true)
    {
        $lanes = my_works_dashboard_lane_keys();
        $sections = array(
            'ad_hoc'  => array(),
            'project' => array(),
        );
        foreach ($lanes as $lane) {
            $sections['ad_hoc'][$lane] = array();
            $sections['project'][$lane] = array();
        }
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        foreach ($rows as $row) {
            if ($exclude_closed && isset($row->status) && my_works_status_is_closed($row->status)) {
                continue;
            }
            $lane = my_works_dashboard_lane_for_row($row);
            if (!isset($sections['ad_hoc'][$lane])) {
                $lane = 'back_log';
            }
            $has_project = (!empty($row->project_id) && (int) $row->project_id > 0) || !empty($row->project_name);
            if ($has_project) {
                $sections['project'][$lane][] = $row;
            } else {
                $sections['ad_hoc'][$lane][] = $row;
            }
        }
        $counts = array(
            'ad_hoc'  => 0,
            'project' => 0,
            'total'   => 0,
        );
        foreach (array('ad_hoc', 'project') as $section_key) {
            foreach ($lanes as $lane) {
                $n = count($sections[$section_key][$lane]);
                $counts[$section_key] += $n;
                $counts['total'] += $n;
            }
        }
        return array(
            'sections' => $sections,
            'counts'   => $counts,
        );
    }
}

if (!function_exists('my_works_dashboard_lane_focus_pages')) {
    /**
     * Full-view page metadata per overview lane (route, title, view tab).
     *
     * @return array<string, array{route:string,page_title:string,active_tab:string,body_class:string}>
     */
    function my_works_dashboard_lane_focus_pages()
    {
        return array(
            'todays_plan'     => array(
                'route'       => 'my-works/todays-focus',
                'page_title'  => "Today's Focus",
                'active_tab'  => 'todays-focus',
                'body_class'  => 'mw-body-todays-focus',
            ),
            'yesterday'       => array(
                'route'       => 'my-works/yesterday',
                'page_title'  => 'Yesterday',
                'active_tab'  => '',
                'body_class'  => 'mw-body-lane-yesterday',
            ),
            'future_pipeline' => array(
                'route'       => 'my-works/future-pipeline',
                'page_title'  => 'Future Pipeline',
                'active_tab'  => '',
                'body_class'  => 'mw-body-lane-future-pipeline',
            ),
            'back_log'        => array(
                'route'       => 'my-works/back-log',
                'page_title'  => 'Back Log',
                'active_tab'  => '',
                'body_class'  => 'mw-body-lane-back-log',
            ),
            'need_discussion' => array(
                'route'       => 'my-works/need-discussion',
                'page_title'  => 'Need Discussion',
                'active_tab'  => '',
                'body_class'  => 'mw-body-lane-need-discussion',
            ),
        );
    }
}

if (!function_exists('my_works_dashboard_lane_focus_route')) {
    function my_works_dashboard_lane_focus_route($lane)
    {
        $pages = my_works_dashboard_lane_focus_pages();
        $lane = (string) $lane;
        return isset($pages[$lane]['route']) ? (string) $pages[$lane]['route'] : '';
    }
}

if (!function_exists('my_works_build_lane_focus_sections')) {
    /**
     * Single lane full view merging ad hoc + project items.
     *
     * @return array{sections: array, count: int}
     */
    function my_works_build_lane_focus_sections(array $rows, $lane_key, $exclude_closed = true)
    {
        if (!my_works_dashboard_lane_is_valid($lane_key)) {
            return array(
                'sections' => array('focus' => array()),
                'count'    => 0,
            );
        }
        $dash = my_works_build_dashboard_sections($rows, $exclude_closed);
        $merged = array_merge(
            isset($dash['sections']['ad_hoc'][$lane_key]) ? $dash['sections']['ad_hoc'][$lane_key] : array(),
            isset($dash['sections']['project'][$lane_key]) ? $dash['sections']['project'][$lane_key] : array()
        );
        $merged = my_works_dashboard_lane_sort_by_date_asc($merged);
        return array(
            'sections' => array(
                'focus' => array(
                    $lane_key => $merged,
                ),
            ),
            'count' => count($merged),
        );
    }
}

if (!function_exists('my_works_build_todays_focus_sections')) {
    /**
     * Single Today's Plan lane merging ad hoc + project items.
     *
     * @return array{sections: array, count: int}
     */
    function my_works_build_todays_focus_sections(array $rows, $exclude_closed = true)
    {
        return my_works_build_lane_focus_sections($rows, 'todays_plan', $exclude_closed);
    }
}

if (!function_exists('my_works_dashboard_status_dot')) {
    function my_works_dashboard_status_dot($row)
    {
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        $st = isset($row->status) ? (string) $row->status : my_works_status_default_code();
        return my_works_status_dashboard_dot_class($st);
    }
}

if (!function_exists('my_works_short_user_name')) {
    function my_works_short_user_name($name, $email, $user_id = 0)
    {
        $label = my_works_user_label($name, $email, $user_id);
        $parts = preg_split('/\s+/', trim($label));
        if (count($parts) >= 2) {
            $last = array_pop($parts);
            $lastInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $last), 0, 1));
            if ($lastInitial === '') {
                return $label;
            }
            return implode(' ', $parts) . ' ' . $lastInitial . '.';
        }
        return $label;
    }
}

if (!function_exists('my_works_dashboard_project_label')) {
    function my_works_dashboard_project_label($row)
    {
        if (!empty($row->project_name)) {
            return (string) $row->project_name;
        }
        if (!empty($row->client_name)) {
            return (string) $row->client_name;
        }
        return '—';
    }
}

if (!function_exists('my_works_dashboard_group_lane_by_project')) {
    /**
     * Group overview lane rows by project (ad hoc items last, no header).
     *
     * @param array $items
     * @return array<int, array{label: string, project_id: int, items: array}>
     */
    function my_works_dashboard_group_lane_by_project(array $items)
    {
        if (empty($items)) {
            return array();
        }
        $groups = array();
        $order = array();
        foreach ($items as $row) {
            $pid = !empty($row->project_id) ? (int) $row->project_id : 0;
            $key = $pid > 0 ? 'p_' . $pid : 'adhoc';
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'label'      => $pid > 0 ? my_works_dashboard_project_label($row) : '',
                    'project_id' => $pid,
                    'items'      => array(),
                );
                $order[] = $key;
            }
            $groups[$key]['items'][] = $row;
        }
        $result = array();
        foreach ($order as $key) {
            $result[] = $groups[$key];
        }
        usort($result, function ($a, $b) {
            if ($a['project_id'] === 0 && $b['project_id'] === 0) {
                return 0;
            }
            if ($a['project_id'] === 0) {
                return 1;
            }
            if ($b['project_id'] === 0) {
                return -1;
            }
            return strcasecmp($a['label'], $b['label']);
        });
        return $result;
    }
}

if (!function_exists('my_works_dashboard_lane_shows_date')) {
    function my_works_dashboard_lane_shows_date($lane)
    {
        return in_array((string) $lane, array('future_pipeline', 'back_log'), true);
    }
}

if (!function_exists('my_works_dashboard_lane_date_label')) {
    /**
     * Due date for overview lane Date column; falls back to last updated date.
     */
    function my_works_dashboard_lane_date_label($row)
    {
        $raw = '';
        if (!empty($row->due_date)) {
            $raw = (string) $row->due_date;
        } elseif (!empty($row->updated_at)) {
            $raw = substr((string) $row->updated_at, 0, 10);
        }
        if ($raw === '') {
            return '—';
        }
        $ts = strtotime($raw);
        if (!$ts) {
            return $raw;
        }
        return date('M j, Y', $ts);
    }
}

if (!function_exists('my_works_dashboard_lane_date_raw')) {
    function my_works_dashboard_lane_date_raw($row)
    {
        if (!empty($row->due_date)) {
            return (string) $row->due_date;
        }
        if (!empty($row->updated_at)) {
            return substr((string) $row->updated_at, 0, 10);
        }
        return '';
    }
}

if (!function_exists('my_works_dashboard_lane_sort_by_date_asc')) {
    /**
     * Sort overview lane rows by due/updated date ascending (empty dates last).
     *
     * @param array $items
     * @return array
     */
    function my_works_dashboard_lane_sort_by_date_asc(array $items)
    {
        if (count($items) < 2) {
            return $items;
        }
        usort($items, function ($a, $b) {
            $da = my_works_dashboard_lane_date_raw($a);
            $db = my_works_dashboard_lane_date_raw($b);
            if ($da === $db) {
                $ta = isset($a->title) ? (string) $a->title : '';
                $tb = isset($b->title) ? (string) $b->title : '';
                return strcasecmp($ta, $tb);
            }
            if ($da === '') {
                return 1;
            }
            if ($db === '') {
                return -1;
            }
            return strcmp($da, $db);
        });
        return $items;
    }
}

if (!function_exists('my_works_dashboard_week_range_label')) {
    function my_works_dashboard_week_range_label()
    {
        $start = strtotime('monday this week');
        $end = strtotime('sunday this week');
        return date('j M', $start) . ' – ' . date('j M Y', $end);
    }
}
