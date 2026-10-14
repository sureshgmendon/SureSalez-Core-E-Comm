<?php
/**
 * Visual Variation Swatches Module.
 *
 * @package SureSalez\Modules\VisualSwatches
 */

namespace SureSalez\Modules\VisualSwatches;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Visual_Swatches_Module
 *
 * Transforms dull attribute dropdowns into high-converting visual swatches
 * (Hex Color, Split Duotone, Texture Images, and Button Pills).
 */
class Visual_Swatches_Module extends Module_Base {

    public const META_SWATCHES_CONFIG = '_suresalez_swatches_config';

    public function get_id(): string {
        return 'visual-swatches';
    }

    public function get_name(): string {
        return __('Visual Attribute Swatches', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Interactive visual variation swatches (Color circles, image thumbnails, button pills) with accessible keyboard navigation.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Catalog';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        add_action('add_meta_boxes', [$this, 'register_swatches_metabox']);
        add_action('save_post_product', [$this, 'save_swatches_metabox'], 15, 2);
        add_action('wp_footer', [$this, 'inject_frontend_swatches_data'], 25);
    }

    public function register_assets(): void {
        if (!is_singular('product') && !function_exists('is_product')) {
            return;
        }

        wp_enqueue_style(
            'suresalez-swatches-css',
            $this->get_url('assets/css/visual-swatches.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-swatches-js',
            $this->get_url('assets/js/visual-swatches.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        $product_id = get_the_ID();
        if ($product_id) {
            wp_localize_script('suresalez-swatches-js', 'SureSalezSwatchesData', [
                'productId'    => $product_id,
                'swatchesData' => self::get_product_swatches_config($product_id),
            ]);
        }
    }

    public function register_rest_routes(): void {
        register_rest_route(REST_API::NAMESPACE, '/products/(?P<product_id>[\d]+)/swatches', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_swatches'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_update_swatches'],
                'permission_callback' => function () {
                    return current_user_can('edit_products') || current_user_can('manage_options');
                },
            ],
        ]);
    }

    /**
     * Get product swatches configuration.
     *
     * @param int $product_id
     * @return array
     */
    public static function get_product_swatches_config(int $product_id): array {
        $stored = get_post_meta($product_id, self::META_SWATCHES_CONFIG, true);
        if (is_array($stored) && !empty($stored)) {
            return $stored;
        }

        // Default sample swatch definitions if none configured
        return [
            [
                'attribute_name' => 'Color',
                'type'           => 'color',
                'options'        => [
                    ['label' => 'Midnight Slate', 'value' => '#0f172a', 'secondary_value' => '', 'image_url' => '', 'in_stock' => true],
                    ['label' => 'Electric Blue',  'value' => '#2563eb', 'secondary_value' => '', 'image_url' => '', 'in_stock' => true],
                    ['label' => 'Emerald Green',  'value' => '#10b981', 'secondary_value' => '', 'image_url' => '', 'in_stock' => true],
                    ['label' => 'Solar Gold',     'value' => '#f59e0b', 'secondary_value' => '', 'image_url' => '', 'in_stock' => false],
                ],
            ],
            [
                'attribute_name' => 'Storage Size',
                'type'           => 'button',
                'options'        => [
                    ['label' => '128 GB', 'value' => '128gb', 'in_stock' => true],
                    ['label' => '256 GB', 'value' => '256gb', 'in_stock' => true],
                    ['label' => '512 GB', 'value' => '512gb', 'in_stock' => true],
                    ['label' => '1 TB',   'value' => '1tb',   'in_stock' => false],
                ],
            ],
        ];
    }

    public function rest_get_swatches(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('product_id');
        return new \WP_REST_Response([
            'product_id' => $product_id,
            'attributes' => self::get_product_swatches_config($product_id),
        ], 200);
    }

    public function rest_update_swatches(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('product_id');
        $params = $request->get_json_params() ?: $request->get_params();

        if (isset($params['attributes']) && is_array($params['attributes'])) {
            update_post_meta($product_id, self::META_SWATCHES_CONFIG, $params['attributes']);
        }

        return new \WP_REST_Response([
            'success'    => true,
            'attributes' => self::get_product_swatches_config($product_id),
        ], 200);
    }

    public function register_swatches_metabox(): void {
        add_meta_box(
            'suresalez_swatches_config_box',
            __('SureSalez Visual Swatches Configuration', 'suresalez-core'),
            [$this, 'render_swatches_metabox'],
            'product',
            'normal',
            'default'
        );
    }

    public function render_swatches_metabox(\WP_Post $post): void {
        wp_nonce_field('suresalez_save_swatches_meta', 'suresalez_swatches_nonce');
        $config = self::get_product_swatches_config($post->ID);
        $json_text = wp_json_encode($config, JSON_PRETTY_PRINT);
        ?>
        <div style="padding: 10px 0;">
            <p><strong><?php esc_html_e('Product Attribute Swatches Configuration (JSON):', 'suresalez-core'); ?></strong></p>
            <textarea name="suresalez_swatches_json" rows="8" style="width:100%; font-family:monospace; font-size:12px;"><?php echo esc_textarea($json_text); ?></textarea>
            <p class="description">
                <?php esc_html_e('Supported types: "color" (hex codes or two-tone splits), "image" (image_url), "button" (pill label).', 'suresalez-core'); ?>
            </p>
        </div>
        <?php
    }

    public function save_swatches_metabox(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['suresalez_swatches_nonce']) || !wp_verify_nonce($_POST['suresalez_swatches_nonce'], 'suresalez_save_swatches_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['suresalez_swatches_json'])) {
            $raw = trim((string) $_POST['suresalez_swatches_json']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                update_post_meta($post_id, self::META_SWATCHES_CONFIG, $decoded);
            }
        }
    }

    public function inject_frontend_swatches_data(): void {
        if (!is_singular('product') && !is_product_page_fallback()) {
            return;
        }
        $product_id = get_the_ID();
        if (!$product_id) return;
        $config = self::get_product_swatches_config($product_id);
        ?>
        <script id="suresalez-swatches-data-json" type="application/json">
            <?php echo wp_json_encode($config); ?>
        </script>
        <?php
    }
}
