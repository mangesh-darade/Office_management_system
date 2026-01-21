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

// --------------------------------------------------------------------------
// Default SMTP settings (no hardcoded credentials)
// --------------------------------------------------------------------------
// Real SMTP details are stored in Settings > Email as:
//   email_smtp_host, email_smtp_port, email_smtp_user,
//   email_smtp_pass, email_smtp_crypto
// Controllers/helpers should load those settings and override this base
// config at runtime. Here we only keep generic defaults and optional
// environment overrides (for CLI/testing).

// Basic defaults
$config['smtp_host']   = 'smtp.gmail.com';
$config['smtp_port']   = 587; // TLS port
$config['smtp_crypto'] = 'tls';

// Optional env/constant overrides
$env_user = getenv('SMTP_USER') ?: '';
$env_pass = getenv('SMTP_PASS') ?: '';

$config['smtp_user'] = $env_user;
$config['smtp_pass'] = $env_pass;

if (defined('SMTP_USER') && SMTP_USER) { $config['smtp_user'] = SMTP_USER; }
if (defined('SMTP_PASS') && SMTP_PASS) { $config['smtp_pass'] = SMTP_PASS; }

// Sanitize
$config['smtp_user'] = trim((string)$config['smtp_user']);
$config['smtp_pass'] = preg_replace('/\s+/', '', (string)$config['smtp_pass']);
