<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url', 'form']);
        $this->load->model(['User_model','Employee_model']);
    }

    public function index()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('login'); return; }
        $user = $this->User_model->get($uid);
        $employee = null;
        if (!empty($user)) {
            $employee = $this->db->where('user_id', $user->id)->get('employees')->row();
        }
        $this->load->view('profile/index_enhanced', [
            'user' => $user,
            'employee' => $employee
        ]);
    }
    
    public function edit()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('login'); return; }
        
        $user = $this->User_model->get($uid);
        $employee = null;
        if (!empty($user)) {
            $employee = $this->db->where('user_id', $user->id)->get('employees')->row();
        }
        
        if ($this->input->method() === 'post') {
            // Handle profile update
            $data = [
                'name' => trim($this->input->post('name')),
                'email' => trim($this->input->post('email')),
                'phone' => trim($this->input->post('phone')),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle password change
            $password = $this->input->post('password');
            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Handle avatar upload
            if (!empty($_FILES['avatar']['name'])) {
                $config = [
                    'upload_path' => './assets/uploads/avatars/',
                    'allowed_types' => 'jpg|jpeg|png|gif',
                    'max_size' => 2048,
                    'file_name' => 'avatar_' . $uid . '_' . time(),
                    'overwrite' => TRUE,
                    'remove_spaces' => TRUE
                ];
                
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, TRUE);
                }
                
                $this->upload->initialize($config);
                if ($this->upload->do_upload('avatar')) {
                    $upload_data = $this->upload->data();
                    $data['avatar'] = 'assets/uploads/avatars/' . $upload_data['file_name'];
                    
                    // Delete old avatar if exists
                    if (!empty($user->avatar) && file_exists('./' . $user->avatar)) {
                        unlink('./' . $user->avatar);
                    }
                }
            }
            
            // Update user data
            $this->db->where('id', $uid)->update('users', $data);
            
            // Update employee data if exists
            if ($employee) {
                $emp_data = [
                    'first_name' => trim($this->input->post('first_name')),
                    'last_name' => trim($this->input->post('last_name')),
                    'department' => trim($this->input->post('department')),
                    'designation' => trim($this->input->post('designation')),
                    'phone' => trim($this->input->post('phone')),
                    'address' => trim($this->input->post('address')),
                    'bio' => trim($this->input->post('bio'))
                ];
                
                $this->db->where('user_id', $uid)->update('employees', $emp_data);
            }
            
            $this->session->set_flashdata('success', 'Profile updated successfully!');
            redirect('profile');
        }
        
        $this->load->view('profile/edit', [
            'user' => $user,
            'employee' => $employee
        ]);
    }
    
    public function remove_avatar()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('login'); return; }
        
        $user = $this->User_model->get($uid);
        if (!empty($user->avatar) && file_exists('./' . $user->avatar)) {
            unlink('./' . $user->avatar);
        }
        
        $this->db->where('id', $uid)->update('users', ['avatar' => null]);
        $this->session->set_flashdata('success', 'Avatar removed successfully!');
        redirect('profile/edit');
    }
}
