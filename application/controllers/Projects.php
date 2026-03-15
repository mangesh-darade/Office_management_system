<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','group_filter','permission']);
        $this->load->library(['session']);
        $this->load->model('Project_model');
        
        // Authentication check
        if (!(int)$this->session->userdata('user_id')) {
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            redirect('auth/login');
        }
    }

    public function index() {
        if (function_exists('has_module_access') && !has_module_access('projects') && !has_module_access('projects_list')) {
            show_error('You do not have permission to view projects.', 403);
        }
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Admin sees all projects, others see only projects they're members of
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            $projects = $this->Project_model->all($filters);
        } else {
            $projects = $this->Project_model->all([]);
        }
        
        $this->load->view('projects/list', ['projects' => $projects]);
    }

    // GET /projects/create, POST /projects/create
    public function create()
    {
        // Check create permission specifically
        if (!function_exists('has_module_access') || (!has_module_access('projects_add') && !has_module_access('projects'))) {
            $this->session->set_flashdata('info', 'You do not have permission to add projects.');
            redirect('projects');
            return;
        }
        
        $embed = (bool)$this->input->get('embed');
        if ($this->input->method() === 'post') {
            try {
                $this->load->helper('validation');
                
                $code = trim($this->input->post('code'));
                $name = trim($this->input->post('name'));
                $start_date = $this->input->post('start_date') ?: null;
                $end_date = $this->input->post('end_date') ?: null;
                
                // Validation
                if (empty($name)) {
                    $this->session->set_flashdata('error', 'Project name is required.');
                    redirect('projects/create');
                    return;
                }
                
                // Server-side date validation
                if ($start_date && $end_date) {
                    $start_validation = validate_date($start_date);
                    $end_validation = validate_date($end_date);
                    
                    if (!$start_validation['valid']) {
                        $this->session->set_flashdata('error', 'Invalid start date format.');
                        redirect('projects/create');
                        return;
                    }
                    
                    if (!$end_validation['valid']) {
                        $this->session->set_flashdata('error', 'Invalid end date format.');
                        redirect('projects/create');
                        return;
                    }
                    
                    if ($end_date < $start_date) {
                        $this->session->set_flashdata('error', 'End date must be on or after start date.');
                        redirect('projects/create');
                        return;
                    }
                }
                
                // Check for duplicate code if provided
                if ($code !== '') {
                    $existing = $this->db->where('code', $code)->get('projects')->row();
                    if ($existing) {
                        $this->session->set_flashdata('error', 'Project code already exists. Please use a different code.');
                        redirect('projects/create');
                        return;
                    }
                }
                
                $data = [
                    'code' => $code !== '' ? $code : null,
                    'name' => $name,
                    'status' => $this->input->post('status') ?: 'planned',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ];
                
                // Use transaction for data integrity
                $this->db->trans_start();
                $this->db->insert('projects', $data);
                $id = $this->db->insert_id();
                
                // Set created_by if column exists
                if ($this->db->field_exists('created_by', 'projects')) {
                    $this->db->where('id', $id)->update('projects', [
                        'created_by' => (int)$this->session->userdata('user_id')
                    ]);
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception('Database transaction failed');
                }
                
                // Log project creation with change tracking
                $this->load->helper('change_tracker');
                $description = 'Project: ' . (string)$data['name'];
                auto_log_insert('projects', 'projects', (int)$id, $data, $description);
                
                $this->load->helper('notification');
                $success_msg = get_notification_message('projects', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
            } catch (Exception $e) {
                log_message('error', 'Project creation error: ' . $e->getMessage());
                $this->load->helper('notification');
                $error_msg = get_notification_message('projects', 'create', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('projects/create');
                return;
            }

            if ($embed) {
                $name = (string)$data['name'];
                $project_id = (int)$id;
                $safe_name = json_encode($name);
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Project created</title></head><body>";
                echo "<script>\n".
                     "if (window.parent && typeof window.parent.onProjectCreated === 'function') {\n".
                     "  window.parent.onProjectCreated(".$project_id.", " . $safe_name . ");\n".
                     "} else {\n".
                     "  window.close && window.close();\n".
                     "}\n".
                     "</script>";
                echo "</body></html>";
                return;
            }

            redirect('projects/'.$id);
            return;
        }
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('projects', true);
        
        $this->load->view('projects/form', [
            'action' => 'create', 
            'embed' => $embed,
            'statuses' => $statuses_list
        ]);
    }

    // GET /projects/{id}
    public function show($id)
    {
        try {
            $project = $this->db->where('id', (int)$id)->get('projects')->row();
            if (!$project) {
                show_404();
                return;
            }
            
            // Check if user has access to this project
            $user_id = (int)$this->session->userdata('user_id');
            $role_id = (int)$this->session->userdata('role_id');
            
            // Admin and Manager can see all projects
            if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                // Check if user is a member of this project
                $is_member = $this->db->where('project_id', (int)$id)
                                     ->where('user_id', $user_id)
                                     ->get('project_members')
                                     ->row();
                if (!$is_member) {
                    show_error('You do not have access to this project.', 403);
                    return;
                }
            }
            
            // Fetch Tasks
            $this->db->select('t.*, u.email as assignee_email, u.name as assignee_name');
            $this->db->from('tasks t');
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            $this->db->where('t.project_id', (int)$id);
            $this->db->order_by('t.id', 'DESC');
            $tasks = $this->db->get()->result();

            // Calculate Progress & Stats
            $total_tasks = count($tasks);
            $completed_tasks = 0;
            $stats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'blocked' => 0];
            
            foreach ($tasks as $t) {
                $status = $t->status ?: 'pending';
                if (isset($stats[$status])) {
                    $stats[$status]++;
                } else {
                    $stats['pending']++;
                }
                if ($status === 'completed') {
                    $completed_tasks++;
                }
            }
            $progress = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;

            // Fetch Members
            $this->load->model('Project_model');
            $members = $this->Project_model->get_project_members($id);

            // Fetch Requirements
            $requirements = [];
            if ($this->db->table_exists('requirements')) {
                $requirements = $this->db->where('project_id', (int)$id)->get('requirements')->result();
            }
            
            $data = [
                'project' => $project,
                'tasks' => $tasks,
                'members' => $members,
                'requirements' => $requirements,
                'progress' => $progress,
                'stats' => $stats
            ];
            
            $this->load->view('projects/view', $data);
        } catch (Exception $e) {
            log_message('error', 'Project view error: ' . $e->getMessage());
            show_error('An error occurred while loading project details.', 500);
        }
    }

    // GET /projects/{id}/edit, POST /projects/{id}/edit
    public function edit($id)
    {
        // Check edit permission specifically
        if (!function_exists('has_module_access') || (!has_module_access('projects_edit') && !has_module_access('projects'))) {
            show_error('You do not have permission to edit projects.', 403);
        }
        
        $project = $this->db->where('id', (int)$id)->get('projects')->row();
        if (!$project) show_404();
        
        if ($this->input->method() === 'post') {
            try {
                $this->load->helper('validation');
                
                $code = trim($this->input->post('code'));
                $name = trim($this->input->post('name'));
                $start_date = $this->input->post('start_date') ?: null;
                $end_date = $this->input->post('end_date') ?: null;
                
                // Validation
                if (empty($name)) {
                    $this->session->set_flashdata('error', 'Project name is required.');
                    redirect('projects/'.$id.'/edit');
                    return;
                }
                
                // Server-side date validation
                if ($start_date && $end_date) {
                    $start_validation = validate_date($start_date);
                    $end_validation = validate_date($end_date);
                    
                    if (!$start_validation['valid'] || !$end_validation['valid']) {
                        $this->session->set_flashdata('error', 'Invalid date format.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                    
                    if ($end_date < $start_date) {
                        $this->session->set_flashdata('error', 'End date must be on or after start date.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                }
                
                // Check for duplicate code if changed
                if ($code !== '' && $code !== $project->code) {
                    $existing = $this->db->where('code', $code)->where('id !=', (int)$id)->get('projects')->row();
                    if ($existing) {
                        $this->session->set_flashdata('error', 'Project code already exists. Please use a different code.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                }
                
                $data = [
                    'code' => $code !== '' ? $code : null,
                    'name' => $name,
                    'status' => $this->input->post('status') ?: 'planned',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ];
                
                // Load activity tracking helper
                $this->load->helper('change_tracker');
                
                // Get old data before update
                $old_data = track_changes_before('projects', (int)$id);
                
                $this->db->where('id', (int)$id)->update('projects', $data);
                
                // Log update with change tracking
                $description = 'Project: ' . (string)$data['name'];
                track_changes_after('projects', 'projects', (int)$id, $old_data, $data, $description);
                
                $this->load->helper('notification');
                $success_msg = get_notification_message('projects', 'update', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('projects/'.$id);
                return;
            } catch (Exception $e) {
                log_message('error', 'Project update error: ' . $e->getMessage());
                $this->load->helper('notification');
                $error_msg = get_notification_message('projects', 'update', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('projects/'.$id.'/edit');
                return;
            }
        }
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('projects', true);
        
        $this->load->view('projects/form', [
            'action' => 'edit', 
            'project' => $project,
            'statuses' => $statuses_list
        ]);
    }

    // POST /projects/{id}/delete
    public function delete($id)
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        // Check delete permission specifically
        if (!function_exists('has_module_access') || (!has_module_access('projects_delete') && !has_module_access('projects'))) {
            show_error('You do not have permission to delete projects.', 403);
        }
        
        try {
            $project = $this->db->where('id', (int)$id)->get('projects')->row();
            if (!$project) {
                show_404();
                return;
            }
            
            // Load activity tracking helper
            $this->load->helper('change_tracker');
            
            // Get old data before delete
            $old_data = track_changes_before('projects', (int)$id);
            
            // Use transaction to ensure data integrity
            // Note: Foreign key constraints should handle cascade deletes for project_members
            $this->db->trans_start();
            $this->db->where('id', (int)$id)->delete('projects');
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Failed to delete project');
            }
            
            // Log deletion
            $description = 'Project deleted: ' . $project->name;
            auto_log_delete('projects', 'projects', (int)$id, $old_data, $description);
            
            $this->load->helper('notification');
            $success_msg = get_notification_message('projects', 'delete', 'success');
            $this->session->set_flashdata('success', $success_msg);
        } catch (Exception $e) {
            log_message('error', 'Project deletion error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Failed to delete project. Please try again.');
        }
        
        redirect('projects');
    }

    // GET/POST /projects/import
    public function import()
    {
        if ($this->input->method() === 'post') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->session->set_flashdata('error', 'Please upload a valid CSV file');
                redirect('projects/import');
                return;
            }
            $handle = fopen($_FILES['file']['tmp_name'], 'r');
            if (!$handle) { $this->session->set_flashdata('error', 'Unable to read uploaded file'); redirect('projects/import'); return; }
            $header = fgetcsv($handle);
            if (!$header) { fclose($handle); $this->session->set_flashdata('error', 'CSV is empty'); redirect('projects/import'); return; }
            $map = []; foreach ($header as $i=>$c) { $map[strtolower(trim($c))] = $i; }
            $inserted = 0;
            $errors = 0;
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            while (($row = fgetcsv($handle)) !== false) {
                $data = [
                    'code' => (isset($map['code']) && isset($row[$map['code']])) ? $row[$map['code']] : null,
                    'name' => (isset($map['name']) && isset($row[$map['name']])) ? $row[$map['name']] : null,
                    'status' => (isset($map['status']) && isset($row[$map['status']])) ? $row[$map['status']] : 'planned',
                    'start_date' => (isset($map['start_date']) && isset($row[$map['start_date']])) ? $row[$map['start_date']] : null,
                    'end_date' => (isset($map['end_date']) && isset($row[$map['end_date']])) ? $row[$map['end_date']] : null,
                ];
                if (!empty($data['name'])) {
                    $ok = $this->db->insert('projects', $data);
                    if ($ok) {
                        $inserted++;
                    } else {
                        $errors++;
                        $db_error = $this->db->error();
                        if (!empty($db_error['message'])) {
                            log_message('error', 'Project import error: '.$db_error['message']);
                        }
                    }
                }
            }
            $this->db->db_debug = $prev_debug;
            fclose($handle);
            if ($errors > 0 && $inserted === 0) {
                $this->session->set_flashdata('error', 'No projects were imported. Please check your CSV for duplicate codes or invalid data.');
            } elseif ($errors > 0) {
                $this->session->set_flashdata('success', "Imported $inserted projects. Some rows were skipped due to errors (for example, duplicate codes or invalid data).");
            } else {
                $this->session->set_flashdata('success', "Imported $inserted projects");
            }
            redirect('projects');
            return;
        }
        $this->load->view('projects/import');
    }

    // GET /projects/{id}/members
    public function manage_members($project_id)
    {
        $project_id = (int)$project_id;
        $project = $this->db->where('id', $project_id)->get('projects')->row();
        if (!$project) { show_404(); }

        // Fetch members
        $this->load->model('Project_model');
        $members = $this->Project_model->get_project_members($project_id);

        // Basic search for adding members
        $q = trim((string)$this->input->get('q'));
        $users = [];
        if ($q !== ''){
            $this->db->select('id, email');
            if ($this->db->field_exists('name','users')) { $this->db->select('name'); }
            $this->db->from('users');
            $this->db->group_start()
                     ->like('email', $q)
                     ->or_like('name', $q)
                     ->group_end()
                     ->order_by('email','ASC');
            $users = $this->db->get()->result();
        }

        $this->load->view('projects/members', [
            'project' => $project,
            'members' => $members,
            'users' => $users,
            'q' => $q,
        ]);
    }

    // POST /projects/{id}/add-member
    public function add_member($project_id)
    {
        try {
            $project_id = (int)$project_id;
            $user_id = (int)$this->input->post('user_id');
            $role = trim((string)$this->input->post('role')) ?: 'member';
            
            // Validation
            if (!$user_id) {
                $this->session->set_flashdata('error', 'Please select a user.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            // Verify project exists
            $project = $this->db->where('id', $project_id)->get('projects')->row();
            if (!$project) {
                show_404();
                return;
            }
            
            // Verify user exists
            $user = $this->db->where('id', $user_id)->get('users')->row();
            if (!$user) {
                $this->session->set_flashdata('error', 'Selected user does not exist.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            // Check if user is already a member
            $existing = $this->db->where('project_id', $project_id)
                                 ->where('user_id', $user_id)
                                 ->get('project_members')
                                 ->row();
            if ($existing) {
                $this->session->set_flashdata('error', 'User is already a member of this project.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            // Validate role
            $allowed_roles = ['member', 'lead', 'viewer'];
            if (!in_array($role, $allowed_roles, true)) {
                $role = 'member';
            }
            
            $this->load->model('Project_model');
            $ok = $this->Project_model->add_member($project_id, $user_id, $role);
            
            if ($ok) {
                $this->load->helper('activity');
                log_activity('projects', 'assigned', $project_id, 'Added member user#'.$user_id.' as '.$role);
                $this->load->helper('notification');
                $success_msg = get_notification_message('projects', 'member_add', 'success');
                $this->session->set_flashdata('success', $success_msg);
            } else {
                $this->session->set_flashdata('error', 'Failed to add member. Please try again.');
            }
        } catch (Exception $e) {
            log_message('error', 'Add member error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while adding member.');
        }
        
        redirect('projects/'.$project_id.'/members');
    }

    // POST /projects/{id}/remove-member/{user_id}
    public function remove_member($project_id, $user_id)
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        $project_id = (int)$project_id; $user_id = (int)$user_id;
        // Allow only admin or project creator (if column exists)
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== ROLE_ADMIN) {
            $project = $this->db->where('id', $project_id)->get('projects')->row();
            if (!$project) { show_404(); }
            if ($this->db->field_exists('created_by','projects')){
                $me = (int)$this->session->userdata('user_id');
                if ((int)$project->created_by !== $me) { show_error('Forbidden', 403); }
            }
        }
        $this->load->model('Project_model');
        $ok = $this->Project_model->remove_member($project_id, $user_id);
        if ($ok) { $this->load->helper('activity'); log_activity('projects', 'updated', $project_id, 'Removed member user#'.$user_id); }
        $this->load->helper('notification');
        if ($ok) { 
            $success_msg = get_notification_message('projects', 'member_remove', 'success');
            $this->session->set_flashdata('success', $success_msg);
        }
        else { $this->session->set_flashdata('error', 'Failed to remove member.'); }
        redirect('projects/'.$project_id.'/members');
    }

    // POST /projects/{id}/member/{user_id}/role
    public function update_member_role($project_id, $user_id)
    {
        $project_id = (int)$project_id; $user_id = (int)$user_id;
        $role = trim((string)$this->input->post('role')) ?: 'member';
        $this->load->model('Project_model');
        $ok = $this->Project_model->update_member_role($project_id, $user_id, $role);
        if ($ok) { $this->load->helper('activity'); log_activity('projects', 'updated', $project_id, 'Changed role of user#'.$user_id.' to '.$role); }
        if ($ok) { $this->session->set_flashdata('success', 'Role updated.'); }
        else { $this->session->set_flashdata('error', 'Failed to update role.'); }
        redirect('projects/'.$project_id.'/members');
    }
}
