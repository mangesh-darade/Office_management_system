<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Role-based Data Filtering Helper
 * Provides functions to filter data based on user roles and groups
 */

if (!function_exists('get_user_group_filter')) {
    /**
     * Get group filtering conditions for database queries
     * @param int $user_id Current user ID
     * @param int $role_id Current user role ID
     * @return array Filter conditions for different tables
     */
    function get_user_group_filter($user_id = null, $role_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        if ($role_id === null) {
            $role_id = (int)$CI->session->userdata('role_id');
        }
        
        // Admin group (roles 1, 2) sees all data
        if (in_array($role_id, [1, 2], true)) {
            return [
                'users' => [],
                'employees' => [],
                'attendance' => [],
                'projects' => [],
                'tasks' => [],
                'leaves' => [],
                'chats' => []
            ];
        }
        
        // Non-admin users see only their group data
        $filters = [];
        
        // Get user's department/group info
        $CI->db->select('department, designation, reporting_to');
        $user_info = $CI->db->where('user_id', $user_id)->get('employees')->row();
        
        if ($user_info) {
            $dept_name = $user_info->department;
            $designation = $user_info->designation;
            $reporting_to = (int)$user_info->reporting_to;
            
            // Users filter - show users from same department
            if ($dept_name) {
                $filters['users'] = ['department' => $dept_name];
            }
            
            // Employees filter - show employees from same department
            if ($dept_name) {
                $filters['employees'] = ['department' => $dept_name];
            }
            
            // Attendance filter - show attendance from same department
            if ($dept_name) {
                $filters['attendance'] = ['department' => $dept_name];
            }
            
            // Projects filter - show projects where user is member
            $filters['projects'] = ['user_id' => $user_id];
            
            // Tasks filter - show tasks assigned to user or team
            if ($designation && in_array($designation, ['Manager', 'Lead', 'Team Lead'])) {
                $filters['tasks'] = ['department' => $dept_name];
            } else {
                $filters['tasks'] = ['user_id' => $user_id];
            }
            
            // Leaves filter - show leaves from same department
            if ($dept_name) {
                $filters['leaves'] = ['department' => $dept_name];
            }
            
            // Chats filter - show chats from same department
            if ($dept_name) {
                $filters['chats'] = ['department' => $dept_name];
            }
        } else {
            // If no employee record, show only own data
            $filters = [
                'users' => ['id' => $user_id],
                'employees' => ['user_id' => $user_id],
                'attendance' => ['a.user_id' => $user_id],
                'projects' => ['pm.user_id' => $user_id],
                'tasks' => ['assigned_to' => $user_id],
                'leaves' => ['lr.user_id' => $user_id],
                'chats' => ['cm.user_id' => $user_id]
            ];
        }
        
        return $filters;
    }
}

if (!function_exists('apply_group_filter_to_query')) {
    /**
     * Apply group filtering to database query
     * @param object $query Database query object
     * @param string $table Table name for filtering
     * @param array $filters Filter conditions
     * @return object Modified query object
     */
    function apply_group_filter_to_query($query, $table, $filters) {
        if (!isset($filters[$table]) || empty($filters[$table])) {
            return $query;
        }
        
        foreach ($filters[$table] as $field => $value) {
            if (is_array($value)) {
                // For IN clauses, ensure field has table prefix
                if (strpos($field, '.') === false) {
                    $prefixed_field = get_table_prefix_for_field($table, $field);
                    $query->where_in($prefixed_field, $value);
                } else {
                    $query->where_in($field, $value);
                }
            } else {
                // Handle table prefixes to avoid ambiguous column names
                if (strpos($field, '.') === false) {
                    // No table prefix, add appropriate prefix based on context
                    $prefixed_field = get_table_prefix_for_field($table, $field);
                    $query->where($prefixed_field, $value);
                } else {
                    // Already has table prefix, use as-is
                    $query->where($field, $value);
                }
            }
        }
        
        return $query;
    }
}

if (!function_exists('get_table_prefix_for_field')) {
    /**
     * Get appropriate table prefix for a field based on table context
     * @param string $table Table name
     * @param string $field Field name
     * @return string Field name with table prefix
     */
    function get_table_prefix_for_field($table, $field) {
        $prefixes = [
            'employees' => 'e.',
            'projects' => 'p.',
            'tasks' => 't.',
            'attendance' => 'a.',
            'users' => 'u.',
            'leaves' => 'lr.',
            'chats' => 'cm.'
        ];
        
        return isset($prefixes[$table]) ? $prefixes[$table] . $field : $field;
    }
}

if (!function_exists('get_group_filter_sql')) {
    /**
     * Get SQL WHERE conditions for group filtering
     * @param string $table Table name
     * @param array $filters Filter conditions
     * @return string SQL WHERE clause
     */
    function get_group_filter_sql($table, $filters) {
        if (!isset($filters[$table]) || empty($filters[$table])) {
            return '';
        }
        
        $conditions = [];
        foreach ($filters[$table] as $field => $value) {
            if (is_array($value)) {
                $conditions[] = $field . ' IN (' . implode(',', array_map('intval', $value)) . ')';
            } else {
                $conditions[] = $field . ' = ' . (int)$value;
            }
        }
        
        return empty($conditions) ? '' : ' AND ' . implode(' AND ', $conditions);
    }
}

if (!function_exists('can_view_group_data')) {
    /**
     * Check if user can view group-wide data
     * @param int $role_id User role ID
     * @return bool
     */
    function can_view_group_data($role_id = null) {
        if ($role_id === null) {
            $CI =& get_instance();
            $role_id = (int)$CI->session->userdata('role_id');
        }
        
        // Admin and Manager can view group data
        return in_array($role_id, [1, 2, 3], true);
    }
}

if (!function_exists('get_accessible_user_ids')) {
    /**
     * Get list of user IDs that current user can access
     * @param int $user_id Current user ID
     * @param int $role_id Current user role ID
     * @return array Array of accessible user IDs
     */
    function get_accessible_user_ids($user_id = null, $role_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        if ($role_id === null) {
            $role_id = (int)$CI->session->userdata('role_id');
        }
        
        // Admin sees all users
        if (in_array($role_id, [1, 2], true)) {
            $users = $CI->db->select('id')->get('users')->result();
            return array_map('intval', array_column($users, 'id'));
        }
        
        // Get users from same department
        $CI->db->select('e.user_id');
        $CI->db->from('employees e');
        $CI->db->join('employees cu', 'cu.department = e.department');
        $CI->db->where('cu.user_id', $user_id);
        $users = $CI->db->get()->result();
        
        return array_map('intval', array_column($users, 'user_id'));
    }
}
