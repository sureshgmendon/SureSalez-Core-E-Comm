<?php
/**
 * Abstract Base Class for SureSalez Modules.
 *
 * @package SureSalez\Core
 */

namespace SureSalez\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Module_Base
 *
 * Abstract blueprint that every SureSalez sub-module must extend.
 */
abstract class Module_Base {

    /**
     * Unique alphanumeric slug for the module.
     *
     * @return string
     */
    abstract public function get_id(): string;

    /**
     * Human-readable module name.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Descriptive summary of functionality.
     *
     * @return string
     */
    abstract public function get_description(): string;

    /**
     * Category for grouping in admin UI (e.g. Catalog, Logistics, Checkout).
     *
     * @return string
     */
    abstract public function get_category(): string;

    /**
     * Whether this module defaults to active upon initial plugin installation.
     *
     * @return bool
     */
    public function is_default_active(): bool {
        return true;
    }

    /**
     * Initialize module hooks and logic.
     * MUST ONLY be called if this module is verified active by Module_Manager.
     *
     * @return void
     */
    abstract public function init(): void;

    /**
     * Register or enqueue frontend and admin assets conditionally.
     *
     * @return void
     */
    public function register_assets(): void {
        // Child modules override this to register CSS and JS.
    }

    /**
     * Register REST API routes specific to this module.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        // Child modules override this to register routes under suresalez/v1.
    }

    /**
     * Check if this module is currently active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return Module_Manager::instance()->is_module_active($this->get_id());
    }

    /**
     * Get URL to this module's directory.
     *
     * @param string $path Optional relative path.
     * @return string
     */
    public function get_url(string $path = ''): string {
        $base = SURESALEZ_URL . 'modules/' . $this->get_id() . '/';
        return $base . ltrim($path, '/');
    }

    /**
     * Get filesystem path to this module's directory.
     *
     * @param string $path Optional relative path.
     * @return string
     */
    public function get_path(string $path = ''): string {
        $base = SURESALEZ_PATH . 'modules/' . $this->get_id() . '/';
        return $base . ltrim($path, '/');
    }
}
