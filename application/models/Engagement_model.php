<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Engagement_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('engagement_schema', 'schema_columns'));
        engagement_schema_ensure($this->db);
    }

    public function list_releases($filters = array())
    {
        $this->db->select('r.*, p.name AS project_name, u.name AS creator_name');
        $this->db->from('project_releases r');
        $this->db->join('projects p', 'p.id = r.project_id', 'left');
        $this->db->join('users u', 'u.id = r.created_by', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('r.project_id', (int) $filters['project_id']);
        }
        $this->db->order_by('r.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_release($id)
    {
        return $this->db->where('id', (int) $id)->get('project_releases')->row();
    }

    public function save_release($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('project_releases', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('project_releases', $data);
        return (int) $this->db->insert_id();
    }

    public function list_kb($filters = array())
    {
        $this->db->select('k.*, u.name AS author_name');
        $this->db->from('kb_articles k');
        $this->db->join('users u', 'u.id = k.author_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('k.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('k.title', $q)
                ->or_like('k.summary', $q)
                ->group_end();
        }
        $this->db->order_by('k.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_kb($id)
    {
        return $this->db->where('id', (int) $id)->get('kb_articles')->row();
    }

    public function save_kb($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('kb_articles', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('kb_articles', $data);
        return (int) $this->db->insert_id();
    }

    public function list_tickets($filters = array())
    {
        $this->db->select('t.*, req.name AS requester_name, asn.name AS assignee_name');
        $this->db->from('helpdesk_tickets t');
        $this->db->join('users req', 'req.id = t.requester_id', 'left');
        $this->db->join('users asn', 'asn.id = t.assigned_to', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['requester_id'])) {
            $this->db->where('t.requester_id', (int) $filters['requester_id']);
        }
        $this->db->order_by('t.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_ticket($id)
    {
        return $this->db->where('id', (int) $id)->get('helpdesk_tickets')->row();
    }

    public function next_ticket_number()
    {
        $prefix = 'TKT-' . date('Ym') . '-';
        $this->db->like('ticket_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('helpdesk_tickets', 1)->row();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->ticket_number, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function save_ticket($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('helpdesk_tickets', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('helpdesk_tickets', $data);
        return (int) $this->db->insert_id();
    }

    public function list_events($upcoming_only = false)
    {
        $this->db->select('e.*, u.name AS organizer_name');
        $this->db->from('company_events e');
        $this->db->join('users u', 'u.id = e.organizer_id', 'left');
        if ($upcoming_only) {
            $this->db->where('e.start_at >=', date('Y-m-d H:i:s'));
        }
        $this->db->where('e.is_active', 1);
        $this->db->order_by('e.start_at', 'ASC');
        return $this->db->get()->result();
    }

    public function get_event($id)
    {
        return $this->db->where('id', (int) $id)->get('company_events')->row();
    }

    public function save_event($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('company_events', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('company_events', $data);
        return (int) $this->db->insert_id();
    }

    public function list_certifications($filters = array())
    {
        $this->db->select('c.*, u.name AS user_name');
        $this->db->from('employee_certifications c');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('c.status', $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('c.user_id', (int) $filters['user_id']);
        }
        $this->db->order_by('c.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_certification($id)
    {
        return $this->db->where('id', (int) $id)->get('employee_certifications')->row();
    }

    public function save_certification($data, $id = null)
    {
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update('employee_certifications', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('employee_certifications', $data);
        return (int) $this->db->insert_id();
    }

    public function list_feedback($filters = array())
    {
        $this->db->select('f.*, u.name AS submitter_name, c.company_name AS client_name, p.name AS project_name');
        $this->db->from('customer_feedback_entries f');
        $this->db->join('users u', 'u.id = f.submitted_by', 'left');
        $this->db->join('clients c', 'c.id = f.client_id', 'left');
        $this->db->join('projects p', 'p.id = f.project_id', 'left');
        if (!empty($filters['min_rating'])) {
            $this->db->where('f.rating >=', (int) $filters['min_rating']);
        }
        $this->db->order_by('f.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_feedback($id)
    {
        return $this->db->where('id', (int) $id)->get('customer_feedback_entries')->row();
    }

    public function save_feedback($data, $id = null)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('customer_feedback_entries', $data);
        return (int) $this->db->insert_id();
    }

    public function project_options()
    {
        if (!$this->db->table_exists('projects')) {
            return array();
        }
        return $this->db->select('id, name')->order_by('name')->get('projects')->result();
    }

    public function client_options()
    {
        if (!$this->db->table_exists('clients')) {
            return array();
        }
        return $this->db->select('id, company_name')->from('clients')->order_by('company_name', 'ASC')->get()->result();
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
