<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared estimate_hours / actual_hours parsing for Tasks / My Works / Templates / Projects.
 */

if (!function_exists('estimate_hours_parse')) {
    /**
     * Parse optional estimate hours from form/CSV.
     * Empty → null. Invalid → false.
     * EST hr is single digit only: whole numbers 0–9.
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
        if ($v < 0 || $v > 9) {
            return false;
        }
        // Single digit only — no decimals (2.5 rejected).
        if (abs($v - round($v)) > 0.001) {
            return false;
        }
        return (float) (int) round($v);
    }
}

if (!function_exists('estimate_hours_require')) {
    /**
     * Required estimate hours. Empty or invalid → false.
     *
     * @param mixed $raw
     * @return float|false
     */
    function estimate_hours_require($raw)
    {
        $est = estimate_hours_parse($raw);
        if ($est === null || $est === false) {
            return false;
        }
        return $est;
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

if (!function_exists('estimate_hours_row')) {
    /**
     * Compact row/card label: "2hr", "1.5hr", or "—".
     *
     * @param mixed $value
     * @return string
     */
    function estimate_hours_row($value)
    {
        $label = estimate_hours_display($value);
        if ($label === '—') {
            return '—';
        }
        return $label . 'hr';
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

if (!function_exists('actual_hours_parse')) {
    /**
     * Parse optional actual hours (wider range than EST).
     * Empty → null. Invalid → false.
     *
     * @param mixed $raw
     * @return float|null|false
     */
    function actual_hours_parse($raw)
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

if (!function_exists('actual_hours_require')) {
    /**
     * Required actual hours when completing work. Empty or invalid → false.
     *
     * @param mixed $raw
     * @return float|false
     */
    function actual_hours_require($raw)
    {
        $act = actual_hours_parse($raw);
        if ($act === null || $act === false) {
            return false;
        }
        return $act;
    }
}

if (!function_exists('actual_hours_display')) {
    /**
     * @param mixed $value
     * @return string
     */
    function actual_hours_display($value)
    {
        return estimate_hours_display($value);
    }
}

if (!function_exists('actual_hours_ensure_column')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string              $table
     * @param string|null         $after_column Prefer estimate_hours when present
     * @return void
     */
    function actual_hours_ensure_column($db, $table, $after_column = null)
    {
        if (!$db->table_exists($table)) {
            return;
        }
        if (schema_table_has_column($db, $table, 'actual_hours')) {
            return;
        }
        $after = '';
        if ($after_column === null || $after_column === '') {
            if (schema_table_has_column($db, $table, 'estimate_hours')) {
                $after_column = 'estimate_hours';
            }
        }
        if ($after_column !== null && $after_column !== '' && schema_table_has_column($db, $table, $after_column)) {
            $after = ' AFTER `' . $after_column . '`';
        }
        $db->query('ALTER TABLE `' . $table . '` ADD `actual_hours` DECIMAL(6,2) NULL' . $after);
    }
}

if (!function_exists('status_is_task_completed')) {
    /**
     * @param mixed $status
     * @return bool
     */
    function status_is_task_completed($status)
    {
        return strtolower(trim((string) $status)) === 'completed';
    }
}

if (!function_exists('status_is_project_completed')) {
    /**
     * @param mixed $status
     * @return bool
     */
    function status_is_project_completed($status)
    {
        return strtolower(trim((string) $status)) === 'completed';
    }
}
