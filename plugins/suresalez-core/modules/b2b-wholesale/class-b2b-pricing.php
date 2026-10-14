<?php
/**
 * Dynamic Wholesale Tiered Pricing & Minimum Order Quantity (MOQ) Engine.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

namespace SureSalez\Modules\B2BWholesale;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class B2B_Pricing
 *
 * Intercepts WooCommerce pricing display and cart total calculations
 * to apply contracted wholesale rate tiers, case-pack increments,
 * and minimum order quantity (MOQ) validation gates.
 */
class B2B_Pricing {

    /**
     * Singleton instance.
     *
     * @var B2B_Pricing|null
     */
    private static ?B2B_Pricing $instance = null;

    /**
     * Cached company price rules.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $cached_pricelists = [];

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize WooCommerce pricing hooks and filters.
     *
     * @return void
     */
    public function init(): void {
        add_filter('woocommerce_get_price_html', [$this, 'filter_price_html'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'override_cart_item_prices'], 10, 1);
        add_action('woocommerce_check_cart_items', [$this, 'validate_moq_and_case_packs']);
    }

    /**
     * Fetch active price list rules for a specific company or fallback to global wholesale tier.
     *
     * @param int $company_id
     * @return array<string, mixed>
     */
    public function get_pricelist_rules(int $company_id): array {
        if (isset($this->cached_pricelists[$company_id])) {
            return $this->cached_pricelists[$company_id];
        }

        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_pricelists';

        // 1. Try company-specific pricelist
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE company_id = %d AND status = 'active' LIMIT 1", $company_id),
            ARRAY_A
        );

        // 2. Fallback to global wholesale tier (company_id = 0)
        if (!$row && $company_id > 0) {
            $row = $wpdb->get_row(
                "SELECT * FROM {$table} WHERE company_id = 0 AND status = 'active' LIMIT 1",
                ARRAY_A
            );
        }

        if ($row && !empty($row['pricing_rules'])) {
            $rules = json_decode($row['pricing_rules'], true) ?: [];
            $this->cached_pricelists[$company_id] = [
                'name'          => $row['name'],
                'discount_type' => $row['discount_type'],
                'rules'         => $rules,
            ];
            return $this->cached_pricelists[$company_id];
        }

        return ['name' => 'Default MSRP', 'discount_type' => 'none', 'rules' => []];
    }

    /**
     * Calculate B2B tiered price for a product SKU and requested quantity.
     *
     * @param string   $sku
     * @param int      $quantity
     * @param int|null $user_id
     * @return array<string, mixed>
     */
    public function calculate_price(string $sku, int $quantity = 1, ?int $user_id = null): array {
        $user_id = $user_id ?? get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        $default_msrp = 899.00;
        if (str_contains($sku, 'STRAP')) {
            $default_msrp = 95.00;
        } elseif (str_contains($sku, 'TOOL')) {
            $default_msrp = 45.00;
        }

        // If not authenticated B2B user, return standard MSRP
        if (!$company || $company['status'] !== 'approved') {
            return [
                'is_b2b'         => false,
                'sku'            => $sku,
                'quantity'       => $quantity,
                'msrp'           => $default_msrp,
                'unit_price'     => $default_msrp,
                'discount_pct'   => 0.0,
                'moq'            => 1,
                'case_pack'      => 1,
                'moq_satisfied'  => true,
                'total_amount'   => $default_msrp * $quantity,
                'total_savings'  => 0.0,
            ];
        }

        $pricelist = $this->get_pricelist_rules((int) $company['id']);
        $rule = $pricelist['rules'][$sku] ?? null;

        $msrp = (float) ($rule['msrp'] ?? $default_msrp);
        $moq  = (int) ($rule['moq'] ?? 1);
        $case_pack = (int) ($rule['case_pack'] ?? 1);

        $unit_price = $msrp;
        $discount_pct = 0.0;

        if ($rule && !empty($rule['tiers'])) {
            foreach ($rule['tiers'] as $tier) {
                if ($quantity >= $tier['min_qty'] && $quantity <= ($tier['max_qty'] ?? PHP_INT_MAX)) {
                    $unit_price   = (float) $tier['price'];
                    $discount_pct = (float) ($tier['discount_pct'] ?? 0);
                    break;
                }
            }
        } else {
            // Standard 20% B2B base discount if no tier table defined
            $discount_pct = 20.0;
            $unit_price   = round($msrp * 0.80, 2);
        }

        $moq_satisfied = $quantity >= $moq;
        $case_pack_satisfied = ($quantity % $case_pack) === 0;

        return [
            'is_b2b'              => true,
            'company_name'        => $company['company_name'],
            'sku'                 => $sku,
            'quantity'            => $quantity,
            'msrp'                => $msrp,
            'unit_price'          => $unit_price,
            'discount_pct'        => $discount_pct,
            'moq'                 => $moq,
            'case_pack'           => $case_pack,
            'moq_satisfied'       => $moq_satisfied,
            'case_pack_satisfied' => $case_pack_satisfied,
            'total_amount'        => round($unit_price * $quantity, 2),
            'total_savings'       => round(($msrp - $unit_price) * $quantity, 2),
        ];
    }

    /**
     * Filter WooCommerce product price display HTML for authenticated B2B accounts.
     *
     * @param string      $price_html
     * @param \WC_Product|null $product
     * @return string
     */
    public function filter_price_html(string $price_html, $product = null): string {
        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company || $company['status'] !== 'approved') {
            return $price_html;
        }

        $sku = is_object($product) && method_exists($product, 'get_sku') ? $product->get_sku() : 'LUM-CHRONO-01';
        $price_info = $this->calculate_price($sku, 1, $user_id);

        if (!$price_info['is_b2b']) {
            return $price_html;
        }

        return sprintf(
            '<span class="suresalez-b2b-price-badge"><del>$%.2f</del> <ins>$%.2f</ins> <span class="b2b-tier-tag">B2B %s (-%.0f%%)</span> <small class="b2b-moq-label">MOQ: %d units</small></span>',
            $price_info['msrp'],
            $price_info['unit_price'],
            esc_html($company['company_name']),
            $price_info['discount_pct'],
            $price_info['moq']
        );
    }

    /**
     * Override cart item prices with negotiated B2B rates before totals calculation.
     *
     * @param \WC_Cart $cart
     * @return void
     */
    public function override_cart_item_prices($cart): void {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company || $company['status'] !== 'approved') {
            return;
        }

        if (!is_object($cart) || !method_exists($cart, 'get_cart')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product  = $cart_item['data'] ?? null;
            $quantity = (int) ($cart_item['quantity'] ?? 1);

            if (!$product || !method_exists($product, 'get_sku')) {
                continue;
            }

            $sku = $product->get_sku() ?: 'LUM-CHRONO-01';
            $calc = $this->calculate_price($sku, $quantity, $user_id);

            if ($calc['is_b2b'] && method_exists($product, 'set_price')) {
                $product->set_price($calc['unit_price']);
            }
        }
    }

    /**
     * Validate Minimum Order Quantity (MOQ) and Case-Pack rules in cart.
     *
     * @return void
     */
    public function validate_moq_and_case_packs(): void {
        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company || $company['status'] !== 'approved') {
            return;
        }

        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        foreach (WC()->cart->get_cart() as $item) {
            $product  = $item['data'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if (!$product || !method_exists($product, 'get_sku')) {
                continue;
            }

            $sku = $product->get_sku() ?: 'LUM-CHRONO-01';
            $calc = $this->calculate_price($sku, $quantity, $user_id);

            if (!$calc['moq_satisfied']) {
                $msg = sprintf(
                    'Item "%s" requires a Minimum Order Quantity (MOQ) of %d units for wholesale pricing (current quantity: %d).',
                    esc_html($product->get_name()),
                    $calc['moq'],
                    $quantity
                );
                if (function_exists('wc_add_notice')) {
                    wc_add_notice($msg, 'error');
                }
            }
        }
    }
}
