<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified master CSV import — trainings, topics, assignments, assessments, questions.
 */
class Training_import extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('training_csv_import', null, 'csv_import');
        $this->load->helper(array('url', 'form', 'permission', 'training'));

        if (!training_can_import()) {
            $this->session->set_flashdata('access_denied', 'You do not have access to CSV import.');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('training/import', array(
            'ready' => $this->csv_import->type_ready('all'),
        ));
    }

    public function sample($type = 'all')
    {
        $type = trim((string) $type);
        if ($type === '') {
            $type = 'all';
        }
        if ($type !== 'all' || !$this->csv_import->type_ready('all')) {
            show_404();
        }
        $this->csv_import->stream_sample('all');
    }

    public function process()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Please choose a CSV file.');
            redirect('training/import');
        }
        if (!empty($_FILES['csv_file']['error']) && (int) $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'Upload failed (error code ' . (int) $_FILES['csv_file']['error'] . ').');
            redirect('training/import');
        }
        if (!empty($_FILES['csv_file']['size']) && (int) $_FILES['csv_file']['size'] > 5242880) {
            $this->session->set_flashdata('error', 'File too large (max 5 MB).');
            redirect('training/import');
        }

        $result = $this->csv_import->process_upload(
            'all',
            $_FILES['csv_file']['tmp_name'],
            (int) $this->session->userdata('user_id')
        );

        if (!$result['success']) {
            $msg = $result['message'];
            if (!empty($result['errors'])) {
                $msg .= ' ' . implode(' ', array_slice($result['errors'], 0, 5));
                if (count($result['errors']) > 5) {
                    $msg .= ' …';
                }
            }
            $this->session->set_flashdata('error', $msg);
            redirect('training/import');
        }

        $flash = $result['message'];
        if (!empty($result['errors'])) {
            $flash .= ' Warnings: ' . implode(' ', array_slice($result['errors'], 0, 3));
        }
        $this->session->set_flashdata('success', $flash);
        redirect('training/import');
    }
}
