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

