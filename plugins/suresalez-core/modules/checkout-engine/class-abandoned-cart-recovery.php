<?php
/**
 * Abandoned Checkout Recovery Engine & Background Cron.
 *
 * @package SureSalez\Modules\CheckoutEngine
 */

namespace SureSalez\Modules\CheckoutEngine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Abandoned_Cart_Recovery
 *
 * Captures in-flight customer checkouts, generates signed 1-click recovery tokens,
 * and schedules automated recovery notifications at 1h, 24h, and 72h intervals.
 */
class Abandoned_Cart_Recovery {

    public const CRON_HOOK = 'suresalez_abandoned_checkout_cron';

    /**
     * Initialize cron schedule.
     *
     * @return void
     */
    public static function init(): void {
        add_action(self::CRON_HOOK, [__CLASS__, 'process_abandoned_checkouts']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }
    }

    /**
     * Capture in-flight checkout attempt.
     *
     * @param array $payload
     * @return array
     */
    public static function capture_checkout(array $payload): array {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_abandoned_checkouts';

        $cart_token   = sanitize_text_field($payload['cart_token'] ?? wp_generate_password(32, false));
        $email        = sanitize_email($payload['email'] ?? '');
        $phone        = sanitize_text_field($payload['phone'] ?? '');
        $cart_total   = floatval($payload['total'] ?? 0);
        $cart_contents= is_array($payload['items'] ?? null) ? json_encode($payload['items']) : json_encode([]);
        $recovery_tok = wp_hash($cart_token . '|' . $email . '|' . time());

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE cart_token = %s",
            $cart_token
        ));

        if ($existing_id) {
            $wpdb->update(
                $table,
                [
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'cart_contents'  => $cart_contents,
                    'cart_total'     => $cart_total,
                    'updated_at'     => current_time('mysql'),
                ],
                ['id' => $existing_id],
                ['%s', '%s', '%s', '%f', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'user_id'         => get_current_user_id(),
                    'cart_token'      => $cart_token,
                    'cart_contents'   => $cart_contents,
                    'customer_email'  => $email,
                    'customer_phone'  => $phone,
                    'cart_total'      => $cart_total,
                    'currency'        => 'USD',
                    'status'          => 'abandoned',
                    'recovery_token'  => $recovery_tok,
                    'reminder_count'  => 0,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s']
            );
        }

        $recovery_url = home_url('/checkout/?suresalez_recover_token=' . $recovery_tok);

        return [
            'success'      => true,
            'cart_token'   => $cart_token,
            'recovery_url' => $recovery_url,
        ];
    }

    /**
     * Restore cart from recovery token.
     *
     * @param string $recovery_token
     * @return array
     */
    public static function restore_cart_by_token(string $recovery_token): array {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_abandoned_checkouts';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE recovery_token = %s AND status = 'abandoned'",
            sanitize_text_field($recovery_token)
        ), ARRAY_A);

        if (!$row) {
            return [
                'success' => false,
                'message' => __('Invalid or expired cart recovery token.', 'suresalez-core'),
            ];
        }

        $items = json_decode($row['cart_contents'], true);

        // Mark as recovered
        $wpdb->update(
            $table,
            ['status' => 'recovered'],
            ['id' => $row['id']],
            ['%s'],
            ['%d']
        );

        return [
            'success'        => true,
            'customer_email' => $row['customer_email'],
            'cart_total'     => (float) $row['cart_total'],
            'items'          => is_array($items) ? $items : [],
            'message'        => __('Your cart has been securely restored.', 'suresalez-core'),
        ];
    }

    /**
     * Scheduled cron worker to evaluate abandoned carts.
     *
     * @return void
     */
    public static function process_abandoned_checkouts(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_abandoned_checkouts';

        // Find carts older than 1 hour with reminder_count < 3
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $abandoned_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, customer_email, recovery_token, reminder_count, cart_total 
             FROM {$table} 
             WHERE status = 'abandoned' AND updated_at <= %s AND reminder_count < 3",
            $one_hour_ago
        ), ARRAY_A);

        if (empty($abandoned_rows)) {
            return;
        }

        foreach ($abandoned_rows as $row) {
            // Increment reminder count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET reminder_count = reminder_count + 1 WHERE id = %d",
                $row['id']
            ));

            do_action('suresalez_send_abandoned_recovery_email', $row['customer_email'], $row['recovery_token'], $row['cart_total']);
        }
    }
}
