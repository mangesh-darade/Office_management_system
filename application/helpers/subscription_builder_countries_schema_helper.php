<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('subscription_builder_countries_schema_ensure')) {
    function subscription_builder_countries_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        $CI->load->helper('schema_columns');

        if (!$db->table_exists('subscription_builder_countries')) {
            $sql = "CREATE TABLE `subscription_builder_countries` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `code` varchar(10) NOT NULL DEFAULT '',
                `mobile_code` varchar(10) NOT NULL DEFAULT '',
                `currency_code` varchar(10) NOT NULL DEFAULT '',
                `currency_symbol` varchar(10) NOT NULL DEFAULT '',
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_sb_country_name` (`name`),
                UNIQUE KEY `uq_sb_country_code` (`code`),
                KEY `idx_sb_country_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
            subscription_builder_countries_seed_defaults($db);
            return;
        }

        $alter_map = array(
            'code' => "ALTER TABLE `subscription_builder_countries` ADD `code` varchar(10) NOT NULL DEFAULT '' AFTER `name`",
            'mobile_code' => "ALTER TABLE `subscription_builder_countries` ADD `mobile_code` varchar(10) NOT NULL DEFAULT '' AFTER `code`",
            'currency_code' => "ALTER TABLE `subscription_builder_countries` ADD `currency_code` varchar(10) NOT NULL DEFAULT '' AFTER `mobile_code`",
            'currency_symbol' => "ALTER TABLE `subscription_builder_countries` ADD `currency_symbol` varchar(10) NOT NULL DEFAULT '' AFTER `currency_code`",
            'sort_order' => "ALTER TABLE `subscription_builder_countries` ADD `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `currency_symbol`",
            'is_active' => "ALTER TABLE `subscription_builder_countries` ADD `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `sort_order`",
            'created_at' => "ALTER TABLE `subscription_builder_countries` ADD `created_at` datetime DEFAULT NULL AFTER `is_active`",
            'updated_at' => "ALTER TABLE `subscription_builder_countries` ADD `updated_at` datetime DEFAULT NULL AFTER `created_at`",
        );

        foreach ($alter_map as $column => $sql) {
            if (!$db->field_exists($column, 'subscription_builder_countries')) {
                $db->query($sql);
            }
        }

        subscription_builder_countries_seed_defaults($db);
    }
}

if (!function_exists('subscription_builder_countries_seed_defaults')) {
    function subscription_builder_countries_seed_defaults($db)
    {
        if (!$db->table_exists('subscription_builder_countries')) {
            return;
        }

        $count = (int) $db->count_all('subscription_builder_countries');
        if ($count > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array(
            array('India', 'IN', '91', 'INR', '₹', 1),
            array('UAE', 'AE', '971', 'AED', 'AED', 2),
            array('Saudi Arabia', 'SA', '966', 'SAR', 'SAR', 3),
            array('Qatar', 'QA', '974', 'QAR', 'QAR', 4),
            array('Oman', 'OM', '968', 'OMR', 'OMR', 5),
            array('Kuwait', 'KW', '965', 'KWD', 'KWD', 6),
            array('Bahrain', 'BH', '973', 'BHD', 'BHD', 7),
            array('United Kingdom', 'GB', '44', 'GBP', '£', 8),
            array('United States', 'US', '1', 'USD', '$', 9),
        );

        foreach ($rows as $row) {
            $db->insert('subscription_builder_countries', array(
                'name' => $row[0],
                'code' => $row[1],
                'mobile_code' => $row[2],
                'currency_code' => $row[3],
                'currency_symbol' => $row[4],
                'sort_order' => $row[5],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }
    }
}
