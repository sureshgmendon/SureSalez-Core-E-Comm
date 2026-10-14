<?php
/**
 * SureSalez REST API Controller Engine.
 *
 * @package SureSalez\Core
 */

namespace SureSalez\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class REST_API
 *
 * Central base controller and namespace coordinator for all SureSalez REST endpoints.
 */
class REST_API {

    /**
     * API Namespace.
     */
    public const NAMESPACE = 'suresalez/v1';

    /**
     * Initialize REST endpoints.
     *
     * @return void
     */
    public static function init(): void {
        add_action('rest_api_init', [__CLASS__, 'register_base_routes']);
    }

    /**
     * Register core system routes.
     *
     * @return void
     */
    public static function register_base_routes(): void {
        register_rest_route(self::NAMESPACE, '/system/status', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_system_status'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Health check and active module inspection endpoint.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_system_status(\WP_REST_Request $request): \WP_REST_Response {
        $manager = Module_Manager::instance();
        $modules = $manager->get_modules();

        $active_list = [];
        $inactive_list = [];

        foreach ($modules as $id => $module) {
            if ($manager->is_module_active($id)) {
                $active_list[] = [
                    'id'       => $id,
                    'name'     => $module->get_name(),
                    'category' => $module->get_category(),
                ];
            } else {
                $inactive_list[] = [
                    'id'       => $id,
                    'name'     => $module->get_name(),
                    'category' => $module->get_category(),
                ];
            }
        }

        return new \WP_REST_Response([
            'engine'    => 'SureSalez Core',
            'version'   => SURESALEZ_VERSION,
            'timestamp' => current_time('c'),
            'summary'   => [
                'total_modules'    => count($modules),
                'active_modules'   => count($active_list),
                'inactive_modules' => count($inactive_list),
            ],
            'active'    => $active_list,
            'inactive'  => $inactive_list,
        ], 200);
    }
}
