<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('module_status_registry')) {
    /**
     * Status groups shown in Settings → Statuses and used on forms.
     *
     * @return array<string, string> type code => label
     */
    function module_status_registry()
    {
        return array(
            'requirements' => 'Requirements',
            'projects'     => 'Projects',
            'tasks'        => 'Tasks',
            'my_works'     => 'My Works',
            'clients'      => 'Clients',
            'leaves'       => 'Leaves',
            'defects'      => 'Defects',
            'releases'     => 'Releases',
        );
    }
}

if (!function_exists('module_status_fallback_definitions')) {
    function module_status_fallback_definitions($type)
    {
        $type = trim((string) $type);
        $defs = array(
            'clients' => array(
                array('name' => 'Active', 'code' => 'active', 'color' => '#28a745', 'icon' => 'check-circle', 'display_order' => 1),
                array('name' => 'Inactive', 'code' => 'inactive', 'color' => '#6c757d', 'icon' => 'pause-circle', 'display_order' => 2),
                array('name' => 'Blocked', 'code' => 'blocked', 'color' => '#dc3545', 'icon' => 'x-circle', 'display_order' => 3),
            ),
            'leaves' => array(
                array('name' => 'Pending', 'code' => 'pending', 'color' => '#ffc107', 'icon' => 'clock', 'display_order' => 1),
                array('name' => 'Lead Approved', 'code' => 'lead_approved', 'color' => '#17a2b8', 'icon' => 'check', 'display_order' => 2),
                array('name' => 'HR Approved', 'code' => 'hr_approved', 'color' => '#17a2b8', 'icon' => 'check2', 'display_order' => 3),
                array('name' => 'Approved', 'code' => 'approved', 'color' => '#28a745', 'icon' => 'check-circle', 'display_order' => 4),
                array('name' => 'Rejected', 'code' => 'rejected', 'color' => '#dc3545', 'icon' => 'x-circle', 'display_order' => 5),
                array('name' => 'Cancelled', 'code' => 'cancelled', 'color' => '#6c757d', 'icon' => 'ban', 'display_order' => 6),
            ),
            'defects' => array(
                array('name' => 'Open', 'code' => 'open', 'color' => '#dc3545', 'icon' => 'bug', 'display_order' => 1),
                array('name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'icon' => 'play-circle', 'display_order' => 2),
                array('name' => 'Fixed', 'code' => 'fixed', 'color' => '#ffc107', 'icon' => 'wrench', 'display_order' => 3),
                array('name' => 'Verified', 'code' => 'verified', 'color' => '#28a745', 'icon' => 'check-circle', 'display_order' => 4),
                array('name' => 'Closed', 'code' => 'closed', 'color' => '#6c757d', 'icon' => 'check', 'display_order' => 5),
                array('name' => 'Rejected', 'code' => 'rejected', 'color' => '#dc3545', 'icon' => 'x-circle', 'display_order' => 6),
            ),
            'releases' => array(
                array('name' => 'Planned', 'code' => 'planned', 'color' => '#6c757d', 'icon' => 'calendar', 'display_order' => 1),
                array('name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'icon' => 'play-circle', 'display_order' => 2),
                array('name' => 'Released', 'code' => 'released', 'color' => '#28a745', 'icon' => 'rocket', 'display_order' => 3),
                array('name' => 'Cancelled', 'code' => 'cancelled', 'color' => '#dc3545', 'icon' => 'ban', 'display_order' => 4),
            ),
        );
        return isset($defs[$type]) ? $defs[$type] : array();
    }
}

if (!function_exists('module_status_ensure_seeded')) {
    function module_status_ensure_seeded($type)
    {
        static $done = array();
        $type = trim((string) $type);
        if ($type === '' || isset($done[$type])) {
            return;
        }
        $done[$type] = true;
        $defs = module_status_fallback_definitions($type);
        if (empty($defs)) {
            return;
        }
        $CI =& get_instance();
        if (!$CI->db->table_exists('statuses')) {
            return;
        }
        $CI->load->model('Status_model', 'module_statuses');
        if (method_exists($CI->module_statuses, 'seed_module_statuses_if_missing')) {
            $CI->module_statuses->seed_module_statuses_if_missing($type, $defs);
        }
    }
}

if (!function_exists('module_status_records')) {
    /**
     * @return array<int, object>
     */
    function module_status_records($type)
    {
        static $cache = array();
        $type = trim((string) $type);
        if ($type === '') {
            return array();
        }
        if (isset($cache[$type])) {
            return $cache[$type];
        }
        module_status_ensure_seeded($type);
        $records = array();
        $CI =& get_instance();
        if ($CI->db->table_exists('statuses')) {
            $CI->load->model('Status_model', 'module_statuses');
            $records = $CI->module_statuses->get_by_type($type, true);
        }
        if (empty($records)) {
            foreach (module_status_fallback_definitions($type) as $def) {
                $records[] = (object) array_merge($def, array(
                    'type'      => $type,
                    'is_active' => 1,
                ));
            }
        }
        $cache[$type] = $records;
        return $cache[$type];
    }
}

if (!function_exists('module_status_codes')) {
    function module_status_codes($type)
    {
        $codes = array();
        foreach (module_status_records($type) as $row) {
            $codes[] = (string) $row->code;
        }
        return $codes;
    }
}

if (!function_exists('module_status_options')) {
    function module_status_options($type)
    {
        $options = array();
        foreach (module_status_records($type) as $row) {
            $options[(string) $row->code] = (string) $row->name;
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }
}

if (!function_exists('module_status_label')) {
    function module_status_label($code, $type)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return '';
        }
        foreach (module_status_records($type) as $row) {
            if ((string) $row->code === $code) {
                return (string) $row->name;
            }
        }
        return ucfirst(str_replace('_', ' ', $code));
    }
}

if (!function_exists('module_status_is_valid')) {
    function module_status_is_valid($code, $type)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return false;
        }
        return in_array($code, module_status_codes($type), true);
    }
}

if (!function_exists('module_status_sanitize')) {
    /**
     * @return string|false Valid code or false
     */
    function module_status_sanitize($code, $type, $default = '')
    {
        $code = trim((string) $code);
        if ($code === '') {
            $default = trim((string) $default);
            if ($default !== '' && module_status_is_valid($default, $type)) {
                return $default;
            }
            $codes = module_status_codes($type);
            return !empty($codes) ? $codes[0] : false;
        }
        if (!module_status_is_valid($code, $type)) {
            return false;
        }
        return $code;
    }
}
