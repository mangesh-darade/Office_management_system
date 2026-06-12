<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DB admin helpers (CSRF, migration log, schema bootstrap tables)
 */

if (!function_exists('db_ensure_client_db_fields')) {
    function db_ensure_client_db_fields($db)
    {
        if (!$master_db->table_exists('clients')) {
            return;
        }
        if (!schema_table_has_column($master_db, 'clients', 'db_host')) {
            $master_db->query('ALTER TABLE `clients` ADD `db_host` varchar(255) DEFAULT NULL AFTER `db_password`');
        }
        if (!schema_table_has_column($master_db, 'clients', 'db_port')) {
            $master_db->query('ALTER TABLE `clients` ADD `db_port` varchar(10) DEFAULT NULL AFTER `db_host`');
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
