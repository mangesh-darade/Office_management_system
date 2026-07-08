<?php
/**
 * CLI smoke test for Office Meals schema and settings.
 * Usage: php tools/test_meals_db.php
 */
$_SERVER['CI_ENV'] = 'development';
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require APPPATH . 'config/database.php';
require APPPATH . 'helpers/meal_schema_helper.php';
require APPPATH . 'helpers/meal_helper.php';

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

class MealDbStub {
    public $conn;
    public function __construct($mysqli) { $this->conn = $mysqli; }
    public function table_exists($t) {
        $t = $this->conn->real_escape_string($t);
        $r = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $r && $r->num_rows > 0;
    }
    public function query($sql) { return $this->conn->query($sql); }
    public function insert($table, $data) {
        $cols = implode(',', array_keys($data));
        $vals = implode(',', array_map(function ($v) {
            return is_null($v) ? 'NULL' : "'" . $this->conn->real_escape_string((string) $v) . "'";
        }, array_values($data)));
        return $this->conn->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})");
    }
    public function order_by($c, $d) { return $this; }
    public function limit($n) { return $this; }
    public function get($table) {
        $r = $this->conn->query("SELECT * FROM `{$table}` LIMIT 1");
        $row = $r ? $r->fetch_object() : null;
        return new class($row) {
            public $row;
            public function __construct($row) { $this->row = $row; }
            public function row() { return $this->row; }
        };
    }
    public function count_all($table) {
        $r = $this->conn->query("SELECT COUNT(*) AS c FROM `{$table}`");
        $row = $r->fetch_assoc();
        return (int) $row['c'];
    }
    public function list_fields($table) {
        $r = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        $f = array();
        while ($row = $r->fetch_assoc()) { $f[] = $row['Field']; }
        return $f;
    }
}

// Minimal CI stub for schema_columns
if (!function_exists('get_instance')) {
    function &get_instance() {
        static $ci;
        if (!$ci) {
            $ci = new stdClass();
            $ci->load = new class {
                public function helper($h) {
                    $path = APPPATH . 'helpers/schema_columns_helper.php';
                    if (file_exists($path)) require_once $path;
                }
            };
        }
        return $ci;
    }
}

$fail = 0;
$tables = array('meal_settings', 'meal_week_menu', 'meal_calendar', 'meal_orders', 'meal_order_log');
foreach ($tables as $t) {
    $res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
    if ($res && $res->num_rows > 0) {
        echo "[OK] table {$t}\n";
    } else {
        echo "[FAIL] missing table {$t} (run schema via web /meals)\n";
        $fail++;
    }
}

$res = $mysqli->query('SELECT breakfast_cutoff, lunch_cutoff, max_lunch_plates FROM meal_settings LIMIT 1');
if ($res && ($row = $res->fetch_assoc())) {
    $bf = substr($row['breakfast_cutoff'], 0, 5);
    $lu = substr($row['lunch_cutoff'], 0, 5);
    echo "[OK] cutoffs breakfast={$bf} lunch={$lu}\n";
    if (isset($row['max_lunch_plates'])) {
        echo "[OK] max_lunch_plates={$row['max_lunch_plates']}\n";
    } else {
        echo "[WARN] max_lunch_plates column missing — visit /meals/settings\n";
    }
} else {
    echo "[WARN] meal_settings empty — visit /meals once\n";
}

$res = $mysqli->query('SELECT COUNT(*) AS c FROM meal_week_menu');
if ($res) {
    $c = (int) $res->fetch_assoc()['c'];
    if ($c >= 7) {
        echo "[OK] meal_week_menu seeded ({$c} rows)\n";
    } else {
        echo "[WARN] meal_week_menu has {$c} rows — visit /meals/settings\n";
    }
} elseif ($fail === 0) {
    echo "[WARN] meal_week_menu table missing\n";
}

$mysqli->close();
exit($fail > 0 ? 1 : 0);
