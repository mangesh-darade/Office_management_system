<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_model extends CI_Model {
    private $enc_prefix = 'ENC::';

    public function __construct(){ 
        parent::__construct(); 
        $this->load->database();
        $this->load->helper('schema_columns'); 
        $this->load->library('encryption');
        $this->encryption->initialize(['key' => $this->config->item('encryption_key')]);
    }

    public function count_clients($filters = []){
        $this->apply_filters($filters);
        return $this->db->count_all_results('clients');
    }

    /**
     * Status counts for list stats cards (ignores status filter; keeps type/search).
     *
     * @param array $filters
     * @return array code => int
     */
    public function counts_by_status($filters = [])
    {
        $f = is_array($filters) ? $filters : array();
        unset($f['status'], $f['sort'], $f['dir']);
        $this->db->select('IFNULL(NULLIF(TRIM(status), ""), "active") AS status, COUNT(*) AS cnt', false);
        $this->db->from('clients');
        $this->apply_filters($f);
        $this->db->group_by('status');
        $rows = $this->db->get()->result();
        $out = array();
        foreach ($rows as $r) {
            $code = isset($r->status) ? (string) $r->status : 'active';
            $out[$code] = (int) $r->cnt;
        }
        return $out;
    }

    /**
     * Type counts for category tabs (ignores client_type filter; keeps status/search).
     *
     * @param array $filters
     * @return array code => int
     */
    public function counts_by_client_type($filters = [])
    {
        $f = is_array($filters) ? $filters : array();
        unset($f['client_type'], $f['sort'], $f['dir']);
        $this->db->select('IFNULL(NULLIF(TRIM(client_type), ""), "company") AS client_type, COUNT(*) AS cnt', false);
        $this->db->from('clients');
        $this->apply_filters($f);
        $this->db->group_by('client_type');
        $rows = $this->db->get()->result();
        $out = array();
        foreach ($rows as $r) {
            $code = isset($r->client_type) ? (string) $r->client_type : 'company';
            $out[$code] = (int) $r->cnt;
        }
        return $out;
    }

    /**
     * @param array $ids
     * @return int deleted count
     */
    public function delete_clients_by_ids($ids)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (empty($ids)) {
            return 0;
        }
        if ($this->db->table_exists('client_activity')) {
            $this->db->where_in('client_id', $ids)->delete('client_activity');
        }
        $this->db->where_in('id', $ids)->delete('clients');
        return (int) $this->db->affected_rows();
    }

    public function get_clients($filters = [], $limit = null, $offset = 0){
        $this->db->from('clients');
        $this->apply_filters($filters);

        $allowed_sort = array(
            'client_type' => 'client_type',
            'company_name' => 'company_name',
            'created_at' => 'created_at',
        );
        $sort = isset($filters['sort']) ? (string) $filters['sort'] : '';
        $dir = isset($filters['dir']) ? strtolower((string) $filters['dir']) : 'asc';
        if ($dir !== 'desc') {
            $dir = 'asc';
        }
        if ($sort !== '' && isset($allowed_sort[$sort])) {
            $this->db->order_by($allowed_sort[$sort], strtoupper($dir));
            if ($sort !== 'company_name') {
                $this->db->order_by('company_name', 'ASC');
            }
        } else {
            $this->db->order_by('company_name', 'ASC');
            $this->db->order_by('client_type', 'ASC');
        }

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
                ->or_like('phone', $q)
                ->or_like('website', $q)
            ->group_end();
        }
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['ids'])));
            if (!empty($ids)) {
                $this->db->where_in('id', $ids);
            }
        }
    }

    public function get_client_by_code($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return false;
        }
        return $this->db->where('client_code', $code)->get('clients')->row();
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
            if (schema_table_has_column($this->db, 'clients', 'db_host')) {
                $hostRow = $this->db->select('db_host')->where('id', (int) $id)->get('clients')->row();
                $row->db_host = $hostRow ? $hostRow->db_host : null;
            }
            if (schema_table_has_column($this->db, 'clients', 'db_port')) {
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
        // Empty date strings break strict MySQL DATE columns.
        if (array_key_exists('onboarding_date', $data) && ($data['onboarding_date'] === '' || $data['onboarding_date'] === false)) {
            $data['onboarding_date'] = null;
        }
        if (!$this->db->insert('clients', $data)) {
            $err = $this->db->error();
            log_message('error', 'clients.create_client insert failed: ' . (isset($err['message']) ? $err['message'] : 'unknown'));
            return 0;
        }
        return (int) $this->db->insert_id();
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
        $id = (int) $id;
        $this->delete_activity_for_client($id);
        return $this->db->where('id', $id)->delete('clients');
    }

    public function log_activity($client_id, $user_id, $action, $old_value = null, $new_value = null)
    {
        if (!$this->db->table_exists('client_activity')) {
            return 0;
        }
        $allowed = array('created', 'updated', 'status_changed', 'contact_changed', 'urls_changed', 'commented', 'note');
        $action = (string) $action;
        if (!in_array($action, $allowed, true)) {
            $action = 'updated';
        }
        $old_json = null;
        $new_json = null;
        if ($old_value !== null) {
            $old_json = is_string($old_value) ? $old_value : json_encode($old_value);
        }
        if ($new_value !== null) {
            $new_json = is_string($new_value) ? $new_value : json_encode($new_value);
        }
        $this->db->insert('client_activity', array(
            'client_id' => (int) $client_id,
            'user_id' => (int) $user_id > 0 ? (int) $user_id : null,
            'action' => substr($action, 0, 50),
            'old_value' => $old_json,
            'new_value' => $new_json,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_activity($client_id, $limit = 50)
    {
        if (!$this->db->table_exists('client_activity')) {
            return array();
        }
        $select = array('a.*', 'u.email AS user_email');
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $select[] = 'u.name AS user_name';
        }
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $select[] = 'u.full_name AS user_full_name';
        }
        $this->db->select(implode(', ', $select), false);
        $this->db->from('client_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.client_id', (int) $client_id);
        $this->db->order_by('a.id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    /**
     * History timeline for Defects-style UI.
     *
     * @param int $client_id
     * @return array
     */
    public function list_history($client_id)
    {
        $client_id = (int) $client_id;
        $rows = array();

        foreach ($this->list_activity($client_id, 200) as $a) {
            $action = isset($a->action) ? (string) $a->action : 'updated';
            if ($action === 'commented') {
                $action = 'note';
            }
            $name = '';
            if (!empty($a->user_name)) {
                $name = (string) $a->user_name;
            } elseif (!empty($a->user_full_name)) {
                $name = (string) $a->user_full_name;
            } elseif (!empty($a->user_email)) {
                $name = (string) $a->user_email;
            }
            $rows[] = (object) array(
                'id' => (int) $a->id,
                'source' => 'activity',
                'action' => $action,
                'detail' => $this->format_activity_detail($a),
                'user_name' => $name,
                'user_id' => isset($a->user_id) ? (int) $a->user_id : 0,
                'created_at' => (string) $a->created_at,
                'sort_ts' => strtotime((string) $a->created_at) ?: 0,
            );
        }

        usort($rows, function ($a, $b) {
            if ($a->sort_ts === $b->sort_ts) {
                return $b->id - $a->id;
            }
            return ($a->sort_ts < $b->sort_ts) ? 1 : -1;
        });

        return $rows;
    }

    public function delete_activity_for_client($client_id)
    {
        if (!$this->db->table_exists('client_activity')) {
            return false;
        }
        $this->db->where('client_id', (int) $client_id)->delete('client_activity');
        return true;
    }

    public function format_activity_detail($row)
    {
        if (!$row) {
            return '';
        }
        $old = $this->_decode_activity_json(isset($row->old_value) ? $row->old_value : null);
        $new = $this->_decode_activity_json(isset($row->new_value) ? $row->new_value : null);
        $action = isset($row->action) ? (string) $row->action : '';

        if (!empty($new['detail'])) {
            return (string) $new['detail'];
        }

        if ($action === 'status_changed') {
            $from = $this->_activity_value_label(isset($old['status']) ? $old['status'] : '');
            $to = $this->_activity_value_label(isset($new['status']) ? $new['status'] : '');
            if ($from !== '' || $to !== '') {
                return $from . ' → ' . $to;
            }
        }

        if (($action === 'commented' || $action === 'note') && !empty($new['comment'])) {
            return (string) $new['comment'];
        }

        if ($action === 'created') {
            return 'Client created';
        }

        if ($action === 'urls_changed') {
            return !empty($new['detail']) ? (string) $new['detail'] : 'URL / DB sets updated';
        }

        if ($action === 'contact_changed') {
            return !empty($new['detail']) ? (string) $new['detail'] : 'Contact updated';
        }

        if (!empty($new['field']) && (isset($new['from']) || isset($new['to']))) {
            $label = ucwords(str_replace('_', ' ', (string) $new['field']));
            $from = isset($new['from']) ? (string) $new['from'] : '';
            $to = isset($new['to']) ? (string) $new['to'] : '';
            if ($from === '' && $to === '') {
                return $label . ' updated';
            }
            return $label . ': ' . ($from !== '' ? $from : '—') . ' → ' . ($to !== '' ? $to : '—');
        }

        return '';
    }

    /**
     * Log field-level changes on client edit (excludes passwords).
     *
     * @param int $client_id
     * @param int $user_id
     * @param object|array $old_row
     * @param array $new_data
     * @return void
     */
    public function log_client_changes($client_id, $user_id, $old_row, $new_data)
    {
        $client_id = (int) $client_id;
        $user_id = (int) $user_id;
        if ($client_id < 1 || !$this->db->table_exists('client_activity')) {
            return;
        }

        $old = is_array($old_row) ? $old_row : (array) $old_row;
        $fields = array(
            'status' => 'status',
            'company_name' => 'company_name',
            'contact_person' => 'contact_person',
            'email' => 'email',
            'phone' => 'phone',
            'alternate_phone' => 'alternate_phone',
            'website' => 'website',
            'demo_url' => 'demo_url',
            'pos_url' => 'pos_url',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'zip_code' => 'zip_code',
            'gstin' => 'gstin',
            'pan_number' => 'pan_number',
            'industry' => 'industry',
            'onboarding_date' => 'onboarding_date',
            'client_type' => 'client_type',
            'account_manager_id' => 'account_manager',
            'notes' => 'notes',
            'db_name' => 'db_name',
            'db_username' => 'db_username',
            'db_host' => 'db_host',
            'db_port' => 'db_port',
        );

        foreach ($fields as $field => $label) {
            if (!array_key_exists($field, $new_data)) {
                continue;
            }
            $old_val = isset($old[$field]) ? $old[$field] : null;
            $new_val = $new_data[$field];
            if ($this->_activity_values_equal($old_val, $new_val)) {
                continue;
            }
            if ($field === 'status') {
                $this->log_activity($client_id, $user_id, 'status_changed', array(
                    'status' => (string) $old_val,
                ), array(
                    'status' => (string) $new_val,
                ));
                continue;
            }
            $old_display = $this->_activity_scalar_display($old_val);
            $new_display = $this->_activity_scalar_display($new_val);
            if ($field === 'notes' || $field === 'address') {
                if (mb_strlen($old_display) > 120) {
                    $old_display = mb_substr($old_display, 0, 120) . '…';
                }
                if (mb_strlen($new_display) > 120) {
                    $new_display = mb_substr($new_display, 0, 120) . '…';
                }
            }
            if ($field === 'account_manager_id') {
                $old_display = $this->_activity_user_label((int) $old_val);
                $new_display = $this->_activity_user_label((int) $new_val);
            }
            $this->log_activity($client_id, $user_id, 'updated', null, array(
                'field' => $label,
                'from' => $old_display,
                'to' => $new_display,
            ));
        }
    }

    private function _decode_activity_json($raw)
    {
        if ($raw === null || $raw === '') {
            return array();
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function _activity_value_label($status)
    {
        $status = trim((string) $status);
        if ($status === '') {
            return '';
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    private function _activity_scalar_display($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value) && (string) (int) $value === (string) $value) {
            return (string) (int) $value;
        }
        return trim((string) $value);
    }

    private function _activity_values_equal($a, $b)
    {
        return $this->_activity_scalar_display($a) === $this->_activity_scalar_display($b);
    }

    private function _activity_user_label($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return '';
        }
        $select = array('email');
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $select[] = 'name';
        }
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $select[] = 'full_name';
        }
        $user = $this->db->select(implode(', ', $select))
            ->from('users')
            ->where('id', $user_id)
            ->get()
            ->row();
        if (!$user) {
            return 'User #' . $user_id;
        }
        if (!empty($user->name)) {
            return (string) $user->name;
        }
        if (!empty($user->full_name)) {
            return (string) $user->full_name;
        }
        if (!empty($user->email)) {
            return (string) $user->email;
        }
        return 'User #' . $user_id;
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
        if (schema_table_has_column($this->db, 'users', 'full_name')) { $select[] = 'full_name'; }
        if (schema_table_has_column($this->db, 'users', 'name')) { $select[] = 'name'; }
        $this->db->select(implode(',', $select));
        $this->db->from('users');
        if (schema_table_has_column($this->db, 'users', 'full_name')) {
            $this->db->order_by('full_name', 'ASC');
        }
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $this->db->order_by('name', 'ASC');
        }
        $this->db->order_by('email', 'ASC');
        return $this->db->get()->result();
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
