<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('log_activity')) {
    function log_activity($module, $action, $record_id = null, $description = ''){
        $CI =& get_instance();
        $CI->load->library('Activity_logger');
        $CI->activity_logger->log($module, $action, $record_id, $description);
    }
}
