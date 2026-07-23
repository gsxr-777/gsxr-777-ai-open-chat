# WordPress Plugin Submission Guide

## Plugin Information

**Plugin Name:** GSXR-777 AI Open Chat  
**Version:** 1.4.0
**Author:** GSXR-777  
**License:** MIT  
**WordPress Compatibility:** 5.0 - 7.0
**PHP Compatibility:** 7.4+  

## Description

GSXR-777 AI Open Chat is a universal AI chatbot plugin for WordPress that supports multiple AI providers including OpenAI, Claude, Gemini, and others. It provides intelligent customer support, lead generation, and user engagement capabilities with extensive customization options.

## Key Features

- **Universal AI Support** - OpenAI, Claude, Gemini, GigaChat, YandexGPT, Ollama
- **Page Context Awareness** - Bot understands current page content
- **Database Mini-RAG** - Relevant chunks from WordPress content and custom Markdown documents
- **Multilingual Support** - English and Russian built-in
- **Security Features** - Signed sessions, atomic rate limiting, authenticated API-key encryption
- **Customizable Widget** - Colors, position, size, messages
- **Statistics Dashboard** - Usage tracking and analytics
- **Mobile Responsive** - Optimized for all devices

## Files Structure

```
gsxr-777-ai-open-chat/
├── gsxr-777-ai-open-chat.php              # Main plugin file
├── includes/                              # Core, API, REST, RAG, security and statistics
├── public/js/widget.js                    # Accessible frontend widget
├── public/css/widget.css                  # Responsive widget styles
├── admin/js/admin-script.js               # Admin interactions
├── admin/css/admin-style.css              # Admin styles
├── languages/                             # Translation template and Russian catalog
├── tests/                                 # WordPress integration tests
├── composer.json                          # Development tooling
├── readme.txt                             # WordPress readme
└── WORDPRESS_SUBMISSION.md                # This file
```

## Installation Instructions

1. Download the plugin files
2. Create a zip file named `gsxr-777-ai-open-chat.zip`
3. Upload via WordPress admin or extract to `/wp-content/plugins/`
4. Activate the plugin
5. Configure API settings in AI Chat menu

## Configuration Steps

### 1. API Setup
- Go to AI Chat > API Settings
- Select your AI provider (OpenAI, Claude, Gemini, etc.)
- Enter your API key
- Test the connection

### 2. Widget Customization
- Go to AI Chat > Widget Settings
- Customize colors, position, and messages
- Preview changes in real-time

### 3. Knowledge Base (Optional)
- Go to AI Chat > Knowledge Base
- Create Markdown documents with custom content
- Rebuild the index to include all published public posts and pages
- The AI receives only the chunks relevant to the visitor's question

### 4. Display Widget
- Widget appears automatically on all pages
- Use shortcode `[gsxr_777_chat]` for specific placement
- Customize per-page with shortcode parameters

## Security Features

- **Input Sanitization** - All user inputs are properly sanitized
- **Nonce Verification** - CSRF protection on all forms
- **Rate Limiting** - Prevents API abuse
- **Signed Sessions** - History endpoints require a server-issued HMAC token
- **Prompt Isolation** - Site content is marked as untrusted reference data
- **Encrypted Storage** - API keys use authenticated AES-256-GCM encryption

## Privacy Compliance

- **Privacy Controls** - Control page context and diagnostic metadata
- **Persistent Sessions** - The browser stores only signed session credentials; messages remain in WordPress
- **Visitor Deletion** - Visitors can delete their own conversation
- **Data Retention** - Configurable cleanup of old data

## API Providers Supported

### OpenAI
- GPT-4, GPT-3.5-turbo, GPT-4o-mini
- Standard OpenAI API format

### Anthropic Claude
- Claude 3 Sonnet, Haiku, Opus
- Native Anthropic API integration

### Google Gemini
- Gemini Pro, Gemini Flash
- Google AI Studio API

### Other Providers
- GigaChat (Sber)
- YandexGPT
- Ollama (local models)
- OpenRouter
- Any OpenAI-compatible API

## Shortcode Usage

Basic usage:
```
[gsxr_777_chat]
```

With parameters:
```
[gsxr_777_chat title="Support" color="#ff6b35" width="450" height="650"]
```

## Hooks and Filters

### Filters
- `gsxr_777_ai_request_timeout` - Provider HTTP timeout
- `gsxr_777_browser_request_timeout` - Browser-side request timeout
- `gsxr_777_rag_chunk_limit` - Number of retrieved chunks
- `gsxr_777_rag_max_characters` - Maximum retrieved context size
- `gsxr_777_history_max_characters` - Maximum conversation-history context size
- `gsxr_777_ip_rate_limit` - Per-IP request cap
- `gsxr_777_trusted_proxy_ips` - Explicit reverse-proxy IP/CIDR allowlist

## Database Tables

The plugin creates seven tables:
- `wp_gsxr777_sessions` - Chat sessions
- `wp_gsxr777_messages` - Chat messages
- `wp_gsxr777_security_log` - Security events
- `wp_gsxr777_blocked_ips` - Temporary IP blocks
- `wp_gsxr777_rate_limits` - Atomic fixed-window counters
- `wp_gsxr777_knowledge_documents` - Private Markdown documents
- `wp_gsxr777_knowledge_chunks` - Searchable mini-RAG chunks

## Uninstall Process

When uninstalled (if configured):
- Removes all database tables
- Deletes plugin options
- Removes knowledge documents and protected legacy files
- Cleans up transients

## Support and Documentation

- **GitHub:** https://github.com/gsxr-777/gsxr-777-ai-open-chat
- **Documentation:** Included in plugin admin pages
- **Support:** WordPress.org support forums

## Changelog

### Version 1.4.0
- Signed sessions and protected history
- Database-backed mini-RAG
- Authenticated API-key encryption
- Retention cleanup and visitor deletion
- Accessible responsive widget and hardened administration

## Testing Checklist

- [ ] Plugin activates without errors
- [ ] Admin pages load correctly
- [ ] API connection test works
- [ ] Widget displays on frontend
- [ ] Chat functionality works
- [ ] Knowledge base management works
- [ ] Statistics tracking works
- [ ] Security features active
- [ ] Translations load correctly
- [ ] Mobile responsive design
- [ ] Shortcode functionality
- [ ] Uninstall process clean

## WordPress Guidelines Compliance

- ✅ Proper sanitization and escaping
- ✅ Nonce verification for security
- ✅ Internationalization ready
- ✅ No frontend runtime dependencies
- ✅ GPL compatible license
- ✅ Follows plugin header format
- ✅ Proper database operations
- ✅ Clean uninstall process
- ✅ Responsive design
- ✅ Accessibility considerations
- ⏳ Run WordPress Coding Standards autofix/review before repository submission
- ⏳ Complete the integration checklist above on the release WordPress/PHP matrix

## Submission Notes

The runtime implementation is prepared for integration testing. Complete the checklist and resolve the remaining legacy formatting findings before submitting it to WordPress.org.

The plugin provides significant value to WordPress users by enabling easy integration of AI chat capabilities without requiring technical knowledge. The extensive customization options and security features make it suitable for both small websites and enterprise deployments.
