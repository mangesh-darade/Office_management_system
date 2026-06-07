<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Schema for modules required before Rewards & Recognition.
 */

if (!function_exists('engagement_schema_ensure')) {
    function engagement_schema_ensure($db)
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

        if (!$db->table_exists('project_releases')) {
            $db->query("CREATE TABLE `project_releases` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `version` varchar(50) NOT NULL,
                `title` varchar(255) NOT NULL,
                `description` text,
                `planned_date` date DEFAULT NULL,
                `released_at` datetime DEFAULT NULL,
                `status` enum('planned','in_progress','released','cancelled') NOT NULL DEFAULT 'planned',
                `created_by` int(11) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_pr_project` (`project_id`),
                KEY `idx_pr_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($db->table_exists('project_releases')) {
            if (!schema_table_has_column($db, 'project_releases', 'notes_sent_at')) {
                $db->query("ALTER TABLE `project_releases` ADD COLUMN `notes_sent_at` datetime DEFAULT NULL AFTER `released_at`");
            }
        }

        if (!$db->table_exists('project_release_notes')) {
            $db->query("CREATE TABLE `project_release_notes` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `release_id` int(11) NOT NULL,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `point_text` varchar(500) NOT NULL,
                `source_type` varchar(20) DEFAULT 'manual',
                `source_id` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_prn_release` (`release_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('kb_articles')) {
            $db->query("CREATE TABLE `kb_articles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `summary` varchar(500) DEFAULT NULL,
                `body` mediumtext NOT NULL,
                `category` varchar(100) DEFAULT NULL,
                `tags` varchar(255) DEFAULT NULL,
                `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
                `author_id` int(11) NOT NULL,
                `published_at` datetime DEFAULT NULL,
                `view_count` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_kb_status` (`status`),
                KEY `idx_kb_author` (`author_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('helpdesk_tickets')) {
            $db->query("CREATE TABLE `helpdesk_tickets` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ticket_number` varchar(30) NOT NULL,
                `subject` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
                `requester_id` int(11) NOT NULL,
                `assigned_to` int(11) DEFAULT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_ticket_number` (`ticket_number`),
                KEY `idx_ht_status` (`status`),
                KEY `idx_ht_requester` (`requester_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('company_events')) {
            $db->query("CREATE TABLE `company_events` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `description` text,
                `location` varchar(255) DEFAULT NULL,
                `start_at` datetime NOT NULL,
                `end_at` datetime DEFAULT NULL,
                `organizer_id` int(11) NOT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ce_start` (`start_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('employee_certifications')) {
            $db->query("CREATE TABLE `employee_certifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `cert_name` varchar(255) NOT NULL,
                `issuer` varchar(255) DEFAULT NULL,
                `certified_on` date DEFAULT NULL,
                `expires_on` date DEFAULT NULL,
                `credential_id` varchar(100) DEFAULT NULL,
                `evidence_url` varchar(500) DEFAULT NULL,
                `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                `submitted_by` int(11) NOT NULL,
                `approved_by` int(11) DEFAULT NULL,
                `approved_at` datetime DEFAULT NULL,
                `review_comment` text,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ec_user` (`user_id`),
                KEY `idx_ec_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('customer_feedback_entries')) {
            $db->query("CREATE TABLE `customer_feedback_entries` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) DEFAULT NULL,
                `project_id` int(11) DEFAULT NULL,
                `submitted_by` int(11) NOT NULL,
                `customer_name` varchar(150) DEFAULT NULL,
                `rating` tinyint(1) NOT NULL DEFAULT 5,
                `feedback_text` text NOT NULL,
                `sentiment` varchar(20) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_cfe_client` (`client_id`),
                KEY `idx_cfe_rating` (`rating`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('office_closing_submissions')) {
            $db->query("CREATE TABLE `office_closing_submissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `checklist_date` date NOT NULL,
                `notes` text,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_office_close_user_date` (`user_id`,`checklist_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

    }
}
