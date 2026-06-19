<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_subscription_builder_table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('subscription_builder')) {
            return;
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'plan' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'industry' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
            ),
            'module' => array(
                'type' => 'VARCHAR',
                'constraint' => 150,
            ),
            'feature' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'details' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'per_item_set_up_charges' => array(
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ),
            'item_unit' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ),
            'common_set_up_fees' => array(
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ),
            'per_item_per_month_maintenances' => array(
                'type' => 'DECIMAL',
                'constraint' => '12,2',
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
        $this->dbforge->add_key('plan');
        $this->dbforge->add_key('industry');
        $this->dbforge->add_key('module');
        $this->dbforge->create_table('subscription_builder', true);
    }

    public function down()
    {
        if ($this->db->table_exists('subscription_builder')) {
            $this->dbforge->drop_table('subscription_builder', true);
        }
    }
}
