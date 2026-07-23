<?php
/**
 * Frontend Widget Handler class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Widget {

    public function __construct() {
        // Constructor
    }

    public function register_rest_routes() {
        register_rest_route('gsxr-777/v1', '/session', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_create_session'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('gsxr-777/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_message'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));

        register_rest_route('gsxr-777/v1', '/history/(?P<session_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_history'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));

        register_rest_route('gsxr-777/v1', '/history/(?P<session_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'handle_delete_history'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));

        // Dynamic strings endpoint for Polylang and other multi-language plugins
        register_rest_route('gsxr-777/v1', '/strings/', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_strings'),
            'permission_callback' => '__return_true'
        ));

        // Dynamic config endpoint - returns full config with current language
        register_rest_route('gsxr-777/v1', '/config/', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_config'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_create_session() {
        $credentials = $this->create_session_credentials();
        $response = rest_ensure_response(array(
            'success' => true,
            'session_id' => $credentials['session_id'],
            'session_token' => $credentials['session_token']
        ));
        $response->header('Cache-Control', 'no-store, private');

        return $response;
    }

    public function check_session_permissions($request) {
        $session_id = sanitize_text_field((string) $request->get_param('session_id'));
        $session_token = sanitize_text_field((string) $request->get_header('X-GSXR-Session-Token'));

        if (!$this->verify_session_credentials($session_id, $session_token)) {
            return new WP_Error(
                'invalid_session_credentials',
                __('Invalid chat session credentials', 'gsxr-777-ai-open-chat'),
                array('status' => 401)
            );
        }

        return true;
    }

    public function create_session_credentials() {
        $session_id = 'gsxr777_' . str_replace('-', '', wp_generate_uuid4());

        return array(
            'session_id' => $session_id,
            'session_token' => $this->create_session_token($session_id)
        );
    }

    public function verify_session_credentials($session_id, $session_token) {
        $security = new GSXR_777_Security();
        if (!$security->is_valid_session_id($session_id) || !is_string($session_token) || $session_token === '') {
            return false;
        }

        return hash_equals($this->create_session_token($session_id), $session_token);
    }

    public function handle_chat_message($request) {
        $message = $request->get_param('message');
        $session_id = sanitize_text_field((string) $request->get_param('session_id'));
        $context = $request->get_param('context');
        
        // Get user IP
        $ip_address = $this->get_client_ip();
        
        // Security validation
        $security = new GSXR_777_Security();
        $validation = $security->validate_request($message, $session_id, $ip_address);
        
        if (!$validation['valid']) {
            return new WP_Error(
                'security_error',
                $validation['error'],
                array('status' => isset($validation['status']) ? intval($validation['status']) : 400)
            );
        }
        
        $sanitized_message = $validation['sanitized_message'];

        // Read history before storing the current message so it is sent once.
        $history_limit = intval(get_option('gsxr_777_api_history_limit', 20));
        $history = $this->get_conversation_history($session_id, $history_limit);
        $history[] = array(
            'role' => 'user',
            'content' => $sanitized_message
        );

        // Store only the metadata explicitly enabled by the administrator.
        $stats = new GSXR_777_Stats();
        $store_metadata = (bool) get_option('gsxr_777_store_request_metadata', false);
        $raw_user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $user_agent = $store_metadata ? substr($raw_user_agent, 0, 255) : '';
        $stored_ip = $store_metadata && function_exists('wp_privacy_anonymize_ip')
            ? wp_privacy_anonymize_ip($ip_address)
            : '';
        $stats->track_session($session_id, $stored_ip, $user_agent);

        $chat_context = $this->sanitize_context($context);
        
        // Get AI response
        $api = new GSXR_777_API();
        $response = $api->chat_completion($history, $chat_context);
        
        if (!$response['success']) {
            error_log('GSXR-777 API request failed: ' . sanitize_text_field($response['error']));
            return new WP_Error(
                'api_error',
                __('The AI service is temporarily unavailable. Please try again.', 'gsxr-777-ai-open-chat'),
                array('status' => 502)
            );
        }
        
        // Store a complete user/assistant pair only after a successful response.
        $tokens_used = isset($response['tokens_used']) ? $response['tokens_used'] : 0;
        $stats->track_message($session_id, 'user', $sanitized_message);
        $stats->track_message($session_id, 'assistant', $response['content'], $tokens_used);
        
        $rest_response = rest_ensure_response(array(
            'success' => true,
            'message' => $response['content'],
            'tokens_used' => $tokens_used
        ));
        $rest_response->header('Cache-Control', 'no-store, private');

        return $rest_response;
    }

    public function handle_get_history($request) {
        $session_id = sanitize_text_field($request->get_param('session_id'));
        
        if (empty($session_id)) {
            return new WP_Error('invalid_session', __('Invalid session ID', 'gsxr-777-ai-open-chat'), array('status' => 400));
        }
        
        $history = $this->get_conversation_history($session_id);
        
        $response = rest_ensure_response(array(
            'success' => true,
            'history' => $history
        ));
        $response->header('Cache-Control', 'no-store, private');

        return $response;
    }

    public function handle_delete_history($request) {
        $session_id = sanitize_text_field((string) $request->get_param('session_id'));
        $stats = new GSXR_777_Stats();
        $stats->delete_session($session_id);

        $response = rest_ensure_response(array(
            'success' => true,
            'message' => __('Conversation history cleared', 'gsxr-777-ai-open-chat')
        ));
        $response->header('Cache-Control', 'no-store, private');

        return $response;
    }

    public function handle_get_strings() {
        // Switch to current language for translations
        $this->switch_to_current_language();
        
        // Return localized strings based on current language
        // This allows Polylang and other plugins to switch languages dynamically
        $response = rest_ensure_response(array(
            'send' => __('Send', 'gsxr-777-ai-open-chat'),
            'typing' => __('AI is typing...', 'gsxr-777-ai-open-chat'),
            'error' => __('Sorry, something went wrong. Please try again.', 'gsxr-777-ai-open-chat'),
            'retry' => __('Retry', 'gsxr-777-ai-open-chat'),
            'close' => __('Close', 'gsxr-777-ai-open-chat'),
            'minimize' => __('Minimize', 'gsxr-777-ai-open-chat'),
            'maximize' => __('Maximize', 'gsxr-777-ai-open-chat'),
            'open' => __('Open chat', 'gsxr-777-ai-open-chat'),
            'clear' => __('Clear conversation', 'gsxr-777-ai-open-chat'),
            'cleared' => __('Conversation history cleared', 'gsxr-777-ai-open-chat'),
            'sessionError' => __('Could not start a secure chat session', 'gsxr-777-ai-open-chat'),
            'language' => $this->get_current_language()
        ));
        $response->header('Cache-Control', 'no-store, private');

        return $response;
    }

    public function handle_get_config() {
        // Switch to current language for translations
        $this->switch_to_current_language();
        
        // Return full config based on current language
        // This endpoint should be called from JavaScript on page load
        // to get the correct language configuration
        $response = rest_ensure_response($this->get_widget_config());
        $response->header('Cache-Control', 'no-store, private');

        return $response;
    }

    public function enqueue_widget_scripts() {
        // Only load on frontend
        if (is_admin()) {
            return;
        }
        
        // Switch to current language for translations
        $this->switch_to_current_language();
        
        wp_enqueue_script(
            'gsxr-777-widget',
            GSXR_777_PLUGIN_URL . 'public/js/widget.js',
            array(),
            GSXR_777_VERSION,
            true
        );
        
        wp_enqueue_style(
            'gsxr-777-widget',
            GSXR_777_PLUGIN_URL . 'public/css/widget.css',
            array(),
            GSXR_777_VERSION
        );
        
        // Localize script with settings
        wp_localize_script('gsxr-777-widget', 'gsxr777Config', $this->get_widget_config());
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => get_option('gsxr_777_widget_title', __('Chat', 'gsxr-777-ai-open-chat')),
            'color' => get_option('gsxr_777_widget_primary_color', '#2563eb'),
            'width' => get_option('gsxr_777_widget_width', 400),
            'height' => get_option('gsxr_777_widget_height', 600)
        ), $atts, 'gsxr_777_chat');

        $color = sanitize_hex_color($atts['color']);
        $shortcode_config = array(
            'title' => sanitize_text_field($atts['title']),
            'primaryColor' => $color ? $color : '#2563eb',
            'width' => max(300, min(800, intval($atts['width']))),
            'height' => max(400, min(900, intval($atts['height'])))
        );

        return sprintf(
            '<div class="gsxr777-chat-shortcode" data-gsxr777-config="%s"></div>',
            esc_attr(wp_json_encode($shortcode_config))
        );
    }

    private function get_widget_config() {
        // Switch to current language FIRST before any translations
        $this->switch_to_current_language();
        
        // Get current language code for URLs
        $current_locale = $this->get_current_language();
        $current_lang = substr($current_locale, 0, 2); // 'en_US' -> 'en'
        
        // Get saved values
        $saved_title = trim(get_option('gsxr_777_widget_title', ''));
        $saved_welcome = trim(get_option('gsxr_777_widget_welcome', ''));
        $saved_placeholder = trim(get_option('gsxr_777_widget_placeholder', ''));
        
        // Default translated values (will use current language after switch_to_current_language)
        $default_title = __('Chat', 'gsxr-777-ai-open-chat');
        $default_welcome = __('Hello! How can I help you?', 'gsxr-777-ai-open-chat');
        $default_placeholder = __('Type your message...', 'gsxr-777-ai-open-chat');
        
        // Helper to translate string
        $translate = function($string) use ($current_lang) {
            if (empty($string)) return $string;

            // A saved Cyrillic value is already localized. Do not let a stale
            // Polylang string entry replace the site's Russian text with an
            // older English translation.
            if ($current_lang === 'ru' && preg_match('/\p{Cyrillic}/u', $string)) {
                return $string;
            }

            // Keep the existing Russian settings intact while providing the
            // built-in English equivalents when Polylang has no translation
            // entry for these standard widget messages.
            if ($current_lang === 'en') {
                $built_in_translations = array(
                    'Чат-Бот собственной разработки' => 'A self-developed chatbot',
                    "Привет! \nЧто Вас интересует? \nЧем смогу - помогу! \nВсё не знаю, пока учусь :-)" => "Hi! What are you interested in? I'll help as much as I can. I'm still learning :-)\n",
                    'Напишите Ваше сообщение...' => 'Write your message...'
                );
                if (isset($built_in_translations[$string])) {
                    return $built_in_translations[$string];
                }
                $normalized = preg_replace('/\r\n?/', "\n", $string);
                if (isset($built_in_translations[$normalized])) {
                    return $built_in_translations[$normalized];
                }
            }
            
            if (function_exists('pll_translate_string') && !empty($current_lang)) {
                return pll_translate_string($string, $current_lang);
            }
            
            if (function_exists('pll__')) {
                return pll__($string);
            }
            
            return $string;
        };
        
        // For saved values, try to translate them
        if (empty($saved_title)) {
            $title = $default_title;
        } else {
            $translated = $translate($saved_title);
            $title = ($translated !== $saved_title && $translated !== '') ? $translated : $saved_title;
        }
        
        if (empty($saved_welcome)) {
            $welcome = $default_welcome;
        } else {
            $translated = $translate($saved_welcome);
            $welcome = ($translated !== $saved_welcome && $translated !== '') ? $translated : $saved_welcome;
        }
        
        if (empty($saved_placeholder)) {
            $placeholder = $default_placeholder;
        } else {
            $translated = $translate($saved_placeholder);
            $placeholder = ($translated !== $saved_placeholder && $translated !== '') ? $translated : $saved_placeholder;
        }
        
        // Append language to REST URLs if available
        $lang_param = $current_lang ? '?lang=' . $current_lang : '';

        $sanitize_color = function($value, $default) {
            $sanitized = sanitize_hex_color($value);
            return $sanitized ? $sanitized : $default;
        };

        $sanitize_font = function($font, $default) {
            $font = sanitize_text_field($font);
            $font = preg_replace('/[^a-zA-Z0-9,\s"\'\-]/', '', $font);
            return $font !== '' ? $font : $default;
        };

        $primary_color = $sanitize_color(get_option('gsxr_777_widget_primary_color', '#2563eb'), '#2563eb');
        $secondary_color = $sanitize_color(get_option('gsxr_777_widget_secondary_color', '#1d4ed8'), '#1d4ed8');
        $gradient_angle = intval(get_option('gsxr_777_widget_gradient_angle', 135));
        $gradient_angle = max(0, min(360, $gradient_angle));

        $window_background = $sanitize_color(get_option('gsxr_777_widget_chat_background_color', '#ffffff'), '#ffffff');
        $messages_background = $sanitize_color(get_option('gsxr_777_widget_messages_background_color', '#ffffff'), '#ffffff');
        $assistant_background = $sanitize_color(get_option('gsxr_777_widget_assistant_background_color', '#f0f0f0'), '#f0f0f0');
        $assistant_text = $sanitize_color(get_option('gsxr_777_widget_assistant_text_color', '#333333'), '#333333');
        $user_text = $sanitize_color(get_option('gsxr_777_widget_user_text_color', '#ffffff'), '#ffffff');
        $input_background = $sanitize_color(get_option('gsxr_777_widget_input_background_color', '#ffffff'), '#ffffff');
        $input_text = $sanitize_color(get_option('gsxr_777_widget_input_text_color', '#333333'), '#333333');
        $widget_font = $sanitize_font(get_option('gsxr_777_widget_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'), '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif');
        $chat_font = $sanitize_font(get_option('gsxr_777_widget_chat_font_family', 'inherit'), 'inherit');
        
        return array(
            'sessionUrl' => rest_url('gsxr-777/v1/session'),
            'apiUrl' => rest_url('gsxr-777/v1/chat') . $lang_param,
            'historyUrl' => rest_url('gsxr-777/v1/history/'),
            'stringsUrl' => rest_url('gsxr-777/v1/strings/') . $lang_param,
            'configUrl' => rest_url('gsxr-777/v1/config/') . $lang_param,
            'nonce' => wp_create_nonce('wp_rest'),
            'title' => $title,
            'welcome' => $welcome,
            'placeholder' => $placeholder,
            'position' => get_option('gsxr_777_widget_position', 'bottom-right'),
            'primaryColor' => $primary_color,
            'width' => intval(get_option('gsxr_777_widget_width', 400)),
            'height' => intval(get_option('gsxr_777_widget_height', 600)),
            'requestTimeout' => min(
                300000,
                max(30000, intval(apply_filters('gsxr_777_browser_request_timeout', 130000)))
            ),
            'includePageContext' => (bool) get_option('gsxr_777_include_page_context', true),
            'theme' => array(
                'mode' => $this->sanitize_theme_mode(get_option('gsxr_777_widget_theme_mode', 'auto')),
                'primaryColor' => $primary_color,
                'secondaryColor' => $secondary_color,
                'gradientAngle' => $gradient_angle,
                'windowBackground' => $window_background,
                'messagesBackground' => $messages_background,
                'assistantBackground' => $assistant_background,
                'assistantTextColor' => $assistant_text,
                'userTextColor' => $user_text,
                'inputBackground' => $input_background,
                'inputTextColor' => $input_text,
                'widgetFontFamily' => $widget_font,
                'chatFontFamily' => $chat_font
            ),
            'language' => $current_locale,
            'strings' => array(
                'send' => __('Send', 'gsxr-777-ai-open-chat'),
                'typing' => __('AI is typing...', 'gsxr-777-ai-open-chat'),
                'error' => __('Sorry, something went wrong. Please try again.', 'gsxr-777-ai-open-chat'),
                'retry' => __('Retry', 'gsxr-777-ai-open-chat'),
                'close' => __('Close', 'gsxr-777-ai-open-chat'),
                'minimize' => __('Minimize', 'gsxr-777-ai-open-chat'),
                'maximize' => __('Maximize', 'gsxr-777-ai-open-chat'),
                'open' => __('Open chat', 'gsxr-777-ai-open-chat'),
                'clear' => __('Clear conversation', 'gsxr-777-ai-open-chat'),
                'cleared' => __('Conversation history cleared', 'gsxr-777-ai-open-chat'),
                'sessionError' => __('Could not start a secure chat session', 'gsxr-777-ai-open-chat')
            )
        );
    }
    

    private function get_conversation_history($session_id, $limit = 20) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        
        // Validate limit
        $max_limit = 100;
        $limit = min(intval($limit), $max_limit);
        $limit = max(1, $limit);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content FROM {$table_name} 
             WHERE session_id = %s 
             ORDER BY created_at DESC, id DESC
             LIMIT %d",
            $session_id,
            $limit
        ));
        
        // Handle null result
        if (is_null($results)) {
            return array();
        }

        $max_characters = min(
            100000,
            max(5000, intval(apply_filters('gsxr_777_history_max_characters', 30000)))
        );
        $history = array();
        $used_characters = 0;
        foreach ($results as $row) {
            $remaining = $max_characters - $used_characters;
            if ($remaining <= 0) {
                break;
            }

            $content = $this->truncate_text($row->content, $remaining);
            $history[] = array(
                'role' => $row->role,
                'content' => $content
            );
            $used_characters += $this->text_length($content);
        }
        
        return array_reverse($history);
    }

    /**
     * Get current language with Polylang support
     * 
     * @return string Current locale (e.g., 'en_US', 'ru_RU')
     */
    private function get_current_language() {
        // Check if Polylang is active
        if (function_exists('pll_current_language')) {
            // Try to get locale directly (most reliable method)
            $locale = pll_current_language('locale');
            if ($locale && !empty($locale)) {
                return $locale;
            }
            
            // Fallback to language code if locale not available
            $current_lang = pll_current_language();
            if ($current_lang) {
                // Convert language code to locale format
                $locale_map = array(
                    'en' => 'en_US',
                    'ru' => 'ru_RU',
                    'es' => 'es_ES',
                    'fr' => 'fr_FR',
                    'de' => 'de_DE',
                    'it' => 'it_IT',
                    'pt' => 'pt_PT',
                    'pl' => 'pl_PL',
                    'uk' => 'uk_UA'
                );
                if (isset($locale_map[$current_lang])) {
                    return $locale_map[$current_lang];
                }
                // If not in map, return as-is (might be a custom locale)
                return $current_lang;
            }
            
            // In REST API context, try to get language from request
            if (defined('REST_REQUEST') && REST_REQUEST) {
                // Check GET parameter first (most reliable for REST)
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only locale selector.
                if (isset($_GET['lang'])) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only locale selector.
                    $lang_code = sanitize_text_field(wp_unslash($_GET['lang']));
                     $locale_map = array(
                        'en' => 'en_US',
                        'ru' => 'ru_RU',
                        'es' => 'es_ES',
                        'fr' => 'fr_FR',
                        'de' => 'de_DE',
                        'it' => 'it_IT',
                        'pt' => 'pt_PT',
                        'pl' => 'pl_PL',
                        'uk' => 'uk_UA'
                    );
                    if (isset($locale_map[$lang_code])) {
                        return $locale_map[$lang_code];
                    }
                }

                // Check referer URL for language
                $referer = isset($_SERVER['HTTP_REFERER'])
                    ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']))
                    : '';
                if ($referer) {
                    // Extract language from URL (Polylang adds /en/ or /ru/ to URLs)
                    if (preg_match('#/(en|ru|es|fr|de|it|pt|pl|uk)/#', $referer, $matches)) {
                        $lang_code = $matches[1];
                        $locale_map = array(
                            'en' => 'en_US',
                            'ru' => 'ru_RU',
                            'es' => 'es_ES',
                            'fr' => 'fr_FR',
                            'de' => 'de_DE',
                            'it' => 'it_IT',
                            'pt' => 'pt_PT',
                            'pl' => 'pl_PL',
                            'uk' => 'uk_UA'
                        );
                        if (isset($locale_map[$lang_code])) {
                            return $locale_map[$lang_code];
                        }
                    }
                }
                
                // Check Polylang cookie
                if (isset($_COOKIE['pll_language'])) {
                    $cookie_lang = sanitize_text_field(wp_unslash($_COOKIE['pll_language']));
                    if ($cookie_lang) {
                        $locale_map = array(
                            'en' => 'en_US',
                            'ru' => 'ru_RU',
                            'es' => 'es_ES',
                            'fr' => 'fr_FR',
                            'de' => 'de_DE',
                            'it' => 'it_IT',
                            'pt' => 'pt_PT',
                            'pl' => 'pl_PL',
                            'uk' => 'uk_UA'
                        );
                        if (isset($locale_map[$cookie_lang])) {
                            return $locale_map[$cookie_lang];
                        }
                    }
                }
            }
        }
        
        // Fallback to WordPress locale
        return get_locale();
    }
    
    /**
     * Switch to current language for translations
     * This ensures that __() functions return correct translations
     */
    private function switch_to_current_language() {
        $locale = $this->get_current_language();
        if ($locale && $locale !== get_locale() && function_exists('switch_to_locale')) {
            switch_to_locale($locale);
        }
    }

    private function get_client_ip() {
        $remote_address = sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
        $ip = filter_var($remote_address, FILTER_VALIDATE_IP) ? $remote_address : '0.0.0.0';
        $trusted_proxies = (array) apply_filters('gsxr_777_trusted_proxy_ips', array());

        if ($this->is_trusted_proxy($ip, $trusted_proxies)) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $candidate = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }

            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwarded_header = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
                return $this->resolve_forwarded_client_ip($forwarded_header, $ip, $trusted_proxies);
            }
        }

        return $ip;
    }

    private function create_session_token($session_id) {
        $signature = hash_hmac('sha256', $session_id, wp_salt('secure_auth'), true);
        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function sanitize_context($context) {
        if (!is_array($context) || !(bool) get_option('gsxr_777_include_page_context', true)) {
            return array();
        }

        $page_url = isset($context['url']) ? esc_url_raw($context['url']) : '';
        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $page_host = strtolower((string) wp_parse_url($page_url, PHP_URL_HOST));
        if ($page_host === '' || $page_host !== $home_host) {
            $page_url = '';
        }

        return array(
            'page_url' => $page_url,
            'page_title' => $this->truncate_text(
                isset($context['title']) ? sanitize_text_field($context['title']) : '',
                300
            ),
            'page_content' => $this->truncate_text(
                isset($context['content']) ? wp_strip_all_tags($context['content']) : '',
                3000
            ),
            'selected_text' => $this->truncate_text(
                isset($context['selectedText']) ? sanitize_textarea_field($context['selectedText']) : '',
                1000
            )
        );
    }

    private function truncate_text($text, $max_length) {
        $text = wp_check_invalid_utf8((string) $text, true);
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max_length, 'UTF-8');
        }
        if (function_exists('iconv_substr')) {
            $substring = iconv_substr($text, 0, $max_length, 'UTF-8');
            if ($substring !== false) {
                return $substring;
            }
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($characters)
            ? implode('', array_slice($characters, 0, $max_length))
            : substr($text, 0, $max_length);
    }

    private function text_length($text) {
        if (function_exists('mb_strlen')) {
            return mb_strlen((string) $text, 'UTF-8');
        }
        if (function_exists('iconv_strlen')) {
            $length = iconv_strlen((string) $text, 'UTF-8');
            if ($length !== false) {
                return $length;
            }
        }

        $matched = preg_match_all('/./us', (string) $text, $characters);
        return $matched === false ? strlen((string) $text) : $matched;
    }

    private function sanitize_theme_mode($mode) {
        $mode = sanitize_key($mode);
        return in_array($mode, array('auto', 'light', 'dark'), true) ? $mode : 'auto';
    }

    private function is_trusted_proxy($ip, $trusted_proxies) {
        foreach ($trusted_proxies as $trusted_proxy) {
            $trusted_proxy = trim((string) $trusted_proxy);
            if ($trusted_proxy === $ip) {
                return true;
            }

            if (strpos($trusted_proxy, '/') !== false && $this->ip_matches_cidr($ip, $trusted_proxy)) {
                return true;
            }
        }

        return false;
    }

    private function resolve_forwarded_client_ip($header, $remote_address, $trusted_proxies) {
        $chain = array_values(array_filter(
            array_map('trim', explode(',', (string) $header)),
            function($candidate) {
                return filter_var($candidate, FILTER_VALIDATE_IP) !== false;
            }
        ));

        $client_ip = $remote_address;
        foreach (array_reverse($chain) as $candidate) {
            if (!$this->is_trusted_proxy($client_ip, $trusted_proxies)) {
                break;
            }
            $client_ip = $candidate;
        }

        return $client_ip;
    }

    private function ip_matches_cidr($ip, $cidr) {
        list($subnet, $bits) = array_pad(explode('/', $cidr, 2), 2, null);
        $ip_binary = @inet_pton($ip);
        $subnet_binary = @inet_pton($subnet);

        if ($ip_binary === false || $subnet_binary === false || strlen($ip_binary) !== strlen($subnet_binary)) {
            return false;
        }

        $bits = intval($bits);
        $max_bits = strlen($ip_binary) * 8;
        if ($bits < 0 || $bits > $max_bits) {
            return false;
        }

        $full_bytes = intdiv($bits, 8);
        $remaining_bits = $bits % 8;
        if ($full_bytes > 0 && substr($ip_binary, 0, $full_bytes) !== substr($subnet_binary, 0, $full_bytes)) {
            return false;
        }

        if ($remaining_bits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        return (ord($ip_binary[$full_bytes]) & $mask) === (ord($subnet_binary[$full_bytes]) & $mask);
    }
}
