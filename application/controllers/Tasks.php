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
        $tasks = $this->Task_model->all();
        $this->load->view('tasks/list', ['tasks' => $tasks]);
    }

    // GET /tasks/create, POST /tasks/create
    public function create()
    {
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }
            $data = [
                'project_id' => (int)($this->input->post('project_id') ?: 0),
                'title' => trim($this->input->post('title')),
                // Store HTML from editor as-is; display will sanitize allowed tags
                'description' => $this->input->post('description'),
                'assigned_to' => $this->input->post('assigned_to') !== '' ? (int)$this->input->post('assigned_to') : null,
                'status' => $this->input->post('status') ?: 'pending',
                'created_by' => $user_id,
            ];
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
            $this->session->set_flashdata('success', 'Task created');
            redirect('tasks/'.$id);
            return;
        }
        // GET: load projects and users for dropdowns
        $projects = $this->db->order_by('name','ASC')->get('projects')->result();
        // Prefer employee name when available
        if ($this->db->table_exists('employees') && $this->db->field_exists('user_id','employees')) {
            $select = ['users.id','users.email'];
            if ($this->db->field_exists('name','users')) { $select[] = 'users.name'; }
            if ($this->db->field_exists('full_name','users')) { $select[] = 'users.full_name'; }
            if ($this->db->field_exists('name','employees')) { $select[] = 'employees.name AS emp_name'; }
            $this->db->select(implode(',', $select))
                     ->from('users')
                     ->join('employees','employees.user_id = users.id','left');
            if ($this->db->field_exists('name','employees')) {
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
        $this->load->view('tasks/form', ['action' => 'create', 'projects' => $projects, 'users' => $users]);
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
            if ($this->db->field_exists('name','employees')) { $select[] = 'employees.name AS emp_name'; }
            $this->db->select(implode(',', $select))
                     ->from('users')
                     ->join('employees','employees.user_id = users.id','left')
                     ->order_by('employees.name IS NULL ASC', '', false)
                     ->order_by('employees.name','ASC')
                     ->order_by('users.email','ASC');
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
        $this->session->set_flashdata('success', 'Task deleted');
        redirect('tasks');
    }

    // GET /tasks/board
    public function board()
    {
        $statuses = ['pending','in_progress','completed','blocked'];
        $columns = [];
        foreach ($statuses as $st) {
            $columns[$st] = $this->db->where('status', $st)->order_by('id','DESC')->get('tasks')->result();
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
}
