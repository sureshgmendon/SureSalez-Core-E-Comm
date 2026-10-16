<?php
/**
 * Title: Header: Multi-Currency Global
 * Slug: suresalez-theme/header-08-multi-currency-global
 * Categories: header
 * Block Types: core/template-part/header
 * Description: International cross-border header integrating the SureSalez Core multi-currency switcher mount, geo-IP country resolution, and duty-paid delivery assurances.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-global","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-global" style="background-color:#ffffff;border-bottom:1px solid #e2e8f0;">
  <!-- wp:group {"align":"full","style":{"color":{"background":"#0f172a","text":"#f8fafc"},"spacing":{"padding":{"top":"6px","bottom":"6px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignfull" style="background-color:#0f172a;color:#f8fafc;padding-top:6px;padding-bottom:6px;">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem","fontWeight":"500"}}} -->
      <p style="font-size:0.78rem;font-weight:500;margin:0;">🌍 Cross-Border DDP Delivered &bull; Import Duties Included</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;">International Hotline: +1 (800) 992-SURE</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:html -->
      <div id="suresalez-currency-switcher-mount" class="suresalez-currency-wrap">
        <label for="suresalez-currency-select" class="screen-reader-text" style="position:absolute;width:1px;height:1px;overflow:hidden;">Select Currency</label>
        <select id="suresalez-currency-select" class="suresalez-currency-select" style="background:#1e293b;color:#f8fafc;border:1px solid #334155;border-radius:4px;padding:3px 8px;font-size:0.75rem;cursor:pointer;">
          <option value="USD">USD ($)</option>
          <option value="INR" selected>INR (₹)</option>
          <option value="EUR">EUR (€)</option>
          <option value="GBP">GBP (£)</option>
          <option value="AED">AED (د.إ)</option>
        </select>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"16px","bottom":"16px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-top:16px;padding-bottom:16px;">
    <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800"}}} /-->

    <!-- wp:navigation {"layout":{"type":"flex","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.9rem"}}} -->
      <!-- wp:navigation-link {"label":"Global Collections","url":"/shop/"} /-->
      <!-- wp:navigation-link {"label":"Chains (HSN 7117)","url":"/product-category/chains/"} /-->
      <!-- wp:navigation-link {"label":"Earrings","url":"/product-category/earrings/"} /-->
      <!-- wp:navigation-link {"label":"Bangles","url":"/product-category/bangles-bracelets/"} /-->
      <!-- wp:navigation-link {"label":"International Shipping Policy","url":"/shipping/"} /-->
    <!-- /wp:navigation -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1.25rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Cart" style="display:inline-flex;align-items:center;gap:6px;background:#0f172a;color:#fff;border:none;border-radius:6px;padding:7px 14px;cursor:pointer;font-weight:600;font-size:0.85rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
