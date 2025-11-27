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
                `publish_at` datetime DEFAULT NULL,
                `expire_at` datetime DEFAULT NULL,
                `is_recurring` tinyint(1) DEFAULT 0,
                `recurrence_pattern` varchar(50) DEFAULT NULL,
                `recurrence_end` date DEFAULT NULL,
                `email_template` text DEFAULT NULL,
                `auto_send` tinyint(1) DEFAULT 0,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_publish_at` (`publish_at`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        
        // Add new columns if they don't exist
        if ($this->db->table_exists('announcements')) {
            $fields = $this->db->list_fields('announcements');
            
            if (!in_array('publish_at', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `publish_at` datetime DEFAULT NULL AFTER `end_date`");
            }
            if (!in_array('expire_at', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `expire_at` datetime DEFAULT NULL AFTER `publish_at`");
            }
            if (!in_array('is_recurring', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `is_recurring` tinyint(1) DEFAULT 0 AFTER `expire_at`");
            }
            if (!in_array('recurrence_pattern', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `recurrence_pattern` varchar(50) DEFAULT NULL AFTER `is_recurring`");
            }
            if (!in_array('recurrence_end', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `recurrence_end` date DEFAULT NULL AFTER `recurrence_pattern`");
            }
            if (!in_array('email_template', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `email_template` text DEFAULT NULL AFTER `recurrence_end`");
            }
            if (!in_array('auto_send', $fields)) {
                $this->db->query("ALTER TABLE `announcements` ADD COLUMN `auto_send` tinyint(1) DEFAULT 0 AFTER `email_template`");
            }
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
            $publish_at = $this->input->post('publish_at');
            $status = trim((string)$this->input->post('status') ?: 'draft');
            
            // If publish_at is set and in future, set status to scheduled
            if ($publish_at && $publish_at > date('Y-m-d H:i:s')) {
                $status = 'scheduled';
            }
            
            $data = [
                'title' => trim((string)$this->input->post('title')),
                'content' => (string)$this->input->post('content'),
                'posted_by' => (int)$this->session->userdata('user_id'),
                'target_roles' => trim((string)$this->input->post('target_roles') ?: 'all'),
                'priority' => trim((string)$this->input->post('priority') ?: 'medium'),
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
                'publish_at' => $publish_at ?: null,
                'expire_at' => $this->input->post('expire_at') ?: null,
                'is_recurring' => (int)$this->input->post('is_recurring') ?: 0,
                'recurrence_pattern' => $this->input->post('recurrence_pattern') ?: null,
                'recurrence_end' => $this->input->post('recurrence_end') ?: null,
                'email_template' => $this->input->post('email_template') ?: null,
                'auto_send' => (int)$this->input->post('auto_send') ?: 0,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $id = $this->ann->create($data);
            
            // Broadcast immediately if status is published
            if ($status === 'published') {
                $this->broadcast_if_published($data);
            }
            
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
            $publish_at = $this->input->post('publish_at');
            $status = trim((string)$this->input->post('status') ?: 'draft');
            
            // If publish_at is set and in future, set status to scheduled
            if ($publish_at && $publish_at > date('Y-m-d H:i:s')) {
                $status = 'scheduled';
            }
            
            $data = [
                'title' => trim((string)$this->input->post('title')),
                'content' => (string)$this->input->post('content'),
                'target_roles' => trim((string)$this->input->post('target_roles') ?: 'all'),
                'priority' => trim((string)$this->input->post('priority') ?: 'medium'),
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
                'publish_at' => $publish_at ?: null,
                'expire_at' => $this->input->post('expire_at') ?: null,
                'is_recurring' => (int)$this->input->post('is_recurring') ?: 0,
                'recurrence_pattern' => $this->input->post('recurrence_pattern') ?: null,
                'recurrence_end' => $this->input->post('recurrence_end') ?: null,
                'email_template' => $this->input->post('email_template') ?: null,
                'auto_send' => (int)$this->input->post('auto_send') ?: 0,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->ann->update((int)$id, $data);
            
            // Broadcast if transitioning to published
            if ($row->status !== 'published' && $status === 'published') {
                $this->broadcast_if_published($data);
            }
            
            $this->session->set_flashdata('success', 'Announcement updated');
            redirect('announcements'); return;
        }
        $this->load->view('announcements/form', ['action'=>'edit', 'row'=>$row]);
    }

    // GET /announcements/scheduled - Process scheduled announcements
    public function process_scheduled() {
        // This method can be called by cron job or scheduler
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        
        // Process scheduled announcements to publish
        $this->db->where('status', 'scheduled')
                 ->where('publish_at <=', $now)
                 ->update('announcements', ['status' => 'published', 'updated_at' => $now]);
        
        // Get newly published announcements for broadcasting
        $scheduled_to_publish = $this->db->where('status', 'published')
                                       ->where('publish_at <=', $now)
                                       ->where('publish_at >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
                                       ->get('announcements')
                                       ->result();
        
        foreach ($scheduled_to_publish as $announcement) {
            $this->broadcast_if_published([
                'title' => $announcement->title,
                'content' => $announcement->content,
                'target_roles' => $announcement->target_roles,
                'status' => 'published'
            ]);
        }
        
        // Process expired announcements
        $this->db->where('status', 'published')
                 ->where('expire_at <=', $now)
                 ->update('announcements', ['status' => 'expired', 'updated_at' => $now]);
        
        // Process recurring announcements
        $this->process_recurring_announcements();
        
        echo "Scheduled announcements processed successfully";
    }
    
    private function process_recurring_announcements() {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        
        // Get active recurring announcements
        $recurring = $this->db->where('is_recurring', 1)
                            ->where('status', 'published')
                            ->where('recurrence_end >=', $today)
                            ->get('announcements')
                            ->result();
        
        foreach ($recurring as $announcement) {
            if ($this->should_create_recurrence_instance($announcement)) {
                // Create new instance of recurring announcement
                $new_instance = [
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'posted_by' => $announcement->posted_by,
                    'target_roles' => $announcement->target_roles,
                    'priority' => $announcement->priority,
                    'status' => 'published',
                    'start_date' => $today,
                    'end_date' => $today,
                    'publish_at' => $now,
                    'auto_send' => $announcement->auto_send,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                
                $this->db->insert('announcements', $new_instance);
                
                // Send email if auto_send is enabled
                if ($announcement->auto_send) {
                    $this->broadcast_if_published($new_instance);
                }
            }
        }
    }
    
    private function should_create_recurrence_instance($announcement) {
        $pattern = $announcement->recurrence_pattern;
        $today = date('Y-m-d');
        $last_created = $this->get_last_recurrence_date($announcement->id);
        
        if (!$last_created) {
            $last_created = $announcement->created_at ? date('Y-m-d', strtotime($announcement->created_at)) : $today;
        }
        
        switch ($pattern) {
            case 'daily':
                return date('Y-m-d', strtotime($last_created . ' +1 day')) <= $today;
            case 'weekly':
                return date('Y-m-d', strtotime($last_created . ' +1 week')) <= $today;
            case 'monthly':
                return date('Y-m-d', strtotime($last_created . ' +1 month')) <= $today;
            case 'quarterly':
                return date('Y-m-d', strtotime($last_created . ' +3 months')) <= $today;
            default:
                return false;
        }
    }
    
    private function get_last_recurrence_date($announcement_id) {
        $last = $this->db->where('posted_by', $announcement_id)
                         ->where('title LIKE', '%(Recurring)%')
                         ->order_by('created_at', 'DESC')
                         ->limit(1)
                         ->get('announcements')
                         ->row();
        return $last ? date('Y-m-d', strtotime($last->created_at)) : null;
    }
    
    // POST /announcements/{id}/delete
    public function delete($id){
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        $this->ann->delete((int)$id);
        $this->session->set_flashdata('success', 'Announcement deleted');
        redirect('announcements');
    }
    
    // GET /announcements/templates - Email template management
    public function templates() {
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        
        $templates = $this->reminders->get_template('announcement');
        $this->load->view('announcements/templates', ['templates' => $templates]);
    }
    
    // POST /announcements/templates - Save email template
    public function save_template() {
        if (!$this->can_manage()) { show_error('Forbidden', 403); }
        
        $subject = $this->input->post('subject');
        $body = $this->input->post('body');
        
        $this->reminders->save_template('announcement', $subject, $body);
        $this->session->set_flashdata('success', 'Email template saved');
        redirect('announcements/templates');
    }
}
