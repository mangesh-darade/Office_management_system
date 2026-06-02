<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My_work_model extends CI_Model
{
    private $table = 'my_works';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function find($id)
    {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row();
    }

    public function insert(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        if (!empty($data['status']) && $data['status'] === 'closed') {
            $data['closed_at'] = $now;
        }
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (isset($data['status'])) {
            if ($data['status'] === 'closed') {
                $data['closed_at'] = date('Y-m-d H:i:s');
            } else {
                $data['closed_at'] = null;
            }
        }
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        $id = (int) $id;
        if ($this->db->table_exists('my_work_comments')) {
            $this->db->where('work_id', $id)->delete('my_work_comments');
        }
        if ($this->db->table_exists('my_work_activity')) {
            $this->db->where('work_id', $id)->delete('my_work_activity');
        }
        $this->db->where('id', $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function log_activity($work_id, $user_id, $action, $detail = '')
    {
        if (!$this->db->table_exists('my_work_activity')) {
            return 0;
        }
        $this->db->insert('my_work_activity', array(
            'work_id' => (int) $work_id,
            'user_id' => (int) $user_id,
            'action' => substr((string) $action, 0, 50),
            'detail' => (string) $detail,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_activity($work_id, $limit = 50)
    {
        if (!$this->db->table_exists('my_work_activity')) {
            return array();
        }
        $this->db->select('a.*, u.name AS user_name, u.email AS user_email', false);
        $this->db->from('my_work_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.work_id', (int) $work_id);
        $this->db->order_by('a.id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function add_comment($work_id, $user_id, $comment)
    {
        if (!$this->db->table_exists('my_work_comments')) {
            return 0;
        }
        $comment = trim((string) $comment);
        if ($comment === '') {
            return 0;
        }
        $this->db->insert('my_work_comments', array(
            'work_id' => (int) $work_id,
            'user_id' => (int) $user_id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_comments($work_id)
    {
        if (!$this->db->table_exists('my_work_comments')) {
            return array();
        }
        $this->db->select('c.*, u.name AS user_name, u.email AS user_email', false);
        $this->db->from('my_work_comments c');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        $this->db->where('c.work_id', (int) $work_id);
        $this->db->order_by('c.id', 'ASC');
        return $this->db->get()->result();
    }

    public function distinct_tags_scoped($scope_callback = null, $limit = 50)
    {
        $this->db->select('w.tag');
        $this->db->from($this->table . ' w');
        $this->db->where('w.tag IS NOT NULL', null, false);
        $this->db->where('w.tag !=', '');
        if (is_callable($scope_callback)) {
            call_user_func($scope_callback);
        }
        $this->db->group_by('w.tag');
        $this->db->order_by('w.tag', 'ASC');
        $this->db->limit((int) $limit);
        $rows = $this->db->get()->result();
        $tags = array();
        foreach ($rows as $r) {
            if (!empty($r->tag)) {
                foreach (my_works_parse_tags($r->tag) as $t) {
                    $tags[$t] = $t;
                }
            }
        }
        sort($tags);
        return array_values($tags);
    }
}
