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
