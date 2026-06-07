<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Timesheet schema ensure (adds columns missing from older installs).
 */

if (!function_exists('timesheet_schema_ensure')) {
    function timesheet_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!function_exists('schema_table_has_column')) {
            $CI =& get_instance();
            $CI->load->helper('schema_columns');
        }

        if ($db->table_exists('timesheet_entries')
            && !schema_table_has_column($db, 'timesheet_entries', 'billable')) {
            $db->query("ALTER TABLE `timesheet_entries` ADD COLUMN `billable` tinyint(1) NOT NULL DEFAULT 1 AFTER `description`");
        }
    }
}

if (!function_exists('timesheet_billable_hours_sql')) {
    /**
     * SQL fragment for summing billable hours (alias te).
     */
    function timesheet_billable_hours_sql($db, $alias = 'te')
    {
        if (schema_table_has_column($db, 'timesheet_entries', 'billable')) {
            return 'SUM(CASE WHEN ' . $alias . '.billable = 1 THEN ' . $alias . '.hours ELSE 0 END) as billable_hours';
        }
        return 'SUM(' . $alias . '.hours) as billable_hours';
    }
}
