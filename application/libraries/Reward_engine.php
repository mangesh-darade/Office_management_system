<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rule-based reward engine — configurable points, idempotent awards.
 */
class Reward_engine
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('Reward_model', 'rewards');
        $this->CI->load->helper(array('rewards_schema', 'notification'));
        rewards_schema_ensure($this->CI->db);
    }

    /**
     * @param string $trigger_event
     * @param array  $context user_id, source_module, source_record_id, payload, reference_label, actor_id, occurred_at
     * @return array awarded transaction ids
     */
    public function dispatch($trigger_event, array $context)
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : 0;
        if ($user_id <= 0) {
            return array();
        }

        $rules = $this->CI->rewards->get_active_rules_for_event($trigger_event);
        if (empty($rules)) {
            return array();
        }

        $payload = isset($context['payload']) && is_array($context['payload']) ? $context['payload'] : array();
        $source_module = isset($context['source_module']) ? (string) $context['source_module'] : 'system';
        $source_record_id = isset($context['source_record_id']) ? (int) $context['source_record_id'] : null;
        $reference_label = isset($context['reference_label']) ? (string) $context['reference_label'] : '';
        $period_key = isset($context['period_key']) ? (string) $context['period_key'] : date('Y-m');
        $awarded = array();

        foreach ($rules as $rule) {
            if (!$this->matches_conditions($rule, $payload)) {
                continue;
            }
            if (!$this->within_caps($rule, $user_id, $period_key)) {
                continue;
            }

            $idem = $this->build_idempotency_key(
                $rule,
                $user_id,
                $source_module,
                $source_record_id,
                $trigger_event,
                isset($context['occurred_at']) ? (string) $context['occurred_at'] : '',
                isset($context['idempotency_salt']) ? (string) $context['idempotency_salt'] : ''
            );
            if ($this->CI->rewards->rule_exists_by_key($idem)) {
                continue;
            }

            $status = ((int) $rule->requires_approval === 1) ? 'pending' : 'approved';
            $txId = $this->CI->rewards->insert_transaction(array(
                'user_id' => $user_id,
                'rule_id' => (int) $rule->id,
                'category_id' => $rule->category_id ? (int) $rule->category_id : null,
                'points' => (float) $rule->points,
                'status' => $status,
                'source_module' => $source_module,
                'source_record_id' => $source_record_id,
                'source_event' => $trigger_event,
                'idempotency_key' => $idem,
                'reference_label' => $reference_label !== '' ? $reference_label : $rule->name,
                'granted_by' => isset($context['actor_id']) ? (int) $context['actor_id'] : null,
                'period_key' => $period_key,
                'created_at' => isset($context['occurred_at']) ? $context['occurred_at'] : date('Y-m-d H:i:s'),
            ));

            $this->CI->rewards->audit('transaction', $txId, 'created', isset($context['actor_id']) ? (int) $context['actor_id'] : $user_id, null, array('rule' => $rule->code, 'points' => $rule->points));

            if ($status === 'pending') {
                $this->CI->rewards->insert_approval_queue(array(
                    'user_id' => $user_id,
                    'submitted_by' => isset($context['actor_id']) ? (int) $context['actor_id'] : $user_id,
                    'rule_id' => (int) $rule->id,
                    'requested_points' => (float) $rule->points,
                    'status' => 'pending',
                    'submitted_at' => isset($context['occurred_at']) ? $context['occurred_at'] : date('Y-m-d H:i:s'),
                    'source_module' => $source_module,
                    'source_record_id' => $source_record_id,
                    'transaction_id' => $txId,
                ));
            }

            if ($status === 'approved') {
                $levelInfo = $this->CI->rewards->update_user_summary($user_id);
                $this->notify_points_awarded($user_id, $rule, (float) $rule->points);
                if (!empty($levelInfo['level_changed'])) {
                    $this->notify_level_up($user_id, $levelInfo['new_level']);
                }
            }

            $awarded[] = $txId;
        }

        return $awarded;
    }

    protected function matches_conditions($rule, array $payload)
    {
        if (empty($rule->condition_json)) {
            return true;
        }
        $cond = json_decode($rule->condition_json, true);
        if (!is_array($cond) || empty($cond)) {
            return true;
        }

        if (isset($cond['status']) && is_array($cond['status'])) {
            $actual = isset($payload['status']) ? (string) $payload['status'] : '';
            if (!in_array($actual, $cond['status'], true)) {
                return false;
            }
        }

        if (!empty($cond['before_due']) && empty($payload['before_due'])) {
            return false;
        }

        if (!empty($cond['passed']) && empty($payload['passed'])) {
            return false;
        }

        if (isset($cond['min_rating'])) {
            $rating = isset($payload['rating']) ? (int) $payload['rating'] : 0;
            if ($rating < (int) $cond['min_rating']) {
                return false;
            }
        }

        if (isset($cond['min_score'])) {
            $score = 0;
            if (isset($payload['score_percent'])) {
                $score = (float) $payload['score_percent'];
            } elseif (isset($payload['score'])) {
                $score = (float) $payload['score'];
            }
            if ($score < (float) $cond['min_score']) {
                return false;
            }
        }

        if (isset($cond['claim_type'])) {
            $actual = isset($payload['claim_type']) ? (string) $payload['claim_type'] : '';
            if ($actual !== (string) $cond['claim_type']) {
                return false;
            }
        }

        if (isset($cond['penalty_type'])) {
            $actual = isset($payload['penalty_type']) ? (string) $payload['penalty_type'] : '';
            if ($actual !== (string) $cond['penalty_type']) {
                return false;
            }
        }

        if (isset($cond['attendance_tier'])) {
            $actual = isset($payload['attendance_tier']) ? (string) $payload['attendance_tier'] : '';
            if ($actual !== (string) $cond['attendance_tier']) {
                return false;
            }
        }

        if (isset($cond['checkout_tier'])) {
            $actual = isset($payload['checkout_tier']) ? (string) $payload['checkout_tier'] : '';
            if ($actual !== (string) $cond['checkout_tier']) {
                return false;
            }
        }

        if (isset($cond['leave_outcome'])) {
            $actual = isset($payload['leave_outcome']) ? (string) $payload['leave_outcome'] : '';
            if ($actual !== (string) $cond['leave_outcome']) {
                return false;
            }
        }

        if (isset($cond['streak_type'])) {
            $actual = isset($payload['streak_type']) ? (string) $payload['streak_type'] : '';
            if ($actual !== (string) $cond['streak_type']) {
                return false;
            }
        }

        return true;
    }

    protected function within_caps($rule, $user_id, $period_key)
    {
        if ($rule->max_per_day !== null && (int) $rule->max_per_day > 0) {
            if ($this->CI->rewards->count_rule_awards_today((int) $rule->id, $user_id) >= (int) $rule->max_per_day) {
                return false;
            }
        }
        if ($rule->max_per_period !== null && (int) $rule->max_per_period > 0 && $rule->period_type) {
            $pk = $period_key !== '' ? $period_key : $this->period_key_for_type($rule->period_type);
            if ($this->CI->rewards->count_rule_awards_in_period((int) $rule->id, $user_id, $pk) >= (int) $rule->max_per_period) {
                return false;
            }
        }
        return true;
    }

    protected function period_key_for_type($type)
    {
        switch ($type) {
            case 'week':
                return date('o-\WW');
            case 'month':
                return date('Y-m');
            case 'quarter':
                $m = (int) date('n');
                return date('Y') . '-Q' . (string) ceil($m / 3);
            case 'year':
                return date('Y');
            default:
                return date('Y-m-d');
        }
    }

    protected function build_idempotency_key($rule, $user_id, $module, $record_id, $event, $occurred_at = '', $salt = '')
    {
        $parts = array($rule->code, (int) $user_id, $module, (int) $record_id, $event);
        if ((int) $rule->max_per_day === 1) {
            if ($occurred_at !== '') {
                $ts = strtotime($occurred_at);
                $parts[] = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
            } else {
                $parts[] = date('Y-m-d');
            }
        } elseif ($event === 'reward_claim' && (int) $record_id <= 0) {
            // Manual SPL claims have no source row — each submit must be unique,
            // otherwise the same activity can never be submitted again.
            $parts[] = $salt !== '' ? $salt : uniqid('claim_', true);
        }
        return sha1(implode('|', $parts));
    }

    protected function notify_points_awarded($user_id, $rule, $points)
    {
        $sign = $points >= 0 ? '+' : '';
        if (!function_exists('create_notification')) {
            return;
        }
        create_notification(
            $user_id,
            $sign . $points . ' reward points',
            $rule->name,
            'success',
            'rewards',
            null,
            site_url('rewards/history')
        );
    }

    protected function notify_level_up($user_id, $level_code)
    {
        $level = $this->CI->rewards->get_level($level_code);
        $name = $level ? $level->name : ucfirst($level_code);
        if (!function_exists('create_notification')) {
            return;
        }
        create_notification(
            $user_id,
            'Level up: ' . $name,
            'Congratulations! You reached a new recognition level.',
            'success',
            'rewards',
            null,
            site_url('rewards')
        );
    }

    /**
     * Manual grant by admin/manager.
     */
    public function manual_grant($user_id, $points, $label, $granted_by, $notes = '')
    {
        $idem = sha1('manual|' . (int) $user_id . '|' . microtime(true) . '|' . (int) $granted_by);
        $txId = $this->CI->rewards->insert_transaction(array(
            'user_id' => (int) $user_id,
            'rule_id' => null,
            'points' => (float) $points,
            'status' => 'approved',
            'source_module' => 'rewards',
            'source_record_id' => null,
            'source_event' => 'manual_award',
            'idempotency_key' => $idem,
            'reference_label' => $label,
            'granted_by' => (int) $granted_by,
            'period_key' => date('Y-m'),
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $levelInfo = $this->CI->rewards->update_user_summary($user_id);
        create_notification($user_id, '+' . $points . ' points', $label, 'success', 'rewards', $txId, site_url('rewards/history'));
        if (!empty($levelInfo['level_changed'])) {
            $this->notify_level_up($user_id, $levelInfo['new_level']);
        }
        $this->CI->rewards->audit('transaction', $txId, 'manual_grant', (int) $granted_by, null, array('points' => $points, 'label' => $label));
        return $txId;
    }
}
