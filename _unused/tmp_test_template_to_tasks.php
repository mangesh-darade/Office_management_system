<?php
/**
 * Dummy-data test: Create Template Task → inserts into tasks (not my_works).
 * Run: php _unused/tmp_test_template_to_tasks.php
 * Cleans up the inserted dummy row after assertions.
 */
$mysqli = @new mysqli('localhost', 'root', '', 'admin_stadmin_internal_portal');
if ($mysqli->connect_error) {
    fwrite(STDERR, 'DB connect failed: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$fail = 0;
function assert_true($cond, $msg) {
    global $fail;
    if ($cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
        $fail++;
    }
}

$tpl = $mysqli->query("SELECT id, team, template_type, title, estimate_hours FROM template_tasks WHERE is_active=1 ORDER BY id ASC LIMIT 1")->fetch_assoc();
assert_true(!empty($tpl), 'template_tasks catalog has at least one row');

$prj = $mysqli->query("SELECT id, name FROM projects ORDER BY id ASC LIMIT 1")->fetch_assoc();
assert_true(!empty($prj), 'projects has at least one row');

$user = $mysqli->query("SELECT MIN(id) AS id FROM users")->fetch_assoc();
$user_id = (int) $user['id'];
assert_true($user_id > 0, 'users has at least one row');

$title = (string) $tpl['title'];
$marker = '[DUMMY_TEMPLATE_TASK_' . date('YmdHis') . ']';
$desc = $marker . ' Dummy description from automated test.';
$due = date('Y-m-d', strtotime('+7 days'));
$est = $tpl['estimate_hours'] !== null && $tpl['estimate_hours'] !== '' ? (float) $tpl['estimate_hours'] : 1.5;

$stmt = $mysqli->prepare(
    "INSERT INTO tasks (project_id, title, description, assigned_to, created_by, status, priority, due_date, estimate_hours, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 'pending', 'medium', ?, ?, NOW(), NOW())"
);
$project_id = (int) $prj['id'];
$stmt->bind_param('issiisd', $project_id, $title, $desc, $user_id, $user_id, $due, $est);
$ok = $stmt->execute();
assert_true($ok, 'INSERT into tasks succeeded');
$task_id = (int) $mysqli->insert_id;
assert_true($task_id > 0, 'tasks.insert_id > 0 (id=' . $task_id . ')');

$row = $mysqli->query("SELECT * FROM tasks WHERE id=" . $task_id)->fetch_assoc();
assert_true($row && $row['title'] === $title, 'saved title matches template title');
assert_true((int) $row['project_id'] === $project_id, 'saved project_id matches');
assert_true($row['status'] === 'pending', 'status is pending');
assert_true($row['priority'] === 'medium', 'priority is medium');
assert_true((int) $row['assigned_to'] === $user_id, 'assigned_to set');
assert_true((int) $row['created_by'] === $user_id, 'created_by set');
assert_true(strpos((string) $row['description'], $marker) !== false, 'description contains marker');

$mw = $mysqli->query("SELECT COUNT(*) c FROM my_works WHERE title='" . $mysqli->real_escape_string($title) . "' AND details LIKE '%" . $mysqli->real_escape_string($marker) . "%'");
$mw_count = (int) $mw->fetch_assoc()['c'];
assert_true($mw_count === 0, 'no matching row created in my_works');

// Cleanup dummy task
$mysqli->query("DELETE FROM task_attachments WHERE task_id=" . $task_id);
$mysqli->query("DELETE FROM tasks WHERE id=" . $task_id);
$gone = $mysqli->query("SELECT COUNT(*) c FROM tasks WHERE id=" . $task_id)->fetch_assoc();
assert_true((int) $gone['c'] === 0, 'dummy task cleaned up');

echo "\nDone. failures=$fail\n";
exit($fail > 0 ? 1 : 0);
