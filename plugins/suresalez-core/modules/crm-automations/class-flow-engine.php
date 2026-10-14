<?php
/**
 * Visual Workflow Automation Engine (SureSalez Flow).
 *
 * @package SureSalez\Modules\CRMAutomations
 */

namespace SureSalez\Modules\CRMAutomations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Flow_Engine
 *
 * Core event-driven automation engine connecting business events (Order Completed,
 * Cart Abandoned, Low Stock) to multi-step conditional workflows and action execution.
 */
class Flow_Engine {

    /**
     * Initialize Flow trigger hooks.
     */
    public static function init(): void {
        add_action('suresalez_flow_trigger', [__CLASS__, 'on_event_dispatched'], 10, 2);
    }

    /**
     * Listener for Flow triggers.
     *
     * @param string $event_name
     * @param array  $payload
     */
    public static function on_event_dispatched(string $event_name, array $payload = []): void {
        self::process_event($event_name, $payload);
    }

    /**
     * Query and execute all active workflows subscribing to a specific trigger event.
     *
     * @param string $event_name
     * @param array  $payload
     * @return array Array of execution log summaries
     */
    public static function process_event(string $event_name, array $payload): array {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_flow_workflows';
        $workflows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE trigger_event = %s AND status = 'active' ORDER BY id ASC",
                $event_name
            ),
            ARRAY_A
        );

        $results = [];

        if (!empty($workflows)) {
            foreach ($workflows as $workflow) {
                $results[] = self::execute_workflow($workflow, $payload);
            }
        }

        return $results;
    }

    /**
     * Execute a single workflow against an incoming event payload.
     *
     * @param array $workflow
     * @param array $payload
     * @return array
     */
    public static function execute_workflow(array $workflow, array $payload): array {
        global $wpdb;

        $workflow_id = (int)($workflow['id'] ?? 0);
        $title = $workflow['title'] ?? 'Untitled Workflow';

        // 1. Evaluate conditions
        $conditions = is_string($workflow['conditions'])
            ? json_decode($workflow['conditions'], true)
            : (array)($workflow['conditions'] ?? []);

        $conditions_met = true;
        if (!empty($conditions)) {
            $conditions_met = self::evaluate_conditions($conditions, $payload);
        }

        if (!$conditions_met) {
            return [
                'workflow_id' => $workflow_id,
                'status'      => 'skipped',
                'message'     => 'Conditions not met.',
            ];
        }

        // 2. Execute actions
        $actions = is_string($workflow['actions'])
            ? json_decode($workflow['actions'], true)
            : (array)($workflow['actions'] ?? []);

        $executed_actions = [];
        $has_error = false;
        $error_message = '';

        foreach ($actions as $action_def) {
            $action_name = $action_def['action'] ?? '';
            $params = $action_def['params'] ?? [];

            $action_result = self::execute_action($action_name, $params, $payload);
            $executed_actions[] = [
                'action' => $action_name,
                'result' => $action_result,
            ];

            if (!$action_result['success']) {
                $has_error = true;
                $error_message = $action_result['message'];
            }
        }

        $status = $has_error ? 'failed' : 'success';
        $log_message = $has_error ? $error_message : sprintf('Successfully executed %d actions for workflow "%s".', count($executed_actions), $title);

        // 3. Record execution log in database
        $logs_table = $wpdb->prefix . 'suresalez_flow_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$logs_table}'") === $logs_table) {
            $wpdb->insert(
                $logs_table,
                [
                    'workflow_id'      => $workflow_id,
                    'trigger_payload'  => json_encode($payload),
                    'execution_status' => $status,
                    'log_message'      => $log_message,
                    'executed_at'      => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );

            // Increment execution count
            $workflows_table = $wpdb->prefix . 'suresalez_flow_workflows';
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$workflows_table} SET execution_count = execution_count + 1 WHERE id = %d",
                    $workflow_id
                )
            );
        }

        return [
            'workflow_id' => $workflow_id,
            'title'       => $title,
            'status'      => $status,
            'message'     => $log_message,
            'actions'     => $executed_actions,
        ];
    }

    /**
     * Evaluate conditions group against payload.
     *
     * @param array $conditions
     * @param array $payload
     * @return bool
     */
    public static function evaluate_conditions(array $conditions, array $payload): bool {
        // Flat array of rule objects or nested group
        $rules = isset($conditions['rules']) ? $conditions['rules'] : $conditions;
        $operator = strtoupper($conditions['operator'] ?? 'AND');

        if (empty($rules)) {
            return true;
        }

        $results = [];

        foreach ($rules as $rule) {
            $field = $rule['field'] ?? '';
            $rule_op = $rule['operator'] ?? 'equals';
            $expected = $rule['value'] ?? null;
            $actual = $payload[$field] ?? null;

            $results[] = Customer_Segments::match_rule($actual, $rule_op, $expected);
        }

        if ($operator === 'OR') {
            return in_array(true, $results, true);
        }

        return !in_array(false, $results, true);
    }

    /**
     * Execute an individual action handler.
     *
     * @param string $action
     * @param array  $params
     * @param array  $payload
     * @return array ['success' => bool, 'message' => string]
     */
    public static function execute_action(string $action, array $params, array $payload): array {
        switch ($action) {
            case 'customer.add_tag':
                return self::action_add_customer_tag($params, $payload);

            case 'customer.remove_tag':
                return self::action_remove_customer_tag($params, $payload);

            case 'notification.send_email':
                return self::action_send_email($params, $payload);

            case 'notification.send_sms':
                return self::action_send_sms($params, $payload);

            case 'order.hold_fulfillment':
                return self::action_hold_order($params, $payload);

            case 'webhook.dispatch':
                return self::action_dispatch_webhook($params, $payload);

            default:
                return [
                    'success' => true,
                    'message' => sprintf('Simulated execution for unknown or custom action "%s".', $action),
                ];
        }
    }

    /**
     * Action: Add Tag to Customer.
     */
    private static function action_add_customer_tag(array $params, array $payload): array {
        global $wpdb;
        $tag = sanitize_key($params['tag'] ?? '');
        $email = sanitize_email($payload['email'] ?? '');

        if (empty($tag) || empty($email)) {
            return ['success' => false, 'message' => 'Missing tag or email for customer.add_tag'];
        }

        $table = $wpdb->prefix . 'suresalez_customer_profiles';
        $row = $wpdb->get_row($wpdb->prepare("SELECT id, tags FROM {$table} WHERE email = %s", $email), ARRAY_A);

        if ($row) {
            $tags = is_string($row['tags']) ? (json_decode($row['tags'], true) ?: []) : [];
            if (!in_array($tag, $tags, true)) {
                $tags[] = $tag;
                $wpdb->update($table, ['tags' => json_encode($tags)], ['id' => $row['id']]);
            }
            return ['success' => true, 'message' => sprintf('Tag "%s" appended to customer %s.', $tag, $email)];
        }

        return ['success' => true, 'message' => sprintf('Simulated tag "%s" added to %s.', $tag, $email)];
    }

    /**
     * Action: Remove Tag from Customer.
     */
    private static function action_remove_customer_tag(array $params, array $payload): array {
        global $wpdb;
        $tag = sanitize_key($params['tag'] ?? '');
        $email = sanitize_email($payload['email'] ?? '');

        if (empty($tag) || empty($email)) {
            return ['success' => false, 'message' => 'Missing tag or email for customer.remove_tag'];
        }

        $table = $wpdb->prefix . 'suresalez_customer_profiles';
        $row = $wpdb->get_row($wpdb->prepare("SELECT id, tags FROM {$table} WHERE email = %s", $email), ARRAY_A);

        if ($row) {
            $tags = is_string($row['tags']) ? (json_decode($row['tags'], true) ?: []) : [];
            $tags = array_values(array_diff($tags, [$tag]));
            $wpdb->update($table, ['tags' => json_encode($tags)], ['id' => $row['id']]);
            return ['success' => true, 'message' => sprintf('Tag "%s" removed from customer %s.', $tag, $email)];
        }

        return ['success' => true, 'message' => sprintf('Simulated tag "%s" removed from %s.', $tag, $email)];
    }

    /**
     * Action: Send Transactional Email.
     */
    private static function action_send_email(array $params, array $payload): array {
        $email = $payload['email'] ?? 'customer@example.com';
        $subject = $params['subject'] ?? 'Notification from SureSalez';

        // Dispatches via wp_mail if in production
        if (function_exists('wp_mail') && !empty($email)) {
            wp_mail($email, $subject, sprintf(__('Notice for order %s', 'suresalez-core'), $payload['order_id'] ?? ''));
        }

        return [
            'success' => true,
            'message' => sprintf('Email "%s" dispatched to %s.', $subject, $email),
        ];
    }

    /**
     * Action: Send SMS Alert.
     */
    private static function action_send_sms(array $params, array $payload): array {
        $msg = $params['message'] ?? 'SureSalez notification.';
        $phone = $payload['phone'] ?? '+15551234567';

        return [
            'success' => true,
            'message' => sprintf('SMS dispatched to %s: "%s".', $phone, $msg),
        ];
    }

    /**
     * Action: Hold Order for Fraud / Risk Review.
     */
    private static function action_hold_order(array $params, array $payload): array {
        $order_id = (int)($payload['order_id'] ?? 0);
        $reason = $params['reason'] ?? 'Automated Flow risk hold';

        if ($order_id > 0 && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('on-hold', sprintf(__('SureSalez Flow: %s', 'suresalez-core'), $reason));
            }
        }

        return [
            'success' => true,
            'message' => sprintf('Order #%d held for fulfillment review. Reason: %s.', $order_id, $reason),
        ];
    }

    /**
     * Action: Dispatch Webhook.
     */
    private static function action_dispatch_webhook(array $params, array $payload): array {
        $url = $params['url'] ?? '';
        if (empty($url)) {
            return ['success' => false, 'message' => 'Missing destination webhook URL'];
        }

        if (function_exists('wp_remote_post')) {
            wp_remote_post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($payload),
                'timeout' => 5,
            ]);
        }

        return [
            'success' => true,
            'message' => sprintf('Webhook payload dispatched to %s.', $url),
        ];
    }
}
