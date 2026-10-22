<?php
/**
 * Main Footer Template Part (Hybrid Theme)
 *
 * Provides a clean luxury editorial footer:
 * - Brand narrative and newsletter signup
 * - Categorical navigation links
 * - Live Chat mount (#suresalez-live-chat-mount)
 * - Legal copyright & payment badges
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<footer id="colophon" class="suresalez-site-footer" role="contentinfo">
  <div class="suresalez-footer-container">
    <div class="suresalez-footer-grid">
      <!-- Col 1: Brand & Philosophy -->
      <div class="suresalez-footer-col suresalez-footer-brand-col">
        <h3 class="suresalez-footer-title"><?php bloginfo('name'); ?></h3>
        <p class="suresalez-footer-text">
          <?php esc_html_e('Architectural jewelry engineered with aerospace-grade titanium and 24K vacuum vapor deposition. Direct to consumer from 10 global fulfillment hubs.', 'suresalez-theme'); ?>
        </p>
        <div class="suresalez-footer-guarantee">
          <span>🛡️ <?php esc_html_e('Lifetime Tarnish-Proof Guarantee', 'suresalez-theme'); ?></span>
        </div>
      </div>

      <!-- Col 2: Collections -->
      <div class="suresalez-footer-col">
        <h4 class="suresalez-footer-heading"><?php esc_html_e('Collections', 'suresalez-theme'); ?></h4>
        <ul class="suresalez-footer-links">
          <li><a href="<?php echo esc_url(home_url('/shop/?category=chains')); ?>"><?php esc_html_e('Cuban & Rope Chains', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/shop/?category=earrings')); ?>"><?php esc_html_e('Kundan & Diamond Studs', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/shop/?category=bracelets')); ?>"><?php esc_html_e('Tennis Bracelets & Kadas', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/shop/?category=pendants')); ?>"><?php esc_html_e('Solitaire Pendants', 'suresalez-theme'); ?></a></li>
        </ul>
      </div>

      <!-- Col 3: Customer Care & Services -->
      <div class="suresalez-footer-col">
        <h4 class="suresalez-footer-heading"><?php esc_html_e('Customer Care', 'suresalez-theme'); ?></h4>
        <ul class="suresalez-footer-links">
          <li><a href="<?php echo esc_url(home_url('/tracking/')); ?>"><?php esc_html_e('Track Global Shipment', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/returns/')); ?>"><?php esc_html_e('30-Day RMA Returns', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/wholesale/')); ?>"><?php esc_html_e('B2B Wholesale Portal', 'suresalez-theme'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Concierge Support', 'suresalez-theme'); ?></a></li>
        </ul>
      </div>

      <!-- Col 4: Newsletter & Live Chat Mount -->
      <div class="suresalez-footer-col">
        <h4 class="suresalez-footer-heading"><?php esc_html_e('The Atelier Dispatch', 'suresalez-theme'); ?></h4>
        <p class="suresalez-footer-subtext"><?php esc_html_e('Receive private invitations to limited alloy drops and VIP rewards.', 'suresalez-theme'); ?></p>
        <form class="suresalez-newsletter-form" onsubmit="event.preventDefault(); alert('Subscribed to VIP Atelier Dispatch.');">
          <input type="email" class="suresalez-newsletter-input" placeholder="<?php esc_attr_e('Enter your email...', 'suresalez-theme'); ?>" required />
          <button type="submit" class="suresalez-newsletter-btn"><?php esc_html_e('Join', 'suresalez-theme'); ?></button>
        </form>

        <!-- Live Chat Widget Semantic Mount -->
        <div id="suresalez-live-chat-mount" class="suresalez-chat-mount" role="region" aria-label="<?php esc_attr_e('Live Chat Assistant', 'suresalez-theme'); ?>"></div>
      </div>
    </div>

    <!-- Bottom Bar: Copyright & Compliance -->
    <div class="suresalez-footer-bottom">
      <div class="suresalez-copyright">
        &copy; <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved. Powered by SureSalez Enterprise Commerce.', 'suresalez-theme'); ?>
      </div>
      <div class="suresalez-footer-badges">
        <span class="badge-tag">GST HSN 7117</span>
        <span class="badge-tag">AES-256 PCI-DSS</span>
        <span class="badge-tag">10 Global Hubs</span>
      </div>
    </div>
  </div>
</footer>
