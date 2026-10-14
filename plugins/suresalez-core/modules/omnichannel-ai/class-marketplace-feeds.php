<?php
/**
 * Marketplace Syndication Feeds Engine (Google Merchant, Meta & TikTok).
 *
 * @package SureSalez\Modules\OmnichannelAI
 */

namespace SureSalez\Modules\OmnichannelAI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Marketplace_Feeds
 *
 * Generates automated, differential merchant feeds:
 * - Google Merchant Center XML (RSS 2.0 with g: namespace and HSN custom label)
 * - Meta Catalog (Facebook/Instagram Shops) XML & CSV
 * - TikTok Shop Catalog JSON
 */
class Marketplace_Feeds {

    /**
     * Singleton instance.
     *
     * @var Marketplace_Feeds|null
     */
    private static ?Marketplace_Feeds $instance = null;

    /**
     * Feed products list (incorporating Sprint 01 PIM metadata & Sprint 03 HSN codes).
     *
     * @var array<int, array<string, mixed>>
     */
    private array $feed_items = [
        [
            'id'             => 'SS-101',
            'title'          => 'Royal Emerald Automatic Chronograph 42mm',
            'description'    => 'Luxury automatic chronograph featuring a guilloché emerald dial, anti-reflective sapphire crystal, and 50m water resistance.',
            'link'           => 'https://suresalez.store/product/royal-emerald-chronograph',
            'image_link'     => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&auto=format&fit=crop',
            'availability'   => 'in stock',
            'price'          => '899.00 USD',
            'brand'          => 'SureSalez Atelier',
            'gtin'           => '0894561234567',
            'mpn'            => 'EMR-CHRONO-42',
            'category'       => 'Apparel & Accessories > Jewelry > Watches',
            'fb_category'    => 'apparel & accessories > jewelry > watches',
            'condition'      => 'new',
            'hsn_code'       => '7117.90.90',
            'inventory'      => 45,
        ],
        [
            'id'             => 'SS-102',
            'title'          => 'Italian Handcrafted Leather Travel Watch Case',
            'description'    => 'Hand-stitched Tuscan leather travel watch roll with plush microfiber lining for 3 luxury timepieces.',
            'link'           => 'https://suresalez.store/product/italian-leather-watch-case',
            'image_link'     => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1200&auto=format&fit=crop',
            'availability'   => 'in stock',
            'price'          => '120.00 USD',
            'brand'          => 'SureSalez Atelier',
            'gtin'           => '0894561234568',
            'mpn'            => 'LTH-TRV-CASE',
            'category'       => 'Apparel & Accessories > Jewelry > Watch Accessories > Watch Boxes & Cases',
            'fb_category'    => 'apparel & accessories > jewelry > watch accessories',
            'condition'      => 'new',
            'hsn_code'       => '4202.92.00',
            'inventory'      => 110,
        ],
        [
            'id'             => 'SS-201',
            'title'          => 'Milanese Magnetic Mesh Strap - Grade 5 Titanium',
            'description'    => 'Aerospace Grade 5 titanium woven mesh strap engineered with continuous magnetic clasp adjustments.',
            'link'           => 'https://suresalez.store/product/milanese-titanium-strap',
            'image_link'     => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=1200&auto=format&fit=crop',
            'availability'   => 'in stock',
            'price'          => '96.85 USD',
            'brand'          => 'SureSalez Atelier',
            'gtin'           => '0894561234569',
            'mpn'            => 'STRAP-MIL-TI',
            'category'       => 'Apparel & Accessories > Jewelry > Watch Accessories > Watch Bands',
            'fb_category'    => 'apparel & accessories > jewelry > watch accessories',
            'condition'      => 'new',
            'hsn_code'       => '9113.20.00',
            'inventory'      => 85,
        ],
        [
            'id'             => 'SS-202',
            'title'          => 'Ceramic Diver 300M Master Chronometer',
            'description'    => 'Professional deep-sea diving timepiece crafted with scratchproof zirconia ceramic and helium escape valve.',
            'link'           => 'https://suresalez.store/product/ceramic-diver-300m',
            'image_link'     => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?w=1200&auto=format&fit=crop',
            'availability'   => 'in stock',
            'price'          => '1250.00 USD',
            'brand'          => 'SureSalez Atelier',
            'gtin'           => '0894561234570',
            'mpn'            => 'DIV-CER-300M',
            'category'       => 'Apparel & Accessories > Jewelry > Watches',
            'fb_category'    => 'apparel & accessories > jewelry > watches',
            'condition'      => 'new',
            'hsn_code'       => '9102.11.00',
            'inventory'      => 28,
        ],
        [
            'id'             => 'SS-301',
            'title'          => 'Rose Gold Sunburst Dress Watch 38mm',
            'description'    => 'Minimalist ultra-thin mechanical dress watch with 18K rose gold casing, sunburst dial, and alligator strap.',
            'link'           => 'https://suresalez.store/product/rose-gold-sunburst-dress-watch',
            'image_link'     => 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?w=1200&auto=format&fit=crop',
            'availability'   => 'in stock',
            'price'          => '649.00 USD',
            'brand'          => 'SureSalez Atelier',
            'gtin'           => '0894561234571',
            'mpn'            => 'RG-SUNBURST-38',
            'category'       => 'Apparel & Accessories > Jewelry > Watches',
            'fb_category'    => 'apparel & accessories > jewelry > watches',
            'condition'      => 'new',
            'hsn_code'       => '9102.11.00',
            'inventory'      => 60,
        ],
    ];

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
     * Initialize REST routes and WordPress feed endpoints.
     *
     * @return void
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_feed('suresalez-google-merchant', [$this, 'render_google_merchant_feed']);
        add_feed('suresalez-meta-catalog', [$this, 'render_meta_catalog_feed']);
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('suresalez/v1', '/feeds/google-merchant', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_google_merchant'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/feeds/meta-catalog', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_meta_catalog'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/feeds/tiktok-catalog', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_tiktok_catalog'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST endpoint for Google Merchant Center XML.
     *
     * @return \WP_REST_Response
     */
    public function rest_google_merchant(): \WP_REST_Response {
        $xml = $this->generate_google_merchant_xml();
        $response = new \WP_REST_Response($xml, 200);
        $response->set_headers([
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'ETag'          => md5($xml),
        ]);
        return $response;
    }

    /**
     * REST endpoint for Meta Catalog feed.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_meta_catalog(\WP_REST_Request $request): \WP_REST_Response {
        $format = $request->get_param('format') === 'csv' ? 'csv' : 'xml';

        if ($format === 'csv') {
            $csv = $this->generate_meta_catalog_csv();
            $response = new \WP_REST_Response($csv, 200);
            $response->set_headers([
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="meta-catalog.csv"',
            ]);
            return $response;
        }

        $xml = $this->generate_meta_catalog_xml();
        $response = new \WP_REST_Response($xml, 200);
        $response->set_headers([
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
        return $response;
    }

    /**
     * REST endpoint for TikTok Shop Catalog JSON.
     *
     * @return \WP_REST_Response
     */
    public function rest_tiktok_catalog(): \WP_REST_Response {
        $data = [
            'catalog_name' => 'SureSalez TikTok Showcase',
            'version'      => '2.0',
            'items_count'  => count($this->feed_items),
            'products'     => $this->feed_items,
        ];
        return new \WP_REST_Response($data, 200);
    }

    /**
     * Generate Google Merchant Center RSS 2.0 XML with g: namespace.
     *
     * @return string
     */
    public function generate_google_merchant_xml(): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);

        $title = $dom->createElement('title', 'SureSalez Enterprise Store Product Feed');
        $channel->appendChild($title);

        $link = $dom->createElement('link', 'https://suresalez.store');
        $channel->appendChild($link);

        $description = $dom->createElement('description', 'Real-time syndicated inventory and pricing for Google Shopping');
        $channel->appendChild($description);

        foreach ($this->feed_items as $item) {
            $entry = $dom->createElement('item');

            $entry->appendChild($dom->createElement('g:id', htmlspecialchars($item['id'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:title', htmlspecialchars($item['title'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:description', htmlspecialchars($item['description'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:link', htmlspecialchars($item['link'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:image_link', htmlspecialchars($item['image_link'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:availability', htmlspecialchars($item['availability'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:price', htmlspecialchars($item['price'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:brand', htmlspecialchars($item['brand'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:gtin', htmlspecialchars($item['gtin'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:mpn', htmlspecialchars($item['mpn'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:google_product_category', htmlspecialchars($item['category'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:condition', htmlspecialchars($item['condition'], ENT_XML1)));
            
            // Custom Label 0 for Indian HSN Code / Tariff Classification (Sprint 03 Tax Integration)
            $entry->appendChild($dom->createElement('g:custom_label_0', htmlspecialchars('HSN:' . $item['hsn_code'], ENT_XML1)));

            $channel->appendChild($entry);
        }

        return (string) $dom->saveXML();
    }

    /**
     * Generate Meta Catalog XML (Facebook / Instagram Shop).
     *
     * @return string
     */
    public function generate_meta_catalog_xml(): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);

        $channel->appendChild($dom->createElement('title', 'SureSalez Meta Commerce Catalog'));
        $channel->appendChild($dom->createElement('link', 'https://suresalez.store'));

        foreach ($this->feed_items as $item) {
            $entry = $dom->createElement('item');
            $entry->appendChild($dom->createElement('g:id', htmlspecialchars($item['id'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:title', htmlspecialchars($item['title'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:description', htmlspecialchars($item['description'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:availability', htmlspecialchars($item['availability'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:condition', htmlspecialchars($item['condition'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:price', htmlspecialchars($item['price'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:link', htmlspecialchars($item['link'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:image_link', htmlspecialchars($item['image_link'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:brand', htmlspecialchars($item['brand'], ENT_XML1)));
            $entry->appendChild($dom->createElement('g:fb_product_category', htmlspecialchars($item['fb_category'], ENT_XML1)));

            $channel->appendChild($entry);
        }

        return (string) $dom->saveXML();
    }

    /**
     * Generate Meta Catalog CSV.
     *
     * @return string
     */
    public function generate_meta_catalog_csv(): string {
        $fp = fopen('php://memory', 'r+');
        if ($fp === false) {
            return '';
        }

        fputcsv($fp, ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'fb_product_category']);

        foreach ($this->feed_items as $item) {
            fputcsv($fp, [
                $item['id'],
                $item['title'],
                $item['description'],
                $item['availability'],
                $item['condition'],
                $item['price'],
                $item['link'],
                $item['image_link'],
                $item['brand'],
                $item['fb_category'],
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv !== false ? $csv : '';
    }

    /**
     * Render direct WordPress feed for Google Merchant.
     *
     * @return void
     */
    public function render_google_merchant_feed(): void {
        header('Content-Type: application/xml; charset=utf-8');
        echo $this->generate_google_merchant_xml();
        exit;
    }

    /**
     * Render direct WordPress feed for Meta.
     *
     * @return void
     */
    public function render_meta_catalog_feed(): void {
        header('Content-Type: application/xml; charset=utf-8');
        echo $this->generate_meta_catalog_xml();
        exit;
    }
}
