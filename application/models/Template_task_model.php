<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Template_task_model extends CI_Model
{
    private $table = 'template_tasks';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function all_active()
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        return $this->db->from($this->table)
            ->where('is_active', 1)
            ->order_by('team', 'ASC')
            ->order_by('template_type', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
    }

    public function all()
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        return $this->db->from($this->table)
            ->order_by('team', 'ASC')
            ->order_by('template_type', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
    }

    public function find($id)
    {
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        return $this->db->get_where($this->table, array('id' => (int) $id))->row();
    }

    public function insert(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function distinct_teams()
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        $rows = $this->db->select('DISTINCT team', false)
            ->from($this->table)
            ->where('team !=', '')
            ->order_by('team', 'ASC')
            ->get()
            ->result();
        $out = array();
        foreach ($rows as $row) {
            $out[] = (string) $row->team;
        }
        return $out;
    }

    public function distinct_types($team = '')
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        $this->db->select('DISTINCT template_type', false)
            ->from($this->table)
            ->where('template_type !=', '')
            ->where('is_active', 1);
        if ($team !== '') {
            $this->db->where('team', $team);
        }
        $rows = $this->db->order_by('template_type', 'ASC')->get()->result();
        $out = array();
        foreach ($rows as $row) {
            $out[] = (string) $row->template_type;
        }
        return $out;
    }

    public function active_by_team_type($team, $template_type)
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        return $this->db->from($this->table)
            ->where('is_active', 1)
            ->where('team', (string) $team)
            ->where('template_type', (string) $template_type)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
    }
}
