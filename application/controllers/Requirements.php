<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirements extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','hierarchy_filter','schema_columns','types','validation']);
        $this->load->model('Type_model', 'module_types');
        $this->load->library(['session','upload']);
        
        // RBAC Audit: Centralized module access check
        require_controller_access('requirements', true);
        
        $this->ensure_schema();
        $this->load->model(['Requirement_model'=>'requirements','Client_model'=>'clients']);
    }

    private function ensure_schema() {
        $this->load->helper('requirements_schema');
        requirements_schema_ensure($this->db);
    }

    private function _requirement_type_options()
    {
        return module_type_options_resolved('requirements');
    }

    private function _resolve_requirement_type($posted, $fallback = 'new_feature')
    {
        $type = module_type_validate_code($posted, 'requirements', false, $fallback);
        return $type === false ? $fallback : $type;
    }

    // GET /requirements
    public function index(){
        require_module_access(['requirements_list', 'requirements'], true);
        $filters = [
            'status' => $this->input->get('status'),
            'priority' => $this->input->get('priority'),
            'requirement_type' => $this->input->get('requirement_type'),
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
            'requirement_types'=>$this->_requirement_type_options(),
        ]);
    }

    // GET/POST /requirements/create
    public function create(){
        require_module_access(['requirements_add', 'requirements'], true);
        if ($this->input->method() === 'post'){
            $owner_raw = $this->input->post('owner_id');
            if ($owner_raw === '' || $owner_raw === null) {
                $this->session->set_flashdata('error', 'Owner is required.');
                redirect('requirements/create');
                return;
            }
            $received_date = $this->input->post('received_date') ?: date('Y-m-d');
            $expected_delivery_date = $this->input->post('expected_delivery_date') ?: null;
            
            // Server-side date validation
            if ($received_date && $expected_delivery_date && $expected_delivery_date < $received_date) {
                $this->session->set_flashdata('error', 'Expected delivery date must be on or after received date.');
                redirect('requirements/create');
                return;
            }

            $reference_url = normalize_optional_url($this->input->post('reference_url'));
            if ($reference_url === false) {
                $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                redirect('requirements/create');
                return;
            }
            
            $data = [
                'req_number' => $this->generate_req_number(),
                'client_id' => (int)$this->input->post('client_id'),
                'project_id' => $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null,
                'title' => trim($this->input->post('title')),
                'description' => $this->input->post('description'),
                'requirement_type' => $this->_resolve_requirement_type($this->input->post('requirement_type')),
                'priority' => $this->input->post('priority') ?: 'medium',
                'status' => $this->input->post('status') ?: 'received',
                'expected_delivery_date' => $expected_delivery_date,
                'received_date' => $received_date,
                'owner_id' => (int)$owner_raw,
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'created_by' => (int)$this->session->userdata('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'reference_url' => $reference_url,
            ];
            $id = $this->requirements->create_requirement($data);
            // create initial version 1
            $this->requirements->create_version($id, 1, $data);
            
            // Send notification to owner and assigned_to when requirement is created (both in-app and email)
            $notify_users = [];
            if (isset($data['owner_id']) && (int)$data['owner_id'] > 0) {
                $notify_users[] = (int)$data['owner_id'];
            }
            if (isset($data['assigned_to']) && (int)$data['assigned_to'] > 0 && !in_array((int)$data['assigned_to'], $notify_users)) {
                $notify_users[] = (int)$data['assigned_to'];
            }
            
            if (!empty($notify_users)) {
                $req_number = isset($data['req_number']) ? $data['req_number'] : 'REQ-'.date('Y').'-'.str_pad($id, 5, '0', STR_PAD_LEFT);
                $req_title = isset($data['title']) ? mb_substr($data['title'], 0, 50) : 'New Requirement';
                $view_url = site_url('requirements/view/'.$id);
                
                // Load Reminder model for email notifications
                $this->load->model('Reminder_model', 'reminders');
                $this->reminders->ensure_schema();
                
                foreach ($notify_users as $uid) {
                    // Get user email
                    $user_email = '';
                    $user_name = '';
                    if ($this->db->table_exists('users')) {
                        $sel = ['email'];
                        if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'full_name'; }
                        if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'name'; }
                        if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')) { 
                            $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; 
                        }
                        $u = $this->db->select(implode(',', $sel), false)->from('users')->where('id', (int)$uid)->get()->row();
                        if ($u) {
                            $user_email = isset($u->email) ? $u->email : '';
                            if (isset($u->full_label) && $u->full_label !== '') { $user_name = $u->full_label; }
                            else if (isset($u->full_name) && $u->full_name !== '') { $user_name = $u->full_name; }
                            else if (isset($u->name) && $u->name !== '') { $user_name = $u->name; }
                            else { $user_name = $user_email; }
                        }
                    }
                    
                    // In-app notification
                    $this->load->model('Notification_model');
                    $this->Notification_model->create($uid, 'New requirement assigned: '.$req_number, 'You have been assigned to requirement: '.$req_title, 'info', 'requirements');
                    
                    // Email notification (queue via Reminder system)
                    if ($user_email !== '') {
                        $email_subject = 'New Requirement Assigned: '.$req_number;
                        $email_body = "Hello ".$user_name.",\n\n";
                        $email_body .= "You have been assigned to a new requirement:\n\n";
                        $email_body .= "Requirement Number: ".$req_number."\n";
                        $email_body .= "Title: ".$req_title."\n";
                        if (isset($data['priority'])) {
                            $email_body .= "Priority: ".ucfirst($data['priority'])."\n";
                        }
                        if (isset($data['expected_delivery_date'])) {
                            $email_body .= "Expected Delivery: ".$data['expected_delivery_date']."\n";
                        }
                        $email_body .= "\nView requirement: ".$view_url."\n\n";
                        $email_body .= "Thank you.";
                        
                        $this->reminders->enqueue([
                            'user_id' => $uid,
                            'email' => $user_email,
                            'type' => 'requirement_assigned',
                            'subject' => $email_subject,
                            'body' => $email_body,
                            'send_at' => date('Y-m-d H:i:00') // Send immediately (or queue for cron)
                        ]);
                    }
                }
            }
            // attachments (optional)
            if (!empty($_FILES['attachments']['name'][0])){
                $upload_path = FCPATH.'uploads/requirements/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
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
        if ($this->db->table_exists('projects')) {
            $this->db->select('id,name')->from('projects');
            if (schema_table_has_column($this->db, 'projects', 'created_by')) {
                apply_role_hierarchy_filter($this->db, 'created_by');
            } else if (schema_table_has_column($this->db, 'projects', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'manager_id');
            }
            $projects = $this->db->order_by('name','ASC')->get()->result();
        }
        
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('requirements', true);
        
        $this->load->view('requirements/create', [
            'clients'=>$clients,
            'members'=>$members,
            'projects'=>$projects,
            'statuses'=>$statuses_list,
            'requirement_types'=>$this->_requirement_type_options(),
        ]);
    }

    // GET/POST /requirements/edit/{id}
    public function edit($id){
        require_module_access(['requirements_edit', 'requirements'], true);
        $row = $this->requirements->get_requirement((int)$id);
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $owner_raw = $this->input->post('owner_id');
            if ($owner_raw === '' || $owner_raw === null) {
                $this->session->set_flashdata('error', 'Owner is required.');
                redirect('requirements/edit/'.$id);
                return;
            }
            $received_date = $this->input->post('received_date') ?: $row->received_date;
            $expected_delivery_date = $this->input->post('expected_delivery_date') ?: null;
            
            // Server-side date validation
            if ($received_date && $expected_delivery_date && $expected_delivery_date < $received_date) {
                $this->session->set_flashdata('error', 'Expected delivery date must be on or after received date.');
                redirect('requirements/edit/'.$id);
                return;
            }

            $reference_url = normalize_optional_url($this->input->post('reference_url'));
            if ($reference_url === false) {
                $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                redirect('requirements/edit/'.$id);
                return;
            }
            
            $data = [
                'client_id' => (int)$this->input->post('client_id'),
                'project_id' => $this->input->post('project_id') !== '' ? (int)$this->input->post('project_id') : null,
                'title' => trim($this->input->post('title')),
                'description' => $this->input->post('description'),
                'requirement_type' => $this->_resolve_requirement_type($this->input->post('requirement_type'), isset($row->requirement_type) ? $row->requirement_type : 'new_feature'),
                'priority' => $this->input->post('priority') ?: $row->priority,
                'status' => $this->input->post('status') ?: $row->status,
                'expected_delivery_date' => $expected_delivery_date,
                'received_date' => $received_date,
                'owner_id' => $this->input->post('owner_id') !== '' ? (int)$this->input->post('owner_id') : null,
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'updated_at' => date('Y-m-d H:i:s'),
                'reference_url' => $reference_url,
            ];
            // Check if status changed
            $old_status = $row->status;
            $new_status = $data['status'] ?: $row->status;
            $status_changed = ($old_status !== $new_status);
            
            $this->requirements->update_requirement((int)$id, $data);
            // compute next version number
            $nextVer = $this->requirements->next_version_no((int)$id);
            $verData = $data;
            $verData['created_by'] = (int)$this->session->userdata('user_id');
            $verData['created_at'] = date('Y-m-d H:i:s');
            $this->requirements->create_version((int)$id, $nextVer, $verData);
            
            // Send notifications if status changed (both in-app and email)
            if ($status_changed) {
                $notify_users = [];
                // Notify owner
                if (isset($row->owner_id) && (int)$row->owner_id > 0) {
                    $notify_users[] = (int)$row->owner_id;
                }
                // Notify assigned_to
                if (isset($row->assigned_to) && (int)$row->assigned_to > 0 && !in_array((int)$row->assigned_to, $notify_users)) {
                    $notify_users[] = (int)$row->assigned_to;
                }
                // Notify creator
                if (isset($row->created_by) && (int)$row->created_by > 0 && !in_array((int)$row->created_by, $notify_users)) {
                    $notify_users[] = (int)$row->created_by;
                }
                
                $req_number = isset($row->req_number) ? $row->req_number : '#'.$id;
                $req_title = isset($row->title) ? mb_substr($row->title, 0, 50) : 'Requirement';
                $status_from = ucwords(str_replace('_', ' ', $old_status));
                $status_to = ucwords(str_replace('_', ' ', $new_status));
                $view_url = site_url('requirements/view/'.$id);
                
                // Load Reminder model for email notifications
                $this->load->model('Reminder_model', 'reminders');
                $this->reminders->ensure_schema();
                
                foreach ($notify_users as $uid) {
                    // Get user email
                    $user_email = '';
                    $user_name = '';
                    if ($this->db->table_exists('users')) {
                        $sel = ['email'];
                        if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'full_name'; }
                        if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'name'; }
                        if (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')) { 
                            $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; 
                        }
                        $u = $this->db->select(implode(',', $sel), false)->from('users')->where('id', (int)$uid)->get()->row();
                        if ($u) {
                            $user_email = isset($u->email) ? $u->email : '';
                            if (isset($u->full_label) && $u->full_label !== '') { $user_name = $u->full_label; }
                            else if (isset($u->full_name) && $u->full_name !== '') { $user_name = $u->full_name; }
                            else if (isset($u->name) && $u->name !== '') { $user_name = $u->name; }
                            else { $user_name = $user_email; }
                        }
                    }
                    
                    // In-app notification
                    $this->load->model('Notification_model');
                    $this->Notification_model->create($uid, 'Requirement status changed: '.$req_number, 'Status changed from "'.$status_from.'" to "'.$status_to.'" for: '.$req_title, 'info', 'requirements');
                    
                    // Email notification (queue via Reminder system)
                    if ($user_email !== '') {
                        $email_subject = 'Requirement Status Changed: '.$req_number;
                        $email_body = "Hello ".$user_name.",\n\n";
                        $email_body .= "The status of requirement ".$req_number." has been changed:\n\n";
                        $email_body .= "Requirement: ".$req_title."\n";
                        $email_body .= "Previous Status: ".$status_from."\n";
                        $email_body .= "New Status: ".$status_to."\n\n";
                        $email_body .= "View requirement: ".$view_url."\n\n";
                        $email_body .= "Thank you.";
                        
                        $this->reminders->enqueue([
                            'user_id' => $uid,
                            'email' => $user_email,
                            'type' => 'requirement_status_change',
                            'subject' => $email_subject,
                            'body' => $email_body,
                            'send_at' => date('Y-m-d H:i:00') // Send immediately (or queue for cron)
                        ]);
                    }
                }
            }
            
            $this->session->set_flashdata('success','Requirement updated');
            redirect('requirements/view/'.$id);
            return;
        }
        $clients = $this->clients->get_clients([], null, 0);
        $members = $this->requirements->get_team_members();
        $projects = [];
        if ($this->db->table_exists('projects')) {
            $this->db->select('id,name')->from('projects');
            if (schema_table_has_column($this->db, 'projects', 'created_by')) {
                apply_role_hierarchy_filter($this->db, 'created_by');
            } else if (schema_table_has_column($this->db, 'projects', 'manager_id')) {
                apply_role_hierarchy_filter($this->db, 'manager_id');
            }
            $projects = $this->db->order_by('name','ASC')->get()->result();
        }
        
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('requirements', true);
        
        $this->load->view('requirements/edit', [
            'row'=>$row,
            'clients'=>$clients,
            'members'=>$members,
            'projects'=>$projects,
            'statuses'=>$statuses_list,
            'requirement_types'=>$this->_requirement_type_options(),
        ]);
    }

    // GET /requirements/view/{id}
    public function view($id){
        require_module_access(['requirements_view', 'requirements'], true);
        $req = $this->requirements->get_requirement((int)$id);
        if (!$req) { show_404(); }
        $attachments = $this->requirements->get_attachments((int)$id);
        $type = $this->input->get('type');
        $versions = $this->requirements->get_versions((int)$id, $type);
        $comments = $this->requirements->get_requirement_comments((int)$id);
        $this->load->view('requirements/view', [
            'req'=>$req,
            'attachments'=>$attachments,
            'versions'=>$versions,
            'comments'=>$comments,
            'type_filter'=>$type,
            'requirement_types'=>$this->_requirement_type_options(),
        ]);
    }

    // GET /requirements/version/{versionId}
    public function version($versionId){
        require_module_access(['requirements_view', 'requirements'], true);
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
        require_module_access(['requirements_board', 'requirements'], true);
        $columns = array('received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
        $data = array();
        foreach ($columns as $st){
            $data[$st] = $this->requirements->get_requirements(array('status'=>$st), null, 0);
        }
        $this->load->view('requirements/board', array('columns'=>$data));
    }

    // GET /requirements/calendar
    public function calendar(){
        require_module_access(['requirements_calendar', 'requirements'], true);
        $rows = $this->requirements->get_requirements(array(), null, 0);
        $this->load->view('requirements/calendar', array('rows'=>$rows));
    }

    // GET/POST /requirements/import
    public function import()
    {
        require_module_access(['requirements_add', 'requirements'], true);
        if ($this->input->method() === 'post') {
            $user_id = (int) $this->session->userdata('user_id');
            if (!$user_id) {
                redirect('login');
                return;
            }
            $this->load->helper('csv_import');
            $opened = csv_import_open('file');
            if (!$opened['ok']) {
                $this->session->set_flashdata('error', $opened['error']);
                redirect('requirements/import');
                return;
            }
            $columns = csv_import_require_columns($opened['map'], array('title', 'client_name'));
            if (!$columns['ok']) {
                fclose($opened['handle']);
                $this->session->set_flashdata('error', $columns['error']);
                redirect('requirements/import');
                return;
            }
            $inserted = 0;
            $skipped = 0;
            $row_errors = array();
            $client_cache = array();
            $project_cache = array();
            $line = 1;
            $allowed_priority = array('low', 'medium', 'high', 'urgent');
            $allowed_status = array('received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled');
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            while (($row = fgetcsv($opened['handle'])) !== false) {
                $line++;
                $title = csv_import_get($opened['map'], $row, 'title');
                if ($title === '') {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Missing requirement title.');
                    continue;
                }
                $client_name = csv_import_get_any($opened['map'], $row, array('client_name', 'client'), '');
                $client_id = csv_import_resolve_client_id($this->db, $client_name, $client_cache);
                if ($client_id <= 0) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Unknown client name or code.');
                    continue;
                }
                $project_id = null;
                $project_name = csv_import_get_any($opened['map'], $row, array('project_name', 'project'), '');
                if ($project_name !== '') {
                    $resolved_project = csv_import_resolve_project_id($this->db, $opened['map'], $row, $project_cache);
                    if ($resolved_project <= 0) {
                        $skipped++;
                        csv_import_add_row_error($row_errors, $line, 'Unknown project name or code.');
                        continue;
                    }
                    $project_id = $resolved_project;
                }
                $priority = csv_import_validate_enum(
                    csv_import_get($opened['map'], $row, 'priority', 'medium'),
                    $allowed_priority,
                    'medium',
                    $row_errors,
                    $line,
                    'priority'
                );
                if ($priority === false) {
                    $skipped++;
                    continue;
                }
                $status = csv_import_validate_enum(
                    csv_import_get($opened['map'], $row, 'status', 'received'),
                    $allowed_status,
                    'received',
                    $row_errors,
                    $line,
                    'status'
                );
                if ($status === false) {
                    $skipped++;
                    continue;
                }
                $data = array(
                    'req_number' => $this->generate_req_number(),
                    'client_id' => $client_id,
                    'project_id' => $project_id,
                    'title' => $title,
                    'description' => csv_import_get($opened['map'], $row, 'description', null) ?: null,
                    'requirement_type' => $this->_resolve_requirement_type(csv_import_get($opened['map'], $row, 'requirement_type', 'new_feature')),
                    'priority' => $priority,
                    'status' => $status,
                    'received_date' => csv_import_get($opened['map'], $row, 'received_date', date('Y-m-d')) ?: date('Y-m-d'),
                    'expected_delivery_date' => csv_import_get($opened['map'], $row, 'expected_delivery_date', null) ?: null,
                    'owner_id' => $user_id,
                    'assigned_to' => null,
                    'created_by' => $user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                );
                $id = $this->requirements->create_requirement($data);
                if ($id) {
                    $this->requirements->create_version((int) $id, 1, $data);
                    $inserted++;
                } else {
                    $skipped++;
                    $db_error = $this->db->error();
                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';
                    csv_import_add_row_error($row_errors, $line, $reason);
                    log_message('error', 'Requirement import error: ' . $reason);
                }
            }
            $this->db->db_debug = $prev_debug;
            fclose($opened['handle']);
            csv_import_finish($inserted, $skipped, $row_errors, 'requirements', 'requirements', 'requirements/import');
            return;
        }
        $this->load->view('requirements/import');
    }

    // GET /requirements/export
    public function export(){
        require_module_access(['requirements_export', 'requirements'], true);
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

    // POST /requirements/{requirement_id}/comment
    public function add_comment($requirement_id)
    {
        $requirement_id = (int)$requirement_id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { redirect('auth/login'); return; }
        if ($this->input->method() !== 'post') { show_404(); }

        $req = $this->requirements->get_requirement($requirement_id);
        if (!$req) { show_404(); }
        $comment = trim((string)$this->input->post('comment'));
        if ($comment === '') {
            $this->session->set_flashdata('error', 'Comment cannot be empty.');
            redirect('requirements/view/'.$requirement_id);
            return;
        }
        $this->requirements->add_comment($requirement_id, $user_id, $comment);
        $this->load->helper('activity');
        log_activity('requirements', 'commented', (int)$requirement_id, mb_substr($comment, 0, 120));

        // Notify owner and assigned_to if exists and not self
        $notify_users = [];
        if (isset($req->owner_id) && (int)$req->owner_id > 0 && (int)$req->owner_id !== $user_id) {
            $notify_users[] = (int)$req->owner_id;
        }
        if (isset($req->assigned_to) && (int)$req->assigned_to > 0 && (int)$req->assigned_to !== $user_id && !in_array((int)$req->assigned_to, $notify_users)) {
            $notify_users[] = (int)$req->assigned_to;
        }
        
        if (!empty($notify_users)) {
            $this->load->model('Notification_model');
            $req_ref = ($req->req_number ? $req->req_number : '#'.$requirement_id);
            $this->Notification_model->create_bulk(
                $notify_users,
                'New comment on requirement ' . $req_ref,
                mb_substr($comment, 0, 200),
                'info',
                'requirements',
                (int)$requirement_id,
                site_url('requirements/view/' . $requirement_id)
            );
        }

        $this->session->set_flashdata('success', 'Comment added.');
        redirect('requirements/view/'.$requirement_id);
    }

    // GET /requirements/{requirement_id}/comments (AJAX JSON)
    public function get_comments($requirement_id)
    {
        $requirement_id = (int)$requirement_id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'unauthorized'])); return; }
        $req = $this->requirements->get_requirement($requirement_id);
        if (!$req) { $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'not_found'])); return; }
        $rows = $this->requirements->get_requirement_comments($requirement_id);
        $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'comments'=>$rows]));
    }

    // POST /requirements/comment/{comment_id}/delete or GET mapped route
    public function delete_comment($comment_id)
    {
        $comment_id = (int)$comment_id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { redirect('auth/login'); return; }

        // If user has 'requirements_edit' or 'requirements' access, allow delete unconditionally
        $can_delete_all = is_admin_group() || has_module_access('requirements_delete_all');
        if ($can_delete_all) {
            $this->db->where('id', $comment_id)->delete('requirement_comments');
            $this->session->set_flashdata('success', 'Comment deleted.');
            $ref = $this->input->get('ref');
            if ($ref) { redirect($ref); return; }
            redirect('requirements');
            return;
        }

        // Owner-only delete
        $ok = $this->requirements->delete_comment($comment_id, $user_id);
        if ($ok) { $this->session->set_flashdata('success', 'Comment deleted.'); }
        else { $this->session->set_flashdata('error', 'Cannot delete this comment.'); }
        $ref = $this->input->get('ref');
        if ($ref) { redirect($ref); return; }
        redirect('requirements');
    }
}
