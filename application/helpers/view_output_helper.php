<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Safe HTML escape for view output (PHP 8.4+: htmlspecialchars rejects null).
 */
if (!function_exists('esc_view')) {
    function esc_view($value)
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize rich-text HTML for safe display (Summernote-style content).
 * Strips scripts, event handlers, and dangerous URLs while keeping basic formatting.
 */
if (!function_exists('sanitize_html_output')) {
    function sanitize_html_output($html)
    {
        $html = (string) ($html ?? '');
        if ($html === '') {
            return '';
        }
        $allowed = '<p><br><b><strong><i><em><u><s><del><strike><ul><ol><li><a><h1><h2><h3><h4><h5><h6>'
            . '<table><thead><tbody><tr><td><th><span><div><blockquote><pre><code><sub><sup><hr><img>';
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        return $html;
    }
}

/**
 * Sanitize embed HTML (iframe/embed/object only) for external training players.
 */
if (!function_exists('sanitize_embed_code')) {
    function sanitize_embed_code($html)
    {
        $html = (string) ($html ?? '');
        if ($html === '') {
            return '';
        }
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = strip_tags($html, '<iframe><embed><object><div>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        return $html;
    }
}

/**
 * Build a safe query string from current GET params (XSS-safe export/filter URLs).
 */
if (!function_exists('safe_query_string')) {
    function safe_query_string($exclude = array())
    {
        $ci = get_instance();
        if (!$ci || !isset($ci->input)) {
            return '';
        }
        $get = $ci->input->get(null, true);
        if (!is_array($get) || empty($get)) {
            return '';
        }
        foreach ($exclude as $key) {
            unset($get[$key]);
        }
        if (empty($get)) {
            return '';
        }
        return http_build_query($get, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('safe_query_suffix')) {
    function safe_query_suffix($exclude = array())
    {
        $qs = safe_query_string($exclude);
        if ($qs === '') {
            return '';
        }
        return '?' . $qs;
    }
}
