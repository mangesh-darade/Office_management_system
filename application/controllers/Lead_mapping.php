<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lead_mapping extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission', 'hierarchy_filter']);
        $this->load->library(['session']);
        $this->load->model('Lead_user_mapping_model', 'leadMap');

        // Permission-based access (manageable from Permission Manager).
        require_module_access('lead_mapping', true);
    }

    public function index()
    {
        $leads = $this->db->select('id, name, email')
            ->from('users')
            ->where('role_id', ROLE_LEAD)
            ->order_by('name', 'ASC')
            ->get()
            ->result();

        $users = $this->db->select('id, name, email')
            ->from('users')
            ->where('role_id', ROLE_STAFF)
            ->order_by('name', 'ASC')
            ->get()
            ->result();

        $mappings = $this->leadMap->get_all_grouped();
        $this->load->view('lead_mapping/index', [
            'leads' => $leads,
            'users' => $users,
            'mappings' => $mappings,
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $map = $this->input->post('map');
        if (!is_array($map)) { $map = []; }

        foreach ($map as $lead_id => $user_ids) {
            $lead_id = (int)$lead_id;
            $safe_user_ids = is_array($user_ids) ? array_map('intval', $user_ids) : [];
            $this->leadMap->save_mapping($lead_id, $safe_user_ids);
        }

        $this->session->set_flashdata('success', 'Lead-user mapping saved successfully.');
        redirect('lead-mapping');
    }
}
