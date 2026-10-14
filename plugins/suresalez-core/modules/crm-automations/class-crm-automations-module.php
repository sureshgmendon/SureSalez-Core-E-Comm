<?php
/**
 * Customer CRM, Segments & Visual Workflow Engine (Flow) Module.
 *
 * @package SureSalez\Modules\CRMAutomations
 */

namespace SureSalez\Modules\CRMAutomations;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-customer-segments.php';
require_once __DIR__ . '/class-flow-engine.php';
require_once __DIR__ . '/class-chat-lead-capture.php';

/**
 * Class CRM_Automations_Module
 */
class CRM_Automations_Module extends Module_Base {

    public function get_id(): string {
        return 'crm-automations';
    }

    public function get_name(): string {
        return __('Customer CRM, Segments & Visual Workflow Engine (Flow)', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Unified customer 360 intelligence, dynamic behavioral segmentation, visual Flow automation engine with multi-channel triggers, conversational AI chat, and exit-intent lead capture.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Marketing & CRM';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        Customer_Segments::init();
        Flow_Engine::init();

        // Footer injection for chat and lead capture widgets
        add_action('wp_footer', [$this, 'inject_frontend_widgets'], 35);
    }

    public function register_assets(): void {
        wp_enqueue_style(
            'suresalez-chat-css',
            $this->get_url('assets/css/chat-widget.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-chat-js',
            $this->get_url('assets/js/chat-widget.js'),
            ['jquery'],
            SURESALEZ_VERSION,
            true
        );

        wp_localize_script(
            'suresalez-chat-js',
            'sureSalezChatConfig',
            [
                'restUrl'     => esc_url_raw(rest_url('suresalez/v1/chat/message')),
                'nonce'       => wp_create_nonce('wp_rest'),
                'whatsappUrl' => Chat_Lead_Capture::get_whatsapp_url(),
            ]
        );

        wp_enqueue_style(
            'suresalez-lead-capture-css',
            $this->get_url('assets/css/lead-capture.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-lead-capture-js',
            $this->get_url('assets/js/lead-capture.js'),
            ['jquery'],
            SURESALEZ_VERSION,
            true
        );

        wp_localize_script(
            'suresalez-lead-capture-js',
            'sureSalezLeadConfig',
            [
                'restUrl' => esc_url_raw(rest_url('suresalez/v1/crm/lead-capture')),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]
        );
    }

    public function register_routes(): void {
        // 1. Customer 360 Profile
        REST_API::register_route('/crm/customer-360', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_customer_360'],
            'permission_callback' => '__return_true',
        ]);

        // 2. Behavioral Segments
        REST_API::register_route('/crm/segments', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_segments'],
            'permission_callback' => '__return_true',
        ]);

        // 3. Test Flow Automation
        REST_API::register_route('/flow/workflows/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_test_workflow'],
            'permission_callback' => '__return_true',
            'args'                => [
                'event'   => ['required' => true, 'type' => 'string'],
                'payload' => ['required' => false, 'type' => 'object'],
            ],
        ]);

        // 4. Chat Message
        REST_API::register_route('/chat/message', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_chat_message'],
            'permission_callback' => '__return_true',
            'args'                => [
                'message' => ['required' => true, 'type' => 'string'],
            ],
        ]);

        // 5. Lead Capture
        REST_API::register_route('/crm/lead-capture', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_lead_capture'],
            'permission_callback' => '__return_true',
            'args'                => [
                'email' => ['required' => true, 'type' => 'string'],
            ],
        ]);
    }

    /**
     * REST: Get Customer 360 profile.
     */
    public function rest_get_customer_360(\WP_REST_Request $request): \WP_REST_Response {
        $email = sanitize_email($request->get_param('email') ?? '');
        if (empty($email) && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $email = $current_user->user_email;
        }

        if (empty($email)) {
            $email = 'alex.vance@enterprise.com'; // Demo fallback
        }

        $profile = Customer_Segments::get_or_create_profile($email);
        $segments = $profile ? Customer_Segments::get_customer_segments($profile) : [];

        return new \WP_REST_Response([
            'success'  => true,
            'profile'  => $profile,
            'segments' => $segments,
        ], 200);
    }

    /**
     * REST: Get all segments.
     */
    public function rest_get_segments(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'suresalez_segments';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A);

        return new \WP_REST_Response([
            'success'  => true,
            'segments' => $rows ?: [],
        ], 200);
    }

    /**
     * REST: Trigger / test Flow automation.
     */
    public function rest_test_workflow(\WP_REST_Request $request): \WP_REST_Response {
        $event = sanitize_text_field($request->get_param('event'));
        $payload = (array)($request->get_param('payload') ?? []);

        $results = Flow_Engine::process_event($event, $payload);

        return new \WP_REST_Response([
            'success' => true,
            'event'   => $event,
            'results' => $results,
        ], 200);
    }

    /**
     * REST: Process Live Chat message.
     */
    public function rest_chat_message(\WP_REST_Request $request): \WP_REST_Response {
        $msg = sanitize_text_field($request->get_param('message'));
        $bot_res = Chat_Lead_Capture::generate_bot_response($msg);

        return new \WP_REST_Response([
            'success'  => true,
            'response' => $bot_res,
        ], 200);
    }

    /**
     * REST: Lead capture submit.
     */
    public function rest_lead_capture(\WP_REST_Request $request): \WP_REST_Response {
        $email = sanitize_email($request->get_param('email'));
        if (!is_email($email)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Invalid email address'], 400);
        }

        $profile = Customer_Segments::get_or_create_profile($email);
        if ($profile) {
            Customer_Segments::record_timeline_event(
                (int)$profile['id'],
                'lead_captured',
                ['source' => 'exit_intent_modal', 'voucher' => 'WELCOME25']
            );
        }

        return new \WP_REST_Response([
            'success' => true,
            'voucher' => 'WELCOME25',
            'message' => 'Voucher WELCOME25 generated and applied.',
        ], 200);
    }

    /**
     * Injects live chat widget and lead capture modal into footer if not mounted in FSE template.
     */
    public function inject_frontend_widgets(): void {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        echo Chat_Lead_Capture::render_live_chat_widget(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Chat_Lead_Capture::render_lead_capture_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
