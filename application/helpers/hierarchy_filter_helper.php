<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('get_role_name_by_id')) {
    function get_role_name_by_id($role_id)
    {
        $CI =& get_instance();
        $role_id = (int)$role_id;
        if ($role_id <= 0) { return ''; }
        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('roles')) { return ''; }

        // Use raw query to avoid polluting caller query-builder state.
        $row = $CI->db->query(
            'SELECT `name` FROM `roles` WHERE `id` = ? LIMIT 1',
            array($role_id)
        )->row();
        return $row && isset($row->name) ? strtolower(trim((string)$row->name)) : '';
    }
}

if (!function_exists('get_mapped_user_ids_for_lead')) {
    function get_mapped_user_ids_for_lead($lead_id)
    {
        $CI =& get_instance();
        $lead_id = (int)$lead_id;
        if ($lead_id <= 0 || !isset($CI->db) || !$CI->db || !$CI->db->table_exists('lead_user_mapping')) {
            return [];
        }

        // Use raw query to avoid polluting caller query-builder state.
        $rows = $CI->db->query(
            'SELECT `user_id` FROM `lead_user_mapping` WHERE `lead_id` = ?',
            array($lead_id)
        )->result();

        $ids = [];
        foreach ($rows as $r) {
            $uid = isset($r->user_id) ? (int)$r->user_id : 0;
            if ($uid > 0) { $ids[] = $uid; }
        }
        return array_values(array_unique($ids));
    }
}

if (!function_exists('get_accessible_hierarchy_user_ids')) {
    function get_accessible_hierarchy_user_ids($user_id = null, $role_id = null)
    {
        $CI =& get_instance();
        $user_id = $user_id === null ? (int)$CI->session->userdata('user_id') : (int)$user_id;
        $role_id = $role_id === null ? (int)$CI->session->userdata('role_id') : (int)$role_id;

        if ($user_id <= 0) { return []; }

        $role_name = get_role_name_by_id($role_id);
        $is_admin = ($role_id === ROLE_ADMIN || $role_name === 'admin');
        if ($is_admin) {
            return [];
        }

        $is_lead = ($role_id === ROLE_LEAD || $role_name === 'lead');
        if ($is_lead) {
            $mapped = get_mapped_user_ids_for_lead($user_id);
            $mapped[] = $user_id; // Lead can also see own records
            return array_values(array_unique(array_map('intval', $mapped)));
        }

        return [$user_id];
    }
}

if (!function_exists('apply_role_hierarchy_filter')) {
    /**
     * Apply role/hierarchy visibility filter on query builder.
     *
     * @param CI_DB_query_builder $db
     * @param string $column
     * @param int|null $user_id
     * @param int|null $role_id
     * @return CI_DB_query_builder
     */
    function apply_role_hierarchy_filter($db, $column = 'created_by', $user_id = null, $role_id = null)
    {
        $CI =& get_instance();
        $user_id = $user_id === null ? (int)$CI->session->userdata('user_id') : (int)$user_id;
        $role_id = $role_id === null ? (int)$CI->session->userdata('role_id') : (int)$role_id;

        $role_name = get_role_name_by_id($role_id);
        $is_admin = ($role_id === ROLE_ADMIN || $role_name === 'admin');
        if ($is_admin) {
            return $db;
        }

        $allowed_ids = get_accessible_hierarchy_user_ids($user_id, $role_id);
        if (empty($allowed_ids)) {
            $db->where('1 = 0', null, false);
            return $db;
        }

        $db->where_in($column, array_map('intval', $allowed_ids));
        return $db;
    }
}
