<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Subscription Builder catalog table.
 */

if (!function_exists('subscription_builder_schema_ensure')) {
    function subscription_builder_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $CI =& get_instance();
        $CI->load->helper('schema_columns');

        if (!$db->table_exists('subscription_builder')) {
            $sql = "CREATE TABLE `subscription_builder` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `plan` varchar(50) NOT NULL,
                `industry` varchar(100) NOT NULL,
                `country` varchar(100) NOT NULL DEFAULT 'India',
                `module` varchar(150) NOT NULL DEFAULT '',
                `feature` varchar(255) NOT NULL DEFAULT '',
                `details` text DEFAULT NULL,
                `per_item_set_up_charges` decimal(12,2) DEFAULT NULL,
                `item_unit` varchar(100) DEFAULT NULL,
                `common_set_up_fees` decimal(12,2) DEFAULT NULL,
                `per_item_per_month_maintenances` decimal(12,2) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sb_plan` (`plan`),
                KEY `idx_sb_industry` (`industry`),
                KEY `idx_sb_country` (`country`),
                KEY `idx_sb_module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
            return;
        }

        $alter_map = array(
            'plan' => "ALTER TABLE `subscription_builder` ADD `plan` varchar(50) NOT NULL DEFAULT '' AFTER `id`",
            'industry' => "ALTER TABLE `subscription_builder` ADD `industry` varchar(100) NOT NULL DEFAULT '' AFTER `plan`",
            'country' => "ALTER TABLE `subscription_builder` ADD `country` varchar(100) NOT NULL DEFAULT 'India' AFTER `industry`",
            'module' => "ALTER TABLE `subscription_builder` ADD `module` varchar(150) NOT NULL DEFAULT '' AFTER `industry`",
            'feature' => "ALTER TABLE `subscription_builder` ADD `feature` varchar(255) NOT NULL DEFAULT '' AFTER `module`",
            'details' => "ALTER TABLE `subscription_builder` ADD `details` text DEFAULT NULL AFTER `feature`",
            'per_item_set_up_charges' => "ALTER TABLE `subscription_builder` ADD `per_item_set_up_charges` decimal(12,2) DEFAULT NULL AFTER `details`",
            'item_unit' => "ALTER TABLE `subscription_builder` ADD `item_unit` varchar(100) DEFAULT NULL AFTER `per_item_set_up_charges`",
            'common_set_up_fees' => "ALTER TABLE `subscription_builder` ADD `common_set_up_fees` decimal(12,2) DEFAULT NULL AFTER `item_unit`",
            'per_item_per_month_maintenances' => "ALTER TABLE `subscription_builder` ADD `per_item_per_month_maintenances` decimal(12,2) DEFAULT NULL AFTER `common_set_up_fees`",
            'created_at' => "ALTER TABLE `subscription_builder` ADD `created_at` datetime DEFAULT NULL AFTER `per_item_per_month_maintenances`",
            'updated_at' => "ALTER TABLE `subscription_builder` ADD `updated_at` datetime DEFAULT NULL AFTER `created_at`",
        );

        foreach ($alter_map as $column => $sql) {
            if (!schema_table_has_column($db, 'subscription_builder', $column)) {
                $db->query($sql);
            }
        }

        if (schema_table_has_column($db, 'subscription_builder', 'country')) {
            $db->query("UPDATE `subscription_builder` SET `country` = 'India' WHERE `country` IS NULL OR `country` = ''");
        }

        $CI->load->helper('subscription_builder_countries_schema');
        subscription_builder_countries_schema_ensure($db);

        $CI->load->helper('subscription_builder_included_order_schema');
        subscription_builder_included_order_schema_ensure($db);
    }
}
