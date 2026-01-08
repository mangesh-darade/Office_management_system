<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Add indexes to activity_log table for performance
 * 
 * Adds indexes on commonly filtered columns to improve query performance
 */
class Migration_Add_activity_log_indexes extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('activity_log')) {
            // Check if indexes already exist before creating
            $indexes = $this->db->query("SHOW INDEXES FROM activity_log")->result();
            $existing_indexes = array();
            foreach ($indexes as $index) {
                $existing_indexes[] = $index->Key_name;
            }
            
            // Index on actor_id for user filtering
            if (!in_array('idx_actlog_actor', $existing_indexes)) {
                $this->db->query("CREATE INDEX idx_actlog_actor ON activity_log(actor_id)");
            }
            
            // Index on action for action filtering
            if (!in_array('idx_actlog_action', $existing_indexes)) {
                $this->db->query("CREATE INDEX idx_actlog_action ON activity_log(action)");
            }
            
            // Index on created_at for date range filtering
            if (!in_array('idx_actlog_created', $existing_indexes)) {
                $this->db->query("CREATE INDEX idx_actlog_created ON activity_log(created_at)");
            }
            
            // Composite index on entity_type and action for common filter combinations
            if (!in_array('idx_actlog_entity_action', $existing_indexes)) {
                $this->db->query("CREATE INDEX idx_actlog_entity_action ON activity_log(entity_type, action)");
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('activity_log')) {
            $this->db->query("DROP INDEX IF EXISTS idx_actlog_actor ON activity_log");
            $this->db->query("DROP INDEX IF EXISTS idx_actlog_action ON activity_log");
            $this->db->query("DROP INDEX IF EXISTS idx_actlog_created ON activity_log");
            $this->db->query("DROP INDEX IF EXISTS idx_actlog_entity_action ON activity_log");
        }
    }
}

