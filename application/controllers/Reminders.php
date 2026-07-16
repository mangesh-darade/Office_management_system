<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reminders extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(array(
            'url', 'form', 'permission', 'schema_columns',
            'reminders_user', 'reminders_email', 'reminders_template',
            'reminders_cron', 'reminders_pagination', 'reminders_schedule',
            'reminders_google',
        ));
        $this->load->library(array('session', 'email', 'pagination'));

        $method = (string) $this->router->fetch_method();
        $cron_methods = array('cron_morning', 'cron_night', 'send_queue', 'cron_generate_today', 'cron_attendance_alerts');
        if (in_array($method, $cron_methods, true)) {
            // Cron-style methods: allow CLI or a valid cron token; otherwise
            // require the reminders module permission like every other method.
            if (!$this->input->is_cli_request() && !$this->_valid_cron_token()) {
                require_module_access('reminders', true);
            }
        } else {
            require_module_access('reminders', true);
        }

        $this->load->model('Reminder_model', 'reminders');
        $this->reminders->ensure_schema();
    }

    /**
     * Same token rules as Cron controller: settings value or CRON_TOKEN env.
     * No configured token means HTTP token access is disabled (fail closed).
     *
     * @return bool
     */
    private function _valid_cron_token(){
        $token = (string) $this->input->get('token');
        if ($token === '') {
            return false;
        }
        $this->load->model('Setting_model', 'settings');
        $expected = $this->settings->get_setting('cron_secret_token', '');
        if ($expected === '' || $expected === null) {
            $expected = getenv('CRON_TOKEN');
        }
        if ($expected === false || $expected === '' || $expected === null) {
            return false;
        }
        return hash_equals((string) $expected, $token);
    }

    public function index(){
        redirect('reminders/dashboard');
    }

    public function dashboard(){
        $perPage = 20;
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $filter = reminders_dashboard_filter($this->input);
        $total = $this->reminders->count_total($filter);

        $this->pagination->initialize(reminders_dashboard_pagination_config($filter, $total, $perPage));
        $rows = $this->reminders->list_paginated($perPage, $page, $filter);

        $this->load->view('reminders/dashboard', array(
            'rows'             => $rows,
            'pagination_links' => $this->pagination->create_links(),
            'total_rows'       => $total,
            'per_page'         => $perPage,
            'current_page'     => $page,
            'current_filter'   => $filter,
        ));
    }

    public function cron_morning(){
        reminders_queue_daily_type($this->reminders, 'daily_morning');
        $this->session->set_flashdata('success', 'Morning reminders queued');
        redirect('reminders');
    }

    public function cron_night(){
        reminders_queue_daily_type($this->reminders, 'daily_night');
        $this->session->set_flashdata('success', 'Night reminders queued');
        redirect('reminders');
    }

    public function send_queue(){
        // SMTP cron send removed — delivery is via Google Calendar on enqueue/send.
        if ($this->input->is_cli_request() || $this->_valid_cron_token()) {
            echo "Reminders SMTP send-queue disabled. Use Google Calendar (synced on create).\n";
            return;
        }
        $this->session->set_flashdata(
            'success',
            'Send-queue cron is disabled. Reminders are delivered by Google Calendar when created/saved.'
        );
        redirect('reminders');
    }

    public function send_selected(){
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)) {
            $this->session->set_flashdata('error', 'No reminders selected');
            redirect('reminders');
            return;
        }
        $tplCode = trim((string) $this->input->post('tpl_code'));
        $result = reminders_send_selected_ids($this->reminders, $this->db, $this->email, $ids, $tplCode);
        $this->session->set_flashdata(
            'success',
            'Selected processed. Sent: ' . $result['sent'] . ' Failed: ' . $result['failed']
        );
        redirect('reminders');
    }

    public function send(){
        if ($this->input->method() === 'post') {
            $delivery_method = $this->input->post('delivery_method') ?: 'immediate';
            $user_ids = $this->input->post('user_ids');
            // Backward compatible: single user_id from older forms
            if ((!is_array($user_ids) || empty($user_ids)) && $this->input->post('user_id') !== null) {
                $one = (int) $this->input->post('user_id');
                $user_ids = $one > 0 ? array($one) : array();
            }
            if (!is_array($user_ids)) {
                $user_ids = array();
            }

            $subject = trim((string) $this->input->post('subject'));
            $body = (string) $this->input->post('body');
            $role_id = (int) $this->session->userdata('role_id');
            $from = reminders_admin_from_post($role_id, $this->input);
            $send_at = $this->input->post('send_at');

            $clean_ids = array();
            foreach ($user_ids as $uid) {
                $uid = (int) $uid;
                if ($uid > 0) {
                    $clean_ids[$uid] = $uid;
                }
            }
            $user_ids = array_values($clean_ids);

            if (empty($user_ids) || $subject === '') {
                $this->session->set_flashdata('error', 'Please select at least one recipient and enter a subject.');
                redirect('reminders/send');
                return;
            }

            if ($delivery_method === 'scheduled') {
                $send_at = trim((string) $send_at);
                if ($send_at === '') {
                    $this->session->set_flashdata('error', 'Please choose a schedule date and time.');
                    redirect('reminders/send');
                    return;
                }
                $dt = str_replace('T', ' ', $send_at);
                if (strlen($dt) === 16) {
                    $dt .= ':00';
                }
                $dtObj = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
                if (!$dtObj) {
                    $this->session->set_flashdata('error', 'Invalid schedule date/time.');
                    redirect('reminders/send');
                    return;
                }
                $scheduled_time = $dtObj->format('Y-m-d H:i:s');
            } else {
                $scheduled_time = null;
            }

            $count = 0;
            $failed = 0;
            foreach ($user_ids as $user_id) {
                $contact = reminders_fetch_user_contact($this->db, $user_id);
                if ($contact['email'] === '') {
                    $failed++;
                    continue;
                }
                list($finalSubject, $finalBody) = reminders_replace_vars(
                    $subject,
                    $body !== '' ? $body : ('Hello ' . $contact['name'] . "\n\n" . $subject),
                    $contact['name'],
                    $contact['email']
                );
                $send_time = $scheduled_time ? $scheduled_time : date('Y-m-d H:i:00');
                $rid = $this->reminders->enqueue(array(
                    'user_id'    => $user_id,
                    'email'      => $contact['email'],
                    'type'       => 'manual',
                    'subject'    => $finalSubject,
                    'body'       => $finalBody,
                    'from_email' => $from['from_email'] !== '' ? $from['from_email'] : null,
                    'from_name'  => $from['from_name'] !== '' ? $from['from_name'] : null,
                    'send_at'    => $send_time,
                ));
                if ($rid) {
                    $row = $this->reminders->get($rid);
                    if ($row && isset($row->status) && $row->status === 'sent') {
                        $count++;
                    } else {
                        $failed++;
                    }
                } else {
                    $failed++;
                }
            }

            if ($count > 0) {
                $message = $scheduled_time
                    ? 'Reminders scheduled via Google Calendar for ' . date('M j, Y H:i', strtotime($scheduled_time))
                    : 'Reminders created via Google Calendar';
                $message .= ' for ' . $count . ' recipient(s).';
                if ($failed > 0) {
                    $message .= ' Failed: ' . $failed . '.';
                }
                $this->session->set_flashdata('success', $message);
            } else {
                $this->session->set_flashdata(
                    'error',
                    'No reminders were created. Connect Google Calendar and ensure recipients have email addresses.'
                );
            }
            redirect('reminders/dashboard');
            return;
        }
        $this->load->view('reminders/send', array('users' => $this->reminders->all_users()));
    }

    public function schedules(){
        $this->load->view('reminders/schedules', array('rows' => $this->reminders->list_schedules()));
    }

    public function schedule_create(){
        if ($this->input->method() === 'post') {
            $parsed = reminders_parse_schedule_post($this->input);
            if (!$parsed['ok']) {
                $this->session->set_flashdata('error', $parsed['error']);
                redirect('reminders/schedules/create');
                return;
            }
            $data = $parsed['data'];
            $data['active'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            $sid = $this->reminders->create_schedule($data);
            if ($sid <= 0) {
                $this->session->set_flashdata('error', 'Failed to create schedule.');
                redirect('reminders/schedules/create');
                return;
            }

            $schedule = $this->reminders->get_schedule($sid);
            $push = reminders_schedule_push_to_google($this->reminders, $this->db, $schedule, true);
            if (!empty($push['ok'])) {
                $this->session->set_flashdata(
                    'success',
                    'Schedule created. Google Calendar synced ' . (int) $push['synced']
                    . ' reminder(s) for ' . $push['send_at']
                    . ($push['failed'] > 0 ? ' (' . (int) $push['failed'] . ' failed)' : '') . '.'
                );
            } else {
                $this->session->set_flashdata(
                    'error',
                    'Schedule saved, but Google sync failed: ' . $push['message']
                );
            }
            redirect('reminders/schedules');
            return;
        }
        $this->load->view('reminders/schedule_form', array('users' => $this->reminders->all_users()));
    }

    public function schedule_edit($id){
        $id = (int) $id;
        if ($id <= 0) {
            redirect('reminders/schedules');
            return;
        }
        $schedule = $this->reminders->get_schedule($id);
        if (!$schedule) {
            $this->session->set_flashdata('error', 'Schedule not found');
            redirect('reminders/schedules');
            return;
        }
        if ($this->input->method() === 'post') {
            $parsed = reminders_parse_schedule_post($this->input);
            if (!$parsed['ok']) {
                $this->session->set_flashdata('error', $parsed['error']);
                redirect('reminders/schedules/' . $id . '/edit');
                return;
            }
            $this->reminders->update_schedule($id, $parsed['data']);
            $updated = $this->reminders->get_schedule($id);
            // Clear last_run so new next occurrence can be pushed
            if ($updated) {
                $this->reminders->db->where('id', $id)->update('reminder_schedules', array('last_run_date' => null));
                $updated->last_run_date = null;
            }
            $push = reminders_schedule_push_to_google($this->reminders, $this->db, $updated, true);
            if (!empty($push['ok'])) {
                $this->session->set_flashdata(
                    'success',
                    'Schedule updated. Google Calendar synced ' . (int) $push['synced']
                    . ' reminder(s) for ' . $push['send_at'] . '.'
                );
            } else {
                $this->session->set_flashdata(
                    'error',
                    'Schedule saved, but Google sync failed: ' . $push['message']
                );
            }
            redirect('reminders/schedules');
            return;
        }
        $this->load->view('reminders/schedule_form', array(
            'users'       => $this->reminders->all_users(),
            'schedule'    => $schedule,
            'form_action' => site_url('reminders/schedules/' . $id . '/edit'),
        ));
    }

    public function schedule_delete($id){
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $id;
        if ($id <= 0) {
            redirect('reminders/schedules');
            return;
        }
        if (!$this->reminders->get_schedule($id)) {
            $this->session->set_flashdata('error', 'Schedule not found');
            redirect('reminders/schedules');
            return;
        }
        $this->reminders->delete_schedule($id);
        $this->session->set_flashdata('success', 'Schedule deleted');
        redirect('reminders/schedules');
    }

    public function schedule_activate($id){
        $this->_set_schedule_active($id, 1, 'Schedule activated');
    }

    public function schedule_deactivate($id){
        $this->_set_schedule_active($id, 0, 'Schedule deactivated');
    }

    private function _set_schedule_active($id, $active, $success_msg)
    {
        $id = (int) $id;
        if ($id <= 0) {
            redirect('reminders/schedules');
            return;
        }
        if (!$this->reminders->get_schedule($id)) {
            $this->session->set_flashdata('error', 'Schedule not found');
            redirect('reminders/schedules');
            return;
        }
        $this->reminders->set_schedule_active($id, $active);
        $this->session->set_flashdata('success', $success_msg);
        redirect('reminders/schedules');
    }

    public function cron_generate_today(){
        $this->load->helper('attendance_alert');
        $result = reminders_generate_from_schedules($this->reminders, $this->db);
        $att = attendance_alert_plan_daily($this->reminders, $this->db);
        $msg = 'Schedules processed. Queued: ' . (int) $result['queued']
            . ', Google synced: ' . (int) $result['synced']
            . ', Failed: ' . (int) $result['failed']
            . '. Attendance alerts — check-in: ' . (int) $att['checkin']
            . ', check-out: ' . (int) $att['checkout']
            . ', skipped: ' . (int) $att['skipped'];
        if ($this->input->is_cli_request() || $this->_valid_cron_token()) {
            echo $msg . "\n";
            return;
        }
        $this->session->set_flashdata('success', $msg);
        redirect('reminders');
    }

    public function cron_attendance_alerts(){
        $this->load->helper('attendance_alert');
        $att = attendance_alert_plan_daily($this->reminders, $this->db);
        $msg = 'Attendance alerts — check-in: ' . (int) $att['checkin']
            . ', check-out: ' . (int) $att['checkout']
            . ', skipped: ' . (int) $att['skipped'];
        if ($this->input->is_cli_request() || $this->_valid_cron_token()) {
            echo $msg . "\n";
            return;
        }
        $this->session->set_flashdata('success', $msg);
        redirect('reminders');
    }

    public function announce(){
        if ($this->input->method() === 'post') {
            $subject = trim($this->input->post('subject'));
            $body = (string) $this->input->post('body');
            $role_id = (int) $this->session->userdata('role_id');
            $from = reminders_admin_from_post($role_id, $this->input);
            if ($subject === '') {
                $this->session->set_flashdata('error', 'Subject is required');
                redirect('reminders/announce');
                return;
            }
            $from_email = $from['from_email'];
            $from_name = $from['from_name'];
            if ($from_email === '') {
                $from_email = (string) $this->session->userdata('email');
            }
            if ($from_name === '') {
                $contact = reminders_fetch_user_contact($this->db, (int) $this->session->userdata('user_id'));
                if ($contact['name'] !== '') {
                    $from_name = $contact['name'];
                }
            }
            $count = 0;
            foreach ($this->reminders->all_users() as $u) {
                $to = isset($u->email) ? $u->email : '';
                if ($to === '') {
                    continue;
                }
                $this->reminders->enqueue(array(
                    'user_id'    => isset($u->id) ? (int) $u->id : null,
                    'email'      => $to,
                    'type'       => 'announcement',
                    'subject'    => $subject,
                    'body'       => $body !== '' ? $body : $subject,
                    'from_email' => $from_email !== '' ? $from_email : null,
                    'from_name'  => $from_name !== '' ? $from_name : null,
                    'send_at'    => date('Y-m-d H:i:00'),
                ));
                $count++;
            }
            $this->session->set_flashdata('success', 'Announcement queued to ' . $count . ' users.');
            redirect('reminders');
            return;
        }
        $this->load->view('reminders/announce');
    }

    public function templates(){
        if ($this->input->method() === 'post') {
            $m_subj = trim($this->input->post('morning_subject'));
            $m_body = (string) $this->input->post('morning_body');
            $n_subj = trim($this->input->post('night_subject'));
            $n_body = (string) $this->input->post('night_body');
            $b_subj = trim($this->input->post('bulk_subject'));
            $b_body = (string) $this->input->post('bulk_body');
            if ($m_subj !== '') {
                $this->reminders->save_template('daily_morning', $m_subj, $m_body);
            }
            if ($n_subj !== '') {
                $this->reminders->save_template('daily_night', $n_subj, $n_body);
            }
            if ($b_subj !== '') {
                $this->reminders->save_template('bulk_manual', $b_subj, $b_body);
            }
            $this->session->set_flashdata('success', 'Templates saved');
            redirect('reminders/templates');
            return;
        }
        $this->load->view('reminders/templates', reminders_templates_view_data($this->reminders));
    }

    public function bulk(){
        if ($this->input->method() === 'post') {
            $to_raw = (string) $this->input->post('to_emails');
            $subject = trim($this->input->post('subject'));
            $body = (string) $this->input->post('body');
            $role_id = (int) $this->session->userdata('role_id');
            $from = reminders_admin_from_post($role_id, $this->input);
            if ($to_raw === '' || $subject === '') {
                $this->session->set_flashdata('error', 'Please enter at least one recipient and subject');
                redirect('reminders/bulk');
                return;
            }
            $parts = preg_split('/[\s,;]+/', $to_raw);
            $emails = array();
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '' || !filter_var($p, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $emails[] = $p;
            }
            if (empty($emails)) {
                $this->session->set_flashdata('error', 'No valid email addresses found');
                redirect('reminders/bulk');
                return;
            }
            $sender = reminders_resolve_sender_defaults($this->db, $from['from_email'], $from['from_name'], 0, '');
            $count = 0;
            foreach ($emails as $to) {
                list($subjRendered, $bodyRendered) = $this->reminders->render_template($subject, $body, array('name' => $to));
                $this->reminders->enqueue(array(
                    'user_id'    => null,
                    'email'      => $to,
                    'type'       => 'bulk_manual',
                    'subject'    => $subjRendered,
                    'body'       => $bodyRendered !== '' ? $bodyRendered : $subjRendered,
                    'from_email' => $sender['from_email'] !== '' ? $sender['from_email'] : null,
                    'from_name'  => $sender['from_name'] !== '' ? $sender['from_name'] : null,
                    'send_at'    => date('Y-m-d H:i:00'),
                ));
                $count++;
            }
            $this->session->set_flashdata('success', 'Bulk reminders queued to ' . $count . ' recipients.');
            redirect('reminders');
            return;
        }
        $tpl = $this->reminders->get_template('bulk_manual');
        $defaults = reminders_builtin_templates()['bulk_manual'];
        $this->load->view('reminders/bulk', array(
            'bulk_subject' => ($tpl && isset($tpl->subject)) ? $tpl->subject : $defaults['subject'],
            'bulk_body'    => ($tpl && isset($tpl->body)) ? $tpl->body : $defaults['body'],
        ));
    }

    public function import_sample(){
        $csv = "email,name\nuser1@example.com,User One\nuser2@example.com,User Two\n";
        $this->output
            ->set_content_type('text/csv')
            ->set_header('Content-Disposition: attachment; filename="reminders_sample.csv"')
            ->set_output($csv);
    }

    public function import(){
        if ($this->input->method() === 'post') {
            $tplCode = trim((string) $this->input->post('tpl_code'));
            $role_id = (int) $this->session->userdata('role_id');
            $from = reminders_admin_from_post($role_id, $this->input);
            if ($tplCode === '') {
                $this->session->set_flashdata('error', 'Please select a template');
                redirect('reminders/import');
                return;
            }
            $resolved = reminders_resolve_template($this->reminders, $tplCode);
            if ($resolved === null) {
                $this->session->set_flashdata('error', 'Template not configured');
                redirect('reminders/import');
                return;
            }
            $parsed = reminders_parse_csv_import();
            if (!$parsed['ok']) {
                $this->session->set_flashdata('error', $parsed['error']);
                redirect('reminders/import');
                return;
            }
            $sender = reminders_resolve_sender_defaults($this->db, $from['from_email'], $from['from_name'], 0, '');
            $queued = reminders_queue_csv_rows(
                $this->reminders,
                $parsed['handle'],
                $parsed['map'],
                $tplCode,
                $resolved['subject'],
                $resolved['body'],
                $sender['from_email'],
                $sender['from_name']
            );
            $this->session->set_flashdata('success', 'Queued ' . $queued . ' reminders from CSV.');
            redirect('reminders');
            return;
        }
        $this->load->view('reminders/import');
    }

    public function send_now($id){
        $id = (int) $id;
        if ($id <= 0) {
            $this->session->set_flashdata('error', 'Invalid reminder ID');
            redirect('reminders/dashboard');
            return;
        }
        $reminder = $this->reminders->get($id);
        if (!$reminder) {
            $this->session->set_flashdata('error', 'Reminder not found');
            redirect('reminders/dashboard');
            return;
        }
        if ($reminder->status === 'sent') {
            $this->session->set_flashdata('error', 'This reminder has already been sent');
            redirect('reminders/dashboard');
            return;
        }
        reminders_configure_email();
        if (reminders_send_one($this->email, $this->reminders, $reminder)) {
            $this->session->set_flashdata('success', 'Reminder scheduled via Google Calendar for ' . $reminder->email);
        } else {
            $this->session->set_flashdata(
                'error',
                'Failed to sync with Google Calendar. Connect Google under Calendar Reminder Settings.'
            );
        }
        redirect('reminders/dashboard');
    }

    public function edit($id){
        $id = (int) $id;
        if ($id <= 0) {
            $this->session->set_flashdata('error', 'Invalid reminder ID');
            redirect('reminders/dashboard');
            return;
        }
        $reminder = $this->reminders->get($id);
        if (!$reminder) {
            $this->session->set_flashdata('error', 'Reminder not found');
            redirect('reminders/dashboard');
            return;
        }
        if ($this->input->method() === 'post') {
            $this->_handle_edit_post($id, $reminder);
            return;
        }
        $old = $this->session->flashdata('reminder_edit_old');
        if (is_array($old) && !empty($old)) {
            foreach ($old as $k => $v) {
                if ($k === 'send_at') {
                    $reminder->send_at = $v;
                } else {
                    $reminder->{$k} = $v;
                }
            }
        }
        $this->load->view('reminders/edit', array('reminder' => $reminder));
    }

    private function _handle_edit_post($id, $reminder)
    {
        $subject = trim($this->input->post('subject'));
        $body = (string) $this->input->post('body');
        $email = trim($this->input->post('email'));
        $role_id = (int) $this->session->userdata('role_id');
        $from = reminders_admin_from_post($role_id, $this->input);
        $send_at = $this->input->post('send_at');

        $flash_old = function () use ($subject, $body, $email, $from, $send_at) {
            $this->session->set_flashdata('reminder_edit_old', array(
                'subject'    => $subject,
                'body'       => $body,
                'email'      => $email,
                'from_email' => $from['from_email'],
                'from_name'  => $from['from_name'],
                'send_at'    => $send_at,
            ));
        };

        if ($subject === '' || $email === '') {
            $this->session->set_flashdata('error', 'Subject and email are required');
            $flash_old();
            redirect('reminders/edit/' . $id);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address');
            $flash_old();
            redirect('reminders/edit/' . $id);
            return;
        }

        $update_data = array(
            'subject'    => $subject,
            'body'       => $body,
            'email'      => $email,
            'from_email' => $from['from_email'] !== '' ? $from['from_email'] : null,
            'from_name'  => $from['from_name'] !== '' ? $from['from_name'] : null,
        );

        if ($send_at !== null) {
            $send_at = trim($send_at);
            if ($send_at !== '') {
                $dt = str_replace('T', ' ', $send_at);
                if (strlen($dt) === 16) {
                    $dt .= ':00';
                }
                $dtObj = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
                if (!$dtObj) {
                    $this->session->set_flashdata('error', 'Invalid schedule date/time format.');
                    $flash_old();
                    redirect('reminders/edit/' . $id);
                    return;
                }
                if ($dtObj < new DateTime()) {
                    $this->session->set_flashdata('error', 'Scheduled time cannot be in the past.');
                    $flash_old();
                    redirect('reminders/edit/' . $id);
                    return;
                }
                $update_data['send_at'] = $dtObj->format('Y-m-d H:i:s');
            } else {
                $update_data['send_at'] = null;
            }
        }

        $current = $this->reminders->get($id);
        if (!$current) {
            $this->session->set_flashdata('error', 'Reminder not found');
            redirect('reminders/dashboard');
            return;
        }
        if ($current->status === 'sent') {
            $this->session->set_flashdata('error', 'Cannot edit a reminder that has already been sent');
            redirect('reminders/dashboard');
            return;
        }

        $this->reminders->update($id, $update_data);
        // Re-sync to Google Calendar with updated time/content
        $this->load->helper('reminders_google');
        $updated = $this->reminders->get($id);
        if ($updated && $updated->status !== 'sent') {
            $sync = reminders_google_sync_row($this->reminders, $updated, 0);
            if (!empty($sync['ok'])) {
                $this->session->set_flashdata('success', 'Reminder updated and scheduled via Google Calendar');
            } else {
                $this->session->set_flashdata(
                    'error',
                    'Reminder saved, but Google sync failed: ' . (isset($sync['message']) ? $sync['message'] : 'unknown')
                );
            }
        } else {
            $this->session->set_flashdata('success', 'Reminder updated successfully');
        }
        redirect('reminders/dashboard');
    }

    public function delete($id){
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $id;
        if ($id <= 0) {
            redirect('reminders');
            return;
        }
        $this->reminders->delete($id);
        $this->session->set_flashdata('success', 'Reminder deleted');
        redirect('reminders');
    }

    public function delete_selected(){
        $ids = $this->input->post('ids');
        if (!is_array($ids) || empty($ids)) {
            $this->session->set_flashdata('error', 'No reminders selected');
            redirect('reminders');
            return;
        }
        $count = $this->reminders->delete_bulk($ids);
        $this->session->set_flashdata('success', 'Deleted: ' . $count . ' reminders');
        redirect('reminders');
    }
}
