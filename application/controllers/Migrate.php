<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // Command line only for safety
        if (!is_cli()) {
            show_error('Migrations can only be run from the command line.');
        }
        $this->load->library('migration');
    }

    public function index()
    {
        if ($this->migration->current() === FALSE)
        {
            show_error($this->migration->error_string());
        } else {
            echo "Migration successful.\n";
        }
    }
}
