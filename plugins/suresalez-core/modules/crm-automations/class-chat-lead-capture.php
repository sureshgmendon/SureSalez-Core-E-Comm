<?php
/**
 * Conversational Live Chat & Exit-Intent Lead Capture Engine.
 *
 * @package SureSalez\Modules\CRMAutomations
 */

namespace SureSalez\Modules\CRMAutomations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Chat_Lead_Capture
 *
 * Provides intelligent autonomous e-commerce conversational bot logic,
 * WhatsApp Business click-to-chat fallback, and exit-intent promotional lead capture.
 */
class Chat_Lead_Capture {

    public const WHATSAPP_NUMBER = '+15550192834';

    /**
     * FAQ Knowledge Base patterns for the autonomous bot.
     */
    public const BOT_KNOWLEDGE_BASE = [
        'tracking' => [
            'patterns' => ['track', 'where is my order', 'tracking number', 'status of order', 'delivery status', 'shipped'],
            'reply'    => 'You can track any dispatch by providing your Order ID (e.g. #98450) or email. All orders route from our nearest regional hub with live milestone tracking.',
            'action'   => 'lookup_order',
        ],
        'returns' => [
            'patterns' => ['return', 'refund', 'exchange', 'rma', 'send back', 'money back'],
            'reply'    => 'SureSalez offers a 30-day hassle-free return and exchange guarantee on unworn items with original seals intact. You can generate a self-service prepaid shipping label directly in the RMA portal.',
            'action'   => 'link_rma',
        ],
        'shipping' => [
            'patterns' => ['shipping time', 'how long', 'delivery time', 'international shipping', 'express shipping', 'transit'],
            'reply'    => 'Orders placed before 2:00 PM are dispatched same-day from our 10 global fulfillment hubs. Domestic delivery arrives within 2-3 business days; international express takes 3-5 business days with prepaid DDP customs.',
            'action'   => null,
        ],
        'care' => [
            'patterns' => ['care', 'clean', 'scratch', 'water resistance', 'titanium', 'maintenance', 'waterproof'],
            'reply'    => 'Our Grade 5 titanium and sapphire timepieces are rated up to 10 ATM (100 meters). Rinse thoroughly with fresh water after salt or chlorinated exposure, and wipe with a microfiber lens cloth.',
            'action'   => null,
        ],
        'human' => [
            'patterns' => ['human', 'agent', 'support', 'talk to person', 'real person', 'representative'],
            'reply'    => 'Connecting you to our VIP Concierge team. You can also tap below to chat directly with a senior support engineer on WhatsApp Business.',
            'action'   => 'escalate_whatsapp',
        ],
    ];

    /**
     * Process an incoming message and return an autonomous assistant response.
     *
     * @param string $message
     * @return array
     */
    public static function generate_bot_response(string $message): array {
        $clean_msg = strtolower(trim($message));

        if (empty($clean_msg)) {
            return [
                'reply'      => "Hello! How can I assist you with your order, shipping, or product specifications today?",
                'category'   => 'greeting',
                'action'     => null,
                'is_bot'     => true,
                'timestamp'  => current_time('mysql'),
            ];
        }

        foreach (self::BOT_KNOWLEDGE_BASE as $category => $data) {
            foreach ($data['patterns'] as $pattern) {
                if (stripos($clean_msg, $pattern) !== false) {
                    return [
                        'reply'      => $data['reply'],
                        'category'   => $category,
                        'action'     => $data['action'],
                        'is_bot'     => true,
                        'timestamp'  => current_time('mysql'),
                    ];
                }
            }
        }

        // Default intelligent fallback
        return [
            'reply'      => "Thank you for reaching out! I've logged your inquiry with our sales and support team. For immediate assistance, tap below to connect with us on WhatsApp Business.",
            'category'   => 'fallback',
            'action'     => 'escalate_whatsapp',
            'is_bot'     => true,
            'timestamp'  => current_time('mysql'),
        ];
    }

    /**
     * Generate WhatsApp click-to-chat URL.
     *
     * @param string $initial_text
     * @return string
     */
    public static function get_whatsapp_url(string $initial_text = ''): string {
        $number = preg_replace('/[^0-9]/', '', self::WHATSAPP_NUMBER);
        $text = !empty($initial_text) ? urlencode($initial_text) : urlencode('Hi SureSalez, I need help with an order.');
        return 'https://wa.me/' . $number . '?text=' . $text;
    }

    /**
     * Render the Live Chat floating component HTML.
     *
     * @return string
     */
    public static function render_live_chat_widget(): string {
        ob_start();
        ?>
        <div id="suresalez-live-chat-mount" class="suresalez-chat-container">
            <!-- Floating Launcher Button -->
            <button type="button" class="suresalez-chat-launcher" aria-label="Open Live Chat">
                <span class="chat-icon">💬</span>
                <span class="chat-close-icon" style="display:none;">✕</span>
                <span class="chat-unread-dot"></span>
            </button>

            <!-- Chat Drawer Window -->
            <div class="suresalez-chat-window" style="display:none;" role="dialog" aria-modal="true" aria-label="Customer Support Chat">
                <div class="suresalez-chat-header">
                    <div class="chat-agent-info">
                        <div class="agent-avatar">🤖</div>
                        <div>
                            <div class="agent-name">SureSalez AI Sales Concierge</div>
                            <div class="agent-status"><span class="status-indicator"></span> Active &bull; Instant Reply</div>
                        </div>
                    </div>
                    <button type="button" class="chat-close-btn" aria-label="Close Chat">✕</button>
                </div>

                <div class="suresalez-chat-messages" id="suresalez-chat-messages-log">
                    <div class="chat-bubble bot-bubble">
                        <p>Hello! Welcome to SureSalez. I can answer questions about shipping timelines, 30-day returns, or product specs.</p>
                        <span class="bubble-time"><?php echo esc_html(current_time('H:i')); ?></span>
                    </div>
                </div>

                <div class="suresalez-chat-quick-actions">
                    <button type="button" class="chat-quick-btn" data-query="Where is my order?">📦 Track Order</button>
                    <button type="button" class="chat-quick-btn" data-query="Return policy & RMA">🔄 Returns</button>
                    <button type="button" class="chat-quick-btn" data-query="Care instructions">🛡️ Care</button>
                    <a href="<?php echo esc_url(self::get_whatsapp_url('Hi, I need assistance with an item.')); ?>" target="_blank" rel="noopener noreferrer" class="chat-quick-btn whatsapp-btn">📱 WhatsApp</a>
                </div>

                <form class="suresalez-chat-input-bar" id="suresalez-chat-form">
                    <input type="text" id="suresalez-chat-input" placeholder="Type a message or order ID..." autocomplete="off">
                    <button type="submit" aria-label="Send Message">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the Exit-Intent Lead Capture Modal HTML.
     *
     * @return string
     */
    public static function render_lead_capture_modal(): string {
        ob_start();
        ?>
        <div id="suresalez-lead-capture-mount" class="suresalez-modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-label="Exclusive VIP Offer">
            <div class="suresalez-modal-card lead-capture-card">
                <button type="button" class="modal-close-btn" id="suresalez-lead-close" aria-label="Close Modal">✕</button>
                <div class="lead-modal-content">
                    <span class="lead-pill">⚡ Limited First-Order Benefit</span>
                    <h2 class="lead-title">Unlock \$25 Off Your First Order</h2>
                    <p class="lead-desc">Join 40,000+ luxury timepiece collectors. Enter your email to instantly receive an exclusive VIP welcome voucher code.</p>
                    
                    <form id="suresalez-lead-capture-form" class="lead-form">
                        <input type="email" id="suresalez-lead-email" placeholder="Enter your best email address..." required>
                        <button type="submit" class="lead-submit-btn">Get My \$25 Voucher &rarr;</button>
                    </form>
                    <div id="suresalez-lead-success" style="display:none;" class="lead-success-msg">
                        🎉 Voucher Code: <strong>WELCOME25</strong> has been copied and applied to your cart!
                    </div>
                    <div class="lead-disclaimer">Zero spam. Unsubscribe in 1-click anytime.</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
