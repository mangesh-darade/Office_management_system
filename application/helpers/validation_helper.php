<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validation Helper
 * 
 * Provides common validation functions for employee and other data
 */

/**
 * Validate email address
 * 
 * @param string $email
 * @param bool $check_dns
 * @return array ['valid' => bool, 'error' => string]
 */
if (!function_exists('validate_email')) {
    function validate_email($email, $check_dns = false) {
        if (empty($email)) {
            return ['valid' => false, 'error' => 'Email is required.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format.'];
        }
        
        if ($check_dns) {
            $domain = substr(strrchr($email, "@"), 1);
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                return ['valid' => false, 'error' => 'Email domain does not exist.'];
            }
        }
        
        return ['valid' => true, 'error' => ''];
    }
}

/**
 * Validate phone number
 * 
 * @param string $phone
 * @param string $country_code
 * @return array ['valid' => bool, 'error' => string]
 */
if (!function_exists('validate_phone')) {
    function validate_phone($phone, $country_code = 'IN') {
        if (empty($phone)) {
            return ['valid' => false, 'error' => 'Phone number is required.'];
        }
        
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Basic validation - at least 10 digits
        if (strlen($cleaned) < 10) {
            return ['valid' => false, 'error' => 'Phone number must be at least 10 digits.'];
        }
        
        // Country-specific validation
        if ($country_code === 'IN') {
            // Indian phone numbers: 10 digits, may start with +91
            if (preg_match('/^(\+91)?[6-9]\d{9}$/', $cleaned)) {
                return ['valid' => true, 'error' => ''];
            }
            return ['valid' => false, 'error' => 'Invalid Indian phone number format.'];
        }
        
        // Generic validation
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $cleaned)) {
            return ['valid' => true, 'error' => ''];
        }
        
        return ['valid' => false, 'error' => 'Invalid phone number format.'];
    }
}

/**
 * Validate date
 * 
 * @param string $date
 * @param string $format
 * @return array ['valid' => bool, 'error' => string, 'date' => DateTime|null]
 */
if (!function_exists('validate_date')) {
    function validate_date($date, $format = 'Y-m-d') {
        if (empty($date)) {
            return ['valid' => false, 'error' => 'Date is required.', 'date' => null];
        }
        
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return ['valid' => true, 'error' => '', 'date' => $d];
        }
        
        return ['valid' => false, 'error' => 'Invalid date format. Expected: ' . $format, 'date' => null];
    }
}

/**
 * Validate employee data
 * 
 * @param array $data
 * @return array ['valid' => bool, 'errors' => array]
 */
if (!function_exists('validate_employee_data')) {
    function validate_employee_data($data) {
        $errors = [];
        
        // Mandatory fields validation
        // First Name - required, min 2 characters
        if (empty($data['first_name'])) {
            $errors[] = 'First Name is required.';
        } elseif (strlen(trim($data['first_name'])) < 2) {
            $errors[] = 'First Name must be at least 2 characters long.';
        } elseif (strlen(trim($data['first_name'])) > 100) {
            $errors[] = 'First Name cannot exceed 100 characters.';
        }
        
        // Last Name - required, min 2 characters
        if (empty($data['last_name'])) {
            $errors[] = 'Last Name is required.';
        } elseif (strlen(trim($data['last_name'])) < 2) {
            $errors[] = 'Last Name must be at least 2 characters long.';
        } elseif (strlen(trim($data['last_name'])) > 100) {
            $errors[] = 'Last Name cannot exceed 100 characters.';
        }
        
        // Date of Birth - required
        if (empty($data['dob'])) {
            $errors[] = 'Date of Birth is required.';
        } else {
            $dob_validation = validate_date($data['dob']);
            if (!$dob_validation['valid']) {
                $errors[] = 'Date of Birth: ' . $dob_validation['error'];
            } else {
                // Check if DOB is not in the future
                $dob = $dob_validation['date'];
                if ($dob > new DateTime()) {
                    $errors[] = 'Date of Birth cannot be in the future.';
                }
                // Check if age is reasonable (at least 16 years)
                $age = $dob->diff(new DateTime())->y;
                if ($age < 16) {
                    $errors[] = 'Employee must be at least 16 years old.';
                }
            }
        }
        
        // Department - required
        if (empty($data['department'])) {
            $errors[] = 'Department is required.';
        }
        
        // Designation - required
        if (empty($data['designation'])) {
            $errors[] = 'Designation is required.';
        }
        
        // Reporting To - required (can be "None" which is empty string, but we need to check)
        // Note: In the form, "-- None --" has value="", so we accept empty as valid
        // But if you want to require a reporting manager, uncomment below:
        // if (empty($data['reporting_to'])) {
        //     $errors[] = 'Reporting To is required.';
        // }
        
        // Employment Type - required
        if (empty($data['employment_type'])) {
            $errors[] = 'Employment Type is required.';
        } elseif (!in_array($data['employment_type'], ['full_time', 'part_time', 'contract', 'intern'], true)) {
            $errors[] = 'Invalid Employment Type selected.';
        }
        
        // Join Date - required
        if (empty($data['join_date'])) {
            $errors[] = 'Join Date is required.';
        } else {
            $join_validation = validate_date($data['join_date']);
            if (!$join_validation['valid']) {
                $errors[] = 'Join Date: ' . $join_validation['error'];
            }
        }
        
        // Optional field validations (only if provided)
        // Email validation
        if (!empty($data['personal_email'])) {
            $email_validation = validate_email($data['personal_email']);
            if (!$email_validation['valid']) {
                $errors[] = 'Personal email: ' . $email_validation['error'];
            }
        }
        
        // Phone validation
        if (!empty($data['phone'])) {
            $phone_validation = validate_phone($data['phone']);
            if (!$phone_validation['valid']) {
                $errors[] = 'Phone: ' . $phone_validation['error'];
            }
        }
        
        // Salary validation
        if (isset($data['salary_ctc']) && $data['salary_ctc'] !== null && $data['salary_ctc'] !== '') {
            $salary = (float)$data['salary_ctc'];
            if ($salary < 0) {
                $errors[] = 'Salary cannot be negative.';
            }
            if ($salary > 100000000) { // 100 million
                $errors[] = 'Salary value seems unrealistic.';
            }
        }
        
        // PAN validation (if provided)
        if (!empty($data['pan_no'])) {
            if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', strtoupper($data['pan_no']))) {
                $errors[] = 'Invalid PAN number format.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

/**
 * Check if user can access sensitive employee data
 * 
 * @param int $role_id
 * @param int $user_id
 * @param object $employee
 * @return bool
 */
if (!function_exists('can_access_sensitive_employee_data')) {
    function can_access_sensitive_employee_data($role_id, $user_id, $employee) {
        // Admin and Manager can always access
        if (in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            return true;
        }
        
        // Employee can access their own data
        if (isset($employee->user_id) && (int)$employee->user_id === (int)$user_id) {
            return true;
        }
        
        return false;
    }
}

