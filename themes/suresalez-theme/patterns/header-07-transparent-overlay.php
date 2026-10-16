<?php
/**
 * Title: Header: Transparent Hero Overlay
 * Slug: suresalez-theme/header-07-transparent-overlay
 * Categories: header
 * Block Types: core/template-part/header
 * Description: High-fashion floating transparent header with glassmorphism backdrop blur designed to sit seamlessly over full-bleed hero video or photography.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-transparent-overlay","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-transparent-overlay" style="position:absolute;top:0;left:0;right:0;z-index:50;background:rgba(15,23,42,0.35);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.15);padding-top:1.2rem;padding-bottom:1.2rem;">
  <!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} -->
  <div class="wp-block-group alignwide">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
    <div class="wp-block-group">
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800","letterSpacing":"0.05em"}},"color":{"text":"#ffffff"}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:navigation {"layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"},"style":{"typography":{"fontWeight":"600","fontSize":"0.95rem"}},"color":{"text":"#ffffff"}} -->
      <!-- wp:navigation-link {"label":"Haute Craft","url":"/shop/"} /-->
      <!-- wp:navigation-link {"label":"Collections","url":"/collections/"} /-->
      <!-- wp:navigation-link {"label":"Atelier","url":"/our-atelier/"} /-->
      <!-- wp:navigation-link {"label":"Bespoke","url":"/bespoke/"} /-->
    <!-- /wp:navigation -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1.25rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9rem","fontWeight":"500"}},"color":{"text":"#ffffff"}} -->
      <p class="has-text-color" style="color:#ffffff;font-size:0.9rem;font-weight:500;margin:0;"><a href="/my-account/" style="color:#ffffff;text-decoration:none;">Account</a></p>
      <!-- /wp:paragraph -->

      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Open Cart" style="background:rgba(255,255,255,0.2);color:#ffffff;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:6px 14px;display:flex;align-items:center;gap:8px;cursor:pointer;backdrop-filter:blur(8px);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge" style="background:#ffffff;color:#0f172a;font-size:11px;font-weight:800;padding:1px 6px;border-radius:10px;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
