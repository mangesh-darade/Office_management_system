# SendGrid Email Functionality - Complete Documentation

## Overview

SendGrid is an API-based email delivery service integrated into the Office Management System. Unlike the SMTP-based Mail controller, SendGrid uses REST API calls to send emails, providing better deliverability, scalability, and analytics.

## Key Differences: SendGrid vs SMTP (Mail Controller)

| Feature | SendGrid (API) | SMTP (Mail Controller) |
|---------|---------------|------------------------|
| **Method** | REST API calls | SMTP protocol |
| **Configuration** | API Key | SMTP credentials (host, port, user, pass) |
| **Deliverability** | High (managed infrastructure) | Depends on SMTP server |
| **Scalability** | Excellent (handles millions) | Limited by SMTP server |
| **Analytics** | Built-in (opens, clicks, bounces) | Not available |
| **Setup Complexity** | Simple (just API key) | More complex (SMTP settings) |
| **Free Tier** | 100 emails/day | Depends on provider |

## Architecture

### Files Structure

```
application/
├── controllers/
│   └── Sendgrid.php          # Main controller for SendGrid operations
├── config/
│   └── sendgrid.php          # SendGrid configuration (API key, from email)
└── views/
    └── sendgrid/
        └── index.php         # UI for sending emails via SendGrid
```

### Routes

```php
// application/config/routes.php
$route['sendgrid'] = 'sendgrid/index';        // Display SendGrid interface
$route['sendgrid/send'] = 'sendgrid/send';    // Send email via API
$route['sendgrid/test'] = 'sendgrid/test';    // Send test email
```

## Configuration

### 1. Get SendGrid API Key

1. Sign up at [sendgrid.com](https://sendgrid.com) (free tier: 100 emails/day)
2. Log in to SendGrid dashboard
3. Navigate to **Settings → API Keys**
4. Click **Create API Key**
5. Choose permissions:
   - **Full Access** (recommended for testing)
   - **Mail Send** (restricted, production use)
6. Copy the API key (shown only once!)

### 2. Configure API Key

**Option A: Environment Variable (Recommended)**
```bash
# Windows (PowerShell)
$env:SENDGRID_API_KEY = "SG.your_api_key_here"

# Linux/Mac
export SENDGRID_API_KEY="SG.your_api_key_here"
```

**Option B: Config File**
Edit `application/config/sendgrid.php`:
```php
$config['sendgrid_api_key'] = 'SG.your_api_key_here';
```

### 3. Verify Sender Email

1. In SendGrid dashboard, go to **Settings → Sender Authentication**
2. Click **Verify a Single Sender**
3. Fill in the form with your email details
4. Check your email and click the verification link
5. Update `sendgrid_from_email` in config to match verified email

## Usage

### Accessing SendGrid Interface

Navigate to: `http://localhost/Office_management_system/sendgrid`

### Sending Emails

#### Via Web Interface

1. Go to `/sendgrid`
2. Fill in the form:
   - **To**: Recipient email (required)
   - **CC**: Comma-separated CC emails (optional)
   - **BCC**: Comma-separated BCC emails (optional)
   - **Subject**: Email subject (required)
   - **Message**: Email body (required)
   - **HTML Email**: Check to send HTML, uncheck for plain text
   - **Attachment**: Optional file attachment (max 10MB)
3. Click **Send Email**

#### Via Code

```php
// Load SendGrid controller
$this->load->library('sendgrid');

// Or use the controller directly
$email_data = [
    'personalizations' => [
        [
            'to' => [['email' => 'recipient@example.com']],
            'subject' => 'Test Email'
        ]
    ],
    'from' => [
        'email' => 'noreply@example.com',
        'name' => 'Office Management System'
    ],
    'content' => [
        [
            'type' => 'text/html',
            'value' => '<h1>Hello!</h1><p>This is a test email.</p>'
        ]
    ]
];

// Send via API
$result = $this->_send_via_api($email_data);
```

### Test Email

Click **Send Test Email** button or visit `/sendgrid/test` to send a test email to your logged-in user's email address.

## API Implementation Details

### SendGrid API Endpoint

```
POST https://api.sendgrid.com/v3/mail/send
```

### Request Format

```json
{
  "personalizations": [
    {
      "to": [{"email": "recipient@example.com"}],
      "cc": [{"email": "cc@example.com"}],
      "bcc": [{"email": "bcc@example.com"}],
      "subject": "Email Subject"
    }
  ],
  "from": {
    "email": "sender@example.com",
    "name": "Sender Name"
  },
  "content": [
    {
      "type": "text/html",
      "value": "<h1>Email Content</h1>"
    }
  ],
  "attachments": [
    {
      "content": "base64_encoded_file_content",
      "filename": "document.pdf",
      "type": "application/pdf",
      "disposition": "attachment"
    }
  ]
}
```

### Authentication

SendGrid uses Bearer token authentication:
```
Authorization: Bearer SG.your_api_key_here
```

### Response Codes

- **200-299**: Success
- **400**: Bad Request (invalid data)
- **401**: Unauthorized (invalid API key)
- **403**: Forbidden (insufficient permissions)
- **413**: Payload Too Large (attachment too big)
- **500+**: Server Error

## Controller Methods

### `index()`
- **Route**: `GET /sendgrid`
- **Purpose**: Display SendGrid email sending interface
- **Returns**: View with form and configuration status

### `send()`
- **Route**: `POST /sendgrid/send`
- **Purpose**: Send email via SendGrid API
- **Parameters**:
  - `to` (required): Recipient email
  - `cc` (optional): Comma-separated CC emails
  - `bcc` (optional): Comma-separated BCC emails
  - `subject` (required): Email subject
  - `message` (required): Email body
  - `is_html` (optional): 1 for HTML, 0 for plain text
  - `attachment` (optional): File upload
- **Returns**: Redirects with success/error message

### `test()`
- **Route**: `GET /sendgrid/test`
- **Purpose**: Send test email to logged-in user
- **Returns**: Redirects with success/error message

### `_send_via_api($email_data)`
- **Type**: Private method
- **Purpose**: Make HTTP request to SendGrid API
- **Parameters**: Email data array in SendGrid format
- **Returns**: Array with `success` (bool) and `message` (string)

## Features

### ✅ Supported Features

1. **Single Recipient**: Send to one email address
2. **Multiple Recipients**: CC and BCC support
3. **HTML Emails**: Rich text formatting
4. **Plain Text**: Simple text emails
5. **Attachments**: File attachments (max 10MB)
6. **Error Handling**: Detailed error messages
7. **Configuration Status**: Visual indicator of setup status

### ❌ Not Yet Implemented

1. **Multiple To Recipients**: Currently supports one "To" address
2. **Email Templates**: No template system
3. **Scheduled Sending**: No scheduling feature
4. **Analytics Integration**: No tracking of opens/clicks
5. **Bulk Sending**: No batch email functionality

## Error Handling

### Common Errors

1. **API Key Not Configured**
   - **Error**: "SendGrid API key is not configured"
   - **Solution**: Set `SENDGRID_API_KEY` environment variable

2. **Invalid API Key**
   - **Error**: "HTTP 401: Unauthorized"
   - **Solution**: Verify API key is correct and has proper permissions

3. **Unverified Sender**
   - **Error**: "HTTP 403: Sender email not verified"
   - **Solution**: Verify sender email in SendGrid dashboard

4. **Invalid Email Address**
   - **Error**: "Invalid To email address"
   - **Solution**: Check email format is correct

5. **Attachment Too Large**
   - **Error**: "HTTP 413: Payload Too Large"
   - **Solution**: Reduce file size (max 10MB)

## Security Considerations

1. **API Key Protection**
   - Never commit API keys to version control
   - Use environment variables for production
   - Rotate API keys regularly

2. **Email Validation**
   - All email addresses are validated before sending
   - Prevents injection attacks

3. **File Upload Security**
   - File type validation
   - Size limits enforced
   - Temporary file cleanup

## Integration with Other Modules

### Current Integration Points

1. **Reminders Module**: Can be extended to use SendGrid instead of SMTP
2. **Notifications**: Can send system notifications via SendGrid
3. **Requirements/Tasks**: Status change notifications

### Future Integration Opportunities

1. **Email Settings Module**: Add SendGrid as email provider option
2. **Reminders**: Allow choosing between SMTP and SendGrid
3. **Bulk Emails**: Use SendGrid for mass email campaigns

## Testing

### Manual Testing

1. **Test Email**
   - Click "Send Test Email" button
   - Verify email is received
   - Check email formatting

2. **Send Email**
   - Fill form with valid data
   - Send to your own email
   - Verify delivery

3. **Error Cases**
   - Try invalid email address
   - Try without API key configured
   - Try with unverified sender

### API Testing

Use cURL to test SendGrid API directly:

```bash
curl -X POST https://api.sendgrid.com/v3/mail/send \
  -H "Authorization: Bearer SG.your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "personalizations": [{
      "to": [{"email": "test@example.com"}],
      "subject": "Test Email"
    }],
    "from": {"email": "sender@example.com"},
    "content": [{
      "type": "text/plain",
      "value": "Hello, World!"
    }]
  }'
```

## Troubleshooting

### Email Not Sending

1. Check API key is configured correctly
2. Verify sender email is verified in SendGrid
3. Check SendGrid dashboard for error logs
4. Verify recipient email is valid
5. Check server can make HTTPS requests (port 443)

### API Errors

1. **401 Unauthorized**: API key is invalid or expired
2. **403 Forbidden**: API key lacks required permissions
3. **400 Bad Request**: Check email data format
4. **500 Server Error**: SendGrid service issue (check status page)

### Debug Mode

Enable debug logging in CodeIgniter:
```php
// application/config/config.php
$config['log_threshold'] = 4; // Debug level
```

Check logs in `application/logs/` for detailed error messages.

## Best Practices

1. **Use Environment Variables**: Never hardcode API keys
2. **Verify Sender**: Always verify sender email in SendGrid
3. **Handle Errors**: Implement proper error handling
4. **Rate Limiting**: Respect SendGrid rate limits
5. **Monitor Usage**: Track email sending in SendGrid dashboard
6. **Test First**: Always test before production use

## SendGrid Dashboard Features

After sending emails, you can view:

1. **Activity Feed**: See all sent emails
2. **Statistics**: Opens, clicks, bounces, spam reports
3. **Suppressions**: Unsubscribes and bounces
4. **API Usage**: API call statistics
5. **Webhooks**: Configure event notifications

## Comparison with Mail Controller

### When to Use SendGrid

- High volume email sending
- Need for analytics
- Better deliverability required
- API-based integration preferred

### When to Use SMTP (Mail Controller)

- Simple email needs
- Existing SMTP server available
- No API key management desired
- Lower cost (if using free SMTP)

## Future Enhancements

1. **Email Templates**: Create reusable email templates
2. **Scheduled Sending**: Schedule emails for future delivery
3. **Analytics Integration**: Display opens/clicks in dashboard
4. **Bulk Sending**: Send to multiple recipients efficiently
5. **Webhooks**: Handle bounce/spam reports
6. **A/B Testing**: Test different email variations
7. **Unsubscribe Management**: Handle unsubscribe requests

## Resources

- [SendGrid Documentation](https://docs.sendgrid.com/)
- [SendGrid API Reference](https://docs.sendgrid.com/api-reference)
- [SendGrid PHP SDK](https://github.com/sendgrid/sendgrid-php) (alternative to cURL)
- [SendGrid Status Page](https://status.sendgrid.com/)

## Support

For issues or questions:
1. Check SendGrid dashboard for error details
2. Review application logs
3. Test API directly with cURL
4. Check SendGrid documentation
5. Contact SendGrid support if needed

---

**Last Updated**: 2024
**Version**: 1.0
**Author**: Office Management System

