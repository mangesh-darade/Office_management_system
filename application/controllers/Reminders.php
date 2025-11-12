<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reminders extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session','email']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        if (!function_exists('has_module_access') || !has_module_access('reminders')) { show_error('Access Denied', 403); }
        $this->load->model('Reminder_model','reminders');
        $this->reminders->ensure_schema();
    }

    // GET /reminders
    public function index(){
        $rows = $this->reminders->list_recent(100);
        $this->load->view('reminders/index', ['rows'=>$rows]);
    }

    // GET /reminders/cron/morning
    public function cron_morning(){
        // Queue morning login reminder for all users using template
        $tpl = $this->reminders->get_template('daily_morning');
        $defaultSubject = 'Good morning! Daily login reminder';
        $defaultBody = "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.";
        $tplSubject = $tpl && isset($tpl->subject) ? $tpl->subject : $defaultSubject;
        $tplBody = $tpl && isset($tpl->body) ? $tpl->body : $defaultBody;
        $users = $this->reminders->all_users();
        foreach ($users as $u){
            $to = isset($u->email) ? $u->email : '';
            if ($to === '') { continue; }
            $name = '';
            if (isset($u->full_label) && $u->full_label!=='') { $name = $u->full_label; }
            else if (isset($u->full_name) && $u->full_name!=='') { $name = $u->full_name; }
            else if (isset($u->name) && $u->name!=='') { $name = $u->name; }
            list($subj,$body) = $this->reminders->render_template($tplSubject, $tplBody, array('name'=>$name));
            $this->reminders->enqueue([
                'user_id' => isset($u->id)?(int)$u->id:null,
                'email' => $to,
                'type' => 'daily_morning',
                'subject' => $subj,
                'body' => $body,
                'send_at' => date('Y-m-d H:i:00')
            ]);
        }
        $this->session->set_flashdata('success','Morning reminders queued');
        redirect('reminders');
    }

    // GET /reminders/cron/night
    public function cron_night(){
        // Queue night logout reminder for all users using template
        $tpl = $this->reminders->get_template('daily_night');
        $defaultSubject = 'Good evening! Daily logout reminder';
        $defaultBody = "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.";
        $tplSubject = $tpl && isset($tpl->subject) ? $tpl->subject : $defaultSubject;
        $tplBody = $tpl && isset($tpl->body) ? $tpl->body : $defaultBody;
        $users = $this->reminders->all_users();
        foreach ($users as $u){
            $to = isset($u->email) ? $u->email : '';
            if ($to === '') { continue; }
            $name = '';
            if (isset($u->full_label) && $u->full_label!=='') { $name = $u->full_label; }
            else if (isset($u->full_name) && $u->full_name!=='') { $name = $u->full_name; }
            else if (isset($u->name) && $u->name!=='') { $name = $u->name; }
            list($subj,$body) = $this->reminders->render_template($tplSubject, $tplBody, array('name'=>$name));
            $this->reminders->enqueue([
                'user_id' => isset($u->id)?(int)$u->id:null,
                'email' => $to,
                'type' => 'daily_night',
                'subject' => $subj,
                'body' => $body,
                'send_at' => date('Y-m-d H:i:00')
            ]);
        }
        $this->session->set_flashdata('success','Night reminders queued');
        redirect('reminders');
    }

    // GET /reminders/cron/send-queue
    public function send_queue(){
        $sent = 0; $failed = 0;
        // Smaller batch to avoid timeouts
        $queue = $this->reminders->fetch_queue(10);
        // Initialize minimal email config to reduce SMTP hang
        $cfg = array(
            'smtp_timeout' => 10,
            'mailtype' => 'text',
            'newline' => "\r\n",
            'crlf' => "\r\n",
            'charset' => 'utf-8'
        );
        $this->email->initialize($cfg);
        foreach ($queue as $q){
            // Clear previous recipients/headers
            $this->email->clear(true);
            if (!isset($q->email) || $q->email==='') { $this->reminders->mark_error($q->id); $failed++; continue; }
            $fromAddr = isset($q->from_email) && $q->from_email!=='' ? $q->from_email : getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
            $fromName = isset($q->from_name) && $q->from_name!=='' ? $q->from_name : 'Office Management System';
            $this->email->from($fromAddr, $fromName);
            $this->email->to($q->email);
            $this->email->subject($q->subject);
            $this->email->message($q->body);
            // Attempt send with short timeout
            if ($this->email->send()) {
                $this->reminders->mark_sent($q->id); $sent++;
            } else {
                $this->reminders->mark_error($q->id); $failed++;
            }
        }
        $this->session->set_flashdata('success','Queue processed. Sent: '.$sent.' Failed: '.$failed.' (batch size: '.count($queue).')');
        redirect('reminders');
    }

    // POST /reminders/cron/send-selected
    public function send_selected(){
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)){
            $this->session->set_flashdata('error','No reminders selected');
            redirect('reminders'); return;
        }
        $sent = 0; $failed = 0;
        // Initialize email config
        $cfg = array('smtp_timeout'=>10,'mailtype'=>'text','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
        $this->email->initialize($cfg);
        foreach ($ids as $id){
            $q = $this->db->get_where('reminders', array('id'=>(int)$id))->row();
            if (!$q || $q->status==='sent'){ continue; }
            $this->email->clear(true);
            if (!isset($q->email) || $q->email==='') { $this->reminders->mark_error($q->id); $failed++; continue; }
            $fromAddr = isset($q->from_email) && $q->from_email!=='' ? $q->from_email : getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
            $fromName = isset($q->from_name) && $q->from_name!=='' ? $q->from_name : 'Office Management System';
            $this->email->from($fromAddr, $fromName);
            $this->email->to($q->email);
            $this->email->subject($q->subject);
            $this->email->message($q->body);
            if ($this->email->send()) { $this->reminders->mark_sent($q->id); $sent++; }
            else { $this->reminders->mark_error($q->id); $failed++; }
        }
        $this->session->set_flashdata('success','Selected processed. Sent: '.$sent.' Failed: '.$failed);
        redirect('reminders');
    }

    // GET/POST /reminders/send
    public function send(){
        if ($this->input->method() === 'post'){
            $user_id = (int)$this->input->post('user_id');
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $from_email = trim((string)$this->input->post('from_email'));
            $from_name = trim((string)$this->input->post('from_name'));
            if (!$user_id || $subject === ''){
                $this->session->set_flashdata('error','Please select user and enter subject');
                redirect('reminders/send'); return;
            }
            // fetch user email
            $email = '';
            $name = '';
            if ($this->db->table_exists('users')){
                $this->db->from('users')->where('id',$user_id);
                $sel = ['email'];
                if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
                if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')) { $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
                $this->db->select(implode(',', $sel), false);
                $u = $this->db->get()->row();
                if ($u){
                    $email = isset($u->email)?$u->email:'';
                    if (isset($u->full_label) && $u->full_label!=='') { $name = $u->full_label; }
                    else if (isset($u->full_name) && $u->full_name!=='') { $name = $u->full_name; }
                    else if (isset($u->name) && $u->name!=='') { $name = $u->name; }
                }
            }
            if ($email === ''){ $this->session->set_flashdata('error','Selected user has no email'); redirect('reminders/send'); return; }
            $finalBody = $body;
            if ($finalBody === ''){ $finalBody = "Hello ".$name."\n\n".$subject; }
            $rid = $this->reminders->enqueue([
                'user_id' => $user_id,
                'email' => $email,
                'type' => 'manual',
                'subject' => $subject,
                'body' => $finalBody,
                'from_email' => $from_email!=='' ? $from_email : null,
                'from_name' => $from_name!=='' ? $from_name : null,
                'send_at' => date('Y-m-d H:i:00')
            ]);
            // If Send Now requested, deliver immediately
            if ($this->input->post('send_now')==='1'){
                $cfg = array('smtp_timeout'=>10,'mailtype'=>'text','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
                $this->email->initialize($cfg);
                $row = $this->db->get_where('reminders', array('id'=>(int)$rid))->row();
                if ($row && isset($row->email) && $row->email!==''){
                    $this->email->clear(true);
                    $fromAddr = isset($row->from_email) && $row->from_email!=='' ? $row->from_email : (getenv('SMTP_USER') ?: 'no-reply@example.com');
                    $fromName = isset($row->from_name) && $row->from_name!=='' ? $row->from_name : 'Office Management System';
                    $this->email->from($fromAddr, $fromName);
                    $this->email->to($row->email);
                    $this->email->subject($row->subject);
                    $this->email->message($row->body);
                    if ($this->email->send()) { $this->reminders->mark_sent($row->id); $this->session->set_flashdata('success','Reminder sent.'); }
                    else { $this->reminders->mark_error($row->id); $this->session->set_flashdata('error','Failed to send reminder.'); }
                }
                redirect('reminders');
                return;
            }
            $this->session->set_flashdata('success','Reminder queued for the selected user');
            redirect('reminders');
            return;
        }
        $users = $this->reminders->all_users();
        $this->load->view('reminders/send', ['users'=>$users]);
    }

    // GET /reminders/schedules
    public function schedules(){
        $rows = $this->reminders->list_schedules();
        $this->load->view('reminders/schedules', ['rows'=>$rows]);
    }

    // GET/POST /reminders/schedules/create
    public function schedule_create(){
        if ($this->input->method() === 'post'){
            $audience = $this->input->post('audience'); // 'user' or 'all'
            $user_id = $this->input->post('user_id') !== '' ? (int)$this->input->post('user_id') : null;
            $weekdays = trim($this->input->post('weekdays')); // e.g. 1,2,3
            $send_time = trim($this->input->post('send_time')); // HH:MM
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $name = trim($this->input->post('name'));
            if ($audience !== 'all' && $audience !== 'user') { $audience = 'user'; }
            if ($audience === 'user' && !$user_id){
                $this->session->set_flashdata('error','Please select a user for this schedule.');
                redirect('reminders/schedules/create'); return;
            }
            if ($weekdays === '' || $send_time === '' || $subject === '' || $name === ''){
                $this->session->set_flashdata('error','Please fill required fields.');
                redirect('reminders/schedules/create'); return;
            }
            $this->reminders->create_schedule([
                'name' => $name,
                'audience' => $audience,
                'user_id' => $user_id,
                'weekdays' => $weekdays,
                'send_time' => $send_time,
                'subject' => $subject,
                'body' => $body,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->session->set_flashdata('success','Schedule created');
            redirect('reminders/schedules');
            return;
        }
        $users = $this->reminders->all_users();
        $this->load->view('reminders/schedule_form', ['users'=>$users]);
    }

    // GET /reminders/cron/generate-today
    public function cron_generate_today(){
        // Determine current weekday (0=Sunday .. 6=Saturday) and time HH:MM
        $weekday = (int)date('w');
        $nowTime = date('H:i');
        $due = $this->reminders->fetch_due_schedules($weekday, $nowTime);
        $queued = 0;
        foreach ($due as $s){
            if ($s->audience === 'all'){
                $users = $this->reminders->all_users();
                foreach ($users as $u){
                    $to = isset($u->email)?$u->email:'';
                    if ($to==='') { continue; }
                    $this->reminders->enqueue([
                        'user_id' => isset($u->id)?(int)$u->id:null,
                        'email' => $to,
                        'type' => 'schedule',
                        'subject' => $s->subject,
                        'body' => $s->body !== '' ? $s->body : $s->subject,
                        'send_at' => date('Y-m-d').' '.$s->send_time.':00',
                    ]);
                    $queued++;
                }
            } else { // user
                // fetch single user email
                $email = '';
                if ($this->db->table_exists('users')){
                    $row = $this->db->select('email')->from('users')->where('id',(int)$s->user_id)->get()->row();
                    if ($row && isset($row->email)) { $email = $row->email; }
                }
                if ($email !== ''){
                    $this->reminders->enqueue([
                        'user_id' => (int)$s->user_id,
                        'email' => $email,
                        'type' => 'schedule',
                        'subject' => $s->subject,
                        'body' => $s->body !== '' ? $s->body : $s->subject,
                        'send_at' => date('Y-m-d').' '.$s->send_time.':00',
                    ]);
                    $queued++;
                }
            }
            $this->reminders->mark_schedule_ran_today($s->id);
        }
        $this->session->set_flashdata('success','Generated from schedules: '.$queued.' reminders.');
        redirect('reminders');
    }

    // GET/POST /reminders/announce
    public function announce(){
        if ($this->input->method() === 'post'){
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $from_email = trim((string)$this->input->post('from_email'));
            $from_name = trim((string)$this->input->post('from_name'));
            if ($subject === ''){ $this->session->set_flashdata('error','Subject is required'); redirect('reminders/announce'); return; }
            $users = $this->reminders->all_users();
            $count = 0;
            if ($from_email===''){
                // Default to current user's email if available
                $from_email = (string)$this->session->userdata('email');
            }
            if ($from_name===''){
                // Try to compose a name from users table
                $from_name = '';
                $uid = (int)$this->session->userdata('user_id');
                if ($uid>0 && $this->db->table_exists('users')){
                    $sel = ['email'];
                    if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                    if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
                    if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')) { $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
                    $u = $this->db->select(implode(',', $sel), false)->from('users')->where('id',$uid)->get()->row();
                    if ($u){
                        if (isset($u->full_label) && $u->full_label!=='') { $from_name = $u->full_label; }
                        else if (isset($u->full_name) && $u->full_name!=='') { $from_name = $u->full_name; }
                        else if (isset($u->name) && $u->name!=='') { $from_name = $u->name; }
                    }
                }
            }
            foreach ($users as $u){
                $to = isset($u->email)?$u->email:'';
                if ($to==='') { continue; }
                $this->reminders->enqueue(array(
                    'user_id' => isset($u->id)?(int)$u->id:null,
                    'email' => $to,
                    'type' => 'announcement',
                    'subject' => $subject,
                    'body' => $body !== '' ? $body : $subject,
                    'from_email' => $from_email!=='' ? $from_email : null,
                    'from_name' => $from_name!=='' ? $from_name : null,
                    'send_at' => date('Y-m-d H:i:00')
                ));
                $count++;
            }
            $this->session->set_flashdata('success','Announcement queued to '.$count.' users.');
            redirect('reminders');
            return;
        }
        $this->load->view('reminders/announce');
    }

    // GET/POST /reminders/templates
    public function templates(){
        if ($this->input->method()==='post'){
            $m_subj = trim($this->input->post('morning_subject'));
            $m_body = (string)$this->input->post('morning_body');
            $n_subj = trim($this->input->post('night_subject'));
            $n_body = (string)$this->input->post('night_body');
            if ($m_subj!==''){ $this->reminders->save_template('daily_morning', $m_subj, $m_body); }
            if ($n_subj!==''){ $this->reminders->save_template('daily_night', $n_subj, $n_body); }
            $this->session->set_flashdata('success','Templates saved');
            redirect('reminders/templates'); return;
        }
        $m = $this->reminders->get_template('daily_morning');
        $n = $this->reminders->get_template('daily_night');
        $defaults = array(
            'm_subject' => 'Good morning! Daily login reminder',
            'm_body' => "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.",
            'n_subject' => 'Good evening! Daily logout reminder',
            'n_body' => "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.",
        );
        $data = array(
            'morning_subject' => $m && isset($m->subject)?$m->subject:$defaults['m_subject'],
            'morning_body' => $m && isset($m->body)?$m->body:$defaults['m_body'],
            'night_subject' => $n && isset($n->subject)?$n->subject:$defaults['n_subject'],
            'night_body' => $n && isset($n->body)?$n->body:$defaults['n_body'],
        );
        $this->load->view('reminders/templates', $data);
    }

    // GET /reminders/delete/{id}
    public function delete($id){
        $id = (int)$id;
        if ($id <= 0){ redirect('reminders'); return; }
        $this->reminders->delete($id);
        $this->session->set_flashdata('success','Reminder deleted');
        redirect('reminders');
    }

    // POST /reminders/delete-selected
    public function delete_selected(){
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)){
            $this->session->set_flashdata('error','No reminders selected');
            redirect('reminders'); return;
        }
        $count = $this->reminders->delete_bulk($ids);
        $this->session->set_flashdata('success', 'Deleted: '.$count.' reminders');
        redirect('reminders');
    }
}
