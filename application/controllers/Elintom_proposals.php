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
            'can_export' => function_exists('has_module_access') && (
                has_module_access('elintom_proposals')
                || has_module_access('elintom_proposals_export')
                || has_module_access('elintom_proposals_list')
            ),
        ));
    }

    public function download($id)
    {
        $this->export($id, 'pdf');
    }

    public function export($id, $format = 'pdf')
    {
        require_module_access(array('elintom_proposals', 'elintom_proposals_export', 'elintom_proposals_list'), true);

        $format = strtolower(trim((string) $format));
        if (!in_array($format, array('pdf', 'excel', 'xls', 'doc', 'word'), true)) {
            $this->session->set_flashdata('error', 'Unsupported export format.');
            redirect('elintom-proposals');
            return;
        }
        if ($format === 'xls') {
            $format = 'excel';
        }
        if ($format === 'word') {
            $format = 'doc';
        }

        $row = $this->elintom_proposals->find((int) $id);
        if (!$row || empty($row->document_path)) {
            $this->session->set_flashdata('error', 'Proposal not found.');
            redirect('elintom-proposals');
            return;
        }

        $absolute = $this->resolve_absolute_path($row->document_path);
        $ext = ($absolute !== '') ? strtolower(pathinfo($absolute, PATHINFO_EXTENSION)) : '';
        $base_name = ($absolute !== '')
            ? pathinfo($absolute, PATHINFO_FILENAME)
            : ('elintom_proposal_' . (int) $id);
        $download_base = preg_replace('/[^a-z0-9._-]+/i', '_', $base_name);
        if ($download_base === '') {
            $download_base = 'elintom_proposal_' . (int) $id;
        }

        if ($format === 'pdf') {
            if ($absolute === '' || !is_readable($absolute)) {
                $this->session->set_flashdata(
                    'error',
                    'Proposal PDF file is missing on the server. Please re-save this proposal from Subscription Builder, then export again.'
                );
                redirect('elintom-proposals');
                return;
            }
            $this->send_proposal_pdf($absolute, $ext, $download_base . '.pdf');
            return;
        }

        $html = '';
        if ($absolute !== '') {
            $html = $this->resolve_proposal_html($absolute, $ext);
        }
        if ($html === '') {
            $html = $this->build_fallback_export_html($row);
        }

        $this->load->helper('subscription_builder_quote');
        if ($format === 'excel') {
            subscription_builder_quote_send_excel($html, $download_base . '.xls');
            return;
        }

        subscription_builder_quote_send_doc($html, $download_base . '.doc');
    }

    private function resolve_absolute_path($document_path)
    {
        $relative = ltrim(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, (string) $document_path), DIRECTORY_SEPARATOR);
        $absolute = FCPATH . $relative;
        if (!is_file($absolute)) {
            return '';
        }
        return $absolute;
    }

    private function resolve_proposal_html($absolute, $ext)
    {
        if ($ext === 'html' || $ext === 'htm') {
            $html = @file_get_contents($absolute);
            return is_string($html) ? $html : '';
        }

        if ($ext === 'pdf') {
            $sidecar = preg_replace('/\.pdf$/i', '.html', $absolute);
            if (is_string($sidecar) && is_file($sidecar) && is_readable($sidecar)) {
                $html = @file_get_contents($sidecar);
                return is_string($html) ? $html : '';
            }
        }

        return '';
    }

    private function build_fallback_export_html($row)
    {
        $client_name = esc_view(trim((string) ($row->client_name ?? '')), ENT_QUOTES, 'UTF-8');
        $client_business = esc_view(trim((string) ($row->client_business ?? '')), ENT_QUOTES, 'UTF-8');
        $created_at = !empty($row->created_at)
            ? esc_view(date('d M Y, h:i A', strtotime($row->created_at)), ENT_QUOTES, 'UTF-8')
            : '—';
        $doc = esc_view(basename((string) ($row->document_path ?? '')), ENT_QUOTES, 'UTF-8');
        $id = (int) $row->id;

        return '<html><head><meta charset="UTF-8"><title>ElintOm Proposal #' . $id . '</title></head><body>'
            . '<h2>ElintOm Proposal</h2>'
            . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">'
            . '<tr><th align="left">Proposal ID</th><td>' . $id . '</td></tr>'
            . '<tr><th align="left">Client Name</th><td>' . ($client_name !== '' ? $client_name : '—') . '</td></tr>'
            . '<tr><th align="left">Client Business</th><td>' . ($client_business !== '' ? $client_business : '—') . '</td></tr>'
            . '<tr><th align="left">Document</th><td>' . ($doc !== '' ? $doc : '—') . '</td></tr>'
            . '<tr><th align="left">Created At</th><td>' . $created_at . '</td></tr>'
            . '</table>'
            . '<p>Full proposal layout is available via PDF export from ElintOm Proposals.</p>'
            . '</body></html>';
    }

    private function send_proposal_pdf($absolute, $ext, $download_name)
    {
        if ($ext === 'html' || $ext === 'htm') {
            $this->load->helper(array('dompdf_bootstrap'));
            $html = file_get_contents($absolute);
            $pdf_binary = dompdf_render_html($html, 'A4', 'portrait');
            if ($pdf_binary === false) {
                $this->session->set_flashdata('error', 'Unable to generate PDF from saved proposal HTML.');
                redirect('elintom-proposals');
                return;
            }

            $this->force_download_binary($pdf_binary, $download_name, 'application/pdf');
            return;
        }

        $binary = @file_get_contents($absolute);
        if ($binary === false || $binary === '') {
            $this->session->set_flashdata('error', 'Unable to read proposal PDF file.');
            redirect('elintom-proposals');
            return;
        }

        $mime = ($ext === 'pdf') ? 'application/pdf' : 'application/octet-stream';
        $this->force_download_binary($binary, $download_name, $mime);
    }

    private function force_download_binary($binary, $download_name, $mime)
    {
        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', basename((string) $download_name));
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal.pdf';
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type($mime)
            ->set_header('Content-Description: File Transfer')
            ->set_header('Content-Disposition: attachment; filename="' . $safe_name . '"')
            ->set_header('Content-Transfer-Encoding: binary')
            ->set_header('Content-Length: ' . strlen($binary))
            ->set_header('Cache-Control: private, must-revalidate, max-age=0')
            ->set_header('Pragma: public')
            ->set_header('Expires: 0')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output($binary);
    }
}
