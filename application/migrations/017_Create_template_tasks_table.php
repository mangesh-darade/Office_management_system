<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_template_tasks_table extends CI_Migration
{
    public function up()
    {
        $this->load->helper('my_works_template_tasks_schema');
        my_works_template_tasks_schema_ensure($this->db);
    }

    public function down()
    {
        // No-op: table creates are not reversed.
    }
}
