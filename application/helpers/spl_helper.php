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
        return has_module_access('spl')
            || has_module_access('spl_groups_manage')
            || has_module_access('rewards_admin')
            || has_module_access('rewards');
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
        return 'my-reward';
    }
}

if (!function_exists('spl_has_any_index_tab')) {
    function spl_has_any_index_tab()
    {
        return spl_can_my_reward()
            || spl_can_submit()
            || spl_can_approve()
            || spl_can_manage_rules();
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
        return site_url('spl/groups?reward_period=' . urlencode(spl_normalize_reward_period($reward_period)));
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
