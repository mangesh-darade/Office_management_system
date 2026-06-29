<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('module_type_definitions')) {
    /**
     * Default type definitions per module (code, label, display order).
     */
    function module_type_definitions()
    {
        return array(
            'my_works' => array(
                array('code' => 'partner', 'name' => 'Partner', 'order' => 1),
                array('code' => 'srujan_client', 'name' => 'Srujan Client', 'order' => 2),
                array('code' => 'elintom_client', 'name' => 'ElintOm Client', 'order' => 3),
                array('code' => 'elintpos_client', 'name' => 'ElintPOS Client', 'order' => 4),
                array('code' => 'prospect', 'name' => 'Prospect', 'order' => 5),
            ),
            'clients' => array(
                array('code' => 'company', 'name' => 'Company', 'order' => 1),
                array('code' => 'individual', 'name' => 'Individual', 'order' => 2),
                array('code' => 'government', 'name' => 'Government', 'order' => 3),
                array('code' => 'startup', 'name' => 'Startup', 'order' => 4),
                array('code' => 'other', 'name' => 'Other', 'order' => 5),
            ),
            'projects' => array(
                array('code' => 'internal', 'name' => 'Internal', 'order' => 1),
                array('code' => 'client', 'name' => 'Client Project', 'order' => 2),
                array('code' => 'maintenance', 'name' => 'Maintenance', 'order' => 3),
                array('code' => 'support', 'name' => 'Support', 'order' => 4),
                array('code' => 'research', 'name' => 'Research', 'order' => 5),
            ),
            'requirements' => array(
                array('code' => 'new_feature', 'name' => 'New Feature', 'order' => 1),
                array('code' => 'enhancement', 'name' => 'Enhancement', 'order' => 2),
                array('code' => 'bug_fix', 'name' => 'Bug Fix', 'order' => 3),
                array('code' => 'maintenance', 'name' => 'Maintenance', 'order' => 4),
                array('code' => 'consultation', 'name' => 'Consultation', 'order' => 5),
                array('code' => 'announcement', 'name' => 'Announcement', 'order' => 6),
                array('code' => 'other', 'name' => 'Other', 'order' => 7),
            ),
            'employees' => array(
                array('code' => 'full_time', 'name' => 'Full Time', 'order' => 1),
                array('code' => 'part_time', 'name' => 'Part Time', 'order' => 2),
                array('code' => 'contract', 'name' => 'Contract', 'order' => 3),
                array('code' => 'intern', 'name' => 'Intern', 'order' => 4),
            ),
            'tasks' => array(
                array('code' => 'setup_onboarding', 'name' => 'Setup & Onboarding', 'order' => 1),
            ),
        );
    }
}

if (!function_exists('module_type_registry')) {
    function module_type_registry()
    {
        return array(
            'my_works'     => 'My Works',
            'clients'      => 'Clients',
            'projects'     => 'Projects',
            'requirements' => 'Requirements',
            'employees'    => 'Employees',
            'tasks'        => 'Tasks',
        );
    }
}

if (!function_exists('module_type_matches_value')) {
    /**
     * Match a module type code against a stored value (code or display name).
     */
    function module_type_matches_value($code, $module, $value)
    {
        $code = trim((string) $code);
        $value = trim((string) $value);
        if ($code === '' || $value === '') {
            return false;
        }
        if ($code === $value) {
            return true;
        }
        $label = module_type_label($code, $module);
        return $label !== '' && $label === $value;
    }
}

if (!function_exists('module_type_model')) {
    function module_type_model()
    {
        $CI =& get_instance();
        if (!isset($CI->module_types)) {
            $CI->load->model('Type_model', 'module_types');
        }
        return $CI->module_types;
    }
}

if (!function_exists('module_types_for_module')) {
    function module_types_for_module($module, $active_only = true)
    {
        return module_type_model()->get_by_module($module, $active_only);
    }
}

if (!function_exists('module_type_options')) {
    function module_type_options($module)
    {
        return module_type_model()->options_for_module($module);
    }
}

if (!function_exists('module_type_options_resolved')) {
    /** DB options with static fallback when table is empty. */
    function module_type_options_resolved($module)
    {
        $options = module_type_options($module);
        return !empty($options) ? $options : module_type_fallback_options($module);
    }
}

if (!function_exists('module_type_label')) {
    function module_type_label($code, $module)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return '';
        }
        $row = module_type_model()->get_by_code($code, $module);
        if ($row) {
            return (string) $row->name;
        }
        $options = module_type_fallback_options($module);
        return isset($options[$code]) ? $options[$code] : $code;
    }
}

if (!function_exists('module_type_is_valid')) {
    function module_type_is_valid($code, $module)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return true;
        }
        if (module_type_model()->get_by_code($code, $module)) {
            return true;
        }
        $fallback = module_type_fallback_options($module);
        return isset($fallback[$code]);
    }
}

if (!function_exists('module_type_validate_code')) {
    /**
     * @return string|null|false Valid code, null if empty allowed, false if invalid
     */
    function module_type_validate_code($code, $module, $allow_empty = true, $default = null)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return $allow_empty ? null : ($default !== null ? $default : false);
        }
        if (!module_type_is_valid($code, $module)) {
            return false;
        }
        return $code;
    }
}

if (!function_exists('module_type_fallback_options')) {
    function module_type_fallback_options($module)
    {
        $defs = module_type_definitions();
        if (!isset($defs[$module])) {
            return array();
        }
        $options = array();
        foreach ($defs[$module] as $row) {
            $options[$row['code']] = $row['name'];
        }
        return $options;
    }
}

if (!function_exists('module_type_default_seed_rows')) {
    function module_type_default_seed_rows()
    {
        $rows = array();
        foreach (module_type_definitions() as $module => $defs) {
            foreach ($defs as $def) {
                $rows[] = array(
                    'name'          => $def['name'],
                    'code'          => $def['code'],
                    'module'        => $module,
                    'display_order' => (int) $def['order'],
                );
            }
        }
        return $rows;
    }
}
