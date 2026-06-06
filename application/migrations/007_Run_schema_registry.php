<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Run registered schema bootstraps (schema_automation.php registry).
 *
 * Idempotent — safe to run on existing databases. Complements per-controller ensure_schema fallbacks.
 */
class Migration_Run_schema_registry extends CI_Migration {

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
