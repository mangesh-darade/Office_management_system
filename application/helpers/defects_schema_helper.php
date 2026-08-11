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

        if (!function_exists('schema_table_has_column')) {
            $CI =& get_instance();
            $CI->load->helper('schema_columns');
        }

        if (!$db->table_exists('project_defects')) {
            $db->query("CREATE TABLE `project_defects` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `defect_number` varchar(30) NOT NULL,
                `project_id` int(11) DEFAULT NULL,
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
                `verified_by` int(11) DEFAULT NULL,
                `due_date` date DEFAULT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_defect_number` (`defect_number`),
                KEY `idx_pd_project` (`project_id`),
                KEY `idx_pd_status` (`status`),
                KEY `idx_pd_assigned` (`assigned_to`),
                KEY `idx_pd_release` (`release_id`),
                KEY `idx_pd_deleted` (`is_deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($db->table_exists('project_defects')) {
            if (!schema_table_has_column($db, 'project_defects', 'due_date')) {
                $db->query("ALTER TABLE `project_defects` ADD COLUMN `due_date` date DEFAULT NULL AFTER `assigned_to`");
            }
            if (!schema_table_has_column($db, 'project_defects', 'verified_by')) {
                $db->query("ALTER TABLE `project_defects` ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `assigned_to`");
            }
            if (!schema_table_has_column($db, 'project_defects', 'is_deleted')) {
                $db->query("ALTER TABLE `project_defects` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0 AFTER `resolved_at`");
            }
            // Project optional on create/edit (was NOT NULL).
            $col = $db->query("SHOW COLUMNS FROM `project_defects` LIKE 'project_id'")->row_array();
            if (!empty($col) && isset($col['Null']) && strtoupper((string) $col['Null']) === 'NO') {
                $db->query("ALTER TABLE `project_defects` MODIFY COLUMN `project_id` int(11) DEFAULT NULL");
            }
        }

        if (!$db->table_exists('project_defect_comments')) {
            $db->query("CREATE TABLE `project_defect_comments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `defect_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `comment` text NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_pdc_defect` (`defect_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('project_defect_attachments')) {
            $db->query("CREATE TABLE `project_defect_attachments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `defect_id` int(11) NOT NULL,
                `original_name` varchar(255) NOT NULL,
                `stored_name` varchar(255) NOT NULL,
                `file_size` int(11) unsigned NOT NULL DEFAULT 0,
                `uploaded_by` int(11) NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_pda_defect` (`defect_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('project_defect_activity')) {
            $db->query("CREATE TABLE `project_defect_activity` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `defect_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `action` varchar(50) NOT NULL,
                `detail` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_pda2_defect` (`defect_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
