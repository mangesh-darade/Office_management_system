<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Two-factor authentication OTP delivery.
 */

if (!function_exists('auth_2fa_send_otp')) {
    /**
     * @param CI_Controller $CI
     * @param CI_Session $session
     * @param object $user
     * @return array{success:bool,error?:string}
     */
    function auth_2fa_send_otp($CI, $session, $user)
    {
        $code = auth_generate_numeric_otp();
        auth_session_store_2fa_otp($session, $code, 300);

        if (!auth_send_otp_email(
            $CI,
            $user->email,
            'Your Two-Factor Authentication Code',
            $code,
            5
        )) {
            return array('success' => false, 'error' => 'Failed to send 2FA code. Please try again.');
        }

        return array('success' => true);
    }
}
