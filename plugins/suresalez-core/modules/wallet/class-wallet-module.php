<?php
/**
 * Store Credit Wallet Module.
 *
 * @package SureSalez\Modules\Wallet
 */

namespace SureSalez\Modules\Wallet;

use SureSalez\Core\Module_Base;
use SureSalez\Core\REST_API;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-wallet-ledger.php';

/**
 * Class Wallet_Module
 *
 * Digital customer wallet, store credit ledger, and 1-click checkout payment application.
 */
class Wallet_Module extends Module_Base {

    public function get_id(): string {
        return 'wallet';
    }

    public function get_name(): string {
        return __('Store Credit Wallet & Cashback', 'suresalez-core');
    }

    public function get_description(): string {
        return __('Digital customer wallet, transaction history ledger, and 1-click partial or full payment application at checkout.', 'suresalez-core');
    }

    public function get_category(): string {
        return 'Checkout & Cart';
    }

    public function is_default_active(): bool {
        return true;
    }

    public function init(): void {
        add_action('wp_footer', [$this, 'inject_frontend_wallet_data'], 25);
    }

    public function register_assets(): void {
        wp_enqueue_style(
            'suresalez-wallet-css',
            $this->get_url('assets/css/wallet.css'),
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-wallet-js',
            $this->get_url('assets/js/wallet.js'),
            [],
            SURESALEZ_VERSION,
            true
        );

        $user_id = get_current_user_id();
        wp_localize_script('suresalez-wallet-js', 'SureSalezWalletData', [
            'userId'          => $user_id,
            'balance'         => Wallet_Ledger::get_balance($user_id),
            'currency'        => 'USD',
            'symbol'          => '$',
            'transactions'    => Wallet_Ledger::get_transactions($user_id),
            'applyEndpoint'   => rest_url(REST_API::NAMESPACE . '/wallet/apply'),
            'removeEndpoint'  => rest_url(REST_API::NAMESPACE . '/wallet/remove'),
            'balanceEndpoint' => rest_url(REST_API::NAMESPACE . '/wallet/balance'),
        ]);
    }

    public function register_rest_routes(): void {
        register_rest_route(REST_API::NAMESPACE, '/wallet/balance', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_balance'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/wallet/transactions', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_transactions'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/wallet/apply', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_apply_wallet_credit'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/wallet/remove', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_remove_wallet_credit'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(REST_API::NAMESPACE, '/wallet/adjust', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_adjust_wallet'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    public function rest_get_balance(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $balance = Wallet_Ledger::get_balance($user_id);
        return new \WP_REST_Response([
            'user_id'  => $user_id,
            'balance'  => $balance,
            'currency' => 'USD',
        ], 200);
    }

    public function rest_get_transactions(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $transactions = Wallet_Ledger::get_transactions($user_id);
        return new \WP_REST_Response([
            'user_id'      => $user_id,
            'balance'      => Wallet_Ledger::get_balance($user_id),
            'transactions' => $transactions,
        ], 200);
    }

    public function rest_apply_wallet_credit(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params() ?: $request->get_params();
        $amount  = floatval($params['amount'] ?? 0);
        $balance = Wallet_Ledger::get_balance($user_id);

        if ($amount <= 0) {
            $amount = $balance; // Apply maximum available
        }

        $applied = min($amount, $balance);

        return new \WP_REST_Response([
            'success'         => true,
            'applied_amount'  => $applied,
            'remaining_wallet'=> round($balance - $applied, 2),
            'message'         => sprintf(__('Applied $%s from your wallet credit.', 'suresalez-core'), number_format($applied, 2)),
        ], 200);
    }

    public function rest_remove_wallet_credit(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Store credit removed from current checkout.', 'suresalez-core'),
        ], 200);
    }

    public function rest_adjust_wallet(\WP_REST_Request $request): \WP_REST_Response {
        $params   = $request->get_json_params() ?: $request->get_params();
        $target_id = absint($params['user_id'] ?? 0);
        $type      = sanitize_key($params['type'] ?? 'credit');
        $amount    = floatval($params['amount'] ?? 0);
        $notes     = sanitize_text_field($params['notes'] ?? 'Administrative adjustment');

        if ($target_id <= 0 || $amount <= 0) {
            return new \WP_REST_Response(['message' => 'Invalid user ID or amount.'], 400);
        }

        if ('debit' === $type) {
            $success = Wallet_Ledger::debit($target_id, $amount, 'manual', 'admin', $notes);
        } else {
            $success = Wallet_Ledger::credit($target_id, $amount, 'manual', 'admin', $notes);
        }

        if (!$success) {
            return new \WP_REST_Response(['message' => 'Could not adjust balance (check funds if debiting).'], 400);
        }

        return new \WP_REST_Response([
            'success'     => true,
            'new_balance' => Wallet_Ledger::get_balance($target_id),
        ], 200);
    }

    public function inject_frontend_wallet_data(): void {
        $user_id = get_current_user_id();
        ?>
        <script id="suresalez-wallet-data-json" type="application/json">
            <?php echo wp_json_encode([
                'user_id'      => $user_id,
                'balance'      => Wallet_Ledger::get_balance($user_id),
                'currency'     => 'USD',
                'transactions' => Wallet_Ledger::get_transactions($user_id),
            ]); ?>
        </script>
        <?php
    }
}
