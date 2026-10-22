<?php
/**
 * SureSalez Theme — Hybrid PHP Theme Shell
 *
 * Provides a stable, commercial-grade Hybrid presentation shell for the SureSalez
 * decoupled e-commerce architecture. All business logic, custom tables, REST endpoints,
 * and e-commerce calculations reside strictly inside plugins/suresalez-core/.
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SURESALEZ_THEME_VERSION')) {
    define('SURESALEZ_THEME_VERSION', '1.0.0');
}

/**
 * Theme Setup & Feature Support
 */
function suresalez_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');

    // Switch default core markup for search form, comment form, and comments to HTML5.
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Enable wide and full-bleed alignment support for Gutenberg blocks.
    add_theme_support('align-wide');

    // Add support for core custom logo.
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-width'  => true,
        'flex-height' => true,
    ));

    // Add support for responsive embedded content.
    add_theme_support('responsive-embeds');

    // Add support for block editor default styles.
    add_theme_support('wp-block-styles');

    // Add editor styles support.
    add_theme_support('editor-styles');

    // Register primary and mobile navigation menus.
    register_nav_menus(array(
        'primary' => esc_html__('Primary Navigation', 'suresalez-theme'),
        'mobile'  => esc_html__('Mobile Navigation', 'suresalez-theme'),
        'footer'  => esc_html__('Footer Navigation', 'suresalez-theme'),
    ));
}
add_action('after_setup_theme', 'suresalez_theme_setup');

/**
 * Enqueue Theme Stylesheets & Scripts
 */
function suresalez_theme_scripts() {
    // Main stylesheet
    wp_enqueue_style(
        'suresalez-theme-style',
        get_stylesheet_uri(),
        array(),
        SURESALEZ_THEME_VERSION
    );

    // Compile dynamic :root CSS variables (Kadence Core Architecture)
    $dynamic_css = suresalez_compile_dynamic_root_css();
    if (!empty($dynamic_css)) {
        wp_add_inline_style('suresalez-theme-style', $dynamic_css);
    }

    // Lightweight navigation script for mobile drawer toggle
    wp_enqueue_script(
        'suresalez-theme-nav',
        get_template_directory_uri() . '/assets/js/theme-nav.js',
        array(),
        SURESALEZ_THEME_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'suresalez_theme_scripts');

/**
 * Kadence Core Architecture: Dynamic :root Style Compiler
 *
 * Dynamically computes and outputs CSS custom properties into <head>
 * so merchants NEVER have to touch or fix CSS in WordPress.
 *
 * Supports global palette tokens, typography tokens, defensive container clamping,
 * strict logo dimensions, responsive spacing, and button styles.
 *
 * @return string Compiled CSS rules for :root
 */
function suresalez_compile_dynamic_root_css() {
    $variables = array(
        // Luxury Palette Tokens (Oura / Paul Smith)
        '--global-palette1'                    => get_theme_mod('suresalez_color_palette1', '#0a0f1d'),
        '--global-palette2'                    => get_theme_mod('suresalez_color_palette2', '#1e293b'),
        '--global-palette3'                    => get_theme_mod('suresalez_color_palette3', '#3b82f6'),
        '--global-palette4'                    => get_theme_mod('suresalez_color_palette4', '#eff6ff'),
        '--global-palette5'                    => get_theme_mod('suresalez_color_palette5', '#10b981'),
        '--global-palette6'                    => get_theme_mod('suresalez_color_palette6', '#64748b'),
        '--global-palette7'                    => get_theme_mod('suresalez_color_palette7', '#e2e8f0'),
        '--global-palette8'                    => get_theme_mod('suresalez_color_palette8', '#f8fafc'),
        '--global-palette9'                    => get_theme_mod('suresalez_color_palette9', '#ffffff'),

        // Buttons & Highlight Tokens
        '--global-palette-btn-bg'              => get_theme_mod('suresalez_btn_bg', '#0a0f1d'),
        '--global-palette-btn-bg-hover'        => get_theme_mod('suresalez_btn_bg_hover', '#1e293b'),
        '--global-palette-btn'                 => get_theme_mod('suresalez_btn_color', '#ffffff'),
        '--global-palette-btn-hover'           => get_theme_mod('suresalez_btn_color_hover', '#ffffff'),
        '--global-palette-highlight'           => get_theme_mod('suresalez_highlight', '#3b82f6'),

        // Kadence Defensive Container Clamping
        '--global-content-width'               => get_theme_mod('suresalez_content_width', '1440px'),
        '--global-content-edge-padding'        => get_theme_mod('suresalez_content_edge_padding', '1.5rem'),
        '--global-content-edge-padding-mobile' => get_theme_mod('suresalez_content_edge_padding_mobile', '1rem'),

        // Kadence Defensive Logo Dimensions (Strict Clamps)
        '--header-logo-height'                 => get_theme_mod('suresalez_header_logo_height', '60px'),
        '--header-mobile-logo-height'          => get_theme_mod('suresalez_header_mobile_logo_height', '42px'),

        // Spacing Scale
        '--global-xs-spacing'                  => '0.5rem',
        '--global-sm-spacing'                  => '0.75rem',
        '--global-md-spacing'                  => '1.5rem',
        '--global-lg-spacing'                  => '2rem',
        '--global-xl-spacing'                  => '3rem',
        '--global-xxl-spacing'                 => '4rem',

        // Typography
        '--global-body-font-family'            => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        '--global-heading-font-family'         => "'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        '--global-mono-font-family'            => "'JetBrains Mono', Consolas, Monaco, monospace",
    );

    /**
     * Filter the dynamic :root variables allowing modules or customizer extensions
     * to inject or override variables before compilation.
     */
    $variables = apply_filters('suresalez_theme_dynamic_root_vars', $variables);

    $css_rules = array();
    foreach ($variables as $prop => $value) {
        if (!empty($value)) {
            $css_rules[] = '  ' . esc_attr($prop) . ': ' . esc_attr($value) . ';';
        }
    }

    $css = ":root {\n" . implode("\n", $css_rules) . "\n}";

    // Kadence compatibility filter hook
    return apply_filters('kadence_dynamic_css', $css);
}

/**
 * Output dynamic :root styles directly in <head>
 *
 * Injects compiled tokens before layout paints, eliminating CLS and
 * ensuring global tokens are immediately resolved.
 */
function suresalez_output_dynamic_root_css() {
    $dynamic_css = suresalez_compile_dynamic_root_css();
    if (!empty($dynamic_css)) {
        echo "<style id=\"suresalez-theme-dynamic-root-css\">\n" . wp_strip_all_tags($dynamic_css) . "\n</style>\n";
    }
}
add_action('wp_head', 'suresalez_output_dynamic_root_css', 5);

/**
 * Default Semantic Header Rendering Hook
 *
 * Hooks into suresalez_header and kadence_header actions to delegate
 * to the 3-column header template part.
 */
function suresalez_render_default_header() {
    get_template_part('parts/headers/header-main');
}
add_action('suresalez_header', 'suresalez_render_default_header');
add_action('kadence_header', 'suresalez_render_default_header');
