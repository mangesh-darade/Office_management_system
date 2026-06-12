<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth security helpers: toggles, IP whitelist, password expiry, 2FA gate.
 */

if (!function_exists('auth_get_security_toggle')) {
    /**
     * @param object $settings Setting_model instance
     * @param string $setting_key
     * @param string $legacy_key
     * @return string 'yes' or 'no'
     */
    function auth_get_security_toggle($settings, $setting_key, $legacy_key)
    {
        $value = $settings->get_setting($setting_key, null);
        if ($value === null || $value === '') {
            $value = $settings->get_setting($legacy_key, 'no');
        }

        return ($value === 'yes') ? 'yes' : 'no';
    }
}

if (!function_exists('auth_client_ip')) {
    /**
     * Best-effort client IP (respects CI proxy_ips when configured).
     *
     * @return string
     */
    function auth_client_ip()
    {
        $CI =& get_instance();
        if ($CI && isset($CI->input) && method_exists($CI->input, 'ip_address')) {
            return trim((string) $CI->input->ip_address());
        }

        return isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
    }
}

if (!function_exists('auth_ip_in_cidr_range')) {
    function auth_ip_in_cidr_range($ip, $subnet, $mask)
    {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int) $mask);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
}

if (!function_exists('auth_ip_whitelist_check')) {
    /**
     * @param object $settings
     * @param string $ip
     * @return bool
     */
    function auth_ip_whitelist_check($settings, $ip)
    {
        $enabled = auth_get_security_toggle($settings, 'security_enable_ip_whitelist', 'security_ip_whitelist_enabled');
        if ($enabled !== 'yes') {
            return true;
        }
        if ($ip === '') {
            return false;
        }

        $whitelist = $settings->get_setting('security_ip_whitelist', '');
        if ($whitelist === '') {
            return true;
        }

        $allowed_ips = array_map('trim', explode(',', $whitelist));
        if (in_array($ip, $allowed_ips, true)) {
            return true;
        }

        foreach ($allowed_ips as $allowed_ip) {
            if (strpos($allowed_ip, '/') !== false) {
                list($subnet, $mask) = explode('/', $allowed_ip);
                if (auth_ip_in_cidr_range($ip, $subnet, $mask)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('auth_password_not_expired')) {
    /**
     * @param object $settings
     * @param CI_DB_query_builder $db
     * @param object $user
     * @return bool
     */
    function auth_password_not_expired($settings, $db, $user)
    {
        if ($settings->get_setting('security_password_expiry_enabled', 'no') !== 'yes') {
            return true;
        }

        $expiry_days = (int) $settings->get_setting('security_password_expiry_days', 90);
        if ($expiry_days <= 0) {
            return true;
        }
        if (!schema_table_has_column($db, 'users', 'password_changed_at')) {
            return true;
        }
        if (empty($user->password_changed_at)) {
            if (!empty($user->created_at)) {
                $expiry_time = strtotime($user->created_at) + ($expiry_days * 86400);

                return time() < $expiry_time;
            }

            return true;
        }

        $expiry_time = strtotime($user->password_changed_at) + ($expiry_days * 86400);

        return time() < $expiry_time;
    }
}

if (!function_exists('auth_2fa_required_for_user')) {
    /**
     * @param object $settings
     * @param CI_DB_query_builder $db
     * @param object $user
     * @return bool
     */
    function auth_2fa_required_for_user($settings, $db, $user)
    {
        if (auth_get_security_toggle($settings, 'security_enable_2fa', 'security_2fa_enabled') !== 'yes') {
            return false;
        }
        if (schema_table_has_column($db, 'users', 'two_factor_enabled')) {
            return (isset($user->two_factor_enabled) && (int) $user->two_factor_enabled === 1);
        }

        return true;
    }
}

if (!function_exists('auth_is_blocked_redirect_url')) {
    function auth_is_blocked_redirect_url($url)
    {
        $blocked = array(
            'register',
            'auth/register',
            'reset_password',
            'auth/reset_password',
            'forgot_password',
            'auth/forgot_password',
        );
        foreach ($blocked as $fragment) {
            if (strpos($url, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('auth_should_store_redirect_url')) {
    function auth_should_store_redirect_url($current_url)
    {
        return !auth_is_blocked_redirect_url($current_url);
    }
}
