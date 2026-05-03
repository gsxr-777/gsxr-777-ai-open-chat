# GSXR-777 AI Open Chat - Installation Guide

## System Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher (or MariaDB equivalent)
- **Memory:** 128MB minimum (256MB recommended)
- **Disk Space:** 5MB for plugin files

## Installation Methods

### Method 1: WordPress Admin (Recommended)

1. **Download Plugin**
   - Download the plugin zip file
   - Ensure it's named `gsxr-777-ai-open-chat.zip`

2. **Upload via Admin**
   - Log in to your WordPress admin panel
   - Go to `Plugins > Add New`
   - Click `Upload Plugin`
   - Choose the zip file and click `Install Now`
   - Click `Activate Plugin`

### Method 2: FTP Upload

1. **Extract Files**
   - Extract the plugin zip file
   - You should have a folder named `gsxr-777-ai-open-chat`

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the `gsxr-777-ai-open-chat` folder
   - Go to WordPress admin > Plugins
   - Find "GSXR-777 AI Open Chat" and click `Activate`

### Method 3: WordPress CLI

```bash
# Download and install
wp plugin install gsxr-777-ai-open-chat.zip --activate

# Or if uploading manually
wp plugin activate gsxr-777-ai-open-chat
```

## Initial Configuration

### Step 1: Access Plugin Settings

After activation, you'll see a new menu item "AI Chat" in your WordPress admin sidebar.

### Step 2: Configure API Settings

1. **Go to AI Chat > API Settings**

2. **Select AI Provider**
   - Choose from: OpenAI, Claude, Gemini, GigaChat, YandexGPT, Ollama, OpenRouter
   - The API Base URL will auto-populate

3. **Enter API Credentials**
   - **API Key:** Your provider's API key
   - **Model:** Specific model name (e.g., `gpt-4o-mini`, `claude-3-sonnet`)
   - **Temperature:** 0.0-2.0 (controls randomness)
   - **Max Tokens:** Maximum response length

4. **Test Connection**
   - Click "Test Connection" to verify settings
   - You should see a success message

### Step 3: Customize Widget Appearance

1. **Go to AI Chat > Widget Settings**

2. **Configure Display**
   - **Title:** Widget header text
   - **Welcome Message:** Initial greeting
   - **Placeholder:** Input field placeholder
   - **Position:** bottom-right, bottom-left, top-right, top-left
   - **Primary Color:** Widget color scheme
   - **Dimensions:** Width and height in pixels

3. **Save Settings**

### Step 4: Set Up Knowledge Base (Optional)

1. **Go to AI Chat > Knowledge Base**

2. **Create Knowledge Files**
   - Click "New File"
   - Enter filename (must end with .md)
   - Add Markdown content
   - Click "Save File"

3. **Example Knowledge File:**
   ```markdown
   # Company Information
   
   ## About Us
   We are a leading provider of AI solutions.
   
   ## Contact
   - Email: support@example.com
   - Phone: +1 (555) 123-4567
   
   ## Business Hours
   Monday-Friday: 9 AM - 6 PM EST
   ```

## API Provider Setup

### OpenAI Setup

1. **Get API Key**
   - Visit https://platform.openai.com/api-keys
   - Create new secret key
   - Copy the key (starts with `sk-`)

2. **Plugin Configuration**
   - **API Base URL:** `https://api.openai.com/v1`
   - **API Key:** Your OpenAI key
   - **Model:** `gpt-4o-mini` (recommended) or `gpt-3.5-turbo`

### Anthropic Claude Setup

1. **Get API Key**
   - Visit https://console.anthropic.com/
   - Generate API key
   - Copy the key (starts with `sk-ant-`)

2. **Plugin Configuration**
   - **API Base URL:** `https://api.anthropic.com/v1`
   - **API Key:** Your Anthropic key
   - **Model:** `claude-3-sonnet-20240229`

### Google Gemini Setup

1. **Get API Key**
   - Visit https://makersuite.google.com/app/apikey
   - Create API key
   - Copy the key (starts with `AIza`)

2. **Plugin Configuration**
   - **API Base URL:** `https://generativelanguage.googleapis.com/v1beta`
   - **API Key:** Your Google AI key
   - **Model:** `gemini-pro`

### Ollama Setup (Local)

1. **Install Ollama**
   - Download from https://ollama.ai/
   - Install on your server
   - Pull a model: `ollama pull llama2`

2. **Plugin Configuration**
   - **API Base URL:** `http://localhost:11434/v1`
   - **API Key:** `ollama` (any value works)
   - **Model:** `llama2` (or your installed model)

## Widget Display Options

### Automatic Display

By default, the widget appears on all pages in the bottom-right corner.

### Shortcode Usage

Place the widget anywhere using shortcodes:

```php
// Basic usage
[gsxr_777_chat]

// With custom parameters
[gsxr_777_chat title="Support" position="bottom-left" color="#ff6b35"]

// In PHP templates
<?php echo do_shortcode('[gsxr_777_chat]'); ?>
```

### Shortcode Parameters

- `title` - Widget header text
- `position` - bottom-right, bottom-left, top-right, top-left
- `color` - Hex color code (e.g., #ff6b35)
- `width` - Width in pixels (300-800)
- `height` - Height in pixels (400-900)

## Security Configuration

### Rate Limiting

1. **Go to AI Chat > API Settings**
2. **Configure Limits**
   - **Requests per minute:** Default 10
   - **Time window:** Default 60 seconds

### Privacy Settings

Configure what page information is sent to AI:

```php
// In your theme's functions.php
add_filter('gsxr_777_page_context', function($context) {
    // Remove sensitive information
    if (is_admin() || is_user_logged_in()) {
        $context['content'] = '[Private Page]';
    }
    return $context;
});
```

## Troubleshooting

### Common Issues

1. **Widget Not Appearing**
   - Check if plugin is activated
   - Verify no JavaScript errors in browser console
   - Check if theme supports wp_footer()

2. **API Connection Failed**
   - Verify API key is correct
   - Check API base URL format
   - Ensure server can make external HTTP requests
   - Check firewall/proxy settings

3. **Chat Not Responding**
   - Test API connection in settings
   - Check browser network tab for errors
   - Verify nonce and AJAX URLs

4. **Database Errors**
   - Check WordPress database permissions
   - Verify MySQL version compatibility
   - Check error logs for specific issues

### Debug Mode

Enable WordPress debug mode to see detailed errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for error messages.

### Performance Optimization

1. **Caching**
   - Knowledge base content is cached
   - API responses can be cached with filters

2. **Database Cleanup**
   - Old chat sessions auto-cleanup after 90 days
   - Configure in plugin settings

3. **Rate Limiting**
   - Adjust limits based on your traffic
   - Monitor usage in Statistics page

## Uninstallation

### Clean Uninstall

1. **Configure Data Deletion**
   - Go to AI Chat > API Settings
   - Check "Delete all data on uninstall"
   - Save settings

2. **Deactivate and Delete**
   - Go to Plugins page
   - Deactivate plugin
   - Click "Delete"

### Manual Cleanup

If needed, manually remove:

```sql
-- Database tables
DROP TABLE IF EXISTS wp_gsxr777_sessions;
DROP TABLE IF EXISTS wp_gsxr777_messages;
DROP TABLE IF EXISTS wp_gsxr777_security_log;

-- Options
DELETE FROM wp_options WHERE option_name LIKE 'gsxr_777_%';
```

## Support

### Getting Help

1. **Documentation**
   - Check plugin admin pages for built-in help
   - Review this installation guide

2. **Support Channels**
   - WordPress.org support forums
   - GitHub issues: https://github.com/gmen1057/gsxr-777-ai-open-chat

3. **Before Asking for Help**
   - Enable debug mode
   - Check error logs
   - Test with default theme
   - Disable other plugins temporarily

### Reporting Issues

When reporting issues, include:
- WordPress version
- PHP version
- Plugin version
- Error messages
- Steps to reproduce
- Browser and device information

## Updates

### Automatic Updates

The plugin supports WordPress automatic updates. You'll be notified in your admin dashboard when updates are available.

### Manual Updates

1. Download new version
2. Deactivate current plugin
3. Upload new files (overwrite old ones)
4. Reactivate plugin
5. Check settings and test functionality

### Backup Before Updates

Always backup your site before updating:
- Database backup
- Files backup
- Test on staging site first

## Advanced Configuration

### Custom Hooks

```php
// Modify system prompt
add_filter('gsxr_777_system_prompt', function($prompt, $context) {
    return $prompt . "\n\nAdditional instructions here.";
}, 10, 2);

// Custom widget configuration
add_filter('gsxr_777_widget_config', function($config) {
    $config['customOption'] = 'value';
    return $config;
});

// Before chat response
add_action('gsxr_777_before_chat_response', function($message, $session_id) {
    // Log or modify before AI response
}, 10, 2);
```

### Multisite Support

The plugin works on WordPress multisite:
- Activate network-wide or per-site
- Each site has independent settings
- Shared knowledge base possible with custom code

This completes the installation and configuration guide for GSXR-777 AI Open Chat plugin.