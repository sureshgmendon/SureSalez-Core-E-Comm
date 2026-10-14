<?php
/**
 * Enterprise Scale & Multi-Store Organization Module Definition.
 *
 * @package SureSalez\Modules\EnterpriseScale
 */

namespace SureSalez\Modules\EnterpriseScale;

use SureSalez\Core\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-organization-admin.php';
require_once __DIR__ . '/class-tenant-billing.php';
require_once __DIR__ . '/class-enterprise-security.php';
require_once __DIR__ . '/class-scale-engine.php';

/**
 * Class Enterprise_Scale_Module
 *
 * Master module coordinating multi-store tenant orchestration, partner agency white-label billing,
 * immutable audit logs, high-scale checkout queues, and webhook event mesh.
 */
class Enterprise_Scale_Module extends Module_Base {

    public const MODULE_ID = 'enterprise-scale';

    /**
     * Module slug identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return self::MODULE_ID;
    }

    /**
     * Module display title.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Multi-Store Organization Admin, Reseller Tenant Billing & High-Scale APIs';
    }

    /**
     * Module feature description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Multi-tenant organization dashboard, agency white-label configurations, tenant billing meters, enterprise SSO, immutable audit trails, and high-concurrency checkout job queuing.';
    }

    /**
     * Module category.
     *
     * @return string
     */
    public function get_category(): string {
        return 'Enterprise & Scale';
    }

    /**
     * Default active state.
     *
     * @return bool
     */
    public function is_default_active(): bool {
        return true;
    }

    /**
     * Initialize module hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register & enqueue module CSS & JS assets.
     *
     * @return void
     */
    public function register_assets(): void {
        if (!$this->is_active()) {
            return;
        }

        $ver = defined('SURESALEZ_VERSION') ? SURESALEZ_VERSION : '1.9.0';

        // Organization Switcher Assets
        wp_enqueue_style(
            'suresalez-org-switcher-css',
            $this->get_url('assets/css/organization-switcher.css'),
            [],
            $ver
        );

        wp_enqueue_script(
            'suresalez-org-switcher-js',
            $this->get_url('assets/js/organization-switcher.js'),
            [],
            $ver,
            true
        );

        // Tenant Billing Assets
        wp_enqueue_style(
            'suresalez-tenant-billing-css',
            $this->get_url('assets/css/tenant-billing.css'),
            [],
            $ver
        );

        wp_enqueue_script(
            'suresalez-tenant-billing-js',
            $this->get_url('assets/js/tenant-billing.js'),
            [],
            $ver,
            true
        );

        $overview   = Organization_Admin::get_organization_overview(1);
        $current    = Organization_Admin::resolve_current_tenant();
        $billing    = Tenant_Billing::get_billing_summary(1);

        wp_localize_script('suresalez-org-switcher-js', 'suresalezEnterpriseData', [
            'rest_url'       => esc_url_raw(rest_url('suresalez/v1/')),
            'nonce'          => wp_create_nonce('wp_rest'),
            'current_tenant' => $current,
            'overview'       => $overview,
            'billing'        => $billing,
        ]);
    }

    /**
     * Register REST API routes for Enterprise Scale module.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        // GET /suresalez/v1/enterprise/tenants
        register_rest_route('suresalez/v1', '/enterprise/tenants', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_tenants'],
            'permission_callback' => '__return_true',
        ]);

        // POST /suresalez/v1/enterprise/tenants/sync-catalog
        register_rest_route('suresalez/v1', '/enterprise/tenants/sync-catalog', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_sync_catalog'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        // GET /suresalez/v1/enterprise/billing/summary
        register_rest_route('suresalez/v1', '/enterprise/billing/summary', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_billing_summary'],
            'permission_callback' => '__return_true',
        ]);

        // POST /suresalez/v1/enterprise/webhooks/dispatch
        register_rest_route('suresalez/v1', '/enterprise/webhooks/dispatch', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_dispatch_webhook'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        // GET /suresalez/v1/enterprise/audit-logs
        register_rest_route('suresalez/v1', '/enterprise/audit-logs', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_audit_logs'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST Handler: List all organization tenants.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_tenants(\WP_REST_Request $request): \WP_REST_Response {
        $org_id  = (int) ($request->get_param('org_id') ?: 1);
        $tenants = Organization_Admin::get_active_tenants($org_id);

        return new \WP_REST_Response([
            'success' => true,
            'org_id'  => $org_id,
            'tenants' => $tenants,
        ], 200);
    }

    /**
     * REST Handler: Sync product catalog across tenants.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_sync_catalog(\WP_REST_Request $request): \WP_REST_Response {
        $params     = $request->get_json_params() ?: [];
        $product_id = (int) ($params['product_id'] ?? 0);
        $targets    = (array) ($params['tenant_ids'] ?? [1, 2, 3]);
        $overrides  = (array) ($params['overrides'] ?? []);

        $res = Organization_Admin::sync_catalog_to_tenants($product_id, $targets, $overrides);

        return new \WP_REST_Response($res, $res['success'] ? 200 : 400);
    }

    /**
     * REST Handler: Get billing retainer and usage metrics.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_billing_summary(\WP_REST_Request $request): \WP_REST_Response {
        $tenant_id = (int) ($request->get_param('tenant_id') ?: 1);
        $summary   = Tenant_Billing::get_billing_summary($tenant_id);

        return new \WP_REST_Response([
            'success' => true,
            'summary' => $summary,
        ], 200);
    }

    /**
     * REST Handler: Trigger outbound webhook event dispatch.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_dispatch_webhook(\WP_REST_Request $request): \WP_REST_Response {
        $params    = $request->get_json_params() ?: [];
        $topic     = sanitize_text_field($params['topic'] ?? 'order.created');
        $payload   = (array) ($params['payload'] ?? ['order_id' => 9051, 'total' => 899.00]);
        $tenant_id = (int) ($params['tenant_id'] ?? 1);

        $res = Scale_Engine::dispatch_event($topic, $payload, $tenant_id);

        return new \WP_REST_Response($res, 200);
    }

    /**
     * REST Handler: Retrieve immutable audit trail.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_audit_logs(\WP_REST_Request $request): \WP_REST_Response {
        $tenant_id = (int) ($request->get_param('tenant_id') ?: 1);
        $limit     = min(100, max(1, (int) ($request->get_param('limit') ?: 20)));
        $offset    = max(0, (int) ($request->get_param('offset') ?: 0));

        $logs = Enterprise_Security::get_audit_trail($tenant_id, $limit, $offset);

        return new \WP_REST_Response([
            'success' => true,
            'tenant_id' => $tenant_id,
            'count'     => count($logs),
            'logs'      => $logs,
        ], 200);
    }
}
