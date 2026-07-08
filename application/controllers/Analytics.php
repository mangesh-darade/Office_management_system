<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Ai_model');
        $this->load->model('Integration_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'form', 'permission']);

        // Skip RBAC for public calendar feed method
        $method = (string)$this->router->fetch_method();
        if ($method !== 'calendar_feed') {
            // RBAC Audit: Centralized module access check
            require_module_access('analytics', true);
        }
    }

    public function index() {
        $data['title'] = 'AI Analytics & Integrations';
        
        // 1. Get Attrition Risks (Simplified: Top 5 Users)
        $users = $this->db->get('users')->result();
        $risks = [];
        foreach ($users as $user) {
            $prediction = $this->Ai_model->predict_attrition($user->id);
            if ($prediction['risk_score'] > 50) {
                $prediction['name'] = $user->username; 
                $risks[] = $prediction;
            }
        }
        
        // Sort by risk score desc
        usort($risks, function($a, $b) {
            return $b['risk_score'] - $a['risk_score'];
        });
        $data['attrition_risks'] = array_slice($risks, 0, 5);

        // 2. Attendance Forecast (Current User)
        $data['my_forecast'] = $this->Ai_model->forecast_attendance($this->session->userdata('user_id'));

        // 3. Integration Status (Mock check)
        $data['integrations'] = [
            'slack' => $this->session->userdata('slack_webhook') ? true : false,
            'chat' => true // Native Chat is always active
        ];

        // 4. Get Users for Quick Call
        $this->load->model('Chat_model');
        $data['chat_users'] = $this->Chat_model->list_users_for_select();

        $this->load->view('analytics/dashboard', $data);
    }

    // --- INTEGRATION CONFIGURATION ---

    public function save_integrations() {
        $slack = $this->input->post('slack_webhook');
        // Zoom Removed
        
        $this->session->set_userdata('slack_webhook', $slack);

        $this->session->set_flashdata('success', 'Integration settings saved.');
        redirect('analytics');
    }

    // --- QUICK CALL ---

    public function start_quick_call() {
        $target_user = (int)$this->input->post('target_user');
        if (!$target_user) redirect('analytics');

        $current_user = (int)$this->session->userdata('user_id');
        
        $this->load->model('Chat_model');
        
        // Check if starting call with self
        if ($target_user === $current_user) {
            $this->session->set_flashdata('error', 'Cannot call yourself.');
            redirect('analytics');
            return;
        }

        // Get/Create DM
        $conv_id = $this->Chat_model->start_dm($current_user, $target_user);
        
        // Generate Call Link
        $link = $this->Integration_model->get_chat_call_link($conv_id);
        
        redirect($link);
    }

    // --- AI TOOLS ---

    public function analyze_feedback() {
        $text = $this->input->post('feedback_text');
        if (!$text) redirect('analytics');

        $analysis = $this->Ai_model->analyze_sentiment($text);
        
        // Flash the result to show on dashboard
        $this->session->set_flashdata('sentiment_result', $analysis);
        $this->session->set_flashdata('analyzed_text', $text);
        
        redirect('analytics');
    }

    public function parse_resume() {
        if (!empty($_FILES['resume_file']['name'])) {
            if (!isset($_FILES['resume_file']['tmp_name']) || !is_uploaded_file($_FILES['resume_file']['tmp_name'])) {
                $this->session->set_flashdata('error', 'Invalid file upload.');
                redirect('analytics');
                return;
            }
            if ((int) $_FILES['resume_file']['size'] > 5242880) {
                $this->session->set_flashdata('error', 'Resume file is too large (max 5 MB).');
                redirect('analytics');
                return;
            }

            $content = file_get_contents($_FILES['resume_file']['tmp_name']);
            // Strip binary chars in case it's a PDF upload attempt (basic text recovery)
            $clean_content = preg_replace('/[^[:print:]\r\n]/', '', $content);
            
            $parsed = $this->Ai_model->parse_resume_text($clean_content);
            
            $this->session->set_flashdata('resume_result', $parsed);
        } else {
             $this->session->set_flashdata('error', 'Please upload a file.');
        }
        redirect('analytics');
    }

    // --- CALENDAR SYNC ---

    public function calendar_feed($user_id_hash) {
        // Find user by hash (mocking security)
        // Ensure this method is public and doesn't require session login for GCal to read it
        
        // Mock Events
        $events = [
            [
                'id' => 1,
                'title' => 'Team Meeting',
                'description' => 'Weekly Sync',
                'start' => date('Y-m-d 10:00:00'),
                'end' => date('Y-m-d 11:00:00')
            ],
            [
                'id' => 2,
                'title' => 'Project Deadline',
                'description' => 'Submit Deliverables',
                'start' => date('Y-m-d 17:00:00', strtotime('+2 days')),
                'end' => date('Y-m-d 18:00:00', strtotime('+2 days'))
            ]
        ];

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');
        
        echo $this->Integration_model->generate_ical_feed($events);
    }
}
