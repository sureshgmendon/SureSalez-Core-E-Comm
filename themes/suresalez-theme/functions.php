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
