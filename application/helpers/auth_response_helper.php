<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth HTTP response helpers for login/AJAX flows.
 */

if (!function_exists('auth_respond_login_error')) {
    /**
     * @param bool $is_ajax
     * @param string $error
     * @param string $redirect
     * @param array $extra
     * @return void
     */
    function auth_respond_login_error($is_ajax, $error, $redirect = 'auth/login', $extra = array())
    {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(array_merge(array('success' => false, 'error' => $error), $extra));
            exit;
        }

        $CI =& get_instance();
        $CI->session->set_flashdata('error', $error);
        redirect($redirect);
        exit;
    }
}

if (!function_exists('auth_respond_login_success')) {
    /**
     * @param bool $is_ajax
     * @param string $redirect_url
     * @param array $extra
     * @return void
     */
    function auth_respond_login_success($is_ajax, $redirect_url, $extra = array())
    {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(array_merge(array('success' => true, 'redirect' => $redirect_url), $extra));
            exit;
        }

        redirect($redirect_url);
        exit;
    }
}

if (!function_exists('auth_respond_2fa_required')) {
    function auth_respond_2fa_required($is_ajax)
    {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'success'     => true,
                'require_2fa' => true,
                'redirect'    => site_url('auth/verify-2fa'),
            ));
            exit;
        }

        redirect('auth/verify-2fa');
        exit;
    }
}

if (!function_exists('auth_respond_password_expired')) {
    function auth_respond_password_expired($is_ajax, $user_id)
    {
        if ($is_ajax) {
            auth_respond_login_error(
                true,
                'Your password has expired. Please reset your password.',
                'auth/login',
                array('expired' => true)
            );
        }

        $CI =& get_instance();
        $CI->session->set_flashdata('error', 'Your password has expired. Please reset your password.');
        $CI->session->set_userdata('pw_expired_user_id', (int) $user_id);
        redirect('auth/reset_password?expired=1');
        exit;
    }
}

if (!function_exists('auth_log_failed_login')) {
    function auth_log_failed_login($audit, $settings, $event, $user_id, $detail, $ip)
    {
        if ($settings->get_setting('security_log_failed_attempts', 'no') !== 'yes') {
            return;
        }

        $audit->log($event, $user_id, $detail, $ip);
    }
}
