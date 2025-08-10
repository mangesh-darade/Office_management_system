<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mail extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url','form']);
        // Load email config BEFORE initializing email library
        $this->config->load('email');
        $this->load->library(['session','email']);
        // Require login
        if (!$this->session->userdata('user_id')) { redirect('auth/login'); exit; }
    }

    // Simple UI to send a test email
    public function index() {
        $this->load->view('mail/index');
    }

    // POST /mail/send
    public function send() {
        $to      = trim((string)$this->input->post('to'));
        $cc      = trim((string)$this->input->post('cc'));
        $bcc     = trim((string)$this->input->post('bcc'));
        $subject = trim((string)$this->input->post('subject'));
        $message = (string)$this->input->post('message');

        if (!$to || !$subject || !$message) {
            $this->session->set_flashdata('error', 'To, Subject and Message are required.');
            redirect('mail');
            return;
        }

        // Validate email addresses
        if (!$this->_is_valid_email($to)) {
            $this->session->set_flashdata('error', 'Invalid To email address.');
            redirect('mail');
            return;
        }
        if ($cc) {
            foreach ($this->_split_emails($cc) as $em) {
                if (!$this->_is_valid_email($em)) { $this->session->set_flashdata('error', 'Invalid CC address: '.htmlspecialchars($em)); redirect('mail'); return; }
            }
        }
        if ($bcc) {
            foreach ($this->_split_emails($bcc) as $em) {
                if (!$this->_is_valid_email($em)) { $this->session->set_flashdata('error', 'Invalid BCC address: '.htmlspecialchars($em)); redirect('mail'); return; }
            }
        }

        $from = $this->config->item('smtp_user');
        if (!$from) { $from = 'sateri.mangesh@gmail.com'; }

        // Warn if SMTP_PASS is not configured (check both config and email library)
        $smtpPassCfg = (string)$this->config->item('smtp_pass');
        $smtpPassLib = (isset($this->email->smtp_pass) ? (string)$this->email->smtp_pass : '');
        if ($smtpPassCfg === '' && $smtpPassLib === '') {
            $fromDbg = (string)$this->config->item('smtp_user') ?: 'not-set';
            $this->session->set_flashdata('error', 'SMTP password not set. Set SMTP_PASS via env or in application/config/email.php for user: '.$fromDbg);
            redirect('mail');
            return;
        }

        $this->email->clear(true);
        $this->email->from($from, 'System Mailer');
        if ($this->session->userdata('email')) {
            $this->email->reply_to($this->session->userdata('email'));
        }
        $this->email->to($to);
        if ($cc) { $this->email->cc($this->_split_emails($cc)); }
        if ($bcc) { $this->email->bcc($this->_split_emails($bcc)); }
        $this->email->subject($subject);
        $this->email->message($message);

        // Handle single attachment field named 'attachment'
        if (!empty($_FILES['attachment']) && isset($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
            $name = isset($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : 'attachment';
            $type = isset($_FILES['attachment']['type']) ? $_FILES['attachment']['type'] : '';
            $this->email->attach($_FILES['attachment']['tmp_name'], 'attachment', $name, $type);
        }

        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Email sent successfully to '.$to);
        } else {
            $this->session->set_flashdata('error', 'Failed to send: '.$this->email->print_debugger(['headers']));
        }
        redirect('mail');
    }

    private function _split_emails($list) {
        $parts = array_filter(array_map(function($s){ return trim($s); }, explode(',', (string)$list)));
        return $parts;
    }

    private function _is_valid_email($email) {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // GET /mail/test - send a test email to the logged-in user email
    public function test() {
        $to = $this->session->userdata('email');
        if (!$to) { $to = $this->config->item('smtp_user'); }

        $from = $this->config->item('smtp_user');
        if (!$from) { $from = 'sateri.mangesh@gmail.com'; }

        $this->email->clear(true);
        $this->email->from($from, 'System Mailer');
        if ($this->session->userdata('email')) {
            $this->email->reply_to($this->session->userdata('email'));
        }
        $this->email->to($to);
        $this->email->subject('Test Email - '.date('Y-m-d H:i'));
        $this->email->message('<p>Hello,</p><p>This is a test email from CodeIgniter using Gmail SMTP.</p>');

        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Test email sent to '.$to);
        } else {
            $this->session->set_flashdata('error', 'Failed to send test: '.$this->email->print_debugger(['headers']));
        }
        redirect('mail');
    }
}
