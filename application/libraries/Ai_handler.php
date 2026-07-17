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
        $ssml = '<speak version="1.0" xml:lang="' . esc_view($language) . '">'
              . '<voice name="' . esc_view($voice) . '">'
              . esc_view($text)
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

        // SSL verification: enable in production, disable only in development
        if (ENVIRONMENT === 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

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
        // Always resolve schema the current user is allowed to see (RBAC-scoped).
        $allowed_schema = $this->get_full_schema_array();
        $allowed_schema_lc = array();
        foreach ($allowed_schema as $t => $desc) {
            $allowed_schema_lc[strtolower((string) $t)] = $desc;
        }
        $allowed_tables = array_keys($allowed_schema_lc);

        // 1. Get embedding for user query
        $query_vector = $this->get_embedding_with_fallback($user_query);
        
        if (!$query_vector) {
            return $this->get_full_schema_text();
        }

        // 2. Load Vector Store
        $store = $this->load_vector_store();
        if (!is_array($store)) {
            $store = array();
        }
        
        // 3. Find closest matches — only tables the current user may access
        $matches = [];
        foreach ($store as $item) {
            if (empty($item['vector']) || !is_array($item['vector'])) {
                continue;
            }
            if (count($query_vector) !== count($item['vector'])) {
                continue;
            }

            $table_id = isset($item['id']) ? strtolower((string) $item['id']) : '';
            if ($table_id !== '' && !empty($allowed_tables) && !in_array($table_id, $allowed_tables, true)) {
                continue;
            }
            // Prefer live, permission-filtered description over cached admin-built content
            $content = ($table_id !== '' && isset($allowed_schema_lc[$table_id]))
                ? ('Table ' . $table_id . ': ' . $allowed_schema_lc[$table_id])
                : (isset($item['content']) ? $item['content'] : '');
            if ($content === '') {
                continue;
            }

            $similarity = $this->cosine_similarity($query_vector, $item['vector']);
            
            if ($similarity > 0.4) { 
                $key = (string)$similarity;
                while(isset($matches[$key])) { $key .= '1'; }
                $matches[$key] = $content;
            }
        }
        krsort($matches);

        if (!empty($matches)) {
            reset($matches);
            $best_score = (float)key($matches);
            if ($best_score < 0.5) {
                 return $this->get_full_schema_text();
            }
            
            return implode("\n\n", array_slice($matches, 0, 5));
        }

        return $this->get_full_schema_text();
    }

    public function process_query($user_query, $context, $user_info = null, $conversation_history = []) {
        $this->CI->load->helper('ai_chat_features');
        $system_prompt = ai_chat_build_process_system_prompt($context, $user_info, $conversation_history);

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

    /**
     * Validate that a SQL query is a safe SELECT-only query.
     * Rejects INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, etc.
     *
     * @param string $sql
     * @return bool
     */
    public function is_safe_select_query($sql) {
        $sql = trim($sql);
        $normalized = preg_replace('/\s+/', ' ', $sql);
        $upper = strtoupper($normalized);

        $dangerous = [
            'INSERT ', 'UPDATE ', 'DELETE ', 'DROP ', 'ALTER ',
            'TRUNCATE ', 'CREATE ', 'REPLACE ', 'RENAME ',
            'GRANT ', 'REVOKE ', 'CALL ', 'EXEC ', 'EXECUTE ',
            'LOAD ', 'INTO OUTFILE', 'INTO DUMPFILE',
        ];

        foreach ($dangerous as $keyword) {
            if (strpos($upper, $keyword) !== false) {
                return false;
            }
        }

        if (strpos($upper, 'SELECT') !== 0) {
            return false;
        }

        return true;
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

        $this->CI->load->helper('ai_chat_features');
        $system_prompt = ai_chat_build_summarize_system_prompt($user_query, $data_str, $user_name);
        if ($user_name && strpos($system_prompt, $user_name) === false) {
            $system_prompt .= " You can address the user by name ({$user_name}) when appropriate.";
        }

        return $this->call_llm_with_fallback($system_prompt, "Summarize this data in the user's language.");
    }

    /**
     * Classify user intent into a known tool key (multilingual).
     *
     * @param string $user_query
     * @return string|null
     */
    public function classify_intent($user_query)
    {
        $this->CI->load->helper('ai_chat_features');
        $system = ai_chat_build_classify_system_prompt();
        $allowed = ai_chat_allowed_tool_keys();

        $raw = $this->call_llm_with_fallback($system, $user_query);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $raw = trim($raw);
        if (substr($raw, 0, 3) === '```') {
            $raw = preg_replace('/^```json\s*|^```\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) && preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode(str_replace(array("\r", "\n"), ' ', $m[0]), true);
        }
        if (!is_array($decoded) || empty($decoded['tool'])) {
            return null;
        }
        $tool = trim((string) $decoded['tool']);
        if ($tool === 'none' || !in_array($tool, $allowed, true)) {
            return null;
        }
        if (!ai_chat_user_can_use_tool($tool)) {
            return null;
        }
        return $tool;
    }

    /**
     * Rewrite tool HTML answer into the user's language (keeps HTML tags).
     *
     * @param string $user_query
     * @param string $html
     * @return string
     */
    public function localize_answer($user_query, $html)
    {
        $html = (string) $html;
        if ($html === '') {
            return $html;
        }
        $system = "You are a multilingual translator for an office chatbot.
Rewrite the ASSISTANT_HTML answer so it matches the language/script of USER_QUESTION
(English, Marathi, Hindi, Hinglish, Arabic, Bengali, etc.).
Keep the same facts/numbers. Keep simple HTML tags (<strong>, <ul>, <li>, <br>).
Do NOT invent data. Return ONLY the rewritten HTML, no markdown fences.";
        $user = "USER_QUESTION:\n" . $user_query . "\n\nASSISTANT_HTML:\n" . $html;
        $out = $this->call_llm_with_fallback($system, $user);
        if (!is_string($out) || trim($out) === '' || stripos($out, 'Error: All AI services') === 0) {
            return $html;
        }
        $out = trim($out);
        if (substr($out, 0, 3) === '```') {
            $out = preg_replace('/^```html?\s*|^```\s*/i', '', $out);
            $out = preg_replace('/\s*```$/', '', $out);
        }
        return trim($out) !== '' ? trim($out) : $html;
    }

    // --- Core Fallback Logic ---

    private function call_llm_with_fallback($system, $user) {
        $errors = [];

        // Priority -1: OpenAI-compatible custom providers (Settings → AI custom providers)
        $custom = $this->call_custom_providers($system, $user);
        if ($this->is_valid_response($custom)) {
            return $custom;
        }
        if (is_array($custom) && isset($custom['error'])) {
            $errors[] = 'Custom: ' . (is_string($custom['error']) ? $custom['error'] : json_encode($custom['error']));
        }

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
        if (!is_string($res) || empty($res)) return false;
        $decoded = json_decode($res, true);
        if (is_array($decoded) && isset($decoded['error'])) return false;
        return true;
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

        if (ENVIRONMENT === 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        
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

    /**
     * Call enabled custom providers (OpenAI-compatible chat completions API).
     * Expected JSON fields per provider: name, enabled, key, base_url, model.
     */
    private function call_custom_providers($system, $user)
    {
        $custom_providers_json = $this->CI->settings->get_setting('ai_custom_providers');
        if (empty($custom_providers_json)) {
            return null;
        }
        $custom_providers = json_decode($custom_providers_json, true);
        if (!is_array($custom_providers)) {
            return null;
        }
        foreach ($custom_providers as $provider) {
            if (!isset($provider['enabled']) || (string) $provider['enabled'] !== '1') {
                continue;
            }
            $key = isset($provider['key']) ? trim((string) $provider['key']) : '';
            $base = isset($provider['base_url']) ? rtrim(trim((string) $provider['base_url']), '/') : '';
            $model = isset($provider['model']) ? trim((string) $provider['model']) : '';
            if ($key === '' || $base === '' || $model === '') {
                continue;
            }
            $url = (stripos($base, '/chat/completions') !== false) ? $base : ($base . '/chat/completions');
            $data = array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system),
                    array('role' => 'user', 'content' => $user),
                ),
                'temperature' => 0.1,
                'max_tokens' => 2048,
            );
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
            );
            $response = $this->http_post($url, $data, $headers);
            if (isset($response['choices'][0]['message']['content'])) {
                return $response['choices'][0]['message']['content'];
            }
        }
        return array('error' => 'No custom provider succeeded');
    }

    private function call_openrouter($system, $user) {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $data = [
            'model' => 'anthropic/claude-3.5-sonnet',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'max_tokens' => 2048,
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
        $model = $this->CI->settings->get_setting('ai_openai_model', 'gpt-4o-mini');
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'temperature' => 0.1,
            'max_tokens' => 2048
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

    public function index_knowledge_base($force = false) {
        if (empty($this->api_keys['gemini'])) {
            return false;
        }
        if (!$force && file_exists($this->vector_store_path)) {
            $mtime = @filemtime($this->vector_store_path);
            // Refresh automatically if older than 7 days
            if ($mtime && (time() - $mtime) < (7 * 86400)) {
                return true;
            }
        }

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

        if (empty($vectors)) {
            return false;
        }

        file_put_contents($this->vector_store_path, json_encode($vectors));
        return true;
    }

    /**
     * Force re-build of RAG vector store (admin action).
     *
     * @return array{ok:bool,message:string,count?:int}
     */
    public function reindex_knowledge_base()
    {
        if (empty($this->api_keys['gemini'])) {
            return array('ok' => false, 'message' => 'Gemini API key required for embeddings.');
        }
        if (file_exists($this->vector_store_path)) {
            @unlink($this->vector_store_path);
        }
        $ok = $this->index_knowledge_base(true);
        $count = 0;
        if ($ok && file_exists($this->vector_store_path)) {
            $decoded = json_decode((string) file_get_contents($this->vector_store_path), true);
            $count = is_array($decoded) ? count($decoded) : 0;
        }
        return $ok
            ? array('ok' => true, 'message' => 'Knowledge base reindexed.', 'count' => $count)
            : array('ok' => false, 'message' => 'Reindex failed (no vectors written).');
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
            $this->index_knowledge_base(true);
        } else {
            $this->index_knowledge_base(false); // refresh if stale (>7 days)
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
        
        // SSL verification: enable in production, disable only in development
        if (ENVIRONMENT === 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

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
    
    private function get_table_descriptions() {
        $this->CI->load->helper('ai_chat_features');
        return ai_chat_table_descriptions();
    }

    private function get_full_schema_array() {
        // Dynamic Schema Discovery
        $tables = $this->CI->db->list_tables();
        $schema = [];
        
        $descriptions = $this->get_table_descriptions();
        
        // Load permission helper
        $this->CI->load->helper(array('permission', 'ai_chat_features'));
        
        // Get user's accessible modules
        $accessible_modules = [];
        if (function_exists('get_accessible_modules')) {
            $accessible_modules = get_accessible_modules();
        }
        
        $table_to_module_map = ai_chat_table_module_map();
        $whitelist = ai_chat_schema_whitelist();
        
        // Never describe secret-bearing tables to the LLM (matches runtime deny-list).
        $this->CI->load->helper('ai_sql_guard');
        $denied_tables = function_exists('ai_sql_denied_tables') ? ai_sql_denied_tables() : array();
        $denied_col_pattern = function_exists('ai_sql_denied_column_pattern') ? ai_sql_denied_column_pattern() : '';

        foreach ($tables as $table) {
            if (in_array($table, $denied_tables, true)) {
                continue;
            }
            if (in_array($table, $whitelist)) {
                // Check if user has permission to access this table's module
                $module = isset($table_to_module_map[$table]) ? $table_to_module_map[$table] : null;
                
                // Allow access if:
                // 1. Module is not mapped (system tables like 'settings', 'roles' - admin only)
                // 2. User has access to the module
                // 3. User is admin (role_id = 1) - always allow
                $role_id = (int)$this->CI->session->userdata('role_id');
                $has_access = false;
                
                // Admin always has access to everything in the whitelist
                if ($role_id === 1) {
                    $has_access = true;
                } elseif ($module === null) {
                    // System tables - only allow for admin
                    $has_access = false;
                } else {
                    // Check module access
                    if (function_exists('has_module_access')) {
                        $has_access = has_module_access($module);
                    } else {
                        // Fallback: check accessible modules array
                        $has_access = in_array(strtolower($module), array_map('strtolower', $accessible_modules));
                    }
                }
                
                if ($has_access) {
                    $fields = $this->CI->db->list_fields($table);
                    if ($denied_col_pattern !== '' && !empty($fields)) {
                        $fields = array_values(array_filter($fields, function ($f) use ($denied_col_pattern) {
                            return !preg_match($denied_col_pattern, (string) $f);
                        }));
                    }
                    if (!empty($fields)) {
                        $desc = isset($descriptions[$table]) ? " (" . $descriptions[$table] . ")" : "";
                        $schema[$table] = $desc . " Columns: " . implode(', ', $fields);
                    }
                }
            }
        }
        
        return $schema;
    }
}
