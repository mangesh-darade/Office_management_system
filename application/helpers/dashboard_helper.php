<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Helper
 * 
 * Helper functions for dashboard statistics and caching
 */

/**
 * Get dashboard statistics with caching
 * 
 * @param int $user_id User ID
 * @param int $role_id Role ID
 * @param int $cache_ttl Cache time to live in seconds (default: 300 = 5 minutes)
 * @return array Dashboard statistics
 */
if (!function_exists('get_dashboard_stats')) {
    function get_dashboard_stats($user_id, $role_id, $cache_ttl = 300) {
        $CI =& get_instance();
        $CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
        
        // Create cache key based on user_id and role_id
        $cache_key = 'dashboard_stats_' . $user_id . '_' . $role_id;
        
        // Try to get from cache
        $stats = $CI->cache->get($cache_key);
        
        if ($stats === FALSE) {
            // Cache miss - calculate stats
            $stats = calculate_dashboard_stats($user_id, $role_id);
            
            // Store in cache
            $CI->cache->save($cache_key, $stats, $cache_ttl);
        }
        
        return $stats;
    }
}

/**
 * Calculate dashboard statistics
 * 
 * @param int $user_id User ID
 * @param int $role_id Role ID
 * @return array Dashboard statistics
 */
if (!function_exists('calculate_dashboard_stats')) {
    function calculate_dashboard_stats($user_id, $role_id) {
        $CI =& get_instance();
        $stats = [];
        
        // Employee count - optimized query
        if ($CI->db->table_exists('employees')) {
            if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                $query = $CI->db->from('employees');
                if ($CI->db->field_exists('status', 'employees')) {
                    $query->where('status', STATUS_ACTIVE);
                }
                $stats['employees'] = $query->count_all_results();
            } else {
                $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
                if ($user_info && can_view_group_data($role_id)) {
                    $query = $CI->db->from('employees')->where('department', $user_info->department);
                    if ($CI->db->field_exists('status', 'employees')) {
                        $query->where('status', STATUS_ACTIVE);
                    }
                    $stats['employees'] = $query->count_all_results();
                } else {
                    $stats['employees'] = $user_info ? 1 : 0;
                }
            }
        } else {
            $stats['employees'] = 0;
        }
        
        // Projects count - optimized with single query using conditional aggregation
        if ($CI->db->table_exists('projects')) {
            if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                $stats['projects_total'] = $CI->db->from('projects')->count_all_results();
                
                $query = $CI->db->from('projects');
                if ($CI->db->field_exists('status', 'projects')) {
                    $query->where('status', STATUS_ACTIVE);
                }
                $stats['projects_active'] = $query->count_all_results();
            } else {
                // Use single query with JOIN for non-admin users
                $sql = "SELECT 
                            COUNT(DISTINCT p.id) as total,
                            SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active
                        FROM projects p 
                        INNER JOIN project_members pm ON pm.project_id = p.id 
                        WHERE pm.user_id = ?";
                $result = $CI->db->query($sql, [$user_id])->row();
                $stats['projects_total'] = $result ? (int)$result->total : 0;
                $stats['projects_active'] = $result ? (int)$result->active : 0;
            }
        } else {
            $stats['projects_total'] = 0;
            $stats['projects_active'] = 0;
        }
        
        // Tasks count - optimized with single query
        if ($CI->db->table_exists('tasks')) {
            if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                $stats['tasks_total'] = $CI->db->from('tasks')->count_all_results();
                
                $query = $CI->db->from('tasks');
                if ($CI->db->field_exists('status', 'tasks')) {
                    $query->where('status', STATUS_PENDING);
                }
                $stats['tasks_pending'] = $query->count_all_results();
                
                $query = $CI->db->from('tasks');
                if ($CI->db->field_exists('status', 'tasks')) {
                    $query->where('status', STATUS_COMPLETED);
                }
                $stats['tasks_completed'] = $query->count_all_results();
            } else {
                if (can_view_group_data($role_id)) {
                    $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        // Single query for all task counts
                        $sql = "SELECT 
                                    COUNT(t.id) as total,
                                    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
                                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed
                                FROM tasks t 
                                INNER JOIN employees e ON e.user_id = t.assigned_to 
                                WHERE e.department = ?";
                        $result = $CI->db->query($sql, [$user_info->department])->row();
                        $stats['tasks_total'] = $result ? (int)$result->total : 0;
                        $stats['tasks_pending'] = $result ? (int)$result->pending : 0;
                        $stats['tasks_completed'] = $result ? (int)$result->completed : 0;
                    } else {
                        $stats['tasks_total'] = 0;
                        $stats['tasks_pending'] = 0;
                        $stats['tasks_completed'] = 0;
                    }
                } else {
                    // Single query for user's own tasks
                    $sql = "SELECT 
                                COUNT(id) as total,
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                            FROM tasks 
                            WHERE assigned_to = ?";
                    $result = $CI->db->query($sql, [$user_id])->row();
                    $stats['tasks_total'] = $result ? (int)$result->total : 0;
                    $stats['tasks_pending'] = $result ? (int)$result->pending : 0;
                    $stats['tasks_completed'] = $result ? (int)$result->completed : 0;
                }
            }
        } else {
            $stats['tasks_total'] = 0;
            $stats['tasks_pending'] = 0;
            $stats['tasks_completed'] = 0;
        }
        
        // Today's attendance - optimized
        if ($CI->db->table_exists('attendance')) {
            $today = date('Y-m-d');
            $date_col = 'date';
            if (!$CI->db->field_exists('date', 'attendance')) {
                $date_columns = ['attendance_date', 'created_at', 'timestamp', 'log_date'];
                foreach ($date_columns as $col) {
                    if ($CI->db->field_exists($col, 'attendance')) {
                        $date_col = $col;
                        break;
                    }
                }
            }
            
            if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                $stats['attendance_today'] = $CI->db->from('attendance')
                                                   ->where($date_col, $today)
                                                   ->count_all_results();
            } else {
                if (can_view_group_data($role_id)) {
                    $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        $sql = "SELECT COUNT(a.id) as count 
                                FROM attendance a 
                                INNER JOIN employees e ON e.user_id = a.user_id 
                                WHERE a.$date_col = ? AND e.department = ?";
                        $result = $CI->db->query($sql, [$today, $user_info->department])->row();
                        $stats['attendance_today'] = $result ? (int)$result->count : 0;
                    } else {
                        $stats['attendance_today'] = 0;
                    }
                } else {
                    $stats['attendance_today'] = $CI->db->from('attendance')
                                                       ->where($date_col, $today)
                                                       ->where('user_id', $user_id)
                                                       ->count_all_results();
                }
            }
        } else {
            $stats['attendance_today'] = 0;
        }
        
        // Leave requests pending - optimized
        if ($CI->db->table_exists('leave_requests')) {
            if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
                $query = $CI->db->from('leave_requests');
                if ($CI->db->field_exists('status', 'leave_requests')) {
                    $query->where('status', STATUS_PENDING);
                }
                $stats['leaves_pending'] = $query->count_all_results();
            } else {
                if (can_view_group_data($role_id)) {
                    $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
                    if ($user_info) {
                        $sql = "SELECT COUNT(lr.id) as count 
                                FROM leave_requests lr 
                                INNER JOIN employees e ON e.user_id = lr.user_id 
                                INNER JOIN employees cu ON cu.department = e.department 
                                WHERE cu.user_id = ?";
                        if ($CI->db->field_exists('status', 'leave_requests')) {
                            $sql .= " AND lr.status = 'pending'";
                        }
                        $result = $CI->db->query($sql, [$user_id])->row();
                        $stats['leaves_pending'] = $result ? (int)$result->count : 0;
                    } else {
                        $stats['leaves_pending'] = 0;
                    }
                } else {
                    $query = $CI->db->from('leave_requests')->where('user_id', $user_id);
                    if ($CI->db->field_exists('status', 'leave_requests')) {
                        $query->where('status', STATUS_PENDING);
                    }
                    $stats['leaves_pending'] = $query->count_all_results();
                }
            }
        } else {
            $stats['leaves_pending'] = 0;
        }
        
        return $stats;
    }
}

/**
 * Clear dashboard cache for a user
 * 
 * @param int $user_id User ID
 * @param int $role_id Role ID
 * @return bool
 */
if (!function_exists('clear_dashboard_cache')) {
    function clear_dashboard_cache($user_id, $role_id) {
        $CI =& get_instance();
        $CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
        
        $cache_key = 'dashboard_stats_' . $user_id . '_' . $role_id;
        return $CI->cache->delete($cache_key);
    }
}

/**
 * Clear all dashboard caches
 * 
 * @return void
 */
if (!function_exists('clear_all_dashboard_cache')) {
    function clear_all_dashboard_cache() {
        $CI =& get_instance();
        $CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
        
        // Clear cache directory for dashboard stats
        $cache_path = $CI->config->item('cache_path');
        if (empty($cache_path)) {
            $cache_path = APPPATH . 'cache/';
        }
        
        $pattern = $cache_path . 'dashboard_stats_*';
        $files = glob($pattern);
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}

