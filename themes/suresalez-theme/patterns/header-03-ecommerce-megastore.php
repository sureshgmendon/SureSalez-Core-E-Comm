<?php
/**
 * Title: Header: E-Commerce Megastore
 * Slug: suresalez-theme/header-03-ecommerce-megastore
 * Categories: header
 * Block Types: core/template-part/header
 * Description: 3-tier high-conversion e-commerce megastore layout featuring customer service top-strip, full-width search and utility row, and category navigation with promotional pill badges.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-megastore","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-megastore" style="background-color:#ffffff;border-bottom:2px solid #0f172a;">
  <!-- wp:group {"align":"full","style":{"color":{"background":"#f8fafc","text":"#475569"},"spacing":{"padding":{"top":"6px","bottom":"6px"}},"border":{"bottom":{"color":"#e2e8f0","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between"}} -->
  <div class="wp-block-group alignfull" style="background-color:#f8fafc;color:#475569;border-bottom:1px solid #e2e8f0;padding-top:6px;padding-bottom:6px;">
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem","fontWeight":"500"}}} -->
    <p style="font-size:0.78rem;font-weight:500;margin:0;">📦 10 Global Inventory Hubs &bull; Same-Day Dispatch &bull; BIS Certified</p>
    <!-- /wp:paragraph -->
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"1.25rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;"><a href="/track-order/" style="text-decoration:none;color:#475569;">Track Order</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;"><a href="/b2b-wholesale/" style="text-decoration:none;color:#475569;font-weight:600;">B2B Wholesale Portal</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;">📞 24/7 Concierge: +1 (800) 555-SURE</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"16px","bottom":"16px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-top:16px;padding-bottom:16px;">
    <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.6rem","fontWeight":"800","letterSpacing":"-0.03em"}}} /-->

    <!-- wp:group {"style":{"layout":{"selfStretch":"fill"}},"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-group" style="max-width:550px;width:100%;">
      <!-- wp:search {"label":"Search Catalog","showLabel":false,"placeholder":"Search 50+ fine imitation designs by SKU, style or stone...","width":100,"widthUnit":"%","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1.5rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"600"}}} -->
      <p style="font-size:0.85rem;font-weight:600;margin:0;"><a href="/my-account/" style="text-decoration:none;color:#0f172a;">👤 Sign In</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#0f172a;color:#fff;border-radius:6px;border:none;cursor:pointer;font-weight:600;font-size:0.85rem;">
          <span>🛒 Cart</span>
          <span class="suresalez-cart-count-badge" style="background:#3b82f6;color:#fff;padding:2px 7px;border-radius:9999px;font-size:0.75rem;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"align":"full","style":{"color":{"background":"#0f172a","text":"#f8fafc"},"spacing":{"padding":{"top":"10px","bottom":"10px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignfull" style="background-color:#0f172a;color:#f8fafc;padding-top:10px;padding-bottom:10px;">
    <!-- wp:navigation {"layout":{"type":"flex","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.85rem","textTransform":"uppercase"}}} -->
      <!-- wp:navigation-link {"label":"All Products","url":"/shop/"} /-->
      <!-- wp:navigation-link {"label":"Chains & Chokers","url":"/product-category/chains/"} /-->
      <!-- wp:navigation-link {"label":"Earrings & Jhumkas","url":"/product-category/earrings/"} /-->
      <!-- wp:navigation-link {"label":"Bangles & Bracelets","url":"/product-category/bangles-bracelets/"} /-->
      <!-- wp:navigation-link {"label":"Pendants & Lockets","url":"/product-category/pendants/"} /-->
      <!-- wp:navigation-link {"label":"Flash Sale 🔥","url":"/sale/"} /-->
    <!-- /wp:navigation -->
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem","fontWeight":"700"}},"color":{"text":"#fbbf24"}} -->
    <p class="has-text-color" style="color:#fbbf24;font-size:0.78rem;font-weight:700;margin:0;">⚡ FESTIVE 35% OFF WITH CODE: FESTIVE35</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
