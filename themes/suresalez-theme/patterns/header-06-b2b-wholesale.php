<?php
/**
 * Title: Header: B2B Wholesale Portal
 * Slug: suresalez-theme/header-06-b2b-wholesale
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Dedicated wholesale B2B merchant header featuring statutory GSTIN/EIN verification badges, quick bulk order matrix link, RFQ quote generator, and store credit wallet balance pill.
 */
?>
<!-- wp:group {"tagName":"header","align":"full","className":"suresalez-header suresalez-header-b2b","layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull suresalez-header suresalez-header-b2b" style="background-color:#ffffff;border-bottom:2px solid #3b82f6;">
  <!-- wp:group {"align":"full","style":{"color":{"background":"#1e293b","text":"#f1f5f9"},"spacing":{"padding":{"top":"8px","bottom":"8px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignfull" style="background-color:#1e293b;color:#f1f5f9;padding-top:8px;padding-bottom:8px;">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"0.75rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem","fontWeight":"700"}},"className":"suresalez-tax-badge"} -->
      <p class="suresalez-tax-badge" style="background:#059669;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:700;margin:0;">GSTIN VERIFIED WHOLESALE</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem","fontWeight":"500"}}} -->
      <p style="font-size:0.78rem;font-weight:500;margin:0;">HSN 7117 Compliant &bull; Net-30 Payment Terms Approved</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;"><a href="/b2b-company-dashboard/" style="color:#93c5fd;text-decoration:none;font-weight:600;">Corporate Accounts Portal &rarr;</a></p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.78rem"}}} -->
      <p style="font-size:0.78rem;margin:0;">B2B Desk: +1 (888) B2B-SURE</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"16px","bottom":"16px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-top:16px;padding-bottom:16px;">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800"}}} /-->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem","fontWeight":"700","letterSpacing":"0.05em"}},"color":{"text":"#3b82f6"}} -->
      <p class="has-text-color" style="color:#3b82f6;border-left:2px solid #e2e8f0;padding-left:10px;font-size:0.75rem;font-weight:700;letter-spacing:0.05em;margin:0;">WHOLESALE</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem","fontWeight":"600"}}} -->
      <p style="font-size:0.85rem;font-weight:600;margin:0;"><a href="/b2b-order-matrix/" style="text-decoration:none;color:#0f172a;display:inline-flex;align-items:center;gap:6px;">📋 Bulk CSV Order Matrix</a></p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons">
        <!-- wp:button {"backgroundColor":"accent","style":{"typography":{"fontSize":"0.82rem","fontWeight":"600"},"spacing":{"padding":{"top":"7px","bottom":"7px","left":"14px","right":"14px"}}}} -->
        <div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="/request-a-quote/" style="font-size:0.82rem;font-weight:600;padding-top:7px;padding-bottom:7px;padding-left:14px;padding-right:14px;">Submit RFQ Quote</a></div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:html -->
      <div id="suresalez-wallet-balance-mount" class="suresalez-wallet-pill-wrap">
        <a href="/my-account/wallet/" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:6px;text-decoration:none;font-size:0.8rem;font-weight:700;">
          <span>Credit:</span>
          <span class="suresalez-wallet-balance-val">$2,450.00</span>
        </a>
      </div>
      <div id="suresalez-cart-drawer-mount" class="suresalez-cart-trigger-wrap">
        <button type="button" class="suresalez-cart-btn" aria-label="Order Cart" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#0f172a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.82rem;">
          <span>PO Items</span>
          <span class="suresalez-cart-count-badge" style="background:#3b82f6;color:#fff;padding:2px 6px;border-radius:9999px;font-size:0.75rem;">0</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</header>
<!-- /wp:group -->
