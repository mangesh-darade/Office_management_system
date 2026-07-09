<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cached column lookups for arbitrary tables (models with multiple tables).
 */

if (!function_exists('schema_table_column_cache_store')) {
    /**
     * @return array<string,array<string,bool>>
     */
    function &schema_table_column_cache_store()
    {
        static $cache = array();

        return $cache;
    }
}

if (!function_exists('schema_reset_column_cache')) {
    /**
     * @param string|null $table
     * @return void
     */
    function schema_reset_column_cache($table = null)
    {
        $cache =& schema_table_column_cache_store();
        if ($table === null) {
            $cache = array();
            return;
        }
        unset($cache[$table]);
    }
}

if (!function_exists('schema_invalidate_db_cache')) {
    /**
     * Clear CI DB table/field caches after DDL (raw CREATE/ALTER does not refresh them).
     *
     * @param CI_DB_query_builder $db
     * @param string|null $table
     * @return void
     */
    function schema_invalidate_db_cache($db, $table = null)
    {
        if (isset($db->data_cache['table_names'])) {
            unset($db->data_cache['table_names']);
        }
        if ($table !== null && isset($db->data_cache['field_names'][$table])) {
            unset($db->data_cache['field_names'][$table]);
        } elseif ($table === null && isset($db->data_cache['field_names'])) {
            unset($db->data_cache['field_names']);
        }
        schema_reset_column_cache($table);
    }
}

if (!function_exists('schema_db_error_is_duplicate')) {
    /**
     * @param CI_DB_query_builder $db
     * @param array<int> $codes MySQL error codes (1050 table exists, 1060 duplicate column, 1061 duplicate key)
     * @return bool
     */
    function schema_db_error_is_duplicate($db, array $codes = array(1050, 1060, 1061))
    {
        if (!method_exists($db, 'error')) {
            return false;
        }
        $err = $db->error();
        if (!is_array($err) || empty($err['code'])) {
            return false;
        }

        return in_array((int) $err['code'], $codes, true);
    }
}

if (!function_exists('schema_safe_create_table')) {
    /**
     * Create table only when missing; uses IF NOT EXISTS and refreshes CI caches.
     *
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string $sql CREATE TABLE statement
     * @return bool
     */
    function schema_safe_create_table($db, $table, $sql)
    {
        $table = trim((string) $table);
        if ($table === '' || trim((string) $sql) === '') {
            return false;
        }

        schema_invalidate_db_cache($db);
        if ($db->table_exists($table)) {
            return true;
        }

        $sql = trim((string) $sql);
        if (stripos($sql, 'IF NOT EXISTS') === false) {
            $sql = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $sql, 1);
        }

        $ok = $db->query($sql);
        schema_invalidate_db_cache($db, $table);

        if ($ok !== false) {
            return true;
        }
        if (schema_db_error_is_duplicate($db, array(1050))) {
            schema_invalidate_db_cache($db);
            return $db->table_exists($table);
        }

        return false;
    }
}

if (!function_exists('schema_safe_add_column')) {
    /**
     * Add column only when missing; ignores duplicate-column errors.
     *
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string $column
     * @param string $alter_sql ALTER TABLE ... ADD COLUMN ...
     * @return bool
     */
    function schema_safe_add_column($db, $table, $column, $alter_sql)
    {
        $table = trim((string) $table);
        $column = trim((string) $column);
        if ($table === '' || $column === '' || trim((string) $alter_sql) === '') {
            return false;
        }

        schema_invalidate_db_cache($db, $table);
        if (!$db->table_exists($table)) {
            return false;
        }
        if (schema_table_has_column($db, $table, $column)) {
            return true;
        }

        $ok = $db->query($alter_sql);
        schema_invalidate_db_cache($db, $table);

        if ($ok !== false) {
            return true;
        }
        if (schema_db_error_is_duplicate($db, array(1060))) {
            return schema_table_has_column($db, $table, $column);
        }

        return false;
    }
}

if (!function_exists('schema_table_column_map')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @return array<string,bool>
     */
    function schema_table_column_map($db, $table)
    {
        $cache =& schema_table_column_cache_store();

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
