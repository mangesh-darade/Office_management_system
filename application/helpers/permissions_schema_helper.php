<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permissions + roles bootstrap (shared by Permissions controller and schema registry).
 */

if (!function_exists('permissions_schema_ensure')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function permissions_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('permissions')) {
            $sql = "CREATE TABLE `permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_id` int(11) NOT NULL,
                `module` varchar(100) NOT NULL,
                `can_access` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_role_module` (`role_id`,`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        } else {
            $db->where("(module IS NULL OR TRIM(module) = '')", null, false)->delete('permissions');

            $idx = $db->query("SHOW INDEX FROM `permissions` WHERE Key_name = 'idx_role_module'")->result();
            if (!empty($idx)) {
                $db->query("ALTER TABLE `permissions` DROP INDEX `idx_role_module`, ADD UNIQUE KEY `uq_role_module` (`role_id`,`module`)");
            } else {
                $uq = $db->query("SHOW INDEX FROM `permissions` WHERE Key_name = 'uq_role_module'")->result();
                if (empty($uq)) {
                    $db->query("ALTER TABLE `permissions` ADD UNIQUE KEY `uq_role_module` (`role_id`,`module`)");
                }
            }

            $col = $db->query("SHOW COLUMNS FROM `permissions` LIKE 'module'")->row();
            if ($col && strpos(strtolower($col->Type), 'varchar(64)') !== false) {
                $db->query("ALTER TABLE `permissions` MODIFY COLUMN `module` varchar(100) NOT NULL");
            }

            $old_assets = $db->where('module', 'assets_mgmt')->get('permissions')->result();
            if (!empty($old_assets)) {
                foreach ($old_assets as $row) {
                    $exists = $db->where('role_id', (int) $row->role_id)->where('module', 'assets')->get('permissions')->row();
                    if (!$exists) {
                        $db->insert('permissions', array(
                            'role_id'    => (int) $row->role_id,
                            'module'     => 'assets',
                            'can_access' => (int) $row->can_access,
                        ));
                    }
                }
                $db->where('module', 'assets_mgmt')->delete('permissions');
            }

            $old_perm_edit = $db->where('module', 'permissions_edit')->get('permissions')->result();
            if (!empty($old_perm_edit)) {
                foreach ($old_perm_edit as $row) {
                    $exists = $db->where('role_id', (int) $row->role_id)->where('module', 'permissions')->get('permissions')->row();
                    if (!$exists) {
                        $db->insert('permissions', array(
                            'role_id'    => (int) $row->role_id,
                            'module'     => 'permissions',
                            'can_access' => (int) $row->can_access,
                        ));
                    } elseif ((int) $row->can_access === 1) {
                        $db->where('id', (int) $exists->id)->update('permissions', array('can_access' => 1));
                    }
                }
                $db->where('module', 'permissions_edit')->delete('permissions');
            }
        }

        $CI =& get_instance();
        $CI->load->helper('org_schema');
        org_schema_ensure_roles($db);

        $CI->load->helper('permission');
        if (function_exists('seed_coaching_defaults_if_needed')) {
            seed_coaching_defaults_if_needed();
        }
        if (function_exists('seed_guide_permission_if_needed')) {
            seed_guide_permission_if_needed();
        }
        if (function_exists('seed_attendance_export_if_needed')) {
            seed_attendance_export_if_needed();
        }
        if (function_exists('seed_engagement_rewards_permissions_if_needed')) {
            seed_engagement_rewards_permissions_if_needed();
        }
        if (function_exists('seed_project_extensions_permissions_if_needed')) {
            seed_project_extensions_permissions_if_needed();
        }
        if (function_exists('seed_subscription_builder_permissions_if_needed')) {
            seed_subscription_builder_permissions_if_needed();
        }
    }
}
