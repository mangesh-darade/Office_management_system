<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Company Helper
 * Provides functions for dynamic company name retrieval throughout the system
 */

if (!function_exists('get_company_name')) {
    /**
     * Get company name from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return string Company name or default fallback
     */
    function get_company_name($ci = null) {
        // Get CI instance if not provided
        if ($ci === null) {
            $ci =& get_instance();
        }
        
        // Load settings model if not already loaded
        if (!isset($ci->settings) || !$ci->settings instanceof Setting_model) {
            $ci->load->model('Setting_model', 'settings');
        }
        
        // Get company name from settings
        $company_name = $ci->settings->get_setting('company_name');
        
        // Return company name or default fallback
        return !empty($company_name) ? $company_name : 'Office Management System';
    }
}

if (!function_exists('get_company_email')) {
    /**
     * Get company email from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return string Company email or default fallback
     */
    function get_company_email($ci = null) {
        if ($ci === null) {
            $ci =& get_instance();
        }
        
        if (!isset($ci->settings) || !$ci->settings instanceof Setting_model) {
            $ci->load->model('Setting_model', 'settings');
        }
        
        $company_email = $ci->settings->get_setting('company_email');
        return !empty($company_email) ? $company_email : 'noreply@example.com';
    }
}

if (!function_exists('get_company_phone')) {
    /**
     * Get company phone from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return string Company phone or default fallback
     */
    function get_company_phone($ci = null) {
        if ($ci === null) {
            $ci =& get_instance();
        }
        
        if (!isset($ci->settings) || !$ci->settings instanceof Setting_model) {
            $ci->load->model('Setting_model', 'settings');
        }
        
        $company_phone = $ci->settings->get_setting('company_phone');
        return !empty($company_phone) ? $company_phone : '+1-234-567-8900';
    }
}

if (!function_exists('get_company_address')) {
    /**
     * Get company address from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return string Company address or default fallback
     */
    function get_company_address($ci = null) {
        if ($ci === null) {
            $ci =& get_instance();
        }
        
        if (!isset($ci->settings) || !$ci->settings instanceof Setting_model) {
            $ci->load->model('Setting_model', 'settings');
        }
        
        $company_address = $ci->settings->get_setting('company_address');
        return !empty($company_address) ? $company_address : '123 Business Street, City, State 12345';
    }
}

if (!function_exists('get_company_logo')) {
    /**
     * Get company logo path from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return string Company logo path or empty string
     */
    function get_company_logo($ci = null) {
        if ($ci === null) {
            $ci =& get_instance();
        }
        
        if (!isset($ci->settings) || !$ci->settings instanceof Setting_model) {
            $ci->load->model('Setting_model', 'settings');
        }
        
        return $ci->settings->get_setting('company_logo', '');
    }
}

if (!function_exists('get_company_details')) {
    /**
     * Get all company details from settings
     * @param CI_Controller $ci CodeIgniter instance (optional)
     * @return array Array of all company details
     */
    function get_company_details($ci = null) {
        return [
            'name' => get_company_name($ci),
            'email' => get_company_email($ci),
            'phone' => get_company_phone($ci),
            'address' => get_company_address($ci),
            'logo' => get_company_logo($ci)
        ];
    }
}
