# Kadence WooCommerce & Shop Kit Architecture
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Target Systems:** Kadence WooCommerce Engine (`inc/components/woocommerce/component.php`, `woocommerce.min.css`, Kadence Shop Kit features)

---

## 1. Product Catalog Grid Architecture

Kadence replaces the standard WooCommerce loop markup with a responsive, modern CSS Grid card structure.

```
┌────────────────────────────────────────────────────────────────────────┐
│               ARCHIVE TOOLBAR (.kadence-shop-top-bar)                  │
│  [Results: Showing 1-12 of 36 results]     [Sort By: Price low-high ▼] │
├────────────────────────────────────────────────────────────────────────┤
│                       PRODUCT CATALOG GRID                             │
│  ┌───────────────────┐ ┌───────────────────┐ ┌───────────────────┐     │
│  │ [SALE]   [Quick]  │ │                   │ │ [OUT OF STOCK]    │     │
│  │   Product Image   │ │   Product Image   │ │   Product Image   │     │
│  │   (Hover Swap)    │ │   (Hover Swap)    │ │                   │     │
│  ├───────────────────┤ ├───────────────────┤ ├───────────────────┤     │
│  │ Apparel           │ │ Footwear          │ │ Accessories       │     │
│  │ Organic Cotton Tee│ │ Trail Runner Pro  │ │ Leather Tote      │     │
│  │ ★★★★★ (18)        │ │ ★★★★☆ (9)         │ │ ★★★★★ (42)        │     │
│  │ $32.00 $45.00     │ │ $119.00           │ │ $89.00            │     │
│  │ [● Black] [● Blue]│ │ [8] [9] [10] [11] │ │ [One Size]        │     │
│  │ [Add to Cart]     │ │ [Select Options]  │ │ [Read More]       │     │
│  └───────────────────┘ └───────────────────┘ └───────────────────┘     │
└────────────────────────────────────────────────────────────────────────┘
```

### Grid Column Rules & Responsive Layout
The product archive loop (`ul.products`) adopts CSS Grid with configurable column breakpoints:
```css
/* Responsive WooCommerce Product Grid */
.woocommerce ul.products {
  display: grid;
  grid-template-columns: repeat(var(--woo-columns-desktop, 4), minmax(0, 1fr));
  gap: var(--woo-grid-gap, 2rem);
  list-style: none;
  padding: 0;
  margin: 0;
}

@media screen and (max-width: 1024px) {
  .woocommerce ul.products {
    grid-template-columns: repeat(var(--woo-columns-tablet, 3), minmax(0, 1fr));
    gap: 1.5rem;
  }
}

@media screen and (max-width: 719px) {
  .woocommerce ul.products {
    grid-template-columns: repeat(var(--woo-columns-mobile, 2), minmax(0, 1fr));
    gap: 1rem;
  }
}
```

---

## 2. Product Loop Card Anatomy

Each card within `li.product` contains two discrete structural layers:
1. **Media Area (`.product-thumbnail-wrap`)**:
   - Primary product image.
   - Secondary hover image (`.secondary-image`) with smooth cross-fade opacity.
   - Dynamic badges (`.onsale`, `.out-of-stock`, `.badge-custom`).
   - Quick View trigger overlay (`.kadence-quick-view-btn`).
2. **Details Card (`.product-details.content-bg.entry-content-wrap`)**:
   - Product taxonomy / category (`.product-category`).
   - Title link (`.woocommerce-loop-product__title`).
   - Star rating (`.star-rating`).
   - Price block with regular and discounted prices (`.price`).
   - Interactive variation swatches (`.kadence-swatches-wrap`).
   - Add-to-cart button wrap (`.product-action-wrap`).

---

## 3. Kadence Shop Kit Variation Swatches

Standard WooCommerce dropdowns (`<select>`) are replaced with visual, accessible swatch components:

```html
<div class="kadence-swatches-wrap" data-attribute_name="attribute_pa_color">
  <!-- Color Swatch -->
  <button 
    type="button" 
    class="kadence-swatch kadence-swatch-color is-selected" 
    data-value="navy" 
    aria-label="Color: Navy"
    style="--swatch-color: #1a365d;">
    <span class="kadence-swatch-tooltip">Navy</span>
  </button>

  <button 
    type="button" 
    class="kadence-swatch kadence-swatch-color" 
    data-value="olive" 
    aria-label="Color: Olive"
    style="--swatch-color: #556b2f;">
    <span class="kadence-swatch-tooltip">Olive</span>
  </button>

  <!-- Button / Pill Swatch -->
  <button 
    type="button" 
    class="kadence-swatch kadence-swatch-button" 
    data-value="large" 
    aria-label="Size: Large">
    Large
  </button>
</div>
```

```css
/* Swatch Styling */
.kadence-swatch {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid transparent;
  outline: 1px solid var(--global-palette6);
  outline-offset: 2px;
  background-color: var(--swatch-color);
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.kadence-swatch:hover,
.kadence-swatch.is-selected {
  outline-color: var(--global-palette1);
  outline-width: 2px;
  transform: scale(1.1);
}

.kadence-swatch.kadence-swatch-button {
  width: auto;
  height: auto;
  padding: 0.35rem 0.75rem;
  border-radius: var(--global-border-radius);
  font-size: 0.85rem;
  background: var(--global-palette7);
  color: var(--global-palette3);
  outline: none;
  border: 1px solid var(--global-palette6);
}

.kadence-swatch.kadence-swatch-button.is-selected {
  background: var(--global-palette1);
  color: #ffffff;
  border-color: var(--global-palette1);
}
```

---

## 4. Product Badges System

Kadence supports flexible badge shapes, placements, and styles:
- **Badge Types**:
  - `Sale`: Percentage discount (`-25%`) or text (`SALE`).
  - `Out of Stock`: Low stock alert or sold out banner.
  - `New / Featured`: Product launch tag.
- **Badge Shapes**: Round, rectangular, pill, or ribbon banner.
- **Positioning**: Top-left, top-right, or over image center.

```css
.woocommerce span.onsale {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 9;
  background: var(--global-palette13, #b82105);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
```

---

## 5. Single Product Page Architecture

On the single product template, Kadence divides content into a 2-column layout:
1. **Left Column (`.woocommerce-product-gallery`)**:
   - Integrated Splide / Flexslider carousel.
   - Vertical or horizontal thumbnail pagination.
   - Lightbox modal trigger + cursor zoom.
   - Video badge and playable embedded product video.
2. **Right Column (`.summary.entry-summary`)**:
   - Breadcrumbs (`kadence()->breadcrumbs()`).
   - Product title (`h1.product_title`).
   - Star reviews with jump link to reviews tab.
   - Price display.
   - Short description / excerpt.
   - Variation selection swatches.
   - Quantity selector with custom `+` / `-` stepper buttons.
   - Primary `Add to Cart` CTA + Direct `Buy Now` button.
   - SKU, categories, and tags meta.
3. **Sticky Add-to-Cart Bar**:
   - Triggers when the user scrolls past the primary Add to Cart button.
   - Anchors to bottom or top of viewport on mobile and desktop.
   - Displays miniature product image, title, price, and instant checkout button.
