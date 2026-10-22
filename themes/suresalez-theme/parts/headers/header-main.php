<?php
/**
 * Main Header Template Part (Hybrid Theme)
 *
 * Provides a responsive luxury header layout:
 * - Desktop (>= 768px): Logo left/center, navigation links, search pill, account link,
 *   currency switcher mount, and slide-out cart trigger right.
 * - Mobile (< 768px): Strictly 2-row layout:
 *   * Row 1: Flex row with hamburger menu (left), brand logo (centered), profile icon (right).
 *   * Row 2: Full-width search input pill directly underneath.
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<header id="masthead" class="suresalez-site-header" role="banner">
  <!-- Desktop Header Layout (>= 768px) -->
  <div class="suresalez-header-desktop">
    <div class="suresalez-header-container">
      <!-- Left: Brand Logo / Title -->
      <div class="suresalez-header-brand">
        <?php if (has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="suresalez-brand-title" rel="home">
            <?php bloginfo('name'); ?>
          </a>
        <?php endif; ?>
      </div>

      <!-- Center: Desktop Navigation Links -->
      <nav class="suresalez-desktop-nav" role="navigation" aria-label="<?php esc_attr_e('Primary Navigation', 'suresalez-theme'); ?>">
        <?php
        if (has_nav_menu('primary')) :
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'suresalez-nav-menu',
                'fallback_cb'    => false,
            ));
        else :
        ?>
          <ul class="suresalez-nav-menu">
            <li><a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Shop', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/collections/')); ?>"><?php esc_html_e('Collections', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'suresalez-theme'); ?></a></li>
          </ul>
        <?php endif; ?>
      </nav>

      <!-- Right: Search Pill, Currency Mount, Account & Cart Drawer -->
      <div class="suresalez-header-actions">
        <!-- Desktop Search Pill -->
        <form role="search" method="get" class="suresalez-search-form" action="<?php echo esc_url(home_url('/')); ?>">
          <div class="suresalez-search-pill-wrap">
            <svg class="suresalez-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" class="suresalez-search-pill" placeholder="<?php esc_attr_e('Search jewelry...', 'suresalez-theme'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
          </div>
        </form>

        <!-- Currency Switcher Semantic Mount -->
        <div id="suresalez-currency-switcher-mount" class="suresalez-currency-mount" role="region" aria-label="<?php esc_attr_e('Currency Switcher', 'suresalez-theme'); ?>"></div>

        <!-- Account Profile Link -->
        <a href="<?php echo esc_url(is_user_logged_in() ? get_permalink(get_option('woocommerce_myaccount_page_id')) ?: home_url('/account/') : wp_login_url()); ?>" class="suresalez-header-icon-btn" aria-label="<?php esc_attr_e('My Account', 'suresalez-theme'); ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>

        <!-- Slide-Out Cart Drawer Mount -->
        <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
          <button type="button" class="suresalez-cart-btn" aria-label="<?php esc_attr_e('Open Cart Drawer', 'suresalez-theme'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span class="suresalez-cart-count-badge">0</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Header Layout (< 768px): Strictly 2-Row Layout -->
  <div class="suresalez-header-mobile">
    <!-- Row 1: Hamburger (left), Logo (centered), Profile (right) -->
    <div class="suresalez-mobile-row-1">
      <div class="suresalez-mobile-col-left">
        <button type="button" class="suresalez-hamburger-toggle" aria-label="<?php esc_attr_e('Toggle Navigation Menu', 'suresalez-theme'); ?>" aria-expanded="false" aria-controls="mobile-navigation-drawer">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </div>

      <div class="suresalez-mobile-col-center">
        <?php if (has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="suresalez-mobile-brand" rel="home">
            <?php bloginfo('name'); ?>
          </a>
        <?php endif; ?>
      </div>

      <div class="suresalez-mobile-col-right">
        <a href="<?php echo esc_url(is_user_logged_in() ? get_permalink(get_option('woocommerce_myaccount_page_id')) ?: home_url('/account/') : wp_login_url()); ?>" class="suresalez-mobile-profile" aria-label="<?php esc_attr_e('Account', 'suresalez-theme'); ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
      </div>
    </div>

    <!-- Row 2: Full-Width Search Input Pill Directly Underneath -->
    <div class="suresalez-mobile-row-2">
      <form role="search" method="get" class="suresalez-mobile-search-form" action="<?php echo esc_url(home_url('/')); ?>">
        <div class="suresalez-mobile-search-pill-wrap">
          <svg class="suresalez-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" class="suresalez-mobile-search-pill" placeholder="<?php esc_attr_e('Search luxury jewellery & collections...', 'suresalez-theme'); ?>" value="<?php echo get_search_query(); ?>" name="s" aria-label="<?php esc_attr_e('Search', 'suresalez-theme'); ?>" />
        </div>
      </form>
    </div>
  </div>

  <!-- Mobile Slide-Out Drawer Navigation Panel -->
  <div id="mobile-navigation-drawer" class="suresalez-mobile-drawer" aria-hidden="true">
    <div class="suresalez-drawer-inner">
      <div class="suresalez-drawer-header">
        <span class="suresalez-drawer-title"><?php esc_html_e('Menu', 'suresalez-theme'); ?></span>
        <button type="button" class="suresalez-drawer-close" aria-label="<?php esc_attr_e('Close Menu', 'suresalez-theme'); ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <nav class="suresalez-drawer-nav">
        <?php
        if (has_nav_menu('mobile') || has_nav_menu('primary')) :
            wp_nav_menu(array(
                'theme_location' => has_nav_menu('mobile') ? 'mobile' : 'primary',
                'container'      => false,
                'menu_class'     => 'suresalez-drawer-menu',
                'fallback_cb'    => false,
            ));
        else :
        ?>
          <ul class="suresalez-drawer-menu">
            <li><a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Shop All', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/collections/')); ?>"><?php esc_html_e('Collections', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About Story', 'suresalez-theme'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'suresalez-theme'); ?></a></li>
          </ul>
        <?php endif; ?>
      </nav>

      <div class="suresalez-drawer-footer">
        <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="suresalez-drawer-cta-btn">
          <?php esc_html_e('Explore Autumn Drop →', 'suresalez-theme'); ?>
        </a>
      </div>
    </div>
  </div>
  <div class="suresalez-drawer-overlay"></div>
</header>
