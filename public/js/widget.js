/**
 * GSXR-777 AI Chat Widget
 */

(function () {
    'use strict';

    if (typeof gsxr777Config === 'undefined') {
        return;
    }

    const SESSION_STORAGE_KEY = 'gsxr777_chat_session_v2';
    const SESSION_ID_PATTERN = /^gsxr777_[a-f0-9]{32}$/;
    let instanceCounter = 0;
    let sharedSessionPromise = null;

    class GSXR777ChatWidget {
        constructor(config, options = {}) {
            this.config = config;
            this.mount = options.mount || document.body;
            this.isInline = Boolean(options.inline);
            this.isOpen = this.isInline;
            this.currentLanguage = config.language || 'en_US';
            this.strings = config.strings || {};
            this.session = null;
            this.lastFailedMessage = '';
            this.instanceId = `gsxr777-chat-${++instanceCounter}`;
            this.colorSchemeMedia = window.matchMedia
                ? window.matchMedia('(prefers-color-scheme: dark)')
                : null;

            this.init();
        }

        async init() {
            this.createWidget();
            this.setupLanguageListener();
            this.setupColorSchemeListener();
            this.setBusy(true);

            try {
                await this.ensureSession();
                await this.loadHistory();
            } catch (error) {
                this.addMessage(
                    'error',
                    error.message || this.strings.sessionError || 'Could not start a secure chat session',
                    false
                );
            } finally {
                this.setBusy(false);
            }
        }

        createWidget() {
            const container = document.createElement('section');
            container.className = `gsxr777-chat-widget ${this.config.position || 'bottom-right'}`;
            container.dataset.themeMode = this.getThemeMode();
            if (this.isInline) {
                container.classList.add('gsxr777-chat-widget--inline');
            }

            const toggle = this.createIconButton(
                'gsxr777-chat-toggle',
                this.strings.open || this.config.title || 'Open chat',
                '<path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="currentColor"/>'
            );
            toggle.setAttribute('aria-controls', `${this.instanceId}-window`);
            toggle.setAttribute('aria-expanded', String(this.isOpen));
            toggle.addEventListener('click', () => this.toggle());
            if (this.isInline) {
                toggle.hidden = true;
            }
            container.appendChild(toggle);

            const chatWindow = document.createElement('div');
            chatWindow.className = 'gsxr777-chat-window';
            chatWindow.id = `${this.instanceId}-window`;
            chatWindow.setAttribute('role', 'dialog');
            chatWindow.setAttribute('aria-modal', 'false');
            chatWindow.setAttribute('aria-labelledby', `${this.instanceId}-title`);
            chatWindow.hidden = !this.isInline;

            const header = document.createElement('header');
            header.className = 'gsxr777-chat-header';

            const title = document.createElement('h2');
            title.id = `${this.instanceId}-title`;
            title.className = 'gsxr777-chat-title';
            title.textContent = this.config.title || '';
            header.appendChild(title);

            const headerActions = document.createElement('div');
            headerActions.className = 'gsxr777-chat-header-actions';

            const clearButton = this.createIconButton(
                'gsxr777-chat-clear',
                this.strings.clear || 'Clear conversation',
                '<path d="M4 7H20M9 7V4H15V7M7 7L8 20H16L17 7M10 11V17M14 11V17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            );
            clearButton.addEventListener('click', () => this.clearHistory());
            headerActions.appendChild(clearButton);

            const closeButton = this.createIconButton(
                'gsxr777-chat-close',
                this.strings.close || 'Close',
                '<path d="M18 6L6 18M6 6L18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
            );
            closeButton.addEventListener('click', () => this.close());
            if (this.isInline) {
                closeButton.hidden = true;
            }
            headerActions.appendChild(closeButton);
            header.appendChild(headerActions);
            chatWindow.appendChild(header);

            const messages = document.createElement('div');
            messages.className = 'gsxr777-chat-messages';
            messages.setAttribute('role', 'log');
            messages.setAttribute('aria-live', 'polite');
            messages.setAttribute('aria-relevant', 'additions text');
            chatWindow.appendChild(messages);

            const form = document.createElement('form');
            form.className = 'gsxr777-chat-input-container';

            const label = document.createElement('label');
            label.className = 'gsxr777-visually-hidden';
            label.htmlFor = `${this.instanceId}-input`;
            label.textContent = this.config.placeholder || 'Type your message';
            form.appendChild(label);

            const input = document.createElement('textarea');
            input.id = `${this.instanceId}-input`;
            input.className = 'gsxr777-chat-input';
            input.placeholder = this.config.placeholder || '';
            input.rows = 1;
            input.maxLength = 5000;
            form.appendChild(input);

            const sendButton = this.createIconButton(
                'gsxr777-chat-send',
                this.strings.send || 'Send',
                '<path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            );
            sendButton.type = 'submit';
            form.appendChild(sendButton);
            chatWindow.appendChild(form);
            container.appendChild(chatWindow);
            this.mount.appendChild(container);

            this.container = container;
            this.toggleButton = toggle;
            this.window = chatWindow;
            this.title = title;
            this.clearButton = clearButton;
            this.closeButton = closeButton;
            this.messagesContainer = messages;
            this.form = form;
            this.input = input;
            this.sendButton = sendButton;

            this.applyTheme();
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.sendMessage();
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                    event.preventDefault();
                    this.sendMessage();
                }
            });
            input.addEventListener('input', () => {
                input.style.height = 'auto';
                input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
            });
            chatWindow.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !this.isInline) {
                    event.preventDefault();
                    this.close();
                }
            });

            this.addWelcomeMessage();
        }

        createIconButton(className, label, svgContent) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            button.setAttribute('aria-label', label);

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', '24');
            svg.setAttribute('height', '24');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('focusable', 'false');
            svg.innerHTML = svgContent;
            button.appendChild(svg);

            return button;
        }

        addWelcomeMessage() {
            if (this.config.welcome) {
                this.addMessage('assistant', this.config.welcome, false);
            }
        }

        getThemeMode() {
            const mode = this.config.theme?.mode;
            return ['auto', 'light', 'dark'].includes(mode) ? mode : 'auto';
        }

        applyTheme() {
            if (!this.container) {
                return;
            }

            const theme = this.config.theme || {};
            const mode = this.getThemeMode();
            const useDarkPalette = mode === 'dark'
                || (mode === 'auto' && this.colorSchemeMedia?.matches);
            const primaryColor = theme.primaryColor || this.config.primaryColor || '#2563eb';
            const secondaryColor = theme.secondaryColor || '#1d4ed8';
            const gradientAngle = Number.isFinite(Number(theme.gradientAngle))
                ? Math.max(0, Math.min(360, Number(theme.gradientAngle)))
                : 135;

            // Appearance settings are explicit user choices and must win over
            // the automatic light/dark fallback palette. The system theme only
            // selects defaults when a setting is absent.
            const surfaceTheme = useDarkPalette
                ? {
                    windowBackground: theme.windowBackground || '#1e1e1e',
                    messagesBackground: theme.messagesBackground || '#1e1e1e',
                    assistantBackground: theme.assistantBackground || '#2a2a2a',
                    assistantTextColor: theme.assistantTextColor || '#f1f5f9',
                    inputBackground: theme.inputBackground || '#252525',
                    inputTextColor: theme.inputTextColor || '#f1f5f9',
                    borderColor: '#454545'
                }
                : {
                    windowBackground: theme.windowBackground || '#ffffff',
                    messagesBackground: theme.messagesBackground || '#ffffff',
                    assistantBackground: theme.assistantBackground || '#f0f0f0',
                    assistantTextColor: theme.assistantTextColor || '#333333',
                    inputBackground: theme.inputBackground || '#ffffff',
                    inputTextColor: theme.inputTextColor || '#333333',
                    borderColor: '#d1d5db'
                };

            const vars = {
                '--gsxr-accent-color': primaryColor,
                '--gsxr-accent-gradient': `linear-gradient(${gradientAngle}deg, ${primaryColor} 0%, ${secondaryColor} 100%)`,
                '--gsxr-window-bg': surfaceTheme.windowBackground,
                '--gsxr-messages-bg': surfaceTheme.messagesBackground,
                '--gsxr-assistant-bg': surfaceTheme.assistantBackground,
                '--gsxr-assistant-color': surfaceTheme.assistantTextColor,
                '--gsxr-user-color': theme.userTextColor || '#ffffff',
                '--gsxr-input-bg': surfaceTheme.inputBackground,
                '--gsxr-input-color': surfaceTheme.inputTextColor,
                '--gsxr-input-border': surfaceTheme.borderColor,
                '--gsxr-border-color': surfaceTheme.borderColor,
                '--gsxr-widget-font-family': theme.widgetFontFamily || 'system-ui, sans-serif',
                '--gsxr-chat-font-family': theme.chatFontFamily || 'inherit',
                '--gsxr-widget-width': `${Math.max(300, Math.min(800, Number(this.config.width) || 400))}px`,
                '--gsxr-widget-height': `${Math.max(400, Math.min(900, Number(this.config.height) || 600))}px`
            };

            Object.entries(vars).forEach(([name, value]) => {
                this.container.style.setProperty(name, value);
            });
            this.container.dataset.themeMode = useDarkPalette ? 'dark' : 'light';
        }

        setupColorSchemeListener() {
            if (!this.colorSchemeMedia) {
                return;
            }

            const listener = () => {
                if (this.getThemeMode() === 'auto') {
                    this.applyTheme();
                }
            };
            if (typeof this.colorSchemeMedia.addEventListener === 'function') {
                this.colorSchemeMedia.addEventListener('change', listener);
            } else if (typeof this.colorSchemeMedia.addListener === 'function') {
                this.colorSchemeMedia.addListener(listener);
            }
        }

        setupLanguageListener() {
            document.addEventListener('pll_language_changed', () => this.onLanguageChange());
            window.addEventListener('popstate', () => this.onLanguageChange());
        }

        async onLanguageChange() {
            try {
                const response = await this.fetchWithTimeout(
                    this.getUrlWithLang(this.config.configUrl),
                    {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': this.config.nonce
                        }
                    },
                    15000
                );
                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                this.config = { ...this.config, ...data };
                this.strings = data.strings || this.strings;
                this.currentLanguage = data.language || this.currentLanguage;
                this.updateUIStrings();
                this.applyTheme();
            } catch (error) {
                // A language refresh failure must not break the existing widget.
            }
        }

        updateUIStrings() {
            this.title.textContent = this.config.title || '';
            this.input.placeholder = this.config.placeholder || '';
            this.toggleButton.setAttribute('aria-label', this.strings.open || this.config.title || 'Open chat');
            this.closeButton.setAttribute('aria-label', this.strings.close || 'Close');
            this.clearButton.setAttribute('aria-label', this.strings.clear || 'Clear conversation');
            this.sendButton.setAttribute('aria-label', this.strings.send || 'Send');
        }

        getUrlWithLang(url) {
            const documentLanguage = document.documentElement.lang || '';
            const languageCode = (documentLanguage || this.currentLanguage)
                .split(/[-_]/)[0]
                .toLowerCase();
            if (!languageCode) {
                return url;
            }

            const parsedUrl = new URL(url, window.location.origin);
            parsedUrl.searchParams.set('lang', languageCode);
            return parsedUrl.toString();
        }

        getStoredSession() {
            try {
                const stored = JSON.parse(localStorage.getItem(SESSION_STORAGE_KEY) || 'null');
                if (
                    stored
                    && SESSION_ID_PATTERN.test(stored.session_id)
                    && typeof stored.session_token === 'string'
                    && stored.session_token.length >= 40
                ) {
                    return stored;
                }
            } catch (error) {
                // Storage may be unavailable in hardened/private browser modes.
            }

            return null;
        }

        storeSession(session) {
            try {
                localStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify(session));
            } catch (error) {
                // The in-memory session remains usable for the current page.
            }
        }

        clearStoredSession() {
            try {
                localStorage.removeItem(SESSION_STORAGE_KEY);
            } catch (error) {
                // Nothing else to do.
            }
        }

        async ensureSession(forceNew = false) {
            if (forceNew) {
                this.session = null;
                sharedSessionPromise = null;
                this.clearStoredSession();
            }

            if (this.session) {
                return this.session;
            }

            const stored = this.getStoredSession();
            if (stored) {
                this.session = stored;
                return stored;
            }

            if (!sharedSessionPromise) {
                sharedSessionPromise = this.fetchWithTimeout(
                    this.config.sessionUrl,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: '{}'
                    },
                    15000
                ).then(async (response) => {
                    if (!response.ok) {
                        throw new Error(this.strings.sessionError || 'Could not start a secure chat session');
                    }
                    const data = await response.json();
                    if (
                        !data.success
                        || !SESSION_ID_PATTERN.test(data.session_id || '')
                        || typeof data.session_token !== 'string'
                    ) {
                        throw new Error(this.strings.sessionError || 'Could not start a secure chat session');
                    }
                    return {
                        session_id: data.session_id,
                        session_token: data.session_token
                    };
                }).finally(() => {
                    sharedSessionPromise = null;
                });
            }

            this.session = await sharedSessionPromise;
            this.storeSession(this.session);
            return this.session;
        }

        getSessionHeaders() {
            return {
                'X-GSXR-Session-Token': this.session?.session_token || '',
                'X-WP-Nonce': this.config.nonce
            };
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
            this.window.hidden = false;
            this.toggleButton.setAttribute('aria-expanded', 'true');
            this.input.focus();
        }

        close() {
            if (this.isInline) {
                return;
            }
            this.isOpen = false;
            this.window.hidden = true;
            this.toggleButton.setAttribute('aria-expanded', 'false');
            this.toggleButton.focus();
        }

        setBusy(isBusy) {
            this.input.disabled = isBusy;
            this.sendButton.disabled = isBusy;
            this.clearButton.disabled = isBusy;
            this.container.setAttribute('aria-busy', String(isBusy));
        }

        capturePageContext() {
            if (!this.config.includePageContext) {
                return {};
            }

            const contentRoot = document.querySelector('main, article, [role="main"]');
            const pageText = contentRoot?.innerText || '';

            return {
                url: window.location.href,
                title: document.title,
                content: Array.from(pageText).slice(0, 3000).join(''),
                selectedText: Array.from(window.getSelection()?.toString() || '').slice(0, 1000).join('')
            };
        }

        async sendMessage(messageOverride = '', options = {}) {
            const message = (messageOverride || this.input.value).trim();
            if (!message || this.input.disabled) {
                return;
            }

            const displayUser = options.displayUser !== false;
            const authRetried = options.authRetried === true;
            this.setBusy(true);
            this.lastFailedMessage = '';

            if (displayUser) {
                this.addMessage('user', message, false);
            }
            this.input.value = '';
            this.input.style.height = 'auto';
            const typingId = this.addTypingIndicator();

            try {
                await this.ensureSession();
                const response = await this.fetchWithTimeout(
                    this.config.apiUrl,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            ...this.getSessionHeaders()
                        },
                        body: JSON.stringify({
                            message,
                            session_id: this.session.session_id,
                            context: this.capturePageContext()
                        })
                    },
                    Number(this.config.requestTimeout) || 130000
                );

                if (response.status === 401 && !authRetried) {
                    this.removeTypingIndicator(typingId);
                    await this.ensureSession(true);
                    this.setBusy(false);
                    return await this.sendMessage(message, { displayUser: false, authRetried: true });
                }

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success || !data.message) {
                    throw new Error(data.message || this.strings.error || 'Error occurred');
                }

                this.removeTypingIndicator(typingId);
                this.addMessage('assistant', data.message, false);
            } catch (error) {
                this.removeTypingIndicator(typingId);
                this.lastFailedMessage = message;
                this.addMessage(
                    'error',
                    error.name === 'AbortError'
                        ? (this.strings.error || 'The request timed out. Please try again.')
                        : (error.message || this.strings.error || 'Something went wrong.'),
                    true
                );
            } finally {
                this.setBusy(false);
                this.input.focus();
            }
        }

        async clearHistory() {
            try {
                this.setBusy(true);
                await this.ensureSession();
                const response = await this.fetchWithTimeout(
                    `${this.config.historyUrl}${encodeURIComponent(this.session.session_id)}`,
                    {
                        method: 'DELETE',
                        headers: this.getSessionHeaders()
                    },
                    15000
                );
                if (!response.ok) {
                    throw new Error(this.strings.error || 'Could not clear conversation');
                }

                this.messagesContainer.replaceChildren();
                this.addWelcomeMessage();
                this.addStatusMessage(this.strings.cleared || 'Conversation history cleared');
            } catch (error) {
                this.addMessage('error', error.message || this.strings.error || 'Something went wrong.', false);
            } finally {
                this.setBusy(false);
            }
        }

        addStatusMessage(content) {
            const status = document.createElement('p');
            status.className = 'gsxr777-chat-status';
            status.setAttribute('role', 'status');
            status.textContent = content;
            this.messagesContainer.appendChild(status);
        }

        addMessage(role, content, showRetry) {
            const safeRole = ['user', 'assistant', 'error'].includes(role) ? role : 'assistant';
            const message = document.createElement('div');
            message.className = `gsxr777-chat-message ${safeRole}`;

            const contentElement = document.createElement('div');
            contentElement.className = 'gsxr777-chat-message-content';
            contentElement.innerHTML = this.formatMessage(String(content || ''));
            message.appendChild(contentElement);

            if (safeRole === 'error' && showRetry) {
                const retryButton = document.createElement('button');
                retryButton.type = 'button';
                retryButton.className = 'gsxr777-chat-retry';
                retryButton.textContent = this.strings.retry || 'Retry';
                retryButton.addEventListener('click', () => {
                    const failedMessage = this.lastFailedMessage;
                    message.remove();
                    this.sendMessage(failedMessage, { displayUser: false });
                });
                message.appendChild(retryButton);
            }

            this.messagesContainer.appendChild(message);
            this.scrollToBottom();
        }

        addTypingIndicator() {
            const typing = document.createElement('div');
            typing.className = 'gsxr777-chat-typing';
            typing.id = `${this.instanceId}-typing-${Date.now()}`;
            typing.setAttribute('role', 'status');

            const label = document.createElement('span');
            label.className = 'gsxr777-visually-hidden';
            label.textContent = this.strings.typing || 'AI is typing';
            typing.appendChild(label);

            for (let index = 0; index < 3; index += 1) {
                const dot = document.createElement('span');
                dot.className = 'gsxr777-chat-typing-dot';
                dot.setAttribute('aria-hidden', 'true');
                typing.appendChild(dot);
            }

            this.messagesContainer.appendChild(typing);
            this.scrollToBottom();
            return typing.id;
        }

        removeTypingIndicator(id) {
            document.getElementById(id)?.remove();
        }

        formatMessage(text) {
            return this.escapeHtml(text)
                .replace(/```([\s\S]+?)```/g, '<pre><code>$1</code></pre>')
                .replace(/`([^`\n]+?)`/g, '<code>$1</code>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
                .replace(/\n/g, '<br>');
        }

        escapeHtml(text) {
            const element = document.createElement('div');
            element.textContent = text;
            return element.innerHTML;
        }

        scrollToBottom() {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }

        async loadHistory() {
            const response = await this.fetchWithTimeout(
                this.getUrlWithLang(
                    `${this.config.historyUrl}${encodeURIComponent(this.session.session_id)}`
                ),
                {
                    method: 'GET',
                    headers: this.getSessionHeaders()
                },
                15000
            );

            if (response.status === 401) {
                await this.ensureSession(true);
                return;
            }
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (data.success && Array.isArray(data.history) && data.history.length > 0) {
                this.messagesContainer.replaceChildren();
                data.history.forEach((message) => {
                    this.addMessage(message.role, message.content, false);
                });
            }
        }

        async fetchWithTimeout(url, options, timeout) {
            const controller = new AbortController();
            const timer = window.setTimeout(() => controller.abort(), timeout);
            try {
                return await fetch(url, { ...options, signal: controller.signal });
            } finally {
                window.clearTimeout(timer);
            }
        }
    }

    const initializeWidgets = () => {
        // The plugin is a global floating widget. Some themes place the
        // shortcode inside hidden/footer containers; mounting inline there
        // makes the chat invisible or gives it an unwanted white frame.
        // Keep shortcode markup harmless and mount one visible global widget.
        new GSXR777ChatWidget(gsxr777Config);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWidgets, { once: true });
    } else {
        initializeWidgets();
    }

    if (window.GSXR777_DEBUG) {
        window.GSXR777ChatWidget = GSXR777ChatWidget;
    }
})();
