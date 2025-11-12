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
        foreach ($data as $k=>$v){
            // Only allow known prefixes
            if (preg_match('/^(company_|attendance_|leave_|email_|notify_)/', $k)){
                $this->settings->set_setting($k, is_array($v) ? json_encode($v) : $v);
            }
        }
        $this->session->set_flashdata('success', 'Settings saved.');
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
            $this->settings->set_setting('company_logo', $path);
            $this->session->set_flashdata('success', 'Logo uploaded.');
        } else {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
        }
        redirect('settings');
    }

    // POST /settings/test-email
    public function test_email(){
        $to = trim((string)$this->input->post('to')) ?: (string)$this->session->userdata('email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)){
            $this->session->set_flashdata('error', 'Provide a valid email');
            redirect('settings'); return;
        }
        $this->email->clear(true);
        $this->email->to($to);
        $this->email->subject('Settings: Test Email');
        $this->email->message('<p>This is a test email from Settings.</p>');
        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Test email sent to '.$to);
        } else {
            $this->session->set_flashdata('error', 'Failed to send test email.');
        }
        redirect('settings');
    }
}
