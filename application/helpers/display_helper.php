<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Display Helper
 * 
 * Provides functions to format dates, times, currency, and pagination
 * based on system settings
 */

/**
 * Format date according to system settings
 * 
 * @param string $date Date string (any format)
 * @param string|null $format Optional custom format (if null, uses system setting)
 * @return string Formatted date
 */
if (!function_exists('format_date')) {
    function format_date($date, $format = null) {
        $CI =& get_instance();
        
        if (empty($date)) {
            return '';
        }
        
        // Get format from settings if not provided
        if ($format === null) {
            $CI->load->model('Setting_model', 'settings');
            $format = $CI->settings->get_setting('system_date_format', 'Y-m-d');
        }
        
        // Convert to timestamp if string
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === false) {
            return $date; // Return original if invalid
        }
        
        return date($format, $timestamp);
    }
}

/**
 * Format time according to system settings
 * 
 * @param string $time Time string (H:i:s format)
 * @param string|null $format Optional custom format (if null, uses system setting)
 * @return string Formatted time
 */
if (!function_exists('format_time')) {
    function format_time($time, $format = null) {
        $CI =& get_instance();
        
        if (empty($time)) {
            return '';
        }
        
        // Get format from settings if not provided
        if ($format === null) {
            $CI->load->model('Setting_model', 'settings');
            $time_format = $CI->settings->get_setting('system_time_format', '24h');
            $format = ($time_format === '12h') ? 'g:i A' : 'H:i';
        }
        
        // Parse time
        $timestamp = strtotime('1970-01-01 ' . $time);
        
        if ($timestamp === false) {
            return $time; // Return original if invalid
        }
        
        return date($format, $timestamp);
    }
}

/**
 * Format datetime according to system settings
 * 
 * @param string $datetime Datetime string
 * @param string|null $date_format Optional date format
 * @param string|null $time_format Optional time format
 * @return string Formatted datetime
 */
if (!function_exists('format_datetime')) {
    function format_datetime($datetime, $date_format = null, $time_format = null) {
        $CI =& get_instance();
        
        if (empty($datetime)) {
            return '';
        }
        
        // Get formats from settings if not provided
        if ($date_format === null || $time_format === null) {
            $CI->load->model('Setting_model', 'settings');
            
            if ($date_format === null) {
                $date_format = $CI->settings->get_setting('system_date_format', 'Y-m-d');
            }
            
            if ($time_format === null) {
                $sys_time_format = $CI->settings->get_setting('system_time_format', '24h');
                $time_format = ($sys_time_format === '12h') ? 'g:i A' : 'H:i';
            }
        }
        
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        
        if ($timestamp === false) {
            return $datetime; // Return original if invalid
        }
        
        $formatted_date = date($date_format, $timestamp);
        $formatted_time = date($time_format, $timestamp);
        
        return $formatted_date . ' ' . $formatted_time;
    }
}

/**
 * Format currency amount according to system settings
 * 
 * @param float|string $amount Amount to format
 * @param bool $show_symbol Whether to show currency symbol
 * @return string Formatted currency
 */
if (!function_exists('format_currency')) {
    function format_currency($amount, $show_symbol = true) {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        $amount = (float)$amount;
        
        // Get currency settings
        $currency = $CI->settings->get_setting('system_currency', 'USD');
        $symbol = $CI->settings->get_setting('system_currency_symbol', '$');
        
        // Format number with 2 decimal places
        $formatted = number_format($amount, 2, '.', ',');
        
        // Add currency symbol if requested
        if ($show_symbol && !empty($symbol)) {
            // Place symbol before or after based on currency
            $symbol_position = in_array($currency, ['USD', 'GBP', 'EUR', 'INR']) ? 'before' : 'before';
            
            if ($symbol_position === 'before') {
                return $symbol . $formatted;
            } else {
                return $formatted . ' ' . $symbol;
            }
        }
        
        return $formatted;
    }
}

/**
 * Get default items per page from settings
 * 
 * @return int Items per page
 */
if (!function_exists('get_items_per_page')) {
    function get_items_per_page() {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        $items_per_page = (int)$CI->settings->get_setting('system_items_per_page', 20);
        
        // Ensure valid range
        if ($items_per_page < 10) {
            $items_per_page = 10;
        } elseif ($items_per_page > 100) {
            $items_per_page = 100;
        }
        
        return $items_per_page;
    }
}

/**
 * Get currency symbol from settings
 * 
 * @return string Currency symbol
 */
if (!function_exists('get_currency_symbol')) {
    function get_currency_symbol() {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        return $CI->settings->get_setting('system_currency_symbol', '$');
    }
}

/**
 * Get currency code from settings
 * 
 * @return string Currency code
 */
if (!function_exists('get_currency_code')) {
    function get_currency_code() {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        return $CI->settings->get_setting('system_currency', 'USD');
    }
}

/**
 * Get date format from settings
 * 
 * @return string Date format
 */
if (!function_exists('get_date_format')) {
    function get_date_format() {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        return $CI->settings->get_setting('system_date_format', 'Y-m-d');
    }
}

/**
 * Get time format from settings (returns '12h' or '24h')
 * 
 * @return string Time format
 */
if (!function_exists('get_time_format')) {
    function get_time_format() {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        
        return $CI->settings->get_setting('system_time_format', '24h');
    }
}
