# Kadence Design System & CSS Token Specifications
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Target Systems:** Kadence `:root` CSS Engine (`inc/components/styles/component.php`, `theme.json`, and `global.min.css`)

---

## 1. The Kadence 15-Color Semantic Palette

Kadence utilizes a signature **15-color global palette** system. Instead of hardcoding hex colors across templates, components consume abstract semantic variables (`--global-palette1` through `--global-palette15`).

```
┌────────────────────────────────────────────────────────────────────────┐
│                      KADENCE 15-COLOR PALETTE SYSTEM                   │
├─────────┬──────────────────────┬─────────────┬─────────────────────────┤
│ Slug    │ Semantic Role        │ Default Hex │ Functional Usage        │
├─────────┼──────────────────────┼─────────────┼─────────────────────────┤
│ palette1│ Primary Accent       │ #2B6CB0     │ Brand primary, CTAs     │
│ palette2│ Accent Alternative   │ #215387     │ Button hover, active    │
│ palette3│ Strongest Text       │ #1A202C     │ Headings (H1-H6), titles│
│ palette4│ Strong Text          │ #2D3748     │ Body text, nav links    │
│ palette5│ Medium Text          │ #4A5568     │ Secondary text, excerpts│
│ palette6│ Subtle Text / Border │ #718096     │ Meta info, dividers     │
│ palette7│ Subtle Background    │ #EDF2F7     │ Light borders, pills    │
│ palette8│ Lighter Background   │ #F7FAFC     │ Card bg, secondary sec  │
│ palette9│ Clean White / Light  │ #FFFFFF     │ Page bg, card surface   │
│palette10│ Complement Accent    │ dynamic*    │ High-contrast badges    │
│palette11│ Notice - Success     │ #13612E     │ In-stock, cart success  │
│palette12│ Notice - Info        │ #1159AF     │ Notice alerts, tooltips │
│palette13│ Notice - Alert       │ #B82105     │ Sale badge, errors      │
│palette14│ Notice - Warning     │ #F7630C     │ Low stock, warnings     │
│palette15│ Notice - Rating      │ #F5A524     │ Star ratings, highlights│
└─────────┴──────────────────────┴─────────────┴─────────────────────────┘
```

> **Dynamic Complementary Calculation (`palette10`):**  
> When unset or defaulted to `#FFFFFF`, Kadence automatically synthesizes an ultra-accurate perceptual complementary color via CSS OKLCH relative color syntax:  
> `oklch(from var(--global-palette1) calc(l + 0.10 * (1 - l)) calc(c * 1.00) calc(h + 180) / 100%)`

---

## 2. Global CSS Variables Reference

Kadence compiles all customizer settings into an inline `:root` block emitted in `wp_head`:

```css
:root {
  /* --- 15 Global Palettes --- */
  --global-palette1: #2B6CB0;
  --global-palette2: #215387;
  --global-palette3: #1A202C;
  --global-palette4: #2D3748;
  --global-palette5: #4A5568;
  --global-palette6: #718096;
  --global-palette7: #EDF2F7;
  --global-palette8: #F7FAFC;
  --global-palette9: #ffffff;
  --global-palette9rgb: 255, 255, 255;
  --global-palette10: oklch(from var(--global-palette1) calc(l + 0.10 * (1 - l)) calc(c * 1.00) calc(h + 180) / 100%);
  --global-palette11: #13612e;
  --global-palette12: #1159af;
  --global-palette13: #b82105;
  --global-palette14: #f7630c;
  --global-palette15: #f5a524;

  /* --- Interactive Highlight Colors --- */
  --global-palette-highlight: var(--global-palette1);
  --global-palette-highlight-alt: var(--global-palette2);
  --global-palette-highlight-alt2: var(--global-palette9);

  /* --- Button States --- */
  --global-palette-btn-bg: var(--global-palette1);
  --global-palette-btn-bg-hover: var(--global-palette2);
  --global-palette-btn: #ffffff;
  --global-palette-btn-hover: #ffffff;

  --global-palette-btn-sec-bg: var(--global-palette7);
  --global-palette-btn-sec-bg-hover: var(--global-palette6);
  --global-palette-btn-sec: var(--global-palette3);
  --global-palette-btn-sec-hover: #ffffff;

  --global-palette-btn-out-bg: transparent;
  --global-palette-btn-out-bg-hover: var(--global-palette1);
  --global-palette-btn-out: var(--global-palette1);
  --global-palette-btn-out-hover: #ffffff;

  /* --- Typography Families --- */
  --global-body-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  --global-heading-font-family: inherit;
  --global-primary-nav-font-family: inherit;
  --global-fallback-font: sans-serif;
  --global-display-fallback-font: sans-serif;

  /* --- Layout & Container Geometry --- */
  --global-content-width: 1290px;
  --global-content-wide-width: calc(1290px + 200px);
  --global-content-narrow-width: 840px;
  --global-content-edge-padding: 1.5rem;
  --global-content-boxed-padding: 2rem;
  --global-calc-content-width: calc(var(--global-content-width) - (2 * var(--global-content-edge-padding)));
  --wp--style--global--content-size: var(--global-calc-content-width);
  --wp--style--global--wide-size: var(--global-content-width);

  /* --- Spacing Scale --- */
  --global-xxs-spacing: 0.25rem;
  --global-xs-spacing: 0.5rem;
  --global-sm-spacing: 0.75rem;
  --global-md-spacing: 1rem;
  --global-lg-spacing: 1.5rem;
  --global-xl-spacing: 2rem;
  --global-xxl-spacing: 3rem;
  --global-xxxl-spacing: 4rem;

  /* --- Shadows & Radii --- */
  --global-border-radius: 4px;
  --global-box-shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
  --global-box-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  --global-box-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  --global-box-shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}
```

---

## 3. Responsive Breakpoints & Device Thresholds

Kadence relies on an explicit **3-tier viewport model**:

```
0px                  719px / 767px                1024px               1025px+
├──────────────────────────┼─────────────────────────┼───────────────────►
│       Mobile View        │       Tablet View       │   Desktop View    │
│  @media (max-width: 719) │  @media (720 to 1024)   │  @media (1025px+) │
└──────────────────────────┴─────────────────────────┴───────────────────┘
```

1. **Desktop Viewport (`(min-width: 1025px)`)**:
   - Header: Full multi-row layout (`#main-header`) displays Top, Main, Bottom rows.
   - Navigation: Full inline horizontal menu with multi-level dropdowns or mega menus.
   - Product Grid: 4 to 6 columns.
   - Sidebars: CSS Grid 2-column layout (`grid-template-columns: 5fr 2fr` or `2fr 5fr`).
2. **Tablet Viewport (`(min-width: 720px) and (max-width: 1024px)`)**:
   - Header: Switches to `#mobile-header` or condensed tablet row.
   - Product Grid: 2 to 3 columns.
   - Sidebars: Automatically collapse beneath content unless sticky drawer is configured.
3. **Mobile Viewport (`(max-width: 719px)`)**:
   - Header: Strict `#mobile-header` with hamburger toggle trigger (`data-toggle-target="#mobile-drawer"`).
   - Product Grid: 1 or 2 columns (`grid-template-columns: repeat(2, minmax(0, 1fr))`).
   - Sticky Actions: Sticky Add-to-Cart bar activates at bottom of viewport.

---

## 4. Typography Scale & Fluid Dynamics

Kadence supports both fixed rem/px scales and dynamic clamp-based typography:

```css
/* Typography Scale Hierarchy */
h1, .entry-title.h1-style {
  font-size: clamp(2rem, 1.5rem + 2.5vw, 3rem);
  line-height: 1.2;
  font-weight: 700;
  color: var(--global-palette3);
}

h2 {
  font-size: clamp(1.5rem, 1.2rem + 1.5vw, 2.25rem);
  line-height: 1.25;
  font-weight: 700;
  color: var(--global-palette3);
}

h3 {
  font-size: clamp(1.25rem, 1.1rem + 0.75vw, 1.75rem);
  line-height: 1.3;
  font-weight: 600;
  color: var(--global-palette3);
}

h4 {
  font-size: 1.25rem;
  line-height: 1.4;
  font-weight: 600;
  color: var(--global-palette3);
}

h5 {
  font-size: 1.125rem;
  line-height: 1.4;
  font-weight: 600;
  color: var(--global-palette3);
}

h6 {
  font-size: 1rem;
  line-height: 1.5;
  font-weight: 600;
  color: var(--global-palette3);
}

body, p, input, select, textarea {
  font-family: var(--global-body-font-family);
  font-size: 17px;
  line-height: 1.6;
  color: var(--global-palette4);
}

.text-small { font-size: 0.875rem; }
.text-medium { font-size: 1rem; }
.text-large { font-size: 1.25rem; }
.text-larger { font-size: 1.5rem; }
.text-xxlarge { font-size: 2rem; }
```

---

## 5. Layout Containers & Spacing Mechanics

### 1. Site Container (`.site-container`)
- Default `max-width: var(--global-content-width, 1290px);`
- Default `padding: 0 var(--global-content-edge-padding, 1.5rem);`
- `margin: 0 auto;`

### 2. Boxed vs. Unboxed Architecture
- **Boxed Mode (`body.content-style-boxed`)**:
  - `body`: Background set to `--global-palette8` or custom color/pattern.
  - `#inner-wrap .content-bg`: White card surface (`background: var(--global-palette9)`), boxed padding `2rem`, subtle border radius and shadow (`--global-box-shadow-sm`).
- **Unboxed Mode (`body.content-style-unboxed`)**:
  - Seamless edge-to-edge flow with background transparency (`background: transparent`).
  - No card wrapper boxes on singular content.
