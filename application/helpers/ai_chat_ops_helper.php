<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Chat ops: memory, follow-ups, rate limit, audit, suggestions, SQL confirm, deep links.
 */

if (!function_exists('ai_chat_memory_get')) {
    function ai_chat_memory_get()
    {
        $CI =& get_instance();
        $mem = $CI->session->userdata('ai_chat_memory');
        return is_array($mem) ? $mem : array();
    }
}

if (!function_exists('ai_chat_memory_set')) {
    /**
     * @param array $patch
     * @return void
     */
    function ai_chat_memory_set(array $patch)
    {
        $CI =& get_instance();
        $mem = ai_chat_memory_get();
        foreach ($patch as $k => $v) {
            $mem[$k] = $v;
        }
        $mem['updated_at'] = date('Y-m-d H:i:s');
        $CI->session->set_userdata('ai_chat_memory', $mem);
    }
}

if (!function_exists('ai_chat_memory_clear')) {
    function ai_chat_memory_clear()
    {
        $CI =& get_instance();
        $CI->session->unset_userdata('ai_chat_memory');
        $CI->session->unset_userdata('ai_pending_sql');
    }
}

if (!function_exists('ai_chat_is_followup')) {
    /**
     * Detect short follow-ups that refer to previous result.
     *
     * @param string $message
     * @return string|null export_csv|export_excel|export_pdf|repeat|filter_hint|null
     */
    function ai_chat_is_followup($message)
    {
        $m = strtolower(trim((string) $message));
        if ($m === '') {
            return null;
        }
        if (preg_match('/^(export|download)\s*(as\s*)?(csv|excel|xlsx|pdf)?$/i', $m, $mm)) {
            $fmt = isset($mm[3]) ? strtolower($mm[3]) : 'csv';
            if ($fmt === 'xlsx') {
                $fmt = 'excel';
            }
            return 'export_' . $fmt;
        }
        if (preg_match('/\b(export|download)\b.*\b(csv|excel|pdf)\b/i', $m, $mm)) {
            return 'export_' . strtolower($mm[2] === 'xlsx' ? 'excel' : $mm[2]);
        }
        if (preg_match('/^(same|again|repeat|te\s*ch|te\s*same|तोच|वही)\b/iu', $m)) {
            return 'repeat';
        }
        if (preg_match('/^(only|just|filter|show only|फक्त|केवल)\b/iu', $m)) {
            return 'filter_hint';
        }
        return null;
    }
}

if (!function_exists('ai_chat_suggestion_chips')) {
    /**
     * Chips from tool catalog, filtered by current user's module permissions.
     *
     * @return array<int,array{label:string,message:string}>
     */
    function ai_chat_suggestion_chips()
    {
        $chips = array();
        if (!function_exists('ai_chat_tool_catalog')) {
            return $chips;
        }
        foreach (ai_chat_tool_catalog() as $tool => $meta) {
            if (function_exists('ai_chat_user_can_use_tool') && !ai_chat_user_can_use_tool($tool)) {
                continue;
            }
            $chips[] = array(
                'label' => isset($meta['label']) ? $meta['label'] : $tool,
                'message' => isset($meta['message']) ? $meta['message'] : $tool,
            );
        }
        return $chips;
    }
}

if (!function_exists('ai_chat_deep_link')) {
    /**
     * @param string $module
     * @param int    $id
     * @return string HTML anchor or empty
     */
    function ai_chat_deep_link($module, $id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return '';
        }
        $map = array(
            'tasks' => 'tasks/view/' . $id,
            'leave' => 'leave_requests/view/' . $id,
            'projects' => 'projects/view/' . $id,
            'attendance' => 'attendance',
            'spl' => 'spl',
            'daily_activity' => 'daily-activity',
        );
        if (!isset($map[$module])) {
            return '';
        }
        $url = site_url($map[$module]);
        return ' <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="small">Open</a>';
    }
}

if (!function_exists('ai_chat_rate_limit_ok')) {
    /**
     * Soft rate limit: max N AI turns per user per hour (session + optional DB).
     *
     * @param int $user_id
     * @param int $max_per_hour
     * @return array{ok:bool,remaining:int,message?:string}
     */
    function ai_chat_rate_limit_ok($user_id, $max_per_hour = 60)
    {
        $CI =& get_instance();
        $user_id = (int) $user_id;
        $bucket = $CI->session->userdata('ai_rate_bucket');
        if (!is_array($bucket) || empty($bucket['hour']) || $bucket['hour'] !== date('YmdH')) {
            $bucket = array('hour' => date('YmdH'), 'count' => 0);
        }
        if ((int) $bucket['count'] >= (int) $max_per_hour) {
            return array(
                'ok' => false,
                'remaining' => 0,
                'message' => 'AI rate limit reached. Please try again in a bit.',
            );
        }
        $bucket['count'] = (int) $bucket['count'] + 1;
        $CI->session->set_userdata('ai_rate_bucket', $bucket);
        return array('ok' => true, 'remaining' => max(0, $max_per_hour - (int) $bucket['count']));
    }
}

if (!function_exists('ai_chat_audit_ensure')) {
    function ai_chat_audit_ensure()
    {
        $CI =& get_instance();
        $CI->db->query("CREATE TABLE IF NOT EXISTS `ai_chat_intent_log` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `message` VARCHAR(500) NOT NULL,
            `tool` VARCHAR(64) NULL,
            `source` VARCHAR(32) NULL,
            `ok` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ai_intent_user` (`user_id`),
            KEY `idx_ai_intent_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('ai_chat_audit_log')) {
    /**
     * @param int         $user_id
     * @param string      $message
     * @param string|null $tool
     * @param string      $source
     * @param bool        $ok
     * @return void
     */
    function ai_chat_audit_log($user_id, $message, $tool, $source, $ok = true)
    {
        try {
            ai_chat_audit_ensure();
            $CI =& get_instance();
            $CI->db->insert('ai_chat_intent_log', array(
                'user_id' => (int) $user_id,
                'message' => mb_substr((string) $message, 0, 500),
                'tool' => $tool ? mb_substr((string) $tool, 0, 64) : null,
                'source' => mb_substr((string) $source, 0, 32),
                'ok' => $ok ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        } catch (Exception $e) {
            log_message('error', 'ai_chat_audit_log: ' . $e->getMessage());
        }
    }
}

if (!function_exists('ai_chat_apply_hierarchy_user_ids')) {
    /**
     * Restrict query builder to hierarchy-visible user_ids.
     *
     * @param CI_DB_query_builder $db
     * @param string              $column
     * @return CI_DB_query_builder
     */
    function ai_chat_apply_hierarchy_user_ids($db, $column = 'user_id')
    {
        $CI =& get_instance();
        if (!function_exists('get_accessible_hierarchy_user_ids')) {
            $CI->load->helper('hierarchy_filter');
        }
        $allowed = get_accessible_hierarchy_user_ids();
        if (empty($allowed)) {
            return $db; // admin / all-org
        }
        $db->where_in($column, array_map('intval', $allowed));
        return $db;
    }
}

if (!function_exists('ai_chat_sql_confirm_html')) {
    /**
     * @param string $token
     * @param string $preview_sql
     * @return string
     */
    function ai_chat_sql_confirm_html($token, $preview_sql)
    {
        $safe = htmlspecialchars((string) $preview_sql, ENT_QUOTES, 'UTF-8');
        $tok = htmlspecialchars((string) $token, ENT_QUOTES, 'UTF-8');
        return '<p>I prepared a database query. Review the <strong>full</strong> SQL below, then confirm:</p>'
            . '<pre class="small bg-light p-2 rounded border" style="white-space:pre-wrap;max-height:280px;overflow:auto;">' . $safe . '</pre>'
            . '<button type="button" class="btn btn-sm btn-primary me-2 ai-confirm-sql" data-token="' . $tok . '">Run query</button>'
            . '<button type="button" class="btn btn-sm btn-outline-secondary ai-cancel-sql" data-token="' . $tok . '">Cancel</button>';
    }
}

if (!function_exists('ai_chat_eval_cases')) {
    /**
     * Deep eval phrases → expected tool (null = non-tool).
     *
     * @return array<int,array{q:string,tool:?string}>
     */
    function ai_chat_eval_cases()
    {
        return array(
            array('q' => 'What is my leave balance?', 'tool' => 'my_leave_balance'),
            array('q' => 'mazya balance ;eaes kiti aahet sang', 'tool' => 'my_leave_balance'),
            array('q' => 'मेरी छुट्टी कितनी बची है', 'tool' => 'my_leave_balance'),
            array('q' => 'Who is on leave today?', 'tool' => 'who_on_leave_today'),
            array('q' => 'aaj kon leave var aahe', 'tool' => 'who_on_leave_today'),
            array('q' => 'My attendance today', 'tool' => 'my_attendance_today'),
            array('q' => 'mazi hajri aaj', 'tool' => 'my_attendance_today'),
            array('q' => 'Show my open tasks', 'tool' => 'my_open_tasks'),
            array('q' => 'My daily activity today', 'tool' => 'my_daily_activity_today'),
            array('q' => 'Pending SPL approvals', 'tool' => 'spl_pending_approvals'),
            array('q' => 'Who is late today?', 'tool' => 'who_late_today'),
            array('q' => 'mala report de attendance all user cha', 'tool' => 'attendance_today_report'),
            array('q' => 'Show today attendance report', 'tool' => 'attendance_today_report'),
            array('q' => 'I want all user attendance report for this month', 'tool' => 'attendance_today_report'),
            array('q' => 'all users attendance this month', 'tool' => 'attendance_today_report'),
            array('q' => 'Show my pending leave requests', 'tool' => 'my_pending_leaves'),
            array('q' => 'My SPL points', 'tool' => 'my_spl_points'),
            array('q' => 'hello how are you', 'tool' => null),
            array('q' => 'export csv', 'tool' => null), // follow-up, not a tool
        );
    }
}
