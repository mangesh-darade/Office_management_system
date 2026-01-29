<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_handler {

    protected $CI;
    protected $api_keys = [];
    protected $vector_store_path;
    protected $azure_region;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Setting_model');
        
        // Helper to get key only if enabled
        $get_key_if_enabled = function($provider) {
            $enabled = $this->CI->settings->get_setting('ai_' . $provider . '_enabled', 'no'); // Default no
            if ($enabled !== 'yes') return null;
            
            $db_key = $this->CI->settings->get_setting('ai_' . $provider . '_api_key');
            $key = !empty($db_key) ? $db_key : $this->CI->config->item($provider . '_api_key');
            return trim($key);
        };

        $this->api_keys['gemini'] = $get_key_if_enabled('gemini');
        $this->api_keys['openai'] = $get_key_if_enabled('openai');
        $this->api_keys['openrouter'] = $get_key_if_enabled('openrouter');
        $this->api_keys['huggingface'] = $get_key_if_enabled('huggingface');
        
        // Azure has slightly different key name in config 'azure_speech_key' vs provider 'azure_speech' logic
        // We'll normalize manually
        $azure_enabled = $this->CI->settings->get_setting('ai_azure_speech_enabled', 'yes');
        if ($azure_enabled === 'yes') {
             $db_azure = $this->CI->settings->get_setting('ai_azure_speech_key');
             $this->api_keys['azure'] = !empty($db_azure) ? $db_azure : $this->CI->config->item('azure_speech_key');
             $this->azure_region = $this->CI->settings->get_setting('ai_azure_speech_region', 'eastus');
        } else {
            $this->api_keys['azure'] = null;
            $this->azure_region = null;
        }
        
        // Load custom AI providers from settings
        $this->load_custom_providers();
        
        // Define path for our flat-file vector store
        $this->vector_store_path = APPPATH . 'libraries/ai_vectors.json';
    }

    /**
     * Text-to-Speech using Azure Speech service
     * Returns relative file path on success or ['error' => '...'] on failure
     */
    public function text_to_speech($text, $language = 'en-US', $voice = '') {
        $text = trim((string)$text);
        if ($text === '') {
            return ['error' => 'No text provided for text-to-speech'];
        }

        if (empty($this->api_keys['azure']) || empty($this->azure_region)) {
            return ['error' => 'Azure Speech is not configured. Please set API key and region in AI settings.'];
        }

        // Basic language -> default voice mapping (you can extend this list)
        $default_voices = [
            'en-US' => 'en-US-JennyNeural',
            'en-GB' => 'en-GB-LibbyNeural',
            'ar-EG' => 'ar-EG-SalmaNeural',
            'hi-IN' => 'hi-IN-SwaraNeural',
            'bn-IN' => 'bn-IN-TanishaaNeural'
        ];

        if ($voice === '') {
            $voice = isset($default_voices[$language]) ? $default_voices[$language] : 'en-US-JennyNeural';
        }

        // Ensure uploads/tts directory exists
        $tts_dir = FCPATH . 'uploads/tts/';
        if (!is_dir($tts_dir)) {
            @mkdir($tts_dir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $filename = 'tts_' . $timestamp . '.mp3';
        $filepath = $tts_dir . $filename;

        // Build SSML payload
        $ssml = '<speak version="1.0" xml:lang="' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '">'
              . '<voice name="' . htmlspecialchars($voice, ENT_QUOTES, 'UTF-8') . '">'
              . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
              . '</voice></speak>';

        $url = 'https://' . $this->azure_region . '.tts.speech.microsoft.com/cognitiveservices/v1';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat: audio-24khz-48kbitrate-mono-mp3',
            'Ocp-Apim-Subscription-Key: ' . $this->api_keys['azure']
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ssml);

        // Disable SSL verification for development environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $audio = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Azure TTS CURL error: ' . $error_msg];
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$audio) {
            return ['error' => 'Azure TTS request failed with status code ' . $http_code];
        }

        if (file_put_contents($filepath, $audio) === false) {
            return ['error' => 'Failed to save TTS audio file'];
        }

        // Return relative path for browser download/playback
        return ['path' => 'uploads/tts/' . $filename, 'format' => 'mp3'];
    }
    
    /**
     * Load custom AI providers from settings
     * Custom providers are stored as JSON array in 'ai_custom_providers' setting
     */
    private function load_custom_providers() {
        $custom_providers_json = $this->CI->settings->get_setting('ai_custom_providers');
        if (!empty($custom_providers_json)) {
            $custom_providers = json_decode($custom_providers_json, true);
            if (is_array($custom_providers)) {
                foreach ($custom_providers as $provider) {
                    if (isset($provider['name']) && isset($provider['enabled']) && $provider['enabled'] == '1') {
                        $provider_key = 'custom_' . strtolower(preg_replace('/[^a-z0-9]/', '_', $provider['name']));
                        $this->api_keys[$provider_key] = isset($provider['key']) ? trim($provider['key']) : null;
                    }
                }
            }
        }
    }
    
    /**
     * Get list of all available custom providers
     * @return array Array of custom provider info
     */
    public function get_custom_providers() {
        $custom_providers_json = $this->CI->settings->get_setting('ai_custom_providers');
        if (empty($custom_providers_json)) {
            return [];
        }
        
        $custom_providers = json_decode($custom_providers_json, true);
        if (!is_array($custom_providers)) {
            return [];
        }
        
        // Return only enabled providers with their info
        $enabled = [];
        foreach ($custom_providers as $provider) {
            if (isset($provider['enabled']) && $provider['enabled'] == '1') {
                $enabled[] = [
                    'name' => isset($provider['name']) ? $provider['name'] : '',
                    'has_key' => !empty(isset($provider['key']) ? trim($provider['key']) : '')
                ];
            }
        }
        
        return $enabled;
    }

    /**
     * The Main RAG Entry point
     */
    public function get_relevant_context($user_query) {
        // 1. Get embedding for user query
        $query_vector = $this->get_embedding_with_fallback($user_query);
        
        if (!$query_vector) {
            // Fallback: Return full schema if embeddings fail
            return $this->get_full_schema_text();
        }

        // 2. Load Vector Store
        $store = $this->load_vector_store();
        
        // 3. Find closest matches (Cosine Similarity)
        $matches = [];
        foreach ($store as $item) {
            // Ensure vector dimensions match
            if (count($query_vector) !== count($item['vector'])) continue;

            $similarity = $this->cosine_similarity($query_vector, $item['vector']);
            if ($similarity > 0.6) { // Slightly lower threshold for broader matching
                $matches["$similarity"] = $item['content'];
            }
        }
        krsort($matches); // Sort by similarity descending

        // If matches found, return combined text, else return full schema
        if (!empty($matches)) {
            return implode("\n\n", array_slice($matches, 0, 5));
        }

        return $this->get_full_schema_text();
    }

    public function process_query($user_query, $context, $user_info = null, $conversation_history = []) {
        // Build user context information
        $user_context = '';
        $user_name = 'User'; // Default value
        $user_id = null;
        
        if ($user_info && isset($user_info['id'])) {
            $user_name = isset($user_info['name']) && !empty($user_info['name']) ? $user_info['name'] : 'User';
            $user_email = isset($user_info['email']) && !empty($user_info['email']) ? $user_info['email'] : '';
            $user_id = $user_info['id'];
            
            $user_context = "\n\nCurrent Logged-in User Information:\n";
            $user_context .= "- User ID: {$user_id}\n";
            if (!empty($user_name)) {
                $user_context .= "- Name: {$user_name}\n";
            }
            if (!empty($user_email)) {
                $user_context .= "- Email: {$user_email}\n";
            }
            $user_context .= "\nIMPORTANT: When the user asks about 'my' data, 'my attendance', 'my tasks', etc., filter by user_id = {$user_id} or the appropriate user identifier column.\n";
            $user_context .= "You can reference the logged-in user's name as: {$user_name}\n";
        }
        
        // Build conversation history context
        $history_context = '';
        if (!empty($conversation_history) && is_array($conversation_history)) {
            $history_context = "\n\nPrevious Conversation Context (for reference):\n";
            $count = 0;
            foreach (array_slice($conversation_history, -6) as $exchange) { // Last 3 exchanges (6 messages)
                if (isset($exchange['user'])) {
                    $history_context .= "User: " . $exchange['user'] . "\n";
                }
                if (isset($exchange['assistant'])) {
                    // Truncate long responses
                    $assistant_msg = strlen($exchange['assistant']) > 200 ? substr($exchange['assistant'], 0, 200) . '...' : $exchange['assistant'];
                    $history_context .= "Assistant: " . strip_tags($assistant_msg) . "\n";
                }
                $history_context .= "---\n";
                $count++;
                if ($count >= 3) break; // Limit to last 3 exchanges
            }
            $history_context .= "\nUse this context to understand follow-up questions, date ranges, or criteria mentioned in previous messages.\n";
        }
        
        // Current date/time context for date range calculations
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        $current_datetime = date('Y-m-d H:i:s');
        $date_context = "\n\nCurrent Date/Time Context:\n";
        $date_context .= "- Today's Date: {$current_date}\n";
        $date_context .= "- Current Time: {$current_time}\n";
        $date_context .= "- Current DateTime: {$current_datetime}\n";
        $date_context .= "\nDate Range Guidelines:\n";
        $date_context .= "- 'today' = {$current_date}\n";
        $date_context .= "- 'yesterday' = " . date('Y-m-d', strtotime('-1 day')) . "\n";
        $date_context .= "- 'this week' = Start of current week to {$current_date}\n";
        $date_context .= "- 'last week' = Previous week (Monday to Sunday)\n";
        $date_context .= "- 'this month' = " . date('Y-m-01') . " to {$current_date}\n";
        $date_context .= "- 'last month' = First day to last day of previous month\n";
        $date_context .= "- 'this year' = " . date('Y-01-01') . " to {$current_date}\n";
        $date_context .= "- When user says 'last 7 days', 'last 30 days', etc., calculate from {$current_date} backwards\n";
        $date_context .= "- Always use DATE() function for date comparisons: WHERE DATE(column) >= 'YYYY-MM-DD'\n";
        
        $system_prompt = "You are an expert SQL Assistant for an Office Management System based on CodeIgniter and MySQL.
        Your goal is to answer user questions by querying the database.
        You can understand and respond in multiple languages (for example: English, Arabic, Hindi, Bengali, Marathi, etc.). By default, respond in the same language that the user uses in their latest question, unless they clearly ask you to use another language or to translate.
        
        Here is the Database Schema Context:
        $context
        {$user_context}
        {$date_context}
        {$history_context}
        
        Rules:
        1. If the user asks for data, generate a valid MYSQL SELECT query. JSON response format: {\"type\": \"sql_query\", \"query\": \"...\"}
        2. Do NOT use JOINs unless necessary.
        3. ALWAYS use table aliases (e.g., `attendance.status` or `a.status`) for common columns like 'status', 'id', 'name', 'created_at' to avoid ambiguity.
        4. Only SELECT columns relevant to the question.
        5. When user asks about 'my' data, 'my attendance', 'my tasks', 'my leaves', etc., automatically filter by the logged-in user's ID.
        6. When the user asks about 'my leave balance', 'available leave', or similar, prefer reading from the leave_balances table filtered by the logged-in user's ID (and, if present, join leave_types to show type names), instead of listing all leave_requests.
        7. For date ranges: Use DATE() function for date comparisons. If user says 'last month', 'this week', etc., calculate the exact date range based on current date context.
        8. If user asks a follow-up question without specifying dates/criteria, use information from previous conversation context.
        9. If user asks for export/file/download/excel/pdf/csv, include that in your response but still generate the SQL query.
        10. You can mention the logged-in user's name when appropriate (e.g., 'Here is your attendance, {$user_name}').
        11. If the question is not about data/database, answer normally. JSON response: {\"type\": \"text\", \"text\": \"...\"}
        12. Return ONLY valid JSON. Do not wrap in markdown code blocks.
        13. Always respond in the same language as the user's latest question, unless they explicitly request a different language or a translation.";

        // Call LLM with Fallback Logic
        $response = $this->call_llm_with_fallback($system_prompt, $user_query);
        
        // clean markdown
        $response = trim($response);
        // Remove markdown block indicators if present
        if (substr($response, 0, 3) === '```') {
            $response = preg_replace('/^```json\s*|^```\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        }
        $response = trim($response);

        // Common Issue: Literal newlines inside JSON strings cause json_decode to fail.
        // We replace newlines that are likely inside strings with a space or escaped newline.
        // A simple heuristic: Replace \n that is NOT followed by a JSON structural character or key with a space.
        // Better yet, strict Regex extraction is safer if the structure is known.
        
        // 1. Try direct decode first (cleanest)
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // 2. Try regex extraction of the full JSON object
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $json_str = $matches[0];
            
            // Fix literal newlines: 
            $json_str = str_replace(["\r\n", "\r", "\n"], "\\n", $json_str);
            
            $decoded = json_decode($json_str, true);
            if ($decoded) return $decoded;
            
            // Try cleaning control chars
            $json_str = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $matches[0]);
             $decoded = json_decode($json_str, true);
            if ($decoded) return $decoded;
        }

        // Final Fallback: Treat as plain text, but if it LOOKS like a sql_query, try to force it
        // This handles cases where json_decode fails but it's clearly a structured intent
        if (stripos($response, '"type": "sql_query"') !== false || stripos($response, '"type":"sql_query"') !== false) {
             // Try one last desperate cleanup for common trailing comma or quote issues if needed
             // For now, let's just error clearly or return it as text to debug
        }

        return ['type' => 'text', 'text' => $response];
    }

    public function summarize_data($user_query, $data_result, $user_name = null) {
        if (empty($data_result)) {
            $greeting = $user_name ? "Hi {$user_name}, " : "";
            return $greeting . "I found no records matching your request.";
        }
        
        $data_str = json_encode($data_result);
        if (strlen($data_str) > 5000) { // Limit context size
            $data_str = substr($data_str, 0, 5000) . "...(truncated)";
        }

        $user_context = $user_name ? "The logged-in user's name is: {$user_name}. " : "";
        $system_prompt = "You are a helpful analyst. The user asked: '$user_query'.
        {$user_context}The database returned this JSON data:
        $data_str
        
        Summarize this data in natural language (HTML format allowed). Keep it concise.";
        if ($user_name) {
            $system_prompt .= " You can address the user by name ({$user_name}) when appropriate.";
        }

        return $this->call_llm_with_fallback($system_prompt, "Summarize this data.");
    }

    // --- Core Fallback Logic ---

    private function call_llm_with_fallback($system, $user) {
        $errors = [];

        // Priority 0: OpenAI (Premium)
        if (!empty($this->api_keys['openai'])) {
            $res = $this->call_openai($system, $user);
            if ($this->is_valid_response($res)) return $res;
            $errors[] = "OpenAI: " . (isset($res['error']) ? $res['error'] : 'Unknown error');
        }

        // Priority 1: Gemini (Google)
        if (!empty($this->api_keys['gemini'])) {
            $res = $this->call_gemini($system, $user);
            if ($this->is_valid_response($res)) return $res;
            $errors[] = "Gemini: " . (isset($res['error']) ? $res['error'] : 'Unknown error');
        }

        // Priority 2: OpenRouter (Claude/OpenAI)
        if (!empty($this->api_keys['openrouter'])) {
            $res = $this->call_openrouter($system, $user);
            if ($this->is_valid_response($res)) return $res;
            $errors[] = "OpenRouter: " . (isset($res['error']) ? $res['error'] : 'Unknown error');
        }

        // Priority 3: Hugging Face (DialoGPT/Other)
        if (!empty($this->api_keys['huggingface'])) {
            $res = $this->call_huggingface($system, $user);
            if ($this->is_valid_response($res)) return $res;
            $errors[] = "Hugging Face: " . (isset($res['error']) ? $res['error'] : 'Unknown error');
        }

        return "Error: All AI services failed. Support details: " . implode("; ", $errors);
    }

    private function is_valid_response($res) {
        return is_string($res) && !empty($res) && !isset($res['error']);
    }

    // --- Provider Implementations ---

    private function call_gemini($system, $user) {
        // First, try to get list of available models for this API key
        $available_models = $this->list_gemini_models();
        $models = [];
        
        // If we have available models, use those (prioritize free-tier models first)
        if (!empty($available_models)) {
            // Prioritize FREE-TIER models first (flash models), then premium (pro models)
            // Free tier models: flash, flash-lite (no cost)
            // Premium models: pro (requires paid tier)
            $priority_order = [
                // Free-tier models (try these first)
                'gemini-2.5-flash',
                'gemini-2.5-flash-lite',
                'gemini-2.0-flash',
                'gemini-2.0-flash-001',
                'gemini-2.0-flash-lite-001',
                'gemini-2.0-flash-lite',
                // Premium models (try these only if free models fail)
                'gemini-2.5-pro',
            ];
            
            $models_to_try = [];
            // Add priority models first
            foreach ($priority_order as $priority_model) {
                if (in_array($priority_model, $available_models)) {
                    $models_to_try[] = ['path' => 'v1/models/' . $priority_model, 'name' => $priority_model];
                }
            }
            // Add any other available models
            foreach ($available_models as $model) {
                if (!in_array($model, $priority_order)) {
                    $models_to_try[] = ['path' => 'v1/models/' . $model, 'name' => $model];
                }
            }
            
            if (!empty($models_to_try)) {
                $models = $models_to_try;
            } else {
                // Fallback to default models if something went wrong
                $models = $this->get_default_gemini_models();
            }
        } else {
            // If we can't list models, try common models
            $models = $this->get_default_gemini_models();
        }

        $last_error = '';
        $errors = [];

        foreach ($models as $model_info) {
            $model_path = $model_info['path'];
            $model_name = $model_info['name'];

            // v1 generateContent endpoint
            $url = "https://generativelanguage.googleapis.com/{$model_path}:generateContent?key=" . $this->api_keys['gemini'];

            // Send system + user as separate parts in the same user message.
            // This matches the latest Gemini API expectations and avoids
            // "GenerateContentRequest.contents: contents is not specified" errors.
            $data = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => (string) $system],
                        ['text' => "User Question: " . (string) $user],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 8192,
                ],
            ];

            $response = $this->http_post($url, $data, ['Content-Type: application/json; charset=utf-8']);

            // Check for successful response
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                return $response['candidates'][0]['content']['parts'][0]['text'];
            }

            // Track errors for debugging
            if (isset($response['error'])) {
                $error_msg = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown error';
                $error_code = isset($response['error']['code']) ? $response['error']['code'] : '';
                
                $errors[] = "$model_name: $error_msg";
                $last_error = $error_msg;
                
                // If it's a 404 or "not found" error, try next model
                if ($error_code == 404 || 
                    strpos(strtolower($error_msg), 'not found') !== false ||
                    strpos(strtolower($error_msg), 'not supported') !== false) {
                    continue; // Try next model
                }
                
                // If it's a quota/billing error, try next model (might be free tier hitting premium model)
                if ($error_code == 429 || 
                    strpos(strtolower($error_msg), 'quota') !== false ||
                    strpos(strtolower($error_msg), 'exceeded') !== false ||
                    strpos(strtolower($error_msg), 'billing') !== false ||
                    strpos(strtolower($error_msg), 'free_tier') !== false) {
                    $errors[] = "$model_name: Quota/billing limit (trying next model)";
                    continue; // Try next model - might be free tier trying premium model
                }
                
                // Authentication or permission errors - stop trying
                if ($error_code == 401 || $error_code == 403) {
                    return ['error' => "Gemini Error ($model_name): " . $error_msg];
                }
            }
        }
        
        // If all models failed, return detailed error
        $error_details = implode('; ', $errors);
        $model_hint = '';
        if (!empty($available_models)) {
            $model_hint = " Tried available models: " . implode(', ', $available_models);
        }
        return ['error' => "Gemini Error: All models failed. " . ($error_details ?: $last_error ?: 'Please check your API key and model availability.') . $model_hint];
    }
    
    /**
     * Get default Gemini models to try if we can't list available models
     * Prioritize free-tier models (flash) over premium models (pro)
     */
    private function get_default_gemini_models() {
        return [
            // Free-tier models first (no billing required)
            ['path' => 'v1/models/gemini-2.5-flash', 'name' => 'gemini-2.5-flash'],
            ['path' => 'v1/models/gemini-2.0-flash', 'name' => 'gemini-2.0-flash'],
            ['path' => 'v1/models/gemini-1.5-flash', 'name' => 'gemini-1.5-flash'],
            // Premium models last (require paid tier)
            ['path' => 'v1/models/gemini-2.5-pro', 'name' => 'gemini-2.5-pro'],
            ['path' => 'v1/models/gemini-1.5-pro', 'name' => 'gemini-1.5-pro'],
            ['path' => 'v1/models/gemini-pro', 'name' => 'gemini-pro'],
        ];
    }
    
    /**
     * List available Gemini models for the API key
     * This helps diagnose which models are actually available
     */
    private function list_gemini_models() {
        $url = "https://generativelanguage.googleapis.com/v1/models?key=" . $this->api_keys['gemini'];
        
        // Use GET request for listing models
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return [];
        }
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['models'])) {
            return [];
        }
        
        $model_names = [];
        foreach ($decoded['models'] as $model) {
            if (isset($model['name'])) {
                // Extract just the model name (e.g., "models/gemini-pro" -> "gemini-pro")
                $name = str_replace('models/', '', $model['name']);
                $name = str_replace('v1/', '', $name);
                $name = str_replace('v1beta/', '', $name);
                if (strpos($name, 'gemini') !== false) {
                    $model_names[] = $name;
                }
            }
        }
        return array_unique($model_names);
    }

    private function call_openrouter($system, $user) {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $data = [
            'model' => 'anthropic/claude-3.5-sonnet', // User preferred model
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'max_tokens' => 100, // Drastically reduced for low credit balance
            'temperature' => 0.1
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_keys['openrouter'],
            'HTTP-Referer: http://localhost', // Required by OpenRouter
            'X-Title: Office Management System'
        ];

        $response = $this->http_post($url, $data, $headers);

        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return ['error' => json_encode($response)];
    }

    private function call_huggingface($system, $user) {
        // Switch to a better instruction following model (Mistral)
        $url = "https://router.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3";
        
        // Mistral Instruct format [INST] ... [/INST]
        $combined_input = "[INST] " . $system . "\n\n" . $user . " [/INST]";

        $data = ['inputs' => $combined_input, 'parameters' => ['max_new_tokens' => 500, 'return_full_text' => false]];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_keys['huggingface']
        ];

        $response = $this->http_post($url, $data, $headers);

        if (isset($response['error'])) {
             return ['error' => $response['error']];
        }

        if (isset($response[0]['generated_text'])) {
            return $response[0]['generated_text'];
        }

        return ['error' => json_encode($response)];
    }

    private function call_openai($system, $user) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo', // Widest compatibility (Tier 0 supported)
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'temperature' => 0.1
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_keys['openai']
        ];

        $response = $this->http_post($url, $data, $headers);

        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return ['error' => json_encode($response)];
    }

    // --- Vector DB Logic ---

    public function index_knowledge_base() {
        if (empty($this->api_keys['gemini'])) return false;

        $schema = $this->get_full_schema_array();
        $vectors = [];

        foreach ($schema as $table => $desc) {
            $text = "Table $table: $desc";
            $vec = $this->get_embedding_with_fallback($text);
            if ($vec) {
                $vectors[] = [
                    'id' => $table,
                    'content' => $text,
                    'vector' => $vec
                ];
            }
        }
        
        file_put_contents($this->vector_store_path, json_encode($vectors));
        return true;
    }

    private function get_embedding_with_fallback($text) {
        // Try Gemini Embedding first (fast, free tier)
        if (!empty($this->api_keys['gemini'])) {
            $vec = $this->get_gemini_embedding($text);
            if ($vec) return $vec;
        }
        
        // Could add OpenRouter embedding check here if needed
        return null;
    }

    private function get_gemini_embedding($text) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent?key=" . $this->api_keys['gemini'];
        $data = [
            'model' => 'models/embedding-001',
            'content' => [
                'parts' => [['text' => $text]]
            ]
        ];
        
        $result = $this->http_post($url, $data, ['Content-Type: application/json']);
        
        if (isset($result['embedding']['values'])) {
            return $result['embedding']['values'];
        }
        return null;
    }

    private function cosine_similarity($vec_a, $vec_b) {
        $dot_product = 0;
        $norm_a = 0;
        $norm_b = 0;
        $count = count($vec_a);
        
        // Handle dimension mismatch gracefully
        if ($count !== count($vec_b)) return 0;

        for ($i = 0; $i < $count; $i++) {
            $dot_product += $vec_a[$i] * $vec_b[$i];
            $norm_a += $vec_a[$i] * $vec_a[$i];
            $norm_b += $vec_b[$i] * $vec_b[$i];
        }
        
        if ($norm_a == 0 || $norm_b == 0) return 0;
        
        return $dot_product / (sqrt($norm_a) * sqrt($norm_b));
    }

    private function load_vector_store() {
        if (!file_exists($this->vector_store_path)) {
            $this->index_knowledge_base();
        }
        if (file_exists($this->vector_store_path)) {
            return json_decode(file_get_contents($this->vector_store_path), true);
        }
        return [];
    }

    // --- Helpers ---

    private function http_post($url, $data, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Disable SSL verification for development environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'CURL Error: ' . $error_msg];
        }

        curl_close($ch);
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            return ['error' => 'Invalid JSON', 'raw' => substr($response, 0, 200) . '...']; // Log start of raw output for debug
        }
        
        return $decoded;
    }
    
    // --- Schema Knowledge ---
    
    private function get_full_schema_text() {
        $arr = $this->get_full_schema_array();
        $txt = "";
        foreach ($arr as $t => $d) {
            $txt .= "Table $t: $d\n";
        }
        return $txt;
    }
    
    private function get_full_schema_array() {
        // Dynamic Schema Discovery
        $tables = $this->CI->db->list_tables();
        $schema = [];
        
        // Load permission helper
        $this->CI->load->helper('permission');
        
        // Get user's accessible modules
        $accessible_modules = [];
        if (function_exists('get_accessible_modules')) {
            $accessible_modules = get_accessible_modules();
        }
        
        // Map database tables to modules (for permission checking)
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
            'assets' => 'assets_mgmt'
        ];
        
        // Whitelist important tables to keep context focused
        $whitelist = [
            'users', 'employees', 'attendance', 'leave_requests', 'leave_types', 'leave_approvals', 'leave_balances',
            'projects', 'tasks', 'departments', 'designations', 
            'clients', 'roles', 'settings',
            'activity_log', 'notifications', 'holidays', 'api_integrations',
            'expenses', 'payroll', 'chats', 'announcements', 'reminders', 'assets', 'requirements', 'timesheets'
        ];
        
        foreach ($tables as $table) {
            if (in_array($table, $whitelist)) {
                // Check if user has permission to access this table's module
                $module = isset($table_to_module_map[$table]) ? $table_to_module_map[$table] : null;
                
                // Allow access if:
                // 1. Module is not mapped (system tables like 'settings', 'roles' - admin only)
                // 2. User has access to the module
                // 3. User is admin (role_id = 1) - always allow
                $role_id = (int)$this->CI->session->userdata('role_id');
                $has_access = false;
                
                if ($module === null) {
                    // System tables - only allow for admin
                    $has_access = ($role_id === 1);
                } else {
                    // Check module access
                    if (function_exists('has_module_access')) {
                        $has_access = has_module_access($module);
                    } else {
                        // Fallback: check accessible modules array
                        $has_access = in_array(strtolower($module), array_map('strtolower', $accessible_modules));
                    }
                    // Admin always has access
                    if (!$has_access && $role_id === 1) {
                        $has_access = true;
                    }
                }
                
                if ($has_access) {
                    $fields = $this->CI->db->list_fields($table);
                    if (!empty($fields)) {
                        $schema[$table] = implode(', ', $fields);
                    }
                }
            }
        }
        
        return $schema;
    }
}
