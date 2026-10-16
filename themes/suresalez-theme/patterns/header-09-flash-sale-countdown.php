<?php
/**
 * Title: Header: Flash Sale Countdown
 * Slug: suresalez-theme/header-09-flash-sale-countdown
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Urgency-driven promotional header with live countdown campaign banner mount, discount marquee, and instant Shop Sale CTA.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-flash-sale","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-flash-sale" style="background-color:#ffffff;border-bottom:2px solid #ef4444;">
  <!-- wp:html -->
  <div id="suresalez-campaign-banner-mount" class="suresalez-campaign-bar" style="background:linear-gradient(90deg, #b91c1c 0%, #ef4444 50%, #b91c1c 100%);color:#ffffff;padding:8px 16px;text-align:center;font-size:0.82rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:16px;">
    <span>⚡ FLASH SALE ENDS IN: <span class="suresalez-countdown-timer" style="background:rgba(0,0,0,0.3);padding:2px 8px;border-radius:4px;font-family:monospace;letter-spacing:1px;">04h 32m 19s</span></span>
    <span>USE CODE: <strong style="text-decoration:underline;">FLASH35</strong> FOR EXTRA 35% OFF</span>
  </div>
  <!-- /wp:html -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"14px","bottom":"14px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-top:14px;padding-bottom:14px;">
    <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800"}}} /-->

    <!-- wp:navigation {"layout":{"type":"flex","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.9rem"}}} -->
      <!-- wp:navigation-link {"label":"⚡ Flash Deals","url":"/flash-deals/"} /-->
      <!-- wp:navigation-link {"label":"Chains","url":"/product-category/chains/"} /-->
      <!-- wp:navigation-link {"label":"Earrings","url":"/product-category/earrings/"} /-->
      <!-- wp:navigation-link {"label":"Bangles","url":"/product-category/bangles-bracelets/"} /-->
    <!-- /wp:navigation -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:buttons -->
      <div class="wp-block-buttons">
        <!-- wp:button {"style":{"color":{"background":"#ef4444","text":"#ffffff"},"typography":{"fontSize":"0.85rem","fontWeight":"700"}}} -->
        <div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="/flash-deals/" style="background-color:#ef4444;color:#ffffff;font-size:0.85rem;font-weight:700;border-radius:6px;padding:8px 16px;">Shop Flash Drop &rarr;</a></div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Cart" style="display:inline-flex;align-items:center;gap:6px;background:#0f172a;color:#fff;border:none;border-radius:6px;padding:8px 12px;cursor:pointer;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge" style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-size:11px;font-weight:700;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
