/**
 * Kadence Zero-Dependency Vanilla JS Runtime
 * Off-Canvas Drawer State Machine, Focus Trap, Dropdowns, Swatches & Sticky Header
 * Reverse-Engineered from Kadence Theme `navigation-lite.min.js`
 */

(function () {
  'use strict';

  window.kadence = window.kadence || {
    /**
     * Toggles an attribute value between two options
     */
    toggleAttribute: function (element, attr, val1, val2) {
      if (!element) return;
      const current = element.getAttribute(attr);
      element.setAttribute(attr, current === val1 ? val2 : val1);
    },

    /**
     * Calculates scrollbar width delta to prevent horizontal layout shift
     */
    getScrollbarWidth: function () {
      return window.innerWidth - document.documentElement.clientWidth;
    },

    /**
     * Master Drawer State Machine
     * Opens/closes off-canvas panels with transition delays and focus trapping
     */
    toggleDrawer: function (triggerEl, shouldFocus) {
      shouldFocus = typeof shouldFocus === 'undefined' ? true : shouldFocus;
      if (!triggerEl || !triggerEl.dataset.toggleTarget) return;

      const targetSelector = triggerEl.dataset.toggleTarget;
      const drawer = document.querySelector(targetSelector);
      if (!drawer) return;

      const bodyClass = triggerEl.dataset.toggleBodyClass;
      const duration = triggerEl.dataset.toggleDuration ? parseInt(triggerEl.dataset.toggleDuration, 10) : 250;
      const scrollbarDelta = window.kadence.getScrollbarWidth();

      window.kadence.toggleAttribute(triggerEl, 'aria-expanded', 'true', 'false');

      // CLOSING STATE
      if (drawer.classList.contains('show-drawer')) {
        if (bodyClass) {
          document.body.classList.remove(bodyClass);
        }
        drawer.classList.remove('active');
        drawer.classList.remove('pop-animated');
        document.body.classList.remove('kadence-scrollbar-fixer');

        setTimeout(function () {
          drawer.classList.remove('show-drawer');
          if (triggerEl.dataset.setFocus && shouldFocus) {
            const focusEl = document.querySelector(triggerEl.dataset.setFocus);
            if (focusEl) {
              focusEl.focus();
              if (focusEl.hasAttribute('aria-expanded')) {
                window.kadence.toggleAttribute(focusEl, 'aria-expanded', 'true', 'false');
              }
            }
          }
        }, duration);

      // OPENING STATE
      } else {
        drawer.classList.add('show-drawer');
        if (bodyClass) {
          document.body.classList.add(bodyClass);
          if (bodyClass.includes('showing-popup-drawer-')) {
            document.body.style.setProperty('--scrollbar-offset', scrollbarDelta + 'px');
            document.body.classList.add('kadence-scrollbar-fixer');
          }
        }

        setTimeout(function () {
          drawer.classList.add('active');
          if (triggerEl.dataset.setFocus && shouldFocus) {
            const focusEl = document.querySelector(triggerEl.dataset.setFocus);
            if (focusEl) {
              if (focusEl.hasAttribute('aria-expanded')) {
                window.kadence.toggleAttribute(focusEl, 'aria-expanded', 'true', 'false');
              }
              focusEl.focus();
            }
          }
        }, 10);

        setTimeout(function () {
          drawer.classList.add('pop-animated');
        }, duration);

        // WCAG Focus Trap Loop
        window.kadence.trapFocus(drawer);
      }
    },

    /**
     * Traps keyboard navigation inside active modal/drawer
     */
    trapFocus: function (container) {
      const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (!focusableElements.length) return;

      const firstEl = focusableElements[0];
      const lastEl = focusableElements[focusableElements.length - 1];

      function handleKey(e) {
        if (e.key !== 'Tab' && e.keyCode !== 9) return;
        if (!container.classList.contains('active')) {
          document.removeEventListener('keydown', handleKey);
          return;
        }

        if (e.shiftKey) {
          if (document.activeElement === firstEl) {
            lastEl.focus();
            e.preventDefault();
          }
        } else {
          if (document.activeElement === lastEl) {
            firstEl.focus();
            e.preventDefault();
          }
        }
      }

      document.addEventListener('keydown', handleKey);
    },

    /**
     * Initializes all drawer trigger and backdrop click listeners
     */
    initDrawers: function () {
      // Toggle button clicks
      const toggles = document.querySelectorAll('.drawer-toggle');
      toggles.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          window.kadence.toggleDrawer(btn);
        });
      });

      // ESC key listener closes all open drawers
      document.addEventListener('keyup', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          const activeDrawers = document.querySelectorAll('.popup-drawer.show-drawer.active');
          activeDrawers.forEach(function (drawer) {
            const closeBtn = drawer.querySelector('.drawer-toggle');
            if (closeBtn) {
              window.kadence.toggleDrawer(closeBtn);
            } else {
              const targetStr = drawer.dataset.drawerTargetString;
              const trigger = document.querySelector(`*[data-toggle-target="${targetStr}"]`);
              if (trigger) window.kadence.toggleDrawer(trigger);
            }
          });
        }
      });

      // Overlay backdrop click to close
      document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('drawer-overlay')) {
          const targetStr = e.target.dataset.drawerTargetString;
          const trigger = document.querySelector(`*[data-toggle-target="${targetStr}"]`);
          if (trigger) {
            window.kadence.toggleDrawer(trigger);
          }
        }
      });
    },

    /**
     * Initializes responsive navigation dropdown toggles
     */
    initNavDropdowns: function () {
      const specialToggles = document.querySelectorAll('.dropdown-nav-special-toggle');
      specialToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          const parentItem = toggle.closest('.menu-item');
          if (parentItem) {
            const isToggled = parentItem.classList.toggle('menu-item--toggled-on');
            toggle.setAttribute('aria-expanded', isToggled ? 'true' : 'false');
          }
        });
      });
    },

    /**
     * Sticky Header Observer & Shrink Scroll Mechanics
     */
    initStickyHeader: function () {
      const headerWrap = document.querySelector('.kadence-sticky-header');
      if (!headerWrap) return;

      const threshold = 80;
      let lastScrollY = window.scrollY;

      window.addEventListener('scroll', function () {
        const currentScrollY = window.scrollY;

        if (currentScrollY > threshold) {
          if (!headerWrap.classList.contains('item-is-fixed')) {
            headerWrap.classList.add('item-is-fixed');
          }
        } else {
          if (headerWrap.classList.contains('item-is-fixed')) {
            headerWrap.classList.remove('item-is-fixed');
          }
        }

        lastScrollY = currentScrollY;
      }, { passive: true });
    },

    /**
     * Interactive Variation Swatches Demo Controller
     */
    initSwatches: function () {
      const swatches = document.querySelectorAll('.kadence-swatch');
      swatches.forEach(function (swatch) {
        swatch.addEventListener('click', function (e) {
          e.preventDefault();
          const parentWrap = swatch.closest('.kadence-swatches-wrap');
          if (parentWrap) {
            parentWrap.querySelectorAll('.kadence-swatch').forEach(function (s) {
              s.classList.remove('is-selected');
            });
            swatch.classList.add('is-selected');
          }
        });
      });
    },

    /**
     * Quick View Modal Trigger Handler
     */
    initQuickView: function () {
      const qvButtons = document.querySelectorAll('.kadence-quick-view-btn');
      qvButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          const modal = document.querySelector('#quick-view-drawer');
          if (modal) {
            window.kadence.toggleDrawer(btn);
          }
        });
      });
    },

    /**
     * Simulated Add-to-Cart with Mini-Cart Refresh & Drawer Open
     */
    initCartInteractions: function () {
      const addBtns = document.querySelectorAll('.ajax_add_to_cart');
      const cartToggle = document.querySelector('.header-cart-button');
      const cartTotalBadge = document.querySelector('.header-cart-total');

      addBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          const originalText = btn.textContent;
          btn.textContent = 'Adding...';
          btn.disabled = true;

          setTimeout(function () {
            btn.textContent = 'Added ✓';
            if (cartTotalBadge) {
              const current = parseInt(cartTotalBadge.textContent, 10) || 0;
              cartTotalBadge.textContent = current + 1;
            }

            setTimeout(function () {
              btn.textContent = originalText;
              btn.disabled = false;
              // Open slide-out cart drawer
              if (cartToggle) {
                window.kadence.toggleDrawer(cartToggle);
              }
            }, 400);
          }, 450);
        });
      });

      // Mini-cart remove items handler
      document.addEventListener('click', function (e) {
        if (e.target && e.target.closest('.mini-cart-remove')) {
          e.preventDefault();
          const item = e.target.closest('.mini-cart-item');
          if (item) {
            item.style.opacity = '0.3';
            item.style.transform = 'translateX(20px)';
            item.style.transition = 'all 0.25s ease';
            setTimeout(function () {
              item.remove();
              if (cartTotalBadge) {
                const current = parseInt(cartTotalBadge.textContent, 10) || 0;
                if (current > 0) cartTotalBadge.textContent = current - 1;
              }
            }, 250);
          }
        }
      });
    },

    /**
     * Master Initialization
     */
    init: function () {
      window.kadence.initDrawers();
      window.kadence.initNavDropdowns();
      window.kadence.initStickyHeader();
      window.kadence.initSwatches();
      window.kadence.initQuickView();
      window.kadence.initCartInteractions();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.kadence.init);
  } else {
    window.kadence.init();
  }
})();
