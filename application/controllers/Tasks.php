<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','group_filter','email_settings']);
        $this->load->library(['session']);
        $this->load->model('Task_model');
        // Schema changes moved to migrations - run: php index.php migrate
    }

    /**
     * Ensure schema - DEPRECATED: Use migrations instead
     * This method is kept for backward compatibility but should not be used
     * Run migrations using: php index.php migrate
     */
    private function ensure_schema(){
        // Schema changes have been moved to migrations
        // See: application/migrations/001_Add_task_schema_fields.php
        // Run migrations using: php index.php migrate
        log_message('debug', 'Tasks::ensure_schema() called - consider using migrations instead');
    }

    public function index() {
        // Check list permission specifically
        if (!function_exists('has_module_access') || !has_module_access('tasks_list')) {
            show_error('You do not have permission to view tasks.', 403);
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        
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
            if ($this->db->field_exists('name','projects')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if ($this->db->field_exists('full_name','users')) { $select[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            if ($this->db->field_exists('name','employees')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        $this->db->select(implode(',', $select));
        
        // Apply group-based filtering
        if (!$is_admin && $user_id) {
            if (can_view_group_data($role_id)) {
                // Managers can see team tasks
                if (!empty($filters['tasks'])) {
                    apply_group_filter_to_query($this->db, 'tasks', $filters);
                }
            } else {
                // Regular users see only their own tasks
                $this->db->where('t.assigned_to', $user_id);
            }
        }
        
        // Apply filters
        if ($project_filter !== '') { $this->db->where('t.project_id', (int)$project_filter); }
        if ($is_admin && $assignee_filter !== '') { $this->db->where('t.assigned_to', (int)$assignee_filter); }
        if ($status_filter !== '') { $this->db->where('t.status', $status_filter); }
        if ($priority_filter !== '' && $this->db->field_exists('priority','tasks')) { $this->db->where('t.priority', $priority_filter); }
        $this->db->order_by('t.id','DESC');
        $tasks = $this->db->get()->result();

        // Dropdown data
        $projects = [];
        if ($this->db->table_exists('projects')) {
            $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        }
        $assignees = [];
        if ($is_admin) {
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                $sel = ['users.id','users.email'];
                $hasEmpName3 = $this->db->field_exists('name','employees');
                if ($hasEmpName3) { $sel[] = 'employees.name AS emp_name'; }
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'users.full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'users.name'; }
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
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
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
        if (!function_exists('has_module_access') || !has_module_access('tasks_add')) {
            show_error('You do not have permission to add tasks.', 403);
        }
        
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
            
            $data = [
                'project_id' => $project_id,
                'project_ids' => $project_ids_json,
                'requirement_id' => $requirement_id,
                'title' => trim($this->input->post('title')),
                // Store HTML from editor as-is; display will sanitize allowed tags
                'description' => $this->input->post('description'),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status' => $this->input->post('status') ?: 'pending',
                'priority' => $this->input->post('priority') ?: 'medium',
                'start_date' => $this->input->post('start_date') ?: null,
                'due_date' => $this->input->post('due_date') ?: null,
                'created_by' => $user_id,
            ];
            // If a requirement is selected, override the title with the requirement's title if title is empty
            if ($requirement_id && empty($data['title'])) {
                $reqTitleRow = $this->db->select('title')->from('requirements')->where('id', (int)$requirement_id)->get()->row();
                if ($reqTitleRow && isset($reqTitleRow->title) && trim((string)$reqTitleRow->title) !== '') {
                    $data['title'] = (string)$reqTitleRow->title;
                }
            }
            // Optional attachment
            if ($this->db->field_exists('attachment_path', 'tasks') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/tasks/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
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
            $this->load->helper('notification');
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
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            $select = ['users.id','users.email'];
            if ($this->db->field_exists('name','users')) { $select[] = 'users.name'; }
            if ($this->db->field_exists('full_name','users')) { $select[] = 'users.full_name'; }
            $hasEmpName = $this->db->field_exists('name','employees');
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
            if ($this->db->field_exists('full_name','users')) { $userSelect[] = 'full_name'; }
            if ($this->db->field_exists('name','users')) { $userSelect[] = 'name'; }
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
        // Debug: Log the request
        error_log("Preview method called with ID: " . $id);
        
        $this->db->from('tasks t');
        $select = ['t.*'];
        
        // Join projects for name if available
        if ($this->db->table_exists('projects')) {
            if ($this->db->field_exists('name','projects')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        
        // Join users for assignee info
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if ($this->db->field_exists('full_name','users')) { $select[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        
        // Join employees for assignee employee name
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            if ($this->db->field_exists('name','employees')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        
        // Join users for creator info
        if ($this->db->table_exists('users')) {
            $select[] = 'cu.email AS creator_email';
            if ($this->db->field_exists('full_name','users')) { $select[] = 'cu.full_name AS creator_full_name'; }
            if ($this->db->field_exists('name','users')) { $select[] = 'cu.name AS creator_name'; }
            $this->db->join('users cu', 'cu.id = t.created_by', 'left');
        }
        
        // Join requirements if requirement_id exists
        if ($this->db->table_exists('requirements') && $this->db->field_exists('requirement_id', 'tasks')) {
            $select[] = 'r.id AS requirement_id';
            $select[] = 'r.req_number AS requirement_number';
            $select[] = 'r.title AS requirement_title';
            $select[] = 'r.status AS requirement_status';
            $this->db->join('requirements r', 'r.id = t.requirement_id', 'left');
        }
        
        $this->db->select(implode(',', $select));
        $this->db->where('t.id', (int)$id);
        $task = $this->db->get()->row();
        
        error_log("Task query result: " . ($task ? "Found task" : "Task not found"));
        
        if (!$task) {
            error_log("Task not found, returning 404");
            return $this->output->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => 'Task not found']));
        }
        
        // Visibility: non-admin group can only preview tasks assigned to them (or created_by them when available)
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        error_log("User permissions - ID: $user_id, Role: $role_id, Admin: " . ($is_admin ? 'Yes' : 'No'));
        
        if ($user_id && !$is_admin) {
            $assigned = isset($task->assigned_to) ? (int)$task->assigned_to : 0;
            $creator = (isset($task->created_by) ? (int)$task->created_by : 0);
            error_log("Access check - Assigned: $assigned, Creator: $creator, User: $user_id");
            
            if ($assigned !== $user_id && $creator !== $user_id) { 
                error_log("Access denied");
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
        $html .= '<h4 class="mb-3">' . htmlspecialchars($task->title) . '</h4>';
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
            $html .= '<small class="text-muted">Project:</small><br><strong>' . htmlspecialchars($task->project_name) . '</strong>';
            $html .= '</div>';
        }
        
        // Requirement
        if (!empty($task->requirement_title)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Requirement:</small><br><strong>' . htmlspecialchars($task->requirement_title) . '</strong>';
            $html .= '</div>';
        }
        
        // Assignee
        $assignee = $assigneeName($task);
        if (!empty($assignee)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Assigned to:</small><br><strong>' . htmlspecialchars($assignee) . '</strong>';
            $html .= '</div>';
        }
        
        // Creator
        $creator = $creatorName($task);
        if (!empty($creator)) {
            $html .= '<div class="mb-3">';
            $html .= '<small class="text-muted">Created by:</small><br><strong>' . htmlspecialchars($creator) . '</strong>';
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
        
        error_log("Generated HTML length: " . strlen($html));
        
        return $this->output->set_content_type('text/html')->set_output($html);
    }

    // GET /tasks/{id}
    public function show($id)
    {
        $this->db->from('tasks t');
        $select = ['t.*'];
        
        // Join projects for name if available
        if ($this->db->table_exists('projects')) {
            if ($this->db->field_exists('name','projects')) { $select[] = 'p.name AS project_name'; }
            $this->db->join('projects p','p.id = t.project_id','left');
        }
        
        // Join users for assignee info
        if ($this->db->table_exists('users')) {
            $select[] = 'u.email AS assignee_email';
            if ($this->db->field_exists('full_name','users')) { $select[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')) { $select[] = 'u.name'; }
            $this->db->join('users u', 'u.id = t.assigned_to', 'left');
        }
        
        // Join employees for assignee employee name
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            if ($this->db->field_exists('name','employees')) { $select[] = 'e.name AS emp_name'; }
            $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
        }
        
        // Join users for creator info
        if ($this->db->table_exists('users')) {
            $select[] = 'cu.email AS creator_email';
            if ($this->db->field_exists('full_name','users')) { $select[] = 'cu.full_name AS creator_full_name'; }
            if ($this->db->field_exists('name','users')) { $select[] = 'cu.name AS creator_name'; }
            $this->db->join('users cu', 'cu.id = t.created_by', 'left');
        }
        
        // Join requirements if requirement_id exists
        if ($this->db->table_exists('requirements') && $this->db->field_exists('requirement_id', 'tasks')) {
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
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        if ($user_id && !$is_admin) {
            $assigned = isset($task->assigned_to) ? (int)$task->assigned_to : 0;
            $creator = (isset($task->created_by) ? (int)$task->created_by : 0);
            if ($assigned !== $user_id && $creator !== $user_id) { show_error('Forbidden', 403); }
        }
        
        $this->load->view('tasks/view', ['task' => $task]);
    }

    // GET /tasks/{id}/edit, POST /tasks/{id}/edit
    public function edit($id)
    {
        // Check edit permission specifically
        if (!function_exists('has_module_access') || !has_module_access('tasks_edit')) {
            show_error('You do not have permission to edit tasks.', 403);
        }
        
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
            
            $data = [
                'project_id' => $project_id,
                'project_ids' => $project_ids_json,
                'requirement_id' => $this->input->post('requirement_id') !== '' ? (int)$this->input->post('requirement_id') : null,
                'title' => trim($this->input->post('title')),
                // Store HTML from editor
                'description' => $this->input->post('description'),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status' => $this->input->post('status') ?: 'pending',
                'priority' => $this->input->post('priority') ?: 'medium',
                'start_date' => $this->input->post('start_date') ?: null,
                'due_date' => $this->input->post('due_date') ?: null,
            ];
            // Only set updated_by if the column exists in tasks table
            if ($this->db->field_exists('updated_by', 'tasks')) {
                $data['updated_by'] = $user_id;
            }
            // Optional new attachment
            if ($this->db->field_exists('attachment_path', 'tasks') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/tasks/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
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
                $this->load->helper('notification');
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
            
            $this->load->helper('notification');
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
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            $select = ['users.id','users.email'];
            if ($this->db->field_exists('name','users')) { $select[] = 'users.name'; }
            if ($this->db->field_exists('full_name','users')) { $select[] = 'users.full_name'; }
            $hasEmpName2 = $this->db->field_exists('name','employees');
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
            if ($this->db->field_exists('full_name','users')) { $userSelect[] = 'full_name'; }
            if ($this->db->field_exists('name','users')) { $userSelect[] = 'name'; }
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
        // Check delete permission specifically
        if (!function_exists('has_module_access') || !has_module_access('tasks_delete')) {
            show_error('You do not have permission to delete tasks.', 403);
        }
        
        // Ownership: non-admin group can only delete tasks assigned to them (or created_by them when available)
        $task = $this->db->where('id', (int)$id)->get('tasks')->row();
        if (!$task) { show_404(); }
        $currentUser = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = (function_exists('is_admin_group') && is_admin_group()) || $role_id === 1;
        if ($currentUser && !$is_admin) {
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
        
        $this->load->helper('notification');
        $success_msg = get_notification_message('tasks', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('tasks');
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
                if ($this->db->field_exists('name','projects')) { $select[] = 'p.name AS project_name'; }
                $this->db->join('projects p','p.id = t.project_id','left');
            }
            if ($this->db->table_exists('users')) {
                $select[] = 'u.email AS assignee_email';
                if ($this->db->field_exists('full_name','users')) { $select[] = 'u.full_name'; }
                if ($this->db->field_exists('name','users')) { $select[] = 'u.name'; }
                $this->db->join('users u', 'u.id = t.assigned_to', 'left');
            }
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                if ($this->db->field_exists('name','employees')) { $select[] = 'e.name AS emp_name'; }
                $this->db->join('employees e', 'e.user_id = t.assigned_to', 'left');
            }
            $this->db->select(implode(',', $select));
            
            // Apply base filters
            if (!$is_admin && $user_id) {
                $this->db->where('t.assigned_to', $user_id);
            }
            $this->db->where('t.status', $st);
            
            // Apply additional filters
            if ($project_filter !== '') { 
                $this->db->where('t.project_id', (int)$project_filter); 
            }
            if ($is_admin && $assignee_filter !== '') { 
                $this->db->where('t.assigned_to', (int)$assignee_filter); 
            }
            if ($priority_filter !== '' && $this->db->field_exists('priority','tasks')) { 
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
            if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
                $sel = ['users.id','users.email'];
                $hasEmpName3 = $this->db->field_exists('name','employees');
                if ($hasEmpName3) { $sel[] = 'employees.name AS emp_name'; }
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'users.full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'users.name'; }
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
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
                $assignees = $this->db->select(implode(',', $sel))->from('users')->order_by('email','ASC')->get()->result();
            }
        }
        
        $this->load->view('tasks/board', [
            'columns' => $columns,
            'projects' => $projects,
            'assignees' => $assignees,
            'filter_project_id' => $project_filter,
            'filter_assigned_to' => $assignee_filter,
            'filter_priority' => $priority_filter,
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
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->session->set_flashdata('error', 'Please upload a valid CSV file');
                redirect('tasks/import');
                return;
            }
            $handle = fopen($_FILES['file']['tmp_name'], 'r');
            if (!$handle) { $this->session->set_flashdata('error', 'Unable to read uploaded file'); redirect('tasks/import'); return; }
            $header = fgetcsv($handle);
            if (!$header) { fclose($handle); $this->session->set_flashdata('error', 'CSV is empty'); redirect('tasks/import'); return; }
            $map = []; foreach ($header as $i=>$c) { $map[strtolower(trim($c))] = $i; }
            $inserted = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = [
                    'project_id' => isset($map['project_id']) ? (int)(isset($row[$map['project_id']]) ? $row[$map['project_id']] : 0) : null,
                    'title' => isset($map['title']) && isset($row[$map['title']]) ? $row[$map['title']] : null,
                    'description' => isset($map['description']) && isset($row[$map['description']]) ? $row[$map['description']] : null,
                    'assigned_to' => isset($map['assigned_to']) ? (int)(isset($row[$map['assigned_to']]) ? $row[$map['assigned_to']] : 0) : null,
                    'status' => isset($map['status']) && isset($row[$map['status']]) ? $row[$map['status']] : 'pending',
                    'created_by' => $user_id,
                ];
                if (!empty($data['title'])) { $this->db->insert('tasks', $data); $inserted++; }
            }
            fclose($handle);
            $this->session->set_flashdata('success', "Imported $inserted tasks");
            redirect('tasks');
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
        if (isset($task->assigned_to) && (int)$task->assigned_to > 0 && (int)$task->assigned_to !== $user_id && $this->db->table_exists('notifications')) {
            $this->db->insert('notifications', [
                'user_id' => (int)$task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'New comment on task #'.$task_id,
                'body' => mb_substr($comment, 0, 200),
                'channel' => 'in_app',
                'created_at' => date('Y-m-d H:i:s')
            ]);
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
}
