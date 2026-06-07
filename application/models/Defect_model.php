<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Defect_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('defects_schema', 'schema_columns'));
        defects_schema_ensure($this->db);
    }

    public function list_defects($filters = array())
    {
        $this->db->select('d.*, p.name AS project_name, rep.name AS reporter_name, asn.name AS assignee_name, r.version AS release_version');
        $this->db->from('project_defects d');
        $this->db->join('projects p', 'p.id = d.project_id', 'left');
        $this->db->join('users rep', 'rep.id = d.reported_by', 'left');
        $this->db->join('users asn', 'asn.id = d.assigned_to', 'left');
        $this->db->join('project_releases r', 'r.id = d.release_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('d.status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $this->db->where('d.severity', $filters['severity']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('d.project_id', (int) $filters['project_id']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->where('d.assigned_to', (int) $filters['assigned_to']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('d.title', $q)
                ->or_like('d.defect_number', $q)
                ->or_like('d.description', $q)
                ->group_end();
        }
        $this->db->order_by('d.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_defect($id)
    {
        $this->db->select('d.*, p.name AS project_name, rep.name AS reporter_name, asn.name AS assignee_name, r.version AS release_version, r.title AS release_title');
        $this->db->from('project_defects d');
        $this->db->join('projects p', 'p.id = d.project_id', 'left');
        $this->db->join('users rep', 'rep.id = d.reported_by', 'left');
        $this->db->join('users asn', 'asn.id = d.assigned_to', 'left');
        $this->db->join('project_releases r', 'r.id = d.release_id', 'left');
        $this->db->where('d.id', (int) $id);
        return $this->db->get()->row();
    }

    public function next_defect_number()
    {
        $prefix = 'DEF-' . date('Ym') . '-';
        $this->db->like('defect_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('project_defects', 1)->row();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->defect_number, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function save_defect($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('project_defects', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('project_defects', $data);
        return (int) $this->db->insert_id();
    }

    public function delete_defect($id)
    {
        return $this->db->where('id', (int) $id)->delete('project_defects');
    }

    public function project_options()
    {
        if (!$this->db->table_exists('projects')) {
            return array();
        }
        return $this->db->select('id, name')->order_by('name')->get('projects')->result();
    }

    public function release_options($project_id = null)
    {
        if (!$this->db->table_exists('project_releases')) {
            return array();
        }
        $this->db->select('id, version, title, project_id')->from('project_releases');
        if ($project_id) {
            $this->db->where('project_id', (int) $project_id);
        }
        return $this->db->order_by('id', 'DESC')->get()->result();
    }

    public function task_options($project_id = null)
    {
        if (!$this->db->table_exists('tasks')) {
            return array();
        }
        $this->db->select('id, title, project_id')->from('tasks');
        if ($project_id) {
            $this->db->where('project_id', (int) $project_id);
        }
        return $this->db->order_by('id', 'DESC')->limit(200)->get()->result();
    }

    public function user_options()
    {
        $this->db->select('id, name')->from('users');
        if (schema_table_has_column($this->db, 'users', 'status')) {
            $this->db->where('status', 'active');
        }
        return $this->db->order_by('name')->get()->result();
    }
}
