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
        $tplCode = trim((string)$this->input->post('tpl_code'));
        $tplSubject = null;
        $tplBody = null;
        if ($tplCode !== ''){
            $tpl = null;
            if ($tplCode === 'daily_morning'){
                $tpl = $this->reminders->get_template('daily_morning');
                $tplSubject = 'Good morning! Daily login reminder';
                $tplBody = "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.";
            } elseif ($tplCode === 'daily_night'){
                $tpl = $this->reminders->get_template('daily_night');
                $tplSubject = 'Good evening! Daily logout reminder';
                $tplBody = "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.";
            } elseif ($tplCode === 'bulk_manual'){
                $tpl = $this->reminders->get_template('bulk_manual');
                $tplSubject = 'Bulk message';
                $tplBody = "Hello {name}\n\nThis is a bulk message.";
            }
            if ($tpl && isset($tpl->subject)) { $tplSubject = $tpl->subject; }
            if ($tpl && isset($tpl->body)) { $tplBody = $tpl->body; }
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
            $subject = $q->subject;
            $body = $q->body;
            if ($tplCode !== '' && $tplSubject !== null && $tplBody !== null){
                $name = '';
                if (isset($q->user_id) && (int)$q->user_id > 0 && $this->db->table_exists('users')){
                    $label = '';
                    $sel = array('email');
                    if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
                    if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
                    if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')) { $sel[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
                    $u = $this->db->select(implode(',', $sel), false)->from('users')->where('id',(int)$q->user_id)->get()->row();
                    if ($u){
                        if (isset($u->full_label) && $u->full_label!=='') { $label = $u->full_label; }
                        else if (isset($u->full_name) && $u->full_name!=='') { $label = $u->full_name; }
                        else if (isset($u->name) && $u->name!=='') { $label = $u->name; }
                    }
                    $name = $label !== '' ? $label : $q->email;
                } else {
                    $name = $q->email;
                }
                list($subject, $body) = $this->reminders->render_template($tplSubject, $tplBody, array('name'=>$name));
                $update = array('subject' => $subject, 'body' => $body);
                if ($tplCode === 'daily_morning' || $tplCode === 'daily_night' || $tplCode === 'bulk_manual'){
                    $update['type'] = $tplCode;
                }
                $this->db->where('id',(int)$q->id)->update('reminders', $update);
            }
            $this->email->from($fromAddr, $fromName);
            $this->email->to($q->email);
            $this->email->subject($subject);
            $this->email->message($body);
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
            $schedule_type = $this->input->post('schedule_type');
            $schedule_type = ($schedule_type === 'once') ? 'once' : 'weekly';
            $one_time_raw = trim($this->input->post('one_time_at'));
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $name = trim($this->input->post('name'));
            if ($audience !== 'all' && $audience !== 'user') { $audience = 'user'; }
            if ($audience === 'user' && !$user_id){
                $this->session->set_flashdata('error','Please select a user for this schedule.');
                redirect('reminders/schedules/create'); return;
            }
            if ($subject === '' || $name === ''){
                $this->session->set_flashdata('error','Please fill required fields.');
                redirect('reminders/schedules/create'); return;
            }
            $one_time_at = null;
            if ($schedule_type === 'weekly'){
                if ($weekdays === '' || $send_time === ''){
                    $this->session->set_flashdata('error','Please select weekdays and send time.');
                    redirect('reminders/schedules/create'); return;
                }
            } else { // one-time
                if ($one_time_raw === ''){
                    $this->session->set_flashdata('error','Please select send date and time.');
                    redirect('reminders/schedules/create'); return;
                }
                // HTML datetime-local comes as YYYY-MM-DDTHH:MM
                $dt = str_replace('T', ' ', $one_time_raw);
                if (strlen($dt) === 16){ $dt .= ':00'; }
                $one_time_at = $dt;
                // Derive send_time for display/logical consistency
                $send_time = substr($dt, 11, 5);
                // One-time schedules do not use weekdays
                $weekdays = '';
            }
            $this->reminders->create_schedule([
                'name' => $name,
                'audience' => $audience,
                'user_id' => $user_id,
                'weekdays' => $weekdays,
                'schedule_type' => $schedule_type,
                'send_time' => $send_time,
                'one_time_at' => $one_time_at,
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

    // GET/POST /reminders/schedules/{id}/edit
    public function schedule_edit($id){
        $id = (int)$id;
        if ($id <= 0){
            redirect('reminders/schedules');
            return;
        }
        $schedule = $this->reminders->get_schedule($id);
        if (!$schedule){
            $this->session->set_flashdata('error','Schedule not found');
            redirect('reminders/schedules');
            return;
        }
        if ($this->input->method() === 'post'){
            $audience = $this->input->post('audience');
            $user_id = $this->input->post('user_id') !== '' ? (int)$this->input->post('user_id') : null;
            $weekdays = trim($this->input->post('weekdays'));
            $send_time = trim($this->input->post('send_time'));
            $schedule_type = $this->input->post('schedule_type');
            $schedule_type = ($schedule_type === 'once') ? 'once' : 'weekly';
            $one_time_raw = trim($this->input->post('one_time_at'));
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $name = trim($this->input->post('name'));
            if ($audience !== 'all' && $audience !== 'user') { $audience = 'user'; }
            if ($audience === 'user' && !$user_id){
                $this->session->set_flashdata('error','Please select a user for this schedule.');
                redirect('reminders/schedules/'.$id.'/edit'); return;
            }
            if ($subject === '' || $name === ''){
                $this->session->set_flashdata('error','Please fill required fields.');
                redirect('reminders/schedules/'.$id.'/edit'); return;
            }
            $one_time_at = null;
            if ($schedule_type === 'weekly'){
                if ($weekdays === '' || $send_time === ''){
                    $this->session->set_flashdata('error','Please select weekdays and send time.');
                    redirect('reminders/schedules/'.$id.'/edit'); return;
                }
            } else { // one-time
                if ($one_time_raw === ''){
                    $this->session->set_flashdata('error','Please select send date and time.');
                    redirect('reminders/schedules/'.$id.'/edit'); return;
                }
                $dt = str_replace('T', ' ', $one_time_raw);
                if (strlen($dt) === 16){ $dt .= ':00'; }
                $one_time_at = $dt;
                $send_time = substr($dt, 11, 5);
                $weekdays = '';
            }
            $this->reminders->update_schedule($id, array(
                'name' => $name,
                'audience' => $audience,
                'user_id' => $user_id,
                'weekdays' => $weekdays,
                'schedule_type' => $schedule_type,
                'send_time' => $send_time,
                'one_time_at' => $one_time_at,
                'subject' => $subject,
                'body' => $body,
            ));
            $this->session->set_flashdata('success','Schedule updated');
            redirect('reminders/schedules');
            return;
        }
        $users = $this->reminders->all_users();
        $this->load->view('reminders/schedule_form', array(
            'users' => $users,
            'schedule' => $schedule,
            'form_action' => site_url('reminders/schedules/'.$id.'/edit'),
        ));
    }

    // GET /reminders/schedules/{id}/delete
    public function schedule_delete($id){
        $id = (int)$id;
        if ($id <= 0){ redirect('reminders/schedules'); return; }
        $schedule = $this->reminders->get_schedule($id);
        if (!$schedule){
            $this->session->set_flashdata('error','Schedule not found');
            redirect('reminders/schedules'); return;
        }
        $this->reminders->delete_schedule($id);
        $this->session->set_flashdata('success','Schedule deleted');
        redirect('reminders/schedules');
    }

    // GET /reminders/schedules/{id}/activate
    public function schedule_activate($id){
        $id = (int)$id;
        if ($id <= 0){ redirect('reminders/schedules'); return; }
        $schedule = $this->reminders->get_schedule($id);
        if (!$schedule){
            $this->session->set_flashdata('error','Schedule not found');
            redirect('reminders/schedules'); return;
        }
        $this->reminders->set_schedule_active($id, 1);
        $this->session->set_flashdata('success','Schedule activated');
        redirect('reminders/schedules');
    }

    // GET /reminders/schedules/{id}/deactivate
    public function schedule_deactivate($id){
        $id = (int)$id;
        if ($id <= 0){ redirect('reminders/schedules'); return; }
        $schedule = $this->reminders->get_schedule($id);
        if (!$schedule){
            $this->session->set_flashdata('error','Schedule not found');
            redirect('reminders/schedules'); return;
        }
        $this->reminders->set_schedule_active($id, 0);
        $this->session->set_flashdata('success','Schedule deactivated');
        redirect('reminders/schedules');
    }

    // GET /reminders/cron/generate-today
    public function cron_generate_today(){
        // Determine current weekday (0=Sunday .. 6=Saturday) and time HH:MM
        $weekday = (int)date('w');
        $nowTime = date('H:i');
        $due = $this->reminders->fetch_due_schedules($weekday, $nowTime);
        $queued = 0;
        foreach ($due as $s){
            // Determine send_at: use one_time_at for one-time schedules, otherwise today + send_time
            $sendAt = null;
            if (isset($s->schedule_type) && $s->schedule_type === 'once' && isset($s->one_time_at) && $s->one_time_at){
                $sendAt = $s->one_time_at;
            } else {
                $sendAt = date('Y-m-d').' '.$s->send_time.':00';
            }
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
                        'send_at' => $sendAt,
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
                        'send_at' => $sendAt,
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
            $b_subj = trim($this->input->post('bulk_subject'));
            $b_body = (string)$this->input->post('bulk_body');
            if ($m_subj!==''){ $this->reminders->save_template('daily_morning', $m_subj, $m_body); }
            if ($n_subj!==''){ $this->reminders->save_template('daily_night', $n_subj, $n_body); }
            if ($b_subj!==''){ $this->reminders->save_template('bulk_manual', $b_subj, $b_body); }
            $this->session->set_flashdata('success','Templates saved');
            redirect('reminders/templates'); return;
        }
        $m = $this->reminders->get_template('daily_morning');
        $n = $this->reminders->get_template('daily_night');
        $b = $this->reminders->get_template('bulk_manual');
        $defaults = array(
            'm_subject' => 'Good morning! Daily login reminder',
            'm_body' => "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.",
            'n_subject' => 'Good evening! Daily logout reminder',
            'n_body' => "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.",
            'b_subject' => 'Bulk message',
            'b_body' => "Hello {name}\n\nThis is a bulk message.",
        );
        $data = array(
            'morning_subject' => $m && isset($m->subject)?$m->subject:$defaults['m_subject'],
            'morning_body' => $m && isset($m->body)?$m->body:$defaults['m_body'],
            'night_subject' => $n && isset($n->subject)?$n->subject:$defaults['n_subject'],
            'night_body' => $n && isset($n->body)?$n->body:$defaults['n_body'],
            'bulk_subject' => $b && isset($b->subject)?$b->subject:$defaults['b_subject'],
            'bulk_body' => $b && isset($b->body)?$b->body:$defaults['b_body'],
        );
        $this->load->view('reminders/templates', $data);
    }

    // GET/POST /reminders/bulk
    public function bulk(){
        if ($this->input->method() === 'post'){
            $to_raw = (string)$this->input->post('to_emails');
            $subject = trim($this->input->post('subject'));
            $body = (string)$this->input->post('body');
            $from_email = trim((string)$this->input->post('from_email'));
            $from_name = trim((string)$this->input->post('from_name'));
            if ($to_raw === '' || $subject === ''){
                $this->session->set_flashdata('error','Please enter at least one recipient and subject');
                redirect('reminders/bulk'); return;
            }
            $parts = preg_split('/[\s,;]+/', $to_raw);
            $emails = array();
            foreach ($parts as $p){
                $p = trim($p);
                if ($p === '') continue;
                if (!filter_var($p, FILTER_VALIDATE_EMAIL)) continue;
                $emails[] = $p;
            }
            if (empty($emails)){
                $this->session->set_flashdata('error','No valid email addresses found');
                redirect('reminders/bulk'); return;
            }
            if ($from_email===''){
                $from_email = getenv('SMTP_USER');
                if (!$from_email || $from_email==='') { $from_email = 'no-reply@example.com'; }
            }
            if ($from_name===''){
                $from_name = 'Office Management System';
            }
            $count = 0;
            foreach ($emails as $to){
                list($subjRendered, $bodyRendered) = $this->reminders->render_template($subject, $body, array('name'=>$to));
                $this->reminders->enqueue(array(
                    'user_id' => null,
                    'email' => $to,
                    'type' => 'bulk_manual',
                    'subject' => $subjRendered,
                    'body' => $bodyRendered !== '' ? $bodyRendered : $subjRendered,
                    'from_email' => $from_email!=='' ? $from_email : null,
                    'from_name' => $from_name!=='' ? $from_name : null,
                    'send_at' => date('Y-m-d H:i:00')
                ));
                $count++;
            }
            $this->session->set_flashdata('success','Bulk reminders queued to '.$count.' recipients.');
            redirect('reminders');
            return;
        }
        $tpl = $this->reminders->get_template('bulk_manual');
        $defaults = array(
            'b_subject' => 'Bulk message',
            'b_body' => "Hello {name}\n\nThis is a bulk message.",
        );
        $data = array(
            'bulk_subject' => $tpl && isset($tpl->subject)?$tpl->subject:$defaults['b_subject'],
            'bulk_body' => $tpl && isset($tpl->body)?$tpl->body:$defaults['b_body'],
        );
        $this->load->view('reminders/bulk', $data);
    }

    // GET /reminders/import-sample
    public function import_sample(){
        $csv = "email,name\n";
        $csv .= "user1@example.com,User One\n";
        $csv .= "user2@example.com,User Two\n";
        $this->output
            ->set_content_type('text/csv')
            ->set_header('Content-Disposition: attachment; filename="reminders_sample.csv"')
            ->set_output($csv);
    }

    // GET/POST /reminders/import
    public function import(){
        if ($this->input->method() === 'post'){
            $tplCode = trim((string)$this->input->post('tpl_code'));
            $from_email = trim((string)$this->input->post('from_email'));
            $from_name = trim((string)$this->input->post('from_name'));
            if ($tplCode === ''){
                $this->session->set_flashdata('error','Please select a template');
                redirect('reminders/import'); return;
            }
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK){
                $this->session->set_flashdata('error','Please upload a valid CSV file');
                redirect('reminders/import'); return;
            }
            $path = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($path, 'r');
            if (!$handle){
                $this->session->set_flashdata('error','Unable to read uploaded file');
                redirect('reminders/import'); return;
            }
            $header = fgetcsv($handle);
            if (!$header){ fclose($handle); $this->session->set_flashdata('error','CSV is empty'); redirect('reminders/import'); return; }
            $map = array();
            foreach ($header as $i => $col){ $map[strtolower(trim($col))] = $i; }
            if (!isset($map['email'])){
                fclose($handle);
                $this->session->set_flashdata('error','CSV must contain an email column');
                redirect('reminders/import'); return;
            }
            // Load template
            $tpl = null; $tplSubject = null; $tplBody = null;
            if ($tplCode === 'daily_morning'){
                $tpl = $this->reminders->get_template('daily_morning');
                $tplSubject = 'Good morning! Daily login reminder';
                $tplBody = "Hello {name}\n\nThis is your morning reminder to login and check your tasks and announcements.";
            } elseif ($tplCode === 'daily_night'){
                $tpl = $this->reminders->get_template('daily_night');
                $tplSubject = 'Good evening! Daily logout reminder';
                $tplBody = "Hello {name}\n\nThis is your evening reminder to finalize updates and logout.";
            } elseif ($tplCode === 'bulk_manual'){
                $tpl = $this->reminders->get_template('bulk_manual');
                $tplSubject = 'Bulk message';
                $tplBody = "Hello {name}\n\nThis is a bulk message.";
            }
            if ($tpl && isset($tpl->subject)) { $tplSubject = $tpl->subject; }
            if ($tpl && isset($tpl->body)) { $tplBody = $tpl->body; }
            if ($tplSubject === null || $tplBody === null){
                fclose($handle);
                $this->session->set_flashdata('error','Template not configured');
                redirect('reminders/import'); return;
            }
            if ($from_email===''){
                $from_email = getenv('SMTP_USER');
                if (!$from_email || $from_email==='') { $from_email = 'no-reply@example.com'; }
            }
            if ($from_name===''){
                $from_name = 'Office Management System';
            }
            $queued = 0;
            while (($row = fgetcsv($handle)) !== false){
                $email = isset($row[$map['email']]) ? trim($row[$map['email']]) : '';
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { continue; }
                $name = $email;
                if (isset($map['name']) && isset($row[$map['name']]) && trim($row[$map['name']]) !== ''){
                    $name = trim($row[$map['name']]);
                }
                list($subjRendered, $bodyRendered) = $this->reminders->render_template($tplSubject, $tplBody, array('name'=>$name));
                $this->reminders->enqueue(array(
                    'user_id' => null,
                    'email' => $email,
                    'type' => $tplCode,
                    'subject' => $subjRendered,
                    'body' => $bodyRendered !== '' ? $bodyRendered : $subjRendered,
                    'from_email' => $from_email!=='' ? $from_email : null,
                    'from_name' => $from_name!=='' ? $from_name : null,
                    'send_at' => date('Y-m-d H:i:00')
                ));
                $queued++;
            }
            fclose($handle);
            $this->session->set_flashdata('success','Queued '.$queued.' reminders from CSV.');
            redirect('reminders');
            return;
        }
        $this->load->view('reminders/import');
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
