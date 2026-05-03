/**
 * GSXR-777 AI Chat Widget
 * Supports dynamic language switching with Polylang
 */

(function () {
    'use strict';

    // Check if config is available
    if (typeof gsxr777Config === 'undefined') {
        console.error('GSXR-777: Configuration not found');
        return;
    }

    class GSXR777ChatWidget {
        constructor(config) {
            this.config = config;
            this.sessionId = this.generateSessionId();
            this.isOpen = false;
            this.isMinimized = false;
            this.currentLanguage = config.language || 'en_US';
            this.strings = config.strings || {};

            this.init();
            this.setupLanguageListener();
        }

        async init() {
            // Load current language config first
            await this.loadConfig();
            this.createWidget();
            this.loadHistory();
        }

        generateSessionId() {
            return 'gsxr777_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        createWidget() {
            // Create container
            const container = document.createElement('div');
            container.className = `gsxr777-chat-widget ${this.config.position || 'bottom-right'}`;
            container.id = 'gsxr-777-chat-widget';
            document.body.appendChild(container);

            // Create toggle button
            const toggle = document.createElement('button');
            toggle.className = 'gsxr777-chat-toggle';
            toggle.setAttribute('aria-label', this.strings.open || this.config.title || '');
            toggle.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="currentColor"/>
                </svg>
            `;
            toggle.addEventListener('click', () => this.toggle());
            container.appendChild(toggle);

            // Create chat window
            const window = document.createElement('div');
            window.className = 'gsxr777-chat-window';
            window.style.width = (this.config.width || 400) + 'px';
            window.style.height = (this.config.height || 600) + 'px';
            window.style.display = 'none';
            container.appendChild(window);

            // Create header
            const header = document.createElement('div');
            header.className = 'gsxr777-chat-header';
            header.innerHTML = `
                <h3>${this.config.title || ''}</h3>
                <button class="gsxr777-chat-close" aria-label="${this.strings.close || ''}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            `;
            header.querySelector('.gsxr777-chat-close').addEventListener('click', () => this.close());
            window.appendChild(header);

            // Create messages container
            const messages = document.createElement('div');
            messages.className = 'gsxr777-chat-messages';
            window.appendChild(messages);

            // Create input container
            const inputContainer = document.createElement('div');
            inputContainer.className = 'gsxr777-chat-input-container';
            inputContainer.innerHTML = `
                <textarea class="gsxr777-chat-input" 
                    placeholder="${this.config.placeholder || ''}" 
                    rows="1"></textarea>
                <button class="gsxr777-chat-send" aria-label="${this.strings.send || ''}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            `;
            window.appendChild(inputContainer);

            // Store references
            this.container = container;
            this.window = window;
            this.messagesContainer = messages;
            this.input = inputContainer.querySelector('.gsxr777-chat-input');
            this.sendButton = inputContainer.querySelector('.gsxr777-chat-send');

            this.applyTheme();

            // Setup input handlers
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            this.input.addEventListener('input', () => {
                // Auto-resize textarea
                this.input.style.height = 'auto';
                this.input.style.height = Math.min(this.input.scrollHeight, 100) + 'px';
            });

            this.sendButton.addEventListener('click', () => this.sendMessage());

            // Add welcome message
            if (this.config.welcome) {
                this.addMessage('assistant', this.config.welcome);
            }
        }

        applyTheme() {
            if (!this.container) {
                return;
            }

            const theme = this.config.theme || {};
            const primaryColor = theme.primaryColor || this.config.primaryColor || '#2563eb';
            const secondaryColor = theme.secondaryColor || '#1d4ed8';
            const gradientAngle = Number.isFinite(parseInt(theme.gradientAngle, 10)) ? parseInt(theme.gradientAngle, 10) : 135;
            const accentGradient = `linear-gradient(${gradientAngle}deg, ${primaryColor} 0%, ${secondaryColor} 100%)`;

            const vars = {
                '--gsxr-accent-color': primaryColor,
                '--gsxr-accent-gradient': accentGradient,
                '--gsxr-window-bg': theme.windowBackground || '#ffffff',
                '--gsxr-messages-bg': theme.messagesBackground || '#ffffff',
                '--gsxr-assistant-bg': theme.assistantBackground || '#f0f0f0',
                '--gsxr-assistant-color': theme.assistantTextColor || '#333333',
                '--gsxr-user-color': theme.userTextColor || '#ffffff',
                '--gsxr-input-bg': theme.inputBackground || '#ffffff',
                '--gsxr-input-color': theme.inputTextColor || '#333333',
                '--gsxr-widget-font-family': theme.widgetFontFamily || '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                '--gsxr-chat-font-family': theme.chatFontFamily || 'inherit'
            };

            Object.entries(vars).forEach(([name, value]) => {
                if (typeof value === 'string' && value.trim() !== '') {
                    this.container.style.setProperty(name, value);
                }
            });
        }

        getUrlWithLang(url) {
            // Try to detect language from DOM
            const lang = document.documentElement.lang ||
                (document.querySelector('html[lang]')?.getAttribute('lang') || '');

            // Extract language code (e.g. 'en-US' -> 'en')
            let langCode = lang ? lang.split('-')[0] : '';

            // If not found, use currentLanguage
            if (!langCode && this.currentLanguage) {
                langCode = this.currentLanguage.split('_')[0];
            }

            if (!langCode) return url;

            const separator = url.includes('?') ? '&' : '?';
            // Remove existing lang param if any
            let cleanUrl = url;
            if (url.includes('lang=')) {
                cleanUrl = url.replace(/([?&])lang=[^&]*(&|$)/, '$1');
                // Clean up trailing separators
                if (cleanUrl.endsWith('?') || cleanUrl.endsWith('&')) {
                    cleanUrl = cleanUrl.slice(0, -1);
                }
            }

            const finalSeparator = cleanUrl.includes('?') ? '&' : '?';
            return `${cleanUrl}${finalSeparator}lang=${langCode}`;
        }

        async loadStrings() {
            try {
                const url = this.getUrlWithLang(this.config.stringsUrl);
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': this.config.nonce
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.strings = data;
                    this.currentLanguage = data.language || this.currentLanguage;
                    this.updateUIStrings();
                }
            } catch (error) {
                console.error('GSXR-777: Failed to load strings', error);
            }
        }

        async loadConfig() {
            try {
                const url = this.getUrlWithLang(this.config.configUrl);
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': this.config.nonce
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    // Update config with new language strings
                    const oldLanguage = this.currentLanguage;
                    this.config = { ...this.config, ...data };
                    this.strings = data.strings || this.strings;
                    this.currentLanguage = data.language || this.currentLanguage;
                    this.applyTheme();

                    // Only update UI if language actually changed
                    if (oldLanguage !== this.currentLanguage) {
                        this.updateUIStrings();
                    }
                }
            } catch (error) {
                console.error('GSXR-777: Failed to load config', error);
            }
        }

        updateUIStrings() {
            // Update button labels
            const closeButton = this.window.querySelector('.gsxr777-chat-close');
            if (closeButton) {
                closeButton.setAttribute('aria-label', this.strings.close || 'Close');
            }

            const sendButton = this.window.querySelector('.gsxr777-chat-send');
            if (sendButton) {
                sendButton.setAttribute('aria-label', this.strings.send || 'Send');
            }

            // Update placeholder
            if (this.input && this.config.placeholder) {
                this.input.placeholder = this.config.placeholder;
            }

            // Update title
            const title = this.window.querySelector('h3');
            if (title && this.config.title) {
                title.textContent = this.config.title;
            }

            // Update welcome message if it exists and is the first message
            const messages = this.messagesContainer.querySelectorAll('.gsxr777-chat-message.assistant');
            if (messages.length > 0 && this.config.welcome) {
                const firstMessage = messages[0];
                const content = firstMessage.querySelector('.gsxr777-chat-message-content');
                if (content) {
                    // Always update first message if it's a welcome message
                    // Check if it's likely a welcome message (first message, short, contains greeting words)
                    const currentText = content.textContent.trim().toLowerCase();
                    const isWelcomeMessage = currentText.length < 300 && (
                        currentText.includes('привет') ||
                        currentText.includes('hello') ||
                        currentText.includes('help') ||
                        currentText.includes('помочь') ||
                        messages.length === 1 // Only message = likely welcome
                    );

                    if (isWelcomeMessage) {
                        content.innerHTML = this.formatMessage(this.config.welcome);
                    }
                }
            }
        }

        setupLanguageListener() {
            // Listen for Polylang language change events
            if (typeof jQuery !== 'undefined') {
                // Polylang uses jQuery events
                jQuery(document).on('pll_language_changed', () => {
                    this.onLanguageChange();
                });
            }

            // Also listen for custom events that might be triggered
            document.addEventListener('pll_language_changed', () => {
                this.onLanguageChange();
            });

            // Listen for URL changes (Polylang changes URL when switching languages)
            let lastUrl = location.href;
            let lastLang = this.currentLanguage;

            // Check language periodically (for cases where events don't fire)
            setInterval(() => {
                const currentLang = document.documentElement.lang ||
                    (document.querySelector('html[lang]')?.getAttribute('lang') || '');
                if (currentLang && currentLang !== lastLang) {
                    lastLang = currentLang;
                    this.onLanguageChange();
                }
            }, 1000);

            // Listen for URL changes
            const urlObserver = new MutationObserver(() => {
                const url = location.href;
                if (url !== lastUrl) {
                    lastUrl = url;
                    // Small delay to allow Polylang to update
                    setTimeout(() => {
                        this.onLanguageChange();
                    }, 200);
                }
            });
            urlObserver.observe(document, { subtree: true, childList: true });

            // Check for language changes on page visibility change
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    setTimeout(() => {
                        this.onLanguageChange();
                    }, 100);
                }
            });

            // Listen for popstate (browser back/forward)
            window.addEventListener('popstate', () => {
                setTimeout(() => {
                    this.onLanguageChange();
                }, 100);
            });
        }

        async onLanguageChange() {
            // Reload strings and config when language changes
            const oldConfig = { ...this.config };
            await this.loadConfig();

            // Force UI update if config changed
            if (oldConfig.title !== this.config.title ||
                oldConfig.welcome !== this.config.welcome ||
                oldConfig.placeholder !== this.config.placeholder) {
                this.updateUIStrings();
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            this.isOpen = true;
            this.window.style.display = 'flex';
            this.input.focus();
        }

        close() {
            this.isOpen = false;
            this.window.style.display = 'none';
        }

        async sendMessage() {
            const message = this.input.value.trim();
            if (!message) return;

            // Disable input
            this.input.disabled = true;
            this.sendButton.disabled = true;

            // Add user message
            this.addMessage('user', message);
            this.input.value = '';
            this.input.style.height = 'auto';

            // Show typing indicator
            const typingId = this.addTypingIndicator();

            try {
                // Get page context
                const context = {
                    url: window.location.href,
                    title: document.title,
                    content: document.body.innerText.substring(0, 5000),
                    selectedText: window.getSelection().toString()
                };

                const response = await fetch(this.config.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.config.nonce
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: this.sessionId,
                        context: context
                    })
                });

                // Remove typing indicator
                this.removeTypingIndicator(typingId);

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || this.strings.error || 'Error occurred');
                }

                const data = await response.json();
                if (data.success && data.message) {
                    this.addMessage('assistant', data.message);
                } else {
                    throw new Error(data.error || this.strings.error || 'Error occurred');
                }
            } catch (error) {
                this.removeTypingIndicator(typingId);
                this.addMessage('error', error.message || this.strings.error || 'Sorry, something went wrong. Please try again.');
            } finally {
                // Re-enable input
                this.input.disabled = false;
                this.sendButton.disabled = false;
                this.input.focus();
            }
        }

        addMessage(role, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `gsxr777-chat-message ${role}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'gsxr777-chat-message-content';

            if (role === 'error') {
                contentDiv.innerHTML = `
                    <div style="color: #dc2626; margin-bottom: 8px;">${this.escapeHtml(content)}</div>
                    <button onclick="this.closest('.gsxr777-chat-message').nextElementSibling && this.closest('.gsxr777-chat-message').nextElementSibling.querySelector('.gsxr777-chat-send').click()" 
                            style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        ${this.strings.retry || 'Retry'}
                    </button>
                `;
            } else {
                // Convert markdown-like formatting
                contentDiv.innerHTML = this.formatMessage(content);
            }

            messageDiv.appendChild(contentDiv);
            this.messagesContainer.appendChild(messageDiv);
            this.scrollToBottom();
        }

        addTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'gsxr777-chat-typing';
            typingDiv.id = 'gsxr777-typing-' + Date.now();
            typingDiv.innerHTML = `
                <span></span>
                <span></span>
                <span></span>
            `;
            this.messagesContainer.appendChild(typingDiv);
            this.scrollToBottom();
            return typingDiv.id;
        }

        removeTypingIndicator(id) {
            const indicator = document.getElementById(id);
            if (indicator) {
                indicator.remove();
            }
        }

        formatMessage(text) {
            // Simple markdown-like formatting
            return this.escapeHtml(text)
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code>$1</code>')
                .replace(/```([\s\S]+?)```/g, '<pre><code>$1</code></pre>')
                .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
                .replace(/\n/g, '<br>');
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        scrollToBottom() {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }

        async loadHistory() {
            try {
                const historyUrl = this.getUrlWithLang(this.config.historyUrl + this.sessionId);
                const response = await fetch(historyUrl, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': this.config.nonce
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.history && data.history.length > 0) {
                        // Clear welcome message if history exists
                        this.messagesContainer.innerHTML = '';
                        data.history.forEach(msg => {
                            this.addMessage(msg.role, msg.content);
                        });
                    }
                }
            } catch (error) {
                console.error('GSXR-777: Failed to load history', error);
            }
        }
    }

    // Initialize widget when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new GSXR777ChatWidget(gsxr777Config);
        });
    } else {
        new GSXR777ChatWidget(gsxr777Config);
    }

    // Expose widget instance globally for debugging
    window.GSXR777ChatWidget = GSXR777ChatWidget;
})();

