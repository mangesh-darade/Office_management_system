<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recruitment extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Recruitment_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'permission']);

        // Allow external candidates to access the apply page without auth
        $method = (string)$this->router->fetch_method();
        if ($method === 'apply') {
            return;
        }

        // RBAC Audit: Centralized module access check
        require_module_access(['recruitment', 'recruitment_jobs', 'recruitment_candidates', 'recruitment_interviews', 'recruitment_export'], true);
    }

    private function _is_admin() {
        $role_id = (int)$this->session->userdata('role_id');
        return ($role_id === 1) || (function_exists('is_admin_group') && is_admin_group());
    }

    public function index(){
        $status = $this->input->get('status');
        $data = [
            'jobs'   => $this->Recruitment_model->get_jobs($status ? $status : null),
            'status' => $status,
        ];
        $this->load->view('recruitment/jobs', $data);
    }

    public function create_job(){
        if ($this->input->method() === 'post'){
            $data = [
                'title'            => $this->input->post('title'),
                'description'      => $this->input->post('description'),
                'department'       => $this->input->post('department'),
                'positions'        => (int)$this->input->post('positions'),
                'experience_level' => $this->input->post('experience_level'),
                'status'           => $this->input->post('status') ? $this->input->post('status') : 'open',
                'created_by'       => $this->session->userdata('user_id'),
                'created_at'       => date('Y-m-d H:i:s'),
            ];
            $this->Recruitment_model->save_job($data);
            $this->session->set_flashdata('success', 'Job posting created successfully.');
            redirect('recruitment');
            return;
        }
        $this->load->view('recruitment/create_job');
    }

    public function edit_job($id){
        $job = $this->Recruitment_model->get_job($id);
        if (!$job) { show_404(); return; }

        if ($this->input->method() === 'post'){
            $data = [
                'title'            => $this->input->post('title'),
                'description'      => $this->input->post('description'),
                'department'       => $this->input->post('department'),
                'positions'        => (int)$this->input->post('positions'),
                'experience_level' => $this->input->post('experience_level'),
                'status'           => $this->input->post('status'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ];
            $this->Recruitment_model->save_job($data, $id);
            $this->session->set_flashdata('success', 'Job posting updated.');
            redirect('recruitment');
            return;
        }
        $this->load->view('recruitment/create_job', ['job' => $job, 'editing' => true]);
    }

    public function delete_job($id){
        if ($this->input->method() !== 'post') { show_404(); return; }
        $job = $this->Recruitment_model->get_job($id);
        if (!$job) { show_404(); return; }
        $this->Recruitment_model->delete_job($id);
        $this->session->set_flashdata('success', 'Job posting deleted.');
        redirect('recruitment');
    }

    public function close_job($id){
        if ($this->input->method() !== 'post') { show_404(); return; }
        $job = $this->Recruitment_model->get_job($id);
        if (!$job) { show_404(); return; }
        $this->Recruitment_model->save_job(['status' => 'closed', 'updated_at' => date('Y-m-d H:i:s')], $id);
        $this->session->set_flashdata('success', 'Job posting closed.');
        redirect('recruitment');
    }

    public function candidates(){
        $job_id = $this->input->get('job_id');
        $status = $this->input->get('status');
        $data['candidates'] = $this->Recruitment_model->get_candidates(
            $job_id ? (int)$job_id : null,
            $status ? $status : null
        );
        $data['jobs']       = $this->Recruitment_model->get_jobs();
        $data['job_id']     = $job_id;
        $data['status']     = $status;
        $this->load->view('recruitment/candidates', $data);
    }

    public function candidate_view($id){
        $candidate = $this->Recruitment_model->get_candidate($id);
        if (!$candidate) { show_404(); return; }
        $interviews = $this->Recruitment_model->get_interviews($id);
        $this->load->model('User_model');
        $data = [
            'candidate'  => $candidate,
            'interviews' => $interviews,
        ];
        $this->load->view('recruitment/candidate_view', $data);
    }

    public function candidate_status($id){
        if ($this->input->method() !== 'post') { show_404(); return; }
        $candidate = $this->Recruitment_model->get_candidate($id);
        if (!$candidate) { show_404(); return; }
        $new_status = $this->input->post('status');
        $allowed = ['applied', 'screening', 'interviewing', 'offered', 'hired', 'rejected'];
        if (!in_array($new_status, $allowed)) {
            $this->session->set_flashdata('error', 'Invalid status.');
            redirect('recruitment/candidate/' . $id);
            return;
        }
        $this->Recruitment_model->save_candidate(['status' => $new_status], $id);
        $this->session->set_flashdata('success', 'Candidate status updated to "' . ucfirst($new_status) . '".');
        redirect('recruitment/candidate/' . $id);
    }

    public function apply($job_id){
        $job = $this->Recruitment_model->get_job($job_id);
        if (!$job || $job->status !== 'open') { show_404(); return; }

        if ($this->input->method() === 'post'){
            $config['upload_path']   = './uploads/resumes/';
            $config['allowed_types'] = 'pdf|doc|docx';
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);

            if (!is_dir('./uploads/resumes/')) { mkdir('./uploads/resumes/', 0755, true); }

            $resume_path = null;
            if (!empty($_FILES['resume']['name'])) {
                if ($this->upload->do_upload('resume')){
                    $upload_data = $this->upload->data();
                    $resume_path = 'uploads/resumes/' . $upload_data['file_name'];
                }
            }

            $data = [
                'job_post_id' => $job_id,
                'first_name'  => $this->input->post('first_name'),
                'last_name'   => $this->input->post('last_name'),
                'email'       => $this->input->post('email'),
                'phone'       => $this->input->post('phone'),
                'resume_path' => $resume_path,
                'status'      => 'applied',
                'created_at'  => date('Y-m-d H:i:s'),
            ];
            $this->Recruitment_model->save_candidate($data);
            $this->session->set_flashdata('success', 'Your application has been submitted successfully. We will be in touch soon.');
            // Redirect to a thank-you / confirmation page, NOT the internal candidates list
            redirect('recruitment/apply/' . $job_id . '?applied=1');
            return;
        }
        $applied = $this->input->get('applied');
        $this->load->view('recruitment/apply', ['job' => $job, 'applied' => $applied]);
    }

    public function schedule_interview($candidate_id){
        if ($this->input->method() === 'post'){
            $data = [
                'candidate_id'   => $candidate_id,
                'interviewer_id' => $this->input->post('interviewer_id'),
                'interview_date' => $this->input->post('interview_date'),
                'type'           => $this->input->post('type'),
                'location'       => $this->input->post('location'),
                'notes'          => $this->input->post('notes'),
                'status'         => 'scheduled',
                'created_at'     => date('Y-m-d H:i:s'),
            ];
            $this->Recruitment_model->save_interview($data);

            // Update candidate status to interviewing
            $this->Recruitment_model->save_candidate(['status' => 'interviewing'], $candidate_id);

            // Send Email to Candidate
            $candidate = $this->Recruitment_model->get_candidate($candidate_id);
            if ($candidate) {
                $this->load->helper('email');
                $company_name = 'Our Company';
                // Try to get company name from settings
                if ($this->db->table_exists('settings')) {
                    $setting = $this->db->get_where('settings', ['key' => 'company_name'])->row();
                    if ($setting) { $company_name = $setting->value; }
                }
                $job_title = isset($candidate->job_title) ? $candidate->job_title : 'Position';
                $placeholders = [
                    'candidate_name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'job_title'      => $job_title,
                    'date'           => date('d M Y h:i A', strtotime($data['interview_date'])),
                    'type'           => $data['type'],
                    'company_name'   => $company_name,
                ];
                if (function_exists('send_dynamic_email')) {
                    send_dynamic_email(
                        $candidate->email,
                        'Interview Scheduled - ' . $job_title,
                        'recruitment',
                        'interview_scheduled',
                        $placeholders
                    );
                }
            }

            $this->session->set_flashdata('success', 'Interview scheduled successfully.');
            redirect('recruitment/candidate/' . $candidate_id);
        }
        $data['candidate']   = $this->Recruitment_model->get_candidate($candidate_id);
        if (!$data['candidate']) { show_404(); return; }
        $this->load->model('User_model');
        $data['interviewers'] = $this->User_model->get_all();
        $this->load->view('recruitment/schedule_interview', $data);
    }

    public function export(){
        if (!$this->_is_admin() && function_exists('has_module_access') && !has_module_access('recruitment_export') && !has_module_access('recruitment_candidates') && !has_module_access('recruitment')) {
            show_error('Access denied.', 403);
        }
        $candidates = $this->Recruitment_model->get_candidates();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="candidates_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Job Title', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Applied At']);
        foreach ($candidates as $c) {
            fputcsv($out, [
                $c->id, $c->job_title, $c->first_name, $c->last_name,
                $c->email, $c->phone, $c->status, $c->created_at,
            ]);
        }
        fclose($out);
        exit;
    }
}
