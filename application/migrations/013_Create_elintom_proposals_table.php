<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_elintom_proposals_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('elintom_proposals')) {
            return;
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'client_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'client_business' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'document_path' => array(
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ),
            'created_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        ));
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('created_by');
        $this->dbforge->add_key('created_at');
        $this->dbforge->create_table('elintom_proposals', true);
    }

    public function down()
    {
        if ($this->db->table_exists('elintom_proposals')) {
            $this->dbforge->drop_table('elintom_proposals', true);
        }
    }
}
