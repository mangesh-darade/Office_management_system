<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('my_works_status_fallback_definitions')) {
    function my_works_status_fallback_definitions()
    {
        return array(
            array('name' => 'New', 'code' => 'new', 'color' => '#3b82f6', 'icon' => 'circle', 'display_order' => 1),
            array('name' => 'In Progress', 'code' => 'in_progress', 'color' => '#eab308', 'icon' => 'play-circle', 'display_order' => 2),
            array('name' => 'Needs Discussion', 'code' => 'need_discussion', 'color' => '#ef4444', 'icon' => 'chat-dots', 'display_order' => 3),
            array('name' => 'Closed', 'code' => 'closed', 'color' => '#22c55e', 'icon' => 'check-circle', 'display_order' => 4),
            array('name' => 'Postponed', 'code' => 'postponed', 'color' => '#f97316', 'icon' => 'pause-circle', 'display_order' => 5),
        );
    }
}

if (!function_exists('my_works_status_ensure_seeded')) {
    function my_works_status_ensure_seeded()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $CI =& get_instance();
        $CI->load->model('Status_model', 'statuses');
        if (!method_exists($CI->statuses, 'seed_my_works_statuses_if_missing')) {
            return;
        }
        $CI->statuses->seed_my_works_statuses_if_missing();
    }
}

if (!function_exists('my_works_status_records')) {
    /**
     * Active my_works statuses from statuses table (ordered).
     *
     * @return array<int, object>
     */
    function my_works_status_records()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        my_works_status_ensure_seeded();
        $records = array();
        $CI =& get_instance();
        if ($CI->db->table_exists('statuses')) {
            $CI->load->model('Status_model', 'statuses');
            $records = $CI->statuses->get_by_type('my_works', true);
        }
        if (empty($records)) {
            foreach (my_works_status_fallback_definitions() as $def) {
                $records[] = (object) array_merge($def, array(
                    'type'      => 'my_works',
                    'is_active' => 1,
                    'icon'      => $def['icon'],
                ));
            }
        }
        $cache = $records;
        return $cache;
    }
}

if (!function_exists('my_works_status_codes')) {
    function my_works_status_codes()
    {
        $codes = array();
        foreach (my_works_status_records() as $row) {
            $codes[] = (string) $row->code;
        }
        if (empty($codes)) {
            return array('new', 'in_progress', 'closed');
        }
        return $codes;
    }
}

if (!function_exists('my_works_status_default_code')) {
    function my_works_status_default_code()
    {
        $codes = my_works_status_codes();
        return $codes[0];
    }
}

if (!function_exists('my_works_status_is_valid')) {
    function my_works_status_is_valid($code)
    {
        return in_array((string) $code, my_works_status_codes(), true);
    }
}

if (!function_exists('my_works_status_find')) {
    function my_works_status_find($code)
    {
        $code = (string) $code;
        foreach (my_works_status_records() as $row) {
            if ((string) $row->code === $code) {
                return $row;
            }
        }
        return null;
    }
}

if (!function_exists('my_works_status_label')) {
    function my_works_status_label($code)
    {
        $row = my_works_status_find($code);
        if ($row) {
            return (string) $row->name;
        }
        $labels = my_works_status_labels();
        return isset($labels[$code]) ? $labels[$code] : (string) $code;
    }
}

if (!function_exists('my_works_status_hex_color')) {
    function my_works_status_hex_color($code)
    {
        $row = my_works_status_find($code);
        if ($row && !empty($row->color)) {
            return (string) $row->color;
        }
        return '#6c757d';
    }
}

if (!function_exists('my_works_status_row_bg_color')) {
    /**
     * Light tinted row background from status color (dashboard lane rows).
     */
    function my_works_status_row_bg_color($code, $alpha = 0.12)
    {
        $CI =& get_instance();
        $CI->load->helper('status_row');
        return status_row_bg_from_hex(my_works_status_hex_color($code), $alpha);
    }
}

if (!function_exists('my_works_status_bootstrap_class')) {
    function my_works_status_bootstrap_class($code)
    {
        $map = array(
            'new'              => 'secondary',
            'in_progress'      => 'primary',
            'need_discussion'  => 'danger',
            'needs_discussion' => 'danger',
            'closed'           => 'success',
            'postponed'        => 'warning',
        );
        $code = (string) $code;
        if (isset($map[$code])) {
            return $map[$code];
        }
        return 'secondary';
    }
}

if (!function_exists('my_works_finished_status_codes')) {
    /**
     * Status codes treated as finished for Second Brain planning lanes.
     * Safe to call mid-query: loads once into a static cache.
     *
     * @return array<int, string>
     */
    function my_works_finished_status_codes()
    {
        static $codes = null;
        if (is_array($codes)) {
            return $codes;
        }
        $codes = array('closed', 'complete', 'completed', 'done', 'finished');
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db || !$CI->db->table_exists('statuses')) {
            return $codes;
        }
        // Use a fresh query so we do not reset an in-progress Query Builder.
        $sql = "SELECT code, name FROM statuses WHERE type = 'my_works'";
        $query = $CI->db->query($sql);
        if (!$query) {
            return $codes;
        }
        foreach ($query->result() as $row) {
            $code = strtolower(trim((string) $row->code));
            $name = strtolower(trim((string) $row->name));
            if ($code === '') {
                continue;
            }
            if (in_array($code, $codes, true)) {
                continue;
            }
            if (preg_match('/\b(closed|complete|completed|done|finished)\b/', $name)
                || preg_match('/(closed|complet|done|finish)/', $code)
            ) {
                $codes[] = $code;
            }
        }
        return $codes;
    }
}

if (!function_exists('my_works_status_is_closed')) {
    /**
     * True for finished work (Closed / Complete / Completed / Done).
     */
    function my_works_status_is_closed($code)
    {
        $code = strtolower(trim((string) $code));
        if ($code === '') {
            return false;
        }
        if (in_array($code, my_works_finished_status_codes(), true)) {
            return true;
        }
        // Legacy / free-text variants (e.g. "task_completed") — never treat "incomplete".
        if (strpos($code, 'incomplet') !== false) {
            return false;
        }
        if (preg_match('/^(closed|complete[ds]?|done|finished)$/', $code)) {
            return true;
        }
        if (preg_match('/(closed|complet|finished|\bdone\b)/', $code)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('my_works_row_is_finished')) {
    /**
     * Finished when status is closed/complete OR closed_at is set.
     *
     * @param object|array $row
     */
    function my_works_row_is_finished($row)
    {
        if (is_array($row)) {
            $row = (object) $row;
        }
        if (!$row) {
            return false;
        }
        if (isset($row->status) && my_works_status_is_closed($row->status)) {
            return true;
        }
        if (!empty($row->closed_at) && (string) $row->closed_at !== '0000-00-00 00:00:00') {
            return true;
        }
        return false;
    }
}

if (!function_exists('my_works_status_is_open')) {
    function my_works_status_is_open($code)
    {
        return !my_works_status_is_closed($code);
    }
}

if (!function_exists('my_works_status_dashboard_dot_class')) {
    function my_works_status_dashboard_dot_class($code)
    {
        $map = array(
            'new'              => 'new',
            'in_progress'      => 'in_progress',
            'need_discussion'  => 'need_discussion',
            'needs_discussion' => 'need_discussion',
            'closed'           => 'closed',
            'postponed'        => 'postponed',
        );
        $code = (string) $code;
        if (isset($map[$code])) {
            return $map[$code];
        }
        return 'new';
    }
}

if (!function_exists('my_works_status_sanitize')) {
    function my_works_status_sanitize($code, $fallback = null)
    {
        $code = trim((string) $code);
        if ($fallback === null) {
            $fallback = my_works_status_default_code();
        }
        if (my_works_status_is_valid($code)) {
            return $code;
        }
        if (my_works_status_is_valid($fallback)) {
            return $fallback;
        }
        return 'new';
    }
}

if (!function_exists('my_works_apply_open_status_filter')) {
    /**
     * Query builder: exclude finished (closed/complete) status rows.
     *
     * @param CI_DB_query_builder $db
     * @param string              $column e.g. w.status
     */
    function my_works_apply_open_status_filter($db, $column = 'w.status')
    {
        $codes = my_works_finished_status_codes();
        if (!empty($codes)) {
            $db->where_not_in($column, $codes);
        }

        $CI =& get_instance();
        if (!isset($CI->db) || !function_exists('schema_table_has_column')
            || !schema_table_has_column($CI->db, 'my_works', 'closed_at')
        ) {
            return;
        }

        // MySQL 8 / strict mode rejects DATETIME literal '0000-00-00 00:00:00' (errno 1525).
        // Compare as CHAR so legacy zero-dates still count as open.
        $closed_col = (strpos($column, 'w.') === 0) ? 'w.closed_at' : 'closed_at';
        $db->group_start()
            ->where($closed_col . ' IS NULL', null, false)
            ->or_where("CAST(" . $closed_col . " AS CHAR) = '0000-00-00 00:00:00'", null, false)
            ->group_end();
    }
}
