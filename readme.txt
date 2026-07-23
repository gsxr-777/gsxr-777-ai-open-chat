=== GSXR-777 AI Open Chat ===
Contributors: gsxr777
Tags: ai, chat, chatbot, openai, customer support
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI chat widget for WordPress with multiple providers, customizable design, and multilingual support.

== Description ==

GSXR-777 AI Open Chat is a powerful and flexible AI chatbot plugin for WordPress that brings intelligent conversation capabilities to your website. With support for multiple AI providers and extensive customization options, it's the perfect solution for customer support, lead generation, and user engagement.

= Key Features =

* **Universal AI Support** - Works with OpenAI, Claude, Gemini, GigaChat, YandexGPT, Ollama, and any OpenAI-compatible API
* **Page Context Awareness** - Bot understands the current page content and can answer questions about it
* **Database Mini-RAG** - Searches only relevant chunks from published WordPress content and custom Markdown documents
* **Multilingual Support** - Built-in support for English and Russian, with easy translation capabilities
* **Customizable Widget** - Full control over colors, position, size, and messages
* **Security Features** - Signed chat sessions, atomic IP/session rate limiting, authenticated API-key encryption, and input validation
* **Chat History** - Persistent conversation history across page reloads
* **Statistics Dashboard** - Track usage, sessions, and performance metrics
* **Mobile Responsive** - Optimized for all devices and screen sizes
* **Privacy Controls** - Configure page context, metadata storage, retention periods, and visitor-controlled history deletion

= Supported AI Providers =

* **OpenAI** - GPT-4, GPT-3.5, and other models
* **Anthropic Claude** - Claude 3 Sonnet, Haiku, and Opus
* **Google Gemini** - Gemini Pro and other models
* **GigaChat** - Sber's Russian AI model
* **YandexGPT** - Yandex's AI models
* **Ollama** - Local AI models
* **OpenRouter** - Access to multiple AI providers
* **Custom APIs** - Any OpenAI-compatible endpoint

= Easy Setup =

1. Install and activate the plugin
2. Go to AI Chat > API Settings
3. Select your AI provider and enter your API key
4. Customize the widget appearance in Widget Settings
5. Add knowledge base content if needed
6. The chat widget will automatically appear on your site

= Shortcode Support =

Use the `[gsxr_777_chat]` shortcode to display the chat widget anywhere on your site with custom parameters:

`[gsxr_777_chat title="Support" color="#ff6b35" width="420" height="640"]`

= Developer Friendly =

* Clean, well-documented code
* Automated PHP syntax, WordPress integration-test, and coding-standard tooling
* Extensive hooks and filters
* REST API endpoints
* Signed public sessions and nonce-protected administration
* Proper sanitization and escaping

= Privacy & Security =

* All API keys are encrypted before storage
* Rate limiting prevents abuse
* Atomic IP and session rate limiting
* Retrieved/site content is isolated from trusted system instructions
* Configurable page context, metadata storage, and retention periods
* Privacy-policy helper text and visitor-controlled conversation deletion

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "GSXR-777 AI Open Chat"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload it to your WordPress site via Plugins > Add New > Upload Plugin
3. Activate the plugin through the 'Plugins' menu

= Configuration =

1. Navigate to AI Chat in your WordPress admin menu
2. Go to API Settings and configure your AI provider
3. Test the connection to ensure everything works
4. Customize the widget appearance in Widget Settings
5. Add knowledge base content if needed

== Frequently Asked Questions ==

= Which AI providers are supported? =

The plugin supports OpenAI, Anthropic Claude, Google Gemini, GigaChat, YandexGPT, Ollama, OpenRouter, and any OpenAI-compatible API endpoint.

= Do I need an API key? =

Yes, you need an API key from your chosen AI provider. Most providers offer free tiers or trial credits to get started.

= Is the chat history saved? =

Yes, chat history is saved in your WordPress database and persists across page reloads. Users can continue their conversations seamlessly.

= Can I customize the widget appearance? =

Absolutely! You can customize colors, position, size, messages, and more through the Widget Settings page.

= Is it mobile-friendly? =

Yes, the widget is fully responsive and optimized for mobile devices.

= How does the knowledge base work? =

The plugin stores Markdown documents in the WordPress database and splits them into searchable chunks. Published public posts and pages are indexed too. For each question, the bot retrieves a small set of relevant chunks instead of sending the entire knowledge base to the AI provider.

= Is it secure? =

Yes. The plugin uses signed sessions, atomic rate limiting, authenticated API-key encryption, strict endpoint validation, input validation, and generic public API errors.

= Can I use it on multiple pages? =

Yes, the widget can be displayed site-wide or on specific pages using shortcodes or widget settings.

= Does it support multiple languages? =

The plugin interface supports English and Russian out of the box, and you can easily add translations for other languages.

= What about GDPR compliance? =

The plugin is designed with privacy controls, but installing it alone does not make a site legally compliant. You can disable page context and diagnostic metadata, configure retention, expose the generated privacy-policy text, and let visitors clear their conversation.

= What data is sent to an AI provider? =

When the site owner configures an AI provider, visitor messages are sent to that provider to generate replies. If page context is enabled, the current page title, URL, selected text, and relevant page or knowledge-base excerpts may also be sent. The plugin does not send data to a provider until the owner configures an API endpoint and key. Review the provider's terms and privacy policy, and update your site's privacy notice before enabling the chat.

== Screenshots ==

1. Chat widget in action on the frontend
2. API Settings page with provider selection
3. Knowledge Base management interface
4. Widget customization options
5. Statistics dashboard
6. Mobile responsive design

== Changelog ==

= 1.4.0 =
* Added signed persistent chat sessions and protected history endpoints
* Added atomic IP/session rate limiting and trusted-proxy handling
* Added a database-backed mini-RAG index for knowledge files and WordPress content
* Moved knowledge documents from public uploads into the WordPress database
* Added automatic retention cleanup and visitor-controlled history deletion
* Improved API-key encryption, accessibility, dark mode, shortcode rendering, and admin security

= 1.0.0 =
* Initial release
* Support for OpenAI, Claude, Gemini, and other AI providers
* Knowledge base management
* Customizable widget design
* Security features and rate limiting
* Statistics dashboard
* Multilingual support (English/Russian)
* Mobile responsive design
* Shortcode support

== Upgrade Notice ==

= 1.4.0 =
Adds signed sessions, authenticated key encryption, database mini-RAG, automatic retention cleanup, and an accessible responsive widget.

== Support ==

For support, feature requests, or bug reports, please visit:
* GitHub: https://github.com/gsxr-777/gsxr-777-ai-open-chat
* Support Forum: https://wordpress.org/support/plugin/gsxr-777-ai-open-chat/

== Contributing ==

We welcome contributions! Please see our GitHub repository for contribution guidelines and development setup instructions.

== License ==

This plugin is licensed under the GNU General Public License, version 2 or later. See the LICENSE file for details.
