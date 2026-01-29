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
        
        // Ensure user is logged in
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
        
        // Check AI module access permission
        $role_id = (int)$this->session->userdata('role_id');
        $allowed = false;
        
        if (function_exists('has_module_access')) {
            $allowed = has_module_access('ai') || has_module_access('ai_chat');
        }
        
        // Fallback: if no permissions row exists yet for 'ai', allow Admin (role 1) and Manager (role 2)
        if (!$allowed) {
            $hasPermRow = false;
            if ($this->db->table_exists('permissions')) {
                $this->db->where_in('module', ['ai', 'ai_chat']);
                $hasPermRow = ($this->db->count_all_results('permissions') > 0);
            }
            if (!$hasPermRow && in_array($role_id, [1, 2], true)) {
                $allowed = true;
            }
        }
        
        if (!$allowed) {
            $this->session->set_flashdata('access_denied', 'You do not have permission to access the AI Assistant module.');
            redirect('dashboard');
        }
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
                        
                        // Use export endpoint for format conversion (proper URL with query parameters)
                        $export_data = base64_encode(json_encode($query_result));
                        $export_query = urlencode($message);
                        
                        if ($detected_format === 'csv') {
                            $excel_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=excel';
                            $pdf_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=pdf';
                            $final_answer .= "<a href='{$excel_url}' class='btn btn-success btn-sm me-2'><i class='bi bi-file-earmark-excel'></i> Excel</a>";
                            $final_answer .= "<a href='{$pdf_url}' class='btn btn-danger btn-sm'><i class='bi bi-file-earmark-pdf'></i> PDF</a>";
                        } elseif ($detected_format === 'excel') {
                            $csv_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=csv';
                            $pdf_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=pdf';
                            $final_answer .= "<a href='{$csv_url}' class='btn btn-primary btn-sm me-2'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</a>";
                            $final_answer .= "<a href='{$pdf_url}' class='btn btn-danger btn-sm'><i class='bi bi-file-earmark-pdf'></i> PDF</a>";
                        } elseif ($detected_format === 'pdf') {
                            $csv_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=csv';
                            $excel_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=excel';
                            $final_answer .= "<a href='{$csv_url}' class='btn btn-primary btn-sm me-2'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</a>";
                            $final_answer .= "<a href='{$excel_url}' class='btn btn-success btn-sm'><i class='bi bi-file-earmark-excel'></i> Excel</a>";
                        }
                    } else {
                        $final_answer = isset($file_info['error']) ? $file_info['error'] : 'Error generating export file.';
                    }
                } else {
                    // 4. Summarize the data for the user (pass user info for personalized responses)
                    $user_name = isset($user_info['name']) ? $user_info['name'] : null;
                    $final_answer = $this->ai_handler->summarize_data($message, $query_result, $user_name);
                    
                    // Add export options if data is available
                    if (is_array($query_result) && !isset($query_result['error']) && !empty($query_result)) {
                        $export_data = base64_encode(json_encode($query_result));
                        $export_query = urlencode($message);
                        
                        $csv_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=csv';
                        $excel_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=excel';
                        $pdf_url = site_url('ai_chat/export') . '?data=' . urlencode($export_data) . '&query=' . $export_query . '&format=pdf';
                        
                        $final_answer .= "<br><br><small>Export options: ";
                        $final_answer .= "<a href='{$csv_url}' class='btn btn-sm btn-outline-primary me-1'><i class='bi bi-file-earmark-spreadsheet'></i> CSV</a> ";
                        $final_answer .= "<a href='{$excel_url}' class='btn btn-sm btn-outline-success me-1'><i class='bi bi-file-earmark-excel'></i> Excel</a> ";
                        $final_answer .= "<a href='{$pdf_url}' class='btn btn-sm btn-outline-danger'><i class='bi bi-file-earmark-pdf'></i> PDF</a>";
                        $final_answer .= "</small>";
                    }
                }
                
                // Save AI response to conversation history
                $conversation_history[] = [
                    'assistant' => $final_answer,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $this->session->set_userdata('ai_conversation_history', $conversation_history);
                
                echo json_encode([
                    'status' => 'success',
                    'response' => $final_answer,
                    'debug_sql' => $response['query']
                ]);
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
                    'response' => $text_content
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
        
        // Check if query starts with SELECT (case-insensitive)
        if (stripos($sql, 'SELECT') !== 0) {
            return ['error' => 'Only SELECT queries are allowed for safety.'];
        }
        
        // Check for destructive SQL keywords as whole words (not substrings)
        // Use regex with word boundaries to avoid false positives like "updated_at" matching "UPDATE"
        $destructive_patterns = [
            '/\bDROP\b/i',      // DROP TABLE, DROP DATABASE, etc.
            '/\bDELETE\b/i',    // DELETE FROM
            '/\bUPDATE\b/i',    // UPDATE table SET
            '/\bINSERT\b/i',    // INSERT INTO
            '/\bTRUNCATE\b/i',  // TRUNCATE TABLE
            '/\bALTER\b/i',     // ALTER TABLE
            '/\bCREATE\b/i',    // CREATE TABLE
            '/\bREPLACE\b/i',   // REPLACE INTO
            '/\bEXEC\b/i',      // EXEC, EXECUTE
            '/\bEXECUTE\b/i',   // EXECUTE
            '/\bCALL\b/i',      // CALL procedure
        ];
        
        foreach ($destructive_patterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return ['error' => 'Destructive queries are blocked.'];
            }
        }
        
        // Check table access permissions
        $this->load->helper('permission');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Map tables to modules for permission checking
        $table_to_module_map = [
            'users' => 'users',
            'employees' => 'employees',
            'attendance' => 'attendance',
            'leave_requests' => 'leave_requests',
            'leave_types' => 'leave_requests',
            'leave_approvals' => 'leave_requests',
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
            'assets' => 'assets_mgmt'
        ];
        
        // Extract table names from SQL query (simple regex match)
        preg_match_all('/\bFROM\s+`?(\w+)`?/i', $sql, $from_matches);
        preg_match_all('/\bJOIN\s+`?(\w+)`?/i', $sql, $join_matches);
        
        $tables_in_query = array_merge(
            isset($from_matches[1]) ? $from_matches[1] : [],
            isset($join_matches[1]) ? $join_matches[1] : []
        );
        $tables_in_query = array_unique(array_map('strtolower', $tables_in_query));
        
        // Check permission for each table
        foreach ($tables_in_query as $table) {
            $module = isset($table_to_module_map[$table]) ? $table_to_module_map[$table] : null;
            
            if ($module === null) {
                // System tables (settings, roles, etc.) - only allow admin
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

        // Execute the query
        $query = $this->db->query($sql);
        if (!$query) {
            $db_error = $this->db->error();
            $error_msg = isset($db_error['message']) ? $db_error['message'] : 'Unknown database error';
            return ['error' => 'Query failed: ' . $error_msg];
        }
        
        return $query->result_array();
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
    <title>' . htmlspecialchars($title ?: 'Report') . '</title>
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
    <h2>' . htmlspecialchars($title ?: 'AI Generated Report') . '</h2>
    <div class="header-info">
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Total Records:</strong> ' . count($data) . '</p>
    </div>
    <table>
        <thead>
            <tr>';
        
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
        }
        
        $html .= '</tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
    </table>
</body>
</html>';
        
        // Try to use DomPDF if available
        if (class_exists('\\Dompdf\\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                
                file_put_contents($filepath, $dompdf->output());
                return ['path' => 'uploads/reports/' . $filename, 'format' => 'pdf'];
            } catch (Exception $e) {
                // Fallback to HTML
                $html_filename = $filename_base . '.html';
                file_put_contents($dir . $html_filename, $html);
                return ['path' => 'uploads/reports/' . $html_filename, 'format' => 'html'];
            }
        } else {
            // Fallback to HTML file
            $html_filename = $filename_base . '.html';
            file_put_contents($dir . $html_filename, $html);
            return ['path' => 'uploads/reports/' . $html_filename, 'format' => 'html'];
        }
    }
    
    /**
     * Export endpoint for direct file downloads
     */
    public function export() {
        $data_encoded = $this->input->get('data');
        $format = strtolower($this->input->get('format') ?: 'csv');
        $query = $this->input->get('query') ?: 'Report';
        
        if (empty($data_encoded)) {
            show_error('No data provided for export', 400);
            return;
        }
        
        $data = json_decode(base64_decode($data_encoded), true);
        if (!is_array($data)) {
            show_error('Invalid data format', 400);
            return;
        }
        
        $file_info = $this->generate_export_file($data, $format, $query);
        
        if ($file_info && !isset($file_info['error'])) {
            $filepath = FCPATH . $file_info['path'];
            if (file_exists($filepath)) {
                $mime_types = [
                    'csv' => 'text/csv',
                    'excel' => 'text/csv',
                    'pdf' => 'application/pdf',
                    'html' => 'text/html'
                ];
                
                $mime = isset($mime_types[$file_info['format']]) ? $mime_types[$file_info['format']] : 'application/octet-stream';
                $ext = pathinfo($filepath, PATHINFO_EXTENSION);
                
                header('Content-Type: ' . $mime);
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        }
        
        show_error('Error generating export file', 500);
    }
    
    /**
     * Clear conversation history
     */
    public function clear_history() {
        $this->session->unset_userdata('ai_conversation_history');
        echo json_encode(['status' => 'success', 'message' => 'Conversation history cleared']);
    }
}
