<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Requirements module tables
 */

if (!function_exists('requirements_schema_ensure')) {
    function requirements_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (!$db->table_exists('requirements')){
            $sql = "CREATE TABLE `requirements` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `req_number` varchar(50) DEFAULT NULL,
                `client_id` int(11) NOT NULL,
                `project_id` int(11) DEFAULT NULL,
                `title` varchar(500) NOT NULL,
                `description` text,
                `requirement_type` varchar(50) DEFAULT 'new_feature',
                `priority` varchar(20) DEFAULT 'medium',
                `status` varchar(50) DEFAULT 'received',
                `budget_estimate` decimal(15,2) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'INR',
                `expected_delivery_date` date DEFAULT NULL,
                `received_date` date DEFAULT NULL,
                `owner_id` int(11) DEFAULT NULL,
                `assigned_to` int(11) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_req_number` (`req_number`),
                KEY `idx_client` (`client_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        // Add missing columns when table already exists
        if ($db->table_exists('requirements')){
            $fields = $db->list_fields('requirements');
            if (!in_array('owner_id', $fields, true)) { $db->query("ALTER TABLE `requirements` ADD `owner_id` INT(11) NULL AFTER `received_date`"); }
            if (!in_array('reference_url', $fields, true)) { $db->query("ALTER TABLE `requirements` ADD `reference_url` VARCHAR(500) NULL DEFAULT NULL"); }
        }
        if (!$db->table_exists('requirement_attachments')){
            $sql2 = "CREATE TABLE `requirement_attachments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `requirement_id` int(11) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `original_name` varchar(255) NOT NULL,
                `file_path` varchar(500) NOT NULL,
                `file_size` int(11) DEFAULT NULL,
                `file_type` varchar(100) DEFAULT NULL,
                `uploaded_by` int(11) DEFAULT NULL,
                `uploaded_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_requirement` (`requirement_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql2);
        }
        if (!$db->table_exists('requirement_versions')){
            $sql3 = "CREATE TABLE `requirement_versions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `requirement_id` int(11) NOT NULL,
                `version_no` int(11) NOT NULL,
                `title` varchar(500) NOT NULL,
                `description` text,
                `requirement_type` varchar(50) DEFAULT NULL,
                `priority` varchar(20) DEFAULT NULL,
                `status` varchar(50) DEFAULT NULL,
                `budget_estimate` decimal(15,2) DEFAULT NULL,
                `expected_delivery_date` date DEFAULT NULL,
                `received_date` date DEFAULT NULL,
                `owner_id` int(11) DEFAULT NULL,
                `assigned_to` int(11) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_req` (`requirement_id`),
                KEY `idx_version` (`version_no`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql3);
        }
        if (!$db->table_exists('requirement_comments')){
            $sql4 = "CREATE TABLE `requirement_comments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `requirement_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `comment` text NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_req_comment` (`requirement_id`),
                KEY `idx_user_comment` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql4);
        }
        // Add missing columns to versions as well
        if ($db->table_exists('requirement_versions')){
            $vfields = $db->list_fields('requirement_versions');
            if (!in_array('owner_id', $vfields, true)) { $db->query("ALTER TABLE `requirement_versions` ADD `owner_id` INT(11) NULL AFTER `received_date`"); }
            if (!in_array('reference_url', $vfields, true)) { $db->query("ALTER TABLE `requirement_versions` ADD `reference_url` VARCHAR(500) NULL DEFAULT NULL"); }
        }
    }
}
