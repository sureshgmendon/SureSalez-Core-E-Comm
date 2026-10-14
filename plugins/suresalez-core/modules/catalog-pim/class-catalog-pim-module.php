<?php
/**
 * Catalog Architecture, PIM & DAM Module.
 *
 * @package SureSalez\Modules\CatalogPIM
 */

namespace SureSalez\Modules\CatalogPIM;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-metafields.php';
require_once __DIR__ . '/class-dam.php';

/**
 * Class Catalog_PIM_Module
 */
class Catalog_PIM_Module extends Module_Base {

    private Metafields $metafields;
    private DAM $dam;

    public function __construct() {
        $this->metafields = new Metafields();
        $this->dam = new DAM();
    }

    public function get_id(): string {
        return 'catalog-pim';
    }

    public function get_name(): string {
        return __('Catalog Architecture, PIM & DAM', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Enterprise product identifiers (EAN, UPC, GTIN, MPN, HS Code), extended technical specs, 360 spin viewer, and PDF datasheets.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Catalog';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        $this->metafields->init();
        $this->dam->init();

        // Single product template hooks
        add_action('wp_footer', [$this, 'inject_frontend_pim_hydration'], 25);
    }

    public function register_assets(): void {
        if (!is_singular('product') && !is_product_page_fallback()) {
            return;
        }

        wp_enqueue_style(
            'suresalez-catalog-pim',
            $this->get_url('assets/css/catalog-pim.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-catalog-pim',
            $this->get_url('assets/js/catalog-pim.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        $product_id = get_the_ID();
        if ($product_id) {
            wp_localize_script('suresalez-catalog-pim', 'SureSalezCatalogPIMData', [
                'productId'   => $product_id,
                'apiEndpoint' => rest_url(REST_API::NAMESPACE . "/products/{$product_id}/metafields"),
                'damEndpoint' => rest_url(REST_API::NAMESPACE . "/products/{$product_id}/dam"),
                'metafields'  => Metafields::get_product_metafields($product_id),
                'dam'         => DAM::get_product_dam_assets($product_id),
            ]);
        }
    }

    public function register_rest_routes(): void {
        register_rest_route(REST_API::NAMESPACE, '/products/(?P<id>[\d]+)/metafields', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_metafields'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_update_metafields'],
                'permission_callback' => function () {
                    return current_user_can('edit_products') || current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route(REST_API::NAMESPACE, '/products/(?P<id>[\d]+)/dam', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_dam'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_get_metafields(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('id');
        $post = get_post($product_id);

        if (!$post || 'product' !== $post->post_type) {
            return new \WP_REST_Response(['message' => 'Product not found'], 404);
        }

        $data = Metafields::get_product_metafields($product_id);
        return new \WP_REST_Response(['product_id' => $product_id, 'metafields' => $data], 200);
    }

    public function rest_update_metafields(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('id');
        $post = get_post($product_id);

        if (!$post || 'product' !== $post->post_type) {
            return new \WP_REST_Response(['message' => 'Product not found'], 404);
        }

        $params = $request->get_json_params() ?: $request->get_params();

        foreach (Metafields::FIELDS as $meta_key => $field_def) {
            $param_key = ltrim($meta_key, '_');
            if (isset($params[$param_key])) {
                $sanitized = $this->metafields->sanitize_field_value($params[$param_key], $meta_key);
                update_post_meta($product_id, $meta_key, $sanitized);
            }
        }

        return new \WP_REST_Response([
            'success'    => true,
            'product_id' => $product_id,
            'metafields' => Metafields::get_product_metafields($product_id),
        ], 200);
    }

    public function rest_get_dam(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('id');
        $post = get_post($product_id);

        if (!$post || 'product' !== $post->post_type) {
            return new \WP_REST_Response(['message' => 'Product not found'], 404);
        }

        $dam = DAM::get_product_dam_assets($product_id);
        return new \WP_REST_Response(['product_id' => $product_id, 'dam' => $dam], 200);
    }

    public function inject_frontend_pim_hydration(): void {
        if (!is_singular('product') && !is_product_page_fallback()) {
            return;
        }
        $product_id = get_the_ID();
        if (!$product_id) return;
        $meta = Metafields::get_product_metafields($product_id);
        $dam = DAM::get_product_dam_assets($product_id);
        ?>
        <script id="suresalez-pim-data-json" type="application/json">
            <?php echo wp_json_encode(['metafields' => $meta, 'dam' => $dam]); ?>
        </script>
        <?php
    }
}

/**
 * Fallback check for product page.
 */
function is_product_page_fallback(): bool {
    if (function_exists('is_product') && is_product()) {
        return true;
    }
    return is_singular('product');
}
