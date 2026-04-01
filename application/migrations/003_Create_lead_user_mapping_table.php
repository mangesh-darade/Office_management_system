<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_lead_user_mapping_table extends CI_Migration {
    public function up()
    {
        if (!$this->db->table_exists('lead_user_mapping')) {
            $this->db->query("CREATE TABLE `lead_user_mapping` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_lead_user` (`lead_id`,`user_id`),
                KEY `idx_lead` (`lead_id`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('lead_user_mapping')) {
            $this->db->query("DROP TABLE IF EXISTS lead_user_mapping");
        }
    }
}
