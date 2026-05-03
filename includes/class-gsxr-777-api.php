<?php
/**
 * API Integration class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_API {

    public function __construct() {
        // Constructor
    }

    public function chat_completion($messages, $context = array(), $runtime_config = array()) {
        $api_base_url = isset($runtime_config['api_base_url']) ? $runtime_config['api_base_url'] : get_option('gsxr_777_api_base_url');
        $api_key = isset($runtime_config['api_key']) ? $runtime_config['api_key'] : $this->decrypt_api_key(get_option('gsxr_777_api_key'));
        $api_model = isset($runtime_config['api_model']) ? $runtime_config['api_model'] : get_option('gsxr_777_api_model');
        $temperature = isset($runtime_config['temperature']) ? $runtime_config['temperature'] : get_option('gsxr_777_api_temperature', 0.7);
        $max_tokens = isset($runtime_config['max_tokens']) ? $runtime_config['max_tokens'] : get_option('gsxr_777_api_max_tokens', 1000);
        $api_project_id = isset($runtime_config['api_project_id']) ? $runtime_config['api_project_id'] : get_option('gsxr_777_api_project_id', '');

        if (empty($api_base_url) || empty($api_key) || empty($api_model)) {
            return array(
                'success' => false,
                'error' => __('API configuration is incomplete', 'gsxr-777')
            );
        }

        // Build system prompt with context
        $system_prompt = $this->build_system_prompt($context);
        
        // Prepare messages array
        $api_messages = array();
        if (!empty($system_prompt)) {
            $api_messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }
        
        // Add conversation messages
        foreach ($messages as $message) {
            $api_messages[] = array(
                'role' => $message['role'],
                'content' => $message['content']
            );
        }

        // Prepare request payload
        $payload = array(
            'model' => $api_model,
            'messages' => $api_messages,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        );

        // Determine API format based on base URL
        if ($this->is_yandex_api($api_base_url)) {
            return $this->send_yandex_request($api_base_url, $api_key, $payload, $api_project_id);
        } elseif (strpos($api_base_url, 'anthropic.com') !== false) {
            return $this->send_anthropic_request($api_base_url, $api_key, $payload);
        } elseif (strpos($api_base_url, 'generativelanguage.googleapis.com') !== false) {
            return $this->send_gemini_request($api_base_url, $api_key, $payload);
        } else {
            // Default to OpenAI format
            return $this->send_openai_request($api_base_url, $api_key, $payload);
        }
    }

    public function encrypt_api_key($key) {
        if (empty($key)) {
            return '';
        }
        
        // Check if OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            error_log('GSXR-777: OpenSSL not available, cannot encrypt API key');
            return '';
        }
        
        $encryption_key = wp_salt('auth');
        $iv = openssl_random_pseudo_bytes(16);
        
        $encrypted = openssl_encrypt(
            $key,
            'AES-256-CBC',
            $encryption_key,
            0,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }

    public function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }
        
        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_key); // Fallback
        }
        
        $encryption_key = wp_salt('auth');
        $data = base64_decode($encrypted_key);
        
        if ($data === false) {
            return '';
        }
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $encryption_key,
            0,
            $iv
        );
    }

    public function test_connection($api_base_url = null, $api_key = null, $api_model = null, $api_project_id = null) {
        $api_base_url = $api_base_url ?: get_option('gsxr_777_api_base_url');
        $api_key = $api_key ?: $this->decrypt_api_key(get_option('gsxr_777_api_key'));
        $api_model = $api_model ?: get_option('gsxr_777_api_model');
        $api_project_id = is_null($api_project_id) ? get_option('gsxr_777_api_project_id', '') : $api_project_id;

        if (empty($api_base_url) || empty($api_key) || empty($api_model)) {
            return array(
                'success' => false,
                'error' => __('API configuration is incomplete', 'gsxr-777')
            );
        }

        // Simple test message
        $test_messages = array(
            array(
                'role' => 'user',
                'content' => 'Hello, this is a test message. Please respond with "Test successful".'
            )
        );

        $runtime_config = array(
            'api_base_url' => $api_base_url,
            'api_key' => $api_key,
            'api_model' => $api_model,
            'api_project_id' => $api_project_id
        );

        $result = $this->chat_completion($test_messages, array(), $runtime_config);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => __('Connection test successful!', 'gsxr-777')
            );
        } else {
            return array(
                'success' => false,
                'error' => $result['error']
            );
        }
    }

    public function build_system_prompt($context) {
        $knowledge = new GSXR_777_Knowledge();
        $knowledge_content = $knowledge->get_aggregated_content();
        
        $prompt = __('You are a helpful AI assistant integrated into a WordPress website.', 'gsxr-777');
        
        if (!empty($knowledge_content)) {
            $prompt .= "\n\n" . __('Knowledge Base:', 'gsxr-777') . "\n" . $knowledge_content;
        }
        
        if (!empty($context['page_url'])) {
            $prompt .= "\n\n" . __('Current page URL:', 'gsxr-777') . ' ' . $context['page_url'];
        }
        
        if (!empty($context['page_title'])) {
            $prompt .= "\n" . __('Page title:', 'gsxr-777') . ' ' . $context['page_title'];
        }
        
        if (!empty($context['page_content'])) {
            $prompt .= "\n" . __('Page content:', 'gsxr-777') . "\n" . substr($context['page_content'], 0, 2000);
        }
        
        if (!empty($context['selected_text'])) {
            $prompt .= "\n\n" . __('User selected text:', 'gsxr-777') . "\n" . $context['selected_text'];
        }
        
        return $prompt;
    }

    private function send_openai_request($api_base_url, $api_key, $payload) {
        $endpoint = rtrim($api_base_url, '/') . '/chat/completions';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 15  // Reduced timeout from 30 to 15 seconds
        ));
        
        if (is_wp_error($response)) {
            error_log('GSXR-777 API Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('API request failed', 'gsxr-777');
            error_log('GSXR-777 API Response Error: ' . $error_message);
            return array(
                'success' => false,
                'error' => $error_message
            );
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
                'tokens_used' => isset($data['usage']['total_tokens']) ? $data['usage']['total_tokens'] : 0
            );
        }
        
        error_log('GSXR-777 Invalid API response format');
        return array(
            'success' => false,
            'error' => __('Invalid API response format', 'gsxr-777')
        );
    }

    private function send_yandex_request($api_base_url, $api_key, $payload, $api_project_id = '') {
        $endpoint = $this->get_yandex_responses_endpoint($api_base_url);

        if (empty($api_project_id)) {
            return array(
                'success' => false,
                'error' => __('Yandex Project ID is required (OpenAI-Project header)', 'gsxr-777')
            );
        }

        $system_message = '';
        $input_messages = array();

        foreach ($payload['messages'] as $message) {
            if ($message['role'] === 'system') {
                $system_message = trim($system_message . "\n\n" . $message['content']);
            } else {
                $input_messages[] = array(
                    'role' => $message['role'],
                    'content' => $message['content']
                );
            }
        }

        if (empty($input_messages)) {
            $input_messages[] = array(
                'role' => 'user',
                'content' => __('Hello', 'gsxr-777')
            );
        }

        $yandex_payload = array(
            'model' => $payload['model'],
            'input' => $input_messages,
            'temperature' => $payload['temperature'],
            'max_output_tokens' => $payload['max_tokens']
        );

        if (!empty($system_message)) {
            $yandex_payload['instructions'] = $system_message;
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Api-Key ' . $api_key,
            'OpenAI-Project' => $api_project_id
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($yandex_payload),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = $this->extract_api_error_message($data, __('API request failed', 'gsxr-777'));
            if ($error_message === __('API request failed', 'gsxr-777')) {
                $response_message = wp_remote_retrieve_response_message($response);
                $error_message = !empty($response_message)
                    ? sprintf(__('API request failed (%1$d %2$s)', 'gsxr-777'), $status_code, $response_message)
                    : sprintf(__('API request failed (HTTP %d)', 'gsxr-777'), $status_code);

                if (empty($data) && !empty($body)) {
                    $error_message .= ': ' . wp_strip_all_tags(substr($body, 0, 240));
                }
            }

            return array(
                'success' => false,
                'error' => $error_message
            );
        }

        // Some models return queued/in_progress first, so poll by id until completed/failed.
        if (isset($data['status']) && in_array($data['status'], array('queued', 'in_progress'), true) && !empty($data['id'])) {
            $polled = $this->poll_yandex_response(
                $endpoint,
                $data['id'],
                $headers,
                18,
                450000
            );

            if (!$polled['success']) {
                return $polled;
            }

            $data = $polled['data'];
        }

        return $this->parse_yandex_response_payload($data);
    }

    private function send_anthropic_request($api_base_url, $api_key, $payload) {
        $endpoint = rtrim($api_base_url, '/') . '/messages';
        
        // Convert OpenAI format to Anthropic format
        $system_message = '';
        $messages = array();
        
        foreach ($payload['messages'] as $message) {
            if ($message['role'] === 'system') {
                $system_message = $message['content'];
            } else {
                $messages[] = $message;
            }
        }
        
        $anthropic_payload = array(
            'model' => $payload['model'],
            'max_tokens' => $payload['max_tokens'],
            'messages' => $messages
        );
        
        if (!empty($system_message)) {
            $anthropic_payload['system'] = $system_message;
        }
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($anthropic_payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('API request failed', 'gsxr-777');
            return array(
                'success' => false,
                'error' => $error_message
            );
        }
        
        if (isset($data['content'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['content'][0]['text'],
                'tokens_used' => isset($data['usage']['output_tokens']) ? $data['usage']['output_tokens'] : 0
            );
        }
        
        return array(
            'success' => false,
            'error' => __('Invalid API response format', 'gsxr-777')
        );
    }

    private function send_gemini_request($api_base_url, $api_key, $payload) {
        $model = $payload['model'];
        $endpoint = rtrim($api_base_url, '/') . '/models/' . $model . ':generateContent?key=' . $api_key;
        
        // Convert OpenAI format to Gemini format
        $contents = array();
        $system_instruction = '';
        
        foreach ($payload['messages'] as $message) {
            if ($message['role'] === 'system') {
                $system_instruction = $message['content'];
            } else {
                $role = $message['role'] === 'assistant' ? 'model' : 'user';
                $contents[] = array(
                    'role' => $role,
                    'parts' => array(
                        array('text' => $message['content'])
                    )
                );
            }
        }
        
        $gemini_payload = array(
            'contents' => $contents,
            'generationConfig' => array(
                'temperature' => $payload['temperature'],
                'maxOutputTokens' => $payload['max_tokens']
            )
        );
        
        if (!empty($system_instruction)) {
            $gemini_payload['systemInstruction'] = array(
                'parts' => array(
                    array('text' => $system_instruction)
                )
            );
        }
        
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($gemini_payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('API request failed', 'gsxr-777');
            return array(
                'success' => false,
                'error' => $error_message
            );
        }
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['candidates'][0]['content']['parts'][0]['text'],
                'tokens_used' => isset($data['usageMetadata']['totalTokenCount']) ? $data['usageMetadata']['totalTokenCount'] : 0
            );
        }
        
        return array(
            'success' => false,
            'error' => __('Invalid API response format', 'gsxr-777')
        );
    }

    private function is_yandex_api($api_base_url) {
        $host = wp_parse_url($api_base_url, PHP_URL_HOST);

        if (empty($host)) {
            return false;
        }

        return strpos($host, 'yandex.net') !== false;
    }

    private function get_yandex_responses_endpoint($api_base_url) {
        $base_url = rtrim($api_base_url, '/');

        if (substr($base_url, -10) === '/responses') {
            return $base_url;
        }

        if (substr($base_url, -17) === '/chat/completions') {
            return substr($base_url, 0, -17) . '/responses';
        }

        return $base_url . '/responses';
    }

    private function extract_api_error_message($data, $fallback_message) {
        if (isset($data['error']['message']) && is_string($data['error']['message']) && $data['error']['message'] !== '') {
            return $data['error']['message'];
        }

        if (isset($data['error']['code']) && is_string($data['error']['code']) && $data['error']['code'] !== '') {
            return $data['error']['code'];
        }

        if (isset($data['message']) && is_string($data['message']) && $data['message'] !== '') {
            return $data['message'];
        }

        return $fallback_message;
    }

    private function parse_yandex_response_payload($data) {
        if (!is_array($data)) {
            return array(
                'success' => false,
                'error' => __('Invalid API response format', 'gsxr-777')
            );
        }

        if (isset($data['status']) && $data['status'] === 'failed') {
            return array(
                'success' => false,
                'error' => $this->extract_api_error_message($data, __('API request failed', 'gsxr-777'))
            );
        }

        if (isset($data['status']) && $data['status'] === 'incomplete') {
            $incomplete_reason = isset($data['incomplete_details']['reason']) ? $data['incomplete_details']['reason'] : '';
            return array(
                'success' => false,
                'error' => $incomplete_reason
                    ? sprintf(__('Response incomplete: %s', 'gsxr-777'), $incomplete_reason)
                    : __('Response incomplete', 'gsxr-777')
            );
        }

        $content = '';

        if (isset($data['output_text']) && is_string($data['output_text'])) {
            $content = trim($data['output_text']);
        }

        if ($content === '' && isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $output_item) {
                if (!isset($output_item['content']) || !is_array($output_item['content'])) {
                    continue;
                }

                foreach ($output_item['content'] as $content_item) {
                    if (!is_array($content_item)) {
                        continue;
                    }

                    if (!empty($content_item['text']) && is_string($content_item['text'])) {
                        $content .= ($content === '' ? '' : "\n") . $content_item['text'];
                        continue;
                    }

                    if (!empty($content_item['refusal']) && is_string($content_item['refusal'])) {
                        $content .= ($content === '' ? '' : "\n") . $content_item['refusal'];
                    }
                }
            }
        }

        if ($content !== '') {
            return array(
                'success' => true,
                'content' => $content,
                'tokens_used' => isset($data['usage']['total_tokens']) ? intval($data['usage']['total_tokens']) : 0
            );
        }

        if (!empty($data['status']) && in_array($data['status'], array('queued', 'in_progress'), true)) {
            return array(
                'success' => false,
                'error' => __('Response is still being generated. Please try again in a few seconds.', 'gsxr-777')
            );
        }

        return array(
            'success' => false,
            'error' => __('Invalid API response format', 'gsxr-777')
        );
    }

    private function poll_yandex_response($endpoint, $response_id, $headers, $max_attempts = 15, $sleep_microseconds = 400000) {
        $base_endpoint = rtrim($endpoint, '/');
        $poll_url = $base_endpoint . '/' . rawurlencode($response_id);
        $last_data = null;

        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $poll_response = wp_remote_get($poll_url, array(
                'headers' => $headers,
                'timeout' => 20
            ));

            if (is_wp_error($poll_response)) {
                return array(
                    'success' => false,
                    'error' => $poll_response->get_error_message()
                );
            }

            $poll_body = wp_remote_retrieve_body($poll_response);
            $poll_data = json_decode($poll_body, true);
            $poll_status_code = wp_remote_retrieve_response_code($poll_response);

            if ($poll_status_code < 200 || $poll_status_code >= 300) {
                return array(
                    'success' => false,
                    'error' => $this->extract_api_error_message($poll_data, __('API request failed', 'gsxr-777'))
                );
            }

            $last_data = $poll_data;
            $status = isset($poll_data['status']) ? $poll_data['status'] : '';

            if (!in_array($status, array('queued', 'in_progress'), true)) {
                return array(
                    'success' => true,
                    'data' => $poll_data
                );
            }

            usleep($sleep_microseconds);
        }

        return array(
            'success' => false,
            'error' => __('Response generation timeout. Please try again.', 'gsxr-777'),
            'data' => $last_data
        );
    }
}
