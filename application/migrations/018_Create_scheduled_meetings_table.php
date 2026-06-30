<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_scheduled_meetings_table extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('scheduled_meetings')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ),
                'conversation_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ),
                'title' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ),
                'room_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ),
                'scheduled_at' => array(
                    'type' => 'DATETIME',
                ),
                'duration_minutes' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 60,
                ),
                'created_by' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                ),
                'host_user_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                ),
                'status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'scheduled',
                ),
                'meeting_password' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ),
                'participants_json' => array(
                    'type' => 'TEXT',
                    'null' => true,
                ),
                'notes' => array(
                    'type' => 'TEXT',
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
            $this->dbforge->add_key('conversation_id');
            $this->dbforge->add_key('scheduled_at');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('host_user_id');
            $this->dbforge->create_table('scheduled_meetings', true);
        }

        if (!$this->db->table_exists('active_meetings')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ),
                'conversation_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                ),
                'room_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ),
                'host_user_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                ),
                'started_at' => array(
                    'type' => 'DATETIME',
                ),
                'ended_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('conversation_id');
            $this->dbforge->add_key('room_name');
            $this->dbforge->create_table('active_meetings', true);
        }

        if (!$this->db->table_exists('conversation_meeting_rooms')) {
            $this->dbforge->add_field(array(
                'conversation_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                ),
                'room_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('conversation_id', true);
            $this->dbforge->add_key('room_name');
            $this->dbforge->create_table('conversation_meeting_rooms', true);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('conversation_meeting_rooms')) {
            $this->dbforge->drop_table('conversation_meeting_rooms', true);
        }
        if ($this->db->table_exists('active_meetings')) {
            $this->dbforge->drop_table('active_meetings', true);
        }
        if ($this->db->table_exists('scheduled_meetings')) {
            $this->dbforge->drop_table('scheduled_meetings', true);
        }
    }
}
