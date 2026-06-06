<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cached column lookups for arbitrary tables (models with multiple tables).
 */

if (!function_exists('schema_table_column_map')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @return array<string,bool>
     */
    function schema_table_column_map($db, $table)
    {
        static $cache = array();

        if (!isset($cache[$table])) {
            $cache[$table] = array();
            if ($db->table_exists($table)) {
                foreach ($db->list_fields($table) as $field) {
                    $cache[$table][$field] = true;
                }
            }
        }

        return $cache[$table];
    }
}

if (!function_exists('schema_table_has_column')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string $column
     * @return bool
     */
    function schema_table_has_column($db, $table, $column)
    {
        $map = schema_table_column_map($db, $table);

        return isset($map[$column]);
    }
}

if (!function_exists('payslip_schema_columns')) {
    /**
     * Resolve legacy payslip column names (pay_period vs period, etc.).
     *
     * @param CI_DB_query_builder $db
     * @return array{period_col:string,user_col:string,has_gross_salary:bool}
     */
    function payslip_schema_columns($db)
    {
        return array(
            'period_col'       => schema_table_has_column($db, 'payslips', 'pay_period') ? 'pay_period' : 'period',
            'user_col'         => schema_table_has_column($db, 'payslips', 'employee_id') ? 'employee_id' : 'user_id',
            'has_gross_salary' => schema_table_has_column($db, 'payslips', 'gross_salary'),
        );
    }
}
