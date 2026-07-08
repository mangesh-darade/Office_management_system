<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('rewards_schema', 'schema_columns'));
        rewards_schema_ensure($this->db);
    }

    /**
     * @return string|null employees column used for department linkage
     */
    private function employee_department_column()
    {
        if (!$this->db->table_exists('employees')) {
            return null;
        }
        if (schema_table_has_column($this->db, 'employees', 'department_id')) {
            return 'department_id';
        }
        if (schema_table_has_column($this->db, 'employees', 'department')) {
            return 'department';
        }
        return null;
    }

    /**
     * Apply department filter on employees join alias `e`.
     */
    private function apply_employee_department_filter($department_id)
    {
        $department_id = (int) $department_id;
        if ($department_id <= 0) {
            return;
        }
        $col = $this->employee_department_column();
        if ($col === 'department_id') {
            $this->db->where('e.department_id', $department_id);
            return;
        }
        if ($col === 'department' && $this->db->table_exists('departments')) {
            $dept = $this->db->where('id', $department_id)->get('departments')->row();
            if ($dept) {
                $name = isset($dept->dept_name) ? $dept->dept_name : (isset($dept->name) ? $dept->name : null);
                if ($name !== null && $name !== '') {
                    $this->db->where('e.department', $name);
                }
            }
        }
    }

    /**
     * Resolve departments.id for reward_leaderboard from an employees row fragment.
     *
     * @param object $row
     * @return int|null
     */
    private function resolve_department_id_for_row($row)
    {
        if (isset($row->department_id) && $row->department_id !== null && $row->department_id !== '') {
            return (int) $row->department_id;
        }
        if (!isset($row->department) || $row->department === '' || !$this->db->table_exists('departments')) {
            return null;
        }
        $name = trim((string) $row->department);
        if ($name === '') {
            return null;
        }
        if (schema_table_has_column($this->db, 'departments', 'dept_name')) {
            $dept = $this->db->select('id')->from('departments')->where('dept_name', $name)->limit(1)->get()->row();
            if ($dept) {
                return (int) $dept->id;
            }
        }
        if (schema_table_has_column($this->db, 'departments', 'name')) {
            $dept = $this->db->select('id')->from('departments')->where('name', $name)->limit(1)->get()->row();
            if ($dept) {
                return (int) $dept->id;
            }
        }
        return null;
    }

    public function get_active_rules_for_event($trigger_event)
    {
        $today = date('Y-m-d');
        $this->db->from('reward_rules');
        $this->db->where('trigger_event', $trigger_event);
        $this->db->where('is_active', 1);
        $this->db->group_start()
            ->where('effective_from IS NULL', null, false)
            ->or_where('effective_from <=', $today)
            ->group_end();
        $this->db->group_start()
            ->where('effective_to IS NULL', null, false)
            ->or_where('effective_to >=', $today)
            ->group_end();
        return $this->db->get()->result();
    }

    public function rule_exists_by_key($idempotency_key)
    {
        return (bool) $this->db->where('idempotency_key', $idempotency_key)->count_all_results('reward_transactions');
    }

    public function count_rule_awards_in_period($rule_id, $user_id, $period_key)
    {
        $this->db->where('rule_id', (int) $rule_id);
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('period_key', $period_key);
        $this->db->where_in('status', array('approved', 'pending'));
        return (int) $this->db->count_all_results('reward_transactions');
    }

    public function count_rule_awards_today($rule_id, $user_id)
    {
        $this->db->where('rule_id', (int) $rule_id);
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        $this->db->where_in('status', array('approved', 'pending'));
        return (int) $this->db->count_all_results('reward_transactions');
    }

    public function insert_transaction($data)
    {
        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert('reward_transactions', $data);
        return (int) $this->db->insert_id();
    }

    public function update_user_summary($user_id)
    {
        $uid = (int) $user_id;
        $lifetime = $this->db->select_sum('points')
            ->where('user_id', $uid)
            ->where('status', 'approved')
            ->get('reward_transactions')->row();
        $lifetimePts = $lifetime && $lifetime->points !== null ? (float) $lifetime->points : 0.0;

        $monthKey = date('Y-m');
        $month = $this->db->select_sum('points')
            ->where('user_id', $uid)
            ->where('status', 'approved')
            ->where('period_key', $monthKey)
            ->get('reward_transactions')->row();
        $monthPts = $month && $month->points !== null ? (float) $month->points : 0.0;

        $level = $this->resolve_level($lifetimePts);
        $row = array(
            'user_id' => $uid,
            'lifetime_points' => $lifetimePts,
            'current_level_code' => $level,
            'month_points' => $monthPts,
            'last_awarded_at' => date('Y-m-d H:i:s'),
        );
        $exists = $this->db->where('user_id', $uid)->get('user_reward_summary')->row();
        if ($exists) {
            $oldLevel = (string) $exists->current_level_code;
            $this->db->where('user_id', $uid)->update('user_reward_summary', $row);
            return array('level_changed' => ($oldLevel !== $level), 'old_level' => $oldLevel, 'new_level' => $level, 'lifetime_points' => $lifetimePts);
        }
        $this->db->insert('user_reward_summary', $row);
        return array('level_changed' => ($level !== 'starter'), 'old_level' => 'starter', 'new_level' => $level, 'lifetime_points' => $lifetimePts);
    }

    public function get_user_summary($user_id)
    {
        $row = $this->db->where('user_id', (int) $user_id)->get('user_reward_summary')->row();
        if (!$row) {
            return (object) array(
                'user_id' => (int) $user_id,
                'lifetime_points' => 0,
                'current_level_code' => 'starter',
                'month_points' => 0,
            );
        }
        return $row;
    }

    public function resolve_level($lifetime_points)
    {
        $levels = $this->db->where('is_active', 1)->order_by('min_lifetime_points', 'DESC')->get('reward_levels')->result();
        foreach ($levels as $l) {
            if ((float) $lifetime_points >= (float) $l->min_lifetime_points) {
                return $l->code;
            }
        }
        return 'starter';
    }

    public function get_level($code)
    {
        return $this->db->where('code', $code)->get('reward_levels')->row();
    }

    public function list_levels($active_only = false)
    {
        if (!$this->db->table_exists('reward_levels')) {
            return array();
        }
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('min_lifetime_points', 'ASC');
        return $this->db->get('reward_levels')->result();
    }

    public function get_level_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get('reward_levels')->row();
    }

    public function save_level($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('reward_levels', $data);
            return (int) $id;
        }
        $this->db->insert('reward_levels', $data);
        return (int) $this->db->insert_id();
    }

    public function list_transactions($user_id, $limit = 50, $offset = 0)
    {
        $this->db->select('t.*, r.name AS rule_name');
        $this->db->from('reward_transactions t');
        $this->db->join('reward_rules r', 'r.id = t.rule_id', 'left');
        $this->db->where('t.user_id', (int) $user_id);
        $this->db->order_by('t.id', 'DESC');
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function list_user_activity_feed($user_id, $limit = 20, $date_from = null, $date_to = null)
    {
        $this->db->select('t.*, r.name AS rule_name, r.code AS rule_code, COALESCE(c.name, rc.name) AS category_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('reward_rules r', 'r.id = t.rule_id', 'left');
        $this->db->join('reward_categories c', 'c.id = t.category_id', 'left');
        $this->db->join('reward_categories rc', 'rc.id = r.category_id', 'left');
        $this->db->where('t.user_id', (int) $user_id);
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->order_by('t.id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function sum_user_activity_points($user_id, $date_from = null, $date_to = null)
    {
        $this->db->select(
            'COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points > 0 THEN t.points ELSE 0 END), 0) AS positive_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points < 0 THEN ABS(t.points) ELSE 0 END), 0) AS negative_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' THEN t.points ELSE 0 END), 0) AS net_points,'
            . ' SUM(CASE WHEN t.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,'
            . ' SUM(CASE WHEN t.status = \'approved\' THEN 1 ELSE 0 END) AS approved_count,'
            . ' SUM(CASE WHEN t.status = \'rejected\' THEN 1 ELSE 0 END) AS rejected_count',
            false
        );
        $this->db->from('reward_transactions t');
        $this->db->where('t.user_id', (int) $user_id);
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $row = $this->db->get()->row();
        return array(
            'positive' => $row ? (float) $row->positive_points : 0,
            'negative' => $row ? (float) $row->negative_points : 0,
            'net' => $row ? (float) $row->net_points : 0,
            'pending_count' => $row ? (int) $row->pending_count : 0,
            'approved_count' => $row ? (int) $row->approved_count : 0,
            'rejected_count' => $row ? (int) $row->rejected_count : 0,
        );
    }

    public function count_user_pending_transactions($user_id)
    {
        return (int) $this->db
            ->where('user_id', (int) $user_id)
            ->where('status', 'pending')
            ->count_all_results('reward_transactions');
    }

    public function leaderboard($period_type, $period_key, $department_id = null, $limit = 20)
    {
        $this->db->from('reward_leaderboard');
        $this->db->where('period_type', $period_type);
        $this->db->where('period_key', $period_key);
        if ($department_id) {
            $this->db->where('department_id', (int) $department_id);
        }
        $this->db->order_by('rank_overall', 'ASC');
        $this->db->limit($limit);
        $rows = $this->db->get()->result();
        if (!empty($rows)) {
            return $rows;
        }
        return $this->leaderboard_live($period_type, $period_key, $department_id, $limit);
    }

    public function leaderboard_live($period_type, $period_key, $department_id = null, $limit = 20)
    {
        $this->db->select('t.user_id, SUM(t.points) AS net_points, u.name AS user_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('users u', 'u.id = t.user_id', 'left');
        if ($this->db->table_exists('employees')) {
            $this->db->join('employees e', 'e.user_id = t.user_id', 'left');
        }
        $this->db->where('t.status', 'approved');
        if ($period_type !== 'all_time') {
            $this->db->where('t.period_key', $period_key);
        }
        if ($department_id && $this->db->table_exists('employees')) {
            $this->apply_employee_department_filter($department_id);
        }
        $this->db->group_by('t.user_id');
        $this->db->order_by('net_points', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function list_rules($active_only = false)
    {
        $this->db->select('r.*, c.name AS category_name');
        $this->db->from('reward_rules r');
        $this->db->join('reward_categories c', 'c.id = r.category_id', 'left');
        if ($active_only) {
            $this->db->where('r.is_active', 1);
        }
        $this->db->order_by('r.trigger_event')->order_by('r.name');
        return $this->db->get()->result();
    }

    public function get_rule($id)
    {
        return $this->db->where('id', (int) $id)->get('reward_rules')->row();
    }

    public function save_rule($data, $id = null)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) {
            $this->db->where('id', (int) $id)->update('reward_rules', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('reward_rules', $data);
        return (int) $this->db->insert_id();
    }

    public function audit($entity_type, $entity_id, $action, $actor_id, $old = null, $new = null)
    {
        $CI =& get_instance();
        $this->db->insert('reward_audit_logs', array(
            'entity_type' => $entity_type,
            'entity_id' => (int) $entity_id,
            'action' => $action,
            'actor_id' => $actor_id ? (int) $actor_id : null,
            'old_values' => $old !== null ? json_encode($old) : null,
            'new_values' => $new !== null ? json_encode($new) : null,
            'ip_address' => $CI->input ? $CI->input->ip_address() : null,
        ));
    }

    public function rebuild_leaderboard($period_type, $period_key)
    {
        $this->db->where('period_type', $period_type);
        $this->db->where('period_key', $period_key);
        $this->db->delete('reward_leaderboard');

        $deptCol = $this->employee_department_column();
        $select = 't.user_id, SUM(CASE WHEN t.points > 0 THEN t.points ELSE 0 END) AS points_earned, '
            . 'SUM(CASE WHEN t.points < 0 THEN ABS(t.points) ELSE 0 END) AS points_lost, SUM(t.points) AS net_points';
        if ($deptCol) {
            $select .= ', MAX(e.' . $deptCol . ') AS employee_department';
        }
        $this->db->select($select, false);
        $this->db->from('reward_transactions t');
        if ($this->db->table_exists('employees')) {
            $this->db->join('employees e', 'e.user_id = t.user_id', 'left');
        }
        $this->db->where('t.status', 'approved');
        if ($period_type !== 'all_time') {
            $this->db->where('t.period_key', $period_key);
        }
        $this->db->group_by('t.user_id');
        $this->db->order_by('net_points', 'DESC');
        $rows = $this->db->get()->result();

        $rank = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $rank++;
            $summary = $this->get_user_summary($row->user_id);
            $deptRow = new stdClass();
            if ($deptCol === 'department_id' && isset($row->employee_department)) {
                $deptRow->department_id = $row->employee_department;
            } elseif ($deptCol === 'department' && isset($row->employee_department)) {
                $deptRow->department = $row->employee_department;
            }
            $this->db->insert('reward_leaderboard', array(
                'user_id' => (int) $row->user_id,
                'department_id' => $this->resolve_department_id_for_row($deptRow),
                'period_type' => $period_type,
                'period_key' => $period_key,
                'points_earned' => (float) $row->points_earned,
                'points_lost' => (float) $row->points_lost,
                'net_points' => (float) $row->net_points,
                'rank_overall' => $rank,
                'level_code' => $summary->current_level_code,
                'computed_at' => $now,
            ));
        }
        return $rank;
    }

    public function list_claim_rules()
    {
        $this->db->from('reward_rules');
        $this->db->where('trigger_event', 'reward_claim');
        $this->db->where('is_active', 1);
        $this->db->order_by('name');
        return $this->db->get()->result();
    }

    public function get_rule_by_code($code)
    {
        return $this->db->where('code', (string) $code)->get('reward_rules')->row();
    }

    public function insert_approval_queue(array $data)
    {
        if (empty($data['submitted_at'])) {
            $data['submitted_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert('reward_approval_queue', $data);
        return (int) $this->db->insert_id();
    }

    public function list_pending_approvals($limit = 100)
    {
        $this->db->select('q.*, u.name AS recipient_name, s.name AS submitter_name, r.name AS rule_name, r.code AS rule_code, r.points AS rule_points');
        $this->db->from('reward_approval_queue q');
        $this->db->join('users u', 'u.id = q.user_id', 'left');
        $this->db->join('users s', 's.id = q.submitted_by', 'left');
        $this->db->join('reward_rules r', 'r.id = q.rule_id', 'left');
        $this->db->where('q.status', 'pending');
        $this->db->order_by('q.submitted_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function get_approval_queue($id)
    {
        return $this->db->where('id', (int) $id)->get('reward_approval_queue')->row();
    }

    public function list_spl_pending_approvals($limit = 100)
    {
        $this->db->select('q.*, u.name AS recipient_name, s.name AS submitter_name, r.name AS rule_name, r.code AS rule_code, r.points AS rule_points, c.name AS category_name, t.reference_label', false);
        $this->db->from('reward_approval_queue q');
        $this->db->join('users u', 'u.id = q.user_id', 'left');
        $this->db->join('users s', 's.id = q.submitted_by', 'left');
        $this->db->join('reward_rules r', 'r.id = q.rule_id', 'left');
        $this->db->join('reward_categories c', 'c.id = r.category_id', 'left');
        $this->db->join('reward_transactions t', 't.id = q.transaction_id', 'left');
        $this->db->where('q.status', 'pending');
        $this->db->where('q.source_module', 'spl');
        $this->db->order_by('q.submitted_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function list_spl_user_pending_approvals($user_id, $limit = 20)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return array();
        }
        $this->db->select('q.*, u.name AS recipient_name, s.name AS submitter_name, r.name AS rule_name, r.code AS rule_code, r.points AS rule_points, c.name AS category_name, t.reference_label', false);
        $this->db->from('reward_approval_queue q');
        $this->db->join('users u', 'u.id = q.user_id', 'left');
        $this->db->join('users s', 's.id = q.submitted_by', 'left');
        $this->db->join('reward_rules r', 'r.id = q.rule_id', 'left');
        $this->db->join('reward_categories c', 'c.id = r.category_id', 'left');
        $this->db->join('reward_transactions t', 't.id = q.transaction_id', 'left');
        $this->db->where('q.status', 'pending');
        $this->db->where('q.source_module', 'spl');
        $this->db->group_start()
            ->where('q.user_id', $user_id)
            ->or_where('q.submitted_by', $user_id)
            ->group_end();
        $this->db->order_by('q.submitted_at', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function list_spl_approval_history($status = 'approved', $limit = 100)
    {
        $status = in_array($status, array('approved', 'rejected'), true) ? $status : 'approved';
        $this->db->select('q.*, u.name AS recipient_name, s.name AS submitter_name, a.name AS approver_name, r.name AS rule_name, r.code AS rule_code, r.points AS rule_points, c.name AS category_name, t.reference_label', false);
        $this->db->from('reward_approval_queue q');
        $this->db->join('users u', 'u.id = q.user_id', 'left');
        $this->db->join('users s', 's.id = q.submitted_by', 'left');
        $this->db->join('users a', 'a.id = q.approver_id', 'left');
        $this->db->join('reward_rules r', 'r.id = q.rule_id', 'left');
        $this->db->join('reward_categories c', 'c.id = r.category_id', 'left');
        $this->db->join('reward_transactions t', 't.id = q.transaction_id', 'left');
        $this->db->where('q.status', $status);
        $this->db->where('q.source_module', 'spl');
        $this->db->order_by('q.decided_at', 'DESC');
        $this->db->order_by('q.id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function count_spl_approvals_by_status($status)
    {
        $status = (string) $status;
        if (!in_array($status, array('pending', 'approved', 'rejected'), true)) {
            return 0;
        }
        return (int) $this->db
            ->where('status', $status)
            ->where('source_module', 'spl')
            ->count_all_results('reward_approval_queue');
    }

    public function get_evidence_for_queue($queue_id)
    {
        if (!$this->db->table_exists('reward_evidence')) {
            return null;
        }
        return $this->db->where('approval_queue_id', (int) $queue_id)->order_by('id', 'DESC')->limit(1)->get('reward_evidence')->row();
    }

    public function approve_pending($queue_id, $approver_id, $comment = '')
    {
        $q = $this->get_approval_queue($queue_id);
        if (!$q || $q->status !== 'pending') {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $q->transaction_id)->update('reward_transactions', array(
            'status' => 'approved',
            'approved_by' => (int) $approver_id,
            'approved_at' => $now,
        ));
        $this->db->where('id', (int) $queue_id)->update('reward_approval_queue', array(
            'status' => 'approved',
            'approver_id' => (int) $approver_id,
            'decided_at' => $now,
            'decision_comment' => $comment !== '' ? $comment : null,
        ));
        $this->update_user_summary((int) $q->user_id);
        $CI =& get_instance();
        $CI->load->helper('notification');
        if (function_exists('create_notification')) {
            $rule = $q->rule_id ? $this->get_rule((int) $q->rule_id) : null;
            $label = $rule ? $rule->name : 'Reward claim';
            $pts = (float) $q->requested_points;
            $sign = $pts >= 0 ? '+' : '';
            create_notification(
                (int) $q->user_id,
                $sign . number_format($pts, 0) . ' points approved',
                $label,
                'success',
                'rewards',
                (int) $q->transaction_id,
                site_url('spl/dashboard?tab=my-reward')
            );
        }
        return true;
    }

    public function reject_pending($queue_id, $approver_id, $comment = '')
    {
        $q = $this->get_approval_queue($queue_id);
        if (!$q || $q->status !== 'pending') {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $q->transaction_id)->update('reward_transactions', array(
            'status' => 'rejected',
            'approved_by' => (int) $approver_id,
            'approved_at' => $now,
        ));
        $this->db->where('id', (int) $queue_id)->update('reward_approval_queue', array(
            'status' => 'rejected',
            'approver_id' => (int) $approver_id,
            'decided_at' => $now,
            'decision_comment' => $comment !== '' ? $comment : null,
        ));
        return true;
    }
}
