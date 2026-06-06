<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * In-app user guide — readable help with screenshots for all modules.
 */
class Guide extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'user_guide', 'permission']);
        $this->load->library('session');

        if (!(int) $this->session->userdata('user_id')) {
            redirect('login');
            return;
        }

        require_module_access('guide', true);
    }

    public function index()
    {
        $this->load->view('guide/index', [
            'title'   => 'User Guide',
            'modules' => user_guide_modules(),
        ]);
    }

    public function module($slug = '')
    {
        $module = user_guide_module_by_slug($slug);
        if ($module === null) {
            show_404();
            return;
        }

        $content = user_guide_render_module($module['file']);
        if ($content === null) {
            show_404();
            return;
        }

        $this->load->view('guide/module', [
            'title'    => 'User Guide — ' . $module['title'],
            'module'   => $module,
            'modules'  => user_guide_modules(),
            'content'  => $content,
        ]);
    }
}
