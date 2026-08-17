<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Detect oversized multipart POST before CSRF fails with a generic 403.
 */
class MY_Security extends CI_Security {

    public function csrf_verify()
    {
        $method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
        if ($method === 'POST') {
            $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
            $content_type = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
            $is_form_post = (strpos($content_type, 'application/x-www-form-urlencoded') !== false)
                || (strpos($content_type, 'multipart/form-data') !== false);

            // PHP leaves $_POST empty for JSON bodies AND when post_max_size is exceeded.
            // Only the latter is an oversized form upload.
            if ($is_form_post && $content_length > 0 && empty($_POST) && empty($_FILES)) {
                $this->_show_post_body_too_large();
            }

            // CI3 only reads CSRF from $_POST. JSON/AJAX sends it in X-CSRF-Token.
            if (empty($_POST[$this->_csrf_token_name]) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $_POST[$this->_csrf_token_name] = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
        }

        return parent::csrf_verify();
    }

    private function _show_post_body_too_large()
    {
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $message = 'The attachment is too large for the server to accept. '
            . 'Current PHP limits: upload_max_filesize=' . $upload_max
            . ', post_max_size=' . $post_max
            . '. Increase these in php.ini (or .user.ini) or use a smaller file.';

        if (!headers_sent()) {
            http_response_code(413);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Upload too large</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#f5f7fa;margin:0;padding:2rem;display:flex;align-items:center;justify-content:center;min-height:100vh}'
            . '.box{max-width:520px;background:#fff;border-radius:12px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,.08)}'
            . 'h1{margin:0 0 1rem;font-size:1.35rem}p{color:#4b5563;line-height:1.6}a{color:#2563eb}</style></head><body>'
            . '<div class="box"><h1>Upload too large</h1><p>' . htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="javascript:history.back()">&larr; Go back</a></p></div></body></html>';
        exit;
    }
}
