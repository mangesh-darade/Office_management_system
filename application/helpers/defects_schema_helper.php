<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Schema for project defect / bug tracking.
 */

if (!function_exists('defects_schema_ensure')) {
    function defects_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('project_defects')) {
            $db->query("CREATE TABLE `project_defects` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `defect_number` varchar(30) NOT NULL,
                `project_id` int(11) NOT NULL,
                `release_id` int(11) DEFAULT NULL,
                `task_id` int(11) DEFAULT NULL,
                `title` varchar(255) NOT NULL,
                `description` text,
                `steps_to_reproduce` text,
                `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `status` enum('open','in_progress','fixed','verified','closed','rejected') NOT NULL DEFAULT 'open',
                `reported_by` int(11) NOT NULL,
                `assigned_to` int(11) DEFAULT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_defect_number` (`defect_number`),
                KEY `idx_pd_project` (`project_id`),
                KEY `idx_pd_status` (`status`),
                KEY `idx_pd_assigned` (`assigned_to`),
                KEY `idx_pd_release` (`release_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
