<?php
/**
 * Customer 360 & Dynamic Segmentation Engine.
 *
 * @package SureSalez\Modules\CRMAutomations
 */

namespace SureSalez\Modules\CRMAutomations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Customer_Segments
 *
 * Manages customer 360 profiles, real-time behavioral metrics (Spend, Orders, AOV, Recency),
 * timeline tracking, and dynamic rule-based behavioral segmentation.
 */
class Customer_Segments {

    /**
     * Initialize event hooks for customer tracking.
     */
    public static function init(): void {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'on_order_completed'], 20, 1);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'on_order_completed'], 20, 1);
        add_action('suresalez_cart_abandoned', [__CLASS__, 'on_cart_abandoned'], 20, 1);
    }

    /**
     * Hook listener: order completed.
     *
     * @param int $order_id
     */
    public static function on_order_completed($order_id): void {
        self::sync_customer_from_order((int)$order_id);
    }

    /**
     * Hook listener: cart abandoned.
     *
     * @param array $cart_data
     */
    public static function on_cart_abandoned(array $cart_data): void {
        $email = $cart_data['customer_email'] ?? '';
        if (empty($email)) {
            return;
        }

        $profile = self::get_or_create_profile($email, (int)($cart_data['user_id'] ?? 0));
        if ($profile) {
            self::record_timeline_event(
                (int)$profile['id'],
                'checkout_abandoned',
                [
                    'cart_total' => $cart_data['cart_total'] ?? 0,
                    'currency'   => $cart_data['currency'] ?? 'USD',
                    'token'      => $cart_data['recovery_token'] ?? '',
                ]
            );
        }
    }

    /**
     * Sync and update customer 360 profile from a completed or processing order.
     *
     * @param int $order_id
     * @return array|null Updated profile array
     */
    public static function sync_customer_from_order(int $order_id): ?array {
        global $wpdb;

        $email = '';
        $phone = '';
        $user_id = 0;
        $order_total = 0.0;
        $order_date = current_time('mysql');

        if (function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
            if ($order) {
                $email = $order->get_billing_email();
                $phone = $order->get_billing_phone();
                $user_id = (int)$order->get_customer_id();
                $order_total = (float)$order->get_total();
                $date_created = $order->get_date_created();
                if ($date_created) {
                    $order_date = $date_created->date('Y-m-d H:i:s');
                }
            }
        }

        if (empty($email)) {
            return null;
        }

        $profile = self::get_or_create_profile($email, $user_id, $phone);
        if (!$profile) {
            return null;
        }

        $table = $wpdb->prefix . 'suresalez_customer_profiles';

        // Calculate aggregate statistics
        $new_order_count = (int)$profile['order_count'] + 1;
        $new_total_spend = round((float)$profile['total_spend'] + $order_total, 2);
        $new_aov = round($new_total_spend / max(1, $new_order_count), 2);
        $first_order_at = !empty($profile['first_order_at']) ? $profile['first_order_at'] : $order_date;
        $last_order_at = $order_date;

        // Auto-assign VIP and Repeat tags
        $existing_tags = is_string($profile['tags']) ? (json_decode($profile['tags'], true) ?: []) : (array)($profile['tags'] ?? []);
        if ($new_total_spend >= 1000.00 && !in_array('vip', $existing_tags, true)) {
            $existing_tags[] = 'vip';
        }
        if ($new_order_count >= 2 && !in_array('repeat_buyer', $existing_tags, true)) {
            $existing_tags[] = 'repeat_buyer';
        }

        $updated_data = [
            'total_spend'    => $new_total_spend,
            'order_count'    => $new_order_count,
            'aov'            => $new_aov,
            'first_order_at' => $first_order_at,
            'last_order_at'  => $last_order_at,
            'tags'           => json_encode(array_values(array_unique($existing_tags))),
            'updated_at'     => current_time('mysql'),
        ];

        $wpdb->update($table, $updated_data, ['id' => $profile['id']]);

        // Record timeline event
        self::record_timeline_event(
            (int)$profile['id'],
            'order',
            [
                'order_id'    => $order_id,
                'order_total' => $order_total,
                'date'        => $order_date,
            ]
        );

        // Trigger Flow Automation event
        do_action('suresalez_flow_trigger', 'order.completed', [
            'customer_id' => $profile['id'],
            'email'       => $email,
            'order_id'    => $order_id,
            'order_total' => $order_total,
            'total_spend' => $new_total_spend,
            'order_count' => $new_order_count,
            'tags'        => $existing_tags,
        ]);

        return array_merge($profile, $updated_data);
    }

    /**
     * Get or create customer profile by email.
     *
     * @param string $email
     * @param int    $user_id
     * @param string $phone
     * @return array|null
     */
    public static function get_or_create_profile(string $email, int $user_id = 0, string $phone = ''): ?array {
        global $wpdb;

        $email = sanitize_email(strtolower(trim($email)));
        if (empty($email)) {
            return null;
        }

        $table = $wpdb->prefix . 'suresalez_customer_profiles';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE email = %s", $email), ARRAY_A);

        if ($row) {
            if ($user_id > 0 && empty($row['user_id'])) {
                $wpdb->update($table, ['user_id' => $user_id], ['id' => $row['id']]);
                $row['user_id'] = $user_id;
            }
            return $row;
        }

        // Create new customer profile
        $data = [
            'user_id'        => $user_id,
            'email'          => $email,
            'phone'          => sanitize_text_field($phone),
            'total_spend'    => 0.00,
            'order_count'    => 0,
            'aov'            => 0.00,
            'first_order_at' => null,
            'last_order_at'  => null,
            'tags'           => json_encode([]),
            'updated_at'     => current_time('mysql'),
        ];

        $wpdb->insert($table, $data);
        $data['id'] = $wpdb->insert_id;

        return $data;
    }

    /**
     * Record an event on the customer's timeline.
     *
     * @param int    $customer_id
     * @param string $event_type
     * @param array  $payload
     * @return bool
     */
    public static function record_timeline_event(int $customer_id, string $event_type, array $payload = []): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_customer_timeline';
        $result = $wpdb->insert(
            $table,
            [
                'customer_id'   => $customer_id,
                'event_type'    => sanitize_key($event_type),
                'event_payload' => json_encode($payload),
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Evaluate whether a customer profile satisfies a segment's conditions.
     *
     * @param array $conditions Group of rules with 'operator' (AND/OR) and 'rules'
     * @param array $profile Customer 360 profile
     * @return bool
     */
    public static function evaluate_segment(array $conditions, array $profile): bool {
        $operator = strtoupper($conditions['operator'] ?? 'AND');
        $rules = $conditions['rules'] ?? [];

        if (empty($rules)) {
            return false;
        }

        $results = [];

        // Precompute virtual metrics
        $now = current_time('timestamp');
        $days_since_last_order = 9999;
        if (!empty($profile['last_order_at'])) {
            $last_ts = strtotime($profile['last_order_at']);
            if ($last_ts) {
                $days_since_last_order = max(0, (int)floor(($now - $last_ts) / 86400));
            }
        }

        $customer_tags = [];
        if (!empty($profile['tags'])) {
            $customer_tags = is_string($profile['tags']) ? (json_decode($profile['tags'], true) ?: []) : (array)$profile['tags'];
        }

        foreach ($rules as $rule) {
            $field = $rule['field'] ?? '';
            $rule_op = $rule['operator'] ?? 'equals';
            $expected = $rule['value'] ?? null;

            $actual = null;
            if ($field === 'days_since_last_order') {
                $actual = $days_since_last_order;
            } elseif ($field === 'tags') {
                $actual = $customer_tags;
            } else {
                $actual = $profile[$field] ?? null;
            }

            $matched = self::match_rule($actual, $rule_op, $expected);
            $results[] = $matched;
        }

        if ($operator === 'OR') {
            return in_array(true, $results, true);
        }

        // AND operator
        return !in_array(false, $results, true);
    }

    /**
     * Match a single rule condition against an actual value.
     *
     * @param mixed  $actual
     * @param string $operator
     * @param mixed  $expected
     * @return bool
     */
    public static function match_rule($actual, string $operator, $expected): bool {
        switch ($operator) {
            case 'equals':
            case '==':
                return $actual == $expected;

            case 'not_equals':
            case '!=':
                return $actual != $expected;

            case 'greater_than':
            case '>':
                return floatval($actual) > floatval($expected);

            case 'greater_than_or_equal':
            case '>=':
                return floatval($actual) >= floatval($expected);

            case 'less_than':
            case '<':
                return floatval($actual) < floatval($expected);

            case 'less_than_or_equal':
            case '<=':
                return floatval($actual) <= floatval($expected);

            case 'contains':
                if (is_array($actual)) {
                    return in_array($expected, $actual, true);
                }
                return stripos((string)$actual, (string)$expected) !== false;

            case 'not_contains':
                if (is_array($actual)) {
                    return !in_array($expected, $actual, true);
                }
                return stripos((string)$actual, (string)$expected) === false;

            case 'in_array':
                return is_array($expected) && in_array($actual, $expected, true);

            default:
                return false;
        }
    }

    /**
     * Get all matching segments for a customer profile.
     *
     * @param array $profile
     * @return array Array of matching segment rows
     */
    public static function get_customer_segments(array $profile): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_segments';
        $segments = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A);

        if (empty($segments)) {
            return [];
        }

        $matched = [];
        foreach ($segments as $segment) {
            $conditions = json_decode($segment['conditions'], true);
            if (is_array($conditions) && self::evaluate_segment($conditions, $profile)) {
                $matched[] = [
                    'id'   => $segment['id'],
                    'name' => $segment['name'],
                    'slug' => $segment['slug'],
                ];
            }
        }

        return $matched;
    }
}
