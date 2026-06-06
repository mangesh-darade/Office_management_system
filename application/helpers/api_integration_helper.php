<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Integration Helper
 * 
 * Provides functions to retrieve API credentials from the api_integrations table
 */

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
     * Get WhatsApp (Twilio) credentials from database
     * 
     * @param int|null $integration_id Optional: specific integration ID
     * @return array ['account_sid' => string, 'auth_token' => string, 'from_number' => string, 'content_sid' => string, 'integration_id' => int]
     */
    function get_whatsapp_credentials($integration_id = null) {
        $integration = get_api_integration('whatsapp', $integration_id);
        
        if (!$integration || !$integration->is_active) {
            return [
                'account_sid' => '',
                'auth_token' => '',
                'from_number' => '',
                'content_sid' => '',
                'integration_id' => null
            ];
        }
        
        return [
            'account_sid' => $integration->account_id ?: '',
            'auth_token' => $integration->auth_token ?: '',
            'from_number' => $integration->from_number ?: '',
            'content_sid' => $integration->content_sid ?: '',
            'integration_id' => $integration->id
        ];
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

if (!function_exists('normalize_whatsapp_phone')) {
    /**
     * Format a phone number for Twilio WhatsApp API.
     *
     * @param string $phone
     * @param string $default_country Country code without + (e.g. 91)
     * @return string
     */
    function normalize_whatsapp_phone($phone, $default_country = '91')
    {
        if (strpos($phone, 'whatsapp:') === 0) {
            return $phone;
        }
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }
        if ($default_country !== '' && strlen($digits) === 10) {
            $digits = $default_country . $digits;
        }
        return 'whatsapp:+' . $digits;
    }
}

if (!function_exists('send_whatsapp_message')) {
    /**
     * Send a WhatsApp message via Twilio (shared by Whatsapp + Coaching modules).
     *
     * @param string $phone Recipient phone
     * @param string $message Plain-text body
     * @param array  $options content_sid, content_variables, integration_id, default_country
     * @return array ['success' => bool, 'error' => string|null]
     */
    function send_whatsapp_message($phone, $message, $options = array())
    {
        $creds = get_whatsapp_credentials(
            isset($options['integration_id']) ? $options['integration_id'] : null
        );
        if (empty($creds['account_sid']) || empty($creds['auth_token'])) {
            return array('success' => false, 'error' => 'WhatsApp credentials not configured.');
        }

        $default_country = isset($options['default_country']) ? $options['default_country'] : '91';
        $to = normalize_whatsapp_phone($phone, $default_country);
        if ($to === '') {
            return array('success' => false, 'error' => 'Invalid phone number.');
        }

        $from = $creds['from_number'] ? $creds['from_number'] : 'whatsapp:+14155238886';
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $creds['account_sid'] . '/Messages.json';
        $data = array('From' => $from, 'To' => $to);

        if (!empty($options['content_sid'])) {
            $data['ContentSid'] = $options['content_sid'];
            if (!empty($options['content_variables'])) {
                $data['ContentVariables'] = is_array($options['content_variables'])
                    ? json_encode($options['content_variables'])
                    : $options['content_variables'];
            }
        } else {
            $data['Body'] = $message;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $creds['account_sid'] . ':' . $creds['auth_token']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return array('success' => false, 'error' => 'cURL Error: ' . $curl_error);
        }

        log_message('debug', 'Twilio API Response - HTTP Code: ' . $http_code);
        log_message('debug', 'Twilio API Response Body: ' . $response);

        $response_data = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            if (is_array($response_data) && isset($response_data['status']) && in_array($response_data['status'], array('failed', 'undelivered'), true)) {
                $error_msg = !empty($response_data['message']) ? $response_data['message'] : 'Message failed to deliver';
                if (!empty($response_data['error_code'])) {
                    $error_msg .= ' (Error Code: ' . $response_data['error_code'] . ')';
                }
                return array('success' => false, 'error' => $error_msg);
            }
            if (is_array($response_data) && isset($response_data['code']) && (int) $response_data['code'] !== 0) {
                $error_msg = !empty($response_data['message']) ? $response_data['message'] : 'Twilio API Error';
                return array('success' => false, 'error' => $error_msg . ' (Code: ' . $response_data['code'] . ')');
            }
            return array(
                'success' => true,
                'error' => null,
                'message_sid' => (is_array($response_data) && !empty($response_data['sid'])) ? $response_data['sid'] : null,
                'status' => (is_array($response_data) && !empty($response_data['status'])) ? $response_data['status'] : 'queued',
            );
        }

        $error_msg = 'HTTP ' . $http_code;
        if (is_array($response_data)) {
            if (!empty($response_data['message'])) {
                $error_msg .= ': ' . $response_data['message'];
            }
            if (!empty($response_data['code'])) {
                $error_msg .= ' (Code: ' . $response_data['code'] . ')';
            }
            if (!empty($response_data['more_info'])) {
                $error_msg .= ' - More info: ' . $response_data['more_info'];
            }
        }
        return array('success' => false, 'error' => $error_msg);
    }
}

if (!function_exists('twilio_webhook_request_url')) {
    /**
     * Reconstruct the full webhook URL Twilio signed (must match console URL exactly).
     *
     * @return string
     */
    function twilio_webhook_request_url()
    {
        $protocol = 'http';
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')) {
            $protocol = 'https';
        }

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        return $protocol . '://' . $host . $uri;
    }
}

if (!function_exists('validate_twilio_webhook_signature')) {
    /**
     * Validate X-Twilio-Signature for inbound webhooks (HMAC-SHA1).
     *
     * @param string $signature Header value from X-Twilio-Signature
     * @param string $url       Full webhook URL (see twilio_webhook_request_url)
     * @param array  $post_vars POST parameters Twilio sent
     * @param string $auth_token Twilio auth token
     * @return bool
     */
    function validate_twilio_webhook_signature($signature, $url, array $post_vars, $auth_token)
    {
        if ($auth_token === '' || $auth_token === null || $signature === '' || $signature === null) {
            return false;
        }

        ksort($post_vars);
        $data = $url;
        foreach ($post_vars as $key => $value) {
            $data .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $auth_token, true));

        return hash_equals($expected, $signature);
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

