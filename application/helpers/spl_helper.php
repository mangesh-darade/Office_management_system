<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('spl_access_module_keys')) {
    function spl_access_module_keys()
    {
        return array(
            'spl', 'spl_my_reward', 'spl_submit', 'spl_approve', 'spl_rules',
            'spl_groups', 'spl_groups_manage',
            'rewards', 'rewards_submit', 'rewards_rules', 'rewards_admin', 'rewards_approve', 'rewards_leaderboard',
        );
    }
}

if (!function_exists('spl_can_access')) {
    function spl_can_access()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        foreach (spl_access_module_keys() as $key) {
            if (has_module_access($key)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('spl_can_my_reward')) {
    function spl_can_my_reward()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('spl')
            || has_module_access('spl_my_reward')
            || has_module_access('rewards');
    }
}

if (!function_exists('spl_can_submit')) {
    function spl_can_submit()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('spl')
            || has_module_access('spl_submit')
            || has_module_access('rewards_submit')
            || has_module_access('rewards');
    }
}

if (!function_exists('spl_can_view_levels')) {
    function spl_can_view_levels()
    {
        return spl_can_my_reward()
            || spl_can_submit()
            || spl_can_approve()
            || spl_can_manage_rules();
    }
}

if (!function_exists('spl_level_emoji')) {
    function spl_level_emoji($code)
    {
        $map = array(
            'starter' => '🌱',
            'bronze' => '🥉',
            'silver' => '🥈',
            'gold' => '🥇',
            'platinum' => '💎',
            'legend' => '🚀',
        );
        $code = strtolower(trim((string) $code));
        return isset($map[$code]) ? $map[$code] : '🏅';
    }
}

if (!function_exists('spl_format_level_points_range')) {
    function spl_format_level_points_range($level)
    {
        if (!$level) {
            return '—';
        }
        $min = (float) $level->min_lifetime_points;
        $max = $level->max_lifetime_points;
        if ($max === null || $max === '') {
            return number_format($min, 0) . '+';
        }
        return number_format($min, 0) . '–' . number_format((float) $max, 0);
    }
}

if (!function_exists('spl_can_manage_rules')) {
    function spl_can_manage_rules()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('spl')
            || has_module_access('spl_rules')
            || has_module_access('rewards_rules')
            || has_module_access('rewards_admin')
            || has_module_access('rewards');
    }
}

if (!function_exists('spl_can_manage_groups')) {
    function spl_can_manage_groups()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        if (has_module_access('spl')
            || has_module_access('spl_groups_manage')
            || has_module_access('rewards_admin')
            || has_module_access('rewards')) {
            return true;
        }
        if (function_exists('is_admin_group') && is_admin_group()) {
            return true;
        }
        return false;
    }
}

if (!function_exists('spl_require_manage_groups')) {
    function spl_require_manage_groups()
    {
        $CI =& get_instance();
        if (!(int) $CI->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }
        if (!spl_can_manage_groups()) {
            $CI->session->set_flashdata('error', 'You do not have permission to manage SPL groups.');
            redirect('spl/groups');
        }
    }
}

if (!function_exists('spl_can_approve')) {
    function spl_can_approve()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('spl')
            || has_module_access('spl_approve')
            || has_module_access('rewards_approve')
            || has_module_access('rewards_admin');
    }
}

if (!function_exists('spl_can_view_groups')) {
    function spl_can_view_groups()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('spl')
            || has_module_access('spl_groups')
            || has_module_access('spl_groups_manage')
            || has_module_access('rewards')
            || has_module_access('rewards_admin');
    }
}

if (!function_exists('spl_dashboard_url')) {
    function spl_dashboard_url($tab = '', $extra = array())
    {
        $params = is_array($extra) ? $extra : array();
        $tab = trim((string) $tab);
        if ($tab !== '' && $tab !== 'overview') {
            $params['tab'] = $tab;
        }
        $qs = http_build_query($params);
        return site_url('spl/dashboard' . ($qs !== '' ? '?' . $qs : ''));
    }
}

if (!function_exists('spl_normalize_unified_tab')) {
    function spl_normalize_unified_tab($tab)
    {
        $tab = trim((string) $tab);
        if ($tab === '') {
            return 'overview';
        }
        $valid = array('overview', 'my-reward', 'levels', 'activity', 'approvals', 'rules', 'groups');
        if (!in_array($tab, $valid, true)) {
            return 'overview';
        }
        return $tab;
    }
}

if (!function_exists('spl_tab_allowed')) {
    function spl_tab_allowed($tab)
    {
        switch (spl_normalize_unified_tab($tab)) {
            case 'overview':
                return spl_can_access();
            case 'my-reward':
                return spl_can_my_reward();
            case 'levels':
                return spl_can_view_levels();
            case 'activity':
                return spl_can_submit();
            case 'approvals':
                return spl_can_approve();
            case 'rules':
                return spl_can_manage_rules();
            case 'groups':
                return spl_can_view_groups();
            default:
                return false;
        }
    }
}

if (!function_exists('spl_resolve_unified_tab')) {
    function spl_resolve_unified_tab($tab)
    {
        $tab = spl_normalize_unified_tab($tab);
        if (spl_tab_allowed($tab)) {
            return $tab;
        }
        foreach (array('overview', 'my-reward', 'levels', 'activity', 'approvals', 'rules', 'groups') as $candidate) {
            if (spl_tab_allowed($candidate)) {
                return $candidate;
            }
        }
        return 'overview';
    }
}

if (!function_exists('spl_resolve_default_tab')) {
    function spl_resolve_default_tab()
    {
        if (spl_can_my_reward()) {
            return 'my-reward';
        }
        if (spl_can_submit()) {
            return 'activity';
        }
        if (spl_can_approve()) {
            return 'approvals';
        }
        if (spl_can_manage_rules()) {
            return 'rules';
        }
        if (spl_can_view_groups()) {
            return 'groups';
        }
        return 'my-reward';
    }
}

if (!function_exists('spl_has_any_index_tab')) {
    function spl_has_any_index_tab()
    {
        return spl_can_my_reward()
            || spl_can_submit()
            || spl_can_approve()
            || spl_can_manage_rules()
            || spl_can_view_levels();
    }
}

if (!function_exists('spl_valid_reward_periods')) {
    function spl_valid_reward_periods()
    {
        return array('today', 'week', 'month', 'all');
    }
}

if (!function_exists('spl_normalize_reward_period')) {
    function spl_normalize_reward_period($period)
    {
        $period = strtolower(trim((string) $period));
        if (!in_array($period, spl_valid_reward_periods(), true)) {
            return 'week';
        }
        return $period;
    }
}

if (!function_exists('spl_reward_period_bounds')) {
    function spl_reward_period_bounds($period)
    {
        $period = spl_normalize_reward_period($period);
        $today = date('Y-m-d');
        if ($period === 'today') {
            return array(
                'key' => 'today',
                'from' => $today,
                'to' => $today,
                'label' => 'Today',
            );
        }
        if ($period === 'week') {
            $dayOfWeek = (int) date('N');
            $from = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days'));
            return array(
                'key' => 'week',
                'from' => $from,
                'to' => $today,
                'label' => 'This week',
            );
        }
        if ($period === 'month') {
            return array(
                'key' => 'month',
                'from' => date('Y-m-01'),
                'to' => $today,
                'label' => 'This month',
            );
        }
        return array(
            'key' => 'all',
            'from' => null,
            'to' => null,
            'label' => 'All time',
        );
    }
}

if (!function_exists('spl_groups_url')) {
    function spl_groups_url($reward_period = 'week')
    {
        return spl_dashboard_url('groups', array(
            'reward_period' => spl_normalize_reward_period($reward_period),
        ));
    }
}

if (!function_exists('spl_list_users_for_groups')) {
    function spl_list_users_for_groups()
    {
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('users')) {
            return array();
        }
        $CI->load->helper('schema_columns');
        $db = $CI->db;

        if ($db->table_exists('employees') && schema_table_has_column($db, 'employees', 'user_id') && schema_table_has_column($db, 'employees', 'name')) {
            $db->select('users.id, users.email, employees.name AS name', false);
            $db->from('users');
            $db->join('employees', 'employees.user_id = users.id', 'left');
            if (schema_table_has_column($db, 'users', 'status')) {
                $db->where('users.status', 'active');
            }
            $db->order_by('employees.name IS NULL', 'ASC', false);
            $db->order_by('employees.name', 'ASC');
            $rows = $db->get()->result();
            foreach ($rows as $row) {
                if (trim((string) $row->name) === '' && schema_table_has_column($db, 'users', 'name')) {
                    $u = $db->select('name')->from('users')->where('id', (int) $row->id)->get()->row();
                    if ($u && trim((string) $u->name) !== '') {
                        $row->name = $u->name;
                    }
                }
                if (trim((string) $row->name) === '') {
                    $row->name = $row->email;
                }
            }
            return $rows;
        }

        $db->select('id, name, email');
        if (schema_table_has_column($db, 'users', 'status')) {
            $db->where('status', 'active');
        }
        $db->order_by('name', 'ASC');
        $rows = $db->get('users')->result();
        foreach ($rows as $row) {
            if (trim((string) $row->name) === '') {
                $row->name = $row->email;
            }
        }
        return $rows;
    }
}

if (!function_exists('spl_poster_url')) {
    function spl_poster_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        return base_url(ltrim(str_replace('\\', '/', $path), '/'));
    }
}

if (!function_exists('spl_poster_fs_path')) {
    function spl_poster_fs_path($path)
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        if ($path === '' || strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return '';
        }
        return rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
    }
}

if (!function_exists('spl_poster_info')) {
    function spl_poster_info($path)
    {
        $info = array(
            'url' => '',
            'width' => 0,
            'height' => 0,
            'dimensions' => '',
            'exists' => false,
        );
        $path = trim((string) $path);
        if ($path === '') {
            return $info;
        }
        $fs = spl_poster_fs_path($path);
        if ($fs === '' || !is_file($fs)) {
            return $info;
        }
        $info['exists'] = true;
        $info['url'] = spl_poster_url($path);
        $size = @getimagesize($fs);
        if (is_array($size)) {
            $info['width'] = (int) $size[0];
            $info['height'] = (int) $size[1];
            if ($info['width'] > 0 && $info['height'] > 0) {
                $info['dimensions'] = $info['width'] . ' × ' . $info['height'] . ' px';
            }
        }
        return $info;
    }
}

if (!function_exists('spl_save_evidence_file')) {
    function spl_save_evidence_file($queue_id, $uploaded_by)
    {
        $CI =& get_instance();
        $queue_id = (int) $queue_id;
        $uploaded_by = (int) $uploaded_by;
        if ($queue_id <= 0 || empty($_FILES['attachment']['name'])) {
            return false;
        }
        $dir = FCPATH . 'uploads/spl/evidence/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $orig = (string) $_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = array('pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx');
        if (!in_array($ext, $allowed, true)) {
            return false;
        }
        $safe = 'spl_ev_' . $queue_id . '_' . time() . '.' . $ext;
        $dest = $dir . $safe;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
            return false;
        }
        $CI->db->insert('reward_evidence', array(
            'approval_queue_id' => $queue_id,
            'file_path' => 'uploads/spl/evidence/' . $safe,
            'file_name' => $orig,
            'uploaded_by' => $uploaded_by,
        ));
        return true;
    }
}

if (!function_exists('spl_enrich_approval_rows')) {
    function spl_enrich_approval_rows($rewards, array $rows)
    {
        foreach ($rows as $idx => $row) {
            $evidence = $rewards->get_evidence_for_queue((int) $row->id);
            $rows[$idx]->evidence_file = ($evidence && !empty($evidence->file_path)) ? $evidence->file_path : '';
            $rows[$idx]->evidence_name = ($evidence && !empty($evidence->file_name)) ? $evidence->file_name : '';
        }
        return $rows;
    }
}

if (!function_exists('spl_sanitize_note_html')) {
    function spl_sanitize_note_html($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span><div><del><sub><sup>';
        $clean = strip_tags($html, $allowed);
        return trim($clean);
    }
}

if (!function_exists('spl_render_note_html')) {
    function spl_render_note_html($html)
    {
        $clean = spl_sanitize_note_html($html);
        if ($clean === '') {
            return '—';
        }
        return $clean;
    }
}

if (!function_exists('spl_activity_title')) {
    function spl_activity_title($row)
    {
        if (!empty($row->rule_name)) {
            return (string) $row->rule_name;
        }
        $map = array(
            'attendance_checkin' => 'Check-in',
            'attendance_checkout' => 'Check-out',
            'attendance_penalty' => 'Attendance penalty',
            'reward_claim' => 'Activity claim',
            'daily_activity_logged' => 'Daily activity',
            'project_status_update' => 'Project status update',
            'manual_award' => 'Manual award',
            'peer_cheer_sent' => 'Cheer sent',
            'peer_cheer_received' => 'Cheer received',
            'consistency_review' => 'Consistency review',
        );
        $event = (string) $row->source_event;
        if (isset($map[$event])) {
            return $map[$event];
        }
        return ucwords(str_replace('_', ' ', $event));
    }
}

if (!function_exists('spl_activity_source_label')) {
    function spl_activity_source_label($row)
    {
        $module = (string) $row->source_module;
        if ($module === 'attendance') {
            return 'Auto · Attendance';
        }
        if ($module === 'spl') {
            return 'Manual · SPL';
        }
        if ($module === 'rewards') {
            return 'Rewards';
        }
        if ($module === 'system') {
            return 'System';
        }
        return ucfirst($module);
    }
}

if (!function_exists('spl_activity_icon_class')) {
    function spl_activity_icon_class($row)
    {
        $module = (string) $row->source_module;
        $event = (string) $row->source_event;
        if ($module === 'attendance' || strpos($event, 'attendance') === 0) {
            return 'bi-clock-history';
        }
        if ($module === 'spl') {
            return 'bi-journal-check';
        }
        if (strpos($event, 'penalty') !== false || (float) $row->points < 0) {
            return 'bi-dash-circle';
        }
        return 'bi-star-fill';
    }
}

if (!function_exists('spl_activity_status_meta')) {
    function spl_activity_status_meta($status)
    {
        $status = (string) $status;
        if ($status === 'pending') {
            return array('label' => 'Pending approval', 'class' => 'warning');
        }
        if ($status === 'approved') {
            return array('label' => 'Approved', 'class' => 'success');
        }
        if ($status === 'rejected') {
            return array('label' => 'Rejected', 'class' => 'danger');
        }
        if ($status === 'reversed') {
            return array('label' => 'Reversed', 'class' => 'secondary');
        }
        return array('label' => ucfirst($status), 'class' => 'secondary');
    }
}

if (!function_exists('spl_format_activity_datetime')) {
    function spl_format_activity_datetime($datetime)
    {
        $datetime = trim((string) $datetime);
        if ($datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        if (!$ts) {
            return $datetime;
        }
        return date('M j, Y · g:i A', $ts);
    }
}

if (!function_exists('spl_activity_note_preview')) {
    function spl_activity_note_preview($html, $max = 100)
    {
        $clean = spl_sanitize_note_html($html);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($clean)));
        if ($plain === '') {
            return '';
        }
        if (strlen($plain) > (int) $max) {
            return substr($plain, 0, (int) $max) . '…';
        }
        return $plain;
    }
}

if (!function_exists('spl_award_attendance_points')) {
    /**
     * Auto-award SPL points for check-in / check-out only (no approval).
     */
    function spl_award_attendance_points($user_id, $action, $attendance_id = 0, $status = 'present', $occurred_at = '')
    {
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('reward_rules')) {
            return;
        }
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return;
        }
        $CI->load->helper('rewards');
        if (!function_exists('reward_engine_dispatch')) {
            return;
        }

        $base = array(
            'user_id' => $user_id,
            'actor_id' => $user_id,
            'source_module' => 'attendance',
            'source_record_id' => (int) $attendance_id > 0 ? (int) $attendance_id : null,
            'occurred_at' => $occurred_at !== '' ? $occurred_at : date('Y-m-d H:i:s'),
        );

        if ($action === 'in') {
            $rewardStatus = 'present';
            if ($status === 'ontime' || $status === 'on_time') {
                $rewardStatus = 'ontime';
            } elseif ($status === 'present') {
                $rewardStatus = 'present';
            } elseif ($status === 'late') {
                $rewardStatus = 'late';
            }
            reward_engine_dispatch('attendance_checkin', array_merge($base, array(
                'reference_label' => 'Check-in',
                'payload' => array('status' => $rewardStatus),
            )));
            $CI->load->helper('rewards_automation');
            if (function_exists('rewards_automation_after_checkin')) {
                rewards_automation_after_checkin($CI->db, $user_id);
            }
            return;
        }

        if ($action === 'out') {
            reward_engine_dispatch('attendance_checkout', array_merge($base, array(
                'reference_label' => 'Check-out',
                'payload' => array(),
            )));
        }
    }
}

if (!function_exists('seed_spl_default_permissions_if_needed')) {
    function seed_spl_default_permissions_if_needed()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions') || !$CI->db->table_exists('roles')) {
            return;
        }
        $CI->load->helper('schema_columns');

        $staff_keys = array('spl_my_reward', 'spl_submit');
        $admin_extra = array(
            'spl', 'spl_approve', 'spl_rules', 'spl_groups', 'spl_groups_manage',
            'rewards', 'rewards_submit', 'rewards_rules', 'rewards_admin', 'rewards_approve', 'rewards_leaderboard',
        );

        $all_roles = $CI->db->select('id')->from('roles')->get()->result();
        $admin_role_ids = array();
        if (schema_table_has_column($CI->db, 'roles', 'group_type')) {
            foreach ($CI->db->select('id')->from('roles')->where('group_type', 'admin')->get()->result() as $r) {
                $admin_role_ids[(int) $r->id] = true;
            }
        }
        if (empty($admin_role_ids)) {
            foreach (array(1, 2, 3) as $rid) {
                $admin_role_ids[$rid] = true;
            }
        }

        foreach ($all_roles as $role) {
            $role_id = (int) $role->id;
            if ($role_id <= 0) {
                continue;
            }
            $keys = $staff_keys;
            if (isset($admin_role_ids[$role_id])) {
                $keys = array_merge($keys, $admin_extra);
            }
            foreach (array_unique($keys) as $module) {
                $exists = $CI->db
                    ->where('role_id', $role_id)
                    ->where('module', $module)
                    ->limit(1)
                    ->get('permissions')
                    ->row();
                if ($exists) {
                    continue;
                }
                $CI->db->insert('permissions', array(
                    'role_id' => $role_id,
                    'module' => $module,
                    'can_access' => 1,
                ));
            }
        }
    }
}

if (!function_exists('spl_season_info')) {
    function spl_season_info()
    {
        $month = (int) date('n');
        $year = (int) date('Y');
        $quarter = (int) ceil($month / 3);
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;
        $start = sprintf('%04d-%02d-01', $year, $startMonth);
        $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
        $startLabel = date('jS M', strtotime($start));
        $endLabel = date('jS M Y', strtotime($end));
        $totalDays = max(1, (int) ((strtotime($end) - strtotime($start)) / 86400) + 1);
        $elapsed = max(0, (int) ((strtotime(date('Y-m-d')) - strtotime($start)) / 86400) + 1);
        $elapsed = min($elapsed, $totalDays);
        $remaining = max(0, $totalDays - $elapsed);
        $pct = (int) round(($elapsed / $totalDays) * 100);
        return array(
            'name' => 'Season ' . $quarter,
            'label' => $startLabel . ' – ' . $endLabel,
            'start' => $start,
            'end' => $end,
            'progress_pct' => $pct,
            'days_remaining' => $remaining,
            'total_days' => $totalDays,
        );
    }
}

if (!function_exists('spl_user_initials')) {
    function spl_user_initials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

if (!function_exists('spl_user_avatar_url')) {
    function spl_user_avatar_url($user_row)
    {
        if (!$user_row || empty($user_row->avatar)) {
            return '';
        }
        $avatar = trim((string) $user_row->avatar);
        if ($avatar === '') {
            return '';
        }
        if (strpos($avatar, 'http://') === 0 || strpos($avatar, 'https://') === 0) {
            return $avatar;
        }
        if (strpos($avatar, 'uploads/') === 0) {
            return base_url($avatar);
        }
        return base_url('uploads/avatars/' . ltrim($avatar, '/'));
    }
}

if (!function_exists('spl_dashboard_user_row')) {
    function spl_dashboard_user_row($user_id)
    {
        $CI =& get_instance();
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !isset($CI->db) || !$CI->db->table_exists('users')) {
            return null;
        }
        $CI->load->helper('schema_columns');
        $db = $CI->db;
        if ($db->table_exists('employees') && schema_table_has_column($db, 'employees', 'user_id') && schema_table_has_column($db, 'employees', 'name')) {
            $db->select('users.id, users.email, users.avatar, employees.name AS employee_name, users.name AS user_name', false);
            $db->from('users');
            $db->join('employees', 'employees.user_id = users.id', 'left');
            $db->where('users.id', $user_id);
            $row = $db->get()->row();
            if ($row) {
                $display = trim((string) $row->employee_name);
                if ($display === '') {
                    $display = trim((string) $row->user_name);
                }
                if ($display === '') {
                    $display = (string) $row->email;
                }
                $row->display_name = $display;
            }
            return $row;
        }
        $row = $db->select('id, name, email, avatar')->where('id', $user_id)->get('users')->row();
        if ($row) {
            $display = trim((string) $row->name);
            if ($display === '') {
                $display = (string) $row->email;
            }
            $row->display_name = $display;
        }
        return $row;
    }
}

if (!function_exists('spl_next_level_info')) {
    function spl_next_level_info($summary, $current_level, array $levels)
    {
        $lifetime = $summary ? (float) $summary->lifetime_points : 0;
        $current_code = $current_level ? (string) $current_level->code : 'starter';
        $sorted = $levels;
        usort($sorted, function ($a, $b) {
            return (float) $a->min_lifetime_points <=> (float) $b->min_lifetime_points;
        });
        $next = null;
        foreach ($sorted as $level) {
            if ((float) $level->min_lifetime_points > $lifetime) {
                $next = $level;
                break;
            }
        }
        if (!$next) {
            return array(
                'code' => $current_code,
                'name' => $current_level ? (string) $current_level->name : 'Max',
                'points_to_go' => 0,
                'progress_pct' => 100,
            );
        }
        $current_min = $current_level ? (float) $current_level->min_lifetime_points : 0;
        $next_min = (float) $next->min_lifetime_points;
        $span = max(1, $next_min - $current_min);
        $progress = max(0, min(100, (int) round((($lifetime - $current_min) / $span) * 100)));
        return array(
            'code' => (string) $next->code,
            'name' => (string) $next->name,
            'points_to_go' => max(0, (int) ceil($next_min - $lifetime)),
            'progress_pct' => $progress,
        );
    }
}

if (!function_exists('spl_dashboard_is_user_scoped')) {
    function spl_dashboard_is_user_scoped()
    {
        return function_exists('is_user_group') && is_user_group();
    }
}

if (!function_exists('spl_enrich_user_activity_feed_names')) {
    function spl_enrich_user_activity_feed_names(array $rows, $display_name)
    {
        $name = trim((string) $display_name);
        if ($name === '') {
            $name = 'User';
        }
        foreach ($rows as $idx => $row) {
            $rows[$idx]->user_name = $name;
        }
        return $rows;
    }
}

if (!function_exists('spl_build_dashboard_data')) {
    function spl_build_dashboard_data($user_id)
    {
        $CI =& get_instance();
        $uid = (int) $user_id;
        $is_user_scoped = spl_dashboard_is_user_scoped();
        $is_org_view = !$is_user_scoped;
        $CI->load->model(array('Reward_model' => 'rewards', 'Spl_model' => 'spl'));

        $today_bounds = spl_reward_period_bounds('today');
        $week_bounds = spl_reward_period_bounds('week');
        $month_bounds = spl_reward_period_bounds('month');
        $season = spl_season_info();

        $summary = $CI->rewards->get_user_summary($uid);
        $level = $CI->rewards->get_level($summary->current_level_code);
        $levels = $CI->rewards->list_levels(true);
        $next_level = spl_next_level_info($summary, $level, $levels);
        $user = spl_dashboard_user_row($uid);
        $display_name = $user && !empty($user->display_name) ? (string) $user->display_name : 'User';
        $avatar_url = spl_user_avatar_url($user);
        $total_users = max(1, $CI->spl->count_reward_users());
        $lifetime_rank = $CI->spl->get_user_lifetime_rank($uid);
        $month_rank = $CI->spl->get_user_month_rank($uid);
        $streak = $CI->spl->compute_points_streak_days($uid);

        $kpi_today = $CI->rewards->sum_user_activity_points($uid, $today_bounds['from'], $today_bounds['to']);
        $kpi_week = $CI->rewards->sum_user_activity_points($uid, $week_bounds['from'], $week_bounds['to']);
        $kpi_month = $CI->rewards->sum_user_activity_points($uid, $month_bounds['from'], $month_bounds['to']);
        $pending_count = (int) $CI->rewards->count_user_pending_transactions($uid);

        if ($is_user_scoped) {
            $recent = $CI->rewards->list_user_activity_feed($uid, 20, $today_bounds['from'], $today_bounds['to']);
            if (empty($recent)) {
                $recent = $CI->rewards->list_user_activity_feed($uid, 20, $week_bounds['from'], $week_bounds['to']);
            }
        } else {
            $recent = $CI->spl->list_org_activity_feed(20);
        }

        $CI->spl->sync_all_rules_to_all_groups();
        $board_groups = $CI->spl->list_groups_board(true, $week_bounds['from'], $week_bounds['to']);
        $my_group = null;
        $my_group_rank = 0;
        $team_standings = array();
        $rank = 0;
        usort($board_groups, function ($a, $b) {
            return (float) $b->total_period_net <=> (float) $a->total_period_net;
        });
        $user_groups = $CI->spl->list_groups_for_user($uid, true);
        $my_group_ids = array();
        foreach ($user_groups as $ug) {
            $my_group_ids[(int) $ug->id] = true;
        }
        foreach ($board_groups as $group) {
            $rank++;
            $prev_week_from = date('Y-m-d', strtotime($week_bounds['from'] . ' -7 days'));
            $prev_week_to = date('Y-m-d', strtotime($week_bounds['to'] . ' -7 days'));
            $prev_stats = $CI->spl->sum_group_member_points_by_status((int) $group->id, $prev_week_from, $prev_week_to);
            $trend = (float) $group->total_period_net - (float) $prev_stats->net;
            $team_standings[] = (object) array(
                'rank' => $rank,
                'id' => (int) $group->id,
                'name' => (string) $group->name,
                'code' => (string) $group->code,
                'points' => (float) $group->total_period_net,
                'trend' => $trend,
                'poster_path' => isset($group->poster_path) ? (string) $group->poster_path : '',
            );
            if (isset($my_group_ids[(int) $group->id])) {
                $my_group = $group;
                $my_group_rank = $rank;
            }
        }

        $team_overview = null;
        if ($my_group) {
            $team_status = $CI->spl->sum_group_member_points_by_status((int) $my_group->id, $week_bounds['from'], $week_bounds['to']);
            $prev_week_from = date('Y-m-d', strtotime($week_bounds['from'] . ' -7 days'));
            $prev_week_to = date('Y-m-d', strtotime($week_bounds['to'] . ' -7 days'));
            $prev_team = $CI->spl->sum_group_member_points_by_status((int) $my_group->id, $prev_week_from, $prev_week_to);
            $members = !empty($my_group->members) ? $my_group->members : array();
            usort($members, function ($a, $b) {
                return (float) $b->display_points <=> (float) $a->display_points;
            });
            $max_member_pts = 0;
            foreach ($members as $member) {
                $pts = (float) $member->display_points;
                if ($pts > $max_member_pts) {
                    $max_member_pts = $pts;
                }
            }
            $team_overview = (object) array(
                'id' => (int) $my_group->id,
                'name' => (string) $my_group->name,
                'poster_path' => isset($my_group->poster_path) ? (string) $my_group->poster_path : '',
                'score' => (float) $my_group->total_period_net,
                'trend' => (float) $my_group->total_period_net - (float) $prev_team->net,
                'approved' => (float) $team_status->approved,
                'pending' => (float) $team_status->pending,
                'deducted' => (float) $team_status->deducted,
                'members' => $members,
                'max_member_points' => $max_member_pts > 0 ? $max_member_pts : 1,
            );
        }

        $pending_approvals = array();
        if ($is_user_scoped) {
            $pending_approvals = spl_enrich_approval_rows($CI->rewards, $CI->rewards->list_spl_user_pending_approvals($uid, 20));
        } elseif (spl_can_approve()) {
            $pending_approvals = spl_enrich_approval_rows($CI->rewards, $CI->rewards->list_spl_pending_approvals(20));
        }

        if ($is_user_scoped) {
            $live_feed = spl_enrich_user_activity_feed_names($CI->rewards->list_user_activity_feed($uid, 20), $display_name);
        } else {
            $live_feed = $CI->spl->list_org_activity_feed(20);
        }
        $week_top = $CI->spl->list_top_users_by_period($week_bounds['from'], $week_bounds['to'], 4);
        $category_leaders = $CI->spl->list_category_leaders($month_bounds['from'], $month_bounds['to'], 10);

        $top_performers = array();
        $performer_defs = array(
            array('key' => 'top_scorer', 'title' => 'Top Scorer', 'icon' => 'bi-trophy-fill', 'category' => null),
            array('key' => 'most_innovative', 'title' => 'Most Innovative', 'icon' => 'bi-lightbulb-fill', 'category' => 'innovation'),
            array('key' => 'team_player', 'title' => 'Team Player', 'icon' => 'bi-people-fill', 'category' => 'team'),
            array('key' => 'learning_star', 'title' => 'Learning Star', 'icon' => 'bi-mortarboard-fill', 'category' => 'learning'),
        );
        foreach ($performer_defs as $def) {
            $row = null;
            if ($def['category'] === null && !empty($week_top)) {
                $row = $week_top[0];
            } elseif ($def['category'] !== null) {
                $row = $CI->spl->list_top_user_by_category($def['category'], $week_bounds['from'], $week_bounds['to']);
            }
            if ($row) {
                $top_performers[] = (object) array(
                    'key' => $def['key'],
                    'title' => $def['title'],
                    'icon' => $def['icon'],
                    'user_id' => (int) $row->user_id,
                    'user_name' => (string) $row->user_name,
                    'points' => (float) $row->net_points,
                );
            }
        }

        $badges = spl_compute_user_badges($uid, $summary, $streak, $level);

        $notification_count = 0;
        $CI->load->helper('notification');
        if (function_exists('get_unread_notification_count')) {
            $notification_count = (int) get_unread_notification_count($uid);
        }

        return array(
            'user' => $user,
            'display_name' => $display_name,
            'avatar_url' => $avatar_url,
            'initials' => spl_user_initials($display_name),
            'summary' => $summary,
            'level' => $level,
            'next_level' => $next_level,
            'lifetime_rank' => $lifetime_rank,
            'month_rank' => $month_rank,
            'total_users' => $total_users,
            'streak' => $streak,
            'kpi_today' => $kpi_today,
            'kpi_week' => $kpi_week,
            'kpi_month' => $kpi_month,
            'pending_count' => $pending_count,
            'recent' => $recent,
            'team_overview' => $team_overview,
            'my_group_rank' => $my_group_rank,
            'team_standings' => $team_standings,
            'pending_approvals' => $pending_approvals,
            'live_feed' => $live_feed,
            'top_performers' => $top_performers,
            'category_leaders' => $category_leaders,
            'badges' => $badges,
            'season' => $season,
            'notification_count' => $notification_count,
            'week_bounds' => $week_bounds,
            'month_bounds' => $month_bounds,
            'can_approve' => spl_can_approve(),
            'can_submit' => spl_can_submit(),
            'current_user_id' => $uid,
            'is_user_scoped' => $is_user_scoped,
            'is_org_view' => $is_org_view,
        );
    }
}

if (!function_exists('spl_compute_user_badges')) {
    function spl_compute_user_badges($user_id, $summary, array $streak, $level)
    {
        $badges = array();
        if (!empty($streak['current']) && (int) $streak['current'] >= 7) {
            $badges[] = array('icon' => 'bi-fire', 'label' => (int) $streak['current'] . ' Day Streak');
        }
        if ($level && in_array((string) $level->code, array('gold', 'platinum', 'legend'), true)) {
            $badges[] = array('icon' => 'bi-award-fill', 'label' => ucfirst((string) $level->code) . ' Level');
        }
        if ($summary && (float) $summary->lifetime_points >= 5000) {
            $badges[] = array('icon' => 'bi-star-fill', 'label' => '5K Club');
        }
        if (!empty($streak['best']) && (int) $streak['best'] >= 20) {
            $badges[] = array('icon' => 'bi-calendar2-check', 'label' => '20 Day Streak');
        }
        $CI =& get_instance();
        if (isset($CI->db) && $CI->db->table_exists('reward_transactions')) {
            $innovation = $CI->db->select('COUNT(*) AS cnt', false)
                ->from('reward_transactions t')
                ->join('reward_rules r', 'r.id = t.rule_id', 'inner')
                ->join('reward_categories c', 'c.id = r.category_id', 'inner')
                ->where('t.user_id', (int) $user_id)
                ->where('t.status', 'approved')
                ->where('c.code', 'innovation')
                ->get()->row();
            if ($innovation && (int) $innovation->cnt >= 3) {
                $badges[] = array('icon' => 'bi-lightbulb-fill', 'label' => 'Innovation Hero');
            }
        }
        if (count($badges) < 4 && $summary && (float) $summary->month_points >= 500) {
            $badges[] = array('icon' => 'bi-graph-up-arrow', 'label' => 'Monthly Star');
        }
        return array_slice($badges, 0, 6);
    }
}

if (!function_exists('spl_time_ago')) {
    function spl_time_ago($datetime)
    {
        $datetime = trim((string) $datetime);
        if ($datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if (!$ts) {
            return $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . ' mins ago';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . ' hrs ago';
        }
        return date('M j, g:i A', $ts);
    }
}
