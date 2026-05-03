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
        register_rest_route('gsxr-777/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_message'),
            'permission_callback' => array($this, 'check_rest_permissions')
        ));

        register_rest_route('gsxr-777/v1', '/history/(?P<session_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_history'),
            'permission_callback' => '__return_true'
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

    public function check_rest_permissions() {
        // Allow all requests - rate limiting and security validation done in handle_chat_message
        // This endpoint requires valid message content to be useful
        return true;
    }

    public function handle_chat_message($request) {
        $message = sanitize_textarea_field($request->get_param('message'));
        $session_id = sanitize_text_field($request->get_param('session_id'));
        $context = $request->get_param('context');
        
        // Get user IP
        $ip_address = $this->get_client_ip();
        
        // Security validation
        $security = new GSXR_777_Security();
        $validation = $security->validate_request($message, $session_id, $ip_address);
        
        if (!$validation['valid']) {
            return new WP_Error('security_error', $validation['error'], array('status' => 429));
        }
        
        $sanitized_message = $validation['sanitized_message'];
        
        // Track session and message
        $stats = new GSXR_777_Stats();
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $stats->track_session($session_id, $ip_address, $user_agent);
        $stats->track_message($session_id, 'user', $sanitized_message);
        
        // Get conversation history
        $history = $this->get_conversation_history($session_id);
        
        // Add current message to history
        $history[] = array(
            'role' => 'user',
            'content' => $sanitized_message
        );
        
        // Prepare context
        $chat_context = array();
        if (is_array($context)) {
            $chat_context = array(
                'page_url' => isset($context['url']) ? esc_url_raw($context['url']) : '',
                'page_title' => isset($context['title']) ? sanitize_text_field($context['title']) : '',
                'page_content' => isset($context['content']) ? wp_strip_all_tags($context['content']) : '',
                'selected_text' => isset($context['selectedText']) ? sanitize_textarea_field($context['selectedText']) : ''
            );
        }
        
        // Get AI response
        $api = new GSXR_777_API();
        $response = $api->chat_completion($history, $chat_context);
        
        if (!$response['success']) {
            return new WP_Error('api_error', $response['error'], array('status' => 500));
        }
        
        // Track AI response
        $tokens_used = isset($response['tokens_used']) ? $response['tokens_used'] : 0;
        $stats->track_message($session_id, 'assistant', $response['content'], $tokens_used);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => $response['content'],
            'tokens_used' => $tokens_used
        ));
    }

    public function handle_get_history($request) {
        $session_id = sanitize_text_field($request->get_param('session_id'));
        
        if (empty($session_id)) {
            return new WP_Error('invalid_session', __('Invalid session ID', 'gsxr-777'), array('status' => 400));
        }
        
        $history = $this->get_conversation_history($session_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'history' => $history
        ));
    }

    public function handle_get_strings() {
        // Switch to current language for translations
        $this->switch_to_current_language();
        
        // Return localized strings based on current language
        // This allows Polylang and other plugins to switch languages dynamically
        return rest_ensure_response(array(
            'send' => __('Send', 'gsxr-777'),
            'typing' => __('AI is typing...', 'gsxr-777'),
            'error' => __('Sorry, something went wrong. Please try again.', 'gsxr-777'),
            'retry' => __('Retry', 'gsxr-777'),
            'close' => __('Close', 'gsxr-777'),
            'minimize' => __('Minimize', 'gsxr-777'),
            'maximize' => __('Maximize', 'gsxr-777'),
            'language' => $this->get_current_language()
        ));
    }

    public function handle_get_config() {
        // Switch to current language for translations
        $this->switch_to_current_language();
        
        // Return full config based on current language
        // This endpoint should be called from JavaScript on page load
        // to get the correct language configuration
        return rest_ensure_response($this->get_widget_config());
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

    public function register_shortcode() {
        add_shortcode('gsxr_777_chat', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => get_option('gsxr_777_widget_title', __('Chat', 'gsxr-777')),
            'position' => get_option('gsxr_777_widget_position', 'bottom-right'),
            'color' => get_option('gsxr_777_widget_primary_color', '#2563eb'),
            'width' => get_option('gsxr_777_widget_width', 400),
            'height' => get_option('gsxr_777_widget_height', 600)
        ), $atts, 'gsxr_777_chat');
        
        // Enqueue scripts if not already done
        $this->enqueue_widget_scripts();
        
        // Override config for shortcode using wp_add_inline_script
        // This must be called after wp_enqueue_script and wp_localize_script
        $inline_script = 'if (typeof gsxr777Config !== "undefined") { ' .
            'gsxr777Config.title = ' . wp_json_encode($atts['title']) . '; ' .
            'gsxr777Config.position = ' . wp_json_encode($atts['position']) . '; ' .
            'gsxr777Config.primaryColor = ' . wp_json_encode($atts['color']) . '; ' .
            'gsxr777Config.width = ' . intval($atts['width']) . '; ' .
            'gsxr777Config.height = ' . intval($atts['height']) . '; }';
        
        wp_add_inline_script('gsxr-777-widget', $inline_script);
        
        return '<div id="gsxr-777-chat-shortcode"></div>';
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
        $default_title = __('Chat', 'gsxr-777');
        $default_welcome = __('Hello! How can I help you?', 'gsxr-777');
        $default_placeholder = __('Type your message...', 'gsxr-777');
        
        // Helper to translate string
        $translate = function($string) use ($current_lang) {
            if (empty($string)) return $string;
            
            // COMPENSATION FOR INVERTED DATA:
            // The user has English text entered in the Russian translation field and vice-versa.
            // We swap the requested language to return the content the user expects.
            $target_lang = $current_lang;
            if ($current_lang === 'en') {
                $target_lang = 'ru';
            } elseif ($current_lang === 'ru') {
                $target_lang = 'en';
            }
            
            // Try explicit translation with SWAPPED language
            if (function_exists('pll_translate_string') && !empty($target_lang)) {
                return pll_translate_string($string, $target_lang);
            }
            
            // Fallback to current context (this usually relies on implicit state, might be tricky if swapped)
            if (function_exists('pll__')) {
                // If we need to swap for pll__ we'd need to switch valid polylang language, which is hard.
                // Relying on pll_translate_string is safer. 
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
        
        return array(
            'apiUrl' => rest_url('gsxr-777/v1/chat') . $lang_param,
            'historyUrl' => rest_url('gsxr-777/v1/history/') . $lang_param,
            'stringsUrl' => rest_url('gsxr-777/v1/strings/') . $lang_param,
            'configUrl' => rest_url('gsxr-777/v1/config/') . $lang_param,
            'nonce' => wp_create_nonce('wp_rest'),
            'title' => $title,
            'welcome' => $welcome,
            'placeholder' => $placeholder,
            'position' => get_option('gsxr_777_widget_position', 'bottom-right'),
            'primaryColor' => get_option('gsxr_777_widget_primary_color', '#2563eb'),
            'width' => intval(get_option('gsxr_777_widget_width', 400)),
            'height' => intval(get_option('gsxr_777_widget_height', 600)),
            'language' => $current_locale,
            'strings' => array(
                'send' => __('Send', 'gsxr-777'),
                'typing' => __('AI is typing...', 'gsxr-777'),
                'error' => __('Sorry, something went wrong. Please try again.', 'gsxr-777'),
                'retry' => __('Retry', 'gsxr-777'),
                'close' => __('Close', 'gsxr-777'),
                'minimize' => __('Minimize', 'gsxr-777'),
                'maximize' => __('Maximize', 'gsxr-777')
            )
        );
    }
    

    private function get_conversation_history($session_id, $limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gsxr777_messages';
        
        // Validate limit
        $max_limit = 100;
        $limit = min(intval($limit), $max_limit);
        $limit = max(1, $limit);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content FROM {$table_name} 
             WHERE session_id = %s 
             ORDER BY created_at ASC 
             LIMIT %d",
            $session_id,
            $limit
        ));
        
        // Handle null result
        if (is_null($results)) {
            return array();
        }
        
        $history = array();
        foreach ($results as $row) {
            $history[] = array(
                'role' => $row->role,
                'content' => $row->content
            );
        }
        
        return $history;
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
                if (isset($_GET['lang'])) {
                    $lang_code = sanitize_text_field($_GET['lang']);
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
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
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
                    $cookie_lang = sanitize_text_field($_COOKIE['pll_language']);
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
        // Always reload textdomain first to ensure we have latest translations
        load_plugin_textdomain('gsxr-777', false, dirname(GSXR_777_PLUGIN_BASENAME) . '/languages');
        
        // Check if Polylang is active
        if (function_exists('pll_current_language')) {
            $locale = $this->get_current_language();
            $current_locale = get_locale();
            
            // If locale is different, try to load the correct translation file
            if ($locale && $locale !== $current_locale) {
                // Try to load the translation file for the current Polylang language
                $mofile = GSXR_777_PLUGIN_DIR . 'languages/gsxr-777-' . $locale . '.mo';
                if (file_exists($mofile)) {
                    // Unload current textdomain
                    unload_textdomain('gsxr-777');
                    // Load the correct translation file
                    load_textdomain('gsxr-777', $mofile);
                } else {
                    // If translation file doesn't exist, try to switch locale
                    // This works in non-REST context
                    if (function_exists('switch_to_locale') && !defined('REST_REQUEST')) {
                        switch_to_locale($locale);
                        // Reload textdomain after switching
                        load_plugin_textdomain('gsxr-777', false, dirname(GSXR_777_PLUGIN_BASENAME) . '/languages');
                    }
                }
            }
        }
    }

    private function get_client_ip() {
        // Trust only specific headers, prioritize Cloudflare and X-Forwarded-For
        $ip = null;
        
        // Cloudflare IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } 
        // Standard proxy headers
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get the first IP in the list
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = $ips[0];
        } 
        // Direct connection
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        // Sanitize and validate IP
        $ip = sanitize_text_field($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return '127.0.0.1';
        }
        
        return $ip;
    }
}