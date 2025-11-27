<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(['session','upload','email']);
        $this->load->helper(['url','form']);
        $this->load->model('Setting_model','settings');
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
    }

    // GET /settings
    public function index(){
        $all = $this->settings->get_all_settings();
        $this->load->view('settings/index', ['settings' => $all]);
    }

    // POST /settings/update
    public function update(){
        if ($this->input->method() !== 'post') { show_404(); }
        $data = $this->input->post();
        
        // Handle weekend checkboxes
        if (isset($data['attendance_weekends']) && is_array($data['attendance_weekends'])) {
            $data['attendance_weekends'] = implode(',', $data['attendance_weekends']);
        } elseif (!isset($data['attendance_weekends'])) {
            $data['attendance_weekends'] = '';
        }
        
        // Handle checkbox values for switches
        $checkbox_fields = ['leave_carry_forward', 'notify_in_app', 'notify_email'];
        foreach ($checkbox_fields as $field) {
            $data[$field] = isset($data[$field]) ? $data[$field] : 'no';
        }
        
        foreach ($data as $k=>$v){
            // Only allow known prefixes
            if (preg_match('/^(company_|attendance_|leave_|email_|notify_)/', $k)){
                $this->settings->set_setting($k, is_array($v) ? json_encode($v) : $v);
            }
        }
        $this->session->set_flashdata('success', 'Settings saved successfully.');
        redirect('settings');
    }

    // POST /settings/remove-logo
    public function remove_logo(){
        if ($this->input->method() !== 'post') { show_404(); }
        
        // Get current logo path to delete file
        $current_logo = $this->settings->get_setting('company_logo');
        if ($current_logo && file_exists(FCPATH . $current_logo)) {
            unlink(FCPATH . $current_logo);
        }
        
        // Clear logo setting
        $this->settings->set_setting('company_logo', '');
        $this->session->set_flashdata('success', 'Logo removed successfully.');
        redirect('settings');
    }

    // POST /settings/upload-logo
    public function upload_logo(){
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK){
            $this->session->set_flashdata('error', 'Upload a valid logo file.');
            redirect('settings'); return;
        }
        $upload_path = FCPATH.'uploads/settings/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
        $config = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|gif',
            'max_size' => 2048,
            'encrypt_name' => true,
        ];
        $this->upload->initialize($config);
        if ($this->upload->do_upload('logo')){
            $up = $this->upload->data();
            $path = 'uploads/settings/'.$up['file_name'];
            
            // Remove old logo if exists
            $old_logo = $this->settings->get_setting('company_logo');
            if ($old_logo && file_exists(FCPATH . $old_logo)) {
                unlink(FCPATH . $old_logo);
            }
            
            $this->settings->set_setting('company_logo', $path);
            $this->session->set_flashdata('success', 'Logo uploaded successfully.');
        } else {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
        }
        redirect('settings');
    }

    // POST /settings/test-email
    public function test_email(){
        $to = trim((string)$this->input->post('to')) ?: (string)$this->session->userdata('email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)){
            $this->session->set_flashdata('error', 'Provide a valid email address');
            redirect('settings'); return;
        }
        
        // Configure email settings from database
        $smtp_user = $this->settings->get_setting('email_smtp_user');
        $smtp_pass = $this->settings->get_setting('email_smtp_pass');
        $smtp_host = $this->settings->get_setting('email_smtp_host', 'smtp.gmail.com');
        $smtp_port = $this->settings->get_setting('email_smtp_port', '587');
        $smtp_crypto = $this->settings->get_setting('email_smtp_crypto', 'tls');
        
        if ($smtp_user && $smtp_pass) {
            $config = [
                'protocol' => 'smtp',
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_user' => $smtp_user,
                'smtp_pass' => $smtp_pass,
                'smtp_crypto' => $smtp_crypto,
                'mailtype' => 'html',
                'charset' => 'utf-8',
                'wordwrap' => TRUE
            ];
            $this->email->initialize($config);
        }
        
        $this->email->clear(true);
        $this->email->to($to);
        $this->email->from($smtp_user ?: 'noreply@example.com', 'Office Management System');
        $this->email->subject('Settings: Test Email');
        $this->email->message('<p>This is a test email from Office Management System settings.</p><p>If you receive this email, your SMTP configuration is working correctly.</p>');
        
        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Test email sent successfully to ' . $to);
        } else {
            $error = $this->email->print_debugger(['headers']);
            $this->session->set_flashdata('error', 'Failed to send test email. Please check your SMTP configuration.');
        }
        redirect('settings');
    }
}
