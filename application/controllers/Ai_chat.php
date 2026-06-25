<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_chat extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load necessary models and libraries
        $this->load->model('Ai_model');
        $this->load->model('Chat_model'); // Reusing chat model for history if needed
        $this->load->library('session');
        $this->load->helper(['url', 'permission']);
        
        // RBAC Audit: Centralized module access check
        require_module_access(['ai', 'ai_chat'], true);
    }

    public function index() {
        $data['page_title'] = 'AI Assistant';

        // Load existing AI conversation history from session so it persists after refresh
        $history = $this->session->userdata('ai_conversation_history');
        if (!is_array($history)) {
            $history = [];
        }
        $data['conversation_history'] = $history;

        // In a real app, you'd load a layout view
        $this->load->view('ai_chat/index', $data);
    }

    public function send_message() {
        // CSRF protection should be handled by CI, assuming it's on or we add checks
        $message = $this->input->post('message');
        $format = strtolower(trim($this->input->post('format') ?: '')); // excel, pdf, csv, or empty
        
        if (empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Empty message']);
            return;
        }

        try {
            // Load the AI Handler (we will create this library)
            $this->load->library('Ai_handler');
            
            // Get logged-in user information
            $user_id = (int)$this->session->userdata('user_id');
            $user_email = (string)$this->session->userdata('email');
            $user_role_id = (int)$this->session->userdata('role_id');
            
            // Get user details from database
            $user_info = null;
            if ($user_id > 0) {
                $this->load->model('User_model');
                $user = $this->User_model->get($user_id);
                if ($user) {
                    $user_info = [
                        'id' => $user_id,
                        'name' => isset($user->name) ? $user->name : '',
                        'email' => $user_email ?: (isset($user->email) ? $user->email : ''),
                        'role_id' => $user_role_id ?: (isset($user->role_id) ? $user->role_id : null)
                    ];
                }
            }
            
            // Get conversation history from session (last 5 messages for context)
            $conversation_history = $this->session->userdata('ai_conversation_history');
            if (!is_array($conversation_history)) {
                $conversation_history = [];
            }
            // Keep only last 5 exchanges to avoid token limits
            if (count($conversation_history) > 10) {
                $conversation_history = array_slice($conversation_history, -10);
            }
            
            // 1. Vector Search / RAG: Understand the intent and fetch context
            // The AI Handler will use a "SimpleVectorStore" to find relevant DB schema/tables
            $context = $this->ai_handler->get_relevant_context($message);
            
            // 2. LLM Processing: Generate SQL or Answer (pass user info and conversation history)
            $response = $this->ai_handler->process_query($message, $context, $user_info, $conversation_history);
            
            // Save current message to conversation history
            $conversation_history[] = [
                'user' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // 3. If SQL was generated, execute it (Safely)
            if (isset($response['type']) && $response['type'] === 'sql_query') {
                $query_result = $this->execute_safe_query($response['query']);
                
                // Check if user wants a file/report (detect format from message or use provided format)
                $wants_file = (stripos($message, 'file') !== false || 
                              stripos($message, 'report') !== false || 
                              stripos($message, 'download') !== false ||
                              stripos($message, 'export') !== false ||
                              stripos($message, 'excel') !== false ||
                              stripos($message, 'pdf') !== false ||
                              stripos($message, 'csv') !== false ||
                              !empty($format));
                
                // Detect format from message or use provided format
                $detected_format = 'csv'; // default
                if (!empty($format) && in_array($format, ['excel', 'pdf', 'csv'])) {
                    $detected_format = $format;
                } else if (stripos($message, 'excel') !== false || stripos($message, 'xlsx') !== false) {
                    $detected_format = 'excel';
                } else if (stripos($message, 'pdf') !== false) {
                    $detected_format = 'pdf';
                } else if (stripos($message, 'csv') !== false) {
                    $detected_format = 'csv';
                }
                
                if ($wants_file && is_array($query_result) && !isset($query_result['error'])) {
                    // Generate file in requested format
                    $file_info = $this->generate_export_file($query_result, $detected_format, $message);
                    
                    if ($file_info && !isset($file_info['error'])) {
                        $download_url = base_url($file_info['path']);
                        $format_name = strtoupper($detected_format);
                        $final_answer = "I have generated the {$format_name} report for you. <br>";
                        $final_answer .= "<a href='{$download_url}' target='_blank' class='btn btn-primary btn-sm me-2'><i class='bi bi-download'></i> Download {$format_name}</a>";
                        
                        // Use export endpoint for format conversion (POST method for security)
                        $export_data = base64_encode(json_encode($query_result));
                        $export_query = esc_view($message);
                        
                        if ($detected_format === 'csv') {
                            $final_answer .= "<button type='button' class='btn btn-success btn-sm me-2 export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='excel'><i class='bi bi-file-earmark-excel'></i> Excel</button>";
                            $final_answer .= "<button type='button' class='btn btn-danger btn-sm export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='pdf'><i class='bi bi-file-earmark-pdf'></i> PDF</button>";
                        } elseif ($detected_format === 'excel') {
                            $final_answer .= "<button type='button' class='btn btn-primary btn-sm me-2 export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='csv'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</button>";
                            $final_answer .= "<button type='button' class='btn btn-danger btn-sm export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='pdf'><i class='bi bi-file-earmark-pdf'></i> PDF</button>";
                        } elseif ($detected_format === 'pdf') {
                            $final_answer .= "<button type='button' class='btn btn-primary btn-sm me-2 export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='csv'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</button>";
                            $final_answer .= "<button type='button' class='btn btn-success btn-sm export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='excel'><i class='bi bi-file-earmark-excel'></i> Excel</button>";
                        }
                    } else {
                        $final_answer = isset($file_info['error']) ? $file_info['error'] : 'Error generating export file.';
                    }
                } else {
                    // 4. Summarize the data for the user (pass user info for personalized responses)
                    $user_name = isset($user_info['name']) ? $user_info['name'] : null;
                    $final_answer = $this->ai_handler->summarize_data($message, $query_result, $user_name);
                    
                    // Add export options if data is available (POST method for security)
                    if (is_array($query_result) && !isset($query_result['error']) && !empty($query_result)) {
                        $export_data = base64_encode(json_encode($query_result));
                        $export_query = esc_view($message);
                        
                        $final_answer .= "<br><br><small>Export options: ";
                        $final_answer .= "<button type='button' class='btn btn-sm btn-outline-primary me-1 export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='csv'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</button> ";
                        $final_answer .= "<button type='button' class='btn btn-sm btn-outline-success me-1 export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='excel'><i class='bi bi-file-earmark-excel'></i> Excel</button> ";
                        $final_answer .= "<button type='button' class='btn btn-sm btn-outline-danger export-btn' data-export-data='" . esc_view($export_data) . "' data-export-query='" . esc_view($export_query) . "' data-export-format='pdf'><i class='bi bi-file-earmark-pdf'></i> PDF</button>";
                        $final_answer .= "</small>";
                    }
                }
                
                // Save AI response to conversation history
                $conversation_history[] = [
                    'assistant' => $final_answer,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $this->session->set_userdata('ai_conversation_history', $conversation_history);
                
                $payload = [
                    'status' => 'success',
                    'response' => $final_answer,
                    'csrf_token' => $this->security->get_csrf_hash(),
                ];
                if (ENVIRONMENT === 'development' && (int) $this->session->userdata('role_id') === ROLE_ADMIN) {
                    $payload['debug_sql'] = isset($response['query']) ? $response['query'] : '';
                }
                echo json_encode($payload);
            } else {
                // Just a conversational response
                $text_content = isset($response['text']) ? $response['text'] : json_encode($response);
                
                // Save to conversation history
                $conversation_history[] = [
                    'assistant' => $text_content,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $this->session->set_userdata('ai_conversation_history', $conversation_history);
                
                echo json_encode([
                    'status' => 'success',
                    'response' => $text_content,
                    'csrf_token' => $this->security->get_csrf_hash()
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Text-to-Speech endpoint using Azure Speech
     * POST: text, language (optional), voice (optional)
     */
    public function tts() {
        $text = $this->input->post('text');
        $language = $this->input->post('language') ?: 'en-US';
        $voice = $this->input->post('voice') ?: '';

        if (empty($text)) {
            echo json_encode(['status' => 'error', 'message' => 'No text provided']);
            return;
        }

        try {
            $this->load->library('Ai_handler');
            $result = $this->ai_handler->text_to_speech($text, $language, $voice);

            if (isset($result['error'])) {
                echo json_encode(['status' => 'error', 'message' => $result['error']]);
                return;
            }

            $audio_url = base_url($result['path']);
            echo json_encode([
                'status' => 'success',
                'audio_url' => $audio_url,
                'format' => $result['format']
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function execute_safe_query($sql) {
        // SECURITY CRITICAL: Ensure this is a SELECT query only
        $sql = trim($sql);
        
        // 1. Strip comments to prevent hidden commands handling
        // Strip multi-line comments /* ... */
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        // Strip single line comments -- or # (be careful not to strip valid content)
        // This is a basic strip, sophisticated SQL parsers do better but this is PHP 5.6 compliant
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/#.*$/m', '', $sql);
        $sql = trim($sql);

        // 2. Strict SELECT check
        if (!preg_match('/^\s*SELECT\b/i', $sql)) {
            return ['error' => 'Only SELECT queries are allowed for safety.'];
        }
        
        // 3. Check for destructive SQL keywords as whole words
        $destructive_patterns = [
            '/\bDROP\b/i',      // DROP TABLE, DATABASE
            '/\bDELETE\b/i',    // DELETE FROM
            '/\bUPDATE\b/i',    // UPDATE table
            '/\bINSERT\b/i',    // INSERT INTO
            '/\bTRUNCATE\b/i',  // TRUNCATE TABLE
            '/\bALTER\b/i',     // ALTER TABLE
            '/\bCREATE\b/i',    // CREATE TABLE
            '/\bREPLACE\b/i',   // REPLACE INTO
            '/\bEXEC\b/i',      // EXEC
            '/\bEXECUTE\b/i',   // EXECUTE
            '/\bCALL\b/i',      // CALL
            '/\bGRANT\b/i',     // GRANT permissions
            '/\bREVOKE\b/i',    // REVOKE permissions
            '/\bLOCK\b/i',      // LOCK TABLES
            '/\bUNLOCK\b/i',    // UNLOCK TABLES
            '/\bRENAME\b/i',    // RENAME TABLE
            '/\bUNION\b/i',     // Block UNION to prevent accessing unpermitted tables via injection
            '/\bINTO\b/i',      // SELECT ... INTO (file write)
            '/\bOUTFILE\b/i',   // SELECT ... INTO OUTFILE
            '/\bDUMPFILE\b/i'   // SELECT ... INTO DUMPFILE
        ];
        
        foreach ($destructive_patterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return ['error' => 'Destructive or unsafe queries are blocked.'];
            }
        }

        // Block multi-statement and system-catalog probing
        if (strpos($sql, ';') !== false) {
            return ['error' => 'Multiple SQL statements are not allowed.'];
        }

        if (preg_match('/\b(SLEEP|BENCHMARK|LOAD_FILE|GET_LOCK|RELEASE_LOCK)\s*\(/i', $sql)) {
            return ['error' => 'Unsafe SQL functions are blocked.'];
        }

        if (preg_match('/\binformation_schema\b|\bmysql\.|\bperformance_schema\b|\bsys\./i', $sql)) {
            return ['error' => 'Access to system tables is blocked.'];
        }
        
        // 4. Check table access permissions (Existing Logic)
        $this->load->helper(['permission', 'ai_sql_guard']);
        $role_id = (int)$this->session->userdata('role_id');
        
        // Map tables to modules for permission checking
        $table_to_module_map = [
            'users' => 'users',
            'employees' => 'employees',
            'attendance' => 'attendance',
            'leave_requests' => 'leave_requests',
            'leave_types' => 'leave_requests',
            'leave_approvals' => 'leave_requests',
            'leave_balances' => 'leave_balances',
            'projects' => 'projects',
            'tasks' => 'tasks',
            'requirements' => 'requirements',
            'timesheets' => 'timesheets',
            'departments' => 'departments',
            'designations' => 'designations',
            'clients' => 'clients',
            'roles' => 'permissions',
            'settings' => 'settings',
            'activity_log' => 'activity',
            'notifications' => 'notifications',
            'holidays' => 'leave_requests',
            'api_integrations' => 'settings',
            'expenses' => 'expenses',
            'payroll' => 'payroll',
            'chats' => 'chats',
            'announcements' => 'announcements',
            'reminders' => 'reminders',
            'assets' => 'assets'
        ];
        
        // Extract table names
        preg_match_all('/\bFROM\s+`?(\w+)`?/i', $sql, $from_matches);
        preg_match_all('/\bJOIN\s+`?(\w+)`?/i', $sql, $join_matches);
        
        $tables_in_query = array_merge(
            isset($from_matches[1]) ? $from_matches[1] : [],
            isset($join_matches[1]) ? $join_matches[1] : []
        );
        $tables_in_query = array_unique(array_map('strtolower', $tables_in_query));

        $blocked_catalog = array('information_schema', 'mysql', 'performance_schema', 'sys');
        foreach ($tables_in_query as $table) {
            if (in_array($table, $blocked_catalog, true)) {
                return ['error' => 'Access to system tables is blocked.'];
            }
        }

        // Hard deny-list: secret-bearing tables and sensitive columns
        // are blocked for every role, including admin.
        $guard_error = ai_sql_guard_check($sql, $tables_in_query);
        if ($guard_error !== null) {
            ai_sql_audit_log($sql, 'blocked', $guard_error);
            return ['error' => $guard_error];
        }
        
        foreach ($tables_in_query as $table) {
            $module = isset($table_to_module_map[$table]) ? $table_to_module_map[$table] : null;
            
            if ($module === null) {
                // System tables - Allow Admin (1) only
                if ($role_id !== 1) {
                    return ['error' => "You do not have permission to access the '{$table}' table."];
                }
            } else {
                // Check module access
                if (function_exists('has_module_access')) {
                    $has_access = has_module_access($module);
                    // Admin always has access
                    if (!$has_access && $role_id === 1) {
                        $has_access = true;
                    }
                    if (!$has_access) {
                        return ['error' => "You do not have permission to access the '{$table}' table. Access to '{$module}' module is required."];
                    }
                }
            }
        }

        // 5. Enforce LIMIT cap to prevent massive data dumps
        $sql = rtrim($sql, ';');

        if (preg_match('/\bLIMIT\s+(\d+)/i', $sql, $limit_match)) {
            if ((int) $limit_match[1] > 100) {
                $sql = preg_replace('/\bLIMIT\s+\d+/i', 'LIMIT 100', $sql, 1);
            }
        } else {
            $sql .= ' LIMIT 100';
        }

        $this->load->library('ai_handler');
        if (!$this->ai_handler->is_safe_select_query($sql)) {
            ai_sql_audit_log($sql, 'blocked', 'safety validation failed');
            return ['error' => 'Query failed safety validation.'];
        }

        // Execute on the least-privilege connection (SELECT-only MySQL user
        // when AI_DB_RO_USER/AI_DB_RO_PASS env vars are configured).
        $ai_db = $this->load->database('ai_readonly', TRUE);
        if (!$ai_db || !$ai_db->conn_id) {
            $ai_db = $this->db;
        }
        $query = $ai_db->query($sql);
        if (!$query) {
            $db_error = $ai_db->error();
            $error_msg = isset($db_error['message']) ? $db_error['message'] : 'Unknown database error';
            ai_sql_audit_log($sql, 'blocked', 'db error: ' . $error_msg);
            return ['error' => 'Query failed: ' . $error_msg];
        }

        $rows = ai_sql_scrub_result($query->result_array());
        ai_sql_audit_log($sql, 'executed', count($rows) . ' rows');

        return $rows;
    }
    
    /**
     * Generate export file in specified format (CSV, Excel, or PDF)
     */
    private function generate_export_file($data, $format = 'csv', $query_description = '') {
        if (empty($data) || !is_array($data)) {
            return ['error' => 'No data to export'];
        }
        
        // Ensure reports directory exists
        $reports_dir = FCPATH . 'uploads/reports/';
        if (!is_dir($reports_dir)) {
            mkdir($reports_dir, 0755, true);
        }
        
        $timestamp = date('Ymd_His');
        $safe_description = preg_replace('/[^a-zA-Z0-9_]/', '_', substr($query_description, 0, 30));
        $filename_base = 'ai_report_' . $safe_description . '_' . $timestamp;
        
        switch ($format) {
            case 'excel':
                return $this->generate_excel_file($data, $reports_dir, $filename_base);
                
            case 'pdf':
                return $this->generate_pdf_file($data, $reports_dir, $filename_base, $query_description);
                
            case 'csv':
            default:
                return $this->generate_csv_file($data, $reports_dir, $filename_base);
        }
    }
    
    /**
     * Generate CSV file
     */
    private function generate_csv_file($data, $dir, $filename_base) {
        $filename = $filename_base . '.csv';
        $filepath = $dir . $filename;
        
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            return ['error' => 'Could not create CSV file'];
        }
        
        // Add BOM for UTF-8 Excel compatibility
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
        
        return ['path' => 'uploads/reports/' . $filename, 'format' => 'csv'];
    }
    
    /**
     * Generate Excel file (CSV with Excel-compatible formatting)
     */
    private function generate_excel_file($data, $dir, $filename_base) {
        // For simplicity, generate CSV with Excel-compatible headers
        // In production, you might want to use PhpSpreadsheet library
        $filename = $filename_base . '.csv';
        $filepath = $dir . $filename;
        
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            return ['error' => 'Could not create Excel file'];
        }
        
        // Add BOM for UTF-8 Excel compatibility
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
        
        return ['path' => 'uploads/reports/' . $filename, 'format' => 'excel'];
    }
    
    /**
     * Generate PDF file
     */
    private function generate_pdf_file($data, $dir, $filename_base, $title = '') {
        $filename = $filename_base . '.pdf';
        $filepath = $dir . $filename;
        
        // Build HTML for PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_view($title ?: 'Report') . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 10px; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #667eea; color: white; padding: 8px; text-align: left; font-weight: bold; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .header-info { margin-bottom: 20px; color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <h2>' . esc_view($title ?: 'AI Generated Report') . '</h2>
    <div class="header-info">
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Total Records:</strong> ' . count($data) . '</p>
    </div>
    <table>
        <thead>
            <tr>';
        
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . esc_view($header) . '</th>';
            }
        }
        
        $html .= '</tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_view($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
    </table>
</body>
</html>';
        
        // Render via Pdf_export facade (Dompdf) or fallback to HTML file
        $this->load->library('pdf_export');
        if ($this->pdf_export->html_to_pdf_file($html, $filepath, 'A4', 'landscape')) {
            return ['path' => 'uploads/reports/' . $filename, 'format' => 'pdf'];
        }

        $html_filename = $filename_base . '.html';
        file_put_contents($dir . $html_filename, $html);
        return ['path' => 'uploads/reports/' . $html_filename, 'format' => 'html'];
    }
    
    /**
     * Export endpoint for direct file downloads
     * Changed to POST method for security - prevents data exposure in URLs
     */
    public function export() {
        // Only allow POST requests for security
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed. Please use POST request.', 405);
            return;
        }
        
        $data_encoded = $this->input->post('data');
        $format = strtolower($this->input->post('format') ?: 'csv');
        $query = $this->input->post('query') ?: 'Report';
        
        if (empty($data_encoded)) {
            show_error('No data provided for export', 400);
            return;
        }
        
        // Decode and validate
        $decoded_json = base64_decode($data_encoded);
        if ($decoded_json === false) {
             show_error('Base64 decode failed', 400);
             return;
        }
        
        $data = json_decode($decoded_json, true);
        if (!is_array($data)) {
            show_error('Invalid data format (JSON decode failed)', 400);
            return;
        }
        
        $file_info = $this->generate_export_file($data, $format, $query);
        
        if ($file_info && !isset($file_info['error'])) {
            $filepath = FCPATH . $file_info['path'];
            if (file_exists($filepath)) {
                $mime_types = [
                    'csv' => 'text/csv',
                    'excel' => 'text/csv', // CSV with BOM serves as Excel
                    'pdf' => 'application/pdf',
                    'html' => 'text/html'
                ];
                
                $mime = isset($mime_types[$file_info['format']]) ? $mime_types[$file_info['format']] : 'application/octet-stream';
                
                // CRITICAL: Clear any previous output buffers to prevent file corruption
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                header('Content-Type: ' . $mime);
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                readfile($filepath);
                exit;
            }
        }
        
        show_error('Error generating export file: ' . (isset($file_info['error']) ? $file_info['error'] : 'Unknown error'), 500);
    }
    
    /**
     * Clear conversation history
     */
    public function clear_history() {
        $this->session->unset_userdata('ai_conversation_history');
        echo json_encode(['status' => 'success', 'message' => 'Conversation history cleared']);
    }
}
