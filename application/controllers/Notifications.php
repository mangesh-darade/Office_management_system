<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->model('Notification_model');
    }

    public function index() {
        $this->load->view('notifications/index');
    }
}
