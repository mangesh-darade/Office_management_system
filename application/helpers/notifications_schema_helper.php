<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifications module schema (push subscriptions table).
 */

if (!function_exists('notifications_schema_ensure_push_subscriptions')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function notifications_schema_ensure_push_subscriptions($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if ($db->table_exists('push_subscriptions')) {
            return;
        }

        $db->query("CREATE TABLE `push_subscriptions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `endpoint` text NOT NULL,
            `p256dh_key` varchar(255) NOT NULL,
            `auth_token` varchar(255) NOT NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_user_endpoint` (`user_id`, `endpoint`(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}
