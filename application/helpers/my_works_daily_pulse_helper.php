<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Second Brain — Daily Pulse aggregator (today only).
 */

if (!function_exists('my_works_daily_pulse_limit')) {
    function my_works_daily_pulse_limit()
    {
        return 20;
    }
}

if (!function_exists('my_works_daily_pulse_trim_list')) {
    /**
     * @param array $rows
     * @param int   $limit
     * @return array{items:array,total:int,more:int}
     */
    function my_works_daily_pulse_trim_list(array $rows, $limit = null)
    {
        $limit = $limit !== null ? (int) $limit : my_works_daily_pulse_limit();
        $total = count($rows);
        if ($limit > 0 && $total > $limit) {
            return array(
                'items' => array_slice($rows, 0, $limit),
                'total' => $total,
                'more'  => $total - $limit,
            );
        }
        return array(
            'items' => $rows,
            'total' => $total,
            'more'  => 0,
        );
    }
}

if (!function_exists('my_works_daily_pulse_has_access')) {
    function my_works_daily_pulse_has_access(array $keys)
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        foreach ($keys as $key) {
            if (has_module_access($key)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('my_works_daily_pulse_scoped_users')) {
    /**
     * @return array<int, object> keyed by user id
     */
    function my_works_daily_pulse_scoped_users($db, $can_view_all, $user_id, $role_id)
    {
        $CI =& get_instance();
        $CI->load->helper(array('my_works_access', 'schema_columns'));
        $users = my_works_filter_users_for_dropdown($db, $can_view_all, $user_id, $role_id);
        $map = array();
        foreach ($users as $u) {
            $map[(int) $u->id] = $u;
        }
        return $map;
    }
}

if (!function_exists('my_works_daily_pulse_clients_added')) {
    function my_works_daily_pulse_clients_added($db, $today)
    {
        if (!my_works_daily_pulse_has_access(array('clients', 'clients_list'))
            || !$db->table_exists('clients')
        ) {
            return null;
        }
        $db->reset_query();
        $select = 'id, company_name, created_at, created_by';
        if (schema_table_has_column($db, 'clients', 'client_code')) {
            $select .= ', client_code';
        }
        $db->select($select);
        $db->from('clients');
        $db->where('DATE(created_at)', $today);
        if (function_exists('apply_role_hierarchy_filter')) {
            apply_role_hierarchy_filter($db, 'created_by');
        }
        $db->order_by('created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id'           => (int) $r->id,
                'company_name' => (string) ($r->company_name ?: ''),
                'client_code'  => (string) (isset($r->client_code) ? $r->client_code : ''),
                'created_at'   => (string) $r->created_at,
                'url'          => site_url('clients/view/' . (int) $r->id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
    }
}

if (!function_exists('my_works_daily_pulse_attendance')) {
    function my_works_daily_pulse_attendance($db, $today, array $user_map)
    {
        if (!my_works_daily_pulse_has_access(array('attendance', 'attendance_list', 'leaves', 'leaves_list'))
            && empty($user_map)
        ) {
            return null;
        }
        if (!my_works_daily_pulse_has_access(array('attendance', 'attendance_list', 'leaves', 'leaves_list'))) {
            return null;
        }

        $on_leave = array();
        $wfh = array();
        $leave_user_ids = array();

        if ($db->table_exists('leave_requests')) {
            $db->reset_query();
            $has_types = $db->table_exists('leave_types');
            if ($has_types) {
                $db->select('lr.user_id, lr.status, lr.start_date, lr.end_date, u.name AS user_name, lt.name AS leave_type', false);
            } else {
                $db->select('lr.user_id, lr.status, lr.start_date, lr.end_date, u.name AS user_name, NULL AS leave_type', false);
            }
            $db->from('leave_requests lr');
            $db->join('users u', 'u.id = lr.user_id', 'left');
            if ($has_types) {
                $db->join('leave_types lt', 'lt.id = lr.type_id', 'left');
            }
            $db->where('lr.start_date <=', $today);
            $db->where('lr.end_date >=', $today);
            $db->where_in('lr.status', array('lead_approved', 'hr_approved', 'approved'));
            if (!empty($user_map)) {
                $db->where_in('lr.user_id', array_keys($user_map));
            }
            $db->order_by('u.name', 'ASC');
            $db->limit(100);
            foreach ($db->get()->result() as $row) {
                $uid = (int) $row->user_id;
                $leave_user_ids[$uid] = true;
                $type_name = isset($row->leave_type) ? trim((string) $row->leave_type) : '';
                $is_wfh = (bool) preg_match('/work\s*from\s*home|\bwfh\b/i', $type_name);
                $item = array(
                    'user_id'    => $uid,
                    'name'       => (string) ($row->user_name ?: ('User #' . $uid)),
                    'leave_type' => $type_name !== '' ? $type_name : 'Leave',
                    'status'     => (string) $row->status,
                );
                if ($is_wfh) {
                    $wfh[] = $item;
                } else {
                    $on_leave[] = $item;
                }
            }
        }

        $checked_in = array();
        $checked_out = array();
        $not_checked_in = array();
        $punched_ids = array();

        if ($db->table_exists('attendance')) {
            $CI =& get_instance();
            $CI->load->model('Attendance_model', 'attendance_model');
            $cols = $CI->attendance_model->get_columns();
            $date_col = $cols['date'];
            $in_col = $cols['punch_in'];
            $out_col = $cols['punch_out'];

            $db->reset_query();
            $db->select('a.user_id, a.status, u.name AS user_name, a.' . $in_col . ' AS punch_in, a.' . $out_col . ' AS punch_out', false);
            $db->from('attendance a');
            $db->join('users u', 'u.id = a.user_id', 'left');
            $db->where('a.' . $date_col, $today);
            if (!empty($user_map)) {
                $db->where_in('a.user_id', array_keys($user_map));
            }
            $db->order_by('u.name', 'ASC');
            $db->limit(200);
            foreach ($db->get()->result() as $row) {
                $uid = (int) $row->user_id;
                $status = strtolower(trim((string) (isset($row->status) ? $row->status : '')));
                if ($status === 'work_from_home' || $status === 'wfh') {
                    if (!isset($leave_user_ids[$uid])) {
                        $wfh[] = array(
                            'user_id'    => $uid,
                            'name'       => (string) ($row->user_name ?: ('User #' . $uid)),
                            'leave_type' => 'Work From Home',
                            'status'     => $status,
                        );
                        $leave_user_ids[$uid] = true;
                    }
                }
                $punch_in = !empty($row->punch_in) ? (string) $row->punch_in : '';
                $punch_out = !empty($row->punch_out) ? (string) $row->punch_out : '';
                if ($punch_in !== '') {
                    $punched_ids[$uid] = true;
                    $entry = array(
                        'user_id'   => $uid,
                        'name'      => (string) ($row->user_name ?: ('User #' . $uid)),
                        'check_in'  => $punch_in,
                        'check_out' => $punch_out,
                        'status'    => $status,
                    );
                    $checked_in[] = $entry;
                    if ($punch_out !== '') {
                        $checked_out[] = $entry;
                    }
                }
            }
        }

        foreach ($user_map as $uid => $u) {
            $uid = (int) $uid;
            if (isset($leave_user_ids[$uid]) || isset($punched_ids[$uid])) {
                continue;
            }
            $not_checked_in[] = array(
                'user_id' => $uid,
                'name'    => (string) (!empty($u->name) ? $u->name : ('User #' . $uid)),
            );
        }

        return array(
            'on_leave'       => my_works_daily_pulse_trim_list($on_leave),
            'wfh'            => my_works_daily_pulse_trim_list($wfh),
            'checked_in'     => my_works_daily_pulse_trim_list($checked_in),
            'checked_out'    => my_works_daily_pulse_trim_list($checked_out),
            'not_checked_in' => my_works_daily_pulse_trim_list($not_checked_in),
        );
    }
}

if (!function_exists('my_works_daily_pulse_daily_activity')) {
    function my_works_daily_pulse_daily_activity($db, $today, array $user_map)
    {
        if (!my_works_daily_pulse_has_access(array('daily_activity', 'reports', 'my_works', 'my_works_list'))) {
            // Still show for Second Brain users using work logs
        }
        if (!$db->table_exists('daily_work_logs') || empty($user_map)) {
            return array(
                'logged'     => my_works_daily_pulse_trim_list(array()),
                'not_logged' => my_works_daily_pulse_trim_list(array()),
                'view_all_url' => site_url('reports/daily-activity'),
            );
        }

        $db->reset_query();
        $db->select('dl.user_id, u.name AS user_name, COUNT(dl.id) AS entry_count', false);
        $db->from('daily_work_logs dl');
        $db->join('users u', 'u.id = dl.user_id', 'left');
        $db->where('dl.work_date', $today);
        $db->where_in('dl.user_id', array_keys($user_map));
        $db->group_by('dl.user_id');
        $db->order_by('u.name', 'ASC');
        $logged_rows = $db->get()->result();

        $logged_ids = array();
        $logged = array();
        foreach ($logged_rows as $row) {
            $uid = (int) $row->user_id;
            $logged_ids[$uid] = true;
            $logged[] = array(
                'user_id'     => $uid,
                'name'        => (string) ($row->user_name ?: ('User #' . $uid)),
                'entry_count' => (int) $row->entry_count,
            );
        }

        $not_logged = array();
        foreach ($user_map as $uid => $u) {
            $uid = (int) $uid;
            if (isset($logged_ids[$uid])) {
                continue;
            }
            $name = (string) (!empty($u->name) ? $u->name : ('User #' . $uid));
            $not_logged[] = array(
                'user_id' => $uid,
                'name'    => $name,
                'label'   => '- ' . $name,
            );
        }

        return array(
            'logged'       => my_works_daily_pulse_trim_list($logged),
            'not_logged'   => my_works_daily_pulse_trim_list($not_logged),
            'view_all_url' => site_url('reports/daily-activity'),
        );
    }
}

if (!function_exists('my_works_daily_pulse_work_history')) {
    /**
     * @param string $mode project|adhoc
     */
    function my_works_daily_pulse_work_history($db, $today, $can_view_all, $user_id, $mode)
    {
        if (!$db->table_exists('my_work_activity') || !$db->table_exists('my_works')) {
            return my_works_daily_pulse_trim_list(array());
        }
        $CI =& get_instance();
        $CI->load->helper(array('my_works_access', 'schema_columns'));

        $db->reset_query();
        $db->select('a.id, a.work_id, a.action, a.detail, a.created_at, a.user_id, w.title, w.project_id, u.name AS user_name, p.name AS project_name', false);
        $db->from('my_work_activity a');
        $db->join('my_works w', 'w.id = a.work_id', 'inner');
        $db->join('users u', 'u.id = a.user_id', 'left');
        if ($db->table_exists('projects') && schema_table_has_column($db, 'my_works', 'project_id')) {
            $db->join('projects p', 'p.id = w.project_id', 'left');
        }
        $db->where('DATE(a.created_at)', $today);
        if ($mode === 'project') {
            $db->group_start()
                ->where('w.project_id >', 0)
                ->group_end();
        } else {
            $db->group_start()
                ->where('w.project_id IS NULL', null, false)
                ->or_where('w.project_id', 0)
                ->group_end();
        }
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        $db->order_by('a.created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();

        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'work_id'      => (int) $r->work_id,
                'title'        => (string) ($r->title ?: ''),
                'action'       => (string) ($r->action ?: ''),
                'detail'       => (string) (isset($r->detail) ? $r->detail : ''),
                'user_name'    => (string) ($r->user_name ?: ''),
                'project_name' => (string) (isset($r->project_name) ? $r->project_name : ''),
                'at'           => (string) $r->created_at,
                'url'          => site_url('my-works/' . (int) $r->work_id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
    }
}

if (!function_exists('my_works_daily_pulse_requirements')) {
    function my_works_daily_pulse_requirements($db, $today)
    {
        if (!my_works_daily_pulse_has_access(array('requirements', 'requirements_list'))
            || !$db->table_exists('requirements')
        ) {
            return null;
        }
        $db->reset_query();
        $select = 'id, title, status, created_at';
        if (schema_table_has_column($db, 'requirements', 'req_number')) {
            $select .= ', req_number';
        }
        if (schema_table_has_column($db, 'requirements', 'project_id')) {
            $select .= ', project_id';
        }
        $db->select($select);
        $db->from('requirements');
        $db->where('DATE(created_at)', $today);
        if (function_exists('apply_role_hierarchy_filter') && schema_table_has_column($db, 'requirements', 'created_by')) {
            apply_role_hierarchy_filter($db, 'created_by');
        }
        $db->order_by('created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id'         => (int) $r->id,
                'req_number' => (string) (isset($r->req_number) ? $r->req_number : ('#' . (int) $r->id)),
                'title'      => (string) ($r->title ?: ''),
                'project_id' => isset($r->project_id) ? (int) $r->project_id : 0,
                'status'     => (string) (isset($r->status) ? $r->status : ''),
                'url'        => site_url('requirements/view/' . (int) $r->id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
    }
}

if (!function_exists('my_works_daily_pulse_defects')) {
    function my_works_daily_pulse_defects($db, $today)
    {
        if (!my_works_daily_pulse_has_access(array('defects', 'defects_list'))
            || !$db->table_exists('project_defects')
        ) {
            return null;
        }
        $db->reset_query();
        $select = 'id, title, status, created_at';
        if (schema_table_has_column($db, 'project_defects', 'defect_number')) {
            $select .= ', defect_number';
        }
        if (schema_table_has_column($db, 'project_defects', 'severity')) {
            $select .= ', severity';
        }
        if (schema_table_has_column($db, 'project_defects', 'project_id')) {
            $select .= ', project_id';
        }
        $db->select($select);
        $db->from('project_defects');
        $db->where('DATE(created_at)', $today);
        if (schema_table_has_column($db, 'project_defects', 'is_deleted')) {
            $db->where('is_deleted', 0);
        }
        if (function_exists('apply_role_hierarchy_filter') && schema_table_has_column($db, 'project_defects', 'reported_by')) {
            apply_role_hierarchy_filter($db, 'reported_by');
        }
        $db->order_by('created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id'            => (int) $r->id,
                'defect_number' => (string) (isset($r->defect_number) ? $r->defect_number : ('#' . (int) $r->id)),
                'title'         => (string) ($r->title ?: ''),
                'severity'      => (string) (isset($r->severity) ? $r->severity : ''),
                'project_id'    => isset($r->project_id) ? (int) $r->project_id : 0,
                'status'        => (string) (isset($r->status) ? $r->status : ''),
                'url'           => site_url('defects/view/' . (int) $r->id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
    }
}

if (!function_exists('my_works_daily_pulse_overview_today')) {
    function my_works_daily_pulse_overview_today($db, $can_view_all, $user_id)
    {
        $CI =& get_instance();
        $CI->load->helper(array('my_works', 'my_works_query'));
        $filters = array(
            'status'          => '',
            'tag'             => '',
            'q'               => '',
            'created_for'     => 0,
            'created_by'      => 0,
            'client_id'       => 0,
            'project_id'      => 0,
            'work_type'       => '',
            'involvement'     => 'all',
            'urgent_only'     => 0,
            'important_only'  => 0,
            'overdue_only'    => 0,
            'current_user_id' => (int) $user_id,
        );
        $rows = my_works_fetch_rows($db, $filters, $can_view_all, $user_id, 500, 0, true);
        $dash = my_works_build_dashboard_sections($rows, true);
        $adhoc = isset($dash['sections']['ad_hoc']['todays_plan']) ? $dash['sections']['ad_hoc']['todays_plan'] : array();
        $project = isset($dash['sections']['project']['todays_plan']) ? $dash['sections']['project']['todays_plan'] : array();

        $map_items = function ($list) {
            $out = array();
            foreach ($list as $r) {
                $out[] = array(
                    'id'    => (int) $r->id,
                    'title' => (string) ($r->title ?: ''),
                    'url'   => site_url('my-works/' . (int) $r->id),
                );
            }
            return my_works_daily_pulse_trim_list($out);
        };

        return array(
            'ad_hoc'  => $map_items($adhoc),
            'project' => $map_items($project),
            'url'     => site_url('my-works?tab=overview'),
            'focus_url' => site_url('my-works/todays-focus'),
        );
    }
}

if (!function_exists('my_works_daily_pulse_spl_groups')) {
    function my_works_daily_pulse_spl_groups()
    {
        $CI =& get_instance();
        $CI->load->helper('spl');
        if (!function_exists('spl_can_access') || !spl_can_access()) {
            return null;
        }
        if (function_exists('spl_can_view_groups') && !spl_can_view_groups()) {
            return null;
        }
        if (!$CI->db->table_exists('spl_groups')) {
            return null;
        }
        $CI->load->model('Spl_model', 'spl');
        $bounds = spl_reward_period_bounds('week');
        $from = isset($bounds['from']) ? $bounds['from'] : date('Y-m-d', strtotime('monday this week'));
        $to = isset($bounds['to']) ? $bounds['to'] : date('Y-m-d');
        $board = $CI->spl->list_groups_board(true, $from, $to);
        usort($board, function ($a, $b) {
            $pa = isset($a->total_period_net) ? (float) $a->total_period_net : 0;
            $pb = isset($b->total_period_net) ? (float) $b->total_period_net : 0;
            if ($pa === $pb) {
                return strcmp((string) $a->name, (string) $b->name);
            }
            return ($pa < $pb) ? 1 : -1;
        });
        $out = array();
        $rank = 1;
        foreach ($board as $g) {
            $out[] = array(
                'rank'         => $rank++,
                'id'           => (int) $g->id,
                'name'         => (string) $g->name,
                'code'         => (string) $g->code,
                'points'       => isset($g->total_period_net) ? (float) $g->total_period_net : 0,
                'member_count' => isset($g->member_count) ? (int) $g->member_count : 0,
                'url'          => site_url('spl/groups/' . (int) $g->id),
            );
        }
        return array(
            'items'  => my_works_daily_pulse_trim_list($out),
            'period' => 'week',
            'from'   => $from,
            'to'     => $to,
            'url'    => function_exists('spl_groups_url') ? spl_groups_url('week') : site_url('spl/groups'),
        );
    }
}

if (!function_exists('my_works_build_daily_pulse')) {
    /**
     * @return array
     */
    function my_works_build_daily_pulse($db, $user_id, $can_view_all, $role_id = 0)
    {
        $CI =& get_instance();
        $CI->load->helper(array('schema_columns', 'my_works_access', 'hierarchy_filter'));
        // Warm column maps before section queries so list_fields does not reset QB mid-build.
        schema_table_has_column($db, 'users', 'status');
        schema_table_has_column($db, 'my_works', 'project_id');
        schema_table_has_column($db, 'requirements', 'req_number');
        schema_table_has_column($db, 'requirements', 'project_id');
        schema_table_has_column($db, 'requirements', 'created_by');
        schema_table_has_column($db, 'project_defects', 'defect_number');
        schema_table_has_column($db, 'project_defects', 'severity');
        schema_table_has_column($db, 'project_defects', 'project_id');
        schema_table_has_column($db, 'project_defects', 'is_deleted');
        schema_table_has_column($db, 'project_defects', 'reported_by');

        $today = date('Y-m-d');
        $user_map = my_works_daily_pulse_scoped_users($db, $can_view_all, $user_id, $role_id);

        return array(
            'date'               => $today,
            'clients_added'      => my_works_daily_pulse_clients_added($db, $today),
            'attendance'         => my_works_daily_pulse_attendance($db, $today, $user_map),
            'daily_activity'     => my_works_daily_pulse_daily_activity($db, $today, $user_map),
            'project_history'    => my_works_daily_pulse_work_history($db, $today, $can_view_all, $user_id, 'project'),
            'adhoc_history'      => my_works_daily_pulse_work_history($db, $today, $can_view_all, $user_id, 'adhoc'),
            'requirements_added' => my_works_daily_pulse_requirements($db, $today),
            'defects_added'      => my_works_daily_pulse_defects($db, $today),
            'overview_today'     => my_works_daily_pulse_overview_today($db, $can_view_all, $user_id),
            'spl_group_scores'   => my_works_daily_pulse_spl_groups(),
        );
    }
}
