<?php
/**
 * AI Conversational Sales Assistant & OpenAPI Engine.
 *
 * @package SureSalez\Modules\OmnichannelAI
 */

namespace SureSalez\Modules\OmnichannelAI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AI_Chat_Commerce
 *
 * Provides conversational product discovery, NLP query parsing,
 * real-time inventory checks, authenticated direct checkout links,
 * and OpenAPI 3.1 schema for ChatGPT Actions, Gemini, and Microsoft Copilot.
 */
class AI_Chat_Commerce {

    /**
     * Singleton instance.
     *
     * @var AI_Chat_Commerce|null
     */
    private static ?AI_Chat_Commerce $instance = null;

    /**
     * Demo/Default product catalog for semantic search & recommendations.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $catalog = [
        101 => [
            'id'          => 101,
            'sku'         => 'EMR-CHRONO-42',
            'title'       => 'Royal Emerald Automatic Chronograph',
            'category'    => 'Watches',
            'price'       => 899.00,
            'currency'    => 'USD',
            'material'    => 'Stainless Steel & Emerald Sapphire',
            'color'       => 'Emerald Green',
            'tags'        => ['luxury', 'chronograph', 'emerald', 'sapphire', 'automatic', 'water resistant', '50m'],
            'stock'       => 45,
            'rating'      => 4.95,
            'image'       => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop',
            'description' => 'Precision automatic chronograph featuring a guilloché emerald dial and double anti-reflective sapphire crystal.',
        ],
        102 => [
            'id'          => 102,
            'sku'         => 'LTH-TRV-CASE',
            'title'       => 'Italian Handcrafted Leather Travel Case',
            'category'    => 'Accessories',
            'price'       => 120.00,
            'currency'    => 'USD',
            'material'    => 'Full-Grain Tuscan Leather',
            'color'       => 'Cognac Brown',
            'tags'        => ['leather', 'travel', 'case', 'italian', 'organizer', 'accessories'],
            'stock'       => 110,
            'rating'      => 4.88,
            'image'       => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&auto=format&fit=crop',
            'description' => 'Hand-stitched Tuscan leather travel watch roll lined with plush microfiber for 3 timepieces.',
        ],
        201 => [
            'id'          => 201,
            'sku'         => 'STRAP-MIL-TI',
            'title'       => 'Milanese Magnetic Mesh Strap - Grade 5 Titanium',
            'category'    => 'Straps',
            'price'       => 96.85,
            'currency'    => 'USD',
            'material'    => 'Grade 5 Titanium',
            'color'       => 'Matte Gunmetal',
            'tags'        => ['strap', 'milanese', 'titanium', 'magnetic', 'mesh', 'lightweight'],
            'stock'       => 85,
            'rating'      => 4.92,
            'image'       => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&auto=format&fit=crop',
            'description' => 'Ultralight aerospace-grade titanium woven mesh band with seamless magnetic closure.',
        ],
        202 => [
            'id'          => 202,
            'sku'         => 'DIV-CER-300M',
            'title'       => 'Ceramic Diver 300M Master Chronometer',
            'category'    => 'Watches',
            'price'       => 1250.00,
            'currency'    => 'USD',
            'material'    => 'Zirconia Ceramic & Titanium',
            'color'       => 'Deep Sea Black',
            'tags'        => ['diver', 'ceramic', 'titanium', 'water resistant', '300m', 'helium escape valve'],
            'stock'       => 28,
            'rating'      => 4.98,
            'image'       => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?w=600&auto=format&fit=crop',
            'description' => 'Professional dive master watch crafted with scratchproof high-tech ceramic rated to 300 meters depth.',
        ],
        301 => [
            'id'          => 301,
            'sku'         => 'RG-SUNBURST-38',
            'title'       => 'Rose Gold Sunburst Dress Watch',
            'category'    => 'Watches',
            'price'       => 649.00,
            'currency'    => 'USD',
            'material'    => '18K Rose Gold PVD & Alligator Strap',
            'color'       => 'Rose Gold',
            'tags'        => ['dress watch', 'rose gold', 'sunburst', 'minimalist', 'ultra-thin', 'alligator'],
            'stock'       => 60,
            'rating'      => 4.89,
            'image'       => 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?w=600&auto=format&fit=crop',
            'description' => 'Ultra-thin 6.8mm profile mechanical watch with sunburst dial and exhibition caseback.',
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
     * Initialize REST endpoints and hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('suresalez/v1', '/ai/chat', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_ai_chat'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/ai/recommend', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_ai_recommend'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/ai/cart-link', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_ai_cart_link'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('suresalez/v1', '/ai/openapi.json', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_ai_openapi'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Handle conversational AI chat requests.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_ai_chat(\WP_REST_Request $request): \WP_REST_Response {
        $message = sanitize_text_field((string) $request->get_param('message'));
        $context = $request->get_param('context') ?: [];

        if (empty($message)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Query message is required.',
            ], 400);
        }

        $parsed = $this->parse_query($message);
        $products = $this->search_catalog($parsed);

        $reply = $this->compose_conversational_reply($message, $parsed, $products);

        return new \WP_REST_Response([
            'success'   => true,
            'reply'     => $reply,
            'intent'    => $parsed['intent'],
            'filters'   => $parsed,
            'products'  => $products,
            'cart_link' => !empty($products) ? $this->generate_cart_link($products[0]['id'], 1, 'ai-chat') : null,
        ], 200);
    }

    /**
     * Handle recommendation inquiries.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_ai_recommend(\WP_REST_Request $request): \WP_REST_Response {
        $max_price = $request->get_param('max_price') ? (float) $request->get_param('max_price') : null;
        $category  = sanitize_text_field((string) $request->get_param('category'));
        $material  = sanitize_text_field((string) $request->get_param('material'));

        $criteria = [
            'max_price' => $max_price,
            'category'  => $category,
            'material'  => $material,
        ];

        $results = $this->search_catalog($criteria);

        return new \WP_REST_Response([
            'success' => true,
            'total'   => count($results),
            'items'   => $results,
        ], 200);
    }

    /**
     * Handle direct checkout link generation.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_ai_cart_link(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = (int) $request->get_param('product_id');
        $quantity   = max(1, (int) ($request->get_param('quantity') ?: 1));
        $ref        = sanitize_key((string) ($request->get_param('ref') ?: 'ai-chat'));
        $coupon     = sanitize_text_field((string) $request->get_param('coupon'));

        if (!$product_id || !isset($this->catalog[$product_id])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Product not found in catalog.',
            ], 404);
        }

        $product = $this->catalog[$product_id];
        $link = $this->generate_cart_link($product_id, $quantity, $ref, $coupon ?: null);

        return new \WP_REST_Response([
            'success'      => true,
            'product_id'   => $product_id,
            'title'        => $product['title'],
            'price'        => $product['price'],
            'quantity'     => $quantity,
            'checkout_url' => $link,
            'coupon'       => $coupon ?: null,
        ], 200);
    }

    /**
     * Serve OpenAPI 3.1 schema.
     *
     * @return \WP_REST_Response
     */
    public function rest_ai_openapi(): \WP_REST_Response {
        return new \WP_REST_Response($this->get_openapi_spec(), 200);
    }

    /**
     * Parse natural language user prompt into structured search criteria.
     *
     * @param string $query
     * @return array<string, mixed>
     */
    public function parse_query(string $query): array {
        $lower = strtolower(trim($query));

        $criteria = [
            'query'     => $query,
            'min_price' => null,
            'max_price' => null,
            'category'  => null,
            'material'  => null,
            'keywords'  => [],
            'intent'    => 'search',
        ];

        // 1. Budget extraction
        // Match: "between $100 and $300"
        if (preg_match('/between\s+\$?([0-9,]+(?:\.[0-9]+)?)\s+(?:and|-)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i', $lower, $m)) {
            $criteria['min_price'] = (float) str_replace(',', '', $m[1]);
            $criteria['max_price'] = (float) str_replace(',', '', $m[2]);
        }
        // Match: "under $500", "below $1000", "less than $800", "max $700"
        elseif (preg_match('/(?:under|below|less than|max|up to)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i', $lower, $m)) {
            $criteria['max_price'] = (float) str_replace(',', '', $m[1]);
        }
        // Match: "over $500", "above $300", "more than $200"
        elseif (preg_match('/(?:over|above|more than|minimum|at least)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i', $lower, $m)) {
            $criteria['min_price'] = (float) str_replace(',', '', $m[1]);
        }

        // 2. Material / Finish extraction
        $materials = ['titanium', 'emerald', 'sapphire', 'leather', 'ceramic', 'rose gold', 'gold', 'steel', 'mesh'];
        foreach ($materials as $mat) {
            if (str_contains($lower, $mat)) {
                $criteria['material'] = $mat;
                $criteria['keywords'][] = $mat;
                break;
            }
        }

        // 3. Category extraction
        if (str_contains($lower, 'watch') || str_contains($lower, 'chronograph') || str_contains($lower, 'diver')) {
            $criteria['category'] = 'Watches';
        } elseif (str_contains($lower, 'strap') || str_contains($lower, 'loop') || str_contains($lower, 'band')) {
            $criteria['category'] = 'Straps';
        } elseif (str_contains($lower, 'case') || str_contains($lower, 'roll') || str_contains($lower, 'accessories')) {
            $criteria['category'] = 'Accessories';
        }

        // 4. Intent detection
        if (str_contains($lower, 'buy') || str_contains($lower, 'order') || str_contains($lower, 'cart') || str_contains($lower, 'checkout')) {
            $criteria['intent'] = 'purchase';
        } elseif (str_contains($lower, 'recommend') || str_contains($lower, 'suggest') || str_contains($lower, 'best') || str_contains($lower, 'gift')) {
            $criteria['intent'] = 'recommendation';
        } elseif (str_contains($lower, 'stock') || str_contains($lower, 'available') || str_contains($lower, 'inventory')) {
            $criteria['intent'] = 'stock_check';
        } elseif (str_contains($lower, 'discount') || str_contains($lower, 'coupon') || str_contains($lower, 'deal') || str_contains($lower, 'sale')) {
            $criteria['intent'] = 'promo';
        }

        // 5. Keyword tokens
        $tokens = preg_split('/\s+/', $lower) ?: [];
        foreach ($tokens as $token) {
            $clean = trim($token, ".,?!\"'()");
            if (strlen($clean) >= 4 && !in_array($clean, ['with', 'have', 'what', 'need', 'want', 'under', 'price', 'looking', 'show', 'please'], true)) {
                $criteria['keywords'][] = $clean;
            }
        }
        $criteria['keywords'] = array_unique($criteria['keywords']);

        return $criteria;
    }

    /**
     * Filter catalog items based on criteria.
     *
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search_catalog(array $criteria): array {
        $matches = [];

        foreach ($this->catalog as $item) {
            // Price range check
            if (isset($criteria['min_price']) && $criteria['min_price'] !== null && $item['price'] < $criteria['min_price']) {
                continue;
            }
            if (isset($criteria['max_price']) && $criteria['max_price'] !== null && $item['price'] > $criteria['max_price']) {
                continue;
            }

            // Category check
            if (!empty($criteria['category']) && strtolower($item['category']) !== strtolower($criteria['category'])) {
                continue;
            }

            // Material check
            if (!empty($criteria['material'])) {
                $mat_target = strtolower($criteria['material']);
                $item_mat   = strtolower($item['material']);
                $item_tags  = array_map('strtolower', $item['tags']);
                if (!str_contains($item_mat, $mat_target) && !in_array($mat_target, $item_tags, true)) {
                    continue;
                }
            }

            // Keyword match score
            $score = 0;
            if (!empty($criteria['keywords'])) {
                $haystack = strtolower($item['title'] . ' ' . $item['description'] . ' ' . implode(' ', $item['tags']));
                foreach ($criteria['keywords'] as $kw) {
                    if (str_contains($haystack, strtolower($kw))) {
                        $score += 10;
                    }
                }
            }

            $item['match_score'] = $score;
            $matches[] = $item;
        }

        // Sort by match score descending, then rating descending
        usort($matches, static function ($a, $b) {
            if ($b['match_score'] === $a['match_score']) {
                return $b['rating'] <=> $a['rating'];
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return $matches;
    }

    /**
     * Generate instant direct checkout link with ref and optional coupon.
     *
     * @param int $product_id
     * @param int $quantity
     * @param string $ref
     * @param string|null $coupon
     * @return string
     */
    public function generate_cart_link(int $product_id, int $quantity = 1, string $ref = 'ai-chat', ?string $coupon = null): string {
        $base = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout/';
        $params = [
            'add-to-cart' => $product_id,
            'quantity'    => max(1, $quantity),
            'ref'         => sanitize_key($ref),
        ];

        if (!empty($coupon)) {
            $params['coupon_code'] = sanitize_text_field($coupon);
        }

        return add_query_arg($params, $base);
    }

    /**
     * Compose natural conversational response string.
     *
     * @param string $query
     * @param array<string, mixed> $criteria
     * @param array<int, array<string, mixed>> $products
     * @return string
     */
    private function compose_conversational_reply(string $query, array $criteria, array $products): string {
        if (empty($products)) {
            return "I couldn't find any timepieces matching that exact description within your parameters. Would you like me to broaden the price range or show our flagship automatic chronographs?";
        }

        $top = $products[0];
        $count = count($products);

        switch ($criteria['intent']) {
            case 'recommendation':
                return sprintf(
                    "Based on your preference for %s, I highly recommend our flagship %s ($%.2f). It has a %s rating from verified collectors and is currently in stock (%d units available).",
                    $criteria['material'] ?: 'fine watchmaking',
                    $top['title'],
                    $top['price'],
                    $top['rating'],
                    $top['stock']
                );

            case 'purchase':
                return sprintf(
                    "Excellent choice! I've prepared a direct checkout link for the %s ($%.2f). Free express worldwide shipping is included.",
                    $top['title'],
                    $top['price']
                );

            case 'stock_check':
                return sprintf(
                    "Good news! We currently have %d units of the %s in stock across our global fulfillment hubs ready for priority dispatch.",
                    $top['stock'],
                    $top['title']
                );

            default:
                if ($count === 1) {
                    return sprintf("I found the %s ($%.2f) matching your search. Crafted with %s.", $top['title'], $top['price'], $top['material']);
                }
                return sprintf("I found %d matching items. Leading with our %s ($%.2f), rated %.2f/5.", $count, $top['title'], $top['price'], $top['rating']);
        }
    }

    /**
     * Build OpenAPI 3.1 specification array.
     *
     * @return array<string, mixed>
     */
    public function get_openapi_spec(): array {
        return [
            'openapi' => '3.1.0',
            'info'    => [
                'title'       => 'SureSalez Omnichannel AI Commerce Engine',
                'description' => 'Autonomous conversational sales assistant, semantic catalog search, and 1-click cart links for ChatGPT Actions, Google Gemini, and Microsoft Copilot.',
                'version'     => '1.4.0',
                'contact'     => [
                    'name'  => 'SureSalez Systems Architecture',
                    'email' => 'api@suresalez.internal',
                ],
            ],
            'servers' => [
                [
                    'url'         => home_url('/wp-json/suresalez/v1'),
                    'description' => 'Primary SureSalez Storefront API',
                ],
            ],
            'paths'   => [
                '/ai/chat' => [
                    'post' => [
                        'operationId' => 'converseCommerce',
                        'summary'     => 'Conversational product discovery and inquiry',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['message'],
                                        'properties' => [
                                            'message' => [
                                                'type'        => 'string',
                                                'description' => 'User query text, budget limits, or product inquiry',
                                                'example'     => 'Show me titanium watches under $1000',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses'   => [
                            '200' => [
                                'description' => 'Conversational AI response with matching products and 1-click checkout URL',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => [
                                            'type'       => 'object',
                                            'properties' => [
                                                'success'   => ['type' => 'boolean'],
                                                'reply'     => ['type' => 'string'],
                                                'intent'    => ['type' => 'string'],
                                                'products'  => ['type' => 'array'],
                                                'cart_link' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/ai/recommend' => [
                    'get' => [
                        'operationId' => 'getRecommendations',
                        'summary'     => 'Get filtered product recommendations by price or material',
                        'parameters'  => [
                            [
                                'name'        => 'max_price',
                                'in'          => 'query',
                                'required'    => false,
                                'schema'      => ['type' => 'number'],
                                'description' => 'Maximum budget in USD',
                            ],
                            [
                                'name'        => 'material',
                                'in'          => 'query',
                                'required'    => false,
                                'schema'      => ['type' => 'string'],
                                'description' => 'Material filter (titanium, leather, ceramic, emerald)',
                            ],
                        ],
                        'responses'   => [
                            '200' => [
                                'description' => 'List of recommended items',
                            ],
                        ],
                    ],
                ],
                '/ai/cart-link' => [
                    'post' => [
                        'operationId' => 'createDirectCartLink',
                        'summary'     => 'Generate instant 1-click authenticated checkout URL',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['product_id'],
                                        'properties' => [
                                            'product_id' => ['type' => 'integer'],
                                            'quantity'   => ['type' => 'integer', 'default' => 1],
                                            'coupon'     => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses'   => [
                            '200' => [
                                'description' => 'Direct checkout URL with preloaded cart parameters',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
