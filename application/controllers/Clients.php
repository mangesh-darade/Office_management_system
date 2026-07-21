<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','error_handler','schema_columns','types','validation']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_controller_access('clients', true);
        
        $this->ensure_schema();
        $this->load->model('Client_model','clients');
    }

    private function ensure_schema() {
        $this->load->helper('clients_schema');
        clients_schema_ensure($this->db);
        $this->load->model('Type_model', 'module_types');
    }

    private function _client_type_options()
    {
        return module_type_options_resolved('clients');
    }

    private function _resolve_client_type($posted)
    {
        $type = module_type_validate_code($posted, 'clients', false, 'company');
        return $type === false ? 'company' : $type;
    }

    /**
     * @param string $field_label
     * @param string $redirect_path
     * @return string|null
     */
    private function _normalize_client_url($field_name, $field_label, $redirect_path)
    {
        $normalized = normalize_optional_url($this->input->post($field_name));
        if ($normalized === false) {
            $this->session->set_flashdata('error', 'Please enter a valid ' . $field_label . ' or leave it blank.');
            redirect($redirect_path);
            exit;
        }
        return $normalized;
    }

    private function upload_logo($existing = null){
        if (!isset($_FILES['logo']) || empty($_FILES['logo']['name'])){
            return $existing;
        }
        $base_path = FCPATH.'uploads/clients';
        if (!is_dir($base_path)){
            @mkdir($base_path, 0755, true);
        }
        $config = [
            'upload_path' => $base_path,
            'allowed_types' => 'gif|jpg|jpeg|png|webp|svg',
            'max_size' => 2048,
            'encrypt_name' => true,
        ];
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('logo')){
            $error = trim(strip_tags($this->upload->display_errors('', '')));
            if ($error !== ''){
                $this->session->set_flashdata('error', $error);
            }
            return $existing;
        }
        $data = $this->upload->data();
        return 'uploads/clients/'.$data['file_name'];
    }

    // GET /clients
    public function index(){
        require_module_access(['clients_list', 'clients'], true);
        $client_types = $this->_client_type_options();
        $active_tab = $this->input->get('tab');
        $active_tab = ($active_tab === 'cart') ? 'cart' : 'list';

        if ($active_tab === 'cart') {
            $cart = $this->_build_client_cart_data();
            $this->load->view('clients/index', array_merge($cart, array(
                'active_tab'   => 'cart',
                'client_types' => $client_types,
                'rows'         => array(),
                'lanes'        => array(),
                'show_lanes'   => false,
                'filters'      => array(),
                'type_counts'  => array(),
                'status_counts'=> array(),
                'stats_total'  => isset($cart['client_cards']) ? count($cart['client_cards']) : 0,
                'pagination'   => array(
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 0,
                    'total_pages' => 1,
                ),
            )));
            return;
        }

        try {
            $filters = [
                'status' => $this->input->get('status'),
                'client_type' => $this->input->get('client_type'),
                'search' => $this->input->get('q'),
                'sort' => $this->input->get('sort'),
                'dir' => $this->input->get('dir'),
            ];
            if (!empty($filters['client_type']) && !isset($client_types[$filters['client_type']])) {
                $filters['client_type'] = '';
            }
            $this->load->helper('module_status');
            if (!empty($filters['status']) && !module_status_is_valid($filters['status'], 'clients')) {
                $filters['status'] = '';
            }

            $status_counts = array();
            $sc = safe_db_operation(function () use ($filters) {
                return $this->clients->counts_by_status($filters);
            }, 'Unable to load status counts.');
            if (!empty($sc['success']) && is_array($sc['data'])) {
                $status_counts = $sc['data'];
            }

            $type_counts = array();
            $tc = safe_db_operation(function () use ($filters) {
                return $this->clients->counts_by_client_type($filters);
            }, 'Unable to load type counts.');
            if (!empty($tc['success']) && is_array($tc['data'])) {
                $raw = $tc['data'];
                foreach ($client_types as $code => $_label) {
                    $type_counts[$code] = isset($raw[$code]) ? (int) $raw[$code] : 0;
                }
            }

            $stats_base = $filters;
            unset($stats_base['status']);
            $stats_total = 0;
            $st = safe_db_operation(function () use ($stats_base) {
                return (int) $this->clients->count_clients($stats_base);
            }, 'Unable to count clients.');
            if (!empty($st['success'])) {
                $stats_total = (int) $st['data'];
            }

            // Card/lane board: Clients (all) or Active status, with no type filter
            $status_filter = isset($filters['status']) ? (string) $filters['status'] : '';
            $type_filter = isset($filters['client_type']) ? (string) $filters['client_type'] : '';
            $show_lanes = ($type_filter === '' && ($status_filter === '' || $status_filter === 'active'));

            $rows = array();
            $lanes = array();
            $pagination = array(
                'page' => 1,
                'per_page' => 25,
                'total' => 0,
                'total_pages' => 1,
            );

            if ($show_lanes) {
                $board_filters = $filters;
                unset($board_filters['client_type']);
                $result = safe_db_operation(function () use ($board_filters) {
                    return $this->clients->get_clients($board_filters, 1000, 0);
                }, 'Unable to load clients. Please try again.');
                if (!$result['success']) {
                    $this->session->set_flashdata('error', $result['error']);
                } else {
                    $rows = $result['data'];
                }
                foreach ($client_types as $code => $_label) {
                    $lanes[$code] = array();
                }
                foreach ($rows as $row) {
                    $code = isset($row->client_type) ? (string) $row->client_type : 'company';
                    if (!isset($lanes[$code])) {
                        if (!isset($client_types[$code])) {
                            continue;
                        }
                        $lanes[$code] = array();
                    }
                    $lanes[$code][] = $row;
                }
                $pagination['total'] = is_array($rows) ? count($rows) : 0;
            } else {
                $per_page = 25;
                $page = max(1, (int) $this->input->get('page'));
                $offset = ($page - 1) * $per_page;
                $total = 0;

                $total_result = safe_db_operation(function () use ($filters) {
                    return (int) $this->clients->count_clients($filters);
                }, 'Unable to count clients.');
                if (!empty($total_result['success'])) {
                    $total = (int) $total_result['data'];
                }
                $total_pages = max(1, (int) ceil($total / $per_page));
                if ($page > $total_pages) {
                    $page = $total_pages;
                    $offset = ($page - 1) * $per_page;
                }

                $result = safe_db_operation(function () use ($filters, $per_page, $offset) {
                    return $this->clients->get_clients($filters, $per_page, $offset);
                }, 'Unable to load clients. Please try again.');

                if (!$result['success']) {
                    $this->session->set_flashdata('error', $result['error']);
                } else {
                    $rows = $result['data'];
                }

                $pagination = array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'total_pages' => $total_pages,
                );
            }

            $this->load->view('clients/index', [
                'rows' => $rows,
                'lanes' => $lanes,
                'show_lanes' => $show_lanes,
                'filters' => $filters,
                'client_types' => $client_types,
                'type_counts' => $type_counts,
                'status_counts' => $status_counts,
                'stats_total' => $stats_total,
                'pagination' => $pagination,
                'active_tab' => 'list',
            ]);

        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load clients list. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            $this->load->view('clients/index', $this->_clients_index_empty($client_types));
        } catch (Throwable $e) {
            $error_message = handle_database_error($e, 'Unable to load clients list. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            $this->load->view('clients/index', $this->_clients_index_empty($client_types));
        }
    }

    private function _clients_index_empty($client_types)
    {
        return array(
            'rows' => array(),
            'lanes' => array(),
            'show_lanes' => true,
            'filters' => array(),
            'client_types' => $client_types,
            'type_counts' => array(),
            'status_counts' => array(),
            'stats_total' => 0,
            'pagination' => array(
                'page' => 1,
                'per_page' => 25,
                'total' => 0,
                'total_pages' => 1,
            ),
            'active_tab' => 'list',
        );
    }

    /**
     * Client cart data: clients with nested projects and tasks (alphabetical).
     *
     * @return array{client_cards:array,total_projects:int,total_tasks:int}
     */
    private function _build_client_cart_data()
    {
        $clients = safe_db_operation(function () {
            return $this->clients->get_clients(array('sort' => 'company_name', 'dir' => 'asc'), 500, 0);
        }, 'Unable to load clients.');
        $client_rows = (!empty($clients['success']) && is_array($clients['data'])) ? $clients['data'] : array();

        $projects_by_client = array();
        $tasks_by_project = array();
        $all_project_ids = array();

        if ($this->db->table_exists('projects')
            && schema_table_has_column($this->db, 'projects', 'client_id')
            && !empty($client_rows)
        ) {
            $client_ids = array();
            foreach ($client_rows as $c) {
                $client_ids[] = (int) $c->id;
            }
            $client_ids = array_values(array_filter($client_ids));

            if (!empty($client_ids)) {
                $this->db->from('projects');
                $this->db->where_in('client_id', $client_ids);
                $this->db->order_by('name', 'ASC');
                foreach ($this->db->get()->result() as $project) {
                    $cid = (int) $project->client_id;
                    $pid = (int) $project->id;
                    if (!isset($projects_by_client[$cid])) {
                        $projects_by_client[$cid] = array();
                    }
                    $projects_by_client[$cid][] = $project;
                    $all_project_ids[] = $pid;
                }
            }
        }

        if ($this->db->table_exists('tasks') && !empty($all_project_ids)) {
            $sel = array('t.id', 't.title', 't.status', 't.project_id');
            if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
                $sel[] = 't.due_date';
            }
            if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                $sel[] = 't.assigned_to';
            }
            $this->db->select(implode(',', $sel));
            $this->db->from('tasks t');
            $this->db->where_in('t.project_id', $all_project_ids);
            if (schema_table_has_column($this->db, 'tasks', 'assigned_to')
                && $this->db->table_exists('users')
            ) {
                $this->db->select('u.name AS assignee_name, u.email AS assignee_email', false);
                $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            }
            $this->db->order_by('t.id', 'DESC');
            foreach ($this->db->get()->result() as $task) {
                $pid = (int) $task->project_id;
                if (!isset($tasks_by_project[$pid])) {
                    $tasks_by_project[$pid] = array();
                }
                $tasks_by_project[$pid][] = $task;
            }
        }

        $client_cards = array();
        $total_projects = 0;
        $total_tasks = 0;
        foreach ($client_rows as $c) {
            $cid = (int) $c->id;
            $projects = isset($projects_by_client[$cid]) ? $projects_by_client[$cid] : array();
            $project_sections = array();
            $client_task_count = 0;
            foreach ($projects as $project) {
                $pid = (int) $project->id;
                $tasks = isset($tasks_by_project[$pid]) ? $tasks_by_project[$pid] : array();
                $client_task_count += count($tasks);
                $project_sections[] = array(
                    'project' => $project,
                    'tasks'   => $tasks,
                );
            }
            $total_projects += count($project_sections);
            $total_tasks += $client_task_count;
            $client_cards[] = array(
                'client'        => $c,
                'projects'      => $project_sections,
                'project_count' => count($project_sections),
                'task_count'    => $client_task_count,
            );
        }

        usort($client_cards, function ($a, $b) {
            $an = isset($a['client']->company_name) ? strtolower(trim((string) $a['client']->company_name)) : '';
            $bn = isset($b['client']->company_name) ? strtolower(trim((string) $b['client']->company_name)) : '';
            return strcmp($an, $bn);
        });

        return array(
            'client_cards'   => $client_cards,
            'total_projects' => $total_projects,
            'total_tasks'    => $total_tasks,
        );
    }

    // GET/POST /clients/create
    public function create(){
        require_module_access(['clients_add', 'clients'], true);
        if ($this->input->method() === 'post'){
            try {
                // Validation rules - Mandatory fields
                $this->load->library('form_validation');
                
                // Mandatory fields
                $this->form_validation->set_rules('company_name', 'Company Name', 'required|trim|min_length[2]|max_length[255]');
                $this->form_validation->set_rules('contact_person', 'Contact Person', 'required|trim|min_length[2]|max_length[200]');
                $this->form_validation->set_rules('phone', 'Phone', 'required|trim|min_length[10]|max_length[20]|regex_match[/^[0-9+\s\-\(\)]+$/]');
                
                if ($this->form_validation->run() == FALSE) {
                    $errors = validation_errors();
                    $this->session->set_flashdata('error', handle_validation_error($errors));
                    redirect('clients/create');
                    return;
                }
                
                // Additional business logic validation
                $company_name = trim($this->input->post('company_name'));
                $contact_person = trim($this->input->post('contact_person'));
                $email = trim($this->input->post('email'));
                $phone = trim($this->input->post('phone'));
                
                // Double-check mandatory fields (server-side validation)
                if (empty($company_name)) {
                    $this->session->set_flashdata('error', 'Company Name is required.');
                    redirect('clients/create');
                    return;
                }
                if (empty($contact_person)) {
                    $this->session->set_flashdata('error', 'Contact Person is required.');
                    redirect('clients/create');
                    return;
                }
                if (empty($phone)) {
                    $this->session->set_flashdata('error', 'Phone is required.');
                    redirect('clients/create');
                    return;
                }
                
                // Check if email already exists
                if (!empty($email)) {
                    if ($this->clients->email_exists($email)) {
                        $this->session->set_flashdata('error', 'This email address is already registered. Please use a different email.');
                        redirect('clients/create');
                        return;
                    }
                }
                
                // Check if phone already exists
                if (!empty($phone)) {
                    if ($this->clients->phone_exists($phone)) {
                        $this->session->set_flashdata('error', 'This phone number is already registered. Please use a different phone number.');
                        redirect('clients/create');
                        return;
                    }
                }
                
                // Generate client code
                $client_code = $this->clients->generate_client_code();
                if (empty($client_code)) {
                    $this->session->set_flashdata('error', 'Unable to generate client code. Please try again.');
                    redirect('clients/create');
                    return;
                }
                
                // Upload logo
                $logo_path = $this->upload_logo();

                $website = $this->_normalize_client_url('website', 'URL', 'clients/create');
                $demo_url = $this->_normalize_client_url('demo_url', 'Demo URL', 'clients/create');
                $pos_url = $this->_normalize_client_url('pos_url', 'POS URL', 'clients/create');
                
                // Prepare data
                $data = [
                    'client_code' => $client_code,
                    'company_name' => $company_name,
                    'contact_person' => $contact_person,
                    'email' => $email,
                    'phone' => $phone,
                    'alternate_phone' => trim($this->input->post('alternate_phone')),
                    'website' => $website,
                    'demo_url' => $demo_url,
                    'pos_url' => $pos_url,
                    'address' => trim($this->input->post('address')),
                    'city' => trim($this->input->post('city')),
                    'state' => trim($this->input->post('state')),
                    'country' => trim($this->input->post('country')) ?: 'India',
                    'zip_code' => trim($this->input->post('zip_code')),
                    'gstin' => trim($this->input->post('gstin')),
                    'pan_number' => trim($this->input->post('pan_number')),
                    'industry' => trim($this->input->post('industry')),
                    'onboarding_date' => $this->input->post('onboarding_date') ?: null,
                    'client_type' => $this->_resolve_client_type($this->input->post('client_type')),
                    'account_manager_id' => $this->input->post('account_manager_id') !== '' ? (int)$this->input->post('account_manager_id') : null,
                    'notes' => trim($this->input->post('notes')),
                    'db_name' => trim($this->input->post('db_name')),
                    'db_username' => trim($this->input->post('db_username')),
                    'db_password' => trim($this->input->post('db_password')),
                    'db_host' => trim($this->input->post('db_host')),
                    'db_port' => trim($this->input->post('db_port')),
                    'logo' => $logo_path,
                    'status' => 'active',
                    'created_by' => (int)$this->session->userdata('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                // Create client with error handling
                $result = safe_db_operation(function() use ($data) {
                    return $this->clients->create_client($data);
                }, 'Failed to create client. Please try again.');
                
                if (!$result['success']) {
                    $this->session->set_flashdata('error', $result['error']);
                    redirect('clients/create');
                    return;
                }
                
                $id = $result['data'];
                if (empty($id) || $id <= 0) {
                    $error_msg = get_notification_message('clients', 'create', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                    redirect('clients/create');
                    return;
                }
                
                $success_msg = get_notification_message('clients', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('clients/view/'.$id);
                return;
                
            } catch (Exception $e) {
                $error_message = handle_database_error($e, 'An error occurred while creating the client. Please try again.');
                $this->session->set_flashdata('error', $error_message);
                redirect('clients/create');
                return;
            } catch (Throwable $e) {
                $error_message = handle_database_error($e, 'An error occurred while creating the client. Please try again.');
                $this->session->set_flashdata('error', $error_message);
                redirect('clients/create');
                return;
            }
        }
        
        try {
            $managers = $this->clients->get_account_managers();
            $this->load->view('clients/create', [
                'managers'=>$managers,
                'client_types'=>$this->_client_type_options(),
            ]);
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client creation form. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }

    /**
     * Second Brain → Clients tab: alphabetical client cards with projects;
     * click a project row to expand its tasks.
     * GET /clients/dashboard?embed=1
     */
    public function dashboard()
    {
        require_module_access(array('clients_list', 'clients_view', 'clients'), true);

        $embed = (bool) $this->input->get('embed');
        $cart = $this->_build_client_cart_data();

        $this->load->view('clients/dashboard', array_merge($cart, array(
            'embed' => $embed,
        )));
    }

    // GET /clients/view/{id}
    public function view($id){
        require_module_access(['clients_view', 'clients'], true);
        $id = (int)$id;
        if ($id <= 0) {
            show_404();
            return;
        }

        try {
            $result = safe_db_operation(function() use ($id) {
                return $this->clients->get_client($id);
            }, 'Unable to load client details. Please try again.');

            if (!$result['success'] || !$result['data']) {
                if (!$result['data']) {
                    show_404();
                    return;
                }
                $this->session->set_flashdata('error', $result['error']);
                redirect('clients');
                return;
            }

            $c = $result['data'];
            $related = $this->_client_related_work($id);

            $this->load->view('clients/view', array_merge(array(
                'client' => $c,
                'assignable_users' => $this->_load_assignable_users(),
                'can_manage_tasks' => function_exists('has_module_access')
                    && (has_module_access('tasks_add') || has_module_access('tasks_edit') || has_module_access('tasks')),
                'can_manage_requirements' => function_exists('has_module_access')
                    && (has_module_access('requirements_add') || has_module_access('requirements_edit') || has_module_access('requirements')),
                'can_manage_defects' => function_exists('has_module_access')
                    && (has_module_access('defects_add') || has_module_access('defects_edit') || has_module_access('defects')),
                'can_delete_tasks' => function_exists('has_module_access')
                    && (has_module_access('tasks_delete') || has_module_access('tasks')),
                'can_delete_requirements' => function_exists('has_module_access')
                    && (has_module_access('requirements_delete') || has_module_access('requirements')),
                'can_delete_defects' => function_exists('has_module_access')
                    && (has_module_access('defects_delete') || has_module_access('defects')),
            ), $related));

        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client details. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        } catch (Throwable $e) {
            $error_message = handle_database_error($e, 'Unable to load client details. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }

    /**
     * Related work for client detail tabs (projects / requirements / tasks / defects).
     *
     * @param int $client_id
     * @return array
     */
    private function _client_related_work($client_id)
    {
        $client_id = (int) $client_id;
        $projects = array();
        $requirements = array();
        $tasks = array();
        $defects = array();
        $project_ids = array();

        if ($client_id > 0 && $this->db->table_exists('projects')
            && schema_table_has_column($this->db, 'projects', 'client_id')
        ) {
            $this->db->from('projects');
            $this->db->where('client_id', $client_id);
            $this->db->order_by('name', 'ASC');
            $projects = $this->db->get()->result();
            foreach ($projects as $p) {
                $project_ids[] = (int) $p->id;
            }
        }

        if ($client_id > 0 && $this->db->table_exists('requirements')
            && schema_table_has_column($this->db, 'requirements', 'client_id')
        ) {
            $sel = array('r.id', 'r.title', 'r.status', 'r.priority', 'r.project_id', 'r.created_at');
            if (schema_table_has_column($this->db, 'requirements', 'req_number')) {
                $sel[] = 'r.req_number';
            }
            if (schema_table_has_column($this->db, 'requirements', 'assigned_to')) {
                $sel[] = 'r.assigned_to';
            }
            $this->db->select(implode(',', $sel));
            $this->db->from('requirements r');
            $this->db->where('r.client_id', $client_id);
            if ($this->db->table_exists('projects')) {
                $this->db->select('p.name AS project_name', false);
                $this->db->join('projects p', 'p.id = r.project_id', 'left');
            }
            if (schema_table_has_column($this->db, 'requirements', 'assigned_to')
                && $this->db->table_exists('users')
            ) {
                $this->db->select('u.name AS assignee_name, u.email AS assignee_email', false);
                $this->db->join('users u', 'u.id = r.assigned_to', 'left');
            }
            $this->db->order_by('r.id', 'DESC');
            $this->db->limit(200);
            $requirements = $this->db->get()->result();
        }

        // Include projects referenced by this client's requirements (legacy rows may lack client_id).
        if ($this->db->table_exists('projects')) {
            $extra_project_ids = array();
            foreach ($requirements as $req_row) {
                $pid = !empty($req_row->project_id) ? (int) $req_row->project_id : 0;
                if ($pid > 0 && !in_array($pid, $project_ids, true)) {
                    $extra_project_ids[] = $pid;
                }
            }
            if (!empty($extra_project_ids)) {
                $extra_rows = $this->db->from('projects')
                    ->where_in('id', $extra_project_ids)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result();
                foreach ($extra_rows as $ep) {
                    $projects[] = $ep;
                    $project_ids[] = (int) $ep->id;
                }
                usort($projects, function ($a, $b) {
                    return strcasecmp((string) $a->name, (string) $b->name);
                });
            }
        }

        $requirement_ids = array();
        foreach ($requirements as $req_row) {
            $rid = (int) $req_row->id;
            if ($rid > 0) {
                $requirement_ids[] = $rid;
            }
        }

        $has_task_project_filter = !empty($project_ids);
        $has_task_req_filter = !empty($requirement_ids)
            && schema_table_has_column($this->db, 'tasks', 'requirement_id');

        if ($this->db->table_exists('tasks')
            && ($has_task_project_filter || $has_task_req_filter)
        ) {
            $sel = array('t.id', 't.title', 't.status', 't.project_id');
            if (schema_table_has_column($this->db, 'tasks', 'priority')) {
                $sel[] = 't.priority';
            }
            if (schema_table_has_column($this->db, 'tasks', 'due_date')) {
                $sel[] = 't.due_date';
            }
            if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                $sel[] = 't.assigned_to';
            }
            $this->db->select(implode(',', $sel));
            $this->db->from('tasks t');
            $this->db->group_start();
            if ($has_task_project_filter) {
                $this->db->where_in('t.project_id', $project_ids);
            }
            if ($has_task_req_filter) {
                if ($has_task_project_filter) {
                    $this->db->or_where_in('t.requirement_id', $requirement_ids);
                } else {
                    $this->db->where_in('t.requirement_id', $requirement_ids);
                }
            }
            $this->db->group_end();
            if ($this->db->table_exists('projects')) {
                $this->db->select('p.name AS project_name', false);
                $this->db->join('projects p', 'p.id = t.project_id', 'left');
            }
            if (schema_table_has_column($this->db, 'tasks', 'assigned_to')
                && $this->db->table_exists('users')
            ) {
                $this->db->select('u.name AS assignee_name, u.email AS assignee_email', false);
                $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            }
            $this->db->order_by('t.id', 'DESC');
            $this->db->limit(300);
            $tasks = $this->db->get()->result();
        }

        if (!empty($project_ids) && $this->db->table_exists('project_defects')) {
            $sel = array('d.id', 'd.title', 'd.status', 'd.project_id');
            if (schema_table_has_column($this->db, 'project_defects', 'defect_number')) {
                $sel[] = 'd.defect_number';
            }
            if (schema_table_has_column($this->db, 'project_defects', 'priority')) {
                $sel[] = 'd.priority';
            }
            if (schema_table_has_column($this->db, 'project_defects', 'assigned_to')) {
                $sel[] = 'd.assigned_to';
            }
            $this->db->select(implode(',', $sel));
            $this->db->from('project_defects d');
            $this->db->where_in('d.project_id', $project_ids);
            if (schema_table_has_column($this->db, 'project_defects', 'is_deleted')) {
                $this->db->group_start();
                $this->db->where('d.is_deleted', 0);
                $this->db->or_where('d.is_deleted IS NULL', null, false);
                $this->db->group_end();
            }
            if ($this->db->table_exists('projects')) {
                $this->db->select('p.name AS project_name', false);
                $this->db->join('projects p', 'p.id = d.project_id', 'left');
            }
            if (schema_table_has_column($this->db, 'project_defects', 'assigned_to')
                && $this->db->table_exists('users')
            ) {
                $this->db->select('u.name AS assignee_name, u.email AS assignee_email', false);
                $this->db->join('users u', 'u.id = d.assigned_to', 'left');
            }
            $this->db->order_by('d.id', 'DESC');
            $this->db->limit(300);
            $defects = $this->db->get()->result();
        }

        return array(
            'projects' => $projects,
            'requirements' => $requirements,
            'tasks' => $tasks,
            'defects' => $defects,
        );
    }

    public function edit($id){
        require_module_access(['clients_edit', 'clients'], true);
        $id = (int)$id;
        if ($id <= 0) {
            show_404();
            return;
        }
        
        try {
            $c = $this->clients->get_client($id);
            if (!$c) { 
                show_404();
                return;
            }
            
            if ($this->input->method() === 'post'){
                try {
                    // Validation - Mandatory fields only
                    $this->load->library('form_validation');
                    
                    // Mandatory fields
                    $this->form_validation->set_rules('company_name', 'Company Name', 'required|trim|min_length[2]|max_length[255]');
                    $this->form_validation->set_rules('contact_person', 'Contact Person', 'required|trim|min_length[2]|max_length[200]');
                    $this->form_validation->set_rules('phone', 'Phone', 'required|trim|min_length[10]|max_length[20]|regex_match[/^[0-9+\s\-\(\)]+$/]');
                    
                    if ($this->form_validation->run() == FALSE) {
                        $errors = validation_errors();
                        $this->session->set_flashdata('error', handle_validation_error($errors));
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    // Extract and validate mandatory fields
                    $company_name = trim($this->input->post('company_name'));
                    $contact_person = trim($this->input->post('contact_person'));
                    $phone = trim($this->input->post('phone'));
                    $email = trim($this->input->post('email'));
                    
                    // Double-check mandatory fields (server-side validation)
                    if (empty($company_name)) {
                        $this->session->set_flashdata('error', 'Company Name is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    if (empty($contact_person)) {
                        $this->session->set_flashdata('error', 'Contact Person is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    if (empty($phone)) {
                        $this->session->set_flashdata('error', 'Phone is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    // Check email uniqueness (excluding current client)
                    if (!empty($email) && method_exists($this->clients, 'email_exists')) {
                        if ($this->clients->email_exists($email, $id)) {
                            $this->session->set_flashdata('error', 'This email address is already registered to another client.');
                            redirect('clients/edit/'.$id);
                            return;
                        }
                    }
                    
                    // Check phone uniqueness (excluding current client)
                    if (!empty($phone) && method_exists($this->clients, 'phone_exists')) {
                        if ($this->clients->phone_exists($phone, $id)) {
                            $this->session->set_flashdata('error', 'This phone number is already registered to another client.');
                            redirect('clients/edit/'.$id);
                            return;
                        }
                    }
                    
                    $logo_path = $this->upload_logo(isset($c->logo) ? $c->logo : null);
                    $website = $this->_normalize_client_url('website', 'URL', 'clients/edit/'.$id);
                    $demo_url = $this->_normalize_client_url('demo_url', 'Demo URL', 'clients/edit/'.$id);
                    $pos_url = $this->_normalize_client_url('pos_url', 'POS URL', 'clients/edit/'.$id);
                    $data = [
                        'company_name' => $company_name,
                        'contact_person' => $contact_person,
                        'email' => $email,
                        'phone' => $phone,
                        'alternate_phone' => trim($this->input->post('alternate_phone')),
                        'website' => $website,
                        'demo_url' => $demo_url,
                        'pos_url' => $pos_url,
                        'address' => trim($this->input->post('address')),
                        'city' => trim($this->input->post('city')),
                        'state' => trim($this->input->post('state')),
                        'country' => trim($this->input->post('country')) ?: 'India',
                        'zip_code' => trim($this->input->post('zip_code')),
                        'gstin' => trim($this->input->post('gstin')),
                        'pan_number' => trim($this->input->post('pan_number')),
                        'industry' => trim($this->input->post('industry')),
                        'onboarding_date' => $this->input->post('onboarding_date') ?: null,
                        'client_type' => $this->_resolve_client_type($this->input->post('client_type')),
                        'account_manager_id' => $this->input->post('account_manager_id') !== '' ? (int)$this->input->post('account_manager_id') : null,
                        'notes' => trim($this->input->post('notes')),
                        'db_name' => trim($this->input->post('db_name')),
                        'db_username' => trim($this->input->post('db_username')),
                        'db_password' => trim($this->input->post('db_password')),
                        'db_host' => trim($this->input->post('db_host')),
                        'db_port' => trim($this->input->post('db_port')),
                        'logo' => $logo_path,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $status = $this->input->post('status');
                    if ($status !== null && $status !== ''){
                        $this->load->helper('module_status');
                        $status = module_status_sanitize($status, 'clients', isset($c->status) ? (string) $c->status : 'active');
                        if ($status === false) {
                            $this->session->set_flashdata('error', 'Invalid client status selected.');
                            redirect('clients/edit/'.$id);
                            return;
                        }
                        $data['status'] = $status;
                    }
                    
                    // Update with error handling
                    $result = safe_db_operation(function() use ($id, $data) {
                        return $this->clients->update_client($id, $data);
                    }, 'Failed to update client. Please try again.');
                    
                    if (!$result['success']) {
                        $this->session->set_flashdata('error', $result['error']);
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    $success_msg = get_notification_message('clients', 'update', 'success');
                    $this->session->set_flashdata('success', $success_msg);
                    redirect('clients/view/'.$id);
                    return;
                    
                } catch (Exception $e) {
                    $error_message = handle_database_error($e, 'An error occurred while updating the client. Please try again.');
                    $this->session->set_flashdata('error', $error_message);
                    redirect('clients/edit/'.$id);
                    return;
                } catch (Throwable $e) {
                    $error_message = handle_database_error($e, 'An error occurred while updating the client. Please try again.');
                    $this->session->set_flashdata('error', $error_message);
                    redirect('clients/edit/'.$id);
                    return;
                }
            }
            
            $managers = $this->clients->get_account_managers();
            $this->load->view('clients/edit', [
                'client'=>$c,
                'managers'=>$managers,
                'client_types'=>$this->_client_type_options(),
            ]);
            
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }
    
    // GET /clients/export
    public function export(){
        require_module_access(['clients_export', 'clients'], true);
        $filters = [
            'status' => $this->input->get('status'),
            'client_type' => $this->input->get('client_type'),
            'search' => $this->input->get('q')
        ];
        $ids_raw = $this->input->get('ids');
        if ($ids_raw !== null && $ids_raw !== '') {
            if (is_array($ids_raw)) {
                $filters['ids'] = $ids_raw;
            } else {
                $filters['ids'] = preg_split('/\s*,\s*/', (string) $ids_raw);
            }
        }

        $clients = $this->clients->get_clients($filters, null, 0);
        $columns = $this->_client_csv_columns();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="clients_export_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, array_column($columns, 'header'));

        foreach ($clients as $client) {
            $row = array();
            foreach ($columns as $col) {
                $field = $col['field'];
                $row[] = isset($client->$field) ? $client->$field : '';
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    // GET/POST /clients/import
    public function import()
    {
        require_module_access(['clients_import', 'clients'], true);

        if ($this->input->method() === 'post') {
            $this->load->helper(['csv_import', 'module_status', 'validation', 'types']);
            $opened = csv_import_open('file');
            if (!$opened['ok']) {
                $this->session->set_flashdata('error', $opened['error']);
                redirect('clients');
                return;
            }
            $columns = csv_import_require_columns($opened['map'], array('phone'), array(
                array('company_name', 'company name'),
                array('contact_person', 'contact person'),
            ));
            if (!$columns['ok']) {
                fclose($opened['handle']);
                $this->session->set_flashdata('error', $columns['error']);
                redirect('clients');
                return;
            }

            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            $row_errors = array();
            $line = 1;
            $max_rows = 1000;
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            $user_id = (int) $this->session->userdata('user_id');
            $now = date('Y-m-d H:i:s');

            while (($row = fgetcsv($opened['handle'])) !== false) {
                $line++;
                if (($line - 1) > $max_rows) {
                    csv_import_add_row_error($row_errors, $line, 'Import limit is ' . $max_rows . ' rows per file.');
                    break;
                }

                $company_name = $this->_csv_client_get($opened['map'], $row, 'company_name');
                $contact_person = $this->_csv_client_get($opened['map'], $row, 'contact_person');
                $phone = $this->_csv_client_get($opened['map'], $row, 'phone');

                if ($company_name === '' || $contact_person === '' || $phone === '') {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'company_name, contact_person, and phone are required.');
                    continue;
                }
                if (strlen(preg_replace('/\D+/', '', $phone)) < 10) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'phone must be at least 10 digits.');
                    continue;
                }

                $client_code = $this->_csv_client_get($opened['map'], $row, 'client_code');
                $email = $this->_csv_client_get($opened['map'], $row, 'email');
                $existing = null;
                $existing_id = 0;
                if ($client_code !== '') {
                    $existing = $this->clients->get_client_by_code($client_code);
                    if ($existing) {
                        $existing_id = (int) $existing->id;
                    }
                }

                if ($email !== '' && $this->clients->email_exists($email, $existing_id > 0 ? $existing_id : null)) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Email already registered to another client.');
                    continue;
                }
                if ($this->clients->phone_exists($phone, $existing_id > 0 ? $existing_id : null)) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Phone already registered to another client.');
                    continue;
                }

                $client_type_raw = $this->_csv_client_get($opened['map'], $row, 'client_type', 'company');
                $client_type = module_type_validate_code($client_type_raw, 'clients', false, 'company');
                if ($client_type === false) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Invalid client_type "' . $client_type_raw . '".');
                    continue;
                }

                $status_raw = $this->_csv_client_get($opened['map'], $row, 'status', 'active');
                $status = module_status_sanitize($status_raw, 'clients', 'active');
                if ($status === false) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Invalid status "' . $status_raw . '".');
                    continue;
                }

                $website = $this->_csv_client_normalize_url($this->_csv_client_get($opened['map'], $row, 'website'), 'URL', $line, $row_errors);
                if ($website === false) {
                    $skipped++;
                    continue;
                }
                $demo_url = $this->_csv_client_normalize_url($this->_csv_client_get($opened['map'], $row, 'demo_url'), 'Demo URL', $line, $row_errors);
                if ($demo_url === false) {
                    $skipped++;
                    continue;
                }
                $pos_url = $this->_csv_client_normalize_url($this->_csv_client_get($opened['map'], $row, 'pos_url'), 'POS URL', $line, $row_errors);
                if ($pos_url === false) {
                    $skipped++;
                    continue;
                }

                $data = array(
                    'company_name' => $company_name,
                    'contact_person' => $contact_person,
                    'email' => $email,
                    'phone' => $phone,
                    'alternate_phone' => $this->_csv_client_get($opened['map'], $row, 'alternate_phone'),
                    'website' => $website,
                    'demo_url' => $demo_url,
                    'pos_url' => $pos_url,
                    'address' => $this->_csv_client_get($opened['map'], $row, 'address'),
                    'city' => $this->_csv_client_get($opened['map'], $row, 'city'),
                    'state' => $this->_csv_client_get($opened['map'], $row, 'state'),
                    'country' => $this->_csv_client_get($opened['map'], $row, 'country', 'India') ?: 'India',
                    'zip_code' => $this->_csv_client_get($opened['map'], $row, 'zip_code'),
                    'gstin' => $this->_csv_client_get($opened['map'], $row, 'gstin'),
                    'pan_number' => $this->_csv_client_get($opened['map'], $row, 'pan_number'),
                    'industry' => $this->_csv_client_get($opened['map'], $row, 'industry'),
                    'onboarding_date' => $this->_csv_client_get($opened['map'], $row, 'onboarding_date') ?: null,
                    'client_type' => $client_type,
                    'status' => $status,
                    'notes' => $this->_csv_client_get($opened['map'], $row, 'notes'),
                    'updated_at' => $now,
                );

                if ($existing_id > 0) {
                    $ok = $this->clients->update_client($existing_id, $data);
                    if ($ok) {
                        $updated++;
                    } else {
                        $skipped++;
                        $db_error = $this->db->error();
                        $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database update failed.';
                        csv_import_add_row_error($row_errors, $line, $reason);
                        log_message('error', 'Client import update error: ' . $reason);
                    }
                    continue;
                }

                if ($client_code === '') {
                    $client_code = $this->clients->generate_client_code();
                    if ($client_code === '') {
                        $skipped++;
                        csv_import_add_row_error($row_errors, $line, 'Unable to generate client code.');
                        continue;
                    }
                } else {
                    if ($this->clients->get_client_by_code($client_code)) {
                        $skipped++;
                        csv_import_add_row_error($row_errors, $line, 'client_code already exists: ' . $client_code . '.');
                        continue;
                    }
                }

                $data['client_code'] = $client_code;
                $data['created_by'] = $user_id > 0 ? $user_id : null;
                $data['created_at'] = $now;
                $data['status'] = $status;

                $new_id = $this->clients->create_client($data);
                if ($new_id) {
                    $inserted++;
                } else {
                    $skipped++;
                    $db_error = $this->db->error();
                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';
                    csv_import_add_row_error($row_errors, $line, $reason);
                    log_message('error', 'Client import insert error: ' . $reason);
                }
            }

            $this->db->db_debug = $prev_debug;
            fclose($opened['handle']);

            $total_saved = $inserted + $updated;
            if ($total_saved === 0) {
                csv_import_finish(0, $skipped, $row_errors, 'clients', 'clients', 'clients');
                return;
            }

            $msg = array();
            if ($inserted > 0) {
                $msg[] = 'Imported ' . $inserted . ' client(s)';
            }
            if ($updated > 0) {
                $msg[] = 'updated ' . $updated . ' client(s)';
            }
            $flash = implode('; ', $msg) . '.';
            if ($skipped > 0) {
                $flash .= ' ' . (int) $skipped . ' row(s) skipped.';
            }
            $this->session->set_flashdata('success', $flash);
            if (!empty($row_errors)) {
                $this->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
            }
            redirect('clients');
            return;
        }

        redirect('clients');
    }

    /**
     * CSV column map shared by export and import docs.
     *
     * @return array<int, array{header:string,field:string}>
     */
    private function _client_csv_columns()
    {
        return array(
            array('header' => 'client_code', 'field' => 'client_code'),
            array('header' => 'company_name', 'field' => 'company_name'),
            array('header' => 'contact_person', 'field' => 'contact_person'),
            array('header' => 'email', 'field' => 'email'),
            array('header' => 'phone', 'field' => 'phone'),
            array('header' => 'alternate_phone', 'field' => 'alternate_phone'),
            array('header' => 'website', 'field' => 'website'),
            array('header' => 'demo_url', 'field' => 'demo_url'),
            array('header' => 'pos_url', 'field' => 'pos_url'),
            array('header' => 'address', 'field' => 'address'),
            array('header' => 'city', 'field' => 'city'),
            array('header' => 'state', 'field' => 'state'),
            array('header' => 'country', 'field' => 'country'),
            array('header' => 'zip_code', 'field' => 'zip_code'),
            array('header' => 'gstin', 'field' => 'gstin'),
            array('header' => 'pan_number', 'field' => 'pan_number'),
            array('header' => 'industry', 'field' => 'industry'),
            array('header' => 'onboarding_date', 'field' => 'onboarding_date'),
            array('header' => 'client_type', 'field' => 'client_type'),
            array('header' => 'status', 'field' => 'status'),
            array('header' => 'notes', 'field' => 'notes'),
        );
    }

    /**
     * @param array $map
     * @param array $row
     * @param string $key
     * @param string $default
     * @return string
     */
    private function _csv_client_get($map, $row, $key, $default = '')
    {
        $aliases = array(
            'client_code' => array('client_code', 'client code'),
            'company_name' => array('company_name', 'company name'),
            'contact_person' => array('contact_person', 'contact person'),
            'email' => array('email'),
            'phone' => array('phone'),
            'alternate_phone' => array('alternate_phone', 'alternate phone'),
            'website' => array('website', 'url'),
            'demo_url' => array('demo_url', 'demo url'),
            'pos_url' => array('pos_url', 'pos url'),
            'address' => array('address'),
            'city' => array('city'),
            'state' => array('state'),
            'country' => array('country'),
            'zip_code' => array('zip_code', 'zip code', 'zip'),
            'gstin' => array('gstin'),
            'pan_number' => array('pan_number', 'pan number'),
            'industry' => array('industry'),
            'onboarding_date' => array('onboarding_date', 'onboarding date'),
            'client_type' => array('client_type', 'client type'),
            'status' => array('status'),
            'notes' => array('notes'),
        );
        $cols = isset($aliases[$key]) ? $aliases[$key] : array($key);
        return csv_import_get_any($map, $row, $cols, $default);
    }

    /**
     * @param string $value
     * @param string $label
     * @param int $line
     * @param array $row_errors
     * @return string|false|null
     */
    private function _csv_client_normalize_url($value, $label, $line, &$row_errors)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $normalized = normalize_optional_url($value);
        if ($normalized === false) {
            csv_import_add_row_error($row_errors, $line, 'Invalid ' . $label . ' "' . $value . '".');
            return false;
        }
        return $normalized;
    }

    /**
     * POST /clients/delete/{id}
     */
    public function delete($id = null){
        $id = (int)$id;
        if (!$id) { show_404(); }
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }

        require_module_access(['clients_delete', 'clients'], true);

        $client = $this->clients->get_client($id);
        if (!$client) { show_404(); }

        $this->clients->delete_client($id);
        $this->session->set_flashdata('success', 'Client "' . esc_view($client->company_name) . '" deleted successfully.');
        redirect('clients');
    }

    /**
     * POST /clients/bulk-delete
     */
    public function bulk_delete()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
            return;
        }
        require_module_access(['clients_delete', 'clients'], true);
        $ids = $this->input->post('ids');
        if (!is_array($ids)) {
            $ids = array();
        }
        $deleted = $this->clients->delete_clients_by_ids($ids);
        if ($deleted > 0) {
            $this->session->set_flashdata('success', $deleted . ' client(s) deleted successfully.');
        } else {
            $this->session->set_flashdata('error', 'No clients selected for delete.');
        }
        redirect('clients');
    }

    /**
     * GET /clients/{id}/contacts
     * View contacts for a specific client (JSON or view)
     */
    public function contacts($id = null){
        $id = (int)$id;
        if (!$id) { show_404(); }

        $client = $this->clients->get_client($id);
        if (!$client) { show_404(); }

        // Return JSON if AJAX, otherwise redirect to client view
        if ($this->input->is_ajax_request()) {
            $contacts = array();
            if ($this->db->table_exists('client_contacts')) {
                $contacts = $this->db->where('client_id', $id)->order_by('id', 'ASC')->get('client_contacts')->result();
            }
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'contacts' => $contacts]);
            exit;
        }

        redirect('clients/view/' . $id);
    }

    // POST /clients/{id}/inline-save — quick add/update from client detail tabs
    public function inline_save($client_id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        require_module_access(array('clients_view', 'clients'), true);

        $client_id = (int) $client_id;
        $client = $this->clients->get_client($client_id);
        if (!$client) {
            return $this->_inline_json(false, array(), 'Client not found', 404);
        }

        $type = trim((string) $this->input->post('type'));
        $item_id = (int) $this->input->post('id');
        $title = trim((string) $this->input->post('title'));
        $status = trim((string) $this->input->post('status'));
        $priority = trim((string) $this->input->post('priority'));
        $assigned_raw = $this->input->post('assigned_to');
        $assigned_to = ($assigned_raw !== '' && $assigned_raw !== null) ? (int) $assigned_raw : null;
        $project_raw = $this->input->post('project_id');
        $project_id = ($project_raw !== '' && $project_raw !== null) ? (int) $project_raw : 0;

        if ($type === 'task') {
            return $this->_inline_save_task($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id);
        }
        if ($type === 'requirement') {
            return $this->_inline_save_requirement($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id);
        }
        if ($type === 'defect') {
            return $this->_inline_save_defect($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id);
        }

        return $this->_inline_json(false, array(), 'Invalid type', 400);
    }

    // POST /clients/{id}/inline-delete — delete row from client detail tabs
    public function inline_delete($client_id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        require_module_access(array('clients_view', 'clients'), true);

        $client_id = (int) $client_id;
        $client = $this->clients->get_client($client_id);
        if (!$client) {
            return $this->_inline_json(false, array(), 'Client not found', 404);
        }

        $type = trim((string) $this->input->post('type'));
        $item_id = (int) $this->input->post('id');
        if ($item_id < 1) {
            return $this->_inline_json(false, array(), 'Invalid id', 400);
        }

        if ($type === 'task') {
            return $this->_inline_delete_task($client_id, $item_id);
        }
        if ($type === 'requirement') {
            return $this->_inline_delete_requirement($client_id, $item_id);
        }
        if ($type === 'defect') {
            return $this->_inline_delete_defect($client_id, $item_id);
        }

        return $this->_inline_json(false, array(), 'Invalid type', 400);
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

    private function _project_belongs_to_client($project_id, $client_id)
    {
        $project_id = (int) $project_id;
        $client_id = (int) $client_id;
        if ($project_id < 1 || $client_id < 1 || !$this->db->table_exists('projects')) {
            return false;
        }
        if (schema_table_has_column($this->db, 'projects', 'client_id')) {
            $row = $this->db->select('id')
                ->where('id', $project_id)
                ->where('client_id', $client_id)
                ->get('projects')
                ->row();
            if ($row) {
                return true;
            }
        }
        // Allow projects linked via this client's requirements (legacy / missing client_id).
        if ($this->db->table_exists('requirements')
            && schema_table_has_column($this->db, 'requirements', 'client_id')
            && schema_table_has_column($this->db, 'requirements', 'project_id')
        ) {
            $linked = $this->db->select('id')
                ->where('client_id', $client_id)
                ->where('project_id', $project_id)
                ->limit(1)
                ->get('requirements')
                ->row();
            if ($linked) {
                return true;
            }
        }
        return false;
    }

    private function _task_belongs_to_client($task, $client_id)
    {
        if (!$task) {
            return false;
        }
        $client_id = (int) $client_id;
        $project_id = !empty($task->project_id) ? (int) $task->project_id : 0;
        if ($project_id > 0 && $this->_project_belongs_to_client($project_id, $client_id)) {
            return true;
        }
        if (!empty($task->requirement_id)
            && $this->db->table_exists('requirements')
            && schema_table_has_column($this->db, 'requirements', 'client_id')
        ) {
            $req = $this->db->select('id')
                ->where('id', (int) $task->requirement_id)
                ->where('client_id', $client_id)
                ->get('requirements')
                ->row();
            if ($req) {
                return true;
            }
        }
        return false;
    }

    private function _inline_delete_task($client_id, $item_id)
    {
        if (!has_module_access('tasks_delete') && !has_module_access('tasks')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }

        $task = $this->db->where('id', $item_id)->get('tasks')->row();
        if (!$task || !$this->_task_belongs_to_client($task, $client_id)) {
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

    private function _inline_delete_requirement($client_id, $item_id)
    {
        if (!$this->db->table_exists('requirements')) {
            return $this->_inline_json(false, array(), 'Requirements module unavailable', 400);
        }
        if (!has_module_access('requirements_delete') && !has_module_access('requirements')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }

        $req = $this->db->where('id', $item_id)->where('client_id', (int) $client_id)->get('requirements')->row();
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

    private function _inline_delete_defect($client_id, $item_id)
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
        if (!$defect || !$this->_project_belongs_to_client((int) $defect->project_id, $client_id)) {
            return $this->_inline_json(false, array(), 'Defect not found', 404);
        }

        auto_log_delete('defects', 'project_defects', $item_id, (array) $defect, 'Defect deleted: ' . $defect->defect_number);
        $this->project_defects->delete_defect($item_id);
        return $this->_inline_json(true, array('id' => $item_id));
    }

    private function _inline_save_task($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id)
    {
        $allowed_status = array('pending', 'in_progress', 'completed', 'blocked');
        $allowed_priority = array('low', 'medium', 'high', 'urgent');
        if ($status === '' || !in_array($status, $allowed_status, true)) {
            $status = 'pending';
        }
        if ($priority === '' || !in_array($priority, $allowed_priority, true)) {
            $priority = 'medium';
        }

        if ($item_id > 0) {
            if (!has_module_access('tasks_edit') && !has_module_access('tasks')) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
            $task = $this->db->where('id', $item_id)->get('tasks')->row();
            if (!$task || !$this->_task_belongs_to_client($task, $client_id)) {
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
            if ($project_id > 0) {
                if (!$this->_project_belongs_to_client($project_id, $client_id)) {
                    return $this->_inline_json(false, array(), 'Invalid project', 400);
                }
                $update['project_id'] = $project_id;
            }
            $this->db->where('id', $item_id)->update('tasks', $update);
            return $this->_inline_json(true, array('id' => $item_id, 'project_id' => $project_id > 0 ? $project_id : (int) $task->project_id));
        }

        if (!has_module_access('tasks_add') && !has_module_access('tasks')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($project_id < 1 || !$this->_project_belongs_to_client($project_id, $client_id)) {
            return $this->_inline_json(false, array(), 'Select a project for this client', 400);
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
        $this->db->insert('tasks', $insert);
        $new_id = (int) $this->db->insert_id();
        return $this->_inline_json(true, array('id' => $new_id, 'project_id' => $project_id));
    }

    private function _inline_save_requirement($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id)
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

        $resolved_project_id = null;
        if ($project_id > 0) {
            if (!$this->_project_belongs_to_client($project_id, $client_id)) {
                return $this->_inline_json(false, array(), 'Invalid project', 400);
            }
            $resolved_project_id = $project_id;
        }

        if ($item_id > 0) {
            if (!has_module_access('requirements_edit') && !has_module_access('requirements')) {
                return $this->_inline_json(false, array(), 'Access denied', 403);
            }
            $req = $this->db->where('id', $item_id)->where('client_id', (int) $client_id)->get('requirements')->row();
            if (!$req) {
                return $this->_inline_json(false, array(), 'Requirement not found', 404);
            }
            if ($title === '') {
                return $this->_inline_json(false, array(), 'Title is required', 400);
            }
            $update = array(
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assigned_to,
                'updated_at' => date('Y-m-d H:i:s'),
            );
            if (schema_table_has_column($this->db, 'requirements', 'project_id')) {
                $update['project_id'] = $resolved_project_id;
            }
            $this->db->where('id', $item_id)->update('requirements', $update);
            return $this->_inline_json(true, array(
                'id' => $item_id,
                'req_number' => $req->req_number,
                'project_id' => $resolved_project_id,
            ));
        }

        if (!has_module_access('requirements_add') && !has_module_access('requirements')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($title === '') {
            $title = 'New requirement';
        }

        $user_id = (int) $this->session->userdata('user_id');
        $req_number = $this->_inline_generate_req_number();
        $now = date('Y-m-d H:i:s');
        $insert = array(
            'req_number' => $req_number,
            'client_id' => (int) $client_id,
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
        if (schema_table_has_column($this->db, 'requirements', 'project_id')) {
            $insert['project_id'] = $resolved_project_id;
        }
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

        return $this->_inline_json(true, array(
            'id' => $new_id,
            'req_number' => $req_number,
            'project_id' => $resolved_project_id,
        ));
    }

    private function _inline_save_defect($client_id, $item_id, $title, $status, $priority, $assigned_to, $project_id)
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
            if (!$defect || !$this->_project_belongs_to_client((int) $defect->project_id, $client_id)) {
                return $this->_inline_json(false, array(), 'Defect not found', 404);
            }
            if ($title === '') {
                return $this->_inline_json(false, array(), 'Title is required', 400);
            }
            $uid = (int) $this->session->userdata('user_id');
            $old_assignee = (int) $defect->assigned_to;
            $save = array(
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assigned_to,
            );
            if ($project_id > 0) {
                if (!$this->_project_belongs_to_client($project_id, $client_id)) {
                    return $this->_inline_json(false, array(), 'Invalid project', 400);
                }
                $save['project_id'] = $project_id;
            }
            $this->project_defects->save_defect($save, $item_id);
            if ($assigned_to && (int) $assigned_to !== $old_assignee) {
                defect_notify_assignee($item_id, (int) $assigned_to, $title, $uid);
            }
            return $this->_inline_json(true, array(
                'id' => $item_id,
                'defect_number' => $defect->defect_number,
                'project_id' => $project_id > 0 ? $project_id : (int) $defect->project_id,
            ));
        }

        if (!has_module_access('defects_add') && !has_module_access('defects')) {
            return $this->_inline_json(false, array(), 'Access denied', 403);
        }
        if ($project_id < 1 || !$this->_project_belongs_to_client($project_id, $client_id)) {
            return $this->_inline_json(false, array(), 'Select a project for this client', 400);
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
        $this->project_defects->log_activity($new_id, $uid, 'created', 'Created from client tab');
        if ($assigned_to) {
            defect_notify_assignee($new_id, (int) $assigned_to, $title, $uid);
        }

        return $this->_inline_json(true, array(
            'id' => $new_id,
            'defect_number' => $defect_number,
            'project_id' => $project_id,
        ));
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
