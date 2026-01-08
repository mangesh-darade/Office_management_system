<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Error Handler Helper
 * 
 * Provides centralized error handling with user-friendly messages
 * Hides technical database errors and shows meaningful messages to users
 */

if (!function_exists('handle_database_error')) {
    /**
     * Handle database errors and return user-friendly messages
     * 
     * @param Exception|Throwable $exception The exception object
     * @param string $default_message Default message if error can't be mapped
     * @return string User-friendly error message
     */
    function handle_database_error($exception, $default_message = 'An error occurred while processing your request. Please try again.') {
        $error_message = $exception->getMessage();
        $error_code = $exception->getCode();
        
        // Log the actual error for debugging
        log_message('error', 'Database Error: ' . $error_message . ' | Code: ' . $error_code);
        
        // Map common database errors to user-friendly messages
        $error_mappings = [
            // MySQL Error Codes
            1062 => 'This record already exists. Please check for duplicate entries.',
            1451 => 'Cannot delete this record because it is being used by other records.',
            1452 => 'Invalid reference. The related record does not exist.',
            1048 => 'Required field is missing. Please fill in all required fields.',
            1054 => 'Invalid field name. Please contact system administrator.',
            1064 => 'Invalid data format. Please check your input and try again.',
            1146 => 'Database table not found. Please contact system administrator.',
            2002 => 'Cannot connect to database. Please try again later.',
            1045 => 'Database access denied. Please contact system administrator.',
            
            // Common error patterns
            'duplicate entry' => 'This record already exists. Please check for duplicates.',
            'foreign key constraint' => 'Cannot perform this action. Related records exist.',
            'column' => 'Invalid field. Please check your input.',
            'table' => 'Database table error. Please contact administrator.',
            'connection' => 'Database connection failed. Please try again later.',
        ];
        
        // Check error code first
        if (isset($error_mappings[$error_code])) {
            return $error_mappings[$error_code];
        }
        
        // Check error message patterns
        $error_lower = strtolower($error_message);
        foreach ($error_mappings as $pattern => $message) {
            if (is_string($pattern) && strpos($error_lower, $pattern) !== false) {
                return $message;
            }
        }
        
        // Return default message for unknown errors
        return $default_message;
    }
}

if (!function_exists('handle_method_error')) {
    /**
     * Handle method not found errors
     * 
     * @param string $class_name Class name
     * @param string $method_name Method name
     * @return string User-friendly error message
     */
    function handle_method_error($class_name, $method_name) {
        log_message('error', "Method not found: {$class_name}::{$method_name}()");
        return 'A system error occurred. The requested function is not available. Please contact system administrator.';
    }
}

if (!function_exists('handle_validation_error')) {
    /**
     * Format validation errors for display
     * 
     * @param string $errors Validation error string
     * @return string Formatted error message
     */
    function handle_validation_error($errors) {
        if (empty($errors)) {
            return 'Please correct the errors below and try again.';
        }
        // Remove HTML tags and format
        $clean_errors = strip_tags($errors);
        // Replace newlines with spaces
        $clean_errors = str_replace(["\n", "\r"], ' ', $clean_errors);
        // Remove multiple spaces
        $clean_errors = preg_replace('/\s+/', ' ', $clean_errors);
        return trim($clean_errors);
    }
}

if (!function_exists('safe_db_operation')) {
    /**
     * Safely execute database operation with error handling
     * 
     * @param callable $operation Callback function to execute
     * @param string $error_message Custom error message
     * @return array ['success' => bool, 'data' => mixed, 'error' => string]
     */
    function safe_db_operation($operation, $error_message = 'Database operation failed. Please try again.') {
        try {
            $result = $operation();
            return [
                'success' => true,
                'data' => $result,
                'error' => null
            ];
        } catch (Exception $e) {
            $user_message = handle_database_error($e, $error_message);
            return [
                'success' => false,
                'data' => null,
                'error' => $user_message
            ];
        } catch (Throwable $e) {
            $user_message = handle_database_error($e, $error_message);
            return [
                'success' => false,
                'data' => null,
                'error' => $user_message
            ];
        }
    }
}

if (!function_exists('set_user_error')) {
    /**
     * Set user-friendly error message in session
     * 
     * @param CI_Controller $controller Controller instance
     * @param string $message Error message
     * @param string $redirect_url Redirect URL (optional)
     */
    function set_user_error($controller, $message, $redirect_url = null) {
        $controller->session->set_flashdata('error', $message);
        if ($redirect_url !== null) {
            redirect($redirect_url);
        }
    }
}

if (!function_exists('set_user_success')) {
    /**
     * Set success message in session
     * 
     * @param CI_Controller $controller Controller instance
     * @param string $message Success message
     * @param string $redirect_url Redirect URL (optional)
     */
    function set_user_success($controller, $message, $redirect_url = null) {
        $controller->session->set_flashdata('success', $message);
        if ($redirect_url !== null) {
            redirect($redirect_url);
        }
    }
}
