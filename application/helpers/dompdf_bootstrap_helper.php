<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('dompdf_bootstrap_load')) {
    function dompdf_bootstrap_load()
    {
        if (class_exists('\\Dompdf\\Dompdf')) {
            return true;
        }

        static $attempted = false;
        if ($attempted) {
            return class_exists('\\Dompdf\\Dompdf');
        }
        $attempted = true;

        $paths = array(
            FCPATH . 'vendor/autoload.php',
            APPPATH . 'third_party/dompdf/autoload.inc.php',
        );
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            require_once $path;
            if (class_exists('\\Dompdf\\Dompdf')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('dompdf_render_html')) {
    function dompdf_render_html($html, $paper = 'A4', $orientation = 'portrait')
    {
        if (!dompdf_bootstrap_load()) {
            return false;
        }

        $previous_reporting = error_reporting();
        error_reporting($previous_reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            @ini_set('memory_limit', '512M');
            $dompdf = new \Dompdf\Dompdf();
            $options = $dompdf->getOptions();
            $options->setIsRemoteEnabled(true);
            $options->setIsHtml5ParserEnabled(true);
            $options->setDefaultMediaType('print');
            if (defined('FCPATH')) {
                $options->setChroot(FCPATH);
            }
            $dompdf->loadHtml($html);
            $dompdf->setPaper($paper, $orientation);
            $dompdf->render();
            $output = $dompdf->output();
        } catch (Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'Dompdf render failed: ' . $e->getMessage());
            }
            $output = false;
        }

        error_reporting($previous_reporting);

        if ($output === false || $output === '') {
            if (function_exists('log_message')) {
                if ($output === '') {
                    log_message('error', 'Dompdf render returned empty PDF output.');
                } elseif (!dompdf_bootstrap_load()) {
                    log_message('error', 'Dompdf bootstrap load failed.');
                }
            }
            return false;
        }

        return $output;
    }
}
