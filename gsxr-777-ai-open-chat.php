<?php
/**
 * Plugin Name: GSXR-777 AI Open Chat
 * Plugin URI: https://github.com/gsxr-777/gsxr-777-ai-open-chat
 * Description: Universal AI chat widget for WordPress with support for OpenAI, Claude, Gemini, Yandex and other LLM providers.
 * Version: 1.3.1
 * Author: GSXR-777
 * Author URI: https://wln.su/
 * Text Domain: gsxr-777
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: MIT
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GSXR_777_VERSION', '1.3.1');
define('GSXR_777_PLUGIN_FILE', __FILE__);
define('GSXR_777_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSXR_777_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GSXR_777_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load translations
add_action('plugins_loaded', 'gsxr_777_load_textdomain');
function gsxr_777_load_textdomain() {
    $domain = 'gsxr-777';
    $languages_rel_path = dirname(GSXR_777_PLUGIN_BASENAME) . '/languages';

    // Register plugin languages path for regular WP translation loading.
    load_plugin_textdomain($domain, false, $languages_rel_path);

    // Force-load bundled MO as a fallback/merge source, so recently added strings
    // (for example UI descriptions) are available even with stale global language packs.
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
    $mofile = GSXR_777_PLUGIN_DIR . 'languages/' . $domain . '-' . $locale . '.mo';
    if (file_exists($mofile)) {
        load_textdomain($domain, $mofile);
    }
}

// Load the main plugin class
require_once GSXR_777_PLUGIN_DIR . 'includes/class-gsxr-777-core.php';

// Initialize the plugin
function gsxr_777_init() {
    $plugin = new GSXR_777_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'gsxr_777_init');

// Activation hook
register_activation_hook(__FILE__, array('GSXR_777_Core', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('GSXR_777_Core', 'deactivate'));

// Uninstall hook
register_uninstall_hook(__FILE__, array('GSXR_777_Core', 'uninstall'));
