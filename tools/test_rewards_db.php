<?php
/**
 * CLI smoke test for rewards DB schema and rule seed.
 * Usage: php tools/test_rewards_db.php
 */
$_SERVER['CI_ENV'] = 'development';
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require APPPATH . 'config/database.php';
$dbConfig = $db['default'];

$mysqli = @new mysqli(
    $dbConfig['hostname'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database']
);

if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$tables = array(
    'reward_rules',
    'reward_transactions',
    'reward_approval_queue',
    'office_closing_submissions',
    'meal_calendar',
    'meal_week_menu',
    'meal_orders',
);

$fail = 0;
foreach ($tables as $t) {
    $res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
    if ($res && $res->num_rows > 0) {
        echo "[OK] table {$t}\n";
    } else {
        echo "[FAIL] missing table {$t}\n";
        $fail++;
    }
}

$res = $mysqli->query('SELECT COUNT(*) AS c FROM reward_rules WHERE is_active=1');
$row = $res ? $res->fetch_assoc() : null;
$count = $row ? (int) $row['c'] : 0;
echo $count >= 40 ? "[OK] {$count} active rules\n" : "[WARN] only {$count} active rules\n";

$checks = array(
    'self_work_update_submitted' => 20,
    'checkout_submitted' => 10,
    'cheer_received' => 20,
    'major_release' => 100,
);
foreach ($checks as $code => $pts) {
    $stmt = $mysqli->prepare('SELECT points FROM reward_rules WHERE code=? AND is_active=1 LIMIT 1');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) {
        echo "[FAIL] missing rule {$code}\n";
        $fail++;
    } elseif ((float) $r['points'] != $pts) {
        echo "[FAIL] rule {$code} points={$r['points']} expected {$pts}\n";
        $fail++;
    } else {
        echo "[OK] rule {$code} = {$pts} pts\n";
    }
    $stmt->close();
}

$mysqli->close();
exit($fail > 0 ? 1 : 0);
