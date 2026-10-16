<?php
/**
 * Title: Header: Split Balanced Navigation
 * Slug: suresalez-theme/header-05-split-balanced
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Symmetrical editorial header with three catalog navigation items left, prominent center monogram logo, and three brand/account links plus live cart drawer right.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-split-balanced","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-split-balanced" style="background-color:#ffffff;border-bottom:1px solid #e2e8f0;padding-top:1.25rem;padding-bottom:1.25rem;">
  <!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} -->
  <div class="wp-block-group alignwide">
    <!-- wp:navigation {"layout":{"type":"flex","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.9rem","letterSpacing":"0.02em"}}} -->
      <!-- wp:navigation-link {"label":"Shop All","url":"/shop/"} /-->
      <!-- wp:navigation-link {"label":"New In","url":"/collections/new-in/"} /-->
      <!-- wp:navigation-link {"label":"Bridal Heritage","url":"/collections/bridal/"} /-->
    <!-- /wp:navigation -->

    <!-- wp:group {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"}} -->
    <div class="wp-block-group">
      <!-- wp:site-logo {"width":120,"shouldSyncIcon":false} /-->
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800","letterSpacing":"0.08em","textTransform":"uppercase"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1.5rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:navigation {"layout":{"type":"flex","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.9rem","letterSpacing":"0.02em"}}} -->
        <!-- wp:navigation-link {"label":"Our Story","url":"/about/"} /-->
        <!-- wp:navigation-link {"label":"Journal","url":"/blog/"} /-->
        <!-- wp:navigation-link {"label":"Contact","url":"/contact/"} /-->
      <!-- /wp:navigation -->

      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Cart" style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;cursor:pointer;font-weight:600;color:#0f172a;">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge" style="background:#0f172a;color:#fff;font-size:11px;font-weight:700;padding:1px 6px;border-radius:10px;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
