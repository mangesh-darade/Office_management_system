<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching extends Coaching_Controller {

    public function index()
    {
        $this->load->view('coaching/dashboard', [
            'stats' => $this->coaching->dashboard_stats(),
        ]);
    }
}
