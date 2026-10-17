<?php
/**
 * Title: Hero - Minimal Immersive Banner (Oura Style)
 * Slug: suresalez-theme/hero-minimal-immersive
 * Categories: banner, featured
 * Description: Oura Ring-inspired tech-minimalist immersive hero banner with 70vh minimum height, tight grotesque typography, floating highlight pill row, and friction-free dual pill CTAs.
 */
?>
<!-- wp:cover {"url":"https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1800&q=85","dimRatio":60,"overlayColor":"slate-dark","minHeight":70,"minHeightUnit":"vh","align":"full","className":"suresalez-hero suresalez-hero-minimal-immersive","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}}}} -->
<div class="wp-block-cover alignfull has-slate-dark-background-color has-background-dim-60 has-background-dim suresalez-hero suresalez-hero-minimal-immersive" style="padding-top:6rem;padding-bottom:6rem;min-height:70vh;">
  <span aria-hidden="true" class="wp-block-cover__background has-slate-dark-background-color has-background-dim-60 has-background-dim"></span>
  <img class="wp-block-cover__image-background" alt="Luxury Minimalist Jewellery" src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1800&q=85" data-object-fit="cover"/>
  <div class="wp-block-cover__inner-container">
    <!-- wp:group {"layout":{"type":"constrained","wideSize":"1100px","justifyContent":"center"},"style":{"spacing":{"blockGap":"1.5rem"}},"className":"suresalez-hero-content"} -->
    <div class="wp-block-group suresalez-hero-content" style="text-align:center;">
      <!-- wp:group {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"className":"suresalez-pill-highlights"} -->
      <div class="wp-block-group suresalez-pill-highlights" style="display:inline-flex;gap:8px;background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);padding:6px 16px;border-radius:9999px;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;color:#ffffff;text-transform:uppercase;margin:0 auto 1rem;">
        <span>Handcrafted Finish</span>
        <span>&bull;</span>
        <span>Hypoallergenic PVD</span>
        <span>&bull;</span>
        <span>Express Global Shipping</span>
      </div>
      <!-- /wp:group -->

      <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"var(--wp--preset--font-size--hero-display)","fontWeight":"800","lineHeight":"1.05","letterSpacing":"-0.04em"},"color":{"text":"#ffffff"}}} -->
      <h1 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:var(--wp--preset--font-size--hero-display);font-weight:800;line-height:1.05;letter-spacing:-0.04em;margin:0 auto;max-width:900px;">Precision Crafted. Everyday Wear.</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"1.2rem","lineHeight":"1.6","fontWeight":"400"},"color":{"text":"#e2e8f0"}}} -->
      <p class="has-text-color" style="color:#e2e8f0;font-size:1.2rem;line-height:1.6;font-weight:400;margin:0 auto 1.5rem;max-width:680px;">Discover the next evolution of jewelry: bio-compatible aerospace titanium finished with 24K pure gold vapor deposition.</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"1rem"}}} -->
      <div class="wp-block-buttons" style="justify-content:center;">
        <!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#0a0f1d"},"border":{"radius":"9999px"},"typography":{"fontSize":"0.95rem","fontWeight":"700","letterSpacing":"0.01em"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.25rem","right":"2.25rem"}}}} -->
        <div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="/shop/" style="border-radius:9999px;background-color:#ffffff;color:#0a0f1d;font-size:0.95rem;font-weight:700;letter-spacing:0.01em;padding-top:1rem;padding-bottom:1rem;padding-left:2.25rem;padding-right:2.25rem;">Shop All Designs &rarr;</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline","style":{"color":{"text":"#ffffff"},"border":{"radius":"9999px","color":"rgba(255,255,255,0.4)","width":"1px"},"typography":{"fontSize":"0.95rem","fontWeight":"600"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2rem","right":"2rem"}}}} -->
        <div class="wp-block-button has-custom-font-size is-style-outline"><a class="wp-block-button__link has-text-color wp-element-button" href="/about/" style="border-radius:9999px;border-width:1px;border-color:rgba(255,255,255,0.4);color:#ffffff;font-size:0.95rem;font-weight:600;padding-top:1rem;padding-bottom:1rem;padding-left:2rem;padding-right:2rem;">The Technology</a></div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
  </div>
</div>
<!-- /wp:cover -->
