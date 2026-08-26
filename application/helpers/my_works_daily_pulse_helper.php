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

if (!function_exists('my_works_daily_pulse_format_time')) {
    /**
     * Normalize TIME / DATETIME / string punch values to H:i (or '').
     */
    function my_works_daily_pulse_format_time($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '00:00:00' || $value === '0000-00-00 00:00:00') {
            return '';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }
        return date('H:i', $ts);
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
                $db->select('lr.user_id, lr.status, lr.start_date, lr.end_date, lr.reason, u.name AS user_name, lt.name AS leave_type', false);
            } else {
                $db->select('lr.user_id, lr.status, lr.start_date, lr.end_date, lr.reason, u.name AS user_name, NULL AS leave_type', false);
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
                $is_wfh = leave_type_name_is_wfh($type_name);
                $notes = isset($row->reason) ? trim((string) $row->reason) : '';
                if ($is_wfh && stripos($notes, 'WFH:') === 0) {
                    $notes = trim(substr($notes, 4));
                }
                $item = array(
                    'user_id'    => $uid,
                    'name'       => (string) ($row->user_name ?: ('User #' . $uid)),
                    'leave_type' => $type_name !== '' ? $type_name : 'Leave',
                    'status'     => (string) $row->status,
                    'notes'      => $notes,
                    'check_in'   => '',
                    'check_out'  => '',
                );
                if ($is_wfh) {
                    $wfh[$uid] = $item;
                } else {
                    $on_leave[] = $item;
                }
            }
        }

        $checked_in = array();
        $checked_out = array();
        $not_checked_in = array();
        $punched_ids = array();
        $punch_by_user = array();

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
                $punch_in = my_works_daily_pulse_format_time(isset($row->punch_in) ? $row->punch_in : '');
                $punch_out = my_works_daily_pulse_format_time(isset($row->punch_out) ? $row->punch_out : '');
                $punch_by_user[$uid] = array(
                    'check_in'  => $punch_in,
                    'check_out' => $punch_out,
                );

                if ($status === 'work_from_home' || $status === 'wfh') {
                    if (!isset($leave_user_ids[$uid])) {
                        $wfh[$uid] = array(
                            'user_id'    => $uid,
                            'name'       => (string) ($row->user_name ?: ('User #' . $uid)),
                            'leave_type' => 'Work From Home',
                            'status'     => $status,
                            'notes'      => '',
                            'check_in'   => $punch_in,
                            'check_out'  => $punch_out,
                        );
                        $leave_user_ids[$uid] = true;
                    }
                }

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

        // Attach today's punch times onto WFH rows when attendance has them.
        foreach ($wfh as $uid => $item) {
            if (isset($punch_by_user[$uid])) {
                $wfh[$uid]['check_in'] = $punch_by_user[$uid]['check_in'];
                $wfh[$uid]['check_out'] = $punch_by_user[$uid]['check_out'];
            }
        }
        $wfh = array_values($wfh);

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

if (!function_exists('my_works_daily_pulse_daily_activity_logs')) {
    /**
     * Today's daily_work_logs entries for Daily Pulse — uses the actual
     * Daily Activity module records, not my_work_activity.
     */
    function my_works_daily_pulse_daily_activity_logs($db, $today)
    {
        if (!$db->table_exists('daily_work_logs')) {
            return my_works_daily_pulse_trim_list(array());
        }

        $db->reset_query();
        $db->select('dl.id, dl.user_id, dl.activity_title, dl.description, dl.work_date, dl.created_at, u.name AS user_name', false);
        $db->from('daily_work_logs dl');
        $db->join('users u', 'u.id = dl.user_id', 'left');
        $db->where('dl.work_date', $today);
        $db->order_by('dl.created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();

        $out = array();
        foreach ($rows as $r) {
            $title = trim((string) ($r->activity_title ?: ''));
            if ($title === '') {
                $title = 'Daily Log';
            }
            $detail = trim(strip_tags((string) ($r->description ?: '')));
            if (mb_strlen($detail) > 120) {
                $detail = mb_substr($detail, 0, 120) . '...';
            }
            $out[] = array(
                'work_id'      => (int) $r->id,
                'title'        => $title,
                'action'       => 'created',
                'detail'       => $detail,
                'user_name'    => (string) ($r->user_name ?: ''),
                'project_name' => '',
                'client_name'  => '',
                'at'           => (string) $r->created_at,
                'url'          => site_url('daily-activity'),
            );
        }
        return my_works_daily_pulse_trim_list($out);
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
     * Work activity history for Daily Pulse.
     * Split (no double-count): project = project_id>0; client = client_id>0 AND no project; adhoc = no project and no client.
     *
     * @param string $mode project|client|adhoc
     */
    function my_works_daily_pulse_work_history($db, $today, $can_view_all, $user_id, $mode)
    {
        if (!$db->table_exists('my_work_activity') || !$db->table_exists('my_works')) {
            return my_works_daily_pulse_trim_list(array());
        }
        $CI =& get_instance();
        $CI->load->helper(array('my_works_access', 'schema_columns'));

        $has_project = schema_table_has_column($db, 'my_works', 'project_id');
        $has_client = schema_table_has_column($db, 'my_works', 'client_id');
        $join_projects = $has_project && $db->table_exists('projects');
        $join_clients = $has_client && $db->table_exists('clients');

        if ($mode === 'client' && !$has_client) {
            return my_works_daily_pulse_trim_list(array());
        }

        $select = 'a.id, a.work_id, a.action, a.detail, a.created_at, a.user_id, w.title, u.name AS user_name';
        if ($has_project) {
            $select .= ', w.project_id';
        }
        if ($has_client) {
            $select .= ', w.client_id';
        }
        if ($join_projects) {
            $select .= ', p.name AS project_name';
        } else {
            $select .= ', NULL AS project_name';
        }
        if ($join_clients) {
            $select .= ', cl.company_name AS client_name';
        } else {
            $select .= ', NULL AS client_name';
        }

        $db->reset_query();
        $db->select($select, false);
        $db->from('my_work_activity a');
        $db->join('my_works w', 'w.id = a.work_id', 'inner');
        $db->join('users u', 'u.id = a.user_id', 'left');
        if ($join_projects) {
            $db->join('projects p', 'p.id = w.project_id', 'left');
        }
        if ($join_clients) {
            $db->join('clients cl', 'cl.id = w.client_id', 'left');
        }
        $db->where('DATE(a.created_at)', $today);

        // Prefer project | client-only | pure adhoc (mutually exclusive).
        if ($mode === 'project') {
            if ($has_project) {
                $db->where('w.project_id >', 0);
            } else {
                return my_works_daily_pulse_trim_list(array());
            }
        } elseif ($mode === 'client') {
            $db->where('w.client_id >', 0);
            if ($has_project) {
                $db->group_start()
                    ->where('w.project_id IS NULL', null, false)
                    ->or_where('w.project_id', 0)
                    ->group_end();
            }
        } elseif ($mode === 'adhoc') {
            // adhoc: no project and no client
            if ($has_project) {
                $db->group_start()
                    ->where('w.project_id IS NULL', null, false)
                    ->or_where('w.project_id', 0)
                    ->group_end();
            }
            if ($has_client) {
                $db->group_start()
                    ->where('w.client_id IS NULL', null, false)
                    ->or_where('w.client_id', 0)
                    ->group_end();
            }
        }
        // else ($mode === 'all'): no extra filter — fetch all activity types

        // Show overall work history for Daily Pulse without user-specific scope
        // my_works_apply_list_scope($db, $can_view_all, $user_id);
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
                'client_name'  => (string) (isset($r->client_name) ? $r->client_name : ''),
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


if (!function_exists('my_works_daily_pulse_project_history_overall')) {
    function my_works_daily_pulse_project_history_overall($db, $today)
    {
        if (!my_works_daily_pulse_has_access(array('projects', 'projects_list'))
            || !$db->table_exists('project_activity') || !$db->table_exists('projects')
        ) {
            return my_works_daily_pulse_trim_list(array());
        }
        $db->reset_query();
        $db->select('a.id, a.project_id, a.action, a.detail, a.created_at, u.name AS user_name, p.name AS project_name', false);
        $db->from('project_activity a');
        $db->join('projects p', 'p.id = a.project_id', 'inner');
        $db->join('users u', 'u.id = a.user_id', 'left');
        $db->where('DATE(a.created_at)', $today);
        $db->order_by('a.created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();

        $out = array();
        foreach ($rows as $r) {
            $action = (string) $r->action;
            $detail = (string) $r->detail;
            if ($detail === '') {
                $detail = ucfirst(str_replace('_', ' ', $action));
            } else {
                if (mb_strlen($detail) > 100) {
                    $detail = mb_substr($detail, 0, 100) . '...';
                }
            }

            $out[] = array(
                'title'        => 'Project Update',
                'action'       => $action,
                'detail'       => $detail,
                'user_name'    => (string) ($r->user_name ?: ''),
                'project_name' => (string) ($r->project_name ?: ''),
                'client_name'  => '',
                'at'           => (string) $r->created_at,
                'url'          => site_url('projects/show/' . (int) $r->project_id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
    }
}

if (!function_exists('my_works_daily_pulse_client_history_overall')) {
    function my_works_daily_pulse_client_history_overall($db, $today)
    {
        if (!my_works_daily_pulse_has_access(array('clients', 'clients_list'))
            || !$db->table_exists('client_activity') || !$db->table_exists('clients')
        ) {
            return my_works_daily_pulse_trim_list(array());
        }
        $db->reset_query();
        $db->select('a.id, a.client_id, a.action, a.new_value, a.created_at, u.name AS user_name, c.company_name AS client_name', false);
        $db->from('client_activity a');
        $db->join('clients c', 'c.id = a.client_id', 'inner');
        $db->join('users u', 'u.id = a.user_id', 'left');
        $db->where('DATE(a.created_at)', $today);
        $db->order_by('a.created_at', 'DESC');
        $db->limit(100);
        $rows = $db->get()->result();

        $out = array();
        foreach ($rows as $r) {
            $action = (string) $r->action;
            $detail = ucfirst(str_replace('_', ' ', $action));
            if ($action === 'commented' || $action === 'note') {
                $detail = 'Added a note';
                $new = json_decode((string)$r->new_value, true);
                if (is_array($new) && !empty($new['text'])) {
                    $detail = strip_tags($new['text']);
                    if (mb_strlen($detail) > 100) {
                        $detail = mb_substr($detail, 0, 100) . '...';
                    }
                }
            }

            $out[] = array(
                'title'        => 'Client Update',
                'action'       => $action,
                'detail'       => $detail,
                'user_name'    => (string) ($r->user_name ?: ''),
                'project_name' => '',
                'client_name'  => (string) ($r->client_name ?: ''),
                'at'           => (string) $r->created_at,
                'url'          => site_url('clients/view/' . (int) $r->client_id),
            );
        }
        return my_works_daily_pulse_trim_list($out);
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
        schema_table_has_column($db, 'my_works', 'client_id');
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
            'daily_activity'     => my_works_daily_pulse_daily_activity_logs($db, $today),
            'project_history'    => my_works_daily_pulse_project_history_overall($db, $today),
            'client_history'     => my_works_daily_pulse_client_history_overall($db, $today),
            'adhoc_history'      => my_works_daily_pulse_work_history($db, $today, $can_view_all, $user_id, 'adhoc'),
            'requirements_added' => my_works_daily_pulse_requirements($db, $today),
            'defects_added'      => my_works_daily_pulse_defects($db, $today),
            'overview_today'     => my_works_daily_pulse_overview_today($db, $can_view_all, $user_id),
        );
    }
}
