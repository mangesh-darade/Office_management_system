<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_coaches extends Coaching_Controller {

    protected $coaching_permission = 'coaching_coaches';

    public function index()
    {
        $this->load->view('coaching/coaches/index', [
            'rows' => $this->coaching->coaches_all(false),
        ]);
    }

    public function create()
    {
        if ($this->input->method() === 'post') {
            return $this->_save();
        }
        $this->load->view('coaching/coaches/form', [
            'row' => null,
            'users' => $this->_available_users(),
        ]);
    }

    public function edit($id)
    {
        $row = $this->coaching->coach_get($id);
        if (!$row) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            return $this->_save((int) $id);
        }
        $this->load->view('coaching/coaches/form', [
            'row' => $row,
            'users' => $this->_available_users((int) $row->user_id),
        ]);
    }

    public function delete($id)
    {
        $this->coaching->coach_save(['status' => 'inactive'], (int) $id);
        $this->session->set_flashdata('success', 'Coach deactivated.');
        redirect('coaching-coaches');
    }

    private function _save($id = null)
    {
        $user_id = (int) $this->input->post('user_id');
        if ($user_id <= 0) {
            $this->session->set_flashdata('error', 'Select a user.');
            redirect($id ? 'coaching-coaches/edit/' . $id : 'coaching-coaches/create');
            return;
        }
        $existing = $this->coaching->coach_by_user($user_id);
        if ($existing && (!$id || (int) $existing->id !== (int) $id)) {
            $this->session->set_flashdata('error', 'User is already a coach.');
            redirect($id ? 'coaching-coaches/edit/' . $id : 'coaching-coaches/create');
            return;
        }
        $data = [
            'user_id' => $user_id,
            'title' => trim((string) $this->input->post('title')),
            'bio' => trim((string) $this->input->post('bio')),
            'hourly_rate' => (float) $this->input->post('hourly_rate'),
            'commission_pct' => (float) $this->input->post('commission_pct'),
            'status' => $this->input->post('status') === 'inactive' ? 'inactive' : 'active',
        ];
        $this->coaching->coach_save($data, $id);
        $this->session->set_flashdata('success', $id ? 'Coach updated.' : 'Coach created.');
        redirect('coaching-coaches');
    }

    private function _available_users($include_user_id = null)
    {
        $coach_user_ids = array_map(function ($c) {
            return (int) $c->user_id;
        }, $this->coaching->coaches_all(false));

        $this->db->select('id, name, email');
        $this->db->from('users');
        $this->db->where('status', 1);
        if ($coach_user_ids) {
            $this->db->group_start();
            $this->db->where_not_in('id', $coach_user_ids);
            if ($include_user_id) {
                $this->db->or_where('id', (int) $include_user_id);
            }
            $this->db->group_end();
        }
        $this->db->where('role_id !=', ROLE_COACHING_CLIENT);
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }
}
