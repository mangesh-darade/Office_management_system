<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirements extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session','upload']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        if (!function_exists('has_module_access') || !has_module_access('requirements')) { show_error('Access Denied', 403); }
        $this->ensure_schema();
        $this->load->model(['Requirement_model'=>'requirements','Client_model'=>'clients']);
    }

    private function ensure_schema(){
        if (!$this->db->table_exists('requirements')){
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
            $this->db->query($sql);
        }
        // Add missing columns when table already exists
        if ($this->db->table_exists('requirements')){
            $fields = $this->db->list_fields('requirements');
            if (!in_array('owner_id', $fields, true)) { $this->db->query("ALTER TABLE `requirements` ADD `owner_id` INT(11) NULL AFTER `received_date`"); }
        }
        if (!$this->db->table_exists('requirement_attachments')){
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
            $this->db->query($sql2);
        }
        if (!$this->db->table_exists('requirement_versions')){
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
            $this->db->query($sql3);
        }
        // Add missing columns to versions as well
        if ($this->db->table_exists('requirement_versions')){
            $vfields = $this->db->list_fields('requirement_versions');
            if (!in_array('owner_id', $vfields, true)) { $this->db->query("ALTER TABLE `requirement_versions` ADD `owner_id` INT(11) NULL AFTER `received_date`"); }
        }
    }

    // GET /requirements
    public function index(){
        $filters = [
            'status' => $this->input->get('status'),
            'priority' => $this->input->get('priority'),
            'client_id' => $this->input->get('client_id'),
            'assigned_to' => $this->input->get('assigned_to'),
            'search' => $this->input->get('q'),
        ];
        $rows = $this->requirements->get_requirements($filters, null, 0);
        $clients = $this->requirements->get_clients_for_filter();
        $members = $this->requirements->get_team_members();
        $this->load->view('requirements/index', [
            'rows'=>$rows,
            'filters'=>$filters,
            'clients'=>$clients,
            'members'=>$members,
        ]);
    }

    // GET/POST /requirements/create
    public function create(){
        if ($this->input->method() === 'post'){
            $owner_raw = $this->input->post('owner_id');
            if ($owner_raw === '' || $owner_raw === null) {
                $this->session->set_flashdata('error', 'Owner is required.');
                redirect('requirements/create');
                return;
            }
            $data = [
                'req_number' => $this->generate_req_number(),
                'client_id' => (int)$this->input->post('client_id'),
                'project_id' => $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null,
                'title' => trim($this->input->post('title')),
                'description' => $this->input->post('description'),
                'requirement_type' => $this->input->post('requirement_type') ?: 'new_feature',
                'priority' => $this->input->post('priority') ?: 'medium',
                'status' => $this->input->post('status') ?: 'received',
                'budget_estimate' => $this->input->post('budget_estimate') !== '' ? (float)$this->input->post('budget_estimate') : null,
                'expected_delivery_date' => $this->input->post('expected_delivery_date') ?: null,
                'received_date' => $this->input->post('received_date') ?: date('Y-m-d'),
                'owner_id' => (int)$owner_raw,
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'created_by' => (int)$this->session->userdata('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $id = $this->requirements->create_requirement($data);
            // create initial version 1
            $this->requirements->create_version($id, 1, $data);
            // attachments (optional)
            if (!empty($_FILES['attachments']['name'][0])){
                $upload_path = FCPATH.'uploads/requirements/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
                $config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'pdf|doc|docx|xls|xlsx|ppt|pptx|jpg|jpeg|png|gif|zip',
                    'max_size' => 10240,
                    'encrypt_name' => true,
                ];
                $this->upload->initialize($config);
                $count = count($_FILES['attachments']['name']);
                for ($i=0; $i<$count; $i++){
                    $_FILES['file']['name'] = $_FILES['attachments']['name'][$i];
                    $_FILES['file']['type'] = $_FILES['attachments']['type'][$i];
                    $_FILES['file']['tmp_name'] = $_FILES['attachments']['tmp_name'][$i];
                    $_FILES['file']['error'] = $_FILES['attachments']['error'][$i];
                    $_FILES['file']['size'] = $_FILES['attachments']['size'][$i];
                    if ($this->upload->do_upload('file')){
                        $up = $this->upload->data();
                        $this->requirements->add_attachment([
                            'requirement_id' => (int)$id,
                            'file_name' => $up['file_name'],
                            'original_name' => $_FILES['file']['name'],
                            'file_path' => 'uploads/requirements/'.$up['file_name'],
                            'file_size' => $up['file_size'],
                            'file_type' => $up['file_type'],
                            'uploaded_by' => (int)$this->session->userdata('user_id'),
                            'uploaded_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
            $this->session->set_flashdata('success','Requirement created');
            redirect('requirements/view/'.$id);
            return;
        }
        $clients = $this->clients->get_clients([], null, 0);
        $members = $this->requirements->get_team_members();
        $projects = [];
        if ($this->db->table_exists('projects')) { $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result(); }
        $this->load->view('requirements/create', ['clients'=>$clients,'members'=>$members,'projects'=>$projects]);
    }

    // GET/POST /requirements/edit/{id}
    public function edit($id){
        $row = $this->requirements->get_requirement((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $owner_raw = $this->input->post('owner_id');
            if ($owner_raw === '' || $owner_raw === null) {
                $this->session->set_flashdata('error', 'Owner is required.');
                redirect('requirements/edit/'.$id);
                return;
            }
            $data = [
                'client_id' => (int)$this->input->post('client_id'),
                'project_id' => $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null,
                'title' => trim($this->input->post('title')),
                'description' => $this->input->post('description'),
                'requirement_type' => $this->input->post('requirement_type') ?: $row->requirement_type,
                'priority' => $this->input->post('priority') ?: $row->priority,
                'status' => $this->input->post('status') ?: $row->status,
                'budget_estimate' => $this->input->post('budget_estimate') !== '' ? (float)$this->input->post('budget_estimate') : null,
                'expected_delivery_date' => $this->input->post('expected_delivery_date') ?: null,
                'received_date' => $this->input->post('received_date') ?: $row->received_date,
                'owner_id' => $this->input->post('owner_id') !== '' ? (int)$this->input->post('owner_id') : null,
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->requirements->update_requirement((int)$id, $data);
            // compute next version number
            $nextVer = $this->requirements->next_version_no((int)$id);
            $verData = $data;
            $verData['created_by'] = (int)$this->session->userdata('user_id');
            $verData['created_at'] = date('Y-m-d H:i:s');
            $this->requirements->create_version((int)$id, $nextVer, $verData);
            $this->session->set_flashdata('success','Requirement updated');
            redirect('requirements/view/'.$id);
            return;
        }
        $clients = $this->clients->get_clients([], null, 0);
        $members = $this->requirements->get_team_members();
        $projects = [];
        if ($this->db->table_exists('projects')) { $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result(); }
        $this->load->view('requirements/edit', ['row'=>$row,'clients'=>$clients,'members'=>$members,'projects'=>$projects]);
    }

    // GET /requirements/view/{id}
    public function view($id){
        $req = $this->requirements->get_requirement((int)$id);
        if (!$req) { show_404(); }
        $attachments = $this->requirements->get_attachments((int)$id);
        $type = $this->input->get('type');
        $versions = $this->requirements->get_versions((int)$id, $type);
        $this->load->view('requirements/view', [
            'req'=>$req,
            'attachments'=>$attachments,
            'versions'=>$versions,
            'type_filter'=>$type
        ]);
    }

    // GET /requirements/version/{versionId}
    public function version($versionId){
        $ver = $this->requirements->get_version_by_id((int)$versionId);
        if (!$ver) { show_404(); }
        $req = $this->requirements->get_requirement((int)$ver->requirement_id);
        $prev = $this->requirements->get_previous_version((int)$ver->requirement_id, (int)$ver->version_no);
        $this->load->view('requirements/version', [
            'req' => $req,
            'ver' => $ver,
            'prev' => $prev
        ]);
    }

    private function generate_req_number(){
        $year = date('Y');
        $prefix = 'REQ-'.$year.'-';
        $row = $this->db->like('req_number',$prefix,'after')->order_by('id','DESC')->limit(1)->get('requirements')->row();
        $num = 0;
        if ($row && isset($row->req_number)){
            $tail = substr($row->req_number, -5);
            if (ctype_digit($tail)) { $num = (int)$tail; }
        }
        $num++;
        return $prefix.str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    // GET /requirements/board
    public function board(){
        $columns = array('received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
        $data = array();
        foreach ($columns as $st){
            $data[$st] = $this->requirements->get_requirements(array('status'=>$st), null, 0);
        }
        $this->load->view('requirements/board', array('columns'=>$data));
    }

    // GET /requirements/calendar
    public function calendar(){
        $rows = $this->requirements->get_requirements(array(), null, 0);
        $this->load->view('requirements/calendar', array('rows'=>$rows));
    }

    // GET /requirements/export
    public function export(){
        $filters = array(
            'status' => $this->input->get('status'),
            'priority' => $this->input->get('priority'),
            'client_id' => $this->input->get('client_id'),
        );
        $rows = $this->requirements->get_requirements($filters, null, 0);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="requirements_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Req Number','Client','Title','Priority','Status','Expected','Assigned To'));
        foreach ($rows as $r){
            $assigned = isset($r->assigned_to_name) ? $r->assigned_to_name : '';
            $client = isset($r->client_name) ? $r->client_name : '';
            $expected = isset($r->expected_delivery_date) ? $r->expected_delivery_date : '';
            fputcsv($out, array(isset($r->req_number)?$r->req_number:'', $client, $r->title, isset($r->priority)?$r->priority:'', isset($r->status)?$r->status:'', $expected, $assigned));
        }
        fclose($out);
        exit;
    }
}
