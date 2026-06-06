<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Re-run expanded schema_automation registry (clients, expenses, requirements, etc.).
 *
 * Idempotent — safe on existing databases. Enable migration_enabled temporarily to apply.
 */
class Migration_Run_schema_registry_v3 extends CI_Migration {

    public function up()
    {
        $this->load->helper('schema_automation');
        oms_ensure_all_schemas();
    }

    public function down()
    {
        // No-op: schema bootstraps are not reversed.
    }
}
