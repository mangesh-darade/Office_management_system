<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('subscription_builder_included_order_schema_ensure')) {
    function subscription_builder_included_order_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if ($db->table_exists('subscription_builder_included_order')) {
            return;
        }

        $sql = "CREATE TABLE `subscription_builder_included_order` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `plan` varchar(50) NOT NULL,
            `industry` varchar(100) NOT NULL,
            `module_order` text DEFAULT NULL,
            `feature_order` text DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_sb_included_order_plan_industry` (`plan`, `industry`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $db->query($sql);
    }
}
