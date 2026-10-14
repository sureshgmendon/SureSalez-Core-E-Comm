<?php
/**
 * Net Terms & Purchase Order (PO) Checkout Engine.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

namespace SureSalez\Modules\B2BWholesale;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class B2B_Checkout
 *
 * Manages Net 15/30/60 corporate payment terms, credit limit validation,
 * mandatory PO number collection, order status routing, and pro-forma invoice rendering.
 */
class B2B_Checkout {

    /**
     * Singleton instance.
     *
     * @var B2B_Checkout|null
     */
    private static ?B2B_Checkout $instance = null;

    /**
     * Allowed payment terms and day offsets.
     *
     * @var array<string, int>
     */
    public const TERMS_DAYS = [
        'prepaid' => 0,
        'net_15'  => 15,
        'net_30'  => 30,
        'net_60'  => 60,
    ];

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
     * Initialize WooCommerce checkout hooks.
     *
     * @return void
     */
    public function init(): void {
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_b2b_gateways'], 20, 1);
        add_action('woocommerce_after_order_notes', [$this, 'render_po_number_field']);
        add_action('woocommerce_checkout_process', [$this, 'validate_po_number_field']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_b2b_order_meta'], 10, 2);
    }

    /**
     * Check if Net Terms are available for the current buyer and order amount.
     *
     * @param int   $user_id
     * @param float $order_total
     * @return array{available: bool, terms: string, reason: string, available_credit: float}
     */
    public function check_net_terms_eligibility(int $user_id, float $order_total): array {
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company) {
            return [
                'available'        => false,
                'terms'            => 'prepaid',
                'reason'           => 'User is not associated with an authorized B2B corporate account.',
                'available_credit' => 0.0,
            ];
        }

        if ($company['status'] !== 'approved') {
            return [
                'available'        => false,
                'terms'            => 'prepaid',
                'reason'           => 'Corporate account approval status is ' . $company['status'] . '.',
                'available_credit' => 0.0,
            ];
        }

        if ($company['payment_terms'] === 'prepaid') {
            return [
                'available'        => false,
                'terms'            => 'prepaid',
                'reason'           => 'Corporate account is configured for prepaid terms only.',
                'available_credit' => 0.0,
            ];
        }

        // Calculate currently outstanding open invoice balance
        global $wpdb;
        $orders_table = $wpdb->prefix . 'suresalez_b2b_orders';
        $open_balance = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(p.meta_value), 0)
                 FROM {$orders_table} o
                 INNER JOIN {$wpdb->postmeta} p ON p.post_id = o.order_id AND p.meta_key = '_order_total'
                 WHERE o.company_id = %d AND o.approval_status != 'rejected'",
                $company['id']
            )
        );

        $available_credit = max(0.0, $company['credit_limit'] - $open_balance);

        if ($order_total > $available_credit) {
            return [
                'available'        => false,
                'terms'            => $company['payment_terms'],
                'reason'           => sprintf(
                    'Order total ($%.2f) exceeds remaining available credit line ($%.2f of $%.2f limit).',
                    $order_total,
                    $available_credit,
                    $company['credit_limit']
                ),
                'available_credit' => $available_credit,
            ];
        }

        return [
            'available'        => true,
            'terms'            => $company['payment_terms'],
            'reason'           => 'Approved for ' . strtoupper(str_replace('_', ' ', $company['payment_terms'])),
            'available_credit' => $available_credit,
        ];
    }

    /**
     * Filter payment gateways to expose Net Terms gateway for eligible B2B buyers.
     *
     * @param array<string, mixed> $gateways
     * @return array<string, mixed>
     */
    public function filter_b2b_gateways(array $gateways): array {
        $user_id = get_current_user_id();
        $total = function_exists('WC') && WC()->cart ? (float) WC()->cart->get_total('edit') : 0.0;

        $eligibility = $this->check_net_terms_eligibility($user_id, $total);

        if ($eligibility['available']) {
            // Enable Net Terms payment method
            $gateways['suresalez_net_terms'] = (object) [
                'id'          => 'suresalez_net_terms',
                'title'       => sprintf('Corporate Purchase Order (%s)', strtoupper(str_replace('_', ' ', $eligibility['terms']))),
                'description' => sprintf('Invoice will be payable within %d days. PO number required.', self::TERMS_DAYS[$eligibility['terms']] ?? 30),
                'enabled'     => 'yes',
            ];
        }

        return $gateways;
    }

    /**
     * Render mandatory PO number field on checkout.
     *
     * @return void
     */
    public function render_po_number_field(): void {
        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company || $company['status'] !== 'approved') {
            return;
        }

        echo '<div class="suresalez-b2b-checkout-section" style="margin-top:20px; padding:16px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px;">';
        echo '<h3 style="font-size:16px; font-weight:700; margin:0 0 8px 0;">Corporate Purchase Order Details</h3>';
        echo '<p style="font-size:13px; color:#64748b; margin:0 0 12px 0;">Purchasing on behalf of <strong>' . esc_html($company['company_name']) . '</strong> (' . esc_html($company['payment_terms']) . ').</p>';
        
        woocommerce_form_field('suresalez_b2b_po_number', [
            'type'        => 'text',
            'class'       => ['form-row-wide'],
            'label'       => 'Purchase Order (PO) Number',
            'placeholder' => 'e.g. PO-2026-APEX-098',
            'required'    => true,
        ]);
        echo '</div>';
    }

    /**
     * Validate PO number on checkout submission.
     *
     * @return void
     */
    public function validate_po_number_field(): void {
        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company || $company['status'] !== 'approved') {
            return;
        }

        if (empty($_POST['suresalez_b2b_po_number'])) {
            wc_add_notice('Purchase Order (PO) Number is mandatory for corporate B2B orders.', 'error');
        }
    }

    /**
     * Record B2B order metadata and create corporate tracking ledger entry.
     *
     * @param int   $order_id
     * @param array $data
     * @return void
     */
    public function save_b2b_order_meta(int $order_id, array $data): void {
        $user_id = get_current_user_id();
        $company = B2B_Organization::instance()->get_user_company($user_id);

        if (!$company) {
            return;
        }

        $po_number = sanitize_text_field((string) ($_POST['suresalez_b2b_po_number'] ?? 'PO-GENERIC'));
        $payment_terms = $company['payment_terms'] ?: 'net_30';
        $days = self::TERMS_DAYS[$payment_terms] ?? 30;
        $due_date = date('Y-m-d', strtotime("+{$days} days"));

        $order_total = function_exists('wc_get_order') && ($order = wc_get_order($order_id)) ? (float) $order->get_total() : 0.0;
        $approval = B2B_Organization::instance()->evaluate_order_approval($user_id, $order_total);

        $approval_status = $approval['requires_approval'] ? 'pending_manager_approval' : 'approved';

        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_b2b_orders';
        $wpdb->insert(
            $table,
            [
                'order_id'            => $order_id,
                'company_id'          => $company['id'],
                'po_number'           => $po_number,
                'payment_terms'       => $payment_terms,
                'due_date'            => $due_date,
                'approval_status'     => $approval_status,
                'approved_by_user_id' => $approval['requires_approval'] ? 0 : $user_id,
                'created_at'          => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        // If requires approval, hold order
        if ($approval['requires_approval'] && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('on-hold', sprintf('Order held for managerial approval: %s', $approval['reason']));
            }
        }
    }

    /**
     * Generate structured pro-forma invoice data.
     *
     * @param int $order_id
     * @return array<string, mixed>
     */
    public function generate_pro_forma_invoice(int $order_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_orders';
        $b2b_order = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id),
            ARRAY_A
        );

        $company = $b2b_order ? B2B_Organization::instance()->get_company((int) $b2b_order['company_id']) : null;

        return [
            'invoice_number'   => 'PRO-' . date('Y') . '-' . str_pad((string) $order_id, 6, '0', STR_PAD_LEFT),
            'order_id'         => $order_id,
            'po_number'        => $b2b_order['po_number'] ?? 'N/A',
            'company_name'     => $company['company_name'] ?? 'Corporate Buyer',
            'company_tax_id'   => $company['company_tax_id'] ?? 'N/A',
            'payment_terms'    => $b2b_order['payment_terms'] ?? 'net_30',
            'issue_date'       => date('Y-m-d'),
            'due_date'         => $b2b_order['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'statutory_bank'   => [
                'bank_name' => 'JPMorgan Chase Enterprise Commercial Banking',
                'iban'      => 'US89CHAS12345678901234',
                'swift_bic' => 'CHASUS33XXX',
            ],
            'approval_status'  => $b2b_order['approval_status'] ?? 'approved',
        ];
    }
}
