<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Call_model extends CI_Model {
    public function ensure_schema() {
        $this->db->query("CREATE TABLE IF NOT EXISTS calls (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            initiator_id INT UNSIGNED NOT NULL,
            status ENUM('initiated','ringing','connected','ended','failed') NOT NULL DEFAULT 'initiated',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ended_at DATETIME,
            INDEX(conversation_id), INDEX(initiator_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS call_participants (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            call_id BIGINT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            joined_at DATETIME,
            left_at DATETIME,
            UNIQUE KEY uniq_call_user (call_id, user_id), INDEX(call_id), INDEX(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS signaling_messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            call_id BIGINT UNSIGNED NOT NULL,
            from_user_id INT UNSIGNED NOT NULL,
            to_user_id INT UNSIGNED NULL,
            type ENUM('offer','answer','ice') NOT NULL,
            payload MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX(call_id), INDEX(to_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function start_call($conversation_id, $initiator_id) {
        $this->db->insert('calls', [
            'conversation_id'=>$conversation_id,
            'initiator_id'=>$initiator_id,
            'status'=>'initiated',
        ]);
        $call_id = (int)$this->db->insert_id();
        // creator joins by default
        @$this->db->insert('call_participants', ['call_id'=>$call_id,'user_id'=>$initiator_id,'joined_at'=>date('Y-m-d H:i:s')]);
        return $call_id;
    }

    public function add_signal($call_id, $from_user_id, $to_user_id, $type, $payload) {
        $this->db->insert('signaling_messages', [
            'call_id'=>$call_id,
            'from_user_id'=>$from_user_id,
            'to_user_id'=>$to_user_id,
            'type'=>$type,
            'payload'=>$payload,
        ]);
        return (int)$this->db->insert_id();
    }

    public function fetch_signals($call_id, $since_id, $for_user_id) {
        $this->db->from('signaling_messages')
                 ->where('call_id', $call_id)
                 ->where('id >', (int)$since_id)
                 ->group_start()
                    ->where('to_user_id IS NULL', null, false)
                    ->or_where('to_user_id', $for_user_id)
                 ->group_end()
                 ->order_by('id','ASC');
        return $this->db->get()->result();
    }

    public function end_call($call_id) {
        $this->db->where('id',$call_id)->update('calls', ['status'=>'ended','ended_at'=>date('Y-m-d H:i:s')]);
    }
}
