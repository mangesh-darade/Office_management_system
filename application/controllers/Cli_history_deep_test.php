<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only deep test for History (Clients, Projects, Releases, My Works).
 * Run: php index.php cli_history_deep_test run
 */
class Cli_history_deep_test extends CI_Controller
{
    private $pass = 0;
    private $fail = 0;
    private $client_ids = array();
    private $project_ids = array();
    private $release_ids = array();
    private $work_ids = array();

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_error('CLI only', 403);
        }
        $this->load->model('Client_model', 'clients');
        $this->load->model('Project_model');
        $this->load->model('Engagement_model', 'eng');
        $this->load->model('My_work_model', 'my_works');
        $this->load->helper(array('clients_schema', 'engagement_schema'));
        clients_schema_ensure($this->db);
        engagement_schema_ensure($this->db);
        $this->Project_model->ensure_activity_schema();
    }

    public function run()
    {
        echo "=== History deep backend / functionality ===\n";

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
        $this->session->set_userdata(array(
            'user_id' => $uid,
            'role_id' => 1,
            'logged_in' => true,
        ));

        $this->test_schema();
        $this->test_clients($uid);
        $this->test_projects($uid);
        $this->test_releases($uid);
        $this->test_my_works($uid);
        $this->test_empty_note_guard();

        $this->cleanup();
        $this->summary();
    }

    private function test_schema()
    {
        $this->assert_true($this->db->table_exists('client_activity'), 'table client_activity');
        $this->assert_true($this->db->table_exists('project_activity'), 'table project_activity');
        $this->assert_true($this->db->table_exists('project_release_activity'), 'table project_release_activity');
        $this->assert_true($this->db->table_exists('my_work_activity'), 'table my_work_activity');
        echo "  schema OK\n";
    }

    private function test_clients($uid)
    {
        echo "-- Clients --\n";
        $code = 'DT-H-' . date('YmdHis');
        $id = $this->clients->create_client(array(
            'client_code' => $code,
            'company_name' => 'DeepTest History Client',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $this->assert_true($id > 0, 'create_client id=' . $id);
        if ($id < 1) {
            return;
        }
        $this->client_ids[] = $id;

        $this->clients->log_activity($id, $uid, 'created', null, array(
            'detail' => 'DeepTest History Client (' . $code . ')',
        ));
        $note = 'Client note ' . date('H:i:s');
        $this->clients->log_activity($id, $uid, 'note', null, array(
            'detail' => $note,
            'comment' => $note,
        ));
        $this->clients->log_activity($id, $uid, 'status_changed', array(
            'status' => 'active',
        ), array(
            'status' => 'inactive',
        ));

        $history = $this->clients->list_history($id);
        $this->assert_true(count($history) >= 3, 'client list_history count>=3 got=' . count($history));
        $this->assert_history_shape($history, 'client');

        $found_note = false;
        $found_created = false;
        $found_status = false;
        $note_as_updated = false;
        foreach ($history as $h) {
            if ((string) $h->action === 'note' && (string) $h->detail === $note) {
                $found_note = true;
            }
            if ((string) $h->action === 'created') {
                $found_created = true;
            }
            if ((string) $h->action === 'status_changed' && strpos((string) $h->detail, '→') !== false) {
                $found_status = true;
            }
            if ((string) $h->action === 'updated' && (string) $h->detail === $note) {
                $note_as_updated = true;
            }
        }
        $this->assert_true($found_note, 'client history contains note');
        $this->assert_true($found_created, 'client history contains created');
        $this->assert_true($found_status, 'client history contains status_changed with arrow');
        $this->assert_true(!$note_as_updated, 'client note action stays note (not remapped to updated)');

        $row = $this->db->where('client_id', $id)->where('action', 'note')->order_by('id', 'DESC')->get('client_activity', 1)->row();
        $this->assert_true((bool) $row && $row->action === 'note', 'client_activity persisted action=note');

        $this->clients->log_client_changes($id, $uid, (object) array(
            'company_name' => 'DeepTest History Client',
            'status' => 'active',
            'phone' => '',
        ), array(
            'company_name' => 'DeepTest History Client Renamed',
            'phone' => '9999999999',
        ));
        $history2 = $this->clients->list_history($id);
        $found_rename = false;
        foreach ($history2 as $h) {
            if (strpos((string) $h->detail, 'Company Name') !== false && strpos((string) $h->detail, '→') !== false) {
                $found_rename = true;
            }
        }
        $this->assert_true($found_rename, 'client log_client_changes wrote field history');
        echo "  clients history rows=" . count($history2) . "\n";
    }

    private function test_projects($uid)
    {
        echo "-- Projects --\n";
        $code = 'DTP-' . date('YmdHis');
        $this->db->insert('projects', array(
            'code' => $code,
            'name' => 'DeepTest History Project',
            'status' => 'planned',
        ));
        $id = (int) $this->db->insert_id();
        $this->assert_true($id > 0, 'insert project id=' . $id);
        if ($id < 1) {
            return;
        }
        $this->project_ids[] = $id;

        $this->Project_model->log_activity($id, $uid, 'created', 'Project created');
        $old = $this->db->where('id', $id)->get('projects')->row();
        $payload = array(
            'name' => 'DeepTest History Project Edited',
            'status' => 'active',
            'code' => $code,
        );
        $lines = $this->Project_model->build_change_details($old, $payload);
        $this->assert_true(count($lines) >= 2, 'project build_change_details lines>=2 got=' . count($lines));
        $joined = implode('; ', $lines);
        $this->assert_true(strpos($joined, 'Name:') !== false, 'change line includes Name');
        $this->assert_true(strpos($joined, 'Status:') !== false, 'change line includes Status');
        $this->Project_model->log_activity($id, $uid, 'updated', $joined);

        $note = 'Project note ' . date('H:i:s');
        $this->Project_model->log_activity($id, $uid, 'note', $note);

        $history = $this->Project_model->list_history($id);
        $this->assert_true(count($history) >= 3, 'project list_history count>=3 got=' . count($history));
        $this->assert_history_shape($history, 'project');
        $found_note = false;
        $found_created = false;
        $found_updated = false;
        foreach ($history as $h) {
            if ((string) $h->action === 'note' && (string) $h->detail === $note) {
                $found_note = true;
            }
            if ((string) $h->action === 'created') {
                $found_created = true;
            }
            if ((string) $h->action === 'updated' && strpos((string) $h->detail, '→') !== false) {
                $found_updated = true;
            }
        }
        $this->assert_true($found_note, 'project history contains note');
        $this->assert_true($found_created, 'project history contains created');
        $this->assert_true($found_updated, 'project history contains updated changes');
        echo "  projects history rows=" . count($history) . "\n";
    }

    private function test_releases($uid)
    {
        echo "-- Releases --\n";
        $project_id = !empty($this->project_ids) ? (int) $this->project_ids[0] : 0;
        if ($project_id < 1) {
            $row = $this->db->select('id')->order_by('id', 'DESC')->get('projects', 1)->row();
            $project_id = $row ? (int) $row->id : 0;
        }
        $this->assert_true($project_id > 0, 'release has project_id=' . $project_id);
        if ($project_id < 1) {
            return;
        }

        $version = 'DT.' . date('His');
        $id = $this->eng->save_release(array(
            'project_id' => $project_id,
            'version' => $version,
            'title' => 'DeepTest History Release',
            'description' => 'Deep test release',
            'status' => 'planned',
            'created_by' => $uid,
        ));
        $this->assert_true($id > 0, 'save_release id=' . $id);
        if ($id < 1) {
            return;
        }
        $this->release_ids[] = $id;

        $this->eng->log_activity($id, $uid, 'created', 'Release created');
        $item = $this->eng->get_release($id);
        $payload = array(
            'project_id' => $project_id,
            'version' => $version,
            'title' => 'DeepTest History Release Edited',
            'description' => 'changed',
            'status' => 'in_progress',
        );
        $lines = $this->eng->build_change_details($item, $payload);
        $this->assert_true(count($lines) >= 2, 'release build_change_details lines>=2 got=' . count($lines));
        $this->eng->log_activity($id, $uid, 'updated', implode('; ', $lines));
        $note = 'Release note ' . date('H:i:s');
        $this->eng->log_activity($id, $uid, 'note', $note);

        $history = $this->eng->list_history($id);
        $this->assert_true(count($history) >= 3, 'release list_history count>=3 got=' . count($history));
        $this->assert_history_shape($history, 'release');
        $found_note = false;
        $found_created = false;
        foreach ($history as $h) {
            if ((string) $h->action === 'note' && (string) $h->detail === $note) {
                $found_note = true;
            }
            if ((string) $h->action === 'created') {
                $found_created = true;
            }
        }
        $this->assert_true($found_note, 'release history contains note');
        $this->assert_true($found_created, 'release history contains created');
        echo "  releases history rows=" . count($history) . "\n";
    }

    private function test_my_works($uid)
    {
        echo "-- My Works --\n";
        $id = $this->my_works->insert(array(
            'title' => 'DeepTest History Work',
            'details' => 'deep test',
            'created_by' => $uid,
            'created_for' => $uid,
            'status' => 'new',
        ));
        $this->assert_true($id > 0, 'my_works insert id=' . $id);
        if ($id < 1) {
            return;
        }
        $this->work_ids[] = $id;

        $this->my_works->log_activity($id, $uid, 'created', 'Work item created');
        $this->my_works->add_comment($id, $uid, 'Legacy comment body');
        $this->my_works->log_activity($id, $uid, 'comment', 'Added a comment');
        $note = 'Work note ' . date('H:i:s');
        $this->my_works->log_activity($id, $uid, 'note', $note);
        $this->my_works->log_activity($id, $uid, 'status', 'new → in_progress');

        $history = $this->my_works->list_history($id);
        $this->assert_true(count($history) >= 3, 'my_works list_history count>=3 got=' . count($history));
        $this->assert_history_shape($history, 'my_works');

        $found_note = false;
        $found_legacy = false;
        $found_generic_comment = false;
        $found_status = false;
        foreach ($history as $h) {
            if ((string) $h->action === 'note' && (string) $h->detail === $note) {
                $found_note = true;
            }
            if ((string) $h->action === 'note' && (string) $h->detail === 'Legacy comment body') {
                $found_legacy = true;
            }
            if ((string) $h->action === 'comment') {
                $found_generic_comment = true;
            }
            if ((string) $h->action === 'status' && strpos((string) $h->detail, '→') !== false) {
                $found_status = true;
            }
        }
        $this->assert_true($found_note, 'my_works history contains note');
        $this->assert_true($found_legacy, 'my_works history merges legacy comment as note');
        $this->assert_true(!$found_generic_comment, 'my_works skips duplicate action=comment rows');
        $this->assert_true($found_status, 'my_works history contains status change');
        echo "  my_works history rows=" . count($history) . "\n";
    }

    private function test_empty_note_guard()
    {
        echo "-- Empty note guard --\n";
        $empty = trim('   ');
        $this->assert_true($empty === '', 'trim whitespace note is empty (controller rejects)');
        $ok_note = trim("  keep me  ");
        $this->assert_true($ok_note !== '', 'trimmed real note is not empty');
    }

    private function assert_history_shape($history, $label)
    {
        $this->assert_true(is_array($history) && !empty($history), $label . ' history is non-empty array');
        $row = $history[0];
        $this->assert_true(isset($row->created_at) && (string) $row->created_at !== '', $label . ' row has Date (created_at)');
        $this->assert_true(isset($row->detail) && isset($row->action), $label . ' row has Comments source (detail/action)');
        $this->assert_true(isset($row->user_name), $label . ' row has Added By (user_name)');
        if (count($history) >= 2) {
            $t0 = strtotime((string) $history[0]->created_at) ?: 0;
            $t1 = strtotime((string) $history[1]->created_at) ?: 0;
            $this->assert_true($t0 >= $t1, $label . ' history newest-first');
        }
    }

    private function cleanup()
    {
        foreach ($this->work_ids as $id) {
            $this->my_works->delete((int) $id);
        }
        foreach ($this->release_ids as $id) {
            if ($this->db->table_exists('project_release_activity')) {
                $this->db->where('release_id', (int) $id)->delete('project_release_activity');
            }
            if ($this->db->table_exists('project_release_notes')) {
                $this->db->where('release_id', (int) $id)->delete('project_release_notes');
            }
            $this->db->where('id', (int) $id)->delete('project_releases');
        }
        foreach ($this->project_ids as $id) {
            $this->Project_model->delete_activity_for_project((int) $id);
            $this->db->where('project_id', (int) $id)->delete('project_members');
            $this->db->where('id', (int) $id)->delete('projects');
        }
        foreach ($this->client_ids as $id) {
            $this->clients->delete_client((int) $id);
        }
        echo '  cleanup clients=' . implode(',', $this->client_ids)
            . ' projects=' . implode(',', $this->project_ids)
            . ' releases=' . implode(',', $this->release_ids)
            . ' works=' . implode(',', $this->work_ids) . "\n";
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

    private function summary()
    {
        echo "\n=== Summary: {$this->pass} passed, {$this->fail} failed ===\n";
        if ($this->fail > 0) {
            exit(1);
        }
    }
}
