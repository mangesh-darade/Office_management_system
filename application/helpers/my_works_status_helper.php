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

if (!function_exists('my_works_status_is_closed')) {
    function my_works_status_is_closed($code)
    {
        return (string) $code === 'closed';
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
     * Query builder: exclude closed status rows.
     *
     * @param CI_DB_query_builder $db
     * @param string              $column e.g. w.status
     */
    function my_works_apply_open_status_filter($db, $column = 'w.status')
    {
        $db->where($column . ' !=', 'closed');
    }
}
