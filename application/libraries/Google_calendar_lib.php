<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Calendar email reminders via REST API (cURL).
 * Credentials/token stored under application/cache/google_calendar/ (not in session).
 */
class Google_calendar_lib
{
    private const SCOPE = 'https://www.googleapis.com/auth/calendar';
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    /** @var CI_Controller */
    private $CI;
    private $dir;
    private $credentials_file;
    private $token_file;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->dir = APPPATH . 'cache/google_calendar';
        $this->credentials_file = $this->dir . '/credentials.json';
        $this->token_file = $this->dir . '/token.json';

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
    }

    public function get_redirect_uri()
    {
        // Keep stable: no trailing slash; must match Google Console exactly.
        return rtrim(site_url('calendar-reminders/oauth-callback'), '/');
    }

    public function is_configured()
    {
        $creds = $this->load_credentials();
        return !empty($creds['client_id']) && !empty($creds['client_secret']);
    }

    public function is_connected()
    {
        $token = $this->load_token();
        return !empty($token['refresh_token']) || !empty($token['access_token']);
    }

    /**
     * Save OAuth client id/secret (Web application client recommended).
     *
     * @param string $client_id
     * @param string $client_secret
     * @return bool
     */
    public function save_credentials($client_id, $client_secret)
    {
        $client_id = trim((string) $client_id);
        $client_secret = trim((string) $client_secret);
        if ($client_id === '' || $client_secret === '') {
            return false;
        }

        $payload = [
            'web' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'redirect_uris' => [$this->get_redirect_uri()],
            ],
        ];

        return $this->write_json($this->credentials_file, $payload);
    }

    /**
     * Import Google-downloaded credentials.json (web or installed).
     *
     * @param string $json_raw
     * @return array{ok:bool,message:string,warning?:string}
     */
    public function import_credentials_json($json_raw)
    {
        $data = json_decode((string) $json_raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid JSON.'];
        }

        $block = null;
        $is_installed = false;
        if (isset($data['web']) && is_array($data['web'])) {
            $block = $data['web'];
        } elseif (isset($data['installed']) && is_array($data['installed'])) {
            $block = $data['installed'];
            $is_installed = true;
        }

        if (!$block || empty($block['client_id']) || empty($block['client_secret'])) {
            return ['ok' => false, 'message' => 'Missing client_id or client_secret in JSON.'];
        }

        if (!$this->save_credentials($block['client_id'], $block['client_secret'])) {
            return ['ok' => false, 'message' => 'Could not save credentials.'];
        }

        $redirect_uri = $this->get_redirect_uri();
        if ($is_installed) {
            return [
                'ok' => true,
                'message' => 'Client ID and Secret saved.',
                'warning' => 'You pasted a Desktop (installed) OAuth file. That type only allows redirect '
                    . 'http://localhost — it will NOT work with this PHP app. In Google Cloud Console create a '
                    . 'new OAuth client of type Web application and add this redirect URI exactly: '
                    . $redirect_uri,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Credentials saved. Click Connect Google to authorize.',
        ];
    }

    public function get_auth_url()
    {
        $creds = $this->load_credentials();
        if (empty($creds['client_id'])) {
            return '';
        }

        $redirect_uri = $this->get_redirect_uri();
        // Must use the exact same redirect_uri on token exchange
        $this->CI->session->set_userdata('gcal_oauth_redirect_uri', $redirect_uri);

        $params = [
            'client_id' => $creds['client_id'],
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code
     * @return array{ok:bool,message:string}
     */
    public function handle_oauth_callback($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return ['ok' => false, 'message' => 'Missing authorization code.'];
        }

        $creds = $this->load_credentials();
        if (empty($creds['client_id']) || empty($creds['client_secret'])) {
            return ['ok' => false, 'message' => 'Google credentials are not configured.'];
        }

        $redirect_uri = (string) $this->CI->session->userdata('gcal_oauth_redirect_uri');
        if ($redirect_uri === '') {
            $redirect_uri = $this->get_redirect_uri();
        }

        $response = $this->http_post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        ]);

        $this->CI->session->unset_userdata('gcal_oauth_redirect_uri');

        if ($response === false) {
            log_message('error', 'Google Calendar OAuth token exchange: HTTP/cURL failed');
            return [
                'ok' => false,
                'message' => 'Token exchange failed (network/SSL). CA cert was missing for PHP cURL on WAMP — retry Connect Google now. If it still fails, check that PHP curl extension is enabled.',
            ];
        }

        if (empty($response['access_token'])) {
            $err = 'Token exchange failed.';
            if (!empty($response['error'])) {
                $err = 'Google OAuth: ' . $response['error'];
            }
            if (!empty($response['error_description'])) {
                $err .= ' — ' . $response['error_description'];
            }
            $err .= ' | Redirect used: ' . $redirect_uri;
            $err .= ' | Tip: use OAuth type Web application and add this exact redirect URI in Google Console.';
            log_message('error', 'Google Calendar OAuth token exchange failed: ' . $err);
            return ['ok' => false, 'message' => $err];
        }

        $existing = $this->load_token();
        if (empty($response['refresh_token']) && !empty($existing['refresh_token'])) {
            $response['refresh_token'] = $existing['refresh_token'];
        }

        $response['obtained_at'] = time();
        if (!empty($response['expires_in'])) {
            $response['expires_at'] = time() + (int) $response['expires_in'];
        }

        if (!$this->write_json($this->token_file, $response)) {
            return ['ok' => false, 'message' => 'Could not save OAuth token.'];
        }

        return ['ok' => true, 'message' => 'Google Calendar connected.'];
    }

    public function disconnect()
    {
        if (is_file($this->token_file)) {
            @unlink($this->token_file);
        }
        return true;
    }

    /**
     * Create calendar event with email reminder + attendee.
     *
     * @param array $data
     * @return array{ok:bool,message:string,data?:array}
     */
    public function create_reminder_event(array $data)
    {
        $title = trim((string) ($data['title'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $when = trim((string) ($data['when'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $reminder_minutes = (int) ($data['reminder_minutes'] ?? 30);
        $duration_minutes = (int) ($data['duration_minutes'] ?? 30);
        $timezone = trim((string) ($data['timezone'] ?? 'Asia/Kolkata'));

        if ($title === '' || $email === '' || $when === '') {
            return ['ok' => false, 'message' => 'Title, email, and date/time are required.'];
        }

        if ($reminder_minutes < 0) {
            $reminder_minutes = 0;
        }
        if ($duration_minutes < 1) {
            $duration_minutes = 30;
        }

        try {
            $start = new DateTimeImmutable($when, new DateTimeZone($timezone));
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'Invalid date/time.'];
        }

        $end = $start->modify('+' . $duration_minutes . ' minutes');

        $access_token = $this->get_valid_access_token();
        if ($access_token === '') {
            return ['ok' => false, 'message' => 'Google Calendar is not connected. Connect it first.'];
        }

        $body = [
            'summary' => $title,
            'description' => $description !== '' ? $description : ('Reminder for ' . $email),
            'start' => [
                'dateTime' => $start->format(DateTimeInterface::RFC3339),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $end->format(DateTimeInterface::RFC3339),
                'timeZone' => $timezone,
            ],
            'attendees' => [
                ['email' => $email],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => $reminder_minutes],
                    ['method' => 'popup', 'minutes' => $reminder_minutes],
                ],
            ],
        ];

        $url = self::EVENTS_URL . '?sendUpdates=all';
        $result = $this->http_json('POST', $url, $body, $access_token);

        if ($result === false || empty($result['id'])) {
            $msg = is_array($result) && !empty($result['error']['message'])
                ? $result['error']['message']
                : 'Failed to create calendar event.';
            log_message('error', 'Google Calendar create event failed');
            return ['ok' => false, 'message' => $msg];
        }

        return [
            'ok' => true,
            'message' => 'Reminder created.',
            'data' => [
                'id' => $result['id'],
                'summary' => isset($result['summary']) ? $result['summary'] : $title,
                'htmlLink' => isset($result['htmlLink']) ? $result['htmlLink'] : '',
            ],
        ];
    }

    /**
     * @return array{client_id:string,client_secret:string}
     */
    private function load_credentials()
    {
        $empty = ['client_id' => '', 'client_secret' => ''];
        if (!is_file($this->credentials_file)) {
            return $empty;
        }

        $raw = @file_get_contents($this->credentials_file);
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return $empty;
        }

        $block = null;
        if (isset($data['web']) && is_array($data['web'])) {
            $block = $data['web'];
        } elseif (isset($data['installed']) && is_array($data['installed'])) {
            $block = $data['installed'];
        }

        if (!$block) {
            return $empty;
        }

        return [
            'client_id' => isset($block['client_id']) ? (string) $block['client_id'] : '',
            'client_secret' => isset($block['client_secret']) ? (string) $block['client_secret'] : '',
        ];
    }

    private function load_token()
    {
        if (!is_file($this->token_file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($this->token_file), true);
        return is_array($data) ? $data : [];
    }

    private function get_valid_access_token()
    {
        $token = $this->load_token();
        if (empty($token['access_token']) && empty($token['refresh_token'])) {
            return '';
        }

        $expires_at = isset($token['expires_at']) ? (int) $token['expires_at'] : 0;
        if (!empty($token['access_token']) && $expires_at > (time() + 60)) {
            return (string) $token['access_token'];
        }

        if (empty($token['refresh_token'])) {
            return !empty($token['access_token']) ? (string) $token['access_token'] : '';
        }

        $creds = $this->load_credentials();
        $response = $this->http_post(self::TOKEN_URL, [
            'client_id' => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if ($response === false || empty($response['access_token'])) {
            log_message('error', 'Google Calendar token refresh failed');
            return '';
        }

        $token['access_token'] = $response['access_token'];
        if (!empty($response['expires_in'])) {
            $token['expires_at'] = time() + (int) $response['expires_in'];
        }
        if (!empty($response['refresh_token'])) {
            $token['refresh_token'] = $response['refresh_token'];
        }
        $this->write_json($this->token_file, $token);

        return (string) $token['access_token'];
    }

    private function write_json($path, array $data)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        return @file_put_contents($path, $json) !== false;
    }

    /**
     * Resolve CA bundle path for Windows/WAMP (fixes cURL errno 60).
     *
     * @return string Absolute path or empty if none found
     */
    private function get_ca_bundle_path()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $candidates = [
            $this->dir . DIRECTORY_SEPARATOR . 'cacert.pem',
            APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'cacert.pem',
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:\\wamp64\\bin\\php\\php8.4.0\\extras\\ssl\\cacert.pem',
            'C:\\wamp64\\bin\\php\\php8.3.14\\extras\\ssl\\cacert.pem',
            'C:\\wamp64\\bin\\php\\php8.2.26\\extras\\ssl\\cacert.pem',
            'C:\\wamp64\\bin\\php\\php8.1.33\\extras\\ssl\\cacert.pem',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path !== '' && is_file($path)) {
                $cached = $path;
                return $cached;
            }
        }

        $cached = '';
        return $cached;
    }

    /**
     * Apply common cURL SSL options for WAMP.
     *
     * @param resource|\CurlHandle $ch
     */
    private function apply_curl_ssl($ch)
    {
        $ca = $this->get_ca_bundle_path();
        if ($ca !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            return;
        }

        // Last resort for local WAMP only when CA bundle is missing.
        // Prefer placing cacert.pem in application/cache/google_calendar/
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        log_message('error', 'Google Calendar: CA bundle missing; SSL verify disabled for this request');
    }

    private function http_post($url, array $fields)
    {
        if (!function_exists('curl_init')) {
            return $this->http_post_stream($url, $fields);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $this->apply_curl_ssl($ch);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $raw === false) {
            log_message('error', 'Google Calendar cURL error: ' . $error . ' (errno ' . $errno . ')');
            // Fallback when SSL/cURL fails
            $fallback = $this->http_post_stream($url, $fields);
            if ($fallback !== false) {
                return $fallback;
            }
            return false;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            log_message('error', 'Google Calendar token response not JSON (HTTP ' . $http_code . ')');
            return false;
        }

        return $decoded;
    }

    /**
     * POST form body via PHP streams (fallback when cURL SSL fails).
     *
     * @param string $url
     * @param array $fields
     * @return array|false
     */
    private function http_post_stream($url, array $fields)
    {
        $body = http_build_query($fields);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . 'Content-Length: ' . strlen($body) . "\r\n",
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $ca = $this->get_ca_bundle_path();
        if ($ca !== '') {
            $opts['ssl']['verify_peer'] = true;
            $opts['ssl']['verify_peer_name'] = true;
            $opts['ssl']['cafile'] = $ca;
        }

        $ctx = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            log_message('error', 'Google Calendar stream POST failed');
            return false;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : false;
    }

    private function http_json($method, $url, array $body, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $this->apply_curl_ssl($ch);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $raw === false) {
            return false;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : false;
    }
}
