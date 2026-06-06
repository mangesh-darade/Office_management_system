<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified PDF export facade — Dompdf for HTML, Working_pdf_generator for structured payslips.
 */
class Pdf_export {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * @return bool
     */
    public function dompdf_available()
    {
        return class_exists('\\Dompdf\\Dompdf');
    }

    /**
     * Render HTML to PDF bytes via Dompdf.
     *
     * @param string $html
     * @param string $paper   e.g. A4
     * @param string $orientation portrait|landscape
     * @return string|false PDF binary or false if Dompdf unavailable
     */
    public function html_to_pdf($html, $paper = 'A4', $orientation = 'portrait')
    {
        if (!$this->dompdf_available()) {
            return false;
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Write HTML PDF to disk; returns path relative to FCPATH or false.
     *
     * @param string $html
     * @param string $absolute_path Full file path including .pdf
     * @param string $paper
     * @param string $orientation
     * @return bool
     */
    public function html_to_pdf_file($html, $absolute_path, $paper = 'A4', $orientation = 'portrait')
    {
        $binary = $this->html_to_pdf($html, $paper, $orientation);
        if ($binary === false) {
            return false;
        }

        return @file_put_contents($absolute_path, $binary) !== false;
    }

    /**
     * Load Working_pdf_generator for programmatic payslip-style PDFs.
     *
     * @return Working_pdf_generator|null
     */
    public function working_generator()
    {
        if (!file_exists(APPPATH . 'libraries/Working_pdf_generator.php')) {
            return null;
        }

        $this->CI->load->library('working_pdf_generator');
        return $this->CI->working_pdf_generator;
    }
}
