# Kadence Hooked Elements & Customizer Architecture
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Target Systems:** Kadence Elements Framework (`inc/customizer/class-theme-customizer.php`, `inc/customizer/options/`, `custom-layout-options.php`)

---

## 1. Kadence Hooked Elements (Custom Layouts) System

Kadence Elements is a visual hook management engine allowing developers and merchants to inject arbitrary Gutenberg blocks, HTML snippets, or PHP code into any theme hook location without editing template files.

```
┌────────────────────────────────────────────────────────────────────────┐
│                        KADENCE HOOKED ELEMENTS ENGINE                  │
├────────────────────────────────────────────────────────────────────────┤
│  1. Element Placement Types                                            │
│     ├── Hook (Standard DOM injection at any theme hook)                │
│     ├── Content Section (Above/Below Hero, Archive Loop)               │
│     ├── Fixed / Sticky Bar (Top banner, Bottom announcement)           │
│     ├── Modal / Pop-up Trigger (Timed, Exit-intent, Scroll-depth)      │
│     └── Full Template Replacement (Single Post, Product, Archive)      │
├────────────────────────────────────────────────────────────────────────┤
│  2. Targeting & Conditional Display Rules                              │
│     ├── Include / Exclude rules with logical AND/OR evaluations        │
│     ├── Content Types: All Posts, Singular Pages, Woo Products, RMA   │
│     ├── Taxonomies: Categories, Product Tags, Custom Terms             │
│     ├── User Role: All, Logged-in, Logged-out, Admin, B2B Wholesaler   │
│     ├── Device Targeting: Desktop only, Tablet only, Mobile only       │
│     └── Expiration: Date & time scheduling with auto-expire            │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Complete Theme Hook Matrix

Below is the verified master registry of theme action hooks supported by Kadence:

| Lifecycle Phase | Hook Name | Placement Description |
|---|---|---|
| **Document Start** | `wp_head` | Head metadata, analytics, critical CSS tokens |
| **Pre-Header** | `kadence_before_header` | Pre-masthead announcement strips, top notices |
| **Header Masthead**| `kadence_header` | Full header container wrapper |
| **Top Header Row** | `kadence_top_header` | Row 1 (utility notices, secondary links) |
| **Main Header Row**| `kadence_main_header` | Row 2 (logo branding, primary navigation) |
| **Bottom Header**  | `kadence_bottom_header` | Row 3 (categories, mega menus) |
| **Post-Header** | `kadence_after_header` | Hero banners, breadcrumb strips |
| **Content Hero** | `kadence_entry_hero` | Singular title & featured image hero container |
| **Archive Hero** | `kadence_entry_archive_hero` | Archive title, description, category banners |
| **Pre-Content** | `kadence_before_main_content` | Container entry, promotional callouts |
| **Loop Start** | `kadence_loop_entry_content` | Inside post/product card loop |
| **Post-Content** | `kadence_after_main_content` | Author box, related posts/products |
| **Pre-Sidebar** | `kadence_before_sidebar` | Top of widget sidebar |
| **Post-Sidebar** | `kadence_after_sidebar` | Bottom of widget sidebar |
| **Pre-Footer** | `kadence_before_footer` | Newsletter opt-in, trust badges |
| **Footer Masthead**| `kadence_footer` | Full footer container wrapper |
| **Top Footer Row** | `kadence_top_footer` | Footer Row 1 |
| **Mid Footer Row** | `kadence_middle_footer` | Footer Row 2 |
| **Bot Footer Row** | `kadence_bottom_footer` | Footer Row 3 (copyright, footer navigation) |
| **Post-Footer** | `kadence_after_footer` | Legal disclosures |
| **Document End** | `wp_footer` | Modals, drawers, sticky bars, tracking scripts |

### WooCommerce Specialized Hook Attachments
- `woocommerce_before_shop_loop`: Top of product archive, ordering & filter bar.
- `woocommerce_before_single_product`: Above single product hero & breadcrumbs.
- `woocommerce_before_single_product_summary`: Product gallery container.
- `woocommerce_single_product_summary`: Product title, price, swatches, add-to-cart.
- `woocommerce_after_single_product_summary`: Tabs, upsells, related products.
- `kadence-before-side-cart`: Inside `#cart-drawer` before mini-cart item list.
- `kadence-after-side-cart`: Inside `#cart-drawer` after checkout button.

---

## 3. Customizer Settings Schema & PostMessage Transport

Kadence achieves instantaneous live customizer previewing using an internal AST-style CSS builder (`Kadence_CSS`) combined with declarative `live_method` bindings:

```php
// Customizer Setting Declaration Example
'product_archive_columns' => array(
    'control_type' => 'kadence_range_control',
    'section'      => 'product_archive',
    'label'        => esc_html__( 'Product Archive Columns', 'kadence' ),
    'priority'     => 10,
    'default'      => array(
        'desktop' => 4,
        'tablet'  => 3,
        'mobile'  => 2,
    ),
    'live_method'  => array(
        array(
            'type'     => 'class',
            'selector' => '.woocommerce ul.products',
            'pattern'  => 'columns-$',
            'key'      => '',
        ),
    ),
    'transport'    => 'postMessage',
);
```

### Supported Live Method Types
1. **`css`**: Sets a CSS property directly on target selector without reloading the page.
2. **`css_typography`**: Updates font-family, font-size, line-height, letter-spacing, and font-weight simultaneously.
3. **`css_background`**: Updates background-color, background-image, repeat, and attachment.
4. **`class`**: Dynamically swaps CSS classes matching pattern `prefix-$` based on option value.
