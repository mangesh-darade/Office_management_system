<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Db extends CI_Controller {
    private $dm_table = 'dm_manager';
    private $client_migrations_table = 'client_migrations';
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']); // Ensure permission helper is loaded
        $this->load->library(['session']);
        $this->load->model('Client_model', 'client_model');
        
        // RBAC Audit: Centralized module access check
        require_module_access('db', true);

        $this->ensure_dm_manager_table();
        $this->ensure_client_migrations_table();
        $this->ensure_client_db_fields();
    }

    private function ensure_client_db_fields()
    {
        if (!$this->db->table_exists('clients')) {
            return;
        }
        if (!$this->db->field_exists('db_host', 'clients')) {
            $this->db->query('ALTER TABLE `clients` ADD `db_host` varchar(255) DEFAULT NULL AFTER `db_password`');
        }
        if (!$this->db->field_exists('db_port', 'clients')) {
            $this->db->query('ALTER TABLE `clients` ADD `db_port` varchar(10) DEFAULT NULL AFTER `db_host`');
        }
    }

    // Security Helper: Verify CSRF (Manual implementation since global might be off)
    private function verify_csrf(){
        // Ensure we have a token in session
        if (!$this->session->userdata('db_csrf_token')) {
            $this->session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        
        // If it's a POST request to a sensitive endpoint, verify header/post
        $token_in = $this->input->post('csrf_token') ?: $this->input->get_request_header('X-CSRF-Token');
        $token_sess = $this->session->userdata('db_csrf_token');
        
        if (empty($token_in) || !hash_equals($token_sess, $token_in)){
            return false;
        }
        return true;
    }
    
    public function get_csrf_token(){
        if (!$this->session->userdata('db_csrf_token')) {
            $this->session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        echo json_encode(['token' => $this->session->userdata('db_csrf_token')]);
    }

    // Helper: Resolve DB Connection
    /**
     * Connection options: host, port, use_local_credentials, allow_local_fallback
     */
    private function is_local_db_host($host)
    {
        $h = strtolower(trim((string) $host));
        return $h === '' || $h === 'localhost' || $h === '127.0.0.1' || $h === '::1';
    }

    private function resolve_client_connection($client_id, $options = array())
    {
        $creds = $this->client_model->get_client_credentials($client_id);
        if (!$creds) {
            throw new Exception('Client not found.');
        }
        if (empty($creds->db_name)) {
            throw new Exception('Client "' . (isset($creds->company_name) ? $creds->company_name : ('#' . $client_id)) . '" has no database name configured.');
        }

        $clientLabel = isset($creds->company_name) ? $creds->company_name : ('Client #' . $client_id);

        if (!empty($options['use_local_credentials'])) {
            return $this->connect_to_verified($creds->db_name, 'local WAMP credentials for "' . $clientLabel . '"');
        }

        $host = !empty($options['host']) ? $options['host'] : (!empty($creds->db_host) ? $creds->db_host : ($this->db->hostname ? $this->db->hostname : 'localhost'));
        $port = !empty($options['port']) ? $options['port'] : (!empty($creds->db_port) ? $creds->db_port : null);
        $allowFallback = !isset($options['allow_local_fallback']) || $options['allow_local_fallback'];
        $storedHostIsLocal = $this->is_local_db_host(isset($creds->db_host) ? $creds->db_host : '');
        $targetHostIsLocal = $this->is_local_db_host($host);

        // Explicit live-server mode: stored cPanel/hosting credentials against remote MySQL
        if (!empty($options['use_live_server'])) {
            if ($host === '' || $this->is_local_db_host($host)) {
                throw new Exception(
                    'Live server mode for "' . $clientLabel . '" needs a remote DB Host (not localhost). '
                    . 'Set it under CRM → Clients → Edit, or enter the MySQL hostname in the override field '
                    . '(cPanel → Remote MySQL shows the server hostname; whitelist your WAMP public IP there).'
                );
            }
            return $this->connect_custom($host, $creds->db_username, $creds->db_password, $creds->db_name, $port);
        }

        // WAMP/dev: no remote host on client record + connecting to localhost → use OMS (root) creds, not cPanel username
        if ($targetHostIsLocal && (empty($creds->db_host) || $storedHostIsLocal)) {
            return $this->connect_to_verified($creds->db_name, 'local WAMP for "' . $clientLabel . '"');
        }

        try {
            return $this->connect_custom($host, $creds->db_username, $creds->db_password, $creds->db_name, $port);
        } catch (Exception $e) {
            if (!$allowFallback || !$targetHostIsLocal) {
                throw new Exception(
                    'Cannot connect to ' . $clientLabel . ' at ' . $host . ' (user: ' . $creds->db_username . '). ' . $e->getMessage()
                    . ($targetHostIsLocal ? ' Tip: enable "Use local WAMP credentials" or create database "' . $creds->db_name . '" on WAMP.' : ' Tip: set DB Host under CRM → Clients for the remote MySQL server.')
                );
            }
            try {
                return $this->connect_to_verified($creds->db_name, 'local fallback for "' . $clientLabel . '"');
            } catch (Exception $e2) {
                throw new Exception(
                    'Remote: ' . $e->getMessage() . ' | Local fallback (db: ' . $creds->db_name . '): ' . $e2->getMessage()
                    . ' — Create database "' . $creds->db_name . '" in phpMyAdmin on WAMP, or set the client DB Host to the live server.'
                );
            }
        }
    }

    private function compare_connection_options_from_request()
    {
        $useLive = $this->input->post('use_live_server') === '1';
        return array(
            'host' => trim((string) $this->input->post('db_host')),
            'port' => trim((string) $this->input->post('db_port')),
            'use_local_credentials' => !$useLive && $this->input->post('use_local_credentials') === '1',
            'allow_local_fallback' => !$useLive && $this->input->post('allow_local_fallback') !== '0',
            'use_live_server' => $useLive,
        );
    }

    private function resolve_connection($client_id, $manual_config = array()) {
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
            return $this->resolve_client_connection($client_id, $opts);
        }
        
        // Fallback or Manual
        if (!empty($manual_config['db'])) {
            require_module_access(['db_admin', 'db'], true);
            return $this->connect_custom(
                $manual_config['host'],
                $manual_config['user'],
                $manual_config['pass'],
                $manual_config['db'],
                $manual_config['port']
            );
        }
        
        // Default System DB
        return $this->connect_to($manual_config['db'] ?: $this->db->database);
    }
    
    // Legacy: Allow manual connection params for manual tool, but restrict to admin?
    // We'll keep the logic but wrap it in permission checks.

    // POST: file_path, database, optional host/user/pass => append DB-only items to the SQL file
    public function compare_update_file_missing(){
        // Security Check
        if (!$this->verify_csrf()) {
           // header('HTTP/1.1 403 Forbidden'); echo json_encode(['success'=>false,'message'=>'CSRF Token Mismatch']); return;
           // Soft-fail for now until frontend sends token, or auto-generate
        }

        $file = (string)$this->input->post('file_path');
        $dbName = trim((string)$this->input->post('database'));
        $client_id = (int)$this->input->post('client_id');
        
        if ($file === '' || ($dbName === '' && !$client_id)){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'File path and database/client are required']); return;
        }

        try {
            $target = $this->resolve_connection($client_id, [
                'host' => $this->input->post('host'),
                'user' => $this->input->post('user'),
                'pass' => $this->input->post('pass'),
                'port' => $this->input->post('port'),
                'db'   => $dbName
            ]);
            // Refresh dbName from target if we used client_id
            $dbName = $target->database;
            
        } catch (Exception $e){
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Connection Failed: '.$e->getMessage()]); return;
        }

        // Parse file schema
        $schema = $this->parse_sql_schema($file);
        // Existing DB structure
        $tables = [];
        $tableNameMap = [];
        $tableTypeMap = [];
        $q = $target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
        foreach ($q->result() as $r){ $tables[strtolower($r->TABLE_NAME)] = true; $tableNameMap[strtolower($r->TABLE_NAME)] = $r->TABLE_NAME; $tableTypeMap[strtolower($r->TABLE_NAME)] = strtoupper((string)$r->TABLE_TYPE); }
        $cols = [];
        if (!empty($tables)){
            $rs = $target->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbName]);
            foreach ($rs->result() as $r){ $t = strtolower($r->TABLE_NAME); $c = strtolower($r->COLUMN_NAME); if (!isset($cols[$t])) $cols[$t] = []; $cols[$t][$c] = true; }
        }
        $fileTablesLower = [];
        foreach ($schema['tables'] as $t => $meta){ $fileTablesLower[strtolower($t)] = $t; }
        // Compute DB-only
        $dbOnlyTables = [];
        $dbOnlyColumns = [];
        foreach ($tables as $tLower => $_){
            if (!isset($fileTablesLower[$tLower])){
                $dbOnlyTables[] = isset($tableNameMap[$tLower]) ? $tableNameMap[$tLower] : $tLower;
                continue;
            }
            $meta = isset($schema['tables'][$fileTablesLower[$tLower]]) ? $schema['tables'][$fileTablesLower[$tLower]] : null;
            $fileCols = $meta ? array_keys($meta['columns']) : [];
            $fileColsMap = [];
            foreach ($fileCols as $fc){ $fileColsMap[strtolower($fc)] = true; }
            // Skip columns comparison for views
            if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
            $dbCols = isset($cols[$tLower]) ? array_keys($cols[$tLower]) : [];
            foreach ($dbCols as $dc){ if (!isset($fileColsMap[$dc])) { $dbOnlyColumns[] = ['table' => (isset($tableNameMap[$tLower])?$tableNameMap[$tLower]:$fileTablesLower[$tLower]), 'column' => $dc]; } }
        }
        // Build SQL for DB-only items
        $appendParts = [];
        $tablesAdded = 0; $columnsAdded = 0;
        foreach ($dbOnlyTables as $tblName){
            try {
                $res = $target->query('SHOW CREATE TABLE `'.$tblName.'`');
                if ($res && $res->num_rows() > 0){
                    $row = $res->row_array();
                    $sqlCreate = '';
                    if (isset($row['Create Table'])){ $sqlCreate = $row['Create Table']; }
                    else { $vals = array_values($row); if (isset($vals[1])) $sqlCreate = $vals[1]; }
                    if ($sqlCreate !== ''){ $appendParts[] = rtrim($sqlCreate, "; \r\n").";"; $tablesAdded++; }
                }
            } catch (Exception $e) { /* ignore */ }
        }
        foreach ($dbOnlyColumns as $it){
            $tName = $it['table']; $cName = $it['column'];
            try {
                $ci = $target->query('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1', [$dbName, $tName, $cName]);
                if ($ci && $ci->num_rows() > 0){
                    $row = $ci->row_array();
                    // Skip generated columns as we cannot reconstruct the AS(...) expression from information_schema
                    if (!empty($row['EXTRA']) && stripos($row['EXTRA'], 'GENERATED') !== false){
                        continue;
                    }
                    $defParts = [];
                    $defParts[] = '`'.$row['COLUMN_NAME'].'`';
                    $defParts[] = $row['COLUMN_TYPE'];
                    if (!empty($row['CHARACTER_SET_NAME'])){ $defParts[] = 'CHARACTER SET '.$row['CHARACTER_SET_NAME']; }
                    if (!empty($row['COLLATION_NAME'])){ $defParts[] = 'COLLATE '.$row['COLLATION_NAME']; }
                    $nullable = strtoupper($row['IS_NULLABLE']) === 'YES';
                    $defParts[] = $nullable ? 'NULL' : 'NOT NULL';
                    if (!is_null($row['COLUMN_DEFAULT'])){
                        $def = $row['COLUMN_DEFAULT'];
                        $upper = strtoupper($def);
                        $isNumeric = is_numeric($def);
                        $isFunc = in_array($upper, ['CURRENT_TIMESTAMP','CURRENT_TIMESTAMP()','NOW()'], true);
                        if ($isFunc){ $defParts[] = 'DEFAULT '.$upper; }
                        else if ($isNumeric){ $defParts[] = 'DEFAULT '.$def; }
                        else if ($def === 'NULL'){ $defParts[] = 'DEFAULT NULL'; }
                        else { $defParts[] = "DEFAULT '".str_replace("'","''", $def)."'"; }
                    } else if ($nullable){ /* DEFAULT NULL optional */ }
                    if (!empty($row['EXTRA'])){ $defParts[] = $row['EXTRA']; }
                    $colDef = implode(' ', array_filter($defParts));
                    $appendParts[] = 'ALTER TABLE `'.$tName.'` ADD COLUMN '.$colDef.';';
                    $columnsAdded++;
                }
            } catch (Exception $e) { /* ignore */ }
        }
        if (empty($appendParts)){
            header('Content-Type: application/json'); echo json_encode(['success'=>true,'tables'=>0,'columns'=>0,'message'=>'No DB-only items']); return;
        }
        // Append to file
        $hdr = "\n\n-- Added by DB Compare on ".date('Y-m-d H:i:s')." for database `".$dbName."`\n";
        $content = $hdr.implode("\n\n", $appendParts)."\n";
        $ok = @file_put_contents($file, $content, FILE_APPEND|LOCK_EX);
        if ($ok === false){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to write to file']); return; }
        header('Content-Type: application/json'); echo json_encode(['success'=>true,'tables'=>$tablesAdded,'columns'=>$columnsAdded]); return;
    }

    public function compare_drop_db_only(){
        // PERMISSION CHECK for Destructive Action
        require_module_access(['db_admin', 'db'], true);

        if (!$this->verify_csrf()) {
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'CSRF Token Mismatch']); return;
        }

        $file = (string)$this->input->post('file_path');
        $dbName = trim((string)$this->input->post('database'));
        $client_id = (int)$this->input->post('client_id');
        $dry_run = (string)$this->input->post('dry_run') === '1';
        $confirm_db_name = trim((string)$this->input->post('confirm_db_name'));

        if (!$dry_run && $confirm_db_name !== $dbName){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Safety Check Failed: DB Name confirmation mismatch.']); return;
        }

        try {
             $target = $this->resolve_connection($client_id, [
                'host' => $this->input->post('host'),
                'user' => $this->input->post('user'),
                'pass' => $this->input->post('pass'),
                'port' => $this->input->post('port'),
                'db'   => $dbName
            ]);
            $dbName = $target->database;
        } catch (Exception $e){
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Connection Failed: '.$e->getMessage()]); return;
        }

        $schema = [];
        if ($file !== '' && @is_file($file)){
            $schema = $this->parse_sql_schema($file);
        } else {
            $schema = ['tables' => []];
        }
        $tables = [];
        $tableNameMap = [];
        $tableTypeMap = [];
        $q = $target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
        foreach ($q->result() as $r){
            $lower = strtolower($r->TABLE_NAME);
            $tables[$lower] = true;
            $tableNameMap[$lower] = $r->TABLE_NAME;
            $tableTypeMap[$lower] = strtoupper((string)$r->TABLE_TYPE);
        }
        $cols = [];
        if (!empty($tables)){
            $rs = $target->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbName]);
            foreach ($rs->result() as $r){
                $t = strtolower($r->TABLE_NAME);
                $c = strtolower($r->COLUMN_NAME);
                if (!isset($cols[$t])) $cols[$t] = [];
                $cols[$t][$c] = true;
            }
        }
        $fileTablesLower = [];
        foreach ($schema['tables'] as $t => $meta){ $fileTablesLower[strtolower($t)] = $t; }
        $dbOnlyTables = [];
        $dbOnlyColumns = [];
        foreach ($tables as $tLower => $_){
            if (!isset($fileTablesLower[$tLower])){
                if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW') continue;
                $dbOnlyTables[] = isset($tableNameMap[$tLower]) ? $tableNameMap[$tLower] : $tLower;
                continue;
            }
            $meta = isset($schema['tables'][$fileTablesLower[$tLower]]) ? $schema['tables'][$fileTablesLower[$tLower]] : null;
            $fileCols = $meta ? array_keys($meta['columns']) : [];
            $fileColsMap = [];
            foreach ($fileCols as $fc){ $fileColsMap[strtolower($fc)] = true; }
            if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
            $dbCols = isset($cols[$tLower]) ? array_keys($cols[$tLower]) : [];
            foreach ($dbCols as $dc){ if (!isset($fileColsMap[$dc])) { $dbOnlyColumns[] = ['table' => (isset($tableNameMap[$tLower])?$tableNameMap[$tLower]:$fileTablesLower[$tLower]), 'column' => $dc]; } }
        }

        // Generate Operations
        $generated_sql = [];
        $detailTables = [];
        $detailColumns = [];
        
        foreach ($dbOnlyTables as $tblName){
            $sql = 'DROP TABLE `'.$tblName.'`';
            $generated_sql[] = $sql;
            $detailTables[] = (string)$tblName;
        }
        foreach ($dbOnlyColumns as $it){
             $sql = 'ALTER TABLE `'.$it['table'].'` DROP COLUMN `'.$it['column'].'`';
             $generated_sql[] = $sql;
             $detailColumns[] = [
                'table' => isset($it['table']) ? (string)$it['table'] : '',
                'column' => isset($it['column']) ? (string)$it['column'] : '',
            ];
        }

        // If Dry Run, Return Plan
        if ($dry_run) {
             header('Content-Type: application/json');
             echo json_encode(['success'=>true, 'dry_run'=>true, 'sql' => $generated_sql, 'tables' => $detailTables, 'columns_count' => count($detailColumns)]); 
             return;
        }

        // Execute
        $droppedTables = 0; $droppedColumns = 0;
        
        $target->trans_start();
        foreach ($generated_sql as $sql){
            try {
                $target->query($sql);
                // Count basic stats
                if (stripos($sql, 'DROP TABLE') !== false) $droppedTables++;
                else $droppedColumns++;
            } catch (Exception $e) { }
        }
        $target->trans_complete();

        $client_name = (string)$this->input->post('client_name');
        $details = [
            'action' => 'revert',
            'dropped_tables' => $detailTables,
            'dropped_columns' => $detailColumns,
        ];
        $this->log_client_migration($client_id ?: null, $client_name, $dbName, 'revert', $droppedTables, $droppedColumns, $file, $details);
        header('Content-Type: application/json'); echo json_encode(['success'=>true,'tables'=>$droppedTables,'columns'=>$droppedColumns]); return;
    }

    private function normalize_column_def($def){
        $s = trim((string)$def);
        // Strip trailing comma if present
        $s = preg_replace('/,\s*$/', '', $s);
        // Replace zero-date defaults
        $s = preg_replace(
            '/\b(timestamp|datetime|date)\b\s+NOT\s+NULL\s+DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            '$1 NULL DEFAULT NULL',
            $s
        );
        $s = preg_replace(
            '/DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            'DEFAULT NULL',
            $s
        );
        return $s;
    }

    // Build a conservative CREATE TABLE from parsed column defs as a fallback
    private function build_create_sql_from_columns($table, $meta){
        $cols = [];
        if (isset($meta['columns']) && is_array($meta['columns'])){
            foreach ($meta['columns'] as $c => $def){
                $cols[] = $this->normalize_column_def($def);
            }
        }
        // If we have an auto-increment id column but no explicit PK, add a primary key on id
        $hasId = false; $hasPk = false;
        foreach ($cols as $line){
            if (preg_match('/^`?id`?\b/i', $line)){ $hasId = true; if (stripos($line,'auto_increment') !== false){ /* ok */ } }
            if (preg_match('/^primary\s+key\b/i', $line)){ $hasPk = true; }
        }
        if ($hasId && !$hasPk){ $cols[] = 'PRIMARY KEY (`id`)'; }
        $body = implode(",\n  ", $cols);
        $sql = "CREATE TABLE `".$table."` (\n  ".$body."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        return $sql;
    }

    // Build a custom DB connection with explicit server params
    private function connect_custom($hostname, $username, $password, $database, $port = null){
        $driver   = property_exists($this->db, 'dbdriver') ? $this->db->dbdriver : 'mysqli';
        $char_set = property_exists($this->db, 'char_set') ? $this->db->char_set : 'utf8';
        $dbcollat = property_exists($this->db, 'dbcollat') ? $this->db->dbcollat : 'utf8_general_ci';
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
            $db = $this->load->database($params, TRUE);
            
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

    // List available databases on the server (for dropdown)
    public function list_databases(){
        $client_id = (int) ($this->input->post('client_id') ?: $this->input->get('client_id'));
        $host = $this->input->post('host') ?: $this->input->get('host');
        $port = $this->input->post('port') ?: $this->input->get('port');
        $user = $this->input->post('user') ?: $this->input->get('user');
        $pass = $this->input->post('pass') ?: $this->input->get('pass');
        try {
            if ($client_id) {
                $tmp = $this->resolve_client_connection($client_id, array(
                    'host' => trim((string) $host),
                    'port' => trim((string) $port),
                    'allow_local_fallback' => true,
                ));
                $res = $tmp->query('SHOW DATABASES');
            } elseif ($host && $user !== null) {
                $tmp = $this->connect_custom($host, $user, (string)$pass, '', $port);
                $res = $tmp->query('SHOW DATABASES');
            } else {
                $res = $this->db->query('SHOW DATABASES');
            }
            $dbs = [];
            foreach ($res->result_array() as $row){
                $vals = array_values($row);
                $name = isset($row['Database']) ? $row['Database'] : (isset($vals[0]) ? $vals[0] : '');
                if ($name === '') continue;
                // Exclude system schemas
                if (in_array(strtolower($name), ['information_schema','mysql','performance_schema','sys'], true)) continue;
                $dbs[] = $name;
            }
            sort($dbs, SORT_NATURAL|SORT_FLAG_CASE);
            header('Content-Type: application/json'); echo json_encode(['success'=>true,'databases'=>$dbs]); return;
        } catch (Exception $e){
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); return;
        }
    }

    // Parse CREATE TABLE blocks from a .sql file to extract database name, tables, and column definitions
    private function parse_sql_schema($path){
        $res = ['database'=>'', 'tables'=>[]];
        if (!is_file($path)) { return $res; }
        $fh = @fopen($path, 'r'); if (!$fh) { return $res; }
        $currentTable = '';
        $inCreate = false; $buffer = '';
        while (!feof($fh)){
            $line = fgets($fh);
            if ($line === false) { break; }
            $trim = rtrim($line, "\r\n");
            if ($res['database'] === '' && preg_match('/^\s*--\s*Database:\s*`([^`]+)`/i', $trim, $m)){
                $res['database'] = $m[1];
            }
            // Recognize phpMyAdmin section header to at least register table presence
            if (!$inCreate && preg_match('/^\s*--\s*Table structure for table\s+`([^`]+)`/i', $trim, $hm)){
                $tname = $hm[1];
                if (!isset($res['tables'][$tname])){ $res['tables'][$tname] = ['columns'=>[], 'create_sql'=>'']; }
            }
            // Recognize DROP TABLE IF EXISTS [`db`.]`table` as a hint the table exists in file
            if (!$inCreate && preg_match('/^\s*DROP\s+TABLE\s+IF\s+EXISTS\s+(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?/i', $trim, $dm)){
                $tname = $dm[1];
                if (!isset($res['tables'][$tname])){ $res['tables'][$tname] = ['columns'=>[], 'create_sql'=>'']; }
            }
            // Recognize CREATE TABLE [IF NOT EXISTS] [`db`.]`table`
            if (!$inCreate && preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?/i', $trim, $m)){
                $inCreate = true; $currentTable = $m[1]; $buffer = $line; if (!isset($res['tables'][$currentTable])){ $res['tables'][$currentTable] = ['columns'=>[], 'create_sql'=>'']; }
                continue;
            }
            if ($inCreate){
                $buffer .= $line;
                // End of CREATE TABLE when we meet a line ending with ';'
                if (strpos($trim, ';') !== false && preg_match('/\)\s*[^;]*;\s*$/', $trim)){
                    // Extract column lines between first '(' and the closing ')'
                    $inner = $buffer;
                    $res['tables'][$currentTable]['create_sql'] = $buffer;
                    $posOpen = strpos($inner, '(');
                    $posClose = strrpos($inner, ')');
                    if ($posOpen !== false && $posClose !== false && $posClose > $posOpen){
                        $inside = substr($inner, $posOpen+1, $posClose-$posOpen-1);
                        $lines = preg_split('/\r?\n/', $inside);
                        foreach ($lines as $ln){
                            $ln = trim($ln);
                            if ($ln === '' || preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY\s+|CONSTRAINT|FOREIGN\s+KEY|INDEX)\b/i',$ln)) { continue; }
                            $ln = rtrim($ln, ",; ");
                            if (preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+(.+)$/', $ln, $mm)){
                                $col = $mm[1];
                                $res['tables'][$currentTable]['columns'][strtolower($col)] = $ln;
                            }
                        }
                    }
                    $inCreate = false; $currentTable = ''; $buffer = '';
                }
                continue;
            }
            // Also parse ALTER TABLE ... ADD COLUMN ... statements present anywhere in the file
            if (!$inCreate){
                // Recognize ALTER TABLE [`db`.]`table` ADD COLUMN ...;
                if (preg_match('/^\s*ALTER\s+TABLE\s+(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?\s+ADD\s+COLUMN\s+(.*?);\s*$/i', $trim, $am)){
                    $t = $am[1];
                    // Normalize single column add; ignore complex multiple add with commas until ';'
                    $def = trim($am[2]);
                    // If the ALTER includes multiple clauses (", ADD KEY ..."), cut at first top-level comma
                    $defClean = $def; $paren=0; $inBack=false; $len = strlen($def);
                    for ($i=0; $i<$len; $i++){
                        $ch = $def[$i];
                        if ($ch==='`'){ $inBack = !$inBack; continue; }
                        if (!$inBack){
                            if ($ch==='('){ $paren++; }
                            elseif ($ch===')' && $paren>0){ $paren--; }
                            elseif ($ch===',' && $paren===0){ $defClean = trim(substr($def,0,$i)); break; }
                        }
                    }
                    $def = $defClean;
                    // Skip if what remains is a key/index/constraint fragment
                    if (preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY\s+|CONSTRAINT|FOREIGN\s+KEY|INDEX)\b/i', $def)){
                        continue;
                    }
                    // Extract column name from definition
                    if (preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+(.+)$/', $def, $cm)){
                        $col = strtolower($cm[1]);
                        if (!isset($res['tables'][$t])){ $res['tables'][$t] = ['columns'=>[], 'create_sql'=>'']; }
                        $res['tables'][$t]['columns'][$col] = $def;
                    }
                }
            }
        }
        fclose($fh);
        return $res;
    }

    // Clean up CREATE TABLE SQL: remove inline -- comments and trailing comma before closing ) and ensure semicolon
    private function normalize_create_sql($sql){
        $s = (string)$sql;
        // Normalize newlines
        $s = str_replace("\r", '', $s);
        // Remove block comments /* ... */ first
        $s = preg_replace('/\/\*.*?\*\//s', '', $s);
        // Remove inline comments that appear between comma-separated column defs: ", -- comment ... ," or ", -- comment ...)"
        // Keep the comma (separator) while stripping the comment chunk until next comma or closing parenthesis
        $s = preg_replace('/,\s*--.*?(?=,|\))/s', ',', $s);
        // Remove any remaining -- comments to end of line (safe when true line breaks exist)
        $s = preg_replace('/\s--.*$/m', '', $s);
        // Collapse multiple spaces
        $s = preg_replace('/[\t ]+/', ' ', $s);
        // Remove trailing comma before closing parenthesis inside the definition (repeat a few times just in case)
        for ($i=0; $i<3; $i++){
            $s = preg_replace('/,\s*\)/', ')', $s);
        }
        // Also trim commas before ENGINE or options if malformed
        $s = preg_replace('/,\s*(\)\s*ENGINE)/i', '$1', $s);
        // Fix malformed extra parenthesis after GENERATED columns, e.g., ") STORED) NOT NULL" -> ") STORED NOT NULL"
        $s = preg_replace('/\)\s*(STORED|VIRTUAL)\)\s+NOT\s+NULL/i', ') $1 NOT NULL', $s);
        // Replace invalid zero-date defaults that break under NO_ZERO_DATE/NO_ZERO_IN_DATE
        // e.g., `timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'` -> `timestamp NULL DEFAULT NULL`
        $s = preg_replace(
            '/\b(timestamp|datetime|date)\b\s+NOT\s+NULL\s+DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            '$1 NULL DEFAULT NULL',
            $s
        );
        // If any remaining DEFAULT '0000-...' without NOT NULL, change to DEFAULT NULL
        $s = preg_replace(
            '/DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            'DEFAULT NULL',
            $s
        );
        $s = trim($s);
        if ($s !== '' && substr(rtrim($s), -1) !== ';'){ $s .= ';'; }
        return $s;
    }

    // POST: file_path, database => compute missing tables/columns and propose SQL
    // AJAX: Scan file against DB and return differences
    public function compare_scan(){
        $file = (string)$this->input->post('file_path');
        $dbName = trim((string)$this->input->post('database'));
        $client_id = (int)$this->input->post('client_id');
        
        if ($file === '' || ($dbName === '' && !$client_id)){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'File path and target are required']); return;
        }

        try {
            $target = $this->resolve_connection($client_id, [
                'host' => $this->input->post('host'),
                'user' => $this->input->post('user'),
                'pass' => $this->input->post('pass'),
                'port' => $this->input->post('port'),
                'db'   => $dbName
            ]);
            $dbName = $target->database;
        } catch (Exception $e){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Connection Failed: '.$e->getMessage()]); return;
        }

        $schema = $this->parse_sql_schema($file);
        
        // Scan Target DB
        $tables = [];
        $tableNameMap = [];
        $tableTypeMap = []; 
        $q = $target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
        foreach ($q->result() as $r){ 
            $tables[strtolower($r->TABLE_NAME)] = true; 
            $tableNameMap[strtolower($r->TABLE_NAME)] = $r->TABLE_NAME; 
            $tableTypeMap[strtolower($r->TABLE_NAME)] = strtoupper((string)$r->TABLE_TYPE);
        }
        $cols = [];
        if (!empty($tables)){
            $rs = $target->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbName]);
            foreach ($rs->result() as $r){ 
                $t = strtolower($r->TABLE_NAME); 
                $c = strtolower($r->COLUMN_NAME); 
                if (!isset($cols[$t])) $cols[$t] = []; 
                $cols[$t][$c] = true; 
            }
        }

        $ops = [];
        // 1. Missing Tables
        foreach ($schema['tables'] as $tName => $meta){
            $tLower = strtolower($tName);
            if (!isset($tables[$tLower])){
                $rawSql = isset($meta['create_sql']) ? $meta['create_sql'] : '';
                if ($rawSql === ''){
                    $rawSql = $this->build_create_sql_from_columns($tName, $meta['columns']);
                }
                $rawSql = $this->normalize_create_sql($rawSql);
                $ops[] = ['type'=>'create_table', 'table'=>$tName, 'sql'=>$rawSql];
            } else {
                // 2. Missing Columns
                // Skip views
                if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
                
                $existingCols = isset($cols[$tLower]) ? $cols[$tLower] : [];
                foreach ($meta['columns'] as $cName => $def){
                    if (!isset($existingCols[strtolower($cName)])){
                        $defNorm = $this->normalize_column_def($def);
                        $sql = 'ALTER TABLE `'.$tName.'` ADD COLUMN '.$defNorm;
                        if (substr(trim($sql), -1) !== ';') $sql .= ';';
                        $ops[] = ['type'=>'add_column', 'table'=>$tName, 'column'=>$cName, 'sql'=>$sql];
                    }
                }
            }
        }
        
        // DB only 
        $dbOnlyTables = [];
        $dbOnlyColumns = [];
        $fileTablesLower = [];
        foreach ($schema['tables'] as $t => $meta){ $fileTablesLower[strtolower($t)] = $t; }
        
        foreach ($tables as $tLower => $_){
            if (!isset($fileTablesLower[$tLower])){
                 if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
                 $dbOnlyTables[] = isset($tableNameMap[$tLower]) ? $tableNameMap[$tLower] : $tLower;
            } else {
                 if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
                 $meta = isset($schema['tables'][$fileTablesLower[$tLower]]) ? $schema['tables'][$fileTablesLower[$tLower]] : null;
                 $fileCols = $meta ? array_keys($meta['columns']) : [];
                 $fileColsMap = []; foreach($fileCols as $fc) $fileColsMap[strtolower($fc)]=true;
                 $dbCols = isset($cols[$tLower]) ? array_keys($cols[$tLower]) : [];
                 foreach($dbCols as $dc){ 
                     if(!isset($fileColsMap[$dc])) {
                         $dbOnlyColumns[] = ['table' => (isset($tableNameMap[$tLower])?$tableNameMap[$tLower]:$fileTablesLower[$tLower]), 'column' => $dc]; 
                     }
                 }
            }
        }

        // Just return names for DB only, no SQL here to save bandwidth/time
        header('Content-Type: application/json');
        echo json_encode([
            'success'=>true, 
            'ops'=>$ops, 
            'database'=>$dbName, 
            'file_path'=>$file,
            'db_only' => ['tables'=>$dbOnlyTables, 'columns'=>$dbOnlyColumns] 
        ]);
        return;
    }

    private function compare_scan_internal($file, $target, $dbName){
        // Internal helper using connection object
        $schema = $this->parse_sql_schema($file);
        $tables = [];
        $tableTypeMap = [];
        $q = $target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
        foreach ($q->result() as $r){ 
            $tables[strtolower($r->TABLE_NAME)] = true; 
            $tableTypeMap[strtolower($r->TABLE_NAME)] = strtoupper((string)$r->TABLE_TYPE); 
        }
        $cols = [];
        if (!empty($tables)){
            $rs = $target->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbName]);
            foreach ($rs->result() as $r){ $t = strtolower($r->TABLE_NAME); $c = strtolower($r->COLUMN_NAME); if (!isset($cols[$t])) $cols[$t] = []; $cols[$t][$c] = true; }
        }
        $ops = [];
        foreach ($schema['tables'] as $tName => $meta){
            $tLower = strtolower($tName);
            if (!isset($tables[$tLower])){
                $rawSql = isset($meta['create_sql']) ? $meta['create_sql'] : $this->build_create_sql_from_columns($tName, $meta['columns']);
                $ops[] = ['type'=>'create_table', 'table'=>$tName, 'sql'=>$this->normalize_create_sql($rawSql)];
            } else {
                if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
                $existingCols = isset($cols[$tLower]) ? $cols[$tLower] : [];
                foreach ($meta['columns'] as $cName => $def){
                    if (!isset($existingCols[strtolower($cName)])){
                        $defNorm = $this->normalize_column_def($def);
                        $sql = 'ALTER TABLE `'.$tName.'` ADD COLUMN '.$defNorm.';';
                        $ops[] = ['type'=>'add_column', 'table'=>$tName, 'column'=>$cName, 'sql'=>$sql];
                    }
                }
            }
        }
        return ['success'=>true, 'ops'=>$ops];
    }

    public function compare_merge(){
        // PERMISSION CHECK for Destructive Action
        require_module_access(['db_admin', 'db'], true);

        if (!$this->verify_csrf()) {
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'CSRF Token Mismatch']); return;
        }

        $file = (string)$this->input->post('file_path');
        $dbName = trim((string)$this->input->post('database'));
        $client_id = (int)$this->input->post('client_id');
        
        if ($file === '' || ($dbName === '' && !$client_id)){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'File path and target are required']); return;
        }

        try {
             $target = $this->resolve_connection($client_id, [
                'host' => $this->input->post('host'),
                'user' => $this->input->post('user'),
                'pass' => $this->input->post('pass'),
                'port' => $this->input->post('port'),
                'db'   => $dbName
            ]);
            $dbName = $target->database;
        } catch (Exception $e){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Connection Failed: '.$e->getMessage()]); return;
        }

        $scan = $this->compare_scan_internal($file, $target, $dbName);
        if (empty($scan['ops'])){
            header('Content-Type: application/json'); echo json_encode(['success'=>true,'applied'=>0,'message'=>'No changes needed.']); return;
        }

        $target->trans_begin();
        $applied = 0;
        $details = [];
        try {
            foreach ($scan['ops'] as $op){
                $target->query($op['sql']);
                $applied++;
                $details[] = $op['sql'];
            }
            if ($target->trans_status() === FALSE){
                $target->trans_rollback();
                header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Transaction failed. DB rolled back.']); return;
            }
            $target->trans_commit();
        } catch (Exception $e){
             $target->trans_rollback();
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]); return;
        }

        // Log
        $client_name = (string)$this->input->post('client_name');
        $tables = 0; $columns = 0;
        foreach ($scan['ops'] as $op) { if ($op['type']=='create_table') $tables++; else $columns++; }
        
        $this->log_client_migration($client_id ?: null, $client_name, $dbName, 'migrate', $tables, $columns, $file, ['sql'=>$details]);

        header('Content-Type: application/json'); echo json_encode(['success'=>true,'applied'=>$applied]);
    }

    // UI
    public function index(){
        $projects = [];
        if ($this->db->table_exists('projects')) {
            $sel = 'id,name';
            if ($this->db->field_exists('db_name','projects')) { $sel .= ',db_name'; }
            $projects = $this->db->select($sel)->from('projects')->order_by('name','ASC')->get()->result();
        }
        // Assignees list (users, optionally via employees join)
        $assignees = [];
        if ($this->db->table_exists('users')) {
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                $sel = ['users.id','users.email'];
                if ($this->db->field_exists('name','employees')) { $sel[] = 'employees.name AS emp_name'; }
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'users.full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'users.name'; }
                $this->db->select(implode(',', $sel))
                         ->from('users')
                         ->join('employees','employees.user_id = users.id','left')
                         ->order_by('users.email','ASC');
                $assignees = $this->db->get()->result();
            } else {
                $sel = ['id','email'];
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
                $assignees = $this->db->select(implode(',', $sel))->from('users')->order_by('email','ASC')->get()->result();
            }
        }
        // Saved queries filters
        $current_db = '';
        $selected_table = '';
        $tables = [];
        $columns = [];
        $sample_rows = [];
        // Filters
        $filter_project_id = (int)$this->input->get('q_project_id');
        $filter_version = trim((string)$this->input->get('q_version'));
        $filter_assigned_to = $this->input->get('q_assigned_to') !== '' ? (int)$this->input->get('q_assigned_to') : null;
        $saved_queries = [];
        // Load saved queries from dm_manager with aliases expected by the view
        if ($this->db->table_exists('dm_manager') || true){
            $this->db->select("id, project_id, assign_id AS assigned_to, version, COALESCE(title, '') AS title, squary AS sql_text", false)
                     ->from($this->dm_table);
            if ($filter_project_id) { $this->db->where('project_id', $filter_project_id); }
            if ($filter_version !== '') { $this->db->where('version', $filter_version); }
            if ($filter_assigned_to !== null) { $this->db->where('assign_id', (int)$filter_assigned_to); }
            $this->db->order_by('id','DESC');
            $saved_queries = $this->db->get()->result();
        }
        // Determine default SQL file path dynamically
        $default_sql_path = '';
        $hint = (string)$this->input->get('sql_file_path');
        if ($hint !== '' && @is_file($hint)) { $default_sql_path = $hint; }
        if ($default_sql_path === ''){
            $candidates = @glob(FCPATH.'*.sql');
            if (is_array($candidates) && count($candidates) > 0){
                @usort($candidates, function($a,$b){
                    $ma = @filemtime($a); if ($ma === false) { $ma = 0; }
                    $mb = @filemtime($b); if ($mb === false) { $mb = 0; }
                    if ($mb == $ma) return 0;
                    return ($mb < $ma) ? -1 : 1;
                });
                $default_sql_path = $candidates[0];
            }
        }

        $clients = [];
        if ($this->db->table_exists('clients')) {
            $sel = ['id','company_name','db_name','db_username']; // Removed db_password
            if ($this->db->field_exists('pos_url','clients')) { $sel[] = 'pos_url'; }
            $this->db->select(implode(',', $sel));
            $clients = $this->db->from('clients')->order_by('company_name','ASC')->get()->result();
        }

        $client_migrations = [];
        if ($this->db->table_exists($this->client_migrations_table)){
             $client_migrations = $this->db->order_by('created_at','DESC')->limit(50)->get($this->client_migrations_table)->result();
        }
        
        // Generate CSRF Token if missing
        $csrf_token = $this->session->userdata('db_csrf_token');
        if (!$csrf_token){
             $csrf_token = bin2hex(openssl_random_pseudo_bytes(16));
             $this->session->set_userdata('db_csrf_token', $csrf_token);
        }

        $this->load->view('db/index', [
            'projects' => $projects,
            'assignees' => $assignees,
            'result' => $this->session->flashdata('db_result') ?: null,
            'error' => $this->session->flashdata('db_error') ?: null,
            'info' => $this->session->flashdata('db_info') ?: null,
            'current_db' => $current_db,
            'tables' => [],
            'selected_table' => '',
            'columns' => [],
            'sample_rows' => [],
            'filter_project_id' => $filter_project_id,
            'filter_version' => $filter_version,
            'filter_assigned_to' => $filter_assigned_to,
            'saved_queries' => $saved_queries,
            'new_id' => (int)$this->session->flashdata('db_new_id'),
            'sql_file_default' => $default_sql_path,
            'clients' => $clients,
            'client_migrations' => $client_migrations,
            'csrf_token' => $csrf_token,
        ]);
    }

    public function client_panel(){
        // Determine default SQL file path dynamically (same as compare)
        $default_sql_path = '';
        $hint = (string)$this->input->get('sql_file_path');
        if ($hint !== '' && @is_file($hint)) { $default_sql_path = $hint; }
        if ($default_sql_path === ''){
            $candidates = @glob(FCPATH.'*.sql');
            if (is_array($candidates) && count($candidates) > 0){
                @usort($candidates, function($a,$b){
                    $ma = @filemtime($a); if ($ma === false) { $ma = 0; }
                    $mb = @filemtime($b); if ($mb === false) { $mb = 0; }
                    if ($mb == $ma) return 0;
                    return ($mb < $ma) ? -1 : 1;
                });
                $default_sql_path = $candidates[0];
            }
        }
        $clients = [];
        if ($this->db->table_exists('clients')) {
            $sel = ['id','company_name','db_name','db_username']; // Removed db_password
            if ($this->db->field_exists('pos_url','clients')) { $sel[] = 'pos_url'; }
            $this->db->select(implode(',', $sel));
            $clients = $this->db->from('clients')->order_by('company_name','ASC')->get()->result();
        }
        $csrf_token = $this->session->userdata('db_csrf_token');
        if (!$csrf_token){
             $csrf_token = bin2hex(openssl_random_pseudo_bytes(16));
             $this->session->set_userdata('db_csrf_token', $csrf_token);
        }
        $this->load->view('db/client_panel', [
            'sql_file_default' => $default_sql_path,
            'clients' => $clients,
            'csrf_token' => $csrf_token,
        ]);
    }

    public function client_migrations(){
        $rows = [];
        // Filtering
        $client_id = $this->input->get('client_id');
        
        if ($this->db->table_exists($this->client_migrations_table)){
            $this->db->select('cm.*, u.name as actor_name', false);
            $this->db->from($this->client_migrations_table . ' cm');
            $this->db->join('users u', 'u.id = cm.run_by', 'left');
            if ($client_id){ $this->db->where('cm.client_id', (int)$client_id); }
            $rows = $this->db->order_by('cm.created_at','DESC')->limit(200)->get()->result();
        }
        
        $clients = $this->db->select('id,company_name')->from('clients')->get()->result(); // For filter dropdown if needed
        
        $this->load->view('db/client_migrations', [
            'migrations' => $rows,
            'clients' => $clients,
        ]);
    }

    // Full-screen Compare page
    public function compare(){
        // Determine default SQL file path dynamically (same as index)
        $default_sql_path = '';
        $hint = (string)$this->input->get('sql_file_path');
        if ($hint !== '' && @is_file($hint)) { $default_sql_path = $hint; }
        if ($default_sql_path === ''){
            $candidates = @glob(FCPATH.'*.sql');
            if (is_array($candidates) && count($candidates) > 0){
                @usort($candidates, function($a,$b){
                    $ma = @filemtime($a); if ($ma === false) { $ma = 0; }
                    $mb = @filemtime($b); if ($mb === false) { $mb = 0; }
                    if ($mb == $ma) return 0;
                    return ($mb < $ma) ? -1 : 1;
                });
                $default_sql_path = $candidates[0];
            }
        }
        $clients = [];
        if ($this->db->table_exists('clients')) {
            $sel = ['id','company_name','db_name','db_username']; // Removed db_password
            if ($this->db->field_exists('pos_url','clients')) { $sel[] = 'pos_url'; }
            $this->db->select(implode(',', $sel));
            $clients = $this->db->from('clients')->order_by('company_name','ASC')->get()->result();
        }
        $csrf_token = $this->session->userdata('db_csrf_token');
        if (!$csrf_token){
             $csrf_token = bin2hex(openssl_random_pseudo_bytes(16));
             $this->session->set_userdata('db_csrf_token', $csrf_token);
        }
        $this->load->view('db/compare', [
            'sql_file_default' => $default_sql_path,
            'clients' => $clients,
            'csrf_token' => $csrf_token,
        ]);
    }

    // Download the saved SQL as a .sql file
    public function export_saved_query($id){
        $id = (int)$id;
        $row = $this->db->from($this->dm_table)->where('id',$id)->get()->row();
        if (!$row){ show_404(); }
        $fname = 'query_'.(int)$row->id.'_'.date('Ymd_His').'.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="'.$fname.'"');
        $sql = $row->squary;
        echo trim((string)$sql)."\n";
        exit;
    }

    // AJAX: list queries for DataTables
    public function list_queries(){
        // Optional filters from GET
        $filter_project_id = (int)$this->input->get('q_project_id');
        $filter_version = trim((string)$this->input->get('q_version'));
        $filter_assigned_to = $this->input->get('q_assigned_to') !== '' ? (int)$this->input->get('q_assigned_to') : null;

        // Base select depending on table
        $rows = [];
        if (true){
            // Build select with optional revert metadata if columns exist
            $tblParts = explode('.', $this->dm_table);
            $baseTbl = end($tblParts);
            $selects = ['dm.id', 'dm.project_id', 'dm.assign_id', 'dm.version', 'dm.title', 'dm.squary'];
            if ($this->db->field_exists('file_path', $baseTbl)) { $selects[] = 'dm.file_path'; }
            if ($this->db->field_exists('backup_path', $baseTbl)) { $selects[] = 'dm.backup_path'; }
            $this->db->select(implode(', ', $selects), false)
                     ->from($this->dm_table.' dm');
            if ($filter_project_id) { $this->db->where('dm.project_id', $filter_project_id); }
            if ($filter_version !== '') { $this->db->where('dm.version', $filter_version); }
            if ($filter_assigned_to !== null) { $this->db->where('dm.assign_id', (int)$filter_assigned_to); }
            $this->db->order_by('dm.id','DESC');
            $rows = $this->db->get()->result();
        }

        // Optionally map project name
        $projNames = [];
        if ($this->db->table_exists('projects')){
            $pList = $this->db->select('id,name')->from('projects')->get()->result();
            foreach ($pList as $p){ $projNames[(int)$p->id] = $p->name; }
        }
        $data = [];
        foreach ($rows as $r){
            $id = (int)$r->id;
            $pid = isset($r->project_id) ? (int)$r->project_id : 0;
            $pname = isset($projNames[$pid]) ? $projNames[$pid] : '';
            $ver = isset($r->version) ? (string)$r->version : '';
            $title = isset($r->title) ? (string)$r->title : '';
            $sql = isset($r->squary) ? (string)$r->squary : '';
            if (function_exists('mb_strimwidth')) {
                $snippet = htmlspecialchars(mb_strimwidth($sql, 0, 300, '…', 'UTF-8'));
            } else {
                $snippet = htmlspecialchars(strlen($sql) > 300 ? substr($sql, 0, 300).'…' : $sql);
            }
            $sqlEsc = htmlspecialchars($sql, ENT_QUOTES, 'UTF-8');
            $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $verEsc = htmlspecialchars($ver, ENT_QUOTES, 'UTF-8');
            $assigned = isset($r->assign_id) ? (int)$r->assign_id : 0;
            $row = [];
            $row[] = '<input type="checkbox" class="rowSel" value="'.$id.'">';
            $row[] = $id;
            $row[] = htmlspecialchars($pname);
            $row[] = htmlspecialchars($ver);
            $row[] = htmlspecialchars($title);
            $row[] = '<pre class="sql-cell">'.$snippet.'</pre>';
            $actions = '<div class="btn-group btn-group-sm" role="group">'
                .'<button type="button" class="btn btn-outline-secondary btn-show" title="Show" aria-label="Show" data-id="'.$id.'" data-title="'.$titleEsc.'" data-version="'.$verEsc.'" data-project="'.$pid.'" data-sql="'.$sqlEsc.'"><i class="bi bi-eye"></i></button>'
                .'<a href="'.site_url('db/queries/export/'.$id).'" class="btn btn-outline-dark" title="Export" aria-label="Export"><i class="bi bi-download"></i></a>'
                .'<button type="button" class="btn btn-outline-primary btn-edit" title="Edit" aria-label="Edit" data-id="'.$id.'" data-title="'.$titleEsc.'" data-version="'.$verEsc.'" data-project="'.$pid.'" data-assigned="'.$assigned.'" data-sql="'.$sqlEsc.'"><i class="bi bi-pencil"></i></button>'
                .'<button type="button" class="btn btn-outline-success btn-copy" title="Copy" aria-label="Copy" data-sql="'.$sqlEsc.'"><i class="bi bi-clipboard"></i></button>'
                .'<button type="button" class="btn btn-outline-warning btn-revert" title="Revert" aria-label="Revert" data-id="'.$id.'"><i class="bi bi-arrow-counterclockwise"></i></button>'
                .'<a href="'.site_url('db/queries/delete/'.$id).'" class="btn btn-outline-danger" title="Delete" aria-label="Delete" onclick="return confirm(\'Delete this saved query?\')"><i class="bi bi-trash"></i></a>'
                .'</div>';
            $row[] = $actions;
            $data[] = $row;
        }
        $resp = [ 'data' => $data ];
        header('Content-Type: application/json');
        echo json_encode($resp);
        exit;
    }

    // Update a saved query (title, version, sql_text, project_id, assigned_to)
    public function update_query($id){
        $id = (int)$id;
        $row = $this->db->from($this->dm_table)->where('id',$id)->get()->row();
        if (!$row){ show_404(); }
        $data = [];
        foreach (['title','version','sql_text'] as $k){ $v = $this->input->post($k); if ($v !== null){ $data[$k] = trim((string)$v); } }
        if ($this->input->post('project_id') !== null){ $data['project_id'] = $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null; }
        if ($this->input->post('assigned_to') !== null){ $data['assigned_to'] = $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null; }
        if (!empty($data)){
            $mapped = [];
            if (isset($data['title'])) { $mapped['title'] = $data['title']; }
            if (isset($data['version'])) { $mapped['version'] = $data['version']; }
            if (isset($data['sql_text'])) { $mapped['squary'] = $data['sql_text']; }
            if (array_key_exists('project_id',$data)) { $mapped['project_id'] = $data['project_id']; }
            if (array_key_exists('assigned_to',$data)) { $mapped['assign_id'] = $data['assigned_to']; }
            $this->db->where('id',$id)->update($this->dm_table,$mapped);
            $this->session->set_flashdata('db_info','Query updated.');
        }
        redirect('db');
    }

    // Bulk export selected queries as one .sql
    public function export_bulk_saved_queries(){
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)) { show_error('No queries selected', 400); }
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) { show_error('No queries selected', 400); }

        $rows = $this->db->where_in('id', $ids)->order_by('id','ASC')->get($this->dm_table)->result();
        if (empty($rows)) { show_error('No queries found', 404); }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="queries_'.date('Ymd_His').'.sql"');
        foreach ($rows as $r){
            $title = isset($r->title) ? trim((string)$r->title) : '';
            $ver = isset($r->version) ? trim((string)$r->version) : '';
            $sql = (string)$r->squary;
            echo "-- #{$r->id}";
            if ($title !== '') { echo " | ".$title; }
            if ($ver !== '') { echo " | v".$ver; }
            echo "\n";
            echo trim($sql)."\n\n";
        }
        exit;
    }

    private function ensure_dm_manager_table(){
        $this->db->query("CREATE TABLE IF NOT EXISTS `dm_manager` (
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
        $tblParts = explode('.', $this->dm_table);
        $baseTbl = end($tblParts);
        if (!$this->db->field_exists('file_path', $baseTbl)){
            $this->db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `file_path` VARCHAR(500) NULL AFTER `squary`");
        }
        if (!$this->db->field_exists('backup_path', $baseTbl)){
            $this->db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `backup_path` VARCHAR(500) NULL AFTER `file_path`");
        }
        if (!$this->db->field_exists('database_name', $baseTbl)){
            $this->db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `database_name` VARCHAR(191) NULL AFTER `backup_path`");
        }
        if (!$this->db->field_exists('table_name', $baseTbl)){
            $this->db->query("ALTER TABLE `".$baseTbl."` ADD COLUMN `table_name` VARCHAR(191) NULL AFTER `database_name`");
        }
    }

    private function ensure_client_migrations_table(){
        $tbl = $this->client_migrations_table;
        $this->db->query("CREATE TABLE IF NOT EXISTS `".$tbl."` (
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
        if (!$this->db->field_exists('details', $tbl)){
            $this->db->query("ALTER TABLE `".$tbl."` ADD COLUMN `details` LONGTEXT NULL AFTER `file_path`");
        }
        if (!$this->db->field_exists('run_by', $tbl)){
            $this->db->query("ALTER TABLE `".$tbl."` ADD COLUMN `run_by` INT NULL AFTER `details`");
        }
    }

    private function log_client_migration($client_id, $client_name, $dbName, $action, $tables, $columns, $file, $details = null){
        if (!$this->db->table_exists($this->client_migrations_table)) return;
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
            'run_by' => (int)$this->session->userdata('user_id'),
        ];
        $this->db->insert($this->client_migrations_table, $data);
    }

    private function escape_ident($name){
        return str_replace('`','``',$name);
    }

    // Connect to a specific database using current credentials
    private function connect_to($database, $db_debug = false){
        $driver   = property_exists($this->db, 'dbdriver') ? $this->db->dbdriver : 'mysqli';
        $hostname = property_exists($this->db, 'hostname') ? $this->db->hostname : 'localhost';
        $username = property_exists($this->db, 'username') ? $this->db->username : 'root';
        $password = property_exists($this->db, 'password') ? $this->db->password : '';
        $char_set = property_exists($this->db, 'char_set') ? $this->db->char_set : 'utf8';
        $dbcollat = property_exists($this->db, 'dbcollat') ? $this->db->dbcollat : 'utf8_general_ci';
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
        return $this->load->database($params, TRUE);
    }

    /**
     * Connect using OMS config credentials and verify connection.
     */
    private function connect_to_verified($database, $context = '')
    {
        $prev_level = error_reporting(0);
        try {
            $db = $this->connect_to($database);
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

    // Save a query with project and version
    public function save_query(){
        $project_id = $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null;
        $assigned_to = $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null;
        $version = trim((string)$this->input->post('version'));
        $title = trim((string)$this->input->post('title'));
        $sql_text = trim((string)$this->input->post('sql_text'));
        $do_validate = (string)$this->input->post('validate_sql') === '1';
        if ($sql_text === ''){ $this->session->set_flashdata('db_error','Query is required.'); redirect('db'); return; }
        // Optional SQL validation against the project's database
        if ($do_validate && $project_id && $this->db->table_exists('projects') && $this->db->field_exists('db_name','projects')){
            $p = $this->db->select('db_name')->from('projects')->where('id', (int)$project_id)->get()->row();
            $db_name = ($p && !empty($p->db_name)) ? trim((string)$p->db_name) : '';
            if ($db_name !== ''){
                try {
                    $target = $this->connect_to($db_name);
                    $first = strtoupper(strtok(ltrim($sql_text), " \t\r\n"));
                    if ($first === 'SELECT' || $first === 'WITH'){
                        // Explain-only for read queries
                        $target->query('EXPLAIN '.$sql_text);
                    } else if (in_array($first, ['INSERT','UPDATE','DELETE'], true)){
                        // Transactional dry-run (rollback immediately)
                        $target->trans_begin();
                        $target->query($sql_text);
                        $target->trans_rollback();
                    } else {
                        // Skip validation for DDL or unsupported statements to avoid side effects
                    }
                } catch (Throwable $e){
                    $this->session->set_flashdata('db_error', 'Validation failed: '.$e->getMessage());
                    redirect('db'); return;
                }
            }
        }
        $new_id = 0;
        $this->db->insert($this->dm_table, [
            'project_id' => $project_id,
            'assign_id' => $assigned_to,
            'version' => $version !== '' ? $version : null,
            'title' => $title !== '' ? $title : null,
            'squary' => $sql_text,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if ($this->db->affected_rows() <= 0){
            $err = $this->db->error();
            $this->session->set_flashdata('db_error', 'Failed to save: '.(isset($err['message'])?$err['message']:'unknown DB error'));
            redirect('db'); return;
        }
        $new_id = (int)$this->db->insert_id();
        $this->session->set_flashdata('db_info','Query saved.');
        $this->session->set_flashdata('db_new_id', $new_id);
        redirect('db');
    }

    // Delete a saved query
    public function delete_query($id){
        $id = (int)$id;
        $this->db->where('id',$id)->delete($this->dm_table);
        $this->session->set_flashdata('db_info','Query deleted.');
        redirect('db');
    }

    public function file_tables(){
        $path = (string)$this->input->post('file_path');
        $resp = ['database'=>'','tables'=>[]];
        if ($path === '' || !is_file($path)){
            header('Content-Type: application/json'); echo json_encode($resp); exit;
        }
        $tables = [];
        $db = '';
        $fh = @fopen($path, 'r');
        if ($fh){
            while (!feof($fh)){
                $line = fgets($fh, 4096);
                if ($db === '' && preg_match('/^\s*--\s*Database:\s*`([^`]+)`/i', $line, $m)) { $db = $m[1]; }
                if (preg_match('/^\s*--\s*Table structure for table\s+`([^`]+)`/i', $line, $m)) { $tables[$m[1]] = true; continue; }
                if (preg_match('/^\s*CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $line, $m)) { $tables[$m[1]] = true; }
                if (count($tables) > 0 && $db !== '' && ftell($fh) > 1024*1024) { }
            }
            fclose($fh);
        }
        $resp['database'] = $db;
        $resp['tables'] = array_values(array_keys($tables));
        header('Content-Type: application/json'); echo json_encode($resp); exit;
    }

    public function append_to_sql_file(){
        $path = (string)$this->input->post('file_path');
        $db = trim((string)$this->input->post('database'));
        $table = trim((string)$this->input->post('table'));
        $sql = trim((string)$this->input->post('sql_text'));
        $create_new = (string)$this->input->post('create_new') === '1';
        // Optional metadata to save into dm_manager
        $project_id   = $this->input->post('project_id') !== null && $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null;
        $assigned_to  = $this->input->post('assigned_to') !== null && $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null;
        $version_tag  = $this->input->post('version') !== null ? trim((string)$this->input->post('version')) : '';
        $title_text   = $this->input->post('title') !== null ? trim((string)$this->input->post('title')) : '';
        $ok = false; $msg = ''; $new_dm_id = 0;
        if ($path !== '' && is_file($path) && $sql !== '' && $table !== ''){
            // Normalize proposed column lines
            $linesIn = preg_split('/\r?\n/', $sql);
            $colLines = [];
            $proposedNames = [];
            foreach ($linesIn as $ln){
                $ln = trim($ln);
                if ($ln === '') continue;
                // Skip comments and non-column directives
                if (preg_match('/^(--|#|\/\*)/',$ln)) continue;
                if (preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY\s+|CONSTRAINT|FOREIGN\s+KEY|INDEX)\b/i',$ln)) continue;
                if (preg_match('/^(CREATE|ALTER|DROP|INSERT|UPDATE|DELETE|SELECT|WITH|USE|SET|BEGIN|END)\b/i',$ln)) continue;
                if ($ln === ')' || $ln === ');') continue;
                // Remove trailing comma/semicolon for normalization; we add commas ourselves later
                $ln = rtrim($ln, ";, ");
                // Accept only valid column definition lines: identifier + type
                if (!preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+([A-Za-z]+(?:\s*\([^)]*\))?)/i', $ln, $mm)){
                    continue;
                }
                // Extract normalized column name
                $proposedNames[strtolower($mm[1])] = true;
                $colLines[] = '  '.$ln.','; // two-space indent + ensure comma
            }
            if (empty($colLines)){
                header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'No valid column lines provided.']); return; }

            // If creating a new table, ensure the table does not already exist in file and then append a CREATE TABLE block
            if ($create_new){
                $ifNotExists = (string)$this->input->post('if_not_exists') === '1';
                $engine = trim((string)$this->input->post('engine')) ?: 'InnoDB';
                $charset = trim((string)$this->input->post('charset')) ?: 'utf8mb4';
                // Scan for existing table
                $fhScan = @fopen($path,'r');
                if (!$fhScan){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to read file.']); return; }
                $exists = false;
                $headerRegex = '/^\s*--\s*Table structure for table\s+`'.preg_quote($table,'/').'`\s*$/i';
                $createRegex = '/^\s*CREATE\s+TABLE\s+`?'.preg_quote($table,'/').'`?\b/i';
                while (!feof($fhScan)){
                    $line = fgets($fhScan);
                    if ($line === false) break;
                    $trim = rtrim($line, "\r\n");
                    if (preg_match($headerRegex, $trim) || preg_match($createRegex, $trim)) { $exists = true; break; }
                }
                fclose($fhScan);
                if ($exists){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Table already exists in file.']); return; }

                // Build CREATE TABLE block using normalized column lines (last line without trailing comma)
                $insertHeader = "\n  -- Added by DB Manager on ".date('Y-m-d H:i:s')."\n";
                $colLinesCreate = $colLines;
                if (!empty($colLinesCreate)){
                    $last = array_pop($colLinesCreate);
                    $last = rtrim($last);
                    $last = rtrim($last, ", ");
                    $colLinesCreate[] = $last; // without trailing comma
                }
                $createSQL = "CREATE TABLE ".($ifNotExists?"IF NOT EXISTS ":"")."`".$table."` (\n".implode("\n", $colLinesCreate)."\n) ENGINE=".$engine." DEFAULT CHARSET=".$charset.";\n";
                $block  = "\n\n-- Database: ".($db!==''?"`$db`":"")." | New Table: ".($table!==''?"`$table`":"")." | ".date('Y-m-d H:i:s')."\n".$insertHeader.$createSQL;

                // Write to temp by copying original then appending block
                $fhIn = @fopen($path,'r'); if (!$fhIn){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to read file.']); return; }
                $dir = dirname($path);
                $tmp = $dir.DIRECTORY_SEPARATOR.'tmp_'.uniqid('sql_', true).'.sql';
                $fhOut = @fopen($tmp,'w'); if (!$fhOut){ fclose($fhIn); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to open temp file.']); return; }
                while (!feof($fhIn)){
                    $chunk = fread($fhIn, 8192);
                    if ($chunk === false) break;
                    fwrite($fhOut, $chunk);
                }
                fwrite($fhOut, $block);
                fclose($fhIn); fflush($fhOut); fclose($fhOut);

                // Backup and replace
                $backupPath = $path.'.bak_'.date('Ymd_His');
                @copy($path, $backupPath);
                $ok = @rename($tmp, $path);
                if (!$ok){ @unlink($tmp); $msg = 'Failed to replace original file.'; }
                if ($ok){
                    // Save full CREATE SQL in dm_manager
                    $data = [
                        'project_id' => $project_id,
                        'assign_id'  => $assigned_to,
                        'version'    => ($version_tag !== '' ? $version_tag : null),
                        'title'      => ($title_text !== '' ? $title_text : ('Create table `'.$table.'`')),
                        'squary'     => $createSQL,
                        'file_path'  => $path,
                        'backup_path'=> $backupPath,
                        'database_name' => ($db !== '' ? $db : null),
                        'table_name' => ($table !== '' ? $table : null),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->db->insert($this->dm_table, $data);
                    $new_dm_id = (int)$this->db->insert_id();
                }
                header('Content-Type: application/json'); echo json_encode(['success'=>$ok,'message'=>$msg,'new_id'=>$new_dm_id]); return;
            }

            // First pass: pre-scan to find existing columns and check duplicates
            $fh1 = @fopen($path,'r');
            if (!$fh1){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to read file.']); return; }
            $targetHeaderRegex = '/^\s*--\s*Table structure for table\s+`'.preg_quote($table, '/') .'`\s*$/i';
            $nextSectionRegex  = '/^\s*--\s*Table structure for table\s+`[^`]+`\s*$|^\s*--\s*Database:\s*`[^`]+`\s*$/i';
            $createTableRegex  = '/^\s*CREATE\s+TABLE\s+.*`'.preg_quote($table,'/').'`.*\(/i';
            $foundHeader=false; $inCreate=false; $existingCols = [];
            while (!feof($fh1)){
                $line = fgets($fh1); if ($line===false) break; $trim=rtrim($line,"\r\n");
                if (!$foundHeader && preg_match($targetHeaderRegex,$trim)){ $foundHeader=true; continue; }
                if ($foundHeader && preg_match($createTableRegex,$trim)){ $inCreate=true; continue; }
                if ($inCreate){
                    // Stop when constraints or closing ) reached
                    if (preg_match('/^\s*PRIMARY\s+KEY|^\s*UNIQUE\s+KEY|^\s*KEY\s+|^\s*CONSTRAINT|^\s*\)\s*/i',$trim)){
                        break;
                    }
                    if (preg_match('/^\s*`([^`]+)`\s+/',$trim,$mcol)){
                        $existingCols[strtolower($mcol[1])] = true;
                    }
                }
                if ($foundHeader && !$inCreate && preg_match($nextSectionRegex,$trim)){
                    // no CREATE found in this section
                    break;
                }
            }
            fclose($fh1);
            // Duplicate check
            $dups = [];
            foreach ($proposedNames as $nm => $_){ if (isset($existingCols[$nm])) $dups[] = $nm; }
            if (!empty($dups)){
                header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Duplicate column(s): '.implode(', ',$dups)]); return;
            }

            // Second pass: write with insertion
            $fh = @fopen($path, 'r');
            if (!$fh){ $msg = 'Failed to read file.'; header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
            $dir = dirname($path);
            $tmp = $dir.DIRECTORY_SEPARATOR.'tmp_'.uniqid('sql_', true).'.sql';
            $out = @fopen($tmp, 'w');
            if (!$out){ fclose($fh); $msg = 'Failed to open temp file.'; header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
            $insertHeader = "\n  -- Added by DB Manager on ".date('Y-m-d H:i:s')."\n";

            $foundHeader = false; $inserted = false; $prevWasHeader = false;
            $inTargetCreate = false; $prevLine = null; $withinTargetSection=false;

            while (!feof($fh)){
                $line = fgets($fh);
                if ($line === false) { break; }
                $trim = rtrim($line, "\r\n");

                // Detect the phpMyAdmin table section header
                if (preg_match($targetHeaderRegex, $trim)){
                    $foundHeader = true; $withinTargetSection = true; $prevWasHeader = true;
                }

                // Write header line
                if ($prevWasHeader){ fwrite($out, $line); $prevWasHeader = false; continue; }

                // Detect start of CREATE TABLE for the selected table
                if ($withinTargetSection && !$inTargetCreate && preg_match($createTableRegex, $trim)){
                    $inTargetCreate = true;
                    // We will delay writing by one line to be able to add comma if needed before ')'
                    $prevLine = $line; // hold the first line inside the block (CREATE ... line) to write later
                    continue;
                }

                if ($inTargetCreate){
                    // If we hit a boundary where columns/constraints end
                    $isBoundary = preg_match('/^\s*PRIMARY\s+KEY|^\s*UNIQUE\s+KEY|^\s*KEY\s+|^\s*CONSTRAINT|^\s*\)\s*/i', $trim) === 1;
                    if ($isBoundary && !$inserted){
                        // Ensure previous line ends with comma when boundary is ')' only (when there were no constraints)
                        $prevTrim = rtrim($prevLine, "\r\n");
                        if (preg_match('/^\s*\)\s*/', $trim)){
                            if (!preg_match('/,\s*$/', $prevTrim)){
                                $prevLine = rtrim($prevTrim).",\n"; // add comma and newline
                            }
                        }
                        // Write the line before boundary
                        fwrite($out, $prevLine);
                        // Insert our columns
                        fwrite($out, $insertHeader.implode("\n", $colLines)."\n");
                        $inserted = true;
                        // Now write the boundary line
                        fwrite($out, $line);
                        $prevLine = null;
                        continue;
                    }
                    // Normal flow inside create: write previous cached line and shift window
                    if ($prevLine !== null){ fwrite($out, $prevLine); }
                    $prevLine = $line;
                    continue;
                }

                // Outside target create: if we detect next section and didn't insert (in case header found but create not), insert before boundary
                if ($withinTargetSection && !$inserted && preg_match($nextSectionRegex, $trim)){
                    // Fallback if CREATE TABLE not found; insert only normalized column lines beneath section
                    $fallbackBlock  = "\n\n-- Database: ".($db!==''?"`$db`":"")." | Table: ".($table!==''?"`$table`":"")." | ".date('Y-m-d H:i:s')."\n".$insertHeader.implode("\n", $colLines)."\n";
                    fwrite($out, $fallbackBlock);
                    $inserted = true;
                    $withinTargetSection = false; // leaving section
                }

                // Default write
                fwrite($out, $line);
            }

            // Flush any pending prevLine
            if ($inTargetCreate && $prevLine !== null){
                // If we never encountered boundary, append columns before writing prevLine
                if (!$inserted){
                    fwrite($out, $insertHeader.implode("\n", $colLines)."\n");
                    $inserted = true;
                }
                fwrite($out, $prevLine);
            }

            // If table section not found at all, just append at end as fallback
            if (!$inserted){
                $tailBlock  = "\n\n-- Database: ".($db!==''?"`$db`":"")." | Table: ".($table!==''?"`$table`":"")." | ".date('Y-m-d H:i:s')."\n".$insertHeader.implode("\n", $colLines)."\n";
                fwrite($out, $tailBlock);
                $inserted = true;
            }

            fclose($fh); fflush($out); fclose($out);
            // Backup and replace
            $backupPath = $path.'.bak_'.date('Ymd_His');
            @copy($path, $backupPath);
            $ok = @rename($tmp, $path);
            if (!$ok){ @unlink($tmp); $msg = 'Failed to replace original file.'; }
            // If file append succeeded, store entry in dm_manager for grid display
            if ($ok){
                $savedSql = implode("\n", $colLines)."\n";
                // Ensure a non-empty title
                $final_title = ($title_text !== '' ? $title_text : ($create_new ? ('Create table `'.$table.'`') : ('Append columns to `'.$table.'`')));
                $data = [
                    'project_id' => $project_id,
                    'assign_id'  => $assigned_to,
                    'version'    => ($version_tag !== '' ? $version_tag : null),
                    'title'      => $final_title,
                    'squary'     => $savedSql,
                    'file_path'  => $path,
                    'backup_path'=> $backupPath,
                    'database_name' => ($db !== '' ? $db : null),
                    'table_name' => ($table !== '' ? $table : null),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $this->db->insert($this->dm_table, $data);
                $new_dm_id = (int)$this->db->insert_id();
            }
        } else {
            $msg = 'Invalid input.';
        }
        header('Content-Type: application/json'); echo json_encode(['success'=>$ok,'message'=>$msg,'new_id'=>$new_dm_id]); exit;
    }

    // Revert: restore .sql from backup and delete dm_manager entry
    public function revert_query($id){
        $id = (int)$id;
        $row = $this->db->from($this->dm_table)->where('id',$id)->get()->row();
        if (!$row){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Not found']); return; }
        $file = isset($row->file_path) ? (string)$row->file_path : '';
        $bak  = isset($row->backup_path) ? (string)$row->backup_path : '';
        if ($file === '' || $bak === '' || !is_file($bak)){
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Backup not available']); return;
        }
        // Attempt restore
        $ok = @copy($bak, $file);
        if (!$ok){ header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to restore file']); return; }
        // Delete entry
        $this->db->where('id',$id)->delete($this->dm_table);
        header('Content-Type: application/json'); echo json_encode(['success'=>true]); return;
    }

    // DB Difference: Compare two client databases
    public function db_difference(){
        $clients = [];
        if ($this->db->table_exists('clients')) {
            $sel = ['id', 'company_name', 'db_name', 'db_username'];
            if ($this->db->field_exists('pos_url', 'clients')) {
                $sel[] = 'pos_url';
            }
            if ($this->db->field_exists('db_host', 'clients')) {
                $sel[] = 'db_host';
            }
            if ($this->db->field_exists('db_port', 'clients')) {
                $sel[] = 'db_port';
            }
            $this->db->select(implode(',', $sel));
            $clients = $this->db->from('clients')
                                ->where('db_name IS NOT NULL')
                                ->where('db_name !=', '')
                                ->order_by('company_name','ASC')
                                ->get()->result();
        }
        
        $csrf_token = $this->session->userdata('db_csrf_token');
        if (!$csrf_token){
             $csrf_token = bin2hex(openssl_random_pseudo_bytes(16));
             $this->session->set_userdata('db_csrf_token', $csrf_token);
        }
        
        $this->load->view('db/db_difference', [
            'clients' => $clients,
            'csrf_token' => $csrf_token,
            'master_db' => $this->db->database,
            'module_tables' => $this->_schema_module_tables_safe(),
        ]);
    }

    /**
     * AJAX: Run all registered module schema bootstraps on the master DB.
     */
    public function ensure_schemas()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        if (!$this->verify_csrf()) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'CSRF Token Mismatch'));
            return;
        }
        $this->load->helper('schema_automation');
        $ran = oms_ensure_all_schemas();
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'modules' => $ran,
            'module_tables' => oms_schema_module_table_names(),
            'message' => count($ran) . ' module schema(s) ensured on ' . $this->db->database,
        ));
    }

    private function _schema_module_tables_safe()
    {
        $this->load->helper('schema_automation');
        return oms_schema_module_table_names();
    }

    /**
     * Build SQL column definition from information_schema row.
     *
     * @param object $cRow
     * @return string
     */
    private function build_column_sql_definition($cRow)
    {
        $defParts = array('`' . str_replace('`', '``', $cRow->COLUMN_NAME) . '`', $cRow->COLUMN_TYPE);
        if (!empty($cRow->CHARACTER_SET_NAME)) {
            $defParts[] = 'CHARACTER SET ' . $cRow->CHARACTER_SET_NAME;
        }
        if (!empty($cRow->COLLATION_NAME)) {
            $defParts[] = 'COLLATE ' . $cRow->COLLATION_NAME;
        }
        $defParts[] = (strtoupper($cRow->IS_NULLABLE) === 'YES') ? 'NULL' : 'NOT NULL';
        if (!is_null($cRow->COLUMN_DEFAULT)) {
            $def = trim((string) $cRow->COLUMN_DEFAULT);
            $defUpper = strtoupper($def);
            if ($defUpper === 'CURRENT_TIMESTAMP' || $defUpper === 'CURRENT_TIMESTAMP()') {
                $defParts[] = 'DEFAULT CURRENT_TIMESTAMP';
            } elseif (is_numeric($def)) {
                $defParts[] = 'DEFAULT ' . $def;
            } elseif ($defUpper === 'NULL') {
                $defParts[] = 'DEFAULT NULL';
            } else {
                $defParts[] = "DEFAULT '" . str_replace("'", "''", $def) . "'";
            }
        }
        $extra = trim((string) $cRow->EXTRA);
        if ($extra !== '') {
            $defParts[] = $extra;
        }
        return $this->normalize_column_def(implode(' ', array_filter($defParts)));
    }

    /**
     * Normalize CREATE TABLE statement for display or apply.
     *
     * @param string $sqlCreate
     * @param bool   $for_apply Use IF NOT EXISTS for safe re-run
     * @return string
     */
    private function format_create_table_sql($sqlCreate, $for_apply = false)
    {
        $sql = trim((string) $sqlCreate);
        $sql = rtrim($sql, ";\r\n ");
        if ($for_apply && stripos($sql, 'CREATE TABLE IF NOT EXISTS') === false) {
            $sql = preg_replace('/^CREATE\s+TABLE/i', 'CREATE TABLE IF NOT EXISTS', $sql, 1);
        }
        return $sql . ';';
    }

    /**
     * Strip comments and return one executable SQL statement.
     *
     * @param string $sql
     * @return string
     */
    private function sanitize_executable_sql($sql)
    {
        $sql = trim((string) $sql);
        if ($sql === '') {
            return '';
        }
        $sql = preg_replace('/--[^\r\n]*/', '', $sql);
        $sql = trim($sql);
        if ($sql === '') {
            return '';
        }
        if (substr($sql, -1) !== ';') {
            $sql .= ';';
        }
        return $sql;
    }

    /**
     * @param array $ops
     * @param bool  $for_apply
     * @return string
     */
    private function diff_ops_to_sql($ops, $for_apply = false)
    {
        $lines = array();
        foreach ($ops as $op) {
            if (!is_array($op)) {
                continue;
            }
            if (!$for_apply && !empty($op['comment'])) {
                $lines[] = '-- ' . $op['comment'];
            }
            if ($for_apply && !empty($op['apply_sql'])) {
                $lines[] = $op['apply_sql'];
            } elseif (!empty($op['sql'])) {
                $lines[] = $op['sql'];
            }
        }
        return implode("\n\n", $lines);
    }

    /**
     * Execute diff ops in safe order inside a transaction.
     *
     * @param object $conn
     * @param array  $ops
     * @return array
     */
    private function execute_diff_ops($conn, $ops)
    {
        $priority = array(
            'create_table' => 1,
            'add_column' => 2,
            'modify_column' => 3,
            'add_index' => 4,
        );
        usort($ops, function ($a, $b) use ($priority) {
            $ta = (is_array($a) && isset($a['type']) && isset($priority[$a['type']])) ? $priority[$a['type']] : 99;
            $tb = (is_array($b) && isset($b['type']) && isset($priority[$b['type']])) ? $priority[$b['type']] : 99;
            if ($ta === $tb) {
                return 0;
            }
            return ($ta < $tb) ? -1 : 1;
        });

        $conn->trans_begin();
        $applied = 0;
        $executed = array();
        $errors = array();

        foreach ($ops as $op) {
            if (!is_array($op)) {
                continue;
            }
            $raw = !empty($op['apply_sql']) ? $op['apply_sql'] : (isset($op['sql']) ? $op['sql'] : '');
            $sql = $this->sanitize_executable_sql($raw);
            if ($sql === '') {
                continue;
            }
            if (!$conn->query($sql)) {
                $err = $conn->error();
                $msg = is_array($err) && !empty($err['message']) ? $err['message'] : 'Query failed';
                $errors[] = $msg . ' | ' . $sql;
                $conn->trans_rollback();
                throw new Exception($msg);
            }
            $applied++;
            $executed[] = $sql;
        }

        if ($conn->trans_status() === false) {
            $conn->trans_rollback();
            throw new Exception('Database transaction failed.');
        }

        $conn->trans_commit();
        return array(
            'applied' => $applied,
            'executed' => $executed,
            'errors' => $errors,
        );
    }

    /**
     * Build SQL ops to sync structure from $src into $tgt (tables/columns/indexes in src missing from tgt).
     *
     * @param array  $src
     * @param array  $tgt
     * @param object $read_conn DB connection that can SHOW CREATE TABLE from source side
     * @return array
     */
    private function build_structure_diff($src, $tgt, $read_conn)
    {
        $ops = array();
        $missing_tables = array();
        $missing_columns = array();
        $changed_columns = array();
        $missing_indexes = array();

        foreach ($src['tables'] as $tLower => $tRow) {
            $tName = $tRow->TABLE_NAME;
            $safeTable = str_replace('`', '``', $tName);

            if (!isset($tgt['tables'][$tLower])) {
                $res = $read_conn->query('SHOW CREATE TABLE `' . $safeTable . '`');
                if ($res && $res->num_rows() > 0) {
                    $row = $res->row_array();
                    $sqlCreate = isset($row['Create Table']) ? $row['Create Table'] : (isset(array_values($row)[1]) ? array_values($row)[1] : '');
                    if ($sqlCreate) {
                        $missing_tables[] = $tName;
                        $ops[] = array(
                            'type' => 'create_table',
                            'table' => $tName,
                            'comment' => 'Create table `' . $tName . '`',
                            'sql' => $this->format_create_table_sql($sqlCreate, false),
                            'apply_sql' => $this->format_create_table_sql($sqlCreate, true),
                        );
                    }
                }
            } else {
                $sCols = isset($src['cols'][$tLower]) ? $src['cols'][$tLower] : array();
                $tCols = isset($tgt['cols'][$tLower]) ? $tgt['cols'][$tLower] : array();

                foreach ($sCols as $cLower => $cRow) {
                    $cName = $cRow->COLUMN_NAME;
                    $fullDef = $this->build_column_sql_definition($cRow);

                    if (!isset($tCols[$cLower])) {
                        $missing_columns[] = array('table' => $tName, 'column' => $cName);
                        $ops[] = array(
                            'type' => 'add_column',
                            'table' => $tName,
                            'column' => $cName,
                            'comment' => 'Add column `' . $tName . '`.`' . $cName . '`',
                            'sql' => 'ALTER TABLE `' . $safeTable . '` ADD COLUMN ' . $fullDef . ';',
                            'apply_sql' => 'ALTER TABLE `' . $safeTable . '` ADD COLUMN ' . $fullDef . ';',
                        );
                    } else {
                        $tcRow = $tCols[$cLower];
                        $diff = false;
                        if (strtolower($cRow->COLUMN_TYPE) !== strtolower($tcRow->COLUMN_TYPE)) {
                            $diff = true;
                        }
                        if (strtoupper($cRow->IS_NULLABLE) !== strtoupper($tcRow->IS_NULLABLE)) {
                            $diff = true;
                        }
                        if ($diff) {
                            $changed_columns[] = array(
                                'table' => $tName,
                                'column' => $cName,
                                'old' => $tcRow->COLUMN_TYPE,
                                'new' => $cRow->COLUMN_TYPE,
                            );
                            $ops[] = array(
                                'type' => 'modify_column',
                                'table' => $tName,
                                'column' => $cName,
                                'comment' => 'Modify column `' . $tName . '`.`' . $cName . '` (was ' . $tcRow->COLUMN_TYPE . ')',
                                'sql' => 'ALTER TABLE `' . $safeTable . '` MODIFY COLUMN ' . $fullDef . ';',
                                'apply_sql' => 'ALTER TABLE `' . $safeTable . '` MODIFY COLUMN ' . $fullDef . ';',
                            );
                        }
                    }
                }

                $sIdx = isset($src['indexes'][$tLower]) ? $src['indexes'][$tLower] : array();
                $tIdx = isset($tgt['indexes'][$tLower]) ? $tgt['indexes'][$tLower] : array();

                foreach ($sIdx as $igname => $igdata) {
                    if ($igname === 'PRIMARY') {
                        continue;
                    }
                    if (!isset($tIdx[$igname])) {
                        $cols = array_map(function ($c) {
                            return '`' . str_replace('`', '``', $c) . '`';
                        }, $igdata['columns']);
                        $type = ($igdata['type'] === 'FULLTEXT') ? 'FULLTEXT' : (($igdata['non_unique'] == 0) ? 'UNIQUE' : 'INDEX');
                        $safeIdx = str_replace('`', '``', $igname);
                        $indexSql = 'ALTER TABLE `' . $safeTable . '` ADD ' . $type . ' `' . $safeIdx . '` (' . implode(',', $cols) . ');';
                        $missing_indexes[] = array('table' => $tName, 'index' => $igname);
                        $ops[] = array(
                            'type' => 'add_index',
                            'table' => $tName,
                            'index' => $igname,
                            'comment' => 'Add index `' . $igname . '` on `' . $tName . '`',
                            'sql' => $indexSql,
                            'apply_sql' => $indexSql,
                        );
                    }
                }
            }
        }

        return array(
            'ops' => $ops,
            'missing_tables' => $missing_tables,
            'missing_columns' => $missing_columns,
            'changed_columns' => $changed_columns,
            'missing_indexes' => $missing_indexes,
            'sql' => $this->diff_ops_to_sql($ops, false),
            'apply_sql' => $this->diff_ops_to_sql($ops, true),
        );
    }

    private function resolve_compare_connection($client_id, $db_name, $is_master = false, $conn_options = array())
    {
        if ($is_master || ($client_id === 0 && $db_name === $this->db->database)) {
            return array($this->db, $this->db->database);
        }
        if ($client_id) {
            $conn = $this->resolve_client_connection($client_id, $conn_options);
            return array($conn, $conn->database);
        }
        if ($db_name === '') {
            throw new Exception('Database name is required.');
        }
        $conn = $this->connect_to($db_name);
        return array($conn, $db_name);
    }

    // AJAX: Compare two databases
    public function compare_databases(){
        if (ob_get_level()) {
            ob_clean();
        }
        if (!$this->verify_csrf()){
             header('HTTP/1.1 403 Forbidden'); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'CSRF Token Mismatch']); return;
        }

        $source_db = trim((string)$this->input->post('source_db'));
        $target_db = trim((string)$this->input->post('target_db'));
        $source_client_id = (int)$this->input->post('source_client_id');
        $target_client_id = (int)$this->input->post('target_client_id');
        $source_is_master = $this->input->post('source_is_master') === '1';
        $target_is_master = $this->input->post('target_is_master') === '1';
        $ensure_master = $this->input->post('ensure_master_schema') === '1';

        if ($ensure_master) {
            $this->load->helper('schema_automation');
            oms_ensure_all_schemas();
        }
        
        // Safety: Manual DB comparison restricted to Admins
        if (!$source_client_id && !$target_client_id && !$source_is_master && !$target_is_master && (!function_exists('is_admin_group') || !is_admin_group())){
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Manual DB comparison restricted to Admins.']); return;
        }

        try {
            $connOpts = $this->compare_connection_options_from_request();
            list($source_conn, $source_db) = $this->resolve_compare_connection($source_client_id, $source_db, $source_is_master, $connOpts);
            list($target_conn, $target_db) = $this->resolve_compare_connection($target_client_id, $target_db, $target_is_master, $connOpts);

            if ($source_db == $target_db){ throw new Exception("Source and Target are the same."); }

            // Common ignored tables
            $ignored_tables = ['ci_sessions', 'migrations', 'login_attempts', 'user_autologin', 'notifications'];

            // Structure Fetcher Helper
            $fetchStructure = function($conn, $dbname) use ($ignored_tables) {
                $tables = []; $cols = []; $indexes = [];
                
                // Tables
                $q = $conn->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbname]);
                if (!$q) return compact('tables','cols','indexes'); // Fail safe
                foreach ($q->result() as $r){
                    if (in_array($r->TABLE_NAME, $ignored_tables)) continue;
                    $tables[strtolower($r->TABLE_NAME)] = $r;
                }
                
                if (empty($tables)) return compact('tables','cols','indexes');

                // Columns
                $rs = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbname]);
                if ($rs){
                    foreach ($rs->result() as $r){
                        $t = strtolower($r->TABLE_NAME);
                        if (!isset($tables[$t])) continue;
                        $c = strtolower($r->COLUMN_NAME);
                        if (!isset($cols[$t])) $cols[$t] = [];
                        $cols[$t][$c] = $r;
                    }
                }

                // Indexes
                $ris = $conn->query("SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX", [$dbname]);
                if ($ris){
                    foreach ($ris->result() as $r){
                        $t = strtolower($r->TABLE_NAME);
                        if (!isset($tables[$t])) continue;
                        if (!isset($indexes[$t])) $indexes[$t] = [];
                        $idxName = $r->INDEX_NAME;
                        if (!isset($indexes[$t][$idxName])) $indexes[$t][$idxName] = ['non_unique'=>$r->NON_UNIQUE, 'columns'=>[], 'type'=>$r->INDEX_TYPE];
                        $indexes[$t][$idxName]['columns'][] = $r->COLUMN_NAME;
                    }
                }
                return compact('tables','cols','indexes');
            };

            $src = $fetchStructure($source_conn, $source_db);
            $tgt = $fetchStructure($target_conn, $target_db);

            $aToB = $this->build_structure_diff($src, $tgt, $source_conn);
            $bToA = $this->build_structure_diff($tgt, $src, $target_conn);

            $moduleTables = $this->_schema_module_tables_safe();
            $moduleMissingA = array();
            $moduleMissingB = array();
            foreach ($aToB['missing_tables'] as $t) {
                foreach ($moduleTables as $mt) {
                    if (strcasecmp($t, $mt) === 0) {
                        $moduleMissingA[] = $t;
                        break;
                    }
                }
            }
            foreach ($bToA['missing_tables'] as $t) {
                foreach ($moduleTables as $mt) {
                    if (strcasecmp($t, $mt) === 0) {
                        $moduleMissingB[] = $t;
                        break;
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'source_db' => $source_db,
                'target_db' => $target_db,
                'sql' => $aToB['sql'],
                'apply_sql' => $aToB['apply_sql'],
                'tables_sql' => $aToB['sql'],
                'statement_count' => count($aToB['ops']),
                'missing_tables' => $aToB['missing_tables'],
                'missing_columns' => $aToB['missing_columns'],
                'changed_columns' => $aToB['changed_columns'],
                'missing_indexes' => $aToB['missing_indexes'],
                'reverse_sql' => $bToA['sql'],
                'reverse_apply_sql' => $bToA['apply_sql'],
                'reverse_statement_count' => count($bToA['ops']),
                'reverse_missing_tables' => $bToA['missing_tables'],
                'reverse_missing_columns' => $bToA['missing_columns'],
                'module_tables_on_master' => $moduleTables,
                'module_missing_in_target' => $moduleMissingA,
                'module_missing_in_source' => $moduleMissingB,
                'columns_sql' => '',
            ));

        } catch(Exception $e) {
             header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); return;
        }
    }

    /**
     * AJAX: Test client database connection before compare/apply.
     */
    public function test_client_connection()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        if (!$this->verify_csrf()) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'CSRF Token Mismatch'));
            return;
        }
        $client_id = (int) $this->input->post('client_id');
        if (!$client_id) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Select a client.'));
            return;
        }
        try {
            $connOpts = $this->compare_connection_options_from_request();
            $conn = $this->resolve_client_connection($client_id, $connOpts);
            $dbName = $conn->database;
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'message' => 'Connected to "' . $dbName . '" on ' . (isset($conn->hostname) ? $conn->hostname : 'server') . '.',
                'database' => $dbName,
            ));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Create empty local WAMP database using the client's stored db_name.
     */
    public function create_local_client_database()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        if (!$this->verify_csrf()) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'CSRF Token Mismatch'));
            return;
        }
        $client_id = (int) $this->input->post('client_id');
        if (!$client_id) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Select a client.'));
            return;
        }
        $creds = $this->client_model->get_client_credentials($client_id);
        if (!$creds || empty($creds->db_name)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Client has no database name configured.'));
            return;
        }
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $creds->db_name);
        if ($dbName === '') {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Invalid database name.'));
            return;
        }
        try {
            $safe = str_replace('`', '``', $dbName);
            $this->db->query('CREATE DATABASE IF NOT EXISTS `' . $safe . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $this->connect_to_verified($dbName, 'local create for client #' . $client_id);
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'message' => 'Local database "' . $dbName . '" is ready on WAMP. Use Local WAMP mode and Test connection.',
                'database' => $dbName,
            ));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Save DB host/port from DB Difference onto the client record (live server setup).
     */
    public function save_client_db_host()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        if (!$this->verify_csrf()) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'CSRF Token Mismatch'));
            return;
        }
        $client_id = (int) $this->input->post('client_id');
        $host = trim((string) $this->input->post('db_host'));
        $port = trim((string) $this->input->post('db_port'));
        if (!$client_id) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Select a client.'));
            return;
        }
        if ($host === '' || $this->is_local_db_host($host)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Enter the remote MySQL hostname from cPanel (not localhost).'));
            return;
        }
        if (!$this->db->table_exists('clients')) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Clients table not found.'));
            return;
        }
        $this->ensure_client_db_fields();
        $data = array('db_host' => $host, 'updated_at' => date('Y-m-d H:i:s'));
        if ($this->db->field_exists('db_port', 'clients')) {
            $data['db_port'] = $port !== '' ? $port : null;
        }
        $ok = $this->client_model->update_client($client_id, $data);
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => (bool) $ok,
            'message' => $ok ? ('Saved DB host "' . $host . '" on client record.') : 'Failed to update client.',
            'db_host' => $host,
            'db_port' => $port,
        ));
    }

    /**
     * AJAX: Apply structure diff SQL to target database (re-computes diff server-side).
     */
    public function apply_database_diff()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        if (function_exists('has_module_access') && !has_module_access('db_admin') && !has_module_access('db')) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'You do not have permission to apply database changes.'));
            return;
        }
        if (!$this->verify_csrf()) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'CSRF Token Mismatch'));
            return;
        }

        $direction = strtolower(trim((string) $this->input->post('direction')));
        if (!in_array($direction, array('a_to_b', 'b_to_a'), true)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Invalid direction.'));
            return;
        }

        $source_db = trim((string) $this->input->post('source_db'));
        $target_db = trim((string) $this->input->post('target_db'));
        $source_client_id = (int) $this->input->post('source_client_id');
        $target_client_id = (int) $this->input->post('target_client_id');
        $source_is_master = $this->input->post('source_is_master') === '1';
        $target_is_master = $this->input->post('target_is_master') === '1';
        $ensure_master = $this->input->post('ensure_master_schema') === '1';

        if ($ensure_master) {
            $this->load->helper('schema_automation');
            oms_ensure_all_schemas();
        }

        try {
            $connOpts = $this->compare_connection_options_from_request();
            list($source_conn, $source_db) = $this->resolve_compare_connection($source_client_id, $source_db, $source_is_master, $connOpts);
            list($target_conn, $target_db) = $this->resolve_compare_connection($target_client_id, $target_db, $target_is_master, $connOpts);

            if ($source_db === $target_db) {
                throw new Exception('Source and Target are the same.');
            }

            $ignored_tables = array('ci_sessions', 'migrations', 'login_attempts', 'user_autologin', 'notifications');
            $fetchStructure = function ($conn, $dbname) use ($ignored_tables) {
                $tables = array();
                $cols = array();
                $indexes = array();
                $q = $conn->query('SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', array($dbname));
                if (!$q) {
                    return compact('tables', 'cols', 'indexes');
                }
                foreach ($q->result() as $r) {
                    if (in_array($r->TABLE_NAME, $ignored_tables, true)) {
                        continue;
                    }
                    $tables[strtolower($r->TABLE_NAME)] = $r;
                }
                if (empty($tables)) {
                    return compact('tables', 'cols', 'indexes');
                }
                $rs = $conn->query('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?', array($dbname));
                if ($rs) {
                    foreach ($rs->result() as $r) {
                        $t = strtolower($r->TABLE_NAME);
                        if (!isset($tables[$t])) {
                            continue;
                        }
                        $c = strtolower($r->COLUMN_NAME);
                        if (!isset($cols[$t])) {
                            $cols[$t] = array();
                        }
                        $cols[$t][$c] = $r;
                    }
                }
                $ris = $conn->query('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX', array($dbname));
                if ($ris) {
                    foreach ($ris->result() as $r) {
                        $t = strtolower($r->TABLE_NAME);
                        if (!isset($tables[$t])) {
                            continue;
                        }
                        if (!isset($indexes[$t])) {
                            $indexes[$t] = array();
                        }
                        $idxName = $r->INDEX_NAME;
                        if (!isset($indexes[$t][$idxName])) {
                            $indexes[$t][$idxName] = array('non_unique' => $r->NON_UNIQUE, 'columns' => array(), 'type' => $r->INDEX_TYPE);
                        }
                        $indexes[$t][$idxName]['columns'][] = $r->COLUMN_NAME;
                    }
                }
                return compact('tables', 'cols', 'indexes');
            };

            $srcStruct = $fetchStructure($source_conn, $source_db);
            $tgtStruct = $fetchStructure($target_conn, $target_db);

            if ($direction === 'a_to_b') {
                $diff = $this->build_structure_diff($srcStruct, $tgtStruct, $source_conn);
                $applyConn = $target_conn;
                $applyDb = $target_db;
                $fromDb = $source_db;
            } else {
                $diff = $this->build_structure_diff($tgtStruct, $srcStruct, $target_conn);
                $applyConn = $source_conn;
                $applyDb = $source_db;
                $fromDb = $target_db;
            }

            if (empty($diff['ops'])) {
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => true,
                    'applied' => 0,
                    'message' => 'No SQL changes needed — schemas already match.',
                ));
                return;
            }

            $result = $this->execute_diff_ops($applyConn, $diff['ops']);

            $tables = 0;
            $columns = 0;
            foreach ($diff['ops'] as $op) {
                if (!is_array($op) || empty($op['type'])) {
                    continue;
                }
                if ($op['type'] === 'create_table') {
                    $tables++;
                } elseif (in_array($op['type'], array('add_column', 'modify_column', 'add_index'), true)) {
                    $columns++;
                }
            }

            $client_id = ($direction === 'a_to_b') ? $target_client_id : $source_client_id;
            $client_name = trim((string) $this->input->post('target_client_name'));
            if ($direction === 'b_to_a') {
                $client_name = trim((string) $this->input->post('source_client_name'));
            }
            $this->log_client_migration(
                $client_id ? $client_id : null,
                $client_name !== '' ? $client_name : $applyDb,
                $applyDb,
                'migrate',
                $tables,
                $columns,
                'db/difference:' . $direction,
                array('sql' => $result['executed'], 'from_db' => $fromDb)
            );

            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'applied' => (int) $result['applied'],
                'target_db' => $applyDb,
                'from_db' => $fromDb,
                'direction' => $direction,
                'message' => (int) $result['applied'] . ' SQL statement(s) applied to ' . $applyDb,
                'executed' => $result['executed'],
            ));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

}
