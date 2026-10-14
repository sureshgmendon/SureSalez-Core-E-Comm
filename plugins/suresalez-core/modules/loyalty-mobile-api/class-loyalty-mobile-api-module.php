<?php
/**
 * Loyalty, VIP Tiers, 301 Redirects & Mobile API Module Definition.
 *
 * @package SureSalez\Modules\LoyaltyMobileAPI
 */

namespace SureSalez\Modules\LoyaltyMobileAPI;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-loyalty-engine.php';
require_once __DIR__ . '/class-referral-program.php';
require_once __DIR__ . '/class-seo-manager.php';
require_once __DIR__ . '/class-mobile-api.php';

/**
 * Class Loyalty_Mobile_API_Module
 *
 * Master module orchestrating gamified loyalty rewards, VIP tiers,
 * automated 301 redirects, JSON-LD Schema.org, and headless mobile APIs.
 */
class Loyalty_Mobile_API_Module extends Module_Base {

    public const MODULE_ID = 'loyalty-mobile-api';

    /**
     * Module slug identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return self::MODULE_ID;
    }

    /**
     * Module display title.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Loyalty Points, VIP Tiers, 301 SEO Redirects & Headless Mobile APIs';
    }

    /**
     * Module feature description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Gamified VIP tiers, points redemption slider, viral referrals, sub-millisecond 301 SEO redirects, and headless mobile REST APIs.';
    }

    /**
     * Module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'Retention & Mobile';
    }

    /**
     * Default active state.
     *
     * @return bool
     */
    public function is_default_active(): bool {
        return true;
    }

    /**
     * Initialize module hooks.
     * Only executed if module is active.
     *
     * @return void
     */
    public function init(): void {
        // 301 SEO Redirects & Schema.org Hooks
        add_action('template_redirect', [SEO_Manager::class, 'handle_pre_query_redirects'], 1);
        add_action('post_updated', [SEO_Manager::class, 'handle_slug_change'], 10, 3);
        add_action('wp_head', [SEO_Manager::class, 'output_schema_in_head']);

        // Viral Referral Cookie Attribution
        add_action('init', [Referral_Program::class, 'track_incoming_referral']);

        // Frontend Scripts & Styles
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // REST API Routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register & enqueue module CSS & JS assets.
     *
     * @return void
     */
    public function register_assets(): void {
        if (!$this->is_active()) {
            return;
        }

        $ver = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.8.0';

        // Loyalty Rewards Portal
        wp_enqueue_style(
            'suresalez-loyalty-portal-css',
            $this->get_url('assets/css/loyalty-portal.css'),
            [],
            $ver
        );

        wp_enqueue_script(
            'suresalez-loyalty-portal-js',
            $this->get_url('assets/js/loyalty-portal.js'),
            [],
            $ver,
            true
        );

        // Referral Sharing Banner
        wp_enqueue_style(
            'suresalez-referral-banner-css',
            $this->get_url('assets/css/referral-banner.css'),
            [],
            $ver
        );

        wp_enqueue_script(
            'suresalez-referral-banner-js',
            $this->get_url('assets/js/referral-banner.js'),
            [],
            $ver,
            true
        );

        $current_user_id = get_current_user_id() ?: 10;
        $loyalty_data    = Loyalty_Engine::get_account($current_user_id);
        $referral_stats  = Referral_Program::get_referral_stats($current_user_id);

        wp_localize_script('suresalez-loyalty-portal-js', 'suresalezLoyaltyData', [
            'rest_url'      => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'         => wp_create_nonce('wp_rest'),
            'loyalty'       => $loyalty_data,
            'referral'      => $referral_stats,
            'point_value'   => Loyalty_Engine::POINTS_VALUE_PER_POINT,
            'min_points'    => Loyalty_Engine::MIN_REDEMPTION_POINTS,
            'max_percent'   => Loyalty_Engine::MAX_REDEMPTION_PERCENT,
        ]);
    }

    /**
     * Register module REST API endpoints.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        // Register Headless Mobile App endpoints
        Mobile_API::register_routes();

        // Calculate checkout points redemption
        register_rest_route('suresalez/v1', '/loyalty/calculate-redemption', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_calculate_redemption'],
            'permission_callback' => '__return_true',
        ]);

        // Validate friend referral code
        register_rest_route('suresalez/v1', '/referrals/validate', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_validate_referral'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST Handler: Calculate redemption discount.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_calculate_redemption(\WP_REST_Request $request): \WP_REST_Response {
        $params     = $request->get_json_params() ?: [];
        $points     = (int) ($params['points'] ?? 0);
        $cart_total = (float) ($params['cart_total'] ?? 0.0);
        $user_id    = (int) ($params['user_id'] ?? get_current_user_id() ?: 10);

        $account = Loyalty_Engine::get_account($user_id);
        $result  = Loyalty_Engine::calculate_redemption($points, $cart_total, $account['points_balance']);

        return new \WP_REST_Response($result, $result['valid'] ? 200 : 400);
    }

    /**
     * REST Handler: Validate referral code and calculate discount.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_validate_referral(\WP_REST_Request $request): \WP_REST_Response {
        $params   = $request->get_json_params() ?: [];
        $code     = sanitize_text_field($params['referral_code'] ?? '');
        $subtotal = (float) ($params['subtotal'] ?? 0.0);
        $user_id  = (int) ($params['user_id'] ?? get_current_user_id() ?: 0);

        $result = Referral_Program::calculate_friend_discount($subtotal, $code, $user_id);

        return new \WP_REST_Response($result, $result['valid'] ? 200 : 400);
    }
}
