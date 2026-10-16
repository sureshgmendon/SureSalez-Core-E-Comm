<?php
/**
 * Title: Footer: Customer Support & RMA Returns Hub
 * Slug: suresalez-theme/footer-06-support-rma-hubs
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Support-focused operations footer integrating the SureSalez Core self-service RMA returns portal mount, 10 inventory hubs directory, and live chat widget concierge.
 */
?>
<!-- wp:group {"tagName":"footer","align":"full","className":"suresalez-footer suresalez-footer-rma-support","layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull suresalez-footer suresalez-footer-rma-support" style="background-color:#0f172a;color:#f8fafc;padding-top:4rem;padding-bottom:2.5rem;border-top:1px solid #1e293b;">
  <!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"2rem","left":"3rem"}}}} -->
  <div class="wp-block-columns alignwide">
    <!-- wp:column {"width":"38%"} -->
    <div class="wp-block-column" style="flex-basis:38%;">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.3rem","fontWeight":"800"}},"color":{"text":"#ffffff"}} -->
      <h3 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:1.3rem;font-weight:800;margin-bottom:1rem;">Self-Service RMA Returns</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9rem","lineHeight":"1.6"}},"color":{"text":"#94a3b8"}} -->
      <p class="has-text-color" style="color:#94a3b8;font-size:0.9rem;line-height:1.6;margin-bottom:1.25rem;">Initiate instant returns with pre-paid barcode return labels. Select store credit for an extra 10% bonus credited to your SureSalez Wallet.</p>
      <!-- /wp:paragraph -->
      <!-- wp:buttons -->
      <div class="wp-block-buttons">
        <!-- wp:button {"backgroundColor":"accent","style":{"typography":{"fontSize":"0.85rem","fontWeight":"700"}}} -->
        <div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="/rma-returns/" style="font-size:0.85rem;font-weight:700;border-radius:6px;padding:8px 18px;">Start RMA Return &rarr;</a></div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"width":"34%"} -->
    <div class="wp-block-column" style="flex-basis:34%;">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.3rem","fontWeight":"800"}},"color":{"text":"#ffffff"}} -->
      <h3 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:1.3rem;font-weight:800;margin-bottom:1rem;">Global Fulfillment Hubs</h3>
      <!-- /wp:heading -->
      <!-- wp:list {"style":{"typography":{"fontSize":"0.85rem"}},"color":{"text":"#94a3b8"}} -->
      <ul class="has-text-color" style="color:#94a3b8;list-style:none;padding:0;margin:0;line-height:2.2;">
        <li>📍 <strong>US Flagship</strong>: Secaucus, NJ (North America)</li>
        <li>📍 <strong>EMEA Center</strong>: Frankfurt, Germany (EU OSS)</li>
        <li>📍 <strong>India Atelier</strong>: Bengaluru, Karnataka (KA-GST)</li>
        <li>📍 <strong>APAC Singapore</strong>: Changi Logistics Hub</li>
      </ul>
      <!-- /wp:list -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"width":"28%"} -->
    <div class="wp-block-column" style="flex-basis:28%;">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.3rem","fontWeight":"800"}},"color":{"text":"#ffffff"}} -->
      <h3 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:1.3rem;font-weight:800;margin-bottom:1rem;">Live Support</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem"}},"color":{"text":"#94a3b8"}} -->
      <p class="has-text-color" style="color:#94a3b8;font-size:0.85rem;margin-bottom:1rem;">Our certified gemologists and customer care specialists are online 24/7.</p>
      <!-- /wp:paragraph -->
      <!-- wp:html -->
      <div id="suresalez-live-chat-mount" class="suresalez-chat-dock-trigger">
        <button type="button" class="suresalez-chat-btn" style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#10b981;color:#fff;border:none;border-radius:6px;font-weight:700;font-size:0.85rem;cursor:pointer;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span>Chat with Concierge</span>
        </button>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</footer>
<!-- /wp:group -->
