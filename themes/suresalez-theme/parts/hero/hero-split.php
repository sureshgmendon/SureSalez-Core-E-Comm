<?php
/**
 * Hero - Editorial Split Fashion Showcase (Hybrid Theme Part)
 *
 * Implements an asymmetric editorial split layout:
 * - Desktop: 55% Left Column / 45% Right Column
 * - Mobile: Stacked, full-width with bounded aspect ratio (4/5 mobile, 16/9 desktop)
 * - Autoplaying background video or high-res visual showcase
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="suresalez-hero-split-section" aria-label="<?php esc_attr_e('Hero Showcase', 'suresalez-theme'); ?>">
  <div class="suresalez-hero-split-container">
    <!-- Left Column: Typography & CTAs (55% on desktop) -->
    <div class="hero-split-col-left">
      <div class="hero-split-eyebrow">
        <?php esc_html_e('Autumn / Winter 2026 Atelier Drop • Limited Run', 'suresalez-theme'); ?>
      </div>

      <h1 class="hero-split-title">
        <?php esc_html_e('The Geometry of Everyday Luxury.', 'suresalez-theme'); ?>
      </h1>

      <p class="hero-split-narrative">
        <?php esc_html_e('Fine imitation jewellery engineered with 24K vacuum-bonded titanium and hand-cut Austrian crystals. Exceptional tactile weight, zero skin irritation, and no traditional retail markups.', 'suresalez-theme'); ?>
      </p>

      <div class="hero-split-actions">
        <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="suresalez-pill-btn suresalez-pill-primary">
          <?php esc_html_e('Explore Collection →', 'suresalez-theme'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/about/')); ?>" class="suresalez-pill-btn suresalez-pill-outline">
          <?php esc_html_e('Discover The Story', 'suresalez-theme'); ?>
        </a>
      </div>

      <div class="hero-split-meta">
        <span class="meta-item">✨ <?php esc_html_e('Hypoallergenic Grade 5 Titanium', 'suresalez-theme'); ?></span>
        <span class="meta-item">🛡️ <?php esc_html_e('Lifetime Anti-Tarnish Guarantee', 'suresalez-theme'); ?></span>
        <span class="meta-item">📦 <?php esc_html_e('Dispatched from 10 Global Hubs', 'suresalez-theme'); ?></span>
      </div>
    </div>

    <!-- Right Column: Media Box (45% on desktop, bounded aspect ratio) -->
    <div class="hero-split-col-right">
      <div class="hero-split-media-box">
        <video autoplay loop muted playsinline poster="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=900&q=85">
          <source src="https://assets.mixkit.co/videos/preview/mixkit-set-of-plateaus-with-gold-jewelry-41220-large.mp4" type="video/mp4">
        </video>
        <div class="hero-split-overlay-badge">
          <span class="badge-title"><?php esc_html_e('Featured: Royal Kundan Cuban Chain in 24K Gold', 'suresalez-theme'); ?></span>
          <span class="badge-price"><?php esc_html_e('$45.00 / ₹3,750', 'suresalez-theme'); ?></span>
        </div>
      </div>
    </div>
  </div>
</section>
