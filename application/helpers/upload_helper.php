<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Upload Helper
 * 
 * Provides secure file upload validation and handling functions
 */

/**
 * Validate uploaded file
 * 
 * @param array $file $_FILES array element
 * @param array $options Validation options (allowed_types, max_size, min_size, required)
 * @return array ['valid' => bool, 'errors' => array, 'file_info' => array]
 */
if (!function_exists('validate_uploaded_file')) {
    function validate_uploaded_file($file, $options = []) {
        $defaults = [
            'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'],
            'max_size' => 10485760, // 10MB in bytes
            'min_size' => 0,
            'required' => false,
            'check_mime' => true,
            'check_content' => false
        ];
        
        $options = array_merge($defaults, $options);
        $errors = [];
        $file_info = [];
        
        // Check if file was uploaded
        if (!isset($file) || !is_array($file)) {
            if ($options['required']) {
                $errors[] = 'No file was uploaded.';
            }
            return ['valid' => empty($errors), 'errors' => $errors, 'file_info' => $file_info];
        }
        
        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
            ];
            $errors[] = isset($error_messages[$file['error']]) 
                ? $error_messages[$file['error']] 
                : 'Unknown upload error.';
            return ['valid' => false, 'errors' => $errors, 'file_info' => $file_info];
        }
        
        // Check if file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload.';
            return ['valid' => false, 'errors' => $errors, 'file_info' => $file_info];
        }
        
        // Get file info
        $file_info['name'] = isset($file['name']) ? $file['name'] : '';
        $file_info['size'] = isset($file['size']) ? (int)$file['size'] : 0;
        $file_info['type'] = isset($file['type']) ? $file['type'] : '';
        $file_info['tmp_name'] = isset($file['tmp_name']) ? $file['tmp_name'] : '';
        
        // Validate file size
        if ($file_info['size'] < $options['min_size']) {
            $errors[] = 'File is too small. Minimum size: ' . format_bytes($options['min_size']);
        }
        if ($file_info['size'] > $options['max_size']) {
            $errors[] = 'File is too large. Maximum size: ' . format_bytes($options['max_size']);
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $file_info['extension'] = $extension;
        
        if (!in_array($extension, array_map('strtolower', $options['allowed_types']))) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $options['allowed_types']);
        }
        
        // Validate MIME type
        if ($options['check_mime'] && function_exists('mime_content_type')) {
            $mime_type = mime_content_type($file_info['tmp_name']);
            $file_info['mime_type'] = $mime_type;
            
            // Map extensions to MIME types
            $mime_map = [
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'xls' => ['application/vnd.ms-excel'],
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'gif' => ['image/gif'],
                'zip' => ['application/zip', 'application/x-zip-compressed']
            ];
            
            if (isset($mime_map[$extension])) {
                if (!in_array($mime_type, $mime_map[$extension])) {
                    $errors[] = 'File MIME type does not match file extension.';
                }
            }
        }
        
        // Check for dangerous file content (basic check)
        if ($options['check_content']) {
            $content = file_get_contents($file_info['tmp_name'], false, null, 0, 1024);
            // Check for PHP tags
            if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
                $errors[] = 'File contains potentially dangerous content.';
            }
        }
        
        // Validate file name (prevent directory traversal)
        if (strpos($file_info['name'], '..') !== false || strpos($file_info['name'], '/') !== false) {
            $errors[] = 'Invalid file name.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => $file_info
        ];
    }
}

/**
 * Format bytes to human readable format
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Sanitize file name
 * 
 * @param string $filename
 * @return string
 */
if (!function_exists('sanitize_filename')) {
    function sanitize_filename($filename) {
        // Remove path components
        $filename = basename($filename);
        // Remove any character that isn't alphanumeric, dash, underscore, or dot
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
        }
        return $filename;
    }
}

