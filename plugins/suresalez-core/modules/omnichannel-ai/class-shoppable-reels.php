<?php
/**
 * Shoppable Video Reels Engine.
 *
 * @package SureSalez\Modules\OmnichannelAI
 */

namespace SureSalez\Modules\OmnichannelAI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Shoppable_Reels
 *
 * Manages mobile-first vertical video reels, interactive product hotspot pins,
 * view & click analytics, and integration with the slide-out cart drawer.
 */
class Shoppable_Reels {

    /**
     * Singleton instance.
     *
     * @var Shoppable_Reels|null
     */
    private static ?Shoppable_Reels $instance = null;

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
     * Initialize hooks and REST routes.
     *
     * @return void
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('suresalez_shoppable_reels', [$this, 'render_shortcode']);
    }

    /**
     * Register REST API routes for reels.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('suresalez/v1', '/reels', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_reels'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/reels/(?P<id>\d+)/view', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_increment_view'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'validate_callback' => static fn($param) => is_numeric($param),
                ],
            ],
        ]);

        register_rest_route('suresalez/v1', '/reels/(?P<id>\d+)/click', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_increment_click'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'validate_callback' => static fn($param) => is_numeric($param),
                ],
            ],
        ]);
    }

    /**
     * Get published reels via REST API.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_reels(\WP_REST_Request $request): \WP_REST_Response {
        $reels = $this->get_published_reels();
        return new \WP_REST_Response([
            'success' => true,
            'count'   => count($reels),
            'reels'   => $reels,
        ], 200);
    }

    /**
     * Increment views counter.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_increment_view(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $new_count = $this->increment_views($id);

        return new \WP_REST_Response([
            'success'     => true,
            'reel_id'     => $id,
            'views_count' => $new_count,
        ], 200);
    }

    /**
     * Increment clicks counter.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_increment_click(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $new_count = $this->increment_clicks($id);

        return new \WP_REST_Response([
            'success'      => true,
            'reel_id'      => $id,
            'clicks_count' => $new_count,
        ], 200);
    }

    /**
     * Retrieve all active published reels with decoded hotspot tags.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_published_reels(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_shoppable_videos';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s ORDER BY sort_order ASC, id ASC",
                'published'
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        $formatted = [];
        foreach ($rows as $row) {
            $tagged = json_decode((string) $row['tagged_product_ids'], true);
            $formatted[] = [
                'id'             => (int) $row['id'],
                'title'          => (string) $row['title'],
                'video_url'      => (string) $row['video_url'],
                'thumbnail_url'  => (string) $row['thumbnail_url'],
                'duration'       => (int) $row['duration'],
                'tagged_products'=> is_array($tagged) ? $tagged : [],
                'views_count'    => (int) $row['views_count'],
                'clicks_count'   => (int) $row['clicks_count'],
                'sort_order'     => (int) $row['sort_order'],
                'status'         => (string) $row['status'],
                'created_at'     => (string) $row['created_at'],
            ];
        }

        return $formatted;
    }

    /**
     * Increment view count in database.
     *
     * @param int $reel_id
     * @return int
     */
    public function increment_views(int $reel_id): int {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_shoppable_videos';

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET views_count = views_count + 1 WHERE id = %d",
                $reel_id
            )
        );

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT views_count FROM {$table} WHERE id = %d", $reel_id)
        );
    }

    /**
     * Increment click count in database.
     *
     * @param int $reel_id
     * @return int
     */
    public function increment_clicks(int $reel_id): int {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_shoppable_videos';

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET clicks_count = clicks_count + 1 WHERE id = %d",
                $reel_id
            )
        );

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT clicks_count FROM {$table} WHERE id = %d", $reel_id)
        );
    }

    /**
     * Render the shortcode.
     *
     * @return string
     */
    public function render_shortcode(): string {
        return $this->render_reels_ribbon_html();
    }

    /**
     * Render the HTML markup for the shoppable reels ribbon.
     *
     * @return string
     */
    public function render_reels_ribbon_html(): string {
        $reels = $this->get_published_reels();
        if (empty($reels)) {
            return '';
        }

        ob_start();
        ?>
        <div class="suresalez-reels-container" data-reels-count="<?php echo esc_attr((string) count($reels)); ?>">
            <div class="suresalez-reels-header">
                <div class="suresalez-reels-badge">
                    <span class="suresalez-pulse-dot"></span>
                    <span>SHOP THE LOOK IN MOTION</span>
                </div>
                <h3 class="suresalez-reels-title">Interactive Shoppable Reels</h3>
                <p class="suresalez-reels-subtitle">Tap any hotspot to view fine watchmaking details and add directly to your bag.</p>
            </div>

            <div class="suresalez-reels-carousel" role="region" aria-label="Shoppable Video Carousel">
                <?php foreach ($reels as $index => $reel): ?>
                    <?php
                    $first_product = !empty($reel['tagged_products']) ? $reel['tagged_products'][0] : null;
                    $product_count = count($reel['tagged_products']);
                    ?>
                    <article class="suresalez-reel-card" data-reel-id="<?php echo esc_attr((string) $reel['id']); ?>" data-reel-index="<?php echo esc_attr((string) $index); ?>" tabindex="0">
                        <div class="suresalez-reel-media">
                            <img src="<?php echo esc_url($reel['thumbnail_url']); ?>" alt="<?php echo esc_attr($reel['title']); ?>" class="suresalez-reel-thumb" loading="lazy" />
                            <div class="suresalez-reel-overlay"></div>
                            
                            <div class="suresalez-reel-play-btn" aria-label="Play Reel">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>

                            <span class="suresalez-reel-duration"><?php echo esc_html(sprintf('0:%02d', $reel['duration'])); ?></span>

                            <?php if ($product_count > 0): ?>
                                <div class="suresalez-reel-hotspot-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                        <line x1="3" y1="6" x2="21" y2="6"/>
                                        <path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                    <span><?php echo esc_html($product_count === 1 ? '1 Tagged Product' : "{$product_count} Tagged Products"); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Interactive Product Hotspots preview -->
                            <?php foreach ($reel['tagged_products'] as $p): ?>
                                <button class="suresalez-hotspot-pin" 
                                    style="left: <?php echo esc_attr((string) $p['x_pct']); ?>%; top: <?php echo esc_attr((string) $p['y_pct']); ?>%;"
                                    data-product-id="<?php echo esc_attr((string) $p['product_id']); ?>"
                                    data-product-title="<?php echo esc_attr((string) $p['title']); ?>"
                                    data-product-price="<?php echo esc_attr((string) $p['price']); ?>"
                                    aria-label="<?php echo esc_attr(sprintf('%s - $%.2f', $p['title'], $p['price'])); ?>">
                                    <span class="suresalez-hotspot-pulse"></span>
                                    <span class="suresalez-hotspot-dot"></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="suresalez-reel-meta">
                            <h4 class="suresalez-reel-name"><?php echo esc_html($reel['title']); ?></h4>
                            <?php if ($first_product): ?>
                                <div class="suresalez-reel-lead-product">
                                    <span class="suresalez-reel-prod-title"><?php echo esc_html($first_product['title']); ?></span>
                                    <span class="suresalez-reel-prod-price">$<?php echo esc_html(number_format((float) $first_product['price'], 2)); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="suresalez-reel-stats">
                                <span><?php echo esc_html(number_format($reel['views_count'])); ?> views</span>
                                <span class="suresalez-dot-sep">•</span>
                                <button type="button" class="suresalez-reel-quick-buy" data-product-id="<?php echo esc_attr((string) ($first_product['product_id'] ?? 0)); ?>">
                                    Quick Buy
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Fullscreen Video Reel Modal Template -->
            <div id="suresalez-reel-modal" class="suresalez-reel-modal" role="dialog" aria-modal="true" aria-hidden="true" style="display: none;">
                <div class="suresalez-reel-modal-backdrop"></div>
                <div class="suresalez-reel-modal-content">
                    <button type="button" class="suresalez-reel-modal-close" aria-label="Close Reel">&times;</button>
                    
                    <div class="suresalez-reel-viewport">
                        <video id="suresalez-active-video" class="suresalez-active-video" playsinline loop preload="metadata">
                            <source src="" type="video/mp4" />
                        </video>
                        <div id="suresalez-modal-hotspots" class="suresalez-modal-hotspots"></div>
                    </div>

                    <div id="suresalez-reel-product-drawer" class="suresalez-reel-product-drawer">
                        <!-- Hydrated by JS when hotspot or quick-buy is clicked -->
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
