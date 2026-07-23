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
    private const VERSION_OPTION = 'gsxr_777_plugin_version';
    private const CLEANUP_HOOK = 'gsxr_777_daily_cleanup';
    private const REBUILD_INDEX_HOOK = 'gsxr_777_rebuild_knowledge_index';

    public function __construct() {
        $this->load_dependencies();
    }

    public function run() {
        $did_upgrade = self::maybe_upgrade();
        $this->api->migrate_stored_api_key();
        $this->knowledge->migrate_legacy_files();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_maintenance_hooks();
        if ($did_upgrade) {
            self::schedule_knowledge_rebuild();
        }
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
            add_action('wp_ajax_gsxr_777_rebuild_knowledge_index', array($this->admin, 'handle_ajax_rebuild_knowledge_index'));
        }
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this->widget, 'enqueue_widget_scripts'));
        add_action('rest_api_init', array($this->widget, 'register_rest_routes'));
        add_shortcode('gsxr_777_chat', array($this->widget, 'render_shortcode'));
        add_action('save_post', array($this->knowledge, 'index_post'), 20, 3);
        add_action('before_delete_post', array($this->knowledge, 'delete_post_from_index'));
        add_action('admin_init', array($this, 'add_privacy_policy_content'));
    }

    private function define_maintenance_hooks() {
        add_action(self::CLEANUP_HOOK, array($this, 'run_daily_cleanup'));
        add_action(self::REBUILD_INDEX_HOOK, array($this->knowledge, 'rebuild_index'));

        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK);
        }
    }

    public static function activate() {
        // Check minimum requirements
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            deactivate_plugins(GSXR_777_PLUGIN_BASENAME);
            wp_die(esc_html__('GSXR-777 AI Open Chat requires PHP 7.4.0 or higher.', 'gsxr-777-ai-open-chat'));
        }

        global $wp_version;
        if (version_compare($wp_version, '5.0.0', '<')) {
            deactivate_plugins(GSXR_777_PLUGIN_BASENAME);
            wp_die(esc_html__('GSXR-777 AI Open Chat requires WordPress 5.0.0 or higher.', 'gsxr-777-ai-open-chat'));
        }

        self::maybe_upgrade(true);
        self::schedule_maintenance();
    }

    public static function deactivate() {
        // Clear any transients
        delete_transient('gsxr_777_api_test_result');
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        wp_clear_scheduled_hook(self::REBUILD_INDEX_HOOK);
    }

    public static function uninstall() {
        if (get_option('gsxr_777_delete_data_on_uninstall', false)) {
            self::delete_tables();
            self::delete_options();
            self::delete_knowledge_directory();
        }
    }

    private static function maybe_upgrade($force = false) {
        $installed_version = get_option(self::VERSION_OPTION, false);

        if (!$force && !self::needs_upgrade($installed_version)) {
            return false;
        }

        // Keep schema and defaults in sync during silent plugin updates.
        self::create_tables();
        self::create_knowledge_directory();
        self::set_default_options();
        update_option(self::VERSION_OPTION, GSXR_777_VERSION);
        return true;
    }

    private static function needs_upgrade($installed_version) {
        if ($installed_version === false || $installed_version === '') {
            return true;
        }

        return version_compare((string) $installed_version, GSXR_777_VERSION, '<');
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $security_table = esc_sql($wpdb->prefix . 'gsxr777_security_log');
        $blocked_ips_table = esc_sql($wpdb->prefix . 'gsxr777_blocked_ips');
        $rate_limits_table = esc_sql($wpdb->prefix . 'gsxr777_rate_limits');
        $knowledge_chunks_table = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        $knowledge_documents_table = esc_sql($wpdb->prefix . 'gsxr777_knowledge_documents');

        $sql = "CREATE TABLE $sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY idx_created_at (created_at),
            KEY idx_last_activity (last_activity)
        ) $charset_collate;

        CREATE TABLE $messages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            role enum('user','assistant') NOT NULL,
            content longtext NOT NULL,
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
        ) $charset_collate;

        CREATE TABLE $rate_limits_table (
            rate_key char(64) NOT NULL,
            request_count int unsigned NOT NULL DEFAULT 1,
            expires_at datetime NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (rate_key),
            KEY idx_expires_at (expires_at)
        ) $charset_collate;

        CREATE TABLE $knowledge_chunks_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_type varchar(20) NOT NULL,
            source_key char(64) NOT NULL,
            source_url text DEFAULT NULL,
            title text NOT NULL,
            chunk_index int unsigned NOT NULL DEFAULT 0,
            content longtext NOT NULL,
            content_hash char(64) NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY source_chunk (source_type, source_key, chunk_index),
            KEY idx_source_key (source_key),
            KEY idx_updated_at (updated_at)
        ) $charset_collate;

        CREATE TABLE $knowledge_documents_table (
            source_key char(64) NOT NULL,
            filename varchar(190) NOT NULL,
            content longtext NOT NULL,
            content_hash char(64) NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (source_key),
            UNIQUE KEY filename (filename),
            KEY idx_updated_at (updated_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // FULLTEXT is optional: retrieval has a portable LIKE-based fallback.
        $fulltext_index = $wpdb->get_var($wpdb->prepare(
            "SHOW INDEX FROM {$knowledge_chunks_table} WHERE Key_name = %s",
            'search_content'
        ));
        if (!$fulltext_index) {
            $wpdb->query("ALTER TABLE {$knowledge_chunks_table} ADD FULLTEXT KEY search_content (title, content)");
        }
        
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
            
            // Deny direct HTTP access on Apache/OpenLiteSpeed.
            $htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(?:md|txt|json)$\">\nRequire all denied\n</FilesMatch>\n<IfModule !mod_authz_core.c>\n<FilesMatch \"\\.(?:md|txt|json)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n</IfModule>";
            $result = file_put_contents($knowledge_dir . '/.htaccess', $htaccess_content, LOCK_EX);
            if ($result === false) {
                error_log('GSXR-777: Could not write .htaccess file');
            }
            
            $index_content = "<?php\n// Silence is golden.\n";
            if (file_put_contents($knowledge_dir . '/index.php', $index_content, LOCK_EX) === false) {
                error_log('GSXR-777: Could not write index.php file');
            }
        } else {
            // Repair protection created by older plugin versions.
            $htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(?:md|txt|json)$\">\nRequire all denied\n</FilesMatch>\n<IfModule !mod_authz_core.c>\n<FilesMatch \"\\.(?:md|txt|json)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n</IfModule>";
            file_put_contents($knowledge_dir . '/.htaccess', $htaccess_content, LOCK_EX);
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
            'gsxr_777_widget_title' => __('Chat', 'gsxr-777-ai-open-chat'),
            'gsxr_777_widget_welcome' => __('Hello! How can I help you?', 'gsxr-777-ai-open-chat'),
            'gsxr_777_widget_placeholder' => __('Type your message...', 'gsxr-777-ai-open-chat'),
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
            'gsxr_777_data_retention_days' => 90,
            'gsxr_777_security_log_retention_days' => 30,
            'gsxr_777_store_request_metadata' => false,
            'gsxr_777_include_page_context' => true,
            'gsxr_777_widget_theme_mode' => 'auto',
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
            $wpdb->prefix . 'gsxr777_blocked_ips',
            $wpdb->prefix . 'gsxr777_rate_limits',
            $wpdb->prefix . 'gsxr777_knowledge_chunks',
            $wpdb->prefix . 'gsxr777_knowledge_documents'
        );

        foreach ($tables as $table) {
            $table = esc_sql($table);
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
            'gsxr_777_data_retention_days',
            'gsxr_777_security_log_retention_days',
            'gsxr_777_store_request_metadata',
            'gsxr_777_include_page_context',
            'gsxr_777_widget_theme_mode',
            'gsxr_777_delete_data_on_uninstall',
            'gsxr_777_legacy_knowledge_migrated',
            self::VERSION_OPTION
        );

        foreach ($options as $option) {
            delete_option($option);
        }
    }

    private static function delete_knowledge_directory() {
        $upload_dir = wp_upload_dir();
        $knowledge_dir = $upload_dir['basedir'] . '/gsxr-777-knowledge';
        
        if (file_exists($knowledge_dir)) {
            // Only delete files owned by the plugin.
            $files = array_merge(
                glob($knowledge_dir . '/*.md') ?: array(),
                array($knowledge_dir . '/.htaccess', $knowledge_dir . '/index.php')
            );
            
            if ($files !== false) {
                foreach ($files as $file) {
                    // Verify file is in the knowledge directory (prevent path traversal)
                    if (is_file($file) && strpos(realpath($file), realpath($knowledge_dir)) === 0) {
                        wp_delete_file($file);
                    }
                }
            }
            
            // Leave an empty protected directory in place. Removing files is
            // sufficient for uninstall and avoids direct filesystem rmdir().
        }
    }

    private static function schedule_maintenance() {
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK);
        }

        self::schedule_knowledge_rebuild();
    }

    private static function schedule_knowledge_rebuild() {
        if (!wp_next_scheduled(self::REBUILD_INDEX_HOOK)) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::REBUILD_INDEX_HOOK);
        }
    }

    public function run_daily_cleanup() {
        $data_days = max(1, intval(get_option('gsxr_777_data_retention_days', 90)));
        $security_days = max(1, intval(get_option('gsxr_777_security_log_retention_days', 30)));

        $this->stats->cleanup_old_data($data_days);
        $this->security->cleanup_old_logs($security_days);
        $this->security->cleanup_rate_limits();
    }

    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<p>' . esc_html__(
            'The AI chat may store conversation messages and an anonymized request identifier for abuse prevention. Messages and selected page context may be sent to the configured AI provider. Chat data is automatically removed after the configured retention period, and visitors can clear their own conversation from the chat interface.',
            'gsxr-777-ai-open-chat'
        ) . '</p>';

        wp_add_privacy_policy_content(
            __('GSXR-777 AI Open Chat', 'gsxr-777-ai-open-chat'),
            wp_kses_post($content)
        );
    }
}
