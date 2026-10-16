<?php
/**
 * Title: Header: Centered Luxury
 * Slug: suresalez-theme/header-02-centered-luxury
 * Categories: header
 * Block Types: core/template-part/header
 * Description: High-end luxury header with top announcement bar, large centered jewelry brand identity, and centered catalog navigation below.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-centered-luxury","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-centered-luxury" style="background-color:#ffffff;border-bottom:1px solid #e2e8f0;">
  <!-- wp:group {"align":"full","style":{"color":{"background":"#0f172a","text":"#f8fafc"},"spacing":{"padding":{"top":"8px","bottom":"8px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
  <div class="wp-block-group alignfull" style="background-color:#0f172a;color:#f8fafc;padding-top:8px;padding-bottom:8px;">
    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.8rem","letterSpacing":"0.08em","textTransform":"uppercase","fontWeight":"600"}}} -->
    <p class="has-text-align-center" style="font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;font-weight:600;margin:0;">Complimentary Insured Express Delivery Worldwide On Orders Over $150 / ₹4,999</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"24px","bottom":"16px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-top:24px;padding-bottom:16px;">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"500"}}} -->
      <p style="font-size:0.85rem;font-weight:500;margin:0;"><a href="/our-atelier/" style="text-decoration:none;color:#64748b;">The Atelier</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"500"}}} -->
      <p style="font-size:0.85rem;font-weight:500;margin:0;"><a href="/sustainability/" style="text-decoration:none;color:#64748b;">Certified PVD</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
    <div class="wp-block-group" style="text-align:center;">
      <!-- wp:site-title {"level":1,"style":{"typography":{"fontSize":"2rem","fontStyle":"normal","fontWeight":"700","letterSpacing":"0.12em","textTransform":"uppercase"}}} /-->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem","letterSpacing":"0.15em","textTransform":"uppercase","fontWeight":"500"},"color":{"text":"#94a3b8"}}} -->
      <p class="has-text-color" style="color:#94a3b8;font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;font-weight:500;margin:4px 0 0;">Haute Imitation Jewellery</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"500"}}} -->
      <p style="font-size:0.85rem;font-weight:500;margin:0;"><a href="/my-account/" style="text-decoration:none;color:#0f172a;">Account</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Open Bag">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span class="suresalez-cart-count-badge">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- wp:navigation {"align":"full","layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"},"style":{"spacing":{"padding":{"top":"12px","bottom":"16px"}},"typography":{"fontWeight":"600","fontSize":"0.9rem","letterSpacing":"0.05em","textTransform":"uppercase"}}} -->
    <!-- wp:navigation-link {"label":"Chains & Chokers","url":"/product-category/chains/"} /-->
    <!-- wp:navigation-link {"label":"Earrings & Jhumkas","url":"/product-category/earrings/"} /-->
    <!-- wp:navigation-link {"label":"Bangles & Kadas","url":"/product-category/bangles-bracelets/"} /-->
    <!-- wp:navigation-link {"label":"Pendants & Medallions","url":"/product-category/pendants/"} /-->
    <!-- wp:navigation-link {"label":"High Craft 24K","url":"/collections/high-craft/"} /-->
  <!-- /wp:navigation -->
</header>
<!-- /wp:group -->
