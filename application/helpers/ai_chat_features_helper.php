<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Chat advanced features: table map, deterministic tools, data-scope SQL enforce.
 */

if (!function_exists('ai_chat_table_module_map')) {
    /**
     * @return array<string,string>
     */
    function ai_chat_table_module_map()
    {
        return array(
            'users' => 'users',
            'employees' => 'employees',
            'attendance' => 'attendance',
            'leave_requests' => 'leave_requests',
            'leave_types' => 'leave_requests',
            'leave_approvals' => 'leave_requests',
            'leave_balances' => 'leave_balances',
            'holidays' => 'leave_requests',
            'projects' => 'projects',
            'project_members' => 'projects',
            'tasks' => 'tasks',
            'requirements' => 'requirements',
            'timesheets' => 'timesheets',
            'departments' => 'departments',
            'designations' => 'designations',
            'clients' => 'clients',
            'roles' => 'permissions',
            'activity_log' => 'activity',
            'notifications' => 'notifications',
            'expenses' => 'expenses',
            'payroll' => 'payroll',
            'chats' => 'chats',
            'announcements' => 'announcements',
            'reminders' => 'reminders',
            'assets' => 'assets',
            'daily_work_logs' => 'daily_activity',
            'reward_approval_queue' => 'spl',
            'reward_transactions' => 'spl',
            'reward_rules' => 'spl',
            'reward_categories' => 'spl',
            'coaching_clients' => 'coaching_clients',
            'coaching_sessions' => 'coaching_sessions',
            'coaching_coaches' => 'coaching_coaches',
            'coaching_leads' => 'coaching_leads',
            'training_courses' => 'training_lms',
            'training_enrollments' => 'training_lms',
            'training_modules' => 'training_lms',
        );
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
     * @return array<string,string>
     */
    function ai_chat_table_descriptions()
    {
        return array(
            'users' => 'System users and login. Columns include id, email, role_id, status.',
            'employees' => 'Employee profiles linked to users, department, designation.',
            'attendance' => 'Daily punch_in/punch_out and attendance status.',
            'leave_requests' => 'Leave applications and approval status.',
            'leave_types' => 'Leave type master (Sick, Casual, etc.).',
            'leave_approvals' => 'Leave approval/rejection records.',
            'leave_balances' => 'Remaining leave quota per user/type.',
            'holidays' => 'Public holidays and non-working days.',
            'projects' => 'Projects, status, dates, manager.',
            'project_members' => 'Users assigned to projects.',
            'tasks' => 'Tasks with assignee, status, priority, due dates.',
            'requirements' => 'Project requirements and ownership.',
            'timesheets' => 'Hours logged on tasks/projects.',
            'departments' => 'Company departments.',
            'designations' => 'Job titles.',
            'clients' => 'Client companies.',
            'roles' => 'RBAC roles.',
            'activity_log' => 'System activity audit trail.',
            'notifications' => 'In-app notifications.',
            'expenses' => 'Expense claims.',
            'payroll' => 'Payroll / salary records.',
            'chats' => 'Internal chat metadata (prefer conversations/messages elsewhere).',
            'announcements' => 'Company announcements.',
            'reminders' => 'Scheduled reminders (including attendance Google alerts).',
            'assets' => 'Company assets assigned to employees.',
            'daily_work_logs' => 'Daily activity logs (title, description, work_date, user_id).',
            'reward_approval_queue' => 'SPL / rewards pending approvals.',
            'reward_transactions' => 'SPL point transactions.',
            'reward_rules' => 'SPL reward rules configuration.',
            'reward_categories' => 'SPL categories.',
            'coaching_clients' => 'Coaching CRM clients.',
            'coaching_sessions' => 'Coaching sessions schedule/status.',
            'coaching_coaches' => 'Coaches.',
            'coaching_leads' => 'Coaching leads pipeline.',
            'training_courses' => 'LMS courses.',
            'training_enrollments' => 'Course enrollments per user.',
            'training_modules' => 'LMS course modules.',
        );
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
        if (function_exists('ai_chat_score_intent')) {
            $scored = ai_chat_score_intent($message);
            if (!empty($scored['tool'])) {
                return $scored['tool'];
            }
        }

        $raw = strtolower(trim((string) $message));
        $m = function_exists('ai_chat_normalize_message')
            ? ai_chat_normalize_message($message)
            : $raw;
        if ($m === '') {
            return null;
        }

        // Legacy English regex fallback
        if (preg_match('/\b(my|mine)\b.*\b(leave\s*balance|available\s*leave|leaves?\s*left|remaining\s*leave|leave\s*balances?)\b/i', $m)
            || preg_match('/\b(leave\s*balance|how many leaves?|leaves?\s*balance)\b.*\b(i|me|my)\b/i', $m)
            || preg_match('/\b(my|mine)\b.*\b(leaves?|leave)\b.*\b(balance|balances|how many|left|remaining)\b/i', $m)
            || preg_match('/\b(my|mine)\b.*\b(balance|balances)\b.*\b(leaves?|leave)\b/i', $m)
            || (preg_match('/\b(balance|balances)\b/i', $m) && preg_match('/\b(leave|leaves)\b/i', $m) && preg_match('/\b(my|mine|tell|how many)\b/i', $m))
        ) {
            return 'my_leave_balance';
        }
        if (preg_match('/\b(who|anyone).*\b(on\s*leave|leave\s*today)|on\s*leave\s*today/i', $m)) {
            return 'who_on_leave_today';
        }
        if (preg_match('/\b(my|mine)\b.*\b(attendance|check[\s-]?in|punch)\b/i', $m)
            || preg_match('/\bdid i (check|punch)\s*in\b/i', $m)
        ) {
            return 'my_attendance_today';
        }
        if (preg_match('/\b(my|mine)\b.*\b(open|pending|active)?\s*tasks?\b/i', $m)
            || preg_match('/\btasks?\s*(assigned\s*to\s*me|for\s*me)\b/i', $m)
        ) {
            return 'my_open_tasks';
        }
        if (preg_match('/\b(my|mine)\b.*\b(daily\s*activity|work\s*log)\b/i', $m)
            || (preg_match('/\bdaily\s*activity\b/i', $m) && preg_match('/\b(my|me|i|today)\b/i', $m))
        ) {
            return 'my_daily_activity_today';
        }
        if (preg_match('/\b(pending|waiting)\b.*\b(spl|reward|approval)s?\b|\bspl\b.*\bpending\b/i', $m)) {
            return 'spl_pending_approvals';
        }
        if (preg_match('/\b(who|anyone).*\blate\b|\blate\s*today\b|\bwho\s+is\s+late\b/i', $m)) {
            return 'who_late_today';
        }
        if (preg_match('/\b(my|mine)\b.*\bpending\b.*\bleaves?\b|\bpending\s+leave\s+requests?\b/i', $m)) {
            return 'my_pending_leaves';
        }
        if (preg_match('/\b(my|mine)\b.*\b(spl\s*)?points\b|\bmy\s+spl\b/i', $m)) {
            return 'my_spl_points';
        }
        return null;
    }
}

if (!function_exists('ai_chat_run_tool')) {
    /**
     * Execute a deterministic read-only tool. Returns HTML summary + rows.
     *
     * @param string              $tool
     * @param int                 $user_id
     * @param CI_DB_query_builder $db
     * @return array{ok:bool,html:string,rows?:array,error?:string}
     */
    function ai_chat_run_tool($tool, $user_id, $db)
    {
        $user_id = (int) $user_id;
        $today = date('Y-m-d');

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
                if ($db->field_exists('attendance_date', 'attendance')) {
                    $db->where('attendance_date', $today);
                } elseif ($db->field_exists('date', 'attendance')) {
                    $db->where('date', $today);
                } elseif ($db->field_exists('punch_in', 'attendance')) {
                    $db->where('DATE(punch_in) = ' . $db->escape($today), null, false);
                }
                $rows = $db->limit(10)->get()->result_array();
                if (empty($rows)) {
                    return array('ok' => true, 'html' => 'No attendance record for you today (' . $today . ').', 'rows' => array());
                }
                $r = $rows[0];
                $in = isset($r['punch_in']) ? $r['punch_in'] : (isset($r['check_in']) ? $r['check_in'] : '-');
                $out = isset($r['punch_out']) ? $r['punch_out'] : (isset($r['check_out']) ? $r['check_out'] : '-');
                $st = isset($r['status']) ? $r['status'] : '-';
                $html = '<strong>Your attendance today</strong><br>Check-in: '
                    . htmlspecialchars((string) $in, ENT_QUOTES, 'UTF-8')
                    . '<br>Check-out: ' . htmlspecialchars((string) $out, ENT_QUOTES, 'UTF-8')
                    . '<br>Status: ' . htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8');
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
                if (!function_exists('has_module_access') || !(has_module_access('spl') || has_module_access('rewards'))) {
                    if ((int) get_instance()->session->userdata('role_id') !== 1) {
                        return array('ok' => false, 'error' => 'No SPL access', 'html' => 'You do not have SPL access.');
                    }
                }
                $db->from('reward_approval_queue');
                if ($db->field_exists('status', 'reward_approval_queue')) {
                    $db->where('status', 'pending');
                }
                $n = (int) $db->count_all_results();
                $html = '<strong>SPL pending approvals:</strong> ' . $n;
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
