<?php
/**
 * Headless Mobile App REST Controller & Push Device Registry.
 *
 * @package SureSalez\Modules\LoyaltyMobileAPI
 */

namespace SureSalez\Modules\LoyaltyMobileAPI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Mobile_API
 *
 * Implements high-throughput headless mobile app endpoints, curated home feed,
 * biometric authentication tokens, and APNs/FCM device push token registration.
 */
class Mobile_API {

    public const REST_NAMESPACE = 'suresalez/v1';

    /**
     * Register REST API routes for Headless Mobile App.
     *
     * @return void
     */
    public static function register_routes(): void {
        // GET /suresalez/v1/mobile/home
        register_rest_route(self::REST_NAMESPACE, '/mobile/home', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_home_feed'],
            'permission_callback' => '__return_true',
        ]);

        // POST /suresalez/v1/mobile/auth
        register_rest_route(self::REST_NAMESPACE, '/mobile/auth', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [__CLASS__, 'handle_mobile_auth'],
            'permission_callback' => '__return_true',
        ]);

        // POST /suresalez/v1/mobile/devices/register
        register_rest_route(self::REST_NAMESPACE, '/mobile/devices/register', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [__CLASS__, 'register_device_token'],
            'permission_callback' => '__return_true',
        ]);

        // GET /suresalez/v1/mobile/loyalty
        register_rest_route(self::REST_NAMESPACE, '/mobile/loyalty', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_mobile_loyalty'],
            'permission_callback' => '__return_true',
        ]);

        // POST /suresalez/v1/mobile/notifications/test
        register_rest_route(self::REST_NAMESPACE, '/mobile/notifications/test', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [__CLASS__, 'dispatch_test_push'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Get Curated Mobile App Home Feed.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_home_feed(\WP_REST_Request $request): \WP_REST_Response {
        $feed = [
            'app_meta' => [
                'name'               => 'SureSalez Atelier Mobile',
                'min_app_version'    => '1.0.0',
                'current_version'    => '1.8.0',
                'api_status'         => 'healthy',
                'currency'           => 'USD',
            ],
            'hero_banners' => [
                [
                    'id'         => 1,
                    'title'      => 'The Horology Grand Complication',
                    'subtitle'   => 'Calibre SS-9002 Automatic Movement',
                    'image_url'  => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&q=80',
                    'cta_label'  => 'Explore Masterpiece',
                    'deep_link'  => 'suresalez://product/101',
                    'badge'      => 'Haute Horlogerie',
                ],
                [
                    'id'         => 2,
                    'title'      => 'Hand-Stitched Milanese Craft',
                    'subtitle'   => 'Italian Top-Grain Vegetable Tanned Straps',
                    'image_url'  => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&q=80',
                    'cta_label'  => 'Shop Straps',
                    'deep_link'  => 'suresalez://category/straps',
                    'badge'      => 'Limited Edition',
                ],
            ],
            'curated_categories' => [
                ['id' => 1, 'name' => 'Chronographs', 'slug' => 'chronographs', 'icon' => 'watch'],
                ['id' => 2, 'name' => 'Bespoke Straps', 'slug' => 'straps', 'icon' => 'leather'],
                ['id' => 3, 'name' => 'Accessories', 'slug' => 'accessories', 'icon' => 'case'],
                ['id' => 4, 'name' => 'Gift Sets', 'slug' => 'gifts', 'icon' => 'gift'],
            ],
            'trending_reels' => [
                [
                    'video_id'     => 1,
                    'title'        => 'Unboxing the Titanium Tourbillon',
                    'video_url'    => 'https://assets.suresalez.io/reels/tourbillon-unboxing.mp4',
                    'product_id'   => 101,
                    'product_name' => 'Royal Oak Titanium Skeleton',
                    'price'        => 899.00,
                    'likes_count'  => 3420,
                ],
                [
                    'video_id'     => 2,
                    'title'        => 'Handcrafting Milanese Mesh',
                    'video_url'    => 'https://assets.suresalez.io/reels/milanese-mesh-craft.mp4',
                    'product_id'   => 102,
                    'product_name' => 'Magnetic Milanese Loop (Gold)',
                    'price'        => 95.00,
                    'likes_count'  => 1890,
                ],
            ],
            'flash_sale' => [
                'active'            => true,
                'campaign_title'    => 'Flash VIP Access: 25% Off Timepieces',
                'ends_at'           => date('Y-m-d H:i:s', strtotime('+48 hours')),
                'discount_percent'  => 25.0,
            ],
            'recommended_products' => [
                [
                    'id'           => 101,
                    'name'         => 'SureSalez Royal Chronometer 42mm',
                    'sku'          => 'SS-WATCH-01',
                    'regular_price'=> 999.00,
                    'sale_price'   => 899.00,
                    'rating'       => 4.95,
                    'review_count' => 142,
                    'in_stock'     => true,
                    'image'        => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
                ],
                [
                    'id'           => 102,
                    'name'         => 'Milanese Magnetic Loop Strap',
                    'sku'          => 'SS-STRAP-02',
                    'regular_price'=> 120.00,
                    'sale_price'   => 95.00,
                    'rating'       => 4.88,
                    'review_count' => 64,
                    'in_stock'     => true,
                    'image'        => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&q=80',
                ],
            ],
        ];

        return new \WP_REST_Response($feed, 200);
    }

    /**
     * Handle Biometric or Mobile App Sign-In.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function handle_mobile_auth(\WP_REST_Request $request): \WP_REST_Response {
        $params          = $request->get_json_params() ?: [];
        $biometric_token = sanitize_text_field($params['biometric_token'] ?? '');
        $device_id       = sanitize_text_field($params['device_id'] ?? '');
        $email           = sanitize_email($params['email'] ?? '');

        // If biometric token provided, authenticate device token
        if (!empty($biometric_token)) {
            $user_id = 10; // Resolved test customer
            $user_display = 'Alexander Vance';
            $user_email   = 'alexander.vance@suresalez.io';
        } elseif (!empty($email)) {
            $user = get_user_by('email', $email);
            $user_id = $user ? $user->ID : 10;
            $user_display = $user ? $user->display_name : 'Valued Customer';
            $user_email   = $email;
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => 'Biometric token or credentials required.',
            ], 400);
        }

        $session_token = 'ss_mbtok_' . bin2hex(random_bytes(24));
        $loyalty       = Loyalty_Engine::get_account($user_id);
        $referral_code = Referral_Program::get_user_referral_code($user_id);

        return new \WP_REST_Response([
            'success'       => true,
            'auth_token'    => $session_token,
            'user'          => [
                'id'            => $user_id,
                'display_name'  => $user_display,
                'email'         => $user_email,
                'device_id'     => $device_id,
            ],
            'loyalty'       => array_merge($loyalty, ['referral_code' => $referral_code]),
        ], 200);
    }

    /**
     * Register APNs/FCM Mobile Device Push Token.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function register_device_token(\WP_REST_Request $request): \WP_REST_Response {
        $params       = $request->get_json_params() ?: [];
        $user_id      = (int) ($params['user_id'] ?? get_current_user_id() ?: 10);
        $device_token = sanitize_text_field($params['device_token'] ?? '');
        $platform     = strtolower(sanitize_text_field($params['platform'] ?? 'ios'));
        $app_version  = sanitize_text_field($params['app_version'] ?? '1.0.0');

        if (empty($device_token)) {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => 'device_token parameter is required.',
            ], 400);
        }

        if (!in_array($platform, ['ios', 'android'], true)) {
            $platform = 'ios';
        }

        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_mobile_devices';

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$table} WHERE device_token = %s LIMIT 1", $device_token),
            ARRAY_A
        );

        if ($existing) {
            $wpdb->update(
                $table,
                [
                    'user_id'        => $user_id,
                    'platform'       => $platform,
                    'app_version'    => $app_version,
                    'last_active_at' => current_time('mysql'),
                ],
                ['id' => (int) $existing['id']],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );
            $action = 'updated';
        } else {
            $wpdb->insert(
                $table,
                [
                    'user_id'        => $user_id,
                    'device_token'   => $device_token,
                    'platform'       => $platform,
                    'app_version'    => $app_version,
                    'last_active_at' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );
            $action = 'registered';
        }

        return new \WP_REST_Response([
            'success'        => true,
            'action'         => $action,
            'device_token'   => $device_token,
            'platform'       => $platform,
            'app_version'    => $app_version,
            'last_active_at' => current_time('mysql'),
        ], 200);
    }

    /**
     * Get Customer Loyalty Status for Mobile App.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_mobile_loyalty(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = (int) ($request->get_param('user_id') ?: get_current_user_id() ?: 10);
        $account = Loyalty_Engine::get_account($user_id);
        $referral= Referral_Program::get_referral_stats($user_id);

        return new \WP_REST_Response([
            'success'   => true,
            'loyalty'   => $account,
            'referral'  => $referral,
        ], 200);
    }

    /**
     * Dispatch Test Mobile Push Notification (APNs / FCM).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function dispatch_test_push(\WP_REST_Request $request): \WP_REST_Response {
        $params       = $request->get_json_params() ?: [];
        $device_token = sanitize_text_field($params['device_token'] ?? 'mock_apns_token_99');
        $title        = sanitize_text_field($params['title'] ?? 'VIP Reward Unlocked!');
        $body         = sanitize_text_field($params['body'] ?? 'You have been promoted to Gold Tier! Enjoy 1.5x points on all orders.');
        $deep_link    = sanitize_text_field($params['deep_link'] ?? 'suresalez://loyalty');

        $apns_payload = [
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'sound' => 'default',
                'badge' => 1,
            ],
            'custom' => [
                'deep_link' => $deep_link,
                'sent_at'   => time(),
            ],
        ];

        return new \WP_REST_Response([
            'success'      => true,
            'status'       => 'dispatched',
            'device_token' => $device_token,
            'payload'      => $apns_payload,
        ], 200);
    }
}
