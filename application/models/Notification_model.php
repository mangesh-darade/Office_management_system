<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Schema_columns_trait.php';

/**
 * Notification_model
 * Handles all database operations for in-app notifications.
 */
class Notification_model extends CI_Model {
    use Schema_columns_trait;

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
    public function ensure_schema() {
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
        } else {
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
                if (!$this->has_column($col)) {
                    $this->db->query($alter_sql);
                }
            }
        }

        $this->load->helper('notifications_schema');
        notifications_schema_ensure_push_subscriptions($this->db);

        $this->backfill_legacy_notifications();
    }

    /**
     * One-time-safe migration for legacy rows (body/payload/read_at).
     */
    private function backfill_legacy_notifications() {
        if (!$this->db->table_exists($this->table)) {
            return;
        }

        if ($this->has_column('body') && $this->has_column('message')) {
            $this->db->query(
                "UPDATE `{$this->table}` SET `message` = `body`"
                . " WHERE (`message` IS NULL OR `message` = '') AND `body` IS NOT NULL AND TRIM(`body`) != ''"
            );
        }

        if ($this->has_column('read_at') && $this->has_column('is_read')) {
            $this->db->query(
                "UPDATE `{$this->table}` SET `is_read` = 1"
                . " WHERE `read_at` IS NOT NULL AND `is_read` = 0"
            );
        }

        if (!$this->has_column('payload')) {
            return;
        }

        $rows = $this->db->query(
            "SELECT id, module, related_id, action_url, payload FROM `{$this->table}`"
            . " WHERE payload IS NOT NULL AND TRIM(payload) != ''"
            . " AND ((module IS NULL OR module = '') OR action_url IS NULL OR action_url = '')"
            . " LIMIT 300"
        )->result();

        foreach ($rows as $row) {
            $payload = json_decode((string) $row->payload, true);
            if (!is_array($payload)) {
                continue;
            }
            $update = array();
            if ((empty($row->module) || trim((string) $row->module) === '') && !empty($payload['module'])) {
                $update['module'] = (string) $payload['module'];
            }
            if (empty($row->related_id) && !empty($payload['related_id'])) {
                $update['related_id'] = (int) $payload['related_id'];
            }
            if ((empty($row->action_url) || trim((string) $row->action_url) === '') && !empty($payload['action_url'])) {
                $update['action_url'] = (string) $payload['action_url'];
            }
            if (!empty($update)) {
                $this->db->where('id', (int) $row->id)->update($this->table, $update);
            }
        }
    }

    private function normalize_rows($rows) {
        $this->load->helper('notification');
        if (!is_array($rows)) {
            return $rows;
        }
        return notification_normalize_rows($rows);
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
        $rows = $this->db
            ->where('user_id', (int)$user_id)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get($this->table)
            ->result();
        return $this->normalize_rows($rows);
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_unread($user_id, $limit = 20) {
        $rows = $this->db
            ->where('user_id', (int)$user_id)
            ->where('is_read', 0)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result();
        return $this->normalize_rows($rows);
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $user_id
     * @return int
     */
    public function count_unread($user_id) {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('is_deleted', 0);
        $this->apply_unread_where();
        return (int) $this->db->count_all_results($this->table);
    }

    private function apply_unread_where() {
        $this->db->group_start();
        $this->db->where('is_read', 0);
        if ($this->has_column('read_at')) {
            $this->db->or_group_start();
            $this->db->where('is_read IS NULL', null, false);
            $this->db->where('read_at IS NULL', null, false);
            $this->db->group_end();
        } else {
            $this->db->or_where('is_read IS NULL', null, false);
        }
        $this->db->group_end();
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
                 ->where('is_deleted', 0)
                 ->update($this->table, [
                     'is_read' => 1,
                     'read_at' => date('Y-m-d H:i:s'),
                 ]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Mark all notifications for a user as read.
     *
     * @param int $user_id
     */
    public function mark_all_read($user_id) {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('is_deleted', 0);
        $this->apply_unread_where();
        $this->db->update($this->table, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->affected_rows();
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
        $row = $this->db->get_where($this->table, ['id' => (int)$id])->row();
        $this->load->helper('notification');
        return notification_normalize_row($row);
    }
}
