<?php
/**
 * Context-Aware Payment Gateway Router.
 *
 * @package SureSalez\Modules\Localization
 */

namespace SureSalez\Modules\Localization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Gateway_Router
 *
 * Dynamically routes and filters payment gateways based on customer billing/shipping
 * jurisdiction, selected currency, and gateway capabilities.
 */
class Gateway_Router {

    /**
     * Domestic gateway IDs for India.
     */
    public const DOMESTIC_IN_GATEWAYS = [
        'razorpay',
        'cashfree',
        'upi',
        'paytm',
        'phonepe',
        'suresalez_wallet',
        'cod',
    ];

    /**
     * International gateway IDs.
     */
    public const INTERNATIONAL_GATEWAYS = [
        'stripe',
        'stripe_cc',
        'paypal',
        'suresalez_wallet',
    ];

    /**
     * Supported settlement currencies by gateway.
     */
    public const GATEWAY_CURRENCIES = [
        'razorpay'         => ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD'],
        'cashfree'         => ['INR'],
        'upi'              => ['INR'],
        'paytm'            => ['INR'],
        'phonepe'          => ['INR'],
        'stripe'           => ['USD', 'EUR', 'GBP', 'INR', 'AED', 'JPY', 'AUD', 'CAD'],
        'stripe_cc'        => ['USD', 'EUR', 'GBP', 'INR', 'AED', 'JPY', 'AUD', 'CAD'],
        'paypal'           => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY'],
        'suresalez_wallet' => ['USD', 'EUR', 'GBP', 'INR', 'AED', 'JPY', 'AUD', 'CAD'],
        'cod'              => ['INR', 'AED', 'USD'],
    ];

    /**
     * Initialize gateway router filters.
     */
    public static function init(): void {
        add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'filter_woocommerce_gateways'], 20);
    }

    /**
     * Filter available WooCommerce payment gateways based on customer geography & currency.
     *
     * @param array $available_gateways
     * @return array
     */
    public static function filter_woocommerce_gateways($available_gateways) {
        if (is_admin() && !wp_doing_ajax()) {
            return $available_gateways;
        }

        if (!is_array($available_gateways) || empty($available_gateways)) {
            return $available_gateways;
        }

        // Determine destination country
        $country = '';
        if (function_exists('WC') && WC()->customer) {
            $country = WC()->customer->get_billing_country();
            if (empty($country)) {
                $country = WC()->customer->get_shipping_country();
            }
        }

        if (empty($country)) {
            $country = Currency_Switcher::detect_visitor_country();
        }

        $currency = Currency_Switcher::get_current_currency();

        return self::route_gateways($available_gateways, $country, $currency);
    }

    /**
     * Core pure-logic routing engine.
     *
     * @param array  $gateways Key-value or indexed array of gateways (objects or strings)
     * @param string $country  ISO 2-letter country code
     * @param string $currency ISO 3-letter currency code
     * @return array Filtered gateways
     */
    public static function route_gateways(array $gateways, string $country, string $currency): array {
        $country = strtoupper(trim($country));
        $currency = strtoupper(trim($currency));
        $is_india = ($country === 'IN');

        $filtered = [];

        foreach ($gateways as $id => $gateway) {
            $gateway_id = is_object($gateway) && isset($gateway->id) ? $gateway->id : (is_string($gateway) ? $gateway : (string)$id);

            // 1. Regional suitability check
            if ($is_india) {
                // If India, domestic gateways are prioritized.
                // Suppress foreign-only PayPal if paying in INR since PayPal INR domestic is restricted
                if ($gateway_id === 'paypal' && $currency === 'INR') {
                    continue;
                }
            } else {
                // If foreign/international customer, suppress domestic-only India gateways
                if (in_array($gateway_id, ['cashfree', 'upi', 'paytm', 'phonepe'], true)) {
                    continue;
                }
            }

            // 2. Settlement currency capability check
            if (isset(self::GATEWAY_CURRENCIES[$gateway_id])) {
                $supported_currencies = self::GATEWAY_CURRENCIES[$gateway_id];
                if (!in_array($currency, $supported_currencies, true)) {
                    // Gateway cannot settle in current checkout currency
                    continue;
                }
            }

            $filtered[$id] = $gateway;
        }

        return $filtered;
    }

    /**
     * Get structured metadata of recommended gateways for frontend REST/AJAX.
     *
     * @param string $country
     * @param string $currency
     * @return array
     */
    public static function get_recommended_gateways(string $country, string $currency): array {
        $country = strtoupper(trim($country));
        $currency = strtoupper(trim($currency));
        $is_india = ($country === 'IN');

        if ($is_india) {
            return [
                'market' => 'domestic_in',
                'country' => 'IN',
                'currency' => $currency,
                'gateways' => [
                    [
                        'id' => 'razorpay',
                        'title' => 'Razorpay (Cards, Netbanking, Wallets)',
                        'badge' => 'Instant',
                        'methods' => ['visa', 'mastercard', 'rupay', 'netbanking'],
                        'recommended' => true,
                    ],
                    [
                        'id' => 'upi',
                        'title' => 'UPI Instant QR & Intent (GPay / PhonePe / Paytm)',
                        'badge' => 'Zero Fee',
                        'methods' => ['gpay', 'phonepe', 'paytm', 'bhim'],
                        'recommended' => true,
                    ],
                    [
                        'id' => 'cashfree',
                        'title' => 'Cashfree Payments',
                        'badge' => '',
                        'methods' => ['cards', 'upi', 'netbanking'],
                        'recommended' => false,
                    ],
                    [
                        'id' => 'suresalez_wallet',
                        'title' => 'SureSalez Store Credit Wallet',
                        'badge' => 'Instant Checkout',
                        'methods' => ['wallet'],
                        'recommended' => false,
                    ],
                    [
                        'id' => 'cod',
                        'title' => 'Cash on Delivery',
                        'badge' => 'Pay at Doorstep',
                        'methods' => ['cash'],
                        'recommended' => false,
                    ],
                ],
            ];
        }

        // International market
        return [
            'market' => 'international',
            'country' => $country,
            'currency' => $currency,
            'gateways' => [
                [
                    'id' => 'stripe',
                    'title' => 'Credit / Debit Card (Stripe)',
                    'badge' => 'Secure 256-bit',
                    'methods' => ['visa', 'mastercard', 'amex', 'apple_pay', 'google_pay'],
                    'recommended' => true,
                ],
                [
                    'id' => 'paypal',
                    'title' => 'PayPal / Pay Later',
                    'badge' => 'Buyer Protection',
                    'methods' => ['paypal', 'venmo'],
                    'recommended' => in_array($currency, self::GATEWAY_CURRENCIES['paypal'], true),
                ],
                [
                    'id' => 'suresalez_wallet',
                    'title' => 'SureSalez Store Credit Wallet',
                    'badge' => 'Instant Checkout',
                    'methods' => ['wallet'],
                    'recommended' => false,
                ],
            ],
        ];
    }
}
