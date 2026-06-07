<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Project Action-Priority Matrix (Effort × Impact).
 */

if (!function_exists('project_matrix_quadrants')) {
    function project_matrix_quadrants()
    {
        return apm_quadrants();
    }
}

if (!function_exists('project_matrix_grid_order')) {
    function project_matrix_grid_order()
    {
        return apm_grid_order();
    }
}

if (!function_exists('project_matrix_parse_filters')) {
    function project_matrix_parse_filters($input)
    {
        return array(
            'status'  => trim((string) $input->get('status')),
            'search'  => trim((string) $input->get('search')),
            'client_id' => (int) $input->get('client_id'),
        );
    }
}

if (!function_exists('project_matrix_is_at_risk')) {
    function project_matrix_is_at_risk($project, $days_ahead = 14)
    {
        $status = strtolower(trim((string) (isset($project->status) ? $project->status : '')));
        if (in_array($status, array('completed', 'cancelled'), true)) {
            return false;
        }
        if (empty($project->end_date)) {
            return ($status === 'on_hold');
        }
        $end = strtotime((string) $project->end_date);
        if (!$end) {
            return false;
        }
        $today = strtotime(date('Y-m-d'));
        if ($end < $today) {
            return true;
        }
        $threshold = strtotime('+' . (int) $days_ahead . ' days', $today);
        return ($end <= $threshold);
    }
}

if (!function_exists('project_matrix_high_impact')) {
    function project_matrix_high_impact($project)
    {
        $status = strtolower(trim((string) (isset($project->status) ? $project->status : '')));
        if ($status === 'active') {
            return true;
        }
        if ($status === 'completed' || $status === 'cancelled') {
            return false;
        }
        if (!empty($project->is_at_risk)) {
            return true;
        }
        $open_tasks = max(0, (int) (isset($project->total_tasks) ? $project->total_tasks : 0) - (int) (isset($project->completed_tasks) ? $project->completed_tasks : 0));
        if ($open_tasks >= 3) {
            return true;
        }
        if ((int) (isset($project->open_works) ? $project->open_works : 0) >= 2) {
            return true;
        }
        return false;
    }
}

if (!function_exists('project_matrix_high_effort')) {
    function project_matrix_high_effort($project)
    {
        $open_tasks = max(0, (int) (isset($project->total_tasks) ? $project->total_tasks : 0) - (int) (isset($project->completed_tasks) ? $project->completed_tasks : 0));
        if ($open_tasks > 5) {
            return true;
        }
        if ((int) (isset($project->open_works) ? $project->open_works : 0) > 3) {
            return true;
        }
        if (!empty($project->start_date) && !empty($project->end_date)) {
            $start = strtotime((string) $project->start_date);
            $end = strtotime((string) $project->end_date);
            if ($start && $end && ($end - $start) > (120 * 86400)) {
                return true;
            }
        }
        $status = strtolower(trim((string) (isset($project->status) ? $project->status : '')));
        return ($status === 'active' && $open_tasks > 0);
    }
}

if (!function_exists('project_matrix_quadrant_for_project')) {
    function project_matrix_quadrant_for_project($project)
    {
        $status = strtolower(trim((string) (isset($project->status) ? $project->status : 'planned')));
        if ($status === 'cancelled') {
            return null;
        }
        return apm_quadrant_from_axes(
            project_matrix_high_impact($project),
            project_matrix_high_effort($project)
        );
    }
}

if (!function_exists('project_matrix_task_stats')) {
    function project_matrix_task_stats($db, array $project_ids)
    {
        $out = array();
        if (empty($project_ids) || !$db->table_exists('tasks')) {
            return $out;
        }
        $ids = array();
        foreach ($project_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return $out;
        }
        $rows = $db->select('project_id, status, COUNT(*) AS cnt', false)
            ->from('tasks')
            ->where_in('project_id', $ids)
            ->group_by(array('project_id', 'status'))
            ->get()
            ->result();
        foreach ($rows as $r) {
            $pid = (int) $r->project_id;
            if (!isset($out[$pid])) {
                $out[$pid] = array('total' => 0, 'completed' => 0);
            }
            $cnt = (int) $r->cnt;
            $out[$pid]['total'] += $cnt;
            if ((string) $r->status === 'completed') {
                $out[$pid]['completed'] += $cnt;
            }
        }
        return $out;
    }
}

if (!function_exists('project_matrix_work_counts')) {
    function project_matrix_work_counts($db, array $project_ids)
    {
        $out = array();
        if (empty($project_ids) || !$db->table_exists('my_works')) {
            return $out;
        }
        if (!function_exists('schema_table_has_column')) {
            $CI =& get_instance();
            $CI->load->helper('schema_columns');
        }
        if (!schema_table_has_column($db, 'my_works', 'project_id')) {
            return $out;
        }
        $ids = array();
        foreach ($project_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return $out;
        }
        $rows = $db->select('project_id, COUNT(*) AS cnt', false)
            ->from('my_works')
            ->where_in('project_id', $ids)
            ->where('status !=', 'closed')
            ->group_by('project_id')
            ->get()
            ->result();
        foreach ($rows as $r) {
            $out[(int) $r->project_id] = (int) $r->cnt;
        }
        return $out;
    }
}

if (!function_exists('project_matrix_enrich_projects')) {
    function project_matrix_enrich_projects($db, array $projects)
    {
        $ids = array();
        foreach ($projects as $p) {
            $ids[] = (int) $p->id;
        }
        $task_stats = project_matrix_task_stats($db, $ids);
        $work_counts = project_matrix_work_counts($db, $ids);

        foreach ($projects as $p) {
            $pid = (int) $p->id;
            $total = isset($task_stats[$pid]) ? (int) $task_stats[$pid]['total'] : 0;
            $completed = isset($task_stats[$pid]) ? (int) $task_stats[$pid]['completed'] : 0;
            $p->total_tasks = $total;
            $p->completed_tasks = $completed;
            $p->completion_pct = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            $p->open_works = isset($work_counts[$pid]) ? (int) $work_counts[$pid] : 0;
            $p->is_at_risk = project_matrix_is_at_risk($p);
            $p->matrix_quadrant = project_matrix_quadrant_for_project($p);
            if (!empty($p->end_date)) {
                $end = strtotime((string) $p->end_date);
                $today = strtotime(date('Y-m-d'));
                $p->days_remaining = $end ? (int) floor(($end - $today) / 86400) : null;
                $p->is_overdue = ($end && $end < $today);
            } else {
                $p->days_remaining = null;
                $p->is_overdue = false;
            }
        }
        return $projects;
    }
}

if (!function_exists('project_matrix_filter_projects')) {
    function project_matrix_filter_projects(array $projects, array $filters, $db = null)
    {
        if ($db === null) {
            $CI =& get_instance();
            $db = $CI->db;
        }
        if (!function_exists('schema_table_has_column')) {
            $CI =& get_instance();
            $CI->load->helper('schema_columns');
        }
        $out = array();
        foreach ($projects as $p) {
            if (!empty($filters['status']) && (string) $p->status !== (string) $filters['status']) {
                continue;
            }
            if (!empty($filters['search'])) {
                $q = strtolower($filters['search']);
                $hay = strtolower(trim((string) $p->name . ' ' . (isset($p->code) ? $p->code : '')));
                if (strpos($hay, $q) === false) {
                    continue;
                }
            }
            if (!empty($filters['client_id']) && schema_table_has_column($db, 'projects', 'client_id')) {
                if ((int) (isset($p->client_id) ? $p->client_id : 0) !== (int) $filters['client_id']) {
                    continue;
                }
            }
            $q = isset($p->matrix_quadrant) ? $p->matrix_quadrant : project_matrix_quadrant_for_project($p);
            if ($q === null) {
                continue;
            }
            $out[] = $p;
        }
        return $out;
    }
}

if (!function_exists('project_matrix_build_columns')) {
    function project_matrix_build_columns(array $projects)
    {
        $defs = project_matrix_quadrants();
        $columns = array();
        foreach (array_keys($defs) as $key) {
            $columns[$key] = array();
        }
        foreach ($projects as $p) {
            $q = isset($p->matrix_quadrant) ? $p->matrix_quadrant : project_matrix_quadrant_for_project($p);
            if ($q === null || !isset($columns[$q])) {
                continue;
            }
            $columns[$q][] = $p;
        }
        return $columns;
    }
}

if (!function_exists('project_matrix_status_label')) {
    function project_matrix_status_label($code, array $status_map = array())
    {
        $code = (string) $code;
        if (isset($status_map[$code])) {
            return $status_map[$code];
        }
        return ucwords(str_replace('_', ' ', $code));
    }
}

if (!function_exists('project_matrix_can_view_works')) {
    function project_matrix_can_view_works()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('my_works') || has_module_access('my_works_list');
    }
}

if (!function_exists('project_matrix_can_view_tasks')) {
    function project_matrix_can_view_tasks()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('tasks') || has_module_access('tasks_list') || has_module_access('tasks_manage');
    }
}
