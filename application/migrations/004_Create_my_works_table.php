<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_my_works_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('my_works')) {
            return;
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'details' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'tag' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ),
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ),
            'attachment_original' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ),
            'attachment_stored' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ),
            'created_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ),
            'created_for' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('new', 'in_progress', 'closed'),
                'default' => 'new',
            ),
            'is_urgent' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'is_important' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'closed_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        ));
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('created_by');
        $this->dbforge->add_key('created_for');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('my_works', true);
    }

    public function down()
    {
        if ($this->db->table_exists('my_works')) {
            $this->dbforge->drop_table('my_works', true);
        }
    }
}
