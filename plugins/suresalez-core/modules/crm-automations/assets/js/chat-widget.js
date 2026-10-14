/**
 * SureSalez Conversational Live Chat Widget Client Script
 *
 * @package SureSalez\Modules\CRMAutomations
 */

(function () {
    'use strict';

    function initChatWidget() {
        var mount = document.getElementById('suresalez-live-chat-mount');
        if (!mount || mount.dataset.bound) {
            return;
        }
        mount.dataset.bound = 'true';

        var launcher = mount.querySelector('.suresalez-chat-launcher');
        var chatWindow = mount.querySelector('.suresalez-chat-window');
        var closeBtn = mount.querySelector('.chat-close-btn');
        var form = document.getElementById('suresalez-chat-form');
        var input = document.getElementById('suresalez-chat-input');
        var messagesLog = document.getElementById('suresalez-chat-messages-log');
        var quickButtons = mount.querySelectorAll('.chat-quick-btn');
        var chatIcon = mount.querySelector('.chat-icon');
        var closeIcon = mount.querySelector('.chat-close-icon');
        var unreadDot = mount.querySelector('.chat-unread-dot');

        if (!launcher || !chatWindow) return;

        function toggleChat(open) {
            var isCurrentlyOpen = chatWindow.style.display !== 'none';
            var shouldOpen = typeof open === 'boolean' ? open : !isCurrentlyOpen;

            chatWindow.style.display = shouldOpen ? 'flex' : 'none';
            if (chatIcon) chatIcon.style.display = shouldOpen ? 'none' : 'inline';
            if (closeIcon) closeIcon.style.display = shouldOpen ? 'inline' : 'none';
            if (unreadDot && shouldOpen) unreadDot.style.display = 'none';

            if (shouldOpen && input) {
                setTimeout(function () { input.focus(); }, 100);
            }
        }

        launcher.addEventListener('click', function (e) {
            e.preventDefault();
            toggleChat();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleChat(false);
            });
        }

        // Quick action clicks
        quickButtons.forEach(function (btn) {
            if (btn.classList.contains('whatsapp-btn')) return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var query = this.getAttribute('data-query');
                if (query) {
                    sendMessage(query);
                }
            });
        });

        if (form && input) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = input.value.trim();
                if (text) {
                    sendMessage(text);
                    input.value = '';
                }
            });
        }

        function appendMessage(text, isUser) {
            if (!messagesLog) return;
            var now = new Date();
            var timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            var bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (isUser ? 'user-bubble' : 'bot-bubble');
            bubble.innerHTML = '<p>' + escapeHtml(text) + '</p><span class="bubble-time">' + timeStr + '</span>';

            messagesLog.appendChild(bubble);
            messagesLog.scrollTop = messagesLog.scrollHeight;
        }

        function sendMessage(text) {
            appendMessage(text, true);

            // Send to REST API or fallback bot
            if (window.sureSalezChatConfig && window.sureSalezChatConfig.restUrl) {
                fetch(window.sureSalezChatConfig.restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.sureSalezChatConfig.nonce || ''
                    },
                    body: JSON.stringify({ message: text })
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var reply = (data.response && data.response.reply) ? data.response.reply : 'I am looking into that for you right now.';
                    setTimeout(function () { appendMessage(reply, false); }, 300);
                })
                .catch(function () {
                    clientFallbackBot(text);
                });
            } else {
                clientFallbackBot(text);
            }
        }

        function clientFallbackBot(text) {
            var lower = text.toLowerCase();
            var reply = 'Thank you for reaching out! A specialist will assist you shortly.';

            if (lower.indexOf('track') !== -1 || lower.indexOf('order') !== -1) {
                reply = 'Orders dispatch same-day from our nearest regional hub. You can track progress with your order number (#98450).';
            } else if (lower.indexOf('return') !== -1 || lower.indexOf('refund') !== -1) {
                reply = 'SureSalez offers a 30-day hassle-free return guarantee with prepaid labels in our customer RMA portal.';
            } else if (lower.indexOf('care') !== -1 || lower.indexOf('clean') !== -1 || lower.indexOf('water') !== -1) {
                reply = 'Our Grade 5 titanium watches are 10 ATM water-resistant. Rinse with clean water and dry with a microfiber cloth.';
            }

            setTimeout(function () { appendMessage(reply, false); }, 300);
        }

        function escapeHtml(string) {
            var entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return String(string).replace(/[&<>"']/g, function (s) {
                return entityMap[s];
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatWidget);
    } else {
        initChatWidget();
    }

    window.SureSalezChat = {
        init: initChatWidget
    };
})();
