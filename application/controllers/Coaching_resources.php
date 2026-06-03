<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_resources extends Coaching_Controller {

    protected $coaching_permission = 'coaching_resources';

    public function index()
    {
        $client_id = $this->input->get('client_id') ? (int) $this->input->get('client_id') : null;
        $this->load->view('coaching/resources/index', [
            'rows' => $this->coaching->resources_list($client_id),
            'clients' => $this->coaching->clients_all(),
            'client_id' => $client_id,
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $path = trim((string) $this->input->post('external_url'));
        if (!empty($_FILES['file']['name'])) {
            $upload_path = FCPATH . 'uploads/coaching/';
            if (!is_dir($upload_path)) {
                @mkdir($upload_path, 0755, true);
            }
            $config = [
                'upload_path' => $upload_path,
                'allowed_types' => 'pdf|doc|docx|ppt|pptx|xls|xlsx|jpg|jpeg|png|mp4|zip',
                'max_size' => 51200,
                'encrypt_name' => true,
            ];
            $this->load->library('upload', $config);
            if ($this->upload->do_upload('file')) {
                $up = $this->upload->data();
                $path = 'uploads/coaching/' . $up['file_name'];
            }
        }
        $this->coaching->resource_save([
            'coaching_client_id' => $this->input->post('coaching_client_id') ? (int) $this->input->post('coaching_client_id') : null,
            'title' => trim((string) $this->input->post('title')),
            'file_path' => strpos($path, 'uploads/') === 0 ? $path : null,
            'external_url' => filter_var($path, FILTER_VALIDATE_URL) ? $path : trim((string) $this->input->post('external_url')),
            'resource_type' => $this->input->post('resource_type') ?: 'document',
            'visible_to_client' => (int) $this->input->post('visible_to_client') ? 1 : 0,
            'created_by' => (int) $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Resource saved.');
        redirect('coaching-resources');
    }
}
