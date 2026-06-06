<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Persistent login attempt tracking and lockout.
 */

if (!function_exists('auth_login_attempts_ensure_table')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function auth_login_attempts_ensure_table($db)
    {
        if ($db->table_exists('login_attempts')) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `identifier` varchar(255) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `attempts` int(11) DEFAULT 1,
            `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
            `locked_until` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_identifier_ip` (`identifier`, `ip_address`),
            KEY `idx_locked_until` (`locked_until`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->query($sql);
    }
}

if (!function_exists('auth_login_attempts_check')) {
    /**
     * @return array{locked:bool,minutes:int}
     */
    function auth_login_attempts_check($db, $identifier, $ip, $max_attempts, $lockout_duration)
    {
        auth_login_attempts_ensure_table($db);

        $attempt = $db->where('identifier', $identifier)
            ->where('ip_address', $ip)
            ->get('login_attempts')
            ->row();

        if (!$attempt) {
            return array('locked' => false, 'minutes' => 0);
        }

        if ($attempt->locked_until && strtotime($attempt->locked_until) > time()) {
            $minutes = (int) ceil((strtotime($attempt->locked_until) - time()) / 60);

            return array('locked' => true, 'minutes' => $minutes);
        }

        if ((int) $attempt->attempts >= $max_attempts) {
            $last_attempt_time = strtotime($attempt->last_attempt);
            $lockout_seconds = $lockout_duration * 60;

            if ((time() - $last_attempt_time) < $lockout_seconds) {
                $minutes = (int) ceil(($lockout_seconds - (time() - $last_attempt_time)) / 60);
                $db->where('id', $attempt->id)->update('login_attempts', array(
                    'locked_until' => date('Y-m-d H:i:s', time() + $lockout_seconds),
                ));

                return array('locked' => true, 'minutes' => $minutes);
            }

            $db->where('id', $attempt->id)->update('login_attempts', array(
                'attempts'     => 0,
                'locked_until' => null,
            ));
        }

        return array('locked' => false, 'minutes' => 0);
    }
}

if (!function_exists('auth_login_attempts_record_failed')) {
    function auth_login_attempts_record_failed($db, $identifier, $ip, $max_attempts, $lockout_duration)
    {
        if (!$db->table_exists('login_attempts')) {
            return;
        }

        $attempt = $db->where('identifier', $identifier)
            ->where('ip_address', $ip)
            ->get('login_attempts')
            ->row();

        $now = date('Y-m-d H:i:s');

        if ($attempt) {
            $new_attempts = (int) $attempt->attempts + 1;
            $locked_until = null;
            if ($new_attempts >= $max_attempts) {
                $locked_until = date('Y-m-d H:i:s', time() + ($lockout_duration * 60));
            }
            $db->where('id', $attempt->id)->update('login_attempts', array(
                'attempts'       => $new_attempts,
                'last_attempt'   => $now,
                'locked_until'   => $locked_until,
            ));

            return;
        }

        $db->insert('login_attempts', array(
            'identifier'   => $identifier,
            'ip_address'   => $ip,
            'attempts'     => 1,
            'last_attempt' => $now,
        ));
    }
}

if (!function_exists('auth_login_attempts_clear')) {
    function auth_login_attempts_clear($db, $identifier, $ip)
    {
        if (!$db->table_exists('login_attempts')) {
            return;
        }

        $db->where('identifier', $identifier)
            ->where('ip_address', $ip)
            ->delete('login_attempts');
    }
}
