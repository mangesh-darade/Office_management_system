<?php
$_SERVER['CI_ENV'] = 'development';
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require_once BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';

$db = array();
require APPPATH . 'config/database.php';
$active = DB($db['default'], true);

echo "Distinct countries in DB:\n";
$rows = $active->query('SELECT country, COUNT(*) AS c FROM subscription_builder GROUP BY country ORDER BY c DESC')->result();
foreach ($rows as $row) {
    echo '  ' . $row->country . ' => ' . $row->c . "\n";
}

require BASEPATH . 'core/Model.php';

class Check_CI_Controller
{
    public static $instance;
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new Check_CI_Loader();
        self::$instance = $this;
    }
}

class Check_CI_Loader
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
        require_once APPPATH . 'models/' . $name . '.php';
        $CI = get_instance();
        $CI->$alias = new $name();
    }
}

function &get_instance()
{
    return Check_CI_Controller::$instance;
}

new Check_CI_Controller($active);
require APPPATH . 'helpers/subscription_builder_schema_helper.php';
require APPPATH . 'helpers/subscription_builder_countries_schema_helper.php';
subscription_builder_schema_ensure($active);
subscription_builder_countries_schema_ensure($active);
require APPPATH . 'models/Subscription_builder_model.php';
require APPPATH . 'models/Subscription_builder_countries_model.php';

$model = new Subscription_builder_model();

foreach (array('India', 'UAE') as $sel) {
    $catalog = $model->get_catalog('Professional', 'Retail', $sel);
    echo "\n=== {$sel} chargeable (setup fees) ===\n";
    foreach ($catalog['chargeable'] as $row) {
        echo '  ' . $row['module'] . ' / ' . $row['feature'] . ' => ' . $row['common_set_up_fees'] . "\n";
    }
    echo "included: " . count($catalog['included']) . "\n";
}
