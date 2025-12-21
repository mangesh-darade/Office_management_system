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
            $current_password = $this->input->post('current_password');
            
            if (!empty($password)) {
                // Verify current password first
                if (empty($current_password)) {
                    $this->session->set_flashdata('error', 'Please enter your current password to change your password!');
                    redirect('profile/edit');
                    return;
                }
                
                // Get current user data to verify password
                $current_user = $this->User_model->get($uid);
                if (!password_verify($current_password, $current_user->password)) {
                    $this->session->set_flashdata('error', 'Current password is incorrect!');
                    redirect('profile/edit');
                    return;
                }
                
                // Validate new password
                if (strlen($password) < 6) {
                    $this->session->set_flashdata('error', 'New password must be at least 6 characters long!');
                    redirect('profile/edit');
                    return;
                }
                
                $confirm_password = $this->input->post('confirm_password');
                if ($password !== $confirm_password) {
                    $this->session->set_flashdata('error', 'New passwords do not match!');
                    redirect('profile/edit');
                    return;
                }
                
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
    
    public function delete_profile()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('login'); return; }
        
        // Only allow admins to delete their own profile or users to delete themselves
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) { // Only admins can delete profiles
            $this->session->set_flashdata('error', 'Only administrators can delete profiles!');
            redirect('profile');
            return;
        }
        
        if ($this->input->method() === 'post') {
            $confirmation = trim($this->input->post('confirmation'));
            if ($confirmation !== 'DELETE') {
                $this->session->set_flashdata('error', 'Please type DELETE exactly to confirm profile deletion!');
                redirect('profile/edit');
                return;
            }
            
            try {
                // Get user data before deletion
                $user = $this->User_model->get($uid);
                
                // Delete avatar file if exists
                if (!empty($user->avatar) && file_exists('./' . $user->avatar)) {
                    unlink('./' . $user->avatar);
                }
                
                // Delete employee record if exists
                $this->db->where('user_id', $uid)->delete('employees');
                
                // Delete user record
                $this->db->where('id', $uid)->delete('users');
                
                // Destroy session and redirect to login
                $this->session->sess_destroy();
                
                echo '<script>
                    alert("Profile deleted successfully! You will be redirected to the login page.");
                    window.location.href = "' . site_url('login') . '";
                </script>';
                exit;
                
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Failed to delete profile: ' . $e->getMessage());
                redirect('profile/edit');
            }
        }
        
        redirect('profile/edit');
    }
}
