<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function index(){
        $user_id = $this->session->userdata('user_id');
        $role_id = $this->session->userdata('role_id');
        if (!$user_id) { redirect('auth/login'); return; }
        
        $this->load->database();
        
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
                            ->where('start_date IS NULL', null, false)
                            ->or_where('start_date <=', $today)
                         ->group_end();
            }
            
            // Check if end_date column exists
            if ($this->db->field_exists('end_date', 'announcements')) {
                $this->db->group_start()
                            ->where('end_date IS NULL', null, false)
                            ->or_where('end_date >=', $today)
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
        
        // Fetch dashboard statistics
        $stats = [];
        
        // Employee count
        if ($this->db->table_exists('employees')) {
            // Check if status column exists
            if ($this->db->field_exists('status', 'employees')) {
                $this->db->where('status', 'active');
                $stats['employees'] = $this->db->count_all_results('employees');
            } else {
                // Count all employees if status column doesn't exist
                $stats['employees'] = $this->db->count_all_results('employees');
            }
        } else {
            $stats['employees'] = 0;
        }
        
        // Projects count
        if ($this->db->table_exists('projects')) {
            $stats['projects_total'] = $this->db->count_all_results('projects');
            // Check if status column exists
            if ($this->db->field_exists('status', 'projects')) {
                $this->db->where('status', 'active');
                $stats['projects_active'] = $this->db->count_all_results('projects');
            } else {
                // If no status column, assume all are active
                $stats['projects_active'] = $stats['projects_total'];
            }
        } else {
            $stats['projects_total'] = 0;
            $stats['projects_active'] = 0;
        }
        
        // Tasks count
        if ($this->db->table_exists('tasks')) {
            $stats['tasks_total'] = $this->db->count_all_results('tasks');
            
            // Check if status column exists
            if ($this->db->field_exists('status', 'tasks')) {
                $this->db->where('status', 'pending');
                $stats['tasks_pending'] = $this->db->count_all_results('tasks');
                $this->db->where('status', 'completed');
                $stats['tasks_completed'] = $this->db->count_all_results('tasks');
            } else {
                // If no status column, set defaults
                $stats['tasks_pending'] = 0;
                $stats['tasks_completed'] = 0;
            }
        } else {
            $stats['tasks_total'] = 0;
            $stats['tasks_pending'] = 0;
            $stats['tasks_completed'] = 0;
        }
        
        // Today's attendance
        if ($this->db->table_exists('attendance')) {
            $today = date('Y-m-d');
            // Check if date column exists
            if ($this->db->field_exists('date', 'attendance')) {
                $this->db->where('date', $today);
                $stats['attendance_today'] = $this->db->count_all_results('attendance');
            } else {
                // Try alternative date column names
                $date_columns = ['attendance_date', 'created_at', 'timestamp', 'log_date'];
                $found_date = false;
                
                foreach ($date_columns as $col) {
                    if ($this->db->field_exists($col, 'attendance')) {
                        $this->db->where($col, $today);
                        $stats['attendance_today'] = $this->db->count_all_results('attendance');
                        $found_date = true;
                        break;
                    }
                }
                
                if (!$found_date) {
                    // If no date column found, count all attendance records
                    $stats['attendance_today'] = $this->db->count_all_results('attendance');
                }
            }
        } else {
            $stats['attendance_today'] = 0;
        }
        
        // Leave requests pending
        if ($this->db->table_exists('leave_requests')) {
            // Check if status column exists
            if ($this->db->field_exists('status', 'leave_requests')) {
                $this->db->where('status', 'pending');
                $stats['leaves_pending'] = $this->db->count_all_results('leave_requests');
            } else {
                // If no status column, count all
                $stats['leaves_pending'] = $this->db->count_all_results('leave_requests');
            }
        } else {
            $stats['leaves_pending'] = 0;
        }
        
        $this->load->view('dashboard/index', [
            'role_id' => $role_id, 
            'announcements' => $announcements,
            'stats' => $stats
        ]);
    }
}
