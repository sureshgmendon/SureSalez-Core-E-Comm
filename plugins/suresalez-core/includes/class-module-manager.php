<?php
/**
 * SureSalez Central Module Manager.
 *
 * @package SureSalez\Core
 */

namespace SureSalez\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Module_Manager
 *
 * Coordinates registration, persistent database state, and strictly conditional
 * execution of all feature sub-modules. Zero hooks or assets execute for inactive modules.
 */
class Module_Manager {

    /**
     * Singleton instance.
     *
     * @var Module_Manager|null
     */
    private static ?Module_Manager $instance = null;

    /**
     * Registered module instances indexed by slug.
     *
     * @var array<string, Module_Base>
     */
    private array $modules = [];

    /**
     * Active module state cache.
     *
     * @var array<string, bool>|null
     */
    private ?array $active_states = null;

    /**
     * Option key for persistent settings.
     */
    public const OPTION_KEY = 'suresalez_active_modules';

    /**
     * Get singleton instance.
     *
     * @return Module_Manager
     */
    public static function instance(): Module_Manager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submission']);
        add_action('wp_ajax_suresalez_toggle_module', [$this, 'ajax_toggle_module']);
        add_action('rest_api_init', [$this, 'register_active_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_active_frontend_assets'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Register a module instance with the manager.
     *
     * @param Module_Base $module
     * @return void
     */
    public function register_module(Module_Base $module): void {
        $id = $module->get_id();
        $this->modules[$id] = $module;

        // Conditional Execution: Only initialize if module is verified active
        if ($this->is_module_active($id)) {
            $module->init();
        }
    }

    /**
     * Check if a module is currently active.
     *
     * @param string $module_id
     * @return bool
     */
    public function is_module_active(string $module_id): bool {
        if (null === $this->active_states) {
            $this->load_active_states();
        }

        if (isset($this->active_states[$module_id])) {
            return (bool) $this->active_states[$module_id];
        }

        // Check if module instance defines a default state
        if (isset($this->modules[$module_id])) {
            return $this->modules[$module_id]->is_default_active();
        }

        return true; // Default fallback
    }

    /**
     * Set active state for a module.
     *
     * @param string $module_id
     * @param bool   $is_active
     * @return bool
     */
    public function set_module_status(string $module_id, bool $is_active): bool {
        if (null === $this->active_states) {
            $this->load_active_states();
        }

        $this->active_states[$module_id] = $is_active;
        $updated = update_option(self::OPTION_KEY, $this->active_states);

        do_action('suresalez_module_status_changed', $module_id, $is_active);

        return $updated;
    }

    /**
     * Load active states from wp_options table.
     *
     * @return void
     */
    private function load_active_states(): void {
        $stored = get_option(self::OPTION_KEY, false);

        if (false === $stored || !is_array($stored)) {
            $this->active_states = [];
            // Seed defaults
            foreach ($this->modules as $id => $module) {
                $this->active_states[$id] = $module->is_default_active();
            }
            update_option(self::OPTION_KEY, $this->active_states);
        } else {
            $this->active_states = $stored;
        }
    }

    /**
     * Get all registered modules.
     *
     * @return array<string, Module_Base>
     */
    public function get_modules(): array {
        return $this->modules;
    }

    /**
     * Get a specific module by ID.
     *
     * @param string $module_id
     * @return Module_Base|null
     */
    public function get_module(string $module_id): ?Module_Base {
        return $this->modules[$module_id] ?? null;
    }

    /**
     * Register REST API routes for all active modules.
     *
     * @return void
     */
    public function register_active_rest_routes(): void {
        foreach ($this->modules as $id => $module) {
            if ($this->is_module_active($id)) {
                $module->register_rest_routes();
            }
        }
    }

    /**
     * Enqueue frontend assets for active modules ONLY.
     *
     * @return void
     */
    public function enqueue_active_frontend_assets(): void {
        foreach ($this->modules as $id => $module) {
            if ($this->is_module_active($id)) {
                $module->register_assets();
            }
        }
    }

    /**
     * Enqueue admin UI assets for the Module Manager dashboard.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void {
        if (strpos($hook, 'suresalez-modules') === false && strpos($hook, 'suresalez') === false) {
            return;
        }

        wp_enqueue_style(
            'suresalez-admin-style',
            SURESALEZ_URL . 'admin/assets/css/admin.css',
            [],
            SURESALEZ_VERSION
        );

        wp_enqueue_script(
            'suresalez-admin-script',
            SURESALEZ_URL . 'admin/assets/js/admin.js',
            [],
            SURESALEZ_VERSION,
            true
        );

        wp_localize_script('suresalez-admin-script', 'SureSalezAdminData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('suresalez_toggle_module_nonce'),
        ]);
    }

    /**
     * Register admin menu item in wp-admin.
     *
     * @return void
     */
    public function register_admin_menu(): void {
        add_menu_page(
            __('SureSalez Core', 'suresalez-core'),
            __('SureSalez', 'suresalez-core'),
            'manage_options',
            'suresalez-dashboard',
            [$this, 'render_admin_dashboard'],
            'dashicons-cart',
            26
        );

        add_submenu_page(
            'suresalez-dashboard',
            __('Module Manager', 'suresalez-core'),
            __('Modules', 'suresalez-core'),
            'manage_options',
            'suresalez-modules',
            [$this, 'render_admin_dashboard']
        );
    }

    /**
     * Handle non-AJAX POST form submission from settings page.
     *
     * @return void
     */
    public function handle_form_submission(): void {
        if (!isset($_POST['suresalez_save_modules'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized capability.', 'suresalez-core'));
        }

        check_admin_referer('suresalez_modules_save_action', 'suresalez_modules_nonce');

        $active_submitted = isset($_POST['modules']) && is_array($_POST['modules']) 
            ? array_map('sanitize_key', $_POST['modules']) 
            : [];

        $new_states = [];
        foreach ($this->modules as $id => $module) {
            $is_active = in_array($id, $active_submitted, true);
            $new_states[$id] = $is_active;
        }

        $this->active_states = $new_states;
        update_option(self::OPTION_KEY, $new_states);

        wp_safe_redirect(add_query_arg([
            'page'    => 'suresalez-modules',
            'updated' => 'true'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * AJAX handler for 1-click instantaneous module toggle.
     *
     * @return void
     */
    public function ajax_toggle_module(): void {
        check_ajax_referer('suresalez_toggle_module_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'suresalez-core')], 403);
        }

        $module_id = isset($_POST['module_id']) ? sanitize_key($_POST['module_id']) : '';
        $state     = isset($_POST['state']) && 'true' === $_POST['state'];

        if (empty($module_id) || !isset($this->modules[$module_id])) {
            wp_send_json_error(['message' => __('Invalid module ID.', 'suresalez-core')], 400);
        }

        $this->set_module_status($module_id, $state);

        wp_send_json_success([
            'module_id' => $module_id,
            'is_active' => $state,
            'message'   => sprintf(
                __('Module "%s" is now %s.', 'suresalez-core'),
                $this->modules[$module_id]->get_name(),
                $state ? __('ACTIVE', 'suresalez-core') : __('INACTIVE (Hooks & Assets Unloaded)', 'suresalez-core')
            )
        ]);
    }

    /**
     * Render the admin Module Manager dashboard page.
     *
     * @return void
     */
    public function render_admin_dashboard(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $modules = $this->get_modules();
        $categories = [];

        foreach ($modules as $module) {
            $cat = $module->get_category();
            if (!isset($categories[$cat])) {
                $categories[$cat] = [];
            }
            $categories[$cat][] = $module;
        }

        ?>
        <div class="wrap suresalez-admin-wrap">
            <header class="suresalez-header-bar">
                <div class="suresalez-brand">
                    <span class="dashicons dashicons-superhero-alt brand-icon"></span>
                    <div>
                        <h1>SureSalez Core <span class="badge-version">v<?php echo esc_html(SURESALEZ_VERSION); ?></span></h1>
                        <p class="subtitle"><?php esc_html_e('Decoupled Modular Enterprise E-Commerce Engine', 'suresalez-core'); ?></p>
                    </div>
                </div>
                <div class="suresalez-status-pill">
                    <span class="status-dot green"></span>
                    <?php 
                    $active_count = 0;
                    foreach ($modules as $m) {
                        if ($this->is_module_active($m->get_id())) {
                            $active_count++;
                        }
                    }
                    printf(
                        esc_html__('%1$d of %2$d Modules Active', 'suresalez-core'),
                        $active_count,
                        count($modules)
                    );
                    ?>
                </div>
            </header>

            <?php if (isset($_GET['updated']) && 'true' === $_GET['updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Module configuration successfully updated and saved to persistent database storage.', 'suresalez-core'); ?></strong></p>
                </div>
            <?php endif; ?>

            <div class="suresalez-admin-alert info">
                <span class="dashicons dashicons-shield-alt"></span>
                <p>
                    <strong><?php esc_html_e('Zero-Bloat Architectural Guarantee:', 'suresalez-core'); ?></strong>
                    <?php esc_html_e('When a feature sub-module is toggled OFF, 0 PHP classes instantiate, 0 action/filter hooks register, and 0 bytes of CSS/JS are enqueued into frontend or admin pages.', 'suresalez-core'); ?>
                </p>
            </div>

            <form method="post" action="" id="suresalez-module-form">
                <?php wp_nonce_field('suresalez_modules_save_action', 'suresalez_modules_nonce'); ?>

                <?php foreach ($categories as $category_name => $cat_modules): ?>
                    <div class="suresalez-module-category">
                        <h2>
                            <span class="dashicons dashicons-category"></span>
                            <?php echo esc_html($category_name); ?>
                        </h2>
                        <div class="suresalez-module-grid">
                            <?php foreach ($cat_modules as $mod): 
                                $is_active = $this->is_module_active($mod->get_id());
                                ?>
                                <div class="suresalez-module-card <?php echo $is_active ? 'active' : 'inactive'; ?>" data-module-id="<?php echo esc_attr($mod->get_id()); ?>">
                                    <div class="card-header">
                                        <h3><?php echo esc_html($mod->get_name()); ?></h3>
                                        <label class="suresalez-switch">
                                            <input type="checkbox" name="modules[]" value="<?php echo esc_attr($mod->get_id()); ?>" <?php checked($is_active); ?> class="suresalez-toggle-input" data-module-id="<?php echo esc_attr($mod->get_id()); ?>">
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <p class="module-desc"><?php echo esc_html($mod->get_description()); ?></p>
                                    <div class="card-footer">
                                        <span class="module-id-badge"><code><?php echo esc_html($mod->get_id()); ?></code></span>
                                        <span class="runtime-badge <?php echo $is_active ? 'loaded' : 'unloaded'; ?>">
                                            <?php echo $is_active ? esc_html__('Active & Enqueued', 'suresalez-core') : esc_html__('Unloaded (Zero Bloat)', 'suresalez-core'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="suresalez-form-actions">
                    <button type="submit" name="suresalez_save_modules" class="button button-primary button-hero">
                        <?php esc_html_e('Save Module Configuration', 'suresalez-core'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
}
