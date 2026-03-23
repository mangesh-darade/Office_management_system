<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leaves extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','download','permission']);
        $this->load->library(['session','email']);
        $this->load->model('Leave_model');
        
        // RBAC Audit: Centralized module access check
        require_module_access(['leaves', 'leave_requests', 'leaves_list'], true);
    }

    public function index() {
        require_module_access(['leaves_list', 'leaves'], true);
        $this->load->view('leaves/index');
    }

    // GET /leaves/export
    public function export_csv()
    {
        require_module_access(['leaves_list', 'leaves'], true);
        $this->load->dbutil();
        if (!$this->db->table_exists('leaves')) {
            $this->session->set_flashdata('error', 'Leaves table does not exist.');
            redirect('leaves');
            return;
        }
        $query = $this->db->query('SELECT * FROM leaves ORDER BY id DESC');
        $csv = $this->dbutil->csv_from_result($query, ",", "\r\n");
        force_download('leaves_export_'.date('Ymd_His').'.csv', $csv);
    }

    // POST /leaves/test-email
    public function test_email()
    {
        require_module_access(['leaves_list', 'leaves'], true);
        $to = $this->input->post('to');
        if (!$to) {
            // fallback to logged-in user's email if available
            $to = (string)$this->session->userdata('email');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Please provide a valid email address.');
            redirect('leaves');
            return;
        }

        $this->email->from('no-reply@localhost', 'Office Management');
        $this->email->to($to);
        $this->email->subject('Test Email from Office Management');
        $this->email->message('<p>This is a test email to confirm email configuration is working.</p>');

        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Email sent successfully to '.$to);
        } else {
            $this->session->set_flashdata('error', 'Email failed to send. Please check email configuration.');
        }
        redirect('leaves');
    }
}
