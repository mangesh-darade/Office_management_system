<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Settings Controller
 * 
 * Manages email notification preferences for all modules
 */
class Email_settings extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        $this->ensure_schema();
        
        // RBAC Audit: Centralized module access check
        // Check for specific methods that users should access for their own prefs
        $method = (string)$this->router->fetch_method();
        if ($method !== 'user_preferences') {
            require_module_access(['email_settings', 'settings'], true);
        } else {
            // Still require login for user preferences
            if (!(int)$this->session->userdata('user_id')) { 
                redirect('auth/login'); 
            }
        }
    }

    private function ensure_schema() {
        // Create email_settings table
        if (!$this->db->table_exists('email_settings')) {
            $sql = "CREATE TABLE `email_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(50) NOT NULL,
                `event_type` varchar(50) NOT NULL,
                `is_enabled` tinyint(1) DEFAULT 1,
                `recipient_type` varchar(20) DEFAULT 'assignee',
                `custom_recipients` text DEFAULT NULL,
                `email_template` text DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_module_event` (`module`, `event_type`),
                KEY `idx_module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        
        // Create user_email_preferences table
        if (!$this->db->table_exists('user_email_preferences')) {
            $sql = "CREATE TABLE `user_email_preferences` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `module` varchar(50) NOT NULL,
                `event_type` varchar(50) NOT NULL,
                `is_enabled` tinyint(1) DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_user_module_event` (`user_id`, `module`, `event_type`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        
        // Insert default email settings
        $this->insert_default_settings();
    }

    private function insert_default_settings() {
        $default_settings = [
            // Tasks module
            ['module' => 'tasks', 'event_type' => 'created', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'updated', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'status_changed', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'comment_added', 'recipient_type' => 'assignee'],
            ['module' => 'tasks', 'event_type' => 'daily_summary', 'recipient_type' => 'assignee'],
            
            // Projects module
            ['module' => 'projects', 'event_type' => 'created', 'recipient_type' => 'members'],
            ['module' => 'projects', 'event_type' => 'updated', 'recipient_type' => 'members'],
            ['module' => 'projects', 'event_type' => 'member_added', 'recipient_type' => 'new_member'],
            ['module' => 'projects', 'event_type' => 'status_changed', 'recipient_type' => 'members'],
            
            // Leave Requests module
            ['module' => 'leave_requests', 'event_type' => 'submitted', 'recipient_type' => 'manager'],
            ['module' => 'leave_requests', 'event_type' => 'approved', 'recipient_type' => 'employee'],
            ['module' => 'leave_requests', 'event_type' => 'rejected', 'recipient_type' => 'employee'],
            ['module' => 'leave_requests', 'event_type' => 'cancelled', 'recipient_type' => 'manager'],
            
            // Attendance module
            ['module' => 'attendance', 'event_type' => 'check_in', 'recipient_type' => 'self'],
            ['module' => 'attendance', 'event_type' => 'check_out', 'recipient_type' => 'self'],
            ['module' => 'attendance', 'event_type' => 'absent', 'recipient_type' => 'manager'],
            ['module' => 'attendance', 'event_type' => 'daily_summary', 'recipient_type' => 'admin'],
            
            // Announcements module
            ['module' => 'announcements', 'event_type' => 'published', 'recipient_type' => 'target_roles'],
            ['module' => 'announcements', 'event_type' => 'updated', 'recipient_type' => 'target_roles'],
            
            // Employees module
            ['module' => 'employees', 'event_type' => 'created', 'recipient_type' => 'admin'],
            ['module' => 'employees', 'event_type' => 'updated', 'recipient_type' => 'self'],
            ['module' => 'employees', 'event_type' => 'deleted', 'recipient_type' => 'admin'],
            
            // Timesheets module
            ['module' => 'timesheets', 'event_type' => 'submitted', 'recipient_type' => 'manager'],
            ['module' => 'timesheets', 'event_type' => 'approved', 'recipient_type' => 'employee'],
            ['module' => 'timesheets', 'event_type' => 'rejected', 'recipient_type' => 'employee'],
            ['module' => 'timesheets', 'event_type' => 'weekly_summary', 'recipient_type' => 'employee'],
            
            // Payroll module
            ['module' => 'payroll', 'event_type' => 'generated', 'recipient_type' => 'employee'],
            ['module' => 'payroll', 'event_type' => 'updated', 'recipient_type' => 'employee'],
            ['module' => 'payroll', 'event_type' => 'disbursed', 'recipient_type' => 'employee'],

            // Recruitment module
            ['module' => 'recruitment', 'event_type' => 'interview_scheduled', 'recipient_type' => 'candidate'],
        ];

        foreach ($default_settings as $setting) {
            $this->db->where('module', $setting['module']);
            $this->db->where('event_type', $setting['event_type']);
            $exists = $this->db->get('email_settings')->row();
            
            if (!$exists) {
                // Initialize default template if missing
                if ($setting['module'] === 'recruitment' && $setting['event_type'] === 'interview_scheduled') {
                    $setting['email_template'] = "Dear {candidate_name},\n\nYour interview for the position of {job_title} has been scheduled.\nDate: {date}\nType: {type}\n\nPlease be available.\n\nBest Regards,\nHR Team";
                }
                $this->db->insert('email_settings', $setting);
            }
        }
    }

    public function index() {
        // Ensure new settings are loaded
        $this->insert_default_settings();

        $settings = $this->db->order_by('module', 'ASC')->order_by('event_type', 'ASC')->get('email_settings')->result();
        
        // Group settings by module
        $grouped_settings = [];
        foreach ($settings as $setting) {
            $grouped_settings[$setting->module][] = $setting;
        }
        
        $this->load->view('email_settings/index', [
            'grouped_settings' => $grouped_settings,
            'modules' => $this->get_module_info()
        ]);
    }

    public function edit_template($id) {
        $setting = $this->db->where('id', (int)$id)->get('email_settings')->row();
        if (!$setting) show_404();

        if ($this->input->method() === 'post') {
            $data = [
                'email_template' => $this->input->post('email_template'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->db->where('id', (int)$id)->update('email_settings', $data);
            $this->session->set_flashdata('success', 'Email template updated successfully');
            redirect('email-settings');
        }

        $this->load->view('email_settings/edit_template', ['setting' => $setting]);
    }

    public function update() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $settings = $this->input->post('settings');
        
        foreach ($settings as $id => $data) {
            $this->db->where('id', (int)$id);
            $this->db->update('email_settings', [
                'is_enabled' => isset($data['is_enabled']) ? 1 : 0,
                'recipient_type' => $data['recipient_type'],
                'custom_recipients' => !empty($data['custom_recipients']) ? $data['custom_recipients'] : null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->session->set_flashdata('success', 'Email settings updated successfully');
        redirect('email-settings');
    }

    public function user_preferences() {
        $user_id = (int)$this->session->userdata('user_id');
        
        if ($this->input->method() === 'post') {
            $preferences = $this->input->post('preferences');
            
            foreach ($preferences as $module => $events) {
                foreach ($events as $event_type => $enabled) {
                    $this->db->where('user_id', $user_id);
                    $this->db->where('module', $module);
                    $this->db->where('event_type', $event_type);
                    $exists = $this->db->get('user_email_preferences')->row();
                    
                    if ($exists) {
                        $this->db->where('id', $exists->id);
                        $this->db->update('user_email_preferences', [
                            'is_enabled' => $enabled ? 1 : 0,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        $this->db->insert('user_email_preferences', [
                            'user_id' => $user_id,
                            'module' => $module,
                            'event_type' => $event_type,
                            'is_enabled' => $enabled ? 1 : 0
                        ]);
                    }
                }
            }

            $this->session->set_flashdata('success', 'Email preferences updated successfully');
            redirect('email-settings/user-preferences');
        }

        // Get user's current preferences
        $user_preferences = [];
        $preferences = $this->db->where('user_id', $user_id)->get('user_email_preferences')->result();
        foreach ($preferences as $pref) {
            $user_preferences[$pref->module][$pref->event_type] = $pref->is_enabled;
        }

        // Get all available settings for the form
        $all_settings = $this->db->order_by('module', 'ASC')->order_by('event_type', 'ASC')->get('email_settings')->result();
        $grouped_settings = [];
        foreach ($all_settings as $setting) {
            $grouped_settings[$setting->module][] = $setting;
        }

        $this->load->view('email_settings/user_preferences', [
            'grouped_settings' => $grouped_settings,
            'user_preferences' => $user_preferences,
            'modules' => $this->get_module_info()
        ]);
    }

    private function get_module_info() {
        return [
            'tasks' => [
                'name' => 'Tasks',
                'description' => 'Task management and assignments',
                'events' => [
                    'created' => 'New task created',
                    'updated' => 'Task details updated',
                    'status_changed' => 'Task status changed',
                    'comment_added' => 'Comment added to task',
                    'daily_summary' => 'Daily task summary'
                ]
            ],
            'projects' => [
                'name' => 'Projects',
                'description' => 'Project management and collaboration',
                'events' => [
                    'created' => 'New project created',
                    'updated' => 'Project details updated',
                    'member_added' => 'New member added',
                    'status_changed' => 'Project status changed'
                ]
            ],
            'leave_requests' => [
                'name' => 'Leave Requests',
                'description' => 'Employee leave management',
                'events' => [
                    'submitted' => 'Leave request submitted',
                    'approved' => 'Leave request approved',
                    'rejected' => 'Leave request rejected',
                    'cancelled' => 'Leave request cancelled'
                ]
            ],
            'attendance' => [
                'name' => 'Attendance',
                'description' => 'Employee attendance tracking',
                'events' => [
                    'check_in' => 'Employee checked in',
                    'check_out' => 'Employee checked out',
                    'absent' => 'Employee marked absent',
                    'daily_summary' => 'Daily attendance summary'
                ]
            ],
            'announcements' => [
                'name' => 'Announcements',
                'description' => 'Company announcements and notices',
                'events' => [
                    'published' => 'Announcement published',
                    'updated' => 'Announcement updated'
                ]
            ],
            'employees' => [
                'name' => 'Employees',
                'description' => 'Employee management and records',
                'events' => [
                    'created' => 'New employee added',
                    'updated' => 'Employee details updated',
                    'deleted' => 'Employee record deleted'
                ]
            ],
            'timesheets' => [
                'name' => 'Timesheets',
                'description' => 'Time tracking and timesheet management',
                'events' => [
                    'submitted' => 'Timesheet submitted',
                    'approved' => 'Timesheet approved',
                    'rejected' => 'Timesheet rejected',
                    'weekly_summary' => 'Weekly timesheet summary'
                ]
            ],
            'payroll' => [
                'name' => 'Payroll',
                'description' => 'Payroll processing and payments',
                'events' => [
                    'generated' => 'Payroll generated',
                    'updated' => 'Payroll details updated',
                    'disbursed' => 'Salary disbursed'
                ]
            ],
            'recruitment' => [
                'name' => 'Recruitment',
                'description' => 'Job applications and interviews',
                'events' => [
                    'interview_scheduled' => 'Interview Scheduled'
                ]
            ]
        ];
    }

    public function test_email() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $module = $this->input->post('module');
        $event_type = $this->input->post('event_type');
        $test_email = $this->input->post('test_email');

        if (!$test_email || !$module || !$event_type) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Missing required parameters']));
        }

        // Load email helper
        $this->load->helper('email');

        // Create test data
        $test_data = $this->get_test_data($module, $event_type);

        // Send test email
        $subject = "Test Email: {$module} - {$event_type}";
        $sent = send_task_notification($test_email, $subject, $test_data, 'test');

        if ($sent) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => true, 'message' => 'Test email sent successfully']));
        } else {
            return $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to send test email']));
        }
    }

    private function get_test_data($module, $event_type) {
        // Create a generic test object
        $test_data = new stdClass();
        $test_data->id = 1;
        $test_data->title = "Test {$module} {$event_type}";
        $test_data->description = "This is a test email for {$module} - {$event_type} event.";
        $test_data->status = 'pending';
        $test_data->priority = 'medium';
        $test_data->project_name = 'Test Project';
        $test_data->due_date = date('Y-m-d', strtotime('+7 days'));
        $test_data->created_at = date('Y-m-d H:i:s');

        return $test_data;
    }
}
