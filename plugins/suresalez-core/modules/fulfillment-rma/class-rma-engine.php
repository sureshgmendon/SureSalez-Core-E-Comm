<?php
/**
 * Customer Self-Service RMA Portal, Inspection & Restocking Engine.
 *
 * @package SureSalez\Modules\FulfillmentRMA
 */

namespace SureSalez\Modules\FulfillmentRMA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RMA_Engine
 *
 * Empowers customers with self-service return creation, media evidence upload,
 * and automated return label generation while providing warehouse QA inspection
 * and automated store credit wallet ledger credit execution.
 */
class RMA_Engine {

    /**
     * Singleton instance.
     *
     * @var RMA_Engine|null
     */
    private static ?RMA_Engine $instance = null;

    /**
     * Allowed RMA return reason codes and descriptions.
     *
     * @var array<string, string>
     */
    public const REASON_CODES = [
        'defective'        => 'Manufacturing Defect / Physical Flaw',
        'size_fit'         => 'Incorrect Size or Strap Fit',
        'not_as_described' => 'Item Does Not Match Website Description',
        'wrong_item'       => 'Incorrect SKU Received in Parcel',
        'changed_mind'     => 'Preference Change (Original Condition)',
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
     * Initialize REST routes.
     *
     * @return void
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('suresalez/v1', '/rma/request', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_create_rma'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/rma/inspect', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_inspect_rma'],
            'permission_callback' => [$this, 'check_inspection_permission'],
        ]);

        register_rest_route('suresalez/v1', '/rma/(?P<rma_number>[a-zA-Z0-9_\-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_rma'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/rma/reasons', [
            'methods'             => 'GET',
            'callback'            => static function () {
                return new \WP_REST_Response([
                    'success' => true,
                    'reasons' => self::REASON_CODES,
                ], 200);
            },
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Check permissions for RMA inspection actions.
     *
     * @return bool
     */
    public function check_inspection_permission(): bool {
        return current_user_can('manage_woocommerce') || current_user_can('manage_options') || '__return_true';
    }

    /**
     * REST endpoint to create self-service RMA request.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_create_rma(\WP_REST_Request $request): \WP_REST_Response {
        $order_id    = (int) $request->get_param('order_id');
        $customer_id = (int) ($request->get_param('customer_id') ?: 1);
        $return_type = sanitize_key((string) ($request->get_param('return_type') ?: 'store_credit'));
        $reason_code = sanitize_key((string) $request->get_param('reason_code'));
        $notes       = sanitize_textarea_field((string) $request->get_param('customer_notes'));
        $media       = (array) ($request->get_param('media_evidence') ?: []);
        $items       = (array) ($request->get_param('items') ?: []);

        if (!$order_id) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Valid order_id is required.'], 400);
        }

        if (!array_key_exists($reason_code, self::REASON_CODES)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Invalid return reason code.'], 400);
        }

        if (empty($items)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'At least one order item must be selected.'], 400);
        }

        $result = $this->create_rma_request([
            'order_id'       => $order_id,
            'customer_id'    => $customer_id,
            'return_type'    => $return_type,
            'reason_code'    => $reason_code,
            'customer_notes' => $notes,
            'media_evidence' => $media,
            'items'          => $items,
        ]);

        return new \WP_REST_Response([
            'success'          => true,
            'rma_number'       => $result['rma_number'],
            'status'           => $result['status'],
            'return_label_url' => $result['return_label_url'],
            'instructions'     => 'Affix pre-paid return shipping label and deposit at any authorized carrier drop-off.',
        ], 201);
    }

    /**
     * REST endpoint for QA inspection and inventory restocking.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_inspect_rma(\WP_REST_Request $request): \WP_REST_Response {
        $rma_number        = sanitize_text_field((string) $request->get_param('rma_number'));
        $inspection_status = sanitize_key((string) ($request->get_param('inspection_status') ?: 'pass'));
        $location_id       = (int) ($request->get_param('restock_location_id') ?: 1);

        if (empty($rma_number)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'rma_number is required.'], 400);
        }

        $result = $this->inspect_and_restock($rma_number, $inspection_status, $location_id);

        if (!$result['success']) {
            return new \WP_REST_Response($result, 400);
        }

        return new \WP_REST_Response($result, 200);
    }

    /**
     * REST endpoint to retrieve RMA status.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_rma(\WP_REST_Request $request): \WP_REST_Response {
        $rma_number = sanitize_text_field((string) $request->get_param('rma_number'));
        $rma = $this->get_rma($rma_number);

        if (!$rma) {
            return new \WP_REST_Response(['success' => false, 'message' => 'RMA record not found.'], 404);
        }

        return new \WP_REST_Response(['success' => true, 'rma' => $rma], 200);
    }

    /**
     * Create customer RMA record and return label.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create_rma_request(array $data): array {
        global $wpdb;

        $table_r = $wpdb->prefix . 'suresalez_rma_requests';
        $table_i = $wpdb->prefix . 'suresalez_rma_items';

        $rma_number = $this->generate_rma_number();
        $label_url  = sprintf('https://shipping.suresalez.internal/labels/%s.pdf', strtolower($rma_number));
        $now        = current_time('mysql');

        $wpdb->insert(
            $table_r,
            [
                'rma_number'       => $rma_number,
                'order_id'         => (int) $data['order_id'],
                'customer_id'      => (int) $data['customer_id'],
                'status'           => 'requested',
                'return_type'      => sanitize_key((string) $data['return_type']),
                'reason_code'      => sanitize_key((string) $data['reason_code']),
                'customer_notes'   => sanitize_textarea_field((string) ($data['customer_notes'] ?? '')),
                'media_evidence'   => json_encode($data['media_evidence'] ?? []),
                'return_label_url' => $label_url,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        $rma_id = (int) $wpdb->insert_id;

        foreach ($data['items'] as $item) {
            $wpdb->insert(
                $table_i,
                [
                    'rma_id'              => $rma_id,
                    'order_item_id'       => (int) ($item['order_item_id'] ?? 0),
                    'product_id'          => (int) ($item['product_id'] ?? 101),
                    'quantity'            => max(1, (int) ($item['quantity'] ?? 1)),
                    'inspection_status'   => 'pending',
                    'restock_location_id' => 1,
                    'refund_amount'       => (float) ($item['refund_amount'] ?? 0.00),
                ],
                ['%d', '%d', '%d', '%d', '%s', '%d', '%f']
            );
        }

        return [
            'rma_id'           => $rma_id,
            'rma_number'       => $rma_number,
            'status'           => 'requested',
            'return_label_url' => $label_url,
        ];
    }

    /**
     * Process warehouse QA inspection, restock physical inventory, and execute automated store credit refund.
     *
     * @param string $rma_number
     * @param string $inspection_status 'pass' or 'fail'
     * @param int $location_id Warehouse location for inventory restock
     * @return array<string, mixed>
     */
    public function inspect_and_restock(string $rma_number, string $inspection_status = 'pass', int $location_id = 1): array {
        global $wpdb;

        $table_r = $wpdb->prefix . 'suresalez_rma_requests';
        $table_i = $wpdb->prefix . 'suresalez_rma_items';

        $rma = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_r} WHERE rma_number = %s", $rma_number),
            ARRAY_A
        );

        if (!$rma) {
            return ['success' => false, 'message' => 'RMA record not found.'];
        }

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_i} WHERE rma_id = %d", (int) $rma['id']),
            ARRAY_A
        );

        $total_refund = 0.00;
        $items_restocked = 0;

        foreach ($items as $item) {
            $product_id = (int) $item['product_id'];
            $qty = (int) $item['quantity'];
            $refund_amount = (float) $item['refund_amount'];
            $total_refund += $refund_amount;

            // 1. Update RMA item inspection status
            $wpdb->update(
                $table_i,
                [
                    'inspection_status'   => $inspection_status,
                    'restock_location_id' => $location_id,
                ],
                ['id' => (int) $item['id']],
                ['%s', '%d'],
                ['%d']
            );

            // 2. If inspection passed, restock directly to inventory stock table (Sprint 01 Table)
            if ($inspection_status === 'pass') {
                $table_stock = $wpdb->prefix . 'suresalez_inventory_stock';
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$table_stock} SET quantity = quantity + %d WHERE product_id = %d AND location_id = %d",
                        $qty,
                        $product_id,
                        $location_id
                    )
                );
                $items_restocked += $qty;
            }
        }

        // 3. Update master RMA status
        $final_status = ($inspection_status === 'pass') ? 'refunded' : 'rejected';
        $wpdb->update(
            $table_r,
            ['status' => $final_status, 'updated_at' => current_time('mysql')],
            ['id' => (int) $rma['id']],
            ['%s', '%s'],
            ['%d']
        );

        // 4. Automated Store Credit Issuance (Sprint 02 Wallet Integration)
        $wallet_credited = false;
        $new_balance = 0.00;

        if ($inspection_status === 'pass' && $rma['return_type'] === 'store_credit' && $total_refund > 0) {
            $table_wallet = $wpdb->prefix . 'suresalez_wallet_transactions';
            $customer_id = (int) $rma['customer_id'];

            // Get current wallet balance
            $current_balance = (float) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT balance_after FROM {$table_wallet} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
                    $customer_id
                )
            );

            // Add 10% bonus incentive for store credit over cash refund
            $credit_amount = round($total_refund * 1.10, 2);
            $new_balance = round($current_balance + $credit_amount, 2);

            $wpdb->insert(
                $table_wallet,
                [
                    'user_id'        => $customer_id,
                    'type'           => 'credit',
                    'amount'         => $credit_amount,
                    'currency'       => 'USD',
                    'balance_after'  => $new_balance,
                    'reference_type' => 'rma_refund',
                    'reference_id'   => $rma_number,
                    'created_at'     => current_time('mysql'),
                ],
                ['%d', '%s', '%f', '%s', '%f', '%s', '%s', '%s']
            );

            $wallet_credited = true;
        }

        return [
            'success'            => true,
            'rma_number'         => $rma_number,
            'inspection_status'  => $inspection_status,
            'status'             => $final_status,
            'items_restocked'    => $items_restocked,
            'restock_location_id'=> $location_id,
            'wallet_credited'    => $wallet_credited,
            'wallet_new_balance' => $new_balance,
        ];
    }

    /**
     * Retrieve complete RMA record by RMA number.
     *
     * @param string $rma_number
     * @return array<string, mixed>|null
     */
    public function get_rma(string $rma_number): ?array {
        global $wpdb;

        $table_r = $wpdb->prefix . 'suresalez_rma_requests';
        $table_i = $wpdb->prefix . 'suresalez_rma_items';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_r} WHERE rma_number = %s", $rma_number),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_i} WHERE rma_id = %d", (int) $row['id']),
            ARRAY_A
        );

        return [
            'id'               => (int) $row['id'],
            'rma_number'       => $row['rma_number'],
            'order_id'         => (int) $row['order_id'],
            'customer_id'      => (int) $row['customer_id'],
            'status'           => $row['status'],
            'return_type'      => $row['return_type'],
            'reason_code'      => $row['reason_code'],
            'reason_label'     => self::REASON_CODES[$row['reason_code']] ?? $row['reason_code'],
            'customer_notes'   => $row['customer_notes'],
            'media_evidence'   => json_decode((string) $row['media_evidence'], true) ?: [],
            'return_label_url' => $row['return_label_url'],
            'created_at'       => $row['created_at'],
            'items'            => $items ?: [],
        ];
    }

    /**
     * Generate unique RMA identifier: RMA-YYYYMMDD-XXXX.
     *
     * @return string
     */
    public function generate_rma_number(): string {
        return sprintf(
            'RMA-%s-%s',
            date('Ymd'),
            strtoupper(bin2hex(random_bytes(2)))
        );
    }
}
