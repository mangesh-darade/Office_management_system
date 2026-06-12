<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * My Works module tables (shared by controller and schema registry).
 */

if (!function_exists('my_works_schema_ensure')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function my_works_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('my_works')) {
            $db->query("CREATE TABLE `my_works` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `details` text DEFAULT NULL,
                `tag` varchar(255) DEFAULT NULL,
                `url` varchar(500) DEFAULT NULL,
                `attachment_original` varchar(255) DEFAULT NULL,
                `attachment_stored` varchar(255) DEFAULT NULL,
                `created_by` int(11) unsigned NOT NULL,
                `created_for` int(11) unsigned NOT NULL,
                `status` enum('new','in_progress','closed') NOT NULL DEFAULT 'new',
                `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
                `is_important` tinyint(1) NOT NULL DEFAULT 0,
                `due_date` date DEFAULT NULL,
                `task_id` int(11) unsigned DEFAULT NULL,
                `client_id` int(11) unsigned DEFAULT NULL,
                `project_id` int(11) unsigned DEFAULT NULL,
                `work_type` varchar(50) DEFAULT NULL,
                `closing_comment` text DEFAULT NULL,
                `closed_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_my_works_created_by` (`created_by`),
                KEY `idx_my_works_created_for` (`created_for`),
                KEY `idx_my_works_status` (`status`),
                KEY `idx_my_works_due` (`due_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!schema_table_has_column($db, 'my_works', 'due_date')) {
                $db->query('ALTER TABLE `my_works` ADD `due_date` date DEFAULT NULL AFTER `is_important`');
            }
            if (!schema_table_has_column($db, 'my_works', 'task_id')) {
                $db->query('ALTER TABLE `my_works` ADD `task_id` int(11) unsigned DEFAULT NULL AFTER `due_date`');
            }
            if (!schema_table_has_column($db, 'my_works', 'client_id')) {
                $db->query('ALTER TABLE `my_works` ADD `client_id` int(11) unsigned DEFAULT NULL AFTER `task_id`');
            }
            if (!schema_table_has_column($db, 'my_works', 'project_id')) {
                $db->query('ALTER TABLE `my_works` ADD `project_id` int(11) unsigned DEFAULT NULL AFTER `client_id`');
            }
            if (!schema_table_has_column($db, 'my_works', 'work_type')) {
                $db->query('ALTER TABLE `my_works` ADD `work_type` varchar(50) DEFAULT NULL AFTER `project_id`');
            }
            if (!schema_table_has_column($db, 'my_works', 'closing_comment')) {
                $db->query('ALTER TABLE `my_works` ADD `closing_comment` text DEFAULT NULL AFTER `work_type`');
            }
            if (schema_table_has_column($db, 'my_works', 'tag')) {
                $db->query('ALTER TABLE `my_works` MODIFY `tag` varchar(255) DEFAULT NULL');
            }
        }

        if (!$db->table_exists('my_work_activity')) {
            $db->query("CREATE TABLE `my_work_activity` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `action` varchar(50) NOT NULL,
                `detail` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mwa_work` (`work_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('my_work_comments')) {
            $db->query("CREATE TABLE `my_work_comments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `comment` text NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mwc_work` (`work_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('my_work_attachments')) {
            $db->query("CREATE TABLE `my_work_attachments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `work_id` int(11) unsigned NOT NULL,
                `original_name` varchar(255) NOT NULL,
                `stored_name` varchar(255) NOT NULL,
                `file_size` int(11) unsigned NOT NULL DEFAULT 0,
                `sort_order` int(11) unsigned NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mwa_work` (`work_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($db->table_exists('my_work_attachments') && $db->table_exists('my_works')) {
            $legacy = $db->select('id, attachment_original, attachment_stored')
                ->from('my_works')
                ->where('attachment_stored IS NOT NULL', null, false)
                ->where('attachment_stored !=', '')
                ->get()
                ->result();
            foreach ($legacy as $row) {
                $exists = $db->where('work_id', (int) $row->id)
                    ->count_all_results('my_work_attachments');
                if ($exists > 0) {
                    continue;
                }
                $path = FCPATH . 'uploads/my_works/' . $row->attachment_stored;
                $size = is_file($path) ? (int) filesize($path) : 0;
                $db->insert('my_work_attachments', array(
                    'work_id'       => (int) $row->id,
                    'original_name' => $row->attachment_original ? (string) $row->attachment_original : (string) $row->attachment_stored,
                    'stored_name'   => (string) $row->attachment_stored,
                    'file_size'     => $size,
                    'sort_order'    => 0,
                    'created_at'    => date('Y-m-d H:i:s'),
                ));
            }
        }
    }
}
