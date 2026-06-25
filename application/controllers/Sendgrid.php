<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SendGrid Controller
 * 
 * Provides API-based email sending using SendGrid's REST API
 * This is an alternative to SMTP-based email (Mail controller)
 * 
 * Features:
 * - Send emails via SendGrid API
 * - Test email functionality
 * - Support for attachments, CC, BCC
 * - HTML and plain text emails
 */
class Sendgrid extends CI_Controller {
    
    private $api_key;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library('session');
        
        // RBAC Audit: Centralized module access check
        require_module_access(['sendgrid', 'settings'], true);
        
        // Load database and helper
        $this->load->database();
        $this->load->helper('api_integration');
        
        // Get credentials from database only
        $creds = get_sendgrid_credentials();
        
        if (!empty($creds['api_key'])) {
            // Use database credentials
            $this->api_key = $creds['api_key'];
            $this->from_email = $creds['from_email'] ?: '';
            $this->from_name = $creds['from_name'] ?: '';
        } else {
            // No credentials found in database
            $this->api_key = '';
            $this->from_email = '';
            $this->from_name = '';
        }
    }
    
    /**
     * GET /sendgrid
     * Display SendGrid email sending interface
     */
    public function index() {
        $data = [
            'api_key_configured' => !empty($this->api_key),
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'api_key_preview' => $this->api_key ? substr($this->api_key, 0, 8) . '...' : 'Not configured'
        ];
        
        $this->load->view('sendgrid/index', $data);
    }
    
    /**
     * POST /sendgrid/send
     * Send email via SendGrid API
     */
    public function send() {
        // Check if API key is configured
        if (empty($this->api_key)) {
            $this->session->set_flashdata('error', 'SendGrid API key is not configured. Please add SendGrid integration in Settings → API Integrations.');
            redirect('sendgrid');
            return;
        }
        
        // Get form data
        $to = trim((string)$this->input->post('to'));
        $cc = trim((string)$this->input->post('cc'));
        $bcc = trim((string)$this->input->post('bcc'));
        $subject = trim((string)$this->input->post('subject'));
        $message = (string)$this->input->post('message');
        $is_html = (bool)$this->input->post('is_html');
        
        // Validation
        if (!$to || !$subject || !$message) {
            $this->session->set_flashdata('error', 'To, Subject and Message are required.');
            redirect('sendgrid');
            return;
        }
        
        // Validate email addresses
        if (!$this->_is_valid_email($to)) {
            $this->session->set_flashdata('error', 'Invalid To email address.');
            redirect('sendgrid');
            return;
        }
        
        $cc_emails = [];
        if ($cc) {
            $cc_emails = $this->_split_emails($cc);
            foreach ($cc_emails as $em) {
                if (!$this->_is_valid_email($em)) {
                    $this->session->set_flashdata('error', 'Invalid CC address: ' . esc_view($em));
                    redirect('sendgrid');
                    return;
                }
            }
        }
        
        $bcc_emails = [];
        if ($bcc) {
            $bcc_emails = $this->_split_emails($bcc);
            foreach ($bcc_emails as $em) {
                if (!$this->_is_valid_email($em)) {
                    $this->session->set_flashdata('error', 'Invalid BCC address: ' . esc_view($em));
                    redirect('sendgrid');
                    return;
                }
            }
        }
        
        // Prepare SendGrid API request
        $email_data = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->from_email,
                'name' => $this->from_name
            ],
            'content' => [
                [
                    'type' => $is_html ? 'text/html' : 'text/plain',
                    'value' => $message
                ]
            ]
        ];
        
        // Add CC
        if (!empty($cc_emails)) {
            $email_data['personalizations'][0]['cc'] = array_map(function($email) {
                return ['email' => $email];
            }, $cc_emails);
        }
        
        // Add BCC
        if (!empty($bcc_emails)) {
            $email_data['personalizations'][0]['bcc'] = array_map(function($email) {
                return ['email' => $email];
            }, $bcc_emails);
        }
        
        // Handle attachment
        if (!empty($_FILES['attachment']) && isset($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
            $file_path = $_FILES['attachment']['tmp_name'];
            $file_name = isset($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : 'attachment';
            $file_content = base64_encode(file_get_contents($file_path));
            $file_type = isset($_FILES['attachment']['type']) ? $_FILES['attachment']['type'] : 'application/octet-stream';
            
            $email_data['attachments'] = [
                [
                    'content' => $file_content,
                    'filename' => $file_name,
                    'type' => $file_type,
                    'disposition' => 'attachment'
                ]
            ];
        }
        
        // Send via SendGrid API
        $result = $this->_send_via_api($email_data);
        
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Email sent successfully to ' . $to . ' via SendGrid API');
        } else {
            $this->session->set_flashdata('error', 'Failed to send email: ' . $result['message']);
        }
        
        redirect('sendgrid');
    }
    
    /**
     * GET /sendgrid/test
     * Send a test email to the logged-in user
     */
    public function test() {
        // Check if API key is configured
        if (empty($this->api_key)) {
            $this->session->set_flashdata('error', 'SendGrid API key is not configured. Please add SendGrid integration in Settings → API Integrations.');
            redirect('sendgrid');
            return;
        }
        
        $to = $this->session->userdata('email');
        if (!$to) {
            $to = $this->from_email;
        }
        
        $email_data = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => 'SendGrid Test Email - ' . date('Y-m-d H:i:s')
                ]
            ],
            'from' => [
                'email' => $this->from_email,
                'name' => $this->from_name
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => '<h2>SendGrid Test Email</h2><p>Hello,</p><p>This is a test email sent via SendGrid API.</p><p>If you received this email, your SendGrid configuration is working correctly!</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>'
                ]
            ]
        ];
        
        $result = $this->_send_via_api($email_data);
        
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Test email sent successfully to ' . $to . ' via SendGrid API');
        } else {
            $this->session->set_flashdata('error', 'Failed to send test email: ' . $result['message']);
        }
        
        redirect('sendgrid');
    }
    
    /**
     * Send email via SendGrid REST API
     * 
     * @param array $email_data Email data in SendGrid format
     * @return array ['success' => bool, 'message' => string]
     */
    private function _send_via_api($email_data) {
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // SSL verification stays ON outside local development (prevents MITM on API traffic).
        $ssl_verify = (ENVIRONMENT !== 'development');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl_verify ? 2 : 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $curl_error
            ];
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } else {
            $error_data = json_decode($response, true);
            $error_message = 'HTTP ' . $http_code;
            if (isset($error_data['errors']) && is_array($error_data['errors'])) {
                $error_messages = array_map(function($err) {
                    return isset($err['message']) ? $err['message'] : '';
                }, $error_data['errors']);
                $error_message .= ': ' . implode(', ', $error_messages);
            } else {
                $error_message .= ': ' . ($response ?: 'Unknown error');
            }
            
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }
    
    /**
     * Split comma-separated email list
     * 
     * @param string $list Comma-separated email addresses
     * @return array Array of email addresses
     */
    private function _split_emails($list) {
        $parts = array_filter(array_map(function($s) {
            return trim($s);
        }, explode(',', (string)$list)));
        return $parts;
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email address
     * @return bool True if valid
     */
    private function _is_valid_email($email) {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

