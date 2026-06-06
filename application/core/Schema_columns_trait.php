<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Per-request column cache for CI models with a $table property.
 */
trait Schema_columns_trait
{
    /** @var array<string,array<string,bool>> */
    private $_schema_column_maps = array();

    /**
     * @param string $column
     * @return bool
     */
    protected function has_column($column)
    {
        if (!isset($this->table) || $this->table === '') {
            return false;
        }

        $table = (string) $this->table;
        if (!isset($this->_schema_column_maps[$table])) {
            $this->_schema_column_maps[$table] = array();
            if ($this->db->table_exists($table)) {
                foreach ($this->db->list_fields($table) as $field) {
                    $this->_schema_column_maps[$table][$field] = true;
                }
            }
        }

        return isset($this->_schema_column_maps[$table][$column]);
    }
}
