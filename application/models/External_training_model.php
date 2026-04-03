<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class External_training_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function schema_ready()
    {
        return $this->db->table_exists('sma_external_trainings');
    }

    public function all()
    {
        $this->db->from('sma_external_trainings');
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->get('sma_external_trainings')->row();
    }

    public function insert($data)
    {
        $this->db->insert('sma_external_trainings', $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id)->update('sma_external_trainings', $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete('sma_external_trainings');
        return $this->db->affected_rows() > 0;
    }
}

