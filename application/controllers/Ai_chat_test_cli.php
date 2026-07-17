<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI functional tests for AI Chat advanced features (no live LLM required).
 * Run: php index.php ai_chat_test_cli run
 */
class Ai_chat_test_cli extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
            return;
        }
        $this->load->database();
        $this->load->helper(array('ai_chat_features', 'ai_chat_intent', 'ai_chat_ops', 'ai_sql_guard'));
        $this->load->model('Ai_conversation_model', 'ai_conv');
    }

    public function eval()
    {
        $pass = 0;
        $fail = 0;
        echo "AI Chat deep eval suite\n";
        $cases = ai_chat_eval_cases();
        foreach ($cases as $i => $case) {
            $got = ai_chat_match_tool($case['q']);
            $want = $case['tool'];
            // follow-up phrases are not tools
            if ($want === null && ai_chat_is_followup($case['q'])) {
                $ok = ($got === null);
            } else {
                $ok = ($got === $want);
            }
            if ($ok) {
                $pass++;
                echo '[PASS] #' . ($i + 1) . ' => ' . var_export($got, true) . "\n";
            } else {
                $fail++;
                echo '[FAIL] #' . ($i + 1) . ' q=' . $case['q'] . ' got=' . var_export($got, true) . ' want=' . var_export($want, true) . "\n";
            }
        }
        echo "EVAL: $pass PASS / $fail FAIL / total " . count($cases) . "\n";
        exit($fail > 0 ? 1 : 0);
    }

    public function run()
    {
        $pass = 0;
        $fail = 0;
        $tag = 'AI_CHAT_TEST_' . date('YmdHis');
        $cleanup = array();

        $ok = function ($cond, $msg) use (&$pass, &$fail) {
            if ($cond) {
                $pass++;
                echo "[PASS] $msg\n";
                return true;
            }
            $fail++;
            echo "[FAIL] $msg\n";
            return false;
        };

        $section = function ($title) {
            echo "\n=== $title ===\n";
        };

        echo "AI Chat advanced features test ($tag)\n";

        $section('1. Tool intent matching');
        $ok(ai_chat_match_tool('What is my leave balance?') === 'my_leave_balance', 'match my_leave_balance');
        $ok(ai_chat_match_tool('mazya balance ;eaes kiti aahet sang') === 'my_leave_balance', 'match Marathi leave balance typo');
        $ok(ai_chat_match_tool('मेरी छुट्टी कितनी बची है') === 'my_leave_balance', 'match Hindi Devanagari leave');
        $ok(ai_chat_match_tool('माझा रजा बॅलन्स किती') === 'my_leave_balance', 'match Marathi Devanagari leave');
        $ok(ai_chat_match_tool('aaj kon leave var aahe') === 'who_on_leave_today', 'match Marathi who on leave');
        $ok(ai_chat_match_tool('mazi hajri aaj') === 'my_attendance_today', 'match Marathi attendance');
        $ok(ai_chat_match_tool('Who is on leave today?') === 'who_on_leave_today', 'match who_on_leave_today');
        $ok(ai_chat_match_tool('Show my open tasks') === 'my_open_tasks', 'match my_open_tasks');
        $ok(ai_chat_match_tool('my attendance today') === 'my_attendance_today', 'match my_attendance_today');
        $ok(ai_chat_match_tool('pending SPL approvals') === 'spl_pending_approvals', 'match spl_pending_approvals');
        $ok(ai_chat_match_tool('hello world') === null, 'non-tool returns null');
        $this->load->helper('ai_chat_intent');
        $scored = ai_chat_score_intent('mazya leave kiti aahet');
        $ok(!empty($scored['tool']) && $scored['tool'] === 'my_leave_balance', 'score intent leave balance');
        $ok(ai_chat_needs_localization('mazya leave kiti') === true, 'needs localization for Marathi mix');
        $ok(ai_chat_needs_localization('What is my leave balance?') === false, 'no localization for plain English');

        $section('2. Schema whitelist expansion');
        $wl = ai_chat_schema_whitelist();
        $ok(in_array('daily_work_logs', $wl, true), 'whitelist has daily_work_logs');
        $ok(in_array('reward_approval_queue', $wl, true), 'whitelist has reward_approval_queue');
        $ok(in_array('coaching_sessions', $wl, true), 'whitelist has coaching_sessions');
        $ok(in_array('training_courses', $wl, true), 'whitelist has training_courses');
        $map = ai_chat_table_module_map();
        $ok(isset($map['daily_work_logs']) && $map['daily_work_logs'] === 'daily_activity', 'module map daily_activity');

        $section('3. Data-scope SQL enforce');
        $scoped = ai_chat_enforce_data_scope_sql('SELECT * FROM attendance LIMIT 10', 42);
        // Without session admin bypass, helper still injects when data_scope_sees_all is false
        $ok(strpos($scoped, '42') !== false || strpos($scoped, 'user_id') !== false, 'scope injects user_id for attendance');
        $already = ai_chat_enforce_data_scope_sql('SELECT * FROM attendance WHERE user_id = 42 LIMIT 10', 42);
        $ok(substr_count(strtolower($already), 'user_id') >= 1, 'already-scoped SQL kept');

        $section('4. SQL guard deny-list');
        $blocked = ai_sql_guard_check('SELECT api_key FROM settings', array('settings'));
        $ok($blocked !== null, 'settings table blocked');
        $blocked2 = ai_sql_guard_check('SELECT password FROM users', array('users'));
        $ok($blocked2 !== null, 'password column blocked');

        $section('5. Persistent conversation model');
        $user = $this->db->select('id')->from('users')->order_by('id', 'ASC')->limit(1)->get()->row();
        $uid = $user ? (int) $user->id : 0;
        $ok($uid > 0, "picked user_id=$uid");
        if ($uid > 0) {
            $conv = $this->ai_conv->start_new($uid);
            $ok($conv && (int) $conv->id > 0, 'start_new conversation');
            $cid = (int) $conv->id;
            $cleanup[] = $cid;
            $mid1 = $this->ai_conv->add_message($cid, 'user', $tag . ' hello');
            $mid2 = $this->ai_conv->add_message($cid, 'assistant', $tag . ' reply');
            $hist = $this->ai_conv->get_session_style_history($cid, $uid, 20);
            $ok(count($hist) >= 2, 'messages persisted (ids ' . $mid1 . '/' . $mid2 . ', hist=' . count($hist) . ')');
            $ok($this->ai_conv->owns($cid, $uid), 'owns conversation');
            $ok(!$this->ai_conv->owns($cid, $uid + 99999), 'other user does not own');
        }

        $section('6. Deterministic tools with dummy rows');
        if ($uid > 0) {
            $today = date('Y-m-d');

            if ($this->db->table_exists('tasks')) {
                $task_row = array(
                    'title' => $tag . ' task',
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                );
                if ($this->db->field_exists('assigned_to', 'tasks')) {
                    $task_row['assigned_to'] = $uid;
                } elseif ($this->db->field_exists('assignee_id', 'tasks')) {
                    $task_row['assignee_id'] = $uid;
                } elseif ($this->db->field_exists('user_id', 'tasks')) {
                    $task_row['user_id'] = $uid;
                }
                if ($this->db->field_exists('updated_at', 'tasks')) {
                    $task_row['updated_at'] = date('Y-m-d H:i:s');
                }
                $this->db->insert('tasks', $task_row);
                $task_id = (int) $this->db->insert_id();
                $ok($task_id > 0, "dummy task id=$task_id");
                $tool = ai_chat_run_tool('my_open_tasks', $uid, $this->db);
                $ok(!empty($tool['ok']) && stripos($tool['html'], $tag) !== false, 'my_open_tasks finds dummy task');
                if ($task_id > 0) {
                    $this->db->where('id', $task_id)->delete('tasks');
                }
            } else {
                echo "[SKIP] tasks table missing\n";
            }

            if ($this->db->table_exists('daily_work_logs')) {
                $log = array(
                    'user_id' => $uid,
                    'work_date' => $today,
                    'description' => $tag . ' desc',
                    'created_at' => date('Y-m-d H:i:s'),
                );
                if ($this->db->field_exists('activity_title', 'daily_work_logs')) {
                    $log['activity_title'] = $tag . ' activity';
                } elseif ($this->db->field_exists('title', 'daily_work_logs')) {
                    $log['title'] = $tag . ' activity';
                }
                $this->db->insert('daily_work_logs', $log);
                $log_id = (int) $this->db->insert_id();
                $ok($log_id > 0, "dummy daily_work_logs id=$log_id");
                $tool = ai_chat_run_tool('my_daily_activity_today', $uid, $this->db);
                $ok(!empty($tool['ok']) && (stripos($tool['html'], $tag) !== false || stripos($tool['html'], 'daily activity') !== false), 'my_daily_activity_today works');
                if ($log_id > 0) {
                    $this->db->where('id', $log_id)->delete('daily_work_logs');
                }
            } else {
                echo "[SKIP] daily_work_logs missing\n";
            }

            $tool_att = ai_chat_run_tool('my_attendance_today', $uid, $this->db);
            $ok(!empty($tool_att['ok']), 'my_attendance_today runs (may be empty)');

            $tool_bal = ai_chat_run_tool('my_leave_balance', $uid, $this->db);
            $ok(!empty($tool_bal['ok']) || !empty($tool_bal['error']), 'my_leave_balance returns structured result');
        }

        $section('7. Export button helper');
        $html = ai_chat_append_export_buttons('Hello', array(array('a' => 1)), 'test');
        $ok(strpos($html, 'export-btn') !== false && strpos($html, 'data-export-format="csv"') !== false, 'export buttons rendered');

        $section('8. New tools + ops helpers');
        $ok(ai_chat_match_tool('Who is late today?') === 'who_late_today', 'match who_late_today');
        $ok(ai_chat_match_tool('mala report de attendance all user cha') === 'attendance_today_report', 'match Marathi attendance report');
        $ok(ai_chat_match_tool('I want all user attendance report for this month') === 'attendance_today_report', 'match month attendance report');
        $rangeMonth = ai_chat_parse_date_range('all users attendance this month');
        $ok(!empty($rangeMonth['from']) && $rangeMonth['from'] === date('Y-m-01') && empty($rangeMonth['is_today']), 'parse this month range');
        $ok(ai_chat_match_tool('Show my pending leave requests') === 'my_pending_leaves', 'match my_pending_leaves');
        $ok(ai_chat_match_tool('My SPL points') === 'my_spl_points', 'match my_spl_points');
        $ok(ai_chat_is_followup('export csv') === 'export_csv', 'followup export csv');
        $ok(ai_chat_is_followup('again') === 'repeat', 'followup repeat');
        $chips = ai_chat_suggestion_chips();
        $ok(count($chips) >= 5, 'suggestion chips count');
        $confirm = ai_chat_sql_confirm_html('abc123', 'SELECT 1');
        $ok(strpos($confirm, 'ai-confirm-sql') !== false, 'sql confirm helper still available');
        if ($uid > 0) {
            $t1 = ai_chat_run_tool('who_late_today', $uid, $this->db);
            $ok(!empty($t1['ok']) || !empty($t1['error']), 'who_late_today structured');
            $tReport = ai_chat_run_tool('attendance_today_report', $uid, $this->db);
            $ok(!empty($tReport['ok']) || !empty($tReport['error']), 'attendance_today_report structured');
            $t2 = ai_chat_run_tool('my_pending_leaves', $uid, $this->db);
            $ok(!empty($t2['ok']), 'my_pending_leaves runs');
            $t3 = ai_chat_run_tool('my_spl_points', $uid, $this->db);
            $ok(!empty($t3['ok']) || !empty($t3['error']), 'my_spl_points structured');
            ai_chat_audit_log($uid, 'test audit ' . $tag, 'my_leave_balance', 'cli', true);
            $ok($this->db->table_exists('ai_chat_intent_log'), 'intent log table exists');
        }

        $section('Cleanup');
        foreach ($cleanup as $cid) {
            $this->db->where('conversation_id', (int) $cid)->delete('ai_conversation_messages');
            $this->db->where('id', (int) $cid)->delete('ai_conversations');
            echo "cleaned conversation $cid\n";
        }

        echo "\n==============================\n";
        echo "RESULT: $pass PASS / $fail FAIL\n";
        echo "==============================\n";
        echo "Also run: php index.php ai_chat_test_cli eval\n";
        exit($fail > 0 ? 1 : 0);
    }
}
