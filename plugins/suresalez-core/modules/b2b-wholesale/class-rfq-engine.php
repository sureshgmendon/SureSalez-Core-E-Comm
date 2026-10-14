<?php
/**
 * Request For Quote (RFQ) & Negotiation Engine.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

namespace SureSalez\Modules\B2BWholesale;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RFQ_Engine
 *
 * Powers customer cart-to-quote conversion, merchant discount negotiations,
 * counter-offer notifications, and 1-click quote acceptance sessions.
 */
class RFQ_Engine {

    /**
     * Singleton instance.
     *
     * @var RFQ_Engine|null
     */
    private static ?RFQ_Engine $instance = null;

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
        register_rest_route('suresalez/v1', '/b2b/quotes/create', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_create_quote'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/b2b/quotes/(?P<id>\d+)/counter', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_counter_quote'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => ['validate_callback' => static fn($param) => is_numeric($param)],
            ],
        ]);

        register_rest_route('suresalez/v1', '/b2b/quotes/(?P<id>\d+)/accept', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_accept_quote'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => ['validate_callback' => static fn($param) => is_numeric($param)],
            ],
        ]);

        register_rest_route('suresalez/v1', '/b2b/quotes/(?P<token>[a-zA-Z0-9_\-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_quote_by_token'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Generate standard RFQ code.
     *
     * @return string
     */
    public function generate_quote_number(): string {
        return sprintf('RFQ-%s-%04d', date('Ymd'), wp_rand(1000, 9999));
    }

    /**
     * Generate secure token for 1-click acceptance.
     *
     * @return string
     */
    public function generate_quote_token(): string {
        return 'rfq_tok_' . bin2hex(random_bytes(16));
    }

    /**
     * Create new RFQ quote request.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create_quote(array $data): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_quotes';

        $quote_number = $this->generate_quote_number();
        $quote_token  = $this->generate_quote_token();

        $company_id   = (int) ($data['company_id'] ?? 1);
        $customer_id  = (int) ($data['customer_id'] ?? 10);
        $items        = (array) ($data['items'] ?? []);
        $notes        = sanitize_textarea_field((string) ($data['notes'] ?? ''));

        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 1);
            $req_price = (float) ($item['requested_price'] ?? 0);
            $subtotal += ($req_price * $qty);
        }

        $freight = (float) ($data['freight_amount'] ?? 0.0);
        $total_quoted = $subtotal + $freight;
        $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));

        $wpdb->insert(
            $table,
            [
                'quote_number'    => $quote_number,
                'company_id'      => $company_id,
                'customer_id'     => $customer_id,
                'items'           => wp_json_encode($items),
                'subtotal'        => $subtotal,
                'freight_amount'  => $freight,
                'total_quoted'    => $total_quoted,
                'status'          => 'submitted',
                'quote_token'     => $quote_token,
                'notes'           => $notes,
                'expiration_date' => $expiration,
                'created_at'      => current_time('mysql'),
            ],
            ['%s', '%d', '%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        $quote_id = (int) $wpdb->insert_id;

        return [
            'quote_id'        => $quote_id,
            'quote_number'    => $quote_number,
            'quote_token'     => $quote_token,
            'status'          => 'submitted',
            'total_quoted'    => $total_quoted,
            'acceptance_url'  => home_url('/checkout/?b2b_quote_token=' . $quote_token),
        ];
    }

    /**
     * Submit merchant counter-offer with adjusted pricing and terms.
     *
     * @param int   $quote_id
     * @param array $counter_data
     * @return array<string, mixed>
     */
    public function counter_offer(int $quote_id, array $counter_data): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_quotes';
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $quote_id), ARRAY_A);

        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found.'];
        }

        $items = !empty($counter_data['items']) ? (array) $counter_data['items'] : json_decode($quote['items'], true);
        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 1);
            $quoted = (float) ($item['quoted_price'] ?? ($item['requested_price'] ?? 0));
            $subtotal += ($quoted * $qty);
        }

        $freight = isset($counter_data['freight_amount']) ? (float) $counter_data['freight_amount'] : (float) $quote['freight_amount'];
        $total = $subtotal + $freight;
        $counter_notes = sanitize_textarea_field((string) ($counter_data['counter_notes'] ?? ''));

        $wpdb->update(
            $table,
            [
                'items'           => wp_json_encode($items),
                'subtotal'        => $subtotal,
                'freight_amount'  => $freight,
                'total_quoted'    => $total,
                'status'          => 'counter_offered',
                'counter_notes'   => $counter_notes,
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+14 days')),
            ],
            ['id' => $quote_id],
            ['%s', '%f', '%f', '%f', '%s', '%s', '%s'],
            ['%d']
        );

        return [
            'success'        => true,
            'quote_id'       => $quote_id,
            'quote_number'   => $quote['quote_number'],
            'status'         => 'counter_offered',
            'subtotal'       => $subtotal,
            'freight_amount' => $freight,
            'total_quoted'   => $total,
            'acceptance_url' => home_url('/checkout/?b2b_quote_token=' . $quote['quote_token']),
        ];
    }

    /**
     * Buyer accepts quote and unlocks checkout session.
     *
     * @param int|string $identifier Quote ID or Token
     * @return array<string, mixed>
     */
    public function accept_quote($identifier): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_quotes';

        if (is_numeric($identifier)) {
            $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $identifier), ARRAY_A);
        } else {
            $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE quote_token = %s", (string) $identifier), ARRAY_A);
        }

        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found.'];
        }

        if ($quote['status'] === 'expired') {
            return ['success' => false, 'message' => 'This quote has expired. Please submit a new RFQ.'];
        }

        $wpdb->update(
            $table,
            ['status' => 'accepted'],
            ['id' => $quote['id']],
            ['%s'],
            ['%d']
        );

        return [
            'success'        => true,
            'quote_id'       => (int) $quote['id'],
            'quote_number'   => $quote['quote_number'],
            'status'         => 'accepted',
            'total_quoted'   => (float) $quote['total_quoted'],
            'checkout_url'   => home_url('/checkout/?b2b_quote_accepted=' . $quote['quote_token']),
            'message'        => 'Quote accepted! Redirecting to secure checkout with locked negotiated pricing.',
        ];
    }

    /**
     * REST endpoint: Create RFQ.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_create_quote(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_json_params() ?: $request->get_params();
        $result = $this->create_quote($params);

        return new \WP_REST_Response(array_merge(['success' => true], $result), 201);
    }

    /**
     * REST endpoint: Counter-offer.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_counter_quote(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $result = $this->counter_offer($id, $params);

        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response($result, $status);
    }

    /**
     * REST endpoint: Accept quote.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_accept_quote(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $result = $this->accept_quote($id);

        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response($result, $status);
    }

    /**
     * REST endpoint: Get quote by token.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_quote_by_token(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $token = sanitize_text_field((string) $request->get_param('token'));
        $table = $wpdb->prefix . 'suresalez_b2b_quotes';
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE quote_token = %s", $token), ARRAY_A);

        if (!$quote) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Quote token invalid.'], 404);
        }

        $quote['items'] = json_decode($quote['items'], true);
        $quote['subtotal'] = (float) $quote['subtotal'];
        $quote['freight_amount'] = (float) $quote['freight_amount'];
        $quote['total_quoted'] = (float) $quote['total_quoted'];

        return new \WP_REST_Response(['success' => true, 'quote' => $quote], 200);
    }
}
