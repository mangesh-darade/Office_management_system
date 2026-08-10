<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRM clients and client_contacts tables
 */

if (!function_exists('clients_schema_ensure')) {
    function clients_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (!$db->table_exists('clients')){
            $sql = "CREATE TABLE `clients` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_code` varchar(50) NOT NULL,
                `company_name` varchar(255) NOT NULL,
                `contact_person` varchar(200) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `alternate_phone` varchar(20) DEFAULT NULL,
                `website` varchar(255) DEFAULT NULL,
                `demo_url` varchar(255) DEFAULT NULL,
                `pos_url` varchar(255) DEFAULT NULL,
                `address` text,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `country` varchar(100) DEFAULT 'India',
                `zip_code` varchar(20) DEFAULT NULL,
                `gstin` varchar(50) DEFAULT NULL,
                `pan_number` varchar(20) DEFAULT NULL,
                `industry` varchar(100) DEFAULT NULL,
                `onboarding_date` date DEFAULT NULL,
                `client_type` varchar(30) DEFAULT 'company',
                `account_manager_id` int(11) DEFAULT NULL,
                `status` varchar(20) DEFAULT 'active',
                `notes` text,
                `db_name` varchar(255) DEFAULT NULL,
                `db_username` varchar(255) DEFAULT NULL,
                `db_password` varchar(255) DEFAULT NULL,
                `logo` varchar(255) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_client_code` (`client_code`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
        if ($db->table_exists('clients')){
            $fields = [
                'demo_url' => "ALTER TABLE `clients` ADD `demo_url` varchar(255) DEFAULT NULL AFTER `website`",
                'pos_url' => "ALTER TABLE `clients` ADD `pos_url` varchar(255) DEFAULT NULL AFTER `demo_url`",
                'onboarding_date' => "ALTER TABLE `clients` ADD `onboarding_date` date DEFAULT NULL AFTER `industry`",
                'db_name' => "ALTER TABLE `clients` ADD `db_name` varchar(255) DEFAULT NULL AFTER `notes`",
                'db_username' => "ALTER TABLE `clients` ADD `db_username` varchar(255) DEFAULT NULL AFTER `db_name`",
                'db_password' => "ALTER TABLE `clients` ADD `db_password` varchar(255) DEFAULT NULL AFTER `db_username`",
                'db_host' => "ALTER TABLE `clients` ADD `db_host` varchar(255) DEFAULT NULL AFTER `db_password`",
                'db_port' => "ALTER TABLE `clients` ADD `db_port` varchar(10) DEFAULT NULL AFTER `db_host`",
            ];
            foreach ($fields as $field => $sql){
                if (!schema_table_has_column($db, 'clients', $field)){
                    $db->query($sql);
                }
            }
            // Ensure id is usable as insert PK (missing AI caused "Clients create error").
            // Only when ids are already unique — never rewrite / dedupe data here.
            $id_col = $db->query("SHOW COLUMNS FROM `clients` LIKE 'id'");
            $id_meta = ($id_col && $id_col->num_rows() > 0) ? $id_col->row_array() : null;
            if (is_array($id_meta)) {
                $extra = isset($id_meta['Extra']) ? strtolower((string) $id_meta['Extra']) : '';
                $key = isset($id_meta['Key']) ? strtoupper((string) $id_meta['Key']) : '';
                $dup = $db->query('SELECT id FROM `clients` GROUP BY id HAVING COUNT(*) > 1 LIMIT 1');
                $has_dup = ($dup && $dup->num_rows() > 0);
                if (!$has_dup) {
                    if ($key !== 'PRI') {
                        $db->query('ALTER TABLE `clients` ADD PRIMARY KEY (`id`)');
                    }
                    if (strpos($extra, 'auto_increment') === false) {
                        $db->query('ALTER TABLE `clients` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT');
                    }
                }
            }
        }
        if (!$db->table_exists('client_contacts')){
            $sql2 = "CREATE TABLE `client_contacts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) NOT NULL,
                `contact_name` varchar(200) NOT NULL,
                `designation` varchar(100) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `is_primary` tinyint(1) DEFAULT 0,
                `department` varchar(100) DEFAULT NULL,
                `notes` text,
                `status` varchar(20) DEFAULT 'active',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_client` (`client_id`),
                KEY `idx_primary` (`is_primary`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql2);
        }
        // Multiple URL + DB sets per client
        if (!$db->table_exists('client_urls')) {
            $sql3 = "CREATE TABLE `client_urls` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) NOT NULL,
                `version` varchar(50) NOT NULL DEFAULT '1.0',
                `url` varchar(500) NOT NULL,
                `url_type` varchar(30) DEFAULT 'website',
                `db_name` varchar(255) DEFAULT NULL,
                `db_username` varchar(255) DEFAULT NULL,
                `db_password` varchar(255) DEFAULT NULL,
                `db_host` varchar(255) DEFAULT NULL,
                `db_port` varchar(20) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_client_id` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql3);
        }
        if ($db->table_exists('client_urls')) {
            $url_db_fields = array(
                'db_name' => "ALTER TABLE `client_urls` ADD `db_name` varchar(255) DEFAULT NULL AFTER `url_type`",
                'db_username' => "ALTER TABLE `client_urls` ADD `db_username` varchar(255) DEFAULT NULL AFTER `db_name`",
                'db_password' => "ALTER TABLE `client_urls` ADD `db_password` varchar(255) DEFAULT NULL AFTER `db_username`",
                'db_host' => "ALTER TABLE `client_urls` ADD `db_host` varchar(255) DEFAULT NULL AFTER `db_password`",
                'db_port' => "ALTER TABLE `client_urls` ADD `db_port` varchar(20) DEFAULT NULL AFTER `db_host`",
            );
            foreach ($url_db_fields as $field => $sql) {
                if (!schema_table_has_column($db, 'client_urls', $field)) {
                    $db->query($sql);
                }
            }
        }
        if (!$db->table_exists('client_activity')) {
            $db->query("CREATE TABLE `client_activity` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` int(11) UNSIGNED NOT NULL,
                `user_id` int(11) UNSIGNED DEFAULT NULL,
                `action` varchar(50) NOT NULL,
                `old_value` longtext DEFAULT NULL,
                `new_value` longtext DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_cact_client` (`client_id`),
                KEY `idx_cact_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
