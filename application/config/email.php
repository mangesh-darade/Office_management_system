<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
| Configure Gmail SMTP. Set SMTP credentials via environment variables to avoid
| hardcoding secrets in the repository. For local dev, you can set them in Apache/
| Windows environment variables.
*/
$config['protocol']   = 'smtp';
$config['mailtype']   = 'html';
$config['charset']    = 'utf-8';
$config['newline']    = "\r\n";
$config['crlf']       = "\r\n";
$config['wordwrap']   = true;
$config['useragent']  = 'CodeIgniter Mailer';
$config['smtp_timeout'] = 10; // seconds

// Gmail SMTP settings
$config['smtp_host']  = 'smtp.gmail.com';
$config['smtp_port']  = 587; // TLS port
$config['smtp_crypto']= 'tls';

// Use env vars to avoid committing secrets
$env_user = getenv('SMTP_USER');
$env_pass = getenv('SMTP_PASS');

// Default user to requested Gmail if env not set; password must be set via env
$config['smtp_user']  = $env_user ?: 'sateri.mangesh@gmail.com';
$config['smtp_pass']  = $env_pass ?: 'umvj fwhe frmp kutu';

// Optional: define constants in index.php or Apache env if getenv isn't available
if (defined('SMTP_USER') && SMTP_USER) { $config['smtp_user'] = SMTP_USER; }
if (defined('SMTP_PASS') && SMTP_PASS) { $config['smtp_pass'] = SMTP_PASS; }

// Sanitize: remove any whitespace that may be pasted into App Passwords
$config['smtp_user'] = trim((string)$config['smtp_user']);
$config['smtp_pass'] = preg_replace('/\s+/', '', (string)$config['smtp_pass']);
