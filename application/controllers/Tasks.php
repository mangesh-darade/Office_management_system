<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','group_filter','hierarchy_filter','email_settings','schema_columns','validation']);
        $this->load->library(['session']);
        $this->load->model('Task_model');
        
        // RBAC Audit: Centralized module access check
        // Allow users with either 'tasks' or 'tasks_list' access
        require_module_access(['tasks', 'tasks_list'], true);
    }

    public function index() {
        require_module_access(['tasks_list', 'tasks'], true);
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('tasks_manage');
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);

        // Filters from GET
        $project_filter = trim((string)$this->input->get('project_id'));
        $assignee_filter = trim((string)$this->input->get('assigned_to'));
        $status_filter = trim((string)$this->input->get('status'));
        $priority_filter = trim((string)$this->input->get('priority'));

        $this->db->from('tasks t');
        $select = ['t.*'];
        // Join projects for name if available
        if ($this->db->table_exists('projects')) {
            if (schema_table_has_column($this->db, 'projects', 'name')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'u.full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            if (schema_table_has_column($this->db, 'employees', 'name')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        $this->db->select(implode(',', $select));
        
        // Admin sees all tasks; others see tasks assigned to them only
        $can_view_all = (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data())
            || has_module_access('tasks_view_all');
        if (!$can_view_all && $user_id) {
            apply_role_hierarchy_filter($this->db, 't.assigned_to', $user_id, $role_id);
        }
        
        // Apply filters
        if ($project_filter !== '') { $this->db->where('t.project_id', (int)$project_filter); }
        if ($is_admin && $assignee_filter !== '') { $this->db->where('t.assigned_to', (int)$assignee_filter); }
        if ($status_filter !== '') { $this->db->where('t.status', $status_filter); }
        if ($priority_filter !== '' && schema_table_has_column($this->db, 'tasks', 'priority')) { $this->db->where('t.priority', $priority_filter); }
        if (schema_table_has_column($this->db, 'tasks', 'priority')) {
            $priority_order = "CASE t.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5 END";
            $this->db->order_by($priority_order, 'ASC', false);
        }
        $this->db->order_by('t.id', 'DESC');
        $tasks = $this->db->get()->result();

        // Dropdown data
        $projects = [];
        if ($this->db->table_exists('projects')) {
            $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        }
        $assignees = [];
        if ($is_admin) {
            if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                $sel = ['users.id','users.email'];
                $hasEmpName3 = schema_table_has_column($this->db, 'employees', 'name');
                if ($hasEmpName3) { $sel[] = 'employees.name AS emp_name'; }
                if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'users.full_name'; }
                if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'users.name'; }
                $this->db->select(implode(',', $sel))
                         ->from('users')
                         ->join('employees','employees.user_id = users.id','left');
                if ($hasEmpName3) {
                    $this->db->order_by('employees.name IS NULL ASC', '', false)
                             ->order_by('employees.name','ASC');
                }
                $this->db->order_by('users.email','ASC');
                $assignees = $this->db->get()->result();
            } else if ($this->db->table_exists('users')) {
                $sel = ['id','email'];
                if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'full_name'; }
                if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'name'; }
                $assignees = $this->db->select(implode(',', $sel))->from('users')->order_by('email','ASC')->get()->result();
            }
        }

        $this->load->view('tasks/list', [
            'tasks' => $tasks,
            'is_admin' => $is_admin,
            'projects' => $projects,
            'assignees' => $assignees,
            'filter_project_id' => $project_filter,
            'filter_assigned_to' => $assignee_filter,
            'filter_status' => $status_filter,
            'filter_priority' => $priority_filter,
        ]);
    }

    // GET /tasks/create, POST /tasks/create
    public function create()
    {
        // Check create permission specifically
        require_module_access(['tasks_add', 'tasks'], true);

        $this->_ensure_reference_url_column();
        
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            $requirement_id = $this->input->post('requirement_id') !== '' ? (int)$this->input->post('requirement_id') : null;
            // Handle multi-select projects
            $project_ids = $this->input->post('project_ids');
            $project_id = 0;
            $project_ids_json = null;
            if (is_array($project_ids) && !empty($project_ids)) {
                $project_ids = array_map('intval', array_filter($project_ids));
                if (!empty($project_ids)) {
                    $project_id = $project_ids[0]; // First project for backward compatibility
                    $project_ids_json = json_encode($project_ids);
                }
            } else {
                // Fallback to single project_id if project_ids not provided
                $project_id = (int)($this->input->post('project_id') ?: 0);
                if ($project_id > 0) {
                    $project_ids_json = json_encode([$project_id]);
                }
            }
            
            // Cache field list once — avoids multiple DB roundtrips and safely handles un-migrated schemas
            $task_fields = $this->db->list_fields('tasks');

            $data = [
                'project_id'  => $project_id,
                'title'       => trim($this->input->post('title')),
                'description' => $this->input->post('description', TRUE),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status'      => $this->input->post('status') ?: 'pending',
                'created_by'  => $user_id,
            ];
            // Guard all migration-added columns
            if (in_array('requirement_id', $task_fields, true)) {
                $data['requirement_id'] = $requirement_id;
            }
            if (in_array('priority', $task_fields, true)) {
                $data['priority'] = $this->input->post('priority') ?: 'medium';
            }
            if (in_array('start_date', $task_fields, true)) {
                $data['start_date'] = $this->input->post('start_date') ?: null;
            }
            if (in_array('due_date', $task_fields, true)) {
                $data['due_date'] = $this->input->post('due_date') ?: null;
            }
            if ($project_ids_json !== null && in_array('project_ids', $task_fields, true)) {
                $data['project_ids'] = $project_ids_json;
            }
            $reference_url = normalize_optional_url($this->input->post('reference_url'));
            if ($reference_url === false) {
                $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                redirect('tasks/create');
                return;
            }
            $data['reference_url'] = $reference_url;
            // If a requirement is selected, override the title with the requirement's title if title is empty
            if ($requirement_id && empty($data['title'])) {
                $reqTitleRow = $this->db->select('title')->from('requirements')->where('id', (int)$requirement_id)->get()->row();
                if ($reqTitleRow && isset($reqTitleRow->title) && trim((string)$reqTitleRow->title) !== '') {
                    $data['title'] = (string)$reqTitleRow->title;
                }
            }
            // Optional attachment
            if (schema_table_has_column($this->db, 'tasks', 'attachment_path') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/tasks/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
                $this->load->library('upload');
                $config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                    'max_size' => 4096,
                    'encrypt_name' => true,
                ];
                $this->upload->initialize($config);
                if ($this->upload->do_upload('attachment')) {
                    $up = $this->upload->data();
                    $data['attachment_path'] = 'uploads/tasks/'.$up['file_name'];
                } else {
                    $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                    redirect('tasks/create');
                    return;
                }
            }
            $this->db->insert('tasks', $data);
            $id = $this->db->insert_id();
            
            // Log task creation with change tracking
            $this->load->helper('change_tracker');
            $description = 'Task: ' . (string)$data['title'];
            auto_log_insert('tasks', 'tasks', (int)$id, $data, $description);
            
            // Send email notification using settings system
            if (isset($data['assigned_to']) && !empty($data['assigned_to'])){
                // Get task details for email
                $task_details = $this->db->select('t.*, p.name as project_name')
                    ->from('tasks t')
                    ->join('projects p', 'p.id = t.project_id', 'left')
                    ->where('t.id', $id)
                    ->get()->row();
                
                if ($task_details) {
                    $sent = send_notification_with_settings('tasks', 'created', $task_details, $task_details->assigned_to);
                    
                    if ($sent) {
                        log_message('info', 'Task notification sent using settings system for task #' . $id);
                    } else {
                        log_message('info', 'Task notification disabled or failed for task #' . $id);
                    }
                }
                
                // Also create reminder for backward compatibility
                $this->load->model('Reminder_model','reminders');
                $this->reminders->ensure_schema();
                $subject = 'Task assigned: '.(string)$data['title'];
                $body = 'You have been assigned a task: '.(string)$data['title'].'\n\nOpen: '.site_url('tasks/'.$id);
                $this->reminders->enqueue([
                    'user_id' => (int)$data['assigned_to'],
                    'email' => get_user_email_by_id((int)$data['assigned_to']),
                    'type' => 'task_assigned',
                    'subject' => $subject,
                    'body' => $body,
                    'send_at' => date('Y-m-d H:i:00')
                ]);
            }
            $success_msg = get_notification_message('tasks', 'create', 'success');
            $this->session->set_flashdata('success', $success_msg);
            redirect('tasks/'.$id);
            return;
        }
        // GET: load projects, requirements, and users for dropdowns
        // Sort by name ascending, handling NULL/empty names
        $projects = $this->db->select('id, name, code')
                            ->from('projects')
                            ->order_by('name IS NULL ASC', '', false)
                            ->order_by('name', 'ASC')
                            ->get()->result();
        $requirements = [];
        if ($this->db->table_exists('requirements')) {
            $this->db->select('id, project_id, title, req_number')->from('requirements');
            $this->db->order_by('title','ASC');
            $requirements = $this->db->get()->result();
        }
        // Check if requirement_id is passed via query string
        $preselected_requirement = $this->input->get('requirement_id') ? (int)$this->input->get('requirement_id') : null;
        // Prefer employee name when available
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            $select = ['users.id','users.email'];
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'users.name'; }
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'users.full_name'; }
            $hasEmpName = schema_table_has_column($this->db, 'employees', 'name');
            if ($hasEmpName) { $select[] = 'employees.name AS emp_name'; }
            $this->db->select(implode(',', $select))
                     ->from('users')
                     ->join('employees','employees.user_id = users.id','left');
            if ($hasEmpName) {
                $this->db->order_by('employees.name IS NULL ASC', '', false)
                         ->order_by('employees.name','ASC');
            }
            $this->db->order_by('users.email','ASC');
            $users = $this->db->get()->result();
        } else {
            $userSelect = ['id','email'];
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $userSelect[] = 'full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $userSelect[] = 'name'; }
            $users = $this->db->select(implode(',', $userSelect))
                              ->from('users')
                              ->order_by('email','ASC')
                              ->get()->result();
        }
        $this->load->view('tasks/form', [
            'action' => 'create', 
            'projects' => $projects, 
            'users' => $users, 
            'requirements' => $requirements,
            'preselected_requirement' => $preselected_requirement
        ]);
    }

    // GET /tasks/{id}/preview
    public function preview($id)
    {
        $this->db->from('tasks t');
        $select = ['t.*'];
        
        // Join projects for name if available
        if ($this->db->table_exists('projects')) {
            if (schema_table_has_column($this->db, 'projects', 'name')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        
        // Join users for assignee info
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'u.full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        
        // Join employees for assignee employee name
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            if (schema_table_has_column($this->db, 'employees', 'name')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        
        // Join users for creator info
        if ($this->db->table_exists('users')) {
            $select[] = 'cu.email AS creator_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'cu.full_name AS creator_full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'cu.name AS creator_name'; }
            $this->db->join('users cu', 'cu.id = t.created_by', 'left');
        }
        
        // Join requirements if requirement_id exists
        if ($this->db->table_exists('requirements') && schema_table_has_column($this->db, 'tasks', 'requirement_id')) {
            $select[] = 'r.id AS requirement_id';
            $select[] = 'r.req_number AS requirement_number';
            $select[] = 'r.title AS requirement_title';
            $select[] = 'r.status AS requirement_status';
            $this->db->join('requirements r', 'r.id = t.requirement_id', 'left');
        }
        
        $this->db->select(implode(',', $select));
        $this->db->where('t.id', (int)$id);
        $task = $this->db->get()->row();
        
        if (!$task) {
            return $this->output->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => 'Task not found']));
        }
        
        // Visibility: non-admin group can only preview tasks assigned to them (or created_by them when available)
        $user_id = (int)$this->session->userdata('user_id');
        $can_view_all = is_admin_group() || has_module_access('tasks_view_all');
        
        if ($user_id && !$can_view_all) {
            $assigned = isset($task->assigned_to) ? (int)$task->assigned_to : 0;
            $creator = (isset($task->created_by) ? (int)$task->created_by : 0);
            $visible_ids = get_accessible_hierarchy_user_ids($user_id, (int)$this->session->userdata('role_id'));
            $can_see_hierarchy = empty($visible_ids) ? true : in_array($creator, $visible_ids, true);
            if (!$can_see_hierarchy && $assigned !== $user_id && $creator !== $user_id) {
                return $this->output->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['error' => 'Access denied']));
            }
        }
        // Helper functions for names
        $assigneeName = function($task) {
            $name = '';
            if (isset($task->emp_name) && trim((string)$task->emp_name) !== '') { $name = $task->emp_name; }
            else if (isset($task->full_name) && trim((string)$task->full_name) !== '') { $name = $task->full_name; }
            else if (isset($task->name) && trim((string)$task->name) !== '') { $name = $task->name; }
            else if (isset($task->assignee_email)) { $name = $task->assignee_email; }
            return trim((string)$name);
        };
        
        $creatorName = function($task) {
            $name = '';
            if (isset($task->creator_full_name) && trim((string)$task->creator_full_name) !== '') { $name = $task->creator_full_name; }
            else if (isset($task->creator_name) && trim((string)$task->creator_name) !== '') { $name = $task->creator_name; }
            else if (isset($task->creator_email)) { $name = $task->creator_email; }
            return trim((string)$name);
        };
        
        // Generate preview HTML
        $html = '<div class="task-preview">';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-8">';
        
        // Task title and status
        $html .= '<h4 class="mb-3">' . esc_view($task->title) . '</h4>';
        $html .= '<div class="d-flex gap-2 mb-3">';
        $status_labels = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'blocked' => 'Blocked'];
        $status_colors = ['pending' => 'secondary', 'in_progress' => 'info', 'completed' => 'success', 'blocked' => 'danger'];
        $status = isset($task->status) ? $task->status : 'pending';
        $html .= '<span class="badge bg-' . $status_colors[$status] . '">' . $status_labels[$status] . '</span>';
        
        if (isset($task->priority)) {
            $priority_colors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'dark'];
            $html .= '<span class="badge bg-' . $priority_colors[$task->priority] . '">Priority: ' . ucfirst($task->priority) . '</span>';
        }
        $html .= '</div>';
        
        // Description
        if (!empty($task->description)) {
            $allowed = '<p><br><strong><em><b><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
            $desc = strip_tags($task->description, $allowed);
            $html .= '<div class="task-description mb-4">' . $desc . '</div>';
        }
        
        // Dates
        $html .= '<div class="row mb-3">';
        if (!empty($task->start_date)) {
            $html .= '<div class="col-sm-6"><small class="text-muted">Start Date:</small><br><strong>' . date('M j, Y', strtotime($task->start_date)) . '</strong></div>';
        }
        if (!empty($task->due_date)) {
            $html .= '<div class="col-sm-6"><small class="text-muted">Due Date:</small><br><strong>' . date('M j, Y', strtotime($task->due_date)) . '</strong></div>';
        }
        $html .= '</div>';
        
        // Attachment
        if (!empty($task->attachment_path)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Attachment:</small><br>';
            $html .= '<a href="' . base_url($task->attachment_path) . '" target="_blank" class="btn btn-sm btn-outline-primary">';
            $html .= '<i class="bi bi-file-earmark me-1"></i>View Attachment</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="col-md-4">';
        
        // Sidebar info
        $html .= '<div class="card bg-light">';
        $html .= '<div class="card-body">';
        
        // Task ID
        $html .= '<div class="mb-3">';
        $html .= '<small class="text-muted">Task ID:</small><br><strong>#' . $task->id . '</strong>';
        $html .= '</div>';
        
        // Project
        if (!empty($task->project_name)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Project:</small><br><strong>' . esc_view($task->project_name) . '</strong>';
            $html .= '</div>';
        }
        
        // Requirement
        if (!empty($task->requirement_title)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Requirement:</small><br><strong>' . esc_view($task->requirement_title) . '</strong>';
            $html .= '</div>';
        }

        if (!empty($task->reference_url)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">URL / Link:</small><br>';
            $html .= '<a href="' . esc_view($task->reference_url) . '" target="_blank" rel="noopener noreferrer" class="text-break">' . esc_view($task->reference_url) . '</a>';
            $html .= '</div>';
        }
        
        // Assignee
        $assignee = $assigneeName($task);
        if (!empty($assignee)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Assigned to:</small><br><strong>' . esc_view($assignee) . '</strong>';
            $html .= '</div>';
        }
        
        // Creator
        $creator = $creatorName($task);
        if (!empty($creator)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Created by:</small><br><strong>' . esc_view($creator) . '</strong>';
            $html .= '</div>';
        }
        
        // Created date
        if (!empty($task->created_at)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Created:</small><br><strong>' . date('M j, Y H:i', strtotime($task->created_at)) . '</strong>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $this->output->set_content_type('text/html')->set_output($html);
    }

    // GET /tasks/{id}
    public function show($id)
    {
        $this->_ensure_reference_url_column();
        $this->db->from('tasks t');
        $select = ['t.*'];
        
        // Join projects for name if available
        if ($this->db->table_exists('projects')) {
            if (schema_table_has_column($this->db, 'projects', 'name')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        
        // Join users for assignee info
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'u.full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        
        // Join employees for assignee employee name
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            if (schema_table_has_column($this->db, 'employees', 'name')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        
        // Join users for creator info
        if ($this->db->table_exists('users')) {
            $select[] = 'cu.email AS creator_email';
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'cu.full_name AS creator_full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'cu.name AS creator_name'; }
            $this->db->join('users cu', 'cu.id = t.created_by', 'left');
        }
        
        // Join requirements if requirement_id exists
        if ($this->db->table_exists('requirements') && schema_table_has_column($this->db, 'tasks', 'requirement_id')) {
            $select[] = 'r.id AS requirement_id';
            $select[] = 'r.req_number AS requirement_number';
            $select[] = 'r.title AS requirement_title';
            $select[] = 'r.status AS requirement_status';
            $this->db->join('requirements r', 'r.id = t.requirement_id', 'left');
        }
        
        $this->db->select(implode(',', $select));
        $this->db->where('t.id', (int)$id);
        $task = $this->db->get()->row();
        
        if (!$task) show_404();
        
        // Visibility: non-admin group can only view tasks assigned to them (or created_by them when available)
        $user_id = (int)$this->session->userdata('user_id');
        $can_view_all = is_admin_group() || has_module_access('tasks_view_all');
        if ($user_id && !$can_view_all) {
            $assigned = isset($task->assigned_to) ? (int)$task->assigned_to : 0;
            $creator = (isset($task->created_by) ? (int)$task->created_by : 0);
            $visible_ids = get_accessible_hierarchy_user_ids($user_id, (int)$this->session->userdata('role_id'));
            $can_see_hierarchy = empty($visible_ids) ? true : in_array($creator, $visible_ids, true);
            if (!$can_see_hierarchy && $assigned !== $user_id && $creator !== $user_id) { show_error('Forbidden', 403); }
        }
        
        $this->load->view('tasks/view', ['task' => $task]);
    }

    // GET /tasks/{id}/edit, POST /tasks/{id}/edit
    public function edit($id)
    {
        // Check edit permission specifically
        require_module_access(['tasks_edit', 'tasks'], true);

        $this->_ensure_reference_url_column();
        
        // Initialize variables
        $requirements = [];
        
        $task = $this->db->where('id', (int)$id)->get('tasks')->row();
        if (!$task) show_404();
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            // Handle multi-select projects
            $project_ids = $this->input->post('project_ids');
            $project_id = isset($task) ? (int)$task->project_id : 0;
            $project_ids_json = null;
            if (is_array($project_ids) && !empty($project_ids)) {
                $project_ids = array_map('intval', array_filter($project_ids));
                if (!empty($project_ids)) {
                    $project_id = $project_ids[0]; // First project for backward compatibility
                    $project_ids_json = json_encode($project_ids);
                }
            } else {
                // Fallback to single project_id if project_ids not provided
                $project_id = (int)($this->input->post('project_id') ?: $project_id);
                if ($project_id > 0) {
                    $project_ids_json = json_encode([$project_id]);
                }
            }
            
            // Cache field list once — avoids multiple DB roundtrips and safely handles un-migrated schemas
            $task_fields = $this->db->list_fields('tasks');

            $data = [
                'project_id'  => $project_id,
                'title'       => trim($this->input->post('title')),
                'description' => $this->input->post('description', TRUE),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status'      => $this->input->post('status') ?: 'pending',
            ];
            // Guard all migration-added columns
            if (in_array('requirement_id', $task_fields, true)) {
                $data['requirement_id'] = $this->input->post('requirement_id') !== '' ? (int)$this->input->post('requirement_id') : null;
            }
            if (in_array('priority', $task_fields, true)) {
                $data['priority'] = $this->input->post('priority') ?: 'medium';
            }
            if (in_array('start_date', $task_fields, true)) {
                $data['start_date'] = $this->input->post('start_date') ?: null;
            }
            if (in_array('due_date', $task_fields, true)) {
                $data['due_date'] = $this->input->post('due_date') ?: null;
            }
            if ($project_ids_json !== null && in_array('project_ids', $task_fields, true)) {
                $data['project_ids'] = $project_ids_json;
            }
            $reference_url = normalize_optional_url($this->input->post('reference_url'));
            if ($reference_url === false) {
                $this->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
                redirect('tasks/'.$id.'/edit');
                return;
            }
            $data['reference_url'] = $reference_url;
            // Only set updated_by if the column exists in tasks table
            if (schema_table_has_column($this->db, 'tasks', 'updated_by')) {
                $data['updated_by'] = $user_id;
            }
            // Optional new attachment
            if (schema_table_has_column($this->db, 'tasks', 'attachment_path') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/tasks/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
                $this->load->library('upload');
                $config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                    'max_size' => 4096,
                    'encrypt_name' => true,
                ];
                $this->upload->initialize($config);
                if ($this->upload->do_upload('attachment')) {
                    $up = $this->upload->data();
                    $data['attachment_path'] = 'uploads/tasks/'.$up['file_name'];
                } else {
                    $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                    redirect('tasks/'.$id.'/edit');
                    return;
                }
            }
            // Perform update with db_debug disabled so DB errors don't render a full error page
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            $ok = $this->db->where('id', (int)$id)->update('tasks', $data);
            $this->db->db_debug = $prev_debug;
            if (!$ok) {
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    log_message('error', 'Task update error: '.$db_error['message']);
                }
                $error_msg = get_notification_message('tasks', 'update', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('tasks/'.$id.'/edit');
                return;
            }
            
            // Load activity tracking helper
            $this->load->helper('change_tracker');
            
            // Get old data before update (already fetched above as $task)
            $old_data = $task ? (array)$task : track_changes_before('tasks', (int)$id);
            
            // Log update with change tracking
            $description = 'Task: ' . (string)$data['title'];
            track_changes_after('tasks', 'tasks', (int)$id, $old_data, $data, $description);
            
            // Send email notification if assignee changed
            $old_assignee_id = isset($task->assigned_to) ? (int)$task->assigned_to : null;
            $new_assignee_id = isset($data['assigned_to']) ? (int)$data['assigned_to'] : null;
            
            if ($new_assignee_id && $new_assignee_id !== $old_assignee_id) {
                $email = get_user_email_by_id($new_assignee_id);
                
                if ($email) {
                    // Get updated task details with project info for email
                    $task_details = $this->db->select('t.*, p.name as project_name')
                        ->from('tasks t')
                        ->join('projects p', 'p.id = t.project_id', 'left')
                        ->where('t.id', $id)
                        ->get()->row();
                    
                    if ($task_details) {
                        $subject = 'Task Updated: ' . $task_details->title;
                        $sent = send_task_notification($email, $subject, $task_details, 'updated');
                        
                        if ($sent) {
                            log_message('info', 'Task update notification email sent to ' . $email . ' for task #' . $id);
                        } else {
                            log_message('error', 'Failed to send task update notification email to ' . $email . ' for task #' . $id);
                        }
                    }
                }
            }
            
            // Also send notification if status changed (to current assignee)
            $old_status = isset($task->status) ? $task->status : 'pending';
            $new_status = $data['status'];
            
            if ($old_status !== $new_status && $new_assignee_id) {
                $email = get_user_email_by_id($new_assignee_id);
                
                if ($email) {
                    // Get updated task details with project info for email
                    $task_details = $this->db->select('t.*, p.name as project_name')
                        ->from('tasks t')
                        ->join('projects p', 'p.id = t.project_id', 'left')
                        ->where('t.id', $id)
                        ->get()->row();
                    
                    if ($task_details) {
                        $subject = 'Task Status Changed: ' . $task_details->title;
                        $sent = send_task_notification($email, $subject, $task_details, 'status_changed');
                        
                        if ($sent) {
                            log_message('info', 'Task status change notification email sent to ' . $email . ' for task #' . $id);
                        } else {
                            log_message('error', 'Failed to send task status change notification email to ' . $email . ' for task #' . $id);
                        }
                    }
                }
            }
            
            $success_msg = get_notification_message('tasks', 'update', 'success');
            $this->session->set_flashdata('success', $success_msg);
            redirect('tasks/'.$id);
            return;
        }
        // GET: load projects, requirements, and users for dropdowns
        // Sort by name ascending, handling NULL/empty names
        $projects = $this->db->select('id, name, code')
                            ->from('projects')
                            ->order_by('name IS NULL ASC', '', false)
                            ->order_by('name', 'ASC')
                            ->get()->result();
        
        // Load requirements
        $requirements = [];
        if ($this->db->table_exists('requirements')) {
            $this->db->select('id, project_id, title, req_number')->from('requirements');
            $this->db->order_by('title','ASC');
            $requirements = $this->db->get()->result();
        }
        
        // Load users
        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            $select = ['users.id','users.email'];
            if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'users.name'; }
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'users.full_name'; }
            $hasEmpName2 = schema_table_has_column($this->db, 'employees', 'name');
            if ($hasEmpName2) { $select[] = 'employees.name AS emp_name'; }
            $this->db->select(implode(',', $select))
                     ->from('users')
                     ->join('employees','employees.user_id = users.id','left');
            if ($hasEmpName2) {
                $this->db->order_by('employees.name IS NULL ASC', '', false)
                         ->order_by('employees.name','ASC');
            }
            $this->db->order_by('users.email','ASC');
            $users = $this->db->get()->result();
        } else {
            $userSelect = ['id','email'];
            if (schema_table_has_column($this->db, 'users', 'full_name')) { $userSelect[] = 'full_name'; }
            if (schema_table_has_column($this->db, 'users', 'name')) { $userSelect[] = 'name'; }
            $users = $this->db->select(implode(',', $userSelect))
                              ->from('users')
                              ->order_by('email','ASC')
                              ->get()->result();
        }
        // Load statuses from database
        $this->load->model('Status_model', 'statuses');
        $statuses_list = $this->statuses->get_by_type('tasks', true);
        
        $this->load->view('tasks/form', [
            'action' => 'edit', 
            'task' => $task, 
            'projects' => $projects, 
            'users' => $users, 
            'requirements' => $requirements,
            'statuses' => $statuses_list
        ]);
    }

    // POST /tasks/{id}/delete
    public function delete($id)
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }
        // Check delete permission specifically
        require_module_access(['tasks_delete', 'tasks'], true);
        
        // Ownership: non-admin group can only delete tasks assigned to them (or created_by them when available)
        $task = $this->db->where('id', (int)$id)->get('tasks')->row();
        if (!$task) { show_404(); }
        $currentUser = (int)$this->session->userdata('user_id');
        $can_manage_all = is_admin_group() || has_module_access('tasks_delete_all');
        if ($currentUser && !$can_manage_all) {
            $assigned = isset($task->assigned_to) ? (int)$task->assigned_to : 0;
            $creator = (isset($task->created_by) ? (int)$task->created_by : 0);
            if ($assigned !== $currentUser && $creator !== $currentUser) { show_error('Forbidden', 403); }
        }

        // Load activity tracking helper
        $this->load->helper('change_tracker');
        
        // Get old data before delete (already fetched above as $task)
        $old_data = $task ? (array)$task : track_changes_before('tasks', (int)$id);

        $this->db->where('id', (int)$id)->delete('tasks');
        
        // Log deletion
        $description = 'Task deleted' . ($task && isset($task->title) ? ': ' . $task->title : '');
        auto_log_delete('tasks', 'tasks', (int)$id, $old_data, $description);
        
        $success_msg = get_notification_message('tasks', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('tasks');
    }

    // GET /tasks/my-dashboard
    public function user_dashboard()
    {
        require_module_access(['tasks_list', 'tasks'], true);

        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        if ($user_id < 1) {
            redirect('auth/login');
            return;
        }

        $can_view_all = $this->_user_dashboard_can_view_all($role_id);

        $this->load->model('Status_model', 'statuses');
        $status_rows = $this->statuses->get_by_type('tasks', true);
        if (empty($status_rows)) {
            $status_rows = array(
                (object) array('code' => 'pending', 'name' => 'Pending', 'color' => '#6c757d'),
                (object) array('code' => 'in_progress', 'name' => 'In Progress', 'color' => '#007bff'),
                (object) array('code' => 'completed', 'name' => 'Completed', 'color' => '#28a745'),
                (object) array('code' => 'blocked', 'name' => 'Blocked', 'color' => '#dc3545'),
            );
        }

        $filter_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($this->input->get('user_id') !== null ? (int)$this->input->get('user_id') : -1);
        $filter_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : ($this->input->get('project_id') !== null ? (int)$this->input->get('project_id') : -1);
        $filter_status = isset($_GET['status']) ? (string)$_GET['status'] : ($this->input->get('status') !== null ? (string)$this->input->get('status') : 'all');

        // When viewing a specific employee's detail, resolve their name (not the logged-in user's name)
        $display_name = ($filter_user_id > 0)
            ? $this->_user_dashboard_display_name($filter_user_id)
            : $this->_user_dashboard_display_name($user_id);

        $filter_projects = array();
        if ($this->db->table_exists('projects')) {
            $filter_projects = $this->db->select('id, name')->order_by('name', 'asc')->get('projects')->result();
        }

        $filter_users = array();
        if ($this->db->table_exists('users')) {
            $filter_users = $this->db->select('id, name')->order_by('name', 'asc')->get('users')->result();
        }

        $tasks = $this->_user_dashboard_fetch_tasks($user_id, $role_id, $can_view_all, $filter_user_id, $filter_project_id, $filter_status);

        $type_counts = array(
            'total'        => count($tasks),
            'project_task' => 0,
            'my_work'      => 0,
            'ad_hoc'       => 0,
            'requirement'  => 0,
        );
        foreach ($tasks as $t) {
            $t_type = isset($t->item_type) ? (string) $t->item_type : 'project_task';
            if (isset($type_counts[$t_type])) {
                $type_counts[$t_type]++;
            }
        }

        if ($can_view_all) {
            $group_cards = $this->_user_dashboard_employee_cards($tasks, $status_rows, $filter_user_id);
            $group_mode = 'employee';
            $page_title = 'Team Dashboard';
            $subtitle = 'All employee tasks, requirements, and work items';
        } else {
            $group_cards = $this->_user_dashboard_project_cards($tasks, $status_rows);
            $group_mode = 'project';
            $page_title = 'My Team Dashboard';
            $subtitle = 'Items assigned to you or created by you';
            if (!empty($display_name)) {
                $subtitle = 'Items for ' . $display_name . ' (assigned or created by you)';
            }
        }

        $this->load->view('tasks/user_dashboard', array(
            'status_rows'   => $status_rows,
            'group_cards'   => $group_cards,
            'group_mode'    => $group_mode,
            'page_title'    => $page_title,
            'display_name'  => $display_name,
            'task_total'    => count($tasks),
            'type_counts'   => $type_counts,
            'is_admin_view' => $can_view_all,
            'subtitle'      => $subtitle,
            'filter_user_id'    => $filter_user_id,
            'filter_project_id' => $filter_project_id,
            'filter_status'     => $filter_status,
            'filter_projects'   => $filter_projects,
            'filter_users'      => $filter_users,
        ));
    }

    public function ajax_update_item_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = (int) $this->input->post('id');
        $type = (string) $this->input->post('type');
        $source = trim((string) $this->input->post('source'));
        $status = (string) $this->input->post('status');

        if ($id <= 0 || empty($type) || empty($status)) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Invalid parameters.']));
        }

        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        $is_admin = $this->_user_dashboard_can_view_all($role_id);

        $table = '';
        $assigned_field = '';
        $created_field = '';

        if ($source === 'my_works' || $type === 'my_work') {
            $table = 'my_works';
            $assigned_field = 'created_for';
            $created_field = 'created_by';
        } else if ($source === 'requirements' || $type === 'requirement') {
            $table = 'requirements';
            $assigned_field = 'assigned_to';
            $created_field = 'created_by';
        } else if ($source === 'tasks' || $type === 'project_task' || $type === 'ad_hoc') {
            $table = 'tasks';
            $assigned_field = 'assigned_to';
            $created_field = 'created_by';
        }

        if (empty($table) || !$this->db->table_exists($table)) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Invalid item type.']));
        }

        // Check permission if not admin
        if (!$is_admin) {
            $this->db->where('id', $id);
            $item = $this->db->get($table)->row();
            if (!$item) {
                return $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Item not found.']));
            }
            $assigned_to = isset($item->{$assigned_field}) ? (int) $item->{$assigned_field} : 0;
            $created_by = isset($item->{$created_field}) ? (int) $item->{$created_field} : 0;
            
            if ($assigned_to !== $user_id && $created_by !== $user_id) {
                return $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Permission denied.']));
            }
        }

        $this->db->where('id', $id);
        $updated = $this->db->update($table, ['status' => $status]);

        if ($updated) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => true]));
        } else {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Database error.']));
        }
    }

    /**
     * @param int $role_id
     * @return bool
     */
    private function _user_dashboard_can_view_all($role_id)
    {
        $role_id = (int) $role_id;
        if ($role_id === 1) {
            return true;
        }
        if (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
            return true;
        }
        if (function_exists('has_module_access') && has_module_access('tasks_view_all')) {
            return true;
        }
        if (function_exists('is_admin_group') && is_admin_group()) {
            return true;
        }
        return false;
    }

    /**
     * @param object $t
     * @param array $status_map
     * @return array
     */
    private function _user_dashboard_format_item($t, $status_map = array())
    {
        $item_type = isset($t->item_type) ? (string) $t->item_type : 'project_task';
        $item_source = isset($t->item_source) ? (string) $t->item_source : '';
        if ($item_source === '') {
            if ($item_type === 'requirement') {
                $item_source = 'requirements';
            } else if ($item_type === 'my_work') {
                $item_source = 'my_works';
            } else {
                $item_source = 'tasks';
            }
        }

        $url = '#';
        if ($item_source === 'tasks') {
            $url = site_url('tasks/view/' . $t->id);
        } else if ($item_source === 'requirements') {
            $url = site_url('requirements/view/' . $t->id);
        } else if ($item_source === 'my_works') {
            $url = site_url('my-works/' . $t->id);
        }

        $status = isset($t->status) ? $t->status : 'pending';
        $status_label = ucfirst(str_replace('_', ' ', $status));
        $status_color = '#6b7280';
        if (isset($status_map[$status])) {
            $status_label = isset($status_map[$status]->name) ? $status_map[$status]->name : $status_label;
            $status_color = isset($status_map[$status]->color) ? $status_map[$status]->color : $status_color;
        }

        $detail = '';
        if (!empty($t->project_name)) {
            $detail = trim((string) $t->project_name);
        }

        return array(
            'item_type'    => $item_type,
            'item_source'  => $item_source,
            'id'           => $t->id,
            'title'        => isset($t->title) ? $t->title : '',
            'status'       => $status,
            'status_label' => $status_label,
            'status_color' => $status_color,
            'date'         => isset($t->due_date) ? $t->due_date : '',
            'url'          => $url,
            'detail'       => $detail,
        );
    }

    /**
     * @param int $user_id
     * @return string
     */
    private function _user_dashboard_display_name($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return '';
        }

        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'name')) {
            $emp = $this->db->select('name')->from('employees')->where('user_id', $user_id)->limit(1)->get()->row();
            if ($emp && trim((string) $emp->name) !== '') {
                return trim((string) $emp->name);
            }
        }

        if ($this->db->table_exists('users')) {
            $user = $this->db->where('id', $user_id)->limit(1)->get('users')->row();
            if ($user) {
                if (schema_table_has_column($this->db, 'users', 'full_name') && trim((string) $user->full_name) !== '') {
                    return trim((string) $user->full_name);
                }
                if (schema_table_has_column($this->db, 'users', 'name') && trim((string) $user->name) !== '') {
                    return trim((string) $user->name);
                }
                if (!empty($user->email)) {
                    return (string) $user->email;
                }
            }
        }

        return '';
    }

    /**
     * @param int $user_id
     * @param int $role_id
     * @param bool $can_view_all
     * @param int $filter_user_id
     * @param int $filter_project_id
     * @param string $filter_status
     * @return array
     */
    private function _user_dashboard_fetch_tasks($user_id, $role_id, $can_view_all, $filter_user_id = -1, $filter_project_id = -1, $filter_status = 'all')
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return array();
        }

        $all_items = array();
        $this->load->helper('my_works_status');

        // 1. Fetch Tasks
        if ($this->db->table_exists('tasks')) {
            $select = array(
                "'project_task' AS item_type",
                't.id',
                't.project_id',
                't.title',
                't.status',
                't.due_date',
                't.start_date',
                't.created_at',
                't.assigned_to',
            );
            if (schema_table_has_column($this->db, 'tasks', 'created_by')) {
                $select[] = 't.created_by';
            }
            if (schema_table_has_column($this->db, 'tasks', 'priority')) {
                $select[] = 't.priority';
            }

            $this->db->from('tasks t');

            if ($this->db->table_exists('projects') && schema_table_has_column($this->db, 'projects', 'name')) {
                $select[] = 'p.name AS project_name';
                $this->db->join('projects p', 'p.id = t.project_id', 'left');
            }

            if ($can_view_all) {
                if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                    if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                        if (schema_table_has_column($this->db, 'employees', 'name')) {
                            $select[] = 'e.name AS assignee_name';
                        }
                        if (schema_table_has_column($this->db, 'employees', 'emp_code')) {
                            $select[] = 'e.emp_code AS assignee_code';
                        }
                        $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
                    }
                }
                if ($this->db->table_exists('users')) {
                    if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                        if (schema_table_has_column($this->db, 'users', 'email')) {
                            $select[] = 'u.email AS assignee_email';
                        }
                        if (schema_table_has_column($this->db, 'users', 'full_name')) {
                            $select[] = 'u.full_name AS assignee_full_name';
                        }
                        if (schema_table_has_column($this->db, 'users', 'name')) {
                            $select[] = 'u.name AS assignee_user_name';
                        }
                        $this->db->join('users u', 'u.id = t.assigned_to', 'left');
                    }
                }
            }

            $this->db->select(implode(',', $select));

            if ($filter_project_id > 0 && schema_table_has_column($this->db, 'tasks', 'project_id')) {
                $this->db->where('t.project_id', $filter_project_id);
            }
            if ($filter_status !== 'all' && schema_table_has_column($this->db, 'tasks', 'status')) {
                $this->db->where('t.status', $filter_status);
            }
            if ($filter_user_id > 0 && schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                $this->db->where('t.assigned_to', $filter_user_id);
            }

            if (!$can_view_all) {
                $this->db->group_start();
                $this->db->where('t.assigned_to', $user_id);
                if (schema_table_has_column($this->db, 'tasks', 'created_by')) {
                    $this->db->or_where('t.created_by', $user_id);
                }
                $this->db->group_end();
            }

            $task_rows = $this->db->get()->result();
            foreach ($task_rows as $row) {
                $project_id = isset($row->project_id) ? (int) $row->project_id : 0;
                $row->item_source = 'tasks';
                $row->item_type = ($project_id > 0) ? 'project_task' : 'ad_hoc';
                $all_items[] = $row;
            }
        }

        // 2. Fetch Requirements
        if ($this->db->table_exists('requirements')) {
            $select = array(
                "'requirement' AS item_type",
                'r.id',
                'r.title',
                'r.status',
                'r.created_at',
                'r.assigned_to',
            );
            if (schema_table_has_column($this->db, 'requirements', 'project_id')) {
                $select[] = 'r.project_id';
            }
            if (schema_table_has_column($this->db, 'requirements', 'expected_delivery_date')) {
                $select[] = 'r.expected_delivery_date AS due_date';
            } else if (schema_table_has_column($this->db, 'requirements', 'due_date')) {
                $select[] = 'r.due_date';
            } else if (schema_table_has_column($this->db, 'requirements', 'received_date')) {
                $select[] = 'r.received_date AS due_date';
            }

            $this->db->from('requirements r');

            if ($this->db->table_exists('projects') && schema_table_has_column($this->db, 'projects', 'name')) {
                $select[] = 'p.name AS project_name';
                if (schema_table_has_column($this->db, 'requirements', 'project_id')) {
                    $this->db->join('projects p', 'p.id = r.project_id', 'left');
                }
            }

            if ($can_view_all) {
                if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                    if (schema_table_has_column($this->db, 'requirements', 'assigned_to')) {
                        if (schema_table_has_column($this->db, 'employees', 'name')) {
                            $select[] = 'e.name AS assignee_name';
                        }
                        if (schema_table_has_column($this->db, 'employees', 'emp_code')) {
                            $select[] = 'e.emp_code AS assignee_code';
                        }
                        $this->db->join('employees e', 'e.user_id = r.assigned_to', 'left');
                    }
                }
                if ($this->db->table_exists('users')) {
                    if (schema_table_has_column($this->db, 'requirements', 'assigned_to')) {
                        if (schema_table_has_column($this->db, 'users', 'email')) {
                            $select[] = 'u.email AS assignee_email';
                        }
                        if (schema_table_has_column($this->db, 'users', 'full_name')) {
                            $select[] = 'u.full_name AS assignee_full_name';
                        }
                        if (schema_table_has_column($this->db, 'users', 'name')) {
                            $select[] = 'u.name AS assignee_user_name';
                        }
                        $this->db->join('users u', 'u.id = r.assigned_to', 'left');
                    }
                }
            }

            $this->db->select(implode(',', $select));

            if ($filter_project_id > 0 && schema_table_has_column($this->db, 'requirements', 'project_id')) {
                $this->db->where('r.project_id', $filter_project_id);
            }
            if ($filter_status !== 'all' && schema_table_has_column($this->db, 'requirements', 'status')) {
                $this->db->where('r.status', $filter_status);
            }
            if ($filter_user_id > 0 && schema_table_has_column($this->db, 'requirements', 'assigned_to')) {
                $this->db->where('r.assigned_to', $filter_user_id);
            }

            if (!$can_view_all) {
                $this->db->group_start();
                $this->db->where('r.assigned_to', $user_id);
                if (schema_table_has_column($this->db, 'requirements', 'created_by')) {
                    $this->db->or_where('r.created_by', $user_id);
                }
                $this->db->group_end();
            }

            $req_rows = $this->db->get()->result();
            foreach ($req_rows as $row) {
                $row->item_source = 'requirements';
                $all_items[] = $row;
            }
        }

        // 3. Fetch My Works (Second Brain)
        if ($this->db->table_exists('my_works')) {
            $select = array(
                "'my_work' AS item_type",
                'm.id',
                'm.title',
                'm.status',
                'm.created_at',
            );
            if (schema_table_has_column($this->db, 'my_works', 'due_date')) {
                $select[] = 'm.due_date';
            }
            if (schema_table_has_column($this->db, 'my_works', 'created_for')) {
                $select[] = 'm.created_for AS assigned_to';
            } else if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                $select[] = 'm.created_by AS assigned_to';
            }
            if (schema_table_has_column($this->db, 'my_works', 'project_id')) {
                $select[] = 'm.project_id';
            }

            $this->db->from('my_works m');

            if ($this->db->table_exists('projects') && schema_table_has_column($this->db, 'projects', 'name')
                && schema_table_has_column($this->db, 'my_works', 'project_id')) {
                $select[] = 'p.name AS project_name';
                $this->db->join('projects p', 'p.id = m.project_id', 'left');
            }

            if ($can_view_all) {
                if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                    if (schema_table_has_column($this->db, 'my_works', 'created_for')) {
                        if (schema_table_has_column($this->db, 'employees', 'name')) {
                            $select[] = 'e.name AS assignee_name';
                        }
                        if (schema_table_has_column($this->db, 'employees', 'emp_code')) {
                            $select[] = 'e.emp_code AS assignee_code';
                        }
                        $this->db->join('employees e', 'e.user_id = m.created_for', 'left');
                    } else if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                        if (schema_table_has_column($this->db, 'employees', 'name')) {
                            $select[] = 'e.name AS assignee_name';
                        }
                        if (schema_table_has_column($this->db, 'employees', 'emp_code')) {
                            $select[] = 'e.emp_code AS assignee_code';
                        }
                        $this->db->join('employees e', 'e.user_id = m.created_by', 'left');
                    }
                }
                if ($this->db->table_exists('users')) {
                    if (schema_table_has_column($this->db, 'my_works', 'created_for')) {
                        if (schema_table_has_column($this->db, 'users', 'email')) {
                            $select[] = 'u.email AS assignee_email';
                        }
                        if (schema_table_has_column($this->db, 'users', 'full_name')) {
                            $select[] = 'u.full_name AS assignee_full_name';
                        }
                        if (schema_table_has_column($this->db, 'users', 'name')) {
                            $select[] = 'u.name AS assignee_user_name';
                        }
                        $this->db->join('users u', 'u.id = m.created_for', 'left');
                    } else if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                        if (schema_table_has_column($this->db, 'users', 'email')) {
                            $select[] = 'u.email AS assignee_email';
                        }
                        if (schema_table_has_column($this->db, 'users', 'full_name')) {
                            $select[] = 'u.full_name AS assignee_full_name';
                        }
                        if (schema_table_has_column($this->db, 'users', 'name')) {
                            $select[] = 'u.name AS assignee_user_name';
                        }
                        $this->db->join('users u', 'u.id = m.created_by', 'left');
                    }
                }
            }

            $this->db->select(implode(',', $select));

            if ($filter_project_id > 0 && schema_table_has_column($this->db, 'my_works', 'project_id')) {
                $this->db->where('m.project_id', $filter_project_id);
            }
            if ($filter_status !== 'all' && schema_table_has_column($this->db, 'my_works', 'status')) {
                $this->db->where('m.status', $filter_status);
            }
            if ($filter_user_id > 0) {
                if (schema_table_has_column($this->db, 'my_works', 'created_for')) {
                    $this->db->where('m.created_for', $filter_user_id);
                } else if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                    $this->db->where('m.created_by', $filter_user_id);
                }
            }

            if (!$can_view_all) {
                if (schema_table_has_column($this->db, 'my_works', 'created_for')) {
                    $this->db->group_start();
                    $this->db->where('m.created_for', $user_id);
                    if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                        $this->db->or_where('m.created_by', $user_id);
                    }
                    $this->db->group_end();
                } else if (schema_table_has_column($this->db, 'my_works', 'created_by')) {
                    $this->db->where('m.created_by', $user_id);
                }
            }

            $my_work_rows = $this->db->get()->result();
            foreach ($my_work_rows as $row) {
                $project_id = isset($row->project_id) ? (int) $row->project_id : 0;
                $has_project = $project_id > 0 || !empty($row->project_name);
                $row->item_source = 'my_works';
                $row->item_type = $has_project ? 'my_work' : 'ad_hoc';
                if ($filter_status === 'all' && isset($row->status) && my_works_status_is_closed($row->status)) {
                    continue;
                }
                $all_items[] = $row;
            }
        }

        usort($all_items, function ($a, $b) {
            $dateA = isset($a->due_date) && !empty($a->due_date) ? $a->due_date : '9999-12-31';
            $dateB = isset($b->due_date) && !empty($b->due_date) ? $b->due_date : '9999-12-31';
            if ($dateA === $dateB) {
                $idA = isset($a->id) ? (int)$a->id : 0;
                $idB = isset($b->id) ? (int)$b->id : 0;
                return $idA - $idB;
            }
            return strcmp($dateA, $dateB);
        });

        return $all_items;
    }

    /**
     * @param array $tasks
     * @param array $status_rows
     * @return array
     */
    private function _user_dashboard_employee_cards($tasks, $status_rows = array(), $filter_user_id = -1)
    {
        $status_map = array();
        if (is_array($status_rows)) {
            foreach ($status_rows as $sr) {
                if (isset($sr->code)) {
                    $status_map[$sr->code] = $sr;
                }
            }
        }

        $grouped = array();
        $employees_info = array();

        if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
            $select = array('user_id');
            if (schema_table_has_column($this->db, 'employees', 'name')) {
                $select[] = 'name AS assignee_name';
            }
            if (schema_table_has_column($this->db, 'employees', 'emp_code')) {
                $select[] = 'emp_code AS assignee_code';
            }
            $this->db->select(implode(',', $select));
            if (schema_table_has_column($this->db, 'employees', 'status')) {
                $this->db->where('status', 'active');
            }
            // If a specific user is being filtered, only load that employee record
            if ($filter_user_id > 0) {
                $this->db->where('user_id', $filter_user_id);
            }
            $all_emps = $this->db->get('employees')->result();
            foreach ($all_emps as $emp) {
                if (!empty($emp->user_id)) {
                    $uid = (int) $emp->user_id;
                    $grouped[$uid] = array();
                    $employees_info[$uid] = $emp;
                }
            }
        }

        foreach ($tasks as $task) {
            $assignee_id = isset($task->assigned_to) ? (int) $task->assigned_to : 0;
            if (!isset($grouped[$assignee_id])) {
                $grouped[$assignee_id] = array();
            }
            $grouped[$assignee_id][] = $task;
        }

        if (empty($grouped)) {
            return array();
        }

        $cards = array();
        foreach ($grouped as $assignee_id => $employee_tasks) {
            $assignee_id = (int) $assignee_id;
            $label = 'Unassigned';
            $code = '—';

            if (isset($employees_info[$assignee_id])) {
                $emp_info = $employees_info[$assignee_id];
                if (!empty($emp_info->assignee_name)) {
                    $label = trim((string) $emp_info->assignee_name);
                }
                if (!empty($emp_info->assignee_code)) {
                    $code = trim((string) $emp_info->assignee_code);
                }
            }

            if ($assignee_id > 0 && !empty($employee_tasks)) {
                $first = $employee_tasks[0];
                if ($label === 'Unassigned') {
                    if (!empty($first->assignee_name)) {
                        $label = trim((string) $first->assignee_name);
                    } else if (!empty($first->assignee_full_name)) {
                        $label = trim((string) $first->assignee_full_name);
                    } else if (!empty($first->assignee_user_name)) {
                        $label = trim((string) $first->assignee_user_name);
                    } else if (!empty($first->assignee_email)) {
                        $label = trim((string) $first->assignee_email);
                    } else {
                        $label = $this->_user_dashboard_display_name($assignee_id);
                    }
                }
                
                if ($code === '—' || $code === '') {
                    if (!empty($first->assignee_code)) {
                        $code = trim((string) $first->assignee_code);
                    } else {
                        $code = '#' . $assignee_id;
                    }
                }
            }
            
            if ($label === 'Unassigned' && $assignee_id > 0) {
                $display_name = $this->_user_dashboard_display_name($assignee_id);
                if (!empty($display_name)) {
                    $label = $display_name;
                } else {
                    $label = 'User #' . $assignee_id;
                }
                if ($code === '—' || $code === '') {
                    $code = '#' . $assignee_id;
                }
            }

            $formatted_items = array();
            foreach ($employee_tasks as $t) {
                $formatted_items[] = $this->_user_dashboard_format_item($t, $status_map);
            }

            $cards[] = array(
                'entity' => (object) array(
                    'id'   => $assignee_id,
                    'code' => $code,
                    'name' => $label,
                ),
                'items' => $formatted_items,
            );
        }

        usort($cards, function ($a, $b) {
            $a_name = isset($a['entity']->name) ? strtolower(trim((string) $a['entity']->name)) : '';
            $b_name = isset($b['entity']->name) ? strtolower(trim((string) $b['entity']->name)) : '';
            if ($a_name === 'unassigned') {
                return 1;
            }
            if ($b_name === 'unassigned') {
                return -1;
            }
            return strcmp($a_name, $b_name);
        });

        return $cards;
    }


    /**
     * @param array $tasks
     * @param array $status_rows
     * @return array
     */
    private function _user_dashboard_project_cards($tasks, $status_rows = array())
    {
        $status_map = array();
        if (is_array($status_rows)) {
            foreach ($status_rows as $sr) {
                if (isset($sr->code)) {
                    $status_map[$sr->code] = $sr;
                }
            }
        }

        $grouped = array();
        foreach ($tasks as $task) {
            $project_id = isset($task->project_id) ? (int) $task->project_id : 0;
            if (!isset($grouped[$project_id])) {
                $grouped[$project_id] = array();
            }
            $grouped[$project_id][] = $task;
        }

        if (empty($grouped)) {
            return array();
        }

        $project_ids = array();
        foreach (array_keys($grouped) as $project_id) {
            $project_id = (int) $project_id;
            if ($project_id > 0) {
                $project_ids[] = $project_id;
            }
        }

        $projects_by_id = array();
        if (!empty($project_ids) && $this->db->table_exists('projects')) {
            $rows = $this->db->where_in('id', $project_ids)->get('projects')->result();
            foreach ($rows as $row) {
                $projects_by_id[(int) $row->id] = $row;
            }
        }

        $cards = array();
        foreach ($grouped as $project_id => $project_tasks) {
            $project_id = (int) $project_id;
            if ($project_id > 0 && isset($projects_by_id[$project_id])) {
                $project = $projects_by_id[$project_id];
            } else {
                $project = (object) array(
                    'id'   => 0,
                    'code' => '—',
                    'name' => 'General Tasks',
                );
            }

            $formatted_items = array();
            foreach ($project_tasks as $t) {
                $formatted_items[] = $this->_user_dashboard_format_item($t, $status_map);
            }

            $cards[] = array(
                'entity' => $project,
                'items'   => $formatted_items,
            );
        }

        usort($cards, function ($a, $b) {
            $a_name = isset($a['entity']->name) ? strtolower(trim((string) $a['entity']->name)) : '';
            $b_name = isset($b['entity']->name) ? strtolower(trim((string) $b['entity']->name)) : '';
            if ($a_name === $b_name) {
                $a_code = isset($a['entity']->code) ? strtolower(trim((string) $a['entity']->code)) : '';
                $b_code = isset($b['entity']->code) ? strtolower(trim((string) $b['entity']->code)) : '';
                return strcmp($a_code, $b_code);
            }
            if ($a_name === 'general tasks') {
                return 1;
            }
            if ($b_name === 'general tasks') {
                return -1;
            }
            return strcmp($a_name, $b_name);
        });

        return $cards;
    }

    // GET /tasks/board
    public function board()
    {
        $statuses = ['pending','in_progress','completed','blocked'];
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        
        // Get filters from GET parameters
        $project_filter = trim((string)$this->input->get('project_id'));
        $assignee_filter = trim((string)$this->input->get('assigned_to'));
        $priority_filter = trim((string)$this->input->get('priority'));
        
        $columns = [];
        foreach ($statuses as $st) {
            $this->db->from('tasks t');
            $select = ['t.*'];
            if ($this->db->table_exists('projects')) {
                if (schema_table_has_column($this->db, 'projects', 'name')) { $select[] = 'p.name AS project_name'; }
                $this->db->join('projects p','p.id = t.project_id','left');
            }
            if ($this->db->table_exists('users')) {
                $select[] = 'u.email AS assignee_email';
                if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'u.full_name'; }
                if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'u.name'; }
                $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            }
            if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                if (schema_table_has_column($this->db, 'employees', 'name')) { $select[] = 'e.name AS emp_name'; }
                $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
            }
            $this->db->select(implode(',', $select));
            
            // Apply base filters
            if (!$is_admin && $user_id) {
                apply_role_hierarchy_filter($this->db, 't.created_by', $user_id, $role_id);
            }
            $this->db->where('t.status', $st);
            
            // Apply additional filters
            if ($project_filter !== '') { 
                $this->db->where('t.project_id', (int)$project_filter); 
            }
            if ($is_admin && $assignee_filter !== '') { 
                $this->db->where('t.assigned_to', (int)$assignee_filter); 
            }
            if ($priority_filter !== '' && schema_table_has_column($this->db, 'tasks', 'priority')) { 
                $this->db->where('t.priority', $priority_filter); 
            }
            
            $this->db->order_by('t.id','DESC');
            $columns[$st] = $this->db->get()->result();
        }
        
        // Get filter options
        $projects = [];
        if ($this->db->table_exists('projects')) {
            $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        }
        
        $assignees = [];
        if ($is_admin) {
            if ($this->db->table_exists('employees') && schema_table_has_column($this->db, 'employees', 'user_id')) {
                $sel = ['users.id','users.email'];
                $hasEmpName3 = schema_table_has_column($this->db, 'employees', 'name');
                if ($hasEmpName3) { $sel[] = 'employees.name AS emp_name'; }
                if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'users.full_name'; }
                if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'users.name'; }
                $this->db->select(implode(',', $sel))
                         ->from('users')
                         ->join('employees','employees.user_id = users.id','left');
                if ($hasEmpName3) {
                    $this->db->order_by('employees.name IS NULL ASC', '', false)
                             ->order_by('employees.name','ASC');
                }
                $this->db->order_by('users.email','ASC');
                $assignees = $this->db->get()->result();
            } else if ($this->db->table_exists('users')) {
                $sel = ['id','email'];
                if (schema_table_has_column($this->db, 'users', 'full_name')) { $sel[] = 'full_name'; }
                if (schema_table_has_column($this->db, 'users', 'name')) { $sel[] = 'name'; }
                $assignees = $this->db->select(implode(',', $sel))->from('users')->order_by('email','ASC')->get()->result();
            }
        }
        
        $board_stats = array();
        $total_tasks = 0;
        foreach ($statuses as $st) {
            $cnt = count($columns[$st]);
            $board_stats[$st] = $cnt;
            $total_tasks += $cnt;
        }
        $completed_count = isset($board_stats['completed']) ? (int) $board_stats['completed'] : 0;
        $board_progress = ($total_tasks > 0) ? (int) round(($completed_count / $total_tasks) * 100) : 0;

        $this->load->view('tasks/board', [
            'columns' => $columns,
            'projects' => $projects,
            'assignees' => $assignees,
            'filter_project_id' => $project_filter,
            'filter_assigned_to' => $assignee_filter,
            'filter_priority' => $priority_filter,
            'board_stats' => $board_stats,
            'total_tasks' => $total_tasks,
            'board_progress' => $board_progress,
        ]);
    }

    // POST /tasks/update-status
    public function update_status()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = (int)$this->input->post('id');
        $status = trim($this->input->post('status'));
        if (!$id || !in_array($status, ['pending','in_progress','completed','blocked'])) {
            return $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'Invalid input']));
        }
        
        // Get current task details before update
        $task = $this->db->where('id', $id)->get('tasks')->row();
        if (!$task) {
            return $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'Task not found']));
        }
        
        $old_status = $task->status;
        
        // Update task status
        $this->db->where('id',$id)->update('tasks',['status'=>$status]);
        $this->load->helper('activity');
        log_activity('tasks', 'status_changed', (int)$id, 'Status: '.$status);
        
        // Send email notification if status changed and task has assignee
        if ($old_status !== $status && !empty($task->assigned_to)) {
            $email = get_user_email_by_id($task->assigned_to);
            
            if ($email) {
                // Get updated task details with project info for email
                $task_details = $this->db->select('t.*, p.name as project_name')
                    ->from('tasks t')
                    ->join('projects p', 'p.id = t.project_id', 'left')
                    ->where('t.id', $id)
                    ->get()->row();
                
                if ($task_details) {
                    $subject = 'Task Status Changed: ' . $task_details->title;
                    $sent = send_task_notification($email, $subject, $task_details, 'status_changed');
                    
                    if ($sent) {
                        log_message('info', 'Task status change notification email sent to ' . $email . ' for task #' . $id);
                    } else {
                        log_message('error', 'Failed to send task status change notification email to ' . $email . ' for task #' . $id);
                    }
                }
            }
        }
        
        return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true]));
    }

    // POST /tasks/bulk-update-status
    public function bulk_update_status()
    {
        if ($this->input->method() !== 'post') show_404();
        
        $task_ids = $this->input->post('task_ids');
        $status = trim($this->input->post('status'));
        
        // Validate input
        if (empty($task_ids) || !is_array($task_ids) || !in_array($status, ['pending','in_progress','completed','blocked'])) {
            return $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'Invalid input']));
        }
        
        // Filter and validate task IDs
        $valid_ids = array_filter($task_ids, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (empty($valid_ids)) {
            return $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'No valid task IDs']));
        }
        
        // Check permissions for each task
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        
        if (!$is_admin && $user_id) {
            // For non-admin users, verify they can update these tasks
            $tasks = $this->db->where_in('id', $valid_ids)->get('tasks')->result();
            $valid_ids = array_filter($valid_ids, function($id) use ($tasks, $user_id) {
                foreach ($tasks as $task) {
                    if ($task->id == $id) {
                        return (int)$task->assigned_to === $user_id || (isset($task->created_by) && (int)$task->created_by === $user_id);
                    }
                }
                return false;
            });
        }
        
        if (empty($valid_ids)) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'Permission denied']));
        }
        
        // Perform bulk update
        $this->db->where_in('id', $valid_ids)->update('tasks', ['status' => $status]);
        $updated_count = $this->db->affected_rows();
        
        // Log activity for each task
        $this->load->helper('activity');
        foreach ($valid_ids as $id) {
            log_activity('tasks', 'bulk_status_changed', (int)$id, 'Bulk Status: '.$status);
        }
        
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'ok' => true, 
            'updated' => $updated_count,
            'message' => "Updated {$updated_count} tasks to {$status}"
        ]));
    }

    // GET/POST /tasks/import
    public function import()
    {
        require_module_access(['tasks_import', 'tasks'], true);

        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            $this->load->helper('csv_import');
            $opened = csv_import_open('file');
            if (!$opened['ok']) {
                $this->session->set_flashdata('error', $opened['error']);
                redirect('tasks/import');
                return;
            }
            $columns = csv_import_require_columns($opened['map'], array('title'), array(array('project_name', 'project', 'project_id')));
            if (!$columns['ok']) {
                fclose($opened['handle']);
                $this->session->set_flashdata('error', $columns['error']);
                redirect('tasks/import');
                return;
            }
            $inserted = 0;
            $skipped = 0;
            $row_errors = array();
            $project_cache = array();
            $line = 1;
            $allowed_status = array('pending', 'in_progress', 'completed', 'blocked');
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            while (($row = fgetcsv($opened['handle'])) !== false) {
                $line++;
                $title = csv_import_get($opened['map'], $row, 'title');
                if ($title === '') {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Missing task title.');
                    continue;
                }
                $project_id = csv_import_resolve_project_id($this->db, $opened['map'], $row, $project_cache);
                if ($project_id <= 0) {
                    $skipped++;
                    csv_import_add_row_error($row_errors, $line, 'Unknown project name or code.');
                    continue;
                }
                $status = csv_import_validate_enum(
                    csv_import_get($opened['map'], $row, 'status', 'pending'),
                    $allowed_status,
                    'pending',
                    $row_errors,
                    $line,
                    'status'
                );
                if ($status === false) {
                    $skipped++;
                    continue;
                }
                $assigned_to = null;
                $assignee_raw = csv_import_get($opened['map'], $row, 'assigned_to', '');
                if ($assignee_raw !== '') {
                    $assigned_to = (int) $assignee_raw;
                }
                $data = array(
                    'project_id' => $project_id,
                    'title' => $title,
                    'description' => csv_import_get($opened['map'], $row, 'description', null) ?: null,
                    'assigned_to' => $assigned_to,
                    'status' => $status,
                    'created_by' => $user_id,
                );
                if ($this->db->insert('tasks', $data)) {
                    $inserted++;
                } else {
                    $skipped++;
                    $db_error = $this->db->error();
                    $reason = !empty($db_error['message']) ? $db_error['message'] : 'Database insert failed.';
                    csv_import_add_row_error($row_errors, $line, $reason);
                    log_message('error', 'Task import error: ' . $reason);
                }
            }
            $this->db->db_debug = $prev_debug;
            fclose($opened['handle']);
            csv_import_finish($inserted, $skipped, $row_errors, 'tasks', 'tasks', 'tasks/import');
            return;
        }
        $this->load->view('tasks/import');
    }

    // POST /tasks/send-daily-summary
    public function send_daily_summary()
    {
        if ($this->input->method() !== 'post') show_404();

        $user_id = (int)$this->input->post('user_id');
        if (!$user_id) {
            return $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'User ID required']));
        }

        // Non-admins may only trigger their own summary (prevents task/PII leak by user_id).
        $session_user_id = (int)$this->session->userdata('user_id');
        $is_admin = ((int)$this->session->userdata('role_id') === 1) || (function_exists('is_admin_group') && is_admin_group());
        if (!$is_admin && $user_id !== $session_user_id) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'You can only send your own summary']));
        }
        
        // Get user's tasks ordered by priority
        $tasks = get_user_tasks_by_priority($user_id);
        
        if (empty($tasks)) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'message'=>'No active tasks found']));
        }
        
        // Get user email
        $email = get_user_email_by_id($user_id);
        if (!$email) {
            return $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'User email not found']));
        }
        
        // Send email with priority-ordered tasks
        $subject = 'Your Daily Task Summary - ' . count($tasks) . ' Tasks';
        $sent = send_multiple_tasks_notification($email, $subject, $tasks, 'daily_summary');
        
        if ($sent) {
            log_message('info', 'Daily task summary sent to user ' . $user_id . ' (' . $email . ')');
            return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'message'=>'Daily summary sent successfully']));
        } else {
            log_message('error', 'Failed to send daily task summary to user ' . $user_id . ' (' . $email . ')');
            return $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'Failed to send email']));
        }
    }

    // GET /tasks/send-all-summaries (Admin only)
    public function send_all_summaries()
    {
        // Check admin permission
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        
        if (!$is_admin) {
            show_error('Admin access required', 403);
        }
        
        // Get all users with active tasks
        $this->db->select('DISTINCT t.assigned_to, u.email');
        $this->db->from('tasks t');
        $this->db->join('users u', 'u.id = t.assigned_to', 'inner');
        $this->db->where('t.status !=', 'completed');
        $this->db->where('t.assigned_to IS NOT NULL');
        $users = $this->db->get()->result();
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($users as $user) {
            $tasks = get_user_tasks_by_priority($user->assigned_to);
            
            if (!empty($tasks) && $user->email) {
                $subject = 'Your Daily Task Summary - ' . count($tasks) . ' Tasks';
                $sent = send_multiple_tasks_notification($user->email, $subject, $tasks, 'daily_summary');
                
                if ($sent) {
                    $sent_count++;
                    log_message('info', 'Daily summary sent to user ' . $user->assigned_to . ' (' . $user->email . ')');
                } else {
                    $failed_count++;
                    log_message('error', 'Failed to send daily summary to user ' . $user->assigned_to . ' (' . $user->email . ')');
                }
            }
        }
        
        $this->session->set_flashdata('success', "Daily summaries sent: $sent_count successful, $failed_count failed");
        redirect('tasks');
    }

    // POST /tasks/{task_id}/comment
    public function add_comment($task_id)
    {
        $task_id = (int)$task_id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { redirect('login'); return; }
        if ($this->input->method() !== 'post') { show_404(); }

        $task = $this->db->where('id', $task_id)->get('tasks')->row();
        if (!$task) { show_404(); }
        
        $comment = trim((string)$this->input->post('comment'));
        if ($comment === '') {
            $this->session->set_flashdata('error', 'Comment cannot be empty.');
            redirect('tasks/'.$task_id);
            return;
        }
        $this->Task_model->add_comment($task_id, $user_id, $comment);
        $this->load->helper('activity');
        log_activity('tasks', 'commented', (int)$task_id, mb_substr($comment, 0, 120));

        // Notify assignee if exists and not self
        if (isset($task->assigned_to) && (int)$task->assigned_to > 0 && (int)$task->assigned_to !== $user_id) {
            $this->load->model('Notification_model');
            $this->Notification_model->create(
                (int)$task->assigned_to,
                'New comment on task #' . $task_id,
                mb_substr($comment, 0, 200),
                'info',
                'tasks',
                (int)$task_id,
                site_url('tasks/' . $task_id)
            );
        }

        $this->session->set_flashdata('success', 'Comment added.');
        redirect('tasks/'.$task_id);
    }

    // GET /tasks/{task_id}/comments (AJAX JSON)
    public function get_comments($task_id)
    {
        $task_id = (int)$task_id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'unauthorized'])); return; }
        $task = $this->db->where('id', $task_id)->get('tasks')->row();
        if (!$task) { $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'not_found'])); return; }
        $rows = $this->Task_model->get_task_comments($task_id);
        $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'comments'=>$rows]));
    }

    // POST /tasks/comment/{comment_id}/delete or GET mapped route
    public function delete_comment($comment_id)
    {
        $comment_id = (int)$comment_id;
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        if (!$user_id) { redirect('login'); return; }

        // If admin, allow delete unconditionally
        if ($role_id === 1) {
            $this->db->where('id', $comment_id)->delete('task_comments');
            $this->session->set_flashdata('success', 'Comment deleted.');
            // Try to redirect back to task if known
            $ref = $this->input->get('ref');
            if ($ref) { redirect($ref); return; }
            redirect('tasks');
            return;
        }

        // Owner-only delete
        $ok = $this->Task_model->delete_comment($comment_id, $user_id);
        if ($ok) { $this->session->set_flashdata('success', 'Comment deleted.'); }
        else { $this->session->set_flashdata('error', 'Cannot delete this comment.'); }
        $ref = $this->input->get('ref');
        if ($ref) { redirect($ref); return; }
        redirect('tasks');
    }

    // GET /tasks/get_by_project/{project_id}
    public function get_by_project($project_id) {
        $project_id = (int)$project_id;
        $this->output->set_content_type('application/json');
        
        if (!$project_id) {
            $this->output->set_output(json_encode([]));
            return;
        }

        $tasks = $this->db->select('id, title')
                          ->from('tasks')
                          ->where('project_id', $project_id)
                          ->order_by('id', 'DESC');
        apply_role_hierarchy_filter($this->db, 'created_by');
        $tasks = $this->db->get()->result();
                          
        $this->output->set_output(json_encode($tasks));
    }

    private function _ensure_reference_url_column()
    {
        if (!$this->db->table_exists('tasks')) {
            return;
        }
        $fields = $this->db->list_fields('tasks');
        if (!in_array('reference_url', $fields, true)) {
            $this->db->query("ALTER TABLE `tasks` ADD `reference_url` VARCHAR(500) NULL DEFAULT NULL");
        }
    }
}
