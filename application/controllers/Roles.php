<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'permission','schema_columns']);
        $this->load->library(['session']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('roles', true);
        
        $this->ensure_schema();
    }

    public function index() {
        $this->load->model('Role_model');
        $data = [
            'title' => 'Roles',
            'rows'  => $this->Role_model->get_all(),
        ];
        $this->load->view('roles/index', $data);
    }

    public function store() {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        // Require explicit add permission
        if (function_exists('has_module_access') && !has_module_access('roles') && !has_module_access('permissions')) {
            show_error('You do not have permission to add roles.', 403);
        }
        $name = trim($this->input->post('name', true) ?: '');
        $groupType = strtolower(trim((string)$this->input->post('group_type', true)));
        if ($groupType !== 'admin' && $groupType !== 'user') {
            $groupType = 'user';
        }
        if ($name === '') {
            $this->session->set_flashdata('error', 'Role name is required.');
            redirect('roles');
            return;
        }
        if (!$this->db->table_exists('roles')) {
            $this->session->set_flashdata('error', 'Roles table is not available.');
            redirect('roles');
            return;
        }
        $exists = $this->db->where('name', $name)->get('roles')->row();
        if ($exists) {
            $this->session->set_flashdata('error', 'Role already exists.');
            redirect('roles');
            return;
        }
        $data = ['name' => $name];
        if (schema_table_has_column($this->db, 'roles', 'group_type')) {
            $data['group_type'] = $groupType;
        }
        if (schema_table_has_column($this->db, 'roles', 'is_active')) {
            $data['is_active'] = 1;
        }
        if (schema_table_has_column($this->db, 'roles', 'sort_order')) {
            $maxRow = $this->db->select_max('sort_order')->get('roles')->row();
            $next = 1;
            if ($maxRow && isset($maxRow->sort_order)) {
                $next = (int)$maxRow->sort_order + 1;
            }
            $data['sort_order'] = $next;
        }
        $this->db->insert('roles', $data);
        $new_id = $this->db->insert_id();
        
        // Log role creation
        $this->load->helper('change_tracker');
        $description = 'Role: ' . $name;
        auto_log_insert('roles', 'roles', $new_id, $data, $description);
        
        $this->session->set_flashdata('success', 'Role added.');
        redirect('roles');
    }

    /**
     * Update an existing role (name / group only for now)
     */
    public function update() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $id = (int)$this->input->post('id');
        if ($id <= 0) {
            $this->session->set_flashdata('error', 'Invalid role selected.');
            redirect('roles');
            return;
        }

        if (!$this->db->table_exists('roles')) {
            $this->session->set_flashdata('error', 'Roles table is not available.');
            redirect('roles');
            return;
        }

        $role = $this->db->where('id', $id)->get('roles')->row();
        if (!$role) {
            $this->session->set_flashdata('error', 'Role not found.');
            redirect('roles');
            return;
        }

        $name = trim($this->input->post('name', true) ?: '');
        $groupType = strtolower(trim((string)$this->input->post('group_type', true)));
        if ($groupType !== 'admin' && $groupType !== 'user') {
            $groupType = 'user';
        }

        if ($name === '') {
            $this->session->set_flashdata('error', 'Role name is required.');
            redirect('roles');
            return;
        }

        // Check for duplicate name (excluding this role)
        $exists = $this->db->where('name', $name)
                           ->where('id !=', $id)
                           ->get('roles')
                           ->row();
        if ($exists) {
            $this->session->set_flashdata('error', 'Another role with this name already exists.');
            redirect('roles');
            return;
        }

        $data = ['name' => $name];
        if (schema_table_has_column($this->db, 'roles', 'group_type')) {
            $data['group_type'] = $groupType;
        }

        $this->db->where('id', $id)->update('roles', $data);

        // Log role update if helper available
        if (!function_exists('auto_log_update')) {
            $this->load->helper('change_tracker');
        }
        if (function_exists('auto_log_update')) {
            $description = 'Role updated: ' . $name;
            auto_log_update('roles', 'roles', $id, $data, $description);
        }

        $this->session->set_flashdata('success', 'Role updated.');
        redirect('roles');
    }

    /**
     * Delete a role (with basic safety check)
     */
    public function delete($id = null) {
        // Destructive actions must be POST only
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $id = (int)$id;
        if ($id <= 0) {
            show_404();
        }

        if (!$this->db->table_exists('roles')) {
            $this->session->set_flashdata('error', 'Roles table is not available.');
            redirect('roles');
            return;
        }

        $role = $this->db->where('id', $id)->get('roles')->row();
        if (!$role) {
            $this->session->set_flashdata('error', 'Role not found.');
            redirect('roles');
            return;
        }

        // Prevent deleting roles that are in use by users
        $inUse = false;
        $userCount = 0;
        $userNames = [];
        if ($this->db->table_exists('users')) {
            if (schema_table_has_column($this->db, 'users', 'role_id')) {
                $users = $this->db->select('name, email')->where('role_id', $id)->get('users')->result();
                $userCount = count($users);
                if ($userCount > 0) {
                    $inUse = true;
                    foreach ($users as $user) {
                        $name = isset($user->name) && trim($user->name) !== '' 
                            ? trim($user->name) 
                            : (isset($user->email) ? trim($user->email) : 'Unknown User');
                        $userNames[] = $name;
                    }
                }
            } elseif (schema_table_has_column($this->db, 'users', 'role')) {
                $roleName = isset($role->name) ? strtolower(trim($role->name)) : '';
                if ($roleName !== '') {
                    $users = $this->db
                        ->select('name, email')
                        ->where('LOWER(role) =', $roleName)
                        ->get('users')
                        ->result();
                    $userCount = count($users);
                    if ($userCount > 0) {
                        $inUse = true;
                        foreach ($users as $user) {
                            $name = isset($user->name) && trim($user->name) !== '' 
                                ? trim($user->name) 
                                : (isset($user->email) ? trim($user->email) : 'Unknown User');
                            $userNames[] = $name;
                        }
                    }
                }
            }
        }

        if ($inUse) {
            $roleName = isset($role->name) ? esc_view($role->name) : 'This role';
            
            // Format user names for display
            $maxDisplay = 5; // Show first 5 names
            $displayNames = array_slice($userNames, 0, $maxDisplay);
            $remainingCount = $userCount - $maxDisplay;
            
            $namesText = implode(', ', $displayNames);
            if ($remainingCount > 0) {
                $namesText .= ' and ' . $remainingCount . ' more user(s)';
            }
            
            $message = $roleName . ' is already assigned to: ' . $namesText . '. Cannot delete role with assigned users.';
            $this->session->set_flashdata('error', $message);
            redirect('roles');
            return;
        }

        $this->db->where('id', $id)->delete('roles');

        // Log role delete if helper available
        if (!function_exists('auto_log_delete')) {
            $this->load->helper('change_tracker');
        }
        if (function_exists('auto_log_delete')) {
            $description = 'Role deleted: ' . (isset($role->name) ? $role->name : ('ID ' . $id));
            auto_log_delete('roles', 'roles', $id, $role, $description);
        }

        $this->session->set_flashdata('success', 'Role deleted.');
        redirect('roles');
    }

    private function ensure_schema() {
        $this->load->helper('org_schema');
        org_schema_ensure_roles($this->db);
    }
}
