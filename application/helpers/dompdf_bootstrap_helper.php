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
    /**
     * @param string $html
     * @param string $paper
     * @param string $orientation
     * @param string|null $error Populated with a short failure reason
     * @return string|false
     */
    function dompdf_render_html($html, $paper = 'A4', $orientation = 'portrait', &$error = null)
    {
        $error = null;
        $html = (string) $html;
        if (trim($html) === '') {
            $error = 'Empty HTML content.';
            if (function_exists('log_message')) {
                log_message('error', 'Dompdf render failed: empty HTML content.');
            }
            return false;
        }

        if (!dompdf_bootstrap_load()) {
            $error = 'Dompdf library is not installed (run composer install).';
            if (function_exists('log_message')) {
                log_message('error', 'Dompdf bootstrap load failed.');
            }
            return false;
        }

        // Raise memory early — Apache php.ini defaults to 128M which is tight for large quotes.
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '120');

        // PHP 8.5 surfaces Dompdf deprecations; keep them out of JSON/HTML responses.
        $previous_reporting = error_reporting();
        error_reporting($previous_reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);
        $previous_display = ini_get('display_errors');
        @ini_set('display_errors', '0');
        $buffer_level = ob_get_level();
        ob_start();

        $output = false;
        try {
            if (!preg_match('/<meta[^>]+charset=/i', $html)) {
                if (stripos($html, '<head>') !== false) {
                    $html = preg_replace('/<head>/i', '<head><meta charset="UTF-8">', $html, 1);
                } else {
                    $html = '<meta charset="UTF-8">' . $html;
                }
            }

            $dompdf = new \Dompdf\Dompdf();
            $options = $dompdf->getOptions();
            // Quotes use data-URI logos; disable remote to avoid live-server network failures.
            $options->setIsRemoteEnabled(false);
            $options->setIsHtml5ParserEnabled(true);
            $options->setDefaultMediaType('print');
            $options->setDefaultFont('DejaVu Sans');
            if (defined('FCPATH')) {
                $options->setChroot(FCPATH);
            }

            $temp_dir = defined('FCPATH') ? (FCPATH . 'application/cache/dompdf') : '';
            if ($temp_dir !== '' && !is_dir($temp_dir)) {
                @mkdir($temp_dir, 0777, true);
            }
            if ($temp_dir === '' || !is_dir($temp_dir) || !is_writable($temp_dir)) {
                $temp_dir = rtrim(sys_get_temp_dir(), '\\/');
            }
            if ($temp_dir !== '' && is_dir($temp_dir) && is_writable($temp_dir)) {
                $options->setTempDir($temp_dir);
                $options->setFontCache($temp_dir);
            }

            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper($paper, $orientation);
            $dompdf->render();
            $output = $dompdf->output();
        } catch (Throwable $e) {
            $error = 'PDF engine error: ' . $e->getMessage();
            if (function_exists('log_message')) {
                log_message('error', 'Dompdf render failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
            $output = false;
        }

        // Discard only the buffer we opened (never touch CI's outer buffers).
        while (ob_get_level() > $buffer_level) {
            ob_end_clean();
        }
        error_reporting($previous_reporting);
        if ($previous_display !== false) {
            @ini_set('display_errors', $previous_display);
        }

        if ($output === false || $output === '') {
            if ($error === null) {
                $error = 'PDF engine returned empty output.';
            }
            if (function_exists('log_message')) {
                log_message('error', 'Dompdf render returned empty PDF output.');
            }
            return false;
        }

        return $output;
    }
}
