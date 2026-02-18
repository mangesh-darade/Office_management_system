<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Superadmin extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'permission', 'html']);
        $this->load->library('session');
        
        // Authentication check first
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
        
        $role_id = (int)$this->session->userdata('role_id');
        $role = $this->db->get_where('roles', ['id' => $role_id])->row();
        $is_superadmin_role = ($role && strtolower(trim($role->name)) === 'superadmin');
        $has_superadmin_perm = function_exists('has_module_access') && has_module_access('superadmin');
        if (!$is_superadmin_role && !$has_superadmin_perm) {
            show_error('Access Denied. You do not have permission to access Super Admin.', 403);
        }
    }

    public function index() {
        $data['page_title'] = 'Superadmin Dashboard';
        
        // System Stats
        $data['users_count'] = $this->db->count_all('users');
        $data['projects_count'] = $this->db->table_exists('projects') ? $this->db->count_all('projects') : 0;
        $data['tasks_count'] = $this->db->table_exists('tasks') ? $this->db->count_all('tasks') : 0;
        $data['db_platform'] = $this->db->platform();
        $data['db_version'] = $this->db->version();
        $data['php_version'] = phpversion();
        $data['server_ip'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'Unknown';
        
        // Roles overview
        $data['roles'] = $this->db->get('roles')->result();
        
        // Recent Users
        $data['recent_users'] = $this->db->order_by('created_at', 'DESC')->limit(5)->get('users')->result();

        // Admin Actions
        $data['admin_actions'] = [
            ['label' => 'Manage Roles', 'url' => 'roles', 'icon' => 'bi-person-badge'],
            ['label' => 'System Settings', 'url' => 'settings', 'icon' => 'bi-gear'],
            ['label' => 'Database Manager', 'url' => 'db', 'icon' => 'bi-database'],
            ['label' => 'Permissions', 'url' => 'permissions', 'icon' => 'bi-shield-lock'],
        ];

        $this->load->view('superadmin/dashboard', $data);
    }

    public function phpinfo() {
        // phpinfo() removed for security - it exposes sensitive server configuration
        show_error('This function has been disabled for security reasons.', 403);
    }
}
