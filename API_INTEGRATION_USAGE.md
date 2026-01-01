# API Integration Usage Guide

This guide shows how to retrieve ID, token, and related data from the `api_integrations` table in your code.

## Helper Functions

The `api_integration_helper.php` provides several helper functions to retrieve credentials:

### 1. Get SendGrid Credentials

```php
// Load the helper (auto-loaded, but you can load manually)
$this->load->helper('api_integration');

// Get default SendGrid integration
$creds = get_sendgrid_credentials();

// Returns:
// [
//     'api_key' => 'SG.xxxxxxxxxxxxx',
//     'from_email' => 'noreply@example.com',
//     'from_name' => 'Company Name',
//     'integration_id' => 1
// ]

// Get specific integration by ID
$creds = get_sendgrid_credentials(5); // Get integration with ID 5
```

### 2. Get WhatsApp (Twilio) Credentials

```php
// Get default WhatsApp integration
$creds = get_whatsapp_credentials();

// Returns:
// [
//     'account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxx',
//     'auth_token' => 'xxxxxxxxxxxxxxxxxxxxx',
//     'from_number' => 'whatsapp:+14155238886',
//     'content_sid' => 'HXxxxxxxxxxxxxxxxxxxxxx',
//     'integration_id' => 2
// ]

// Get specific integration by ID
$creds = get_whatsapp_credentials(3);
```

### 3. Get SMTP Credentials

```php
// Get default SMTP integration
$creds = get_smtp_credentials();

// Returns:
// [
//     'host' => 'smtp.gmail.com',
//     'port' => '587',
//     'user' => 'user@example.com',
//     'password' => 'password',
//     'from_email' => 'user@example.com',
//     'from_name' => 'Company Name',
//     'integration_id' => 3
// ]
```

### 4. Get Any Integration by Service Type

```php
// Get integration object directly
$integration = get_api_integration('sendgrid');
// or
$integration = get_api_integration('whatsapp');
// or
$integration = get_api_integration('smtp');

// Access properties:
// $integration->id
// $integration->service_type
// $integration->service_name
// $integration->account_id
// $integration->auth_token
// $integration->from_email
// $integration->from_name
// $integration->from_number (for WhatsApp)
// $integration->content_sid (for WhatsApp)
// $integration->is_active
// $integration->is_default
// $integration->notes

// Get specific integration by ID
$integration = get_api_integration('sendgrid', 5);
```

### 5. Check if Integration is Configured

```php
// Check if service is configured and active
if (is_api_integration_configured('sendgrid')) {
    // SendGrid is configured
}

if (is_api_integration_configured('whatsapp')) {
    // WhatsApp is configured
}
```

### 6. Get All Integrations

```php
// Get all integrations
$all = get_all_api_integrations();

// Get all SendGrid integrations
$sendgrid_integrations = get_all_api_integrations('sendgrid');

// Get all WhatsApp integrations
$whatsapp_integrations = get_all_api_integrations('whatsapp');
```

## Usage Examples

### Example 1: Send Email Using SendGrid from Database

```php
// In your controller
public function send_email() {
    $this->load->helper('api_integration');
    
    $creds = get_sendgrid_credentials();
    
    if (empty($creds['api_key'])) {
        // Handle error - no credentials
        return;
    }
    
    // Use credentials
    $api_key = $creds['api_key'];
    $from_email = $creds['from_email'];
    $from_name = $creds['from_name'];
    
    // Send email using SendGrid API
    // ... your email sending code ...
}
```

### Example 2: Send WhatsApp Message Using Twilio from Database

```php
// In your controller
public function send_whatsapp() {
    $this->load->helper('api_integration');
    
    $creds = get_whatsapp_credentials();
    
    if (empty($creds['account_sid']) || empty($creds['auth_token'])) {
        // Handle error - no credentials
        return;
    }
    
    // Use credentials
    $account_sid = $creds['account_sid'];
    $auth_token = $creds['auth_token'];
    $from_number = $creds['from_number'];
    $content_sid = $creds['content_sid']; // Optional template
    
    // Send WhatsApp message using Twilio API
    // ... your WhatsApp sending code ...
}
```

### Example 3: Using Model Directly

```php
// In your controller
public function get_credentials() {
    $this->load->model('Api_integration_model', 'api');
    
    // Get default SendGrid
    $sendgrid = $this->api->get_default('sendgrid');
    if ($sendgrid) {
        $api_key = $sendgrid->account_id;
        $from_email = $sendgrid->from_email;
        $from_name = $sendgrid->from_name;
    }
    
    // Get default WhatsApp
    $whatsapp = $this->api->get_default('whatsapp');
    if ($whatsapp) {
        $account_sid = $whatsapp->account_id;
        $auth_token = $whatsapp->auth_token;
        $from_number = $whatsapp->from_number;
        $content_sid = $whatsapp->content_sid;
    }
    
    // Get by ID
    $integration = $this->api->get_by_id(1);
    
    // Get all
    $all = $this->api->get_all('sendgrid');
}
```

## Integration with Existing Controllers

The `Sendgrid` and `Whatsapp` controllers have been updated to automatically use database credentials first, then fall back to config files if not found in database.

### Priority Order:
1. **Database** (`api_integrations` table) - Default active integration
2. **Config File** (`application/config/sendgrid.php` or `whatsapp.php`)
3. **Environment Variables** (`SENDGRID_API_KEY`, `TWILIO_ACCOUNT_SID`, etc.)

## Database Table Structure

```sql
CREATE TABLE `api_integrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_type` varchar(50) NOT NULL,        -- 'sendgrid', 'whatsapp', 'smtp'
  `service_name` varchar(100) NOT NULL,       -- Display name
  `account_id` varchar(255) DEFAULT NULL,     -- API Key, Account SID, etc.
  `auth_token` text DEFAULT NULL,             -- Auth Token, API Secret, Password
  `from_email` varchar(255) DEFAULT NULL,     -- For email services
  `from_name` varchar(255) DEFAULT NULL,      -- For email services
  `from_number` varchar(50) DEFAULT NULL,    -- For WhatsApp/SMS
  `content_sid` varchar(255) DEFAULT NULL,    -- Twilio Content Template SID
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,         -- Default service for this type
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

## Notes

- The helper functions automatically return the **default** integration for each service type
- If no default is set, it returns the first **active** integration
- All helper functions return `null` or empty arrays if no integration is found
- The controllers (`Sendgrid`, `Whatsapp`) automatically use database credentials if available

