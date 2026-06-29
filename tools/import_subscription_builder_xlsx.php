<?php
/**
 * CLI import for Subscription Builder XLSX.
 * Usage: php tools/import_subscription_builder_xlsx.php "path/to/file.xlsx" [--replace]
 */
$_SERVER['CI_ENV'] = 'development';
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require_once BASEPATH . 'core/Common.php';

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
    }
}

require BASEPATH . 'database/DB.php';

$db = array();
require APPPATH . 'config/database.php';
$active_db = DB($db['default'], true);

require BASEPATH . 'core/Model.php';

class Import_CI_Controller
{
    public static $instance;
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new Import_CI_Loader();
        self::$instance = $this;
    }
}

class Import_CI_Loader
{
    public function helper($name)
    {
        $file = APPPATH . 'helpers/' . $name . '_helper.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    public function model($name, $alias = null)
    {
        if ($alias === null) {
            $alias = $name;
        }

        if (!class_exists('CI_Model', false)) {
            require BASEPATH . 'core/Model.php';
        }

        $path = APPPATH . 'models/' . $name . '.php';
        if (!is_file($path)) {
            return;
        }

        require_once $path;
        $CI = get_instance();
        $CI->$alias = new $name();
    }
}

function &get_instance()
{
    return Import_CI_Controller::$instance;
}

new Import_CI_Controller($active_db);

require APPPATH . 'helpers/schema_columns_helper.php';
require APPPATH . 'helpers/subscription_builder_schema_helper.php';
require APPPATH . 'helpers/subscription_builder_countries_schema_helper.php';
subscription_builder_schema_ensure($active_db);
subscription_builder_countries_schema_ensure($active_db);

require APPPATH . 'helpers/subscription_builder_import_helper.php';
require APPPATH . 'models/Subscription_builder_model.php';
require APPPATH . 'models/Subscription_builder_countries_model.php';

$file = '';
$replace = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--replace') {
        $replace = true;
        continue;
    }
    if ($file === '') {
        $file = $arg;
    }
}

if ($file === '') {
    $file = FCPATH . 'database/subscription_builder_import_user.xlsx';
}

if (!is_file($file)) {
    fwrite(STDERR, "File not found: {$file}\n");
    exit(1);
}

$grid = subscription_builder_parse_xlsx_file($file);
if (empty($grid)) {
    fwrite(STDERR, "Unable to parse XLSX.\n");
    exit(1);
}

$model = new Subscription_builder_model();
$parsed = $model->parse_import_grid($grid);
echo 'Parsed rows: ' . count($parsed) . PHP_EOL;

$sample = array_slice($parsed, 727, 3);
foreach ($sample as $i => $row) {
    echo 'Sample ' . ($i + 1) . ': ' . json_encode($row) . PHP_EOL;
}

$inserted = $model->import_parsed_rows($parsed, $replace);
echo 'Imported rows: ' . $inserted . ($replace ? ' (replaced all)' : '') . PHP_EOL;
