<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth session establishment, remember-me, login audit fields.
 */

if (!function_exists('auth_session_write_user')) {
    /**
     * @param CI_Session $session
     * @param object $user
     * @return void
     */
    function auth_session_write_user($session, $user)
    {
        $session->set_userdata('user_id', (int) $user->id);
        $session->set_userdata('role_id', (int) $user->role_id);
        $session->set_userdata('email', $user->email);
        $session->set_userdata('last_activity', time());
        $session->set_userdata('session_id', session_id());
    }
}

if (!function_exists('auth_session_set_pending_2fa')) {
    function auth_session_set_pending_2fa($session, $user_id, $ip)
    {
        $session->set_userdata('pending_login_user_id', (int) $user_id);
        $session->set_userdata('pending_login_ip', $ip);
    }
}

if (!function_exists('auth_session_clear_pending_2fa')) {
    function auth_session_clear_pending_2fa($session)
    {
        $session->unset_userdata(array('pending_login_user_id', 'pending_login_ip'));
    }
}

if (!function_exists('auth_session_clear_2fa_otp')) {
    function auth_session_clear_2fa_otp($session)
    {
        $session->unset_userdata(array('2fa_otp_hash', '2fa_otp_expires'));
    }
}

if (!function_exists('auth_session_store_2fa_otp')) {
    function auth_session_store_2fa_otp($session, $code, $ttl_seconds = 300)
    {
        $session->set_userdata(array(
            '2fa_otp_hash'    => password_hash((string) $code, PASSWORD_DEFAULT),
            '2fa_otp_expires' => time() + (int) $ttl_seconds,
        ));
    }
}

if (!function_exists('auth_session_verify_2fa_otp')) {
    /**
     * @return array{ok:bool,error?:string}
     */
    function auth_session_verify_2fa_otp($session, $code)
    {
        $otp_hash = (string) $session->userdata('2fa_otp_hash');
        $otp_expires = (int) $session->userdata('2fa_otp_expires');

        if ($code === '' || $otp_hash === '' || !$otp_expires || time() > $otp_expires) {
            return array('ok' => false, 'error' => 'Invalid or expired 2FA code. Please try again.');
        }
        if (!password_verify($code, $otp_hash)) {
            return array('ok' => false, 'error' => 'Invalid 2FA code. Please check and try again.');
        }

        return array('ok' => true);
    }
}

if (!function_exists('auth_remember_me_set')) {
    /**
     * @param CI_DB_query_builder $db
     * @param object $user
     * @return void
     */
    function auth_remember_me_set($db, $user)
    {
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
            $selector = bin2hex(random_bytes(8));
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
            $selector = bin2hex(openssl_random_pseudo_bytes(8));
        }

        $expires = time() + (86400 * 30);
        $expires_date = date('Y-m-d H:i:s', $expires);

        if (!$db->table_exists('remember_tokens')) {
            $db->query("CREATE TABLE IF NOT EXISTS `remember_tokens` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `selector` varchar(16) NOT NULL,
                `token_hash` varchar(64) NOT NULL,
                `user_id` int(11) NOT NULL,
                `expires` datetime NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `selector` (`selector`),
                KEY `user_id` (`user_id`),
                KEY `expires` (`expires`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        $db->where('user_id', (int) $user->id)->delete('remember_tokens');
        $db->insert('remember_tokens', array(
            'selector'   => $selector,
            'token_hash' => hash('sha256', $token),
            'user_id'    => (int) $user->id,
            'expires'    => $expires_date,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $cookie_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        set_cookie('remember_me', $selector . ':' . $token, $expires, '/', '', $cookie_secure, true);
    }
}

if (!function_exists('auth_record_login')) {
    /**
     * @param CI_DB_query_builder $db
     * @param object $user
     * @return void
     */
    function auth_record_login($db, $user)
    {
        try {
            $now = date('Y-m-d H:i:s');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : '';

            $data = array();
            if (schema_table_has_column($db, 'users', 'last_login')) {
                $data['last_login'] = $now;
            }
            if (schema_table_has_column($db, 'users', 'last_login_at')) {
                $data['last_login_at'] = $now;
            }
            if (schema_table_has_column($db, 'users', 'last_login_on')) {
                $data['last_login_on'] = $now;
            }
            if (schema_table_has_column($db, 'users', 'last_seen_at')) {
                $data['last_seen_at'] = $now;
            }
            if (schema_table_has_column($db, 'users', 'last_login_ip')) {
                $data['last_login_ip'] = $ip;
            }
            if (schema_table_has_column($db, 'users', 'last_login_user_agent')) {
                $data['last_login_user_agent'] = $user_agent;
            }

            if (!empty($data)) {
                $db->where('id', (int) $user->id)->update('users', $data);
            }
        } catch (Exception $e) {
            error_log('Login recording error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('auth_resolve_post_login_redirect')) {
    /**
     * @param CI_Session $session
     * @param object $user
     * @param bool $is_ajax
     * @return string
     */
    function auth_resolve_post_login_redirect($session, $user, $is_ajax)
    {
        $CI =& get_instance();
        $CI->load->helper('coaching');
        $default_home = function_exists('coaching_login_redirect')
            ? coaching_login_redirect((int) $user->role_id)
            : 'dashboard';

        $stored = $session->userdata('redirect_url');
        $session->unset_userdata('redirect_url');

        if ($is_ajax) {
            $redirect_url = $stored ?: site_url($default_home);
            if (auth_is_blocked_redirect_url($redirect_url)) {
                return site_url('dashboard');
            }

            return $redirect_url;
        }

        $redirect_url = $stored ?: $default_home;
        if (auth_is_blocked_redirect_url($redirect_url)) {
            return 'dashboard';
        }

        return $redirect_url;
    }
}

if (!function_exists('auth_complete_login')) {
    /**
     * Regenerate session and write authenticated user data.
     *
     * @param CI_Session $session
     * @param object $user
     * @return void
     */
    function auth_complete_login($session, $user)
    {
        $session->sess_regenerate(true);
        auth_session_write_user($session, $user);
    }
}
