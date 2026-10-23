# Kadence Engineering Implementation Guide & Developer Blueprint
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Deliverable:** Complete implementation blueprint for cloning Kadence's design, layout, and UX standards

---

## 1. Architectural Boundaries & Implementation Rules

To successfully implement a Kadence-grade commerce presentation layer, adhere to these architectural invariants:

1. **Pure Presentation Independence**:
   - Zero hardcoded business logic or SQL queries in layout templates.
   - All presentation styles consume CSS custom properties defined in the `:root` tokens file.
2. **Modular CSS Partitioning**:
   - Split stylesheets into focused chunks: `tokens.css` (tokens), `kadence-layout.css` (structural grid/containers), and `kadence-components.css` (UI widgets, cards, swatches, drawers).
3. **Vanilla JavaScript Runtime**:
   - Zero jQuery dependencies for core interactivity.
   - All drawer, modal, and sticky controllers must operate via native DOM APIs (`addEventListener`, `classList`, `setAttribute`).
4. **Accessibility First (WCAG 2.1 AA)**:
   - Every toggle must maintain `aria-expanded` and `aria-controls`.
   - Modals and drawers must lock body scroll and trap focus between `firstFocusable` and `lastFocusable` elements.
   - The Escape key (`keyCode 27`) must immediately close all active overlays.

---

## 2. Step-by-Step Implementation Sequence

```
┌────────────────────────────────────────────────────────────────────────┐
│                        IMPLEMENTATION WORKFLOW                         │
├────────────────────────────────────────────────────────────────────────┤
│  Step 1: Core Design System Tokens (tokens.css)                        │
│          Declare 15-color palette, typography scale, spacing, containers│
├────────────────────────────────────────────────────────────────────────┤
│  Step 2: Structural Layout Engine (kadence-layout.css)                 │
│          Multi-row header grid, footer column layouts, breakpoints     │
├────────────────────────────────────────────────────────────────────────┤
│  Step 3: Interactive Components (kadence-components.css)               │
│          Product cards, swatches, badges, buttons, drawers, modals     │
├────────────────────────────────────────────────────────────────────────┤
│  Step 4: Zero-Dependency JavaScript Controller (kadence-runtime.js)    │
│          Drawer state machine, scrollbar compensation, sticky scroll   │
├────────────────────────────────────────────────────────────────────────┤
│  Step 5: Master Frontend Showcase Template (index.html)                │
│          Integrate complete masthead, catalog, drawers, and footer     │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Implementing the Header & Navigation

### HTML Structural Tree
```html
<header id="masthead" class="site-header">
  <div id="main-header" class="site-header-wrap">
    <div class="site-header-inner-wrap kadence-sticky-header" data-shrink="true" data-shrink-height="64">
      
      <!-- Top Row -->
      <div class="site-top-header-wrap site-header-row-layout-contained">
        <div class="site-container">
          <div class="site-header-row">
            <div class="site-header-section site-header-section-left">
              <span class="header-html">⚡ Free express shipping on orders over $150</span>
            </div>
            <div class="site-header-section site-header-section-right">
              <a href="#currency" class="header-link">USD ($)</a>
              <a href="#account" class="header-link">Account</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Row -->
      <div class="site-main-header-wrap site-header-row-layout-contained">
        <div class="site-container">
          <div class="site-header-row site-header-row-center-column">
            <!-- Left: Logo -->
            <div class="site-header-section site-header-section-left">
              <a href="/" class="brand-logo">Kadence<span>Store</span></a>
            </div>
            <!-- Center: Navigation -->
            <div class="site-header-section site-header-section-center">
              <nav class="header-navigation nav--toggle-sub" role="navigation">
                <ul class="menu">
                  <li class="menu-item current-menu-item"><a href="/">Home</a></li>
                  <li class="menu-item menu-item-has-children">
                    <a href="#shop">Shop</a>
                    <button class="dropdown-nav-special-toggle" aria-expanded="false" aria-label="Toggle Submenu">
                      <svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                    </button>
                    <ul class="sub-menu">
                      <li><a href="#apparel">Apparel & Footwear</a></li>
                      <li><a href="#electronics">Electronics & Audio</a></li>
                      <li><a href="#home">Home & Living</a></li>
                    </ul>
                  </li>
                  <li class="menu-item"><a href="#collections">Collections</a></li>
                  <li class="menu-item"><a href="#sale">Sale</a></li>
                </ul>
              </nav>
            </div>
            <!-- Right: Actions -->
            <div class="site-header-section site-header-section-right">
              <button class="search-toggle-open drawer-toggle" data-toggle-target="#search-drawer" aria-label="Search">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
              </button>
              <button class="header-cart-button drawer-toggle" data-toggle-target="#cart-drawer" data-toggle-body-class="showing-popup-drawer-from-right" aria-label="Cart">
                <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                <span class="header-cart-total">3</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</header>
```

---

## 4. Implementing the WooCommerce Product Grid

Ensure that each card in `ul.products` conforms to the 2-stage visual card hierarchy:

```html
<ul class="products columns-4">
  <li class="product type-product status-publish has-post-thumbnail">
    <div class="product-thumbnail-wrap">
      <span class="onsale">-25%</span>
      <a href="#product" class="woocommerce-LoopProduct-link">
        <img src="product-primary.webp" class="primary-image" alt="Product Name" loading="lazy" />
        <img src="product-secondary.webp" class="secondary-image" alt="Product Hover" loading="lazy" />
      </a>
      <button class="kadence-quick-view-btn" data-product-id="101" aria-label="Quick View">Quick View</button>
    </div>
    
    <div class="product-details content-bg entry-content-wrap">
      <div class="product-category">Apparel</div>
      <h3 class="woocommerce-loop-product__title"><a href="#product">Merino Wool Zip-Up</a></h3>
      <div class="star-rating" role="img" aria-label="Rated 5 out of 5">★★★★★</div>
      <span class="price"><del>$120.00</del> <ins>$89.00</ins></span>
      
      <!-- Swatches -->
      <div class="kadence-swatches-wrap">
        <button class="kadence-swatch is-selected" style="--swatch-color: #2b3a4a;" aria-label="Navy"></button>
        <button class="kadence-swatch" style="--swatch-color: #556b2f;" aria-label="Olive"></button>
        <button class="kadence-swatch" style="--swatch-color: #222222;" aria-label="Black"></button>
      </div>

      <div class="product-action-wrap">
        <button class="button add_to_cart_button ajax_add_to_cart">Add to Cart</button>
      </div>
    </div>
  </li>
</ul>
```

---

## 5. JavaScript Runtime Integration

Include `kadence-runtime.js` at the bottom of the document:
- The script automatically binds event listeners to `.drawer-toggle` buttons.
- Calculates dynamic scrollbar offsets to eliminate jitter.
- Toggles `active` and `show-drawer` classes with smooth cubic-bezier easing.
- Listens for global `keyup` on ESC to gracefully close drawers.
- Initializes IntersectionObserver to activate sticky masthead styles when scrolling down.
