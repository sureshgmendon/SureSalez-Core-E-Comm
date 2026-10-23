<?php
/**
 * The header template for SureSalez Hybrid Theme
 *
 * Displays all of the <head> section and starts the page structure,
 * delegating the visual header to parts/headers/header-main.php.
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="profile" href="https://gmpg.org/xfn/11">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary">
  <?php esc_html_e('Skip to content', 'suresalez-theme'); ?>
</a>

<div id="page" class="site-wrapper">
  <?php
  /**
   * Semantic Header Action Hooks (SureSalez Core Architecture)
   *
   * Hooked by default: suresalez_render_default_header -> parts/headers/header-main
   */
  do_action('suresalez_before_header');

  if (has_action('suresalez_header')) {
      do_action('suresalez_header');
  } else {
      get_template_part('parts/headers/header-main');
  }

  do_action('suresalez_after_header');
  ?>
