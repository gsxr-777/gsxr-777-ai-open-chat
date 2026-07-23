<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Admin {

    private $api_key_mask = '********';

    public function __construct() {
        // Constructor
    }

    public function add_admin_menu() {
        add_menu_page(
            __('GSXR-777 AI Chat', 'gsxr-777-ai-open-chat'),
            __('AI Chat', 'gsxr-777-ai-open-chat'),
            'manage_options',
            'gsxr-777-ai-chat',
            array($this, 'render_api_settings_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('API Settings', 'gsxr-777-ai-open-chat'),
            __('API Settings', 'gsxr-777-ai-open-chat'),
            'manage_options',
            'gsxr-777-ai-chat',
            array($this, 'render_api_settings_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Knowledge Base', 'gsxr-777-ai-open-chat'),
            __('Knowledge Base', 'gsxr-777-ai-open-chat'),
            'manage_options',
            'gsxr-777-knowledge',
            array($this, 'render_knowledge_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Widget Settings', 'gsxr-777-ai-open-chat'),
            __('Widget Settings', 'gsxr-777-ai-open-chat'),
            'manage_options',
            'gsxr-777-widget',
            array($this, 'render_widget_settings_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Statistics', 'gsxr-777-ai-open-chat'),
            __('Statistics', 'gsxr-777-ai-open-chat'),
            'manage_options',
            'gsxr-777-stats',
            array($this, 'render_statistics_page')
        );
    }

    public function register_settings() {
        // API Settings
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_base_url', array(
            'sanitize_callback' => array($this, 'sanitize_api_base_url')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_key', array(
            'sanitize_callback' => array($this, 'sanitize_api_key')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_model', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_project_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_temperature', array(
            'sanitize_callback' => array($this, 'sanitize_temperature')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_max_tokens', array(
            'sanitize_callback' => array($this, 'sanitize_max_tokens')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_personality', array(
            'sanitize_callback' => array($this, 'sanitize_personality')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_top_p', array(
            'sanitize_callback' => array($this, 'sanitize_top_p')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_frequency_penalty', array(
            'sanitize_callback' => array($this, 'sanitize_penalty')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_presence_penalty', array(
            'sanitize_callback' => array($this, 'sanitize_penalty')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_history_limit', array(
            'sanitize_callback' => array($this, 'sanitize_history_limit')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_system_instructions', array(
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_data_retention_days', array(
            'sanitize_callback' => array($this, 'sanitize_retention_days')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_security_log_retention_days', array(
            'sanitize_callback' => array($this, 'sanitize_retention_days')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_store_request_metadata', array(
            'sanitize_callback' => array($this, 'sanitize_boolean')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_include_page_context', array(
            'sanitize_callback' => array($this, 'sanitize_boolean')
        ));
        register_setting('gsxr_777_api_settings', 'gsxr_777_delete_data_on_uninstall', array(
            'sanitize_callback' => array($this, 'sanitize_boolean')
        ));

        // Widget Settings
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_title', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_welcome', array(
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_placeholder', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_position', array(
            'sanitize_callback' => array($this, 'sanitize_position')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_primary_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_secondary_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_gradient_angle', array(
            'sanitize_callback' => array($this, 'sanitize_gradient_angle')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_chat_background_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_messages_background_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_assistant_background_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_assistant_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_user_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_input_background_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_input_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_font_family', array(
            'sanitize_callback' => array($this, 'sanitize_font_family')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_chat_font_family', array(
            'sanitize_callback' => array($this, 'sanitize_font_family')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_width', array(
            'sanitize_callback' => array($this, 'sanitize_widget_size')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_height', array(
            'sanitize_callback' => array($this, 'sanitize_widget_size')
        ));
        register_setting('gsxr_777_widget_settings', 'gsxr_777_widget_theme_mode', array(
            'sanitize_callback' => array($this, 'sanitize_theme_mode')
        ));
    }

    public function render_api_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('gsxr_777_api_settings');

            $api_base_url = $this->sanitize_api_base_url(
                esc_url_raw($this->get_post_value('gsxr_777_api_base_url'))
            );
            if ($api_base_url === '') {
                echo '<div class="notice notice-error"><p>' .
                    esc_html__('Use HTTPS for remote API endpoints. Plain HTTP is allowed only for local services.', 'gsxr-777-ai-open-chat') .
                    '</p></div>';
                return;
            }
            update_option('gsxr_777_api_base_url', $api_base_url);

            // Encrypt API key before saving
            $api = new GSXR_777_API();
            $stored_encrypted_key = get_option('gsxr_777_api_key', '');
            $submitted_api_key = isset($_POST['gsxr_777_api_key'])
                ? sanitize_text_field(wp_unslash($_POST['gsxr_777_api_key']))
                : '';
            $is_masked_value = isset($_POST['gsxr_777_api_key_masked']) &&
                sanitize_text_field(wp_unslash($_POST['gsxr_777_api_key_masked'])) === '1' &&
                $submitted_api_key === $this->api_key_mask;

            // Keep existing encrypted key when password field is left empty.
            if ($submitted_api_key !== '' && !$is_masked_value) {
                // Guard against accidental re-encryption of already encrypted value.
                if (empty($stored_encrypted_key) || !hash_equals($stored_encrypted_key, $submitted_api_key)) {
                    $encrypted_key = $api->encrypt_api_key($submitted_api_key);
                    if ($encrypted_key === '') {
                        echo '<div class="notice notice-error"><p>' .
                            esc_html__('The API key could not be encrypted. Settings were not saved.', 'gsxr-777-ai-open-chat') .
                            '</p></div>';
                        return;
                    }
                    update_option('gsxr_777_api_key', $encrypted_key);
                }
            }
            
            update_option('gsxr_777_api_model', sanitize_text_field($this->get_post_value('gsxr_777_api_model')));
            update_option('gsxr_777_api_project_id', sanitize_text_field($this->get_post_value('gsxr_777_api_project_id')));
            update_option('gsxr_777_api_temperature', $this->sanitize_temperature($this->get_post_value('gsxr_777_api_temperature', 0.7)));
            update_option('gsxr_777_api_max_tokens', $this->sanitize_max_tokens($this->get_post_value('gsxr_777_api_max_tokens', 1000)));
            update_option('gsxr_777_api_personality', $this->sanitize_personality($this->get_post_value('gsxr_777_api_personality', 'friendly')));
            update_option('gsxr_777_api_top_p', $this->sanitize_top_p($this->get_post_value('gsxr_777_api_top_p', 1)));
            update_option('gsxr_777_api_frequency_penalty', $this->sanitize_penalty($this->get_post_value('gsxr_777_api_frequency_penalty', 0)));
            update_option('gsxr_777_api_presence_penalty', $this->sanitize_penalty($this->get_post_value('gsxr_777_api_presence_penalty', 0)));
            update_option('gsxr_777_api_history_limit', $this->sanitize_history_limit($this->get_post_value('gsxr_777_api_history_limit', 20)));
            update_option('gsxr_777_api_system_instructions', sanitize_textarea_field($this->get_post_value('gsxr_777_api_system_instructions')));
            update_option('gsxr_777_data_retention_days', $this->sanitize_retention_days($this->get_post_value('gsxr_777_data_retention_days', 90)));
            update_option('gsxr_777_security_log_retention_days', $this->sanitize_retention_days($this->get_post_value('gsxr_777_security_log_retention_days', 30)));
            update_option('gsxr_777_store_request_metadata', isset($_POST['gsxr_777_store_request_metadata']));
            update_option('gsxr_777_include_page_context', isset($_POST['gsxr_777_include_page_context']));
            update_option('gsxr_777_delete_data_on_uninstall', isset($_POST['gsxr_777_delete_data_on_uninstall']));
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'gsxr-777-ai-open-chat') . '</p></div>';
        }

        $api_base_url = get_option('gsxr_777_api_base_url', 'https://api.openai.com/v1');
        $stored_api_key = get_option('gsxr_777_api_key', '');
        $has_api_key = !empty($stored_api_key);
        $api_key = $has_api_key ? $this->api_key_mask : '';
        $api_model = get_option('gsxr_777_api_model', 'gpt-4o-mini');
        $api_project_id = get_option('gsxr_777_api_project_id', '');
        $api_temperature = get_option('gsxr_777_api_temperature', 0.7);
        $api_max_tokens = get_option('gsxr_777_api_max_tokens', 1000);
        $api_personality = get_option('gsxr_777_api_personality', 'friendly');
        $api_top_p = get_option('gsxr_777_api_top_p', 1);
        $api_frequency_penalty = get_option('gsxr_777_api_frequency_penalty', 0);
        $api_presence_penalty = get_option('gsxr_777_api_presence_penalty', 0);
        $api_history_limit = get_option('gsxr_777_api_history_limit', 20);
        $api_system_instructions = get_option('gsxr_777_api_system_instructions', '');
        $data_retention_days = get_option('gsxr_777_data_retention_days', 90);
        $security_log_retention_days = get_option('gsxr_777_security_log_retention_days', 30);
        $store_request_metadata = (bool) get_option('gsxr_777_store_request_metadata', false);
        $include_page_context = (bool) get_option('gsxr_777_include_page_context', true);
        $delete_data_on_uninstall = (bool) get_option('gsxr_777_delete_data_on_uninstall', false);

        $personality_options = array(
            'friendly' => __('Friendly', 'gsxr-777-ai-open-chat'),
            'sarcastic' => __('Sarcastic', 'gsxr-777-ai-open-chat'),
            'pragmatic' => __('Pragmatic', 'gsxr-777-ai-open-chat'),
            'funny' => __('Funny', 'gsxr-777-ai-open-chat'),
            'formal' => __('Formal', 'gsxr-777-ai-open-chat'),
            'empathetic' => __('Empathetic', 'gsxr-777-ai-open-chat'),
            'expert' => __('Expert', 'gsxr-777-ai-open-chat'),
            'concise' => __('Concise', 'gsxr-777-ai-open-chat')
        );

        $providers = array(
            'OpenAI' => array(
                'url' => 'https://api.openai.com/v1',
                'key_url' => 'https://platform.openai.com/account/api-keys',
                'doc_url' => 'https://platform.openai.com/docs/models'
            ),
            'Anthropic Claude' => array(
                'url' => 'https://api.anthropic.com/v1',
                'key_url' => 'https://console.anthropic.com/account/keys',
                'doc_url' => 'https://docs.anthropic.com/claude/reference/getting-started-with-the-api'
            ),
            'Google Gemini' => array(
                'url' => 'https://generativelanguage.googleapis.com/v1beta',
                'key_url' => 'https://ai.google.dev/tutorials/setup',
                'doc_url' => 'https://ai.google.dev/models'
            ),
            'Yandex AI' => array(
                'url' => 'https://ai.api.cloud.yandex.net/v1',
                'key_url' => 'https://console.yandex.cloud/iam',
                'doc_url' => 'https://yandex.cloud/en/docs/foundation-models/'
            ),
            'Ollama' => array(
                'url' => 'http://localhost:11434/v1',
                'key_url' => 'https://ollama.ai/',
                'doc_url' => 'https://github.com/ollama/ollama'
            ),
            'OpenRouter' => array(
                'url' => 'https://openrouter.ai/api/v1',
                'key_url' => 'https://openrouter.ai/keys',
                'doc_url' => 'https://openrouter.ai/docs'
            )
        );

        ?>
        <div class="wrap gsxr777-admin-page">
            <h1><?php esc_html_e('API Settings', 'gsxr-777-ai-open-chat'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('gsxr_777_api_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('AI Provider', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <select id="ai_provider">
                                <option value=""><?php esc_html_e('Select Provider', 'gsxr-777-ai-open-chat'); ?></option>
                                <?php foreach ($providers as $name => $config): ?>
                                    <option value="<?php echo esc_attr($config['url']); ?>" 
                                            data-key-url="<?php echo esc_attr($config['key_url']); ?>"
                                            data-doc-url="<?php echo esc_attr($config['doc_url']); ?>"
                                            <?php selected($api_base_url, $config['url']); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose your AI provider', 'gsxr-777-ai-open-chat'); ?><br />
                                <span id="provider_links" class="gsxr777-provider-links">
                                    <a id="key_link" href="#" target="_blank" rel="noopener noreferrer" class="gsxr777-provider-link">
                                        🔑 <?php esc_html_e('Get API Key', 'gsxr-777-ai-open-chat'); ?>
                                    </a>
                                    <a id="doc_link" href="#" target="_blank" rel="noopener noreferrer" class="gsxr777-provider-link">
                                        📚 <?php esc_html_e('API Documentation', 'gsxr-777-ai-open-chat'); ?>
                                    </a>
                                </span>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('API Base URL', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="url" id="gsxr_777_api_base_url" name="gsxr_777_api_base_url" 
                                   value="<?php echo esc_attr($api_base_url); ?>" class="regular-text" required />
                            <p class="description"><?php esc_html_e('Base URL for the AI API endpoint', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('API Key', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="password" name="gsxr_777_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="new-password" />
                            <input type="hidden" name="gsxr_777_api_key_masked" value="<?php echo $has_api_key ? '1' : '0'; ?>" />
                            <p class="description">
                                <?php esc_html_e('Your API key (stored encrypted). Leave blank to keep current key.', 'gsxr-777-ai-open-chat'); ?>
                                <?php if ($has_api_key): ?>
                                    <br /><span class="gsxr777-key-saved"><?php esc_html_e('Current key is saved.', 'gsxr-777-ai-open-chat'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Model', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="text" name="gsxr_777_api_model" 
                                   value="<?php echo esc_attr($api_model); ?>" class="regular-text" required />
                            <p class="description"><?php esc_html_e('AI model name (e.g., gpt-4o-mini, claude-3-sonnet)', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Project / Folder ID', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="text" name="gsxr_777_api_project_id"
                                   value="<?php echo esc_attr($api_project_id); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Optional for most providers. Required for Yandex Responses API as OpenAI-Project header value (example: b1gad9eo3bbmgfl7od3h).', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Temperature', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_temperature" 
                                   value="<?php echo esc_attr($api_temperature); ?>" 
                                   min="0" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php esc_html_e('Controls randomness (0.0 = deterministic, 2.0 = very random)', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Max Tokens', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_max_tokens" 
                                   value="<?php echo esc_attr($api_max_tokens); ?>" 
                                   min="1" max="4000" class="small-text" />
                            <p class="description"><?php esc_html_e('Maximum tokens in response', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Assistant Personality', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <select name="gsxr_777_api_personality">
                                <?php foreach ($personality_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($api_personality, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Defines the tone of model responses (friendly, sarcastic, pragmatic, funny, etc.).', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Top P', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_top_p"
                                   value="<?php echo esc_attr($api_top_p); ?>"
                                   min="0" max="1" step="0.05" class="small-text" />
                            <p class="description"><?php esc_html_e('Nucleus sampling threshold (0.0-1.0). Lower values make output more focused.', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Frequency Penalty', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_frequency_penalty"
                                   value="<?php echo esc_attr($api_frequency_penalty); ?>"
                                   min="-2" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php esc_html_e('Reduces repetitive words or phrases (-2.0 to 2.0).', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Presence Penalty', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_presence_penalty"
                                   value="<?php echo esc_attr($api_presence_penalty); ?>"
                                   min="-2" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php esc_html_e('Encourages introducing new topics (-2.0 to 2.0).', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Conversation Context Size', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_history_limit"
                                   value="<?php echo esc_attr($api_history_limit); ?>"
                                   min="1" max="100" class="small-text" />
                            <p class="description"><?php esc_html_e('How many previous messages are sent to the model as context.', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Additional System Instructions', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <textarea name="gsxr_777_api_system_instructions" rows="4" class="large-text"><?php echo esc_textarea($api_system_instructions); ?></textarea>
                            <p class="description"><?php esc_html_e('Extra rules for the assistant behavior. Applies to every request.', 'gsxr-777-ai-open-chat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gsxr_777_data_retention_days"><?php esc_html_e('Chat Data Retention', 'gsxr-777-ai-open-chat'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gsxr_777_data_retention_days" name="gsxr_777_data_retention_days"
                                   value="<?php echo esc_attr($data_retention_days); ?>"
                                   min="1" max="3650" class="small-text" />
                            <span><?php esc_html_e('days', 'gsxr-777-ai-open-chat'); ?></span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gsxr_777_security_log_retention_days"><?php esc_html_e('Security Log Retention', 'gsxr-777-ai-open-chat'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gsxr_777_security_log_retention_days" name="gsxr_777_security_log_retention_days"
                                   value="<?php echo esc_attr($security_log_retention_days); ?>"
                                   min="1" max="3650" class="small-text" />
                            <span><?php esc_html_e('days', 'gsxr-777-ai-open-chat'); ?></span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Page Context', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="gsxr_777_include_page_context" value="1"
                                       <?php checked($include_page_context); ?> />
                                <?php esc_html_e('Send limited current-page text to the configured AI provider.', 'gsxr-777-ai-open-chat'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Request Metadata', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="gsxr_777_store_request_metadata" value="1"
                                       <?php checked($store_request_metadata); ?> />
                                <?php esc_html_e('Store anonymized IP and a shortened browser user agent for diagnostics.', 'gsxr-777-ai-open-chat'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Delete Data on Uninstall', 'gsxr-777-ai-open-chat'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="gsxr_777_delete_data_on_uninstall" value="1"
                                       <?php checked($delete_data_on_uninstall); ?> />
                                <?php esc_html_e('Permanently remove chat history, settings, and mini-RAG data when the plugin is deleted.', 'gsxr-777-ai-open-chat'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php esc_html_e('Save Settings', 'gsxr-777-ai-open-chat'); ?>" />
                    <button type="button" id="test_connection" class="button">
                        <?php esc_html_e('Test Connection', 'gsxr-777-ai-open-chat'); ?>
                    </button>
                </p>
            </form>
            
            <div id="test_result" class="gsxr777-test-result" role="status" aria-live="polite"></div>
        </div>
        
        <?php
    }

    public function render_knowledge_page() {
        $knowledge = new GSXR_777_Knowledge();
        $files = $knowledge->get_all_files();
        $index_stats = $knowledge->get_index_stats();
        
        if (isset($_POST['save_file'])) {
            check_admin_referer('gsxr_777_knowledge');
            
            $filename = sanitize_file_name($this->get_post_value('filename'));
            $content = $this->get_post_value('content');
            
            if ($knowledge->save_file($filename, $content)) {
                echo '<div class="notice notice-success"><p>' . esc_html__('File saved successfully!', 'gsxr-777-ai-open-chat') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error saving file!', 'gsxr-777-ai-open-chat') . '</p></div>';
            }
            
            $files = $knowledge->get_all_files(); // Refresh file list
        }
        
        // Render Knowledge page HTML
        ?>
        <div class="wrap gsxr777-admin-page gsxr777-admin">
            <h1><?php esc_html_e('Knowledge Base', 'gsxr-777-ai-open-chat'); ?></h1>
            <p>
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: Number of indexed sources, 2: Number of indexed text chunks. */
                    __('Mini-RAG index: %1$d sources, %2$d chunks.', 'gsxr-777-ai-open-chat'),
                    $index_stats['sources'],
                    $index_stats['chunks']
                ));
                ?>
                <button type="button" id="gsxr777-rebuild-index" class="button">
                    <?php esc_html_e('Rebuild site index', 'gsxr-777-ai-open-chat'); ?>
                </button>
                <span id="gsxr777-index-status" class="gsxr777-index-status" role="status" aria-live="polite"></span>
            </p>
            
            <div class="gsxr777-knowledge-container">
                <div class="gsxr777-knowledge-sidebar">
                    <h2><?php esc_html_e('Files', 'gsxr-777-ai-open-chat'); ?></h2>
                    <div id="file_list">
                        <?php if (empty($files)): ?>
                            <p><?php esc_html_e('No knowledge files found.', 'gsxr-777-ai-open-chat'); ?></p>
                        <?php else: ?>
                            <ul class="gsxr777-file-list">
                                <?php foreach ($files as $file): ?>
                                    <li>
                                        <button type="button" class="button-link gsxr777-load-file" data-filename="<?php echo esc_attr($file['name']); ?>">
                                            <?php echo esc_html($file['name']); ?>
                                        </button>
                                        <small>(<?php echo esc_html($file['modified']); ?>)</small>
                                        <button type="button" data-filename="<?php echo esc_attr($file['name']); ?>"
                                                class="button button-small gsxr777-delete-file">
                                            <?php esc_html_e('Delete', 'gsxr-777-ai-open-chat'); ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="gsxr777-new-file" class="button">
                        <?php esc_html_e('New File', 'gsxr-777-ai-open-chat'); ?>
                    </button>
                </div>
                
                <div class="gsxr777-knowledge-editor">
                    <form method="post" action="">
                        <?php wp_nonce_field('gsxr_777_knowledge'); ?>
                        
                        <p>
                            <label for="filename"><?php esc_html_e('Filename:', 'gsxr-777-ai-open-chat'); ?></label>
                            <input type="text" id="filename" name="filename" class="regular-text" 
                                   placeholder="example.md" required />
                        </p>
                        
                        <p>
                            <label for="content"><?php esc_html_e('Content:', 'gsxr-777-ai-open-chat'); ?></label>
                            <textarea id="content" name="content" rows="20" class="large-text code" 
                                      placeholder="# Your markdown content here..."></textarea>
                        </p>
                        
                        <p class="submit">
                            <input type="submit" name="save_file" class="button-primary" 
                                   value="<?php esc_html_e('Save File', 'gsxr-777-ai-open-chat'); ?>" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <?php
    }

    public function render_widget_settings_page() {
        $allowed_tabs = array('general', 'appearance');
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'general';
        }

        if (isset($_POST['submit'])) {
            check_admin_referer('gsxr_777_widget_settings');

            $settings_section = sanitize_key($this->get_post_value('gsxr_777_widget_settings_section', 'general'));
            if (!in_array($settings_section, $allowed_tabs, true)) {
                $settings_section = 'general';
            }

            if ($settings_section === 'appearance') {
                update_option('gsxr_777_widget_primary_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_primary_color'), '#2563eb'));
                update_option('gsxr_777_widget_secondary_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_secondary_color'), '#1d4ed8'));
                update_option('gsxr_777_widget_gradient_angle', $this->sanitize_gradient_angle($this->get_post_value('gsxr_777_widget_gradient_angle', 135)));
                update_option('gsxr_777_widget_chat_background_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_chat_background_color'), '#ffffff'));
                update_option('gsxr_777_widget_messages_background_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_messages_background_color'), '#ffffff'));
                update_option('gsxr_777_widget_assistant_background_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_assistant_background_color'), '#f0f0f0'));
                update_option('gsxr_777_widget_assistant_text_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_assistant_text_color'), '#333333'));
                update_option('gsxr_777_widget_user_text_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_user_text_color'), '#ffffff'));
                update_option('gsxr_777_widget_input_background_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_input_background_color'), '#ffffff'));
                update_option('gsxr_777_widget_input_text_color', $this->sanitize_color($this->get_post_value('gsxr_777_widget_input_text_color'), '#333333'));
                update_option('gsxr_777_widget_font_family', $this->sanitize_font_family($this->get_post_value('gsxr_777_widget_font_family')));
                update_option('gsxr_777_widget_chat_font_family', $this->sanitize_font_family($this->get_post_value('gsxr_777_widget_chat_font_family')));
                update_option('gsxr_777_widget_theme_mode', $this->sanitize_theme_mode($this->get_post_value('gsxr_777_widget_theme_mode', 'auto')));
            } else {
                // Validate welcome message length (max 500 chars)
                $welcome = $this->get_post_value('gsxr_777_widget_welcome');
                if ($this->text_length($welcome) > 500) {
                    echo '<div class="notice notice-error"><p>' .
                         esc_html(sprintf(
                             /* translators: %d: Current welcome-message character count. */
                             __('Welcome message is too long. Maximum 500 characters, current: %d', 'gsxr-777-ai-open-chat'),
                             $this->text_length($welcome)
                         )) .
                         '</p></div>';
                    return;
                }

                // Validate title length (max 100 chars)
                $title = $this->get_post_value('gsxr_777_widget_title');
                if ($this->text_length($title) > 100) {
                    echo '<div class="notice notice-error"><p>' .
                         esc_html(sprintf(
                             /* translators: %d: Current widget-title character count. */
                             __('Title is too long. Maximum 100 characters, current: %d', 'gsxr-777-ai-open-chat'),
                             $this->text_length($title)
                         )) .
                         '</p></div>';
                    return;
                }

                // Validate widget dimensions
                $width = intval($this->get_post_value('gsxr_777_widget_width', 400));
                $height = intval($this->get_post_value('gsxr_777_widget_height', 600));

                if ($width < 300 || $width > 800 || $height < 400 || $height > 900) {
                    echo '<div class="notice notice-error"><p>' .
                         esc_html__('Invalid widget dimensions. Width must be 300-800px and height 400-900px.', 'gsxr-777-ai-open-chat') .
                         '</p></div>';
                    return;
                }

                update_option('gsxr_777_widget_title', sanitize_text_field($title));
                update_option('gsxr_777_widget_welcome', sanitize_textarea_field($welcome));
                update_option('gsxr_777_widget_placeholder', sanitize_text_field($this->get_post_value('gsxr_777_widget_placeholder')));
                update_option('gsxr_777_widget_position', $this->sanitize_position($this->get_post_value('gsxr_777_widget_position', 'bottom-right')));
                update_option('gsxr_777_widget_width', $width);
                update_option('gsxr_777_widget_height', $height);
            }

            // Public pages may contain a cached wp_localize_script payload.
            // Purge the page/cache layer after a successful settings save so
            // appearance changes are visible immediately.
            do_action('litespeed_purge_all');

            $active_tab = $settings_section;
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'gsxr-777-ai-open-chat') . '</p></div>';
        }

        $widget_title = get_option('gsxr_777_widget_title', __('Chat', 'gsxr-777-ai-open-chat'));
        $widget_welcome = get_option('gsxr_777_widget_welcome', __('Hello! How can I help you?', 'gsxr-777-ai-open-chat'));
        $widget_placeholder = get_option('gsxr_777_widget_placeholder', __('Type your message...', 'gsxr-777-ai-open-chat'));
        $widget_position = get_option('gsxr_777_widget_position', 'bottom-right');
        $widget_primary_color = get_option('gsxr_777_widget_primary_color', '#2563eb');
        $widget_secondary_color = get_option('gsxr_777_widget_secondary_color', '#1d4ed8');
        $widget_gradient_angle = get_option('gsxr_777_widget_gradient_angle', 135);
        $widget_chat_background_color = get_option('gsxr_777_widget_chat_background_color', '#ffffff');
        $widget_messages_background_color = get_option('gsxr_777_widget_messages_background_color', '#ffffff');
        $widget_assistant_background_color = get_option('gsxr_777_widget_assistant_background_color', '#f0f0f0');
        $widget_assistant_text_color = get_option('gsxr_777_widget_assistant_text_color', '#333333');
        $widget_user_text_color = get_option('gsxr_777_widget_user_text_color', '#ffffff');
        $widget_input_background_color = get_option('gsxr_777_widget_input_background_color', '#ffffff');
        $widget_input_text_color = get_option('gsxr_777_widget_input_text_color', '#333333');
        $widget_font_family = get_option('gsxr_777_widget_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif');
        $widget_chat_font_family = get_option('gsxr_777_widget_chat_font_family', 'inherit');
        $widget_theme_mode = $this->sanitize_theme_mode(get_option('gsxr_777_widget_theme_mode', 'auto'));
        $widget_width = get_option('gsxr_777_widget_width', 400);
        $widget_height = get_option('gsxr_777_widget_height', 600);
        $tabs_base_url = admin_url('admin.php?page=gsxr-777-widget');
        ?>
        <div class="wrap gsxr777-admin-page">
            <h1><?php esc_html_e('Widget Settings', 'gsxr-777-ai-open-chat'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg('tab', 'general', $tabs_base_url)); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Основные', 'gsxr-777-ai-open-chat'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'appearance', $tabs_base_url)); ?>" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Внешний вид', 'gsxr-777-ai-open-chat'); ?>
                </a>
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('gsxr_777_widget_settings'); ?>
                <input type="hidden" name="gsxr_777_widget_settings_section" value="<?php echo esc_attr($active_tab); ?>" />
                
                <table class="form-table">
                    <?php if ($active_tab === 'appearance'): ?>
                        <tr>
                            <th scope="row">
                                <label for="gsxr_777_widget_theme_mode"><?php esc_html_e('Color Scheme', 'gsxr-777-ai-open-chat'); ?></label>
                            </th>
                            <td>
                                <select id="gsxr_777_widget_theme_mode" name="gsxr_777_widget_theme_mode">
                                    <option value="auto" <?php selected($widget_theme_mode, 'auto'); ?>><?php esc_html_e('Follow system', 'gsxr-777-ai-open-chat'); ?></option>
                                    <option value="light" <?php selected($widget_theme_mode, 'light'); ?>><?php esc_html_e('Light', 'gsxr-777-ai-open-chat'); ?></option>
                                    <option value="dark" <?php selected($widget_theme_mode, 'dark'); ?>><?php esc_html_e('Dark', 'gsxr-777-ai-open-chat'); ?></option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Primary Color', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_primary_color"
                                       value="<?php echo esc_attr($widget_primary_color); ?>" />
                                <p class="description"><?php esc_html_e('Main accent color for buttons, links and focus states.', 'gsxr-777-ai-open-chat'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Secondary Gradient Color', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_secondary_color"
                                       value="<?php echo esc_attr($widget_secondary_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Gradient Angle', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="number" name="gsxr_777_widget_gradient_angle"
                                       value="<?php echo esc_attr($widget_gradient_angle); ?>"
                                       min="0" max="360" class="small-text" />
                                <p class="description"><?php esc_html_e('Used for header/button/message gradients.', 'gsxr-777-ai-open-chat'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Chat Window Background', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_chat_background_color"
                                       value="<?php echo esc_attr($widget_chat_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Messages Area Background', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_messages_background_color"
                                       value="<?php echo esc_attr($widget_messages_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Assistant Message Background', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_assistant_background_color"
                                       value="<?php echo esc_attr($widget_assistant_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Assistant Message Text', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_assistant_text_color"
                                       value="<?php echo esc_attr($widget_assistant_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('User Message Text', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_user_text_color"
                                       value="<?php echo esc_attr($widget_user_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Input Background', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_input_background_color"
                                       value="<?php echo esc_attr($widget_input_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Input Text Color', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_input_text_color"
                                       value="<?php echo esc_attr($widget_input_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Widget Font Family', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_font_family"
                                       value="<?php echo esc_attr($widget_font_family); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Example: "Segoe UI", Roboto, Arial, sans-serif', 'gsxr-777-ai-open-chat'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Chat Font Family', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_chat_font_family"
                                       value="<?php echo esc_attr($widget_chat_font_family); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Used in chat messages and input area.', 'gsxr-777-ai-open-chat'); ?></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Widget Title', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_title"
                                       value="<?php echo esc_attr($widget_title); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Welcome Message', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <textarea name="gsxr_777_widget_welcome" rows="3" class="large-text"><?php echo esc_textarea($widget_welcome); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Input Placeholder', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_placeholder"
                                       value="<?php echo esc_attr($widget_placeholder); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Position', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <select name="gsxr_777_widget_position">
                                    <option value="bottom-right" <?php selected($widget_position, 'bottom-right'); ?>>
                                        <?php esc_html_e('Bottom Right', 'gsxr-777-ai-open-chat'); ?>
                                    </option>
                                    <option value="bottom-left" <?php selected($widget_position, 'bottom-left'); ?>>
                                        <?php esc_html_e('Bottom Left', 'gsxr-777-ai-open-chat'); ?>
                                    </option>
                                    <option value="top-right" <?php selected($widget_position, 'top-right'); ?>>
                                        <?php esc_html_e('Top Right', 'gsxr-777-ai-open-chat'); ?>
                                    </option>
                                    <option value="top-left" <?php selected($widget_position, 'top-left'); ?>>
                                        <?php esc_html_e('Top Left', 'gsxr-777-ai-open-chat'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Width (px)', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="number" name="gsxr_777_widget_width"
                                       value="<?php echo esc_attr($widget_width); ?>"
                                       min="300" max="800" class="small-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e('Height (px)', 'gsxr-777-ai-open-chat'); ?></th>
                            <td>
                                <input type="number" name="gsxr_777_widget_height"
                                       value="<?php echo esc_attr($widget_height); ?>"
                                       min="400" max="900" class="small-text" />
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php esc_html_e('Save Settings', 'gsxr-777-ai-open-chat'); ?>" />
                </p>
            </form>
            
            <?php if ($active_tab === 'general'): ?>
                <h2><?php esc_html_e('Shortcode Usage', 'gsxr-777-ai-open-chat'); ?></h2>
                <p><?php esc_html_e('Use this shortcode to display the chat widget:', 'gsxr-777-ai-open-chat'); ?></p>
                <code>[gsxr_777_chat]</code>

                <p><?php esc_html_e('With custom parameters:', 'gsxr-777-ai-open-chat'); ?></p>
                <code>[gsxr_777_chat title="Support" color="#ff6b35" width="420" height="640"]</code>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_statistics_page() {
        $stats = new GSXR_777_Stats();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only statistics filter.
        $period = isset($_GET['period']) ? absint(wp_unslash($_GET['period'])) : 30;
        if (!in_array($period, array(7, 30, 90), true)) {
            $period = 30;
        }
        
        $total_sessions = $stats->get_total_sessions($period);
        $total_messages = $stats->get_total_messages($period);
        $avg_messages = $stats->get_average_messages_per_session($period);
        ?>
        <div class="wrap gsxr777-admin-page">
            <h1><?php esc_html_e('Statistics', 'gsxr-777-ai-open-chat'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="gsxr-777-stats" />
                <label class="screen-reader-text" for="gsxr777-stats-period"><?php esc_html_e('Statistics period', 'gsxr-777-ai-open-chat'); ?></label>
                <select id="gsxr777-stats-period" name="period">
                    <option value="7" <?php selected($period, '7'); ?>><?php esc_html_e('Last 7 days', 'gsxr-777-ai-open-chat'); ?></option>
                    <option value="30" <?php selected($period, '30'); ?>><?php esc_html_e('Last 30 days', 'gsxr-777-ai-open-chat'); ?></option>
                    <option value="90" <?php selected($period, '90'); ?>><?php esc_html_e('Last 90 days', 'gsxr-777-ai-open-chat'); ?></option>
                </select>
            </form>
            
            <div class="gsxr777-stats-grid">
                <div class="gsxr777-stat-card">
                    <h3><?php esc_html_e('Total Sessions', 'gsxr-777-ai-open-chat'); ?></h3>
                    <p class="gsxr777-stat-value primary"><?php echo esc_html(number_format_i18n($total_sessions)); ?></p>
                </div>
                
                <div class="gsxr777-stat-card">
                    <h3><?php esc_html_e('Total Messages', 'gsxr-777-ai-open-chat'); ?></h3>
                    <p class="gsxr777-stat-value success"><?php echo esc_html(number_format_i18n($total_messages)); ?></p>
                </div>
                
                <div class="gsxr777-stat-card">
                    <h3><?php esc_html_e('Avg Messages/Session', 'gsxr-777-ai-open-chat'); ?></h3>
                    <p class="gsxr777-stat-value warning"><?php echo esc_html(number_format_i18n($avg_messages, 1)); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'gsxr-777-ai-open-chat') !== false) {
            wp_enqueue_style(
                'gsxr-777-admin',
                GSXR_777_PLUGIN_URL . 'admin/css/admin-style.css',
                array(),
                GSXR_777_VERSION
            );
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'gsxr-777-ai-open-chat') !== false) {
            wp_enqueue_script(
                'gsxr-777-admin',
                GSXR_777_PLUGIN_URL . 'admin/js/admin-script.js',
                array(),
                GSXR_777_VERSION,
                true
            );
            wp_localize_script('gsxr-777-admin', 'gsxr777_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'testNonce' => wp_create_nonce('gsxr_777_test_connection'),
                'knowledgeNonce' => wp_create_nonce('gsxr_777_knowledge'),
                'strings' => array(
                    'testing' => __('Testing...', 'gsxr-777-ai-open-chat'),
                    'testConnection' => __('Test Connection', 'gsxr-777-ai-open-chat'),
                    'connectionFailed' => __('Connection test failed', 'gsxr-777-ai-open-chat'),
                    'deleteConfirm' => __('Are you sure you want to delete this file?', 'gsxr-777-ai-open-chat'),
                    'deleteFailed' => __('Failed to delete file', 'gsxr-777-ai-open-chat'),
                    'requestFailed' => __('Request failed', 'gsxr-777-ai-open-chat')
                )
            ));
        }
    }

    public function handle_ajax_test_connection() {
        try {
            check_ajax_referer('gsxr_777_test_connection', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777-ai-open-chat'));
            }
            
            $api_base_url = $this->sanitize_api_base_url(
                isset($_POST['api_base_url']) ? esc_url_raw(wp_unslash($_POST['api_base_url'])) : ''
            );
            $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
            $api_key_masked = isset($_POST['api_key_masked']) ? sanitize_text_field(wp_unslash($_POST['api_key_masked'])) : '0';
            $api_model = isset($_POST['api_model']) ? sanitize_text_field(wp_unslash($_POST['api_model'])) : '';
            $api_project_id = isset($_POST['api_project_id']) ? sanitize_text_field(wp_unslash($_POST['api_project_id'])) : '';

            $api = new GSXR_777_API();
            $stored_encrypted_key = get_option('gsxr_777_api_key', '');
            $is_masked_value = $api_key_masked === '1' && $api_key === $this->api_key_mask;

            // If key is empty (or matches stored encrypted blob), use saved decrypted key.
            if (empty($api_key) || $is_masked_value || (!empty($stored_encrypted_key) && hash_equals($stored_encrypted_key, $api_key))) {
                $api_key = $api->decrypt_api_key($stored_encrypted_key);
            }

            if (empty($api_base_url) || empty($api_key) || empty($api_model)) {
                throw new Exception(__('Missing required fields', 'gsxr-777-ai-open-chat'));
            }

            $result = $api->test_connection($api_base_url, $api_key, $api_model, $api_project_id);
            
            if ($result['success']) {
                wp_send_json_success(array('message' => __('Connection successful!', 'gsxr-777-ai-open-chat')));
            } else {
                wp_send_json_error(array('message' => $result['error']));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (test_connection): ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred. Please check the logs.', 'gsxr-777-ai-open-chat')));
        }
    }

    public function handle_ajax_save_knowledge_file() {
        try {
            check_ajax_referer('gsxr_777_knowledge', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777-ai-open-chat'));
            }
            
            $filename = isset($_POST['filename']) ? sanitize_file_name(wp_unslash($_POST['filename'])) : '';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Markdown is validated and stored as text by GSXR_777_Knowledge.
            $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
            
            // Validate filename
            if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777-ai-open-chat'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777-ai-open-chat'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $result = $knowledge->save_file($filename, $content);
            
            if ($result) {
                wp_send_json_success(array('message' => __('File saved successfully!', 'gsxr-777-ai-open-chat')));
            } else {
                throw new Exception(__('Error saving file!', 'gsxr-777-ai-open-chat'));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (save_knowledge_file): ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function handle_ajax_delete_knowledge_file() {
        try {
            check_ajax_referer('gsxr_777_knowledge', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777-ai-open-chat'));
            }
            
            $filename = isset($_POST['filename']) ? sanitize_text_field(wp_unslash($_POST['filename'])) : '';
            
            // Validate filename format
            if (empty($filename)) {
                throw new Exception(__('Filename is required', 'gsxr-777-ai-open-chat'));
            }
            
            // Check extension
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777-ai-open-chat'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777-ai-open-chat'));
            }
            
            // Validate filename characters
            if (!preg_match('/^[a-zA-Z0-9._-]+\.md$/', $filename)) {
                throw new Exception(__('Invalid filename format', 'gsxr-777-ai-open-chat'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $result = $knowledge->delete_file($filename);
            
            if ($result) {
                wp_send_json_success(array('message' => __('File deleted successfully!', 'gsxr-777-ai-open-chat')));
            } else {
                throw new Exception(__('Error deleting file!', 'gsxr-777-ai-open-chat'));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (delete_knowledge_file): ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function handle_ajax_get_knowledge_file() {
        try {
            check_ajax_referer('gsxr_777_knowledge', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777-ai-open-chat'));
            }
            
            $filename = isset($_POST['filename']) ? sanitize_file_name(wp_unslash($_POST['filename'])) : '';
            
            // Validate filename
            if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777-ai-open-chat'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777-ai-open-chat'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $content = $knowledge->get_file_content($filename);
            
            if ($content !== false) {
                wp_send_json_success(array('content' => $content));
            } else {
                throw new Exception(__('File not found', 'gsxr-777-ai-open-chat'));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (get_knowledge_file): ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function handle_ajax_rebuild_knowledge_index() {
        check_ajax_referer('gsxr_777_knowledge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                array('message' => __('Insufficient permissions', 'gsxr-777-ai-open-chat')),
                403
            );
        }

        $knowledge = new GSXR_777_Knowledge();
        $knowledge->rebuild_index();
        $stats = $knowledge->get_index_stats();

        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: 1: Number of indexed sources, 2: Number of indexed text chunks. */
                __('Index rebuilt: %1$d sources, %2$d chunks.', 'gsxr-777-ai-open-chat'),
                $stats['sources'],
                $stats['chunks']
            )
        ));
    }

    // Sanitization callbacks
    public function sanitize_api_key($key) {
        $stored_key = get_option('gsxr_777_api_key', '');
        $key = sanitize_text_field(wp_unslash((string) $key));

        if ($key === '' || $key === $this->api_key_mask) {
            return $stored_key;
        }

        if ($stored_key !== '' && hash_equals($stored_key, $key)) {
            return $stored_key;
        }

        $api = new GSXR_777_API();
        $encrypted = $api->encrypt_api_key($key);
        if ($encrypted === '') {
            add_settings_error(
                'gsxr_777_api_key',
                'gsxr_777_api_key_encryption_failed',
                __('The API key could not be encrypted.', 'gsxr-777-ai-open-chat')
            );
            return $stored_key;
        }

        return $encrypted;
    }

    public function sanitize_api_base_url($url) {
        $url = esc_url_raw(trim((string) $url));
        $api = new GSXR_777_API();
        return $api->is_allowed_api_base_url($url) ? untrailingslashit($url) : '';
    }

    public function sanitize_max_tokens($tokens) {
        return max(1, min(32000, absint($tokens)));
    }

    public function sanitize_retention_days($days) {
        return max(1, min(3650, absint($days)));
    }

    public function sanitize_boolean($value) {
        return empty($value) ? 0 : 1;
    }

    public function sanitize_theme_mode($mode) {
        $mode = sanitize_key($mode);
        return in_array($mode, array('auto', 'light', 'dark'), true) ? $mode : 'auto';
    }

    public function sanitize_temperature($temp) {
        $temp = floatval($temp);
        return max(0, min(2, $temp));
    }

    public function sanitize_top_p($top_p) {
        $top_p = floatval($top_p);
        return max(0, min(1, $top_p));
    }

    public function sanitize_penalty($penalty) {
        $penalty = floatval($penalty);
        return max(-2, min(2, $penalty));
    }

    public function sanitize_history_limit($limit) {
        $limit = intval($limit);
        return max(1, min(100, $limit));
    }

    public function sanitize_personality($personality) {
        $allowed = array('friendly', 'sarcastic', 'pragmatic', 'funny', 'formal', 'empathetic', 'expert', 'concise');
        $personality = sanitize_key($personality);
        return in_array($personality, $allowed, true) ? $personality : 'friendly';
    }

    public function sanitize_position($position) {
        $allowed = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
        $position = sanitize_key($position);
        return in_array($position, $allowed, true) ? $position : 'bottom-right';
    }

    public function sanitize_gradient_angle($angle) {
        $angle = intval($angle);
        return max(0, min(360, $angle));
    }

    public function sanitize_font_family($font_family) {
        $font_family = sanitize_text_field($font_family);
        $font_family = preg_replace('/[^a-zA-Z0-9,\s"\'\-]/', '', $font_family);

        if ($font_family === '') {
            return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        }

        return $font_family;
    }

    public function sanitize_color($color, $default = '#2563eb') {
        $sanitized = sanitize_hex_color($color);
        return $sanitized ? $sanitized : $default;
    }

    public function sanitize_widget_size($size) {
        $size = intval($size);
        return max(300, min(900, $size));
    }

    private function get_post_value($key, $default = '') {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers verify their form nonce and apply field-specific sanitization.
        return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : $default;
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
}
