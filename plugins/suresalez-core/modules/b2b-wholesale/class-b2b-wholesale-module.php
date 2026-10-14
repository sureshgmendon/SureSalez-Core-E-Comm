<?php
/**
 * B2B Wholesale Commerce, Organization Accounts & RFQ Quote Master Module.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

namespace SureSalez\Modules\B2BWholesale;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class B2B_Wholesale_Module
 *
 * Orchestrates multi-seat corporate accounts, dynamic wholesale price lists,
 * Net 15/30/60 purchase order checkout, quick bulk order CSV matrices,
 * and Request-For-Quote (RFQ) negotiation sessions.
 */
class B2B_Wholesale_Module extends Module_Base {

    /**
     * Get module identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return 'b2b-wholesale';
    }

    /**
     * Get module display name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'B2B Wholesale Accounts, Tiered Pricing & RFQ Quote Engine';
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Corporate organization hierarchies, custom price lists, Net-terms checkout, bulk CSV matrices, and RFQ quote negotiation.';
    }

    /**
     * Get module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'B2B Wholesale';
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
        require_once __DIR__ . '/class-b2b-organization.php';
        require_once __DIR__ . '/class-b2b-pricing.php';
        require_once __DIR__ . '/class-b2b-checkout.php';
        require_once __DIR__ . '/class-bulk-order.php';
        require_once __DIR__ . '/class-rfq-engine.php';

        // Initialize sub-engines
        B2B_Organization::instance()->init();
        B2B_Pricing::instance()->init();
        B2B_Checkout::instance()->init();
        Bulk_Order::instance()->init();
        RFQ_Engine::instance()->init();

        // Enqueue frontend scripts & styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enqueue styles and scripts for bulk order grid and RFQ portal.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        $version = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.6.0';

        wp_enqueue_style(
            'suresalez-b2b-bulk-order',
            plugins_url('assets/css/bulk-order.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_style(
            'suresalez-b2b-rfq-portal',
            plugins_url('assets/css/rfq-portal.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_script(
            'suresalez-b2b-bulk-order',
            plugins_url('assets/js/bulk-order.js', __FILE__),
            [],
            $version,
            true
        );

        wp_enqueue_script(
            'suresalez-b2b-rfq-portal',
            plugins_url('assets/js/rfq-portal.js', __FILE__),
            [],
            $version,
            true
        );

        $user_id = get_current_user_id() ?: 10;
        $company = B2B_Organization::instance()->get_user_company($user_id);

        wp_localize_script('suresalez-b2b-bulk-order', 'suresalezB2BData', [
            'restUrl'          => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'            => wp_create_nonce('wp_rest'),
            'currentCompany'   => $company ?: [
                'id'                 => 1,
                'company_name'       => 'Apex Chronometry Distributors Ltd',
                'payment_terms'      => 'net_30',
                'credit_limit'       => 50000.00,
                'approval_threshold' => 5000.00,
            ],
            'isB2B'            => !empty($company),
        ]);
    }
}
