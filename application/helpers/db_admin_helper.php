<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DB admin helpers (CSRF, migration log, schema bootstrap tables)
 */

if (!function_exists('db_ensure_client_db_fields')) {
    function db_ensure_client_db_fields($db)
    {
        if (!$db->table_exists('clients')) {
            return;
        }
        if (!schema_table_has_column($db, 'clients', 'db_host')) {
            $db->query('ALTER TABLE `clients` ADD `db_host` varchar(255) DEFAULT NULL AFTER `db_password`');
        }
        if (!schema_table_has_column($db, 'clients', 'db_port')) {
            $db->query('ALTER TABLE `clients` ADD `db_port` varchar(10) DEFAULT NULL AFTER `db_host`');
        }
    }
}

if (!function_exists('db_verify_csrf')) {
    function db_verify_csrf($session, $input)
    {
        // Ensure we have a token in session
        if (!$session->userdata('db_csrf_token')) {
            $session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        
        // If it's a POST request to a sensitive endpoint, verify header/post
        $token_in = $input->post('csrf_token') ?: $input->get_request_header('X-CSRF-Token');
        $token_sess = $session->userdata('db_csrf_token');
        
        if (empty($token_in) || !hash_equals($token_sess, $token_in)){
            return false;
        }
        return true;
    }
}

if (!function_exists('db_ensure_dm_manager_table')) {
    function db_ensure_dm_manager_table($db, $dm_table = 'dm_manager')
    {
        $db->query("CREATE TABLE IF NOT EXISTS `dm_manager` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` INT NULL,
            `assign_id` INT NULL,
            `version` VARCHAR(50) NULL,
            `title` VARCHAR(191) NULL,
            `squary` LONGTEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Backward-compat: if old saved_queries exists but dm_manager doesn't, no migration here (out of scope)
        // Ensure optional metadata columns exist for revert support
        $tblParts = explode('.', $dm_table);
        $baseTbl = end($tblParts);
        if (!schema_table_has_column($db, $baseTbl, 'file_path')) {
            $db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `file_path` VARCHAR(500) NULL AFTER `squary`");
        }
        if (!schema_table_has_column($db, $baseTbl, 'backup_path')) {
            $db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `backup_path` VARCHAR(500) NULL AFTER `file_path`");
        }
        if (!schema_table_has_column($db, $baseTbl, 'database_name')) {
            $db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `database_name` VARCHAR(191) NULL AFTER `backup_path`");
        }
        if (!schema_table_has_column($db, $baseTbl, 'table_name')) {
            $db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `table_name` VARCHAR(191) NULL AFTER `database_name`");
        }
    }
}

if (!function_exists('db_ensure_client_migrations_table')) {
    function db_ensure_client_migrations_table($db, $client_migrations_table = 'client_migrations')
    {
        $tbl = $client_migrations_table;
        $db->query("CREATE TABLE IF NOT EXISTS `".$tbl."` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id` INT NULL,
            `client_name` VARCHAR(255) NOT NULL,
            `database_name` VARCHAR(191) NOT NULL,
            `action` VARCHAR(20) NOT NULL,
            `tables_count` INT NOT NULL DEFAULT 0,
            `columns_count` INT NOT NULL DEFAULT 0,
            `file_path` VARCHAR(500) NULL,
            `details` LONGTEXT NULL,
            `run_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX (`client_id`),
            INDEX (`database_name`),
            INDEX (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!schema_table_has_column($db, $tbl, 'details')) {
            $db->query("ALTER TABLE `".$tbl."` ADD COLUMN `details` LONGTEXT NULL AFTER `file_path`");
        }
        if (!schema_table_has_column($db, $tbl, 'run_by')) {
            $db->query("ALTER TABLE `".$tbl."` ADD COLUMN `run_by` INT NULL AFTER `details`");
        }
    }
}

if (!function_exists('db_log_client_migration')) {
    function db_log_client_migration($db, $session, $client_migrations_table, $client_id, $client_name, $dbName, $action, $tables, $columns, $file, $details = null)
    {
        if (!$db->table_exists($client_migrations_table)) return;
        if (is_array($details) || is_object($details)){
            $details = @json_encode($details);
        }
        $data = [
            'client_id' => $client_id ? (int)$client_id : null,
            'client_name' => (string)$client_name,
            'database_name' => (string)$dbName,
            'action' => (string)$action,
            'tables_count' => (int)$tables,
            'columns_count' => (int)$columns,
            'file_path' => $file !== '' ? $file : null,
            'details' => ($details !== null && $details !== '') ? (string)$details : null,
            'run_by' => (int)$session->userdata('user_id'),
        ];
        $db->insert($client_migrations_table, $data);
    }
}

if (!function_exists('db_escape_ident')) {
    function db_escape_ident($name)
    {
        return str_replace('`','``',$name);
    }
}


if (!function_exists('db_csrf_token')) {
    function db_csrf_token($session)
    {
        if (!$session->userdata('db_csrf_token')) {
            $session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        return $session->userdata('db_csrf_token');
    }
}
