<?php
/**
 * Checkout Extensibility, Cart Drawer & 1-Click Upsells Module.
 *
 * @package SureSalez\Modules\CheckoutEngine
 */

namespace SureSalez\Modules\CheckoutEngine;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-cart-drawer.php';
require_once __DIR__ . '/class-checkout-customizer.php';
require_once __DIR__ . '/class-post-purchase-upsell.php';
require_once __DIR__ . '/class-abandoned-cart-recovery.php';

/**
 * Class Checkout_Engine_Module
 */
class Checkout_Engine_Module extends Module_Base {

    public function get_id(): string {
        return 'checkout-engine';
    }

    public function get_name(): string {
        return __('Checkout Extensibility, Cart Drawer & 1-Click Upsells', 'suresalez-core');
    }

    public function get_description(): string {
        return __('AJAX slide-out cart drawer with free shipping progress, single-page/multi-step checkout with field customizer, 1-click post-purchase upsells, and abandoned cart recovery.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Checkout & Cart';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        Abandoned_Cart_Recovery::init();
        add_action('wp_footer', [$this, 'inject_cart_drawer_html'], 30);
    }

    public function register_assets(): void {
        wp_enqueue_style(
            'suresalez-cart-drawer-css',
            $this->get_url('assets/css/cart-drawer.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-cart-drawer-js',
            $this->get_url('assets/js/cart-drawer.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        wp_enqueue_style(
            'suresalez-checkout-css',
            $this->get_url('assets/css/checkout.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-checkout-js',
            $this->get_url('assets/js/checkout.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        wp_enqueue_style(
            'suresalez-upsell-modal-css',
            $this->get_url('assets/css/upsell-modal.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-upsell-modal-js',
            $this->get_url('assets/js/upsell-modal.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        $cart_state = Cart_Drawer::get_cart_state();
        $checkout_config = Checkout_Customizer::get_config();

        wp_localize_script('suresalez-cart-drawer-js', 'SureSalezCheckoutData', [
            'cart'             => $cart_state,
            'config'           => $checkout_config,
            'cartEndpoints'    => [
                'items'  => rest_url(REST_API::NAMESPACE . '/cart-drawer/items'),
                'update' => rest_url(REST_API::NAMESPACE . '/cart-drawer/update'),
                'addon'  => rest_url(REST_API::NAMESPACE . '/cart-drawer/addon'),
                'notes'  => rest_url(REST_API::NAMESPACE . '/cart-drawer/notes'),
            ],
            'checkoutEndpoints'=> [
                'validate' => rest_url(REST_API::NAMESPACE . '/checkout/validate'),
                'process'  => rest_url(REST_API::NAMESPACE . '/checkout/process'),
                'capture'  => rest_url(REST_API::NAMESPACE . '/checkout/capture-abandoned'),
            ],
            'upsellEndpoints'  => [
                'offer'   => rest_url(REST_API::NAMESPACE . '/checkout/upsell-offer'),
                'accept'  => rest_url(REST_API::NAMESPACE . '/checkout/upsell-accept'),
                'decline' => rest_url(REST_API::NAMESPACE . '/checkout/upsell-decline'),
            ],
        ]);
    }

    public function register_rest_routes(): void {
        // Cart Drawer routes
        register_rest_route(REST_API::NAMESPACE, '/cart-drawer/items', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_cart_items'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/cart-drawer/update', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_update_cart_item'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/cart-drawer/addon', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_toggle_cart_addon'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/cart-drawer/notes', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_save_cart_notes'],
            'permission_callback' => '__return_true',
        ]);

        // Checkout & Validation routes
        register_rest_route(REST_API::NAMESPACE, '/checkout/config', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_checkout_config'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/checkout/validate', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_validate_checkout'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/checkout/process', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_process_checkout'],
            'permission_callback' => '__return_true',
        ]);

        // Upsell routes
        register_rest_route(REST_API::NAMESPACE, '/checkout/upsell-offer', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_upsell_offer'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/checkout/upsell-accept', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_accept_upsell'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/checkout/upsell-decline', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_decline_upsell'],
            'permission_callback' => '__return_true',
        ]);

        // Abandoned cart routes
        register_rest_route(REST_API::NAMESPACE, '/checkout/capture-abandoned', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_capture_abandoned'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/checkout/recover-cart', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_recover_cart'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_get_cart_items(): \WP_REST_Response {
        return new \WP_REST_Response(Cart_Drawer::get_cart_state(), 200);
    }

    public function rest_update_cart_item(\WP_REST_Request $request): \WP_REST_Response {
        $params     = $request->get_json_params() ?: $request->get_params();
        $product_id = (int) ($params['product_id'] ?? 0);
        $quantity   = (int) ($params['quantity'] ?? 1);

        $state = Cart_Drawer::update_item_quantity($product_id, $quantity);
        return new \WP_REST_Response($state, 200);
    }

    public function rest_toggle_cart_addon(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $addon  = sanitize_key($params['addon'] ?? '');
        $state  = !empty($params['state']);

        $result = Cart_Drawer::toggle_addon($addon, $state);
        return new \WP_REST_Response($result, 200);
    }

    public function rest_save_cart_notes(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $notes  = sanitize_textarea_field($params['notes'] ?? '');

        $result = Cart_Drawer::save_notes($notes);
        return new \WP_REST_Response($result, 200);
    }

    public function rest_get_checkout_config(): \WP_REST_Response {
        return new \WP_REST_Response(Checkout_Customizer::get_config(), 200);
    }

    public function rest_validate_checkout(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $res = Checkout_Customizer::validate_checkout_data($params);
        return new \WP_REST_Response($res, $res['valid'] ? 200 : 400);
    }

    public function rest_process_checkout(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $val = Checkout_Customizer::validate_checkout_data($params);

        if (!$val['valid']) {
            return new \WP_REST_Response(['success' => false, 'errors' => $val['errors']], 400);
        }

        $order_id = rand(98500, 99999);
        $cart = Cart_Drawer::get_cart_state();
        $product_ids = array_column($cart['items'], 'id');
        $upsell_offer = Post_Purchase_Upsell::get_eligible_offer($product_ids);

        return new \WP_REST_Response([
            'success'      => true,
            'order_id'     => $order_id,
            'total'        => $cart['total'],
            'has_upsell'   => ($upsell_offer !== null),
            'upsell_offer' => $upsell_offer,
            'redirect_url' => home_url('/checkout/order-received/?order=' . $order_id),
        ], 200);
    }

    public function rest_get_upsell_offer(\WP_REST_Request $request): \WP_REST_Response {
        $offer = Post_Purchase_Upsell::get_demo_offer();
        return new \WP_REST_Response(['offer' => $offer], 200);
    }

    public function rest_accept_upsell(\WP_REST_Request $request): \WP_REST_Response {
        $params   = $request->get_json_params() ?: $request->get_params();
        $offer_id = (int) ($params['offer_id'] ?? 1);
        $order_id = (int) ($params['order_id'] ?? 0);

        $res = Post_Purchase_Upsell::accept_offer($offer_id, $order_id);
        return new \WP_REST_Response($res, 200);
    }

    public function rest_decline_upsell(): \WP_REST_Response {
        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Upsell offer declined.', 'suresalez-core'),
        ], 200);
    }

    public function rest_capture_abandoned(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $res = Abandoned_Cart_Recovery::capture_checkout($params);
        return new \WP_REST_Response($res, 200);
    }

    public function rest_recover_cart(\WP_REST_Request $request): \WP_REST_Response {
        $token = sanitize_text_field($request->get_param('token') ?? '');
        $res = Abandoned_Cart_Recovery::restore_cart_by_token($token);
        return new \WP_REST_Response($res, $res['success'] ? 200 : 404);
    }

    public function inject_cart_drawer_html(): void {
        ?>
        <!-- SureSalez Core Slide-Out Cart Drawer Mount Anchor -->
        <div id="suresalez-cart-drawer-mount"></div>
        <div id="suresalez-post-purchase-modal-mount"></div>
        <?php
    }
}
