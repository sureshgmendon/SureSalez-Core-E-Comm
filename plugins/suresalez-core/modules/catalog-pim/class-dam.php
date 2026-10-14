<?php
/**
 * Digital Asset Management (DAM) Engine.
 *
 * @package SureSalez\Modules\CatalogPIM
 */

namespace SureSalez\Modules\CatalogPIM;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DAM
 *
 * Handles enterprise digital assets including 360-degree spin image sequences,
 * downloadable PDF/CAD technical datasheets, and high-res asset delivery.
 */
class DAM {

    /**
     * Meta keys for DAM.
     */
    public const META_360_IMAGES = '_suresalez_dam_360_images';
    public const META_DATASHEETS  = '_suresalez_dam_datasheets';

    /**
     * Initialize DAM hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action('add_meta_boxes', [$this, 'register_dam_meta_box']);
        add_action('save_post_product', [$this, 'save_dam_meta'], 10, 2);
    }

    /**
     * Register DAM meta box.
     *
     * @return void
     */
    public function register_dam_meta_box(): void {
        add_meta_box(
            'suresalez_dam_assets_box',
            __('SureSalez DAM: 360 Viewer & Technical Datasheets', 'suresalez-core'),
            [$this, 'render_dam_meta_box'],
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Render the admin DAM metabox.
     *
     * @param \WP_Post $post
     * @return void
     */
    public function render_dam_meta_box(\WP_Post $post): void {
        wp_nonce_field('suresalez_save_dam_meta', 'suresalez_dam_nonce');

        $images_360 = get_post_meta($post->ID, self::META_360_IMAGES, true);
        $datasheets = get_post_meta($post->ID, self::META_DATASHEETS, true);

        if (!is_string($images_360)) {
            $images_360 = is_array($images_360) ? implode("\n", $images_360) : '';
        }
        if (!is_string($datasheets)) {
            $datasheets = is_array($datasheets) ? wp_json_encode($datasheets, JSON_PRETTY_PRINT) : '';
        }
        ?>
        <div style="padding: 10px 0;">
            <p><strong><?php esc_html_e('360-Degree Interactive Image Spin Sequence (One image URL per line):', 'suresalez-core'); ?></strong></p>
            <textarea name="_suresalez_dam_360_images" rows="4" style="width:100%; font-family:monospace; font-size:12px;" placeholder="https://cdn.example.com/products/frame-01.jpg&#10;https://cdn.example.com/products/frame-02.jpg&#10;..."><?php echo esc_textarea($images_360); ?></textarea>
            <p class="description"><?php esc_html_e('Add 24 to 72 sequential frame URLs for smooth 360-degree rotation in the frontend viewer.', 'suresalez-core'); ?></p>

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #e2e8f0;">

            <p><strong><?php esc_html_e('Downloadable Technical Datasheets & CAD Specs (JSON Array):', 'suresalez-core'); ?></strong></p>
            <textarea name="_suresalez_dam_datasheets" rows="4" style="width:100%; font-family:monospace; font-size:12px;" placeholder='[{"title":"Technical Whitepaper (PDF)","url":"https://example.com/spec.pdf","size":"2.4 MB","type":"PDF"}]'><?php echo esc_textarea($datasheets); ?></textarea>
            <p class="description"><?php esc_html_e('Structured array of downloadable engineering specs, manuals, and compliance certificates.', 'suresalez-core'); ?></p>
        </div>
        <?php
    }

    /**
     * Save DAM meta values.
     *
     * @param int      $post_id
     * @param \WP_Post $post
     * @return void
     */
    public function save_dam_meta(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['suresalez_dam_nonce']) || !wp_verify_nonce($_POST['suresalez_dam_nonce'], 'suresalez_save_dam_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['_suresalez_dam_360_images'])) {
            $raw_lines = explode("\n", str_replace("\r", "", (string) $_POST['_suresalez_dam_360_images']));
            $clean_urls = [];
            foreach ($raw_lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    $clean_urls[] = esc_url_raw($trimmed);
                }
            }
            update_post_meta($post_id, self::META_360_IMAGES, $clean_urls);
        }

        if (isset($_POST['_suresalez_dam_datasheets'])) {
            $raw_json = trim((string) $_POST['_suresalez_dam_datasheets']);
            if (empty($raw_json)) {
                delete_post_meta($post_id, self::META_DATASHEETS);
            } else {
                $decoded = json_decode($raw_json, true);
                if (is_array($decoded)) {
                    $clean_docs = [];
                    foreach ($decoded as $item) {
                        if (is_array($item)) {
                            $clean_docs[] = [
                                'title' => sanitize_text_field($item['title'] ?? ''),
                                'url'   => esc_url_raw($item['url'] ?? ''),
                                'size'  => sanitize_text_field($item['size'] ?? ''),
                                'type'  => sanitize_text_field($item['type'] ?? 'PDF'),
                            ];
                        }
                    }
                    update_post_meta($post_id, self::META_DATASHEETS, $clean_docs);
                }
            }
        }
    }

    /**
     * Get DAM assets for a product.
     *
     * @param int $product_id
     * @return array<string, mixed>
     */
    public static function get_product_dam_assets(int $product_id): array {
        $images_360 = get_post_meta($product_id, self::META_360_IMAGES, true);
        $datasheets = get_post_meta($product_id, self::META_DATASHEETS, true);

        return [
            'images_360' => is_array($images_360) ? $images_360 : [],
            'datasheets' => is_array($datasheets) ? $datasheets : [],
        ];
    }
}
