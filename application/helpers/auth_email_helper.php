<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared auth email + OTP utilities.
 */

if (!function_exists('auth_generate_numeric_otp')) {
    function auth_generate_numeric_otp()
    {
        try {
            if (function_exists('random_int')) {
                return random_int(100000, 999999);
            }
        } catch (Exception $e) {
            // Fall through to mt_rand.
        }

        return mt_rand(100000, 999999);
    }
}

if (!function_exists('auth_send_html_email')) {
    /**
     * @param CI_Controller $CI
     * @param string $to
     * @param string $subject
     * @param string $html_body
     * @return bool
     */
    function auth_send_html_email($CI, $to, $subject, $html_body)
    {
        try {
            $CI->config->load('email');
            $CI->load->library('email');
            $CI->load->helper('email');
            configure_email_from_settings();
            $CI->email->clear(true);
            $from = get_system_from_email();
            if (!$from) {
                $from = 'no-reply@example.com';
            }
            $CI->email->from($from, get_company_name());
            $CI->email->to($to);
            $CI->email->subject($subject);
            $CI->email->message($html_body);

            return (bool) $CI->email->send();
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('auth_send_otp_email')) {
    /**
     * @param CI_Controller $CI
     * @param string $to
     * @param string $subject
     * @param int|string $code
     * @param int $ttl_minutes
     * @return bool
     */
    function auth_send_otp_email($CI, $to, $subject, $code, $ttl_minutes)
    {
        $safe_code = htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8');
        $message = '<p>Your verification code is <strong>' . $safe_code . '</strong>.</p>';
        $message .= '<p>It will expire in ' . (int) $ttl_minutes . ' minutes.</p>';

        return auth_send_html_email($CI, $to, $subject, $message);
    }
}

if (!function_exists('auth_is_gmail_address')) {
    function auth_is_gmail_address($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $parts = explode('@', $email);
        $domain = isset($parts[1]) ? strtolower(trim($parts[1])) : '';

        return ($domain === 'gmail.com' || $domain === 'googlemail.com');
    }
}
