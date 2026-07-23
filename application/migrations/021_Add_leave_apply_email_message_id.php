<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_leave_apply_email_message_id extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('leave_requests')) {
            return;
        }
        $this->load->helper('schema_columns');
        if (!schema_table_has_column($this->db, 'leave_requests', 'apply_email_message_id')) {
            $this->db->query("ALTER TABLE `leave_requests` ADD COLUMN `apply_email_message_id` VARCHAR(255) NULL DEFAULT NULL AFTER `manager_id`");
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('leave_requests')) {
            return;
        }
        $this->load->helper('schema_columns');
        if (schema_table_has_column($this->db, 'leave_requests', 'apply_email_message_id')) {
            $this->db->query("ALTER TABLE `leave_requests` DROP COLUMN `apply_email_message_id`");
        }
    }
}
