<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_task_activity_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('task_activity')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `task_activity` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `task_id` bigint(20) UNSIGNED NOT NULL,
            `user_id` bigint(20) UNSIGNED DEFAULT NULL,
            `action` enum('created','updated','status_changed','assigned','commented','attachment_added') NOT NULL,
            `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tact_task` (`task_id`),
            KEY `fk_tact_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        if ($this->db->table_exists('task_activity')) {
            $this->dbforge->drop_table('task_activity', true);
        }
    }
}
