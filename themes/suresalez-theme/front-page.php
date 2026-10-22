<?php
/**
 * The front-page template for SureSalez Hybrid Theme
 *
 * Renders the homepage shell directly from disk:
 * - Loads header via get_header()
 * - Loads the split luxury hero via get_template_part('parts/hero/hero-split')
 * - Executes the standard page loop with the_content()
 * - Provides semantic mounts for all SureSalez Core modular features
 * - Loads footer via get_footer()
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
    endif;
    ?>

    <!-- Catalog Overview Section -->
    <section class="suresalez-catalog-section" aria-label="<?php esc_attr_e('Catalog Overview', 'suresalez-theme'); ?>">
      <div class="suresalez-section-header">
        <h2 class="suresalez-section-title"><?php esc_html_e('Catalog Overview', 'suresalez-theme'); ?></h2>
        <p class="suresalez-section-subtitle"><?php esc_html_e('Discover enterprise products powered by SureSalez high-performance catalog architecture.', 'suresalez-theme'); ?></p>
      </div>

      <!-- Shoppable Video Reels Mount -->
      <div id="suresalez-shoppable-reels-mount" class="suresalez-shoppable-reels-mount" role="region" aria-label="<?php esc_attr_e('Shoppable Video Reels', 'suresalez-theme'); ?>"></div>

      <!-- Product Query Grid (Hybrid Loop) -->
      <?php
      $product_args = array(
          'post_type'      => 'product',
          'posts_per_page' => 9,
          'orderby'        => 'date',
          'order'          => 'DESC',
      );
      $product_query = new WP_Query($product_args);

      if ($product_query->have_posts()) :
      ?>
        <div class="suresalez-product-grid">
          <?php
          while ($product_query->have_posts()) :
              $product_query->the_post();
          ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('suresalez-product-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <div class="product-card-thumbnail">
                  <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium_large'); ?>
                  </a>
                </div>
              <?php endif; ?>
              <div class="product-card-body">
                <h3 class="product-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="product-card-excerpt"><?php the_excerpt(); ?></div>
              </div>
            </article>
          <?php
          endwhile;
          wp_reset_postdata();
          ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- SureSalez Core Modular Semantic Mount Points -->
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
