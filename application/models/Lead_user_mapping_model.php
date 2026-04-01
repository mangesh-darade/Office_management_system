<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lead_user_mapping_model extends CI_Model {
    private $table = 'lead_user_mapping';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        if (!$this->db->table_exists($this->table)) {
            $this->db->query("CREATE TABLE `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_lead_user` (`lead_id`,`user_id`),
                KEY `idx_lead` (`lead_id`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
    }

    public function get_all_grouped()
    {
        $rows = $this->db->select('lead_id, user_id')->from($this->table)->get()->result();
        $out = [];
        foreach ($rows as $r) {
            $lid = (int)$r->lead_id;
            $uid = (int)$r->user_id;
            if ($lid <= 0 || $uid <= 0) { continue; }
            if (!isset($out[$lid])) { $out[$lid] = []; }
            $out[$lid][] = $uid;
        }
        foreach ($out as $lid => $uids) {
            $out[$lid] = array_values(array_unique(array_map('intval', $uids)));
        }
        return $out;
    }

    public function get_by_lead($lead_id)
    {
        $lead_id = (int)$lead_id;
        if ($lead_id <= 0) { return []; }
        $rows = $this->db->select('user_id')->from($this->table)->where('lead_id', $lead_id)->get()->result();
        $ids = [];
        foreach ($rows as $r) {
            $uid = isset($r->user_id) ? (int)$r->user_id : 0;
            if ($uid > 0) { $ids[] = $uid; }
        }
        return array_values(array_unique($ids));
    }

    public function save_mapping($lead_id, array $user_ids)
    {
        $lead_id = (int)$lead_id;
        if ($lead_id <= 0) { return false; }

        $clean = [];
        foreach ($user_ids as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) { $clean[] = $uid; }
        }
        $clean = array_values(array_unique($clean));

        $this->db->trans_start();
        $this->db->where('lead_id', $lead_id)->delete($this->table);
        foreach ($clean as $uid) {
            $this->db->insert($this->table, [
                'lead_id' => $lead_id,
                'user_id' => $uid,
            ]);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
