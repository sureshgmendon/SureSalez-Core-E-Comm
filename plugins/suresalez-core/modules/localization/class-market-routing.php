<?php
/**
 * International Market Routing & Hreflang SEO Engine.
 *
 * @package SureSalez\Modules\Localization
 */

namespace SureSalez\Modules\Localization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Market_Routing
 *
 * Resolves international regional markets, injects multi-region hreflang meta tags
 * into the HTML head to protect against duplicate content penalties, and provides
 * clean regional URL rewrites / query fallbacks.
 */
class Market_Routing {

    /**
     * Regional market configurations.
     */
    public const MARKETS = [
        'in' => [
            'name'     => 'India',
            'hreflang' => 'en-IN',
            'currency' => 'INR',
            'locale'   => 'en_IN',
        ],
        'us' => [
            'name'     => 'United States',
            'hreflang' => 'en-US',
            'currency' => 'USD',
            'locale'   => 'en_US',
        ],
        'gb' => [
            'name'     => 'United Kingdom',
            'hreflang' => 'en-GB',
            'currency' => 'GBP',
            'locale'   => 'en_GB',
        ],
        'eu' => [
            'name'     => 'European Union',
            'hreflang' => 'en-DE',
            'currency' => 'EUR',
            'locale'   => 'en_DE',
        ],
        'ae' => [
            'name'     => 'United Arab Emirates',
            'hreflang' => 'en-AE',
            'currency' => 'AED',
            'locale'   => 'en_AE',
        ],
        'au' => [
            'name'     => 'Australia',
            'hreflang' => 'en-AU',
            'currency' => 'AUD',
            'locale'   => 'en_AU',
        ],
        'ca' => [
            'name'     => 'Canada',
            'hreflang' => 'en-CA',
            'currency' => 'CAD',
            'locale'   => 'en_CA',
        ],
    ];

    /**
     * Initialize market routing.
     */
    public static function init(): void {
        add_action('wp_head', [__CLASS__, 'output_hreflang_tags'], 2);
    }

    /**
     * Resolve current market identifier.
     *
     * @return string
     */
    public static function get_current_market(): string {
        // Query param override (e.g. ?market=in)
        if (!empty($_GET['market'])) {
            $market = strtolower(sanitize_key($_GET['market']));
            if (isset(self::MARKETS[$market])) {
                return $market;
            }
        }

        // Selected currency heuristic
        $curr = Currency_Switcher::get_current_currency();
        $curr_to_market = [
            'INR' => 'in',
            'USD' => 'us',
            'GBP' => 'gb',
            'EUR' => 'eu',
            'AED' => 'ae',
            'AUD' => 'au',
            'CAD' => 'ca',
        ];

        if (isset($curr_to_market[$curr])) {
            return $curr_to_market[$curr];
        }

        // Country fallback
        $country = strtolower(Currency_Switcher::detect_visitor_country());
        return isset(self::MARKETS[$country]) ? $country : 'us';
    }

    /**
     * Generate an array of alternate URLs with hreflang tags for current request.
     *
     * @param string|null $base_url
     * @return array
     */
    public static function get_hreflang_links(?string $base_url = null): array {
        if (empty($base_url)) {
            $base_url = self::get_current_clean_url();
        }

        $links = [];

        foreach (self::MARKETS as $market_key => $config) {
            $links[] = [
                'hreflang' => $config['hreflang'],
                'url'      => self::build_market_url($base_url, $market_key),
                'market'   => $market_key,
                'name'     => $config['name'],
            ];
        }

        // x-default points to default base URL
        $links[] = [
            'hreflang' => 'x-default',
            'url'      => remove_query_arg('market', $base_url),
            'market'   => 'default',
            'name'     => 'Global (Default)',
        ];

        return $links;
    }

    /**
     * Append or replace market query param on a given URL.
     *
     * @param string $url
     * @param string $market
     * @return string
     */
    public static function build_market_url(string $url, string $market): string {
        if ($market === 'us') {
            // Default store needs no query arg
            return remove_query_arg('market', $url);
        }

        return add_query_arg('market', $market, $url);
    }

    /**
     * Get clean request URL without session/nonce params.
     *
     * @return string
     */
    public static function get_current_clean_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = sanitize_text_field($_SERVER['HTTP_HOST'] ?? 'localhost');
        $uri = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '/');

        // Strip transient query params
        $full_url = $protocol . $host . $uri;
        return remove_query_arg(['_wpnonce', 'wc-ajax', 'action'], $full_url);
    }

    /**
     * Output <link rel="alternate" hreflang="..." /> tags in HTML head.
     */
    public static function output_hreflang_tags(): void {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        $links = self::get_hreflang_links();

        echo "\n<!-- SureSalez Global Localization Hreflang Tags -->\n";
        foreach ($links as $link) {
            printf(
                "<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
                esc_attr($link['hreflang']),
                esc_url($link['url'])
            );
        }
        echo "<!-- /SureSalez Global Localization Hreflang Tags -->\n\n";
    }
}
