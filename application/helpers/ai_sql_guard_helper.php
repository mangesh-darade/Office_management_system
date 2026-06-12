<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI SQL guard: deny-lists and audit logging for LLM-generated queries.
 *
 * Layers (defense in depth, applied in Ai_chat::execute_safe_query):
 *  1. Table deny-list  — tables holding credentials/secrets are never queryable.
 *  2. Column deny-list — SQL text referencing sensitive columns is rejected.
 *  3. Result scrubbing — sensitive columns are stripped from rows (covers SELECT *).
 *  4. Audit logging    — every executed AI query is written to security_audit_log.
 */

if (!function_exists('ai_sql_denied_tables')) {
    /**
     * Tables that must never be exposed through AI chat, regardless of role.
     *
     * @return string[]
     */
    function ai_sql_denied_tables()
    {
        return array(
            // Credentials / API secrets
            'api_integrations',
            'settings',                  // holds cron_secret_token, ai_gemini_api_key, SMTP config
            'system_settings',
            'email_settings',
            'coaching_payment_settings', // Razorpay key_secret
            // Auth / session artifacts
            'login_attempts',
            'remember_tokens',
            'security_audit_log',
            'ci_sessions',
            'sessions',
        );
    }
}

if (!function_exists('ai_sql_denied_column_pattern')) {
    /**
     * Regex matching sensitive column names (word-boundary, case-insensitive).
     *
     * @return string
     */
    function ai_sql_denied_column_pattern()
    {
        return '/\b('
            . 'password|password_hash|password_changed_at'
            . '|auth_token|api_key|access_token|refresh_token|secret|key_secret|webhook_secret'
            . '|remember_token|email_verify_token|reset_token'
            . '|otp|code_hash|two_factor[a-z_]*'
            . ')\b/i';
    }
}

if (!function_exists('ai_sql_guard_check')) {
    /**
     * Validate AI-generated SQL against the deny-lists.
     *
     * @param string   $sql
     * @param string[] $tables_in_query Lowercased table names referenced by the query.
     * @return string|null Error message when blocked, null when allowed.
     */
    function ai_sql_guard_check($sql, array $tables_in_query)
    {
        $denied_tables = ai_sql_denied_tables();
        foreach ($tables_in_query as $table) {
            if (in_array($table, $denied_tables, true)) {
                return "Access to the '{$table}' table is not available through AI chat.";
            }
        }

        if (preg_match(ai_sql_denied_column_pattern(), $sql, $m)) {
            return "Queries referencing sensitive columns ('{$m[1]}') are blocked.";
        }

        return null;
    }
}

if (!function_exists('ai_sql_scrub_result')) {
    /**
     * Strip sensitive columns from result rows (covers SELECT * and aliases
     * that slip past the SQL-text check).
     *
     * @param array $rows result_array() rows
     * @return array
     */
    function ai_sql_scrub_result(array $rows)
    {
        if (empty($rows)) {
            return $rows;
        }
        $pattern = ai_sql_denied_column_pattern();
        $denied_keys = array();
        foreach (array_keys($rows[0]) as $key) {
            if (preg_match($pattern, (string) $key)) {
                $denied_keys[] = $key;
            }
        }
        if (empty($denied_keys)) {
            return $rows;
        }
        foreach ($rows as $i => $row) {
            foreach ($denied_keys as $key) {
                unset($rows[$i][$key]);
            }
        }
        return $rows;
    }
}

if (!function_exists('ai_sql_audit_log')) {
    /**
     * Record an executed (or blocked) AI query in security_audit_log.
     *
     * @param string $sql
     * @param string $status 'executed' | 'blocked'
     * @param string $detail Extra context (block reason, row count).
     * @return void
     */
    function ai_sql_audit_log($sql, $status, $detail = '')
    {
        $CI =& get_instance();
        try {
            if (!isset($CI->audit)) {
                $CI->load->model('Security_audit_model', 'audit');
            }
            $summary = 'AI SQL ' . $status
                . ($detail !== '' ? ' (' . $detail . ')' : '')
                . ': ' . substr(trim((string) $sql), 0, 1000);
            $CI->audit->log('ai_sql_' . $status, (int) $CI->session->userdata('user_id'), $summary);
        } catch (Exception $e) {
            log_message('error', 'ai_sql_audit_log failed: ' . $e->getMessage());
        }
    }
}
