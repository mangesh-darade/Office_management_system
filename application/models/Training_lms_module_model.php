<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * training_modules
 */
class Training_lms_module_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('schema_columns');
    }

    public function schema_ready()
    {
        return $this->db->table_exists('training_modules');
    }

    /**
     * Add columns/tables for enrollments, completions, prerequisites, max submissions (idempotent).
     */
    public function ensure_extensions()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (!$this->db->table_exists('training_modules') || !$this->db->table_exists('training_topics')) {
            return;
        }
        if (!schema_table_has_column($this->db, 'training_topics', 'prerequisite_topic_id')) {
            $this->db->query("ALTER TABLE `training_topics` ADD `prerequisite_topic_id` int(11) DEFAULT NULL COMMENT 'Enforced prerequisite topic' AFTER `module_id`");
            $this->db->query('ALTER TABLE `training_topics` ADD KEY `idx_tt_prereq` (`prerequisite_topic_id`)');
        }
        if ($this->db->table_exists('assignments') && !schema_table_has_column($this->db, 'assignments', 'max_submissions')) {
            $this->db->query("ALTER TABLE `assignments` ADD `max_submissions` int(11) NOT NULL DEFAULT 0 COMMENT '0 = unlimited uploads per user' AFTER `details`");
        }
        if (!$this->db->table_exists('training_enrollments')) {
            $this->db->query("CREATE TABLE `training_enrollments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `module_id` int(11) NOT NULL,
              `assigned_by` int(11) DEFAULT NULL,
              `due_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_te_user_module` (`user_id`,`module_id`),
              KEY `idx_te_module` (`module_id`),
              CONSTRAINT `fk_te_module_lms` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->table_exists('training_topic_completions')) {
            $this->db->query("CREATE TABLE `training_topic_completions` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `topic_id` int(11) NOT NULL,
              `completed_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_ttc_user_topic` (`user_id`,`topic_id`),
              KEY `idx_ttc_topic` (`topic_id`),
              CONSTRAINT `fk_ttc_topic_lms` FOREIGN KEY (`topic_id`) REFERENCES `training_topics` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /**
     * Learner catalogue: LMS admin / role 1 see all modules.
     * If training_enrollments has any rows system-wide, only enrolled modules are listed for other users;
     * users with no enrollments then see an empty list. If the enrollments table is empty, everyone still sees all modules.
     *
     * @param int  $user_id
     * @param bool $active_only
     * @param bool $is_lms_manager
     * @return array
     */
    public function list_with_topic_counts_for_user($user_id, $active_only, $is_lms_manager)
    {
        if ($is_lms_manager || !$this->db->table_exists('training_enrollments')) {
            return $this->list_with_topic_counts($active_only);
        }
        if ((int) $this->db->count_all('training_enrollments') < 1) {
            return $this->list_with_topic_counts($active_only);
        }
        if ($this->db->where('user_id', (int) $user_id)->count_all_results('training_enrollments') < 1) {
            return array();
        }
        $this->db->select('m.*, (SELECT COUNT(*) FROM training_topics t WHERE t.module_id = m.id) AS topic_count', false);
        $this->db->from('training_modules m');
        $this->db->join('training_enrollments e', 'e.module_id = m.id', 'inner');
        $this->db->where('e.user_id', (int) $user_id);
        if ($active_only) {
            $this->db->where('m.status', 'active');
        }
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('m.id', 'ASC');
        return $this->db->get()->result();
    }

    public function list_all($active_only = false)
    {
        $this->db->from('training_modules');
        if ($active_only) {
            $this->db->where('status', 'active');
        }
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->get('training_modules')->row();
    }

    public function insert_row($data)
    {
        $this->db->insert('training_modules', $data);
        return (int) $this->db->insert_id();
    }

    public function update_row($id, $data)
    {
        $this->db->where('id', (int) $id)->update('training_modules', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_row($id)
    {
        $this->db->where('id', (int) $id)->delete('training_modules');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Topic count per module (for cards).
     */
    public function list_with_topic_counts($active_only = false)
    {
        $this->db->select('m.*, (SELECT COUNT(*) FROM training_topics t WHERE t.module_id = m.id) AS topic_count', false);
        $this->db->from('training_modules m');
        if ($active_only) {
            $this->db->where('m.status', 'active');
        }
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('m.id', 'ASC');
        return $this->db->get()->result();
    }
}
