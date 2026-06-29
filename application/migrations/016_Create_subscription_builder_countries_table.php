<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_subscription_builder_countries_table extends CI_Migration
{
    public function up()
    {
        $this->load->helper('subscription_builder_countries_schema');
        subscription_builder_countries_schema_ensure($this->db);
    }

    public function down()
    {
        // No-op: table creates are not reversed.
    }
}
