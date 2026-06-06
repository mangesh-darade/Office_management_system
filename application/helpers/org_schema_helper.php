<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Org-structure DDL shared by CI migrations and controller ensure_schema fallbacks.
 */

if (!function_exists('org_schema_ensure_departments')) {
    /**
     * Create or upgrade departments table (idempotent).
     *
     * @param CI_DB_query_builder $db
     * @return void
     */
    function org_schema_ensure_departments($db)
    {
        $db->query("CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dept_code VARCHAR(20) UNIQUE NOT NULL,
            dept_name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            manager_id INT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ($db->table_exists('departments') && !schema_table_has_column($db, 'departments', 'deleted_at')) {
            $db->query('ALTER TABLE departments ADD COLUMN deleted_at DATETIME NULL AFTER status');
        }

        org_schema_ensure_composite_index(
            $db,
            'departments',
            'dept_code',
            array('dept_code', 'uq_dept_code'),
            'uq_dept_code_active'
        );
    }
}

if (!function_exists('org_schema_ensure_designations')) {
    /**
     * Create or upgrade designations table (idempotent).
     *
     * @param CI_DB_query_builder $db
     * @return void
     */
    function org_schema_ensure_designations($db)
    {
        if (!$db->table_exists('designations')) {
            $db->query("CREATE TABLE `designations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `designation_code` varchar(20) NOT NULL,
                `designation_name` varchar(100) NOT NULL,
                `department_id` int(11) DEFAULT NULL,
                `level` int(11) DEFAULT 1,
                `status` varchar(20) DEFAULT 'active',
                `deleted_at` datetime NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_designation_code` (`designation_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        if ($db->table_exists('designations') && !schema_table_has_column($db, 'designations', 'deleted_at')) {
            $db->query('ALTER TABLE designations ADD COLUMN deleted_at DATETIME NULL AFTER status');
        }

        org_schema_ensure_composite_index(
            $db,
            'designations',
            'designation_code',
            array('uq_designation_code', 'designation_code'),
            'uq_designation_code_active'
        );
    }
}

if (!function_exists('org_schema_ensure_roles')) {
    /**
     * Create or upgrade roles table and seed defaults (idempotent).
     *
     * @param CI_DB_query_builder $db
     * @return void
     */
    function org_schema_ensure_roles($db)
    {
        if (!$db->table_exists('roles')) {
            $db->query("CREATE TABLE `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `group_type` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        if ($db->table_exists('roles') && !schema_table_has_column($db, 'roles', 'group_type')) {
            $db->query('ALTER TABLE `roles` ADD `group_type` varchar(50) DEFAULT NULL AFTER `name`');
        }

        if ($db->table_exists('roles') && !schema_table_has_column($db, 'roles', 'is_active')) {
            $db->query('ALTER TABLE `roles` ADD `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `group_type`');
            $db->query('UPDATE `roles` SET `is_active` = 1');
        }

        if ($db->table_exists('roles') && !schema_table_has_column($db, 'roles', 'sort_order')) {
            $db->query('ALTER TABLE `roles` ADD `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `is_active`');
            $db->query('UPDATE `roles` SET `sort_order` = `id`');
        }

        if (!$db->table_exists('roles')) {
            return;
        }

        $count = $db->count_all('roles');
        if ((int) $count === 0) {
            $defaults = array(
                1 => array('name' => 'Admin',   'group_type' => 'admin'),
                2 => array('name' => 'Manager', 'group_type' => 'admin'),
                3 => array('name' => 'Lead',    'group_type' => 'admin'),
                4 => array('name' => 'Staff',   'group_type' => 'user'),
            );
            foreach ($defaults as $id => $cfg) {
                $db->insert('roles', array(
                    'id'         => (int) $id,
                    'name'       => $cfg['name'],
                    'group_type' => $cfg['group_type'],
                    'is_active'  => 1,
                    'sort_order' => (int) $id,
                ));
            }
            return;
        }

        if (schema_table_has_column($db, 'roles', 'group_type')) {
            $db->where_in('id', array(1, 2, 3));
            $db->where('(group_type IS NULL OR group_type = \'\')', null, false);
            $db->update('roles', array('group_type' => 'admin'));

            $db->where('id', 4);
            $db->where('(group_type IS NULL OR group_type = \'\')', null, false);
            $db->update('roles', array('group_type' => 'user'));
        }
    }
}

if (!function_exists('org_schema_ensure_composite_index')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string $code_column
     * @param array  $legacy_index_names
     * @param string $composite_key
     * @return void
     */
    function org_schema_ensure_composite_index($db, $table, $code_column, array $legacy_index_names, $composite_key)
    {
        if (!$db->table_exists($table)) {
            return;
        }

        $exists = $db->query(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $db->escape($composite_key)
        );
        if ($exists->num_rows() > 0) {
            return;
        }

        foreach ($legacy_index_names as $index_name) {
            $check = $db->query(
                'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $db->escape($index_name)
            );
            if ($check->num_rows() > 0) {
                try {
                    $db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index_name . '`');
                } catch (Exception $e) {
                    // Non-fatal.
                }
            }
        }

        try {
            $db->query(
                'ALTER TABLE `' . $table . '` ADD UNIQUE KEY `' . $composite_key . '` (`'
                . $code_column . '`, deleted_at)'
            );
        } catch (Exception $e) {
            // Non-fatal.
        }
    }
}

if (!function_exists('org_schema_ensure_all')) {
    /**
     * Ensure all org-structure tables (departments, designations, roles).
     *
     * @param CI_DB_query_builder|null $db
     * @return void
     */
    function org_schema_ensure_all($db = null)
    {
        if ($db === null) {
            $CI =& get_instance();
            $db = $CI->db;
        }

        org_schema_ensure_departments($db);
        org_schema_ensure_designations($db);
        org_schema_ensure_roles($db);
    }
}
