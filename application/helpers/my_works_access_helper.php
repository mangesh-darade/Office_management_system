<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * My Works access control, assignable users, and row-level permissions.
 */

if (!function_exists('my_works_apply_list_scope')) {
    /**
     * Personal list scope: created by me OR assigned to me. Admin/view-all: no filter.
     *
     * @param CI_DB_query_builder $db
     * @param bool $can_view_all
     * @param int  $user_id
     */
    function my_works_apply_list_scope($db, $can_view_all, $user_id)
    {
        if ($can_view_all) {
            return;
        }
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            $db->where('1 = 0', null, false);
            return;
        }
        $db->group_start();
        $db->where('w.created_by', $user_id);
        $db->or_where('w.created_for', $user_id);
        if ($db->table_exists('my_works_assignees')) {
            $db->or_where(
                'EXISTS (SELECT 1 FROM `my_works_assignees` _mwa WHERE _mwa.`work_id` = w.id AND _mwa.`user_id` = ' . $user_id . ')',
                null,
                false
            );
        }
        $db->group_end();
    }
}

if (!function_exists('my_works_user_can_access')) {
    function my_works_user_can_access($work, $can_view_all, $user_id)
    {
        if (!$work) {
            return false;
        }
        if ($can_view_all) {
            return true;
        }
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }
        return my_works_user_in_personal_scope($work, $user_id);
    }
}

if (!function_exists('my_works_require_access')) {
    function my_works_require_access($work, $can_view_all, $user_id)
    {
        if (!my_works_user_can_access($work, $can_view_all, $user_id)) {
            show_error('You do not have permission to access this work item.', 403);
        }
    }
}

if (!function_exists('my_works_assignable_user_ids')) {
    /**
     * @return int[]|null null = all users (admin view)
     */
    function my_works_assignable_user_ids($can_view_all, $user_id, $role_id)
    {
        if ($can_view_all) {
            return null;
        }
        $user_id = (int) $user_id;
        $role_id = (int) $role_id;
        $allowed = get_accessible_hierarchy_user_ids($user_id, $role_id);
        if (empty($allowed)) {
            return array($user_id);
        }
        if (!in_array($user_id, $allowed, true)) {
            $allowed[] = $user_id;
        }
        return array_values(array_unique(array_map('intval', $allowed)));
    }
}

if (!function_exists('my_works_user_in_assign_scope')) {
    function my_works_user_in_assign_scope($target_user_id, $can_view_all, $user_id, $role_id)
    {
        $target_user_id = (int) $target_user_id;
        if ($target_user_id <= 0) {
            return false;
        }
        if ($can_view_all) {
            return true;
        }
        $user_id = (int) $user_id;
        if ($target_user_id === $user_id) {
            return true;
        }
        $allowed = my_works_assignable_user_ids($can_view_all, $user_id, $role_id);
        return is_array($allowed) && in_array($target_user_id, $allowed, true);
    }
}

if (!function_exists('my_works_user_options')) {
    function my_works_user_options($db)
    {
        if (!$db->table_exists('users')) {
            return array();
        }
        $db->select('id, name, email');
        $db->from('users');
        if (schema_table_has_column($db, 'users', 'status')) {
            $db->where('status', 'active');
        }
        $db->order_by('name', 'ASC');
        return $db->get()->result();
    }
}

if (!function_exists('my_works_assignable_users')) {
    function my_works_assignable_users($db, $can_view_all, $user_id, $role_id)
    {
        // Always return all active users so every user can be assigned work
        // regardless of the current user's role/hierarchy scope.
        return my_works_user_options($db);
    }
}

if (!function_exists('my_works_filter_users_for_dropdown')) {
    function my_works_filter_users_for_dropdown($db, $can_view_all, $user_id, $role_id)
    {
        return $can_view_all
            ? my_works_user_options($db)
            : my_works_assignable_users($db, $can_view_all, $user_id, $role_id);
    }
}

if (!function_exists('my_works_can_edit_full')) {
    function my_works_can_edit_full($item, $can_view_all, $user_id)
    {
        if (!function_exists('has_module_access') || !(has_module_access('my_works_edit') || has_module_access('my_works'))) {
            return false;
        }
        return my_works_user_can_access($item, $can_view_all, $user_id);
    }
}

if (!function_exists('my_works_can_update_status')) {
    function my_works_can_update_status($item, $can_view_all, $user_id)
    {
        // Tasks on Overview are display-only (status lives in tasks module).
        if (is_object($item) && !empty($item->item_source) && $item->item_source === 'tasks') {
            return false;
        }
        if (my_works_can_edit_full($item, $can_view_all, $user_id)) {
            return true;
        }
        $user_id = (int) $user_id;
        return $user_id > 0 && (
            (int) $item->created_for === $user_id
            || (function_exists('multi_assignees_includes_user')
                && multi_assignees_includes_user('my_works_assignees', 'work_id', (int) $item->id, $user_id))
        );
    }
}

if (!function_exists('my_works_can_delete')) {
    function my_works_can_delete($item, $can_view_all, $user_id)
    {
        if (!function_exists('has_module_access') || !(has_module_access('my_works_delete') || has_module_access('my_works'))) {
            return false;
        }
        if ($can_view_all) {
            return true;
        }
        return (int) $item->created_by === (int) $user_id;
    }
}
