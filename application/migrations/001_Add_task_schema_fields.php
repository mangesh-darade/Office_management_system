<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Add task schema fields
 * 
 * Adds requirement_id, priority, due_date, start_date, and project_ids fields to tasks table
 */
class Migration_Add_task_schema_fields extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('tasks')) {
            $fields = $this->db->list_fields('tasks');
            
            // Add requirement_id if missing
            if (!in_array('requirement_id', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` ADD `requirement_id` INT(11) NULL AFTER `project_id`");
                $this->db->query("ALTER TABLE `tasks` ADD INDEX `idx_tasks_requirement` (`requirement_id`)");
            }
            
            // Add priority if missing
            if (!in_array('priority', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` ADD `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium' AFTER `status`");
            }
            
            // Add due_date if missing
            if (!in_array('due_date', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` ADD `due_date` DATE NULL AFTER `status`");
            }
            
            // Add start_date if missing
            if (!in_array('start_date', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` ADD `start_date` DATE NULL AFTER `status`");
            }
            
            // Add project_ids for multi-select support
            if (!in_array('project_ids', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` ADD `project_ids` TEXT NULL AFTER `project_id`");
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('tasks')) {
            $fields = $this->db->list_fields('tasks');
            
            // Remove fields in reverse order
            if (in_array('project_ids', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` DROP COLUMN `project_ids`");
            }
            
            if (in_array('start_date', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` DROP COLUMN `start_date`");
            }
            
            if (in_array('due_date', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` DROP COLUMN `due_date`");
            }
            
            if (in_array('priority', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` DROP COLUMN `priority`");
            }
            
            if (in_array('requirement_id', $fields, true)) {
                $this->db->query("ALTER TABLE `tasks` DROP INDEX `idx_tasks_requirement`");
                $this->db->query("ALTER TABLE `tasks` DROP COLUMN `requirement_id`");
            }
        }
    }
}

