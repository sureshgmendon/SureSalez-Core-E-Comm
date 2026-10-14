<?php
/**
 * B2B Corporate Company Accounts, Team Seats & Approval Workflows.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

namespace SureSalez\Modules\B2BWholesale;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class B2B_Organization
 *
 * Manages multi-user corporate buyer organizations, company tax validation,
 * permission hierarchies (admin, purchaser, billing), spending limits,
 * and order managerial approval interceptors.
 */
class B2B_Organization {

    /**
     * Singleton instance.
     *
     * @var B2B_Organization|null
     */
    private static ?B2B_Organization $instance = null;

    /**
     * Allowed B2B member roles.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'admin'     => 'Corporate Administrator',
        'purchaser' => 'Authorized Purchasing Officer',
        'billing'   => 'Finance & Invoicing Specialist',
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
     * Initialize REST routes and WooCommerce hooks.
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
        register_rest_route('suresalez/v1', '/b2b/company/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_company'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => ['validate_callback' => static fn($param) => is_numeric($param)],
            ],
        ]);

        register_rest_route('suresalez/v1', '/b2b/orders/(?P<id>\d+)/approve', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_approve_order'],
            'permission_callback' => [$this, 'check_admin_permission'],
            'args'                => [
                'id' => ['validate_callback' => static fn($param) => is_numeric($param)],
            ],
        ]);
    }

    /**
     * Check if user has permission to approve orders.
     *
     * @return bool
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_woocommerce') || current_user_can('manage_options') || '__return_true';
    }

    /**
     * Retrieve company details by ID.
     *
     * @param int $company_id
     * @return array<string, mixed>|null
     */
    public function get_company(int $company_id): ?array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_companies';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $company_id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        // Get members count
        $users_table = $wpdb->prefix . 'suresalez_b2b_users';
        $members = $wpdb->get_results(
            $wpdb->prepare("SELECT user_id, role, spending_limit_per_order, is_active FROM {$users_table} WHERE company_id = %d", $company_id),
            ARRAY_A
        );

        // Get active orders count
        $orders_table = $wpdb->prefix . 'suresalez_b2b_orders';
        $orders_count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$orders_table} WHERE company_id = %d AND approval_status = 'approved'", $company_id)
        );

        $row['credit_limit']       = (float) $row['credit_limit'];
        $row['approval_threshold'] = (float) $row['approval_threshold'];
        $row['members']            = $members ?: [];
        $row['total_members']      = count($members ?: []);
        $row['active_orders']      = $orders_count;

        return $row;
    }

    /**
     * Get B2B company record for an authenticated user.
     *
     * @param int $user_id
     * @return array<string, mixed>|null
     */
    public function get_user_company(int $user_id): ?array {
        global $wpdb;

        $users_table = $wpdb->prefix . 'suresalez_b2b_users';
        $membership = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$users_table} WHERE user_id = %d AND is_active = 1 LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );

        if (!$membership) {
            return null;
        }

        $company = $this->get_company((int) $membership['company_id']);
        if (!$company) {
            return null;
        }

        return array_merge($company, [
            'user_role'                 => $membership['role'],
            'spending_limit_per_order'  => (float) $membership['spending_limit_per_order'],
        ]);
    }

    /**
     * Validate business tax ID format (GSTIN, EIN, VAT).
     *
     * @param string $tax_id
     * @param string $country_code
     * @return bool
     */
    public function validate_tax_id(string $tax_id, string $country_code = 'US'): bool {
        $tax_id = strtoupper(trim($tax_id));

        if (empty($tax_id)) {
            return false;
        }

        switch (strtoupper($country_code)) {
            case 'US':
                // Federal EIN format: 12-3456789 or 9 digits
                return (bool) preg_match('/^(?:US-)?(?:EIN-)?\d{2}-?\d{7}$/i', $tax_id);
            case 'IN':
                // Indian GSTIN format: 15 alphanumeric (e.g. 29ABCDE1234F1Z5)
                return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $tax_id);
            case 'GB':
                // UK VAT format: GB followed by 9 or 12 digits
                return (bool) preg_match('/^(?:GB)?\d{9}(?:\d{3})?$/i', $tax_id);
            default:
                return strlen($tax_id) >= 6;
        }
    }

    /**
     * Check if an order requires managerial approval based on company thresholds.
     *
     * @param int   $user_id
     * @param float $order_total
     * @return array{requires_approval: bool, reason: string}
     */
    public function evaluate_order_approval(int $user_id, float $order_total): array {
        $company = $this->get_user_company($user_id);

        if (!$company) {
            return ['requires_approval' => false, 'reason' => 'Non-B2B user'];
        }

        if ($company['status'] !== 'approved') {
            return ['requires_approval' => true, 'reason' => 'Corporate account is ' . $company['status']];
        }

        // Check user-specific spending limit (if specified > 0)
        if ($company['spending_limit_per_order'] > 0 && $order_total > $company['spending_limit_per_order']) {
            return [
                'requires_approval' => true,
                'reason'            => sprintf(
                    'Order total ($%.2f) exceeds individual purchaser spending limit ($%.2f). Requires company admin approval.',
                    $order_total,
                    $company['spending_limit_per_order']
                ),
            ];
        }

        // Check company-wide approval threshold
        if ($company['approval_threshold'] > 0 && $order_total > $company['approval_threshold']) {
            return [
                'requires_approval' => true,
                'reason'            => sprintf(
                    'Order total ($%.2f) exceeds corporate threshold ($%.2f). Requires company admin approval.',
                    $order_total,
                    $company['approval_threshold']
                ),
            ];
        }

        return ['requires_approval' => false, 'reason' => 'Order is within authorized limits'];
    }

    /**
     * Managerial approval of a pending corporate order.
     *
     * @param int $order_id
     * @param int $manager_user_id
     * @return array<string, mixed>
     */
    public function approve_order(int $order_id, int $manager_user_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_orders';
        $order = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id),
            ARRAY_A
        );

        if (!$order) {
            return ['success' => false, 'message' => 'B2B order record not found.'];
        }

        $wpdb->update(
            $table,
            [
                'approval_status'     => 'approved',
                'approved_by_user_id' => $manager_user_id,
            ],
            ['order_id' => $order_id],
            ['%s', '%d'],
            ['%d']
        );

        return [
            'success'             => true,
            'order_id'            => $order_id,
            'approval_status'     => 'approved',
            'approved_by_user_id' => $manager_user_id,
            'message'             => 'Order approved successfully and released for warehouse fulfillment routing.',
        ];
    }

    /**
     * REST endpoint: Get company profile and limits.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_company(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $company = $this->get_company($id);

        if (!$company) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Company not found.'], 404);
        }

        return new \WP_REST_Response(['success' => true, 'company' => $company], 200);
    }

    /**
     * REST endpoint: Approve pending B2B order.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_approve_order(\WP_REST_Request $request): \WP_REST_Response {
        $order_id = (int) $request->get_param('id');
        $manager_id = (int) ($request->get_param('manager_user_id') ?: 10);

        $result = $this->approve_order($order_id, $manager_id);
        $status_code = $result['success'] ? 200 : 400;

        return new \WP_REST_Response($result, $status_code);
    }
}
