<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Add optional reference_url to projects, tasks, and requirements
 */
class Migration_Add_reference_url_field extends CI_Migration {

    public function up()
    {
        $tables = array('projects', 'tasks', 'requirements', 'requirement_versions');
        foreach ($tables as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $fields = $this->db->list_fields($table);
            if (!in_array('reference_url', $fields, true)) {
                $this->db->query("ALTER TABLE `{$table}` ADD `reference_url` VARCHAR(500) NULL DEFAULT NULL");
            }
        }
    }

    public function down()
    {
        $tables = array('projects', 'tasks', 'requirements', 'requirement_versions');
        foreach ($tables as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $fields = $this->db->list_fields($table);
            if (in_array('reference_url', $fields, true)) {
                $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `reference_url`");
            }
        }
    }
}
