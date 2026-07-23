<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * My Works list filters, queries, stats, and list/board view data.
 */

if (!function_exists('my_works_parse_filters')) {
    /**
     * @param CI_Input $input
     * @param int      $user_id
     * @return array
     */
    function my_works_parse_filters($input, $user_id)
    {
        return array(
            'status'          => trim((string) $input->get('status')),
            'tag'             => trim((string) $input->get('tag')),
            'q'               => trim((string) $input->get('q')),
            'created_for'     => (int) $input->get('created_for'),
            'created_by'      => (int) $input->get('created_by'),
            'client_id'       => (int) $input->get('client_id'),
            'project_id'      => (int) $input->get('project_id'),
            'work_type'       => trim((string) $input->get('work_type')),
            'involvement'     => trim((string) $input->get('involvement')),
            'urgent_only'     => $input->get('urgent_only') ? 1 : 0,
            'important_only'  => $input->get('important_only') ? 1 : 0,
            'overdue_only'    => $input->get('overdue_only') ? 1 : 0,
            'current_user_id' => (int) $user_id,
        );
    }
}

if (!function_exists('my_works_sanitize_filters')) {
    function my_works_sanitize_filters(array $filters, $can_view_all, $user_id)
    {
        if (!$can_view_all) {
            if ($filters['created_for'] > 0 && (int) $filters['created_for'] !== (int) $user_id) {
                $filters['created_for'] = 0;
            }
            $filters['created_by'] = 0;
        }
        if (!in_array($filters['involvement'], array('all', 'created', 'assigned'), true)) {
            $filters['involvement'] = 'all';
        }
        return $filters;
    }
}

if (!function_exists('my_works_warm_schema_cache')) {
    /**
     * Pre-load my_works column map before building list queries.
     * schema_table_has_column() calls list_fields() on first use and resets the active QB.
     */
    function my_works_warm_schema_cache($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        schema_table_has_column($db, 'my_works', 'client_id');
        schema_table_has_column($db, 'my_works', 'project_id');
        schema_table_has_column($db, 'my_works', 'work_type');
        schema_table_has_column($db, 'my_works', 'due_date');
        schema_table_has_column($db, 'my_works', 'closed_at');
    }
}

if (!function_exists('my_works_warm_status_cache')) {
    /**
     * Pre-load status codes before building list queries.
     * my_works_status_is_valid() otherwise queries DB mid-build and resets the active QB.
     */
    function my_works_warm_status_cache()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        my_works_status_codes();
        my_works_finished_status_codes();
    }
}

if (!function_exists('my_works_warm_query_caches')) {
    function my_works_warm_query_caches($db)
    {
        my_works_warm_schema_cache($db);
        my_works_warm_status_cache();
    }
}

if (!function_exists('my_works_begin_scoped_query')) {
    function my_works_begin_scoped_query($db, array $filters, $can_view_all, $user_id, $include_status = true)
    {
        my_works_warm_query_caches($db);
        $db->reset_query();
        $db->from('my_works w');
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        my_works_apply_filters_to_query($db, $filters, $include_status);
    }
}

if (!function_exists('my_works_apply_filters_to_query')) {
    function my_works_apply_filters_to_query($db, array $filters, $include_status = true)
    {
        if ($include_status && $filters['status'] !== '') {
            my_works_warm_status_cache();
            if (my_works_status_is_valid($filters['status'])) {
                $db->where('w.status', $filters['status']);
            }
        }
        if ($filters['tag'] !== '') {
            $db->like('w.tag', $filters['tag']);
        }
        if ($filters['created_for'] > 0) {
            $CI =& get_instance();
            $CI->load->helper('multi_assignee');
            multi_assignees_apply_user_match(
                $db,
                'w.created_for',
                'my_works_assignees',
                'work_id',
                'w.id',
                (int) $filters['created_for']
            );
        }
        if ($filters['created_by'] > 0) {
            $db->where('w.created_by', $filters['created_by']);
        }
        if (!empty($filters['client_id']) && schema_table_has_column($db, 'my_works', 'client_id')) {
            $db->where('w.client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['project_id']) && schema_table_has_column($db, 'my_works', 'project_id')) {
            $db->where('w.project_id', (int) $filters['project_id']);
        }
        if ($filters['work_type'] !== '' && schema_table_has_column($db, 'my_works', 'work_type')) {
            $CI =& get_instance();
            $CI->load->helper('types');
            if (module_type_is_valid($filters['work_type'], 'my_works')) {
                $db->where('w.work_type', $filters['work_type']);
            }
        }
        if ($filters['involvement'] === 'created') {
            $db->where('w.created_by', $filters['current_user_id']);
        } elseif ($filters['involvement'] === 'assigned') {
            $CI =& get_instance();
            $CI->load->helper('multi_assignee');
            multi_assignees_apply_user_match(
                $db,
                'w.created_for',
                'my_works_assignees',
                'work_id',
                'w.id',
                (int) $filters['current_user_id']
            );
        }
        if ($filters['q'] !== '') {
            $db->group_start()
                ->like('w.title', $filters['q'])
                ->or_like('w.details', $filters['q'])
                ->or_like('w.tag', $filters['q'])
            ->group_end();
        }
        if ($filters['urgent_only']) {
            $db->where('w.is_urgent', 1);
        }
        if ($filters['important_only']) {
            $db->where('w.is_important', 1);
        }
        if ($filters['overdue_only'] && schema_table_has_column($db, 'my_works', 'due_date')) {
            $db->where('w.due_date IS NOT NULL', null, false);
            $db->where('w.due_date <', date('Y-m-d'));
            my_works_warm_status_cache();
            my_works_apply_open_status_filter($db, 'w.status');
        }
    }
}

if (!function_exists('my_works_count_rows')) {
    function my_works_count_rows($db, array $filters, $can_view_all, $user_id)
    {
        my_works_begin_scoped_query($db, $filters, $can_view_all, $user_id, true);
        return (int) $db->count_all_results();
    }
}

if (!function_exists('my_works_fetch_rows')) {
    /**
     * @param bool $open_only When true and no status filter is set, exclude finished statuses.
     */
    function my_works_fetch_rows($db, array $filters, $can_view_all, $user_id, $limit = null, $offset = 0, $open_only = false)
    {
        my_works_warm_query_caches($db);
        $db->reset_query();
        $db->select('w.*, cb.name AS created_by_name, cb.email AS created_by_email, cf.name AS created_for_name, cf.email AS created_for_email', false);
        if ($db->table_exists('clients') && schema_table_has_column($db, 'my_works', 'client_id')) {
            $db->select('cl.company_name AS client_name', false);
        }
        if ($db->table_exists('projects') && schema_table_has_column($db, 'my_works', 'project_id')) {
            $db->select('p.name AS project_name', false);
        }
        $db->from('my_works w');
        $db->join('users cb', 'cb.id = w.created_by', 'left');
        $db->join('users cf', 'cf.id = w.created_for', 'left');
        if ($db->table_exists('clients') && schema_table_has_column($db, 'my_works', 'client_id')) {
            $db->join('clients cl', 'cl.id = w.client_id', 'left');
        }
        if ($db->table_exists('projects') && schema_table_has_column($db, 'my_works', 'project_id')) {
            $db->join('projects p', 'p.id = w.project_id', 'left');
        }
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        my_works_apply_filters_to_query($db, $filters, true);
        if ($open_only && empty($filters['status'])) {
            $CI =& get_instance();
            $CI->load->helper('my_works_status');
            my_works_apply_open_status_filter($db, 'w.status');
        }
        $db->order_by('w.is_urgent', 'DESC');
        $db->order_by('w.is_important', 'DESC');
        if (schema_table_has_column($db, 'my_works', 'due_date')) {
            $db->order_by('w.due_date', 'ASC');
        }
        $db->order_by('w.updated_at', 'DESC');
        $db->order_by('w.id', 'DESC');
        if ($limit !== null) {
            $db->limit((int) $limit, (int) $offset);
        }
        return $db->get()->result();
    }
}

if (!function_exists('my_works_fetch_stats')) {
    function my_works_fetch_stats($db, array $filters, $can_view_all, $user_id)
    {
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        $stats = array('total' => 0, 'urgent' => 0, 'overdue' => 0, 'assigned_to_me' => 0);
        foreach (my_works_status_codes() as $st) {
            $stats[$st] = 0;
        }
        $uid = $filters['current_user_id'];
        my_works_warm_query_caches($db);
        foreach (my_works_status_codes() as $st) {
            $tmp = $filters;
            $tmp['status'] = '';
            my_works_begin_scoped_query($db, $tmp, $can_view_all, $user_id, false);
            $db->where('w.status', $st);
            $stats[$st] = (int) $db->count_all_results();
        }
        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        my_works_begin_scoped_query($db, $tmp, $can_view_all, $user_id, false);
        $stats['total'] = (int) $db->count_all_results();

        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        my_works_begin_scoped_query($db, $tmp, $can_view_all, $user_id, false);
        $db->where('w.is_urgent', 1);
        my_works_apply_open_status_filter($db, 'w.status');
        $stats['urgent'] = (int) $db->count_all_results();

        if (schema_table_has_column($db, 'my_works', 'due_date')) {
            $tmp = $filters;
            $tmp['status'] = '';
            $tmp['overdue_only'] = 0;
            my_works_begin_scoped_query($db, $tmp, $can_view_all, $user_id, false);
            $db->where('w.due_date IS NOT NULL', null, false);
            $db->where('w.due_date <', date('Y-m-d'));
            my_works_apply_open_status_filter($db, 'w.status');
            $stats['overdue'] = (int) $db->count_all_results();
        }

        if ($uid > 0) {
            my_works_warm_query_caches($db);
            $db->reset_query();
            $db->from('my_works w');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $CI->load->helper('multi_assignee');
            multi_assignees_apply_user_match(
                $db,
                'w.created_for',
                'my_works_assignees',
                'work_id',
                'w.id',
                (int) $uid
            );
            my_works_apply_open_status_filter($db, 'w.status');
            $stats['assigned_to_me'] = (int) $db->count_all_results();
        }
        return $stats;
    }
}

if (!function_exists('my_works_list_view_data')) {
    /**
     * @param My_work_model $model
     * @param callable|null $scope_callback Passed to distinct_tags_scoped
     */
    function my_works_list_view_data($db, $model, array $filters, $view_mode, $list_cap, $can_view_all, $user_id, $role_id, $scope_callback)
    {
        my_works_warm_query_caches($db);
        $open_only = in_array((string) $view_mode, array('overview'), true);
        $total = my_works_count_rows($db, $filters, $can_view_all, $user_id);
        $rows = my_works_fetch_rows($db, $filters, $can_view_all, $user_id, $list_cap, 0, $open_only);
        $stats = my_works_fetch_stats($db, $filters, $can_view_all, $user_id);
        $CI =& get_instance();
        $CI->load->helper('my_works_status');
        $columns = array();
        foreach (my_works_status_codes() as $st) {
            $columns[$st] = array();
        }
        foreach ($rows as $row) {
            $st = isset($row->status) ? (string) $row->status : my_works_status_default_code();
            if (!isset($columns[$st])) {
                $st = my_works_status_default_code();
            }
            $columns[$st][] = $row;
        }
        $matrix_columns = my_works_build_matrix_columns($rows, ($filters['status'] === ''));
        $CI->load->helper('my_works_attachment');
        $work_ids = array();
        foreach ($rows as $row) {
            if (!empty($row->id)) {
                $work_ids[] = (int) $row->id;
            }
        }
        $attachments_map = my_works_attachments_bulk_map($db, $work_ids);
        $assignee_names_map = array();
        if (!empty($work_ids) && function_exists('multi_assignees_names_map')) {
            $CI->load->helper('multi_assignee');
            $assignee_names_map = multi_assignees_names_map('my_works_assignees', 'work_id', $work_ids);
        }
        return array(
            'rows'             => $rows,
            'attachments_map'  => $attachments_map,
            'assignee_names_map' => $assignee_names_map,
            'filters'          => $filters,
            'stats'            => $stats,
            'columns'          => $columns,
            'matrix_columns'   => $matrix_columns,
            'total_rows'       => $total,
            'list_capped'      => ($total > count($rows)),
            'list_shown_count' => count($rows),
            'tags'             => $model->distinct_tags_scoped($scope_callback),
            'clients'          => my_works_clients_for_dropdown($db),
            'projects'         => my_works_projects_for_dropdown($db),
            'projects_have_client' => schema_table_has_column($db, 'projects', 'client_id'),
            'users'            => my_works_filter_users_for_dropdown($db, $can_view_all, $user_id, $role_id),
            'can_view_all'     => $can_view_all,
            'can_filter_users' => $can_view_all,
            'can_export'       => function_exists('has_module_access') && (has_module_access('my_works_export') || has_module_access('my_works')),
            'scope'            => my_works_scope_context($can_view_all, array($user_id)),
            'view_mode'        => $view_mode,
            'can_add'          => function_exists('my_works_can_add') && my_works_can_add(),
            'can_quick_edit'   => function_exists('has_module_access') && (has_module_access('my_works_edit') || has_module_access('my_works')),
        );
    }
}

if (!function_exists('my_works_fetch_recent_feed')) {
    /**
     * Recent activity + comments for overview feed (scoped).
     *
     * @return array<int, array<string, mixed>>
     */
    function my_works_fetch_recent_feed($db, $can_view_all, $user_id, $limit = 40)
    {
        $limit = max(1, min(100, (int) $limit));
        $feed = array();

        if ($db->table_exists('my_work_activity')) {
            $db->select('a.id, a.work_id, a.user_id, a.action, a.detail, a.created_at, u.name AS user_name, w.title AS work_title', false);
            $db->from('my_work_activity a');
            $db->join('my_works w', 'w.id = a.work_id');
            $db->join('users u', 'u.id = a.user_id', 'left');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $db->order_by('a.id', 'DESC');
            $db->limit($limit);
            foreach ($db->get()->result() as $row) {
                $detail = trim((string) $row->detail);
                $text = (string) $row->action;
                if ($detail !== '') {
                    $text .= ': ' . $detail;
                }
                $feed[] = array(
                    'type'       => 'activity',
                    'sort_key'   => (string) $row->created_at . '-a' . (int) $row->id,
                    'work_id'    => (int) $row->work_id,
                    'work_title' => (string) $row->work_title,
                    'user_id'    => (int) $row->user_id,
                    'user_name'  => (string) $row->user_name,
                    'text'       => $text,
                    'created_at' => (string) $row->created_at,
                );
            }
        }

        if ($db->table_exists('my_work_comments')) {
            $db->select('c.id, c.work_id, c.user_id, c.comment, c.created_at, u.name AS user_name, w.title AS work_title', false);
            $db->from('my_work_comments c');
            $db->join('my_works w', 'w.id = c.work_id');
            $db->join('users u', 'u.id = c.user_id', 'left');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $db->order_by('c.id', 'DESC');
            $db->limit($limit);
            foreach ($db->get()->result() as $row) {
                $feed[] = array(
                    'type'       => 'comment',
                    'sort_key'   => (string) $row->created_at . '-c' . (int) $row->id,
                    'work_id'    => (int) $row->work_id,
                    'work_title' => (string) $row->work_title,
                    'user_id'    => (int) $row->user_id,
                    'user_name'  => (string) $row->user_name,
                    'text'       => (string) $row->comment,
                    'created_at' => (string) $row->created_at,
                );
            }
        }

        usort($feed, function ($a, $b) {
            return strcmp($b['sort_key'], $a['sort_key']);
        });

        return array_slice($feed, 0, $limit);
    }
}

if (!function_exists('my_works_overview_item_payload')) {
    /**
     * @param object $row
     * @param array  $attachments_map
     * @return array<string, mixed>
     */
    function my_works_overview_item_payload($row, array $attachments_map, $assignee_extra_names = null)
    {
        $statusLabels = my_works_status_labels();
        $statusColors = my_works_status_colors();
        $st = isset($row->status) ? (string) $row->status : 'new';
        $wid = (int) $row->id;
        $atts = isset($attachments_map[$wid]) ? $attachments_map[$wid] : array();
        $details = isset($row->details) ? trim(strip_tags((string) $row->details)) : '';
        if (strlen($details) > 600) {
            $details = substr($details, 0, 600) . '…';
        }
        $forLabel = my_works_user_label($row->created_for_name, $row->created_for_email, $row->created_for);
        if (function_exists('multi_assignees_format_label')) {
            $forLabel = multi_assignees_format_label($forLabel, $assignee_extra_names);
        }
        $byLabel = my_works_user_label($row->created_by_name, $row->created_by_email, $row->created_by);
        $year = '';
        if (!empty($row->due_date)) {
            $year = substr((string) $row->due_date, 0, 4);
        } elseif (!empty($row->created_at)) {
            $year = substr((string) $row->created_at, 0, 4);
        }
        $priority = my_works_priority_label($row);
        $priorityClass = my_works_priority_class($row);
        $typeLabel = !empty($row->work_type) ? my_works_type_label($row->work_type) : 'Work item';
        $attNames = array();
        foreach ($atts as $att) {
            if (!empty($att->original_name)) {
                $attNames[] = (string) $att->original_name;
            } elseif (!empty($att->stored_name)) {
                $attNames[] = (string) $att->stored_name;
            }
        }
        return array(
            'id'             => $wid,
            'title'          => (string) $row->title,
            'status'         => $st,
            'status_label'   => isset($statusLabels[$st]) ? $statusLabels[$st] : $st,
            'status_color'   => isset($statusColors[$st]) ? $statusColors[$st] : 'secondary',
            'priority'       => $priority,
            'priority_class' => $priorityClass,
            'year'           => $year,
            'type_label'     => $typeLabel,
            'due_date'       => !empty($row->due_date) ? (string) $row->due_date : '',
            'assignee'       => $forLabel,
            'creator'        => $byLabel,
            'details'        => $details,
            'url'            => !empty($row->url) ? (string) $row->url : '',
            'tags'           => my_works_parse_tags(isset($row->tag) ? $row->tag : ''),
            'is_urgent'      => (int) $row->is_urgent === 1,
            'is_important'   => (int) $row->is_important === 1,
            'is_overdue'     => my_works_is_overdue($row),
            'updated_at'     => !empty($row->updated_at) ? (string) $row->updated_at : '',
            'updated_label'  => my_works_format_when(isset($row->updated_at) ? $row->updated_at : ''),
            'client'         => !empty($row->client_name) ? (string) $row->client_name : '',
            'project'        => !empty($row->project_name) ? (string) $row->project_name : '',
            'attachment_count' => count($atts),
            'attachments'    => $attNames,
            'view_url'       => site_url('my-works/' . $wid),
            'edit_url'       => site_url('my-works/' . $wid . '/edit'),
        );
    }
}

if (!function_exists('my_works_overview_task_status_codes')) {
    /**
     * Task module statuses that can appear on Overview project lanes.
     *
     * @return string[]
     */
    function my_works_overview_task_status_codes()
    {
        return array('pending', 'in_progress', 'blocked', 'completed');
    }
}

if (!function_exists('my_works_normalize_task_for_overview')) {
    /**
     * Shape a tasks-table row like a my_works dashboard row (project section only).
     *
     * @param object $row
     * @return object
     */
    function my_works_normalize_task_for_overview($row)
    {
        if (!is_object($row)) {
            return $row;
        }
        $row->item_source = 'tasks';
        $assigned = isset($row->assigned_to) ? (int) $row->assigned_to : 0;
        $row->created_for = $assigned;
        if (!isset($row->created_for_name) || $row->created_for_name === null || $row->created_for_name === '') {
            if (!empty($row->assignee_name)) {
                $row->created_for_name = (string) $row->assignee_name;
            } elseif (!empty($row->assignee_user_name)) {
                $row->created_for_name = (string) $row->assignee_user_name;
            } elseif (!empty($row->assignee_full_name)) {
                $row->created_for_name = (string) $row->assignee_full_name;
            } else {
                $row->created_for_name = '';
            }
        }
        if (!isset($row->created_for_email) || $row->created_for_email === null) {
            $row->created_for_email = !empty($row->assignee_email) ? (string) $row->assignee_email : '';
        }
        if (!isset($row->tag)) {
            $row->tag = '';
        }
        if (!isset($row->is_urgent)) {
            $row->is_urgent = 0;
        }
        if (!isset($row->is_important)) {
            $row->is_important = 0;
        }
        if (!isset($row->closed_at)) {
            $row->closed_at = null;
        }
        return $row;
    }
}

if (!function_exists('my_works_fetch_overview_project_tasks')) {
    /**
     * Open project tasks for Overview Project section (same filters/scope as Team cart where possible).
     *
     * @param CI_DB_query_builder $db
     * @param array $filters
     * @param bool $can_view_all
     * @param int $user_id
     * @param int|null $limit
     * @return object[]
     */
    function my_works_fetch_overview_project_tasks($db, array $filters, $can_view_all, $user_id, $limit = null)
    {
        if (!$db->table_exists('tasks') || !schema_table_has_column($db, 'tasks', 'project_id')) {
            return array();
        }
        // Warm column map before building QB (list_fields resets active query).
        schema_table_has_column($db, 'tasks', 'assigned_to');
        schema_table_has_column($db, 'tasks', 'created_by');
        schema_table_has_column($db, 'tasks', 'estimate_hours');
        schema_table_has_column($db, 'tasks', 'start_date');
        schema_table_has_column($db, 'tasks', 'updated_at');
        schema_table_has_column($db, 'tasks', 'due_date');
        schema_table_has_column($db, 'tasks', 'description');
        schema_table_has_column($db, 'projects', 'name');
        schema_table_has_column($db, 'projects', 'client_id');
        schema_table_has_column($db, 'users', 'name');
        schema_table_has_column($db, 'users', 'email');
        schema_table_has_column($db, 'employees', 'name');

        // Filters that only exist on my_works — skip tasks so Overview stays coherent.
        if (!empty($filters['tag'])
            || !empty($filters['work_type'])
            || !empty($filters['urgent_only'])
            || !empty($filters['important_only'])
        ) {
            return array();
        }

        $status = isset($filters['status']) ? strtolower(trim((string) $filters['status'])) : '';
        $task_statuses = my_works_overview_task_status_codes();
        if ($status !== '' && !in_array($status, $task_statuses, true)) {
            return array();
        }

        $user_id = (int) $user_id;
        $select = array(
            't.id',
            't.project_id',
            't.title',
            't.status',
            't.due_date',
            't.created_at',
            't.assigned_to',
        );
        if (schema_table_has_column($db, 'tasks', 'updated_at')) {
            $select[] = 't.updated_at';
        }
        if (schema_table_has_column($db, 'tasks', 'created_by')) {
            $select[] = 't.created_by';
        }
        if (schema_table_has_column($db, 'tasks', 'estimate_hours')) {
            $select[] = 't.estimate_hours';
        }
        if (schema_table_has_column($db, 'tasks', 'start_date')) {
            $select[] = 't.start_date';
        }

        $db->reset_query();
        $db->from('tasks t');

        if ($db->table_exists('projects') && schema_table_has_column($db, 'projects', 'name')) {
            $select[] = 'p.name AS project_name';
            $db->join('projects p', 'p.id = t.project_id', 'left');
            if (!empty($filters['client_id']) && schema_table_has_column($db, 'projects', 'client_id')) {
                $db->where('p.client_id', (int) $filters['client_id']);
            }
        } elseif (!empty($filters['client_id'])) {
            return array();
        }

        if ($db->table_exists('users') && schema_table_has_column($db, 'tasks', 'assigned_to')) {
            if (schema_table_has_column($db, 'users', 'name')) {
                $select[] = 'u.name AS assignee_user_name';
            }
            if (schema_table_has_column($db, 'users', 'email')) {
                $select[] = 'u.email AS assignee_email';
            }
            $db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        if ($db->table_exists('employees') && schema_table_has_column($db, 'tasks', 'assigned_to')) {
            if (schema_table_has_column($db, 'employees', 'name')) {
                $select[] = 'e.name AS assignee_name';
            }
            $db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }

        $db->select(implode(', ', $select), false);
        $db->where('t.project_id IS NOT NULL', null, false);
        $db->where('t.project_id >', 0);

        if (!empty($filters['project_id'])) {
            $db->where('t.project_id', (int) $filters['project_id']);
        }
        if (!empty($filters['created_for']) && schema_table_has_column($db, 'tasks', 'assigned_to')) {
            $CI =& get_instance();
            $CI->load->helper('multi_assignee');
            multi_assignees_apply_user_match(
                $db,
                't.assigned_to',
                'task_assignees',
                'task_id',
                't.id',
                (int) $filters['created_for']
            );
        }
        if (!empty($filters['created_by']) && schema_table_has_column($db, 'tasks', 'created_by')) {
            $db->where('t.created_by', (int) $filters['created_by']);
        }
        if ($status !== '') {
            $db->where('t.status', $status);
        } else {
            // Match Overview: open planning work only.
            $CI =& get_instance();
            $CI->load->helper('my_works_status');
            $db->group_start();
            $db->where('t.status IS NULL', null, false);
            $db->or_where('t.status', '');
            foreach ($task_statuses as $st) {
                if ($st === 'completed') {
                    continue;
                }
                $db->or_where('t.status', $st);
            }
            $db->group_end();
        }
        if (!empty($filters['overdue_only']) && schema_table_has_column($db, 'tasks', 'due_date')) {
            $db->where('t.due_date IS NOT NULL', null, false);
            $db->where('t.due_date <', date('Y-m-d'));
        }
        if (!empty($filters['q'])) {
            $q = (string) $filters['q'];
            $db->group_start();
            $db->like('t.title', $q);
            if (schema_table_has_column($db, 'tasks', 'description')) {
                $db->or_like('t.description', $q);
            }
            $db->group_end();
        }

        $involvement = isset($filters['involvement']) ? (string) $filters['involvement'] : 'all';
        if ($involvement === 'assigned' && schema_table_has_column($db, 'tasks', 'assigned_to') && $user_id > 0) {
            $CI =& get_instance();
            $CI->load->helper('multi_assignee');
            multi_assignees_apply_user_match(
                $db,
                't.assigned_to',
                'task_assignees',
                'task_id',
                't.id',
                $user_id
            );
        } elseif ($involvement === 'created' && schema_table_has_column($db, 'tasks', 'created_by') && $user_id > 0) {
            $db->where('t.created_by', $user_id);
        }

        if (!$can_view_all) {
            if ($user_id < 1) {
                return array();
            }
            $CI =& get_instance();
            $CI->load->helper('multi_assignee');
            $db->group_start();
            if (schema_table_has_column($db, 'tasks', 'assigned_to')) {
                multi_assignees_apply_user_match(
                    $db,
                    't.assigned_to',
                    'task_assignees',
                    'task_id',
                    't.id',
                    $user_id
                );
            }
            if (schema_table_has_column($db, 'tasks', 'created_by')) {
                $db->or_where('t.created_by', $user_id);
            }
            $db->group_end();
        }

        if (schema_table_has_column($db, 'tasks', 'due_date')) {
            $db->order_by('t.due_date', 'ASC');
        }
        if (schema_table_has_column($db, 'tasks', 'updated_at')) {
            $db->order_by('t.updated_at', 'DESC');
        } else {
            $db->order_by('t.created_at', 'DESC');
        }
        $db->order_by('t.id', 'DESC');
        if ($limit !== null) {
            $db->limit((int) $limit, 0);
        }

        $rows = $db->get()->result();
        $out = array();
        foreach ($rows as $row) {
            $out[] = my_works_normalize_task_for_overview($row);
        }
        return $out;
    }
}
