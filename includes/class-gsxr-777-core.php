<?php
/**
 * Core plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Core {
    
    private $admin;
    private $widget;
    private $api;
    private $knowledge;
    private $security;
    private $stats;

    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
    }

    public function run() {
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // Register Polylang strings after init (when Polylang is fully loaded)
        add_action('init', array($this, 'register_polylang_strings'), 20);
    }
    
    /**
     * Register strings with Polylang for translation
     */
    public function register_polylang_strings() {
        // Register widget settings strings with Polylang if available
        if (function_exists('pll_register_string')) {
            $title = trim(get_option('gsxr_777_widget_title', ''));
            $welcome = trim(get_option('gsxr_777_widget_welcome', ''));
            $placeholder = trim(get_option('gsxr_777_widget_placeholder', ''));
            
            if ($title) {
                pll_register_string('gsxr-777-widget-title', $title, 'GSXR-777 AI Chat');
            }
            if ($welcome) {
                pll_register_string('gsxr-777-widget-welcome', $welcome, 'GSXR-777 AI Chat');
            }
            if ($placeholder) {
                pll_register_string('gsxr-777-widget-placeholder', $placeholder, 'GSXR-777 AI Chat');
            }
        }
    }

    private function load_dependencies() {
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-admin.php';
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-widget.php';
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-api.php';
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-knowledge.php';
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-security.php';
        require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-stats.php';

        $this->admin = new GSXR_777_Admin();
        $this->widget = new GSXR_777_Widget();
        $this->api = new GSXR_777_API();
        $this->knowledge = new GSXR_777_Knowledge();
        $this->security = new GSXR_777_Security();
        $this->stats = new GSXR_777_Stats();
    }

    private function set_locale() {
        add_action('init', array($this, 'load_plugin_textdomain'));
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'gsxr-777',
            false,
            dirname(GSXR_777_PLUGIN_BASENAME) . '/languages/'
        );
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            add_action('admin_menu', array($this->admin, 'add_admin_menu'));
            add_action('admin_init', array($this->admin, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_styles'));
            add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_scripts'));
            
            // AJAX handlers
            add_action('wp_ajax_gsxr_777_test_connection', array($this->admin, 'handle_ajax_test_connection'));
            add_action('wp_ajax_gsxr_777_save_knowledge_file', array($this->admin, 'handle_ajax_save_knowledge_file'));
            add_action('wp_ajax_gsxr_777_delete_knowledge_file', array($this->admin, 'handle_ajax_delete_knowledge_file'));
            add_action('wp_ajax_gsxr_777_get_knowledge_file', array($this->admin, 'handle_ajax_get_knowledge_file'));
        }
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this->widget, 'enqueue_widget_scripts'));
        add_action('rest_api_init', array($this->widget, 'register_rest_routes'));
        add_shortcode('gsxr_777_chat', array($this->widget, 'render_shortcode'));
    }

    public static function activate() {
        // Check minimum requirements
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            deactivate_plugins(GSXR_777_PLUGIN_BASENAME);
            wp_die(__('GSXR-777 AI Open Chat requires PHP 7.4.0 or higher.', 'gsxr-777'));
        }

        global $wp_version;
        if (version_compare($wp_version, '5.0.0', '<')) {
            deactivate_plugins(GSXR_777_PLUGIN_BASENAME);
            wp_die(__('GSXR-777 AI Open Chat requires WordPress 5.0.0 or higher.', 'gsxr-777'));
        }

        // Create database tables
        self::create_tables();
        
        // Create knowledge directory
        self::create_knowledge_directory();
        
        // Set default options
        self::set_default_options();
    }

    public static function deactivate() {
        // Clear any transients
        delete_transient('gsxr_777_api_test_result');
    }

    public static function uninstall() {
        if (get_option('gsxr_777_delete_data_on_uninstall', false)) {
            self::delete_tables();
            self::delete_options();
            self::delete_knowledge_directory();
        }
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sessions_table = $wpdb->prefix . 'gsxr777_sessions';
        $messages_table = $wpdb->prefix . 'gsxr777_messages';
        $security_table = $wpdb->prefix . 'gsxr777_security_log';
        $blocked_ips_table = $wpdb->prefix . 'gsxr777_blocked_ips';

        $sql = "CREATE TABLE $sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;

        CREATE TABLE $messages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            role enum('user','assistant') NOT NULL,
            content text NOT NULL,
            tokens_used int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;

        CREATE TABLE $security_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            event_type varchar(50) NOT NULL,
            details text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ip_address (ip_address),
            KEY idx_event_type (event_type),
            KEY idx_created_at (created_at)
        ) $charset_collate;

        CREATE TABLE $blocked_ips_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL UNIQUE,
            reason varchar(255),
            blocked_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime,
            PRIMARY KEY (id),
            KEY idx_ip_address (ip_address),
            KEY idx_expires_at (expires_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check for database errors
        global $wpdb;
        if (!empty($wpdb->last_error)) {
            error_log('GSXR-777 Database Error: ' . $wpdb->last_error);
        }
    }

    private static function create_knowledge_directory() {
        $upload_dir = wp_upload_dir();
        $knowledge_dir = $upload_dir['basedir'] . '/gsxr-777-knowledge';
        
        if (!file_exists($knowledge_dir)) {
            // Create directory with error handling
            if (!wp_mkdir_p($knowledge_dir)) {
                error_log('GSXR-777: Could not create knowledge directory: ' . $knowledge_dir);
                return false;
            }
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n<Files *.md>\nOrder allow,deny\nAllow from all\n</Files>";
            $result = file_put_contents($knowledge_dir . '/.htaccess', $htaccess_content, LOCK_EX);
            if ($result === false) {
                error_log('GSXR-777: Could not write .htaccess file');
            }
            
            // Create example knowledge file
            $example_content = "# Welcome to GSXR-777 AI Chat\n\nThis is an example knowledge base file. You can edit or delete this file and add your own content.";
            $result = file_put_contents($knowledge_dir . '/welcome.md', $example_content, LOCK_EX);
            if ($result === false) {
                error_log('GSXR-777: Could not write welcome.md file');
            }
        }
        return true;
    }

    private static function set_default_options() {
        $defaults = array(
            'gsxr_777_api_base_url' => 'https://api.openai.com/v1',
            'gsxr_777_api_model' => 'gpt-4o-mini',
            'gsxr_777_api_project_id' => '',
            'gsxr_777_api_temperature' => 0.7,
            'gsxr_777_api_max_tokens' => 1000,
            'gsxr_777_api_personality' => 'friendly',
            'gsxr_777_api_top_p' => 1,
            'gsxr_777_api_frequency_penalty' => 0,
            'gsxr_777_api_presence_penalty' => 0,
            'gsxr_777_api_history_limit' => 20,
            'gsxr_777_api_system_instructions' => '',
            'gsxr_777_widget_title' => __('Chat', 'gsxr-777'),
            'gsxr_777_widget_welcome' => __('Hello! How can I help you?', 'gsxr-777'),
            'gsxr_777_widget_placeholder' => __('Type your message...', 'gsxr-777'),
            'gsxr_777_widget_position' => 'bottom-right',
            'gsxr_777_widget_primary_color' => '#2563eb',
            'gsxr_777_widget_secondary_color' => '#1d4ed8',
            'gsxr_777_widget_gradient_angle' => 135,
            'gsxr_777_widget_chat_background_color' => '#ffffff',
            'gsxr_777_widget_messages_background_color' => '#ffffff',
            'gsxr_777_widget_assistant_background_color' => '#f0f0f0',
            'gsxr_777_widget_assistant_text_color' => '#333333',
            'gsxr_777_widget_user_text_color' => '#ffffff',
            'gsxr_777_widget_input_background_color' => '#ffffff',
            'gsxr_777_widget_input_text_color' => '#333333',
            'gsxr_777_widget_font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'gsxr_777_widget_chat_font_family' => 'inherit',
            'gsxr_777_widget_width' => 400,
            'gsxr_777_widget_height' => 600,
            'gsxr_777_rate_limit_requests' => 10,
            'gsxr_777_rate_limit_window' => 60,
            'gsxr_777_delete_data_on_uninstall' => false
        );

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    private static function delete_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gsxr777_sessions',
            $wpdb->prefix . 'gsxr777_messages',
            $wpdb->prefix . 'gsxr777_security_log',
            $wpdb->prefix . 'gsxr777_blocked_ips'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    private static function delete_options() {
        $options = array(
            'gsxr_777_api_base_url',
            'gsxr_777_api_key',
            'gsxr_777_api_model',
            'gsxr_777_api_project_id',
            'gsxr_777_api_temperature',
            'gsxr_777_api_max_tokens',
            'gsxr_777_api_personality',
            'gsxr_777_api_top_p',
            'gsxr_777_api_frequency_penalty',
            'gsxr_777_api_presence_penalty',
            'gsxr_777_api_history_limit',
            'gsxr_777_api_system_instructions',
            'gsxr_777_widget_title',
            'gsxr_777_widget_welcome',
            'gsxr_777_widget_placeholder',
            'gsxr_777_widget_position',
            'gsxr_777_widget_primary_color',
            'gsxr_777_widget_secondary_color',
            'gsxr_777_widget_gradient_angle',
            'gsxr_777_widget_chat_background_color',
            'gsxr_777_widget_messages_background_color',
            'gsxr_777_widget_assistant_background_color',
            'gsxr_777_widget_assistant_text_color',
            'gsxr_777_widget_user_text_color',
            'gsxr_777_widget_input_background_color',
            'gsxr_777_widget_input_text_color',
            'gsxr_777_widget_font_family',
            'gsxr_777_widget_chat_font_family',
            'gsxr_777_widget_width',
            'gsxr_777_widget_height',
            'gsxr_777_rate_limit_requests',
            'gsxr_777_rate_limit_window',
            'gsxr_777_delete_data_on_uninstall'
        );

        foreach ($options as $option) {
            delete_option($option);
        }
    }

    private static function delete_knowledge_directory() {
        $upload_dir = wp_upload_dir();
        $knowledge_dir = $upload_dir['basedir'] . '/gsxr-777-knowledge';
        
        if (file_exists($knowledge_dir)) {
            // Only delete .md and .htaccess files, prevent deletion of other files
            $files = glob($knowledge_dir . '/*.{md,htaccess}', GLOB_BRACE);
            
            if ($files !== false) {
                foreach ($files as $file) {
                    // Verify file is in the knowledge directory (prevent path traversal)
                    if (is_file($file) && strpos(realpath($file), realpath($knowledge_dir)) === 0) {
                        @unlink($file);
                    }
                }
            }
            
            // Only remove directory if it's empty
            if (is_dir($knowledge_dir)) {
                @rmdir($knowledge_dir);
            }
        }
    }
}
