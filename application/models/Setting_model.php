<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {
    private $table = 'settings';
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        if (!$this->db->table_exists($this->table)){
            $sql = "CREATE TABLE `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `key` varchar(190) NOT NULL,
                `value` text NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_setting_key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    public function get_setting($key, $default = null){
        $row = $this->db->get_where($this->table, ['key' => $key])->row();
        if ($row) { return $row->value; }
        return $default;
    }

    public function set_setting($key, $value){
        $exists = $this->db->get_where($this->table, ['key' => $key])->row();
        if ($exists){
            $this->db->where('id', (int)$exists->id)->update($this->table, ['value' => $value]);
        } else {
            $this->db->insert($this->table, ['key' => $key, 'value' => $value]);
        }
        return true;
    }

    public function get_all_settings(){
        $rows = $this->db->get($this->table)->result();
        $out = [];
        foreach ($rows as $r){ $out[$r->key] = $r->value; }
        return $out;
    }

    public function get_settings_by_group($group){
        $this->db->like('key', $group.'_', 'after');
        $rows = $this->db->get($this->table)->result();
        $out = [];
        foreach ($rows as $r){ $out[$r->key] = $r->value; }
        return $out;
    }
}
