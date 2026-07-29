<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add actual_hours for complete-status capture on my_works and projects.
 * tasks.actual_hours already exists in Install schema.
 */
class Migration_Add_actual_hours_columns extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('my_works')
            && !$this->db->field_exists('actual_hours', 'my_works')
        ) {
            $after = $this->db->field_exists('estimate_hours', 'my_works')
                ? ' AFTER `estimate_hours`'
                : '';
            $this->db->query('ALTER TABLE `my_works` ADD `actual_hours` DECIMAL(6,2) NULL' . $after);
        }

        if ($this->db->table_exists('projects')
            && !$this->db->field_exists('actual_hours', 'projects')
        ) {
            $after = $this->db->field_exists('estimate_hours', 'projects')
                ? ' AFTER `estimate_hours`'
                : '';
            $this->db->query('ALTER TABLE `projects` ADD `actual_hours` DECIMAL(6,2) NULL' . $after);
        }

        if ($this->db->table_exists('tasks')
            && !$this->db->field_exists('actual_hours', 'tasks')
        ) {
            $after = $this->db->field_exists('estimate_hours', 'tasks')
                ? ' AFTER `estimate_hours`'
                : '';
            $this->db->query('ALTER TABLE `tasks` ADD `actual_hours` DECIMAL(6,2) NULL' . $after);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('my_works') && $this->db->field_exists('actual_hours', 'my_works')) {
            $this->db->query('ALTER TABLE `my_works` DROP COLUMN `actual_hours`');
        }
        if ($this->db->table_exists('projects') && $this->db->field_exists('actual_hours', 'projects')) {
            $this->db->query('ALTER TABLE `projects` DROP COLUMN `actual_hours`');
        }
        // Do not drop tasks.actual_hours on down — may pre-exist from Install.
    }
}
