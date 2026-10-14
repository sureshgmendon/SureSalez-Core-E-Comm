<?php
/**
 * Plugin Name: SureSalez Core
 * Plugin URI: https://suresalez.com
 * Description: Modular, enterprise-grade, decoupled e-commerce engine with multi-warehouse inventory, digital asset management, visual swatches, and enterprise review workflows.
 * Version: 1.0.0
 * Author: SureSalez Systems Architecture Team
 * Author URI: https://suresalez.com
 * Text Domain: suresalez-core
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SureSalez\Core
 */

namespace SureSalez\Core;

if (!defined('ABSPATH')) {
    exit;
}

// Global Plugin Constants
define('SURESALEZ_VERSION', '1.0.0');
define('SURESALEZ_FILE', __FILE__);
define('SURESALEZ_PATH', plugin_dir_path(__FILE__));
define('SURESALEZ_URL', plugin_dir_url(__FILE__));
define('SURESALEZ_BASENAME', plugin_basename(__FILE__));

/**
 * Main Singleton Bootstrap Class for SureSalez Core.
 */
final class Core {

    /**
     * Singleton instance.
     *
     * @var Core|null
     */
    private static ?Core $instance = null;

    /**
     * Get singleton instance.
     *
     * @return Core
     */
    public static function instance(): Core {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
        $this->init_hooks();
    }

    /**
     * Require internal framework dependencies.
     *
     * @return void
     */
    private function load_dependencies(): void {
        require_once SURESALEZ_PATH . 'includes/class-module-base.php';
        require_once SURESALEZ_PATH . 'includes/class-module-manager.php';
        require_once SURESALEZ_PATH . 'includes/class-database.php';
        require_once SURESALEZ_PATH . 'includes/class-rest-api.php';
    }

    /**
     * Discover and register all feature sub-modules.
     *
     * @return void
     */
    private function init_modules(): void {
        $manager = Module_Manager::instance();

        // Sprint 01 Modules
        require_once SURESALEZ_PATH . 'modules/catalog-pim/class-catalog-pim-module.php';
        require_once SURESALEZ_PATH . 'modules/multi-location-inventory/class-multi-location-inventory-module.php';
        require_once SURESALEZ_PATH . 'modules/visual-swatches/class-visual-swatches-module.php';
        require_once SURESALEZ_PATH . 'modules/reviews-qa/class-reviews-qa-module.php';

        $manager->register_module(new \SureSalez\Modules\CatalogPIM\Catalog_PIM_Module());
        $manager->register_module(new \SureSalez\Modules\MultiLocationInventory\Multi_Location_Inventory_Module());
        $manager->register_module(new \SureSalez\Modules\VisualSwatches\Visual_Swatches_Module());
        $manager->register_module(new \SureSalez\Modules\ReviewsQA\Reviews_QA_Module());

        // Sprint 02 Modules
        require_once SURESALEZ_PATH . 'modules/checkout-engine/class-checkout-engine-module.php';
        require_once SURESALEZ_PATH . 'modules/wallet/class-wallet-module.php';

        $manager->register_module(new \SureSalez\Modules\CheckoutEngine\Checkout_Engine_Module());
        $manager->register_module(new \SureSalez\Modules\Wallet\Wallet_Module());

        // Sprint 03 Modules
        require_once SURESALEZ_PATH . 'modules/localization/class-localization-module.php';
        $manager->register_module(new \SureSalez\Modules\Localization\Localization_Module());

        // Sprint 04 Modules
        require_once SURESALEZ_PATH . 'modules/crm-automations/class-crm-automations-module.php';
        $manager->register_module(new \SureSalez\Modules\CRMAutomations\CRM_Automations_Module());

        // Sprint 05 Modules
        require_once SURESALEZ_PATH . 'modules/omnichannel-ai/class-omnichannel-ai-module.php';
        $manager->register_module(new \SureSalez\Modules\OmnichannelAI\Omnichannel_AI_Module());

        // Sprint 06 Modules
        require_once SURESALEZ_PATH . 'modules/fulfillment-rma/class-fulfillment-rma-module.php';
        $manager->register_module(new \SureSalez\Modules\FulfillmentRMA\Fulfillment_RMA_Module());

        // Sprint 07 Modules
        require_once SURESALEZ_PATH . 'modules/b2b-wholesale/class-b2b-wholesale-module.php';
        $manager->register_module(new \SureSalez\Modules\B2BWholesale\B2B_Wholesale_Module());

        // Sprint 08-10 module placeholders (scaffolded for architectural completion)
        $this->register_roadmap_modules($manager);
    }

    /**
     * Register placeholder module instances for upcoming roadmap sprints.
     *
     * @param Module_Manager $manager
     * @return void
     */
    private function register_roadmap_modules(Module_Manager $manager): void {
        $roadmap_defs = [
            'pos-marketplace-subscriptions' => [
                'name'     => 'In-Store POS, Marketplace & Subscriptions',
                'desc'     => 'Progressive Web App retail POS, multi-vendor commission payouts, and recurring subscription billing.',
                'category' => 'Commerce Channels',
                'default'  => false,
            ],
            'loyalty-mobile-apis' => [
                'name'     => 'Loyalty Points, 301 Redirects & Headless Mobile APIs',
                'desc'     => 'Gamified VIP tiers, sub-millisecond regex 301 redirects, and high-performance headless mobile REST/GraphQL APIs.',
                'category' => 'Retention & Scale',
                'default'  => false,
            ],
            'multistore-billing' => [
                'name'     => 'Multi-Store Organization Admin & Tenant Billing',
                'desc'     => 'Multi-store tenant orchestration, reseller white-label billing, and Redis edge caching for extreme scale.',
                'category' => 'Enterprise & Scale',
                'default'  => false,
            ],
        ];

        foreach ($roadmap_defs as $id => $info) {
            $manager->register_module(new class($id, $info['name'], $info['desc'], $info['category'], $info['default']) extends Module_Base {
                public function __construct(
                    private string $id,
                    private string $name,
                    private string $desc,
                    private string $category,
                    private bool $default
                ) {}
                public function get_id(): string { return $this->id; }
                public function get_name(): string { return $this->name; }
                public function get_description(): string { return $this->desc; }
                public function get_category(): string { return $this->category; }
                public function is_default_active(): bool { return $this->default; }
                public function init(): void {
                    // Hooks for roadmap module execute only if active
                }
            });
        }
    }

    /**
     * Initialize core hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        REST_API::init();
        add_action('init', [$this, 'register_product_cpt_fallback'], 5);
    }

    /**
     * Fallback registration for 'product' post type if WooCommerce is not loaded.
     * Ensures our custom metafields, REST routes, and templates function independently.
     *
     * @return void
     */
    public function register_product_cpt_fallback(): void {
        if (!post_type_exists('product')) {
            register_post_type('product', [
                'labels' => [
                    'name'          => __('Products', 'suresalez-core'),
                    'singular_name' => __('Product', 'suresalez-core'),
                ],
                'public'       => true,
                'has_archive'  => true,
                'show_in_rest' => true,
                'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'menu_icon'    => 'dashicons-products',
            ]);
        }
    }
}

// Lifecycle Hooks
register_activation_hook(__FILE__, function () {
    require_once SURESALEZ_PATH . 'includes/class-database.php';
    Database::create_tables();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Bootstrap Core Engine
add_action('plugins_loaded', function () {
    Core::instance();
});
