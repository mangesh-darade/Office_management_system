<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('web_push_send_to_users')) {
    /**
     * @param int[] $user_ids
     * @param array $payload title, body, url, tag, requireInteraction
     */
    function web_push_send_to_users($user_ids, $payload)
    {
        if (empty($user_ids)) {
            return;
        }
        $CI =& get_instance();
        $CI->load->helper('notifications_schema');
        if (!isset($CI->db)) {
            $CI->load->database();
        }
        notifications_schema_ensure_push_subscriptions($CI->db);
        if (!$CI->db->table_exists('push_subscriptions')) {
            return;
        }

        $CI->load->library('Web_push');
        $wp = Web_push::instance_from_settings();
        if (!$wp) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $user_ids = array_unique(array_map('intval', $user_ids));
        $user_ids = array_values(array_filter($user_ids, function ($id) {
            return $id > 0;
        }));
        if (empty($user_ids)) {
            return;
        }

        $CI->db->where_in('user_id', $user_ids);
        $rows = $CI->db->get('push_subscriptions')->result_array();
        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            try {
                $result = $wp->send($row, $json);
                if (!empty($result['expired'])) {
                    $CI->db->where('id', (int) $row['id'])->delete('push_subscriptions');
                }
            } catch (Exception $e) {
                log_message('error', 'Web push failed: ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('web_push_notify_chat_message')) {
    function web_push_notify_chat_message($conversation_id, $from_user_id, $message)
    {
        $conversation_id = (int) $conversation_id;
        $from_user_id = (int) $from_user_id;
        if ($conversation_id <= 0 || $from_user_id <= 0) {
            return;
        }

        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $participants = $CI->db->select('user_id')
            ->from('conversation_participants')
            ->where('conversation_id', $conversation_id)
            ->where('user_id !=', $from_user_id)
            ->get()->result();
        if (empty($participants)) {
            return;
        }

        $sender_name = 'Someone';
        $CI->load->helper('schema_columns');
        $sel = ['email'];
        if (schema_table_has_column($CI->db, 'users', 'full_name')) {
            $sel[] = 'full_name';
        } elseif (schema_table_has_column($CI->db, 'users', 'name')) {
            $sel[] = 'name';
        }
        $user = $CI->db->select(implode(',', $sel))->from('users')->where('id', $from_user_id)->get()->row();
        if ($user) {
            if (!empty($user->full_name)) {
                $sender_name = $user->full_name;
            } elseif (!empty($user->name)) {
                $sender_name = $user->name;
            } elseif (!empty($user->email)) {
                $sender_name = $user->email;
            }
        }

        $body = '';
        if (is_object($message)) {
            if (!empty($message->body)) {
                $body = (string) $message->body;
            } elseif (!empty($message->attachment_path)) {
                $body = 'Sent an attachment';
            }
        } elseif (is_array($message)) {
            if (!empty($message['body'])) {
                $body = (string) $message['body'];
            } elseif (!empty($message['attachment_path'])) {
                $body = 'Sent an attachment';
            }
        }
        if (strlen($body) > 180) {
            $body = substr($body, 0, 177) . '...';
        }
        if ($body === '') {
            $body = 'New message';
        }

        $recipient_ids = [];
        foreach ($participants as $p) {
            $recipient_ids[] = (int) $p->user_id;
        }

        $url = site_url('chats/app?open=' . $conversation_id);
        web_push_send_to_users($recipient_ids, [
            'title' => $sender_name,
            'body' => $body,
            'url' => $url,
            'tag' => 'chat-' . $conversation_id,
            'requireInteraction' => false,
        ]);
    }
}

if (!function_exists('web_push_notify_incoming_call')) {
    function web_push_notify_incoming_call($call_id, $from_user_id, $conversation_id, $to_user_ids)
    {
        $call_id = (int) $call_id;
        $from_user_id = (int) $from_user_id;
        $conversation_id = (int) $conversation_id;
        if ($call_id <= 0 || $from_user_id <= 0 || $conversation_id <= 0) {
            return;
        }

        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $from_label = 'Someone';
        $CI->load->helper('schema_columns');
        $sel = ['email'];
        if (schema_table_has_column($CI->db, 'users', 'full_name')) {
            $sel[] = 'full_name';
        } elseif (schema_table_has_column($CI->db, 'users', 'name')) {
            $sel[] = 'name';
        }
        $user = $CI->db->select(implode(',', $sel))->from('users')->where('id', $from_user_id)->get()->row();
        if ($user) {
            if (!empty($user->full_name)) {
                $from_label = $user->full_name;
            } elseif (!empty($user->name)) {
                $from_label = $user->name;
            } elseif (!empty($user->email)) {
                $from_label = $user->email;
            }
        }

        if (!is_array($to_user_ids)) {
            $to_user_ids = [$to_user_ids];
        }
        $to_user_ids = array_values(array_filter(array_map('intval', $to_user_ids), function ($id) use ($from_user_id) {
            return $id > 0 && $id !== $from_user_id;
        }));
        if (empty($to_user_ids)) {
            return;
        }

        $url = site_url('chats/app?open=' . $conversation_id . '&call=' . $call_id);
        web_push_send_to_users($to_user_ids, [
            'title' => 'Incoming call',
            'body' => $from_label . ' is calling you',
            'url' => $url,
            'tag' => 'call-' . $call_id,
            'requireInteraction' => true,
        ]);
    }
}
