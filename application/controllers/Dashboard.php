<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function index(){
        $user_id = $this->session->userdata('user_id');
        $role_id = $this->session->userdata('role_id');
        if (!$user_id) { redirect('auth/login'); return; }
        
        $this->load->database();
        $this->load->helper(['group_filter', 'permission']);
        
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
        
        // Fetch dashboard statistics
        $stats = [];
        
        // Employee count - fixed approach to avoid duplicate columns
        if ($this->db->table_exists('employees')) {
            if (in_array($role_id, [1, 2], true)) {
                // Admin sees all employees
                $this->db->from('employees');
                if ($this->db->field_exists('status', 'employees')) {
                    $this->db->where('status', 'active');
                }
                $stats['employees'] = $this->db->count_all_results();
            } else {
                // Non-admin users see filtered employees
                $user_info = $this->db->where('user_id', $user_id)->get('employees')->row();
                if ($user_info && can_view_group_data($role_id)) {
                    // Managers see department employees
                    $this->db->from('employees');
                    $this->db->where('department', $user_info->department);
                    if ($this->db->field_exists('status', 'employees')) {
                        $this->db->where('status', 'active');
                    }
                    $stats['employees'] = $this->db->count_all_results();
                } else {
                    // Regular users see only themselves
                    $stats['employees'] = $user_info ? 1 : 0;
                }
            }
        } else {
            $stats['employees'] = 0;
        }
        
        // Projects count - fixed approach to avoid duplicate columns
        if ($this->db->table_exists('projects')) {
            if (in_array($role_id, [1, 2], true)) {
                // Admin sees all projects
                $this->db->from('projects');
                $stats['projects_total'] = $this->db->count_all_results();
                
                // Active projects
                $this->db->from('projects');
                if ($this->db->field_exists('status', 'projects')) {
                    $this->db->where('status', 'active');
                }
                $stats['projects_active'] = $this->db->count_all_results();
            } else {
                // Non-admin users see only projects they're members of
                // Use subquery approach to avoid duplicate column issues
                $sql = "SELECT COUNT(DISTINCT p.id) as count 
                        FROM projects p 
                        INNER JOIN project_members pm ON pm.project_id = p.id 
                        WHERE pm.user_id = ?";
                $result = $this->db->query($sql, [$user_id])->row();
                $stats['projects_total'] = $result ? (int)$result->count : 0;
                
                // Active projects for non-admin users
                $sql_active = "SELECT COUNT(DISTINCT p.id) as count 
                              FROM projects p 
                              INNER JOIN project_members pm ON pm.project_id = p.id 
                              WHERE pm.user_id = ?";
                if ($this->db->field_exists('status', 'projects')) {
                    $sql_active .= " AND p.status = 'active'";
                }
                $result_active = $this->db->query($sql_active, [$user_id])->row();
                $stats['projects_active'] = $result_active ? (int)$result_active->count : 0;
            }
        } else {
            $stats['projects_total'] = 0;
            $stats['projects_active'] = 0;
        }
        
        // Tasks count - fixed approach to avoid duplicate columns
        if ($this->db->table_exists('tasks')) {
            if (in_array($role_id, [1, 2], true)) {
                // Admin sees all tasks
                $this->db->from('tasks');
                $stats['tasks_total'] = $this->db->count_all_results();
                
                // Pending tasks
                $this->db->from('tasks');
                if ($this->db->field_exists('status', 'tasks')) {
                    $this->db->where('status', 'pending');
                }
                $stats['tasks_pending'] = $this->db->count_all_results();
                
                // Completed tasks
                $this->db->from('tasks');
                if ($this->db->field_exists('status', 'tasks')) {
                    $this->db->where('status', 'completed');
                }
                $stats['tasks_completed'] = $this->db->count_all_results();
            } else {
                // Non-admin users see filtered tasks
                if (can_view_group_data($role_id)) {
                    // Managers see department tasks - use subquery approach
                    $user_info = $this->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        // Total tasks in department
                        $sql = "SELECT COUNT(t.id) as count 
                                FROM tasks t 
                                INNER JOIN employees e ON e.user_id = t.assigned_to 
                                WHERE e.department = ?";
                        $result = $this->db->query($sql, [$user_info->department])->row();
                        $stats['tasks_total'] = $result ? (int)$result->count : 0;
                        
                        // Pending tasks in department
                        $sql_pending = "SELECT COUNT(t.id) as count 
                                       FROM tasks t 
                                       INNER JOIN employees e ON e.user_id = t.assigned_to 
                                       WHERE e.department = ?";
                        if ($this->db->field_exists('status', 'tasks')) {
                            $sql_pending .= " AND t.status = 'pending'";
                        }
                        $result_pending = $this->db->query($sql_pending, [$user_info->department])->row();
                        $stats['tasks_pending'] = $result_pending ? (int)$result_pending->count : 0;
                        
                        // Completed tasks in department
                        $sql_completed = "SELECT COUNT(t.id) as count 
                                         FROM tasks t 
                                         INNER JOIN employees e ON e.user_id = t.assigned_to 
                                         WHERE e.department = ?";
                        if ($this->db->field_exists('status', 'tasks')) {
                            $sql_completed .= " AND t.status = 'completed'";
                        }
                        $result_completed = $this->db->query($sql_completed, [$user_info->department])->row();
                        $stats['tasks_completed'] = $result_completed ? (int)$result_completed->count : 0;
                    } else {
                        $stats['tasks_total'] = 0;
                        $stats['tasks_pending'] = 0;
                        $stats['tasks_completed'] = 0;
                    }
                } else {
                    // Regular users see only their own tasks
                    $this->db->from('tasks');
                    $this->db->where('assigned_to', $user_id);
                    $stats['tasks_total'] = $this->db->count_all_results();
                    
                    // Pending tasks
                    $this->db->from('tasks');
                    $this->db->where('assigned_to', $user_id);
                    if ($this->db->field_exists('status', 'tasks')) {
                        $this->db->where('status', 'pending');
                    }
                    $stats['tasks_pending'] = $this->db->count_all_results();
                    
                    // Completed tasks
                    $this->db->from('tasks');
                    $this->db->where('assigned_to', $user_id);
                    if ($this->db->field_exists('status', 'tasks')) {
                        $this->db->where('status', 'completed');
                    }
                    $stats['tasks_completed'] = $this->db->count_all_results();
                }
            }
        } else {
            $stats['tasks_total'] = 0;
            $stats['tasks_pending'] = 0;
            $stats['tasks_completed'] = 0;
        }
        
        // Today's attendance - fixed approach to avoid duplicate columns
        if ($this->db->table_exists('attendance')) {
            $today = date('Y-m-d');
            
            // Get date column name
            $date_col = 'date';
            if (!$this->db->field_exists('date', 'attendance')) {
                $date_columns = ['attendance_date', 'created_at', 'timestamp', 'log_date'];
                foreach ($date_columns as $col) {
                    if ($this->db->field_exists($col, 'attendance')) {
                        $date_col = $col;
                        break;
                    }
                }
            }
            
            if (in_array($role_id, [1, 2], true)) {
                // Admin sees all attendance - simple query
                $this->db->from('attendance');
                $this->db->where($date_col, $today);
                $stats['attendance_today'] = $this->db->count_all_results();
            } else {
                // Non-admin users see filtered attendance
                if (can_view_group_data($role_id)) {
                    // Managers see department attendance - use subquery approach
                    $user_info = $this->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        $sql = "SELECT COUNT(a.id) as count 
                                FROM attendance a 
                                INNER JOIN employees e ON e.user_id = a.user_id 
                                WHERE a.$date_col = ? AND e.department = ?";
                        $result = $this->db->query($sql, [$today, $user_info->department])->row();
                        $stats['attendance_today'] = $result ? (int)$result->count : 0;
                    } else {
                        $stats['attendance_today'] = 0;
                    }
                } else {
                    // Regular users see only their own attendance - simple query
                    $this->db->from('attendance');
                    $this->db->where($date_col, $today);
                    $this->db->where('user_id', $user_id);
                    $stats['attendance_today'] = $this->db->count_all_results();
                }
            }
        } else {
            $stats['attendance_today'] = 0;
        }
        
        // Leave requests pending - fixed approach to avoid duplicate columns
        if ($this->db->table_exists('leave_requests')) {
            if (in_array($role_id, [1, 2], true)) {
                // Admin sees all pending leave requests - simple query
                $this->db->from('leave_requests');
                if ($this->db->field_exists('status', 'leave_requests')) {
                    $this->db->where('status', 'pending');
                }
                $stats['leaves_pending'] = $this->db->count_all_results();
            } else {
                // Non-admin users see filtered leave requests
                if (can_view_group_data($role_id)) {
                    // Managers can see team leave requests - use subquery approach
                    $user_info = $this->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        $sql = "SELECT COUNT(lr.id) as count 
                                FROM leave_requests lr 
                                INNER JOIN employees e ON e.user_id = lr.user_id 
                                INNER JOIN employees cu ON cu.department = e.department 
                                WHERE cu.user_id = ?";
                        if ($this->db->field_exists('status', 'leave_requests')) {
                            $sql .= " AND lr.status = 'pending'";
                        }
                        $result = $this->db->query($sql, [$user_id])->row();
                        $stats['leaves_pending'] = $result ? (int)$result->count : 0;
                    } else {
                        $stats['leaves_pending'] = 0;
                    }
                } else {
                    // Regular users see only their own pending leave requests - simple query
                    $this->db->from('leave_requests');
                    $this->db->where('user_id', $user_id);
                    if ($this->db->field_exists('status', 'leave_requests')) {
                        $this->db->where('status', 'pending');
                    }
                    $stats['leaves_pending'] = $this->db->count_all_results();
                }
            }
        } else {
            $stats['leaves_pending'] = 0;
        }
        
        $this->load->view('dashboard/index', [
            'role_id' => $role_id, 
            'announcements' => $announcements,
            'stats' => $stats,
            'accessible_modules' => $accessible_modules
        ]);
    }
}
