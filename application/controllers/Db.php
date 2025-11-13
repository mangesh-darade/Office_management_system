<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Db extends CI_Controller {
    private $dm_table = 'dm_manager';
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->ensure_dm_manager_table();
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
                    return ($mb < $ma) ? -1 : 1; // sort desc by mtime
                });
                $default_sql_path = $candidates[0];
            }
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

    private function escape_ident($name){
        return str_replace('`','``',$name);
    }

    // Connect to a specific database using current credentials
    private function connect_to($database){
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
            'db_debug' => (ENVIRONMENT !== 'production'),
            'cache_on' => FALSE,
            'cachedir' => '',
            'save_queries' => TRUE,
        ];
        return $this->load->database($params, TRUE);
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

}
