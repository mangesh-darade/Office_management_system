<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Announcements extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Announcement_model','ann');
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_schema();
        // Load reminders for broadcasting when publishing
        $this->load->model('Reminder_model','reminders');
        if (method_exists($this->reminders, 'ensure_schema')) { $this->reminders->ensure_schema(); }
    }

    private function ensure_schema(){
        if (!$this->db->table_exists('announcements')){
            $sql = "CREATE TABLE `announcements` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `content` text NOT NULL,
                `posted_by` int(11) NOT NULL,
                `target_roles` varchar(100) DEFAULT 'all',
                `priority` varchar(20) DEFAULT 'medium',
                `start_date` date DEFAULT NULL,
                `end_date` date DEFAULT NULL,
                `status` varchar(20) DEFAULT 'draft',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    private function can_manage(){
        $role_id = (int)$this->session->userdata('role_id');
        return in_array($role_id, [1,2], true); // Admin/Manager
    }

    private function broadcast_if_published($data){
        if (!isset($data['status']) || $data['status'] !== 'published') { return; }
        $subject = isset($data['title']) ? $data['title'] : 'Announcement';
        $body = isset($data['content']) ? $data['content'] : $subject;
        $target = isset($data['target_roles']) ? trim((string)$data['target_roles']) : 'all';
        // Determine recipients
        if ($target === 'all' || $target === '' ){
            $users = $this->reminders->all_users();
            foreach ($users as $u){
                $to = isset($u->email)?$u->email:''; if ($to==='') { continue; }
                $this->reminders->enqueue([
                    'user_id' => isset($u->id)?(int)$u->id:null,
                    'email' => $to,
                    'type' => 'announcement',
                    'subject' => $subject,
                    'body' => $body,
                    'send_at' => date('Y-m-d H:i:00')
                ]);
            }
            return;
        }
        // Otherwise treat as CSV of allowed role IDs
        $roles = array();
        $parts = explode(',', $target);
        foreach ($parts as $p){ $p = trim($p); if ($p !== '' && ctype_digit($p)) { $roles[] = (int)$p; } }
        if (empty($roles)) { return; }
        if ($this->db->table_exists('users')){
            $this->db->select('id, email')->from('users')->where_in('role_id', $roles);
            $users = $this->db->get()->result();
            foreach ($users as $u){
                $to = isset($u->email)?$u->email:''; if ($to==='') { continue; }
                $this->reminders->enqueue([
                    'user_id' => isset($u->id)?(int)$u->id:null,
                    'email' => $to,
                    'type' => 'announcement',
                    'subject' => $subject,
                    'body' => $body,
                    'send_at' => date('Y-m-d H:i:00')
                ]);
            }
        }
    }

    // GET /announcements
    public function index(){
        $filters = [
            'status' => trim((string)$this->input->get('status')),
            'q' => trim((string)$this->input->get('q')),
        ];
        $rows = $this->ann->get_all_announcements($filters);
        $this->load->view('announcements/index', [ 'rows'=>$rows, 'filters'=>$filters, 'can_manage'=>$this->can_manage() ]);
    }

    // GET|POST /announcements/create
    public function create(){
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        if ($this->input->method() === 'post'){
            $data = [
                'title' => trim((string)$this->input->post('title')),
                'content' => (string)$this->input->post('content'),
                'posted_by' => (int)$this->session->userdata('user_id'),
                'target_roles' => trim((string)$this->input->post('target_roles') ?: 'all'),
                'priority' => trim((string)$this->input->post('priority') ?: 'medium'),
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
                'status' => trim((string)$this->input->post('status') ?: 'draft'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $id = $this->ann->create($data);
            $this->broadcast_if_published($data);
            $this->session->set_flashdata('success', 'Announcement created');
            redirect('announcements'); return;
        }
        $this->load->view('announcements/form', ['action'=>'create']);
    }

    // GET|POST /announcements/{id}/edit
    public function edit($id){
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        $row = $this->db->get_where('announcements', ['id'=>(int)$id])->row();
        if (!$row) { show_404(); }
        if ($this->input->method() === 'post'){
            $data = [
                'title' => trim((string)$this->input->post('title')),
                'content' => (string)$this->input->post('content'),
                'target_roles' => trim((string)$this->input->post('target_roles') ?: 'all'),
                'priority' => trim((string)$this->input->post('priority') ?: 'medium'),
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
                'status' => trim((string)$this->input->post('status') ?: 'draft'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->ann->update((int)$id, $data);
            // Broadcast if transitioning to published
            if ($row->status !== 'published' && isset($data['status']) && $data['status'] === 'published'){
                $this->broadcast_if_published($data);
            }
            $this->session->set_flashdata('success', 'Announcement updated');
            redirect('announcements'); return;
        }
        $this->load->view('announcements/form', ['action'=>'edit', 'row'=>$row]);
    }

    // POST /announcements/{id}/delete
    public function delete($id){
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        $this->ann->delete((int)$id);
        $this->session->set_flashdata('success', 'Announcement deleted');
        redirect('announcements');
    }
}
