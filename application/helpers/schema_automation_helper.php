<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('oms_schema_automation_config')) {
    function oms_schema_automation_config($key = null)
    {
        $CI =& get_instance();
        $CI->config->load('schema_automation', true);
        if ($key === null) {
            return $CI->config->item('schema_automation');
        }
        return $CI->config->item($key, 'schema_automation');
    }
}

if (!function_exists('oms_ensure_all_schemas')) {
    /**
     * Run every registered schema bootstrap (idempotent).
     *
     * @return array List of module labels that ran successfully
     */
    function oms_ensure_all_schemas()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }
        $CI->load->helper('schema_columns');

        $entries = oms_schema_automation_config('schema_automation');
        if (!is_array($entries)) {
            $entries = array();
        }

        $ran = array();
        foreach ($entries as $entry) {
            if (!is_array($entry) || empty($entry['label'])) {
                continue;
            }
            try {
                if (isset($entry['type']) && $entry['type'] === 'helper') {
                    if (empty($entry['helper']) || empty($entry['function'])) {
                        continue;
                    }
                    $CI->load->helper($entry['helper']);
                    $fn = $entry['function'];
                    if (function_exists($fn)) {
                        $ref = new ReflectionFunction($fn);
                        if ($ref->getNumberOfRequiredParameters() > 0) {
                            call_user_func($fn, $CI->db);
                        } else {
                            call_user_func($fn);
                        }
                        $ran[] = $entry['label'];
                    }
                } elseif (isset($entry['type']) && $entry['type'] === 'model') {
                    if (empty($entry['class'])) {
                        continue;
                    }
                    $alias = !empty($entry['alias']) ? $entry['alias'] : strtolower($entry['class']);
                    $CI->load->model($entry['class'], $alias);
                    if (!empty($entry['method']) && isset($CI->{$alias}) && method_exists($CI->{$alias}, $entry['method'])) {
                        $ref = new ReflectionMethod($CI->{$alias}, $entry['method']);
                        if ($ref->isPublic()) {
                            call_user_func(array($CI->{$alias}, $entry['method']));
                        }
                    }
                    $ran[] = $entry['label'];
                }
            } catch (Exception $e) {
                log_message('error', 'oms_ensure_all_schemas failed for ' . $entry['label'] . ': ' . $e->getMessage());
            }
        }

        return $ran;
    }
}

if (!function_exists('oms_run_all_automation_cron')) {
    /**
     * Execute all registered background automation tasks.
     *
     * @return array Task => result count/message
     */
    function oms_run_all_automation_cron()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $results = array();

        oms_ensure_all_schemas();

        if ($CI->db->table_exists('coaching_sessions')) {
            $CI->load->model('Coaching_model', 'coaching');
            $results['coaching_session_reminders'] = (int) $CI->coaching->process_session_reminder_cron();
            $results['coaching_automation_rules'] = (int) $CI->coaching->run_automation_cron();
        }

        if ($CI->db->table_exists('announcements')) {
            $CI->load->model('Announcement_model', 'announcements');
            if (method_exists($CI->announcements, 'process_scheduled')) {
                $CI->announcements->process_scheduled();
                $results['announcements_scheduled'] = 1;
            }
        }

        return $results;
    }
}

if (!function_exists('oms_schema_module_table_names')) {
    /**
     * Tables on the current DB that match known module prefixes.
     *
     * @return array
     */
    function oms_schema_module_table_names()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $prefixes = oms_schema_automation_config('schema_table_prefixes');
        if (!is_array($prefixes)) {
            $prefixes = array();
        }

        $tables = array();
        $q = $CI->db->query('SHOW TABLES');
        if (!$q) {
            return $tables;
        }
        $col = 'Tables_in_' . $CI->db->database;
        foreach ($q->result_array() as $row) {
            $name = isset($row[$col]) ? $row[$col] : reset($row);
            $lower = strtolower($name);
            foreach ($prefixes as $prefix) {
                if ($prefix !== '' && strpos($lower, strtolower($prefix)) === 0) {
                    $tables[] = $name;
                    break;
                }
            }
        }
        sort($tables);
        return $tables;
    }
}
