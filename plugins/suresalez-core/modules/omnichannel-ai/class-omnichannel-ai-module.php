<?php
/**
 * Omnichannel AI, Marketplace Feeds & Shoppable Reels Master Module.
 *
 * @package SureSalez\Modules\OmnichannelAI
 */

namespace SureSalez\Modules\OmnichannelAI;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Omnichannel_AI_Module
 *
 * Orchestrates Google/Meta/TikTok merchant feeds, conversational AI sales chat,
 * interactive shoppable video reels, server-side CAPI event tracking, and enterprise BI analytics.
 */
class Omnichannel_AI_Module extends Module_Base {

    /**
     * Get unique module identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return 'omnichannel-ai';
    }

    /**
     * Get human-readable module name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Omnichannel Feeds, AI Chat Commerce & Shoppable Reels';
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Google Merchant / Meta / TikTok catalog feeds, conversational AI sales assistant, shoppable vertical video reels, server-side CAPI tracking, and BI analytics.';
    }

    /**
     * Get module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'Marketing & Omnichannel';
    }

    /**
     * Whether module is active by default.
     *
     * @return bool
     */
    public function is_default_active(): bool {
        return true;
    }

    /**
     * Initialize module functionality (only executed if module is toggled ON).
     *
     * @return void
     */
    public function init(): void {
        // Load sub-components
        require_once __DIR__ . '/class-ai-chat-commerce.php';
        require_once __DIR__ . '/class-shoppable-reels.php';
        require_once __DIR__ . '/class-capi-dispatcher.php';
        require_once __DIR__ . '/class-marketplace-feeds.php';
        require_once __DIR__ . '/class-analytics-bi.php';

        // Initialize sub-engines
        AI_Chat_Commerce::instance()->init();
        Shoppable_Reels::instance()->init();
        CAPI_Dispatcher::instance()->init();
        Marketplace_Feeds::instance()->init();
        Analytics_BI::instance()->init();

        // Register client scripts & stylesheets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enqueue styles and scripts for shoppable reels and campaign banners.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        $css_url = plugins_url('assets/css/shoppable-reels.css', __FILE__);
        $js_url  = plugins_url('assets/js/shoppable-reels.js', __FILE__);
        $version = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.4.0';

        wp_enqueue_style(
            'suresalez-shoppable-reels',
            $css_url,
            [],
            $version
        );

        wp_enqueue_script(
            'suresalez-shoppable-reels',
            $js_url,
            [],
            $version,
            true
        );

        // Fetch published reels & active campaign data for instant client hydration
        $reels = Shoppable_Reels::instance()->get_published_reels();
        $campaign = Analytics_BI::instance()->get_active_campaign();

        wp_localize_script('suresalez-shoppable-reels', 'suresalezOmnichannelData', [
            'restUrl'     => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'       => wp_create_nonce('wp_rest'),
            'reels'       => $reels,
            'campaign'    => $campaign,
            'cartUrl'     => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/cart/',
            'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout/',
        ]);
    }
}
