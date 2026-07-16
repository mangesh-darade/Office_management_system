<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todays_plan_model extends CI_Model
{
    private $table = 'todays_plan_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('schema_columns', 'todays_plan_schema'));
        todays_plan_schema_ensure($this->db);
    }

    public function list_for_user_date($user_id, $plan_date)
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        return $this->db->from($this->table)
            ->where('user_id', (int) $user_id)
            ->where('plan_date', $plan_date)
            ->order_by('plan_time', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Past plan days for history tab (excludes today by default).
     *
     * @param int         $user_id
     * @param string|null $before_date Y-m-d
     * @param int         $limit
     * @return array<int,object>
     */
    public function list_history_days($user_id, $before_date = null, $limit = 30)
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        $before_date = $before_date ? $before_date : date('Y-m-d');
        $limit = max(1, min(90, (int) $limit));

        return $this->db->select('plan_date, COUNT(*) AS item_count, SUM(CASE WHEN status = \'done\' THEN 1 ELSE 0 END) AS done_count', false)
            ->from($this->table)
            ->where('user_id', (int) $user_id)
            ->where('plan_date <', $before_date)
            ->group_by('plan_date')
            ->order_by('plan_date', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * History items before a date (optional single day filter).
     *
     * @param int         $user_id
     * @param string|null $plan_date Y-m-d or null for all past
     * @param string|null $before_date
     * @param int         $limit
     * @return array<int,object>
     */
    public function list_history_items($user_id, $plan_date = null, $before_date = null, $limit = 200)
    {
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        $before_date = $before_date ? $before_date : date('Y-m-d');
        $limit = max(1, min(500, (int) $limit));

        $this->db->from($this->table)->where('user_id', (int) $user_id);
        if ($plan_date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan_date)) {
            $this->db->where('plan_date', $plan_date);
        } else {
            $this->db->where('plan_date <', $before_date);
        }

        return $this->db->order_by('plan_date', 'DESC')
            ->order_by('plan_time', 'ASC')
            ->order_by('id', 'ASC')
            ->limit($limit)
            ->get()
            ->result();
    }

    public function get($id)
    {
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        return $this->db->get_where($this->table, array('id' => (int) $id))->row();
    }

    public function insert($data)
    {
        if (!$this->db->table_exists($this->table)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $row = array(
            'user_id'    => (int) $data['user_id'],
            'plan_date'  => $data['plan_date'],
            'plan_time'  => $data['plan_time'],
            'title'      => $data['title'],
            'details'    => isset($data['details']) ? $data['details'] : null,
            'link_type'  => isset($data['link_type']) ? $data['link_type'] : null,
            'link_id'    => isset($data['link_id']) ? (int) $data['link_id'] : null,
            'status'     => isset($data['status']) ? $data['status'] : 'pending',
            'repeat_type'=> isset($data['repeat_type']) ? $data['repeat_type'] : 'once',
            'created_at' => $now,
            'updated_at' => $now,
        );
        $this->db->insert($this->table, $row);
        return (int) $this->db->insert_id();
    }

    public function update($id, $data)
    {
        if (!$this->db->table_exists($this->table)) {
            return false;
        }
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id, $user_id)
    {
        if (!$this->db->table_exists($this->table)) {
            return false;
        }
        $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function belongs_to_user($id, $user_id)
    {
        $row = $this->get($id);
        if (!$row) {
            return false;
        }
        return (int) $row->user_id === (int) $user_id;
    }
}
