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

    public function get_category($id)
    {
        if (!$this->db->table_exists('reward_categories')) {
            return null;
        }
        return $this->db->where('id', (int) $id)->get('reward_categories')->row();
    }

    public function get_category_by_code($code)
    {
        if (!$this->db->table_exists('reward_categories')) {
            return null;
        }
        return $this->db->where('code', (string) $code)->get('reward_categories')->row();
    }

    public function save_category(array $data, $id = null)
    {
        if (!$this->db->table_exists('reward_categories')) {
            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) {
            $this->db->where('id', (int) $id)->update('reward_categories', $data);
            return (int) $id;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('reward_categories', $data);
        return (int) $this->db->insert_id();
    }

    public function toggle_category($id)
    {
        $row = $this->get_category($id);
        if (!$row) {
            return false;
        }
        $next = ((int) $row->is_active === 1) ? 0 : 1;
        $this->db->where('id', (int) $id)->update('reward_categories', array(
            'is_active' => $next,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        return true;
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

    public function list_group_members_with_points($group_id, ?array $period_map = null, $use_period = false)
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

    public function count_reward_users()
    {
        if (!$this->db->table_exists('user_reward_summary')) {
            return 0;
        }
        return (int) $this->db->count_all('user_reward_summary');
    }

    public function get_user_lifetime_rank($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('user_reward_summary')) {
            return 0;
        }
        $row = $this->db->select('lifetime_points')->where('user_id', $user_id)->get('user_reward_summary')->row();
        $pts = $row ? (float) $row->lifetime_points : 0;
        $higher = (int) $this->db->where('lifetime_points >', $pts)->count_all_results('user_reward_summary');
        return $higher + 1;
    }

    public function get_user_month_rank($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('user_reward_summary')) {
            return 0;
        }
        $row = $this->db->select('month_points')->where('user_id', $user_id)->get('user_reward_summary')->row();
        $pts = $row ? (float) $row->month_points : 0;
        $higher = (int) $this->db->where('month_points >', $pts)->count_all_results('user_reward_summary');
        return $higher + 1;
    }

    public function list_org_activity_feed($limit = 20, $date_from = null, $date_to = null)
    {
        if (!$this->db->table_exists('reward_transactions')) {
            return array();
        }
        $this->db->select('t.*, r.name AS rule_name, COALESCE(c.name, rc.name) AS category_name, u.name AS user_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('reward_rules r', 'r.id = t.rule_id', 'left');
        $this->db->join('reward_categories c', 'c.id = t.category_id', 'left');
        $this->db->join('reward_categories rc', 'rc.id = r.category_id', 'left');
        $this->db->join('users u', 'u.id = t.user_id', 'left');
        $this->db->where('t.status', 'approved');
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

    public function sum_org_activity_points($date_from = null, $date_to = null)
    {
        if (!$this->db->table_exists('reward_transactions')) {
            return array(
                'positive' => 0,
                'negative' => 0,
                'net' => 0,
                'pending_points' => 0,
                'pending_count' => 0,
                'approved_count' => 0,
                'rejected_count' => 0,
            );
        }
        $this->db->select(
            'COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points > 0 THEN t.points ELSE 0 END), 0) AS positive_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points < 0 THEN ABS(t.points) ELSE 0 END), 0) AS negative_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' THEN t.points ELSE 0 END), 0) AS net_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'pending\' THEN t.points ELSE 0 END), 0) AS pending_points,'
            . ' SUM(CASE WHEN t.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,'
            . ' SUM(CASE WHEN t.status = \'approved\' THEN 1 ELSE 0 END) AS approved_count,'
            . ' SUM(CASE WHEN t.status = \'rejected\' THEN 1 ELSE 0 END) AS rejected_count',
            false
        );
        $this->db->from('reward_transactions t');
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
            'pending_points' => $row ? (float) $row->pending_points : 0,
            'pending_count' => $row ? (int) $row->pending_count : 0,
            'approved_count' => $row ? (int) $row->approved_count : 0,
            'rejected_count' => $row ? (int) $row->rejected_count : 0,
        );
    }

    public function sum_group_member_points_by_status($group_id, $date_from = null, $date_to = null)
    {
        $members = $this->list_group_members((int) $group_id);
        if (empty($members) || !$this->db->table_exists('reward_transactions')) {
            return (object) array(
                'approved' => 0,
                'pending' => 0,
                'deducted' => 0,
                'net' => 0,
            );
        }
        $user_ids = array();
        foreach ($members as $member) {
            $user_ids[] = (int) $member->id;
        }
        $this->db->select(
            'COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points > 0 THEN t.points ELSE 0 END), 0) AS approved_positive,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'pending\' THEN t.points ELSE 0 END), 0) AS pending_points,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' AND t.points < 0 THEN ABS(t.points) ELSE 0 END), 0) AS deducted,'
            . ' COALESCE(SUM(CASE WHEN t.status = \'approved\' THEN t.points ELSE 0 END), 0) AS net',
            false
        );
        $this->db->from('reward_transactions t');
        $this->db->where_in('t.user_id', $user_ids);
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $row = $this->db->get()->row();
        return (object) array(
            'approved' => $row ? (float) $row->approved_positive : 0,
            'pending' => $row ? (float) $row->pending_points : 0,
            'deducted' => $row ? (float) $row->deducted : 0,
            'net' => $row ? (float) $row->net : 0,
        );
    }

    public function compute_points_streak_days($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('reward_transactions')) {
            return array('current' => 0, 'best' => 0);
        }
        $this->db->select('DATE(created_at) AS award_date', false);
        $this->db->from('reward_transactions');
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'approved');
        $this->db->where('points >', 0);
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('award_date', 'DESC');
        $rows = $this->db->get()->result();
        if (empty($rows)) {
            return array('current' => 0, 'best' => 0);
        }
        $dates = array();
        foreach ($rows as $row) {
            $dates[] = (string) $row->award_date;
        }
        $dateSet = array_flip($dates);
        $current = 0;
        $cursor = date('Y-m-d');
        while (isset($dateSet[$cursor])) {
            $current++;
            $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
        }
        $best = 0;
        $run = 0;
        $prev = null;
        foreach (array_reverse($dates) as $d) {
            if ($prev === null) {
                $run = 1;
            } elseif (strtotime($d) === strtotime($prev . ' +1 day')) {
                $run++;
            } else {
                $run = 1;
            }
            if ($run > $best) {
                $best = $run;
            }
            $prev = $d;
        }
        return array('current' => $current, 'best' => $best);
    }

    public function list_top_users_by_period($date_from, $date_to, $limit = 4)
    {
        if (!$this->db->table_exists('reward_transactions')) {
            return array();
        }
        $this->db->select('t.user_id, SUM(t.points) AS net_points, u.name AS user_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('users u', 'u.id = t.user_id', 'left');
        $this->db->where('t.status', 'approved');
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $this->db->group_by('t.user_id');
        $this->db->order_by('net_points', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function list_top_users_in_group_by_period($group_id, $date_from, $date_to, $limit = 3)
    {
        $group_id = (int) $group_id;
        if ($group_id <= 0 || !$this->db->table_exists('reward_transactions') || !$this->db->table_exists('spl_group_members')) {
            return array();
        }
        $this->db->select('t.user_id, SUM(t.points) AS net_points, u.name AS user_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('spl_group_members m', 'm.user_id = t.user_id', 'inner');
        $this->db->join('users u', 'u.id = t.user_id', 'left');
        $this->db->where('m.group_id', $group_id);
        $this->db->where('t.status', 'approved');
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $this->db->group_by('t.user_id');
        $this->db->order_by('net_points', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result();
    }

    public function list_top_user_by_category($category_code, $date_from, $date_to)
    {
        if (!$this->db->table_exists('reward_transactions') || !$this->db->table_exists('reward_categories')) {
            return null;
        }
        $cat = $this->db->where('code', (string) $category_code)->get('reward_categories')->row();
        if (!$cat) {
            return null;
        }
        $cat_id = (int) $cat->id;
        $this->db->select('t.user_id, SUM(t.points) AS net_points, u.name AS user_name', false);
        $this->db->from('reward_transactions t');
        $this->db->join('reward_rules r', 'r.id = t.rule_id', 'left');
        $this->db->join('users u', 'u.id = t.user_id', 'left');
        $this->db->where('t.status', 'approved');
        $this->db->group_start()
            ->where('t.category_id', $cat_id)
            ->or_where('r.category_id', $cat_id)
            ->group_end();
        if ($date_from !== null && $date_from !== '') {
            $this->db->where('DATE(t.created_at) >=', $date_from);
        }
        if ($date_to !== null && $date_to !== '') {
            $this->db->where('DATE(t.created_at) <=', $date_to);
        }
        $this->db->group_by('t.user_id');
        $this->db->order_by('net_points', 'DESC');
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    public function list_category_leaders($date_from, $date_to, $limit = 5)
    {
        if (!$this->db->table_exists('reward_transactions') || !$this->db->table_exists('reward_categories')) {
            return array();
        }
        $categories = $this->db->where('is_active', 1)->order_by('sort_order')->limit(20)->get('reward_categories')->result();
        $leaders = array();
        foreach ($categories as $cat) {
            $cat_id = (int) $cat->id;
            $this->db->select('t.user_id, SUM(t.points) AS net_points, u.name AS user_name', false);
            $this->db->from('reward_transactions t');
            $this->db->join('reward_rules r', 'r.id = t.rule_id', 'left');
            $this->db->join('users u', 'u.id = t.user_id', 'left');
            $this->db->where('t.status', 'approved');
            $this->db->group_start()
                ->where('t.category_id', $cat_id)
                ->or_where('r.category_id', $cat_id)
                ->group_end();
            if ($date_from !== null && $date_from !== '') {
                $this->db->where('DATE(t.created_at) >=', $date_from);
            }
            if ($date_to !== null && $date_to !== '') {
                $this->db->where('DATE(t.created_at) <=', $date_to);
            }
            $this->db->group_by('t.user_id');
            $this->db->order_by('net_points', 'DESC');
            $this->db->limit(1);
            $top = $this->db->get()->row();
            if ($top && (float) $top->net_points > 0) {
                $leaders[] = (object) array(
                    'category_code' => (string) $cat->code,
                    'category_name' => (string) $cat->name,
                    'user_id' => (int) $top->user_id,
                    'user_name' => (string) $top->user_name,
                    'net_points' => (float) $top->net_points,
                );
            }
            if (count($leaders) >= (int) $limit) {
                break;
            }
        }
        return $leaders;
    }
}
