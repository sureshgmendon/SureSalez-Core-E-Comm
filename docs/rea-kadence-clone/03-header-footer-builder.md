# Kadence Multi-Row Header & Footer Builder Architecture
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Target Systems:** Kadence Header & Footer Engine (`inc/components/custom_header/`, `inc/components/custom_footer/`, `template-parts/header/`, `template-parts/footer/`, and `header.min.css`)

---

## 1. Multi-Row Header Architecture

Kadence features a **3-tier header grid system** supporting independent configuration for:
1. **Top Header (`top`)**: Utility notices, currency selector, secondary contact links, social icons.
2. **Main Header (`main`)**: Primary branding logo, primary navigation menu, AJAX search, slide-out cart toggle.
3. **Bottom Header (`bottom`)**: Mega-menu category lists, promotional strip, multi-department navigations.

```
┌────────────────────────────────────────────────────────────────────────┐
│                        TOP HEADER ROW (.site-top-header-wrap)          │
│  [Left: Promo Notice / Phone]                 [Right: Currency / Login]│
├────────────────────────────────────────────────────────────────────────┤
│                        MAIN HEADER ROW (.site-main-header-wrap)        │
│  [Left: Logo Branding]     [Center: Main Nav]     [Right: Search|Cart] │
├────────────────────────────────────────────────────────────────────────┤
│                        BOTTOM HEADER ROW (.site-bottom-header-wrap)    │
│  [Left: Categories Menu]                      [Right: Free Shipping]   │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. 3-Zone Flex/Grid Column System

Each header row contains a `.site-container` wrapping a `.site-header-row`. The row layout dynamically activates one of three column modes via CSS Grid:

```css
/* Standard 2-Column Mode (Left and Right) */
.site-header-row {
  display: grid;
  grid-template-columns: auto auto;
}

/* 3-Column Mode with Center Placement */
.site-header-row.site-header-row-center-column {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
}

/* Center-Only Column Mode */
.site-header-row.site-header-row-only-center-column {
  display: flex;
  justify-content: center;
}
```

### The 5 Column Attachment Points
Inside each row, Kadence provides 5 granular column zones:
1. `site-header-section-left`: Primary left-aligned container.
2. `site-header-section-left-center`: Pushes adjacent to center from left (`flex-grow: 1; justify-content: flex-end;`).
3. `site-header-section-center`: Exact geometric center alignment (`justify-content: center;`).
4. `site-header-section-right-center`: Pushes adjacent to center from right (`flex-grow: 1; justify-content: flex-start;`).
5. `site-header-section-right`: Primary right-aligned container (`justify-content: flex-end;`).

---

## 3. Sticky & Shrink Header Mechanics

Kadence implements high-performance sticky headers using pure CSS and lightweight IntersectionObserver/scroll tracking:

### 1. Sticky Modes
- `main`: Only the main navigation row sticks to the viewport.
- `top_main`: Both top notice row and main navigation stick together.
- `top_main_bottom`: The entire 3-row masthead sticks.

### 2. Shrink Dynamics
When `data-shrink="true"` is set on `.kadence-sticky-header`, the header transitions smoothly on scroll:
```css
.kadence-sticky-header.item-is-fixed {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1200;
  background: var(--kb-stuck-header-bg, #ffffff);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Logo & row height shrink */
.kadence-sticky-header.item-is-fixed[data-shrink="true"] {
  height: var(--header-shrink-height, 60px);
}

.kadence-sticky-header.item-is-fixed[data-shrink="true"] .site-branding img {
  max-height: calc(var(--header-shrink-height, 60px) - 16px);
  width: auto;
  transition: max-height 0.25s ease;
}
```

### 3. Reveal on Scroll Up
When `data-reveal-scroll-up="true"` is active, the header translates out of view on scroll down (`transform: translateY(-100%)`) and animates down smoothly as soon as the user reverses scroll direction upwards (`transform: translateY(0)`).

---

## 4. Multi-Row Footer Builder

The Kadence Footer Builder provides **Top, Middle, and Bottom Footer Rows** supporting 1 to 6 column flexible grid layouts:

```css
/* Dynamic Column Grids */
.site-footer-row.site-footer-row-columns-1 { grid-template-columns: 1fr; }
.site-footer-row.site-footer-row-columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.site-footer-row.site-footer-row-columns-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.site-footer-row.site-footer-row-columns-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.site-footer-row.site-footer-row-columns-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.site-footer-row.site-footer-row-columns-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }

/* Asymmetrical Layouts */
.site-footer-row.site-footer-row-layout-left-heavy { grid-template-columns: 2fr 1fr 1fr; }
.site-footer-row.site-footer-row-layout-right-heavy { grid-template-columns: 1fr 1fr 2fr; }
.site-footer-row.site-footer-row-layout-center-heavy { grid-template-columns: 1fr 2fr 1fr; }

/* Responsive Auto-Collapse */
@media screen and (max-width: 1024px) {
  .site-footer-row.site-footer-row-columns-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 2rem;
  }
}

@media screen and (max-width: 719px) {
  .site-footer-row {
    grid-template-columns: 1fr !important;
    gap: 1.5rem;
  }
}
```

---

## 5. Navigation Dropdown Animations & Accessibility

Kadence navigation elements feature 3 customizable dropdown animations:
1. `dropdown-animation-fade`: Opacity fade (`0` to `1`).
2. `dropdown-animation-fade-down`: Translates from `-10px` to `0px` with fade.
3. `dropdown-animation-fade-up`: Translates from `10px` to `0px` with fade.

All navigation items comply with WCAG 2.1 AA keyboard accessibility:
- Sub-menus feature dedicated `<button class="dropdown-nav-special-toggle">` elements with dynamically toggled `aria-expanded="true|false"`.
- Underline animation styling uses CSS pseudo-elements:
```css
.navigation[class*="style-underline"] > .menu-container > .menu > .menu-item > a::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 100%;
  height: 2px;
  background: currentColor;
  transform: scale(0, 0) translate(-50%, 0);
  transition: transform 0.2s ease-in-out;
}

.navigation[class*="style-underline"] > .menu-container > .menu > .menu-item:hover > a::after,
.navigation[class*="style-underline"] > .menu-container > .menu > .menu-item.current-menu-item > a::after {
  transform: scale(1, 1) translate(-50%, 0);
}
```
