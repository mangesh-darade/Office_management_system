<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'group_filter', 'hierarchy_filter', 'permission', 'schema_columns', 'types', 'estimate_hours']);
        $this->load->model('Type_model', 'module_types');
        $this->load->library(['session']);
        $this->load->model('Project_model');
        
        // RBAC Audit: Centralized module access check
        // Allow users with either 'projects' or 'projects_list' access
        require_module_access(['projects', 'projects_list'], true);
        estimate_hours_ensure_column($this->db, 'projects', 'end_date');
        actual_hours_ensure_column($this->db, 'projects', 'estimate_hours');
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
                
                $name = trim($this->input->post('name'));
                $start_date = $this->input->post('start_date') ?: null;
                $end_date = $this->input->post('end_date') ?: null;
                
                // Validation
                if (empty($name)) {
                    $this->session->set_flashdata('error', 'Project name is required.');
                    redirect('projects/create' . ($embed ? '?embed=1' : ''));
                    return;
                }

                $client_id = $this->_resolve_client_id_from_post('projects/create' . ($embed ? '?embed=1' : ''));
                if ($client_id === false) {
                    return;
                }
                
                // Server-side date validation
                if ($start_date && $end_date) {
                    $start_validation = validate_date($start_date);
                    $end_validation = validate_date($end_date);
                    
                    if (!$start_validation['valid']) {
                        $this->session->set_flashdata('error', 'Invalid start date format.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                    
                    if (!$end_validation['valid']) {
                        $this->session->set_flashdata('error', 'Invalid end date format.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                    
                    if ($end_date < $start_date) {
                        $this->session->set_flashdata('error', 'End date must be on or after start date.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                }

                $code = $this->_resolve_project_code_for_create($this->input->post('code'));
                if ($code === false) {
                    $this->session->set_flashdata('error', 'Project code already exists. Please use a different code.');
                    redirect('projects/create' . ($embed ? '?embed=1' : ''));
                    return;
                }
                
                $department_id = $this->input->post('department_id') ? (int) $this->input->post('department_id') : null;
                if ($department_id) {
                    $this->load->model('Department_model');
                    if (!$this->Department_model->find($department_id)) {
                        $this->session->set_flashdata('error', 'Please select a valid department.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                }
                
                $data = [
                    'code' => $code,
                    'name' => $name,
                    'status' => $this->input->post('status') ?: 'planned',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'department_id' => $department_id,
                ];
                if (schema_table_has_column($this->db, 'projects', 'client_id')) {
                    $data['client_id'] = $client_id;
                }
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

                if (schema_table_has_column($this->db, 'projects', 'estimate_hours')) {
                    $est = estimate_hours_parse($this->input->post('estimate_hours'));
                    if ($est === false) {
                        $this->session->set_flashdata('error', 'Estimate (hrs) must be a number between 0 and 9999.99.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                    $data['estimate_hours'] = $est;
                }
                if (schema_table_has_column($this->db, 'projects', 'actual_hours')
                    && status_is_project_completed($data['status'])
                ) {
                    $act = actual_hours_require($this->input->post('actual_hours'));
                    if ($act === false) {
                        $this->session->set_flashdata('error', 'Actual (hrs) is required when status is Completed.');
                        redirect('projects/create' . ($embed ? '?embed=1' : ''));
                        return;
                    }
                    $data['actual_hours'] = $act;
                }

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
                    if (!function_exists('send_user_notification_email')) {
                        $this->load->helper('email_settings');
                    }
                    $this->load->helper('notification');
                    $email_data = (object) array(
                        'id' => (int) $id,
                        'project_id' => (int) $id,
                        'title' => (string) $data['name'],
                        'name' => (string) $data['name'],
                        'role' => 'manager',
                    );
                    if (function_exists('create_notification')) {
                        create_notification(
                            (int) $data['manager_id'],
                            'Added to Project',
                            'You were added to "' . $data['name'] . '" as Manager.',
                            'info',
                            'projects',
                            (int) $id,
                            site_url('projects/' . $id)
                        );
                    }
                    send_user_notification_email((int) $data['manager_id'], 'projects', 'member_added', $email_data);
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
        
        $this->load->model('Department_model');
        $departments = $this->Department_model->all();
        
        $project_types = module_type_options_resolved('projects');
        $this->load->view('projects/form', [
            'action' => 'create',
            'embed' => $embed,
            'statuses' => $statuses_list,
            'project_types' => $project_types,
            'users' => $this->_load_assignable_users(),
            'departments' => $departments,
            'clients' => $this->_load_project_clients(),
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
                $this->db->select('r.*, u.email AS assignee_email, u.name AS assignee_name');
                $this->db->from('requirements r');
                $this->db->join('users u', 'u.id = r.assigned_to', 'left');
                $this->db->where('r.project_id', (int) $id);
                apply_role_hierarchy_filter($this->db, 'r.created_by');
                $this->db->order_by('r.id', 'DESC');
                $requirements = $this->db->get()->result();
            }

            $defects = array();
            if ($this->db->table_exists('project_defects')
                && function_exists('has_module_access')
                && (has_module_access('defects') || has_module_access('defects_list'))) {
                $this->load->model('Defect_model', 'project_defects');
                $defects = $this->project_defects->list_defects(array('project_id' => (int) $id));
            }

            $releases = array();
            if ($this->db->table_exists('project_releases')
                && function_exists('has_module_access')
                && (has_module_access('releases') || has_module_access('releases_list'))) {
                $this->load->model('Engagement_model', 'project_releases');
                $releases = $this->project_releases->list_releases(array('project_id' => (int) $id));
            }

            $this->load->helper('my_works');
            $complete_view = dashboard_parse_complete_view($this->input);
            $tasks = dashboard_filter_rows_by_kind($tasks, $complete_view, 'task');
            $requirements = dashboard_filter_rows_by_kind($requirements, $complete_view, 'requirement');
            $defects = dashboard_filter_rows_by_kind($defects, $complete_view, 'defect');
            
            $assignable_users = $this->_load_assignable_users();
            $can_manage_tasks = function_exists('has_module_access')
                && (has_module_access('tasks_add') || has_module_access('tasks_edit') || has_module_access('tasks'));
            $can_manage_requirements = function_exists('has_module_access')
                && (has_module_access('requirements_add') || has_module_access('requirements_edit') || has_module_access('requirements'));
            $can_manage_defects = function_exists('has_module_access')
                && (has_module_access('defects_add') || has_module_access('defects_edit') || has_module_access('defects'));
            $can_delete_tasks = function_exists('has_module_access')
                && (has_module_access('tasks_delete') || has_module_access('tasks'));
            $can_delete_requirements = function_exists('has_module_access')
                && (has_module_access('requirements_delete') || has_module_access('requirements'));
            $can_delete_defects = function_exists('has_module_access')
                && (has_module_access('defects_delete') || has_module_access('defects'));
            $can_manage_releases = function_exists('has_module_access')
                && (has_module_access('releases_add') || has_module_access('releases_edit') || has_module_access('releases'));
            $can_delete_releases = function_exists('has_module_access')
                && (has_module_access('releases_delete') || has_module_access('releases'));

            $data = [
                'project' => $project,
                'tasks' => $tasks,
                'members' => $members,
                'requirements' => $requirements,
                'defects' => $defects,
                'releases' => $releases,
                'progress' => $progress,
                'stats' => $stats,
                'assignable_users' => $assignable_users,
                'can_manage_tasks' => $can_manage_tasks,
                'can_manage_requirements' => $can_manage_requirements,
                'can_manage_defects' => $can_manage_defects,
                'can_delete_tasks' => $can_delete_tasks,
                'can_delete_requirements' => $can_delete_requirements,
                'can_delete_defects' => $can_delete_defects,
                'can_manage_releases' => $can_manage_releases,
                'can_delete_releases' => $can_delete_releases,
                'complete_view'    => $complete_view,
                'complete_view_on' => ($complete_view === 'only'),
            ];
            
            $this->load->view('projects/view', $data);
        } catch (Exception $e) {
            log_message('error', 'Project view error: ' . $e->getMessage());
            show_error('An error occurred while loading project details.', 500);
        }
    }

    // GET /projects/dashboard
    public function dashboard_index()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        $filters = get_user_group_filter($user_id, $role_id);

        $filter_user_id = $this->input->get('user_id') !== null ? (int)$this->input->get('user_id') : -1;
        $filter_project_id = $this->input->get('project_id') !== null ? (int)$this->input->get('project_id') : -1;
        $filter_client_id = $this->input->get('client_id') !== null ? (int)$this->input->get('client_id') : 0;
        if ($filter_client_id < 1) {
            $filter_client_id = 0;
        }
        $filter_status = $this->input->get('status') !== null ? (string)$this->input->get('status') : 'all';
        $filter_department_id = $this->input->get('department_id') !== null ? (int)$this->input->get('department_id') : -1;
        $this->load->helper('my_works');
        $complete_view = dashboard_parse_complete_view($this->input);

        $can_view_all = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('projects_view_all');
        if (!$can_view_all) {
            $projects = $this->Project_model->all($filters);
        } else {
            $projects = $this->Project_model->all(array());
        }

        $has_project_client = schema_table_has_column($this->db, 'projects', 'client_id');

        $filter_clients = array();
        if ($has_project_client && $this->db->table_exists('clients')) {
            $this->db->select('id, company_name');
            $this->db->from('clients');
            $this->db->order_by('company_name', 'ASC');
            $filter_clients = $this->db->get()->result();
        }

        $filter_projects = array();
        if ($this->db->table_exists('projects')) {
            $this->db->select('id, name');
            if ($has_project_client) {
                $this->db->select('client_id');
            }
            $this->db->from('projects');
            if ($has_project_client && $filter_client_id > 0) {
                $this->db->where('client_id', $filter_client_id);
            }
            $this->db->order_by('name', 'asc');
            $filter_projects = $this->db->get()->result();
            if ($filter_project_id > 0) {
                $project_ok = false;
                foreach ($filter_projects as $fp) {
                    if ((int) $fp->id === $filter_project_id) {
                        $project_ok = true;
                        break;
                    }
                }
                if (!$project_ok) {
                    $filter_project_id = 0;
                }
            }
        }

        $filter_users = array();
        if ($this->db->table_exists('users')) {
            $filter_users = $this->db->select('id, name')->order_by('name', 'asc')->get('users')->result();
        }

        $this->load->model('Department_model');
        $filter_departments = $this->Department_model->all();
        $department_map = array();
        foreach ($filter_departments as $dept) {
            $department_map[(int)$dept->id] = (string)$dept->dept_name;
        }

        if (!empty($projects)) {
            usort($projects, function ($a, $b) {
                $a_name = isset($a->name) ? strtolower(trim((string) $a->name)) : '';
                $b_name = isset($b->name) ? strtolower(trim((string) $b->name)) : '';
                if ($a_name === $b_name) {
                    $a_code = isset($a->code) ? strtolower(trim((string) $a->code)) : '';
                    $b_code = isset($b->code) ? strtolower(trim((string) $b->code)) : '';
                    return strcmp($a_code, $b_code);
                }
                return strcmp($a_name, $b_name);
            });
        }

        $this->load->model('Status_model', 'statuses');
        $status_rows = $this->_project_dashboard_status_rows();

        $project_ids = array();
        foreach ($projects as $project) {
            $project_ids[] = (int) $project->id;
        }
        $tasks_by_project = $this->_project_dashboard_tasks_for_projects($project_ids, $filter_user_id, $filter_project_id, $filter_status, $complete_view);

        $project_cards = array();
        foreach ($projects as $project) {
            $project_id = (int) $project->id;
            // If filtering by project, skip projects that don't match
            if ($filter_project_id > 0 && $project_id !== $filter_project_id) {
                continue;
            }
            // If filtering by client, skip projects that don't match
            if ($has_project_client && $filter_client_id > 0) {
                $project_client_id = isset($project->client_id) ? (int) $project->client_id : 0;
                if ($project_client_id !== $filter_client_id) {
                    continue;
                }
            }
            // If filtering by department, skip projects that don't match
            $project_dept_id = isset($project->department_id) ? (int)$project->department_id : 0;
            if ($filter_department_id > 0 && $project_dept_id !== $filter_department_id) {
                continue;
            }

            // Set department name
            $project->department_name = isset($department_map[$project_dept_id]) ? $department_map[$project_dept_id] : '';

            // If the project doesn't have tasks matching the filter, we could choose to hide it.
            // But let's just show it empty if it matches the project_id (or if no project_id filter).
            $project_cards[] = array(
                'project' => $project,
                'tasks'   => isset($tasks_by_project[$project_id]) ? dashboard_filter_items_by_complete_view(
                    $tasks_by_project[$project_id],
                    ($filter_status === 'all') ? $complete_view : 'all'
                ) : array(),
            );
        }

        $filters_active = ($filter_user_id > 0
            || $filter_project_id > 0
            || $filter_client_id > 0
            || ($filter_status !== '' && $filter_status !== 'all')
            || $filter_department_id > 0);
        if ($filters_active && !empty($project_cards)) {
            $project_cards = dashboard_sort_nonempty_first(
                $project_cards,
                function ($card) {
                    return isset($card['tasks']) ? count($card['tasks']) : 0;
                },
                function ($a, $b) {
                    $a_name = isset($a['project']->name) ? strtolower(trim((string) $a['project']->name)) : '';
                    $b_name = isset($b['project']->name) ? strtolower(trim((string) $b['project']->name)) : '';
                    if ($a_name === $b_name) {
                        $a_code = isset($a['project']->code) ? strtolower(trim((string) $a['project']->code)) : '';
                        $b_code = isset($b['project']->code) ? strtolower(trim((string) $b['project']->code)) : '';
                        return strcmp($a_code, $b_code);
                    }
                    return strcmp($a_name, $b_name);
                }
            );
        }

        $this->load->view('projects/dashboard_index', array(
            'project_cards' => $project_cards,
            'status_rows'   => $status_rows,
            'filter_user_id'    => $filter_user_id,
            'filter_project_id' => $filter_project_id,
            'filter_client_id'  => $filter_client_id,
            'filter_status'     => $filter_status,
            'filter_projects'   => $filter_projects,
            'filter_clients'    => $filter_clients,
            'filter_users'      => $filter_users,
            'filter_departments' => $filter_departments,
            'filter_department_id' => $filter_department_id,
            'complete_view'     => $complete_view,
            'complete_view_on'  => ($complete_view === 'only'),
        ));
    }

    // GET /projects/{id}/dashboard
    public function dashboard($id)
    {
        $project_id = (int) $id;
        $project = $this->db->where('id', $project_id)->get('projects')->row();
        if (!$project) {
            show_404();
            return;
        }
        if (!$this->_user_can_access_project($project_id)) {
            return;
        }

        $this->load->model('Status_model', 'statuses');
        $status_rows = $this->_project_dashboard_status_rows();
        $status_codes = $this->_project_dashboard_status_codes($status_rows);
        $columns = $this->_project_dashboard_columns($project_id, $status_codes);
        $stats = $this->_project_task_stats($project_id, $status_codes);

        $completed = isset($stats['completed']) ? (int) $stats['completed'] : 0;
        $total = 0;
        foreach ($stats as $count) {
            $total += (int) $count;
        }
        $progress = ($total > 0) ? round(($completed / $total) * 100) : 0;

        $this->load->view('projects/dashboard', array(
            'project'     => $project,
            'columns'     => $columns,
            'status_rows' => $status_rows,
            'stats'       => $stats,
            'progress'    => $progress,
        ));
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

                $client_id = $this->_resolve_client_id_from_post('projects/'.$id.'/edit');
                if ($client_id === false) {
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
                
                // Keep existing code if blank; otherwise check uniqueness
                if ($code === '') {
                    $code = isset($project->code) ? (string) $project->code : '';
                }
                if ($code === '') {
                    $code = $this->Project_model->generate_project_code();
                }
                if ($code !== (string) $project->code) {
                    $existing = $this->db->where('code', $code)->where('id !=', (int)$id)->get('projects')->row();
                    if ($existing) {
                        $this->session->set_flashdata('error', 'Project code already exists. Please use a different code.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                }
                
                $department_id = $this->input->post('department_id') ? (int) $this->input->post('department_id') : null;
                if ($department_id) {
                    $this->load->model('Department_model');
                    if (!$this->Department_model->find($department_id)) {
                        $this->session->set_flashdata('error', 'Please select a valid department.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                }
                
                $data = [
                    'code' => $code,
                    'name' => $name,
                    'status' => $this->input->post('status') ?: 'planned',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'department_id' => $department_id,
                ];
                if (schema_table_has_column($this->db, 'projects', 'client_id')) {
                    $data['client_id'] = $client_id;
                }
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

                if (schema_table_has_column($this->db, 'projects', 'estimate_hours')) {
                    $est = estimate_hours_parse($this->input->post('estimate_hours'));
                    if ($est === false) {
                        $this->session->set_flashdata('error', 'Estimate (hrs) must be a number between 0 and 9999.99.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                    $data['estimate_hours'] = $est;
                }
                $was_completed = status_is_project_completed(isset($project->status) ? $project->status : '');
                if (schema_table_has_column($this->db, 'projects', 'actual_hours')
                    && status_is_project_completed($data['status'])
                    && !$was_completed
                ) {
                    $act = actual_hours_require($this->input->post('actual_hours'));
                    if ($act === false) {
                        $this->session->set_flashdata('error', 'Actual (hrs) is required when status is Completed.');
                        redirect('projects/'.$id.'/edit');
                        return;
                    }
                    $data['actual_hours'] = $act;
                }

                if (!$this->_apply_manager_id_from_post($data)) {
                    $this->session->set_flashdata('error', 'Please select a valid assignee.');
                    redirect('projects/'.$id.'/edit');
                    return;
                }
                
                // Load activity tracking helper
                $this->load->helper('change_tracker');
                
                // Get old data before update
                $old_data = track_changes_before('projects', (int)$id);
                
                $this->db->where('id', (int)$id)->update('projects', $data);

                if (!empty($data['manager_id']) && !$this->Project_model->check_user_is_member((int) $id, (int) $data['manager_id'])) {
                    $this->Project_model->add_member((int) $id, (int) $data['manager_id'], 'manager');
                    if (!function_exists('send_user_notification_email')) {
                        $this->load->helper('email_settings');
                    }
                    $this->load->helper('notification');
                    $email_data = (object) array(
                        'id' => (int) $id,
                        'project_id' => (int) $id,
                        'title' => (string) $data['name'],
                        'name' => (string) $data['name'],
                        'role' => 'manager',
                    );
                    if (function_exists('create_notification')) {
                        create_notification(
                            (int) $data['manager_id'],
                            'Added to Project',
                            'You were added to "' . $data['name'] . '" as Manager.',
                            'info',
                            'projects',
                            (int) $id,
                            site_url('projects/' . $id)
                        );
                    }
                    send_user_notification_email((int) $data['manager_id'], 'projects', 'member_added', $email_data);
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
        
        $this->load->model('Department_model');
        $departments = $this->Department_model->all();
        
        $project_types = module_type_options_resolved('projects');
        $this->load->view('projects/form', [
            'action' => 'edit',
            'project' => $project,
            'statuses' => $statuses_list,
            'project_types' => $project_types,
            'users' => $this->_load_assignable_users(),
            'departments' => $departments,
            'clients' => $this->_load_project_clients(),
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
                if (schema_table_has_column($this->db, 'projects', 'estimate_hours')) {
                    $est_raw = csv_import_get($opened['map'], $row, 'estimate_hours', '');
                    if ($est_raw !== '') {
                        $est = estimate_hours_parse($est_raw);
                        if ($est === false) {
                            $skipped++;
                            csv_import_add_row_error($row_errors, $line, 'Invalid estimate_hours (use number 0–9999.99).');
                            continue;
                        }
                        $data['estimate_hours'] = $est;
                    }
                }
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

    // GET /projects/export
    public function export()
    {
        require_module_access(array('projects_import', 'projects_list', 'projects'), true);

        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        $filters = get_user_group_filter($user_id, $role_id);
        $can_view_all = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('projects_view_all');
        if (!$can_view_all) {
            $projects = $this->Project_model->all($filters);
        } else {
            $projects = $this->Project_model->all(array());
        }

        $has_manager = schema_table_has_column($this->db, 'projects', 'manager_id');
        $has_client = schema_table_has_column($this->db, 'projects', 'client_id');
        if ($has_manager) {
            $this->_attach_project_assignee_labels($projects);
        }

        $client_names = array();
        if ($has_client && $this->db->table_exists('clients') && !empty($projects)) {
            $client_ids = array();
            foreach ($projects as $p) {
                if (!empty($p->client_id)) {
                    $client_ids[(int) $p->client_id] = true;
                }
            }
            if (!empty($client_ids)) {
                $rows = $this->db->select('id, company_name')
                    ->where_in('id', array_keys($client_ids))
                    ->get('clients')
                    ->result();
                foreach ($rows as $c) {
                    $client_names[(int) $c->id] = (string) $c->company_name;
                }
            }
        }

        $filename = 'projects_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array(
            'id', 'code', 'name', 'status', 'start_date', 'end_date',
            'estimate_hours', 'manager', 'client',
        ));
        foreach ($projects as $p) {
            $client_label = '';
            if ($has_client && !empty($p->client_id) && isset($client_names[(int) $p->client_id])) {
                $client_label = $client_names[(int) $p->client_id];
            }
            fputcsv($out, array(
                (int) $p->id,
                isset($p->code) ? $p->code : '',
                isset($p->name) ? $p->name : '',
                isset($p->status) ? $p->status : '',
                isset($p->start_date) ? $p->start_date : '',
                isset($p->end_date) ? $p->end_date : '',
                isset($p->estimate_hours) && $p->estimate_hours !== null && $p->estimate_hours !== ''
                    ? estimate_hours_display($p->estimate_hours)
                    : '',
                $has_manager && isset($p->assignee_label) ? $p->assignee_label : '',
                $client_label,
            ));
        }
        fclose($out);
        exit;
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
                    'id' => $project_id,
                    'project_id' => $project_id,
                    'title' => $project->name,
                    'name' => $project->name,
                    'role' => $role,
                );
                send_user_notification_email($user_id, 'projects', 'member_added', $email_data);
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
        if (!in_array('client_id', $fields, true)) {
            $this->db->query("ALTER TABLE `projects` ADD `client_id` INT(11) NULL DEFAULT NULL AFTER `name`");
            $this->db->query("ALTER TABLE `projects` ADD KEY `idx_projects_client_id` (`client_id`)");
        }
    }

    /**
     * Active clients for project form dropdown.
     *
     * @return array
     */
    private function _load_project_clients()
    {
        if (!$this->db->table_exists('clients')) {
            return array();
        }
        $this->db->select('id, company_name, client_code');
        $this->db->from('clients');
        $this->db->order_by('company_name', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Resolve posted client_id; returns id, null, or false if invalid.
     *
     * @param string $redirect_path
     * @return int|null|false
     */
    private function _resolve_client_id_from_post($redirect_path)
    {
        if (!schema_table_has_column($this->db, 'projects', 'client_id')) {
            return null;
        }
        $raw = $this->input->post('client_id');
        if ($raw === null || $raw === '') {
            return null;
        }
        $client_id = (int) $raw;
        if ($client_id <= 0 || !$this->db->table_exists('clients')) {
            $this->session->set_flashdata('error', 'Please select a valid client.');
            redirect($redirect_path);
            return false;
        }
        $exists = $this->db->select('id')->where('id', $client_id)->get('clients')->row();
        if (!$exists) {
            $this->session->set_flashdata('error', 'Please select a valid client.');
            redirect($redirect_path);
            return false;
        }
        return $client_id;
    }

    /**
     * Unique project code for insert (always generated when blank).
     *
     * @param string $posted
     * @return string|false
     */
    private function _resolve_project_code_for_create($posted)
    {
        $code = trim((string) $posted);
        if ($code === '') {
            $code = $this->Project_model->generate_project_code();
        }
        $tries = 0;
        while ($tries < 20) {
            $existing = $this->db->where('code', $code)->get('projects')->row();
            if (!$existing) {
                return $code;
            }
            if (trim((string) $posted) !== '') {
                return false;
            }
            $code = $this->Project_model->generate_project_code();
            $tries++;
        }
        return false;
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

    /**
     * @return array
     */
    private function _project_dashboard_status_rows()
    {
        $rows = $this->statuses->get_by_type('tasks', true);
        if (!empty($rows)) {
            return $rows;
        }
        return array(
            (object) array('code' => 'pending', 'name' => 'Pending', 'color' => '#6c757d'),
            (object) array('code' => 'in_progress', 'name' => 'In Progress', 'color' => '#007bff'),
            (object) array('code' => 'completed', 'name' => 'Completed', 'color' => '#28a745'),
            (object) array('code' => 'blocked', 'name' => 'Blocked', 'color' => '#dc3545'),
        );
    }

    /**
     * @param array $status_rows
     * @return array
     */
    private function _project_dashboard_status_codes($status_rows)
    {
        $codes = array();
        foreach ($status_rows as $row) {
            $code = trim((string) $row->code);
            if ($code !== '') {
                $codes[] = $code;
            }
        }
        if (empty($codes)) {
            $codes = array('pending', 'in_progress', 'completed', 'blocked');
        }
        return $codes;
    }

    /**
     * @param int $project_id
     * @param array $status_codes
     * @return array
     */
    private function _project_task_stats($project_id, $status_codes)
    {
        $stats = array();
        foreach ($status_codes as $code) {
            $stats[$code] = 0;
        }

        if (!$this->db->table_exists('tasks')) {
            return $stats;
        }

        $this->db->select('status, COUNT(*) AS cnt', false);
        $this->db->from('tasks');
        $this->db->where('project_id', (int) $project_id);
        apply_role_hierarchy_filter($this->db, 'created_by');
        $this->db->group_by('status');
        $rows = $this->db->get()->result();

        foreach ($rows as $row) {
            $status = trim((string) $row->status);
            if ($status === '') {
                $status = 'pending';
            }
            if (!isset($stats[$status])) {
                $stats[$status] = 0;
            }
            $stats[$status] += (int) $row->cnt;
        }

        return $stats;
    }

    /**
     * @param array $project_ids
     * @param int $filter_user_id
     * @param int $filter_project_id
     * @param string $filter_status
     * @return array<int, array>
     */
    private function _project_dashboard_tasks_for_projects($project_ids, $filter_user_id = -1, $filter_project_id = -1, $filter_status = 'all', $complete_view = 'hide')
    {
        $grouped = array();
        if (empty($project_ids) || !$this->db->table_exists('tasks')) {
            return $grouped;
        }

        $ids = array();
        foreach ($project_ids as $project_id) {
            $project_id = (int) $project_id;
            if ($project_id > 0) {
                $ids[] = $project_id;
            }
        }
        if (empty($ids)) {
            return $grouped;
        }

        $this->db->from('tasks t');
        $select = array(
            't.id',
            't.project_id',
            't.title',
            't.status',
            't.due_date',
            't.start_date',
            't.created_at',
            't.assigned_to',
        );
        if (schema_table_has_column($this->db, 'tasks', 'estimate_hours')) {
            $select[] = 't.estimate_hours';
        }
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) {
                $select[] = 'u.full_name AS assignee_full_name';
            }
            if (schema_table_has_column($this->db, 'users', 'name')) {
                $select[] = 'u.name AS assignee_name';
            }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            if (schema_table_has_column($this->db, 'employees', 'name')) {
                $select[] = 'e.name AS emp_name';
            }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        $this->db->select(implode(',', $select));
        $this->db->where_in('t.project_id', $ids);

        if ($filter_status !== 'all' && schema_table_has_column($this->db, 'tasks', 'status')) {
            $this->db->where('t.status', $filter_status);
        }
        dashboard_apply_complete_view_to_query($this->db, 't.status', $complete_view, $filter_status, 'task');
        if ($filter_user_id > 0 && schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
            $this->db->where('t.assigned_to', $filter_user_id);
        }
        apply_role_hierarchy_filter($this->db, 't.created_by');
        $this->db->order_by('t.project_id', 'ASC');
        $this->db->order_by('t.title', 'ASC');
        $this->db->order_by('t.id', 'ASC');
        $rows = $this->db->get()->result();

        foreach ($rows as $row) {
            $project_id = (int) $row->project_id;
            if (!isset($grouped[$project_id])) {
                $grouped[$project_id] = array();
            }
            $grouped[$project_id][] = $row;
        }

        return $grouped;
    }

    /**
     * @param int $project_id
     * @param array $status_codes
     * @return array
     */
    private function _project_dashboard_columns($project_id, $status_codes)
    {
        $columns = array();
        if (!$this->db->table_exists('tasks')) {
            foreach ($status_codes as $code) {
                $columns[$code] = array();
            }
            return $columns;
        }

        foreach ($status_codes as $status_code) {
            $this->db->from('tasks t');
            $select = array('t.*');
            if ($this->db->table_exists('users')) {
                $select[] = 'u.email AS assignee_email';
                if (schema_table_has_column($this->db, 'users', 'full_name')) {
                    $select[] = 'u.full_name';
                }
                if (schema_table_has_column($this->db, 'users', 'name')) {
                    $select[] = 'u.name';
                }
                $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            }
            if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                if (schema_table_has_column($this->db, 'employees', 'name')) {
                    $select[] = 'e.name AS emp_name';
                }
                $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
            }
            $this->db->select(implode(',', $select));
            $this->db->where('t.project_id', (int) $project_id);
            $this->db->where('t.status', (string) $status_code);
            apply_role_hierarchy_filter($this->db, 't.created_by');
            $this->db->order_by('t.id', 'DESC');
            $columns[$status_code] = $this->db->get()->result();
        }

        return $columns;
    }

    // POST /projects/{id}/inline-save — quick add/update from project detail tabs
    public function inline_save($project_id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $project_id = (int) $project_id;
        if (!$this->_user_can_access_project($project_id)) {
            return;
        }

        $project = $this->db->where('id', $project_id)->get('projects')->row();
        if (!$project) {
            return $this->_inline_json(false, array(), 'Project not found', 404);
        }

        $type = trim((string) $this->input->post('type'));
        $item_id = (int) $this->input->post('id');
        $title = trim((string) $this->input->post('title'));
        $status = trim((string) $this->input->post('status'));
        $priority = trim((string) $this->input->post('priority'));
        $assigned_raw = $this->input->post('assigned_to');
        $assigned_to = ($assigned_raw !== '' && $assigned_raw !== null) ? (int) $assigned_raw : null;
        $estimate_hours = array_key_exists('estimate_hours', $_POST) ? $this->input->post('estimate_hours') : null;

        if ($type === 'task') {
            return $this->_inline_save_task($project_id, $item_id, $title, $status, $priority, $assigned_to, $estimate_hours);
        }
        if ($type === 'requirement') {
            return $this->_inline_save_requirement($project, $item_id, $title, $status, $priority, $assigned_to);
        }
        if ($type === 'defect') {
            return $this->_inline_save_defect($project_id, $item_id, $title, $status, $priority, $assigned_to);
        }

        return $this->_inline_json(false, array(), 'Invalid type', 400);
    }

    // POST /projects/{id}/inline-delete — delete row from project detail tabs
    public function inline_delete($project_id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $project_id = (int) $project_id;
        if (!$this->_user_can_access_project($project_id)) {
            return;
        }

        $type = trim((string) $this->input->post('type'));
        $item_id = (int) $this->input->post('id');
        if ($item_id < 1) {
            return $this->_inline_json(false, array(), 'Invalid id', 400);
        }

        if ($type === 'task') {
            return $this->_inline_delete_task($project_id, $item_id);
        }
        if ($type === 'requirement') {
            return $this->_inline_delete_requirement($project_id, $item_id);
        }
        if ($type === 'defect') {
            return $this->_inline_delete_defect($project_id, $item_id);
        }

        return $this->_inline_json(false, array(), 'Invalid type', 400);
    }

    private function _inline_delete_task($project_id, $item_id)
    {
        if (!has_module_access('tasks_delete') && !has_module_access('tasks')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }

        $task = $this->db->where('id', $item_id)->where('project_id', $project_id)->get('tasks')->row();
        if (!$task) {
            return $this->_inline_json(false, array(), 'Task not found', 404);
        }

        $current_user = (int) $this->session->userdata('user_id');
        $can_manage_all = is_admin_group() || has_module_access('tasks_delete_all');
        if ($current_user > 0 && !$can_manage_all) {
            $assigned = isset($task->assigned_to) ? (int) $task->assigned_to : 0;
            $creator = isset($task->created_by) ? (int) $task->created_by : 0;
            if ($assigned !== $current_user && $creator !== $current_user) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
        }

        $this->load->helper('change_tracker');
        $old_data = (array) $task;
        $this->db->where('id', $item_id)->delete('tasks');
        $description = 'Task deleted' . (isset($task->title) ? ': ' . $task->title : '');
        auto_log_delete('tasks', 'tasks', $item_id, $old_data, $description);

        return $this->_inline_json(true, array('id' => $item_id));
    }

    private function _inline_delete_requirement($project_id, $item_id)
    {
        if (!$this->db->table_exists('requirements')) {
            return $this->_inline_json(false, array(), 'Requirements module unavailable', 400);
        }
        if (!has_module_access('requirements_delete') && !has_module_access('requirements')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }

        $req = $this->db->where('id', $item_id)->where('project_id', $project_id)->get('requirements')->row();
        if (!$req) {
            return $this->_inline_json(false, array(), 'Requirement not found', 404);
        }

        $current_user = (int) $this->session->userdata('user_id');
        $can_manage_all = is_admin_group() || has_module_access('requirements_delete_all');
        if ($current_user > 0 && !$can_manage_all) {
            $creator = isset($req->created_by) ? (int) $req->created_by : 0;
            $owner = isset($req->owner_id) ? (int) $req->owner_id : 0;
            if ($creator !== $current_user && $owner !== $current_user) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
        }

        if ($this->db->table_exists('tasks') && schema_table_has_column($this->db, 'tasks', 'requirement_id')) {
            $this->db->where('requirement_id', $item_id)->update('tasks', array('requirement_id' => null));
        }
        if ($this->db->table_exists('requirement_versions')) {
            $this->db->where('requirement_id', $item_id)->delete('requirement_versions');
        }
        if ($this->db->table_exists('requirement_comments')) {
            $this->db->where('requirement_id', $item_id)->delete('requirement_comments');
        }
        if ($this->db->table_exists('requirement_attachments')) {
            $this->db->where('requirement_id', $item_id)->delete('requirement_attachments');
        }
        $this->db->where('id', $item_id)->delete('requirements');

        return $this->_inline_json(true, array('id' => $item_id));
    }

    private function _inline_delete_defect($project_id, $item_id)
    {
        if (!$this->db->table_exists('project_defects')) {
            return $this->_inline_json(false, array(), 'Defects module unavailable', 400);
        }
        if (!has_module_access('defects_delete') && !has_module_access('defects')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }

        $this->load->model('Defect_model', 'project_defects');
        $this->load->helper(array('change_tracker', 'activity'));
        $defect = $this->project_defects->get_defect($item_id);
        if (!$defect || (int) $defect->project_id !== $project_id) {
            return $this->_inline_json(false, array(), 'Defect not found', 404);
        }

        auto_log_delete('defects', 'project_defects', $item_id, (array) $defect, 'Defect deleted: ' . $defect->defect_number);
        $this->project_defects->delete_defect($item_id);
        return $this->_inline_json(true, array('id' => $item_id));
    }

    private function _inline_json($ok, $data = array(), $error = '', $status = 200)
    {
        $payload = array('ok' => (bool) $ok);
        if ($ok) {
            $payload = array_merge($payload, $data);
        } else {
            $payload['error'] = $error !== '' ? $error : 'Request failed';
        }
        return $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function _inline_save_task($project_id, $item_id, $title, $status, $priority, $assigned_to, $estimate_hours = null)
    {
        $allowed_status = array('pending', 'in_progress', 'completed', 'blocked');
        $allowed_priority = array('low', 'medium', 'high', 'urgent');
        if ($status === '' || !in_array($status, $allowed_status, true)) {
            $status = 'pending';
        }
        if ($priority === '' || !in_array($priority, $allowed_priority, true)) {
            $priority = 'medium';
        }

        $est = null;
        $est_provided = ($estimate_hours !== null);
        if ($est_provided) {
            $est = estimate_hours_parse($estimate_hours);
            if ($est === false) {
                return $this->_inline_json(false, array(), 'Estimate (hrs) must be a number between 0 and 9999.99.', 400);
            }
        }

        if ($item_id > 0) {
            if (!has_module_access('tasks_edit') && !has_module_access('tasks')) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
            $task = $this->db->where('id', $item_id)->where('project_id', $project_id)->get('tasks')->row();
            if (!$task) {
                return $this->_inline_json(false, array(), 'Task not found', 404);
            }
            if ($title === '') {
                return $this->_inline_json(false, array(), 'Title is required', 400);
            }
            $update = array(
                'title' => $title,
                'status' => $status,
                'assigned_to' => $assigned_to,
            );
            $task_fields = $this->db->list_fields('tasks');
            if (in_array('priority', $task_fields, true)) {
                $update['priority'] = $priority;
            }
            if ($est_provided && in_array('estimate_hours', $task_fields, true)) {
                $update['estimate_hours'] = $est;
            }
            $this->db->where('id', $item_id)->update('tasks', $update);
            return $this->_inline_json(true, array('id' => $item_id));
        }

        if (!has_module_access('tasks_add') && !has_module_access('tasks')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($title === '') {
            $title = 'New task';
        }

        $user_id = (int) $this->session->userdata('user_id');
        $task_fields = $this->db->list_fields('tasks');
        $insert = array(
            'project_id' => $project_id,
            'title' => $title,
            'status' => $status,
            'assigned_to' => $assigned_to,
            'created_by' => $user_id,
        );
        if (in_array('priority', $task_fields, true)) {
            $insert['priority'] = $priority;
        }
        if ($est_provided && in_array('estimate_hours', $task_fields, true)) {
            $insert['estimate_hours'] = $est;
        }
        $this->db->insert('tasks', $insert);
        $new_id = (int) $this->db->insert_id();
        return $this->_inline_json(true, array('id' => $new_id));
    }

    private function _inline_save_requirement($project, $item_id, $title, $status, $priority, $assigned_to)
    {
        if (!$this->db->table_exists('requirements')) {
            return $this->_inline_json(false, array(), 'Requirements module unavailable', 400);
        }

        $allowed_status = array('received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled');
        $allowed_priority = array('low', 'medium', 'high', 'urgent');
        if ($status === '' || !in_array($status, $allowed_status, true)) {
            $status = 'received';
        }
        if ($priority === '' || !in_array($priority, $allowed_priority, true)) {
            $priority = 'medium';
        }

        if ($item_id > 0) {
            if (!has_module_access('requirements_edit') && !has_module_access('requirements')) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
            $req = $this->db->where('id', $item_id)->where('project_id', (int) $project->id)->get('requirements')->row();
            if (!$req) {
                return $this->_inline_json(false, array(), 'Requirement not found', 404);
            }
            if ($title === '') {
                return $this->_inline_json(false, array(), 'Title is required', 400);
            }
            $this->db->where('id', $item_id)->update('requirements', array(
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assigned_to,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            return $this->_inline_json(true, array('id' => $item_id, 'req_number' => $req->req_number));
        }

        if (!has_module_access('requirements_add') && !has_module_access('requirements')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($title === '') {
            $title = 'New requirement';
        }

        $client_id = 0;
        if (schema_table_has_column($this->db, 'projects', 'client_id') && !empty($project->client_id)) {
            $client_id = (int) $project->client_id;
        }
        if ($client_id < 1 && $this->db->table_exists('clients')) {
            $client_row = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('clients')->row();
            if ($client_row) {
                $client_id = (int) $client_row->id;
            }
        }
        if ($client_id < 1) {
            return $this->_inline_json(false, array(), 'No client available for requirement', 400);
        }

        $user_id = (int) $this->session->userdata('user_id');
        $req_number = $this->_inline_generate_req_number();
        $now = date('Y-m-d H:i:s');
        $insert = array(
            'req_number' => $req_number,
            'client_id' => $client_id,
            'project_id' => (int) $project->id,
            'title' => $title,
            'description' => '',
            'requirement_type' => 'new_feature',
            'priority' => $priority,
            'status' => $status,
            'received_date' => date('Y-m-d'),
            'owner_id' => $user_id,
            'assigned_to' => $assigned_to,
            'created_by' => $user_id,
            'created_at' => $now,
            'updated_at' => $now,
        );
        $this->db->insert('requirements', $insert);
        $new_id = (int) $this->db->insert_id();

        if ($this->db->table_exists('requirement_versions')) {
            $this->db->insert('requirement_versions', array(
                'requirement_id' => $new_id,
                'version_no' => 1,
                'title' => $title,
                'description' => '',
                'requirement_type' => 'new_feature',
                'priority' => $priority,
                'status' => $status,
                'received_date' => date('Y-m-d'),
                'owner_id' => $user_id,
                'assigned_to' => $assigned_to,
                'created_by' => $user_id,
                'created_at' => $now,
            ));
        }

        return $this->_inline_json(true, array('id' => $new_id, 'req_number' => $req_number));
    }

    private function _inline_save_defect($project_id, $item_id, $title, $status, $priority, $assigned_to)
    {
        if (!$this->db->table_exists('project_defects')) {
            return $this->_inline_json(false, array(), 'Defects module unavailable', 400);
        }

        $allowed_status = array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected');
        $allowed_priority = array('low', 'medium', 'high', 'critical');
        if ($status === '' || !in_array($status, $allowed_status, true)) {
            $status = 'open';
        }
        if ($priority === '' || !in_array($priority, $allowed_priority, true)) {
            $priority = 'medium';
        }

        $this->load->model('Defect_model', 'project_defects');
        $this->load->helper(array('defects_releases', 'change_tracker', 'activity'));

        if ($item_id > 0) {
            if (!has_module_access('defects_edit') && !has_module_access('defects')) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
            $defect = $this->project_defects->get_defect($item_id);
            if (!$defect || (int) $defect->project_id !== $project_id) {
                return $this->_inline_json(false, array(), 'Defect not found', 404);
            }
            if ($title === '') {
                return $this->_inline_json(false, array(), 'Title is required', 400);
            }
            $uid = (int) $this->session->userdata('user_id');
            $old_assignee = (int) $defect->assigned_to;
            $this->project_defects->save_defect(array(
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assigned_to,
            ), $item_id);
            if ($assigned_to && (int) $assigned_to !== $old_assignee) {
                defect_notify_assignee($item_id, (int) $assigned_to, $title, $uid);
            }
            return $this->_inline_json(true, array('id' => $item_id, 'defect_number' => $defect->defect_number));
        }

        if (!has_module_access('defects_add') && !has_module_access('defects')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($title === '') {
            $title = 'New defect';
        }

        $uid = (int) $this->session->userdata('user_id');
        $defect_number = $this->project_defects->next_defect_number();
        $new_id = $this->project_defects->save_defect(array(
            'defect_number' => $defect_number,
            'project_id' => $project_id,
            'title' => $title,
            'description' => '',
            'severity' => 'medium',
            'priority' => $priority,
            'status' => $status,
            'reported_by' => $uid,
            'assigned_to' => $assigned_to,
        ));
        $this->project_defects->log_activity($new_id, $uid, 'created', 'Created from project tab');
        if ($assigned_to) {
            defect_notify_assignee($new_id, (int) $assigned_to, $title, $uid);
        }

        return $this->_inline_json(true, array('id' => $new_id, 'defect_number' => $defect_number));
    }

    private function _inline_generate_req_number()
    {
        $year = date('Y');
        $prefix = 'REQ-' . $year . '-';
        $row = $this->db->like('req_number', $prefix, 'after')->order_by('id', 'DESC')->limit(1)->get('requirements')->row();
        $num = 0;
        if ($row && isset($row->req_number)) {
            $tail = substr($row->req_number, -5);
            if (ctype_digit($tail)) {
                $num = (int) $tail;
            }
        }
        $num++;
        return $prefix . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }
}
