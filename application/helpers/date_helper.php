<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Date Helper
 * 
 * Provides timezone-aware date handling functions
 */

/**
 * Get current date/time in specified timezone
 * PHP 5.6+ compatible
 * 
 * @param string $timezone Timezone identifier (e.g., 'Asia/Kolkata')
 * @param string $format Date format (default: 'Y-m-d H:i:s')
 * @return string
 */
if (!function_exists('get_current_datetime')) {
    function get_current_datetime($timezone = null, $format = 'Y-m-d H:i:s') {
        if ($timezone === null) {
            // Try to get from config or default to Asia/Kolkata
            $CI =& get_instance();
            $timezone = $CI->config->item('timezone');
            if (empty($timezone)) {
                $timezone = 'Asia/Kolkata';
            }
        }
        
        // PHP 5.6+ compatible DateTime with timezone
        try {
            if (class_exists('DateTimeZone')) {
                $dt = new DateTime('now', new DateTimeZone($timezone));
                return $dt->format($format);
            } else {
                // Fallback for very old PHP versions
                return date($format);
            }
        } catch (Exception $e) {
            log_message('error', 'Timezone error: ' . $e->getMessage());
            // Fallback to server time
            return date($format);
        }
    }
}

/**
 * Convert date/time from one timezone to another
 * PHP 5.6+ compatible
 * 
 * @param string $datetime Date/time string
 * @param string $from_timezone Source timezone
 * @param string $to_timezone Target timezone
 * @param string $format Output format
 * @return string
 */
if (!function_exists('convert_timezone')) {
    function convert_timezone($datetime, $from_timezone, $to_timezone, $format = 'Y-m-d H:i:s') {
        // PHP 5.6+ compatible
        try {
            if (class_exists('DateTime') && class_exists('DateTimeZone')) {
                $dt = new DateTime($datetime, new DateTimeZone($from_timezone));
                $dt->setTimezone(new DateTimeZone($to_timezone));
                return $dt->format($format);
            } else {
                // Fallback for very old PHP versions
                return $datetime;
            }
        } catch (Exception $e) {
            log_message('error', 'Timezone conversion error: ' . $e->getMessage());
            return $datetime;
        }
    }
}

/**
 * Get user's timezone (from settings or default)
 * 
 * @param int $user_id
 * @return string
 */
if (!function_exists('get_user_timezone')) {
    function get_user_timezone($user_id = null) {
        $CI =& get_instance();
        
        // Try to get from user settings
        if ($user_id && $CI->db->table_exists('user_settings')) {
            $setting = $CI->db->where('user_id', (int)$user_id)
                              ->where('setting_key', 'timezone')
                              ->get('user_settings')
                              ->row();
            if ($setting && !empty($setting->setting_value)) {
                return $setting->setting_value;
            }
        }
        
        // Try to get from company settings
        if ($CI->db->table_exists('settings')) {
            $setting = $CI->db->where('key', 'company_timezone')
                              ->get('settings')
                              ->row();
            if ($setting && !empty($setting->value)) {
                return $setting->value;
            }
        }
        
        // Default timezone
        return config_item('timezone') ?: 'Asia/Kolkata';
    }
}

/**
 * Validate attendance date (not future, not too old)
 * PHP 5.6+ compatible
 * 
 * @param string $date Date string
 * @param int $max_days_old Maximum days in the past allowed
 * @return array ['valid' => bool, 'error' => string]
 */
if (!function_exists('validate_attendance_date')) {
    function validate_attendance_date($date, $max_days_old = 30) {
        // Load validation helper if not loaded
        $CI =& get_instance();
        $CI->load->helper('validation');
        
        $validation = validate_date($date);
        if (!$validation['valid']) {
            return ['valid' => false, 'error' => $validation['error']];
        }
        
        // PHP 5.6+ compatible DateTime
        if (class_exists('DateTime') && $validation['date'] instanceof DateTime) {
            $attendance_date = $validation['date'];
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $attendance_date->setTime(0, 0, 0);
            
            // Check if date is in the future
            if ($attendance_date > $today) {
                return ['valid' => false, 'error' => 'Attendance date cannot be in the future.'];
            }
            
            // Check if date is too old
            $diff = $today->diff($attendance_date);
            if ($diff->days > $max_days_old) {
                return ['valid' => false, 'error' => "Attendance date cannot be more than {$max_days_old} days old."];
            }
        } else {
            // Fallback for older PHP versions
            $timestamp = strtotime($date);
            $today_timestamp = strtotime(date('Y-m-d'));
            if ($timestamp > $today_timestamp) {
                return ['valid' => false, 'error' => 'Attendance date cannot be in the future.'];
            }
            $days_diff = floor(($today_timestamp - $timestamp) / 86400);
            if ($days_diff > $max_days_old) {
                return ['valid' => false, 'error' => "Attendance date cannot be more than {$max_days_old} days old."];
            }
        }
        
        return ['valid' => true, 'error' => ''];
    }
}

/**
 * Calculate working hours between two times
 * PHP 5.6+ compatible
 * 
 * @param string $punch_in Time string (H:i:s)
 * @param string $punch_out Time string (H:i:s)
 * @return float Hours worked
 */
if (!function_exists('calculate_working_hours')) {
    function calculate_working_hours($punch_in, $punch_out) {
        try {
            // PHP 5.6+ compatible DateTime
            if (class_exists('DateTime')) {
                $in = DateTime::createFromFormat('H:i:s', $punch_in);
                $out = DateTime::createFromFormat('H:i:s', $punch_out);
                
                if (!$in || !$out) {
                    return 0;
                }
                
                // If punch out is before punch in, assume next day
                if ($out < $in) {
                    $out->modify('+1 day');
                }
                
                $diff = $out->diff($in);
                $hours = $diff->h + ($diff->i / 60) + ($diff->s / 3600);
                
                return round($hours, 2);
            } else {
                // Fallback for older PHP versions using strtotime
                $in_time = strtotime('1970-01-01 ' . $punch_in);
                $out_time = strtotime('1970-01-01 ' . $punch_out);
                
                if ($in_time === false || $out_time === false) {
                    return 0;
                }
                
                // If punch out is before punch in, assume next day (add 24 hours)
                if ($out_time < $in_time) {
                    $out_time += 86400; // Add 24 hours in seconds
                }
                
                $seconds = $out_time - $in_time;
                $hours = $seconds / 3600;
                
                return round($hours, 2);
            }
        } catch (Exception $e) {
            log_message('error', 'Calculate working hours error: ' . $e->getMessage());
            return 0;
        }
    }
}

