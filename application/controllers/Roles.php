<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
        $this->load->helper('permission');
        if (function_exists('has_module_access') && !has_module_access('roles') && !has_module_access('permissions')) {
            show_error('You do not have permission to access this module.', 403);
        }
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
        if ($this->db->field_exists('group_type', 'roles')) {
            $data['group_type'] = $groupType;
        }
        if ($this->db->field_exists('is_active', 'roles')) {
            $data['is_active'] = 1;
        }
        if ($this->db->field_exists('sort_order', 'roles')) {
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
        if ($this->db->field_exists('group_type', 'roles')) {
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
            if ($this->db->field_exists('role_id', 'users')) {
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
            } elseif ($this->db->field_exists('role', 'users')) {
                $roleName = isset($role->name) ? strtolower(trim($role->name)) : '';
                if ($roleName !== '') {
                    $users = $this->db->select('name, email')->where('LOWER(role) =', $roleName, false)->get('users')->result();
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
            $roleName = isset($role->name) ? htmlspecialchars($role->name) : 'This role';
            
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
        if (!$this->db->table_exists('roles')) {
            $sql = "CREATE TABLE `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `group_type` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        if ($this->db->table_exists('roles') && !$this->db->field_exists('group_type', 'roles')) {
            $this->db->query("ALTER TABLE `roles` ADD `group_type` varchar(50) DEFAULT NULL AFTER `name`");
        }

        if ($this->db->table_exists('roles')) {
            $count = $this->db->count_all('roles');
            if ((int)$count === 0) {
                $defaults = [
                    1 => ['name' => 'Admin',   'group_type' => 'admin'],
                    2 => ['name' => 'Manager', 'group_type' => 'admin'],
                    3 => ['name' => 'Lead',    'group_type' => 'admin'],
                    4 => ['name' => 'Staff',   'group_type' => 'user'],
                ];
                foreach ($defaults as $id => $cfg) {
                    $row = [
                        'id'         => (int)$id,
                        'name'       => $cfg['name'],
                        'group_type' => $cfg['group_type'],
                        'is_active'  => 1,
                        'sort_order' => (int)$id,
                    ];
                    $this->db->insert('roles', $row);
                }
            } else {
                if ($this->db->field_exists('group_type', 'roles')) {
                    $this->db->where_in('id', [1, 2, 3]);
                    $this->db->where("(group_type IS NULL OR group_type = '')", null, false);
                    $this->db->update('roles', ['group_type' => 'admin']);

                    $this->db->where('id', 4);
                    $this->db->where("(group_type IS NULL OR group_type = '')", null, false);
                    $this->db->update('roles', ['group_type' => 'user']);
                }
            }
        }
    }
}
