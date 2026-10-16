<?php
/**
 * Title: Header: Minimal Split
 * Slug: suresalez-theme/header-01-minimal-split
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Clean split header with brand logo on left, centered navigation menu, and search plus slide-out cart drawer trigger on right.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-minimal-split","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-minimal-split" style="padding-top:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid #e2e8f0;background-color:#ffffff;">
  <!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
    <div class="wp-block-group">
      <!-- wp:site-logo {"width":140,"shouldSyncIcon":false} /-->
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"1.35rem","letterSpacing":"-0.02em"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:navigation {"layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"},"style":{"typography":{"fontWeight":"500","fontSize":"0.95rem"}}} -->
      <!-- wp:navigation-link {"label":"Shop","url":"/shop/"} /-->
      <!-- wp:navigation-link {"label":"Collections","url":"/collections/"} /-->
      <!-- wp:navigation-link {"label":"About","url":"/about/"} /-->
      <!-- wp:navigation-link {"label":"Contact","url":"/contact/"} /-->
    <!-- /wp:navigation -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1.25rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search jewelry...","width":180,"widthUnit":"px","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true} /-->
      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Open Slide-Out Cart">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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
