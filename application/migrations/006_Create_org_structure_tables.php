<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Org structure tables (departments, designations, roles).
 *
 * Replaces runtime ensure_schema DDL in Departments, Designations, Roles controllers.
 * Run via: enable migrations in config, set migration_version to 6, visit migrate route or CLI.
 */
class Migration_Create_org_structure_tables extends CI_Migration {

    public function up()
    {
        $this->load->helper('org_schema');
        org_schema_ensure_all($this->db);
    }

    public function down()
    {
        // Intentionally no-op: dropping org tables would destroy production data.
    }
}
