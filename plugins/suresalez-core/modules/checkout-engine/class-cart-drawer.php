<?php
/**
 * AJAX Slide-Out Cart Drawer Engine.
 *
 * @package SureSalez\Modules\CheckoutEngine
 */

namespace SureSalez\Modules\CheckoutEngine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Cart_Drawer
 *
 * Powers dynamic off-canvas cart drawer with free-shipping meter, in-drawer
 * cross-sells, one-click add-ons (gift wrap, polishing cloth), and debounced notes.
 */
class Cart_Drawer {

    public const FREE_SHIPPING_THRESHOLD = 150.00;

    /**
     * Get default sample cart items for demo / fallback.
     *
     * @return array
     */
    public static function get_cart_state(): array {
        // Return session-stored cart state or fallback demo cart
        $stored = get_transient('suresalez_demo_cart_state');
        if (is_array($stored) && !empty($stored['items'])) {
            return self::calculate_cart_totals($stored);
        }

        $default_cart = [
            'items' => [
                [
                    'id'           => 101,
                    'title'        => 'Apex Pro Chronograph — Titanium Edition',
                    'variant'      => 'Midnight Slate / Titanium Link',
                    'price'        => 899.00,
                    'quantity'     => 1,
                    'image'        => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=300&q=80',
                ],
            ],
            'addons' => [
                'gift_wrap'       => false,
                'polishing_cloth' => false,
            ],
            'order_notes' => '',
        ];

        return self::calculate_cart_totals($default_cart);
    }

    /**
     * Calculate subtotal, free shipping progress, and total.
     *
     * @param array $cart
     * @return array
     */
    public static function calculate_cart_totals(array $cart): array {
        $subtotal = 0.00;
        foreach ($cart['items'] as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }

        // Add-ons calculation
        $addon_total = 0.00;
        if (!empty($cart['addons']['gift_wrap'])) {
            $addon_total += 5.00;
        }
        if (!empty($cart['addons']['polishing_cloth'])) {
            $addon_total += 15.00;
        }

        $total = $subtotal + $addon_total;
        $threshold = self::FREE_SHIPPING_THRESHOLD;
        $progress = min(100, round(($subtotal / $threshold) * 100));
        $remaining = max(0, round($threshold - $subtotal, 2));

        $cart['subtotal']          = round($subtotal, 2);
        $cart['addon_total']       = round($addon_total, 2);
        $cart['total']             = round($total, 2);
        $cart['shipping_threshold']= $threshold;
        $cart['shipping_progress'] = $progress;
        $cart['shipping_remaining']= $remaining;
        $cart['free_shipping']     = ($subtotal >= $threshold);
        $cart['item_count']        = array_sum(array_column($cart['items'], 'quantity'));

        return $cart;
    }

    /**
     * Update item quantity in cart.
     *
     * @param int $product_id
     * @param int $quantity
     * @return array
     */
    public static function update_item_quantity(int $product_id, int $quantity): array {
        $cart = self::get_cart_state();
        $updated_items = [];

        foreach ($cart['items'] as $item) {
            if ($item['id'] === $product_id) {
                if ($quantity > 0) {
                    $item['quantity'] = $quantity;
                    $updated_items[] = $item;
                }
            } else {
                $updated_items[] = $item;
            }
        }

        $cart['items'] = $updated_items;
        $recalculated = self::calculate_cart_totals($cart);
        set_transient('suresalez_demo_cart_state', $recalculated, 12 * HOUR_IN_SECONDS);
        return $recalculated;
    }

    /**
     * Toggle add-on (gift wrap, polishing cloth).
     *
     * @param string $addon_key
     * @param bool   $state
     * @return array
     */
    public static function toggle_addon(string $addon_key, bool $state): array {
        $cart = self::get_cart_state();
        if (isset($cart['addons'][$addon_key])) {
            $cart['addons'][$addon_key] = $state;
        }
        $recalculated = self::calculate_cart_totals($cart);
        set_transient('suresalez_demo_cart_state', $recalculated, 12 * HOUR_IN_SECONDS);
        return $recalculated;
    }

    /**
     * Save order notes.
     *
     * @param string $notes
     * @return array
     */
    public static function save_notes(string $notes): array {
        $cart = self::get_cart_state();
        $cart['order_notes'] = sanitize_textarea_field($notes);
        set_transient('suresalez_demo_cart_state', $cart, 12 * HOUR_IN_SECONDS);
        return $cart;
    }
}
