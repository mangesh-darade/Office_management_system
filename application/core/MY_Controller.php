<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application base controller — CI3 auto-loads MY_Controller before any app controller.
 * Coaching admin controllers extend Coaching_Controller (loaded here).
 */
if (!class_exists('Coaching_Controller', FALSE)) {
    require_once APPPATH . 'core/Coaching_Controller.php';
}

class MY_Controller extends CI_Controller {
}
