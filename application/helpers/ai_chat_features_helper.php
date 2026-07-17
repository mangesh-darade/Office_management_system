<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Chat advanced features: table map, deterministic tools, data-scope SQL enforce.
 */

if (!function_exists('ai_chat_table_module_map')) {
    /**
     * Table → permission module. Loaded from config/table_module_mapping.php (+ AI extras).
     *
     * @return array<string,string>
     */
    function ai_chat_table_module_map()
    {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $cfg = array();
        $CI =& get_instance();
        if ($CI) {
            $CI->config->load('table_module_mapping', true);
            $loaded = $CI->config->item('table_module_mapping', 'table_module_mapping');
            if (!is_array($loaded)) {
                $loaded = $CI->config->item('table_module_mapping');
            }
            if (is_array($loaded)) {
                $cfg = $loaded;
            }
        }

        // Tables used by AI tools / RAG but not always listed in activity mapping.
        $extras = array(
            'leave_approvals' => 'leave_requests',
            'holidays' => 'leave_requests',
            'payroll' => 'payroll',
            'expenses' => 'expenses',
            'activity_log' => 'activity',
            'chats' => 'chats',
            'reward_rules' => 'spl',
            'reward_categories' => 'spl',
            'training_courses' => 'training_lms',
            'training_enrollments' => 'training_lms',
            'training_modules' => 'training_lms',
        );

        $map = array_merge($extras, $cfg);

        // Never expose secret-bearing tables via AI schema even if mapped.
        unset($map['settings'], $map['api_integrations'], $map['permissions']);

        return $map;
    }
}

if (!function_exists('ai_chat_schema_whitelist')) {
    /**
     * @return string[]
     */
    function ai_chat_schema_whitelist()
    {
        return array_keys(ai_chat_table_module_map());
    }
}

if (!function_exists('ai_chat_table_descriptions')) {
    /**
     * Optional short hints only — live columns come from DB list_fields().
     *
     * @return array<string,string>
     */
    function ai_chat_table_descriptions()
    {
        return array();
    }
}

if (!function_exists('ai_chat_tool_catalog')) {
    /**
     * Single source of truth for tools: modules (RBAC), chip text, classifier hint.
     *
     * @return array<string,array{modules_any:string[],label:string,message:string,hint:string}>
     */
    function ai_chat_tool_catalog()
    {
        return array(
            'my_leave_balance' => array(
                'modules_any' => array('leave_balances', 'leave_requests', 'leaves'),
                'label' => 'Leave balance',
                'message' => 'What is my leave balance?',
                'hint' => 'Own leave balance / remaining leaves',
            ),
            'my_pending_leaves' => array(
                'modules_any' => array('leave_requests', 'leaves'),
                'label' => 'My pending leave',
                'message' => 'Show my pending leave requests',
                'hint' => 'Own pending leave requests',
            ),
            'who_on_leave_today' => array(
                'modules_any' => array('leave_requests', 'leaves'),
                'label' => 'On leave today',
                'message' => 'Who is on leave today?',
                'hint' => 'Who is on approved leave today (team/scope)',
            ),
            'my_attendance_today' => array(
                'modules_any' => array('attendance'),
                'label' => 'My attendance',
                'message' => 'My attendance today',
                'hint' => 'Own attendance / check-in today',
            ),
            'attendance_today_report' => array(
                'modules_any' => array('attendance'),
                'label' => 'Attendance report',
                'message' => 'Show all users attendance for this month',
                'hint' => 'Team/all users attendance report — use the date range stated in the question (today / this month / last week)',
            ),
            'who_late_today' => array(
                'modules_any' => array('attendance'),
                'label' => 'Late today',
                'message' => 'Who is late today?',
                'hint' => 'Who checked in late today',
            ),
            'my_open_tasks' => array(
                'modules_any' => array('tasks'),
                'label' => 'Open tasks',
                'message' => 'Show my open tasks',
                'hint' => 'Own open / pending tasks',
            ),
            'my_daily_activity_today' => array(
                'modules_any' => array('daily_activity', 'my_works'),
                'label' => 'Daily activity',
                'message' => 'My daily activity today',
                'hint' => 'Own daily activity / work log today',
            ),
            'spl_pending_approvals' => array(
                'modules_any' => array('spl_approve', 'rewards_approve', 'rewards_admin', 'spl'),
                'label' => 'Pending SPL',
                'message' => 'Pending SPL approvals',
                'hint' => 'Pending SPL / reward approvals',
            ),
            'my_spl_points' => array(
                'modules_any' => array('spl', 'rewards'),
                'label' => 'My SPL points',
                'message' => 'My SPL points',
                'hint' => 'Own SPL / reward points',
            ),
        );
    }
}

if (!function_exists('ai_chat_should_enforce_tool_rbac')) {
    /**
     * Skip RBAC gate in CLI tests (no session). Web always enforces.
     *
     * @return bool
     */
    function ai_chat_should_enforce_tool_rbac()
    {
        if (function_exists('is_cli') && is_cli()) {
            return false;
        }
        $CI =& get_instance();
        if (!$CI || empty($CI->session)) {
            return false;
        }
        return (int) $CI->session->userdata('role_id') > 0;
    }
}

if (!function_exists('ai_chat_user_can_use_tool')) {
    /**
     * @param string $tool
     * @return bool
     */
    function ai_chat_user_can_use_tool($tool)
    {
        $catalog = ai_chat_tool_catalog();
        if (!isset($catalog[$tool])) {
            return false;
        }
        if (!ai_chat_should_enforce_tool_rbac()) {
            return true;
        }
        $CI =& get_instance();
        if ((int) $CI->session->userdata('role_id') === 1) {
            return true;
        }
        if (!function_exists('has_module_access')) {
            $CI->load->helper('permission');
        }
        $mods = isset($catalog[$tool]['modules_any']) ? $catalog[$tool]['modules_any'] : array();
        foreach ($mods as $mod) {
            if (has_module_access($mod)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('ai_chat_allowed_tool_keys')) {
    /**
     * @return string[]
     */
    function ai_chat_allowed_tool_keys()
    {
        $keys = array();
        foreach (array_keys(ai_chat_tool_catalog()) as $tool) {
            if (ai_chat_user_can_use_tool($tool)) {
                $keys[] = $tool;
            }
        }
        return $keys;
    }
}

if (!function_exists('ai_chat_allowed_modules_list')) {
    /**
     * @return string[]
     */
    function ai_chat_allowed_modules_list()
    {
        $mods = array();
        if (function_exists('get_accessible_modules')) {
            $mods = get_accessible_modules();
        }
        if (!is_array($mods)) {
            $mods = array();
        }
        $CI =& get_instance();
        if ($CI && (int) $CI->session->userdata('role_id') === 1) {
            $mods = array_values(array_unique(array_merge($mods, array_values(ai_chat_table_module_map()))));
        }
        sort($mods);
        return $mods;
    }
}

if (!function_exists('ai_chat_setting_text')) {
    /**
     * Optional admin override from settings table (empty = use built-in dynamic builder).
     *
     * @param string $key
     * @return string
     */
    function ai_chat_setting_text($key)
    {
        $CI =& get_instance();
        if (!$CI || empty($CI->settings) || !method_exists($CI->settings, 'get_setting')) {
            return '';
        }
        $v = $CI->settings->get_setting($key, '');
        return is_string($v) ? trim($v) : '';
    }
}

if (!function_exists('ai_chat_build_process_system_prompt')) {
    /**
     * Dynamic system prompt from schema + RBAC + optional settings override.
     *
     * @param string     $schema_context
     * @param array|null $user_info
     * @param array      $conversation_history
     * @return string
     */
    function ai_chat_build_process_system_prompt($schema_context, $user_info = null, $conversation_history = array())
    {
        $override = ai_chat_setting_text('ai_chat_system_prompt');
        $user_id = ($user_info && isset($user_info['id'])) ? (int) $user_info['id'] : 0;
        $user_name = ($user_info && !empty($user_info['name'])) ? (string) $user_info['name'] : 'User';
        $user_email = ($user_info && !empty($user_info['email'])) ? (string) $user_info['email'] : '';

        $allowed_tools = ai_chat_allowed_tool_keys();
        $catalog = ai_chat_tool_catalog();
        $tool_lines = array();
        foreach ($allowed_tools as $key) {
            $hint = isset($catalog[$key]['hint']) ? $catalog[$key]['hint'] : $key;
            $tool_lines[] = '- ' . $key . ': ' . $hint;
        }
        $modules = ai_chat_allowed_modules_list();

        $current_date = date('Y-m-d');
        $parts = array();
        if ($override !== '') {
            $parts[] = $override;
        } else {
            $parts[] = 'You are the Office AI Assistant for this company portal.'
                . ' Answer using ONLY the schema and modules the user is allowed to access.'
                . ' The user may ask in any language or mix; reply in the same language/style.'
                . ' Never mention SQL, table names, columns, or technical database details in type=text answers.';
        }

        $parts[] = "Allowed modules for this user: " . (empty($modules) ? '(none beyond AI chat)' : implode(', ', $modules));
        $parts[] = "Allowed quick tools (prefer these intents when matching): "
            . (empty($tool_lines) ? '(none)' : "\n" . implode("\n", $tool_lines));
        $parts[] = "Database schema available to this user:\n" . (string) $schema_context;

        if ($user_id > 0) {
            $uc = "Logged-in user:\n- User ID: {$user_id}\n- Name: {$user_name}";
            if ($user_email !== '') {
                $uc .= "\n- Email: {$user_email}";
            }
            $uc .= "\nWhen the user asks about their own data, filter by this user id on the ownership column.";
            $parts[] = $uc;
        }

        $parts[] = "Current date/time:\n"
            . "- Today: {$current_date}\n"
            . '- Now: ' . date('Y-m-d H:i:s') . "\n"
            . "- yesterday = " . date('Y-m-d', strtotime('-1 day')) . "\n"
            . '- this month = ' . date('Y-m-01') . " to {$current_date}";

        if (!empty($conversation_history) && is_array($conversation_history)) {
            $hist = "Recent conversation:";
            $n = 0;
            foreach (array_slice($conversation_history, -6) as $exchange) {
                if (isset($exchange['user'])) {
                    $hist .= "\nUser: " . $exchange['user'];
                }
                if (isset($exchange['assistant'])) {
                    $a = strip_tags((string) $exchange['assistant']);
                    if (strlen($a) > 200) {
                        $a = substr($a, 0, 200) . '...';
                    }
                    $hist .= "\nAssistant: " . $a;
                }
                $hist .= "\n---";
                $n++;
                if ($n >= 3) {
                    break;
                }
            }
            $parts[] = $hist;
        }

        $parts[] = 'Response format (JSON only, no markdown fences):'
            . "\n- Data request: {\"type\":\"sql_query\",\"query\":\"SELECT ...\"}"
            . "\n- Non-data: {\"type\":\"text\",\"text\":\"...\"}"
            . "\nRules: single SELECT only; use aliases for ambiguous columns; no JOINs unless needed;"
            . ' only query tables listed in schema above; LIMIT results mentally to relevant columns.';

        return implode("\n\n", $parts);
    }
}

if (!function_exists('ai_chat_build_classify_system_prompt')) {
    /**
     * @return string
     */
    function ai_chat_build_classify_system_prompt()
    {
        $override = ai_chat_setting_text('ai_chat_classify_prompt');
        $allowed = ai_chat_allowed_tool_keys();
        $catalog = ai_chat_tool_catalog();
        $lines = array();
        foreach ($allowed as $key) {
            $hint = isset($catalog[$key]['hint']) ? $catalog[$key]['hint'] : $key;
            $sample = isset($catalog[$key]['message']) ? $catalog[$key]['message'] : '';
            $lines[] = '- ' . $key . ': ' . $hint . ($sample !== '' ? ' (e.g. "' . $sample . '")' : '');
        }
        $tool_list = empty($allowed) ? 'none' : implode(', ', $allowed) . ', none';

        if ($override !== '') {
            return $override . "\n\nAllowed tool keys for THIS user: {$tool_list}\n"
                . (empty($lines) ? '' : "Hints:\n" . implode("\n", $lines) . "\n")
                . 'Return ONLY JSON: {"tool":"..."}';
        }

        return 'You are an intent classifier for the Office AI Assistant.'
            . ' The user may write in any language or mix.'
            . " Map the message to ONE tool key from: {$tool_list}."
            . "\nOnly use tools listed below (these match the user's permissions):\n"
            . (empty($lines) ? "(no tools — return none)\n" : implode("\n", $lines) . "\n")
            . 'If unrelated or not allowed, use none.'
            . "\nReturn ONLY JSON: {\"tool\":\"...\"}";
    }
}

if (!function_exists('ai_chat_build_summarize_system_prompt')) {
    /**
     * @param string      $user_query
     * @param string      $data_str
     * @param string|null $user_name
     * @return string
     */
    function ai_chat_build_summarize_system_prompt($user_query, $data_str, $user_name = null)
    {
        $override = ai_chat_setting_text('ai_chat_summarize_prompt');
        $name_bit = $user_name ? " The logged-in user's name is {$user_name}." : '';
        if ($override !== '') {
            return $override . "\n\nUser asked: {$user_query}.{$name_bit}\nData JSON:\n{$data_str}";
        }
        return 'You are a helpful office assistant.'
            . " The user asked: {$user_query}.{$name_bit}"
            . "\nData JSON:\n{$data_str}"
            . "\nWrite a clear professional answer (simple HTML: <strong>, <ul>, <li>, <br>)."
            . ' Do NOT mention SQL, databases, queries, tables, or columns.'
            . ' Reply in the SAME language/script style as the user question.';
    }
}

if (!function_exists('ai_chat_user_scoped_tables')) {
    /**
     * Tables that must be filtered to current user for staff/user-group roles.
     *
     * @return array<string,string> table => ownership column
     */
    function ai_chat_user_scoped_tables()
    {
        return array(
            'attendance' => 'user_id',
            'leave_requests' => 'user_id',
            'leave_balances' => 'user_id',
            'tasks' => 'assigned_to',
            'timesheets' => 'user_id',
            'expenses' => 'user_id',
            'notifications' => 'user_id',
            'reminders' => 'user_id',
            'daily_work_logs' => 'user_id',
            'reward_transactions' => 'user_id',
            'training_enrollments' => 'user_id',
        );
    }
}

if (!function_exists('ai_chat_enforce_data_scope_sql')) {
    /**
     * For non-org-wide roles, force ownership filter on known user-scoped tables.
     *
     * @param string $sql
     * @param int    $user_id
     * @return string
     */
    function ai_chat_enforce_data_scope_sql($sql, $user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return $sql;
        }
        if (function_exists('data_scope_sees_all_org_data') && data_scope_sees_all_org_data()) {
            return $sql;
        }

        $scoped = ai_chat_user_scoped_tables();
        $sql_l = strtolower($sql);
        $needs = array();
        foreach ($scoped as $table => $col) {
            if (preg_match('/\b(from|join)\s+`?' . preg_quote($table, '/') . '`?\b/i', $sql)) {
                $needs[$table] = $col;
            }
        }
        if (empty($needs)) {
            return $sql;
        }

        foreach ($needs as $table => $col) {
            $patterns = array(
                '/\b' . preg_quote($col, '/') . '\s*=\s*' . $user_id . '\b/i',
                '/\b`?' . preg_quote($table, '/') . '`?\s*\.\s*`?' . preg_quote($col, '/') . '`?\s*=\s*' . $user_id . '\b/i',
                '/\b[a-z]\s*\.\s*`?' . preg_quote($col, '/') . '`?\s*=\s*' . $user_id . '\b/i',
            );
            $has = false;
            foreach ($patterns as $p) {
                if (preg_match($p, $sql)) {
                    $has = true;
                    break;
                }
            }
            if ($has) {
                continue;
            }
            $clause = '`' . $table . '`.`' . $col . '` = ' . $user_id;
            // Prefer unqualified if single-table FROM without alias complexity
            if (preg_match('/\bFROM\s+`?' . preg_quote($table, '/') . '`?\b(?!\s+[a-z])/i', $sql)
                && !preg_match('/\bJOIN\b/i', $sql)
            ) {
                $clause = '`' . $col . '` = ' . $user_id;
            }
            if (preg_match('/\bWHERE\b/i', $sql)) {
                $sql = preg_replace('/\bWHERE\b/i', 'WHERE (' . $clause . ') AND ', $sql, 1);
            } else {
                $sql = preg_replace('/\bLIMIT\b/i', 'WHERE ' . $clause . ' LIMIT', $sql, 1);
                if (!preg_match('/\bWHERE\b/i', $sql)) {
                    $sql = rtrim($sql, '; ') . ' WHERE ' . $clause;
                }
            }
        }
        return $sql;
    }
}

if (!function_exists('ai_chat_normalize_message')) {
    /**
     * Normalize typos / transliteration noise before tool matching.
     *
     * @param string $message
     * @return string
     */
    function ai_chat_normalize_message($message)
    {
        $m = strtolower(trim((string) $message));
        // Fix glued typos before stripping punctuation (e.g. ";eaes" → leaves)
        $m = str_replace(array(';eaes', ';leaves', ';leave'), array(' leaves', ' leaves', ' leave'), $m);
        $m = str_replace(array(';', ',', '?', '!', '.'), ' ', $m);
        $m = preg_replace('/\s+/', ' ', $m);
        // Common leave typos / Marathi-English mix
        $replacements = array(
            'eaes' => 'leaves',
            'leaes' => 'leaves',
            'laves' => 'leaves',
            'leeve' => 'leave',
            'leeves' => 'leaves',
            'balace' => 'balance',
            'balanes' => 'balances',
            'mazya' => 'my',
            'maja' => 'my',
            'maza' => 'my',
            'majhe' => 'my',
            'majhya' => 'my',
            'maze' => 'my',
            'mere' => 'my',
            'mera' => 'my',
            'meri' => 'my',
            'aahet' => 'are',
            'ahet' => 'are',
            'kitne' => 'how many',
            'kiti' => 'how many',
            'sang' => 'tell',
            'saang' => 'tell',
            'batao' => 'tell',
            'bataiye' => 'tell',
        );
        foreach ($replacements as $from => $to) {
            $m = preg_replace('/\b' . preg_quote($from, '/') . '\b/u', $to, $m);
        }
        return trim($m);
    }
}

if (!function_exists('ai_chat_parse_date_range')) {
    /**
     * Read date range from the user's question (defaults to today).
     *
     * @param string $message
     * @return array{from:string,to:string,label:string,is_today:bool}
     */
    function ai_chat_parse_date_range($message)
    {
        $today = date('Y-m-d');
        $m = strtolower(trim((string) $message));
        if (function_exists('ai_chat_normalize_message')) {
            $m .= ' ' . ai_chat_normalize_message($message);
        }
        $m = preg_replace('/\s+/', ' ', $m);

        // Explicit ranges first (question text wins over default "today")
        if (preg_match('/\b(this\s*month|current\s*month|whole\s*month|full\s*month|entire\s*month|ya\s*mahina|hya\s*mahinyat|या\s*महिन|इस\s*महीन|पूर्ण\s*महिन)\b/iu', $m)
            || (preg_match('/\bmonth\b/i', $m) && preg_match('/\b(this|whole|full|entire|all|complete)\b/i', $m))
        ) {
            return array(
                'from' => date('Y-m-01'),
                'to' => $today,
                'label' => 'this month (' . date('Y-m-01') . ' to ' . $today . ')',
                'is_today' => false,
            );
        }
        if (preg_match('/\b(last\s*month|previous\s*month|gela\s*mahina|मागील\s*महिन|पिछले\s*महीन)\b/iu', $m)) {
            $from = date('Y-m-01', strtotime('first day of last month'));
            $to = date('Y-m-t', strtotime('last day of last month'));
            return array(
                'from' => $from,
                'to' => $to,
                'label' => 'last month (' . $from . ' to ' . $to . ')',
                'is_today' => false,
            );
        }
        if (preg_match('/\b(this\s*week|current\s*week|hya\s*athiavda|या\s*आठवड)\b/iu', $m)) {
            $from = date('Y-m-d', strtotime('monday this week'));
            return array(
                'from' => $from,
                'to' => $today,
                'label' => 'this week (' . $from . ' to ' . $today . ')',
                'is_today' => false,
            );
        }
        if (preg_match('/\b(last\s*7\s*days|past\s*7\s*days|last\s*week)\b/i', $m)) {
            $from = date('Y-m-d', strtotime('-7 days'));
            return array(
                'from' => $from,
                'to' => $today,
                'label' => 'last 7 days (' . $from . ' to ' . $today . ')',
                'is_today' => false,
            );
        }
        if (preg_match('/\b(yesterday|kal|काल|कल)\b/iu', $m)) {
            $y = date('Y-m-d', strtotime('-1 day'));
            return array(
                'from' => $y,
                'to' => $y,
                'label' => 'yesterday (' . $y . ')',
                'is_today' => false,
            );
        }

        return array(
            'from' => $today,
            'to' => $today,
            'label' => 'today (' . $today . ')',
            'is_today' => true,
        );
    }
}

if (!function_exists('ai_chat_apply_date_column_range')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string              $column  e.g. att_date or a.att_date
     * @param string              $from
     * @param string              $to
     * @return void
     */
    function ai_chat_apply_date_column_range($db, $column, $from, $to)
    {
        $from = (string) $from;
        $to = (string) $to;
        if ($from === $to) {
            $db->where($column, $from);
            return;
        }
        $db->where($column . ' >=', $from);
        $db->where($column . ' <=', $to);
    }
}

if (!function_exists('ai_chat_is_date_refinement')) {
    /**
     * Short follow-up that only changes the date window of the previous ask.
     *
     * @param string $message
     * @return bool
     */
    function ai_chat_is_date_refinement($message)
    {
        $m = strtolower(trim((string) $message));
        if ($m === '') {
            return false;
        }
        if (!preg_match('/\b(this\s*month|whole\s*month|full\s*month|last\s*month|this\s*week|yesterday|not\s*only\s*today|only\s*today|ya\s*mahina|पूर्ण\s*महिन|या\s*महिन)\b/iu', $m)
            && !preg_match('/\b(month|mahina|महिन)\b/iu', $m)
        ) {
            return false;
        }
        // New domain (leave/tasks/spl) without attendance → not a date refine
        if (preg_match('/\b(leave|leaves|task|tasks|spl|balance|points)\b/i', $m)
            && !preg_match('/\b(attendance|hajri|hazri|punch|report)\b/i', $m)
        ) {
            return false;
        }
        return true;
    }
}

if (!function_exists('ai_chat_is_meta_reask')) {
    /**
     * User complains bot ignored the question — re-run last ask.
     *
     * @param string $message
     * @return bool
     */
    function ai_chat_is_meta_reask($message)
    {
        $m = strtolower(trim((string) $message));
        return (bool) preg_match(
            '/\b(what\s+i\s+asked|please\s+check\s+first|check\s+first|kay\s+vichar|mi\s+kay|काय\s+विचार|आधी\s+पाहा|question\s+first)\b/iu',
            $m
        );
    }
}

if (!function_exists('ai_chat_match_tool')) {
    /**
     * Match a deterministic tool intent from natural language (any language / mix).
     *
     * @param string $message
     * @return string|null tool key
     */
    function ai_chat_match_tool($message)
    {
        if (!function_exists('ai_chat_score_intent')) {
            $CI =& get_instance();
            if ($CI) {
                $CI->load->helper('ai_chat_intent');
            }
        }

        $raw = strtolower(trim((string) $message));
        $m = function_exists('ai_chat_normalize_message')
            ? ai_chat_normalize_message($message)
            : $raw;
        $range = function_exists('ai_chat_parse_date_range')
            ? ai_chat_parse_date_range($message)
            : array('is_today' => true);

        // Question-first: all-user / team attendance report (any date range) beats "my today"
        $wants_team_attendance = (preg_match('/\b(attendance|hajri|hazri|उपस्थिती|हाजिरी)\b/iu', $message . ' ' . $m)
            && preg_match('/\b(all|team|everyone|users?|report|sagl|sagle|सर्व|रिपोर्ट)\b/iu', $message . ' ' . $m)
            && !preg_match('/\b(my|mine|mazya|mera)\s+(attendance|hajri)\b/i', $m));
        if ($wants_team_attendance) {
            $matched = 'attendance_today_report';
            if (function_exists('ai_chat_user_can_use_tool') && !ai_chat_user_can_use_tool($matched)) {
                return null;
            }
            return $matched;
        }

        $matched = null;
        if (function_exists('ai_chat_score_intent')) {
            $scored = ai_chat_score_intent($message);
            if (!empty($scored['tool'])) {
                $matched = $scored['tool'];
            }
        }

        if ($matched === null && $m !== '') {
            // Legacy English regex fallback (still permission-gated below)
            if (preg_match('/\b(my|mine)\b.*\b(leave\s*balance|available\s*leave|leaves?\s*left|remaining\s*leave|leave\s*balances?)\b/i', $m)
                || preg_match('/\b(leave\s*balance|how many leaves?|leaves?\s*balance)\b.*\b(i|me|my)\b/i', $m)
                || preg_match('/\b(my|mine)\b.*\b(leaves?|leave)\b.*\b(balance|balances|how many|left|remaining)\b/i', $m)
                || preg_match('/\b(my|mine)\b.*\b(balance|balances)\b.*\b(leaves?|leave)\b/i', $m)
                || (preg_match('/\b(balance|balances)\b/i', $m) && preg_match('/\b(leave|leaves)\b/i', $m) && preg_match('/\b(my|mine|tell|how many)\b/i', $m))
            ) {
                $matched = 'my_leave_balance';
            } elseif (preg_match('/\b(who|anyone).*\b(on\s*leave|leave\s*today)|on\s*leave\s*today/i', $m)) {
                $matched = 'who_on_leave_today';
            } elseif (preg_match('/\b(my|mine)\b.*\b(attendance|check[\s-]?in|punch)\b/i', $m)
                || preg_match('/\bdid i (check|punch)\s*in\b/i', $m)
            ) {
                $matched = 'my_attendance_today';
            } elseif (preg_match('/\b(my|mine)\b.*\b(open|pending|active)?\s*tasks?\b/i', $m)
                || preg_match('/\btasks?\s*(assigned\s*to\s*me|for\s*me)\b/i', $m)
            ) {
                $matched = 'my_open_tasks';
            } elseif (preg_match('/\b(my|mine)\b.*\b(daily\s*activity|work\s*log)\b/i', $m)
                || (preg_match('/\bdaily\s*activity\b/i', $m) && preg_match('/\b(my|me|i|today)\b/i', $m))
            ) {
                $matched = 'my_daily_activity_today';
            } elseif (preg_match('/\b(pending|waiting)\b.*\b(spl|reward|approval)s?\b|\bspl\b.*\bpending\b/i', $m)) {
                $matched = 'spl_pending_approvals';
            } elseif (preg_match('/\b(who|anyone).*\blate\b|\blate\s*today\b|\bwho\s+is\s+late\b/i', $m)) {
                $matched = 'who_late_today';
            } elseif (preg_match('/\b(my|mine)\b.*\bpending\b.*\bleaves?\b|\bpending\s+leave\s+requests?\b/i', $m)) {
                $matched = 'my_pending_leaves';
            } elseif (preg_match('/\b(my|mine)\b.*\b(spl\s*)?points\b|\bmy\s+spl\b/i', $m)) {
                $matched = 'my_spl_points';
            }
        }

        // Own attendance for a non-today range → still my_attendance tool (range applied in run)
        if ($matched === null
            && preg_match('/\b(attendance|hajri|hazri)\b/i', $m)
            && preg_match('/\b(my|mine|mazya|mera)\b/i', $m)
            && empty($range['is_today'])
        ) {
            $matched = 'my_attendance_today';
        }

        if ($matched === null) {
            return null;
        }
        if (function_exists('ai_chat_user_can_use_tool') && !ai_chat_user_can_use_tool($matched)) {
            return null;
        }
        return $matched;
    }
}

if (!function_exists('ai_chat_run_tool')) {
    /**
     * Execute a deterministic read-only tool. Returns HTML summary + rows.
     *
     * @param string              $tool
     * @param int                 $user_id
     * @param CI_DB_query_builder $db
     * @param string              $message Original user question (for date range / scope)
     * @return array{ok:bool,html:string,rows?:array,error?:string}
     */
    function ai_chat_run_tool($tool, $user_id, $db, $message = '')
    {
        $user_id = (int) $user_id;
        $today = date('Y-m-d');
        $range = function_exists('ai_chat_parse_date_range')
            ? ai_chat_parse_date_range($message !== '' ? $message : 'today')
            : array('from' => $today, 'to' => $today, 'label' => 'today (' . $today . ')', 'is_today' => true);
        $date_from = $range['from'];
        $date_to = $range['to'];
        $date_label = $range['label'];

        if (function_exists('ai_chat_user_can_use_tool') && !ai_chat_user_can_use_tool($tool)) {
            return array(
                'ok' => false,
                'error' => 'permission',
                'html' => 'You do not have access to that information.',
            );
        }

        switch ($tool) {
            case 'my_leave_balance':
                if (!$db->table_exists('leave_balances')) {
                    return array('ok' => false, 'error' => 'leave_balances table not found', 'html' => '');
                }
                $type_col = $db->field_exists('type_id', 'leave_balances')
                    ? 'type_id'
                    : ($db->field_exists('leave_type_id', 'leave_balances') ? 'leave_type_id' : null);
                $bal_col = $db->field_exists('closing_balance', 'leave_balances')
                    ? 'closing_balance'
                    : ($db->field_exists('remaining_leaves', 'leave_balances')
                        ? 'remaining_leaves'
                        : ($db->field_exists('balance', 'leave_balances') ? 'balance' : null));

                $db->from('leave_balances lb');
                $db->where('lb.user_id', $user_id);
                if ($db->field_exists('year', 'leave_balances')) {
                    $db->where('lb.year', (int) date('Y'));
                }
                if ($type_col && $db->table_exists('leave_types')) {
                    $db->select('lb.*, lt.name AS leave_type_name', false);
                    $db->join('leave_types lt', 'lt.id = lb.' . $type_col, 'left');
                } else {
                    $db->select('lb.*');
                }
                $rows = $db->limit(50)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'No leave balance records found for you.', 'rows' => array());
                }
                $parts = array('<strong>Your leave balances</strong><ul>');
                foreach ($rows as $r) {
                    $type = isset($r['leave_type_name']) ? $r['leave_type_name'] : (isset($r[$type_col]) ? ('Type #' . $r[$type_col]) : 'Leave');
                    $bal = '-';
                    if ($bal_col && isset($r[$bal_col])) {
                        $bal = $r[$bal_col];
                    } elseif (isset($r['opening_balance'], $r['accrued'], $r['used'])) {
                        $bal = (float) $r['opening_balance'] + (float) $r['accrued'] - (float) $r['used'];
                    }
                    $parts[] = '<li>' . htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') . ': <strong>' . htmlspecialchars((string) $bal, ENT_QUOTES, 'UTF-8') . '</strong></li>';
                }
                $parts[] = '</ul>';
                return array('ok' => true, 'html' => implode('', $parts), 'rows' => $rows);

            case 'who_on_leave_today':
                if (!$db->table_exists('leave_requests')) {
                    return array('ok' => false, 'error' => 'leave_requests missing', 'html' => '');
                }
                if (!function_exists('has_module_access') || !has_module_access('leave_requests')) {
                    if ((int) get_instance()->session->userdata('role_id') !== 1) {
                        return array('ok' => false, 'error' => 'No leave module access', 'html' => 'You do not have access to leave data.');
                    }
                }
                $db->select('lr.id, lr.user_id, lr.status, lr.start_date, lr.end_date, u.email', false);
                $db->from('leave_requests lr');
                $db->join('users u', 'u.id = lr.user_id', 'left');
                $db->where('lr.start_date <=', $today);
                $db->where('lr.end_date >=', $today);
                $db->where_in('lr.status', array('approved', 'Approved', 'APPROVED', 'hr_approved', 'lead_approved'));
                if (function_exists('ai_chat_apply_hierarchy_user_ids')) {
                    ai_chat_apply_hierarchy_user_ids($db, 'lr.user_id');
                }
                $rows = $db->limit(100)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'Nobody in your scope is on approved leave today (' . $today . ').', 'rows' => array());
                }
                $html = '<strong>On leave today (' . $today . ')</strong><ul>';
                foreach ($rows as $r) {
                    $label = !empty($r['email']) ? $r['email'] : ('User #' . $r['user_id']);
                    $link = function_exists('ai_chat_deep_link') ? ai_chat_deep_link('leave', (int) $r['id']) : '';
                    $html .= '<li>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8')
                        . ' (' . htmlspecialchars((string) $r['start_date'], ENT_QUOTES, 'UTF-8')
                        . ' → ' . htmlspecialchars((string) $r['end_date'], ENT_QUOTES, 'UTF-8') . ')'
                        . $link . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'my_attendance_today':
                if (!$db->table_exists('attendance')) {
                    return array('ok' => false, 'error' => 'attendance missing', 'html' => '');
                }
                $db->from('attendance');
                $db->where('user_id', $user_id);
                if ($db->field_exists('att_date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'att_date', $date_from, $date_to);
                } elseif ($db->field_exists('attendance_date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'attendance_date', $date_from, $date_to);
                } elseif ($db->field_exists('date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'date', $date_from, $date_to);
                } elseif ($db->field_exists('punch_in', 'attendance')) {
                    $db->where('DATE(punch_in) >= ' . $db->escape($date_from), null, false);
                    $db->where('DATE(punch_in) <= ' . $db->escape($date_to), null, false);
                }
                $limit = !empty($range['is_today']) ? 10 : 100;
                $rows = $db->order_by('id', 'DESC')->limit($limit)->get()->result_array();
                if (empty($rows)) {
                    return array(
                        'ok' => true,
                        'html' => 'No attendance record for you for ' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '.',
                        'rows' => array(),
                    );
                }
                if (!empty($range['is_today']) && count($rows) === 1) {
                    $r = $rows[0];
                    $in = isset($r['punch_in']) ? $r['punch_in'] : (isset($r['check_in']) ? $r['check_in'] : '-');
                    $out = isset($r['punch_out']) ? $r['punch_out'] : (isset($r['check_out']) ? $r['check_out'] : '-');
                    $st = isset($r['status']) ? $r['status'] : '-';
                    $html = '<strong>Your attendance for ' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</strong><br>Check-in: '
                        . htmlspecialchars((string) $in, ENT_QUOTES, 'UTF-8')
                        . '<br>Check-out: ' . htmlspecialchars((string) $out, ENT_QUOTES, 'UTF-8')
                        . '<br>Status: ' . htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8');
                    return array('ok' => true, 'html' => $html, 'rows' => $rows);
                }
                $html = '<strong>Your attendance for ' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</strong><ul>';
                foreach ($rows as $r) {
                    $d = isset($r['att_date']) ? $r['att_date'] : (isset($r['attendance_date']) ? $r['attendance_date'] : (isset($r['date']) ? $r['date'] : ''));
                    $in = isset($r['punch_in']) ? $r['punch_in'] : (isset($r['check_in']) ? $r['check_in'] : '-');
                    $out = isset($r['punch_out']) ? $r['punch_out'] : (isset($r['check_out']) ? $r['check_out'] : '-');
                    $st = isset($r['status']) ? $r['status'] : '-';
                    $html .= '<li>' . htmlspecialchars((string) $d, ENT_QUOTES, 'UTF-8')
                        . ' — In: ' . htmlspecialchars((string) $in, ENT_QUOTES, 'UTF-8')
                        . ', Out: ' . htmlspecialchars((string) $out, ENT_QUOTES, 'UTF-8')
                        . ', Status: ' . htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'attendance_today_report':
                if (!$db->table_exists('attendance')) {
                    return array('ok' => false, 'error' => 'attendance missing', 'html' => '');
                }
                if (!function_exists('has_module_access') || !has_module_access('attendance')) {
                    if ((int) get_instance()->session->userdata('role_id') !== 1) {
                        return array(
                            'ok' => false,
                            'error' => 'No attendance access',
                            'html' => 'You do not have access to attendance reports.',
                        );
                    }
                }
                $db->select('a.*, u.email, u.name AS user_name', false);
                $db->from('attendance a');
                $db->join('users u', 'u.id = a.user_id', 'left');
                if ($db->field_exists('att_date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'a.att_date', $date_from, $date_to);
                } elseif ($db->field_exists('attendance_date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'a.attendance_date', $date_from, $date_to);
                } elseif ($db->field_exists('date', 'attendance')) {
                    ai_chat_apply_date_column_range($db, 'a.date', $date_from, $date_to);
                } elseif ($db->field_exists('punch_in', 'attendance')) {
                    $db->where('DATE(a.punch_in) >= ' . $db->escape($date_from), null, false);
                    $db->where('DATE(a.punch_in) <= ' . $db->escape($date_to), null, false);
                }
                if (function_exists('ai_chat_apply_hierarchy_user_ids')) {
                    ai_chat_apply_hierarchy_user_ids($db, 'a.user_id');
                }
                $limit = !empty($range['is_today']) ? 100 : 300;
                $order_col = $db->field_exists('att_date', 'attendance') ? 'a.att_date' : 'a.id';
                $rows = $db->order_by('u.name', 'ASC')->order_by($order_col, 'DESC')->limit($limit)->get()->result_array();
                if (empty($rows)) {
                    return array(
                        'ok' => true,
                        'html' => 'No attendance records found for ' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . ' in your scope.',
                        'rows' => array(),
                    );
                }
                $html = '<strong>Attendance for ' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</strong>'
                    . ' <em>(' . count($rows) . ' rows)</em><ul>';
                foreach ($rows as $r) {
                    $label = !empty($r['user_name']) ? $r['user_name'] : (!empty($r['email']) ? $r['email'] : ('User #' . $r['user_id']));
                    $d = isset($r['att_date']) ? $r['att_date'] : (isset($r['attendance_date']) ? $r['attendance_date'] : (isset($r['date']) ? $r['date'] : ''));
                    $in = isset($r['punch_in']) ? $r['punch_in'] : (isset($r['check_in']) ? $r['check_in'] : '-');
                    $out = isset($r['punch_out']) ? $r['punch_out'] : (isset($r['check_out']) ? $r['check_out'] : '-');
                    $st = isset($r['status']) ? $r['status'] : '-';
                    $html .= '<li><strong>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</strong>'
                        . ($d !== '' ? ' (' . htmlspecialchars((string) $d, ENT_QUOTES, 'UTF-8') . ')' : '')
                        . ' — In: ' . htmlspecialchars((string) $in, ENT_QUOTES, 'UTF-8')
                        . ', Out: ' . htmlspecialchars((string) $out, ENT_QUOTES, 'UTF-8')
                        . ', Status: ' . htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8')
                        . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'my_open_tasks':
                if (!$db->table_exists('tasks')) {
                    return array('ok' => false, 'error' => 'tasks missing', 'html' => '');
                }
                $db->from('tasks');
                if ($db->field_exists('assigned_to', 'tasks')) {
                    $db->where('assigned_to', $user_id);
                } elseif ($db->field_exists('assignee_id', 'tasks')) {
                    $db->where('assignee_id', $user_id);
                } elseif ($db->field_exists('user_id', 'tasks')) {
                    $db->where('user_id', $user_id);
                }
                if ($db->field_exists('status', 'tasks')) {
                    $db->where_not_in('status', array('completed', 'Completed', 'done', 'Done', 'closed', 'Closed'));
                }
                $rows = $db->order_by('id', 'DESC')->limit(20)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'You have no open tasks.', 'rows' => array());
                }
                $html = '<strong>Your open tasks</strong><ul>';
                foreach ($rows as $r) {
                    $title = isset($r['title']) ? $r['title'] : (isset($r['name']) ? $r['name'] : ('Task #' . $r['id']));
                    $st = isset($r['status']) ? $r['status'] : '';
                    $link = function_exists('ai_chat_deep_link') ? ai_chat_deep_link('tasks', (int) $r['id']) : '';
                    $html .= '<li>' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8')
                        . ($st !== '' ? ' <em>(' . htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8') . ')</em>' : '')
                        . $link . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'who_late_today':
                if (!$db->table_exists('attendance')) {
                    return array('ok' => false, 'error' => 'attendance missing', 'html' => '');
                }
                $CI =& get_instance();
                if (!isset($CI->settings)) {
                    $CI->load->model('Setting_model', 'settings');
                }
                $start = trim((string) $CI->settings->get_setting('attendance_start_time', '09:30'));
                $db->select('a.*, u.email', false);
                $db->from('attendance a');
                $db->join('users u', 'u.id = a.user_id', 'left');
                if ($db->field_exists('attendance_date', 'attendance')) {
                    $db->where('a.attendance_date', $today);
                } elseif ($db->field_exists('date', 'attendance')) {
                    $db->where('a.date', $today);
                } elseif ($db->field_exists('punch_in', 'attendance')) {
                    $db->where('DATE(a.punch_in) = ' . $db->escape($today), null, false);
                }
                if ($db->field_exists('status', 'attendance')) {
                    $db->group_start();
                    $db->like('a.status', 'late');
                    $db->or_like('a.status', 'Late');
                    $db->group_end();
                } elseif ($db->field_exists('punch_in', 'attendance')) {
                    $db->where('TIME(a.punch_in) >', substr($start, 0, 5) . ':00');
                }
                if (function_exists('ai_chat_apply_hierarchy_user_ids')) {
                    ai_chat_apply_hierarchy_user_ids($db, 'a.user_id');
                }
                $rows = $db->limit(100)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'No late check-ins found in your scope for today.', 'rows' => array());
                }
                $html = '<strong>Late today (' . $today . ')</strong><ul>';
                foreach ($rows as $r) {
                    $label = !empty($r['email']) ? $r['email'] : ('User #' . $r['user_id']);
                    $in = isset($r['punch_in']) ? $r['punch_in'] : '-';
                    $html .= '<li>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8')
                        . ' — ' . htmlspecialchars((string) $in, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'my_pending_leaves':
                if (!$db->table_exists('leave_requests')) {
                    return array('ok' => false, 'error' => 'leave_requests missing', 'html' => '');
                }
                $db->from('leave_requests');
                $db->where('user_id', $user_id);
                $db->where_in('status', array('pending', 'Pending', 'lead_approved'));
                $rows = $db->order_by('id', 'DESC')->limit(20)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'You have no pending leave requests.', 'rows' => array());
                }
                $html = '<strong>Your pending leave requests</strong><ul>';
                foreach ($rows as $r) {
                    $link = function_exists('ai_chat_deep_link') ? ai_chat_deep_link('leave', (int) $r['id']) : '';
                    $html .= '<li>#' . (int) $r['id'] . ' '
                        . htmlspecialchars((string) $r['start_date'], ENT_QUOTES, 'UTF-8') . ' → '
                        . htmlspecialchars((string) $r['end_date'], ENT_QUOTES, 'UTF-8')
                        . ' <em>(' . htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') . ')</em>'
                        . $link . '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'my_spl_points':
                if (!$db->table_exists('reward_transactions')) {
                    return array('ok' => false, 'error' => 'reward_transactions missing', 'html' => 'SPL points table not available.');
                }
                $points_col = $db->field_exists('points', 'reward_transactions')
                    ? 'points'
                    : ($db->field_exists('amount', 'reward_transactions') ? 'amount' : null);
                $db->from('reward_transactions');
                $db->where('user_id', $user_id);
                if ($db->field_exists('status', 'reward_transactions')) {
                    $db->where_in('status', array('approved', 'Approved', 'credited', 'completed'));
                }
                if ($points_col) {
                    $db->select_sum($points_col, 'total_points');
                    $row = $db->get()->row_array();
                    $total = isset($row['total_points']) ? $row['total_points'] : 0;
                    $html = '<strong>Your SPL points:</strong> ' . htmlspecialchars((string) $total, ENT_QUOTES, 'UTF-8');
                    $html .= function_exists('ai_chat_deep_link') ? ai_chat_deep_link('spl', 1) : '';
                    return array('ok' => true, 'html' => $html, 'rows' => array(array('total_points' => $total)));
                }
                $n = (int) $db->count_all_results();
                return array('ok' => true, 'html' => '<strong>Your SPL transactions:</strong> ' . $n, 'rows' => array(array('tx_count' => $n)));

            case 'my_daily_activity_today':
                if (!$db->table_exists('daily_work_logs')) {
                    return array('ok' => false, 'error' => 'daily_work_logs missing', 'html' => '');
                }
                $db->from('daily_work_logs');
                $db->where('user_id', $user_id);
                $db->where('work_date', $today);
                $rows = $db->order_by('id', 'DESC')->limit(10)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'No daily activity logged for today.', 'rows' => array());
                }
                $html = '<strong>Your daily activity today</strong><ul>';
                foreach ($rows as $r) {
                    $t = isset($r['activity_title']) ? $r['activity_title'] : (isset($r['title']) ? $r['title'] : 'Activity');
                    $html .= '<li><strong>' . htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8') . '</strong>';
                    if (!empty($r['description'])) {
                        $html .= '<br><small>' . htmlspecialchars(mb_substr((string) $r['description'], 0, 200), ENT_QUOTES, 'UTF-8') . '</small>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
                return array('ok' => true, 'html' => $html, 'rows' => $rows);

            case 'spl_pending_approvals':
                if (!$db->table_exists('reward_approval_queue')) {
                    return array('ok' => false, 'error' => 'reward_approval_queue missing', 'html' => '');
                }
                $CI =& get_instance();
                $role_id = (int) $CI->session->userdata('role_id');
                $can_approve = ($role_id === 1);
                if (!$can_approve && function_exists('has_module_access')) {
                    $can_approve = has_module_access('spl_approve')
                        || has_module_access('rewards_approve')
                        || has_module_access('rewards_admin');
                }
                if (!$can_approve) {
                    return array(
                        'ok' => false,
                        'error' => 'No SPL approve permission',
                        'html' => 'You do not have permission to view the SPL approval queue.',
                    );
                }
                $db->from('reward_approval_queue');
                if ($db->field_exists('status', 'reward_approval_queue')) {
                    $db->where('status', 'pending');
                }
                // Scope to hierarchy when submitter/user column exists
                $scope_col = null;
                foreach (array('user_id', 'submitted_by', 'employee_id', 'created_by') as $cand) {
                    if ($db->field_exists($cand, 'reward_approval_queue')) {
                        $scope_col = $cand;
                        break;
                    }
                }
                if ($scope_col && function_exists('ai_chat_apply_hierarchy_user_ids')) {
                    ai_chat_apply_hierarchy_user_ids($db, $scope_col);
                }
                $n = (int) $db->count_all_results();
                $html = '<strong>SPL pending approvals (your scope):</strong> ' . $n;
                return array('ok' => true, 'html' => $html, 'rows' => array(array('pending_count' => $n)));

            default:
                return array('ok' => false, 'error' => 'Unknown tool', 'html' => '');
        }
    }
}

if (!function_exists('ai_chat_append_export_buttons')) {
    /**
     * @param string $html
     * @param array  $rows
     * @param string $query_label
     * @return string
     */
    function ai_chat_append_export_buttons($html, array $rows, $query_label)
    {
        if (empty($rows) || isset($rows['error'])) {
            return $html;
        }
        $export_data = base64_encode(json_encode($rows));
        $export_query = htmlspecialchars((string) $query_label, ENT_QUOTES, 'UTF-8');
        $html .= '<br><br><small class="ai-export-opts">Export: ';
        foreach (array('csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF') as $fmt => $label) {
            $cls = $fmt === 'csv' ? 'outline-primary' : ($fmt === 'excel' ? 'outline-success' : 'outline-danger');
            $icon = $fmt === 'pdf' ? 'bi-file-earmark-pdf' : ($fmt === 'excel' ? 'bi-file-earmark-excel' : 'bi-file-earmark-spreadsheet');
            $html .= '<button type="button" class="btn btn-sm btn-' . $cls . ' me-1 export-btn"'
                . ' data-export-data="' . htmlspecialchars($export_data, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-export-query="' . $export_query . '"'
                . ' data-export-format="' . $fmt . '"><i class="bi ' . $icon . '"></i> ' . $label . '</button> ';
        }
        $html .= '</small>';
        return $html;
    }
}
