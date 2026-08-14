<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Widen_api_integrations_auth_token extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('api_integrations') || !$this->db->field_exists('auth_token', 'api_integrations')) {
            return;
        }
        $col = $this->db->query("SHOW COLUMNS FROM `api_integrations` LIKE 'auth_token'")->row();
        $col_type = ($col && isset($col->Type)) ? strtolower((string) $col->Type) : '';
        if ($col_type !== '' && strpos($col_type, 'text') === false) {
            $this->db->query("ALTER TABLE `api_integrations` MODIFY `auth_token` text DEFAULT NULL COMMENT 'Auth Token, API Secret, Password'");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('api_integrations') && $this->db->field_exists('auth_token', 'api_integrations')) {
            $this->db->query("ALTER TABLE `api_integrations` MODIFY `auth_token` varchar(255) DEFAULT NULL COMMENT 'Auth Token, API Secret, Password'");
        }
    }
}
