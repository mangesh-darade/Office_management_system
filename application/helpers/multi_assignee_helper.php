<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Multi-user assignees for tasks, requirements, and my_works.
 * Primary assignee stays on parent.assigned_to / created_for (first selected).
 */

if (!function_exists('multi_assignees_ensure_schema')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function multi_assignees_ensure_schema($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('task_assignees')) {
            $db->query("CREATE TABLE `task_assignees` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `task_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_task_assignees_task_user` (`task_id`, `user_id`),
                KEY `idx_task_assignees_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('requirement_assignees')) {
            $db->query("CREATE TABLE `requirement_assignees` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `requirement_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_requirement_assignees_req_user` (`requirement_id`, `user_id`),
                KEY `idx_requirement_assignees_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('my_works_assignees')) {
            $db->query("CREATE TABLE `my_works_assignees` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_my_works_assignees_work_user` (`work_id`, `user_id`),
                KEY `idx_my_works_assignees_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}

if (!function_exists('multi_assignees_normalize_ids')) {
    /**
     * @param mixed $raw
     * @return int[]
     */
    function multi_assignees_normalize_ids($raw)
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return array();
        }
        if (!is_array($raw)) {
            $raw = array($raw);
        }
        $ids = array();
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

if (!function_exists('multi_assignees_primary')) {
    /**
     * @param int[] $user_ids
     * @return int|null
     */
    function multi_assignees_primary($user_ids)
    {
        if (!is_array($user_ids) || empty($user_ids)) {
            return null;
        }
        $first = (int) $user_ids[0];
        return $first > 0 ? $first : null;
    }
}

if (!function_exists('multi_assignees_sync')) {
    /**
     * Replace assignee set for a parent row.
     *
     * @param string $table
     * @param string $fk_col
     * @param int $parent_id
     * @param int[] $user_ids
     * @return void
     */
    function multi_assignees_sync($table, $fk_col, $parent_id, $user_ids)
    {
        $CI =& get_instance();
        $db = $CI->db;
        $parent_id = (int) $parent_id;
        $allowed = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        if ($parent_id < 1 || !in_array($table, $allowed, true) || !in_array($fk_col, $fk_allowed, true)) {
            return;
        }
        if (!$db->table_exists($table)) {
            return;
        }

        $user_ids = multi_assignees_normalize_ids($user_ids);
        $db->where($fk_col, $parent_id)->delete($table);

        if (empty($user_ids)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $batch = array();
        foreach ($user_ids as $uid) {
            $batch[] = array(
                $fk_col => $parent_id,
                'user_id' => (int) $uid,
                'created_at' => $now,
            );
        }
        $db->insert_batch($table, $batch);
    }
}

if (!function_exists('multi_assignees_list')) {
    /**
     * @param string $table
     * @param string $fk_col
     * @param int $parent_id
     * @return int[]
     */
    function multi_assignees_list($table, $fk_col, $parent_id)
    {
        $CI =& get_instance();
        $db = $CI->db;
        $parent_id = (int) $parent_id;
        $allowed = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        if ($parent_id < 1 || !in_array($table, $allowed, true) || !in_array($fk_col, $fk_allowed, true)) {
            return array();
        }
        if (!$db->table_exists($table)) {
            return array();
        }
        $rows = $db->select('user_id')
            ->from($table)
            ->where($fk_col, $parent_id)
            ->order_by('id', 'ASC')
            ->get()
            ->result();
        $ids = array();
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if ($uid > 0 && !in_array($uid, $ids, true)) {
                $ids[] = $uid;
            }
        }
        return $ids;
    }
}

if (!function_exists('multi_assignees_includes_user')) {
    /**
     * @param string $table
     * @param string $fk_col
     * @param int $parent_id
     * @param int $user_id
     * @return bool
     */
    function multi_assignees_includes_user($table, $fk_col, $parent_id, $user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }
        return in_array($user_id, multi_assignees_list($table, $fk_col, $parent_id), true);
    }
}

if (!function_exists('multi_assignees_resolve_for_edit')) {
    /**
     * Junction IDs plus legacy primary so edit forms stay filled.
     *
     * @param string $table
     * @param string $fk_col
     * @param int $parent_id
     * @param int|null $primary_id
     * @return int[]
     */
    function multi_assignees_resolve_for_edit($table, $fk_col, $parent_id, $primary_id)
    {
        $ids = multi_assignees_list($table, $fk_col, $parent_id);
        $primary_id = (int) $primary_id;
        if (empty($ids) && $primary_id > 0) {
            return array($primary_id);
        }
        if ($primary_id > 0 && !in_array($primary_id, $ids, true)) {
            array_unshift($ids, $primary_id);
        }
        return $ids;
    }
}

if (!function_exists('multi_assignees_ids_map')) {
    /**
     * Map parent_id => list of assignee user_ids (ordered).
     *
     * @param string $table
     * @param string $fk_col
     * @param int[] $parent_ids
     * @return array<int, int[]>
     */
    function multi_assignees_ids_map($table, $fk_col, $parent_ids)
    {
        $CI =& get_instance();
        $db = $CI->db;
        $allowed = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        if (!in_array($table, $allowed, true) || !in_array($fk_col, $fk_allowed, true)) {
            return array();
        }
        $parent_ids = array_values(array_unique(array_filter(array_map('intval', (array) $parent_ids))));
        if (empty($parent_ids) || !$db->table_exists($table)) {
            return array();
        }

        $rows = $db->select($fk_col . ' AS parent_id, user_id')
            ->from($table)
            ->where_in($fk_col, $parent_ids)
            ->order_by('id', 'ASC')
            ->get()
            ->result();

        $map = array();
        foreach ($rows as $row) {
            $pid = (int) $row->parent_id;
            $uid = (int) $row->user_id;
            if ($pid < 1 || $uid < 1) {
                continue;
            }
            if (!isset($map[$pid])) {
                $map[$pid] = array();
            }
            if (!in_array($uid, $map[$pid], true)) {
                $map[$pid][] = $uid;
            }
        }
        return $map;
    }
}

if (!function_exists('multi_assignees_names_map')) {
    /**
     * Map parent_id => list of display name strings (ordered).
     *
     * @param string $table
     * @param string $fk_col
     * @param int[] $parent_ids
     * @return array<int, string[]>
     */
    function multi_assignees_names_map($table, $fk_col, $parent_ids)
    {
        $CI =& get_instance();
        $db = $CI->db;
        $allowed = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        if (!in_array($table, $allowed, true) || !in_array($fk_col, $fk_allowed, true)) {
            return array();
        }
        $parent_ids = array_values(array_unique(array_filter(array_map('intval', (array) $parent_ids))));
        if (empty($parent_ids) || !$db->table_exists($table) || !$db->table_exists('users')) {
            return array();
        }

        $select = array('ma.' . $fk_col . ' AS parent_id', 'ma.user_id', 'u.email');
        if (schema_table_has_column($db, 'users', 'name')) {
            $select[] = 'u.name';
        }
        if (schema_table_has_column($db, 'users', 'full_name')) {
            $select[] = 'u.full_name';
        }
        $has_emp = $db->table_exists('employees') && schema_table_has_column($db, 'employees', 'user_id');
        if ($has_emp && schema_table_has_column($db, 'employees', 'name')) {
            $select[] = 'e.name AS emp_name';
        }

        $db->select(implode(',', $select), false);
        $db->from($table . ' ma');
        $db->join('users u', 'u.id = ma.user_id', 'left');
        if ($has_emp) {
            $db->join('employees e', 'e.user_id = ma.user_id', 'left');
        }
        $db->where_in('ma.' . $fk_col, $parent_ids);
        $db->order_by('ma.id', 'ASC');
        $rows = $db->get()->result();

        $map = array();
        foreach ($rows as $row) {
            $pid = (int) $row->parent_id;
            if (!isset($map[$pid])) {
                $map[$pid] = array();
            }
            $label = '';
            if (isset($row->emp_name) && trim((string) $row->emp_name) !== '') {
                $label = trim((string) $row->emp_name);
            } elseif (isset($row->full_name) && trim((string) $row->full_name) !== '') {
                $label = trim((string) $row->full_name);
            } elseif (isset($row->name) && trim((string) $row->name) !== '') {
                $label = trim((string) $row->name);
            } elseif (isset($row->email)) {
                $label = (string) $row->email;
            }
            if ($label === '') {
                $label = 'User #' . (int) $row->user_id;
            }
            $map[$pid][] = $label;
        }
        return $map;
    }
}

if (!function_exists('multi_assignees_format_label')) {
    /**
     * Primary name + optional "+N" for extras.
     *
     * @param string $primary_label
     * @param string[]|null $extra_names
     * @return string
     */
    function multi_assignees_format_label($primary_label, $extra_names = null)
    {
        $primary_label = trim((string) $primary_label);
        $names = is_array($extra_names) ? $extra_names : array();
        if (empty($names)) {
            return $primary_label;
        }
        if ($primary_label === '') {
            $primary_label = $names[0];
            $extra = count($names) - 1;
        } else {
            $extra = 0;
            foreach ($names as $n) {
                if (strcasecmp(trim((string) $n), $primary_label) !== 0) {
                    $extra++;
                }
            }
            if ($extra === 0 && count($names) > 1) {
                $extra = count($names) - 1;
            }
        }
        if ($extra > 0) {
            return $primary_label . ' +' . $extra;
        }
        return $primary_label;
    }
}

if (!function_exists('multi_assignees_apply_hierarchy_filter')) {
    /**
     * Hierarchy visibility: primary column OR junction user_id in allowed set.
     *
     * @param CI_DB_query_builder $db
     * @param string $primary_col e.g. t.assigned_to
     * @param string $junction_table
     * @param string $fk_col
     * @param string $parent_id_sql e.g. t.id
     * @param int|null $user_id
     * @param int|null $role_id
     * @return CI_DB_query_builder
     */
    function multi_assignees_apply_hierarchy_filter($db, $primary_col, $junction_table, $fk_col, $parent_id_sql, $user_id = null, $role_id = null)
    {
        $CI =& get_instance();
        $user_id = $user_id === null ? (int) $CI->session->userdata('user_id') : (int) $user_id;
        $role_id = $role_id === null ? (int) $CI->session->userdata('role_id') : (int) $role_id;

        if (function_exists('hierarchy_filter_sees_all_records') && hierarchy_filter_sees_all_records($role_id)) {
            return $db;
        }

        $allowed_ids = get_accessible_hierarchy_user_ids($user_id, $role_id);
        if (empty($allowed_ids)) {
            $db->where('1 = 0', null, false);
            return $db;
        }

        $allowed_ids = array_map('intval', $allowed_ids);
        $ids_sql = implode(',', $allowed_ids);

        $allowed_tables = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        $use_junction = in_array($junction_table, $allowed_tables, true)
            && in_array($fk_col, $fk_allowed, true)
            && $db->table_exists($junction_table);

        $db->group_start();
        $db->where_in($primary_col, $allowed_ids);
        if ($use_junction) {
            $db->or_where(
                'EXISTS (SELECT 1 FROM `' . $junction_table . '` _ma WHERE _ma.`' . $fk_col . '` = ' . $parent_id_sql . ' AND _ma.`user_id` IN (' . $ids_sql . '))',
                null,
                false
            );
        }
        $db->group_end();
        return $db;
    }
}

if (!function_exists('multi_assignees_apply_user_match')) {
    /**
     * Filter where primary OR junction matches a single user id.
     *
     * @param CI_DB_query_builder $db
     * @param string $primary_col
     * @param string $junction_table
     * @param string $fk_col
     * @param string $parent_id_sql
     * @param int $match_user_id
     * @return CI_DB_query_builder
     */
    function multi_assignees_apply_user_match($db, $primary_col, $junction_table, $fk_col, $parent_id_sql, $match_user_id)
    {
        $match_user_id = (int) $match_user_id;
        if ($match_user_id < 1) {
            return $db;
        }
        $allowed_tables = array('task_assignees', 'requirement_assignees', 'my_works_assignees');
        $fk_allowed = array('task_id', 'requirement_id', 'work_id');
        $use_junction = in_array($junction_table, $allowed_tables, true)
            && in_array($fk_col, $fk_allowed, true)
            && $db->table_exists($junction_table);

        $db->group_start();
        $db->where($primary_col, $match_user_id);
        if ($use_junction) {
            $db->or_where(
                'EXISTS (SELECT 1 FROM `' . $junction_table . '` _ma WHERE _ma.`' . $fk_col . '` = ' . $parent_id_sql . ' AND _ma.`user_id` = ' . $match_user_id . ')',
                null,
                false
            );
        }
        $db->group_end();
        return $db;
    }
}
