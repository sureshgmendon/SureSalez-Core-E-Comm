<?php
/**
 * Order Routing, Split Shipments, 3PL/POD & Self-Service RMA Master Module.
 *
 * @package SureSalez\Modules\FulfillmentRMA
 */

namespace SureSalez\Modules\FulfillmentRMA;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Fulfillment_RMA_Module
 *
 * Orchestrates multi-warehouse proximity order routing, split shipments across 10 hubs,
 * 3PL/POD dropship purchase order dispatching, warehouse batch pick-pack, and self-service customer RMA returns.
 */
class Fulfillment_RMA_Module extends Module_Base {

    /**
     * Get module identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return 'fulfillment-rma';
    }

    /**
     * Get module display name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Order Routing, Split Shipments, 3PL/POD & Self-Service RMA';
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Multi-warehouse proximity routing, split shipments across 10 hubs, 3PL/POD dropship dispatching, warehouse batch pick-pack, and self-service customer RMA returns.';
    }

    /**
     * Get module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'Logistics & Fulfillment';
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
        require_once __DIR__ . '/class-order-router.php';
        require_once __DIR__ . '/class-batch-fulfillment.php';
        require_once __DIR__ . '/class-dropship-dispatcher.php';
        require_once __DIR__ . '/class-rma-engine.php';

        // Initialize sub-engines
        Order_Router::instance()->init();
        Batch_Fulfillment::instance()->init();
        Dropship_Dispatcher::instance()->init();
        RMA_Engine::instance()->init();

        // Enqueue frontend scripts & styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enqueue styles and scripts for RMA portal and order tracking widget.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        $version = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.5.0';

        wp_enqueue_style(
            'suresalez-rma-portal',
            plugins_url('assets/css/rma-portal.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_style(
            'suresalez-tracking-widget',
            plugins_url('assets/css/tracking-widget.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_script(
            'suresalez-rma-portal',
            plugins_url('assets/js/rma-portal.js', __FILE__),
            [],
            $version,
            true
        );

        wp_enqueue_script(
            'suresalez-tracking-widget',
            plugins_url('assets/js/tracking-widget.js', __FILE__),
            [],
            $version,
            true
        );

        wp_localize_script('suresalez-rma-portal', 'suresalezFulfillmentData', [
            'restUrl'          => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'            => wp_create_nonce('wp_rest'),
            'reasonCodes'      => RMA_Engine::REASON_CODES,
            'returnWindowDays' => 30,
        ]);
    }
}
