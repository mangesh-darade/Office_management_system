<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Integration Helper
 * 
 * Provides functions to retrieve API credentials from the api_integrations table
 */

if (!function_exists('oms_curl_close')) {
    /**
     * Close a cURL handle. curl_close() is a no-op since PHP 8.0 and deprecated in 8.5.
     *
     * @param resource|\CurlHandle|false|null $ch
     * @return void
     */
    function oms_curl_close($ch)
    {
        if ($ch === null || $ch === false) {
            return;
        }
        if (PHP_VERSION_ID < 80500 && function_exists('curl_close')) {
            curl_close($ch);
        }
    }
}

if (!function_exists('get_api_integration')) {
    /**
     * Get API integration by service type
     * 
     * @param string $service_type 'sendgrid', 'whatsapp', 'smtp'
     * @param int|null $integration_id Optional: specific integration ID
     * @return object|null Integration object or null
     */
    function get_api_integration($service_type, $integration_id = null) {
        $CI =& get_instance();
        $CI->load->model('Api_integration_model', 'api');
        
        if ($integration_id) {
            return $CI->api->get_by_id($integration_id);
        }
        
        return $CI->api->get_default($service_type);
    }
}

if (!function_exists('get_sendgrid_credentials')) {
    /**
     * Get SendGrid credentials from database
     * 
     * @param int|null $integration_id Optional: specific integration ID
     * @return array ['api_key' => string, 'from_email' => string, 'from_name' => string, 'integration_id' => int]
     */
    function get_sendgrid_credentials($integration_id = null) {
        $integration = get_api_integration('sendgrid', $integration_id);
        
        if (!$integration || !$integration->is_active) {
            return [
                'api_key' => '',
                'from_email' => '',
                'from_name' => '',
                'integration_id' => null
            ];
        }
        
        return [
            'api_key' => $integration->account_id ?: '',
            'from_email' => $integration->from_email ?: '',
            'from_name' => $integration->from_name ?: '',
            'integration_id' => $integration->id
        ];
    }
}

if (!function_exists('get_whatsapp_credentials')) {
    /**
     * Get WhatsApp Cloud API credentials from api_integrations.
     *
     * Field map: account_id = Phone Number ID, auth_token = access token,
     * content_sid = WABA ID, from_number = display phone, from_name = default template.
     *
     * @param int|null $integration_id Optional: specific integration ID
     * @return array
     */
    function get_whatsapp_credentials($integration_id = null) {
        $integration = get_api_integration('whatsapp', $integration_id);
        $empty = array(
            'phone_number_id' => '',
            'access_token' => '',
            'waba_id' => '',
            'display_phone' => '',
            'app_secret' => '',
            'verify_token' => '',
            'default_template' => '',
            'integration_id' => null,
        );

        if (!$integration || !$integration->is_active) {
            return $empty;
        }

        $raw_token = $integration->auth_token ? (string) $integration->auth_token : '';
        $norm_token = normalize_meta_access_token($raw_token);
        if ($norm_token !== '' && $norm_token !== trim($raw_token) && !empty($integration->id)) {
            $CI =& get_instance();
            $CI->db->where('id', (int) $integration->id)->update('api_integrations', array(
                'auth_token' => $norm_token,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }

        return array(
            'phone_number_id' => $integration->account_id ? trim($integration->account_id) : '',
            'access_token' => $norm_token,
            'waba_id' => $integration->content_sid ? trim($integration->content_sid) : '',
            'display_phone' => $integration->from_number ? trim($integration->from_number) : '',
            'app_secret' => isset($integration->app_secret) ? trim((string) $integration->app_secret) : '',
            'verify_token' => isset($integration->webhook_verify_token) ? trim((string) $integration->webhook_verify_token) : '',
            'default_template' => $integration->from_name ? trim($integration->from_name) : '',
            'integration_id' => $integration->id,
        );
    }
}

if (!function_exists('normalize_meta_access_token')) {
    /**
     * One Meta token only: strip Bearer, quotes, whitespace; if two tokens were pasted, keep the last.
     *
     * @param string $token
     * @return string
     */
    function normalize_meta_access_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return '';
        }
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }
        $token = str_replace(array("\r", "\n", "\t"), ' ', $token);
        $parts = preg_split('/\s+/', $token, -1, PREG_SPLIT_NO_EMPTY);
        $candidates = array();
        foreach ($parts as $part) {
            $part = trim($part, "\"'");
            if ($part === '') {
                continue;
            }
            if (stripos($part, 'Bearer ') === 0) {
                $part = trim(substr($part, 7));
            }
            if ($part !== '') {
                $candidates[] = $part;
            }
        }
        if (empty($candidates)) {
            return '';
        }
        $joined = implode(' ', $candidates);
        if (preg_match_all('/EAA[A-Za-z0-9_\-]+/', $joined, $m) && !empty($m[0])) {
            return (string) end($m[0]);
        }
        return (string) end($candidates);
    }
}

if (!function_exists('sanitize_meta_api_error')) {
    /**
     * Never show access tokens in UI flash messages.
     *
     * @param string $message
     * @return string
     */
    function sanitize_meta_api_error($message)
    {
        $message = (string) $message;
        if (stripos($message, 'Malformed access token') !== false || preg_match('/EAA[A-Za-z0-9_\-]{20,}/', $message)) {
            return 'Malformed access token. In API Integrations, paste a single Meta System User token (one token only). On edit, leave Auth Token blank to keep the current value.';
        }
        $message = preg_replace('/EAA[A-Za-z0-9_\-]+/', '[token]', $message);
        return $message;
    }
}

if (!function_exists('format_meta_graph_error')) {
    /**
     * Build a flash-safe Meta Graph error string from a whatsapp_graph_get() result.
     *
     * @param array $res {http:int, data:?array, curl_error?:string}
     * @param string $fallback
     * @return string
     */
    function format_meta_graph_error($res, $fallback = 'Meta Graph request failed.')
    {
        if (!empty($res['curl_error'])) {
            return 'Could not reach Meta Graph API (' . sanitize_meta_api_error($res['curl_error']) . ').';
        }
        $http = isset($res['http']) ? (int) $res['http'] : 0;
        $data = (!empty($res['data']) && is_array($res['data'])) ? $res['data'] : array();
        $msg = $fallback;
        if (!empty($data['error']['message'])) {
            $msg = sanitize_meta_api_error($data['error']['message']);
        }
        $code = isset($data['error']['code']) ? (int) $data['error']['code'] : 0;
        $sub = isset($data['error']['error_subcode']) ? (int) $data['error']['error_subcode'] : 0;
        $type = !empty($data['error']['type']) ? (string) $data['error']['type'] : '';
        $bits = array($msg);
        if ($http > 0) {
            $bits[] = 'HTTP ' . $http;
        }
        if ($code > 0) {
            $bits[] = 'code ' . $code . ($sub ? ('/' . $sub) : '');
        }
        if ($type !== '') {
            $bits[] = $type;
        }
        return implode(' | ', $bits);
    }
}

if (!function_exists('get_smtp_credentials')) {
    /**
     * Get SMTP credentials from database
     * 
     * @param int|null $integration_id Optional: specific integration ID
     * @return array ['host' => string, 'port' => string, 'user' => string, 'password' => string, 'from_email' => string, 'from_name' => string, 'integration_id' => int]
     */
    function get_smtp_credentials($integration_id = null) {
        $integration = get_api_integration('smtp', $integration_id);
        
        if (!$integration || !$integration->is_active) {
            return [
                'host' => '',
                'port' => '',
                'user' => '',
                'password' => '',
                'from_email' => '',
                'from_name' => '',
                'integration_id' => null
            ];
        }
        
        // For SMTP, account_id might be host, auth_token is password
        // You may need to adjust based on your SMTP structure
        return [
            'host' => $integration->account_id ?: '',
            'port' => '587', // Default, you might want to add port field
            'user' => $integration->from_email ?: '', // Using from_email as SMTP user
            'password' => $integration->auth_token ?: '',
            'from_email' => $integration->from_email ?: '',
            'from_name' => $integration->from_name ?: '',
            'integration_id' => $integration->id
        ];
    }
}

if (!function_exists('is_api_integration_configured')) {
    /**
     * Check if API integration is configured and active
     * 
     * @param string $service_type 'sendgrid', 'whatsapp', 'smtp'
     * @return bool
     */
    function is_api_integration_configured($service_type) {
        $integration = get_api_integration($service_type);
        return $integration && $integration->is_active && 
               !empty($integration->account_id) && 
               !empty($integration->auth_token);
    }
}

if (!function_exists('whatsapp_graph_version')) {
    function whatsapp_graph_version()
    {
        return 'v23.0';
    }
}

if (!function_exists('normalize_whatsapp_phone')) {
    /**
     * Format a phone number for Meta Cloud API (digits only, E.164 without +).
     *
     * @param string $phone
     * @param string $default_country Country code without + (e.g. 91)
     * @return string
     */
    function normalize_whatsapp_phone($phone, $default_country = '91')
    {
        $raw = (string) $phone;
        if (stripos($raw, 'whatsapp:') === 0) {
            $raw = substr($raw, 9);
        }
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return '';
        }
        if ($default_country !== '' && strlen($digits) === 10) {
            $digits = $default_country . $digits;
        }
        return $digits;
    }
}

if (!function_exists('send_whatsapp_message')) {
    /**
     * Send a WhatsApp message via Meta Cloud API (shared by Whatsapp + Coaching).
     *
     * @param string $phone Recipient phone
     * @param string $message Plain-text body (ignored when template_name is set)
     * @param array  $options template_name, language, template_components, integration_id, default_country, skip_inbox
     * @return array ['success' => bool, 'error' => string|null]
     */
    function send_whatsapp_message($phone, $message, $options = array())
    {
        $creds = get_whatsapp_credentials(
            isset($options['integration_id']) ? $options['integration_id'] : null
        );
        if (empty($creds['phone_number_id']) || empty($creds['access_token'])) {
            return array('success' => false, 'error' => 'WhatsApp is not configured.');
        }

        $default_country = isset($options['default_country']) ? $options['default_country'] : '91';
        $to = normalize_whatsapp_phone($phone, $default_country);
        if ($to === '') {
            return array('success' => false, 'error' => 'Invalid phone number.');
        }

        $template_name = '';
        if (!empty($options['template_name'])) {
            $template_name = trim((string) $options['template_name']);
        }

        if ($template_name !== '') {
            $template_name = strtolower(trim($template_name));
            if (!preg_match('/^[a-z0-9_]+$/', $template_name)) {
                return array('success' => false, 'error' => 'Invalid template name.');
            }
            $language = !empty($options['language']) ? $options['language'] : 'en_US';
            $language = preg_replace('/[^a-zA-Z_]/', '', (string) $language);
            if ($language === '') {
                $language = 'en_US';
            }
            $payload = array(
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => array(
                    'name' => $template_name,
                    'language' => array('code' => $language),
                ),
            );
            if (!empty($options['template_components']) && is_array($options['template_components'])) {
                $payload['template']['components'] = $options['template_components'];
            }
            $body_text = $message !== '' ? $message : ('Template: ' . $template_name);
        } else {
            $message = trim((string) $message);
            if ($message === '') {
                return array('success' => false, 'error' => 'Message cannot be empty.');
            }
            if (mb_strlen($message) > 4096) {
                return array('success' => false, 'error' => 'Message exceeds WhatsApp 4096 character limit.');
            }
            $payload = array(
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => array(
                    'preview_url' => !empty($options['preview_url']),
                    'body' => $message,
                ),
            );
            $body_text = $message;
        }

        $url = 'https://graph.facebook.com/' . whatsapp_graph_version() . '/' . rawurlencode($creds['phone_number_id']) . '/messages';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $creds['access_token'],
            'Content-Type: application/json',
        ));
        // Always verify TLS outside local WAMP; mis-set ENVIRONMENT must not disable SSL in prod.
        $ssl_verify = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? false : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl_verify ? 2 : 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        oms_curl_close($ch);

        if ($curl_error) {
            log_message('error', 'WhatsApp Cloud API cURL failed | http:' . $http_code);
            return array('success' => false, 'error' => 'Could not reach WhatsApp.');
        }

        $response_data = json_decode($response, true);
        if ($http_code >= 200 && $http_code < 300 && is_array($response_data) && !empty($response_data['messages'][0]['id'])) {
            $wamid = $response_data['messages'][0]['id'];
            if (empty($options['skip_inbox'])) {
                $CI =& get_instance();
                $CI->load->model('Whatsapp_model', 'whatsapp_inbox');
                $user_id = 0;
                if (isset($CI->session)) {
                    $user_id = (int) $CI->session->userdata('user_id');
                }
                $CI->whatsapp_inbox->record_outbound($to, $body_text, $wamid, $user_id, $template_name);
            }
            return array(
                'success' => true,
                'error' => null,
                'wamid' => $wamid,
                'message_sid' => $wamid,
                'status' => 'accepted',
            );
        }

        $graph_code = 0;
        $graph_sub = 0;
        if (is_array($response_data) && isset($response_data['error']['code'])) {
            $graph_code = (int) $response_data['error']['code'];
        }
        if (is_array($response_data) && isset($response_data['error']['error_subcode'])) {
            $graph_sub = (int) $response_data['error']['error_subcode'];
        }
        log_message('error', 'WhatsApp Cloud API send failed | http:' . $http_code . ' | code:' . $graph_code . ($graph_sub ? ('/' . $graph_sub) : ''));

        $err = 'WhatsApp could not send the message.';
        $meta_raw = '';
        if (!empty($response_data['error']['message'])) {
            $meta_raw = sanitize_meta_api_error($response_data['error']['message']);
            if ($http_code > 0 || $graph_code > 0) {
                $meta_raw .= ' | HTTP ' . (int) $http_code;
                if ($graph_code > 0) {
                    $meta_raw .= ' | code ' . $graph_code . ($graph_sub ? ('/' . $graph_sub) : '');
                }
            }
        }
        if ($graph_code === 100 && $graph_sub === 33) {
            $err = 'This System User token cannot send from the saved Phone Number ID. In Meta Business Manager: System users → assign the WhatsApp account → generate a new token → save it in API Integrations.';
        } elseif ($graph_code === 131047) {
            $err = 'Free-text is blocked outside the 24-hour window. Send an approved template first.';
        } elseif ($graph_code === 132001) {
            $err = 'Template name or language does not match WhatsApp Manager.';
        } elseif ($graph_code === 190) {
            $err = 'Access token expired or invalid. Generate a new token and save it in API Integrations.';
        } elseif ($graph_code === 4 || $graph_code === 80007 || $graph_code === 130429) {
            $err = 'WhatsApp rate limit reached. Wait a moment and try again.';
        } elseif ($meta_raw !== '') {
            $err = $meta_raw;
        }
        if ($meta_raw !== '' && $err !== $meta_raw) {
            $err .= ' Meta: ' . $meta_raw;
        }
        return array('success' => false, 'error' => $err);
    }
}

if (!function_exists('whatsapp_graph_get')) {
    /**
     * GET a Graph API path (no leading slash). Never logs the access token.
     *
     * @param string $path
     * @param string $access_token
     * @return array {http:int, data:array|null, curl_error:string}
     */
    function whatsapp_graph_get($path, $access_token)
    {
        $url = 'https://graph.facebook.com/' . whatsapp_graph_version() . '/' . ltrim($path, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $ssl_verify = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? false : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl_verify ? 2 : 0);
        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        oms_curl_close($ch);
        $data = json_decode($response, true);
        return array(
            'http' => $http_code,
            'data' => is_array($data) ? $data : null,
            'curl_error' => $curl_error,
        );
    }
}

if (!function_exists('discover_whatsapp_waba_id')) {
    /**
     * Resolve a WABA the token can actually read (stored ID, phone-number parent, assigned assets).
     *
     * @param array $creds
     * @return array {waba_id:string, error:?string}
     */
    function discover_whatsapp_waba_id($creds)
    {
        static $cache = array();
        $token = isset($creds['access_token']) ? $creds['access_token'] : '';
        $cache_key = md5($token . '|' . (isset($creds['waba_id']) ? $creds['waba_id'] : '') . '|' . (isset($creds['phone_number_id']) ? $creds['phone_number_id'] : ''));
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $candidates = array();
        $last_graph = '';
        if (!empty($creds['waba_id'])) {
            $candidates[] = trim((string) $creds['waba_id']);
        }
        if (!empty($creds['phone_number_id'])) {
            $phone = rawurlencode($creds['phone_number_id']);
            $r = whatsapp_graph_get($phone . '?fields=whatsapp_business_account', $token);
            if (!empty($r['data']['whatsapp_business_account']['id'])) {
                $candidates[] = (string) $r['data']['whatsapp_business_account']['id'];
            } elseif ($r['http'] < 200 || $r['http'] >= 300 || !empty($r['curl_error'])) {
                $last_graph = format_meta_graph_error($r, 'Phone Number ID is not readable.');
            }
        }
        $assigned = whatsapp_graph_get('me/assigned_whatsapp_business_accounts?fields=id,name&limit=25', $token);
        if (!empty($assigned['data']['data']) && is_array($assigned['data']['data'])) {
            foreach ($assigned['data']['data'] as $row) {
                if (!empty($row['id'])) {
                    $candidates[] = (string) $row['id'];
                }
            }
        } elseif ($assigned['http'] < 200 || $assigned['http'] >= 300 || !empty($assigned['curl_error'])) {
            $last_graph = format_meta_graph_error($assigned, 'Could not list assigned WhatsApp accounts.');
        }
        // Only hit debug_token / businesses when stored + assigned did not already give candidates.
        if (count(array_filter(array_unique($candidates))) < 2) {
            $debug = whatsapp_graph_get('debug_token?input_token=' . rawurlencode($token), $token);
            if (!empty($debug['data']['data']['granular_scopes']) && is_array($debug['data']['data']['granular_scopes'])) {
                foreach ($debug['data']['data']['granular_scopes'] as $scope) {
                    if (empty($scope['target_ids']) || !is_array($scope['target_ids'])) {
                        continue;
                    }
                    foreach ($scope['target_ids'] as $tid) {
                        $tid = trim((string) $tid);
                        if ($tid !== '') {
                            $candidates[] = $tid;
                        }
                    }
                }
            }
            $biz = whatsapp_graph_get('me/businesses?fields=id,name&limit=10', $token);
            if (!empty($biz['data']['data']) && is_array($biz['data']['data'])) {
                $biz_rows = array_slice($biz['data']['data'], 0, 3);
                foreach ($biz_rows as $b) {
                    if (empty($b['id'])) {
                        continue;
                    }
                    $bid = rawurlencode((string) $b['id']);
                    foreach (array('owned_whatsapp_business_accounts', 'client_whatsapp_business_accounts') as $edge) {
                        $wa = whatsapp_graph_get($bid . '/' . $edge . '?fields=id,name&limit=25', $token);
                        if (empty($wa['data']['data']) || !is_array($wa['data']['data'])) {
                            continue;
                        }
                        foreach ($wa['data']['data'] as $row) {
                            if (!empty($row['id'])) {
                                $candidates[] = (string) $row['id'];
                            }
                        }
                    }
                }
            }
        }
        $seen = array();
        $probed = 0;
        foreach ($candidates as $id) {
            $id = trim($id);
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            if ($probed >= 6) {
                break;
            }
            $probed++;
            $enc = rawurlencode($id);
            $check = whatsapp_graph_get($enc . '/message_templates?fields=name&limit=1', $token);
            if ($check['http'] >= 200 && $check['http'] < 300) {
                $result = array('waba_id' => $id, 'error' => null);
                $cache[$cache_key] = $result;
                return $result;
            }
            if ($check['http'] < 200 || $check['http'] >= 300 || !empty($check['curl_error'])) {
                $last_graph = format_meta_graph_error($check, 'WABA message_templates request failed.');
            }
            $phones = whatsapp_graph_get($enc . '/phone_numbers?fields=id&limit=1', $token);
            if ($phones['http'] >= 200 && $phones['http'] < 300) {
                $result = array('waba_id' => $id, 'error' => null);
                $cache[$cache_key] = $result;
                return $result;
            }
            if ($phones['http'] < 200 || $phones['http'] >= 300 || !empty($phones['curl_error'])) {
                $last_graph = format_meta_graph_error($phones, 'WABA phone_numbers request failed.');
            }
        }
        $me = whatsapp_graph_get('me?fields=id,name', $token);
        $who = !empty($me['data']['name']) ? $me['data']['name'] : 'this System User';
        $parts = array();
        if ($last_graph !== '') {
            $parts[] = 'Meta: ' . $last_graph;
        }
        $parts[] = 'System User "' . $who . '" cannot read WhatsApp templates (no WhatsApp account assigned to this token). In Meta Business Manager: Business settings → System users → ' . $who . ' → Assign assets → WhatsApp accounts (manage templates). Generate a new token, save it in API Integrations, then Sync again.';
        $result = array(
            'waba_id' => '',
            'error' => implode(' ', $parts),
        );
        $cache[$cache_key] = $result;
        return $result;
    }
}

if (!function_exists('fetch_whatsapp_templates_from_meta')) {
    /**
     * Pull message templates from Meta Graph API (paginated).
     *
     * @param int|null $integration_id
     * @return array {ok:bool, templates:array, error:?string}
     */
    function fetch_whatsapp_templates_from_meta($integration_id = null)
    {
        $creds = get_whatsapp_credentials($integration_id);
        if (empty($creds['access_token'])) {
            return array('ok' => false, 'templates' => array(), 'error' => 'WhatsApp access token is required.');
        }

        $found = discover_whatsapp_waba_id($creds);
        $waba_id = !empty($found['waba_id']) ? $found['waba_id'] : '';
        if ($waba_id === '') {
            return array('ok' => false, 'templates' => array(), 'error' => $found['error']);
        }
        if ($waba_id !== $creds['waba_id'] && !empty($creds['integration_id'])) {
            $CI =& get_instance();
            $CI->db->where('id', (int) $creds['integration_id'])->update('api_integrations', array(
                'content_sid' => $waba_id,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }

        $out = array();
        $after = '';
        for ($page = 0; $page < 15; $page++) {
            $path = rawurlencode($waba_id) . '/message_templates?fields=id,name,status,language,category,components&limit=100';
            if ($after !== '') {
                $path .= '&after=' . rawurlencode($after);
            }
            $res = whatsapp_graph_get($path, $creds['access_token']);
            if ($res['curl_error']) {
                log_message('error', 'WhatsApp template sync cURL failed | http:' . $res['http']);
                return array('ok' => false, 'templates' => array(), 'error' => format_meta_graph_error($res, 'Could not reach Meta Graph API.'));
            }
            $data = $res['data'];
            if (!is_array($data)) {
                return array('ok' => false, 'templates' => array(), 'error' => 'Meta returned an invalid response.');
            }
            if ($res['http'] < 200 || $res['http'] >= 300) {
                $msg = format_meta_graph_error($res, 'Meta template sync failed.');
                log_message('error', 'WhatsApp template sync failed | http:' . $res['http']);
                return array('ok' => false, 'templates' => array(), 'error' => $msg);
            }

            $rows = !empty($data['data']) && is_array($data['data']) ? $data['data'] : array();
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['name'])) {
                    continue;
                }
                $body = '';
                if (!empty($row['components']) && is_array($row['components'])) {
                    foreach ($row['components'] as $comp) {
                        if (!is_array($comp) || empty($comp['type'])) {
                            continue;
                        }
                        if (strtoupper($comp['type']) === 'BODY' && !empty($comp['text'])) {
                            $body = (string) $comp['text'];
                            break;
                        }
                    }
                }
                $out[] = array(
                    'meta_id' => isset($row['id']) ? (string) $row['id'] : '',
                    'name' => $row['name'],
                    'language' => isset($row['language']) ? $row['language'] : 'en_US',
                    'category' => isset($row['category']) ? $row['category'] : '',
                    'status' => isset($row['status']) ? strtoupper($row['status']) : 'APPROVED',
                    'body' => $body,
                );
            }

            if (empty($data['paging']['cursors']['after']) || empty($data['paging']['next'])) {
                break;
            }
            $after = (string) $data['paging']['cursors']['after'];
        }

        return array('ok' => true, 'templates' => $out, 'error' => null);
    }
}

if (!function_exists('sync_whatsapp_templates')) {
    /**
     * Fetch templates from Meta and replace the local cache.
     *
     * @param int|null $integration_id
     * @return array {ok:bool, count:int, error:?string}
     */
    function sync_whatsapp_templates($integration_id = null)
    {
        $fetched = fetch_whatsapp_templates_from_meta($integration_id);
        if (empty($fetched['ok'])) {
            return array('ok' => false, 'count' => 0, 'error' => !empty($fetched['error']) ? $fetched['error'] : 'Sync failed.');
        }
        $CI =& get_instance();
        $CI->load->model('Whatsapp_model', 'whatsapp_inbox');
        $count = $CI->whatsapp_inbox->replace_templates($fetched['templates']);
        return array('ok' => true, 'count' => $count, 'error' => null);
    }
}

if (!function_exists('list_whatsapp_templates')) {
    /**
     * List cached WhatsApp templates (run Sync from Meta to refresh).
     *
     * @param int|null $integration_id unused; kept for callers
     * @param bool $approved_only
     * @return array
     */
    function list_whatsapp_templates($integration_id = null, $approved_only = true)
    {
        $CI =& get_instance();
        $CI->load->model('Whatsapp_model', 'whatsapp_inbox');
        return $CI->whatsapp_inbox->list_templates($approved_only);
    }
}

if (!function_exists('whatsapp_template_placeholder_count')) {
    /**
     * Count {{1}}..{{n}} placeholders in a template body preview.
     *
     * @param string $body
     * @return int
     */
    function whatsapp_template_placeholder_count($body)
    {
        if (!preg_match_all('/\{\{(\d+)\}\}/', (string) $body, $m) || empty($m[1])) {
            return 0;
        }
        $max = 0;
        foreach ($m[1] as $n) {
            $n = (int) $n;
            if ($n > $max) {
                $max = $n;
            }
        }
        return $max;
    }
}

if (!function_exists('build_whatsapp_template_body_components')) {
    /**
     * Build Meta template BODY components from ordered variable values.
     *
     * @param array $vars
     * @return array
     */
    function build_whatsapp_template_body_components($vars)
    {
        $params = array();
        foreach ($vars as $v) {
            $text = trim((string) $v);
            if ($text === '') {
                continue;
            }
            $params[] = array('type' => 'text', 'text' => mb_substr($text, 0, 1024));
        }
        if (empty($params)) {
            return array();
        }
        return array(
            array(
                'type' => 'body',
                'parameters' => $params,
            ),
        );
    }
}

if (!function_exists('whatsapp_collect_template_vars_from_post')) {
    /**
     * Read template_var[] from POST (CI input).
     *
     * @return array
     */
    function whatsapp_collect_template_vars_from_post()
    {
        $CI =& get_instance();
        $vars = $CI->input->post('template_var');
        if (!is_array($vars)) {
            return array();
        }
        $out = array();
        foreach ($vars as $v) {
            $out[] = trim((string) $v);
        }
        return $out;
    }
}

if (!function_exists('whatsapp_conversation_window')) {
    /**
     * Customer-care 24h window based on last inbound message time.
     *
     * @param int $conversation_id
     * @return array {open:bool, last_inbound_at:?string, expires_at:?string, hours_left:float}
     */
    function whatsapp_conversation_window($conversation_id)
    {
        $CI =& get_instance();
        $CI->load->model('Whatsapp_model', 'whatsapp_inbox');
        $last = $CI->whatsapp_inbox->last_inbound_at((int) $conversation_id);
        if (!$last) {
            return array(
                'open' => false,
                'last_inbound_at' => null,
                'expires_at' => null,
                'hours_left' => 0,
            );
        }
        $ts = strtotime($last);
        $expires = $ts + (24 * 3600);
        $left = ($expires - time()) / 3600;
        return array(
            'open' => $left > 0,
            'last_inbound_at' => $last,
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'hours_left' => $left > 0 ? round($left, 1) : 0,
        );
    }
}

if (!function_exists('mark_whatsapp_message_read')) {
    /**
     * Mark an inbound WhatsApp message as read on Graph (blue ticks for customer).
     *
     * @param string $wamid
     * @param int|null $integration_id
     * @return bool
     */
    function mark_whatsapp_message_read($wamid, $integration_id = null)
    {
        $wamid = trim((string) $wamid);
        if ($wamid === '') {
            return false;
        }
        $creds = get_whatsapp_credentials($integration_id);
        if (empty($creds['phone_number_id']) || empty($creds['access_token'])) {
            return false;
        }
        $url = 'https://graph.facebook.com/' . whatsapp_graph_version() . '/'
            . rawurlencode($creds['phone_number_id']) . '/messages';
        $payload = array(
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $wamid,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $creds['access_token'],
            'Content-Type: application/json',
        ));
        $ssl_verify = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? false : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl_verify ? 2 : 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        oms_curl_close($ch);
        if ($http < 200 || $http >= 300) {
            log_message('error', 'WhatsApp mark read failed | http:' . $http);
            return false;
        }
        return true;
    }
}

if (!function_exists('whatsapp_inbound_body_from_message')) {
    /**
     * Build a display body from a Meta inbound message object (text, caption, media).
     *
     * @param array $msg
     * @return array {body:string, type:string, media_id:?string}
     */
    function whatsapp_inbound_body_from_message($msg)
    {
        $type = isset($msg['type']) ? (string) $msg['type'] : 'text';
        $body = '';
        $media_id = null;
        if ($type === 'text' && !empty($msg['text']['body'])) {
            $body = (string) $msg['text']['body'];
        } elseif ($type === 'button' && !empty($msg['button']['text'])) {
            $body = (string) $msg['button']['text'];
        } elseif ($type === 'interactive') {
            if (!empty($msg['interactive']['button_reply']['title'])) {
                $body = (string) $msg['interactive']['button_reply']['title'];
            } elseif (!empty($msg['interactive']['list_reply']['title'])) {
                $body = (string) $msg['interactive']['list_reply']['title'];
            }
        } elseif (in_array($type, array('image', 'video', 'audio', 'document', 'sticker'), true)) {
            $media_id = !empty($msg[$type]['id']) ? (string) $msg[$type]['id'] : null;
            $caption = '';
            if (!empty($msg[$type]['caption'])) {
                $caption = (string) $msg[$type]['caption'];
            } elseif (!empty($msg[$type]['filename'])) {
                $caption = (string) $msg[$type]['filename'];
            }
            $body = '[' . $type . ']';
            if ($caption !== '') {
                $body .= ' ' . $caption;
            }
            if ($media_id) {
                $body .= ' (media:' . $media_id . ')';
            }
        } else {
            $body = '[' . $type . ']';
        }
        return array(
            'body' => $body,
            'type' => $type,
            'media_id' => $media_id,
        );
    }
}

if (!function_exists('whatsapp_resolve_media_url')) {
    /**
     * Resolve a temporary Meta media download URL (valid ~5 minutes). Best-effort.
     *
     * @param string $media_id
     * @return string
     */
    function whatsapp_resolve_media_url($media_id)
    {
        $creds = get_whatsapp_credentials();
        if ($media_id === '' || empty($creds['access_token'])) {
            return '';
        }
        $res = whatsapp_graph_get(rawurlencode($media_id), $creds['access_token']);
        if (!empty($res['data']['url'])) {
            return (string) $res['data']['url'];
        }
        return '';
    }
}

if (!function_exists('diagnose_whatsapp_connection')) {
    /**
     * Check Meta token, WABA, and Phone Number ID. Persist IDs Graph can actually use.
     *
     * @param int|null $integration_id
     * @return array
     */
    function diagnose_whatsapp_connection($integration_id = null)
    {
        $creds = get_whatsapp_credentials($integration_id);
        $out = array(
            'ok' => false,
            'token_ok' => false,
            'phone_ok' => false,
            'waba_ok' => false,
            'system_user' => '',
            'phone_number_id' => $creds['phone_number_id'],
            'waba_id' => $creds['waba_id'],
            'display_phone' => $creds['display_phone'],
            'repaired' => array(),
            'steps' => array(),
            'error' => null,
            'graph_error' => null,
        );
        if (empty($creds['access_token'])) {
            $out['error'] = 'Access token is missing. Save a Meta System User token in API Integrations.';
            $out['steps'][] = 'Token: missing';
            return $out;
        }

        $token = $creds['access_token'];
        $me = whatsapp_graph_get('me?fields=id,name', $token);
        if ($me['http'] < 200 || $me['http'] >= 300) {
            $out['graph_error'] = format_meta_graph_error($me, 'Access token is invalid or expired.');
            $out['error'] = $out['graph_error'];
            $out['steps'][] = 'Token: FAIL — ' . $out['graph_error'];
            return $out;
        }
        $out['token_ok'] = true;
        $out['system_user'] = !empty($me['data']['name']) ? (string) $me['data']['name'] : '';
        $out['steps'][] = 'Token: OK' . ($out['system_user'] !== '' ? (' (' . $out['system_user'] . ')') : '');

        $found = discover_whatsapp_waba_id($creds);
        if (!empty($found['waba_id'])) {
            $out['waba_ok'] = true;
            $out['waba_id'] = $found['waba_id'];
            $out['steps'][] = 'WABA: OK (' . $out['waba_id'] . ')';
        } else {
            $waba_err = !empty($found['error']) ? $found['error'] : 'No WhatsApp Business Account readable with this token.';
            $out['steps'][] = 'WABA: FAIL';
            $out['graph_error'] = $waba_err;
        }

        $phone_id = $creds['phone_number_id'];
        $display = $creds['display_phone'];
        $phone_err = '';
        if ($phone_id !== '') {
            $pg = whatsapp_graph_get(rawurlencode($phone_id) . '?fields=id,display_phone_number,verified_name', $token);
            if ($pg['http'] >= 200 && $pg['http'] < 300 && !empty($pg['data']['id'])) {
                $out['phone_ok'] = true;
                $phone_id = (string) $pg['data']['id'];
                if (!empty($pg['data']['display_phone_number'])) {
                    $display = preg_replace('/\D/', '', (string) $pg['data']['display_phone_number']);
                }
            } else {
                $phone_err = format_meta_graph_error($pg, 'Phone Number ID is not readable with this token.');
                $out['graph_error'] = $phone_err;
            }
        } else {
            $phone_err = 'Phone Number ID is empty in API Integrations.';
        }
        if (!$out['phone_ok'] && $out['waba_id'] !== '') {
            $plist = whatsapp_graph_get(rawurlencode($out['waba_id']) . '/phone_numbers?fields=id,display_phone_number,verified_name&limit=20', $token);
            if (!empty($plist['data']['data'][0]['id'])) {
                $first = $plist['data']['data'][0];
                $phone_id = (string) $first['id'];
                $out['phone_ok'] = true;
                $phone_err = '';
                if (!empty($first['display_phone_number'])) {
                    $display = preg_replace('/\D/', '', (string) $first['display_phone_number']);
                }
            } elseif ($phone_err === '' && ($plist['http'] < 200 || $plist['http'] >= 300)) {
                $phone_err = format_meta_graph_error($plist, 'Could not list phone numbers on the WABA.');
                $out['graph_error'] = $phone_err;
            }
        }

        $out['phone_number_id'] = $phone_id;
        $out['display_phone'] = $display;
        if ($out['phone_ok']) {
            $out['steps'][] = 'Phone Number ID: OK (' . $phone_id . ')' . ($display !== '' ? (' / ' . $display) : '');
        } else {
            $out['steps'][] = 'Phone Number ID: FAIL' . ($phone_id !== '' ? (' (' . $phone_id . ')') : '');
            if ($phone_err !== '') {
                $out['graph_error'] = $phone_err;
            }
        }

        $upd = array();
        if ($out['waba_ok'] && $out['waba_id'] !== $creds['waba_id']) {
            $upd['content_sid'] = $out['waba_id'];
            $out['repaired'][] = 'WABA ID';
        }
        if ($out['phone_ok'] && $phone_id !== '' && $phone_id !== $creds['phone_number_id']) {
            $upd['account_id'] = $phone_id;
            $out['repaired'][] = 'Phone Number ID';
        }
        if ($display !== '' && $display !== $creds['display_phone']) {
            $upd['from_number'] = $display;
            $out['repaired'][] = 'display phone';
        }
        if (!empty($upd) && !empty($creds['integration_id'])) {
            $CI =& get_instance();
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $CI->db->where('id', (int) $creds['integration_id'])->update('api_integrations', $upd);
        }

        $out['ok'] = $out['token_ok'] && $out['phone_ok'];
        if ($out['ok']) {
            $warn = array();
            if ($creds['app_secret'] === '') {
                $warn[] = 'App Secret is empty — inbound webhooks will be rejected';
            }
            if ($creds['verify_token'] === '') {
                $warn[] = 'Webhook verify token is empty — Meta cannot verify the callback URL';
            }
            if (!$out['waba_ok']) {
                $warn[] = 'WABA not readable — template sync may fail until the WhatsApp account is assigned to this System User';
            }
            if (!empty($warn)) {
                $out['error'] = implode('. ', $warn) . '.';
            }
            return $out;
        }

        $who = $out['system_user'] !== '' ? $out['system_user'] : 'this System User';
        if (!empty($out['graph_error']) && strpos($out['graph_error'], 'System User') !== false) {
            $out['error'] = $out['graph_error'];
            return $out;
        }
        $parts = array();
        if (!empty($out['graph_error'])) {
            $parts[] = 'Meta: ' . $out['graph_error'];
        }
        $parts[] = 'System User "' . $who . '" cannot use the saved Phone Number ID. In Meta Business Manager: Business settings → System users → ' . $who . ' → Assign assets → WhatsApp accounts → generate a new token → save in API Integrations.';
        $out['error'] = implode(' ', $parts);
        return $out;
    }
}

if (!function_exists('format_whatsapp_diagnose_message')) {
    /**
     * Flash-safe summary of diagnose_whatsapp_connection().
     *
     * @param array $d
     * @return string
     */
    function format_whatsapp_diagnose_message($d)
    {
        $steps = (!empty($d['steps']) && is_array($d['steps'])) ? $d['steps'] : array();
        $detail = !empty($steps) ? implode(' · ', $steps) : '';

        if (!empty($d['ok'])) {
            $msg = 'WhatsApp connected. Token and Phone Number ID are usable.';
            if (!empty($d['repaired'])) {
                $msg .= ' Updated ' . implode(', ', $d['repaired']) . ' from Meta.';
            }
            if (!empty($d['display_phone'])) {
                $msg .= ' Number: ' . $d['display_phone'] . '.';
            }
            if ($detail !== '') {
                $msg .= ' [' . $detail . ']';
            }
            if (!empty($d['error'])) {
                $msg .= ' Warning: ' . $d['error'];
            }
            return $msg;
        }

        $msg = !empty($d['error']) ? $d['error'] : 'WhatsApp is not fully connected.';
        if ($detail !== '') {
            $msg .= ' [' . $detail . ']';
        }
        return $msg;
    }
}

if (!function_exists('validate_meta_whatsapp_signature')) {
    /**
     * Validate X-Hub-Signature-256 for Meta WhatsApp webhooks (HMAC-SHA256).
     *
     * @param string $raw_body
     * @param string $header
     * @param string $app_secret
     * @return bool
     */
    function validate_meta_whatsapp_signature($raw_body, $header, $app_secret)
    {
        if ($app_secret === '' || $app_secret === null || $header === '' || $header === null) {
            return false;
        }
        if (strpos($header, 'sha256=') !== 0) {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', (string) $raw_body, $app_secret);
        return hash_equals($expected, $header);
    }
}

if (!function_exists('handle_meta_whatsapp_webhook_http')) {
    /**
     * GET verify handshake + POST inbound/status events. Outputs and sets HTTP status.
     *
     * @return void
     */
    function handle_meta_whatsapp_webhook_http()
    {
        $CI =& get_instance();
        $creds = get_whatsapp_credentials();

        $method = strtolower((string) $CI->input->method(true));
        if ($method === 'get') {
            $mode = isset($_GET['hub_mode']) ? $_GET['hub_mode'] : $CI->input->get('hub_mode');
            if (isset($_GET['hub.mode'])) {
                $mode = $_GET['hub.mode'];
            }
            $token = isset($_GET['hub.verify_token']) ? $_GET['hub.verify_token'] : $CI->input->get('hub_verify_token');
            $challenge = isset($_GET['hub.challenge']) ? $_GET['hub.challenge'] : $CI->input->get('hub_challenge');
            if ($mode === 'subscribe' && $creds['verify_token'] !== '' && hash_equals($creds['verify_token'], (string) $token)) {
                $CI->output->set_status_header(200)->set_output((string) $challenge);
                return;
            }
            $CI->output->set_status_header(403);
            return;
        }

        if ($method !== 'post') {
            $CI->output->set_status_header(405);
            return;
        }

        $raw = file_get_contents('php://input');
        $sig = (string) $CI->input->get_request_header('X-Hub-Signature-256', true);
        if ($creds['app_secret'] === '' || !validate_meta_whatsapp_signature($raw, $sig, $creds['app_secret'])) {
            log_message('error', 'WhatsApp webhook: invalid signature');
            $CI->output->set_status_header(403);
            return;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $CI->output->set_status_header(400);
            return;
        }

        // Always ACK quickly; Meta retries aggressively on non-2xx.
        try {
            process_meta_whatsapp_webhook_payload($payload);
        } catch (Exception $e) {
            log_message('error', 'WhatsApp webhook process failed: ' . $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'WhatsApp webhook process failed: ' . $e->getMessage());
        }
        $CI->output->set_content_type('application/json')->set_status_header(200)->set_output(json_encode(array('ok' => true)));
    }
}

if (!function_exists('process_meta_whatsapp_webhook_payload')) {
    /**
     * Persist inbound messages / delivery statuses and optional coaching enquiry.
     *
     * @param array $payload
     * @return void
     */
    function process_meta_whatsapp_webhook_payload($payload)
    {
        $CI =& get_instance();
        $CI->load->model('Whatsapp_model', 'whatsapp_inbox');

        if (empty($payload['entry']) || !is_array($payload['entry'])) {
            return;
        }

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }
            foreach ($entry['changes'] as $change) {
                if (!is_array($change) || empty($change['value']) || !is_array($change['value'])) {
                    continue;
                }
                $value = $change['value'];
                if (!empty($value['messages']) && is_array($value['messages'])) {
                    $profile_name = '';
                    if (!empty($value['contacts'][0]['profile']['name'])) {
                        $profile_name = (string) $value['contacts'][0]['profile']['name'];
                    }
                    foreach ($value['messages'] as $msg) {
                        if (!is_array($msg)) {
                            continue;
                        }
                        $from = isset($msg['from']) ? normalize_whatsapp_phone($msg['from']) : '';
                        $wamid = isset($msg['id']) ? (string) $msg['id'] : '';
                        $parsed = whatsapp_inbound_body_from_message($msg);
                        $body = $parsed['body'];
                        $type = $parsed['type'];
                        if ($from === '') {
                            continue;
                        }
                        $CI->whatsapp_inbox->record_inbound($from, $profile_name, $body, $wamid, $type, $msg);
                        whatsapp_save_coaching_enquiry($from, $profile_name, $body);
                    }
                }
                if (!empty($value['statuses']) && is_array($value['statuses'])) {
                    foreach ($value['statuses'] as $st) {
                        if (!is_array($st) || empty($st['id'])) {
                            continue;
                        }
                        $status = isset($st['status']) ? (string) $st['status'] : '';
                        $CI->whatsapp_inbox->update_status_by_wamid((string) $st['id'], $status);
                    }
                }
            }
        }
    }
}

if (!function_exists('whatsapp_save_coaching_enquiry')) {
    /**
     * Mirror inbound WhatsApp into coaching CRM when that module is installed.
     *
     * @param string $phone
     * @param string $profile
     * @param string $body
     * @return void
     */
    function whatsapp_save_coaching_enquiry($phone, $profile, $body)
    {
        $CI =& get_instance();
        if (!$CI->db->table_exists('coaching_whatsapp_enquiries')) {
            return;
        }
        if (!isset($CI->coaching)) {
            $CI->load->model('Coaching_model', 'coaching');
        }

        $digits = preg_replace('/\D/', '', (string) $phone);
        $short = $digits;
        if (strlen($short) > 10) {
            $short = substr($short, -10);
        }

        $lead_id = null;
        $existing = null;
        if ($CI->db->table_exists('coaching_leads') && $short !== '') {
            $existing = $CI->db->where('phone', $short)->order_by('id', 'DESC')->get('coaching_leads')->row();
        }
        if (!$existing && $body !== '' && $short !== '') {
            $lead_id = $CI->coaching->lead_save(array(
                'full_name' => $profile !== '' ? $profile : ('WhatsApp ' . $short),
                'phone' => $short,
                'source' => 'whatsapp_inbound',
                'status' => 'new',
                'notes' => $body,
            ));
        }

        $CI->coaching->enquiry_save(array(
            'phone' => $short !== '' ? $short : $phone,
            'contact_name' => $profile !== '' ? $profile : null,
            'message' => $body !== '' ? $body : '(empty message)',
            'status' => 'open',
            'lead_id' => $lead_id ? $lead_id : ($existing ? (int) $existing->id : null),
        ));
    }
}

if (!function_exists('get_jitsi_config')) {
    /**
     * Get Jitsi Meet configuration from api_integrations.
     *
     * @param int|null $integration_id Optional specific integration ID
     * @return array domain, app_id, jwt_secret, enabled, integration_id
     */
    function get_jitsi_config($integration_id = null) {
        $integration = get_api_integration('jitsi', $integration_id);

        if (!$integration || !$integration->is_active || empty($integration->account_id)) {
            return array(
                'domain' => '',
                'app_id' => '',
                'jwt_secret' => '',
                'enabled' => false,
                'integration_id' => null,
                'is_public_server' => false,
                'jwt_enabled' => false,
                'security_warning' => '',
            );
        }

        $app_id = '';
        if (!empty($integration->from_name)) {
            $app_id = trim($integration->from_name);
        }
        if ($app_id === '' && !empty($integration->notes)) {
            $notes = json_decode($integration->notes, true);
            if (is_array($notes) && !empty($notes['app_id'])) {
                $app_id = trim((string) $notes['app_id']);
            }
        }

        $domain = preg_replace('#^https?://#i', '', trim($integration->account_id));
        $domain = rtrim($domain, '/');
        $domain_lc = strtolower($domain);
        $is_public = in_array($domain_lc, array('meet.jit.si', '8x8.vc'), true);
        $jwt_enabled = ($app_id !== '' && $integration->auth_token !== '');

        $security_warning = '';
        if ($is_public) {
            $security_warning = 'Public Jitsi server — meeting rooms are not private. Use self-hosted Jitsi with JWT for production.';
        } elseif ($domain !== '' && !$jwt_enabled) {
            $security_warning = 'JWT is not configured — anyone with the room link may join if they discover the URL.';
        }

        return array(
            'domain' => $domain,
            'app_id' => $app_id,
            'jwt_secret' => $integration->auth_token ? trim($integration->auth_token) : '',
            'enabled' => ($domain !== ''),
            'integration_id' => (int) $integration->id,
            'is_public_server' => $is_public,
            'jwt_enabled' => $jwt_enabled,
            'security_warning' => $security_warning,
        );
    }
}

if (!function_exists('get_all_api_integrations')) {
    /**
     * Get all API integrations (optionally filtered by service type)
     * 
     * @param string|null $service_type Optional: filter by service type
     * @return array Array of integration objects
     */
    function get_all_api_integrations($service_type = null) {
        $CI =& get_instance();
        $CI->load->model('Api_integration_model', 'api');
        return $CI->api->get_all($service_type);
    }
}

