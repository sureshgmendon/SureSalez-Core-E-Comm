<?php
/**
 * Loyalty Points & VIP Tiers Engine.
 *
 * @package SureSalez\Modules\LoyaltyMobileAPI
 */

namespace SureSalez\Modules\LoyaltyMobileAPI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Loyalty_Engine
 *
 * Implements gamified customer loyalty points, VIP tiered multipliers,
 * checkout points redemption sliders, and automated tier promotions.
 */
class Loyalty_Engine {

    public const TIER_BRONZE   = 'bronze';
    public const TIER_SILVER   = 'silver';
    public const TIER_GOLD     = 'gold';
    public const TIER_PLATINUM = 'platinum';

    public const MULTIPLIER_BRONZE   = 1.0;
    public const MULTIPLIER_SILVER   = 1.25;
    public const MULTIPLIER_GOLD     = 1.5;
    public const MULTIPLIER_PLATINUM = 2.0;

    public const THRESHOLD_SILVER   = 500;
    public const THRESHOLD_GOLD     = 1500;
    public const THRESHOLD_PLATINUM = 3000;

    public const POINTS_VALUE_PER_POINT = 0.05; // $5.00 per 100 points ($0.05 / point)
    public const MIN_REDEMPTION_POINTS  = 200;  // $10.00 minimum redemption value
    public const MAX_REDEMPTION_PERCENT = 50.0; // Up to 50% cart total discount

    /**
     * Get VIP tier multiplier.
     *
     * @param string $tier
     * @return float
     */
    public static function get_tier_multiplier(string $tier): float {
        return match (strtolower(trim($tier))) {
            self::TIER_PLATINUM => self::MULTIPLIER_PLATINUM,
            self::TIER_GOLD     => self::MULTIPLIER_GOLD,
            self::TIER_SILVER   => self::MULTIPLIER_SILVER,
            default             => self::MULTIPLIER_BRONZE,
        };
    }

    /**
     * Calculate points earned for an order amount based on customer VIP tier.
     *
     * @param float  $spend
     * @param string $tier
     * @return int
     */
    public static function calculate_points_earned(float $spend, string $tier = self::TIER_BRONZE): int {
        if ($spend <= 0) {
            return 0;
        }

        $multiplier = self::get_tier_multiplier($tier);
        return (int) round($spend * $multiplier);
    }

    /**
     * Evaluate VIP tier level based on lifetime points or spend.
     *
     * @param int   $lifetime_points
     * @param float $rolling_spend
     * @return string
     */
    public static function evaluate_tier(int $lifetime_points, float $rolling_spend = 0.0): string {
        if ($lifetime_points >= self::THRESHOLD_PLATINUM || $rolling_spend >= self::THRESHOLD_PLATINUM) {
            return self::TIER_PLATINUM;
        }
        if ($lifetime_points >= self::THRESHOLD_GOLD || $rolling_spend >= self::THRESHOLD_GOLD) {
            return self::TIER_GOLD;
        }
        if ($lifetime_points >= self::THRESHOLD_SILVER || $rolling_spend >= self::THRESHOLD_SILVER) {
            return self::TIER_SILVER;
        }
        return self::TIER_BRONZE;
    }

    /**
     * Calculate checkout points redemption discount and enforce business rules.
     *
     * @param int   $points_to_redeem
     * @param float $cart_total
     * @param int   $user_balance
     * @return array
     */
    public static function calculate_redemption(int $points_to_redeem, float $cart_total, int $user_balance): array {
        if ($points_to_redeem <= 0) {
            return [
                'valid'           => false,
                'points_redeemed' => 0,
                'discount_amount' => 0.0,
                'new_balance'     => $user_balance,
                'remaining_total' => $cart_total,
                'error'           => 'Points to redeem must be greater than zero.',
            ];
        }

        if ($points_to_redeem < self::MIN_REDEMPTION_POINTS) {
            return [
                'valid'           => false,
                'points_redeemed' => 0,
                'discount_amount' => 0.0,
                'new_balance'     => $user_balance,
                'remaining_total' => $cart_total,
                'error'           => sprintf('Minimum redemption is %d points ($%.2f).', self::MIN_REDEMPTION_POINTS, self::MIN_REDEMPTION_POINTS * self::POINTS_VALUE_PER_POINT),
            ];
        }

        if ($points_to_redeem > $user_balance) {
            return [
                'valid'           => false,
                'points_redeemed' => 0,
                'discount_amount' => 0.0,
                'new_balance'     => $user_balance,
                'remaining_total' => $cart_total,
                'error'           => 'Points to redeem exceed available balance.',
            ];
        }

        $gross_discount = $points_to_redeem * self::POINTS_VALUE_PER_POINT;
        $max_discount   = $cart_total * (self::MAX_REDEMPTION_PERCENT / 100.0);

        $final_discount = min($gross_discount, $max_discount);
        $final_points   = (int) ceil($final_discount / self::POINTS_VALUE_PER_POINT);
        $remaining_total = max(0.0, round($cart_total - $final_discount, 2));

        return [
            'valid'           => true,
            'points_redeemed' => $final_points,
            'discount_amount' => round($final_discount, 2),
            'new_balance'     => $user_balance - $final_points,
            'remaining_total' => $remaining_total,
            'capped'          => $gross_discount > $max_discount,
            'error'           => null,
        ];
    }

    /**
     * Get loyalty account details for a customer.
     *
     * @param int $user_id
     * @return array
     */
    public static function get_account(int $user_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_loyalty_points';
        $row   = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id),
            ARRAY_A
        );

        if ($row) {
            $points_balance  = (int) $row['points_balance'];
            $lifetime_points = (int) $row['lifetime_points'];
            $tier_level      = $row['tier_level'];
        } else {
            $points_balance  = 0;
            $lifetime_points = 0;
            $tier_level      = self::TIER_BRONZE;
        }

        $next_tier_target = match ($tier_level) {
            self::TIER_BRONZE => self::THRESHOLD_SILVER,
            self::TIER_SILVER => self::THRESHOLD_GOLD,
            self::TIER_GOLD   => self::THRESHOLD_PLATINUM,
            default           => self::THRESHOLD_PLATINUM,
        };

        $points_to_next_tier = max(0, $next_tier_target - $lifetime_points);

        return [
            'user_id'             => $user_id,
            'points_balance'      => $points_balance,
            'lifetime_points'     => $lifetime_points,
            'tier_level'          => $tier_level,
            'tier_multiplier'     => self::get_tier_multiplier($tier_level),
            'redemption_value'    => round($points_balance * self::POINTS_VALUE_PER_POINT, 2),
            'next_tier_target'    => $next_tier_target,
            'points_to_next_tier' => $points_to_next_tier,
        ];
    }

    /**
     * Credit loyalty points to user account and log transaction.
     *
     * @param int    $user_id
     * @param int    $points
     * @param string $type
     * @param string $description
     * @param int    $order_id
     * @return bool
     */
    public static function credit_points(
        int $user_id,
        int $points,
        string $type = 'earned',
        string $description = '',
        int $order_id = 0
    ): bool {
        if ($points <= 0 || $user_id <= 0) {
            return false;
        }

        global $wpdb;

        $table_loyalty = $wpdb->prefix . 'suresalez_loyalty_points';
        $table_tx      = $wpdb->prefix . 'suresalez_points_transactions';

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_loyalty} WHERE user_id = %d LIMIT 1", $user_id),
            ARRAY_A
        );

        if ($existing) {
            $new_balance  = (int) $existing['points_balance'] + $points;
            $new_lifetime = (int) $existing['lifetime_points'] + $points;
            $new_tier     = self::evaluate_tier($new_lifetime);

            $wpdb->update(
                $table_loyalty,
                [
                    'points_balance'  => $new_balance,
                    'lifetime_points' => $new_lifetime,
                    'tier_level'      => $new_tier,
                    'updated_at'      => current_time('mysql'),
                ],
                ['user_id' => $user_id],
                ['%d', '%d', '%s', '%s'],
                ['%d']
            );
        } else {
            $new_tier = self::evaluate_tier($points);
            $wpdb->insert(
                $table_loyalty,
                [
                    'user_id'         => $user_id,
                    'points_balance'  => $points,
                    'lifetime_points' => $points,
                    'tier_level'      => $new_tier,
                    'updated_at'      => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%s', '%s']
            );
        }

        // Insert Transaction log
        $wpdb->insert(
            $table_tx,
            [
                'user_id'            => $user_id,
                'points'             => $points,
                'type'               => sanitize_text_field($type),
                'description'        => sanitize_text_field($description ?: "Credited {$points} points"),
                'reference_order_id' => $order_id,
                'created_at'         => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s']
        );

        return true;
    }

    /**
     * Debit points from user account for redemption or expiration.
     *
     * @param int    $user_id
     * @param int    $points
     * @param string $description
     * @param int    $order_id
     * @return bool
     */
    public static function debit_points(
        int $user_id,
        int $points,
        string $description = '',
        int $order_id = 0
    ): bool {
        if ($points <= 0 || $user_id <= 0) {
            return false;
        }

        global $wpdb;

        $table_loyalty = $wpdb->prefix . 'suresalez_loyalty_points';
        $table_tx      = $wpdb->prefix . 'suresalez_points_transactions';

        $account = self::get_account($user_id);
        if ($account['points_balance'] < $points) {
            return false;
        }

        $new_balance = $account['points_balance'] - $points;

        $wpdb->update(
            $table_loyalty,
            [
                'points_balance' => $new_balance,
                'updated_at'     => current_time('mysql'),
            ],
            ['user_id' => $user_id],
            ['%d', '%s'],
            ['%d']
        );

        $wpdb->insert(
            $table_tx,
            [
                'user_id'            => $user_id,
                'points'             => -$points,
                'type'               => 'redeemed',
                'description'        => sanitize_text_field($description ?: "Redeemed {$points} points"),
                'reference_order_id' => $order_id,
                'created_at'         => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s']
        );

        return true;
    }

    /**
     * Award loyalty points automatically upon order completion.
     *
     * @param int   $order_id
     * @param int   $user_id
     * @param float $total_spend
     * @return array
     */
    public static function award_order_points(int $order_id, int $user_id, float $total_spend): array {
        if ($user_id <= 0 || $total_spend <= 0) {
            return [
                'success'       => false,
                'points_earned' => 0,
                'tier'          => self::TIER_BRONZE,
            ];
        }

        $account = self::get_account($user_id);
        $points  = self::calculate_points_earned($total_spend, $account['tier_level']);

        self::credit_points(
            $user_id,
            $points,
            'earned',
            sprintf('Points earned for order #%d (%s %.2fx)', $order_id, ucfirst($account['tier_level']), $account['tier_multiplier']),
            $order_id
        );

        $updated_account = self::get_account($user_id);

        return [
            'success'        => true,
            'order_id'       => $order_id,
            'points_earned'  => $points,
            'tier'           => $updated_account['tier_level'],
            'new_balance'    => $updated_account['points_balance'],
            'tier_upgraded'  => $updated_account['tier_level'] !== $account['tier_level'],
        ];
    }
}
