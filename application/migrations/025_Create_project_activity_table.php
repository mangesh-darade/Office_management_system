<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_project_activity_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('project_activity')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `project_activity` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` int(11) UNSIGNED NOT NULL,
            `user_id` int(11) UNSIGNED DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `detail` text DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_pact_project` (`project_id`),
            KEY `idx_pact_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        if ($this->db->table_exists('project_activity')) {
            $this->dbforge->drop_table('project_activity', true);
        }
    }
}
