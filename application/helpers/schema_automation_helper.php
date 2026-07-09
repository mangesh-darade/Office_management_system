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
        schema_invalidate_db_cache($CI->db);

        $entries = oms_schema_automation_config('schema_automation');
        if (!is_array($entries)) {
            $entries = array();
        }

        $ran = array();
        $prev_db_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;

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
                    schema_invalidate_db_cache($CI->db);
                    $CI->load->model($entry['class'], $alias);
                    $call_method = array_key_exists('call_method', $entry)
                        ? (bool) $entry['call_method']
                        : false;
                    if ($call_method && !empty($entry['method']) && isset($CI->{$alias}) && method_exists($CI->{$alias}, $entry['method'])) {
                        $ref = new ReflectionMethod($CI->{$alias}, $entry['method']);
                        if ($ref->isPublic()) {
                            call_user_func(array($CI->{$alias}, $entry['method']));
                        }
                    }
                    $ran[] = $entry['label'];
                }
            } catch (Exception $e) {
                log_message('error', 'oms_ensure_all_schemas failed for ' . $entry['label'] . ': ' . $e->getMessage());
            } catch (Throwable $e) {
                log_message('error', 'oms_ensure_all_schemas failed for ' . $entry['label'] . ': ' . $e->getMessage());
            }

            schema_invalidate_db_cache($CI->db);
        }

        $CI->db->db_debug = $prev_db_debug;

        return $ran;
    }
}

if (!function_exists('oms_schema_session_flag')) {
    function oms_schema_session_flag()
    {
        return 'oms_schema_ensured';
    }
}

if (!function_exists('oms_schema_already_ensured')) {
    /**
     * @param CI_Session|null $session
     * @return bool
     */
    function oms_schema_already_ensured($session = null)
    {
        return ($session && $session->userdata(oms_schema_session_flag()));
    }
}

if (!function_exists('oms_mark_schema_ensured')) {
    /**
     * @param CI_Session|null $session
     * @return void
     */
    function oms_mark_schema_ensured($session = null)
    {
        if ($session) {
            $session->set_userdata(oms_schema_session_flag(), time());
        }
    }
}

if (!function_exists('oms_ensure_schemas_once_per_session')) {
    /**
     * Run all registered schema bootstraps once per session (shared guard).
     *
     * @param CI_Session|null $session
     * @return array
     */
    function oms_ensure_schemas_once_per_session($session = null)
    {
        static $ran_this_request = false;
        if ($ran_this_request || oms_schema_already_ensured($session)) {
            $ran_this_request = true;
            return array();
        }

        $ran = oms_ensure_all_schemas();
        oms_mark_schema_ensured($session);
        $ran_this_request = true;

        return $ran;
    }
}

if (!function_exists('oms_login_page_schema_ensure_enabled')) {
    function oms_login_page_schema_ensure_enabled()
    {
        $enabled = oms_schema_automation_config('login_page_schema_ensure');
        if ($enabled === false || $enabled === 'no' || $enabled === 0 || $enabled === '0') {
            return false;
        }

        return true;
    }
}

if (!function_exists('oms_ensure_schemas_on_login_page')) {
    /**
     * Run on auth/login page visit (no login required).
     * Creates only missing tables/columns from the central registry.
     *
     * @param CI_Session|null $session
     * @return array
     */
    function oms_ensure_schemas_on_login_page($session = null)
    {
        if (!oms_login_page_schema_ensure_enabled()) {
            return array();
        }

        return oms_ensure_schemas_once_per_session($session);
    }
}

if (!function_exists('oms_login_schema_ensure_enabled')) {
    /**
     * Whether login-time schema ensure is active for the given role.
     *
     * @param int $role_id
     * @return bool
     */
    function oms_login_schema_ensure_enabled($role_id = 0)
    {
        $enabled = oms_schema_automation_config('login_schema_ensure');
        if ($enabled === false || $enabled === 'no' || $enabled === 0 || $enabled === '0') {
            return false;
        }

        $admin_only = oms_schema_automation_config('login_schema_ensure_admin_only');
        if ($admin_only === true || $admin_only === 'yes' || $admin_only === 1 || $admin_only === '1') {
            return ((int) $role_id === 1);
        }

        return true;
    }
}

if (!function_exists('oms_ensure_schemas_on_login')) {
    /**
     * Run registered schema bootstraps once per session after login.
     * Creates only missing tables/columns (each registry entry is idempotent).
     *
     * @param CI_Session|null $session
     * @param int $role_id
     * @return array Module labels that ran
     */
    function oms_ensure_schemas_on_login($session = null, $role_id = 0)
    {
        if (!oms_login_schema_ensure_enabled($role_id)) {
            return array();
        }

        return oms_ensure_schemas_once_per_session($session);
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
