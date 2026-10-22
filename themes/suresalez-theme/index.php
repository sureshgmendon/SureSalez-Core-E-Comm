<?php
/**
 * The main template file for SureSalez Hybrid Theme
 *
 * Used to display a page when nothing more specific matches a query.
 * Calls standard WordPress hooks and mounts the decoupled e-commerce features.
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<!-- Luxury Editorial Split Hero Part -->
<?php get_template_part('parts/hero/hero-split'); ?>

<main id="primary" class="site-main suresalez-main-content">
  <div class="suresalez-content-container">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
    else :
    ?>
      <div class="suresalez-empty-state">
        <h2 class="suresalez-section-title"><?php esc_html_e('Catalog Overview', 'suresalez-theme'); ?></h2>
        <p class="suresalez-section-subtitle"><?php esc_html_e('Discover enterprise products powered by SureSalez high-performance catalog architecture.', 'suresalez-theme'); ?></p>
      </div>
    <?php endif; ?>

    <!-- SureSalez Core Modular Semantic Mount Points -->
    <div id="suresalez-shoppable-reels-mount" class="suresalez-shoppable-reels-mount" role="region" aria-label="<?php esc_attr_e('Shoppable Video Reels', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-order-tracking-mount" class="suresalez-order-tracking-mount" role="region" aria-label="<?php esc_attr_e('Order Tracking & Split Fulfillment', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-rma-portal-mount" class="suresalez-rma-portal-mount" role="region" aria-label="<?php esc_attr_e('Self-Service RMA Returns Portal', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-b2b-bulk-order-mount" class="suresalez-b2b-bulk-order-mount" role="region" aria-label="<?php esc_attr_e('Wholesale Quick Bulk Order Matrix', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-rfq-form-mount" class="suresalez-rfq-form-mount" role="region" aria-label="<?php esc_attr_e('Request For Quote Negotiation Portal', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-pos-screen-mount" class="suresalez-pos-screen-mount" role="region" aria-label="<?php esc_attr_e('In-Store POS Touch Terminal', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-vendor-portal-mount" class="suresalez-vendor-portal-mount" role="region" aria-label="<?php esc_attr_e('Multi-Vendor Marketplace Dashboard', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-subscriptions-mount" class="suresalez-subscriptions-mount" role="region" aria-label="<?php esc_attr_e('Subscribe & Save Management Portal', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-loyalty-rewards-mount" class="suresalez-loyalty-rewards-mount" role="region" aria-label="<?php esc_attr_e('VIP Loyalty Rewards & Tier Meter', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-referral-banner-mount" class="suresalez-referral-banner-mount" role="region" aria-label="<?php esc_attr_e('Dual-Sided Referral Sharing Banner', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-organization-switcher-mount" class="suresalez-organization-switcher-mount" role="region" aria-label="<?php esc_attr_e('Multi-Store Organization Switcher', 'suresalez-theme'); ?>"></div>
    <div id="suresalez-tenant-billing-mount" class="suresalez-tenant-billing-mount" role="region" aria-label="<?php esc_attr_e('Reseller Tenant Billing & Usage Meter', 'suresalez-theme'); ?>"></div>
  </div>
</main>

<?php
get_footer();
