<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recruitment extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Recruitment_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'permission']);

        // Allow external candidates to access the apply page without auth
        if ($this->router->fetch_method() === 'apply') {
            return;
        }

        if (!$this->session->userdata('user_id')) { redirect('auth/login'); }
        $role_id = (int)$this->session->userdata('role_id');
        // Super Admin (role_id 1) can always access; other roles must have explicit module permission
        $is_superadmin = ($role_id === 1);
        $has_recruitment = function_exists('has_module_access') && (
            has_module_access('recruitment') ||
            has_module_access('recruitment_jobs') ||
            has_module_access('recruitment_candidates') ||
            has_module_access('recruitment_interviews')
        );
        if (!$is_superadmin && !$has_recruitment) {
            show_error('You do not have permission to access Recruitment.', 403);
        }
    }

    public function index(){
        $role = $this->session->userdata('role_id');
        $data = [
            'jobs' => $this->Recruitment_model->get_jobs(),
            'role' => $role
        ];
        $this->load->view('recruitment/jobs', $data);
    }

    public function create_job(){
        if ($this->input->method() === 'post'){
            $data = [
                'title' => $this->input->post('title'),
                'description' => $this->input->post('description'),
                'positions' => (int)$this->input->post('positions'),
                'status' => 'open',
                'created_by' => $this->session->userdata('user_id'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->Recruitment_model->save_job($data);
            redirect('recruitment');
        }
        $this->load->view('recruitment/create_job');
    }

    public function candidates(){
        $data['candidates'] = $this->Recruitment_model->get_candidates();
        $this->load->view('recruitment/candidates', $data);
    }

    public function apply($job_id){
        $job = $this->Recruitment_model->get_job($job_id);
        if (!$job) show_404();

        if ($this->input->method() === 'post'){
            $config['upload_path'] = './uploads/resumes/';
            $config['allowed_types'] = 'pdf|doc|docx';
            $config['max_size'] = 2048; // 2MB
            $this->load->library('upload', $config);
            
            if (!is_dir('./uploads/resumes/')) { mkdir('./uploads/resumes/', 0755, true); }

            $resume_path = null;
            if ($this->upload->do_upload('resume')){
                $upload_data = $this->upload->data();
                $resume_path = 'uploads/resumes/' . $upload_data['file_name'];
            }

            $data = [
                'job_post_id' => $job_id,
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'email' => $this->input->post('email'),
                'phone' => $this->input->post('phone'),
                'resume_path' => $resume_path,
                'status' => 'applied',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->Recruitment_model->save_candidate($data);
            $this->session->set_flashdata('success', 'Application submitted successfully.');
            redirect('recruitment/candidates');
        }
        $this->load->view('recruitment/apply', ['job' => $job]);
    }

    public function schedule_interview($candidate_id){
        if ($this->input->method() === 'post'){
            $data = [
                'candidate_id' => $candidate_id,
                'interviewer_id' => $this->input->post('interviewer_id'),
                'interview_date' => $this->input->post('interview_date'),
                'type' => $this->input->post('type'),
                'status' => 'scheduled',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->Recruitment_model->save_interview($data);

            // Send Email to Candidate (Dynamic)
            $candidate = $this->Recruitment_model->get_candidate($candidate_id);
            if ($candidate) {
                $this->load->helper('email'); // Load our helper
                $placeholders = [
                    'candidate_name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'job_title' => $candidate->job_title,
                    'date' => date('d M Y h:i A', strtotime($data['interview_date'])),
                    'type' => $data['type'],
                    'company_name' => 'Our Company' // dynamic if you have settings
                ];
                
                send_dynamic_email(
                    $candidate->email, 
                    "Interview Scheduled - " . $candidate->job_title, 
                    'recruitment', 
                    'interview_scheduled', 
                    $placeholders
                );
            }

            $this->session->set_flashdata('success', 'Interview scheduled and email queued.');
            redirect('recruitment/candidates');
        }
        $data['candidate'] = $this->Recruitment_model->get_candidate($candidate_id);
        $this->load->model('User_model');
        $data['interviewers'] = $this->User_model->get_all(); // Assuming User_model exists and has get_all
        $this->load->view('recruitment/schedule_interview', $data);
    }
}
