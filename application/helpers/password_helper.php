<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password Helper
 * 
 * Provides password validation and policy enforcement functions
 */

/**
 * Validate password strength using security settings
 * 
 * @param string $password The password to validate
 * @param array $options Optional overrides (if null, uses settings from database)
 * @return array ['valid' => bool, 'errors' => array]
 */
if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password, $options = null) {
        $CI =& get_instance();
        
        // If options provided, use them; otherwise load from settings
        if ($options === null) {
            $CI->load->model('Setting_model', 'settings');
            
            // Get settings from database
            $options = [
                'min_length' => (int)($CI->settings->get_setting('security_min_password_length', 8)),
                'require_uppercase' => ($CI->settings->get_setting('security_require_uppercase', 'no') === 'yes'),
                'require_lowercase' => ($CI->settings->get_setting('security_require_lowercase', 'no') === 'yes'),
                'require_number' => ($CI->settings->get_setting('security_require_number', 'no') === 'yes'),
                'require_special' => ($CI->settings->get_setting('security_require_special', 'no') === 'yes')
            ];
        } else {
            // Use provided options with defaults
            $defaults = [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_number' => true,
                'require_special' => false
            ];
            $options = array_merge($defaults, $options);
        }
        
        $errors = [];
        
        if (strlen($password) < $options['min_length']) {
            $errors[] = "Password must be at least {$options['min_length']} characters long.";
        }
        
        if ($options['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        
        if ($options['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        
        if ($options['require_number'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        }
        
        if ($options['require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

/**
 * Get password policy description from settings
 * 
 * @param array $options Optional overrides (if null, uses settings from database)
 * @return string Human-readable policy description
 */
if (!function_exists('get_password_policy_description')) {
    function get_password_policy_description($options = null) {
        $CI =& get_instance();
        
        // If options provided, use them; otherwise load from settings
        if ($options === null) {
            $CI->load->model('Setting_model', 'settings');
            
            $options = [
                'min_length' => (int)($CI->settings->get_setting('security_min_password_length', 8)),
                'require_uppercase' => ($CI->settings->get_setting('security_require_uppercase', 'no') === 'yes'),
                'require_lowercase' => ($CI->settings->get_setting('security_require_lowercase', 'no') === 'yes'),
                'require_number' => ($CI->settings->get_setting('security_require_number', 'no') === 'yes'),
                'require_special' => ($CI->settings->get_setting('security_require_special', 'no') === 'yes')
            ];
        } else {
            $defaults = [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_number' => true,
                'require_special' => false
            ];
            $options = array_merge($defaults, $options);
        }
        
        $requirements = ["at least {$options['min_length']} characters"];
        
        if ($options['require_uppercase']) $requirements[] = "one uppercase letter";
        if ($options['require_lowercase']) $requirements[] = "one lowercase letter";
        if ($options['require_number']) $requirements[] = "one number";
        if ($options['require_special']) $requirements[] = "one special character";
        
        return "Password must contain " . implode(", ", $requirements) . ".";
    }
}

