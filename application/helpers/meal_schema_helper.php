<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Office meals — calendar, orders, settings (no Meal Mantri).
 */
if (!function_exists('meal_schema_ensure')) {
    function meal_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        $CI->load->helper('schema_columns');

        if (!$db->table_exists('meal_settings')) {
            $db->query("CREATE TABLE `meal_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `breakfast_cutoff` time NOT NULL DEFAULT '08:30:00',
                `lunch_cutoff` time NOT NULL DEFAULT '11:00:00',
                `max_lunch_plates` tinyint(1) NOT NULL DEFAULT 2,
                `max_breakfast_plates` tinyint(1) NOT NULL DEFAULT 3,
                `auto_publish_announcements` tinyint(1) NOT NULL DEFAULT 1,
                `skip_weekends_on_apply` tinyint(1) NOT NULL DEFAULT 0,
                `email_change_requests` tinyint(1) NOT NULL DEFAULT 1,
                `show_dashboard_announcement` tinyint(1) NOT NULL DEFAULT 1,
                `provider_contact` varchar(50) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->insert('meal_settings', array(
                'breakfast_cutoff' => '08:30:00',
                'lunch_cutoff' => '11:00:00',
                'max_lunch_plates' => 2,
                'max_breakfast_plates' => 3,
                'auto_publish_announcements' => 1,
                'skip_weekends_on_apply' => 0,
                'email_change_requests' => 1,
                'show_dashboard_announcement' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        } else {
            if (!schema_table_has_column($db, 'meal_settings', 'max_lunch_plates')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `max_lunch_plates` tinyint(1) NOT NULL DEFAULT 2 AFTER `lunch_cutoff`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'auto_publish_announcements')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `auto_publish_announcements` tinyint(1) NOT NULL DEFAULT 1 AFTER `max_lunch_plates`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'skip_weekends_on_apply')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `skip_weekends_on_apply` tinyint(1) NOT NULL DEFAULT 0 AFTER `auto_publish_announcements`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'max_breakfast_plates')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `max_breakfast_plates` tinyint(1) NOT NULL DEFAULT 3 AFTER `max_lunch_plates`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'email_change_requests')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `email_change_requests` tinyint(1) NOT NULL DEFAULT 1 AFTER `skip_weekends_on_apply`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'show_dashboard_announcement')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `show_dashboard_announcement` tinyint(1) NOT NULL DEFAULT 1 AFTER `email_change_requests`");
            }
            if (!schema_table_has_column($db, 'meal_settings', 'provider_contact')) {
                $db->query("ALTER TABLE `meal_settings` ADD COLUMN `provider_contact` varchar(50) DEFAULT NULL AFTER `show_dashboard_announcement`");
            }
        }

        if (!$db->table_exists('meal_week_menu')) {
            $db->query("CREATE TABLE `meal_week_menu` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `day_of_week` tinyint(1) NOT NULL COMMENT '1=Mon .. 7=Sun',
                `has_breakfast` tinyint(1) NOT NULL DEFAULT 0,
                `has_lunch` tinyint(1) NOT NULL DEFAULT 0,
                `breakfast_menu` varchar(255) DEFAULT NULL,
                `lunch_menu` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_meal_week_dow` (`day_of_week`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            meal_schema_seed_week_menu($db);
        } elseif ((int) $db->count_all('meal_week_menu') === 0) {
            meal_schema_seed_week_menu($db);
        }

        if (!$db->table_exists('meal_calendar')) {
            $db->query("CREATE TABLE `meal_calendar` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `meal_date` date NOT NULL,
                `has_breakfast` tinyint(1) NOT NULL DEFAULT 0,
                `has_lunch` tinyint(1) NOT NULL DEFAULT 0,
                `breakfast_note` varchar(255) DEFAULT NULL,
                `lunch_note` varchar(255) DEFAULT NULL,
                `announcement_id` int(11) DEFAULT NULL,
                `updated_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_meal_calendar_date` (`meal_date`),
                KEY `idx_meal_calendar_range` (`meal_date`,`has_breakfast`,`has_lunch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('meal_orders')) {
            $db->query("CREATE TABLE `meal_orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `meal_date` date NOT NULL,
                `want_breakfast` tinyint(1) NOT NULL DEFAULT 0,
                `breakfast_plates` tinyint(1) NOT NULL DEFAULT 0,
                `lunch_plates` tinyint(1) NOT NULL DEFAULT 0,
                `lunch_tiffin` varchar(10) NOT NULL DEFAULT '',
                `additional_breakfast_plates` tinyint(1) NOT NULL DEFAULT 0,
                `additional_lunch_tiffin` varchar(10) NOT NULL DEFAULT '',
                `breakfast_locked_at` datetime DEFAULT NULL,
                `lunch_locked_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_meal_order_user_date` (`user_id`,`meal_date`),
                KEY `idx_meal_orders_date` (`meal_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($db->table_exists('meal_orders') && !schema_table_has_column($db, 'meal_orders', 'breakfast_plates')) {
            $db->query("ALTER TABLE `meal_orders` ADD COLUMN `breakfast_plates` tinyint(1) NOT NULL DEFAULT 0 AFTER `want_breakfast`");
            $db->query("UPDATE `meal_orders` SET `breakfast_plates` = `want_breakfast` WHERE `want_breakfast` = 1 AND `breakfast_plates` = 0");
        }

        if ($db->table_exists('meal_orders') && !schema_table_has_column($db, 'meal_orders', 'lunch_tiffin')) {
            $db->query("ALTER TABLE `meal_orders` ADD COLUMN `lunch_tiffin` varchar(10) NOT NULL DEFAULT '' AFTER `lunch_plates`");
            $db->query("UPDATE `meal_orders` SET `lunch_tiffin` = 'half' WHERE `lunch_plates` = 1 AND (`lunch_tiffin` = '' OR `lunch_tiffin` IS NULL)");
            $db->query("UPDATE `meal_orders` SET `lunch_tiffin` = 'full' WHERE `lunch_plates` >= 2 AND (`lunch_tiffin` = '' OR `lunch_tiffin` IS NULL)");
        }

        if ($db->table_exists('meal_orders') && !schema_table_has_column($db, 'meal_orders', 'additional_breakfast_plates')) {
            $db->query("ALTER TABLE `meal_orders` ADD COLUMN `additional_breakfast_plates` tinyint(1) NOT NULL DEFAULT 0 AFTER `lunch_tiffin`");
        }
        if ($db->table_exists('meal_orders') && !schema_table_has_column($db, 'meal_orders', 'additional_lunch_tiffin')) {
            $db->query("ALTER TABLE `meal_orders` ADD COLUMN `additional_lunch_tiffin` varchar(10) NOT NULL DEFAULT '' AFTER `additional_breakfast_plates`");
        }

        if (!$db->table_exists('meal_order_log')) {
            $db->query("CREATE TABLE `meal_order_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `meal_date` date NOT NULL,
                `field_name` varchar(40) NOT NULL,
                `old_value` varchar(40) DEFAULT NULL,
                `new_value` varchar(40) DEFAULT NULL,
                `changed_by` int(11) NOT NULL,
                `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_meal_log_date` (`meal_date`,`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('meal_change_requests')) {
            $db->query("CREATE TABLE `meal_change_requests` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `meal_date` date NOT NULL,
                `meal_type` varchar(10) NOT NULL COMMENT 'breakfast|lunch',
                `current_value` varchar(20) NOT NULL DEFAULT '',
                `requested_value` varchar(20) NOT NULL DEFAULT '',
                `employee_note` varchar(255) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'pending',
                `reviewed_by` int(11) DEFAULT NULL,
                `review_note` varchar(255) DEFAULT NULL,
                `reviewed_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_meal_change_date_status` (`meal_date`,`status`),
                KEY `idx_meal_change_user` (`user_id`,`meal_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}

if (!function_exists('meal_schema_seed_week_menu')) {
    function meal_schema_seed_week_menu($db)
    {
        $defaults = array(
            1 => array('has_breakfast' => 1, 'has_lunch' => 1, 'breakfast_menu' => 'Poha, Tea', 'lunch_menu' => 'Dal, Rice, Roti'),
            2 => array('has_breakfast' => 1, 'has_lunch' => 1, 'breakfast_menu' => 'Idli, Chutney', 'lunch_menu' => 'Rajma, Rice'),
            3 => array('has_breakfast' => 1, 'has_lunch' => 1, 'breakfast_menu' => 'Upma, Tea', 'lunch_menu' => 'Paneer, Roti'),
            4 => array('has_breakfast' => 1, 'has_lunch' => 1, 'breakfast_menu' => 'Paratha, Curd', 'lunch_menu' => 'Mix veg, Rice'),
            5 => array('has_breakfast' => 1, 'has_lunch' => 1, 'breakfast_menu' => 'Sandwich, Tea', 'lunch_menu' => 'Chole, Rice'),
            6 => array('has_breakfast' => 0, 'has_lunch' => 0, 'breakfast_menu' => '', 'lunch_menu' => ''),
            7 => array('has_breakfast' => 0, 'has_lunch' => 0, 'breakfast_menu' => '', 'lunch_menu' => ''),
        );
        foreach ($defaults as $dow => $row) {
            $db->insert('meal_week_menu', array(
                'day_of_week' => (int) $dow,
                'has_breakfast' => (int) $row['has_breakfast'],
                'has_lunch' => (int) $row['has_lunch'],
                'breakfast_menu' => $row['breakfast_menu'] !== '' ? $row['breakfast_menu'] : null,
                'lunch_menu' => $row['lunch_menu'] !== '' ? $row['lunch_menu'] : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
    }
}
