<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add missing subscription_builder columns on databases created before full schema.
 */
class Migration_Alter_subscription_builder_columns extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('subscription_builder')) {
            return;
        }

        $this->load->helper('subscription_builder_schema');
        subscription_builder_schema_ensure($this->db);
    }

    public function down()
    {
        // No-op: column adds are not reversed.
    }
}
