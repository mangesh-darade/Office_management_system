<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Spl_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('spl_schema', 'rewards_schema'));
        spl_schema_ensure($this->db);
    }

    public function list_categories($active_only = true)
    {
        if (!$this->db->table_exists('reward_categories')) {
            return array();
        }
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('sort_order')->order_by('name');
        return $this->db->get('reward_categories')->result();
    }

    public function list_claim_rules_by_category($category_id = 0)
    {
        $this->db->from('reward_rules');
        $this->db->where('trigger_event', 'reward_claim');
        $this->db->where('is_active', 1);
        $this->db->where('requires_approval', 1);
        if ($category_id > 0) {
            $this->db->where('category_id', (int) $category_id);
        }
        $this->db->order_by('name');
        return $this->db->get()->result();
    }

    public function list_groups($active_only = true)
    {
        if (!$this->db->table_exists('spl_groups')) {
            return array();
        }
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('sort_order')->order_by('name');
        return $this->db->get('spl_groups')->result();
    }

    public function get_group($id)
    {
        return $this->db->where('id', (int) $id)->get('spl_groups')->row();
    }

    public function get_group_by_code($code)
    {
        return $this->db->where('code', (string) $code)->get('spl_groups')->row();
    }

    public function save_group(array $data, $id = null)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) {
            $this->db->where('id', (int) $id)->update('spl_groups', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('spl_groups', $data);
        return (int) $this->db->insert_id();
    }

    public function list_group_rules($group_id)
    {
        $this->db->select('r.*, gr.sort_order AS group_sort, gr.id AS link_id');
        $this->db->from('spl_group_rules gr');
        $this->db->join('reward_rules r', 'r.id = gr.rule_id', 'inner');
        $this->db->where('gr.group_id', (int) $group_id);
        $this->db->where('r.is_active', 1);
        $this->db->order_by('gr.sort_order')->order_by('r.name');
        return $this->db->get()->result();
    }

    public function sync_group_rules($group_id, array $rule_ids)
    {
        $group_id = (int) $group_id;
        $this->db->where('group_id', $group_id)->delete('spl_group_rules');
        $order = 0;
        foreach ($rule_ids as $rid) {
            $rid = (int) $rid;
            if ($rid <= 0) {
                continue;
            }
            $order++;
            $this->db->insert('spl_group_rules', array(
                'group_id' => $group_id,
                'rule_id' => $rid,
                'sort_order' => $order,
            ));
        }
        return true;
    }

    public function list_group_members($group_id)
    {
        if (!$this->db->table_exists('spl_group_members')) {
            return array();
        }
        $this->db->select('u.id, u.name, u.email, m.sort_order');
        $this->db->from('spl_group_members m');
        $this->db->join('users u', 'u.id = m.user_id', 'inner');
        $this->db->where('m.group_id', (int) $group_id);
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('u.name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_reward_levels_map()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = array();
        if (!$this->db->table_exists('reward_levels')) {
            return $cache;
        }
        $rows = $this->db->where('is_active', 1)->order_by('min_lifetime_points', 'ASC')->get('reward_levels')->result();
        foreach ($rows as $row) {
            $cache[(string) $row->code] = (object) array(
                'name' => (string) $row->name,
                'badge_color' => isset($row->badge_color) ? (string) $row->badge_color : '#6c757d',
            );
        }
        if (!isset($cache['starter'])) {
            $cache['starter'] = (object) array('name' => 'Starter', 'badge_color' => '#6c757d');
        }
        return $cache;
    }

    public function get_user_points_map(array $user_ids)
    {
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));
        $map = array();
        foreach ($user_ids as $uid) {
            $map[$uid] = (object) array(
                'lifetime_points' => 0,
                'month_points' => 0,
                'current_level_code' => 'starter',
            );
        }
        if (empty($user_ids) || !$this->db->table_exists('user_reward_summary')) {
            return $map;
        }
        $rows = $this->db->select('user_id, lifetime_points, month_points, current_level_code')
            ->where_in('user_id', $user_ids)
            ->get('user_reward_summary')
            ->result();
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if (!isset($map[$uid])) {
                continue;
            }
            $map[$uid] = (object) array(
                'lifetime_points' => (float) $row->lifetime_points,
                'month_points' => (float) $row->month_points,
                'current_level_code' => !empty($row->current_level_code) ? (string) $row->current_level_code : 'starter',
            );
        }
        return $map;
    }

    public function get_user_period_points_map(array $user_ids, $date_from = null, $date_to = null)
    {
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));
        $map = array();
        foreach ($user_ids as $uid) {
            $map[$uid] = (object) array(
                'net' => 0,
                'positive' => 0,
                'negative' => 0,
            );
        }
        if (empty($user_ids) || !$this->db->table_exists('reward_transactions')) {
            return $map;
        }

        $this->db->select(
            'user_id,'
            . ' COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) AS positive_points,'
            . ' COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) AS negative_points,'
            . ' COALESCE(SUM(points), 0) AS net_points',
            false
        );
        $this->db->from('reward_transactions');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('status', 'approved');
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(created_at) <=', $date_to);
        }
        $this->db->group_by('user_id');
        foreach ($this->db->get()->result() as $row) {
            $uid = (int) $row->user_id;
            if (!isset($map[$uid])) {
                continue;
            }
            $map[$uid] = (object) array(
                'net' => (float) $row->net_points,
                'positive' => (float) $row->positive_points,
                'negative' => (float) $row->negative_points,
            );
        }
        return $map;
    }

    public function list_group_members_with_points($group_id, array $period_map = null, $use_period = false)
    {
        $members = $this->list_group_members($group_id);
        if (empty($members)) {
            return array();
        }
        $user_ids = array();
        foreach ($members as $member) {
            $user_ids[] = (int) $member->id;
        }
        $points_map = $this->get_user_points_map($user_ids);
        $levels_map = $this->get_reward_levels_map();
        $enriched = array();
        foreach ($members as $member) {
            $uid = (int) $member->id;
            $points = isset($points_map[$uid]) ? $points_map[$uid] : (object) array('lifetime_points' => 0, 'month_points' => 0, 'current_level_code' => 'starter');
            $level_code = !empty($points->current_level_code) ? (string) $points->current_level_code : 'starter';
            $level = isset($levels_map[$level_code]) ? $levels_map[$level_code] : (object) array('name' => ucfirst($level_code), 'badge_color' => '#6c757d');
            $period = ($use_period && is_array($period_map) && isset($period_map[$uid]))
                ? $period_map[$uid]
                : (object) array('net' => 0, 'positive' => 0, 'negative' => 0);
            $enriched[] = (object) array(
                'id' => $uid,
                'name' => (string) $member->name,
                'email' => isset($member->email) ? (string) $member->email : '',
                'sort_order' => (int) $member->sort_order,
                'lifetime_points' => (float) $points->lifetime_points,
                'month_points' => (float) $points->month_points,
                'period_net' => (float) $period->net,
                'period_positive' => (float) $period->positive,
                'period_negative' => (float) $period->negative,
                'display_points' => $use_period ? (float) $period->net : (float) $points->lifetime_points,
                'level_code' => $level_code,
                'level_name' => (string) $level->name,
                'level_color' => (string) $level->badge_color,
            );
        }
        return $enriched;
    }

    public function calculate_group_points_stats(array $members, $use_period = false)
    {
        $count = count($members);
        if ($count === 0) {
            return (object) array(
                'member_count' => 0,
                'total_lifetime' => 0,
                'total_month' => 0,
                'avg_lifetime' => 0,
                'avg_month' => 0,
                'total_period_net' => 0,
                'total_period_positive' => 0,
                'total_period_negative' => 0,
                'avg_period_points' => 0,
            );
        }
        $total_lifetime = 0;
        $total_month = 0;
        $total_period_net = 0;
        $total_period_positive = 0;
        $total_period_negative = 0;
        foreach ($members as $member) {
            $total_lifetime += (float) $member->lifetime_points;
            $total_month += (float) $member->month_points;
            $total_period_net += (float) $member->period_net;
            $total_period_positive += (float) $member->period_positive;
            $total_period_negative += (float) $member->period_negative;
        }
        return (object) array(
            'member_count' => $count,
            'total_lifetime' => $total_lifetime,
            'total_month' => $total_month,
            'avg_lifetime' => $total_lifetime / $count,
            'avg_month' => $total_month / $count,
            'total_period_net' => $total_period_net,
            'total_period_positive' => $total_period_positive,
            'total_period_negative' => $total_period_negative,
            'avg_period_points' => $total_period_net / $count,
        );
    }

    public function sync_group_members($group_id, array $user_ids)
    {
        if (!$this->db->table_exists('spl_group_members')) {
            return false;
        }
        $group_id = (int) $group_id;
        $this->db->where('group_id', $group_id)->delete('spl_group_members');
        $order = 0;
        $seen = array();
        foreach ($user_ids as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $order++;
            $this->db->insert('spl_group_members', array(
                'group_id' => $group_id,
                'user_id' => $uid,
                'sort_order' => $order,
            ));
        }
        return true;
    }

    public function list_groups_board($active_only = false, $date_from = null, $date_to = null)
    {
        $groups = $this->list_groups($active_only);
        $use_period = ($date_from !== null && $date_from !== '') || ($date_to !== null && $date_to !== '');
        $all_user_ids = array();
        foreach ($groups as $g) {
            $raw_members = $this->list_group_members((int) $g->id);
            foreach ($raw_members as $member) {
                $all_user_ids[] = (int) $member->id;
            }
        }
        $period_map = $use_period ? $this->get_user_period_points_map($all_user_ids, $date_from, $date_to) : array();

        $board = array();
        foreach ($groups as $g) {
            $gid = (int) $g->id;
            $members = $this->list_group_members_with_points($gid, $period_map, $use_period);
            $rules = $this->list_group_rules($gid);
            $stats = $this->calculate_group_points_stats($members, $use_period);
            $board[] = (object) array(
                'id' => $gid,
                'name' => (string) $g->name,
                'code' => (string) $g->code,
                'description' => isset($g->description) ? (string) $g->description : '',
                'poster_path' => isset($g->poster_path) ? (string) $g->poster_path : '',
                'sort_order' => (int) $g->sort_order,
                'is_active' => (int) $g->is_active,
                'members' => $members,
                'rules' => $rules,
                'rule_count' => count($rules),
                'member_count' => (int) $stats->member_count,
                'avg_lifetime_points' => (float) $stats->avg_lifetime,
                'avg_month_points' => (float) $stats->avg_month,
                'total_lifetime_points' => (float) $stats->total_lifetime,
                'total_month_points' => (float) $stats->total_month,
                'avg_period_points' => (float) $stats->avg_period_points,
                'total_period_net' => (float) $stats->total_period_net,
                'total_period_positive' => (float) $stats->total_period_positive,
                'total_period_negative' => (float) $stats->total_period_negative,
                'use_period_points' => $use_period,
            );
        }
        return $board;
    }

    public function sync_all_rules_to_all_groups()
    {
        if (!$this->db->table_exists('spl_groups') || !$this->db->table_exists('spl_group_rules') || !$this->db->table_exists('reward_rules')) {
            return false;
        }

        $groups = $this->list_groups(false);
        if (empty($groups)) {
            return false;
        }

        $this->db->from('reward_rules');
        $this->db->where('is_active', 1);
        $this->db->where('trigger_event', 'reward_claim');
        $this->db->order_by('name');
        $rules = $this->db->get()->result();
        $ruleIds = array();
        foreach ($rules as $rule) {
            $ruleIds[] = (int) $rule->id;
        }

        foreach ($groups as $group) {
            $this->sync_group_rules((int) $group->id, $ruleIds);
        }

        return true;
    }

    public function group_has_rule($group_id, $rule_id)
    {
        if (!$this->db->table_exists('spl_group_rules')) {
            return false;
        }
        return (bool) $this->db
            ->where('group_id', (int) $group_id)
            ->where('rule_id', (int) $rule_id)
            ->limit(1)
            ->count_all_results('spl_group_rules');
    }

    public function is_group_member($group_id, $user_id)
    {
        if (!$this->db->table_exists('spl_group_members')) {
            return false;
        }
        return (bool) $this->db
            ->where('group_id', (int) $group_id)
            ->where('user_id', (int) $user_id)
            ->limit(1)
            ->count_all_results('spl_group_members');
    }

    public function list_groups_for_user($user_id, $active_only = true)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('spl_group_members')) {
            return array();
        }
        $this->db->select('g.*');
        $this->db->from('spl_group_members m');
        $this->db->join('spl_groups g', 'g.id = m.group_id', 'inner');
        $this->db->where('m.user_id', $user_id);
        if ($active_only) {
            $this->db->where('g.is_active', 1);
        }
        $this->db->order_by('g.sort_order')->order_by('g.name');
        return $this->db->get()->result();
    }

    public function get_latest_queue_for_transaction($transaction_id)
    {
        return $this->db->where('transaction_id', (int) $transaction_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('reward_approval_queue')
            ->row();
    }
}
