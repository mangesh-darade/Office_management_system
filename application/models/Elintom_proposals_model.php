<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Elintom_proposals_model extends CI_Model
{
    private $table = 'elintom_proposals';

    public function table_exists()
    {
        return $this->db->table_exists($this->table);
    }

    public function create($data)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $row = array(
            'client_name' => trim((string) ($data['client_name'] ?? '')),
            'client_business' => trim((string) ($data['client_business'] ?? '')),
            'document_path' => trim((string) ($data['document_path'] ?? '')) ?: null,
            'created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
            'created_at' => !empty($data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s'),
        );

        $this->db->insert($this->table, $row);
        return (int) $this->db->insert_id();
    }

    public function find($id)
    {
        if (!$this->table_exists()) {
            return null;
        }
        $row = $this->db->get_where($this->table, array('id' => (int) $id))->row();
        return $row ? $row : null;
    }

    public function count_all()
    {
        if (!$this->table_exists()) {
            return 0;
        }
        return (int) $this->db->count_all($this->table);
    }

    public function get_paginated($limit = 50, $offset = 0)
    {
        if (!$this->table_exists()) {
            return array();
        }

        $CI =& get_instance();
        $CI->load->helper('schema_columns');

        $this->db->select('p.*');
        if (schema_table_has_column($this->db, 'users', 'name')) {
            $this->db->select('u.name AS created_by_name', false);
        } elseif (schema_table_has_column($this->db, 'users', 'first_name') && schema_table_has_column($this->db, 'users', 'last_name')) {
            $this->db->select("CONCAT(u.first_name, ' ', u.last_name) AS created_by_name", false);
        } else {
            $this->db->select("'' AS created_by_name", false);
        }
        if (schema_table_has_column($this->db, 'users', 'email')) {
            $this->db->select('u.email AS created_by_email', false);
        } else {
            $this->db->select("'' AS created_by_email", false);
        }

        $this->db->from($this->table . ' p');
        $this->db->join('users u', 'u.id = p.created_by', 'left');
        $this->db->order_by('p.created_at', 'DESC');
        $this->db->order_by('p.id', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }
}
