<?php
/**
 * Title: Footer: Dark Enterprise Grid
 * Slug: suresalez-theme/footer-09-dark-enterprise-grid
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Enterprise dark grid footer featuring an embedded multi-currency switcher mount, list of all 10 international fulfillment hubs, and SOC2 / ISO 27001 data compliance badges.
 */
?>
<!-- wp:group {"tagName":"footer","align":"full","className":"suresalez-footer suresalez-footer-enterprise-grid","layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull suresalez-footer suresalez-footer-enterprise-grid" style="background-color:#0f172a;color:#cbd5e1;padding-top:4rem;padding-bottom:2.5rem;border-top:1px solid #1e293b;">
  <!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"2.5rem","left":"3rem"}}}} -->
  <div class="wp-block-columns alignwide">
    <!-- wp:column {"width":"32%"} -->
    <div class="wp-block-column" style="flex-basis:32%;">
      <!-- wp:site-title {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"800"}},"color":{"text":"#ffffff"}} /-->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.88rem","lineHeight":"1.6"}},"color":{"text":"#94a3b8"}} -->
      <p class="has-text-color" style="color:#94a3b8;font-size:0.88rem;line-height:1.6;margin-top:12px;margin-bottom:1.5rem;">Enterprise-grade retail architecture processing multi-currency checkouts across 10 distributed fulfillment centers with automated DDP tax calculation.</p>
      <!-- /wp:paragraph -->

      <!-- wp:html -->
      <div id="suresalez-currency-switcher-mount" class="suresalez-currency-wrap" style="margin-bottom:1rem;">
        <label for="suresalez-footer-currency" style="display:block;font-size:0.75rem;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;">Store Currency</label>
        <select id="suresalez-footer-currency" style="background:#1e293b;color:#f8fafc;border:1px solid #334155;border-radius:6px;padding:6px 12px;font-size:0.85rem;width:200px;">
          <option value="USD">USD ($) United States</option>
          <option value="INR" selected>INR (₹) India (KA-GST)</option>
          <option value="EUR">EUR (€) European Union</option>
          <option value="GBP">GBP (£) United Kingdom</option>
          <option value="AED">AED (د.إ) UAE Dirham</option>
        </select>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"width":"40%"} -->
    <div class="wp-block-column" style="flex-basis:40%;">
      <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"0.95rem","fontWeight":"700","textTransform":"uppercase"}},"color":{"text":"#ffffff"}} -->
      <h4 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:0.95rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;">10 Global Inventory Hubs</h4>
      <!-- /wp:heading -->
      <!-- wp:columns -->
      <div class="wp-block-columns" style="font-size:0.82rem;color:#94a3b8;line-height:2;">
        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:list {"style":{"typography":{"fontSize":"0.82rem"}}} -->
          <ul style="list-style:none;padding:0;margin:0;">
            <li>US-EAST: New Jersey Hub</li>
            <li>US-WEST: Los Angeles Hub</li>
            <li>EU-CENTRAL: Frankfurt DC</li>
            <li>UK-SOUTH: London Gateway</li>
            <li>IN-SOUTH: Bengaluru Atelier</li>
          </ul>
          <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:list {"style":{"typography":{"fontSize":"0.82rem"}}} -->
          <ul style="list-style:none;padding:0;margin:0;">
            <li>IN-WEST: Mumbai Terminal</li>
            <li>APAC-SG: Singapore Changi</li>
            <li>ME-DXB: Dubai Logistics City</li>
            <li>AU-SYD: Sydney Port Botany</li>
            <li>CA-CENTRAL: Toronto Hub</li>
          </ul>
          <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"width":"28%"} -->
    <div class="wp-block-column" style="flex-basis:28%;">
      <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"0.95rem","fontWeight":"700","textTransform":"uppercase"}},"color":{"text":"#ffffff"}} -->
      <h4 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:0.95rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;">Security & Compliance</h4>
      <!-- /wp:heading -->
      <!-- wp:list {"style":{"typography":{"fontSize":"0.85rem"}},"color":{"text":"#94a3b8"}} -->
      <ul class="has-text-color" style="color:#94a3b8;list-style:none;padding:0;margin:0;line-height:2.2;">
        <li>🔒 PCI-DSS Level 1 Payment Gateway</li>
        <li>🛡️ ISO 27001 Certified Infrastructure</li>
        <li>📜 GDPR & DPDP Right-to-be-Forgotten</li>
        <li>⚡ 99.99% Edge Cache Uptime SLA</li>
      </ul>
      <!-- /wp:list -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->

  <!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"2.5rem"},"padding":{"top":"1.5rem"}},"border":{"top":{"color":"#1e293b","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between"}} -->
  <div class="wp-block-group alignwide" style="border-top:1px solid #1e293b;margin-top:2.5rem;padding-top:1.5rem;font-size:0.8rem;color:#64748b;">
    <!-- wp:paragraph -->
    <p style="margin:0;">&copy; 2026 SureSalez Enterprise Scale Architecture. All Rights Reserved.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph -->
    <p style="margin:0;">Decoupled Core Engine v1.9.0 &bull; Pure Presentation Theme</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</footer>
<!-- /wp:group -->
