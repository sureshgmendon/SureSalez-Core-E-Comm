<?php
/**
 * Geo-IP & Multi-Currency Switcher Engine.
 *
 * @package SureSalez\Modules\Localization
 */

namespace SureSalez\Modules\Localization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Currency_Switcher
 *
 * Resolves visitor geolocation via IP/headers, manages active currencies,
 * enforces psychological rounding (.99, .00, nearest integer), and persists
 * visitor selection via secure cookies.
 */
class Currency_Switcher {

    public const COOKIE_NAME = 'suresalez_selected_currency';
    public const BASE_CURRENCY = 'USD';

    /**
     * Get all active currencies from database or fallback defaults.
     *
     * @return array
     */
    public static function get_active_currencies(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_currencies';

        $results = $wpdb->get_results(
            "SELECT code, symbol, name, rate_to_base, rounding_rule FROM {$table} WHERE is_active = 1 ORDER BY id ASC",
            ARRAY_A
        );

        if (!empty($results)) {
            $currencies = [];
            foreach ($results as $row) {
                $currencies[$row['code']] = [
                    'code'          => $row['code'],
                    'symbol'        => $row['symbol'],
                    'name'          => $row['name'],
                    'rate'          => floatval($row['rate_to_base']),
                    'rounding_rule' => $row['rounding_rule'],
                ];
            }
            return $currencies;
        }

        // Fallback default active currencies
        return [
            'USD' => ['code' => 'USD', 'symbol' => '$',   'name' => 'US Dollar',        'rate' => 1.000000, 'rounding_rule' => 'dot_99'],
            'EUR' => ['code' => 'EUR', 'symbol' => '€',   'name' => 'Euro',             'rate' => 0.920000, 'rounding_rule' => 'dot_99'],
            'GBP' => ['code' => 'GBP', 'symbol' => '£',   'name' => 'British Pound',    'rate' => 0.780000, 'rounding_rule' => 'dot_99'],
            'INR' => ['code' => 'INR', 'symbol' => '₹',   'name' => 'Indian Rupee',     'rate' => 84.500000,'rounding_rule' => 'nearest_integer'],
            'AED' => ['code' => 'AED', 'symbol' => 'د.إ', 'name' => 'UAE Dirham',       'rate' => 3.670000, 'rounding_rule' => 'dot_00'],
            'JPY' => ['code' => 'JPY', 'symbol' => '¥',   'name' => 'Japanese Yen',     'rate' => 152.000000,'rounding_rule' => 'nearest_integer'],
            'AUD' => ['code' => 'AUD', 'symbol' => 'A$',  'name' => 'Australian Dollar','rate' => 1.540000, 'rounding_rule' => 'dot_99'],
            'CAD' => ['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian Dollar',  'rate' => 1.380000, 'rounding_rule' => 'dot_99'],
        ];
    }

    /**
     * Resolve visitor's country code based on headers / IP.
     *
     * @return string ISO 2-letter country code
     */
    public static function detect_visitor_country(): string {
        // 1. Cloudflare header
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']));
        }

        // 2. Server GeoIP environment
        if (!empty($_SERVER['GEOIP_COUNTRY_CODE'])) {
            return strtoupper(sanitize_text_field($_SERVER['GEOIP_COUNTRY_CODE']));
        }

        // 3. Fallback to Accept-Language
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);
            if (stripos($lang, 'IN') !== false || stripos($lang, 'hi') !== false) {
                return 'IN';
            }
            if (stripos($lang, 'GB') !== false) {
                return 'GB';
            }
            if (stripos($lang, 'DE') !== false) {
                return 'DE';
            }
            if (stripos($lang, 'JP') !== false) {
                return 'JP';
            }
        }

        return 'US'; // Default fallback
    }

    /**
     * Map country code to default currency.
     *
     * @param string $country_code
     * @return string
     */
    public static function get_default_currency_for_country(string $country_code): string {
        $map = [
            'IN' => 'INR',
            'GB' => 'GBP',
            'DE' => 'EUR',
            'FR' => 'EUR',
            'IT' => 'EUR',
            'ES' => 'EUR',
            'NL' => 'EUR',
            'AE' => 'AED',
            'JP' => 'JPY',
            'AU' => 'AUD',
            'CA' => 'CAD',
            'US' => 'USD',
        ];

        return $map[strtoupper($country_code)] ?? self::BASE_CURRENCY;
    }

    /**
     * Get active selected currency (checks cookie first, then geo-detection).
     *
     * @return string
     */
    public static function get_current_currency(): string {
        $currencies = self::get_active_currencies();

        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $cookie_curr = strtoupper(sanitize_key($_COOKIE[self::COOKIE_NAME]));
            if (isset($currencies[$cookie_curr])) {
                return $cookie_curr;
            }
        }

        $detected_country = self::detect_visitor_country();
        $mapped_curr = self::get_default_currency_for_country($detected_country);

        return isset($currencies[$mapped_curr]) ? $mapped_curr : self::BASE_CURRENCY;
    }

    /**
     * Set selected currency in cookie.
     *
     * @param string $currency_code
     * @return bool
     */
    public static function set_currency(string $currency_code): bool {
        $currencies = self::get_active_currencies();
        $clean = strtoupper(sanitize_key($currency_code));

        if (!isset($currencies[$clean])) {
            return false;
        }

        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $clean, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false);
        }
        $_COOKIE[self::COOKIE_NAME] = $clean;

        do_action('suresalez_currency_changed', $clean);
        return true;
    }

    /**
     * Convert and format price from base USD into target currency with psychological rounding.
     *
     * @param float       $price_usd
     * @param string|null $currency_code
     * @return array Array with ['formatted' => string, 'amount' => float, 'currency' => string, 'symbol' => string]
     */
    public static function convert_price(float $price_usd, ?string $currency_code = null): array {
        $currencies = self::get_active_currencies();
        $target = $currency_code ? strtoupper($currency_code) : self::get_current_currency();

        if (!isset($currencies[$target])) {
            $target = self::BASE_CURRENCY;
        }

        $curr_info = $currencies[$target];
        $raw_converted = $price_usd * $curr_info['rate'];
        $rounded = self::apply_psychological_rounding($raw_converted, $curr_info['rounding_rule']);

        $decimals = in_array($target, ['JPY', 'INR', 'KRW'], true) ? 0 : 2;
        $formatted = $curr_info['symbol'] . number_format($rounded, $decimals);

        return [
            'formatted' => $formatted,
            'amount'    => $rounded,
            'currency'  => $target,
            'symbol'    => $curr_info['symbol'],
            'rate'      => $curr_info['rate'],
        ];
    }

    /**
     * Apply psychological retail charm pricing rules.
     *
     * @param float  $amount
     * @param string $rule
     * @return float
     */
    public static function apply_psychological_rounding(float $amount, string $rule): float {
        switch ($rule) {
            case 'dot_99':
                // E.g. $14.32 -> $14.99; $14.00 -> $14.99
                return floor($amount) + 0.99;

            case 'dot_00':
                // Clean rounded hundredths
                return round($amount);

            case 'nearest_integer':
                // E.g. INR 75,980 or JPY 136,000
                return round($amount);

            default:
                return round($amount, 2);
        }
    }
}
