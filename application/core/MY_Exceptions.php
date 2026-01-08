<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions {
    
    /**
     * Override show_error to use custom 403 page
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500) {
        // If it's a 403 error, use our custom view
        if ($status_code == 403) {
            // Always use the fallback HTML to ensure it displays
            // This works even if CI is not fully loaded
            $this->show_custom_403();
            exit;
        }
        
        // For other errors, use default behavior
        return parent::show_error($heading, $message, $template, $status_code);
    }
    
    /**
     * Custom 403 page - Always displays, no dependencies
     */
    private function show_custom_403() {
        // Set HTTP status code
        if (!headers_sent()) {
            http_response_code(403);
        }
        
        // Get base URL for proper links
        $base_url = '';
        if (defined('BASE_URL')) {
            $base_url = BASE_URL;
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $base_url = $protocol . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        }
        $dashboard_url = rtrim($base_url, '/') . '/dashboard';
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            text-align: center;
            max-width: 500px;
            width: 100%;
            padding: 3rem 2rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .emoji {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
            line-height: 1;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        p {
            font-size: 1.1rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <span class="emoji">🚫</span>
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <span>←</span>
                Go Back
            </a>
            <a href="' . htmlspecialchars($dashboard_url) . '" class="btn btn-primary">
                <span>🏠</span>
                Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>';
        
        // Clear any previous output
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        echo $html;
        exit;
    }
}
