<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'group_filter', 'hierarchy_filter', 'permission', 'schema_columns', 'types']);
        $this->load->model('Type_model', 'module_types');
        $this->load->library(['session']);
        $this->load->model('Project_model');
        
        // RBAC Audit: Centralized module access check
        // Allow users with either 'projects' or 'projects_list' access
        require_module_access(['projects', 'projects_list'], true);
    }

    public function index() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Admin sees all projects; others see only projects they belong to
        $can_view_all = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('projects_view_all');
        if (!$can_view_all) {
            $projects = $this->Project_model->all($filters);
        } else {
            $projects = $this->Project_model->all([]);
        }

        $show_assignee_column = schema_table_has_column($this->db, 'projects', 'manager_id');
        if ($show_assignee_column) {
            $this->_attach_project_assignee_labels($projects);
        }
        
        $this->load->view('projects/list', [
            'projects' => $projects,
            'show_assignee_column' => $show_assignee_column,
        ]);
    }

    public function matrix()
    {
        require_module_access(array('projects', 'projects_list', 'projects_matrix'), true);
        $this->load->helper(array('action_priority_matrix', 'project_matrix'));
        $this->load->model('Status_model', 'statuses');

        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        $filters = project_matrix_parse_filters($this->input);

        $can_view_all = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('projects_view_all');
        $group_filters = get_user_group_filter($user_id, $role_id);
        if (!$can_view_all) {
            $projects = $this->Project_model->all($group_filters);
        } else {
            $projects = $this->Project_model->all(array());
        }

        $projects = project_matrix_enrich_projects($this->db, $projects);
        $projects = project_matrix_filter_projects($projects, $filters, $this->db);
        $matrix_columns = project_matrix_build_columns($projects);

        $status_rows = $this->statuses->get_by_type('projects', true);
        $status_map = array();
        foreach ($status_rows as $s) {
            $status_map[(string) $s->code] = (string) $s->name;
        }

        $clients = array();
        if ($this->db->table_exists('clients') && schema_table_has_column($this->db, 'projects', 'client_id')) {
            $clients = $this->db->select('id, company_name')->order_by('company_name', 'ASC')->get('clients')->result();
        }

        $this->load->view('projects/matrix', array(
            'projects'         => $projects,
            'matrix_columns'   => $matrix_columns,
            'filters'          => $filters,
            'status_map'       => $status_map,
            'status_rows'      => $status_rows,
            'clients'          => $clients,
            'can_view_works'   => project_matrix_can_view_works(),
            'can_view_tasks'   => project_matrix_can_view_tasks(),
        ));
    }

    // GET /projects/create, POST /projects/create
    public function create()
    {
        // Check create permission specifically
        require_module_access(['projects_add', 'projects'], true);

        $this->_ensure_reference_url_column();
        
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
                if (schema_table_has_column($this->db, 'projects', 'project_type')) {
                    $project_type = module_type_validate_code($this->input->post('project_type'), 'projects', true);
                    if ($project_type === false) {
                        $this->session->set_flashdata('error', 'Please select a valid project type.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                    $data['project_type'] = $project_type;
                }
                $reference_url = normalize_optional_url($this->input->post('reference_url'));
                if ($reference_url === false) {
                    $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                    redirect('projects/create' . ($embed ? '?embed=1' : ''));
                    return;
                }
                $data['reference_url'] = $reference_url;

                if (!$this->_apply_manager_id_from_post($data)) {
                    $this->session->set_flashdata('error', 'Please select a valid assignee.');
                    redirect('projects/create' . ($embed ? '?embed=1' : ''));
                    return;
                }
                
                // Use transaction for data integrity
                $this->db->trans_start();
                $this->db->insert('projects', $data);
                $id = $this->db->insert_id();
                
                // Set created_by if column exists
                if (schema_table_has_column($this->db, 'projects', 'created_by')) {
                    $this->db->where('id', $id)->update('projects', [
                        'created_by' => (int)$this->session->userdata('user_id')
                    ]);
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception('Database transaction failed');
                }

                if (!empty($data['manager_id'])) {
                    $this->Project_model->add_member($id, (int) $data['manager_id'], 'manager');
                }
                
                // Log project creation with change tracking
                $this->load->helper('change_tracker');
                $description = 'Project: ' . (string)$data['name'];
                auto_log_insert('projects', 'projects', (int)$id, $data, $description);
                
                $success_msg = get_notification_message('projects', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
            } catch (Exception $e) {
                log_message('error', 'Project creation error: ' . $e->getMessage());
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
        
        $project_types = module_type_options_resolved('projects');
        $this->load->view('projects/form', [
            'action' => 'create',
            'embed' => $embed,
            'statuses' => $statuses_list,
            'project_types' => $project_types,
            'users' => $this->_load_assignable_users(),
        ]);
    }

    // GET /projects/{id}
    public function show($id)
    {
        try {
            $this->_ensure_reference_url_column();
            $project = $this->db->where('id', (int)$id)->get('projects')->row();
            if (!$project) {
                show_404();
                return;
            }
            
            if (!$this->_user_can_access_project((int) $id)) {
                return;
            }
        
            
            // Fetch Tasks
            $this->db->select('t.*, u.email as assignee_email, u.name as assignee_name');
            $this->db->from('tasks t');
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            $this->db->where('t.project_id', (int)$id);
            apply_role_hierarchy_filter($this->db, 't.created_by');
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
                $this->db->where('project_id', (int)$id);
                apply_role_hierarchy_filter($this->db, 'created_by');
                $requirements = $this->db->get('requirements')->result();
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
        require_module_access(['projects_edit', 'projects'], true);

        $this->_ensure_reference_url_column();
        
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
                if (schema_table_has_column($this->db, 'projects', 'project_type')) {
                    $project_type = module_type_validate_code($this->input->post('project_type'), 'projects', true);
                    if ($project_type === false) {
                        $this->session->set_flashdata('error', 'Please select a valid project type.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                    $data['project_type'] = $project_type;
                }
                $reference_url = normalize_optional_url($this->input->post('reference_url'));
                if ($reference_url === false) {
                    $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                    redirect('projects/'.$id.'/edit');
                    return;
                }
                $data['reference_url'] = $reference_url;

                if (!$this->_apply_manager_id_from_post($data)) {
                    $this->session->set_flashdata('error', 'Please select a valid assignee.');
                    redirect('projects/'.$id.'/edit');
                    return;
                }
                
                // Load activity tracking helper
                $this->load->helper('change_tracker');
                
                // Get old data before update
                $old_data = track_changes_before('projects', (int)$id);
                $old_status = isset($project->status) ? (string) $project->status : '';
                $new_status = $this->input->post('status') ?: 'planned';
                
                $this->db->where('id', (int)$id)->update('projects', $data);

                if (!empty($data['manager_id']) && !$this->Project_model->check_user_is_member((int) $id, (int) $data['manager_id'])) {
                    $this->Project_model->add_member((int) $id, (int) $data['manager_id'], 'manager');
                }
                
                if ($old_status !== $new_status) {
                    $this->load->helper('rewards');
                    reward_engine_dispatch('project_status_update', array(
                        'user_id' => (int) $this->session->userdata('user_id'),
                        'source_module' => 'projects',
                        'source_record_id' => (int) $id,
                        'reference_label' => 'Project status: ' . $new_status,
                        'payload' => array('status' => $new_status),
                    ));
                }
                
                // Log update with change tracking
                $description = 'Project: ' . (string)$data['name'];
                track_changes_after('projects', 'projects', (int)$id, $old_data, $data, $description);
                
                $success_msg = get_notification_message('projects', 'update', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('projects/'.$id);
                return;
            } catch (Exception $e) {
                log_message('error', 'Project update error: ' . $e->getMessage());
                $error_msg = get_notification_message('projects', 'update', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('projects/'.$id.'/edit');
                return;
            }
        }
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('projects', true);
        
        $project_types = module_type_options_resolved('projects');
        $this->load->view('projects/form', [
            'action' => 'edit',
            'project' => $project,
            'statuses' => $statuses_list,
            'project_types' => $project_types,
            'users' => $this->_load_assignable_users(),
        ]);
    }

    // POST /projects/{id}/delete
    public function delete($id)
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        // Check delete permission specifically
        require_module_access(['projects_delete', 'projects'], true);
        
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
        require_module_access(['projects_import', 'projects'], true);
        if ($this->input->method() === 'post') {
            $this->load->helper('csv_import');
            $opened = csv_import_open('file');
            if (!$opened['ok']) {
                $this->session->set_flashdata('error', $opened['error']);
                redirect('projects/import');
                return;
            }
            $columns = csv_import_require_columns($opened['map'], array('name'));
            if (!$columns['ok']) {
                fclose($opened['handle']);
                $this->session->set_flashdata('error', $columns['error']);
                redirect('projects/import');
                return;
            }
            $inserted = 0;
            $skipped = 0;
            $row_errors = array();
            $line = 1;
            $allowed_status = array('planned', 'active', 'on_hold', 'completed', 'cancelled');
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            while (($row = fgetcsv($opened['handle'])) !== false) {
                $line++;
                $name = csv_import_get($opened['map'], $row, 'name');
                if ($name === '') {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Missing project name.');
                    continue;
                }
                $status = csv_import_validate_enum(
                    csv_import_get($opened['map'], $row, 'status', 'planned'),
                    $allowed_status,
                    'planned',
                    $row_errors,
                    $line,
                    'status'
                );
                if ($status === false) {
                    $skipped++;
                    continue;
                }
                $data = array(
                    'code' => csv_import_get($opened['map'], $row, 'code', null) ?: null,
                    'name' => $name,
                    'status' => $status,
                    'start_date' => csv_import_get($opened['map'], $row, 'start_date', null) ?: null,
                    'end_date' => csv_import_get($opened['map'], $row, 'end_date', null) ?: null,
                );
                if ($this->db->insert('projects', $data)) {
                    $inserted++;
                } else {
                    $skipped++;
                    $db_error = $this->db->error();
                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';
                    csv_import_add_row_error($row_errors, $line, $reason);
                    log_message('error', 'Project import error: ' . $reason);
                }
            }
            $this->db->db_debug = $prev_debug;
            fclose($opened['handle']);
            csv_import_finish($inserted, $skipped, $row_errors, 'projects', 'projects', 'projects/import');
            return;
        }
        $this->load->view('projects/import');
    }

    private function _user_can_access_project($project_id)
    {
        $project_id = (int) $project_id;
        $user_id = (int) $this->session->userdata('user_id');
        $can_view_all = is_admin_group() || has_module_access('projects_view_all');
        if ($can_view_all) {
            return true;
        }
        $is_member = $this->db->where('project_id', $project_id)
            ->where('user_id', $user_id)
            ->get('project_members')
            ->row();
        if (!$is_member) {
            show_error('You do not have access to this project.', 403);
            return false;
        }
        return true;
    }

    // GET /projects/{id}/members
    public function manage_members($project_id)
    {
        require_module_access(['projects_edit', 'projects'], true);
        $project_id = (int)$project_id;
        try {
            $project = $this->db->where('id', $project_id)->get('projects')->row();
            if (!$project) { show_404(); return; }
            if (!$this->_user_can_access_project($project_id)) {
                return;
            }

            $members = $this->Project_model->get_project_members($project_id);
            $member_user_ids = array();
            foreach ($members as $member_row) {
                $member_user_ids[] = (int) $member_row->user_id;
            }

            $q = trim((string)$this->input->get('q'));
            $users = [];
            if ($q !== '') {
                $this->db->select('id, email');
                if (schema_table_has_column($this->db, 'users', 'name')) { $this->db->select('name'); }
                $this->db->from('users');
                if (!empty($member_user_ids)) {
                    $this->db->where_not_in('id', $member_user_ids);
                }
                $this->db->group_start()
                         ->like('email', $q)
                         ->or_like('name', $q)
                         ->group_end()
                         ->order_by('email', 'ASC');
                $users = $this->db->get()->result();
            }

            $this->load->view('projects/members', [
                'project' => $project,
                'members' => $members,
                'users'   => $users,
                'q'       => $q,
                'member_roles' => $this->Project_model->member_role_options(),
            ]);
        } catch (Exception $e) {
            log_message('error', 'Manage members error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while loading project members.');
            redirect('projects/' . $project_id);
        }
    }

    // POST /projects/{id}/add-member
    public function add_member($project_id)
    {
        require_module_access(['projects_edit', 'projects'], true);
        try {
            $project_id = (int)$project_id;
            $user_id = (int)$this->input->post('user_id');
            $role = $this->Project_model->sanitize_member_role($this->input->post('role'));
            
            if (!$user_id) {
                $this->session->set_flashdata('error', 'Please select a user.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            $project = $this->db->where('id', $project_id)->get('projects')->row();
            if (!$project) {
                show_404();
                return;
            }
            if (!$this->_user_can_access_project($project_id)) {
                return;
            }
            
            $user = $this->db->where('id', $user_id)->get('users')->row();
            if (!$user) {
                $this->session->set_flashdata('error', 'Selected user does not exist.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            if ($this->Project_model->check_user_is_member($project_id, $user_id)) {
                $this->session->set_flashdata('error', 'User is already a member of this project.');
                redirect('projects/'.$project_id.'/members');
                return;
            }
            
            $ok = $this->Project_model->add_member($project_id, $user_id, $role);
            
            if ($ok) {
                $this->load->helper('activity');
                log_activity('projects', 'assigned', $project_id, 'Added member user#'.$user_id.' as '.$role);
                $this->load->helper('notification');
                create_notification(
                    $user_id,
                    'Added to Project',
                    'You were added to "' . $project->name . '" as ' . ucfirst($role) . '.',
                    'info',
                    'projects',
                    $project_id,
                    site_url('projects/' . $project_id)
                );
                if (!function_exists('send_notification_with_settings')) {
                    $this->load->helper('email_settings');
                }
                $email_data = (object) array(
                    'project_id' => $project_id,
                    'title' => $project->name,
                    'role' => $role,
                );
                send_notification_with_settings('projects', 'member_added', $email_data, $user_id);
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
        require_module_access(['projects_edit', 'projects'], true);
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        $project_id = (int)$project_id;
        $user_id    = (int)$user_id;
        try {
            if (!$this->db->where('id', $project_id)->get('projects')->row()) {
                show_404();
                return;
            }
            if (!$this->_user_can_access_project($project_id)) {
                return;
            }
            $ok = $this->Project_model->remove_member($project_id, $user_id);
            if ($ok) {
                $this->load->helper('activity');
                log_activity('projects', 'updated', $project_id, 'Removed member user#' . $user_id);
                $success_msg = get_notification_message('projects', 'member_remove', 'success');
                $this->session->set_flashdata('success', $success_msg);
            } else {
                $this->session->set_flashdata('error', 'Failed to remove member.');
            }
        } catch (Exception $e) {
            log_message('error', 'Remove member error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while removing member.');
        }
        redirect('projects/' . $project_id . '/members');
    }

    // POST /projects/{id}/member/{user_id}/role
    public function update_member_role($project_id, $user_id)
    {
        require_module_access(['projects_edit', 'projects'], true);
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        $project_id = (int)$project_id;
        $user_id    = (int)$user_id;
        try {
            if (!$this->db->where('id', $project_id)->get('projects')->row()) {
                show_404();
                return;
            }
            if (!$this->_user_can_access_project($project_id)) {
                return;
            }
            if (!$this->Project_model->check_user_is_member($project_id, $user_id)) {
                $this->session->set_flashdata('error', 'User is not a member of this project.');
                redirect('projects/' . $project_id . '/members');
                return;
            }
            $role = $this->Project_model->sanitize_member_role($this->input->post('role'));
            $ok = $this->Project_model->update_member_role($project_id, $user_id, $role);
            if ($ok) {
                $this->load->helper('activity');
                log_activity('projects', 'updated', $project_id, 'Changed role of user#' . $user_id . ' to ' . $role);
                $this->session->set_flashdata('success', 'Role updated.');
            } else {
                $this->session->set_flashdata('error', 'Failed to update role.');
            }
        } catch (Exception $e) {
            log_message('error', 'Update member role error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while updating member role.');
        }
        redirect('projects/' . $project_id . '/members');
    }

    private function _ensure_reference_url_column()
    {
        if (!$this->db->table_exists('projects')) {
            return;
        }
        $fields = $this->db->list_fields('projects');
        if (!in_array('reference_url', $fields, true)) {
            $this->db->query("ALTER TABLE `projects` ADD `reference_url` VARCHAR(500) NULL DEFAULT NULL");
        }
    }

    private function _attach_project_assignee_labels(&$projects)
    {
        if (empty($projects) || !$this->db->table_exists('users')) {
            return;
        }

        $manager_ids = array();
        foreach ($projects as $project) {
            if (!empty($project->manager_id)) {
                $manager_ids[] = (int) $project->manager_id;
            }
        }
        $manager_ids = array_values(array_unique($manager_ids));
        $managers = array();
        if (!empty($manager_ids)) {
            $select = array('users.id', 'users.email');
            if (schema_table_has_column($this->db, 'users', 'name')) {
                $select[] = 'users.name';
            }
            if (schema_table_has_column($this->db, 'users', 'full_name')) {
                $select[] = 'users.full_name';
            }
            $has_emp_name = $this->db->table_exists('employees')
                && schema_table_has_column($this->db, 'employees', 'user_id')
                && schema_table_has_column($this->db, 'employees', 'name');
            if ($has_emp_name) {
                $select[] = 'employees.name AS emp_name';
            }
            $this->db->select(implode(',', $select))
                ->from('users');
            if ($has_emp_name) {
                $this->db->join('employees', 'employees.user_id = users.id', 'left');
            }
            $rows = $this->db->where_in('users.id', $manager_ids)->get()->result();
            foreach ($rows as $row) {
                $managers[(int) $row->id] = $row;
            }
        }

        foreach ($projects as $project) {
            $project->assignee_label = '—';
            $manager_id = !empty($project->manager_id) ? (int) $project->manager_id : 0;
            if ($manager_id <= 0 || !isset($managers[$manager_id])) {
                continue;
            }
            $manager = $managers[$manager_id];
            if (isset($manager->emp_name) && trim((string) $manager->emp_name) !== '') {
                $project->assignee_label = trim((string) $manager->emp_name);
            } elseif (!empty($manager->name)) {
                $project->assignee_label = (string) $manager->name;
            } elseif (!empty($manager->full_name)) {
                $project->assignee_label = (string) $manager->full_name;
            } else {
                $project->assignee_label = (string) $manager->email;
            }
        }
    }

    private function _load_assignable_users()
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }

        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            $select = array('users.id', 'users.email');
            if (schema_table_has_column($this->db, 'users', 'name')) {
                $select[] = 'users.name';
            }
            if (schema_table_has_column($this->db, 'users', 'full_name')) {
                $select[] = 'users.full_name';
            }
            $has_emp_name = schema_table_has_column($this->db, 'employees', 'name');
            if ($has_emp_name) {
                $select[] = 'employees.name AS emp_name';
            }
            $this->db->select(implode(',', $select))
                ->from('users')
                ->join('employees', 'employees.user_id = users.id', 'left');
            if ($has_emp_name) {
                $this->db->order_by('employees.name IS NULL ASC', '', false)
                    ->order_by('employees.name', 'ASC');
            }
            $this->db->order_by('users.email', 'ASC');
            return $this->db->get()->result();
        }

        $user_select = array('id', 'email');
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $user_select[] = 'full_name';
        }
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $user_select[] = 'name';
        }

        return $this->db->select(implode(',', $user_select))
            ->from('users')
            ->order_by('email', 'ASC')
            ->get()->result();
    }

    private function _apply_manager_id_from_post(&$data)
    {
        if (!schema_table_has_column($this->db, 'projects', 'manager_id')) {
            return true;
        }

        $manager_id = $this->input->post('manager_id');
        if ($manager_id === '' || $manager_id === null) {
            $data['manager_id'] = null;
            return true;
        }

        $manager_id = (int) $manager_id;
        if ($manager_id <= 0) {
            $data['manager_id'] = null;
            return true;
        }

        $user = $this->db->where('id', $manager_id)->get('users')->row();
        if (!$user) {
            return false;
        }

        $data['manager_id'] = $manager_id;
        return true;
    }
}
