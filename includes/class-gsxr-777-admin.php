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
            __('GSXR-777 AI Chat', 'gsxr-777'),
            __('AI Chat', 'gsxr-777'),
            'manage_options',
            'gsxr-777-ai-chat',
            array($this, 'render_api_settings_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('API Settings', 'gsxr-777'),
            __('API Settings', 'gsxr-777'),
            'manage_options',
            'gsxr-777-ai-chat',
            array($this, 'render_api_settings_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Knowledge Base', 'gsxr-777'),
            __('Knowledge Base', 'gsxr-777'),
            'manage_options',
            'gsxr-777-knowledge',
            array($this, 'render_knowledge_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Widget Settings', 'gsxr-777'),
            __('Widget Settings', 'gsxr-777'),
            'manage_options',
            'gsxr-777-widget',
            array($this, 'render_widget_settings_page')
        );

        add_submenu_page(
            'gsxr-777-ai-chat',
            __('Statistics', 'gsxr-777'),
            __('Statistics', 'gsxr-777'),
            'manage_options',
            'gsxr-777-stats',
            array($this, 'render_statistics_page')
        );
    }

    public function register_settings() {
        // API Settings
        register_setting('gsxr_777_api_settings', 'gsxr_777_api_base_url', array(
            'sanitize_callback' => 'esc_url_raw'
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
            'sanitize_callback' => 'absint'
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
    }

    public function render_api_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('gsxr_777_api_settings');
            
            update_option('gsxr_777_api_base_url', esc_url_raw($_POST['gsxr_777_api_base_url']));
            
            // Encrypt API key before saving
            $api = new GSXR_777_API();
            $stored_encrypted_key = get_option('gsxr_777_api_key', '');
            $submitted_api_key = isset($_POST['gsxr_777_api_key']) ? sanitize_text_field($_POST['gsxr_777_api_key']) : '';
            $is_masked_value = isset($_POST['gsxr_777_api_key_masked']) &&
                sanitize_text_field($_POST['gsxr_777_api_key_masked']) === '1' &&
                $submitted_api_key === $this->api_key_mask;

            // Keep existing encrypted key when password field is left empty.
            if ($submitted_api_key !== '' && !$is_masked_value) {
                // Guard against accidental re-encryption of already encrypted value.
                if (empty($stored_encrypted_key) || !hash_equals($stored_encrypted_key, $submitted_api_key)) {
                    $encrypted_key = $api->encrypt_api_key($submitted_api_key);
                    update_option('gsxr_777_api_key', $encrypted_key);
                }
            }
            
            update_option('gsxr_777_api_model', sanitize_text_field($_POST['gsxr_777_api_model']));
            update_option('gsxr_777_api_project_id', sanitize_text_field($_POST['gsxr_777_api_project_id'] ?? ''));
            update_option('gsxr_777_api_temperature', $this->sanitize_temperature($_POST['gsxr_777_api_temperature']));
            update_option('gsxr_777_api_max_tokens', absint($_POST['gsxr_777_api_max_tokens']));
            update_option('gsxr_777_api_personality', $this->sanitize_personality($_POST['gsxr_777_api_personality'] ?? 'friendly'));
            update_option('gsxr_777_api_top_p', $this->sanitize_top_p($_POST['gsxr_777_api_top_p'] ?? 1));
            update_option('gsxr_777_api_frequency_penalty', $this->sanitize_penalty($_POST['gsxr_777_api_frequency_penalty'] ?? 0));
            update_option('gsxr_777_api_presence_penalty', $this->sanitize_penalty($_POST['gsxr_777_api_presence_penalty'] ?? 0));
            update_option('gsxr_777_api_history_limit', $this->sanitize_history_limit($_POST['gsxr_777_api_history_limit'] ?? 20));
            update_option('gsxr_777_api_system_instructions', sanitize_textarea_field($_POST['gsxr_777_api_system_instructions'] ?? ''));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'gsxr-777') . '</p></div>';
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

        $personality_options = array(
            'friendly' => __('Friendly', 'gsxr-777'),
            'sarcastic' => __('Sarcastic', 'gsxr-777'),
            'pragmatic' => __('Pragmatic', 'gsxr-777'),
            'funny' => __('Funny', 'gsxr-777'),
            'formal' => __('Formal', 'gsxr-777'),
            'empathetic' => __('Empathetic', 'gsxr-777'),
            'expert' => __('Expert', 'gsxr-777'),
            'concise' => __('Concise', 'gsxr-777')
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
        <div class="wrap">
            <h1><?php _e('API Settings', 'gsxr-777'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('gsxr_777_api_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('AI Provider', 'gsxr-777'); ?></th>
                        <td>
                            <select id="ai_provider" onchange="updateApiUrl(); updateProviderLinks()">
                                <option value=""><?php _e('Select Provider', 'gsxr-777'); ?></option>
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
                                <?php _e('Choose your AI provider', 'gsxr-777'); ?><br />
                                <span id="provider_links" style="margin-top: 8px; display: block;">
                                    <a id="key_link" href="#" target="_blank" style="margin-right: 15px; display: none;">
                                        🔑 <?php _e('Get API Key', 'gsxr-777'); ?>
                                    </a>
                                    <a id="doc_link" href="#" target="_blank" style="display: none;">
                                        📚 <?php _e('API Documentation', 'gsxr-777'); ?>
                                    </a>
                                </span>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('API Base URL', 'gsxr-777'); ?></th>
                        <td>
                            <input type="url" id="gsxr_777_api_base_url" name="gsxr_777_api_base_url" 
                                   value="<?php echo esc_attr($api_base_url); ?>" class="regular-text" required />
                            <p class="description"><?php _e('Base URL for the AI API endpoint', 'gsxr-777'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('API Key', 'gsxr-777'); ?></th>
                        <td>
                            <input type="password" name="gsxr_777_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="new-password" />
                            <input type="hidden" name="gsxr_777_api_key_masked" value="<?php echo $has_api_key ? '1' : '0'; ?>" />
                            <p class="description">
                                <?php _e('Your API key (stored encrypted). Leave blank to keep current key.', 'gsxr-777'); ?>
                                <?php if ($has_api_key): ?>
                                    <br /><span style="color:#1d7f1d;"><?php _e('Current key is saved.', 'gsxr-777'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Model', 'gsxr-777'); ?></th>
                        <td>
                            <input type="text" name="gsxr_777_api_model" 
                                   value="<?php echo esc_attr($api_model); ?>" class="regular-text" required />
                            <p class="description"><?php _e('AI model name (e.g., gpt-4o-mini, claude-3-sonnet)', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Project / Folder ID', 'gsxr-777'); ?></th>
                        <td>
                            <input type="text" name="gsxr_777_api_project_id"
                                   value="<?php echo esc_attr($api_project_id); ?>" class="regular-text" />
                            <p class="description"><?php _e('Optional for most providers. Required for Yandex Responses API as OpenAI-Project header value (example: b1gad9eo3bbmgfl7od3h).', 'gsxr-777'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Temperature', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_temperature" 
                                   value="<?php echo esc_attr($api_temperature); ?>" 
                                   min="0" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php _e('Controls randomness (0.0 = deterministic, 2.0 = very random)', 'gsxr-777'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Max Tokens', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_max_tokens" 
                                   value="<?php echo esc_attr($api_max_tokens); ?>" 
                                   min="1" max="4000" class="small-text" />
                            <p class="description"><?php _e('Maximum tokens in response', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Assistant Personality', 'gsxr-777'); ?></th>
                        <td>
                            <select name="gsxr_777_api_personality">
                                <?php foreach ($personality_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($api_personality, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Defines the tone of model responses (friendly, sarcastic, pragmatic, funny, etc.).', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Top P', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_top_p"
                                   value="<?php echo esc_attr($api_top_p); ?>"
                                   min="0" max="1" step="0.05" class="small-text" />
                            <p class="description"><?php _e('Nucleus sampling threshold (0.0-1.0). Lower values make output more focused.', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Frequency Penalty', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_frequency_penalty"
                                   value="<?php echo esc_attr($api_frequency_penalty); ?>"
                                   min="-2" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php _e('Reduces repetitive words or phrases (-2.0 to 2.0).', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Presence Penalty', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_presence_penalty"
                                   value="<?php echo esc_attr($api_presence_penalty); ?>"
                                   min="-2" max="2" step="0.1" class="small-text" />
                            <p class="description"><?php _e('Encourages introducing new topics (-2.0 to 2.0).', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Conversation Context Size', 'gsxr-777'); ?></th>
                        <td>
                            <input type="number" name="gsxr_777_api_history_limit"
                                   value="<?php echo esc_attr($api_history_limit); ?>"
                                   min="1" max="100" class="small-text" />
                            <p class="description"><?php _e('How many previous messages are sent to the model as context.', 'gsxr-777'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Additional System Instructions', 'gsxr-777'); ?></th>
                        <td>
                            <textarea name="gsxr_777_api_system_instructions" rows="4" class="large-text"><?php echo esc_textarea($api_system_instructions); ?></textarea>
                            <p class="description"><?php _e('Extra rules for the assistant behavior. Applies to every request.', 'gsxr-777'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php _e('Save Settings', 'gsxr-777'); ?>" />
                    <button type="button" id="test_connection" class="button">
                        <?php _e('Test Connection', 'gsxr-777'); ?>
                    </button>
                </p>
            </form>
            
            <div id="test_result" style="margin-top: 20px;"></div>
        </div>
        
        <script>
        function updateApiUrl() {
            const provider = document.getElementById('ai_provider');
            const urlField = document.getElementById('gsxr_777_api_base_url');
            if (provider.value) {
                urlField.value = provider.value;
            }
        }

        function updateProviderLinks() {
            const provider = document.getElementById('ai_provider');
            const selectedOption = provider.options[provider.selectedIndex];
            const keyLink = document.getElementById('key_link');
            const docLink = document.getElementById('doc_link');

            if (provider.value) {
                const keyUrl = selectedOption.getAttribute('data-key-url');
                const docUrl = selectedOption.getAttribute('data-doc-url');

                keyLink.href = keyUrl;
                keyLink.style.display = 'inline';
                
                docLink.href = docUrl;
                docLink.style.display = 'inline';
            } else {
                keyLink.style.display = 'none';
                docLink.style.display = 'none';
            }
        }

        // Init links on page load
        window.addEventListener('DOMContentLoaded', updateProviderLinks);
        
        document.getElementById('test_connection').addEventListener('click', function() {
            const button = this;
            const result = document.getElementById('test_result');
            
            button.disabled = true;
            button.textContent = '<?php _e('Testing...', 'gsxr-777'); ?>';
            
            const data = new FormData();
            data.append('action', 'gsxr_777_test_connection');
            data.append('nonce', '<?php echo wp_create_nonce('gsxr_777_test_connection'); ?>');
            data.append('api_base_url', document.querySelector('[name="gsxr_777_api_base_url"]').value);
            data.append('api_key', document.querySelector('[name="gsxr_777_api_key"]').value);
            data.append('api_key_masked', document.querySelector('[name="gsxr_777_api_key_masked"]').value);
            data.append('api_model', document.querySelector('[name="gsxr_777_api_model"]').value);
            data.append('api_project_id', document.querySelector('[name="gsxr_777_api_project_id"]').value);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    result.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error"><p>' + data.data.message + '</p></div>';
                }
            })
            .catch(error => {
                result.innerHTML = '<div class="notice notice-error"><p><?php _e('Connection test failed', 'gsxr-777'); ?></p></div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '<?php _e('Test Connection', 'gsxr-777'); ?>';
            });
        });
        </script>
        <?php
    }

    public function render_knowledge_page() {
        $knowledge = new GSXR_777_Knowledge();
        $files = $knowledge->get_all_files();
        
        if (isset($_POST['save_file'])) {
            check_admin_referer('gsxr_777_knowledge');
            
            $filename = sanitize_file_name($_POST['filename']);
            $content = wp_unslash($_POST['content']);
            
            if ($knowledge->save_file($filename, $content)) {
                echo '<div class="notice notice-success"><p>' . __('File saved successfully!', 'gsxr-777') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error saving file!', 'gsxr-777') . '</p></div>';
            }
            
            $files = $knowledge->get_all_files(); // Refresh file list
        }
        
        // Render Knowledge page HTML
        ?>
            <h1><?php _e('Knowledge Base', 'gsxr-777'); ?></h1>
            
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <h2><?php _e('Files', 'gsxr-777'); ?></h2>
                    <div id="file_list">
                        <?php if (empty($files)): ?>
                            <p><?php _e('No knowledge files found.', 'gsxr-777'); ?></p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($files as $file): ?>
                                    <li>
                                        <a href="#" onclick="loadFile('<?php echo esc_js($file['name']); ?>')">
                                            <?php echo esc_html($file['name']); ?>
                                        </a>
                                        <small>(<?php echo esc_html($file['modified']); ?>)</small>
                                        <button type="button" onclick="deleteFile('<?php echo esc_js($file['name']); ?>')" 
                                                class="button button-small" style="margin-left: 10px;">
                                            <?php _e('Delete', 'gsxr-777'); ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" onclick="newFile()" class="button">
                        <?php _e('New File', 'gsxr-777'); ?>
                    </button>
                </div>
                
                <div style="flex: 2;">
                    <form method="post" action="">
                        <?php wp_nonce_field('gsxr_777_knowledge'); ?>
                        
                        <p>
                            <label for="filename"><?php _e('Filename:', 'gsxr-777'); ?></label>
                            <input type="text" id="filename" name="filename" class="regular-text" 
                                   placeholder="example.md" required />
                        </p>
                        
                        <p>
                            <label for="content"><?php _e('Content:', 'gsxr-777'); ?></label>
                            <textarea id="content" name="content" rows="20" class="large-text code" 
                                      placeholder="# Your markdown content here..."></textarea>
                        </p>
                        
                        <p class="submit">
                            <input type="submit" name="save_file" class="button-primary" 
                                   value="<?php _e('Save File', 'gsxr-777'); ?>" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function loadFile(filename) {
            document.getElementById('filename').value = filename;
            
            const data = new FormData();
            data.append('action', 'gsxr_777_get_knowledge_file');
            data.append('nonce', '<?php echo wp_create_nonce('gsxr_777_knowledge'); ?>');
            data.append('filename', filename);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('content').value = data.data.content;
                }
            });
        }
        
        function newFile() {
            document.getElementById('filename').value = '';
            document.getElementById('content').value = '';
        }
        
        function deleteFile(filename) {
            if (!confirm('<?php _e('Are you sure you want to delete this file?', 'gsxr-777'); ?>')) {
                return;
            }

            const data = new FormData();
            data.append('action', 'gsxr_777_delete_knowledge_file');
            data.append('nonce', '<?php echo wp_create_nonce('gsxr_777_knowledge'); ?>');
            data.append('filename', filename);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to refresh file list
                    location.reload();
                } else {
                    alert('Error: ' + (data.data?.message || 'Failed to delete file'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting file');
            });
        }
        </script>
        <?php
    }

    public function render_widget_settings_page() {
        $allowed_tabs = array('general', 'appearance');
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'general';
        }

        if (isset($_POST['submit'])) {
            check_admin_referer('gsxr_777_widget_settings');

            $settings_section = isset($_POST['gsxr_777_widget_settings_section']) ? sanitize_key($_POST['gsxr_777_widget_settings_section']) : 'general';
            if (!in_array($settings_section, $allowed_tabs, true)) {
                $settings_section = 'general';
            }

            if ($settings_section === 'appearance') {
                update_option('gsxr_777_widget_primary_color', $this->sanitize_color($_POST['gsxr_777_widget_primary_color'] ?? '', '#2563eb'));
                update_option('gsxr_777_widget_secondary_color', $this->sanitize_color($_POST['gsxr_777_widget_secondary_color'] ?? '', '#1d4ed8'));
                update_option('gsxr_777_widget_gradient_angle', $this->sanitize_gradient_angle($_POST['gsxr_777_widget_gradient_angle'] ?? 135));
                update_option('gsxr_777_widget_chat_background_color', $this->sanitize_color($_POST['gsxr_777_widget_chat_background_color'] ?? '', '#ffffff'));
                update_option('gsxr_777_widget_messages_background_color', $this->sanitize_color($_POST['gsxr_777_widget_messages_background_color'] ?? '', '#ffffff'));
                update_option('gsxr_777_widget_assistant_background_color', $this->sanitize_color($_POST['gsxr_777_widget_assistant_background_color'] ?? '', '#f0f0f0'));
                update_option('gsxr_777_widget_assistant_text_color', $this->sanitize_color($_POST['gsxr_777_widget_assistant_text_color'] ?? '', '#333333'));
                update_option('gsxr_777_widget_user_text_color', $this->sanitize_color($_POST['gsxr_777_widget_user_text_color'] ?? '', '#ffffff'));
                update_option('gsxr_777_widget_input_background_color', $this->sanitize_color($_POST['gsxr_777_widget_input_background_color'] ?? '', '#ffffff'));
                update_option('gsxr_777_widget_input_text_color', $this->sanitize_color($_POST['gsxr_777_widget_input_text_color'] ?? '', '#333333'));
                update_option('gsxr_777_widget_font_family', $this->sanitize_font_family($_POST['gsxr_777_widget_font_family'] ?? ''));
                update_option('gsxr_777_widget_chat_font_family', $this->sanitize_font_family($_POST['gsxr_777_widget_chat_font_family'] ?? ''));
            } else {
                // Validate welcome message length (max 500 chars)
                $welcome = $_POST['gsxr_777_widget_welcome'] ?? '';
                if (strlen($welcome) > 500) {
                    echo '<div class="notice notice-error"><p>' .
                         sprintf(__('Welcome message is too long. Maximum 500 characters, current: %d', 'gsxr-777'), strlen($welcome)) .
                         '</p></div>';
                    return;
                }

                // Validate title length (max 100 chars)
                $title = $_POST['gsxr_777_widget_title'] ?? '';
                if (strlen($title) > 100) {
                    echo '<div class="notice notice-error"><p>' .
                         sprintf(__('Title is too long. Maximum 100 characters, current: %d', 'gsxr-777'), strlen($title)) .
                         '</p></div>';
                    return;
                }

                // Validate widget dimensions
                $width = intval($_POST['gsxr_777_widget_width'] ?? 400);
                $height = intval($_POST['gsxr_777_widget_height'] ?? 600);

                if ($width < 300 || $width > 800 || $height < 400 || $height > 900) {
                    echo '<div class="notice notice-error"><p>' .
                         __('Invalid widget dimensions. Width must be 300-800px and height 400-900px.', 'gsxr-777') .
                         '</p></div>';
                    return;
                }

                update_option('gsxr_777_widget_title', sanitize_text_field($title));
                update_option('gsxr_777_widget_welcome', sanitize_textarea_field($welcome));
                update_option('gsxr_777_widget_placeholder', sanitize_text_field($_POST['gsxr_777_widget_placeholder']));
                update_option('gsxr_777_widget_position', $this->sanitize_position($_POST['gsxr_777_widget_position']));
                update_option('gsxr_777_widget_width', $width);
                update_option('gsxr_777_widget_height', $height);
            }

            $active_tab = $settings_section;
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'gsxr-777') . '</p></div>';
        }

        $widget_title = get_option('gsxr_777_widget_title', __('Chat', 'gsxr-777'));
        $widget_welcome = get_option('gsxr_777_widget_welcome', __('Hello! How can I help you?', 'gsxr-777'));
        $widget_placeholder = get_option('gsxr_777_widget_placeholder', __('Type your message...', 'gsxr-777'));
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
        $widget_width = get_option('gsxr_777_widget_width', 400);
        $widget_height = get_option('gsxr_777_widget_height', 600);
        $tabs_base_url = admin_url('admin.php?page=gsxr-777-widget');
        ?>
        <div class="wrap">
            <h1><?php _e('Widget Settings', 'gsxr-777'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg('tab', 'general', $tabs_base_url)); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Основные', 'gsxr-777'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'appearance', $tabs_base_url)); ?>" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Внешний вид', 'gsxr-777'); ?>
                </a>
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('gsxr_777_widget_settings'); ?>
                <input type="hidden" name="gsxr_777_widget_settings_section" value="<?php echo esc_attr($active_tab); ?>" />
                
                <table class="form-table">
                    <?php if ($active_tab === 'appearance'): ?>
                        <tr>
                            <th scope="row"><?php _e('Primary Color', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_primary_color"
                                       value="<?php echo esc_attr($widget_primary_color); ?>" />
                                <p class="description"><?php _e('Main accent color for buttons, links and focus states.', 'gsxr-777'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Secondary Gradient Color', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_secondary_color"
                                       value="<?php echo esc_attr($widget_secondary_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Gradient Angle', 'gsxr-777'); ?></th>
                            <td>
                                <input type="number" name="gsxr_777_widget_gradient_angle"
                                       value="<?php echo esc_attr($widget_gradient_angle); ?>"
                                       min="0" max="360" class="small-text" />
                                <p class="description"><?php _e('Used for header/button/message gradients.', 'gsxr-777'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Chat Window Background', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_chat_background_color"
                                       value="<?php echo esc_attr($widget_chat_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Messages Area Background', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_messages_background_color"
                                       value="<?php echo esc_attr($widget_messages_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Assistant Message Background', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_assistant_background_color"
                                       value="<?php echo esc_attr($widget_assistant_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Assistant Message Text', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_assistant_text_color"
                                       value="<?php echo esc_attr($widget_assistant_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('User Message Text', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_user_text_color"
                                       value="<?php echo esc_attr($widget_user_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Input Background', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_input_background_color"
                                       value="<?php echo esc_attr($widget_input_background_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Input Text Color', 'gsxr-777'); ?></th>
                            <td>
                                <input type="color" name="gsxr_777_widget_input_text_color"
                                       value="<?php echo esc_attr($widget_input_text_color); ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Widget Font Family', 'gsxr-777'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_font_family"
                                       value="<?php echo esc_attr($widget_font_family); ?>" class="regular-text" />
                                <p class="description"><?php _e('Example: "Segoe UI", Roboto, Arial, sans-serif', 'gsxr-777'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Chat Font Family', 'gsxr-777'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_chat_font_family"
                                       value="<?php echo esc_attr($widget_chat_font_family); ?>" class="regular-text" />
                                <p class="description"><?php _e('Used in chat messages and input area.', 'gsxr-777'); ?></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th scope="row"><?php _e('Widget Title', 'gsxr-777'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_title"
                                       value="<?php echo esc_attr($widget_title); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Welcome Message', 'gsxr-777'); ?></th>
                            <td>
                                <textarea name="gsxr_777_widget_welcome" rows="3" class="large-text"><?php echo esc_textarea($widget_welcome); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Input Placeholder', 'gsxr-777'); ?></th>
                            <td>
                                <input type="text" name="gsxr_777_widget_placeholder"
                                       value="<?php echo esc_attr($widget_placeholder); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Position', 'gsxr-777'); ?></th>
                            <td>
                                <select name="gsxr_777_widget_position">
                                    <option value="bottom-right" <?php selected($widget_position, 'bottom-right'); ?>>
                                        <?php _e('Bottom Right', 'gsxr-777'); ?>
                                    </option>
                                    <option value="bottom-left" <?php selected($widget_position, 'bottom-left'); ?>>
                                        <?php _e('Bottom Left', 'gsxr-777'); ?>
                                    </option>
                                    <option value="top-right" <?php selected($widget_position, 'top-right'); ?>>
                                        <?php _e('Top Right', 'gsxr-777'); ?>
                                    </option>
                                    <option value="top-left" <?php selected($widget_position, 'top-left'); ?>>
                                        <?php _e('Top Left', 'gsxr-777'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Width (px)', 'gsxr-777'); ?></th>
                            <td>
                                <input type="number" name="gsxr_777_widget_width"
                                       value="<?php echo esc_attr($widget_width); ?>"
                                       min="300" max="800" class="small-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Height (px)', 'gsxr-777'); ?></th>
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
                           value="<?php _e('Save Settings', 'gsxr-777'); ?>" />
                </p>
            </form>
            
            <?php if ($active_tab === 'general'): ?>
                <h2><?php _e('Shortcode Usage', 'gsxr-777'); ?></h2>
                <p><?php _e('Use this shortcode to display the chat widget:', 'gsxr-777'); ?></p>
                <code>[gsxr_777_chat]</code>

                <p><?php _e('With custom parameters:', 'gsxr-777'); ?></p>
                <code>[gsxr_777_chat title="Support" position="bottom-left"]</code>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_statistics_page() {
        $stats = new GSXR_777_Stats();
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30';
        
        $total_sessions = $stats->get_total_sessions($period);
        $total_messages = $stats->get_total_messages($period);
        $avg_messages = $stats->get_average_messages_per_session($period);
        ?>
        <div class="wrap">
            <h1><?php _e('Statistics', 'gsxr-777'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="gsxr-777-stats" />
                <select name="period" onchange="this.form.submit()">
                    <option value="7" <?php selected($period, '7'); ?>><?php _e('Last 7 days', 'gsxr-777'); ?></option>
                    <option value="30" <?php selected($period, '30'); ?>><?php _e('Last 30 days', 'gsxr-777'); ?></option>
                    <option value="90" <?php selected($period, '90'); ?>><?php _e('Last 90 days', 'gsxr-777'); ?></option>
                </select>
            </form>
            
            <div class="gsxr-stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                <div class="gsxr-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('Total Sessions', 'gsxr-777'); ?></h3>
                    <p style="font-size: 2em; margin: 0; color: #2563eb;"><?php echo number_format($total_sessions); ?></p>
                </div>
                
                <div class="gsxr-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('Total Messages', 'gsxr-777'); ?></h3>
                    <p style="font-size: 2em; margin: 0; color: #16a085;"><?php echo number_format($total_messages); ?></p>
                </div>
                
                <div class="gsxr-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('Avg Messages/Session', 'gsxr-777'); ?></h3>
                    <p style="font-size: 2em; margin: 0; color: #e67e22;"><?php echo number_format($avg_messages, 1); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'gsxr-777') !== false) {
            wp_enqueue_style('gsxr-777-admin', GSXR_777_PLUGIN_URL . 'admin-style.css', array(), GSXR_777_VERSION);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'gsxr-777') !== false) {
            wp_enqueue_script('gsxr-777-admin', GSXR_777_PLUGIN_URL . 'admin-script.js', array('jquery'), GSXR_777_VERSION, true);
            wp_localize_script('gsxr-777-admin', 'gsxr777_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gsxr_777_admin_nonce')
            ));
        }
    }

    public function handle_ajax_test_connection() {
        try {
            check_ajax_referer('gsxr_777_test_connection', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777'));
            }
            
            $api_base_url = esc_url_raw($_POST['api_base_url']);
            $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
            $api_key_masked = isset($_POST['api_key_masked']) ? sanitize_text_field($_POST['api_key_masked']) : '0';
            $api_model = sanitize_text_field($_POST['api_model']);
            $api_project_id = isset($_POST['api_project_id']) ? sanitize_text_field($_POST['api_project_id']) : '';

            $api = new GSXR_777_API();
            $stored_encrypted_key = get_option('gsxr_777_api_key', '');
            $is_masked_value = $api_key_masked === '1' && $api_key === $this->api_key_mask;

            // If key is empty (or matches stored encrypted blob), use saved decrypted key.
            if (empty($api_key) || $is_masked_value || (!empty($stored_encrypted_key) && hash_equals($stored_encrypted_key, $api_key))) {
                $api_key = $api->decrypt_api_key($stored_encrypted_key);
            }

            if (empty($api_base_url) || empty($api_key) || empty($api_model)) {
                throw new Exception(__('Missing required fields', 'gsxr-777'));
            }

            $result = $api->test_connection($api_base_url, $api_key, $api_model, $api_project_id);
            
            if ($result['success']) {
                wp_send_json_success(array('message' => __('Connection successful!', 'gsxr-777')));
            } else {
                wp_send_json_error(array('message' => $result['error']));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (test_connection): ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred. Please check the logs.', 'gsxr-777')));
        }
    }

    public function handle_ajax_save_knowledge_file() {
        try {
            check_ajax_referer('gsxr_777_knowledge', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'gsxr-777'));
            }
            
            $filename = sanitize_file_name($_POST['filename']);
            $content = wp_unslash($_POST['content']);
            
            // Validate filename
            if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $result = $knowledge->save_file($filename, $content);
            
            if ($result) {
                wp_send_json_success(array('message' => __('File saved successfully!', 'gsxr-777')));
            } else {
                throw new Exception(__('Error saving file!', 'gsxr-777'));
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
                throw new Exception(__('Insufficient permissions', 'gsxr-777'));
            }
            
            $filename = isset($_POST['filename']) ? sanitize_text_field($_POST['filename']) : '';
            
            // Validate filename format
            if (empty($filename)) {
                throw new Exception(__('Filename is required', 'gsxr-777'));
            }
            
            // Check extension
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777'));
            }
            
            // Validate filename characters
            if (!preg_match('/^[a-zA-Z0-9._-]+\.md$/', $filename)) {
                throw new Exception(__('Invalid filename format', 'gsxr-777'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $result = $knowledge->delete_file($filename);
            
            if ($result) {
                wp_send_json_success(array('message' => __('File deleted successfully!', 'gsxr-777')));
            } else {
                throw new Exception(__('Error deleting file!', 'gsxr-777'));
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
                throw new Exception(__('Insufficient permissions', 'gsxr-777'));
            }
            
            $filename = sanitize_file_name($_POST['filename']);
            
            // Validate filename
            if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
                throw new Exception(__('Only .md files are allowed', 'gsxr-777'));
            }
            
            // Prevent path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                throw new Exception(__('Invalid filename', 'gsxr-777'));
            }
            
            $knowledge = new GSXR_777_Knowledge();
            $content = $knowledge->get_file_content($filename);
            
            if ($content !== false) {
                wp_send_json_success(array('content' => $content));
            } else {
                throw new Exception(__('File not found', 'gsxr-777'));
            }
        } catch (Exception $e) {
            error_log('GSXR-777 AJAX Error (get_knowledge_file): ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    // Sanitization callbacks
    public function sanitize_api_key($key) {
        return sanitize_text_field($key);
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
        return in_array($personality, $allowed, true) ? $personality : 'friendly';
    }

    public function sanitize_position($position) {
        $allowed = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
        return in_array($position, $allowed) ? $position : 'bottom-right';
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
}
