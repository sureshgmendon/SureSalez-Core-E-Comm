<?php
/**
 * Checkout Extensibility & Field Customizer Engine.
 *
 * @package SureSalez\Modules\CheckoutEngine
 */

namespace SureSalez\Modules\CheckoutEngine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Checkout_Customizer
 *
 * Configures single-page vs multi-step breadcrumb checkout, suppresses redundant
 * fields to optimize mobile conversion, and enforces strict validation rules.
 */
class Checkout_Customizer {

    public const OPTION_CHECKOUT_CONFIG = 'suresalez_checkout_config';

    /**
     * Get checkout layout and field customizer settings.
     *
     * @return array
     */
    public static function get_config(): array {
        $defaults = [
            'layout'             => 'multi_step', // 'multi_step' or 'single_page'
            'suppress_company'   => true,
            'suppress_address_2' => true,
            'suppress_comments'  => false,
            'require_phone'      => true,
            'enable_express_pay' => true,
            'steps'              => [
                ['id' => 'contact',  'label' => '1. Information'],
                ['id' => 'shipping', 'label' => '2. Shipping & Hubs'],
                ['id' => 'payment',  'label' => '3. Payment & Review'],
            ],
        ];

        $stored = get_option(self::OPTION_CHECKOUT_CONFIG, []);
        return is_array($stored) ? wp_parse_args($stored, $defaults) : $defaults;
    }

    /**
     * Update checkout settings.
     *
     * @param array $new_settings
     * @return bool
     */
    public static function update_config(array $new_settings): bool {
        return update_option(self::OPTION_CHECKOUT_CONFIG, $new_settings);
    }

    /**
     * Validate checkout payload fields against strict validation rules.
     *
     * @param array $data
     * @return array Array with ['valid' => bool, 'errors' => array]
     */
    public static function validate_checkout_data(array $data): array {
        $errors = [];

        // Required contact fields
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors['email'] = __('Please provide a valid email address.', 'suresalez-core');
        }

        if (empty($data['first_name'])) {
            $errors['first_name'] = __('First name is required.', 'suresalez-core');
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = __('Last name is required.', 'suresalez-core');
        }

        if (empty($data['address_1'])) {
            $errors['address_1'] = __('Street address is required.', 'suresalez-core');
        }

        if (empty($data['city'])) {
            $errors['city'] = __('City is required.', 'suresalez-core');
        }

        if (empty($data['postal_code'])) {
            $errors['postal_code'] = __('Postal / ZIP code is required.', 'suresalez-core');
        } else {
            // Postal code regex validation
            $postal = strtoupper(trim($data['postal_code']));
            if (strlen($postal) < 3 || strlen($postal) > 10) {
                $errors['postal_code'] = __('Invalid postal code format.', 'suresalez-core');
            }
        }

        // Conditional Tax ID / GSTIN check if provided
        if (!empty($data['tax_id'])) {
            $tax_id = sanitize_text_field($data['tax_id']);
            if (strlen($tax_id) < 8) {
                $errors['tax_id'] = __('Invalid Tax / GSTIN format.', 'suresalez-core');
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}
