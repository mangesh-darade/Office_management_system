<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Access Helper
 * 
 * Provides functions for checking user access to modules and permissions
 */

if (!function_exists('check_module_access')) {
    /**
     * Check if user has access to a specific module
     * 
     * @param string $module Module name
     * @param int $user_id User ID (optional, uses current session if not provided)
     * @return bool Whether user has access
     */
    function check_module_access($module, $user_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if module is globally enabled
        $CI->db->where('setting_key', 'enable_module_' . $module);
        $setting = $CI->db->get('system_settings')->row();
        
        if (!$setting || $setting->setting_value !== '1') {
            return false;
        }
        
        // Check user-specific access
        $CI->db->where('user_id', (int)$user_id);
        $CI->db->where('module', $module);
        $CI->db->where('is_accessible', 1);
        $access = $CI->db->get('user_module_access')->row();
        
        if ($access) {
            return true;
        }
        
        // Check role-based permissions
        $user = $CI->db->where('id', (int)$user_id)->get('users')->row();
        if ($user) {
            $CI->db->where('role_id', $user->role_id);
            $CI->db->where('module', $module);
            $CI->db->where('permission', 'view');
            $CI->db->where('is_allowed', 1);
            $permission = $CI->db->get('role_permissions')->row();
            
            return $permission ? true : false;
        }
        
        return false;
    }
}

if (!function_exists('check_permission')) {
    /**
     * Check if user has specific permission for a module
     * 
     * @param string $module Module name
     * @param string $permission Permission type (view, add, edit, delete, etc.)
     * @param int $user_id User ID (optional)
     * @return bool Whether user has permission
     */
    function check_permission($module, $permission, $user_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        
        if (!$user_id) {
            return false;
        }
        
        // First check module access
        if (!check_module_access($module, $user_id)) {
            return false;
        }
        
        // Check specific permission
        $user = $CI->db->where('id', (int)$user_id)->get('users')->row();
        if ($user) {
            $CI->db->where('role_id', $user->role_id);
            $CI->db->where('module', $module);
            $CI->db->where('permission', $permission);
            $CI->db->where('is_allowed', 1);
            $perm = $CI->db->get('role_permissions')->row();
            
            return $perm ? true : false;
        }
        
        return false;
    }
}

if (!function_exists('system_get_accessible_modules')) {
    /**
     * Get list of modules accessible to a user (system_access version)
     * 
     * @param int $user_id User ID (optional)
     * @return array Array of accessible module names
     */
    function system_get_accessible_modules($user_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        
        if (!$user_id) {
            return [];
        }
        
        $accessible_modules = [];
        
        // Get all enabled modules
        $CI->db->like('setting_key', 'enable_module_%');
        $enabled_modules = $CI->db->get('system_settings')->result();
        
        foreach ($enabled_modules as $module) {
            $module_name = str_replace('enable_module_', '', $module->setting_key);
            
            if ($module->setting_value === '1' && check_module_access($module_name, $user_id)) {
                $accessible_modules[] = $module_name;
            }
        }
        
        return $accessible_modules;
    }
}

if (!function_exists('get_system_setting')) {
    /**
     * Get system setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    function get_system_setting($key, $default = null) {
        $CI =& get_instance();
        
        $CI->db->where('setting_key', $key);
        $setting = $CI->db->get('system_settings')->row();
        
        return $setting ? $setting->setting_value : $default;
    }
}

if (!function_exists('get_success_screen_modules')) {
    /**
     * Get modules to display on success screen for current user
     * 
     * @param int $user_id User ID (optional)
     * @return array Array of module information
     */
    function get_success_screen_modules($user_id = null) {
        $CI =& get_instance();
        
        if ($user_id === null) {
            $user_id = (int)$CI->session->userdata('user_id');
        }
        
        if (!$user_id) {
            return [];
        }
        
        // Check if success screen is enabled
        $show_screen = get_system_setting('show_success_screen', '0');
        if ($show_screen !== '1') {
            return [];
        }
        
        // Get configured modules
        $modules_config = get_system_setting('success_screen_modules', 'dashboard,tasks,projects');
        $configured_modules = explode(',', $modules_config);
        
        $accessible_modules = system_get_accessible_modules($user_id);
        
        // Filter to only include accessible modules
        $success_modules = [];
        foreach ($configured_modules as $module) {
            $module = trim($module);
            if (in_array($module, $accessible_modules)) {
                $success_modules[] = [
                    'name' => $module,
                    'title' => ucfirst(str_replace('_', ' ', $module)),
                    'icon' => get_module_icon($module),
                    'url' => site_url($module)
                ];
            }
        }
        
        return $success_modules;
    }
}

if (!function_exists('get_module_icon')) {
    /**
     * Get icon for a module
     * 
     * @param string $module Module name
     * @return string Bootstrap icon name
     */
    function get_module_icon($module) {
        $icons = [
            'dashboard' => 'grid-3x3-gap-fill',
            'tasks' => 'list-check',
            'projects' => 'folder',
            'employees' => 'people',
            'attendance' => 'clock',
            'leave_requests' => 'calendar-check',
            'announcements' => 'megaphone',
            'reports' => 'graph-up',
            'timesheets' => 'clock-history',
            'payroll' => 'currency-dollar',
            'users' => 'person',
            'settings' => 'gear',
            'email_settings' => 'envelope',
            'system_settings' => 'gear-fill',
            'external_training' => 'play-btn',
        ];
        
        return isset($icons[$module]) ? $icons[$module] : 'grid';
    }
}

if (!function_exists('system_has_module_access')) {
    /**
     * System-level module access check (avoids collision with permission_helper)
     * 
     * @param string $module Module name
     * @return bool Whether current user has access
     */
    function system_has_module_access($module) {
        return check_module_access($module);
    }
}
