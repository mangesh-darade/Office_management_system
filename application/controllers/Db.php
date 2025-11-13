<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Db extends CI_Controller {
    private $dm_table = 'employmanagement.dm_manager';
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
            $this->db->select('dm.id, dm.project_id, dm.assign_id, dm.version, dm.title, dm.squary', false)
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

}
