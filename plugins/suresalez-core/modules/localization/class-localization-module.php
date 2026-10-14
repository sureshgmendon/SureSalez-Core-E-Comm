<?php
/**
 * Global Localization, Multi-Currency & Compliance Module.
 *
 * @package SureSalez\Modules\Localization
 */

namespace SureSalez\Modules\Localization;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-currency-switcher.php';
require_once __DIR__ . '/class-gateway-router.php';
require_once __DIR__ . '/class-tax-compliance.php';
require_once __DIR__ . '/class-market-routing.php';

/**
 * Class Localization_Module
 */
class Localization_Module extends Module_Base {

    public function get_id(): string {
        return 'localization';
    }

    public function get_name(): string {
        return __('Global Localization, Multi-Currency & Compliance', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Real-time multi-currency switcher with charm pricing, context-aware payment gateway routing, Indian GST (CGST/SGST/IGST + LUT) and cross-border duties estimation, and international hreflang SEO.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Localization & Compliance';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        Gateway_Router::init();
        Tax_Compliance::init();
        Market_Routing::init();

        // Hook into WooCommerce active currency display
        add_filter('woocommerce_currency', [__CLASS__, 'filter_woocommerce_currency'], 20);

        // Register shortcode for currency switcher
        add_shortcode('suresalez_currency_switcher', [$this, 'render_currency_switcher_shortcode']);

        // Injected render helper for footer mount fallback
        add_action('wp_footer', [$this, 'inject_currency_switcher_fallback'], 25);
    }

    /**
     * Filter active WooCommerce currency.
     *
     * @param string $currency
     * @return string
     */
    public static function filter_woocommerce_currency($currency) {
        if (is_admin() && !wp_doing_ajax()) {
            return $currency;
        }

        return Currency_Switcher::get_current_currency();
    }

    public function register_assets(): void {
        wp_enqueue_style(
            'suresalez-currency-switcher-css',
            $this->get_url('assets/css/currency-switcher.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-currency-switcher-js',
            $this->get_url('assets/js/currency-switcher.js'),
            ['jquery'],
            SURESALEZ_VERSION,
            true
        );

        wp_localize_script(
            'suresalez-currency-switcher-js',
            'sureSalezLocalizationConfig',
            [
                'ajaxUrl'          => admin_url('admin-ajax.php'),
                'restUrl'          => esc_url_raw(rest_url('suresalez/v1/localization/')),
                'nonce'            => wp_create_nonce('wp_rest'),
                'currentCurrency'  => Currency_Switcher::get_current_currency(),
                'detectedCountry'  => Currency_Switcher::detect_visitor_country(),
                'activeCurrencies' => Currency_Switcher::get_active_currencies(),
            ]
        );

        wp_enqueue_style(
            'suresalez-tax-compliance-css',
            $this->get_url('assets/css/tax-compliance.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-tax-compliance-js',
            $this->get_url('assets/js/tax-compliance.js'),
            ['jquery'],
            SURESALEZ_VERSION,
            true
        );

        wp_localize_script(
            'suresalez-tax-compliance-js',
            'sureSalezTaxConfig',
            [
                'restUrl'     => esc_url_raw(rest_url('suresalez/v1/localization/tax-calculate')),
                'nonce'       => wp_create_nonce('wp_rest'),
                'originState' => Tax_Compliance::get_origin_state(),
            ]
        );
    }

    public function register_routes(): void {
        // 1. Get Currencies
        REST_API::register_route('/localization/currencies', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_currencies'],
            'permission_callback' => '__return_true',
        ]);

        // 2. Switch Currency
        REST_API::register_route('/localization/set-currency', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_set_currency'],
            'permission_callback' => '__return_true',
            'args'                => [
                'currency' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // 3. Tax & Duty Calculation
        REST_API::register_route('/localization/tax-calculate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_calculate_tax'],
            'permission_callback' => '__return_true',
            'args'                => [
                'subtotal' => [
                    'required' => true,
                    'type'     => 'number',
                ],
                'country'  => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'state'    => [
                    'required' => false,
                    'type'     => 'string',
                    'default'  => '',
                ],
                'gstin'    => [
                    'required' => false,
                    'type'     => 'string',
                    'default'  => '',
                ],
            ],
        ]);

        // 4. Validate GSTIN
        REST_API::register_route('/localization/validate-gstin', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_validate_gstin'],
            'permission_callback' => '__return_true',
            'args'                => [
                'gstin' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'state' => [
                    'required' => false,
                    'type'     => 'string',
                    'default'  => '',
                ],
            ],
        ]);

        // 5. Gateway Recommendations
        REST_API::register_route('/localization/gateways', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_gateways'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST: Get currencies.
     */
    public function rest_get_currencies(\WP_REST_Request $request): \WP_REST_Response {
        $currencies = Currency_Switcher::get_active_currencies();
        $current = Currency_Switcher::get_current_currency();
        $detected_country = Currency_Switcher::detect_visitor_country();

        return new \WP_REST_Response([
            'success'          => true,
            'current_currency' => $current,
            'detected_country' => $detected_country,
            'currencies'       => array_values($currencies),
        ], 200);
    }

    /**
     * REST: Switch currency.
     */
    public function rest_set_currency(\WP_REST_Request $request): \WP_REST_Response {
        $currency = strtoupper(sanitize_text_field($request->get_param('currency')));
        $success = Currency_Switcher::set_currency($currency);

        if (!$success) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => sprintf(__('Unsupported currency code: %s', 'suresalez-core'), $currency),
            ], 400);
        }

        return new \WP_REST_Response([
            'success'          => true,
            'current_currency' => $currency,
            'message'          => sprintf(__('Switched store currency to %s', 'suresalez-core'), $currency),
        ], 200);
    }

    /**
     * REST: Calculate taxes & duties.
     */
    public function rest_calculate_tax(\WP_REST_Request $request): \WP_REST_Response {
        $subtotal = floatval($request->get_param('subtotal'));
        $country  = strtoupper(sanitize_text_field($request->get_param('country')));
        $state    = strtoupper(sanitize_text_field($request->get_param('state') ?? ''));
        $gstin    = sanitize_text_field($request->get_param('gstin') ?? '');
        $items    = (array)($request->get_param('items') ?? []);

        $result = Tax_Compliance::calculate_taxes($subtotal, $country, $state, $items, $gstin);

        return new \WP_REST_Response([
            'success' => true,
            'data'    => $result,
        ], 200);
    }

    /**
     * REST: Validate GSTIN.
     */
    public function rest_validate_gstin(\WP_REST_Request $request): \WP_REST_Response {
        $gstin = sanitize_text_field($request->get_param('gstin'));
        $state = sanitize_text_field($request->get_param('state') ?? '');

        $result = Tax_Compliance::validate_gstin($gstin, $state);

        return new \WP_REST_Response([
            'success' => $result['valid'],
            'data'    => $result,
        ], 200);
    }

    /**
     * REST: Get recommended payment gateways.
     */
    public function rest_get_gateways(\WP_REST_Request $request): \WP_REST_Response {
        $country = sanitize_text_field($request->get_param('country') ?: Currency_Switcher::detect_visitor_country());
        $currency = sanitize_text_field($request->get_param('currency') ?: Currency_Switcher::get_current_currency());

        $data = Gateway_Router::get_recommended_gateways($country, $currency);

        return new \WP_REST_Response([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    /**
     * Shortcode renderer: [suresalez_currency_switcher]
     */
    public function render_currency_switcher_shortcode(): string {
        $currencies = Currency_Switcher::get_active_currencies();
        $current = Currency_Switcher::get_current_currency();

        ob_start();
        ?>
        <div class="suresalez-currency-switcher-dropdown">
            <button type="button" class="suresalez-currency-btn" aria-haspopup="listbox" aria-expanded="false">
                <span class="curr-flag"><?php echo esc_html(self::get_flag_emoji($current)); ?></span>
                <span class="curr-code"><?php echo esc_html($current); ?></span>
                <span class="curr-symbol">(<?php echo esc_html($currencies[$current]['symbol'] ?? ''); ?>)</span>
                <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <ul class="suresalez-currency-options" role="listbox">
                <?php foreach ($currencies as $code => $curr): ?>
                    <li class="suresalez-curr-item <?php echo $code === $current ? 'is-active' : ''; ?>" data-currency="<?php echo esc_attr($code); ?>" role="option">
                        <span class="curr-flag"><?php echo esc_html(self::get_flag_emoji($code)); ?></span>
                        <span class="curr-title"><?php echo esc_html($curr['name']); ?></span>
                        <span class="curr-code-badge"><?php echo esc_html($code); ?> (<?php echo esc_html($curr['symbol']); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Injects currency switcher if not already placed by FSE header/footer template.
     */
    public function inject_currency_switcher_fallback(): void {
        // Output template container if needed by frontend JS mounting
        ?>
        <div id="suresalez-currency-switcher-fallback" style="display:none;">
            <?php echo $this->render_currency_switcher_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
    }

    /**
     * Simple flag emoji helper.
     */
    public static function get_flag_emoji(string $curr_code): string {
        $map = [
            'USD' => '🇺🇸',
            'EUR' => '🇪🇺',
            'GBP' => '🇬🇧',
            'INR' => '🇮🇳',
            'AED' => '🇦🇪',
            'JPY' => '🇯🇵',
            'AUD' => '🇦🇺',
            'CAD' => '🇨🇦',
        ];
        return $map[$curr_code] ?? '🌐';
    }
}
