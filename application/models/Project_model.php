<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Schema_columns_trait.php';

class Project_model extends CI_Model {
    use Schema_columns_trait;
    private $table = 'projects';
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper('hierarchy_filter');
        $this->ensure_activity_schema();
    }

    public function member_role_options()
    {
        return array('manager', 'lead', 'developer', 'tester', 'viewer', 'member');
    }

    public function sanitize_member_role($role)
    {
        $role = strtolower(trim((string) $role));
        if ($role === '') {
            return 'member';
        }
        return in_array($role, $this->member_role_options(), true) ? $role : 'member';
    }

    public function ensure_member_role_schema()
    {
        if (!$this->db->table_exists('project_members')) {
            return;
        }
        $row = $this->db->query("SHOW COLUMNS FROM `project_members` LIKE 'role'")->row();
        if (!$row || stripos((string) $row->Type, 'manager') !== false) {
            return;
        }
        $this->db->query(
            "ALTER TABLE `project_members` MODIFY `role` "
            . "ENUM('member','lead','viewer','manager','developer','tester') NOT NULL DEFAULT 'member'"
        );
    }
    
    public function all($filters = []){
        $this->db->select('p.*');
        $this->db->from($this->table . ' p');

        // For non-admin users show projects where they are:
        //   a) a member in project_members, OR
        //   b) the manager_id, OR
        //   c) the created_by
        // Empty $filters means admin — no restriction.
        if (!empty($filters) && isset($filters['user_id'])) {
            $uid = (int) $filters['user_id'];
            $has_created_by = $this->has_column('created_by');
            $has_manager_id = $this->has_column('manager_id');
            $has_members    = $this->db->table_exists('project_members');

            $parts = array();
            if ($has_members) {
                $parts[] = 'EXISTS (SELECT 1 FROM `project_members` pm WHERE pm.project_id = p.id AND pm.user_id = ' . $uid . ')';
            }
            if ($has_manager_id) {
                $parts[] = 'p.`manager_id` = ' . $uid;
            }
            if ($has_created_by) {
                $parts[] = 'p.`created_by` = ' . $uid;
            }
            if (!empty($parts)) {
                $this->db->where('(' . implode(' OR ', $parts) . ')', null, false);
            }
        }

        return $this->db->order_by('p.id','DESC')->group_by('p.id')->get()->result();
    }

    // Members
    public function get_project_members($project_id){
        $this->db->select('pm.user_id, pm.role, u.email, u.name')
                 ->from('project_members pm')
                 ->join('users u', 'u.id = pm.user_id', 'left')
                 ->where('pm.project_id', (int)$project_id)
                 ->order_by('u.email','ASC');
        return $this->db->get()->result();
    }

    public function check_user_is_member($project_id, $user_id){
        return (bool)$this->db->get_where('project_members', [
            'project_id' => (int)$project_id,
            'user_id' => (int)$user_id,
        ])->row();
    }

    public function add_member($project_id, $user_id, $role){
        $this->ensure_member_role_schema();
        if ($this->check_user_is_member($project_id, $user_id)) return true;
        $this->db->insert('project_members', [
            'project_id' => (int)$project_id,
            'user_id' => (int)$user_id,
            'role' => $this->sanitize_member_role($role),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function remove_member($project_id, $user_id){
        $this->db->where(['project_id' => (int)$project_id, 'user_id' => (int)$user_id])
                 ->delete('project_members');
        return $this->db->affected_rows() > 0;
    }

    public function update_member_role($project_id, $user_id, $role){
        $this->ensure_member_role_schema();
        $project_id = (int) $project_id;
        $user_id = (int) $user_id;
        $role = $this->sanitize_member_role($role);
        $existing = $this->db->get_where('project_members', array(
            'project_id' => $project_id,
            'user_id' => $user_id,
        ))->row();
        if (!$existing) {
            return false;
        }
        if ((string) $existing->role === $role) {
            return true;
        }
        $this->db->where(array('project_id' => $project_id, 'user_id' => $user_id))
            ->update('project_members', array('role' => $role));
        return $this->db->affected_rows() > 0;
    }

    /**
     * Auto-generate unique project code: PRJ-YYYY-NNNNN
     *
     * @return string
     */
    public function generate_project_code()
    {
        $year = date('Y');
        $prefix = 'PRJ-' . $year . '-';
        $row = $this->db->like('code', $prefix, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
        $num = 0;
        if ($row && isset($row->code)) {
            $tail = substr($row->code, -5);
            if (ctype_digit($tail)) {
                $num = (int) $tail;
            }
        }
        $num++;
        return $prefix . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    public function ensure_activity_schema()
    {
        if ($this->db->table_exists('project_activity')) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `project_activity` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` int(11) UNSIGNED NOT NULL,
            `user_id` int(11) UNSIGNED DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `detail` text DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_pact_project` (`project_id`),
            KEY `idx_pact_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function log_activity($project_id, $user_id, $action, $detail = '')
    {
        $this->ensure_activity_schema();
        if (!$this->db->table_exists('project_activity')) {
            return 0;
        }
        $this->db->insert('project_activity', array(
            'project_id' => (int) $project_id,
            'user_id' => (int) $user_id > 0 ? (int) $user_id : null,
            'action' => substr((string) $action, 0, 50),
            'detail' => $detail !== '' ? (string) $detail : null,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_activity($project_id)
    {
        if (!$this->db->table_exists('project_activity')) {
            return array();
        }
        $this->db->select('a.*, u.name AS user_name');
        $this->db->from('project_activity a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.project_id', (int) $project_id);
        $this->db->order_by('a.id', 'DESC');
        return $this->db->get()->result();
    }

    public function list_history($project_id)
    {
        $rows = array();
        foreach ($this->list_activity((int) $project_id) as $a) {
            $rows[] = (object) array(
                'id' => (int) $a->id,
                'source' => 'activity',
                'action' => (string) $a->action,
                'detail' => isset($a->detail) ? (string) $a->detail : '',
                'user_name' => isset($a->user_name) ? (string) $a->user_name : '',
                'user_id' => isset($a->user_id) ? (int) $a->user_id : 0,
                'created_at' => (string) $a->created_at,
            );
        }
        return $rows;
    }

    public function delete_activity_for_project($project_id)
    {
        if (!$this->db->table_exists('project_activity')) {
            return false;
        }
        $this->db->where('project_id', (int) $project_id)->delete('project_activity');
        return true;
    }

    public function build_change_details($old, array $new)
    {
        if (!$old) {
            return array();
        }
        $labels = array(
            'name' => 'Name',
            'code' => 'Code',
            'status' => 'Status',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'client_id' => 'Client',
            'department_id' => 'Department',
            'project_type' => 'Type',
            'reference_url' => 'URL / Link',
            'estimate_hours' => 'Estimate (hrs)',
            'actual_hours' => 'Actual (hrs)',
            'manager_id' => 'Assignee',
        );
        $lines = array();
        foreach ($labels as $key => $label) {
            if (!array_key_exists($key, $new)) {
                continue;
            }
            $before = isset($old->$key) ? (string) $old->$key : '';
            $after = $new[$key] === null ? '' : (string) $new[$key];
            if ($before === $after) {
                continue;
            }
            $lines[] = $label . ': ' . ($before !== '' ? $before : '—') . ' → ' . ($after !== '' ? $after : '—');
        }
        return $lines;
    }
}
