<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password Helper
 * 
 * Provides password validation and policy enforcement functions
 */

/**
 * Validate password strength
 * 
 * @param string $password The password to validate
 * @param array $options Validation options (min_length, require_uppercase, require_lowercase, require_number, require_special)
 * @return array ['valid' => bool, 'errors' => array]
 */
if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password, $options = []) {
        $defaults = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => false
        ];
        
        $options = array_merge($defaults, $options);
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
 * Get password policy description
 * 
 * @param array $options Policy options
 * @return string Human-readable policy description
 */
if (!function_exists('get_password_policy_description')) {
    function get_password_policy_description($options = []) {
        $defaults = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => false
        ];
        
        $options = array_merge($defaults, $options);
        $requirements = ["at least {$options['min_length']} characters"];
        
        if ($options['require_uppercase']) $requirements[] = "one uppercase letter";
        if ($options['require_lowercase']) $requirements[] = "one lowercase letter";
        if ($options['require_number']) $requirements[] = "one number";
        if ($options['require_special']) $requirements[] = "one special character";
        
        return "Password must contain " . implode(", ", $requirements) . ".";
    }
}

