<?php
/**
 * Title: Header: Mobile App Shell
 * Slug: suresalez-theme/header-04-app-mobile
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Compact, streamlined app header optimized for mobile-first touch interfaces with off-canvas menu launcher, centered brand wordmark, and live micro-cart.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-app-mobile","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-app-mobile" style="position:sticky;top:0;z-index:40;background-color:#ffffff;border-bottom:1px solid #e2e8f0;padding:12px 16px;">
  <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} -->
  <div class="wp-block-group" style="width:100%;">
    <!-- wp:html -->
    <div class="suresalez-mobile-menu-wrap">
      <button type="button" class="suresalez-hamburger-btn" aria-label="Toggle Mobile Menu" style="background:none;border:none;padding:6px;cursor:pointer;display:flex;align-items:center;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
    <!-- /wp:html -->

    <!-- wp:group {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-group">
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"800","letterSpacing":"-0.02em"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"0.75rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:html -->
      <a href="/search/" class="suresalez-mobile-search-btn" aria-label="Search" style="color:#0f172a;display:flex;align-items:center;padding:6px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </a>
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Cart" style="background:#0f172a;color:#fff;border:none;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;position:relative;cursor:pointer;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge" style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
