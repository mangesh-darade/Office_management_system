<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset_model extends CI_Model {
    private $table = 'assets';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        if (!$this->db->table_exists($this->table)){
            $sql = "CREATE TABLE `assets` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(190) NOT NULL,
                `category` varchar(100) DEFAULT NULL,
                `brand` varchar(100) DEFAULT NULL,
                `model` varchar(100) DEFAULT NULL,
                `serial_no` varchar(190) DEFAULT NULL,
                `asset_tag` varchar(100) DEFAULT NULL,
                `ram` varchar(50) DEFAULT NULL,
                `hdd` varchar(50) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'in_stock',
                `purchased_on` date DEFAULT NULL,
                `notes` text,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        } else {
            // Ensure ram/hdd columns exist for older installs
            $fields = $this->db->list_fields($this->table);
            if (!in_array('ram', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `ram` varchar(50) DEFAULT NULL AFTER `asset_tag`");
            }
            if (!in_array('hdd', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `hdd` varchar(50) DEFAULT NULL AFTER `ram`");
            }
        }
        if (!$this->db->table_exists('asset_allocations')){
            $sql2 = "CREATE TABLE `asset_allocations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `asset_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `allocated_on` date NOT NULL,
                `returned_on` date DEFAULT NULL,
                `remarks` text,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_asset` (`asset_id`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql2);
        }
    }

    public function all_with_current_owner(){
        $this->db->select('a.*, aa.user_id, u.email');
        $this->db->from($this->table.' a');
        $this->db->join('asset_allocations aa', 'aa.asset_id = a.id AND aa.returned_on IS NULL', 'left');
        $this->db->join('users u', 'u.id = aa.user_id', 'left');
        $this->db->order_by('a.name', 'ASC');
        return $this->db->get()->result();
    }

    public function find($id){
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }

    public function create($data){
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update($id, $data){
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function assign_to_user($asset_id, $user_id, $date, $remarks){
        $asset_id = (int)$asset_id;
        $user_id = (int)$user_id;
        $date = $date ?: date('Y-m-d');
        // Close existing open allocation if any
        $this->db->where('asset_id', $asset_id)
                 ->where('returned_on IS NULL', null, false)
                 ->update('asset_allocations', [
                    'returned_on' => $date,
                    'updated_at' => date('Y-m-d H:i:s'),
                 ]);
        // Insert new allocation
        $this->db->insert('asset_allocations', [
            'asset_id' => $asset_id,
            'user_id' => $user_id,
            'allocated_on' => $date,
            'returned_on' => null,
            'remarks' => $remarks,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function current_allocation($asset_id){
        return $this->db->get_where('asset_allocations', [
            'asset_id' => (int)$asset_id,
            'returned_on' => null,
        ])->row();
    }

    public function mark_returned($asset_id, $date){
        $this->db->where('asset_id', (int)$asset_id)
                 ->where('returned_on IS NULL', null, false)
                 ->update('asset_allocations', [
                    'returned_on' => $date,
                    'updated_at' => date('Y-m-d H:i:s'),
                 ]);
    }

    public function assets_for_user($user_id){
        $this->db->select('a.*, aa.allocated_on, aa.remarks');
        $this->db->from($this->table.' a');
        $this->db->join('asset_allocations aa', 'aa.asset_id = a.id AND aa.returned_on IS NULL', 'inner');
        $this->db->where('aa.user_id', (int)$user_id);
        $this->db->order_by('aa.allocated_on', 'DESC');
        return $this->db->get()->result();
    }

    public function get_user_options(){
        $opts = [];
        if (!$this->db->table_exists('users')) { return $opts; }
        $rows = $this->db->select('id, email, name')
                         ->from('users')
                         ->order_by('email','ASC')
                         ->limit(500)
                         ->get()->result();
        foreach ($rows as $r){
            $label = $r->email;
            if (!empty($r->name)) { $label = $r->name.' <'.$r->email.'>'; }
            $opts[] = ['id' => (int)$r->id, 'label' => $label];
        }
        return $opts;
    }
}
