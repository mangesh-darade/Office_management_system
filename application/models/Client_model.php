<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_model extends CI_Model {
    private $enc_prefix = 'ENC::';

    public function __construct(){ 
        parent::__construct(); 
        $this->load->database(); 
        $this->load->library('encryption');
        $this->encryption->initialize(['key' => $this->config->item('encryption_key')]);
    }

    public function count_clients($filters = []){
        $this->apply_filters($filters);
        return $this->db->count_all_results('clients');
    }

    public function get_clients($filters = [], $limit = null, $offset = 0){
        $this->db->from('clients');
        $this->apply_filters($filters);
        $this->db->order_by('created_at','DESC');
        if ($limit !== null){ $this->db->limit((int)$limit, (int)$offset); }
        $res = $this->db->get()->result();
        // Do not decrypt passwords in list view for performance and security
        // Only return them if explicitly needed, or obscure them
        foreach ($res as &$r){
            // clear passwords from listing
            if (isset($r->db_password)) { $r->db_password = '***'; }
        }
        return $res;
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
        $row = $this->db->where('id',(int)$id)->get('clients')->row();
        if ($row) {
            // Keep password as is (masked/encrypted) or decrypt if this method is used for edit forms?
            // Usually for edit forms we might leave it blank or show placeholder.
            // Let's decrypt it here but generic listing shouldn't.
            if (!empty($row->db_password)) {
                $row->db_password = $this->decrypt_password($row->db_password);
            }
        }
        return $row;
    }
    
    // Specific method to get sensitive credentials securely (backend only)
    public function get_client_credentials($id){
        $row = $this->db->select('db_name, db_username, db_password, pos_url, company_name')
                        ->where('id', (int)$id)
                        ->get('clients')
                        ->row();
        if ($row) {
            if ($this->db->field_exists('db_host', 'clients')) {
                $hostRow = $this->db->select('db_host')->where('id', (int) $id)->get('clients')->row();
                $row->db_host = $hostRow ? $hostRow->db_host : null;
            }
            if ($this->db->field_exists('db_port', 'clients')) {
                $portRow = $this->db->select('db_port')->where('id', (int) $id)->get('clients')->row();
                $row->db_port = $portRow ? $portRow->db_port : null;
            }
        }
        if ($row && !empty($row->db_password)){
            $row->db_password = $this->decrypt_password($row->db_password);
        }
        return $row;
    }

    public function create_client($data){
        if (isset($data['db_password']) && $data['db_password'] !== ''){
            $data['db_password'] = $this->encrypt_password($data['db_password']);
        }
        $this->db->insert('clients', $data);
        return (int)$this->db->insert_id();
    }

    public function update_client($id, $data){
        if (isset($data['db_password'])){
            if ($data['db_password'] !== '') {
                $data['db_password'] = $this->encrypt_password($data['db_password']);
            } else {
                // If empty, don't update password (keep existing)
                unset($data['db_password']);
            }
        }
        return $this->db->where('id',(int)$id)->update('clients', $data);
    }

    // Helper to Encrypt
    private function encrypt_password($plain){
        if (empty($plain)) return '';
        // Use CI Encryption
        $enc = $this->encryption->encrypt($plain);
        return $this->enc_prefix . base64_encode($enc);
    }

    // Helper to Decrypt
    private function decrypt_password($cipher){
        if (empty($cipher)) return '';
        // Check prefix
        if (strpos($cipher, $this->enc_prefix) === 0){
            $raw = substr($cipher, strlen($this->enc_prefix));
            $decoded = base64_decode($raw);
            return $this->encryption->decrypt($decoded);
        }
        // Fallback: assume plain text (legacy)
        return $cipher;
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

    /**
     * Check if email already exists in clients table
     * @param string $email Email address to check
     * @param int|null $exclude_id Client ID to exclude from check (for updates)
     * @return bool True if email exists, false otherwise
     */
    public function email_exists($email, $exclude_id = null){
        if (empty($email)) {
            return false;
        }
        $this->db->from('clients');
        $this->db->where('email', trim($email));
        if ($exclude_id !== null) {
            $this->db->where('id !=', (int)$exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }

    /**
     * Check if phone number already exists in clients table
     * @param string $phone Phone number to check
     * @param int|null $exclude_id Client ID to exclude from check (for updates)
     * @return bool True if phone exists, false otherwise
     */
    public function phone_exists($phone, $exclude_id = null){
        if (empty($phone)) {
            return false;
        }
        $this->db->from('clients');
        $this->db->where('phone', trim($phone));
        if ($exclude_id !== null) {
            $this->db->where('id !=', (int)$exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }
}
