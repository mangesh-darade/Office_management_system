<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Legacy default controller — redirects to auth (default route is auth/index).
 */
class Welcome extends CI_Controller {

    public function index()
    {
        redirect('auth');
    }
}
