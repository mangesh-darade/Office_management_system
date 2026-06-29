<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add country column to subscription_builder for regional pricing.
 */
class Migration_Add_subscription_builder_country extends CI_Migration
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
