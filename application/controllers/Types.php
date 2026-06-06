<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Legacy /types URLs redirect to Settings → Module Types.
 */
class Types extends CI_Controller
{
    public function index()
    {
        redirect('settings/types' . ($this->input->server('QUERY_STRING') ? '?' . $this->input->server('QUERY_STRING') : ''));
    }

    public function create()
    {
        redirect('settings/types/create');
    }

    public function view($id)
    {
        redirect('settings/types/' . (int) $id . '/edit');
    }

    public function show($id)
    {
        $this->view($id);
    }

    public function edit($id)
    {
        redirect('settings/types/' . (int) $id . '/edit');
    }

    public function delete($id)
    {
        redirect('settings/types');
    }
}
