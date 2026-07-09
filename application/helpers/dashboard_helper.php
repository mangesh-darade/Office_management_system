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
        $CI->load->helper(['permission', 'group_filter', 'data_scope', 'my_works']);
        $stats = [];
        $sees_all = function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data();
        
        // Employee count - optimized query
        if (dashboard_has_module_access('employees') && $CI->db->table_exists('employees')) {
            if ($sees_all) {
                $query = $CI->db->from('employees');
                if (schema_table_has_column($CI->db, 'employees', 'status')) {
                    $query->where('status', STATUS_ACTIVE);
                }
                $stats['employees'] = $query->count_all_results();
            } else {
                $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
                $stats['employees'] = $user_info ? 1 : 0;
            }
        } else {
            $stats['employees'] = 0;
        }
        
        // Projects count - optimized with single query using conditional aggregation
        if (dashboard_has_module_access('projects') && $CI->db->table_exists('projects')) {
            if ($sees_all) {
                $stats['projects_total'] = $CI->db->from('projects')->count_all_results();
                
                $query = $CI->db->from('projects');
                if (schema_table_has_column($CI->db, 'projects', 'status')) {
                    $query->where('status', STATUS_ACTIVE);
                }
                $stats['projects_active'] = $query->count_all_results();
            } else {
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
        if (dashboard_has_module_access('tasks') && $CI->db->table_exists('tasks')) {
            if ($sees_all) {
                $stats['tasks_total'] = $CI->db->from('tasks')->count_all_results();
                
                $query = $CI->db->from('tasks');
                if (schema_table_has_column($CI->db, 'tasks', 'status')) {
                    $query->where('status', STATUS_PENDING);
                }
                $stats['tasks_pending'] = $query->count_all_results();
                
                $query = $CI->db->from('tasks');
                if (schema_table_has_column($CI->db, 'tasks', 'status')) {
                    $query->where('status', STATUS_COMPLETED);
                }
                $stats['tasks_completed'] = $query->count_all_results();
            } else {
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
        } else {
            $stats['tasks_total'] = 0;
            $stats['tasks_pending'] = 0;
            $stats['tasks_completed'] = 0;
        }
        
        // Today's attendance - optimized
        if (dashboard_has_module_access('attendance') && $CI->db->table_exists('attendance')) {
            $today = date('Y-m-d');
            $date_col = 'att_date';
            if (!schema_table_has_column($CI->db, 'attendance', 'att_date')) {
                $date_columns = ['date', 'attendance_date', 'created_at', 'timestamp', 'log_date'];
                foreach ($date_columns as $col) {
                    if (schema_table_has_column($CI->db, 'attendance', $col)) {
                        $date_col = $col;
                        break;
                    }
                }
            }

            if ($sees_all) {
                $stats['attendance_today'] = $CI->db->from('attendance')
                                                   ->where($date_col, $today)
                                                   ->count_all_results();
            } else {
                $stats['attendance_today'] = $CI->db->from('attendance')
                                                   ->where($date_col, $today)
                                                   ->where('user_id', $user_id)
                                                   ->count_all_results();
            }
        } else {
            $stats['attendance_today'] = 0;
        }
        
        // Leave requests pending - optimized (shown on attendance stat card subtitle)
        if (dashboard_has_module_access('leaves') && $CI->db->table_exists('leave_requests')) {
            if ($sees_all) {
                $query = $CI->db->from('leave_requests');
                if (schema_table_has_column($CI->db, 'leave_requests', 'status')) {
                    $query->where('status', STATUS_PENDING);
                }
                $stats['leaves_pending'] = $query->count_all_results();
            } else {
                $query = $CI->db->from('leave_requests')->where('user_id', $user_id);
                if (schema_table_has_column($CI->db, 'leave_requests', 'status')) {
                    $query->where('status', STATUS_PENDING);
                }
                $stats['leaves_pending'] = $query->count_all_results();
            }
        } else {
            $stats['leaves_pending'] = 0;
        }

        // My Works — personal scope (created by or assigned to user) unless org-wide view
        $stats['my_works_open'] = 0;
        $stats['my_works_urgent'] = 0;
        if (dashboard_has_module_access('my_works') && $CI->db->table_exists('my_works')) {
            $can_view_all_mw = function_exists('my_works_sees_all_org_data') && my_works_sees_all_org_data();
            if (!function_exists('my_works_sees_all_org_data') && function_exists('data_scope_sees_all_org_data')) {
                $can_view_all_mw = data_scope_sees_all_org_data();
            }
            $CI->db->from('my_works w');
            if (!$can_view_all_mw && $user_id > 0) {
                $CI->db->group_start();
                $CI->db->where('w.created_by', (int) $user_id);
                $CI->db->or_where('w.created_for', (int) $user_id);
                $CI->db->group_end();
            }
            $CI->db->where('w.status !=', 'closed');
            $stats['my_works_open'] = (int) $CI->db->count_all_results();

            $CI->db->from('my_works w');
            if (!$can_view_all_mw && $user_id > 0) {
                $CI->db->group_start();
                $CI->db->where('w.created_by', (int) $user_id);
                $CI->db->or_where('w.created_for', (int) $user_id);
                $CI->db->group_end();
            }
            $CI->db->where('w.is_urgent', 1);
            $CI->db->where('w.status !=', 'closed');
            $stats['my_works_urgent'] = (int) $CI->db->count_all_results();
        }

        $stats['defects_open'] = 0;
        $stats['defects_overdue'] = 0;
        if (dashboard_has_module_access('defects')
            && $CI->db->table_exists('project_defects')) {
            $CI->load->model('Defect_model', 'dash_defects');
            $defect_counts = $CI->dash_defects->dashboard_counts($user_id);
            $stats['defects_open'] = (int) $defect_counts['open'];
            $stats['defects_overdue'] = (int) $defect_counts['overdue'];
        }

        $stats['releases_upcoming'] = 0;
        if (dashboard_has_module_access('releases') && $CI->db->table_exists('project_releases')) {
            $CI->load->model('Engagement_model', 'dash_releases');
            $rel_counts = $CI->dash_releases->dashboard_counts();
            $stats['releases_upcoming'] = (int) $rel_counts['upcoming'];
        }

        $stats['spl_points_week'] = 0;
        $stats['spl_level'] = '';
        $stats['spl_pending'] = 0;
        $stats['spl_pending_label'] = 'pending';
        if (function_exists('spl_can_access') && spl_can_access()
            && $CI->db->table_exists('user_reward_summary')
            && $CI->db->table_exists('reward_transactions')) {
            $CI->load->helper('spl');
            $CI->load->model('Reward_model', 'dash_rewards');
            $summary = $CI->dash_rewards->get_user_summary($user_id);
            $bounds = spl_reward_period_bounds('week');
            $totals = $CI->dash_rewards->sum_user_activity_points($user_id, $bounds['from'], $bounds['to']);
            $stats['spl_points_week'] = (int) round($totals['net']);
            $level = $CI->dash_rewards->get_level($summary->current_level_code);
            $stats['spl_level'] = $level ? (string) $level->name : ucfirst((string) $summary->current_level_code);
            if (function_exists('spl_can_approve') && spl_can_approve()) {
                $stats['spl_pending'] = (int) $CI->dash_rewards->count_spl_approvals_by_status('pending');
                $stats['spl_pending_label'] = 'approvals pending';
            } else {
                $stats['spl_pending'] = (int) $totals['pending_count'];
                $stats['spl_pending_label'] = 'submissions pending';
            }
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
