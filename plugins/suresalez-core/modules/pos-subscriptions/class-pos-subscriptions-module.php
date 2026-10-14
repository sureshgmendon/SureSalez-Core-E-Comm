<?php
/**
 * In-Store POS Touch Interface, Multi-Vendor Marketplace & Subscriptions Master Module.
 *
 * @package SureSalez\Modules\POSSubscriptions
 */

namespace SureSalez\Modules\POSSubscriptions;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class POS_Subscriptions_Module
 *
 * Orchestrates retail touch point-of-sale registers, multi-vendor marketplace
 * commission ledgers, and automated recurring subscription lifecycle management.
 */
class POS_Subscriptions_Module extends Module_Base {

    /**
     * Get module identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return 'pos-subscriptions';
    }

    /**
     * Get module display name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'In-Store POS Touch Interface, Multi-Vendor Marketplace & Subscriptions';
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Progressive Web App retail POS, multi-vendor commission payouts, and recurring subscription billing.';
    }

    /**
     * Get module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'Commerce Channels';
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
     * Initialize sub-components when module is active.
     *
     * @return void
     */
    public function init(): void {
        // Load sub-components
        require_once __DIR__ . '/class-pos-terminal.php';
        require_once __DIR__ . '/class-marketplace-vendor.php';
        require_once __DIR__ . '/class-subscription-engine.php';

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Register daily cron hook for subscription renewal
        add_action('suresalez_daily_subscription_renewal', [$this, 'dispatch_daily_renewals']);
        if (!wp_next_scheduled('suresalez_daily_subscription_renewal')) {
            wp_schedule_event(time(), 'daily', 'suresalez_daily_subscription_renewal');
        }

        // Enqueue frontend scripts & styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Run daily automated renewal dispatcher.
     *
     * @return void
     */
    public function dispatch_daily_renewals(): void {
        Subscription_Engine::instance()->process_due_renewals();
    }

    /**
     * Register REST API Endpoints.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        $namespace = 'suresalez/v1';

        // 1. POS Checkout
        register_rest_route($namespace, '/pos/checkout', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_pos_checkout'],
            'permission_callback' => '__return_true',
        ]);

        // 2. POS BOPIS Collect
        register_rest_route($namespace, '/pos/bopis/collect', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_pos_bopis_collect'],
            'permission_callback' => '__return_true',
        ]);

        // 3. Vendor Dashboard
        register_rest_route($namespace, '/vendor/dashboard', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_vendor_dashboard'],
            'permission_callback' => '__return_true',
        ]);

        // 4. Vendor Payout Request
        register_rest_route($namespace, '/vendor/payout-request', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_vendor_payout_request'],
            'permission_callback' => '__return_true',
        ]);

        // 5. Subscription Skip Delivery
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/skip', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_subscription_skip'],
            'permission_callback' => '__return_true',
        ]);

        // 6. Subscription Pause
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/pause', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_subscription_pause'],
            'permission_callback' => '__return_true',
        ]);

        // 7. Subscription Resume
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/resume', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_subscription_resume'],
            'permission_callback' => '__return_true',
        ]);

        // 8. Subscription Cancel
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/cancel', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_subscription_cancel'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST Handler: POS Checkout.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_pos_checkout(\WP_REST_Request $request): \WP_REST_Response {
        $params          = $request->get_json_params() ?: [];
        $register_id     = (int) ($params['register_id'] ?? 1);
        $customer_id     = (int) ($params['customer_id'] ?? 0);
        $items           = (array) ($params['items'] ?? []);
        $payment_method  = sanitize_text_field($params['payment_method'] ?? 'cash');
        $amount_tendered = (float) ($params['amount_tendered'] ?? 0.0);
        $discount_amount = (float) ($params['discount_amount'] ?? 0.0);
        $wallet_balance  = (float) ($params['wallet_balance'] ?? 0.0);

        if (empty($items)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $result = POS_Terminal::instance()->process_checkout(
            $register_id,
            $customer_id,
            $items,
            $payment_method,
            $amount_tendered,
            $discount_amount,
            $wallet_balance
        );

        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * REST Handler: BOPIS Order Collect.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_pos_bopis_collect(\WP_REST_Request $request): \WP_REST_Response {
        $params      = $request->get_json_params() ?: [];
        $pickup_code = sanitize_text_field($params['pickup_code'] ?? '');
        $staff_id    = (int) ($params['staff_id'] ?? get_current_user_id() ?: 1);

        $result = POS_Terminal::instance()->fulfill_bopis_pickup($pickup_code, $staff_id);
        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * REST Handler: Vendor Dashboard.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_vendor_dashboard(\WP_REST_Request $request): \WP_REST_Response {
        $vendor_id = (int) $request->get_param('vendor_id');
        if ($vendor_id <= 0) {
            $vendor_id = 1; // Default vendor profile
        }

        $result = Marketplace_Vendor::instance()->get_vendor_dashboard($vendor_id);
        return new \WP_REST_Response($result, 200);
    }

    /**
     * REST Handler: Vendor Payout Request.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_vendor_payout_request(\WP_REST_Request $request): \WP_REST_Response {
        $params    = $request->get_json_params() ?: [];
        $vendor_id = (int) ($params['vendor_id'] ?? 1);
        $amount    = (float) ($params['amount'] ?? 0.0);

        $result = Marketplace_Vendor::instance()->request_payout($vendor_id, $amount);
        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * REST Handler: Skip Subscription Delivery.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_subscription_skip(\WP_REST_Request $request): \WP_REST_Response {
        $sub_id = (int) $request->get_param('id');
        $result = Subscription_Engine::instance()->skip_delivery($sub_id);
        return new \WP_REST_Response($result, 200);
    }

    /**
     * REST Handler: Pause Subscription.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_subscription_pause(\WP_REST_Request $request): \WP_REST_Response {
        $sub_id = (int) $request->get_param('id');
        $result = Subscription_Engine::instance()->pause_subscription($sub_id);
        return new \WP_REST_Response($result, 200);
    }

    /**
     * REST Handler: Resume Subscription.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_subscription_resume(\WP_REST_Request $request): \WP_REST_Response {
        $sub_id = (int) $request->get_param('id');
        $result = Subscription_Engine::instance()->resume_subscription($sub_id);
        return new \WP_REST_Response($result, 200);
    }

    /**
     * REST Handler: Cancel Subscription.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_subscription_cancel(\WP_REST_Request $request): \WP_REST_Response {
        $sub_id = (int) $request->get_param('id');
        $result = Subscription_Engine::instance()->cancel_subscription($sub_id);
        return new \WP_REST_Response($result, 200);
    }

    /**
     * Enqueue styles and scripts for POS Terminal, Vendor Portal, and Subscriptions.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        $version = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.7.0';

        // Styles
        wp_enqueue_style(
            'suresalez-pos-screen',
            plugins_url('assets/css/pos-screen.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_style(
            'suresalez-vendor-portal',
            plugins_url('assets/css/vendor-portal.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_style(
            'suresalez-subscription-portal',
            plugins_url('assets/css/subscription-portal.css', __FILE__),
            [],
            $version
        );

        // Scripts
        wp_enqueue_script(
            'suresalez-pos-screen',
            plugins_url('assets/js/pos-screen.js', __FILE__),
            [],
            $version,
            true
        );

        wp_enqueue_script(
            'suresalez-vendor-portal',
            plugins_url('assets/js/vendor-portal.js', __FILE__),
            [],
            $version,
            true
        );

        wp_enqueue_script(
            'suresalez-subscription-portal',
            plugins_url('assets/js/subscription-portal.js', __FILE__),
            [],
            $version,
            true
        );

        wp_localize_script('suresalez-pos-screen', 'suresalezPOSData', [
            'restUrl'         => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'           => wp_create_nonce('wp_rest'),
            'registerId'      => 1,
            'registerName'    => 'Register 01 - Manhattan 5th Ave',
            'locationCode'    => 'US-EAST-01',
            'openingCash'     => 500.00,
            'defaultTaxRate'  => 0.0825,
            'catalogProducts' => [
                ['id' => 101, 'sku' => 'LUM-CHRONO-01', 'name' => 'Luminor Chrono Automatic', 'price' => 899.00, 'category' => 'Watches', 'barcode' => '8901234567890'],
                ['id' => 102, 'sku' => 'LUM-STRAP-BLK', 'name' => 'Horween Leather Strap 22mm', 'price' => 95.00, 'category' => 'Accessories', 'barcode' => '8901234567891'],
                ['id' => 103, 'sku' => 'LUM-TOOL-01', 'name' => 'Titanium Spring Bar Tool', 'price' => 45.00, 'category' => 'Tools', 'barcode' => '8901234567892'],
                ['id' => 104, 'sku' => 'LUM-CARE-KIT', 'name' => 'Ultrasonic Polishing Care Kit', 'price' => 29.00, 'category' => 'Care', 'barcode' => '8901234567893'],
            ],
        ]);
    }
}
