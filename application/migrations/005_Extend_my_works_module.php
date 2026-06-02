<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Extend_my_works_module extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('my_works')) {
            if (!$this->db->field_exists('due_date', 'my_works')) {
                $this->dbforge->add_column('my_works', array(
                    'due_date' => array('type' => 'DATE', 'null' => true, 'after' => 'is_important'),
                ));
            }
            if (!$this->db->field_exists('task_id', 'my_works')) {
                $this->dbforge->add_column('my_works', array(
                    'task_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'due_date'),
                ));
            }
        }

        if (!$this->db->table_exists('my_work_activity')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'work_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
                'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
                'action' => array('type' => 'VARCHAR', 'constraint' => 50),
                'detail' => array('type' => 'TEXT', 'null' => true),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('work_id');
            $this->dbforge->create_table('my_work_activity', true);
        }

        if (!$this->db->table_exists('my_work_comments')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'work_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
                'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
                'comment' => array('type' => 'TEXT'),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('work_id');
            $this->dbforge->create_table('my_work_comments', true);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('my_work_comments')) {
            $this->dbforge->drop_table('my_work_comments', true);
        }
        if ($this->db->table_exists('my_work_activity')) {
            $this->dbforge->drop_table('my_work_activity', true);
        }
    }
}
