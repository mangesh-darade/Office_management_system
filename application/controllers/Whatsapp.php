<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Controller
 *
 * Meta Cloud API inbox, templates, and outbound send (tasks/reports).
 */
class Whatsapp extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form', 'permission', 'api_integration']);
        $this->load->library('session');
        $this->load->database();
        $this->load->model('Whatsapp_model', 'whatsapp_inbox');

        if (strtolower((string) $this->router->method) === 'webhook') {
            return;
        }

        require_module_access('whatsapp', true);
        $this->load->config('whatsapp', true);
    }
    
    /**
     * GET /whatsapp
     * Inbox + send tools
     */
    public function index() {
        $conv_id = (int) $this->input->get('c');
        $conversation = $conv_id ? $this->whatsapp_inbox->get_conversation($conv_id) : null;
        $messages = array();
        $window = array('open' => false, 'hours_left' => 0, 'expires_at' => null, 'last_inbound_at' => null);
        if ($conversation) {
            $this->whatsapp_inbox->mark_read($conv_id);
            $conversation->unread_count = 0;
            $messages = $this->whatsapp_inbox->list_messages($conv_id);
            $window = whatsapp_conversation_window($conv_id);
            $last_wamid = $this->whatsapp_inbox->last_inbound_wamid($conv_id);
            if ($last_wamid !== '') {
                mark_whatsapp_message_read($last_wamid);
            }
        }

        $employees = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.department, u.email')
            ->from('employees e')
            ->join('users u', 'u.id = e.user_id', 'left')
            ->where('e.phone IS NOT NULL')
            ->where('e.phone !=', '')
            ->order_by('e.first_name', 'ASC')
            ->get()
            ->result();

        $this->load->view('whatsapp/index', [
            'employees' => $employees,
            'config_configured' => $this->_is_configured(),
            'conversations' => $this->whatsapp_inbox->list_conversations(),
            'conversation' => $conversation,
            'messages' => $messages,
            'templates' => list_whatsapp_templates(),
            'webhook_url' => site_url('whatsapp/webhook'),
            'creds' => get_whatsapp_credentials(),
            'window' => $window,
        ]);
    }

    /**
     * GET/POST /whatsapp/webhook — Meta Cloud API (public).
     */
    public function webhook()
    {
        handle_meta_whatsapp_webhook_http();
    }

    /**
     * POST /whatsapp/reply
     */
    public function reply()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $conv_id = (int) $this->input->post('conversation_id');
        $message = trim((string) $this->input->post('message'));
        $template_name = trim((string) $this->input->post('template_name'));
        if ($template_name === '') {
            $template_name = trim((string) $this->input->post('template_name_custom'));
        }
        $conversation = $this->whatsapp_inbox->get_conversation($conv_id);
        if (!$conversation) {
            $this->session->set_flashdata('error', 'Conversation not found.');
            redirect('whatsapp');
            return;
        }
        if (!$this->_is_configured()) {
            $this->session->set_flashdata('error', 'WhatsApp is not configured.');
            redirect('whatsapp?c=' . $conv_id);
            return;
        }
        $options = array();
        if ($template_name !== '') {
            $options['template_name'] = $template_name;
            $options['language'] = trim((string) $this->input->post('template_language')) ?: 'en_US';
            $components = build_whatsapp_template_body_components(whatsapp_collect_template_vars_from_post());
            if (!empty($components)) {
                $options['template_components'] = $components;
            }
        } elseif ($message !== '') {
            $window = whatsapp_conversation_window($conv_id);
            if (empty($window['open'])) {
                $this->session->set_flashdata('error', 'Free-text is blocked outside the 24-hour window. Choose an approved template (and fill {{variables}} if required).');
                redirect('whatsapp?c=' . $conv_id);
                return;
            }
        }
        if ($message === '' && $template_name === '') {
            $this->session->set_flashdata('error', 'Enter a message or choose a template.');
            redirect('whatsapp?c=' . $conv_id);
            return;
        }
        $result = send_whatsapp_message($conversation->wa_id, $message, $options);
        if (!empty($result['success'])) {
            $this->session->set_flashdata('success', 'Message sent.');
        } else {
            $this->session->set_flashdata('error', !empty($result['error']) ? $result['error'] : 'Send failed.');
        }
        redirect('whatsapp?c=' . $conv_id);
    }

    /**
     * POST /whatsapp/start — new thread by phone.
     */
    public function start()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $phone = trim((string) $this->input->post('phone'));
        $message = trim((string) $this->input->post('message'));
        $template_name = trim((string) $this->input->post('template_name'));
        if ($template_name === '') {
            $template_name = trim((string) $this->input->post('template_name_custom'));
        }
        $wa_id = normalize_whatsapp_phone($phone);
        if ($wa_id === '') {
            $this->session->set_flashdata('error', 'Enter a valid phone number.');
            redirect('whatsapp');
            return;
        }
        if (!$this->_is_configured()) {
            $this->session->set_flashdata('error', 'WhatsApp is not configured.');
            redirect('whatsapp');
            return;
        }
        $options = array();
        if ($template_name !== '') {
            $options['template_name'] = $template_name;
            $options['language'] = trim((string) $this->input->post('template_language')) ?: 'en_US';
            $components = build_whatsapp_template_body_components(whatsapp_collect_template_vars_from_post());
            if (!empty($components)) {
                $options['template_components'] = $components;
            }
        }
        if ($message === '' && $template_name === '') {
            $creds = get_whatsapp_credentials();
            if (!empty($creds['default_template'])) {
                $options['template_name'] = $creds['default_template'];
            } else {
                $cached = list_whatsapp_templates(null, true);
                if (!empty($cached[0]['name'])) {
                    $options['template_name'] = $cached[0]['name'];
                    if (!empty($cached[0]['language'])) {
                        $options['language'] = $cached[0]['language'];
                    }
                } else {
                    $this->session->set_flashdata('error', 'First contact needs a Meta template name (e.g. hello_world). Add it under Templates or type it in New chat.');
                    redirect('whatsapp');
                    return;
                }
            }
        } elseif ($message !== '' && $template_name === '') {
            $this->session->set_flashdata('error', 'First contact outside the 24-hour window must use a template. Select or type a template name.');
            redirect('whatsapp');
            return;
        }
        $result = send_whatsapp_message($wa_id, $message, $options);
        $conv_id = 0;
        if (!empty($result['success'])) {
            $conv_id = $this->whatsapp_inbox->upsert_conversation($wa_id, '');
            $this->session->set_flashdata('success', 'Message sent.');
        } else {
            $this->session->set_flashdata('error', !empty($result['error']) ? $result['error'] : 'Send failed.');
        }
        redirect('whatsapp' . ($conv_id ? ('?c=' . (int) $conv_id) : ''));
    }

    /**
     * GET /whatsapp/templates
     */
    public function templates()
    {
        $this->load->view('whatsapp/templates', array(
            'config_configured' => $this->_is_configured(),
            'templates' => $this->whatsapp_inbox->list_templates(false),
            'last_synced' => $this->whatsapp_inbox->templates_synced_at(),
            'creds' => get_whatsapp_credentials(),
        ));
    }

    /**
     * POST /whatsapp/sync-templates
     */
    public function sync_templates()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        if (!$this->_is_configured()) {
            $this->session->set_flashdata('error', 'WhatsApp is not configured.');
            redirect('whatsapp/templates');
            return;
        }
        $result = sync_whatsapp_templates();
        if (!empty($result['ok'])) {
            $this->session->set_flashdata('success', 'Synced ' . (int) $result['count'] . ' template(s) from Meta.');
        } else {
            $this->session->set_flashdata('error', !empty($result['error']) ? $result['error'] : 'Template sync failed.');
        }
        redirect('whatsapp/templates');
    }

    /**
     * POST /whatsapp/send-template
     */
    public function send_template()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $phone = trim((string) $this->input->post('phone'));
        $template_name = trim((string) $this->input->post('template_name'));
        $language = trim((string) $this->input->post('language'));
        if ($language === '') {
            $language = 'en_US';
        }
        if ($phone === '' || $template_name === '') {
            $this->session->set_flashdata('error', 'Phone and template are required.');
            redirect('whatsapp/templates');
            return;
        }
        if (!$this->_is_configured()) {
            $this->session->set_flashdata('error', 'WhatsApp is not configured.');
            redirect('whatsapp/templates');
            return;
        }
        $result = send_whatsapp_message($phone, '', array(
            'template_name' => $template_name,
            'language' => $language,
            'template_components' => build_whatsapp_template_body_components(whatsapp_collect_template_vars_from_post()),
        ));
        if (!empty($result['success'])) {
            $this->session->set_flashdata('success', 'Template sent.');
        } else {
            $this->session->set_flashdata('error', !empty($result['error']) ? $result['error'] : 'Send failed.');
        }
        redirect('whatsapp/templates');
    }

    /**
     * POST /whatsapp/test-connection
     */
    public function test_connection()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $d = diagnose_whatsapp_connection();
        $msg = format_whatsapp_diagnose_message($d);
        if (!empty($d['ok'])) {
            if (!empty($d['error'])) {
                $this->session->set_flashdata('warning', $msg);
            } else {
                $this->session->set_flashdata('success', $msg);
            }
        } else {
            $this->session->set_flashdata('error', $msg);
        }
        $back = trim((string) $this->input->post('back'));
        if ($back === 'inbox') {
            redirect('whatsapp');
            return;
        }
        redirect('whatsapp/templates');
    }

    /**
     * POST /whatsapp/add-template — cache a Meta template name locally when Graph list is blocked.
     */
    public function add_template()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $name = trim((string) $this->input->post('name'));
        $language = trim((string) $this->input->post('language'));
        if ($language === '') {
            $language = 'en_US';
        }
        $id = $this->whatsapp_inbox->add_template(array(
            'name' => $name,
            'language' => $language,
            'category' => trim((string) $this->input->post('category')),
            'body' => trim((string) $this->input->post('body')),
            'status' => 'APPROVED',
        ));
        if ($id) {
            $this->session->set_flashdata('success', 'Template saved locally. Send still uses this exact name in WhatsApp Manager.');
        } else {
            $this->session->set_flashdata('error', 'Template name must be lowercase letters, numbers, and underscores only (e.g. hello_world).');
        }
        redirect('whatsapp/templates');
    }

    /**
     * POST /whatsapp/delete-template
     */
    public function delete_template()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $ok = $this->whatsapp_inbox->delete_template((int) $this->input->post('id'));
        if ($ok) {
            $this->session->set_flashdata('success', 'Template removed from local cache.');
        } else {
            $this->session->set_flashdata('error', 'Template not found.');
        }
        redirect('whatsapp/templates');
    }
    
    /**
     * POST /whatsapp/send
     * Send WhatsApp message
     */
    public function send() {
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->_json(false, 'Permission denied', 403);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->_json(false, 'WhatsApp is not configured. Please set API credentials.', 400);
            return;
        }
        
        $employee_id = (int)$this->input->post('employee_id');
        $message = trim((string) $this->input->post('message'));
        $template_name = trim((string) $this->input->post('template_name'));
        
        if (!$employee_id || ($message === '' && $template_name === '')) {
            $this->_json(false, 'Employee and a message or template are required.', 400);
            return;
        }
        
        $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code')
            ->from('employees e')
            ->where('e.id', $employee_id)
            ->get()
            ->row();
        
        if (!$employee || !$employee->phone) {
            $this->_json(false, 'Employee not found or phone number not available.', 404);
            return;
        }
        
        $phone = $this->_format_phone($employee->phone);
        $options = array();
        if ($template_name !== '') {
            $options['template_name'] = $template_name;
            $options['language'] = trim((string) $this->input->post('template_language')) ?: 'en_US';
        }
        $result = $this->_send_message($phone, $message, $options);
        
        if (!empty($result['success'])) {
            $this->_log_message($employee_id, $phone, $message, 'sent');
            $this->_json(true, 'WhatsApp message sent successfully.', 200, array('details' => $result));
            return;
        }
        $this->_json(false, !empty($result['error']) ? $result['error'] : 'Send failed.', 400);
    }
    
    /**
     * POST /whatsapp/send-task
     * Send task assignment/update via WhatsApp
     */
    public function send_task() {
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->_json(false, 'Permission denied', 403);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->_json(false, 'WhatsApp is not configured. Please add WhatsApp integration in Settings → API Integrations.', 400);
            return;
        }
        
        $task_id = (int)$this->input->post('task_id');
        $employee_id = (int)$this->input->post('employee_id');
        
        if (!$task_id) {
            $this->_json(false, 'Task ID is required.', 400);
            return;
        }
        
        $task = $this->db->select('t.*, p.name as project_name')
            ->from('tasks t')
            ->join('projects p', 'p.id = t.project_id', 'left')
            ->where('t.id', $task_id)
            ->get()
            ->row();
        
        if (!$task) {
            $this->_json(false, 'Task not found.', 404);
            return;
        }
        
        if ($employee_id) {
            $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.user_id')
                ->from('employees e')
                ->where('e.id', $employee_id)
                ->get()
                ->row();
        } else {
            $assigned_to = (int)$task->assigned_to;
            if ($assigned_to) {
                $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.user_id')
                    ->from('employees e')
                    ->where('e.user_id', $assigned_to)
                    ->get()
                    ->row();
            } else {
                $this->_json(false, 'No employee assigned to this task.', 400);
                return;
            }
        }
        
        if (!$employee || !$employee->phone) {
            $this->_json(false, 'Employee not found or phone number not available.', 404);
            return;
        }
        
        $message = $this->_format_task_message($task, $employee);
        $phone = $this->_format_phone($employee->phone);
        $result = $this->_send_message($phone, $message);
        
        if (!empty($result['success'])) {
            $this->_log_message($employee->id, $phone, $message, 'task', $task_id);
            $this->_json(true, 'Task notification sent via WhatsApp.', 200, array('details' => $result));
            return;
        }
        $this->_json(false, !empty($result['error']) ? $result['error'] : 'Send failed.', 400);
    }
    
    /**
     * POST /whatsapp/send-report
     * Send report summary via WhatsApp
     */
    public function send_report() {
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = ($role_id === 1) || (function_exists('is_admin_group') && is_admin_group());
        if (!$is_admin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->_json(false, 'Permission denied', 403);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->_json(false, 'WhatsApp is not configured. Please add WhatsApp integration in Settings → API Integrations.', 400);
            return;
        }
        
        $employee_id = (int)$this->input->post('employee_id');
        $report_type = $this->input->post('report_type');
        $period = $this->input->post('period');
        
        if (!$employee_id || !$report_type) {
            $this->_json(false, 'Employee ID and report type are required.', 400);
            return;
        }
        
        $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.user_id')
            ->from('employees e')
            ->where('e.id', $employee_id)
            ->get()
            ->row();
        
        if (!$employee || !$employee->phone) {
            $this->_json(false, 'Employee not found or phone number not available.', 404);
            return;
        }
        
        $report_data = $this->_generate_report_summary($employee->user_id, $report_type, $period);
        $message = $this->_format_report_message($employee, $report_type, $period, $report_data);
        $phone = $this->_format_phone($employee->phone);
        $result = $this->_send_message($phone, $message);
        
        if (!empty($result['success'])) {
            $this->_log_message($employee->id, $phone, $message, 'report', null, $report_type);
            $this->_json(true, 'Report sent via WhatsApp.', 200, array('details' => $result));
            return;
        }
        $this->_json(false, !empty($result['error']) ? $result['error'] : 'Send failed.', 400);
    }
    
    private function _json($success, $message, $http = 200, $extra = array())
    {
        $payload = array_merge(array(
            'success' => !empty($success),
            'message' => (string) $message,
        ), is_array($extra) ? $extra : array());
        $this->output
            ->set_status_header((int) $http)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
    }

    /**
     * Send WhatsApp message via Meta Cloud API.
     *
     * @param string $to Phone number
     * @param string $message Message body
     * @param array  $options Optional: template_name, language, integration_id, default_country
     * @return array
     */
    private function _send_message($to, $message, $options = array())
    {
        return send_whatsapp_message($to, $message, $options);
    }

    /**
     * Format phone number (add country code if needed)
     */
    private function _format_phone($phone) {
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If phone already starts with +, return as is
        if (strpos($phone, '+') === 0) {
            return $phone;
        }
        
        // Check if phone number already starts with country code (91 for India)
        if (substr($phone, 0, 2) === '91' && strlen($phone) >= 12) {
            // Already has country code, just add +
            return '+' . $phone;
        }
        
        // Remove leading 0 if present
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // Check again after removing leading 0
        if (substr($phone, 0, 2) === '91' && strlen($phone) >= 12) {
            // Already has country code, just add +
            return '+' . $phone;
        }
        
        // Assume it's Indian number and add +91
        $phone = '+91' . $phone;
        
        return $phone;
    }
    
    /**
     * Format task message
     */
    private function _format_task_message($task, $employee) {
        $template = $this->config->item('task_assignment_template', 'whatsapp') ?: "📋 *New Task*\n\n*{task_title}*\n\n{description}";
        
        $priority_labels = [
            'low' => '🟢 Low',
            'medium' => '🟡 Medium',
            'high' => '🟠 High',
            'urgent' => '🔴 Urgent'
        ];
        
        $status_labels = [
            'pending' => '⏳ Pending',
            'in_progress' => '🔄 In Progress',
            'completed' => '✅ Completed',
            'on_hold' => '⏸️ On Hold'
        ];
        
        $task_priority = isset($task->priority) ? $task->priority : 'medium';
        $task_status = isset($task->status) ? $task->status : 'pending';
        $task_description = isset($task->description) ? $task->description : '';
        $employee_first_name = isset($employee->first_name) ? $employee->first_name : '';
        $employee_last_name = isset($employee->last_name) ? $employee->last_name : '';
        
        $replacements = [
            '{task_title}' => isset($task->title) ? $task->title : 'Untitled Task',
            '{project_name}' => isset($task->project_name) ? $task->project_name : 'N/A',
            '{priority}' => isset($priority_labels[$task_priority]) ? $priority_labels[$task_priority] : 'Medium',
            '{status}' => isset($status_labels[$task_status]) ? $status_labels[$task_status] : 'Pending',
            '{due_date}' => $task->due_date ? date('d M Y', strtotime($task->due_date)) : 'Not set',
            '{description}' => substr(strip_tags($task_description), 0, 200),
            '{task_url}' => site_url('tasks/' . $task->id),
            '{employee_name}' => trim($employee_first_name . ' ' . $employee_last_name)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Format report message
     */
    private function _format_report_message($employee, $report_type, $period, $report_data) {
        $template = $this->config->item('report_template', 'whatsapp') ?: $this->config->item('report_template') ?: "📊 *{report_type} Report*\n\n*Employee:* {employee_name}\n*Period:* {period}\n\n{summary}";
        
        $employee_first_name = isset($employee->first_name) ? $employee->first_name : '';
        $employee_last_name = isset($employee->last_name) ? $employee->last_name : '';
        
        $replacements = [
            '{report_type}' => ucfirst($report_type),
            '{employee_name}' => trim($employee_first_name . ' ' . $employee_last_name),
            '{period}' => ucfirst($period),
            '{summary}' => isset($report_data['summary']) ? $report_data['summary'] : 'No data available',
            '{report_url}' => site_url('reports/' . $report_type . '?employee_id=' . $employee->id)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Generate report summary
     */
    private function _generate_report_summary($user_id, $report_type, $period) {
        $summary = '';
        
        if ($report_type === 'attendance') {
            // Get attendance summary
            $this->db->select('COUNT(*) as total_days, 
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = "leave" THEN 1 ELSE 0 END) as leave_days')
                ->from('attendance')
                ->where('user_id', $user_id);
            
            if ($period === 'week') {
                $this->db->where('date >=', date('Y-m-d', strtotime('-7 days')));
            } elseif ($period === 'month') {
                $this->db->where('date >=', date('Y-m-d', strtotime('first day of this month')));
            }
            
            $stats = $this->db->get()->row();
            
            if ($stats) {
                $summary = "📅 Attendance Summary:\n";
                $summary .= "Present: {$stats->present_days} days\n";
                $summary .= "Absent: {$stats->absent_days} days\n";
                $summary .= "Leave: {$stats->leave_days} days\n";
                $summary .= "Total: {$stats->total_days} days";
            }
        } elseif ($report_type === 'tasks') {
            // Get tasks summary
            $this->db->select('COUNT(*) as total_tasks,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_tasks')
                ->from('tasks')
                ->where('assigned_to', $user_id);
            
            if ($period === 'week') {
                $this->db->where('created_at >=', date('Y-m-d', strtotime('-7 days')));
            } elseif ($period === 'month') {
                $this->db->where('created_at >=', date('Y-m-d', strtotime('first day of this month')));
            }
            
            $stats = $this->db->get()->row();
            
            if ($stats) {
                $summary = "📋 Tasks Summary:\n";
                $summary .= "Total: {$stats->total_tasks}\n";
                $summary .= "✅ Completed: {$stats->completed_tasks}\n";
                $summary .= "🔄 In Progress: {$stats->in_progress_tasks}\n";
                $summary .= "⏳ Pending: {$stats->pending_tasks}";
            }
        }
        
        return ['summary' => $summary];
    }
    
    /**
     * Log WhatsApp message
     */
    private function _log_message($employee_id, $phone, $message, $type = 'manual', $related_id = null, $report_type = null) {
        // Create whatsapp_messages table if it doesn't exist
        if (!$this->db->table_exists('whatsapp_messages')) {
            $this->db->query("CREATE TABLE `whatsapp_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `employee_id` bigint(20) UNSIGNED NOT NULL,
                `phone` varchar(30) NOT NULL,
                `message` text NOT NULL,
                `type` varchar(50) DEFAULT 'manual',
                `related_id` int(11) DEFAULT NULL,
                `report_type` varchar(50) DEFAULT NULL,
                `status` enum('sent','failed') DEFAULT 'sent',
                `sent_by` int(11) DEFAULT NULL,
                `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_employee` (`employee_id`),
                KEY `idx_type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        
        $this->db->insert('whatsapp_messages', [
            'employee_id' => $employee_id,
            'phone' => $phone,
            'message' => $message,
            'type' => $type,
            'related_id' => $related_id,
            'report_type' => $report_type,
            'status' => 'sent',
            'sent_by' => $this->session->userdata('user_id'),
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Check if WhatsApp is configured
     */
    private function _is_configured() {
        $creds = get_whatsapp_credentials();
        return !empty($creds['phone_number_id']) && !empty($creds['access_token']);
    }
}

