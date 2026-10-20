<?php
/**
 * Title: Hero - Video and Image Split Luxury Showcase
 * Slug: suresalez-theme/hero-video-image-split
 * Categories: banner, featured
 * Description: Luxury two-column split layout featuring an autoplaying background video in Column 1 and a high-resolution editorial lifestyle image with interactive CTA card in Column 2.
 */
?>
<!-- wp:group {"align":"full","className":"suresalez-hero-wrapper suresalez-hero-video-image","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull suresalez-hero-wrapper suresalez-hero-video-image">
  <!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"2rem","left":"2rem"}}}} -->
  <div class="wp-block-columns alignwide are-vertically-aligned-center">
    <!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
      <!-- wp:html -->
      <div class="hero-media-box hero-video-box">
        <video autoplay loop muted playsinline poster="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=900&q=85">
          <source src="https://assets.mixkit.co/videos/preview/mixkit-set-of-plateaus-with-gold-jewelry-41220-large.mp4" type="video/mp4">
        </video>
        <div class="hero-media-overlay-badge">
          <span class="badge-dot"></span>
          <span>Atelier Motion &bull; Handcrafted 24K Gold</span>
        </div>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
      <!-- wp:html -->
      <div class="hero-media-box hero-image-box">
        <img src="https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=1000&q=85" alt="Editorial Jewellery Showcase" />
        <div class="hero-cta-card">
          <div class="hero-cta-eyebrow">Autumn / Winter Atelier Drop</div>
          <h2 class="hero-cta-title">Verve Cuban &bull; 24K Titanium</h2>
          <p class="hero-cta-desc">Engineered for tactile weight, skin neutrality, and perpetual luster. Zero markups.</p>
          <div class="hero-cta-action">
            <span class="hero-cta-price">$68.00 / ₹5,600</span>
            <a href="/shop/" class="hero-cta-btn">Explore Collection &rarr;</a>
          </div>
        </div>
      </div>
      <!-- /wp:html -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
