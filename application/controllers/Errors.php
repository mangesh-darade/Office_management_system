<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Custom 403 Forbidden page
     */
    public function permission_denied() {
        $this->output->set_status_header(403);
        $this->load->view('errors/permission_denied');
    }
    
    /**
     * Custom 404 Not Found page
     */
    public function page_missing() {
        $this->output->set_status_header(404);
        $this->load->view('errors/page_missing');
    }
}
