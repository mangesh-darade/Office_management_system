<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function count_clients($filters = []){
        $this->apply_filters($filters);
        return $this->db->count_all_results('clients');
    }

    public function get_clients($filters = [], $limit = null, $offset = 0){
        $this->db->from('clients');
        $this->apply_filters($filters);
        $this->db->order_by('created_at','DESC');
        if ($limit !== null){ $this->db->limit((int)$limit, (int)$offset); }
        return $this->db->get()->result();
    }

    private function apply_filters($filters){
        if (!empty($filters['status'])){ $this->db->where('status', $filters['status']); }
        if (!empty($filters['client_type'])){ $this->db->where('client_type', $filters['client_type']); }
        if (!empty($filters['search'])){
            $q = trim((string)$filters['search']);
            $this->db->group_start()
                ->like('company_name', $q)
                ->or_like('client_code', $q)
                ->or_like('contact_person', $q)
                ->or_like('email', $q)
            ->group_end();
        }
    }

    public function get_client($id){
        return $this->db->where('id',(int)$id)->get('clients')->row();
    }

    public function create_client($data){
        $this->db->insert('clients', $data);
        return (int)$this->db->insert_id();
    }

    public function update_client($id, $data){
        return $this->db->where('id',(int)$id)->update('clients', $data);
    }

    public function delete_client($id){
        return $this->db->where('id',(int)$id)->delete('clients');
    }

    public function generate_client_code(){
        $year = date('Y');
        $prefix = 'CLI-'.$year.'-';
        $row = $this->db->like('client_code',$prefix,'after')->order_by('id','DESC')->limit(1)->get('clients')->row();
        $num = 0;
        if ($row && isset($row->client_code)){
            $tail = substr($row->client_code, -5);
            if (ctype_digit($tail)) { $num = (int)$tail; }
        }
        $num++;
        return $prefix.str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function get_client_contacts($client_id){
        return $this->db->where('client_id',(int)$client_id)->order_by('is_primary','DESC')->order_by('id','ASC')->get('client_contacts')->result();
    }

    public function add_contact($data){
        if (isset($data['is_primary']) && (int)$data['is_primary'] === 1){
            $this->db->where('client_id',(int)$data['client_id'])->update('client_contacts',['is_primary'=>0]);
        }
        $this->db->insert('client_contacts',$data);
        return (int)$this->db->insert_id();
    }

    public function count_client_requirements($client_id){
        return $this->db->where('client_id',(int)$client_id)->count_all_results('requirements');
    }

    public function get_account_managers(){
        $select = ['id','email'];
        if ($this->db->field_exists('full_name','users')) { $select[] = 'full_name'; }
        if ($this->db->field_exists('name','users')) { $select[] = 'name'; }
        return $this->db->select(implode(',', $select))->from('users')->order_by('email','ASC')->get()->result();
    }
}
