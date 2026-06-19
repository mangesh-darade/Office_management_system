<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seed subscription_builder catalog from database/subscription_builder_seed.sql
 */
class Migration_Seed_subscription_builder extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('subscription_builder')) {
            return;
        }

        $count = (int) $this->db->count_all('subscription_builder');
        if ($count > 0) {
            return;
        }

        $sql_file = FCPATH . 'database/subscription_builder_seed.sql';
        if (!is_file($sql_file)) {
            return;
        }

        $sql = file_get_contents($sql_file);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        $statements = preg_split('/;\s*\n/', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || stripos($statement, '--') === 0) {
                continue;
            }
            if (stripos($statement, 'SET NAMES') === 0) {
                continue;
            }
            if (stripos($statement, 'DELETE FROM') === 0 && (int) $this->db->count_all('subscription_builder') === 0) {
                continue;
            }
            $this->db->query($statement);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('subscription_builder')) {
            $this->db->empty_table('subscription_builder');
        }
    }
}
