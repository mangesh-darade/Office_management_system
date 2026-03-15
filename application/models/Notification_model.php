<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification_model
 * Handles all database operations for in-app notifications.
 */
class Notification_model extends CI_Model {

    private $table = 'notifications';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    /**
     * Create the notifications table if missing, or add any missing columns
     * to an existing table so queries never fail with "Unknown column".
     */
    private function ensure_schema() {
        static $done = false;
        if ($done) { return; }
        $done = true;

        if (!$this->db->table_exists($this->table)) {
            $sql = "CREATE TABLE `{$this->table}` (
                `id`         int(11)      NOT NULL AUTO_INCREMENT,
                `user_id`    int(11)      NOT NULL,
                `title`      varchar(255) NOT NULL DEFAULT '',
                `message`    text,
                `type`       varchar(50)  NOT NULL DEFAULT 'info',
                `module`     varchar(100) DEFAULT NULL,
                `related_id` int(11)      DEFAULT NULL,
                `action_url` varchar(500) DEFAULT NULL,
                `is_read`    tinyint(1)   NOT NULL DEFAULT 0,
                `read_at`    datetime     DEFAULT NULL,
                `is_deleted` tinyint(1)   NOT NULL DEFAULT 0,
                `created_at` datetime     NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_notif_user` (`user_id`),
                KEY `idx_notif_read` (`user_id`, `is_read`),
                KEY `idx_notif_deleted` (`user_id`, `is_deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
            return;
        }

        // Add any missing columns to an existing table
        $cols = array(
            'title'      => "ALTER TABLE `{$this->table}` ADD COLUMN `title` varchar(255) NOT NULL DEFAULT '' AFTER `user_id`",
            'message'    => "ALTER TABLE `{$this->table}` ADD COLUMN `message` text AFTER `title`",
            'type'       => "ALTER TABLE `{$this->table}` ADD COLUMN `type` varchar(50) NOT NULL DEFAULT 'info' AFTER `message`",
            'module'     => "ALTER TABLE `{$this->table}` ADD COLUMN `module` varchar(100) DEFAULT NULL AFTER `type`",
            'related_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `related_id` int(11) DEFAULT NULL AFTER `module`",
            'action_url' => "ALTER TABLE `{$this->table}` ADD COLUMN `action_url` varchar(500) DEFAULT NULL AFTER `related_id`",
            'is_read'    => "ALTER TABLE `{$this->table}` ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 0 AFTER `action_url`",
            'read_at'    => "ALTER TABLE `{$this->table}` ADD COLUMN `read_at` datetime DEFAULT NULL AFTER `is_read`",
            'is_deleted' => "ALTER TABLE `{$this->table}` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0 AFTER `read_at`",
            'created_at' => "ALTER TABLE `{$this->table}` ADD COLUMN `created_at` datetime NOT NULL AFTER `is_deleted`",
        );

        foreach ($cols as $col => $alter_sql) {
            if (!$this->db->field_exists($col, $this->table)) {
                $this->db->query($alter_sql);
            }
        }
    }

    /**
     * Create a new notification for a user.
     *
     * @param int    $user_id
     * @param string $title
     * @param string $message
     * @param string $type       info|success|warning|error
     * @param string $module     tasks|projects|leaves|attendance|etc.
     * @param int    $related_id ID of the related entity
     * @param string $action_url URL to navigate to when clicked
     * @return int  Inserted notification ID
     */
    public function create($user_id, $title, $message, $type = 'info', $module = null, $related_id = null, $action_url = null) {
        $data = [
            'user_id'    => (int)$user_id,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'module'     => $module,
            'related_id' => $related_id ? (int)$related_id : null,
            'action_url' => $action_url,
            'is_read'    => 0,
            'is_deleted' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Create notifications for multiple users at once.
     *
     * @param array  $user_ids
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string $module
     * @param int    $related_id
     * @param string $action_url
     */
    public function create_bulk($user_ids, $title, $message, $type = 'info', $module = null, $related_id = null, $action_url = null) {
        $now = date('Y-m-d H:i:s');
        $rows = array();
        foreach ((array)$user_ids as $uid) {
            if (!(int)$uid) { continue; }
            $rows[] = [
                'user_id'    => (int)$uid,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'module'     => $module,
                'related_id' => $related_id ? (int)$related_id : null,
                'action_url' => $action_url,
                'is_read'    => 0,
                'is_deleted' => 0,
                'created_at' => $now,
            ];
        }
        if (!empty($rows)) {
            $this->db->insert_batch($this->table, $rows);
        }
    }

    /**
     * Get all undeleted notifications for a user, newest first.
     *
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_for_user($user_id, $limit = 50, $offset = 0) {
        return $this->db
            ->where('user_id', (int)$user_id)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get($this->table)
            ->result();
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_unread($user_id, $limit = 20) {
        return $this->db
            ->where('user_id', (int)$user_id)
            ->where('is_read', 0)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result();
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $user_id
     * @return int
     */
    public function count_unread($user_id) {
        return $this->db
            ->where('user_id', (int)$user_id)
            ->where('is_read', 0)
            ->where('is_deleted', 0)
            ->count_all_results($this->table);
    }

    /**
     * Mark a single notification as read.
     *
     * @param int $id
     * @param int $user_id  Security check — only mark own notifications
     */
    public function mark_read($id, $user_id) {
        $this->db->where('id', (int)$id)
                 ->where('user_id', (int)$user_id)
                 ->update($this->table, [
                     'is_read' => 1,
                     'read_at' => date('Y-m-d H:i:s'),
                 ]);
    }

    /**
     * Mark all notifications for a user as read.
     *
     * @param int $user_id
     */
    public function mark_all_read($user_id) {
        $this->db->where('user_id', (int)$user_id)
                 ->where('is_read', 0)
                 ->update($this->table, [
                     'is_read' => 1,
                     'read_at' => date('Y-m-d H:i:s'),
                 ]);
    }

    /**
     * Soft-delete a notification.
     *
     * @param int $id
     * @param int $user_id  Security check
     */
    public function delete($id, $user_id) {
        $this->db->where('id', (int)$id)
                 ->where('user_id', (int)$user_id)
                 ->update($this->table, ['is_deleted' => 1]);
    }

    /**
     * Hard-delete old notifications (for maintenance/cleanup).
     *
     * @param int $days  Delete notifications older than this many days
     */
    public function cleanup_old($days = 90) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $this->db->where('created_at <', $cutoff)->delete($this->table);
    }

    /**
     * Get a single notification by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function get($id) {
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }
}
