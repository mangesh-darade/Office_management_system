<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('spl_schema_ensure')) {
    function spl_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        $CI->load->helper('rewards_schema');
        rewards_schema_ensure($db);

        if (!$db->table_exists('spl_groups')) {
            $db->query("CREATE TABLE `spl_groups` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(150) NOT NULL,
                `code` varchar(60) NOT NULL,
                `description` text,
                `poster_path` varchar(500) DEFAULT NULL,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_spl_group_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('spl_group_rules')) {
            $db->query("CREATE TABLE `spl_group_rules` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `group_id` int(11) NOT NULL,
                `rule_id` int(11) NOT NULL,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_spl_group_rule` (`group_id`,`rule_id`),
                KEY `idx_spl_gr_group` (`group_id`),
                KEY `idx_spl_gr_rule` (`rule_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('spl_group_members')) {
            $db->query("CREATE TABLE `spl_group_members` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `group_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_spl_group_member` (`group_id`,`user_id`),
                KEY `idx_spl_gm_group` (`group_id`),
                KEY `idx_spl_gm_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        spl_schema_seed_defaults($db);
        spl_schema_ensure_board_groups($db);

        if (function_exists('seed_spl_default_permissions_if_needed')) {
            seed_spl_default_permissions_if_needed();
        }
    }
}

if (!function_exists('spl_schema_seed_defaults')) {
    function spl_schema_seed_defaults($db)
    {
        if (!$db->table_exists('spl_groups')) {
            return;
        }
        $count = (int) $db->count_all('spl_groups');
        if ($count > 0) {
            return;
        }
        $groups = array(
            array('name' => 'Group 1', 'code' => 'group_1', 'description' => '', 'sort_order' => 1),
            array('name' => 'Group 2', 'code' => 'group_2', 'description' => '', 'sort_order' => 2),
            array('name' => 'Group 3', 'code' => 'group_3', 'description' => '', 'sort_order' => 3),
            array('name' => 'Group 4', 'code' => 'group_4', 'description' => '', 'sort_order' => 4),
            array('name' => 'Group 5', 'code' => 'group_5', 'description' => '', 'sort_order' => 5),
        );
        foreach ($groups as $g) {
            $db->insert('spl_groups', $g);
        }
    }
}

if (!function_exists('spl_schema_find_user_id_by_name')) {
    function spl_schema_find_user_id_by_name($db, $search_name)
    {
        $search_name = trim((string) $search_name);
        if ($search_name === '' || !$db->table_exists('users')) {
            return 0;
        }

        $CI =& get_instance();
        $CI->load->helper('schema_columns');

        if ($db->table_exists('employees') && schema_table_has_column($db, 'employees', 'name') && schema_table_has_column($db, 'employees', 'user_id')) {
            $row = $db->select('e.user_id')
                ->from('employees e')
                ->where('e.name', $search_name)
                ->limit(1)
                ->get()
                ->row();
            if ($row && (int) $row->user_id > 0) {
                return (int) $row->user_id;
            }
            $row = $db->select('e.user_id')
                ->from('employees e')
                ->like('e.name', $search_name, 'both')
                ->limit(1)
                ->get()
                ->row();
            if ($row && (int) $row->user_id > 0) {
                return (int) $row->user_id;
            }
        }

        if (schema_table_has_column($db, 'users', 'name')) {
            $row = $db->select('id')->from('users')->where('name', $search_name)->limit(1)->get()->row();
            if ($row) {
                return (int) $row->id;
            }
            $row = $db->select('id')->from('users')->like('name', $search_name, 'both')->limit(1)->get()->row();
            if ($row) {
                return (int) $row->id;
            }
        }

        if (schema_table_has_column($db, 'users', 'full_name')) {
            $row = $db->select('id')->from('users')->where('full_name', $search_name)->limit(1)->get()->row();
            if ($row) {
                return (int) $row->id;
            }
            $row = $db->select('id')->from('users')->like('full_name', $search_name, 'both')->limit(1)->get()->row();
            if ($row) {
                return (int) $row->id;
            }
        }

        return 0;
    }
}

if (!function_exists('spl_schema_board_member_map')) {
    function spl_schema_board_member_map()
    {
        return array(
            'group_1' => array('Mangesh', 'Pranali', 'Ashwini', 'Aryan', 'Nitin'),
            'group_2' => array('Preetiesh', 'Akshata', 'Vismay', 'Chaitainya', 'Dhanashri'),
            'group_3' => array('Anjali', 'Kajal', 'Sumit', 'Mahesh'),
            'group_4' => array('Amol', 'Sayli', 'Siddhant', 'Ankita', 'Ayush'),
            'group_5' => array('Tanvi', 'Vipin', 'Bhavana', 'Utkarsha', 'Amey'),
        );
    }
}

if (!function_exists('spl_schema_seed_group_members')) {
    function spl_schema_seed_group_members($db, $group_id, array $names)
    {
        if (!$db->table_exists('spl_group_members') || $group_id <= 0) {
            return;
        }
        $existing = (int) $db->where('group_id', (int) $group_id)->count_all_results('spl_group_members');
        if ($existing > 0) {
            return;
        }
        $order = 0;
        $seen = array();
        foreach ($names as $name) {
            $uid = spl_schema_find_user_id_by_name($db, $name);
            if ($uid <= 0 || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $order++;
            $db->insert('spl_group_members', array(
                'group_id' => (int) $group_id,
                'user_id' => $uid,
                'sort_order' => $order,
            ));
        }
    }
}

if (!function_exists('spl_schema_ensure_board_groups')) {
    function spl_schema_ensure_board_groups($db)
    {
        if (!$db->table_exists('spl_groups')) {
            return;
        }

        $board = array(
            array('name' => 'Group 1', 'code' => 'group_1', 'sort_order' => 1),
            array('name' => 'Group 2', 'code' => 'group_2', 'sort_order' => 2),
            array('name' => 'Group 3', 'code' => 'group_3', 'sort_order' => 3),
            array('name' => 'Group 4', 'code' => 'group_4', 'sort_order' => 4),
            array('name' => 'Group 5', 'code' => 'group_5', 'sort_order' => 5),
        );

        $member_map = spl_schema_board_member_map();
        foreach ($board as $slot) {
            $row = $db->where('code', $slot['code'])->get('spl_groups')->row();
            if (!$row) {
                $db->insert('spl_groups', array(
                    'name' => $slot['name'],
                    'code' => $slot['code'],
                    'description' => '',
                    'sort_order' => (int) $slot['sort_order'],
                    'is_active' => 1,
                ));
                $group_id = (int) $db->insert_id();
            } else {
                $group_id = (int) $row->id;
            }
            if ($group_id > 0 && isset($member_map[$slot['code']])) {
                spl_schema_seed_group_members($db, $group_id, $member_map[$slot['code']]);
            }
        }

        if (function_exists('spl_schema_sync_all_group_rules')) {
            spl_schema_sync_all_group_rules($db);
        }
    }
}

if (!function_exists('spl_schema_sync_all_group_rules')) {
    function spl_schema_sync_all_group_rules($db)
    {
        $CI =& get_instance();
        if (!isset($CI->spl)) {
            $CI->load->model('Spl_model', 'spl');
        }
        if (isset($CI->spl) && method_exists($CI->spl, 'sync_all_rules_to_all_groups')) {
            $CI->spl->sync_all_rules_to_all_groups();
        }
    }
}
