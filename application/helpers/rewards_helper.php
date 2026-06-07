<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('reward_engine_dispatch')) {
    /**
     * Fire reward rules for an event (safe no-op if engine unavailable).
     *
     * @param string $trigger_event
     * @param array  $context
     * @return array transaction ids
     */
    function reward_engine_dispatch($trigger_event, array $context)
    {
        $CI =& get_instance();
        if (!$CI->db->table_exists('reward_rules')) {
            $CI->load->helper('rewards_schema');
            rewards_schema_ensure($CI->db);
        }
        $CI->load->library('Reward_engine');
        return $CI->reward_engine->dispatch($trigger_event, $context);
    }
}

if (!function_exists('reward_engine_claim')) {
    /**
     * Submit a named reward claim (lead/admin approval rules).
     *
     * @param string $claim_type Rule claim_type / condition key
     * @param array  $context    user_id, actor_id, reference_label, source_module, source_record_id
     * @return array
     */
    function reward_engine_claim($claim_type, array $context)
    {
        $payload = isset($context['payload']) && is_array($context['payload']) ? $context['payload'] : array();
        $payload['claim_type'] = (string) $claim_type;
        $context['payload'] = $payload;
        return reward_engine_dispatch('reward_claim', $context);
    }
}
