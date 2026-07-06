<?php
/**
 * CLI smoke test: module types + statuses in DB.
 * Usage: php tools/test_module_status_types.php
 */
$_SERVER['CI_ENV'] = 'development';
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require APPPATH . 'config/database.php';
require APPPATH . 'helpers/types_helper.php';
require APPPATH . 'helpers/module_status_helper.php';

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

class ModuleStatusTestDb {
    public $conn;
    private $_where = array();
    private $_table = '';
    private $_order = array();
    public function __construct($mysqli) { $this->conn = $mysqli; }
    public function table_exists($t) {
        $t = $this->conn->real_escape_string($t);
        $r = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $r && $r->num_rows > 0;
    }
    public function select($cols) { return $this; }
    public function from($table) { $this->_table = $table; return $this; }
    public function where($col, $val) { $this->_where[] = array($col, $val); return $this; }
    public function order_by($col, $dir) { $this->_order = array($col, $dir); return $this; }
    public function get($table = null) {
        $table = $table ?: $this->_table;
        $sql = "SELECT * FROM `{$table}` WHERE 1=1";
        foreach ($this->_where as $w) {
            $sql .= " AND `{$w[0]}` = '" . $this->conn->real_escape_string((string) $w[1]) . "'";
        }
        if (!empty($this->_order)) {
            $sql .= " ORDER BY `{$this->_order[0]}` {$this->_order[1]}";
        }
        $this->_where = array();
        $r = $this->conn->query($sql);
        $rows = array();
        if ($r) {
            while ($row = $r->fetch_object()) {
                $rows[] = $row;
            }
        }
        return new ModuleStatusTestResult($rows);
    }
}

class ModuleStatusTestResult {
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function result() { return $this->rows; }
}

$GLOBALS['CI'] = new stdClass();
$GLOBALS['CI']->db = new ModuleStatusTestDb($mysqli);
$GLOBALS['CI']->module_types = new class($mysqli) {
    private $db;
    public function __construct($mysqli) { $this->db = new ModuleStatusTestDb($mysqli); }
    public function options_for_module($module) {
        $options = array();
        if (!$this->db->table_exists('module_types')) {
            return $options;
        }
        $this->db->select('*')->from('module_types')->where('module', $module)->where('is_active', 1);
        $this->db->order_by('display_order', 'ASC');
        foreach ($this->db->get('module_types')->result() as $row) {
            $options[(string) $row->code] = (string) $row->name;
        }
        return $options;
    }
};
$GLOBALS['CI']->module_statuses = new class($mysqli) {
    private $db;
    public function __construct($mysqli) { $this->db = new ModuleStatusTestDb($mysqli); }
    public function get_by_type($type, $active_only = true) {
        $this->db->select('*')->from('statuses')->where('type', $type);
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('display_order', 'ASC');
        return $this->db->get('statuses')->result();
    }
    public function seed_module_statuses_if_missing($type, array $definitions) {}
};
$GLOBALS['CI']->load = new class($GLOBALS['CI']) {
    private $ci;
    public function __construct($ci) { $this->ci = $ci; }
    public function model($name, $alias = null) {
        if ($alias === 'module_types' || $name === 'Type_model') {
            $this->ci->module_types = $this->ci->module_types;
            return;
        }
        $key = $alias ?: 'module_statuses';
        $this->ci->$key = $this->ci->module_statuses;
    }
    public function helper($name) {}
};
if (!function_exists('get_instance')) {
    function &get_instance() { return $GLOBALS['CI']; }
}

$failed = 0;

echo "=== Module Types table ===\n";
$r = $mysqli->query("SHOW TABLES LIKE 'module_types'");
if (!$r || $r->num_rows === 0) {
    echo "[WARN] module_types table missing — forms use fallback definitions\n";
} else {
    $mods = array('requirements', 'clients', 'projects', 'my_works', 'employees');
    foreach ($mods as $mod) {
        $modEsc = $mysqli->real_escape_string($mod);
        $q = $mysqli->query("SELECT COUNT(*) AS c FROM module_types WHERE module = '{$modEsc}' AND is_active = 1");
        $row = $q ? $q->fetch_assoc() : array('c' => 0);
        $dbCount = (int) $row['c'];
        $fallback = module_type_fallback_options($mod);
        $fallbackCount = count($fallback);
        $resolved = module_type_options_resolved($mod);
        $resolvedCount = count($resolved);
        $ok = $resolvedCount > 0;
        echo sprintf("[%s] %s — db:%d fallback:%d resolved:%d\n", $ok ? 'OK' : 'FAIL', $mod, $dbCount, $fallbackCount, $resolvedCount);
        if (!$ok) {
            $failed++;
        }
    }
}

echo "\n=== Statuses table ===\n";
$r = $mysqli->query("SHOW TABLES LIKE 'statuses'");
if (!$r || $r->num_rows === 0) {
    echo "[FAIL] statuses table missing\n";
    exit(1);
}

$statusModules = array('requirements', 'projects', 'tasks', 'my_works', 'clients', 'leaves', 'defects', 'releases');
$now = date('Y-m-d H:i:s');

foreach ($statusModules as $mod) {
    $defs = module_status_fallback_definitions($mod);
    foreach ($defs as $def) {
        $codeEsc = $mysqli->real_escape_string($def['code']);
        $modEsc = $mysqli->real_escape_string($mod);
        $exists = $mysqli->query("SELECT id FROM statuses WHERE code = '{$codeEsc}' AND type = '{$modEsc}' LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            continue;
        }
        $nameEsc = $mysqli->real_escape_string($def['name']);
        $colorEsc = $mysqli->real_escape_string(isset($def['color']) ? $def['color'] : '#6c757d');
        $iconEsc = $mysqli->real_escape_string(isset($def['icon']) ? $def['icon'] : '');
        $order = (int) (isset($def['display_order']) ? $def['display_order'] : 0);
        $mysqli->query("INSERT INTO statuses (name, code, type, color, icon, display_order, is_active, created_at, updated_at)
            VALUES ('{$nameEsc}', '{$codeEsc}', '{$modEsc}', '{$colorEsc}', " . ($iconEsc !== '' ? "'{$iconEsc}'" : 'NULL') . ", {$order}, 1, '{$now}', '{$now}')");
    }
    $modEsc = $mysqli->real_escape_string($mod);
    $q = $mysqli->query("SELECT COUNT(*) AS c FROM statuses WHERE type = '{$modEsc}' AND is_active = 1");
    $row = $q ? $q->fetch_assoc() : array('c' => 0);
    $count = (int) $row['c'];
    $expected = count($defs);
    $ok = $count >= $expected;
    echo sprintf("[%s] %s — %d active (expected >= %d)\n", $ok ? 'OK' : 'FAIL', $mod, $count, $expected);
    if (!$ok) {
        $failed++;
    }
}

echo "\n=== Validation helpers ===\n";

$checks = array(
    array('clients', 'active', true),
    array('clients', 'invalid_x', false),
    array('leaves', 'pending', true),
    array('defects', 'open', true),
    array('releases', 'released', true),
);
foreach ($checks as $c) {
    $valid = module_status_is_valid($c[1], $c[0]);
    $ok = ($valid === $c[2]);
    echo sprintf("[%s] module_status_is_valid('%s', '%s') => %s\n", $ok ? 'OK' : 'FAIL', $c[1], $c[0], $valid ? 'true' : 'false');
    if (!$ok) {
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "\n{$failed} check(s) failed.\n");
    exit(1);
}

echo "\nAll checks passed.\n";
exit(0);
