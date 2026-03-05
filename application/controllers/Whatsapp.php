<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Controller
 * 
 * Handles WhatsApp message sending for tasks and reports
 * Supports Twilio WhatsApp API
 */
class Whatsapp extends CI_Controller {
    
    private $provider;
    
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library('session');
        $this->load->database();
        
        // Require login
        if (!$this->session->userdata('user_id')) { 
            redirect('auth/login'); 
            exit; 
        }
        
        // Load helper
        $this->load->helper('api_integration');
        
        // Try to get credentials from database first, then fallback to config
        $creds = get_whatsapp_credentials();
        
        if (!empty($creds['account_sid']) && !empty($creds['auth_token'])) {
            // Use database credentials - store in config_data for compatibility
            $this->config_data = [
                'twilio_account_sid' => $creds['account_sid'],
                'twilio_auth_token' => $creds['auth_token'],
                'twilio_whatsapp_from' => $creds['from_number'] ?: 'whatsapp:+14155238886',
                'twilio_content_sid' => $creds['content_sid'] ?: '',
                'whatsapp_provider' => 'twilio'
            ];
            $this->provider = 'twilio';
        } else {
            // Fallback to config file
            $this->load->config('whatsapp', true);
            // Access config items from the whatsapp section
            $this->config_data = [];
            $this->provider = $this->config->item('whatsapp_provider', 'whatsapp') ?: $this->config->item('whatsapp_provider') ?: 'twilio';
        }
    }
    
    /**
     * GET /whatsapp
     * Display WhatsApp sending interface
     */
    public function index() {
        // Check permission - Super Admin (role_id 1) or users with whatsapp permission
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            show_error('You do not have permission to access WhatsApp.', 403);
        }
        
        // Get employees with phone numbers
        $employees = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.department, u.email')
            ->from('employees e')
            ->join('users u', 'u.id = e.user_id', 'left')
            ->where('e.phone IS NOT NULL')
            ->where('e.phone !=', '')
            ->order_by('e.first_name', 'ASC')
            ->get()
            ->result();
        
        $data = [
            'employees' => $employees,
            'config_configured' => $this->_is_configured(),
            'provider' => $this->provider
        ];
        
        $this->load->view('whatsapp/index', $data);
    }
    
    /**
     * POST /whatsapp/send
     * Send WhatsApp message
     */
    public function send() {
        // Check permission - Super Admin (role_id 1) or users with whatsapp permission
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'WhatsApp is not configured. Please set API credentials.']);
            return;
        }
        
        $employee_id = (int)$this->input->post('employee_id');
        $message = trim($this->input->post('message'));
        
        if (!$employee_id || !$message) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Employee ID and message are required']);
            return;
        }
        
        // Get employee phone number
        $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code')
            ->from('employees e')
            ->where('e.id', $employee_id)
            ->get()
            ->row();
        
        if (!$employee || !$employee->phone) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found or phone number not available']);
            return;
        }
        
        // Format phone number (add country code if needed)
        $phone = $this->_format_phone($employee->phone);
        
        // Send WhatsApp message
        $result = $this->_send_message($phone, $message);
        
        if ($result['success']) {
            // Log the message
            $this->_log_message($employee_id, $phone, $message, 'sent');
            $status_msg = 'WhatsApp message sent successfully';
            if (isset($result['status']) && $result['status'] === 'queued') {
                $status_msg .= ' (Message queued - recipient may need to join Twilio Sandbox)';
            }
            echo json_encode(['success' => true, 'message' => $status_msg, 'details' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    }
    
    /**
     * POST /whatsapp/send-task
     * Send task assignment/update via WhatsApp
     */
    public function send_task() {
        // Check permission - Super Admin (role_id 1) or users with whatsapp permission
        $role_id = (int)$this->session->userdata('role_id');
        $is_superadmin = ($role_id === 1);
        if (!$is_superadmin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'WhatsApp is not configured. Please add WhatsApp integration in Settings → API Integrations.']);
            return;
        }
        
        $task_id = (int)$this->input->post('task_id');
        $employee_id = (int)$this->input->post('employee_id');
        
        if (!$task_id) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Task ID is required']);
            return;
        }
        
        // Get task details
        $task = $this->db->select('t.*, p.name as project_name')
            ->from('tasks t')
            ->join('projects p', 'p.id = t.project_id', 'left')
            ->where('t.id', $task_id)
            ->get()
            ->row();
        
        if (!$task) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'message' => 'Task not found']);
            return;
        }
        
        // Get employee if specified, otherwise get assigned user
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
                $this->output->set_status_header(400);
                echo json_encode(['success' => false, 'message' => 'No employee assigned to this task']);
                return;
            }
        }
        
        if (!$employee || !$employee->phone) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found or phone number not available']);
            return;
        }
        
        // Format message
        $message = $this->_format_task_message($task, $employee);
        
        // Format phone number
        $phone = $this->_format_phone($employee->phone);
        
        // Send WhatsApp message
        $result = $this->_send_message($phone, $message);
        
        if ($result['success']) {
            // Log the message
            $this->_log_message($employee->id, $phone, $message, 'task', $task_id);
            $status_msg = 'Task notification sent via WhatsApp';
            if (isset($result['status']) && $result['status'] === 'queued') {
                $status_msg .= ' (Message queued - recipient may need to join Twilio Sandbox)';
            }
            echo json_encode(['success' => true, 'message' => $status_msg, 'details' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    }
    
    /**
     * POST /whatsapp/send-report
     * Send report summary via WhatsApp
     */
    public function send_report() {
        // Check permission - allow admin or users with whatsapp permission
        $role_id = (int)$this->session->userdata('role_id');
        $is_admin = ($role_id === 1) || (function_exists('is_admin_group') && is_admin_group());
        if (!$is_admin && (!function_exists('has_module_access') || !has_module_access('whatsapp'))) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if (!$this->_is_configured()) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'WhatsApp is not configured. Please add WhatsApp integration in Settings → API Integrations.']);
            return;
        }
        
        $employee_id = (int)$this->input->post('employee_id');
        $report_type = $this->input->post('report_type'); // 'attendance', 'tasks', etc.
        $period = $this->input->post('period'); // 'week', 'month', etc.
        
        if (!$employee_id || !$report_type) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Employee ID and report type are required']);
            return;
        }
        
        // Get employee
        $employee = $this->db->select('e.id, e.first_name, e.last_name, e.phone, e.emp_code, e.user_id')
            ->from('employees e')
            ->where('e.id', $employee_id)
            ->get()
            ->row();
        
        if (!$employee || !$employee->phone) {
            $this->output->set_status_header(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found or phone number not available']);
            return;
        }
        
        // Generate report summary
        $report_data = $this->_generate_report_summary($employee->user_id, $report_type, $period);
        
        // Format message
        $message = $this->_format_report_message($employee, $report_type, $period, $report_data);
        
        // Format phone number
        $phone = $this->_format_phone($employee->phone);
        
        // Send WhatsApp message
        $result = $this->_send_message($phone, $message);
        
        if ($result['success']) {
            // Log the message
            $this->_log_message($employee->id, $phone, $message, 'report', null, $report_type);
            $status_msg = 'Report sent via WhatsApp';
            if (isset($result['status']) && $result['status'] === 'queued') {
                $status_msg .= ' (Message queued - recipient may need to join Twilio Sandbox)';
            }
            echo json_encode(['success' => true, 'message' => $status_msg, 'details' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    }
    
    /**
     * Send WhatsApp message via Twilio
     * @param string $to Phone number
     * @param string $message Message body (or template message if using ContentSid)
     * @param array $options Optional: ['content_sid' => '...', 'content_variables' => {...}]
     */
    private function _send_message($to, $message, $options = []) {
        if ($this->provider === 'twilio') {
            return $this->_send_via_twilio($to, $message, $options);
        }
        
        return ['success' => false, 'error' => 'Unsupported provider'];
    }
    
    /**
     * Send message via Twilio WhatsApp API
     * Supports both plain text (Body) and template messages (ContentSid + ContentVariables)
     */
    private function _send_via_twilio($to, $message, $options = []) {
        // Get credentials from database only
        $creds = get_whatsapp_credentials();
        
        if (empty($creds['account_sid']) || empty($creds['auth_token'])) {
            return ['success' => false, 'error' => 'WhatsApp credentials not found in database. Please configure in API Integrations.'];
        }
        
        $account_sid = $creds['account_sid'];
        $auth_token = $creds['auth_token'];
        $from = $creds['from_number'] ?: 'whatsapp:+14155238886';
        
        if (empty($account_sid) || empty($auth_token)) {
            return ['success' => false, 'error' => 'Twilio credentials not configured'];
        }
        
        // Format phone number for WhatsApp (add whatsapp: prefix)
        if (strpos($to, 'whatsapp:') === false) {
            $to = 'whatsapp:' . $to;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        // Check if using template message (ContentSid) or plain text (Body)
        $data = [
            'From' => $from,
            'To' => $to
        ];
        
        // Support for template messages via ContentSid
        if (isset($options['content_sid']) && !empty($options['content_sid'])) {
            $data['ContentSid'] = $options['content_sid'];
            // ContentVariables should be a JSON string
            if (isset($options['content_variables']) && !empty($options['content_variables'])) {
                if (is_array($options['content_variables'])) {
                    $data['ContentVariables'] = json_encode($options['content_variables']);
                } else {
                    $data['ContentVariables'] = $options['content_variables'];
                }
            }
        } else {
            // Use plain text Body
            $data['Body'] = $message;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        // SSL Verification - Disabled for local development
        // WARNING: In production, enable SSL verification for security
        // Set to false only if you're in a local/dev environment without proper CA certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return ['success' => false, 'error' => 'cURL Error: ' . $curl_error];
        }
        
        // Log the response for debugging
        log_message('debug', 'Twilio API Response - HTTP Code: ' . $http_code);
        log_message('debug', 'Twilio API Response Body: ' . $response);
        
        $response_data = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            // Check if Twilio returned an error in the response body
            if (isset($response_data['status']) && in_array($response_data['status'], ['failed', 'undelivered'])) {
                $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Message failed to deliver';
                if (isset($response_data['error_code'])) {
                    $error_msg .= ' (Error Code: ' . $response_data['error_code'] . ')';
                }
                return ['success' => false, 'error' => $error_msg];
            }
            
            // Check for common Twilio error messages
            if (isset($response_data['code']) && $response_data['code'] != 0) {
                $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Twilio API Error';
                return ['success' => false, 'error' => $error_msg . ' (Code: ' . $response_data['code'] . ')'];
            }
            
            // Success - message was accepted by Twilio
            return [
                'success' => true,
                'message_sid' => isset($response_data['sid']) ? $response_data['sid'] : null,
                'status' => isset($response_data['status']) ? $response_data['status'] : 'queued'
            ];
        } else {
            // HTTP error
            $error_message = 'HTTP ' . $http_code;
            if (isset($response_data['message'])) {
                $error_message .= ': ' . $response_data['message'];
            }
            if (isset($response_data['code'])) {
                $error_message .= ' (Code: ' . $response_data['code'] . ')';
            }
            if (isset($response_data['more_info'])) {
                $error_message .= ' - More info: ' . $response_data['more_info'];
            }
            return ['success' => false, 'error' => $error_message];
        }
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
        if ($this->provider === 'twilio') {
            // First check database
            $creds = get_whatsapp_credentials();
            if (!empty($creds['account_sid']) && !empty($creds['auth_token'])) {
                return true;
            }
            
            // Fallback to config
            $account_sid = isset($this->config_data['twilio_account_sid']) ? $this->config_data['twilio_account_sid'] : '';
            $auth_token = isset($this->config_data['twilio_auth_token']) ? $this->config_data['twilio_auth_token'] : '';
            
            if (empty($account_sid)) {
                $account_sid = $this->config->item('twilio_account_sid', 'whatsapp');
            }
            if (empty($auth_token)) {
                $auth_token = $this->config->item('twilio_auth_token', 'whatsapp');
            }
            
            // If not found in section, try without section (merged config)
            if (empty($account_sid)) {
                $account_sid = $this->config->item('twilio_account_sid');
            }
            if (empty($auth_token)) {
                $auth_token = $this->config->item('twilio_auth_token');
            }
            
            // Also check environment variables as fallback
            if (empty($account_sid)) {
                $account_sid = getenv('TWILIO_ACCOUNT_SID') ?: '';
            }
            if (empty($auth_token)) {
                $auth_token = getenv('TWILIO_AUTH_TOKEN') ?: '';
            }
            
            return !empty($account_sid) && !empty($auth_token);
        }
        return false;
    }
}

