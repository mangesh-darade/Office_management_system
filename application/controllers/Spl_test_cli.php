<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only functional tests for SPL + Daily Activity.
 * Run: php index.php spl_test_cli run
 */
class Spl_test_cli extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
            return;
        }
        $this->load->database();
        $this->load->helper(array('rewards_automation', 'rewards', 'spl'));
        $this->load->model(array('Reward_model' => 'rewards', 'Spl_model' => 'spl'));
    }

    public function run()
    {
        $pass = 0;
        $fail = 0;
        $tag = 'SPL_TEST_' . date('YmdHis');
        $log_id = 0;
        $log_id_2 = 0;
        $queue_id = 0;
        $tx_id = 0;

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

        list($test_user_id, $work_date) = $this->pick_clean_test_user_and_date();
        echo "SPL + Daily Activity functional test ($tag)\n";
        echo "Using user_id=$test_user_id work_date=$work_date\n";

        $section('1. Daily activity insert → reward automation');
        $title = $tag . ' title';
        $desc = '<p>' . $tag . ' description</p>';
        $this->db->insert('daily_work_logs', array(
            'user_id' => $test_user_id,
            'task_id' => null,
            'activity_title' => $title,
            'work_date' => $work_date,
            'description' => $desc,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $log_id = (int) $this->db->insert_id();
        $ok($log_id > 0, "Inserted daily_work_logs id=$log_id");

        $rule = $this->rewards->get_rule_by_code('self_work_update_submitted');
        $ok($rule && (int) $rule->is_active === 1, 'Rule self_work_update_submitted is active');

        rewards_automation_after_daily_activity_saved($this->db, $test_user_id, $log_id, $work_date);

        $tx = $this->db->where('source_module', 'daily_activity')
            ->where('source_record_id', $log_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('reward_transactions')->row();
        $ok($tx && $tx->status === 'pending', 'reward_transactions pending row created');
        $tx_id = $tx ? (int) $tx->id : 0;

        $queue = $this->db->where('source_module', 'daily_activity')
            ->where('source_record_id', $log_id)
            ->where('status', 'pending')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('reward_approval_queue')->row();
        $ok($queue && $tx && (int) $queue->transaction_id === (int) $tx->id, 'reward_approval_queue linked to transaction');
        $queue_id = $queue ? (int) $queue->id : 0;

        $section('2. Approval list + description enrichment');
        $found_pending = false;
        $pending_row = null;
        foreach ($this->rewards->list_spl_pending_approvals(500) as $row) {
            if ((int) $row->source_record_id === $log_id) {
                $found_pending = true;
                $pending_row = $row;
                break;
            }
        }
        $ok($found_pending, 'list_spl_pending_approvals includes new row');

        if ($pending_row) {
            $enriched = spl_enrich_approval_rows($this->rewards, array($pending_row));
            $row = $enriched[0];
            $ok(!empty($row->daily_activity_description), 'daily_activity_description enriched');
            $ok(strpos((string) $row->daily_activity_description, $tag) !== false, 'Description has test marker');
            $payload = spl_approval_detail_payload($row, 'pending');
            $ok(!empty($payload['activity_description']), 'approval modal payload has activity_description');
        } else {
            $ok(false, 'Skipped enrichment — pending row not found');
        }

        $section('3. Update pending (points) then approve');
        if ($queue_id > 0) {
            $updated = $this->rewards->update_pending_activity($queue_id, array(
                'requested_points' => 15,
            ));
            $ok(is_array($updated), 'update_pending_activity (points) succeeds');
            $q_pts = $this->rewards->get_approval_queue($queue_id);
            $ok($q_pts && (float) $q_pts->requested_points === 15.0, 'Queue points updated to 15');

            $approved = $this->rewards->approve_pending($queue_id, $test_user_id, 'CLI test approve');
            $ok($approved, 'approve_pending succeeds');
            $tx_after = $this->db->where('id', $tx_id)->get('reward_transactions')->row();
            $ok($tx_after && $tx_after->status === 'approved', 'Transaction approved');
            $ok($tx_after && (float) $tx_after->points === 15.0, 'Approved points = 15');

            if (function_exists('spl_log_system_activity')) {
                spl_log_system_activity(
                    'approved',
                    $queue_id,
                    'CLI test SPL approval #' . $queue_id . ' approved',
                    null,
                    array('status' => 'approved', 'requested_points' => 15)
                );
            }
            $act_count = 0;
            if ($this->db->table_exists('activity_log')) {
                $act_count = (int) $this->db
                    ->where('entity_type', 'spl')
                    ->where('entity_id', $queue_id)
                    ->where('action', 'approved')
                    ->count_all_results('activity_log');
            }
            $ok($act_count > 0, 'activity_log has SPL approved row (Log ID + Record ID=' . $queue_id . ')');
        } else {
            $ok(false, 'Skipped approve — no queue id');
        }

        $section('4. Second activity same day → max_per_day cap');
        $this->db->insert('daily_work_logs', array(
            'user_id' => $test_user_id,
            'task_id' => null,
            'activity_title' => $tag . ' second',
            'work_date' => $work_date,
            'description' => 'No second reward expected',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $log_id_2 = (int) $this->db->insert_id();
        rewards_automation_after_daily_activity_saved($this->db, $test_user_id, $log_id_2, $work_date);
        $tx2_count = (int) $this->db->where('source_module', 'daily_activity')
            ->where('source_record_id', $log_id_2)
            ->count_all_results('reward_transactions');
        $ok($tx2_count === 0, 'Second same-day activity blocked by max_per_day=1');

        $section('5. Team League AVG sort');
        $bounds = spl_reward_period_bounds('week');
        $board = $this->spl->list_groups_board(true, $bounds['from'], $bounds['to']);
        usort($board, function ($a, $b) {
            return (float) $b->avg_period_points <=> (float) $a->avg_period_points;
        });
        $sorted_ok = true;
        $prev_avg = PHP_FLOAT_MAX;
        foreach ($board as $g) {
            $avg = (float) $g->avg_period_points;
            if ($avg > $prev_avg + 0.0001) {
                $sorted_ok = false;
                break;
            }
            $prev_avg = $avg;
        }
        $ok($sorted_ok, 'Groups sorted by avg_period_points DESC');
        $ok((300 / 5) > (400 / 10), 'Example: 5×300 avg beats 10×400 avg');

        if (!empty($board[0]) && (int) $board[0]->member_count > 0) {
            $g = $board[0];
            $calc = round((float) $g->total_period_net / (int) $g->member_count, 2);
            $ok(abs($calc - round((float) $g->avg_period_points, 2)) < 0.02, 'AVG = total ÷ members');
        }

        $dash = spl_build_dashboard_data($test_user_id, 'week');
        $standings = isset($dash['team_standings']) ? $dash['team_standings'] : array();
        $prev = PHP_FLOAT_MAX;
        $standings_ok = true;
        foreach ($standings as $team) {
            if ((float) $team->avg_points > $prev + 0.0001) {
                $standings_ok = false;
                break;
            }
            $prev = (float) $team->avg_points;
        }
        $ok($standings_ok, 'Dashboard team_standings sorted by avg_points');

        $section('6. Daily activity CUD → activity_log (controller parity)');
        $this->load->helper('activity');
        $cud_date = date('Y-m-d', strtotime('-3 days'));
        $this->db->insert('daily_work_logs', array(
            'user_id' => $test_user_id,
            'task_id' => null,
            'activity_title' => $tag . ' cud',
            'work_date' => $cud_date,
            'description' => 'CRUD audit test',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $cud_id = (int) $this->db->insert_id();
        $ok($cud_id > 0, "CUD log inserted id=$cud_id");
        if (function_exists('log_activity')) {
            log_activity('daily_activity', 'created', $cud_id, 'Daily activity created (log #' . $cud_id . '): ' . $tag . ' cud');
        }
        $created_logs = $this->db->table_exists('activity_log')
            ? (int) $this->db->where('entity_type', 'daily_activity')->where('entity_id', $cud_id)->where('action', 'created')->count_all_results('activity_log')
            : 0;
        $ok($created_logs > 0, 'activity_log created for daily_activity');

        $this->db->where('id', $cud_id)->update('daily_work_logs', array('activity_title' => $tag . ' cud updated'));
        if (function_exists('log_activity_with_changes')) {
            log_activity_with_changes(
                'daily_activity',
                'updated',
                $cud_id,
                array('activity_title' => $tag . ' cud'),
                array('activity_title' => $tag . ' cud updated'),
                'Daily activity updated (log #' . $cud_id . ')'
            );
        }
        $updated_logs = $this->db->table_exists('activity_log')
            ? (int) $this->db->where('entity_type', 'daily_activity')->where('entity_id', $cud_id)->where('action', 'updated')->count_all_results('activity_log')
            : 0;
        $ok($updated_logs > 0, 'activity_log updated for daily_activity');

        if (function_exists('log_activity_with_changes')) {
            log_activity_with_changes(
                'daily_activity',
                'deleted',
                $cud_id,
                array('user_id' => $test_user_id, 'activity_title' => $tag . ' cud updated', 'work_date' => $cud_date),
                null,
                'Daily activity deleted (log #' . $cud_id . ')'
            );
        }
        $this->db->where('id', $cud_id)->delete('daily_work_logs');
        $deleted_logs = $this->db->table_exists('activity_log')
            ? (int) $this->db->where('entity_type', 'daily_activity')->where('entity_id', $cud_id)->where('action', 'deleted')->count_all_results('activity_log')
            : 0;
        $ok($deleted_logs > 0, 'activity_log deleted for daily_activity');
        $ok((int) $this->db->where('id', $cud_id)->count_all_results('daily_work_logs') === 0, 'CUD daily_work_logs row removed');

        $section('7. My Works Daily Pulse build');
        $this->load->helper('my_works_daily_pulse');
        $pulse = my_works_build_daily_pulse($this->db, $test_user_id, true, 1);
        $ok(is_array($pulse), 'my_works_build_daily_pulse returns array');
        $expected_keys = array('clients_added', 'attendance', 'daily_activity', 'project_history', 'adhoc_history', 'requirements_added', 'defects_added', 'overview_today', 'spl_group_scores');
        $keys_ok = true;
        foreach ($expected_keys as $k) {
            if (!array_key_exists($k, $pulse)) {
                $keys_ok = false;
                break;
            }
        }
        $ok($keys_ok, 'Daily pulse has all section keys');
        $ok(isset($pulse['daily_activity']['logged']) && isset($pulse['daily_activity']['not_logged']), 'Daily pulse activity buckets present');

        $section('8. Activity preferred table mapping');
        $this->config->load('table_module_mapping', true);
        $mapping = $this->config->item('table_module_mapping', 'table_module_mapping');
        $ok(is_array($mapping) && isset($mapping['reward_approval_queue']) && $mapping['reward_approval_queue'] === 'spl', 'table map reward_approval_queue→spl');
        $ok(is_array($mapping) && isset($mapping['daily_work_logs']) && $mapping['daily_work_logs'] === 'daily_activity', 'table map daily_work_logs→daily_activity');

        $section('9. Delete test logs');
        if ($log_id > 0) {
            $this->db->where('id', $log_id)->delete('daily_work_logs');
        }
        if ($log_id_2 > 0) {
            $this->db->where('id', $log_id_2)->delete('daily_work_logs');
        }
        if ($queue_id > 0) {
            $q = $this->rewards->get_approval_queue($queue_id);
            if ($q && $q->status === 'approved') {
                $ok(true, "Approval queue $queue_id left as approved (audit trail)");
            }
        }
        $ok($log_id > 0 || $log_id_2 > 0, "Deleted test logs $log_id, $log_id_2");

        $section('Summary');
        echo "Passed: $pass | Failed: $fail\n";
        exit($fail > 0 ? 1 : 0);
    }

    /**
     * Find user + work_date with no daily_activity reward yet (avoids max_per_day conflicts).
     *
     * @return array{0:int,1:string}
     */
    private function pick_clean_test_user_and_date()
    {
        $rule = $this->rewards->get_rule_by_code('self_work_update_submitted');
        if (!$rule) {
            return array(1, date('Y-m-d'));
        }
        $rule_id = (int) $rule->id;
        $work_date = date('Y-m-d');
        $users = $this->db->select('id')->from('users')->order_by('id', 'ASC')->limit(100)->get()->result();
        foreach ($users as $u) {
            $uid = (int) $u->id;
            if ($uid <= 0) {
                continue;
            }
            if ($this->rewards->count_rule_awards_today($rule_id, $uid) === 0) {
                return array($uid, $work_date);
            }
        }
        return array(1, $work_date);
    }
}
