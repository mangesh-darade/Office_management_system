<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Persistent AI assistant conversation history (separate from employee Chats).
 */
class Ai_conversation_model extends CI_Model
{
    protected $table = 'ai_conversations';
    protected $messages_table = 'ai_conversation_messages';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_schema();
    }

    public function ensure_schema()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ai_conv_user` (`user_id`),
            KEY `idx_ai_conv_updated` (`updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->messages_table}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `conversation_id` INT UNSIGNED NOT NULL,
            `role` ENUM('user','assistant') NOT NULL,
            `content` MEDIUMTEXT NOT NULL,
            `meta_json` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ai_msg_conv` (`conversation_id`),
            KEY `idx_ai_msg_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * @param int $user_id
     * @return object|null
     */
    public function get_or_create_active($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return null;
        }
        $row = $this->db->from($this->table)
            ->where('user_id', $user_id)
            ->order_by('updated_at', 'DESC')
            ->limit(1)
            ->get()
            ->row();
        if ($row) {
            return $row;
        }
        $this->db->insert($this->table, array(
            'user_id' => $user_id,
            'title' => 'Chat ' . date('Y-m-d H:i'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $id = (int) $this->db->insert_id();
        return $this->db->from($this->table)->where('id', $id)->get()->row();
    }

    /**
     * @param int $user_id
     * @return object|null
     */
    public function start_new($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return null;
        }
        $this->db->insert($this->table, array(
            'user_id' => $user_id,
            'title' => 'Chat ' . date('Y-m-d H:i'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $id = (int) $this->db->insert_id();
        return $this->db->from($this->table)->where('id', $id)->get()->row();
    }

    /**
     * @param int         $conversation_id
     * @param string      $role user|assistant
     * @param string      $content
     * @param array|null  $meta
     * @return int insert_id
     */
    public function add_message($conversation_id, $role, $content, $meta = null)
    {
        $conversation_id = (int) $conversation_id;
        if ($conversation_id < 1) {
            return 0;
        }
        $data = array(
            'conversation_id' => $conversation_id,
            'role' => ($role === 'assistant') ? 'assistant' : 'user',
            'content' => (string) $content,
            'meta_json' => $meta ? json_encode($meta) : null,
            'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert($this->messages_table, $data);
        $this->db->where('id', $conversation_id)->update($this->table, array(
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    /**
     * @param int $conversation_id
     * @param int $user_id
     * @param int $limit
     * @return array session-compatible history [{user},{assistant},...]
     */
    public function get_session_style_history($conversation_id, $user_id, $limit = 40)
    {
        $conversation_id = (int) $conversation_id;
        $user_id = (int) $user_id;
        if (!$this->owns($conversation_id, $user_id)) {
            return array();
        }
        $rows = $this->db->from($this->messages_table)
            ->where('conversation_id', $conversation_id)
            ->order_by('id', 'ASC')
            ->limit((int) $limit)
            ->get()
            ->result();
        $out = array();
        foreach ($rows as $r) {
            if ($r->role === 'user') {
                $out[] = array('user' => $r->content, 'timestamp' => $r->created_at);
            } else {
                $out[] = array('assistant' => $r->content, 'timestamp' => $r->created_at);
            }
        }
        return $out;
    }

    /**
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function list_for_user($user_id, $limit = 20)
    {
        return $this->db->from($this->table)
            ->where('user_id', (int) $user_id)
            ->order_by('updated_at', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /**
     * @param int $conversation_id
     * @param int $user_id
     * @return bool
     */
    public function owns($conversation_id, $user_id)
    {
        $n = (int) $this->db->from($this->table)
            ->where('id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->count_all_results();
        return $n > 0;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function clear_all_for_user($user_id)
    {
        $user_id = (int) $user_id;
        $ids = $this->db->select('id')->from($this->table)->where('user_id', $user_id)->get()->result_array();
        foreach ($ids as $row) {
            $this->db->where('conversation_id', (int) $row['id'])->delete($this->messages_table);
        }
        $this->db->where('user_id', $user_id)->delete($this->table);
    }
}
