<?php
/**
 * Automated Tax Compliance, Indian GST Engine & Cross-Border Duties Calculator.
 *
 * @package SureSalez\Modules\Localization
 */

namespace SureSalez\Modules\Localization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Tax_Compliance
 *
 * Handles Indian GST (CGST/SGST vs IGST), GSTIN B2B invoice validation,
 * zero-rated export with LUT documentation, and cross-border import duty calculation.
 */
class Tax_Compliance {

    public const DEFAULT_ORIGIN_COUNTRY = 'IN';
    public const DEFAULT_ORIGIN_STATE   = 'KA'; // Karnataka
    public const DEFAULT_ORIGIN_STATE_CODE = '29';

    /**
     * Indian State Code Mapping (2-digit GST state code => 2-letter ISO state code).
     */
    public const GST_STATE_MAP = [
        '01' => 'JK', '02' => 'HP', '03' => 'PB', '04' => 'CH', '05' => 'UK',
        '06' => 'HR', '07' => 'DL', '08' => 'RJ', '09' => 'UP', '10' => 'BR',
        '11' => 'SK', '12' => 'AR', '13' => 'NL', '14' => 'MN', '15' => 'MZ',
        '16' => 'TR', '17' => 'ML', '18' => 'AS', '19' => 'WB', '20' => 'JH',
        '21' => 'OD', '22' => 'CG', '23' => 'MP', '24' => 'GJ', '26' => 'DD',
        '27' => 'MH', '29' => 'KA', '30' => 'GA', '31' => 'LD', '32' => 'KL',
        '33' => 'TN', '34' => 'PY', '35' => 'AN', '36' => 'TS', '37' => 'AD',
        '38' => 'LA',
    ];

    /**
     * Standard HSN tax rate overrides.
     */
    public const HSN_RATES = [
        '7117'    => 3.0,  // Imitation jewelry (HSN 7117: 3% GST)
        '7113'    => 3.0,  // Precious jewelry (HSN 7113: 3% GST)
        '6109'    => 5.0,  // Apparel / T-shirts (< ₹1000: 5% GST)
        'default' => 18.0, // Standard GST rate: 18%
    ];

    /**
     * Initialize tax hooks.
     */
    public static function init(): void {
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'apply_compliance_tax_fees'], 20);
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'inject_gstin_checkout_field']);
        add_action('woocommerce_checkout_process', [__CLASS__, 'validate_checkout_gstin_field']);
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_order_gstin_meta']);
    }

    /**
     * Get configured store origin state.
     *
     * @return string 2-letter state code (e.g. KA)
     */
    public static function get_origin_state(): string {
        return get_option('suresalez_store_origin_state', self::DEFAULT_ORIGIN_STATE);
    }

    /**
     * Validate an Indian GSTIN (Goods and Services Tax Identification Number).
     *
     * Format: 15 characters: 2 digits (state), 10 chars (PAN), 1 char (entity code), 1 char 'Z', 1 checksum.
     * Regex: ^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$
     *
     * @param string $gstin
     * @param string $expected_state 2-letter state code (optional verification)
     * @return array
     */
    public static function validate_gstin(string $gstin, string $expected_state = ''): array {
        $gstin = strtoupper(trim($gstin));

        if (empty($gstin)) {
            return [
                'valid'   => false,
                'message' => __('GSTIN is empty.', 'suresalez-core'),
            ];
        }

        $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';
        if (!preg_match($pattern, $gstin)) {
            return [
                'valid'   => false,
                'message' => __('Invalid GSTIN format. Must be a 15-character valid alphanumeric code (e.g., 29ABCDE1234F1Z5).', 'suresalez-core'),
            ];
        }

        $state_prefix = substr($gstin, 0, 2);
        $pan = substr($gstin, 2, 10);
        $mapped_state = self::GST_STATE_MAP[$state_prefix] ?? null;

        if (!$mapped_state) {
            return [
                'valid'   => false,
                'message' => sprintf(__('Invalid GSTIN state prefix code "%s".', 'suresalez-core'), $state_prefix),
            ];
        }

        if (!empty($expected_state) && strtoupper($expected_state) !== $mapped_state) {
            return [
                'valid'        => false,
                'message'      => sprintf(
                    __('GSTIN state prefix (%s - %s) does not match the billing state (%s).', 'suresalez-core'),
                    $state_prefix,
                    $mapped_state,
                    strtoupper($expected_state)
                ),
                'state_code'   => $mapped_state,
                'state_prefix' => $state_prefix,
                'pan'          => $pan,
            ];
        }

        return [
            'valid'        => true,
            'message'      => __('GSTIN is valid and verified.', 'suresalez-core'),
            'gstin'        => $gstin,
            'state_prefix' => $state_prefix,
            'state_code'   => $mapped_state,
            'pan'          => $pan,
            'is_business'  => true,
        ];
    }

    /**
     * Calculate comprehensive multi-jurisdiction taxes and duties.
     *
     * @param float       $subtotal       Taxable amount
     * @param string      $dest_country   ISO 2-letter country code
     * @param string      $dest_state     2-letter state code or region
     * @param array       $items          Line items with optional 'hsn' and 'price'
     * @param string|null $gstin          Optional customer GSTIN
     * @param string      $origin_country ISO 2-letter country code
     * @param string      $origin_state   2-letter state code
     * @return array
     */
    public static function calculate_taxes(
        float $subtotal,
        string $dest_country,
        string $dest_state,
        array $items = [],
        ?string $gstin = null,
        string $origin_country = self::DEFAULT_ORIGIN_COUNTRY,
        string $origin_state = self::DEFAULT_ORIGIN_STATE
    ): array {
        $dest_country = strtoupper(trim($dest_country));
        $dest_state   = strtoupper(trim($dest_state));
        $origin_country = strtoupper(trim($origin_country));
        $origin_state   = strtoupper(trim($origin_state));

        // Detect effective tax rate based on line item HSN
        $effective_rate = self::determine_effective_rate($items);

        // Case 1: Domestic India Supply
        if ($dest_country === 'IN') {
            return self::calculate_india_gst($subtotal, $dest_state, $origin_state, $effective_rate, $gstin);
        }

        // Case 2: Cross-Border Export from India
        if ($origin_country === 'IN' && $dest_country !== 'IN') {
            return self::calculate_export_compliance($subtotal, $dest_country, $dest_state, $items);
        }

        // Case 3: International standard tax
        return self::calculate_international_tax($subtotal, $dest_country, $dest_state);
    }

    /**
     * Determine effective tax rate from items or fallback.
     *
     * @param array $items
     * @return float
     */
    public static function determine_effective_rate(array $items): float {
        if (empty($items)) {
            return self::HSN_RATES['default'];
        }

        foreach ($items as $item) {
            $hsn = $item['hsn'] ?? '';
            if (!empty($hsn)) {
                $prefix4 = substr($hsn, 0, 4);
                if (isset(self::HSN_RATES[$prefix4])) {
                    return self::HSN_RATES[$prefix4];
                }
            }
        }

        return self::HSN_RATES['default'];
    }

    /**
     * Compute Indian GST: Intra-State (CGST + SGST) vs Inter-State (IGST).
     *
     * @param float       $subtotal
     * @param string      $dest_state
     * @param string      $origin_state
     * @param float       $gst_rate
     * @param string|null $gstin
     * @return array
     */
    public static function calculate_india_gst(
        float $subtotal,
        string $dest_state,
        string $origin_state,
        float $gst_rate,
        ?string $gstin = null
    ): array {
        $is_intra_state = ($dest_state === $origin_state);
        $gstin_verification = !empty($gstin) ? self::validate_gstin($gstin, $dest_state) : null;

        $breakdown = [];
        $total_tax = 0.0;

        if ($is_intra_state) {
            // Split into CGST (50%) and SGST (50%)
            $half_rate = $gst_rate / 2.0;
            $cgst_amount = round(($subtotal * $half_rate) / 100.0, 2);
            $sgst_amount = round(($subtotal * $half_rate) / 100.0, 2);
            $total_tax = round($cgst_amount + $sgst_amount, 2);

            $breakdown[] = [
                'type'   => 'CGST',
                'label'  => sprintf(__('Central GST (CGST %s%%)', 'suresalez-core'), $half_rate),
                'rate'   => $half_rate,
                'amount' => $cgst_amount,
            ];
            $breakdown[] = [
                'type'   => 'SGST',
                'label'  => sprintf(__('State GST (SGST %s%%)', 'suresalez-core'), $half_rate),
                'rate'   => $half_rate,
                'amount' => $sgst_amount,
            ];
        } else {
            // Integrated GST (IGST 100%)
            $igst_amount = round(($subtotal * $gst_rate) / 100.0, 2);
            $total_tax = $igst_amount;

            $breakdown[] = [
                'type'   => 'IGST',
                'label'  => sprintf(__('Integrated GST (IGST %s%%)', 'suresalez-core'), $gst_rate),
                'rate'   => $gst_rate,
                'amount' => $igst_amount,
            ];
        }

        return [
            'jurisdiction'        => 'IN',
            'tax_system'          => 'GST',
            'is_intra_state'      => $is_intra_state,
            'origin_state'        => $origin_state,
            'dest_state'          => $dest_state,
            'subtotal'            => $subtotal,
            'total_tax'           => $total_tax,
            'grand_total'         => round($subtotal + $total_tax, 2),
            'tax_breakdown'       => $breakdown,
            'is_export'           => false,
            'gstin'               => $gstin_verification && $gstin_verification['valid'] ? $gstin : null,
            'gstin_verification'  => $gstin_verification,
            'compliance_note'     => $gstin_verification && $gstin_verification['valid']
                ? __('B2B Tax Invoice eligible for Input Tax Credit (ITC).', 'suresalez-core')
                : __('Retail B2C Tax Invoice.', 'suresalez-core'),
        ];
    }

    /**
     * Compute Zero-Rated Export with LUT notice and cross-border duties estimation.
     *
     * @param float  $subtotal
     * @param string $dest_country
     * @param string $dest_state
     * @param array  $items
     * @return array
     */
    public static function calculate_export_compliance(
        float $subtotal,
        string $dest_country,
        string $dest_state,
        array $items
    ): array {
        // Zero-rate domestic tax under LUT bond
        $lut_notice = __('Supply meant for export under bond or letter of undertaking (LUT) without payment of integrated tax.', 'suresalez-core');

        // Estimate destination import duties & VAT
        $duty_info = self::estimate_destination_duties($subtotal, $dest_country);

        return [
            'jurisdiction'          => $dest_country,
            'tax_system'            => 'EXPORT_LUT',
            'is_intra_state'        => false,
            'origin_country'        => 'IN',
            'dest_country'          => $dest_country,
            'subtotal'              => $subtotal,
            'total_tax'             => 0.0, // Zero domestic tax
            'grand_total'           => round($subtotal + ($duty_info['precollected'] ? $duty_info['estimated_duty'] : 0.0), 2),
            'tax_breakdown'         => [
                [
                    'type'   => 'EXPORT_ZERO',
                    'label'  => __('Export Zero-Rated (0% GST under LUT)', 'suresalez-core'),
                    'rate'   => 0.0,
                    'amount' => 0.0,
                ],
            ],
            'is_export'             => true,
            'lut_notice'            => $lut_notice,
            'duties_precollected'   => $duty_info['precollected'],
            'delivery_duty_mode'    => $duty_info['precollected'] ? 'DDP' : 'DDU',
            'estimated_import_duty' => $duty_info['estimated_duty'],
            'estimated_import_tax'  => $duty_info['estimated_tax'],
            'duty_badge'            => $duty_info['badge'],
        ];
    }

    /**
     * Estimate destination import customs duty and import VAT/GST.
     *
     * @param float  $subtotal
     * @param string $country
     * @return array
     */
    public static function estimate_destination_duties(float $subtotal, string $country): array {
        // Destination matrix defaults
        $matrix = [
            'US' => ['duty_rate' => 0.0,  'tax_rate' => 0.0,  'precollected' => false, 'badge' => 'US Import: Duty-Free under $800 De Minimis'],
            'GB' => ['duty_rate' => 4.0,  'tax_rate' => 20.0, 'precollected' => true,  'badge' => 'UK DDP: 20% VAT & Customs Pre-collected'],
            'DE' => ['duty_rate' => 4.5,  'tax_rate' => 19.0, 'precollected' => true,  'badge' => 'EU IOSS: 19% German VAT Pre-collected'],
            'FR' => ['duty_rate' => 4.5,  'tax_rate' => 20.0, 'precollected' => true,  'badge' => 'EU IOSS: 20% French VAT Pre-collected'],
            'AE' => ['duty_rate' => 5.0,  'tax_rate' => 5.0,  'precollected' => false, 'badge' => 'UAE: 5% VAT & Duty payable upon delivery (DDU)'],
            'AU' => ['duty_rate' => 5.0,  'tax_rate' => 10.0, 'precollected' => true,  'badge' => 'AU DDP: 10% Australian GST Pre-collected'],
            'CA' => ['duty_rate' => 6.5,  'tax_rate' => 13.0, 'precollected' => false, 'badge' => 'CA: Canadian GST/HST assessed at customs (DDU)'],
        ];

        $rule = $matrix[$country] ?? ['duty_rate' => 5.0, 'tax_rate' => 10.0, 'precollected' => false, 'badge' => 'International Import (DDU)'];

        $duty = round(($subtotal * $rule['duty_rate']) / 100.0, 2);
        $tax  = round(($subtotal * $rule['tax_rate']) / 100.0, 2);

        return [
            'precollected'   => $rule['precollected'],
            'estimated_duty' => $duty,
            'estimated_tax'  => $tax,
            'badge'          => $rule['badge'],
        ];
    }

    /**
     * International non-Indian tax calculation.
     *
     * @param float  $subtotal
     * @param string $country
     * @param string $state
     * @return array
     */
    public static function calculate_international_tax(float $subtotal, string $country, string $state): array {
        // Query suresalez_tax_jurisdictions table if available
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_tax_jurisdictions';
        $rate = 0.0;
        $tax_type = 'TAX';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT tax_type, standard_rate FROM {$table} WHERE country_code = %s AND (state_zone = %s OR state_zone = '*') LIMIT 1",
                    $country,
                    $state
                ),
                ARRAY_A
            );
            if ($row) {
                $rate = floatval($row['standard_rate']);
                $tax_type = $row['tax_type'];
            }
        }

        // Fallback defaults
        if ($rate <= 0.0) {
            if (in_array($country, ['DE', 'FR', 'IT', 'ES', 'NL'], true)) {
                $rate = 20.0;
                $tax_type = 'VAT';
            } elseif ($country === 'GB') {
                $rate = 20.0;
                $tax_type = 'VAT';
            } elseif ($country === 'AU') {
                $rate = 10.0;
                $tax_type = 'GST';
            }
        }

        $tax_amount = round(($subtotal * $rate) / 100.0, 2);

        return [
            'jurisdiction'    => $country,
            'tax_system'      => $tax_type,
            'is_intra_state'  => false,
            'subtotal'        => $subtotal,
            'total_tax'       => $tax_amount,
            'grand_total'     => round($subtotal + $tax_amount, 2),
            'tax_breakdown'   => [
                [
                    'type'   => $tax_type,
                    'label'  => sprintf('%s (%s%%)', $tax_type, $rate),
                    'rate'   => $rate,
                    'amount' => $tax_amount,
                ],
            ],
            'is_export'       => false,
        ];
    }

    /**
     * Inject B2B GSTIN input field into checkout.
     *
     * @param array $fields
     * @return array
     */
    public static function inject_gstin_checkout_field(array $fields): array {
        $fields['billing']['billing_suresalez_gstin'] = [
            'type'        => 'text',
            'label'       => __('GSTIN (Optional for B2B Claim)', 'suresalez-core'),
            'placeholder' => 'e.g., 29ABCDE1234F1Z5',
            'required'    => false,
            'class'       => ['form-row-wide', 'suresalez-gstin-field'],
            'clear'       => true,
            'priority'    => 35,
        ];

        return $fields;
    }

    /**
     * Validate checkout GSTIN when entered by customer.
     */
    public static function validate_checkout_gstin_field(): void {
        if (!empty($_POST['billing_suresalez_gstin'])) {
            $gstin = sanitize_text_field($_POST['billing_suresalez_gstin']);
            $billing_state = !empty($_POST['billing_state']) ? sanitize_text_field($_POST['billing_state']) : '';
            $billing_country = !empty($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : '';

            if ($billing_country === 'IN') {
                $result = self::validate_gstin($gstin, $billing_state);
                if (!$result['valid']) {
                    if (function_exists('wc_add_notice')) {
                        wc_add_notice($result['message'], 'error');
                    }
                }
            }
        }
    }

    /**
     * Save GSTIN order metadata.
     *
     * @param int $order_id
     */
    public static function save_order_gstin_meta(int $order_id): void {
        if (!empty($_POST['billing_suresalez_gstin'])) {
            $gstin = sanitize_text_field($_POST['billing_suresalez_gstin']);
            update_post_meta($order_id, '_billing_suresalez_gstin', strtoupper($gstin));
        }
    }

    /**
     * Hook into WooCommerce cart fee calculations to reflect CGST/SGST/IGST breakdown.
     *
     * @param object $cart
     */
    public static function apply_compliance_tax_fees($cart): void {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }

        if (!$cart || !method_exists($cart, 'get_subtotal')) {
            return;
        }

        $country = '';
        $state   = '';

        if (function_exists('WC') && WC()->customer) {
            $country = WC()->customer->get_shipping_country() ?: WC()->customer->get_billing_country();
            $state   = WC()->customer->get_shipping_state() ?: WC()->customer->get_billing_state();
        }

        if (empty($country)) {
            $country = Currency_Switcher::detect_visitor_country();
        }

        // Only add compliance fee breakdown if native WC tax is not overriding
        // If customer is international and DDP is enabled, add customs duty fee
        if ($country !== 'IN') {
            $duty_info = self::estimate_destination_duties((float)$cart->get_subtotal(), $country);
            if ($duty_info['precollected'] && $duty_info['estimated_duty'] > 0) {
                $cart->add_fee(__('Customs & Import Duties (DDP Pre-collected)', 'suresalez-core'), $duty_info['estimated_duty'], false);
            }
        }
    }
}
