<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_client_urls_table extends CI_Migration
{
    public function up()
    {
        $this->load->helper(array('schema_columns', 'clients_schema'));
        clients_schema_ensure($this->db);
    }

    public function down()
    {
        if ($this->db->table_exists('client_urls')) {
            $this->dbforge->drop_table('client_urls', true);
        }
    }
}
