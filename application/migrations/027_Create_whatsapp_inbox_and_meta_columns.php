<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_whatsapp_inbox_and_meta_columns extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('whatsapp_conversations')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `wa_id` varchar(32) NOT NULL,
                `profile_name` varchar(191) DEFAULT NULL,
                `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
                `last_message` text DEFAULT NULL,
                `last_direction` varchar(8) DEFAULT NULL,
                `last_at` datetime DEFAULT NULL,
                `unread_count` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_wa_id` (`wa_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$this->db->table_exists('whatsapp_inbox_messages')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `whatsapp_inbox_messages` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `conversation_id` bigint(20) UNSIGNED NOT NULL,
                `wamid` varchar(191) DEFAULT NULL,
                `direction` varchar(8) NOT NULL,
                `msg_type` varchar(32) DEFAULT 'text',
                `body` text DEFAULT NULL,
                `status` varchar(32) DEFAULT NULL,
                `template_name` varchar(128) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_wamid` (`wamid`),
                KEY `idx_conv` (`conversation_id`, `id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($this->db->table_exists('api_integrations')) {
            if (!$this->db->field_exists('app_secret', 'api_integrations')) {
                $this->db->query("ALTER TABLE `api_integrations` ADD `app_secret` text DEFAULT NULL");
            }
            if (!$this->db->field_exists('webhook_verify_token', 'api_integrations')) {
                $this->db->query("ALTER TABLE `api_integrations` ADD `webhook_verify_token` varchar(255) DEFAULT NULL");
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('whatsapp_inbox_messages')) {
            $this->dbforge->drop_table('whatsapp_inbox_messages', true);
        }
        if ($this->db->table_exists('whatsapp_conversations')) {
            $this->dbforge->drop_table('whatsapp_conversations', true);
        }
    }
}
