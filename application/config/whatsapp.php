<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Configuration
 * 
 * Configure WhatsApp API settings for sending messages
 * 
 * Options:
 * 1. Twilio WhatsApp API (Recommended) - https://www.twilio.com/whatsapp
 * 2. WhatsApp Business API (Official) - Requires Facebook Business verification
 * 3. WhatsApp Web API (Unofficial) - Using libraries like whatsapp-web.js
 * 
 * This configuration supports Twilio WhatsApp API by default.
 */

// Twilio WhatsApp Configuration
// Get credentials from: https://www.twilio.com/console
// If environment variables are not set, use the values below
// 
// To get your credentials:
// 1. Sign up at https://www.twilio.com (free trial available)
// 2. Go to Twilio Console: https://www.twilio.com/console
// 3. Find your Account SID and Auth Token on the dashboard
// 4. For WhatsApp, join the Twilio Sandbox: https://www.twilio.com/console/sms/whatsapp/sandbox
// 5. Or get a verified WhatsApp number from Twilio
$config['twilio_account_sid'] = getenv('TWILIO_ACCOUNT_SID') ?: '';
$config['twilio_auth_token'] = getenv('TWILIO_AUTH_TOKEN') ?: '';
$config['twilio_whatsapp_from'] = getenv('TWILIO_WHATSAPP_FROM') ?: 'whatsapp:+14155238886'; // Twilio Sandbox number (or your verified WhatsApp number)

// WhatsApp Business API Configuration (Alternative)
$config['whatsapp_business_id'] = getenv('WHATSAPP_BUSINESS_ID') ?: '';
$config['whatsapp_access_token'] = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
$config['whatsapp_phone_number_id'] = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';

// Default Settings
$config['whatsapp_provider'] = 'twilio'; // Options: 'twilio', 'whatsapp_business', 'web'
$config['whatsapp_enabled'] = true; // Enable/disable WhatsApp functionality

// Message Templates
$config['task_assignment_template'] = "📋 *New Task Assigned*\n\n*Task:* {task_title}\n*Project:* {project_name}\n*Priority:* {priority}\n*Due Date:* {due_date}\n*Description:* {description}\n\nView details: {task_url}";
$config['task_update_template'] = "🔄 *Task Updated*\n\n*Task:* {task_title}\n*Status:* {status}\n*Updated By:* {updated_by}\n\nView details: {task_url}";
$config['report_template'] = "📊 *{report_type} Report*\n\n*Employee:* {employee_name}\n*Period:* {period}\n*Summary:*\n{summary}\n\nView full report: {report_url}";

// Twilio Content Templates (for WhatsApp template messages)
// If you have approved WhatsApp templates in Twilio, you can use ContentSid here
// Example: $config['twilio_content_sid'] = 'HXb5b62575e6e4ff6129ad7c8efe1f983e';
$config['twilio_content_sid'] = getenv('TWILIO_CONTENT_SID') ?: ''; // Optional: Your Twilio Content Template SID
$config['use_content_templates'] = false; // Set to true to use ContentSid templates instead of plain text

