<?php
/**
 * Dual-Sided Referral Program Engine.
 *
 * @package SureSalez\Modules\LoyaltyMobileAPI
 */

namespace SureSalez\Modules\LoyaltyMobileAPI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Referral_Program
 *
 * Manages dual-sided viral referrals, custom tracking codes, cookie attribution,
 * friend welcome discounts (15%), and referrer reward point issuance.
 */
class Referral_Program {

    public const COOKIE_NAME             = 'suresalez_ref_code';
    public const COOKIE_EXPIRY_DAYS      = 30;
    public const FRIEND_DISCOUNT_PERCENT = 15.0; // 15% off first order for referred customer
    public const REFERRER_BONUS_POINTS   = 500;  // 500 points ($25 value) awarded to referrer

    /**
     * Generate or retrieve deterministic referral code for user.
     *
     * @param int $user_id
     * @return string
     */
    public static function get_user_referral_code(int $user_id): string {
        if ($user_id <= 0) {
            return '';
        }

        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_referrals';
        $code  = $wpdb->get_var(
            $wpdb->prepare("SELECT referral_code FROM {$table} WHERE referrer_user_id = %d LIMIT 1", $user_id)
        );

        if (!empty($code)) {
            return $code;
        }

        // Generate clean code: SURE- + 6-character hex uppercase
        $hash = strtoupper(substr(md5($user_id . 'suresalez_ref_salt_2026'), 0, 6));
        $generated_code = 'SURE-' . $hash;

        $wpdb->insert(
            $table,
            [
                'referrer_user_id' => $user_id,
                'referral_code'    => $generated_code,
                'referred_user_id' => 0,
                'reward_status'    => 'pending',
                'order_id'         => 0,
                'created_at'       => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%d', '%s']
        );

        return $generated_code;
    }

    /**
     * Find referrer user ID by referral code.
     *
     * @param string $referral_code
     * @return int
     */
    public static function find_referrer_by_code(string $referral_code): int {
        $clean_code = sanitize_text_field(strtoupper(trim($referral_code)));
        if (empty($clean_code)) {
            return 0;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_referrals';
        $user_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT referrer_user_id FROM {$table} WHERE referral_code = %s LIMIT 1", $clean_code)
        );

        return $user_id;
    }

    /**
     * Capture referral cookie if ?ref= query parameter is present.
     *
     * @return void
     */
    public static function track_incoming_referral(): void {
        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $ref_code = sanitize_text_field(strtoupper(trim($_GET['ref'])));
            $referrer_id = self::find_referrer_by_code($ref_code);
            if ($referrer_id > 0) {
                setcookie(
                    self::COOKIE_NAME,
                    $ref_code,
                    time() + (self::COOKIE_EXPIRY_DAYS * DAY_IN_SECONDS),
                    COOKIEPATH,
                    COOKIE_DOMAIN,
                    is_ssl(),
                    true
                );
            }
        }
    }

    /**
     * Calculate 15% friend welcome discount for referred orders.
     *
     * @param float  $subtotal
     * @param string $referral_code
     * @param int    $customer_id
     * @return array
     */
    public static function calculate_friend_discount(float $subtotal, string $referral_code, int $customer_id = 0): array {
        $referrer_id = self::find_referrer_by_code($referral_code);

        if ($referrer_id <= 0 || ($customer_id > 0 && $customer_id === $referrer_id)) {
            return [
                'valid'               => false,
                'discount_percentage' => 0.0,
                'discount_amount'     => 0.0,
                'referral_code'       => $referral_code,
                'error'               => 'Invalid referral code or cannot refer yourself.',
            ];
        }

        $discount_amount = round($subtotal * (self::FRIEND_DISCOUNT_PERCENT / 100.0), 2);

        return [
            'valid'               => true,
            'discount_percentage' => self::FRIEND_DISCOUNT_PERCENT,
            'discount_amount'     => $discount_amount,
            'referral_code'       => $referral_code,
            'referrer_id'         => $referrer_id,
            'error'               => null,
        ];
    }

    /**
     * Process referral reward bonus points for referrer upon completed order.
     *
     * @param int    $order_id
     * @param int    $referred_user_id
     * @param string $referral_code
     * @return array
     */
    public static function process_referral_reward(int $order_id, int $referred_user_id, string $referral_code): array {
        $referrer_id = self::find_referrer_by_code($referral_code);
        if ($referrer_id <= 0 || $referrer_id === $referred_user_id) {
            return [
                'success' => false,
                'error'   => 'Invalid referrer attribution.',
            ];
        }

        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_referrals';

        // Insert referral conversion row
        $wpdb->insert(
            $table,
            [
                'referrer_user_id' => $referrer_id,
                'referral_code'    => $referral_code,
                'referred_user_id' => $referred_user_id,
                'reward_status'    => 'rewarded',
                'order_id'         => $order_id,
                'created_at'       => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%d', '%s']
        );

        // Credit 500 bonus loyalty points to referrer
        Loyalty_Engine::credit_points(
            $referrer_id,
            self::REFERRER_BONUS_POINTS,
            'bonus',
            sprintf('Referral reward for friend order #%d (Code: %s)', $order_id, $referral_code),
            $order_id
        );

        return [
            'success'       => true,
            'referrer_id'   => $referrer_id,
            'points_awarded'=> self::REFERRER_BONUS_POINTS,
            'order_id'      => $order_id,
        ];
    }

    /**
     * Get referral program performance stats for a customer.
     *
     * @param int $user_id
     * @return array
     */
    public static function get_referral_stats(int $user_id): array {
        global $wpdb;

        $code = self::get_user_referral_code($user_id);
        $table = $wpdb->prefix . 'suresalez_referrals';

        $total_invites = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE referrer_user_id = %d AND referred_user_id > 0", $user_id)
        );

        $successful_orders = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE referrer_user_id = %d AND reward_status = 'rewarded'", $user_id)
        );

        $total_points_earned = $successful_orders * self::REFERRER_BONUS_POINTS;

        return [
            'referral_code'       => $code,
            'referral_url'        => home_url('/?ref=' . $code),
            'total_invites'       => $total_invites,
            'successful_orders'   => $successful_orders,
            'total_points_earned' => $total_points_earned,
            'reward_per_referral' => self::REFERRER_BONUS_POINTS,
            'friend_discount'     => self::FRIEND_DISCOUNT_PERCENT,
        ];
    }
}
