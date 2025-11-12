<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Announcement_model extends CI_Model {
    private $table = 'announcements';
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function get_active_announcements($user_role){
        $today = date('Y-m-d');
        $this->db->from($this->table)
                 ->where('status', 'published')
                 ->group_start()
                    ->where('start_date IS NULL', null, false)
                    ->or_where('start_date <=', $today)
                 ->group_end()
                 ->group_start()
                    ->where('end_date IS NULL', null, false)
                    ->or_where('end_date >=', $today)
                 ->group_end()
                 ->order_by('priority','DESC')
                 ->order_by('id','DESC');
        return $this->db->get()->result();
    }

    public function get_all_announcements($filters = []){
        $this->db->from($this->table);
        if (!empty($filters['status'])){ $this->db->where('status', $filters['status']); }
        if (!empty($filters['q'])){
            $this->db->group_start()
                     ->like('title', $filters['q'])
                     ->or_like('content', $filters['q'])
                     ->group_end();
        }
        $this->db->order_by('id','DESC');
        return $this->db->get()->result();
    }

    public function create($data){ $this->db->insert($this->table, $data); return (int)$this->db->insert_id(); }
    public function update($id, $data){ $this->db->where('id',(int)$id)->update($this->table, $data); return $this->db->affected_rows()>=0; }
    public function delete($id){ $this->db->where('id',(int)$id)->delete($this->table); return $this->db->affected_rows()>0; }
}
