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

if (!function_exists('my_works_apply_filters_to_query')) {
    function my_works_apply_filters_to_query($db, array $filters, $include_status = true)
    {
        if ($include_status && $filters['status'] !== '' && in_array($filters['status'], array('new', 'in_progress', 'closed'), true)) {
            $db->where('w.status', $filters['status']);
        }
        if ($filters['tag'] !== '') {
            $db->like('w.tag', $filters['tag']);
        }
        if ($filters['created_for'] > 0) {
            $db->where('w.created_for', $filters['created_for']);
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
            $db->where('w.created_for', $filters['current_user_id']);
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
            $db->where('w.status !=', 'closed');
        }
    }
}

if (!function_exists('my_works_count_rows')) {
    function my_works_count_rows($db, array $filters, $can_view_all, $user_id)
    {
        $db->from('my_works w');
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        my_works_apply_filters_to_query($db, $filters, true);
        return (int) $db->count_all_results();
    }
}

if (!function_exists('my_works_fetch_rows')) {
    function my_works_fetch_rows($db, array $filters, $can_view_all, $user_id, $limit = null, $offset = 0)
    {
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
        $stats = array('total' => 0, 'new' => 0, 'in_progress' => 0, 'closed' => 0, 'urgent' => 0, 'overdue' => 0, 'assigned_to_me' => 0);
        $uid = $filters['current_user_id'];
        foreach (array('new', 'in_progress', 'closed') as $st) {
            $db->from('my_works w');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $tmp = $filters;
            $tmp['status'] = '';
            my_works_apply_filters_to_query($db, $tmp, false);
            $db->where('w.status', $st);
            $stats[$st] = (int) $db->count_all_results();
        }
        $db->from('my_works w');
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        my_works_apply_filters_to_query($db, $tmp, false);
        $stats['total'] = (int) $db->count_all_results();

        $db->from('my_works w');
        my_works_apply_list_scope($db, $can_view_all, $user_id);
        $tmp = $filters;
        $tmp['status'] = '';
        $tmp['urgent_only'] = 0;
        my_works_apply_filters_to_query($db, $tmp, false);
        $db->where('w.is_urgent', 1);
        $db->where('w.status !=', 'closed');
        $stats['urgent'] = (int) $db->count_all_results();

        if (schema_table_has_column($db, 'my_works', 'due_date')) {
            $db->from('my_works w');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $tmp = $filters;
            $tmp['status'] = '';
            $tmp['overdue_only'] = 0;
            my_works_apply_filters_to_query($db, $tmp, false);
            $db->where('w.due_date IS NOT NULL', null, false);
            $db->where('w.due_date <', date('Y-m-d'));
            $db->where('w.status !=', 'closed');
            $stats['overdue'] = (int) $db->count_all_results();
        }

        if ($uid > 0) {
            $db->from('my_works w');
            my_works_apply_list_scope($db, $can_view_all, $user_id);
            $db->where('w.created_for', $uid);
            $db->where('w.status !=', 'closed');
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
        $total = my_works_count_rows($db, $filters, $can_view_all, $user_id);
        $rows = my_works_fetch_rows($db, $filters, $can_view_all, $user_id, $list_cap, 0);
        $stats = my_works_fetch_stats($db, $filters, $can_view_all, $user_id);
        $columns = array('new' => array(), 'in_progress' => array(), 'closed' => array());
        foreach ($rows as $row) {
            $st = isset($row->status) ? (string) $row->status : 'new';
            if (!isset($columns[$st])) {
                $st = 'new';
            }
            $columns[$st][] = $row;
        }
        $matrix_columns = my_works_build_matrix_columns($rows, ($filters['status'] === ''));
        return array(
            'rows'             => $rows,
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
            'can_add'          => function_exists('has_module_access') && (has_module_access('my_works_add') || has_module_access('my_works')),
            'can_quick_edit'   => function_exists('has_module_access') && (has_module_access('my_works_edit') || has_module_access('my_works')),
        );
    }
}
