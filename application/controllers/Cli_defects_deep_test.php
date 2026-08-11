<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only deep test for Defects model + business rules.
 * Run: php index.php cli_defects_deep_test run
 */
class Cli_defects_deep_test extends CI_Controller
{
    private $pass = 0;
    private $fail = 0;
    private $created_ids = array();

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_error('CLI only', 403);
        }
        $this->load->model('Defect_model', 'defects');
        $this->load->helper(array('defects_schema', 'defects_releases'));
    }

    public function run()
    {
        echo "=== Defects deep backend / business logic ===\n";

        $admin = $this->db->select('id, name, email')
            ->where('role_id', 1)
            ->order_by('id', 'ASC')
            ->get('users', 1)
            ->row();
        $this->assert_true((bool) $admin, 'admin user exists');
        if (!$admin) {
            $this->summary();
            return;
        }
        $uid = (int) $admin->id;
        echo "  admin uid={$uid} name=" . $admin->name . "\n";

        // Simulate session for scope helpers that read userdata
        $this->session->set_userdata(array(
            'user_id' => $uid,
            'role_id' => 1,
            'logged_in' => true,
        ));

        $this->test_schema();
        $this->test_next_number();
        $clients = $this->test_client_project_options();
        $project = $this->pick_project($clients);
        $assignee = $this->pick_assignee($uid);
        $id_min = $this->test_create_minimal($uid);
        $id_full = $this->test_create_full($uid, $project, $assignee);
        $this->test_build_change_details($id_full, $uid, $assignee);
        $this->test_edit_status_and_history($id_full, $uid);
        $this->test_history_note($id_full, $uid);
        $this->test_soft_rules($project);
        $this->test_list_filters($id_full, $project);
        $this->test_overdue_flag($uid);
        $this->test_view_payload($id_full);

        $this->cleanup();
        $this->summary();
    }

    private function test_schema()
    {
        $this->assert_true($this->db->table_exists('project_defects'), 'table project_defects');
        $this->assert_true($this->db->table_exists('project_defect_activity'), 'table project_defect_activity');
        $nullable = false;
        $q = $this->db->query("SHOW COLUMNS FROM project_defects LIKE 'project_id'");
        $col = $q ? $q->row() : null;
        if ($col && isset($col->Null) && strtoupper((string) $col->Null) === 'YES') {
            $nullable = true;
        }
        $this->assert_true($nullable, 'project_id is NULL-able (soft create)');
    }

    private function test_next_number()
    {
        $n1 = $this->defects->next_defect_number();
        $this->assert_true((bool) preg_match('/^DEF-\d{6}-\d{4}$/', $n1), 'next_defect_number format: ' . $n1);
        echo "  next number sample: {$n1}\n";
    }

    private function test_client_project_options()
    {
        $clients = $this->defects->client_options();
        $projects = $this->defects->project_options();
        $this->assert_true(is_array($clients), 'client_options returns array');
        $this->assert_true(is_array($projects), 'project_options returns array');
        echo '  clients=' . count($clients) . ' projects=' . count($projects) . "\n";
        if (!empty($projects)) {
            $p = $projects[0];
            $cid = $this->defects->project_client_id((int) $p->id);
            $ok = $this->defects->is_project_accessible((int) $p->id);
            $this->assert_true($ok, 'is_project_accessible project#' . (int) $p->id);
            echo '  sample project id=' . (int) $p->id . ' client_id=' . (int) $cid . ' name=' . (isset($p->name) ? $p->name : '') . "\n";
        }
        return $clients;
    }

    private function pick_project($clients)
    {
        $projects = $this->defects->project_options();
        if (empty($projects)) {
            echo "  WARN: no projects — full create will use null project\n";
            return null;
        }
        return $projects[0];
    }

    private function pick_assignee($uid)
    {
        $users = $this->defects->user_options();
        foreach ($users as $u) {
            $id = (int) $u->id;
            if ($id !== $uid && $this->defects->is_user_assignable($id)) {
                echo '  assignee candidate id=' . $id . ' name=' . $u->name . "\n";
                return $u;
            }
        }
        if ($this->defects->is_user_assignable($uid)) {
            return (object) array('id' => $uid, 'name' => 'self');
        }
        return null;
    }

    private function test_create_minimal($uid)
    {
        $num = $this->defects->next_defect_number();
        $payload = array(
            'defect_number' => $num,
            'project_id' => null,
            'title' => 'Untitled defect',
            'description' => '',
            'steps_to_reproduce' => '',
            'severity' => 'medium',
            'priority' => 'medium',
            'status' => 'open',
            'assigned_to' => null,
            'reported_by' => $uid,
            'due_date' => null,
        );
        $id = $this->defects->save_defect($payload);
        $this->created_ids[] = $id;
        $this->assert_true($id > 0, 'minimal create insert_id=' . $id);
        $this->defects->log_activity($id, $uid, 'created', 'Defect logged');
        $row = $this->defects->get_defect($id);
        $this->assert_true((bool) $row, 'get_defect minimal');
        if ($row) {
            $this->assert_eq($row->title, 'Untitled defect', 'minimal title soft default');
            $this->assert_eq($row->defect_number, $num, 'minimal defect_number persisted');
            $this->assert_eq($row->severity, 'medium', 'minimal severity default');
            $this->assert_eq($row->status, 'open', 'minimal status open');
            $this->assert_true($row->project_id === null || (int) $row->project_id === 0, 'minimal project empty');
            echo "  minimal OK id={$id} number={$row->defect_number}\n";
        }
        return $id;
    }

    private function test_create_full($uid, $project, $assignee)
    {
        $num = $this->defects->next_defect_number();
        $title = 'DeepTest full ' . date('Y-m-d H:i:s');
        $pid = $project ? (int) $project->id : null;
        $aid = $assignee ? (int) $assignee->id : null;
        $payload = array(
            'defect_number' => $num,
            'project_id' => $pid ?: null,
            'title' => $title,
            'description' => '<p>Deep test description value</p>',
            'steps_to_reproduce' => '<ol><li>Open screen</li><li>Click save</li></ol>',
            'severity' => 'high',
            'priority' => 'critical',
            'status' => 'open',
            'assigned_to' => $aid,
            'reported_by' => $uid,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
        );
        $id = $this->defects->save_defect($payload);
        $this->created_ids[] = $id;
        $this->assert_true($id > 0, 'full create insert_id=' . $id);
        $this->defects->log_activity($id, $uid, 'created', 'Defect logged');
        if ($aid) {
            $this->defects->log_activity($id, $uid, 'reassigned', 'Assigned to user #' . $aid);
        }
        $row = $this->defects->get_defect($id);
        $this->assert_true((bool) $row, 'get_defect full');
        if ($row) {
            $this->assert_eq($row->title, $title, 'full title');
            $this->assert_eq($row->severity, 'high', 'full severity');
            $this->assert_eq($row->priority, 'critical', 'full priority');
            $this->assert_eq((int) $row->reported_by, $uid, 'full reported_by');
            if ($pid) {
                $this->assert_eq((int) $row->project_id, $pid, 'full project_id');
            }
            if ($aid) {
                $this->assert_eq((int) $row->assigned_to, $aid, 'full assigned_to');
            }
            echo "  full OK id={$id} number={$row->defect_number} title={$row->title}\n";
            echo '  values: severity=' . $row->severity . ' priority=' . $row->priority
                . ' project=' . (int) $row->project_id . ' assignee=' . (int) $row->assigned_to
                . ' due=' . $row->due_date . "\n";
        }
        return $id;
    }

    private function test_build_change_details($id, $uid, $assignee)
    {
        $old = $this->defects->get_defect($id);
        $this->assert_true((bool) $old, 'build_change: load old');
        if (!$old) {
            return;
        }
        $new = array(
            'title' => $old->title . ' (edited)',
            'severity' => 'critical',
            'priority' => 'low',
            'status' => 'in_progress',
            'assigned_to' => $assignee ? (int) $assignee->id : null,
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'description' => '<p>Updated desc</p>',
            'steps_to_reproduce' => $old->steps_to_reproduce,
            'project_id' => $old->project_id,
            'release_id' => $old->release_id,
            'task_id' => $old->task_id,
        );
        $lines = $this->defects->build_change_details($old, $new);
        $this->assert_true(count($lines) >= 3, 'build_change_details lines>=3 got=' . count($lines));
        $joined = implode('; ', $lines);
        $this->assert_true(strpos($joined, 'Severity:') !== false, 'change contains Severity');
        $this->assert_true(strpos($joined, '→') !== false, 'change uses arrow');
        $this->assert_true(strpos($joined, 'Description: updated') !== false, 'rich text summarized');
        echo "  change lines (" . count($lines) . "):\n";
        foreach ($lines as $line) {
            echo "    - {$line}\n";
        }
    }

    private function test_edit_status_and_history($id, $uid)
    {
        $old = $this->defects->get_defect($id);
        $payload = array(
            'title' => $old->title . ' (edited)',
            'severity' => 'critical',
            'priority' => 'low',
            'status' => 'in_progress',
            'description' => '<p>Updated desc</p>',
            'steps_to_reproduce' => $old->steps_to_reproduce,
            'due_date' => date('Y-m-d', strtotime('+14 days')),
        );
        $lines = $this->defects->build_change_details($old, $payload);
        $this->defects->save_defect($payload, $id);
        $this->defects->log_activity($id, $uid, 'updated', implode('; ', $lines));
        $this->defects->log_activity($id, $uid, 'status', $old->status . ' → in_progress');

        $row = $this->defects->get_defect($id);
        $this->assert_eq($row->status, 'in_progress', 'status after edit');
        $this->assert_eq($row->severity, 'critical', 'severity after edit');
        $this->assert_eq($row->priority, 'low', 'priority after edit');
        $this->assert_eq($row->title, $payload['title'], 'title after edit');

        // close → resolved_at business rule (controller sets it; emulate)
        $close = array(
            'status' => 'closed',
            'resolved_at' => date('Y-m-d H:i:s'),
        );
        $this->defects->save_defect($close, $id);
        $this->defects->log_activity($id, $uid, 'status', 'in_progress → closed');
        $row2 = $this->defects->get_defect($id);
        $this->assert_eq($row2->status, 'closed', 'status closed');
        $this->assert_true(!empty($row2->resolved_at), 'resolved_at set on close: ' . $row2->resolved_at);
        echo "  edit/status OK resolved_at={$row2->resolved_at}\n";
    }

    private function test_history_note($id, $uid)
    {
        $note = 'DeepTest history note ' . date('H:i:s');
        $this->defects->log_activity($id, $uid, 'note', $note);
        $history = $this->defects->list_history($id);
        $this->assert_true(count($history) >= 3, 'list_history count>=3 got=' . count($history));
        $found_note = false;
        $found_created = false;
        $has_user = false;
        foreach ($history as $h) {
            if ((string) $h->action === 'note' && (string) $h->detail === $note) {
                $found_note = true;
            }
            if ((string) $h->action === 'created') {
                $found_created = true;
            }
            if (!empty($h->user_name) || !empty($h->created_at)) {
                $has_user = true;
            }
        }
        $this->assert_true($found_note, 'history contains note value');
        $this->assert_true($found_created, 'history contains created');
        $this->assert_true($has_user, 'history rows have user/date fields');
        echo "  history rows=" . count($history) . " (Date/Comments/Added By source OK)\n";
        $sample = $history[0];
        echo '  latest: date=' . $sample->created_at
            . ' action=' . $sample->action
            . ' by=' . (isset($sample->user_name) ? $sample->user_name : '')
            . ' detail=' . substr((string) $sample->detail, 0, 80) . "\n";
    }

    private function test_soft_rules($project)
    {
        // Invalid severity/priority should be forced to medium by controller; model stores as given —
        // verify accessibility helpers used by soft payload.
        $this->assert_true(!$this->defects->is_project_accessible(999999991), 'inaccessible project rejected');
        $this->assert_true(!$this->defects->is_user_assignable(999999991), 'invalid assignee rejected');
        if ($project) {
            $bad_release = $this->defects->release_belongs_to_project(999999991, (int) $project->id);
            $bad_task = $this->defects->task_belongs_to_project(999999991, (int) $project->id);
            $this->assert_true(!$bad_release, 'orphan release rejected');
            $this->assert_true(!$bad_task, 'orphan task rejected');
        }
        echo "  soft validation helpers OK\n";
    }

    private function test_list_filters($id, $project)
    {
        $all = $this->defects->list_defects(array(), 5, 0);
        $this->assert_true(is_array($all) && count($all) >= 1, 'list_defects returns rows');
        $by_status = $this->defects->list_defects(array('status' => 'closed'), 50, 0);
        $found = false;
        foreach ($by_status as $r) {
            if ((int) $r->id === (int) $id) {
                $found = true;
                break;
            }
        }
        $this->assert_true($found, 'list filter status=closed includes edited defect #' . $id);
        if ($project) {
            $by_proj = $this->defects->count_defects(array('project_id' => (int) $project->id));
            $this->assert_true($by_proj >= 1, 'count_defects by project_id >=1 got=' . $by_proj);
        }
        $q = $this->defects->list_defects(array('q' => 'DeepTest'), 20, 0);
        $this->assert_true(count($q) >= 1, 'search q=DeepTest hits=' . count($q));
        echo "  list/filter OK\n";
    }

    private function test_overdue_flag($uid)
    {
        $num = $this->defects->next_defect_number();
        $id = $this->defects->save_defect(array(
            'defect_number' => $num,
            'project_id' => null,
            'title' => 'DeepTest overdue',
            'severity' => 'medium',
            'priority' => 'medium',
            'status' => 'open',
            'reported_by' => $uid,
            'due_date' => date('Y-m-d', strtotime('-3 days')),
        ));
        $this->created_ids[] = $id;
        $row = $this->defects->get_defect($id);
        $overdue = function_exists('defect_is_overdue') ? defect_is_overdue($row) : false;
        $this->assert_true($overdue, 'defect_is_overdue true for past due open defect');
        $closed = $this->defects->save_defect(array('status' => 'closed', 'resolved_at' => date('Y-m-d H:i:s')), $id);
        $row2 = $this->defects->get_defect($id);
        $overdue2 = function_exists('defect_is_overdue') ? defect_is_overdue($row2) : true;
        $this->assert_true(!$overdue2, 'closed defect not overdue');
        echo "  overdue helper OK\n";
    }

    private function test_view_payload($id)
    {
        $item = $this->defects->get_defect($id);
        $atts = $this->defects->list_attachments($id);
        $hist = $this->defects->list_history($id);
        $this->assert_true((bool) $item, 'view item');
        $this->assert_true(is_array($atts), 'view attachments array');
        $this->assert_true(is_array($hist) && count($hist) > 0, 'view history non-empty');
        // Display name helper
        $name = $this->defects->user_display_name((int) $item->reported_by);
        $this->assert_true($name !== '' && $name !== 'Unassigned', 'user_display_name reporter=' . $name);
        $un = $this->defects->user_display_name(0);
        $this->assert_eq($un, 'Unassigned', 'user_display_name(0)');
        echo "  view payload OK reporter={$name} history=" . count($hist) . "\n";
    }

    private function cleanup()
    {
        foreach ($this->created_ids as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            if ($this->db->table_exists('project_defect_activity')) {
                $this->db->where('defect_id', $id)->delete('project_defect_activity');
            }
            if ($this->db->table_exists('project_defect_comments')) {
                $this->db->where('defect_id', $id)->delete('project_defect_comments');
            }
            if ($this->db->table_exists('project_defect_attachments')) {
                $this->db->where('defect_id', $id)->delete('project_defect_attachments');
            }
            $this->db->where('id', $id)->delete('project_defects');
        }
        echo '  cleanup deleted ids: ' . implode(',', $this->created_ids) . "\n";
    }

    private function assert_true($cond, $msg)
    {
        if ($cond) {
            $this->pass++;
            echo "OK  {$msg}\n";
        } else {
            $this->fail++;
            echo "FAIL {$msg}\n";
        }
    }

    private function assert_eq($actual, $expected, $msg)
    {
        $ok = ((string) $actual === (string) $expected);
        if ($ok) {
            $this->pass++;
            echo "OK  {$msg}\n";
        } else {
            $this->fail++;
            echo "FAIL {$msg} expected=[" . $expected . '] actual=[' . $actual . "]\n";
        }
    }

    private function summary()
    {
        echo "\n=== Summary: {$this->pass} passed, {$this->fail} failed ===\n";
        if ($this->fail > 0) {
            exit(1);
        }
    }
}
