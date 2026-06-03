<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared bootstrap for coaching admin controllers (matches OMS RBAC + model load pattern).
 */
class Coaching_Controller extends CI_Controller {

    /** @var string Permission key passed to coaching_require_access() */
    protected $coaching_permission = 'coaching';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'form', 'permission', 'coaching'));
        $this->load->library('session');
        $this->load->database();
        $this->load->model('Coaching_model', 'coaching');

        if (!$this->coaching_skip_access()) {
            coaching_require_access($this->coaching_permission);
        }
    }

    /**
     * Override in controllers with public actions (e.g. workshop_register).
     *
     * @return bool
     */
    protected function coaching_skip_access()
    {
        return false;
    }

    /**
     * Load CRM client picker data when clients module is available.
     *
     * @return array
     */
    protected function coaching_crm_client_options()
    {
        return coaching_crm_clients_for_select();
    }
}
