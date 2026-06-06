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
    }
}
