<?php
/**
 * Title: Footer: Mobile Sticky Navigation Dock
 * Slug: suresalez-theme/footer-10-mobile-sticky-dock
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Mobile app-style sticky bottom navigation dock featuring instant thumb-reach navigation for Home, Shop, Search, Wishlist, and Slide-Out Cart Trigger with badge counter.
 */
?>
<!-- wp:group {"tagName":"footer","align":"full","className":"suresalez-footer suresalez-footer-sticky-dock-wrap","layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull suresalez-footer suresalez-footer-sticky-dock-wrap" style="background-color:#ffffff;border-top:1px solid #e2e8f0;padding-top:3rem;padding-bottom:5rem;">
  <!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
  <div class="wp-block-group alignwide" style="padding-bottom:1.5rem;border-bottom:1px solid #f1f5f9;">
    <!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"800"}}} /-->
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem"}},"color":{"text":"#64748b"}} -->
    <p class="has-text-color" style="color:#64748b;font-size:0.85rem;margin:0;">&copy; 2026 SureSalez Mobile App Experience. All rights reserved.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:html -->
  <nav class="suresalez-mobile-sticky-dock" aria-label="Mobile Bottom Navigation" style="position:fixed;bottom:0;left:0;right:0;z-index:99;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-top:1px solid #e2e8f0;display:flex;justify-content:space-around;align-items:center;padding:8px 0;box-shadow:0 -4px 12px rgba(0,0,0,0.05);">
    <a href="/" class="suresalez-dock-item" style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#0f172a;font-size:10px;font-weight:600;gap:2px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span>Home</span>
    </a>
    <a href="/shop/" class="suresalez-dock-item" style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#64748b;font-size:10px;font-weight:600;gap:2px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <span>Shop</span>
    </a>
    <a href="/search/" class="suresalez-dock-item" style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#64748b;font-size:10px;font-weight:600;gap:2px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <span>Search</span>
    </a>
    <a href="/wishlist/" class="suresalez-dock-item" style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#64748b;font-size:10px;font-weight:600;gap:2px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      <span>Wishlist</span>
    </a>
    <div id="suresalez-cart-drawer-mount" class="suresalez-dock-item" style="position:relative;">
      <button type="button" class="suresalez-cart-btn" style="display:flex;flex-direction:column;align-items:center;background:none;border:none;color:#0f172a;font-size:10px;font-weight:600;gap:2px;cursor:pointer;padding:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span>Bag</span>
        <span class="suresalez-cart-count-badge" style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;">0</span>
      </button>
    </div>
  </nav>
  <!-- /wp:html -->
</footer>
<!-- /wp:group -->
