<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_whatsapp_templates_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('whatsapp_templates')) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `meta_id` varchar(64) DEFAULT NULL,
            `name` varchar(191) NOT NULL,
            `language` varchar(16) NOT NULL DEFAULT 'en_US',
            `category` varchar(64) DEFAULT NULL,
            `status` varchar(32) DEFAULT NULL,
            `body` text DEFAULT NULL,
            `synced_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_wa_tpl_name_lang` (`name`, `language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        if ($this->db->table_exists('whatsapp_templates')) {
            $this->dbforge->drop_table('whatsapp_templates', true);
        }
    }
}
