/**
 * SureSalez Theme — Navigation & Mobile Drawer Script
 *
 * Lightweight, zero-dependency mobile menu toggle and accessibility handler.
 */
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.querySelector('.suresalez-hamburger-toggle');
  var drawer = document.querySelector('.suresalez-mobile-drawer');
  var overlay = document.querySelector('.suresalez-drawer-overlay');
  var closeBtn = document.querySelector('.suresalez-drawer-close');

  if (!toggle || !drawer) {
    return;
  }

  function openDrawer() {
    drawer.classList.add('is-open');
    if (overlay) overlay.classList.add('is-active');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    if (overlay) overlay.classList.remove('is-active');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    if (drawer.classList.contains('is-open')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      closeDrawer();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      closeDrawer();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });
});
