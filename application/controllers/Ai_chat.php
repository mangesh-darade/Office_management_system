<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_chat extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(array('url', 'permission', 'ai_chat_features', 'ai_chat_intent', 'ai_chat_ops', 'data_scope', 'hierarchy_filter'));
        $this->load->model('Ai_conversation_model', 'ai_conv');
        require_module_access(['ai', 'ai_chat'], true);
    }

    public function index() {
        $data['page_title'] = 'AI Assistant';
        $user_id = (int) $this->session->userdata('user_id');
        $conv_id = (int) $this->session->userdata('ai_conversation_id');
        $history = array();
        if ($user_id > 0) {
            if ($conv_id > 0 && $this->ai_conv->owns($conv_id, $user_id)) {
                $history = $this->ai_conv->get_session_style_history($conv_id, $user_id, 40);
            } else {
                $active = $this->ai_conv->get_or_create_active($user_id);
                if ($active) {
                    $conv_id = (int) $active->id;
                    $this->session->set_userdata('ai_conversation_id', $conv_id);
                    $history = $this->ai_conv->get_session_style_history($conv_id, $user_id, 40);
                }
            }
        }
        if (empty($history)) {
            $sess = $this->session->userdata('ai_conversation_history');
            $history = is_array($sess) ? $sess : array();
        }
        $this->session->set_userdata('ai_conversation_history', $history);
        $data['conversation_history'] = $history;
        $data['conversation_id'] = $conv_id;
        $data['conversations'] = ($user_id > 0) ? $this->ai_conv->list_for_user($user_id, 15) : array();
        $data['is_admin'] = ((int) $this->session->userdata('role_id') === 1);
        $data['suggestion_chips'] = ai_chat_suggestion_chips();
        $this->load->view('ai_chat/index', $data);
    }

    public function send_message() {
        $message = $this->input->post('message');
        $format = strtolower(trim($this->input->post('format') ?: ''));

        if (empty($message)) {
            echo json_encode(array('status' => 'error', 'message' => 'Empty message'));
            return;
        }

        try {
            $result = $this->_handle_chat_turn($message, $format);
            echo json_encode($result);
        } catch (Exception $e) {
            log_message('error', 'AI chat send_message: ' . $e->getMessage());
            echo json_encode(array(
                'status' => 'error',
                'message' => (ENVIRONMENT === 'development') ? $e->getMessage() : 'AI request failed. Please try again.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
        }
    }

    /**
     * SSE progress stream (status events + final response). Improves UX while LLM runs.
     */
    public function send_message_stream() {
        $message = $this->input->post('message');
        $format = strtolower(trim($this->input->post('format') ?: ''));
        if (empty($message)) {
            $this->_sse_emit('error', array('message' => 'Empty message'));
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        try {
            $result = $this->_handle_chat_turn($message, $format, function ($stage, $detail = '') {
                $this->_sse_emit('status', array('stage' => $stage, 'detail' => $detail));
            });
            $this->_sse_emit('done', $result);
        } catch (Exception $e) {
            log_message('error', 'AI chat stream: ' . $e->getMessage());
            $this->_sse_emit('error', array(
                'message' => (ENVIRONMENT === 'development') ? $e->getMessage() : 'AI request failed.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
        }
    }

    private function _sse_emit($event, array $data)
    {
        echo 'event: ' . $event . "\n";
        echo 'data: ' . json_encode($data) . "\n\n";
        if (function_exists('flush')) {
            @flush();
        }
    }

    /**
     * Core turn processor shared by JSON + SSE endpoints.
     *
     * @param string        $message
     * @param string        $format
     * @param callable|null $on_status
     * @return array
     */
    private function _handle_chat_turn($message, $format = '', $on_status = null)
    {
        $notify = function ($stage, $detail = '') use ($on_status) {
            if (is_callable($on_status)) {
                $on_status($stage, $detail);
            }
        };

        $this->load->library('Ai_handler');
        $user_id = (int) $this->session->userdata('user_id');
        $user_email = (string) $this->session->userdata('email');
        $user_role_id = (int) $this->session->userdata('role_id');

        $rate = ai_chat_rate_limit_ok($user_id, 60);
        if (empty($rate['ok'])) {
            return array(
                'status' => 'error',
                'message' => isset($rate['message']) ? $rate['message'] : 'Rate limit',
                'csrf_token' => $this->security->get_csrf_hash(),
            );
        }

        $user_info = null;
        if ($user_id > 0) {
            $this->load->model('User_model');
            $user = $this->User_model->get($user_id);
            if ($user) {
                $user_info = array(
                    'id' => $user_id,
                    'name' => isset($user->name) ? $user->name : '',
                    'email' => $user_email ?: (isset($user->email) ? $user->email : ''),
                    'role_id' => $user_role_id ?: (isset($user->role_id) ? $user->role_id : null),
                );
            }
        }

        $conv_id = (int) $this->session->userdata('ai_conversation_id');
        if ($user_id > 0) {
            if ($conv_id < 1 || !$this->ai_conv->owns($conv_id, $user_id)) {
                $active = $this->ai_conv->get_or_create_active($user_id);
                $conv_id = $active ? (int) $active->id : 0;
                $this->session->set_userdata('ai_conversation_id', $conv_id);
            }
        }

        $conversation_history = $this->session->userdata('ai_conversation_history');
        if (!is_array($conversation_history)) {
            $conversation_history = array();
        }
        if ($conv_id > 0 && $user_id > 0 && empty($conversation_history)) {
            $conversation_history = $this->ai_conv->get_session_style_history($conv_id, $user_id, 20);
        }
        if (count($conversation_history) > 10) {
            $conversation_history = array_slice($conversation_history, -10);
        }

        $conversation_history[] = array(
            'user' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        );
        if ($conv_id > 0) {
            $this->ai_conv->add_message($conv_id, 'user', $message);
        }

        // Follow-ups against last tool result (export / repeat / date refine / re-ask)
        $follow = ai_chat_is_followup($message);
        $mem = ai_chat_memory_get();
        if ($follow && !empty($mem['last_rows']) && is_array($mem['last_rows'])) {
            if (strpos($follow, 'export_') === 0) {
                $fmt = substr($follow, 7);
                $file_info = $this->generate_export_file($mem['last_rows'], $fmt, isset($mem['last_query']) ? $mem['last_query'] : 'export');
                if ($file_info && !isset($file_info['error'])) {
                    $final = 'Exported as ' . strtoupper($fmt) . ': <a href="' . base_url($file_info['path']) . '" target="_blank">Download</a>';
                    ai_chat_audit_log($user_id, $message, isset($mem['last_tool']) ? $mem['last_tool'] : null, 'followup_export', true);
                    return $this->_finalize_assistant_turn($conversation_history, $final, $conv_id, array('source' => 'followup'));
                }
            }
            if ($follow === 'repeat' && !empty($mem['last_html'])) {
                ai_chat_audit_log($user_id, $message, isset($mem['last_tool']) ? $mem['last_tool'] : null, 'followup_repeat', true);
                return $this->_finalize_assistant_turn($conversation_history, $mem['last_html'], $conv_id, array('source' => 'followup'));
            }
            if ($follow === 'filter_hint') {
                $hint = 'Tell me the filter (example: only pending, or only IT). Or ask a full new question.';
                return $this->_finalize_assistant_turn($conversation_history, $hint, $conv_id, array('source' => 'followup'));
            }
        }

        // "what I asked / check first" → re-run last question
        $tool_message = $message;
        if (function_exists('ai_chat_is_meta_reask') && ai_chat_is_meta_reask($message)
            && !empty($mem['last_query'])
        ) {
            $tool_message = (string) $mem['last_query'];
            $notify('reask', 'Re-checking your last question');
        }

        // "this month / not only today" after an attendance/leave tool → same tool, new dates
        if (function_exists('ai_chat_is_date_refinement') && ai_chat_is_date_refinement($message)
            && !empty($mem['last_tool'])
            && empty(ai_chat_match_tool($message))
        ) {
            $tool_message = trim((isset($mem['last_query']) ? $mem['last_query'] : '') . ' ' . $message);
            $notify('refine', 'Updating date range from your question');
        }

        // Multilingual intent → deterministic tools (lexicon score, then LLM classify)
        $notify('tool_check', 'Understanding your question');
        $tool = ai_chat_match_tool($tool_message);
        if (!$tool && function_exists('ai_chat_is_date_refinement') && ai_chat_is_date_refinement($message)
            && !empty($mem['last_tool'])
            && function_exists('ai_chat_user_can_use_tool')
            && ai_chat_user_can_use_tool($mem['last_tool'])
        ) {
            $tool = $mem['last_tool'];
            $tool_message = trim((isset($mem['last_query']) ? $mem['last_query'] : '') . ' ' . $message);
        }
        if (!$tool && $user_id > 0) {
            $notify('intent_llm', 'Detecting intent');
            $tool = $this->ai_handler->classify_intent($tool_message);
        }
        if ($tool && $user_id > 0) {
            $notify('tool_run', $tool);
            $tool_result = ai_chat_run_tool($tool, $user_id, $this->db, $tool_message);
            if (!empty($tool_result['ok'])) {
                $final_answer = $tool_result['html'];
                if (function_exists('ai_chat_needs_localization') && ai_chat_needs_localization($message)) {
                    $notify('localize', 'Matching your language');
                    $final_answer = $this->ai_handler->localize_answer($message, $final_answer);
                }
                if (!empty($tool_result['rows'])) {
                    $final_answer = ai_chat_append_export_buttons($final_answer, $tool_result['rows'], $tool_message);
                }
                ai_chat_memory_set(array(
                    'last_tool' => $tool,
                    'last_query' => $tool_message,
                    'last_html' => $final_answer,
                    'last_rows' => !empty($tool_result['rows']) ? $tool_result['rows'] : array(),
                    'last_range' => function_exists('ai_chat_parse_date_range') ? ai_chat_parse_date_range($tool_message) : null,
                ));
                ai_chat_audit_log($user_id, $message, $tool, 'tool', true);
                return $this->_finalize_assistant_turn($conversation_history, $final_answer, $conv_id, array(
                    'source' => 'tool',
                    'tool' => $tool,
                ));
            }
        }

        $notify('rag', 'Loading schema context');
        $context = $this->ai_handler->get_relevant_context($message);

        $notify('llm', 'Asking AI');
        $response = $this->ai_handler->process_query($message, $context, $user_info, $conversation_history);

        if (isset($response['type']) && $response['type'] === 'sql_query') {
            $notify('sql', 'Fetching your data');
            $sql = trim((string) $response['query']);
            $query_result = $this->execute_safe_query($sql, $user_id);

            $wants_file = (stripos($message, 'file') !== false
                || stripos($message, 'report') !== false
                || stripos($message, 'download') !== false
                || stripos($message, 'export') !== false
                || stripos($message, 'excel') !== false
                || stripos($message, 'pdf') !== false
                || stripos($message, 'csv') !== false
                || $format !== '');

            $detected_format = 'csv';
            if ($format !== '' && in_array($format, array('excel', 'pdf', 'csv'), true)) {
                $detected_format = $format;
            } elseif (stripos($message, 'excel') !== false || stripos($message, 'xlsx') !== false) {
                $detected_format = 'excel';
            } elseif (stripos($message, 'pdf') !== false) {
                $detected_format = 'pdf';
            } elseif (stripos($message, 'csv') !== false) {
                $detected_format = 'csv';
            }

            if (isset($query_result['error'])) {
                $friendly = $this->_friendly_data_error($query_result['error']);
                ai_chat_audit_log($user_id, $message, null, 'sql_error', false);
                return $this->_finalize_assistant_turn($conversation_history, $friendly, $conv_id, array('source' => 'sql_error'));
            }

            if ($wants_file && is_array($query_result) && !empty($query_result)) {
                $file_info = $this->generate_export_file($query_result, $detected_format, $message);
                if ($file_info && !isset($file_info['error'])) {
                    $download_url = base_url($file_info['path']);
                    $format_name = strtoupper($detected_format);
                    $final_answer = 'Here is your ' . $format_name . ' report. '
                        . '<a href="' . htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-primary btn-sm me-2"><i class="bi bi-download"></i> Download ' . $format_name . '</a>';
                    $final_answer = ai_chat_append_export_buttons($final_answer, $query_result, $message);
                } else {
                    $final_answer = 'I could not generate the download file. Please try again.';
                }
            } else {
                $notify('summarize', 'Preparing answer');
                $user_name = isset($user_info['name']) ? $user_info['name'] : null;
                $final_answer = $this->ai_handler->summarize_data($message, $query_result, $user_name);
                if (is_array($query_result) && !empty($query_result)) {
                    $final_answer = ai_chat_append_export_buttons($final_answer, $query_result, $message);
                }
            }

            ai_chat_memory_set(array(
                'last_tool' => null,
                'last_query' => $message,
                'last_html' => $final_answer,
                'last_rows' => $query_result,
            ));
            ai_chat_audit_log($user_id, $message, null, 'sql', true);
            $extra = array('source' => 'sql');
            if (ENVIRONMENT === 'development' && $user_role_id === 1) {
                $extra['debug_sql'] = $sql;
            }
            return $this->_finalize_assistant_turn($conversation_history, $final_answer, $conv_id, $extra);
        }

        $text_content = isset($response['text']) ? $response['text'] : json_encode($response);
        ai_chat_audit_log($user_id, $message, null, 'text', true);
        return $this->_finalize_assistant_turn($conversation_history, $text_content, $conv_id, array('source' => 'text'));
    }

    /**
     * User-facing error (no technical SQL wording).
     *
     * @param string $error
     * @return string
     */
    private function _friendly_data_error($error)
    {
        $error = (string) $error;
        if (stripos($error, 'permission') !== false || stripos($error, 'access') !== false) {
            return 'You do not have access to that information.';
        }
        if (stripos($error, 'destructive') !== false || stripos($error, 'blocked') !== false || stripos($error, 'safety') !== false) {
            return 'I could not complete that request safely. Please rephrase (for example: "today\'s attendance" or "my leave balance").';
        }
        if (stripos($error, 'Query failed') !== false || stripos($error, 'Unknown column') !== false) {
            return 'I could not find that data in the expected format. Try: "Who is on leave today?" or "My attendance today".';
        }
        if (ENVIRONMENT === 'development' && (int) $this->session->userdata('role_id') === 1) {
            return 'Unable to fetch data. (' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . ')';
        }
        return 'I could not fetch that right now. Please try a simpler question.';
    }

    /**
     * Confirm and run a pending LLM SQL query (legacy; UI no longer shows SQL).
     */
    public function confirm_sql()
    {
        // Kept for backward compatibility — prefer auto-run path in send_message.
        $token = (string) $this->input->post('token');
        $cancel = (int) $this->input->post('cancel');
        $pending = $this->session->userdata('ai_pending_sql');
        if ($cancel) {
            $this->session->unset_userdata('ai_pending_sql');
            echo json_encode(array(
                'status' => 'success',
                'response' => 'Cancelled.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
            return;
        }
        if (!is_array($pending) || empty($pending['token']) || !hash_equals((string) $pending['token'], $token)) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Nothing to run. Please ask your question again.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
            return;
        }
        if (!empty($pending['created_at']) && (time() - (int) $pending['created_at']) > 300) {
            $this->session->unset_userdata('ai_pending_sql');
            echo json_encode(array(
                'status' => 'error',
                'message' => 'That request expired. Please ask again.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $message = isset($pending['message']) ? $pending['message'] : 'query';
        $this->load->library('Ai_handler');
        $query_result = $this->execute_safe_query($pending['sql'], $user_id);
        $this->session->unset_userdata('ai_pending_sql');

        if (isset($query_result['error'])) {
            echo json_encode(array(
                'status' => 'success',
                'response' => $this->_friendly_data_error($query_result['error']),
                'csrf_token' => $this->security->get_csrf_hash(),
            ));
            return;
        }

        $user_name = null;
        $this->load->model('User_model');
        $user = $this->User_model->get($user_id);
        if ($user && isset($user->name)) {
            $user_name = $user->name;
        }
        $final_answer = $this->ai_handler->summarize_data($message, $query_result, $user_name);
        if (!empty($query_result)) {
            $final_answer = ai_chat_append_export_buttons($final_answer, $query_result, $message);
        }
        ai_chat_memory_set(array(
            'last_tool' => null,
            'last_query' => $message,
            'last_html' => $final_answer,
            'last_rows' => $query_result,
        ));
        $history = $this->session->userdata('ai_conversation_history');
        if (!is_array($history)) {
            $history = array();
        }
        $conv_id = (int) $this->session->userdata('ai_conversation_id');
        $payload = $this->_finalize_assistant_turn($history, $final_answer, $conv_id, array('source' => 'sql'));
        echo json_encode($payload);
    }

    private function _finalize_assistant_turn(array $conversation_history, $final_answer, $conv_id, array $extra = array())
    {
        $conversation_history[] = array(
            'assistant' => $final_answer,
            'timestamp' => date('Y-m-d H:i:s'),
        );
        if (count($conversation_history) > 40) {
            $conversation_history = array_slice($conversation_history, -40);
        }
        $this->session->set_userdata('ai_conversation_history', $conversation_history);
        if ((int) $conv_id > 0) {
            $this->ai_conv->add_message((int) $conv_id, 'assistant', $final_answer, $extra);
        }

        $payload = array(
            'status' => 'success',
            'response' => $final_answer,
            'csrf_token' => $this->security->get_csrf_hash(),
            'conversation_id' => (int) $conv_id,
            'source' => isset($extra['source']) ? $extra['source'] : 'ai',
        );
        if (isset($extra['debug_sql'])) {
            $payload['debug_sql'] = $extra['debug_sql'];
        }
        if (isset($extra['tool'])) {
            $payload['tool'] = $extra['tool'];
        }
        return $payload;
    }

    public function new_chat() {
        $user_id = (int) $this->session->userdata('user_id');
        $this->session->unset_userdata('ai_conversation_history');
        ai_chat_memory_clear();
        if ($user_id > 0) {
            $conv = $this->ai_conv->start_new($user_id);
            if ($conv) {
                $this->session->set_userdata('ai_conversation_id', (int) $conv->id);
            } else {
                $this->session->unset_userdata('ai_conversation_id');
            }
        }
        echo json_encode(array(
            'status' => 'success',
            'message' => 'New chat started',
            'csrf_token' => $this->security->get_csrf_hash(),
            'conversation_id' => (int) $this->session->userdata('ai_conversation_id'),
        ));
    }

    public function load_conversation() {
        $user_id = (int) $this->session->userdata('user_id');
        $id = (int) $this->input->post('conversation_id');
        if ($user_id < 1 || $id < 1 || !$this->ai_conv->owns($id, $user_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'Conversation not found'));
            return;
        }
        $history = $this->ai_conv->get_session_style_history($id, $user_id, 80);
        $this->session->set_userdata('ai_conversation_id', $id);
        $this->session->set_userdata('ai_conversation_history', $history);
        echo json_encode(array(
            'status' => 'success',
            'history' => $history,
            'csrf_token' => $this->security->get_csrf_hash(),
        ));
    }

    public function reindex() {
        if ((int) $this->session->userdata('role_id') !== 1) {
            echo json_encode(array('status' => 'error', 'message' => 'Admin only'));
            return;
        }
        $this->load->library('Ai_handler');
        $result = $this->ai_handler->reindex_knowledge_base();
        echo json_encode(array(
            'status' => !empty($result['ok']) ? 'success' : 'error',
            'message' => isset($result['message']) ? $result['message'] : '',
            'count' => isset($result['count']) ? (int) $result['count'] : 0,
            'csrf_token' => $this->security->get_csrf_hash(),
        ));
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

    private function execute_safe_query($sql, $user_id = 0) {
        // SECURITY CRITICAL: Ensure this is a SELECT query only
        $sql = trim($sql);
        
        // 1. Strip comments to prevent hidden commands handling
        // Strip multi-line comments /* ... */
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        // Strip single line comments -- or #
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/#.*$/m', '', $sql);
        $sql = trim($sql);

        // 2. Strict SELECT check
        if (!preg_match('/^\s*SELECT\b/i', $sql)) {
            return ['error' => 'Only SELECT queries are allowed for safety.'];
        }
        
        // 3. Check for destructive SQL keywords as whole words
        $destructive_patterns = [
            '/\bDROP\b/i',
            '/\bDELETE\b/i',
            '/\bUPDATE\b/i',
            '/\bINSERT\b/i',
            '/\bTRUNCATE\b/i',
            '/\bALTER\b/i',
            '/\bCREATE\b/i',
            '/\bREPLACE\b/i',
            '/\bEXEC\b/i',
            '/\bEXECUTE\b/i',
            '/\bCALL\b/i',
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/\bLOCK\b/i',
            '/\bUNLOCK\b/i',
            '/\bRENAME\b/i',
            '/\bUNION\b/i',
            '/\bINTO\b/i',
            '/\bOUTFILE\b/i',
            '/\bDUMPFILE\b/i'
        ];
        
        foreach ($destructive_patterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return ['error' => 'Destructive or unsafe queries are blocked.'];
            }
        }

        // Block multi-statement and system-catalog probing
        // Block multi-statement; allow a single trailing semicolon from LLM output
        $sql = rtrim($sql, "; \t\n\r");
        if (strpos($sql, ';') !== false) {
            return ['error' => 'I could not run that query safely. Try asking more simply, e.g. "my leave balance" or "my open tasks".'];
        }

        if (preg_match('/\b(SLEEP|BENCHMARK|LOAD_FILE|GET_LOCK|RELEASE_LOCK)\s*\(/i', $sql)) {
            return ['error' => 'Unsafe SQL functions are blocked.'];
        }

        if (preg_match('/\binformation_schema\b|\bmysql\.|\bperformance_schema\b|\bsys\./i', $sql)) {
            return ['error' => 'Access to system tables is blocked.'];
        }
        
        // 4. Check table access permissions
        $this->load->helper(['permission', 'ai_sql_guard', 'ai_chat_features', 'data_scope']);
        $role_id = (int)$this->session->userdata('role_id');
        if ($user_id < 1) {
            $user_id = (int) $this->session->userdata('user_id');
        }
        
        $table_to_module_map = ai_chat_table_module_map();
        
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

        $guard_error = ai_sql_guard_check($sql, $tables_in_query);
        if ($guard_error !== null) {
            ai_sql_audit_log($sql, 'blocked', $guard_error);
            return ['error' => $guard_error];
        }
        
        foreach ($tables_in_query as $table) {
            $module = isset($table_to_module_map[$table]) ? $table_to_module_map[$table] : null;
            
            if ($module === null) {
                if ($role_id !== 1) {
                    return ['error' => "You do not have permission to access the '{$table}' table."];
                }
            } else {
                if (function_exists('has_module_access')) {
                    $has_access = has_module_access($module);
                    if (!$has_access && $role_id === 1) {
                        $has_access = true;
                    }
                    if (!$has_access) {
                        return ['error' => "You do not have permission to access the '{$table}' table. Access to '{$module}' module is required."];
                    }
                }
            }
        }

        // Hard data-scope for staff / user-group roles
        $sql = ai_chat_enforce_data_scope_sql($sql, $user_id);

        // Hierarchy scope for org-wide tables (team visibility)
        if (function_exists('hierarchy_sql_user_filter') && !hierarchy_filter_sees_all_records($role_id)) {
            foreach (array('attendance' => 'user_id', 'leave_requests' => 'user_id', 'daily_work_logs' => 'user_id') as $tbl => $col) {
                if (preg_match('/\b(from|join)\s+`?' . preg_quote($tbl, '/') . '`?\b/i', $sql)) {
                    $clause = hierarchy_sql_user_filter($tbl . '.' . $col, $user_id, $role_id);
                    if ($clause === '') {
                        $clause = hierarchy_sql_user_filter($col, $user_id, $role_id);
                    }
                    if ($clause !== '' && $clause !== ' AND 1=0') {
                        $inj = ltrim($clause);
                        if (stripos($inj, 'AND ') === 0) {
                            $inj = substr($inj, 4);
                        }
                        if (preg_match('/\bWHERE\b/i', $sql)) {
                            $sql = preg_replace('/\bWHERE\b/i', 'WHERE (' . $inj . ') AND ', $sql, 1);
                        } else {
                            $sql = preg_replace('/\bLIMIT\b/i', 'WHERE ' . $inj . ' LIMIT', $sql, 1);
                        }
                    } elseif ($clause === ' AND 1=0') {
                        return array('error' => 'No accessible rows for your role.');
                    }
                }
            }
        }

        // 5. Enforce LIMIT cap
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

        $ai_db = $this->load->database('ai_readonly', TRUE);
        if (!$ai_db || !$ai_db->conn_id) {
            $ai_db = $this->db;
        }
        $query = $ai_db->query($sql);
        if (!$query) {
            $db_error = $ai_db->error();
            $error_msg = isset($db_error['message']) ? $db_error['message'] : 'Unknown database error';
            ai_sql_audit_log($sql, 'blocked', 'db error: ' . $error_msg);
            $safe = (ENVIRONMENT === 'development')
                ? ('Query failed: ' . $error_msg)
                : 'Query failed. Please rephrase your question.';
            return ['error' => $safe];
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
     * Clear conversation history (session + DB for current user)
     */
    public function clear_history() {
        $user_id = (int) $this->session->userdata('user_id');
        $this->session->unset_userdata('ai_conversation_history');
        $this->session->unset_userdata('ai_conversation_id');
        ai_chat_memory_clear();
        if ($user_id > 0) {
            $this->ai_conv->clear_all_for_user($user_id);
        }
        echo json_encode(array(
            'status' => 'success',
            'message' => 'Conversation history cleared',
            'csrf_token' => $this->security->get_csrf_hash(),
        ));
    }
}
