<?php
/**
 * Multi-Location Warehouse Inventory Module.
 *
 * @package SureSalez\Modules\MultiLocationInventory
 */

namespace SureSalez\Modules\MultiLocationInventory;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Multi_Location_Inventory_Module
 *
 * Tracks, reserves, and routes inventory across 10 global fulfillment centers.
 */
class Multi_Location_Inventory_Module extends Module_Base {

    public function get_id(): string {
        return 'multi-location-inventory';
    }

    public function get_name(): string {
        return __('10-Hub Multi-Location Inventory', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Real-time multi-warehouse inventory allocation across 10 global hubs with safety buffers and transfer logs.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Inventory & Logistics';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        add_action('add_meta_boxes', [$this, 'register_inventory_metabox']);
        add_action('save_post_product', [$this, 'save_inventory_metabox'], 15, 2);
        add_action('wp_footer', [$this, 'inject_frontend_inventory_data'], 25);
    }

    public function register_assets(): void {
        if (!is_singular('product') && !function_exists('is_product')) {
            return;
        }

        wp_enqueue_style(
            'suresalez-multi-inventory',
            $this->get_url('assets/css/multi-location-inventory.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-multi-inventory',
            $this->get_url('assets/js/multi-location-inventory.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        $product_id = get_the_ID();
        if ($product_id) {
            wp_localize_script('suresalez-multi-inventory', 'SureSalezInventoryData', [
                'productId' => $product_id,
                'endpoint'  => rest_url(REST_API::NAMESPACE . "/inventory/stock/{$product_id}"),
                'stockData' => self::get_product_stock_summary($product_id),
            ]);
        }
    }

    public function register_rest_routes(): void {
        register_rest_route(REST_API::NAMESPACE, '/inventory/locations', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_locations'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/inventory/stock/(?P<product_id>[\d]+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_product_stock'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_update_product_stock'],
                'permission_callback' => function () {
                    return current_user_can('manage_options') || current_user_can('edit_products');
                },
            ],
        ]);
    }

    /**
     * Get all active warehouse locations from custom DB table.
     *
     * @return array
     */
    public static function get_active_locations(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_inventory_locations';
        $results = $wpdb->get_results(
            "SELECT id, location_code, name, city, state, country, priority FROM {$table} WHERE is_active = 1 ORDER BY priority ASC",
            ARRAY_A
        );
        return is_array($results) ? $results : [];
    }

    /**
     * Get structured stock summary across all 10 locations for a given product.
     *
     * @param int $product_id
     * @return array
     */
    public static function get_product_stock_summary(int $product_id): array {
        global $wpdb;

        $table_locations = $wpdb->prefix . 'suresalez_inventory_locations';
        $table_stock     = $wpdb->prefix . 'suresalez_inventory_stock';

        $query = $wpdb->prepare(
            "SELECT 
                l.id as location_id,
                l.location_code,
                l.name as location_name,
                l.city,
                l.country,
                l.priority,
                COALESCE(s.stock_quantity, 0) as stock_quantity,
                COALESCE(s.reserved_quantity, 0) as reserved_quantity,
                COALESCE(s.safety_buffer, 0) as safety_buffer
            FROM {$table_locations} l
            LEFT JOIN {$table_stock} s ON (l.id = s.location_id AND s.product_id = %d)
            WHERE l.is_active = 1
            ORDER BY l.priority ASC",
            $product_id
        );

        $results = $wpdb->get_results($query, ARRAY_A);
        $total_available = 0;
        $locations_data = [];

        if (is_array($results)) {
            foreach ($results as $row) {
                $qty = (int) $row['stock_quantity'];
                $res = (int) $row['reserved_quantity'];
                $buf = (int) $row['safety_buffer'];
                $avail = max(0, $qty - $res - $buf);
                $total_available += $avail;

                $locations_data[] = [
                    'location_id'   => (int) $row['location_id'],
                    'code'          => $row['location_code'],
                    'name'          => $row['location_name'],
                    'city'          => $row['city'],
                    'country'       => $row['country'],
                    'quantity'      => $qty,
                    'reserved'      => $res,
                    'safety_buffer' => $buf,
                    'available'     => $avail,
                    'is_in_stock'   => $avail > 0,
                ];
            }
        }

        return [
            'product_id'      => $product_id,
            'total_available' => $total_available,
            'in_stock'        => $total_available > 0,
            'locations'       => $locations_data,
        ];
    }

    /**
     * REST: Get all 10 locations.
     */
    public function rest_get_locations(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response(['locations' => self::get_active_locations()], 200);
    }

    /**
     * REST: Get stock for product.
     */
    public function rest_get_product_stock(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('product_id');
        $summary = self::get_product_stock_summary($product_id);
        return new \WP_REST_Response($summary, 200);
    }

    /**
     * REST: Update location stock balance.
     */
    public function rest_update_product_stock(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $product_id  = (int) $request->get_param('product_id');
        $location_id = (int) $request->get_param('location_id');
        $quantity    = (int) $request->get_param('quantity');
        $buffer      = (int) ($request->get_param('safety_buffer') ?? 0);
        $sku         = sanitize_text_field($request->get_param('sku') ?? '');

        if (!$location_id) {
            return new \WP_REST_Response(['message' => 'Missing location_id'], 400);
        }

        $table_stock = $wpdb->prefix . 'suresalez_inventory_stock';

        $wpdb->replace(
            $table_stock,
            [
                'location_id'    => $location_id,
                'product_id'     => $product_id,
                'sku'            => $sku,
                'stock_quantity' => $quantity,
                'safety_buffer'  => $buffer,
                'updated_at'     => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%d', '%d', '%s']
        );

        return new \WP_REST_Response([
            'success' => true,
            'stock'   => self::get_product_stock_summary($product_id)
        ], 200);
    }

    /**
     * Register product metabox.
     */
    public function register_inventory_metabox(): void {
        add_meta_box(
            'suresalez_multi_inventory_box',
            __('SureSalez Multi-Location Inventory (10 Global Hubs)', 'suresalez-core'),
            [$this, 'render_inventory_metabox'],
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Render product metabox.
     */
    public function render_inventory_metabox(\WP_Post $post): void {
        wp_nonce_field('suresalez_save_inventory_meta', 'suresalez_inventory_nonce');

        $stock_summary = self::get_product_stock_summary($post->ID);
        ?>
        <div class="suresalez-inventory-admin">
            <p><strong><?php esc_html_e('Total Available Across All Hubs:', 'suresalez-core'); ?></strong> 
                <span class="stock-total-badge" style="font-weight:700; color:#15803d; font-size:14px; background:#dcfce7; padding:2px 8px; border-radius:4px;">
                    <?php echo esc_html($stock_summary['total_available']); ?> units
                </span>
            </p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Hub Code', 'suresalez-core'); ?></th>
                        <th><?php esc_html_e('Fulfillment Center', 'suresalez-core'); ?></th>
                        <th><?php esc_html_e('Country', 'suresalez-core'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Stock Qty', 'suresalez-core'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Reserved', 'suresalez-core'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Safety Buffer', 'suresalez-core'); ?></th>
                        <th style="width:100px;"><?php esc_html_e('Net Avail', 'suresalez-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_summary['locations'] as $loc): ?>
                        <tr>
                            <td><code><?php echo esc_html($loc['code']); ?></code></td>
                            <td><strong><?php echo esc_html($loc['name']); ?></strong></td>
                            <td><?php echo esc_html($loc['country']); ?></td>
                            <td>
                                <input type="number" min="0" name="suresalez_stock[<?php echo esc_attr($loc['location_id']); ?>][qty]" value="<?php echo esc_attr($loc['quantity']); ?>" style="width:90px;">
                            </td>
                            <td>
                                <input type="number" min="0" readonly value="<?php echo esc_attr($loc['reserved']); ?>" style="width:90px; background:#f1f5f9;">
                            </td>
                            <td>
                                <input type="number" min="0" name="suresalez_stock[<?php echo esc_attr($loc['location_id']); ?>][buffer]" value="<?php echo esc_attr($loc['safety_buffer']); ?>" style="width:90px;">
                            </td>
                            <td>
                                <strong style="color: <?php echo $loc['available'] > 0 ? '#15803d' : '#ef4444'; ?>">
                                    <?php echo esc_html($loc['available']); ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Save product metabox data.
     */
    public function save_inventory_metabox(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['suresalez_inventory_nonce']) || !wp_verify_nonce($_POST['suresalez_inventory_nonce'], 'suresalez_save_inventory_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['suresalez_stock']) || !is_array($_POST['suresalez_stock'])) {
            return;
        }

        global $wpdb;
        $table_stock = $wpdb->prefix . 'suresalez_inventory_stock';

        foreach ($_POST['suresalez_stock'] as $loc_id => $data) {
            $loc_id = absint($loc_id);
            $qty    = absint($data['qty'] ?? 0);
            $buffer = absint($data['buffer'] ?? 0);

            $wpdb->replace(
                $table_stock,
                [
                    'location_id'    => $loc_id,
                    'product_id'     => $post_id,
                    'sku'            => '',
                    'stock_quantity' => $qty,
                    'safety_buffer'  => $buffer,
                    'updated_at'     => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%d', '%d', '%s']
            );
        }
    }

    public function inject_frontend_inventory_data(): void {
        if (!is_singular('product') && !is_product_page_fallback()) {
            return;
        }
        $product_id = get_the_ID();
        if (!$product_id) return;
        $summary = self::get_product_stock_summary($product_id);
        ?>
        <script id="suresalez-inventory-data-json" type="application/json">
            <?php echo wp_json_encode($summary); ?>
        </script>
        <?php
    }
}
