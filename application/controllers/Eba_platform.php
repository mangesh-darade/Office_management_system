<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Eba_platform extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'permission'));
        $this->load->library(array('session'));
        require_controller_access('eba_platform', true);
    }

    public function index()
    {
        require_module_access(array('eba_platform', 'eba_platform_list'), true);
        $this->load->view('eba_platform/index');
    }
}
