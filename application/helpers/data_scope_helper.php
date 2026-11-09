<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central helpers for listing/query data visibility (Admin vs User / staff).
 *
 * - Admin tier: ROLE_ADMIN, role name "admin", or roles.group_type = "admin" (is_admin_group).
 * - User tier: roles.group_type = "user" or legacy ROLE_STAFF when group_type column missing (is_user_group).
 *
 * Manager/Lead follow the same rule as staff unless their role has admin group_type.
 *
 * PHP 5.6 / CodeIgniter 3 compatible.
 */

if (!function_exists('data_scope_sees_all_org_data')) {
    /**
     * True when listings should show organization-wide rows (no own-record restriction).
     */
    function data_scope_sees_all_org_data()
    {
        $CI =& get_instance();
        if (!$CI || !$CI->session) {
            return false;
        }
        $role_id = (int) $CI->session->userdata('role_id');
        if (function_exists('hierarchy_filter_sees_all_records') && hierarchy_filter_sees_all_records($role_id)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('data_scope_apply_created_by_user_group')) {
    /**
     * Restrict query to rows where $column equals current user id — only for user-group / staff roles.
     * Does not apply to admin or manager/lead (they use hierarchy filters elsewhere).
     *
     * @param CI_DB_query_builder $db
     * @param string              $tableAlias e.g. "p" or "" for no prefix
     * @param string              $column     default created_by
     * @return CI_DB_query_builder
     */
    function data_scope_apply_created_by_user_group($db, $tableAlias = '', $column = 'created_by')
    {
        if (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
            return $db;
        }
        if (!function_exists('is_user_group') || !is_user_group()) {
            return $db;
        }
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid < 1) {
            return $db;
        }
        $col = ($tableAlias !== '') ? $tableAlias . '.' . $column : $column;
        $db->where($col, $uid);
        return $db;
    }
}

if (!function_exists('data_scope_apply_user_id_column')) {
    /**
     * Restrict to rows where a user ownership column (e.g. user_id, employee user link) equals current user.
     * Only applied for user-group / staff; admin sees all; others unchanged (use hierarchy in caller).
     *
     * @param CI_DB_query_builder $db
     * @param string              $tableAlias
     * @param string              $column default user_id
     * @return CI_DB_query_builder
     */
    function data_scope_apply_user_id_column($db, $tableAlias = '', $column = 'user_id')
    {
        if (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
            return $db;
        }
        if (!function_exists('is_user_group') || !is_user_group()) {
            return $db;
        }
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid < 1) {
            return $db;
        }
        $col = ($tableAlias !== '') ? $tableAlias . '.' . $column : $column;
        $db->where($col, $uid);
        return $db;
    }
}

if (!function_exists('data_scope_visible_user_ids')) {
    /**
     * null = no restriction (admin group). Otherwise user IDs whose records are visible:
     * "own" => [me]; "all" => members of my assigned departments + me.
     *
     * @return int[]|null
     */
    function data_scope_visible_user_ids()
    {
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if (!function_exists('data_scope_role_mode')) {
            $CI->load->helper('hierarchy_filter');
        }
        if (data_scope_role_mode() === 'org') {
            return null;
        }
        if ($uid < 1) {
            return array();
        }
        $ids = get_accessible_hierarchy_user_ids($uid, (int) $CI->session->userdata('role_id'));
        $ids[] = $uid;
        return array_values(array_unique(array_map('intval', $ids)));
    }
}

if (!function_exists('data_scope_apply_owner_or_assignee')) {
    /**
     * Restrict a query to records created by / assigned to the visible users.
     *
     * @param CI_DB_query_builder $db
     * @param string[] $columns  e.g. array('t.created_by', 't.assigned_to')
     * @param array[]  $junctions e.g. array(array('task_assignees', 'task_id', 't.id'))
     * @return CI_DB_query_builder
     */
    function data_scope_apply_owner_or_assignee($db, array $columns, array $junctions = array())
    {
        $ids = data_scope_visible_user_ids();
        if ($ids === null) {
            return $db;
        }
        if (empty($ids) || (empty($columns) && empty($junctions))) {
            $db->where('1 = 0', null, false);
            return $db;
        }
        $in = implode(',', $ids);
        $parts = array();
        foreach ($columns as $col) {
            if (preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)?$/', $col)) {
                $parts[] = $col . ' IN (' . $in . ')';
            }
        }
        foreach ($junctions as $j) {
            if (count($j) === 3
                && preg_match('/^[a-zA-Z0-9_]+$/', $j[0]) && preg_match('/^[a-zA-Z0-9_]+$/', $j[1])
                && preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)?$/', $j[2])
                && $db->table_exists($j[0])
            ) {
                $parts[] = 'EXISTS (SELECT 1 FROM `' . $j[0] . '` _ds WHERE _ds.`' . $j[1] . '` = ' . $j[2]
                    . ' AND _ds.`user_id` IN (' . $in . '))';
            }
        }
        if (empty($parts)) {
            $db->where('1 = 0', null, false);
            return $db;
        }
        $db->where('(' . implode(' OR ', $parts) . ')', null, false);
        return $db;
    }
}

if (!function_exists('data_scope_row_visible')) {
    /**
     * Single-row check: any of $fields on $row (object) is a visible user id.
     *
     * @param object|null $row
     * @param string[]    $fields e.g. array('created_by', 'assigned_to')
     * @param callable|null $extra function($row, $ids) => bool for junction checks
     */
    function data_scope_row_visible($row, array $fields, $extra = null)
    {
        if (!$row) {
            return false;
        }
        $ids = data_scope_visible_user_ids();
        if ($ids === null) {
            return true;
        }
        foreach ($fields as $f) {
            if (isset($row->$f) && in_array((int) $row->$f, $ids, true)) {
                return true;
            }
        }
        return is_callable($extra) ? (bool) call_user_func($extra, $row, $ids) : false;
    }
}

if (!function_exists('data_scope_apply_project_scope')) {
    /**
     * Projects visible to me: created by / managed by / member of (me, or my assigned departments' users).
     *
     * @param CI_DB_query_builder $db
     * @param string $alias table alias, e.g. "p" (or "projects" / "" for none)
     */
    function data_scope_apply_project_scope($db, $alias = 'p')
    {
        $t = $alias !== '' ? $alias . '.' : '';
        $base = $alias !== '' ? $alias : 'projects';
        $cols = array();
        if (schema_table_has_column($db, 'projects', 'created_by')) {
            $cols[] = $t . 'created_by';
        }
        if (schema_table_has_column($db, 'projects', 'manager_id')) {
            $cols[] = $t . 'manager_id';
        }
        return data_scope_apply_owner_or_assignee($db, $cols, array(array('project_members', 'project_id', $base . '.id')));
    }
}

if (!function_exists('data_scope_project_visible')) {
    function data_scope_project_visible($project)
    {
        if (!$project) {
            return false;
        }
        $pid = (int) $project->id;
        return data_scope_row_visible($project, array('created_by', 'manager_id'), function ($row, $ids) use ($pid) {
            $CI =& get_instance();
            if (!$CI->db->table_exists('project_members')) {
                return false;
            }
            return (bool) $CI->db->where('project_id', $pid)->where_in('user_id', $ids)->limit(1)->get('project_members')->row();
        });
    }
}


if (!function_exists('data_scope_filter_departments')) {
    /**
     * Keep only departments of visible users (org-wide = unchanged).
     *
     * @param object[] $departments rows with dept_name
     * @return object[]
     */
    function data_scope_filter_departments(array $departments)
    {
        $ids = data_scope_visible_user_ids();
        if ($ids === null) {
            return $departments;
        }
        $CI =& get_instance();
        $names = array();
        if (!empty($ids) && $CI->db->table_exists('employees')) {
            foreach ($CI->db->select('department')->distinct()->where_in('user_id', $ids)->get('employees')->result() as $r) {
                $names[strtolower(trim((string) $r->department))] = true;
            }
        }
        return array_values(array_filter($departments, function ($d) use ($names) {
            return isset($names[strtolower(trim((string) $d->dept_name))]);
        }));
    }
}

if (!function_exists('data_scope_apply_owner_or_assignee_or')) {
    /**
     * Add (col IN visible ids OR ...) as an OR branch inside an open group_start().
     */
    function data_scope_apply_owner_or_assignee_or($db, array $columns, $table)
    {
        $ids = data_scope_visible_user_ids();
        if ($ids === null || empty($ids)) {
            return $db;
        }
        $parts = array();
        foreach ($columns as $c) {
            if (schema_table_has_column($db, $table, $c)) {
                $parts[] = '`' . $table . '`.`' . $c . '` IN (' . implode(',', array_map('intval', $ids)) . ')';
            }
        }
        if (!empty($parts)) {
            $db->or_where('(' . implode(' OR ', $parts) . ')', null, false);
        }
        return $db;
    }
}


if (!function_exists('data_scope_client_filter_where')) {
    /**
     * WHERE fragment (no leading AND) limiting clients to the visible scope, or '' when unrestricted.
     * Call BEFORE starting a query-builder chain (it compiles a sub-select).
     */
    function data_scope_client_filter_where($alias = 'clients')
    {
        $ids = data_scope_visible_user_ids();
        if ($ids === null) {
            return '';
        }
        $CI =& get_instance();
        $in = empty($ids) ? '0' : implode(',', array_map('intval', $ids));
        $a = preg_match('/^[a-zA-Z0-9_]+$/', $alias) ? $alias : 'clients';
        $parts = array();
        foreach (array('created_by', 'account_manager_id') as $c) {
            if (schema_table_has_column($CI->db, 'clients', $c)) {
                $parts[] = '`' . $a . '`.`' . $c . '` IN (' . $in . ')';
            }
        }
        if ($CI->db->table_exists('projects') && schema_table_has_column($CI->db, 'projects', 'client_id')) {
            $CI->db->reset_query();
            $CI->db->select('client_id')->from('projects')->where('client_id >', 0);
            data_scope_apply_project_scope($CI->db, 'projects');
            $parts[] = '`' . $a . '`.`id` IN (' . $CI->db->get_compiled_select() . ')';
        }
        return empty($parts) ? '1 = 0' : '(' . implode(' OR ', $parts) . ')';
    }
}
