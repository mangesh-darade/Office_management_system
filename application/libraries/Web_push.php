<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal Web Push sender (RFC 8291 + VAPID) — no Composer dependency.
 */
class Web_push {

    private $vapid_public_b64;
    private $vapid_private_pem;
    private $vapid_subject;

    public function __construct($public_b64 = null, $private_pem = null, $subject = null)
    {
        $this->vapid_public_b64 = $public_b64;
        $this->vapid_private_pem = $private_pem;
        $this->vapid_subject = $subject;
    }

    public static function ensure_vapid_keys()
    {
        $CI =& get_instance();
        $CI->load->helper('notifications_schema');
        if (!isset($CI->settings)) {
            $CI->load->model('Setting_model', 'settings');
        }
        notifications_schema_ensure_push_subscriptions($CI->db);

        $public = $CI->settings->get_setting('webpush_vapid_public');
        $private = $CI->settings->get_setting('webpush_vapid_private');
        if ($public && $private) {
            return ['public' => $public, 'private' => $private];
        }

        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        $res = openssl_pkey_new($config);
        if (!$res) {
            return null;
        }
        openssl_pkey_export($res, $private_pem);
        $details = openssl_pkey_get_details($res);
        if (empty($details['ec']['x']) || empty($details['ec']['y'])) {
            return null;
        }
        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $public_bin = "\x04" . $x . $y;
        $public_b64 = self::b64url_encode($public_bin);

        $CI->settings->set_setting('webpush_vapid_public', $public_b64);
        $CI->settings->set_setting('webpush_vapid_private', $private_pem);

        return ['public' => $public_b64, 'private' => $private_pem];
    }

    public static function instance_from_settings()
    {
        $keys = self::ensure_vapid_keys();
        if (!$keys) {
            return null;
        }
        $CI =& get_instance();
        if (!isset($CI->settings)) {
            $CI->load->model('Setting_model', 'settings');
        }
        $email = $CI->settings->get_setting('company_email', '');
        if ($email && strpos($email, '@') !== false) {
            $subject = 'mailto:' . $email;
        } else {
            $subject = 'mailto:admin@localhost';
        }
        return new self($keys['public'], $keys['private'], $subject);
    }

    /**
     * @param array $subscription endpoint, p256dh_key, auth_token
     * @param string $payload JSON string
     * @return array status, body, expired
     */
    public function send($subscription, $payload)
    {
        $endpoint = isset($subscription['endpoint']) ? $subscription['endpoint'] : '';
        $p256dh = isset($subscription['p256dh_key']) ? $subscription['p256dh_key'] : '';
        $auth = isset($subscription['auth_token']) ? $subscription['auth_token'] : '';
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return ['status' => 0, 'body' => 'invalid subscription', 'expired' => false];
        }

        $encrypted = $this->encrypt_payload($payload, $p256dh, $auth);
        $vapid_header = $this->vapid_authorization($endpoint);

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Content-Length: ' . strlen($encrypted),
            'TTL: 60',
            'Urgency: high',
            'Authorization: ' . $vapid_header,
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $expired = ($status === 404 || $status === 410);
        return ['status' => $status, 'body' => $body, 'expired' => $expired];
    }

    private function vapid_authorization($endpoint)
    {
        $parts = parse_url($endpoint);
        $audience = $parts['scheme'] . '://' . $parts['host'];
        $header = self::b64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64url_encode(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $this->vapid_subject,
        ]));
        $input = $header . '.' . $claims;
        $key = openssl_pkey_get_private($this->vapid_private_pem);
        openssl_sign($input, $der_sig, $key, OPENSSL_ALGO_SHA256);
        $jose_sig = self::der_to_jose($der_sig);
        return 'vapid t=' . $header . '.' . $claims . '.' . self::b64url_encode($jose_sig) . ', k=' . $this->vapid_public_b64;
    }

    private function encrypt_payload($payload, $p256dh_b64, $auth_b64)
    {
        $user_public = self::b64url_decode($p256dh_b64);
        $user_auth = self::b64url_decode($auth_b64);

        $local_key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $local_details = openssl_pkey_get_details($local_key);
        $local_public = "\x04"
            . str_pad($local_details['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            . str_pad($local_details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        $peer_pem = self::public_key_pem($user_public);
        $peer_key = openssl_pkey_get_public($peer_pem);
        $shared = openssl_pkey_derive($peer_key, $local_key);
        if ($shared === false) {
            throw new Exception('ECDH derive failed');
        }

        $salt = random_bytes(16);
        $ikm = self::hkdf($user_auth, $shared, "WebPush: info\x00" . $user_public . $local_public, 32);
        $cek = self::hkdf($salt, $ikm, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = self::hkdf($salt, $ikm, "Content-Encoding: nonce\x00", 12);

        $pad_len = 0;
        $plaintext = $payload . str_repeat("\x00", $pad_len) . chr($pad_len);

        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

        $record = $salt
            . pack('N', 4096)
            . chr(strlen($local_public))
            . $local_public
            . $ciphertext
            . $tag;

        return $record;
    }

    private static function hkdf($salt, $ikm, $info, $length)
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $t = '';
        $last = '';
        for ($i = 1; strlen($t) < $length; $i++) {
            $last = hash_hmac('sha256', $last . $info . chr($i), $prk, true);
            $t .= $last;
        }
        return substr($t, 0, $length);
    }

    private static function public_key_pem($raw)
    {
        $der = self::asn1_sequence(
            self::asn1_sequence(
                self::asn1_oid("\x06\x08\x2a\x86\x48\xce\x3d\x02\x01"),
                self::asn1_oid("\x06\x05\x2b\x81\x04\x00\x22")
            ),
            self::asn1_bit_string($raw)
        );
        $b64 = base64_encode($der);
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function asn1_sequence($data)
    {
        return "\x30" . self::asn1_length(strlen($data)) . $data;
    }

    private static function asn1_bit_string($data)
    {
        $data = "\x00" . $data;
        return "\x03" . self::asn1_length(strlen($data)) . $data;
    }

    private static function asn1_oid($oid)
    {
        return "\x06" . self::asn1_length(strlen($oid)) . $oid;
    }

    private static function asn1_length($length)
    {
        if ($length < 128) {
            return chr($length);
        }
        $len_bytes = '';
        while ($length > 0) {
            $len_bytes = chr($length & 0xff) . $len_bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($len_bytes)) . $len_bytes;
    }

    private static function der_to_jose($der, $part_len = 32)
    {
        $hex = bin2hex($der);
        if (substr($hex, 0, 2) !== '30') {
            throw new Exception('Invalid DER signature');
        }
        $pos = 4;
        if (hexdec(substr($hex, 2, 2)) > 127) {
            $pos = 6;
        }
        $r_len = hexdec(substr($hex, $pos, 2));
        $pos += 2;
        $r = substr($hex, $pos, $r_len * 2);
        $pos += $r_len * 2;
        $pos += 2;
        $s_len = hexdec(substr($hex, $pos, 2));
        $pos += 2;
        $s = substr($hex, $pos, $s_len * 2);
        $r = str_pad(ltrim($r, '0'), $part_len * 2, '0', STR_PAD_LEFT);
        $s = str_pad(ltrim($s, '0'), $part_len * 2, '0', STR_PAD_LEFT);
        return hex2bin($r . $s);
    }

    public static function b64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function b64url_decode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
