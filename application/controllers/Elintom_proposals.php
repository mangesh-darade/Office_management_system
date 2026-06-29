<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Elintom_proposals extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission'));
        $this->load->library(array('session'));
        require_controller_access('elintom_proposals', true);
        $this->ensure_schema();
        $this->load->model('Elintom_proposals_model', 'elintom_proposals');
    }

    private function ensure_schema()
    {
        $this->load->helper('elintom_proposals_schema');
        elintom_proposals_schema_ensure($this->db);
    }

    public function index()
    {
        require_module_access(array('elintom_proposals', 'elintom_proposals_list'), true);

        $page = max(1, (int) $this->input->get('page'));
        $per_page = 50;
        $total = $this->elintom_proposals->count_all();
        $total_pages = max(1, (int) ceil($total / $per_page));
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $per_page;

        $this->load->view('elintom_proposals/index', array(
            'rows' => $this->elintom_proposals->get_paginated($per_page, $offset),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
        ));
    }

    public function download($id)
    {
        require_module_access(array('elintom_proposals', 'elintom_proposals_list'), true);

        $row = $this->elintom_proposals->find((int) $id);
        if (!$row || empty($row->document_path)) {
            show_404();
        }

        $relative = ltrim(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, (string) $row->document_path), DIRECTORY_SEPARATOR);
        $absolute = FCPATH . $relative;
        if (!is_file($absolute) || !is_readable($absolute)) {
            show_404();
        }

        $filename = basename($absolute);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $download_name = preg_replace('/\.html?$/i', '.pdf', $filename);
        if (!preg_match('/\.pdf$/i', $download_name)) {
            $download_name .= '.pdf';
        }

        if ($ext === 'html' || $ext === 'htm') {
            $this->load->helper(array('dompdf_bootstrap', 'subscription_builder_quote'));
            $html = file_get_contents($absolute);
            $pdf_binary = dompdf_render_html($html, 'A4', 'portrait');
            if ($pdf_binary === false) {
                $this->session->set_flashdata('error', 'PDF engine is not available. Run composer install in the project root to enable PDF downloads.');
                redirect('elintom-proposals');
                return;
            }

            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="' . $download_name . '"')
                ->set_output($pdf_binary);
            return;
        }

        if ($ext === 'pdf') {
            $mime = 'application/pdf';
        } else {
            $mime = 'application/octet-stream';
        }

        $this->output
            ->set_content_type($mime)
            ->set_header('Content-Disposition: attachment; filename="' . $download_name . '"')
            ->set_output(file_get_contents($absolute));
    }
}
