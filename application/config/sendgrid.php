<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SendGrid Configuration
 * 
 * Configure SendGrid API settings for email sending
 * 
 * To use SendGrid:
 * 1. Sign up at https://sendgrid.com
 * 2. Create an API key in SendGrid dashboard
 * 3. Set the API key below or use environment variable SENDGRID_API_KEY
 * 4. Verify your sender email in SendGrid dashboard
 */

// SendGrid API Key
// Get from: https://app.sendgrid.com/settings/api_keys
// Or set via environment variable: SENDGRID_API_KEY
// If environment variable is not set, use the key below
$config['sendgrid_api_key'] = getenv('SENDGRID_API_KEY') ?: '';

// From Email Address
// IMPORTANT: This email MUST be verified in SendGrid dashboard before sending emails
// Steps to verify:
// 1. Log in to SendGrid: https://app.sendgrid.com
// 2. Go to Settings → Sender Authentication
// 3. Click "Verify a Single Sender"
// 4. Fill in your email details and verify via email
// 5. Update this config with your verified email address
// Or set via environment variable: SENDGRID_FROM_EMAIL
// 
// WARNING: Using an unverified email will result in HTTP 403 errors
$config['sendgrid_from_email'] = getenv('SENDGRID_FROM_EMAIL') ?: 'sateri.mangesh@gmail.com';

// From Name
// Display name for the sender
// Or set via environment variable: SENDGRID_FROM_NAME
$config['sendgrid_from_name'] = getenv('SENDGRID_FROM_NAME') ?: 'Office Management System';

