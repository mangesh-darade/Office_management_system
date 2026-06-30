<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meeting_model extends CI_Model {

    public function ensure_schema() {
        if (!$this->db->table_exists('scheduled_meetings')) {
            $sql = "CREATE TABLE `scheduled_meetings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `conversation_id` int(11) DEFAULT NULL,
                `title` varchar(255) NOT NULL,
                `room_name` varchar(120) NOT NULL,
                `scheduled_at` datetime NOT NULL,
                `duration_minutes` int(11) NOT NULL DEFAULT 60,
                `created_by` int(11) NOT NULL,
                `host_user_id` int(11) NOT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'scheduled',
                `meeting_password` varchar(64) DEFAULT NULL,
                `participants_json` text DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_conversation_id` (`conversation_id`),
                KEY `idx_scheduled_at` (`scheduled_at`),
                KEY `idx_status` (`status`),
                KEY `idx_host_user_id` (`host_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        if (!$this->db->table_exists('active_meetings')) {
            $sql = "CREATE TABLE `active_meetings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `conversation_id` int(11) NOT NULL,
                `room_name` varchar(120) NOT NULL,
                `host_user_id` int(11) NOT NULL,
                `started_at` datetime NOT NULL,
                `ended_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_conversation_id` (`conversation_id`),
                KEY `idx_room_name` (`room_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        if (!$this->db->table_exists('conversation_meeting_rooms')) {
            $sql = "CREATE TABLE `conversation_meeting_rooms` (
                `conversation_id` int(11) NOT NULL,
                `room_name` varchar(120) NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`conversation_id`),
                UNIQUE KEY `idx_room_name` (`room_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    public function generate_secure_room_name() {
        return 'oms-' . bin2hex(random_bytes(16));
    }

    public function get_or_create_room_name($conversation_id) {
        $conversation_id = (int) $conversation_id;
        $row = $this->db->get_where('conversation_meeting_rooms', array(
            'conversation_id' => $conversation_id,
        ))->row();
        if ($row && !empty($row->room_name)) {
            return $row->room_name;
        }
        $room_name = $this->generate_secure_room_name();
        $this->db->insert('conversation_meeting_rooms', array(
            'conversation_id' => $conversation_id,
            'room_name' => $room_name,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return $room_name;
    }

    public function room_name_for_conversation($conversation_id) {
        return $this->get_or_create_room_name($conversation_id);
    }

    public function is_participant($conversation_id, $user_id) {
        $row = $this->db->get_where('conversation_participants', array(
            'conversation_id' => (int) $conversation_id,
            'user_id' => (int) $user_id,
        ))->row();
        return (bool) $row;
    }

    public function is_conversation_creator($conversation_id, $user_id) {
        $row = $this->db->select('created_by')
            ->from('conversations')
            ->where('id', (int) $conversation_id)
            ->get()
            ->row();
        if (!$row) {
            return false;
        }
        return ((int) $row->created_by === (int) $user_id);
    }

    public function mark_active($conversation_id, $room_name, $host_user_id) {
        $this->db->where('conversation_id', (int) $conversation_id);
        $this->db->where('ended_at IS NULL', null, false);
        $this->db->update('active_meetings', array('ended_at' => date('Y-m-d H:i:s')));

        $this->db->insert('active_meetings', array(
            'conversation_id' => (int) $conversation_id,
            'room_name' => $room_name,
            'host_user_id' => (int) $host_user_id,
            'started_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function mark_ended($conversation_id) {
        $this->db->where('conversation_id', (int) $conversation_id);
        $this->db->where('ended_at IS NULL', null, false);
        $this->db->update('active_meetings', array('ended_at' => date('Y-m-d H:i:s')));
    }

    public function get_active($conversation_id) {
        return $this->db->from('active_meetings')
            ->where('conversation_id', (int) $conversation_id)
            ->where('ended_at IS NULL', null, false)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row();
    }

    public function list_participants($conversation_id) {
        $this->db->select('u.id, u.email, u.name, cp.role');
        $this->db->from('conversation_participants cp');
        $this->db->join('users u', 'u.id = cp.user_id', 'left');
        $this->db->where('cp.conversation_id', (int) $conversation_id);
        $this->db->order_by('u.name', 'ASC');
        return $this->db->get()->result();
    }

    public function generate_jitsi_jwt($room_name, $user_name, $user_email, $is_moderator, $domain, $app_id, $jwt_secret) {
        if ($jwt_secret === '' || $app_id === '' || $domain === '') {
            return null;
        }

        $header = array('alg' => 'HS256', 'typ' => 'JWT');
        $now = time();
        $payload = array(
            'aud' => 'jitsi',
            'iss' => $app_id,
            'sub' => $domain,
            'room' => $room_name,
            'exp' => $now + 7200,
            'moderator' => $is_moderator ? true : false,
            'context' => array(
                'user' => array(
                    'name' => $user_name,
                    'email' => $user_email,
                    'moderator' => $is_moderator ? 'true' : 'false',
                ),
                'features' => array(
                    'recording' => $is_moderator ? true : false,
                    'livestreaming' => false,
                ),
            ),
        );

        $segments = array(
            $this->_base64url_encode(json_encode($header)),
            $this->_base64url_encode(json_encode($payload)),
        );
        $signing_input = implode('.', $segments);
        $signature = hash_hmac('sha256', $signing_input, $jwt_secret, true);
        $segments[] = $this->_base64url_encode($signature);
        return implode('.', $segments);
    }

    private function _base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function create_scheduled($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('scheduled_meetings', $data);
        return (int) $this->db->insert_id();
    }

    public function get_scheduled($id) {
        return $this->db->get_where('scheduled_meetings', array('id' => (int) $id))->row();
    }

    public function list_upcoming($user_id, $limit = 20) {
        $user_id = (int) $user_id;
        $this->db->from('scheduled_meetings sm');
        $this->db->where('sm.scheduled_at >=', date('Y-m-d H:i:s', time() - 3600));
        $this->db->where_in('sm.status', array('scheduled', 'live'));
        $this->db->group_start();
        $this->db->where('sm.host_user_id', $user_id);
        $this->db->or_where('sm.created_by', $user_id);
        $this->db->or_where('sm.conversation_id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ' . $user_id . ')', null, false);
        $this->db->group_end();
        $this->db->order_by('sm.scheduled_at', 'ASC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function cancel_scheduled($id, $user_id) {
        $row = $this->get_scheduled($id);
        if (!$row) {
            return false;
        }
        if ((int) $row->host_user_id !== (int) $user_id && (int) $row->created_by !== (int) $user_id) {
            return false;
        }
        $this->db->where('id', (int) $id);
        $this->db->update('scheduled_meetings', array(
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        return true;
    }

    public function update_scheduled_status($id, $status) {
        $this->db->where('id', (int) $id);
        $this->db->update('scheduled_meetings', array(
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function can_access_scheduled($row, $user_id, $user_email = '') {
        if (!$row) {
            return false;
        }
        $user_id = (int) $user_id;
        if ((int) $row->host_user_id === $user_id || (int) $row->created_by === $user_id) {
            return true;
        }
        if ($row->conversation_id && $this->is_participant((int) $row->conversation_id, $user_id)) {
            return true;
        }
        if (!$row->conversation_id && $user_email !== '' && !empty($row->participants_json)) {
            $invited = json_decode($row->participants_json, true);
            if (is_array($invited)) {
                $user_email = strtolower(trim($user_email));
                foreach ($invited as $em) {
                    if (strtolower(trim((string) $em)) === $user_email) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
