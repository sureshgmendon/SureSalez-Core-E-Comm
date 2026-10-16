<?php
/**
 * Title: Header: Stacked Clean
 * Slug: suresalez-theme/header-10-stacked-clean
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Minimalist stacked header featuring an off-canvas drawer toggle left, elegant centered brand wordmark, and direct customer wallet link with credit balance badge right.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-stacked-clean","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-stacked-clean" style="background-color:#ffffff;border-bottom:1px solid #e2e8f0;padding:16px 24px;">
  <!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} -->
  <div class="wp-block-group alignwide">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"0.75rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:html -->
      <button type="button" class="suresalez-stacked-menu-btn" aria-label="Open Menu" style="display:inline-flex;align-items:center;gap:8px;background:none;border:1px solid #e2e8f0;border-radius:6px;padding:6px 12px;font-size:0.85rem;font-weight:600;color:#0f172a;cursor:pointer;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        <span>Menu</span>
      </button>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"}} -->
    <div class="wp-block-group">
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800","letterSpacing":"0.05em","textTransform":"uppercase"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"600"}}} -->
      <p style="font-size:0.85rem;font-weight:600;margin:0;"><a href="/my-account/wallet/" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#0f172a;background:#f8fafc;border:1px solid #e2e8f0;padding:6px 12px;border-radius:6px;">💳 Wallet Credit</a></p>
      <!-- /wp:paragraph -->

      <!-- wp:html -->
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Cart" style="display:inline-flex;align-items:center;gap:6px;background:#0f172a;color:#fff;border:none;border-radius:6px;padding:7px 12px;cursor:pointer;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="suresalez-cart-count-badge" style="background:#3b82f6;color:#fff;font-size:11px;font-weight:700;padding:1px 6px;border-radius:10px;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
