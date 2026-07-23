<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_url_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('schema_columns');
    }

    /**
     * @param array $filters keys: client_id, url_type, version, q
     * @return array
     */
    public function list_all($filters = array())
    {
        if (!$this->db->table_exists('client_urls')) {
            return array();
        }
        $this->db->select('cu.*, c.company_name, c.client_code');
        $this->db->from('client_urls cu');
        $this->db->join('clients c', 'c.id = cu.client_id', 'left');
        if (!empty($filters['client_id'])) {
            $this->db->where('cu.client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['url_type'])) {
            $this->db->where('cu.url_type', (string) $filters['url_type']);
        }
        if (!empty($filters['version'])) {
            $this->db->where('cu.version', (string) $filters['version']);
        }
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            if ($q !== '') {
                $this->db->group_start();
                $this->db->like('c.company_name', $q);
                $this->db->or_like('c.client_code', $q);
                $this->db->or_like('cu.url', $q);
                $this->db->or_like('cu.version', $q);
                $this->db->or_like('cu.db_name', $q);
                $this->db->or_like('cu.db_host', $q);
                $this->db->group_end();
            }
        }
        $this->db->order_by('c.company_name', 'ASC');
        $this->db->order_by('cu.id', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : array();
    }

    /**
     * Distinct versions for filter dropdown.
     *
     * @return array
     */
    public function list_versions()
    {
        if (!$this->db->table_exists('client_urls')) {
            return array();
        }
        $this->db->distinct();
        $this->db->select('version');
        $this->db->from('client_urls');
        $this->db->where("version IS NOT NULL AND TRIM(version) != ''", null, false);
        $this->db->order_by('version', 'ASC');
        $q = $this->db->get();
        if (!$q) {
            return array();
        }
        $out = array();
        foreach ($q->result() as $row) {
            if (isset($row->version) && $row->version !== '') {
                $out[] = (string) $row->version;
            }
        }
        return $out;
    }

    /**
     * @param int $client_id
     * @return array
     */
    public function get_by_client($client_id)
    {
        $client_id = (int) $client_id;
        if ($client_id <= 0 || !$this->db->table_exists('client_urls')) {
            return array();
        }
        $q = $this->db->where('client_id', $client_id)
            ->order_by('id', 'ASC')
            ->get('client_urls');
        return $q ? $q->result() : array();
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $id = (int) $id;
        if ($id <= 0 || !$this->db->table_exists('client_urls')) {
            return null;
        }
        $row = $this->db->where('id', $id)->get('client_urls')->row();
        return $row ? $row : null;
    }

    /**
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        if (!$this->db->table_exists('client_urls')) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $row = array(
            'client_id' => (int) $data['client_id'],
            'version' => isset($data['version']) ? (string) $data['version'] : '1.0',
            'url' => isset($data['url']) ? (string) $data['url'] : '',
            'url_type' => isset($data['url_type']) ? (string) $data['url_type'] : 'website',
            'created_at' => $now,
            'updated_at' => $now,
        );
        if (!empty($data['created_by'])) {
            $row['created_by'] = (int) $data['created_by'];
        }
        foreach (array('db_name', 'db_username', 'db_password', 'db_host', 'db_port') as $col) {
            if (array_key_exists($col, $data) && schema_table_has_column($this->db, 'client_urls', $col)) {
                $val = $data[$col];
                $row[$col] = ($val !== null && $val !== '') ? (string) $val : null;
            }
        }
        $ok = $this->db->insert('client_urls', $row);
        return $ok ? (int) $this->db->insert_id() : false;
    }

    /**
     * Replace all URL/DB sets for a client.
     *
     * @param int $client_id
     * @param array $rows
     * @param int|null $created_by
     * @return bool
     */
    public function replace_for_client($client_id, array $rows, $created_by = null)
    {
        $client_id = (int) $client_id;
        if ($client_id < 1 || !$this->db->table_exists('client_urls')) {
            return false;
        }
        $this->db->where('client_id', $client_id)->delete('client_urls');
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['client_id'] = $client_id;
            if ($created_by !== null) {
                $row['created_by'] = (int) $created_by;
            }
            $this->insert($row);
        }
        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $id = (int) $id;
        if ($id <= 0 || !$this->db->table_exists('client_urls')) {
            return false;
        }
        $this->db->where('id', $id)->delete('client_urls');
        return $this->db->affected_rows() >= 0;
    }
}
