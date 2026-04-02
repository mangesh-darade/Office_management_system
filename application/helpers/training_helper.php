<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared helpers for Training LMS + Assessment table naming (ta_* vs legacy).
 */
if (!function_exists('training_physical_assessments_table')) {
    /**
     * @return string Physical table name or empty if not installed
     */
    function training_physical_assessments_table()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) {
            return '';
        }
        if ($CI->db->table_exists('assessments')) {
            return 'assessments';
        }
        if ($CI->db->table_exists('ta_assessments')) {
            return 'ta_assessments';
        }
        return '';
    }
}

if (!function_exists('training_assessments_are_available')) {
    function training_assessments_are_available()
    {
        return training_physical_assessments_table() !== '';
    }
}

/**
 * HMAC for completed-assessment result URLs (reduces leaked bookmark access).
 */
if (!function_exists('training_assessment_result_signature_hmac')) {
    function training_assessment_result_signature_hmac($access_token, $assessment_user_id, $completed_at)
    {
        $CI =& get_instance();
        $key = $CI->config->item('encryption_key');
        if (!$key) {
            return '';
        }
        $payload = (string) $access_token . '|' . (int) $assessment_user_id . '|' . (string) $completed_at;
        return hash_hmac('sha256', $payload, $key);
    }
}

if (!function_exists('training_assessment_signed_result_url')) {
    /**
     * @param string $token URL token (same as access_token)
     * @param object $au   assessment_users row with id, access_token, completed_at
     */
    function training_assessment_signed_result_url($token, $au)
    {
        if (!$au || empty($au->completed_at)) {
            return site_url('training-assessment/result-token/' . rawurlencode($token));
        }
        $sig = training_assessment_result_signature_hmac($au->access_token, (int) $au->id, $au->completed_at);
        return site_url('training-assessment/result-token/' . rawurlencode($token) . '?sig=' . rawurlencode($sig));
    }
}

if (!function_exists('training_assessment_valid_result_signature')) {
    function training_assessment_valid_result_signature($au, $sig)
    {
        if (!$au || empty($au->completed_at) || $sig === null || trim((string) $sig) === '') {
            return false;
        }
        $expect = training_assessment_result_signature_hmac($au->access_token, (int) $au->id, $au->completed_at);
        return $expect !== '' && hash_equals($expect, trim((string) $sig));
    }
}

/**
 * Granular Training & Assessment / LMS screen permissions (Permission Manager keys).
 * Broad legacy keys still grant full access within that area.
 */
if (!function_exists('training_ta_admin_broad')) {
    function training_ta_admin_broad()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        $rid = (int) get_instance()->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        return has_module_access('training_assessment') || has_module_access('training_assessment_manage');
    }
}

if (!function_exists('training_ta_can_screen')) {
    /**
     * @param string $granular_key e.g. training_screen_ta_dashboard
     */
    function training_ta_can_screen($granular_key)
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        $rid = (int) get_instance()->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        if (training_ta_admin_broad()) {
            return true;
        }
        return has_module_access($granular_key);
    }
}

if (!function_exists('training_ta_has_any_admin_screen')) {
    function training_ta_has_any_admin_screen()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        $rid = (int) get_instance()->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        if (training_ta_admin_broad()) {
            return true;
        }
        $keys = array(
            'training_screen_ta_dashboard',
            'training_screen_ta_create',
            'training_screen_ta_import',
            'training_screen_ta_report',
            'training_screen_ta_team_progress',
        );
        foreach ($keys as $k) {
            if (has_module_access($k)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('training_tl_show_hub_nav')) {
    function training_tl_show_hub_nav()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms')
            || has_module_access('training_lms_manage')
            || has_module_access('training_screen_tl_hub');
    }
}

if (!function_exists('training_tl_show_module_nav')) {
    function training_tl_show_module_nav()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms')
            || has_module_access('training_lms_manage')
            || has_module_access('training_screen_tl_module')
            || has_module_access('training_screen_tl_hub');
    }
}

if (!function_exists('training_tl_show_assignment_nav')) {
    function training_tl_show_assignment_nav()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms')
            || has_module_access('training_lms_manage')
            || has_module_access('training_screen_tl_assignment')
            || has_module_access('training_screen_tl_module')
            || has_module_access('training_screen_tl_hub');
    }
}

if (!function_exists('training_tl_learner_any')) {
    function training_tl_learner_any()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        $rid = (int) get_instance()->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        return has_module_access('training_lms')
            || has_module_access('training_lms_manage')
            || has_module_access('training_screen_tl_hub')
            || has_module_access('training_screen_tl_module')
            || has_module_access('training_screen_tl_assignment');
    }
}

if (!function_exists('training_lms_admin_any')) {
    function training_lms_admin_any()
    {
        if (!function_exists('has_module_access')) {
            return false;
        }
        $rid = (int) get_instance()->session->userdata('role_id');
        if ($rid === 1) {
            return true;
        }
        return has_module_access('training_lms_manage')
            || has_module_access('training_screen_lms_admin')
            || has_module_access('training_screen_lms_submissions')
            || has_module_access('training_screen_lms_office_csv');
    }
}

if (!function_exists('training_lms_admin_can_catalog')) {
    function training_lms_admin_can_catalog()
    {
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms_manage') || has_module_access('training_screen_lms_admin');
    }
}

if (!function_exists('training_lms_admin_can_submissions')) {
    function training_lms_admin_can_submissions()
    {
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms_manage') || has_module_access('training_screen_lms_submissions');
    }
}

if (!function_exists('training_lms_admin_can_office')) {
    function training_lms_admin_can_office()
    {
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        return has_module_access('training_lms_manage') || has_module_access('training_screen_lms_office_csv');
    }
}
