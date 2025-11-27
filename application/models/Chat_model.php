<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model {
    public function ensure_schema() {
        // conversations
        $this->db->query("CREATE TABLE IF NOT EXISTS conversations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('dm','group') NOT NULL DEFAULT 'dm',
            title VARCHAR(255),
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (type), INDEX (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // participants
        $this->db->query("CREATE TABLE IF NOT EXISTS conversation_participants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            role ENUM('member','admin') NOT NULL DEFAULT 'member',
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_conv_user (conversation_id, user_id),
            INDEX (conversation_id), INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // messages
        $this->db->query("CREATE TABLE IF NOT EXISTS messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            body MEDIUMTEXT,
            attachment_path VARCHAR(512),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (conversation_id), INDEX (sender_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // reads
        $this->db->query("CREATE TABLE IF NOT EXISTS message_reads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id BIGINT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_msg_user (message_id, user_id), INDEX(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // typing indicators
        $this->db->query("CREATE TABLE IF NOT EXISTS typing_indicators (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            is_typing BOOLEAN NOT NULL DEFAULT TRUE,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_conv_user (conversation_id, user_id),
            INDEX (conversation_id), INDEX (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // online status
        $this->db->query("CREATE TABLE IF NOT EXISTS user_online_status (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            is_online BOOLEAN NOT NULL DEFAULT FALSE,
            last_seen DATETIME,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (user_id), INDEX (is_online), INDEX (last_seen)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // message reactions
        $this->db->query("CREATE TABLE IF NOT EXISTS message_reactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id BIGINT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            reaction VARCHAR(50) NOT NULL, -- emoji or reaction name
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_msg_user_reaction (message_id, user_id, reaction),
            INDEX (message_id), INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function list_users_for_select($filters = []) {
        $sel = ['id','email'];
        if ($this->db->field_exists('name','users')) { $sel[] = 'name'; }
        if ($this->db->field_exists('full_name','users')) { $sel[] = 'full_name'; }
        
        $this->db->select(implode(',', $sel))->from('users');
        
        // Apply group filtering for non-admin users
        if (!empty($filters) && isset($filters['user_id'])) {
            $user_id = $filters['user_id'];
            $this->db->join('employees e', 'e.user_id = users.id');
            $this->db->join('employees cu', 'cu.department = e.department');
            $this->db->where('cu.user_id', $user_id);
        }
        
        return $this->db->order_by('email','ASC')->get()->result();
    }

    public function find_user_by_email($email) {
        return $this->db->get_where('users', ['email' => $email])->row();
    }

    public function start_dm($user_a, $user_b) {
        // Check if existing DM between two users exists
        $sql = "SELECT c.id FROM conversations c
                JOIN conversation_participants p1 ON p1.conversation_id=c.id AND p1.user_id=?
                JOIN conversation_participants p2 ON p2.conversation_id=c.id AND p2.user_id=?
                WHERE c.type='dm' LIMIT 1";
        $row = $this->db->query($sql, [$user_a, $user_b])->row();
        if ($row) return (int)$row->id;
        $this->db->insert('conversations', ['type'=>'dm','created_by'=>$user_a]);
        $cid = (int)$this->db->insert_id();
        $this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$user_a,'role'=>'admin']);
        $this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$user_b,'role'=>'member']);
        return $cid;
    }

    public function create_group($creator_id, $title, $participant_ids) {
        $this->db->insert('conversations', ['type'=>'group','title'=>$title,'created_by'=>$creator_id]);
        $cid = (int)$this->db->insert_id();
        $this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$creator_id,'role'=>'admin']);
        foreach ($participant_ids as $uid) {
            if ($uid == $creator_id) continue;
            @$this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$uid,'role'=>'member']);
        }
        return $cid;
    }

    public function is_participant($conversation_id, $user_id) {
        return (bool)$this->db->get_where('conversation_participants', ['conversation_id'=>$conversation_id,'user_id'=>$user_id])->row();
    }

    public function list_conversations($user_id, $filters = []) {
        $sql = "SELECT c.*, 
                       GROUP_CONCAT(u.email ORDER BY u.email SEPARATOR ', ') AS members
                FROM conversations c
                JOIN conversation_participants cp ON cp.conversation_id=c.id
                JOIN users u ON u.id=cp.user_id";
        
        // Apply group filtering for non-admin users
        if (!empty($filters) && isset($filters['user_id'])) {
            $sql .= " JOIN conversation_participants cp2 ON cp2.conversation_id=c.id
                      WHERE cp.user_id = ? AND cp2.user_id IN (
                          SELECT user_id FROM employees WHERE department IN (
                              SELECT department FROM employees WHERE user_id = ?
                          )
                      )";
        } else {
            $sql .= " WHERE c.id IN (SELECT conversation_id FROM conversation_participants WHERE user_id=?)";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        if (!empty($filters) && isset($filters['user_id'])) {
            return $this->db->query($sql, [$user_id, $user_id])->result();
        } else {
            return $this->db->query($sql, [$user_id])->result();
        }
    }

    public function get_conversation($id) {
        return $this->db->get_where('conversations', ['id'=>$id])->row();
    }

    // Typing indicator methods
    public function set_typing($conversation_id, $user_id, $is_typing = true) {
        if ($is_typing) {
            $this->db->replace('typing_indicators', [
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'is_typing' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->db->delete('typing_indicators', [
                'conversation_id' => $conversation_id,
                'user_id' => $user_id
            ]);
        }
    }

    public function get_typing_users($conversation_id, $exclude_user_id = null) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->where('is_typing', 1);
        $this->db->where('updated_at >', date('Y-m-d H:i:s', strtotime('-5 seconds')));
        if ($exclude_user_id) {
            $this->db->where('user_id !=', $exclude_user_id);
        }
        return $this->db->get('typing_indicators')->result();
    }

    // Online status methods
    public function set_online_status($user_id, $is_online = true) {
        $this->db->replace('user_online_status', [
            'user_id' => $user_id,
            'is_online' => $is_online,
            'last_seen' => $is_online ? null : date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function get_online_status($user_ids) {
        if (!is_array($user_ids)) {
            $user_ids = [$user_ids];
        }
        $this->db->where_in('user_id', $user_ids);
        return $this->db->get('user_online_status')->result();
    }

    public function cleanup_old_typing_indicators() {
        $this->db->where('updated_at <', date('Y-m-d H:i:s', strtotime('-10 seconds')));
        $this->db->delete('typing_indicators');
    }

    // Message reaction methods
    public function add_reaction($message_id, $user_id, $reaction) {
        $this->db->replace('message_reactions', [
            'message_id' => $message_id,
            'user_id' => $user_id,
            'reaction' => $reaction,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function remove_reaction($message_id, $user_id, $reaction) {
        $this->db->delete('message_reactions', [
            'message_id' => $message_id,
            'user_id' => $user_id,
            'reaction' => $reaction
        ]);
    }

    public function get_message_reactions($message_id) {
        $this->db->select('reaction, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids');
        $this->db->where('message_id', $message_id);
        $this->db->group_by('reaction');
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get('message_reactions')->result();
    }

    public function get_user_reaction($message_id, $user_id) {
        return $this->db->get_where('message_reactions', [
            'message_id' => $message_id,
            'user_id' => $user_id
        ])->row();
    }

    // Enhanced fetch messages with reactions
    public function fetch_messages_with_reactions($conversation_id, $since_id = 0) {
        $sql = "SELECT m.*, u.email, 
                       (SELECT GROUP_CONCAT(CONCAT(reaction, ':', COUNT(*)) SEPARATOR '|') 
                        FROM message_reactions mr WHERE mr.message_id = m.id) as reactions
                FROM messages m
                LEFT JOIN users u ON u.id = m.sender_id
                WHERE m.conversation_id = ? AND m.id > ?
                ORDER BY m.created_at ASC";
        return $this->db->query($sql, [$conversation_id, $since_id])->result();
    }

    public function participants($conversation_id) {
        $cols = ['u.id', 'u.email'];
        if ($this->db->field_exists('name','users')) { $cols[] = 'u.name'; }
        $sql = "SELECT ".implode(', ', $cols)." FROM conversation_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.conversation_id=? ORDER BY u.email";
        return $this->db->query($sql, [$conversation_id])->result();
    }

    public function add_message($conversation_id, $sender_id, $body, $attachment_path=null) {
        $this->db->insert('messages', [
            'conversation_id'=>$conversation_id,
            'sender_id'=>$sender_id,
            'body'=>$body,
            'attachment_path'=>$attachment_path,
        ]);
        return (int)$this->db->insert_id();
    }

    public function fetch_messages($conversation_id, $since_id=0) {
        $sel = ['m.*', 'u.email'];
        if ($this->db->field_exists('name','users')) { $sel[] = 'u.name'; }
        $this->db->select(implode(', ', $sel))
                 ->from('messages m')
                 ->join('users u', 'u.id=m.sender_id', 'left')
                 ->where('m.conversation_id', $conversation_id);
        if ($since_id > 0) { $this->db->where('m.id >', (int)$since_id); }
        $this->db->order_by('m.id','ASC');
        return $this->db->get()->result();
    }

    public function add_participants($conversation_id, $user_ids) {
        foreach ($user_ids as $uid) {
            if (!$uid) continue;
            // ignore duplicates via unique key
            @$this->db->insert('conversation_participants', [
                'conversation_id'=>$conversation_id,
                'user_id'=>$uid,
                'role'=>'member'
            ]);
        }
    }

    public function remove_participant($conversation_id, $user_id) {
        $this->db->where(['conversation_id'=>$conversation_id,'user_id'=>$user_id])->delete('conversation_participants');
    }
}
