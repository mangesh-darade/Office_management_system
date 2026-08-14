<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Whatsapp_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    public function ensure_schema()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $this->load->helper('schema_columns');
        schema_safe_create_table($this->db, 'whatsapp_conversations', "CREATE TABLE `whatsapp_conversations` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `wa_id` varchar(32) NOT NULL,
            `profile_name` varchar(191) DEFAULT NULL,
            `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
            `last_message` text DEFAULT NULL,
            `last_direction` varchar(8) DEFAULT NULL,
            `last_at` datetime DEFAULT NULL,
            `unread_count` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_wa_id` (`wa_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        schema_safe_create_table($this->db, 'whatsapp_inbox_messages', "CREATE TABLE `whatsapp_inbox_messages` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `conversation_id` bigint(20) UNSIGNED NOT NULL,
            `wamid` varchar(191) DEFAULT NULL,
            `direction` varchar(8) NOT NULL,
            `msg_type` varchar(32) DEFAULT 'text',
            `body` text DEFAULT NULL,
            `status` varchar(32) DEFAULT NULL,
            `template_name` varchar(128) DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_wamid` (`wamid`),
            KEY `idx_conv` (`conversation_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        schema_safe_create_table($this->db, 'whatsapp_templates', "CREATE TABLE `whatsapp_templates` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `meta_id` varchar(64) DEFAULT NULL,
            `name` varchar(191) NOT NULL,
            `language` varchar(16) NOT NULL DEFAULT 'en_US',
            `category` varchar(64) DEFAULT NULL,
            `status` varchar(32) DEFAULT NULL,
            `body` text DEFAULT NULL,
            `synced_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_wa_tpl_name_lang` (`name`, `language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function list_conversations()
    {
        return $this->db->order_by('last_at', 'DESC')
            ->order_by('id', 'DESC')
            ->get('whatsapp_conversations')
            ->result();
    }

    public function get_conversation($id)
    {
        return $this->db->where('id', (int) $id)->get('whatsapp_conversations')->row();
    }

    public function list_messages($conversation_id, $limit = 200)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->order_by('id', 'ASC')
            ->limit((int) $limit)
            ->get('whatsapp_inbox_messages')
            ->result();
    }

    public function mark_read($conversation_id)
    {
        $this->db->where('id', (int) $conversation_id)->update('whatsapp_conversations', array(
            'unread_count' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        return true;
    }

    public function last_inbound_at($conversation_id)
    {
        $row = $this->db->select('created_at')
            ->where('conversation_id', (int) $conversation_id)
            ->where('direction', 'in')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('whatsapp_inbox_messages')
            ->row();
        return ($row && !empty($row->created_at)) ? $row->created_at : null;
    }

    public function last_inbound_wamid($conversation_id)
    {
        $row = $this->db->select('wamid')
            ->where('conversation_id', (int) $conversation_id)
            ->where('direction', 'in')
            ->where('wamid IS NOT NULL', null, false)
            ->where('wamid !=', '')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('whatsapp_inbox_messages')
            ->row();
        return ($row && !empty($row->wamid)) ? (string) $row->wamid : '';
    }

    public function get_template_by_name($name, $language = '')
    {
        $name = strtolower(trim((string) $name));
        if ($name === '') {
            return null;
        }
        $this->db->where('name', $name);
        if ($language !== '') {
            $this->db->where('language', $language);
        }
        return $this->db->order_by('id', 'DESC')->limit(1)->get('whatsapp_templates')->row();
    }

    public function upsert_conversation($wa_id, $profile_name = '')
    {
        $wa_id = normalize_whatsapp_phone($wa_id);
        if ($wa_id === '') {
            return false;
        }
        $row = $this->db->where('wa_id', $wa_id)->get('whatsapp_conversations')->row();
        $now = date('Y-m-d H:i:s');
        if ($row) {
            $update = array('updated_at' => $now);
            if ($profile_name !== '' && $profile_name !== null) {
                $update['profile_name'] = mb_substr((string) $profile_name, 0, 191);
            }
            $this->db->where('id', (int) $row->id)->update('whatsapp_conversations', $update);
            return (int) $row->id;
        }
        $this->db->insert('whatsapp_conversations', array(
            'wa_id' => $wa_id,
            'profile_name' => $profile_name !== '' ? mb_substr((string) $profile_name, 0, 191) : null,
            'unread_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        return (int) $this->db->insert_id();
    }

    public function record_inbound($wa_id, $profile_name, $body, $wamid, $msg_type = 'text', $raw = null)
    {
        $conversation_id = $this->upsert_conversation($wa_id, $profile_name);
        if (!$conversation_id) {
            return false;
        }
        if ($wamid !== '' && $wamid !== null) {
            $dup = $this->db->where('wamid', $wamid)->get('whatsapp_inbox_messages')->row();
            if ($dup) {
                return (int) $dup->id;
            }
        }
        $now = date('Y-m-d H:i:s');
        $this->db->insert('whatsapp_inbox_messages', array(
            'conversation_id' => $conversation_id,
            'wamid' => ($wamid !== '' && $wamid !== null) ? $wamid : null,
            'direction' => 'in',
            'msg_type' => mb_substr((string) $msg_type, 0, 32),
            'body' => $body,
            'status' => 'received',
            'created_by' => null,
            'created_at' => $now,
        ));
        $id = (int) $this->db->insert_id();
        $this->db->set('last_message', mb_substr((string) $body, 0, 500));
        $this->db->set('last_direction', 'in');
        $this->db->set('last_at', $now);
        $this->db->set('unread_count', 'unread_count+1', false);
        $this->db->set('updated_at', $now);
        $this->db->where('id', $conversation_id)->update('whatsapp_conversations');
        return $id;
    }

    public function record_outbound($wa_id, $body, $wamid, $user_id = 0, $template_name = '')
    {
        $conversation_id = $this->upsert_conversation($wa_id, '');
        if (!$conversation_id) {
            return false;
        }
        if ($wamid !== '' && $wamid !== null) {
            $dup = $this->db->where('wamid', $wamid)->get('whatsapp_inbox_messages')->row();
            if ($dup) {
                return (int) $dup->id;
            }
        }
        $now = date('Y-m-d H:i:s');
        $this->db->insert('whatsapp_inbox_messages', array(
            'conversation_id' => $conversation_id,
            'wamid' => ($wamid !== '' && $wamid !== null) ? $wamid : null,
            'direction' => 'out',
            'msg_type' => $template_name !== '' ? 'template' : 'text',
            'body' => $body,
            'status' => 'accepted',
            'template_name' => $template_name !== '' ? mb_substr($template_name, 0, 128) : null,
            'created_by' => $user_id > 0 ? $user_id : null,
            'created_at' => $now,
        ));
        $id = (int) $this->db->insert_id();
        $this->db->where('id', $conversation_id)->update('whatsapp_conversations', array(
            'last_message' => mb_substr((string) $body, 0, 500),
            'last_direction' => 'out',
            'last_at' => $now,
            'updated_at' => $now,
        ));
        return $id;
    }

    public function update_status_by_wamid($wamid, $status)
    {
        if ($wamid === '' || $wamid === null) {
            return false;
        }
        $this->db->where('wamid', $wamid)->update('whatsapp_inbox_messages', array(
            'status' => mb_substr((string) $status, 0, 32),
        ));
        return true;
    }

    public function list_templates($approved_only = true)
    {
        if ($approved_only) {
            $this->db->where('status', 'APPROVED');
        }
        $rows = $this->db->order_by('name', 'ASC')
            ->order_by('language', 'ASC')
            ->get('whatsapp_templates')
            ->result();
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id' => (int) $r->id,
                'name' => $r->name,
                'language' => $r->language,
                'category' => $r->category ? $r->category : '',
                'status' => $r->status ? strtoupper($r->status) : '',
                'body' => $r->body ? $r->body : '',
            );
        }
        return $out;
    }

    public function templates_synced_at()
    {
        $row = $this->db->select_max('synced_at', 'synced_at')->get('whatsapp_templates')->row();
        if ($row && !empty($row->synced_at)) {
            return $row->synced_at;
        }
        return null;
    }

    public function replace_templates($templates)
    {
        $this->db->trans_start();
        $this->db->empty_table('whatsapp_templates');
        $now = date('Y-m-d H:i:s');
        $count = 0;
        foreach ($templates as $t) {
            if (empty($t['name'])) {
                continue;
            }
            $this->db->insert('whatsapp_templates', array(
                'meta_id' => !empty($t['meta_id']) ? mb_substr((string) $t['meta_id'], 0, 64) : null,
                'name' => mb_substr((string) $t['name'], 0, 191),
                'language' => mb_substr(!empty($t['language']) ? (string) $t['language'] : 'en_US', 0, 16),
                'category' => !empty($t['category']) ? mb_substr((string) $t['category'], 0, 64) : null,
                'status' => !empty($t['status']) ? mb_substr(strtoupper((string) $t['status']), 0, 32) : null,
                'body' => isset($t['body']) ? $t['body'] : null,
                'synced_at' => $now,
            ));
            $count++;
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return 0;
        }
        return $count;
    }

    public function add_template($data)
    {
        $name = isset($data['name']) ? mb_substr(trim((string) $data['name']), 0, 191) : '';
        $name = strtolower($name);
        if ($name === '' || !preg_match('/^[a-z0-9_]+$/', $name)) {
            return false;
        }
        $language = isset($data['language']) ? mb_substr(trim((string) $data['language']), 0, 16) : 'en_US';
        if ($language === '' || !preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $language)) {
            $language = 'en_US';
        }
        $now = date('Y-m-d H:i:s');
        $row = array(
            'name' => $name,
            'language' => $language,
            'category' => !empty($data['category']) ? mb_substr((string) $data['category'], 0, 64) : 'UTILITY',
            'status' => !empty($data['status']) ? mb_substr(strtoupper((string) $data['status']), 0, 32) : 'APPROVED',
            'body' => isset($data['body']) ? $data['body'] : null,
            'synced_at' => $now,
        );
        $existing = $this->db->where('name', $name)->where('language', $language)->get('whatsapp_templates')->row();
        if ($existing) {
            $this->db->where('id', (int) $existing->id)->update('whatsapp_templates', $row);
            return (int) $existing->id;
        }
        $this->db->insert('whatsapp_templates', $row);
        $id = (int) $this->db->insert_id();
        return $id > 0 ? $id : false;
    }

    public function delete_template($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        $this->db->where('id', $id)->delete('whatsapp_templates');
        return $this->db->affected_rows() > 0;
    }
}
