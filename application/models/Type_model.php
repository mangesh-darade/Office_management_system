<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Type_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('schema_columns');
        $this->ensure_schema();
    }

    public function ensure_schema()
    {
        if (!$this->db->table_exists('module_types')) {
            $this->db->query("CREATE TABLE `module_types` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `code` varchar(50) NOT NULL,
                `module` varchar(50) NOT NULL COMMENT 'my_works, clients, projects, requirements, employees',
                `display_order` int(11) DEFAULT 0,
                `is_active` tinyint(1) DEFAULT 1,
                `description` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_module_type_code` (`code`, `module`),
                KEY `idx_module_types_module` (`module`),
                KEY `idx_module_types_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $this->seed_defaults_if_missing();
        if ($this->db->table_exists('projects') && !schema_table_has_column($this->db, 'projects', 'project_type')) {
            $this->db->query("ALTER TABLE `projects` ADD `project_type` varchar(50) DEFAULT NULL AFTER `status`");
        }
    }

    public function registry_modules()
    {
        if (!function_exists('module_type_registry')) {
            get_instance()->load->helper('types');
        }
        return module_type_registry();
    }

    private function default_rows()
    {
        if (!function_exists('module_type_default_seed_rows')) {
            get_instance()->load->helper('types');
        }
        return module_type_default_seed_rows();
    }

    private function seed_defaults_if_missing()
    {
        if (!$this->db->table_exists('module_types')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach ($this->default_rows() as $row) {
            if ($this->get_by_code($row['code'], $row['module'])) {
                continue;
            }
            $row['is_active'] = 1;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $this->db->insert('module_types', $row);
        }
    }

    public function get_all($module = null, $active_only = true)
    {
        $this->db->from('module_types');
        if ($module) {
            $this->db->where('module', $module);
        }
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('module', 'ASC');
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_by_module($module, $active_only = true)
    {
        $this->db->from('module_types');
        $this->db->where('module', (string) $module);
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get('module_types')->row();
    }

    public function get_by_code($code, $module)
    {
        return $this->db->where('code', (string) $code)->where('module', (string) $module)->get('module_types')->row();
    }

    public function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('module_types', $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update('module_types', $data);
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete('module_types');
        return $this->db->affected_rows();
    }

    public function options_for_module($module)
    {
        $options = array();
        foreach ($this->get_by_module($module, true) as $row) {
            $options[$row->code] = $row->name;
        }
        return $options;
    }
}
