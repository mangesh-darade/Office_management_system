<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function index(){
        try {
            $user_id = $this->session->userdata('user_id');
            $role_id = $this->session->userdata('role_id');
            if (!$user_id) { redirect('auth/login'); return; }
            
            $this->load->database();
            $this->load->helper(['group_filter', 'permission', 'dashboard']);
            
            // Get accessible modules for this user
            $accessible_modules = get_accessible_modules();
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        $accessible_user_ids = get_accessible_user_ids($user_id, $role_id);
        
        // Fetch announcements
        $announcements = [];
        if ($this->db->table_exists('announcements')){
            $today = date('Y-m-d');
            $this->db->from('announcements');
            
            // Check if status column exists
            if ($this->db->field_exists('status', 'announcements')) {
                $this->db->where('status','published');
            }
            
            // Check if start_date column exists
            if ($this->db->field_exists('start_date', 'announcements')) {
                $this->db->group_start()
                            ->where('start_date <=', $today)
                            ->or_where('start_date IS NULL')
                         ->group_end();
            }
            
            // Check if end_date column exists
            if ($this->db->field_exists('end_date', 'announcements')) {
                $this->db->group_start()
                            ->where('end_date >=', $today)
                            ->or_where('end_date IS NULL')
                         ->group_end();
            }
            
            // Check if priority column exists
            if ($this->db->field_exists('priority', 'announcements')) {
                $this->db->order_by('priority','DESC');
            }
            
            $this->db->order_by('id','DESC')
                     ->limit(5);
            $announcements = $this->db->get()->result();
        }
        
        // Fetch dashboard statistics with caching (5 minutes TTL)
        $stats = get_dashboard_stats($user_id, $role_id, 300);
        
            $this->load->view('dashboard/index', [
                'role_id' => $role_id, 
                'announcements' => $announcements,
                'stats' => $stats,
                'accessible_modules' => $accessible_modules
            ]);
        } catch (Exception $e) {
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            show_error('An error occurred while loading the dashboard. Please try again later.', 500);
        }
    }
}
