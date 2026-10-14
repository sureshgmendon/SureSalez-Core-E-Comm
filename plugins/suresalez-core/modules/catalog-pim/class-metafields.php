<?php
/**
 * Catalog PIM Metafields Engine.
 *
 * @package SureSalez\Modules\CatalogPIM
 */

namespace SureSalez\Modules\CatalogPIM;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metafields
 *
 * Registers, sanitizes, and renders enterprise product identifiers,
 * extended technical specifications, and REST API bindings.
 */
class Metafields {

    /**
     * Definition of supported metafields.
     */
    public const FIELDS = [
        '_suresalez_ean' => [
            'label'       => 'EAN-13 Barcode',
            'type'        => 'string',
            'description' => 'European Article Number (13-digit standard barcode)',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_upc' => [
            'label'       => 'UPC-A Barcode',
            'type'        => 'string',
            'description' => 'Universal Product Code (12-digit North American standard)',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_gtin' => [
            'label'       => 'GTIN (Global Trade Item Number)',
            'type'        => 'string',
            'description' => 'Universal Global Trade Identification Number',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_mpn' => [
            'label'       => 'Manufacturer Part Number (MPN)',
            'type'        => 'string',
            'description' => 'Unique OEM part number issued by the manufacturer',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_brand' => [
            'label'       => 'Brand / Manufacturer',
            'type'        => 'string',
            'description' => 'Official product brand name',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_hs_code' => [
            'label'       => 'HS Tariff Code',
            'type'        => 'string',
            'description' => 'Harmonized System 6-to-10 digit customs classification code',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_material' => [
            'label'       => 'Primary Material Composition',
            'type'        => 'string',
            'description' => 'E.g., Aerospace Aluminum 7075, Organic Cotton, Carbon Fiber',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_dimensions' => [
            'label'       => 'Package Dimensions (L x W x H)',
            'type'        => 'string',
            'description' => 'Standard packaging dimensions with unit of measurement',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_country_origin' => [
            'label'       => 'Country of Origin',
            'type'        => 'string',
            'description' => 'ISO 2-letter country code or full country name',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_warranty' => [
            'label'       => 'Warranty Terms',
            'type'        => 'string',
            'description' => 'E.g., 2-Year Manufacturer Limited Global Warranty',
            'sanitize'    => 'sanitize_text_field',
        ],
        '_suresalez_specs_json' => [
            'label'       => 'Extended Technical Specifications (JSON)',
            'type'        => 'string',
            'description' => 'Key-value structured technical specifications for catalog tables',
            'sanitize'    => 'sanitize_textarea_field',
        ],
    ];

    /**
     * Initialize hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action('init', [$this, 'register_meta_schema']);
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_product', [$this, 'save_meta_box_data'], 10, 2);
    }

    /**
     * Register post meta for REST API discovery and strict type casting.
     *
     * @return void
     */
    public function register_meta_schema(): void {
        foreach (self::FIELDS as $meta_key => $field) {
            register_post_meta('product', $meta_key, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => $field['type'],
                'description'       => $field['description'],
                'sanitize_callback' => [$this, 'sanitize_field_value'],
                'auth_callback'     => function () {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }

    /**
     * Sanitize meta values based on field definition.
     *
     * @param mixed  $meta_value
     * @param string $meta_key
     * @return mixed
     */
    public function sanitize_field_value($meta_value, string $meta_key = '') {
        if (empty($meta_key) || !isset(self::FIELDS[$meta_key])) {
            return sanitize_text_field($meta_value);
        }

        $callable = self::FIELDS[$meta_key]['sanitize'];
        return is_callable($callable) ? call_user_func($callable, $meta_value) : sanitize_text_field($meta_value);
    }

    /**
     * Add admin meta box to product editor.
     *
     * @return void
     */
    public function register_meta_box(): void {
        add_meta_box(
            'suresalez_pim_metafields_box',
            __('SureSalez PIM: Enterprise Identifiers & Specifications', 'suresalez-core'),
            [$this, 'render_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render the admin metabox HTML.
     *
     * @param \WP_Post $post
     * @return void
     */
    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('suresalez_save_pim_meta', 'suresalez_pim_nonce');

        $values = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = get_post_meta($post->ID, $key, true);
        }
        ?>
        <style>
            .suresalez-pim-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px 24px;
                padding: 12px 0;
            }
            .suresalez-field-group {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .suresalez-field-group.full-width {
                grid-column: span 2;
            }
            .suresalez-field-group label {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .suresalez-field-group input, .suresalez-field-group textarea {
                width: 100%;
                border-radius: 6px;
                border: 1px solid #cbd5e1;
                padding: 6px 10px;
                font-family: inherit;
            }
            .suresalez-field-group .help-text {
                font-size: 11px;
                color: #64748b;
                margin: 0;
            }
        </style>
        <div class="suresalez-pim-grid">
            <div class="suresalez-field-group">
                <label for="suresalez_brand"><?php esc_html_e('Brand Name', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_brand" name="_suresalez_brand" value="<?php echo esc_attr($values['_suresalez_brand'] ?? ''); ?>" placeholder="e.g. Acme Corp">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_brand']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_mpn"><?php esc_html_e('Manufacturer Part Number (MPN)', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_mpn" name="_suresalez_mpn" value="<?php echo esc_attr($values['_suresalez_mpn'] ?? ''); ?>" placeholder="e.g. ACM-7000-X">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_mpn']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_ean"><?php esc_html_e('EAN-13 Barcode', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_ean" name="_suresalez_ean" value="<?php echo esc_attr($values['_suresalez_ean'] ?? ''); ?>" placeholder="e.g. 5901234123457">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_ean']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_upc"><?php esc_html_e('UPC-A Barcode', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_upc" name="_suresalez_upc" value="<?php echo esc_attr($values['_suresalez_upc'] ?? ''); ?>" placeholder="e.g. 012345678905">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_upc']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_gtin"><?php esc_html_e('GTIN', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_gtin" name="_suresalez_gtin" value="<?php echo esc_attr($values['_suresalez_gtin'] ?? ''); ?>" placeholder="e.g. 00012345600012">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_gtin']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_hs_code"><?php esc_html_e('HS Customs Tariff Code', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_hs_code" name="_suresalez_hs_code" value="<?php echo esc_attr($values['_suresalez_hs_code'] ?? ''); ?>" placeholder="e.g. 8471.30.01">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_hs_code']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_material"><?php esc_html_e('Material Composition', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_material" name="_suresalez_material" value="<?php echo esc_attr($values['_suresalez_material'] ?? ''); ?>" placeholder="e.g. Anodized Titanium & Sapphire Glass">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_material']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_dimensions"><?php esc_html_e('Package Dimensions', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_dimensions" name="_suresalez_dimensions" value="<?php echo esc_attr($values['_suresalez_dimensions'] ?? ''); ?>" placeholder="e.g. 250 x 180 x 45 mm (420g)">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_dimensions']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_country_origin"><?php esc_html_e('Country of Origin', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_country_origin" name="_suresalez_country_origin" value="<?php echo esc_attr($values['_suresalez_country_origin'] ?? ''); ?>" placeholder="e.g. US, DE, JP">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_country_origin']['description']); ?></p>
            </div>
            <div class="suresalez-field-group">
                <label for="suresalez_warranty"><?php esc_html_e('Warranty Terms', 'suresalez-core'); ?></label>
                <input type="text" id="suresalez_warranty" name="_suresalez_warranty" value="<?php echo esc_attr($values['_suresalez_warranty'] ?? ''); ?>" placeholder="e.g. 3-Year Enterprise Care Plan">
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_warranty']['description']); ?></p>
            </div>
            <div class="suresalez-field-group full-width">
                <label for="suresalez_specs_json"><?php esc_html_e('Extended Technical Specifications (JSON Key/Value)', 'suresalez-core'); ?></label>
                <textarea id="suresalez_specs_json" name="_suresalez_specs_json" rows="4" placeholder='{"Processor": "Octa-Core 3.4GHz", "Battery": "5000 mAh Li-Po", "Display": "6.7-inch OLED 120Hz"}'><?php echo esc_textarea($values['_suresalez_specs_json'] ?? ''); ?></textarea>
                <p class="help-text"><?php echo esc_html(self::FIELDS['_suresalez_specs_json']['description']); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Save metabox data with nonce check & capability authorization.
     *
     * @param int      $post_id
     * @param \WP_Post $post
     * @return void
     */
    public function save_meta_box_data(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['suresalez_pim_nonce']) || !wp_verify_nonce($_POST['suresalez_pim_nonce'], 'suresalez_save_pim_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach (self::FIELDS as $meta_key => $field) {
            if (isset($_POST[$meta_key])) {
                $sanitized = $this->sanitize_field_value($_POST[$meta_key], $meta_key);
                update_post_meta($post_id, $meta_key, $sanitized);
            }
        }
    }

    /**
     * Get all PIM metafields for a product as a clean structured array.
     *
     * @param int $product_id
     * @return array<string, mixed>
     */
    public static function get_product_metafields(int $product_id): array {
        $result = [];
        foreach (self::FIELDS as $key => $meta_def) {
            $clean_key = ltrim($key, '_');
            $val = get_post_meta($product_id, $key, true);
            if ('_suresalez_specs_json' === $key && !empty($val)) {
                $decoded = json_decode($val, true);
                $result['specs'] = is_array($decoded) ? $decoded : [];
            } else {
                $result[$clean_key] = $val ?: null;
            }
        }
        return $result;
    }
}
