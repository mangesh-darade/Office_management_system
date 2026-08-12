<?php
/**
 * Repair leave_types + leave_requests: remove duplicate rows, add PRIMARY KEY + AUTO_INCREMENT.
 * Fixes: My Leaves showing 3x rows (leave_types JOIN) and apply email skipped (insert_id=0).
 */
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}
define('BASEPATH', true);
require dirname(__DIR__, 2) . '/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) {
    fwrite(STDERR, $m->connect_error . "\n");
    exit(1);
}
$m->set_charset('utf8mb4');

function step($msg)
{
    echo $msg . "\n";
}

function has_primary($m, $table)
{
    $r = $m->query("SHOW INDEX FROM `{$table}` WHERE Key_name = 'PRIMARY'");
    return $r && $r->num_rows > 0;
}

function has_column($m, $table, $col)
{
    $r = $m->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $m->real_escape_string($col) . "'");
    return $r && $r->num_rows > 0;
}

step('=== 1) leave_types: dedupe by name ===');
if (has_column($m, 'leave_types', '_tmp_pk')) {
    $m->query('ALTER TABLE leave_types DROP COLUMN `_tmp_pk`');
}
$m->query('ALTER TABLE leave_types ADD COLUMN `_tmp_pk` INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE FIRST');
$ok = $m->query("DELETE t1 FROM leave_types t1
  INNER JOIN leave_types t2
    ON LOWER(TRIM(t1.name)) = LOWER(TRIM(t2.name))
   AND t1._tmp_pk > t2._tmp_pk");
step($ok ? 'leave_types dups removed' : ('delete fail: ' . $m->error));
$m->query('ALTER TABLE leave_types DROP COLUMN `_tmp_pk`');

if (!has_primary($m, 'leave_types')) {
    $ok = $m->query('ALTER TABLE leave_types MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
    step($ok ? 'leave_types PK+AI added' : ('PK fail: ' . $m->error));
} else {
    $m->query('ALTER TABLE leave_types MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
    step('leave_types AI ensured');
}

step('leave_types count=' . (int) $m->query('SELECT COUNT(*) c FROM leave_types')->fetch_assoc()['c']);
$q = $m->query('SELECT id, name FROM leave_types ORDER BY id');
while ($r = $q->fetch_assoc()) {
    step("  {$r['id']} {$r['name']}");
}

step('=== 2) leave_requests: dedupe exact copies ===');
if (has_column($m, 'leave_requests', '_tmp_pk')) {
    $m->query('ALTER TABLE leave_requests DROP COLUMN `_tmp_pk`');
}
$m->query('ALTER TABLE leave_requests ADD COLUMN `_tmp_pk` INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE FIRST');
$ok = $m->query("DELETE t1 FROM leave_requests t1
  INNER JOIN leave_requests t2
    ON t1.user_id = t2.user_id
   AND t1.type_id = t2.type_id
   AND t1.start_date = t2.start_date
   AND t1.end_date = t2.end_date
   AND IFNULL(t1.created_at,'') = IFNULL(t2.created_at,'')
   AND IFNULL(t1.reason,'') = IFNULL(t2.reason,'')
   AND t1._tmp_pk > t2._tmp_pk");
step($ok ? 'leave_requests dups removed' : ('delete fail: ' . $m->error));

// Assign unique ids to rows stuck at id=0 (or colliding ids)
$max = (int) $m->query('SELECT COALESCE(MAX(id),0) m FROM leave_requests WHERE id > 0')->fetch_assoc()['m'];
$q = $m->query('SELECT _tmp_pk, id FROM leave_requests ORDER BY _tmp_pk');
$seen = array();
$updates = array();
while ($r = $q->fetch_assoc()) {
    $tmp = (int) $r['_tmp_pk'];
    $id = (int) $r['id'];
    if ($id < 1 || isset($seen[$id])) {
        $max++;
        $updates[$tmp] = $max;
        $seen[$max] = true;
    } else {
        $seen[$id] = true;
    }
}
foreach ($updates as $tmp => $newId) {
    $m->query('UPDATE leave_requests SET id = ' . (int) $newId . ' WHERE _tmp_pk = ' . (int) $tmp);
    step("reassigned tmp={$tmp} -> id={$newId}");
}
$m->query('ALTER TABLE leave_requests DROP COLUMN `_tmp_pk`');

$m->query("ALTER TABLE leave_requests
  MODIFY `status` ENUM('pending','lead_approved','hr_approved','approved','rejected','cancelled')
  NOT NULL DEFAULT 'pending'");

if (!has_primary($m, 'leave_requests')) {
    $ok = $m->query('ALTER TABLE leave_requests MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
    step($ok ? 'leave_requests PK+AI added' : ('PK fail: ' . $m->error));
} else {
    $m->query('ALTER TABLE leave_requests MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
    step('leave_requests AI ensured');
}

$ai = $max + 1;
$m->query('ALTER TABLE leave_requests AUTO_INCREMENT = ' . (int) $ai);
step("AUTO_INCREMENT={$ai}");

if ($m->query("SHOW TABLES LIKE 'leave_approvals'")->num_rows) {
    $m->query('DELETE FROM leave_approvals WHERE leave_id = 0 OR leave_id IS NULL');
    step('cleaned leave_approvals with leave_id=0');
}

step('=== 3) verify ===');
step('types=' . $m->query('SELECT COUNT(*) c FROM leave_types')->fetch_assoc()['c']
    . ' distinct=' . $m->query('SELECT COUNT(DISTINCT name) c FROM leave_types')->fetch_assoc()['c']);
step('requests=' . $m->query('SELECT COUNT(*) c FROM leave_requests')->fetch_assoc()['c']
    . ' distinct_ids=' . $m->query('SELECT COUNT(DISTINCT id) c FROM leave_requests')->fetch_assoc()['c']);

$q = $m->query("SELECT lr.id, lt.name, lr.start_date, lr.end_date, lr.status, lr.created_at
  FROM leave_requests lr
  LEFT JOIN leave_types lt ON lt.id = lr.type_id
  WHERE lr.user_id = 1
  ORDER BY lr.created_at DESC");
step('user1 My Leaves join:');
while ($r = $q->fetch_assoc()) {
    step(json_encode($r));
}

$cr = $m->query('SHOW CREATE TABLE leave_requests')->fetch_array()[1];
step(strpos($cr, 'AUTO_INCREMENT') !== false && strpos($cr, 'PRIMARY KEY') !== false
    ? 'SCHEMA_OK leave_requests'
    : 'SCHEMA_BAD leave_requests');
$cr2 = $m->query('SHOW CREATE TABLE leave_types')->fetch_array()[1];
step(strpos($cr2, 'AUTO_INCREMENT') !== false && strpos($cr2, 'PRIMARY KEY') !== false
    ? 'SCHEMA_OK leave_types'
    : 'SCHEMA_BAD leave_types');
