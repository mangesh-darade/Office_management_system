<?php
define('BASEPATH', 'c:/wamp64/www/Office_management_system/system/');
define('ENVIRONMENT', 'development');
$_SERVER['CI_ENV'] = 'development';
chdir('c:/wamp64/www/Office_management_system');
require 'index.php';
$CI =& get_instance();
$CI->load->model('Reward_model', 'rewards');
$q = $CI->rewards->get_approval_queue(5);
echo 'queue5 status=' . ($q ? $q->status : 'null') . ' module=' . ($q ? $q->source_module : '') . "\n";
echo 'approve_pending=' . ($CI->rewards->approve_pending(5, 1, '') ? 'true' : 'false') . "\n";
$q = $CI->rewards->get_approval_queue(4);
echo 'queue4 status=' . ($q ? $q->status : 'null') . "\n";
// simulate spl controller check for id 3 tasks module
$q3 = $CI->rewards->get_approval_queue(3);
echo 'queue3 module=' . $q3->source_module . ' status=' . $q3->status . "\n";
