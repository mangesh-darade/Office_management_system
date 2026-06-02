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
