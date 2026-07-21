<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared estimate_hours parsing for Tasks / My Works / Templates / Projects.
 */

if (!function_exists('estimate_hours_parse')) {
    /**
     * Parse optional estimate hours from form/CSV.
     * Empty → null. Invalid → false.
     *
     * @param mixed $raw
     * @return float|null|false
     */
    function estimate_hours_parse($raw)
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        if (!is_numeric($s)) {
            return false;
        }
        $v = (float) $s;
        if ($v < 0 || $v > 9999.99) {
            return false;
        }
        return round($v, 2);
    }
}

if (!function_exists('estimate_hours_display')) {
    /**
     * @param mixed $value
     * @return string
     */
    function estimate_hours_display($value)
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (!is_numeric($value)) {
            return '—';
        }
        $v = (float) $value;
        if (abs($v - round($v)) < 0.001) {
            return (string) (int) round($v);
        }
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}

if (!function_exists('estimate_hours_ensure_column')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string|null $after_column
     * @return void
     */
    function estimate_hours_ensure_column($db, $table, $after_column = null)
    {
        if (!$db->table_exists($table)) {
            return;
        }
        if (schema_table_has_column($db, $table, 'estimate_hours')) {
            return;
        }
        $after = '';
        if ($after_column !== null && $after_column !== '' && schema_table_has_column($db, $table, $after_column)) {
            $after = ' AFTER `' . $after_column . '`';
        }
        $db->query('ALTER TABLE `' . $table . '` ADD `estimate_hours` DECIMAL(6,2) NULL' . $after);
    }
}
