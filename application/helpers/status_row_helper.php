<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('status_row_normalize_hex')) {
    /**
     * Normalize #RGB / #RRGGBB to 6-char hex without #.
     *
     * @param string $hex
     * @return string|false
     */
    function status_row_normalize_hex($hex)
    {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return false;
        }
        return $hex;
    }
}

if (!function_exists('status_row_bg_from_hex')) {
    /**
     * Light tinted row background from a status hex color.
     *
     * @param string $hex  e.g. #3b82f6 or 3b82f6
     * @param float  $alpha 0.0–1.0
     * @return string rgba(...) or transparent
     */
    function status_row_bg_from_hex($hex, $alpha = 0.12)
    {
        $hex = status_row_normalize_hex($hex);
        if ($hex === false) {
            return 'transparent';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $alpha = max(0.0, min(1.0, (float) $alpha));
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
    }
}

if (!function_exists('status_row_css_var_style')) {
    /**
     * Inline style fragment: --pd-row-status-color CSS variable for dashboard rows.
     *
     * @param string $hex
     * @return string
     */
    function status_row_css_var_style($hex)
    {
        $hex = trim((string) $hex);
        if ($hex === '') {
            $hex = '#94a3b8';
        }
        if ($hex[0] !== '#') {
            $hex = '#' . $hex;
        }
        return '--pd-row-status-color:' . $hex . ';';
    }
}
