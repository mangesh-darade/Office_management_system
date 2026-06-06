<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DB connection helpers for admin Db controller
 */

if (!function_exists('db_is_local_db_host')) {
    function db_is_local_db_host($host)
    {
        $h = strtolower(trim((string) $host));
        return $h === '' || $h === 'localhost' || $h === '127.0.0.1' || $h === '::1';
    }
}

if (!function_exists('db_connect_custom')) {
    function db_connect_custom($CI, $master_db, $hostname, $username, $password, $database, $port = null)
    {
        $driver   = property_exists($master_db, 'dbdriver') ? $master_db->dbdriver : 'mysqli';
        $char_set = property_exists($master_db, 'char_set') ? $master_db->char_set : 'utf8';
        $dbcollat = property_exists($master_db, 'dbcollat') ? $master_db->dbcollat : 'utf8_general_ci';
        $params = [
            'hostname' => $hostname,
            'username' => $username,
            'password' => $password,
            'database' => $database,
            'dbdriver' => $driver,
            'char_set' => $char_set,
            'dbcollat' => $dbcollat,
            'pconnect' => FALSE,
            'db_debug' => FALSE, // Force FALSE to handle errors manually
            'cache_on' => FALSE,
            'cachedir' => '',
            'save_queries' => TRUE,
        ];
        if (!empty($port)) { $params['port'] = (int)$port; }
        
        // Suppress PHP warnings (like mysqli warning) to prevent HTML leakage into JSON
        $prev_level = error_reporting(0);
        try {
            $db = $CI->load->database($params, TRUE);
            
            // Explicit check if connected
            if (!$db->conn_id && !$db->initialize()) {
                 $err = $db->error();
                 error_reporting($prev_level);
                 throw new Exception('Database Connection Failed: ' . (isset($err['message']) && $err['message'] ? $err['message'] : 'Check credentials'));
            }
            error_reporting($prev_level);
            return $db;
        } catch (Exception $e) {
            error_reporting($prev_level);
            throw $e;
        } catch (Throwable $t) {
            error_reporting($prev_level);
            throw new Exception($t->getMessage());
        }
    }
}

if (!function_exists('db_connect_to')) {
    function db_connect_to($CI, $master_db, $database, $db_debug = false)
    {
        $driver   = property_exists($master_db, 'dbdriver') ? $master_db->dbdriver : 'mysqli';
        $hostname = property_exists($master_db, 'hostname') ? $master_db->hostname : 'localhost';
        $username = property_exists($master_db, 'username') ? $master_db->username : 'root';
        $password = property_exists($master_db, 'password') ? $master_db->password : '';
        $char_set = property_exists($master_db, 'char_set') ? $master_db->char_set : 'utf8';
        $dbcollat = property_exists($master_db, 'dbcollat') ? $master_db->dbcollat : 'utf8_general_ci';
        $params = [
            'hostname' => $hostname,
            'username' => $username,
            'password' => $password,
            'database' => $database,
            'dbdriver' => $driver,
            'char_set' => $char_set,
            'dbcollat' => $dbcollat,
            'pconnect' => FALSE,
            'db_debug' => (bool) $db_debug,
            'cache_on' => FALSE,
            'cachedir' => '',
            'save_queries' => TRUE,
        ];
        return $CI->load->database($params, TRUE);
    }
}

if (!function_exists('db_connect_to_verified')) {
    function db_connect_to_verified($CI, $master_db, $database, $context = '')
    {
        $prev_level = error_reporting(0);
        try {
            $db = db_connect_to($CI, $master_db, $database);
            if (!$db->conn_id && method_exists($db, 'initialize')) {
                $db->initialize();
            }
            if (!$db->conn_id) {
                $err = $db->error();
                $msg = is_array($err) && !empty($err['message']) ? $err['message'] : 'Connection failed';
                throw new Exception($msg . ($context !== '' ? ' (' . $context . ')' : ''));
            }
            error_reporting($prev_level);
            return $db;
        } catch (Exception $e) {
            error_reporting($prev_level);
            throw $e;
        }
    }
}

if (!function_exists('db_compare_connection_options_from_request')) {
    function db_compare_connection_options_from_request($input)
    {
        $useLive = $input->post('use_live_server') === '1';
        return array(
            'host' => trim((string) $input->post('db_host')),
            'port' => trim((string) $input->post('db_port')),
            'use_local_credentials' => !$useLive && $input->post('use_local_credentials') === '1',
            'allow_local_fallback' => !$useLive && $input->post('allow_local_fallback') !== '0',
            'use_live_server' => $useLive,
        );
    }
}

if (!function_exists('db_resolve_client_connection')) {
    function db_resolve_client_connection($CI, $client_model, $master_db, $client_id, $options = array())
    {
        $creds = $client_model->get_client_credentials($client_id);
        if (!$creds) {
            throw new Exception('Client not found.');
        }
        if (empty($creds->db_name)) {
            throw new Exception('Client "' . (isset($creds->company_name) ? $creds->company_name : ('#' . $client_id)) . '" has no database name configured.');
        }

        $clientLabel = isset($creds->company_name) ? $creds->company_name : ('Client #' . $client_id);

        if (!empty($options['use_local_credentials'])) {
            return db_connect_to_verified($CI, $master_db, $creds->db_name, 'local WAMP credentials for "' . $clientLabel . '"');
        }

        $host = !empty($options['host']) ? $options['host'] : (!empty($creds->db_host) ? $creds->db_host : ($master_db->hostname ? $master_db->hostname : 'localhost'));
        $port = !empty($options['port']) ? $options['port'] : (!empty($creds->db_port) ? $creds->db_port : null);
        $allowFallback = !isset($options['allow_local_fallback']) || $options['allow_local_fallback'];
        $storedHostIsLocal = db_is_local_db_host(isset($creds->db_host) ? $creds->db_host : '');
        $targetHostIsLocal = db_is_local_db_host($host);

        if (!empty($options['use_live_server'])) {
            if ($host === '' || db_is_local_db_host($host)) {
                throw new Exception(
                    'Live server mode for "' . $clientLabel . '" needs a remote DB Host (not localhost). '
                    . 'Set it under CRM → Clients → Edit, or enter the MySQL hostname in the override field '
                    . '(cPanel → Remote MySQL shows the server hostname; whitelist your WAMP public IP there).'
                );
            }
            return db_connect_custom($CI, $master_db, $host, $creds->db_username, $creds->db_password, $creds->db_name, $port);
        }

        if ($targetHostIsLocal && (empty($creds->db_host) || $storedHostIsLocal)) {
            return db_connect_to_verified($CI, $master_db, $creds->db_name, 'local WAMP for "' . $clientLabel . '"');
        }

        try {
            return db_connect_custom($CI, $master_db, $host, $creds->db_username, $creds->db_password, $creds->db_name, $port);
        } catch (Exception $e) {
            if (!$allowFallback || !$targetHostIsLocal) {
                throw new Exception(
                    'Cannot connect to ' . $clientLabel . ' at ' . $host . ' (user: ' . $creds->db_username . '). ' . $e->getMessage()
                    . ($targetHostIsLocal ? ' Tip: enable "Use local WAMP credentials" or create database "' . $creds->db_name . '" on WAMP.' : ' Tip: set DB Host under CRM → Clients for the remote MySQL server.')
                );
            }
            try {
                return db_connect_to_verified($CI, $master_db, $creds->db_name, 'local fallback for "' . $clientLabel . '"');
            } catch (Exception $e2) {
                throw new Exception(
                    'Remote: ' . $e->getMessage() . ' | Local fallback (db: ' . $creds->db_name . '): ' . $e2->getMessage()
                    . ' — Create database "' . $creds->db_name . '" in phpMyAdmin on WAMP, or set the client DB Host to the live server.'
                );
            }
        }
    }
}

if (!function_exists('db_resolve_connection')) {
    function db_resolve_connection($CI, $client_model, $master_db, $client_id, $manual_config = array())
    {
        if (!empty($client_id)) {
            $opts = array();
            if (!empty($manual_config['host'])) {
                $opts['host'] = $manual_config['host'];
            }
            if (!empty($manual_config['port'])) {
                $opts['port'] = $manual_config['port'];
            }
            if (isset($manual_config['use_local_credentials'])) {
                $opts['use_local_credentials'] = (bool) $manual_config['use_local_credentials'];
            }
            if (isset($manual_config['allow_local_fallback'])) {
                $opts['allow_local_fallback'] = (bool) $manual_config['allow_local_fallback'];
            }
            if (isset($manual_config['use_live_server'])) {
                $opts['use_live_server'] = (bool) $manual_config['use_live_server'];
            }

            return db_resolve_client_connection($CI, $client_model, $master_db, $client_id, $opts);
        }

        if (!empty($manual_config['db'])) {
            require_module_access(array('db_admin', 'db'), true);

            return db_connect_custom(
                $CI,
                $master_db,
                $manual_config['host'],
                $manual_config['user'],
                $manual_config['pass'],
                $manual_config['db'],
                $manual_config['port']
            );
        }

        return db_connect_to($CI, $master_db, $manual_config['db'] ?: $master_db->database);
    }
}

if (!function_exists('db_resolve_compare_connection')) {
    function db_resolve_compare_connection($CI, $client_model, $master_db, $client_id, $db_name, $is_master = false, $conn_options = array())
    {
        if ($is_master || ($client_id === 0 && $db_name === $master_db->database)) {
            return array($master_db, $master_db->database);
        }
        if ($client_id) {
            $conn = db_resolve_client_connection($CI, $client_model, $master_db, $client_id, $conn_options);

            return array($conn, $conn->database);
        }
        if ($db_name === '') {
            throw new Exception('Database name is required.');
        }
        $conn = db_connect_to($CI, $master_db, $db_name);

        return array($conn, $db_name);
    }
}
