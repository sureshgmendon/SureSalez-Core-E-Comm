# Kadence Slide-Out Drawer & Modal System
**Analysis Methodology:** Reverse Engineering Analysis (REA)  
**Target Systems:** Kadence Drawer Framework (`navigation-lite.min.js`, `header.min.css`, `header-functions.php`)

---

## 1. System Architecture

The Kadence drawer system is a high-performance, dependency-free vanilla JavaScript off-canvas engine. Rather than loading third-party libraries (e.g., jQuery, Bootstrap, or Micromodal), Kadence implements an accessible modal and drawer state machine driving three core components:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        KADENCE DRAWER ENGINE                           │
├────────────────────────────────────────────────────────────────────────┤
│  1. Mobile Navigation Drawer (#mobile-drawer)                          │
│     ├── Triggers from hamburger button                                 │
│     ├── Multi-level collapsible menu accordion                         │
│     └── Left or Right slide-in orientation                             │
├────────────────────────────────────────────────────────────────────────┤
│  2. Slide-Out WooCommerce Cart Drawer (#cart-drawer)                   │
│     ├── Triggers from header cart icon button                          │
│     ├── Real-time mini-cart items, quantities, subtotal, and CTAs      │
│     └── Ajax fragment replacement (.kadence-mini-cart-refresh)         │
├────────────────────────────────────────────────────────────────────────┤
│  3. Fullscreen / Modal Search (#search-drawer)                         │
│     ├── Instant keyboard focus into .search-field                      │
│     └── WooCommerce product search filter support                      │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Declarative HTML Markup & Trigger Contracts

Every drawer trigger in Kadence is declared with standard semantic HTML and HTML5 `data-*` attributes:

```html
<!-- Trigger Button Example (Cart Drawer) -->
<button 
  class="drawer-toggle header-cart-button" 
  data-toggle-target="#cart-drawer" 
  data-toggle-body-class="showing-popup-drawer-from-right" 
  aria-expanded="false" 
  aria-label="Shopping Cart"
  data-set-focus=".cart-toggle-close">
  <svg class="kadence-svg-icon" viewBox="0 0 24 24"><!-- Cart Icon --></svg>
  <span class="header-cart-total header-cart-is-empty-false">3</span>
</button>

<!-- Drawer DOM Structure -->
<div 
  id="cart-drawer" 
  class="popup-drawer popup-drawer-layout-sidepanel popup-drawer-side-right" 
  data-drawer-target-string="#cart-drawer"
  role="dialog" 
  aria-modal="true" 
  aria-label="Shopping Cart">
  
  <!-- Backdrop Overlay -->
  <div class="drawer-overlay" data-drawer-target-string="#cart-drawer"></div>
  
  <!-- Sliding Panel Container -->
  <div class="drawer-inner">
    <!-- Header with Close Button -->
    <div class="drawer-header">
      <h3 class="side-cart-header">Review Cart</h3>
      <button 
        class="cart-toggle-close drawer-toggle" 
        data-toggle-target="#cart-drawer" 
        data-toggle-body-class="showing-popup-drawer-from-right" 
        aria-expanded="false" 
        aria-label="Close Cart"
        data-set-focus=".header-cart-button">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>

    <!-- Scrollable Content Area -->
    <div class="drawer-content woocommerce widget_shopping_cart">
      <div class="mini-cart-container">
        <div class="kadence-mini-cart-refresh">
          <!-- Cart Items List, Subtotals, and Checkout Button -->
        </div>
      </div>
    </div>
  </div>
</div>
```

---

## 3. CSS Layout & Slide Transition Dynamics

The drawer uses hardware-accelerated CSS transforms (`translateX`) to achieve 60fps animations:

```css
/* Off-Canvas Container */
.popup-drawer {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 100000;
  visibility: hidden;
  transition: visibility 0.25s ease;
  pointer-events: none;
}

.popup-drawer.show-drawer {
  visibility: visible;
  pointer-events: all;
}

/* Dimmed Overlay Backdrop */
.popup-drawer .drawer-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  opacity: 0;
  transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.popup-drawer.active .drawer-overlay {
  opacity: 1;
}

/* Sliding Panel */
.popup-drawer .drawer-inner {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 100%;
  max-width: 420px;
  background: var(--global-palette9, #ffffff);
  box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  transition: transform 0.3s cubic-bezier(0.77, 0.2, 0.05, 1);
}

/* Right Slide-in Mode */
.popup-drawer.popup-drawer-side-right .drawer-inner {
  right: 0;
  transform: translateX(100%);
}

.popup-drawer.active.popup-drawer-side-right .drawer-inner {
  transform: translateX(0%);
}

/* Left Slide-in Mode */
.popup-drawer.popup-drawer-side-left .drawer-inner {
  left: 0;
  transform: translateX(-100%);
}

.popup-drawer.active.popup-drawer-side-left .drawer-inner {
  transform: translateX(0%);
}

/* Fullscreen Search Mode */
.popup-drawer.popup-drawer-layout-fullwidth .drawer-inner {
  max-width: 100%;
  background: rgba(9, 12, 16, 0.96);
  backdrop-filter: blur(8px);
  justify-content: center;
  align-items: center;
  opacity: 0;
  transform: scale(0.95);
  transition: all 0.25s ease-out;
}

.popup-drawer.active.popup-drawer-layout-fullwidth .drawer-inner {
  opacity: 1;
  transform: scale(1);
}
```

---

## 4. Runtime State Machine & JavaScript Mechanics

Reverse-engineering `navigation-lite.min.js` reveals the exact runtime lifecycle of the `window.kadence.toggleDrawer` state machine:

```
[User Clicks .drawer-toggle]
       │
       ▼
1. Calculate Scrollbar Offset
   f = window.innerWidth - document.documentElement.clientWidth
   body.style.setProperty('--scrollbar-offset', f + 'px')
   body.classList.add('kadence-scrollbar-fixer')
       │
       ▼
2. Toggle Open/Close State
   IF drawer is open (.show-drawer):
     ├─► Remove body class (e.g., 'showing-popup-drawer-from-right')
     ├─► Remove .active & .pop-animated
     ├─► Wait 250ms (transition duration)
     ├─► Remove .show-drawer
     └─► Restore focus to trigger element (data-set-focus)
   ELSE:
     ├─► Add .show-drawer
     ├─► Add body class (locks background scrolling)
     ├─► Delay 10ms -> Add .active (triggers CSS translate animation)
     ├─► Delay 250ms -> Add .pop-animated
     └─► Shift focus to initial interactive child inside drawer
       │
       ▼
3. Focus Trap (Tab & Shift+Tab loop)
   Traps focus within [firstFocusable, lastFocusable] elements.
       │
       ▼
4. Event Dismissals:
   ├─► Click on .drawer-overlay -> calls toggleDrawer()
   ├─► Press ESC key (keyCode 27) -> closes all active drawers
   └─► Click on internal anchor links -> closes drawer smoothly
```

### Scrollbar Layout Shift Prevention
When the modal opens and `overflow: hidden` is applied to `<body>`, the browser scrollbar disappears, causing annoying horizontal layout shifts. Kadence solves this natively by calculating the exact layout scrollbar delta:
```js
const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
document.body.style.setProperty('--scrollbar-offset', scrollbarWidth + 'px');
document.body.classList.add('kadence-scrollbar-fixer');
```
And applying padding compensation via CSS:
```css
body.kadence-scrollbar-fixer {
  overflow: hidden;
  padding-right: var(--scrollbar-offset, 0px);
}

.kadence-scrollbar-fixer .item-is-fixed {
  right: var(--scrollbar-offset, 0);
}
```
