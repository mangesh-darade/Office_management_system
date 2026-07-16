<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('todays_plan_schema_ensure')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function todays_plan_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('todays_plan_items')) {
            $db->query("CREATE TABLE `todays_plan_items` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(11) unsigned NOT NULL,
                `plan_date` date NOT NULL,
                `plan_time` time NOT NULL,
                `title` varchar(255) NOT NULL,
                `details` text DEFAULT NULL,
                `link_type` varchar(30) DEFAULT NULL,
                `link_id` int(11) unsigned DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'pending',
                `reminder_id` int(11) unsigned DEFAULT NULL,
                `google_sync_status` varchar(20) DEFAULT NULL,
                `repeat_type` varchar(20) NOT NULL DEFAULT 'once',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_tpi_user_date` (`user_id`, `plan_date`),
                KEY `idx_tpi_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!schema_table_has_column($db, 'todays_plan_items', 'repeat_type')) {
                $db->query("ALTER TABLE `todays_plan_items` ADD `repeat_type` varchar(20) NOT NULL DEFAULT 'once' AFTER `google_sync_status`");
            }
        }
    }
}
