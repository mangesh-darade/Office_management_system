<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Task_model');
    }

    public function index() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = ($role_id === 1);

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
        if (!$is_admin && $user_id) {
            $this->db->where('t.assigned_to', $user_id);
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
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            $requirement_id = $this->input->post('requirement_id') !== '' ? (int)$this->input->post('requirement_id') : null;
            $data = [
                'project_id' => (int)($this->input->post('project_id') ?: 0),
                'title' => trim($this->input->post('title')),
                // Store HTML from editor as-is; display will sanitize allowed tags
                'description' => $this->input->post('description'),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status' => $this->input->post('status') ?: 'pending',
                'created_by' => $user_id,
            ];
            // If a requirement is selected, override the title with the requirement's title
            if ($requirement_id) {
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
            $this->load->helper('activity');
            log_activity('tasks', 'created', (int)$id, 'Task: '.(string)$data['title']);
            // Auto reminder to assignee if set
            if (isset($data['assigned_to']) && !empty($data['assigned_to'])){
                $assignee_id = (int)$data['assigned_to'];
                $email = '';
                if ($this->db->table_exists('users')){
                    $row = $this->db->select('email')->from('users')->where('id', $assignee_id)->get()->row();
                    if ($row && isset($row->email)) { $email = $row->email; }
                }
                if ($email !== ''){
                    $this->load->model('Reminder_model','reminders');
                    $this->reminders->ensure_schema();
                    $subject = 'Task assigned: '.(string)$data['title'];
                    $body = 'You have been assigned a task: '.(string)$data['title'].'\n\nOpen: '.site_url('tasks/'.$id);
                    $this->reminders->enqueue([
                        'user_id' => $assignee_id,
                        'email' => $email,
                        'type' => 'task_assigned',
                        'subject' => $subject,
                        'body' => $body,
                        'send_at' => date('Y-m-d H:i:00')
                    ]);
                }
            }
            $this->session->set_flashdata('success', 'Task created');
            redirect('tasks/'.$id);
            return;
        }
        // GET: load projects, requirements, and users for dropdowns
        $projects = $this->db->order_by('name','ASC')->get('projects')->result();
        $requirements = [];
        if ($this->db->table_exists('requirements')) {
            $this->db->select('id, project_id, title')->from('requirements');
            $this->db->order_by('title','ASC');
            $requirements = $this->db->get()->result();
        }
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
        $this->load->view('tasks/form', ['action' => 'create', 'projects' => $projects, 'users' => $users, 'requirements' => $requirements]);
    }

    // GET /tasks/{id}
    public function show($id)
    {
        $task = $this->db->where('id', (int)$id)->get('tasks')->row();
        if (!$task) show_404();
        $this->load->view('tasks/view', ['task' => $task]);
    }

    // GET /tasks/{id}/edit, POST /tasks/{id}/edit
    public function edit($id)
    {
        $task = $this->db->where('id', (int)$id)->get('tasks')->row();
        if (!$task) show_404();
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            $data = [
                'project_id' => (int)($this->input->post('project_id') ?: 0),
                'title' => trim($this->input->post('title')),
                // Store HTML from editor
                'description' => $this->input->post('description'),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status' => $this->input->post('status') ?: 'pending',
                'updated_by' => $user_id,
            ];
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
            $this->db->where('id', (int)$id)->update('tasks', $data);
            $this->load->helper('activity');
            log_activity('tasks', 'updated', (int)$id, 'Task: '.(string)$data['title']);
            $this->session->set_flashdata('success', 'Task updated');
            redirect('tasks/'.$id);
            return;
        }
        // GET: load projects and users for dropdowns
        $projects = $this->db->order_by('name','ASC')->get('projects')->result();
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
        $this->load->view('tasks/form', ['action' => 'edit', 'task' => $task, 'projects' => $projects, 'users' => $users]);
    }

    // POST /tasks/{id}/delete
    public function delete($id)
    {
        $this->db->where('id', (int)$id)->delete('tasks');
        $this->load->helper('activity');
        log_activity('tasks', 'deleted', (int)$id, 'Task deleted');
        $this->session->set_flashdata('success', 'Task deleted');
        redirect('tasks');
    }

    // GET /tasks/board
    public function board()
    {
        $statuses = ['pending','in_progress','completed','blocked'];
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
            $this->db->where('t.status', $st)->order_by('t.id','DESC');
            $columns[$st] = $this->db->get()->result();
        }
        $this->load->view('tasks/board', ['columns' => $columns]);
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
        $this->db->where('id',$id)->update('tasks',['status'=>$status]);
        $this->load->helper('activity');
        log_activity('tasks', 'status_changed', (int)$id, 'Status: '.$status);
        return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true]));
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
