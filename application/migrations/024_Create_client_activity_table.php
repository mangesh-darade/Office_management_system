<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_client_activity_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('client_activity')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `client_activity` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id` int(11) UNSIGNED NOT NULL,
            `user_id` int(11) UNSIGNED DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `old_value` longtext DEFAULT NULL,
            `new_value` longtext DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cact_client` (`client_id`),
            KEY `idx_cact_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        if ($this->db->table_exists('client_activity')) {
            $this->dbforge->drop_table('client_activity', true);
        }
    }
}
