<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Settings Controller
 * 
 * Manages system-wide settings including permissions, modules, and access control
 */
class System_settings extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('system_settings', true);
        
        $this->ensure_schema();
    }

    private function ensure_schema() {
        $this->load->helper('system_settings_schema');
        system_settings_schema_ensure($this->db);
    }





    /**
     * Backfill external_training rows into role_permissions for existing databases (adds a column on the System Settings permissions screen).
     */
    private function ensure_external_training_role_permissions()
    {
        if (!$this->db->table_exists('role_permissions')) {
            return;
        }
        $cnt = (int) $this->db->where('module', 'external_training')->count_all_results('role_permissions');
        if ($cnt > 0) {
            return;
        }
        if (!$this->db->table_exists('roles')) {
            return;
        }
        $perms = array('view', 'add', 'edit', 'delete', 'list');
        foreach ($this->db->get('roles')->result() as $r) {
            $rid = (int) $r->id;
            $allowed = $this->default_external_training_role_perms($rid);
            foreach ($perms as $p) {
                $this->db->replace('role_permissions', array(
                    'role_id' => $rid,
                    'module' => 'external_training',
                    'permission' => $p,
                    'is_allowed' => in_array($p, $allowed, true) ? 1 : 0,
                ));
            }
        }
    }

    /** @return string[] */
    private function default_external_training_role_perms($role_id)
    {
        if ($role_id === 1) {
            return array('view', 'add', 'edit', 'delete', 'list');
        }
        if ($role_id === 2) {
            return array('view', 'add', 'edit', 'delete', 'list');
        }
        if ($role_id === 3) {
            return array('view', 'list', 'add', 'edit');
        }
        if ($role_id === 4) {
            return array('view', 'list');
        }
        return array('view', 'list');
    }

    public function index() {
        $categories = $this->db->select('DISTINCT category')->order_by('category')->get('system_settings')->result();
        
        $settings_by_category = [];
        foreach ($categories as $cat) {
            $settings_by_category[$cat->category] = $this->db->where('category', $cat->category)->order_by('setting_key')->get('system_settings')->result();
        }
        
        $this->load->view('system_settings/index', [
            'settings_by_category' => $settings_by_category,
            'categories' => $categories
        ]);
    }

    public function update_settings() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        // Load activity tracking helper
        $this->load->helper('change_tracker');

        $settings = $this->input->post('settings');
        
        // Get old settings before update
        $old_settings = [];
        $changed_settings = [];
        foreach ($settings as $key => $value) {
            $old_setting = $this->db->where('setting_key', $key)->get('system_settings')->row();
            if ($old_setting) {
                $old_value = $old_setting->setting_value;
                $old_settings[$key] = $old_value;
                $new_value = is_array($value) ? json_encode($value) : $value;
                
                if ($old_value !== $new_value) {
                    $changed_settings[$key] = [
                        'before' => $old_value,
                        'after' => $new_value
                    ];
                }
            }
            
            $this->db->where('setting_key', $key);
            $this->db->update('system_settings', [
                'setting_value' => is_array($value) ? json_encode($value) : $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Log settings update if any changes were made
        if (!empty($changed_settings)) {
            $description = 'System settings updated: ' . count($changed_settings) . ' setting(s) changed';
            log_activity_with_changes('settings', 'updated', null, $old_settings, $settings, $description);
        }

        $this->session->set_flashdata('success', 'System settings updated successfully');
        redirect('system-settings');
    }

    public function permissions() {
        $this->ensure_external_training_role_permissions();
        $roles = $this->db->order_by('id')->get('roles')->result();
        $modules = $this->db->select('DISTINCT module')->order_by('module')->get('role_permissions')->result();
        
        $permissions_matrix = [];
        foreach ($roles as $role) {
            foreach ($modules as $module) {
                $perms = $this->db->where('role_id', $role->id)
                               ->where('module', $module->module)
                               ->get('role_permissions')
                               ->result();
                
                foreach ($perms as $perm) {
                    $permissions_matrix[$role->id][$module->module][$perm->permission] = $perm->is_allowed;
                }
            }
        }
        
        $this->load->view('system_settings/permissions', [
            'roles' => $roles,
            'modules' => $modules,
            'permissions_matrix' => $permissions_matrix
        ]);
    }

    public function update_permissions() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        // Load activity tracking helper
        $this->load->helper('change_tracker');

        $permissions = $this->input->post('permissions');
        
        // Get old permissions before update
        $old_permissions = [];
        if ($this->db->table_exists('role_permissions')) {
            $old_rows = $this->db->get('role_permissions')->result();
            foreach ($old_rows as $row) {
                $old_permissions[] = [
                    'role_id' => (int)$row->role_id,
                    'module' => $row->module,
                    'permission' => $row->permission,
                    'is_allowed' => (int)$row->is_allowed
                ];
            }
        }
        
        $new_permissions = [];
        foreach ($permissions as $role_id => $modules) {
            foreach ($modules as $module => $module_perms) {
                foreach ($module_perms as $permission => $is_allowed) {
                    $this->db->replace('role_permissions', [
                        'role_id' => (int)$role_id,
                        'module' => $module,
                        'permission' => $permission,
                        'is_allowed' => (int)$is_allowed,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $new_permissions[] = [
                        'role_id' => (int)$role_id,
                        'module' => $module,
                        'permission' => $permission,
                        'is_allowed' => (int)$is_allowed
                    ];
                }
            }
        }

        // Log permissions update
        $description = 'Role permissions updated for ' . count($permissions) . ' role(s)';
        log_activity_with_changes('permissions', 'updated', null, $old_permissions, $new_permissions, $description);

        $this->session->set_flashdata('success', 'Permission settings updated successfully');
        redirect('system-settings/permissions');
    }

    public function user_access() {
        $users = $this->db->select('u.id, u.email, u.full_name, r.name as role_name')
                       ->from('users u')
                       ->join('roles r', 'r.id = u.role_id', 'left')
                       ->order_by('u.email')
                       ->get()
                       ->result();
        
        $modules = $this->db->select('setting_key, setting_value')
                          ->where('setting_key LIKE', 'enable_module_%')
                          ->get('system_settings')
                          ->result();
        
        $enabled_modules = [];
        foreach ($modules as $module) {
            $module_name = str_replace('enable_module_', '', $module->setting_key);
            $enabled_modules[$module_name] = $module->setting_value == '1';
        }
        
        $user_access = [];
        foreach ($users as $user) {
            $access = $this->db->where('user_id', $user->id)->get('user_module_access')->result();
            foreach ($access as $acc) {
                $user_access[$user->id][$acc->module] = $acc->is_accessible;
            }
        }
        
        $this->load->view('system_settings/user_access', [
            'users' => $users,
            'enabled_modules' => $enabled_modules,
            'user_access' => $user_access
        ]);
    }

    public function update_user_access() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $user_access = $this->input->post('user_access');
        
        foreach ($user_access as $user_id => $modules) {
            foreach ($modules as $module => $is_accessible) {
                $this->db->replace('user_module_access', [
                    'user_id' => (int)$user_id,
                    'module' => $module,
                    'is_accessible' => (int)$is_accessible,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $this->session->set_flashdata('success', 'User access settings updated successfully');
        redirect('system-settings/user-access');
    }

    public function success_screen() {
        $settings = $this->db->where('category', 'ui')->order_by('setting_key')->get('system_settings')->result();
        $ui_settings = [];
        foreach ($settings as $setting) {
            $ui_settings[$setting->setting_key] = $setting->setting_value;
        }
        
        // Get available modules
        $all_modules = [
            'dashboard' => 'Dashboard',
            'tasks' => 'Tasks',
            'projects' => 'Projects',
            'employees' => 'Employees',
            'attendance' => 'Attendance',
            'leave_requests' => 'Leave Requests',
            'announcements' => 'Announcements',
            'reports' => 'Reports',
            'timesheets' => 'Timesheets',
            'payroll' => 'Payroll'
        ];
        
        $enabled_modules = explode(',', isset($ui_settings['success_screen_modules']) ? $ui_settings['success_screen_modules'] : '');
        
        $this->load->view('system_settings/success_screen', [
            'ui_settings' => $ui_settings,
            'all_modules' => $all_modules,
            'enabled_modules' => $enabled_modules
        ]);
    }

    public function update_success_screen() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $settings = $this->input->post('settings');
        
        foreach ($settings as $key => $value) {
            $this->db->where('setting_key', $key);
            $this->db->update('system_settings', [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->session->set_flashdata('success', 'Success screen settings updated successfully');
        redirect('system-settings/success-screen');
    }

    public function get_setting($key) {
        $setting = $this->db->where('setting_key', $key)->get('system_settings')->row();
        return $setting ? $setting->setting_value : null;
    }

    public function get_module_access($user_id, $module) {
        // Check if module is globally enabled
        $module_setting = $this->get_setting('enable_module_' . $module);
        if ($module_setting !== '1') {
            return false;
        }
        
        // Check user-specific access
        $access = $this->db->where('user_id', (int)$user_id)
                        ->where('module', $module)
                        ->where('is_accessible', 1)
                        ->get('user_module_access')
                        ->row();
        
        if ($access) {
            return true;
        }
        
        // Check role-based permissions
        $user = $this->db->where('id', (int)$user_id)->get('users')->row();
        if ($user) {
            $permission = $this->db->where('role_id', $user->role_id)
                              ->where('module', $module)
                              ->where('permission', 'view')
                              ->where('is_allowed', 1)
                              ->get('role_permissions')
                              ->row();
            
            return $permission ? true : false;
        }
        
        return false;
    }
}
