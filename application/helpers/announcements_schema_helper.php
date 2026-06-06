<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Announcements table and column upgrades
 */

if (!function_exists('announcements_schema_ensure')) {
    function announcements_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (!$db->table_exists('announcements')){
            $sql = "CREATE TABLE `announcements` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `content` text NOT NULL,
                `posted_by` int(11) NOT NULL,
                `target_roles` varchar(100) DEFAULT 'all',
                `priority` varchar(20) DEFAULT 'medium',
                `start_date` date DEFAULT NULL,
                `end_date` date DEFAULT NULL,
                `status` varchar(20) DEFAULT 'draft',
                `publish_at` datetime DEFAULT NULL,
                `expire_at` datetime DEFAULT NULL,
                `is_recurring` tinyint(1) DEFAULT 0,
                `recurrence_pattern` varchar(50) DEFAULT NULL,
                `recurrence_end` date DEFAULT NULL,
                `email_template` text DEFAULT NULL,
                `auto_send` tinyint(1) DEFAULT 0,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_publish_at` (`publish_at`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        
        // Add new columns if they don't exist
        if ($db->table_exists('announcements')) {
            $fields = $db->list_fields('announcements');
            
            if (!in_array('publish_at', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `publish_at` datetime DEFAULT NULL AFTER `end_date`");
            }
            if (!in_array('expire_at', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `expire_at` datetime DEFAULT NULL AFTER `publish_at`");
            }
            if (!in_array('is_recurring', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `is_recurring` tinyint(1) DEFAULT 0 AFTER `expire_at`");
            }
            if (!in_array('recurrence_pattern', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `recurrence_pattern` varchar(50) DEFAULT NULL AFTER `is_recurring`");
            }
            if (!in_array('recurrence_end', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `recurrence_end` date DEFAULT NULL AFTER `recurrence_pattern`");
            }
            if (!in_array('email_template', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `email_template` text DEFAULT NULL AFTER `recurrence_end`");
            }
            if (!in_array('auto_send', $fields)) {
                $db->query("ALTER TABLE `announcements` ADD COLUMN `auto_send` tinyint(1) DEFAULT 0 AFTER `email_template`");
            }
        }
    }
}
