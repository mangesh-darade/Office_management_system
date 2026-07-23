<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Multi-user assignees for tasks, requirements, and my_works.
 *
 * CREATE TABLE `task_assignees` (
 *   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *   `task_id` int(11) unsigned NOT NULL,
 *   `user_id` int(11) unsigned NOT NULL,
 *   `created_at` datetime DEFAULT NULL,
 *   PRIMARY KEY (`id`),
 *   UNIQUE KEY `uq_task_assignees_task_user` (`task_id`, `user_id`),
 *   KEY `idx_task_assignees_user` (`user_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `requirement_assignees` (
 *   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *   `requirement_id` int(11) unsigned NOT NULL,
 *   `user_id` int(11) unsigned NOT NULL,
 *   `created_at` datetime DEFAULT NULL,
 *   PRIMARY KEY (`id`),
 *   UNIQUE KEY `uq_requirement_assignees_req_user` (`requirement_id`, `user_id`),
 *   KEY `idx_requirement_assignees_user` (`user_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `my_works_assignees` (
 *   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *   `work_id` int(11) unsigned NOT NULL,
 *   `user_id` int(11) unsigned NOT NULL,
 *   `created_at` datetime DEFAULT NULL,
 *   PRIMARY KEY (`id`),
 *   UNIQUE KEY `uq_my_works_assignees_work_user` (`work_id`, `user_id`),
 *   KEY `idx_my_works_assignees_user` (`user_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class Migration_Create_multi_assignees_tables extends CI_Migration
{
    public function up()
    {
        $this->load->helper(array('schema_columns', 'multi_assignee'));
        multi_assignees_ensure_schema($this->db);
    }

    public function down()
    {
        if ($this->db->table_exists('task_assignees')) {
            $this->dbforge->drop_table('task_assignees', true);
        }
        if ($this->db->table_exists('requirement_assignees')) {
            $this->dbforge->drop_table('requirement_assignees', true);
        }
        if ($this->db->table_exists('my_works_assignees')) {
            $this->dbforge->drop_table('my_works_assignees', true);
        }
    }
}
