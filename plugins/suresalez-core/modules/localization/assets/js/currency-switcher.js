/**
 * SureSalez Multi-Currency Switcher Client Script
 *
 * @package SureSalez\Modules\Localization
 */

(function () {
    'use strict';

    function initCurrencySwitcher() {
        // Mount fallback into containers if needed
        var mountPoints = document.querySelectorAll('#suresalez-currency-switcher-mount, .suresalez-currency-switcher-mount');
        var fallbackHtml = document.getElementById('suresalez-currency-switcher-fallback');

        if (fallbackHtml && mountPoints.length > 0) {
            mountPoints.forEach(function (mount) {
                if (!mount.innerHTML.trim()) {
                    mount.innerHTML = fallbackHtml.innerHTML;
                }
            });
        }

        var dropdowns = document.querySelectorAll('.suresalez-currency-switcher-dropdown');

        dropdowns.forEach(function (dropdown) {
            var btn = dropdown.querySelector('.suresalez-currency-btn');
            var options = dropdown.querySelectorAll('.suresalez-curr-item');

            if (!btn || btn.dataset.bound) {
                return;
            }
            btn.dataset.bound = 'true';

            // Toggle dropdown
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var isOpen = dropdown.classList.contains('is-open');

                // Close any other open dropdowns
                document.querySelectorAll('.suresalez-currency-switcher-dropdown.is-open').forEach(function (d) {
                    if (d !== dropdown) {
                        d.classList.remove('is-open');
                        var b = d.querySelector('.suresalez-currency-btn');
                        if (b) b.setAttribute('aria-expanded', 'false');
                    }
                });

                dropdown.classList.toggle('is-open', !isOpen);
                btn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
            });

            // Option selection
            options.forEach(function (item) {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    var selectedCurrency = this.dataset.currency;
                    if (!selectedCurrency) return;

                    switchCurrency(selectedCurrency);
                    dropdown.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                });
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.suresalez-currency-switcher-dropdown')) {
                document.querySelectorAll('.suresalez-currency-switcher-dropdown.is-open').forEach(function (dropdown) {
                    dropdown.classList.remove('is-open');
                    var btn = dropdown.querySelector('.suresalez-currency-btn');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.suresalez-currency-switcher-dropdown.is-open').forEach(function (dropdown) {
                    dropdown.classList.remove('is-open');
                    var btn = dropdown.querySelector('.suresalez-currency-btn');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            }
        });
    }

    function switchCurrency(currencyCode) {
        // Set cookie directly for immediate responsiveness
        var date = new Date();
        date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = 'suresalez_selected_currency=' + encodeURIComponent(currencyCode) + '; expires=' + date.toUTCString() + '; path=/; SameSite=Lax';

        // Notify REST backend if available
        if (window.sureSalezLocalizationConfig && window.sureSalezLocalizationConfig.restUrl) {
            fetch(window.sureSalezLocalizationConfig.restUrl + 'set-currency', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.sureSalezLocalizationConfig.nonce || ''
                },
                body: JSON.stringify({ currency: currencyCode })
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        } else {
            window.location.reload();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCurrencySwitcher);
    } else {
        initCurrencySwitcher();
    }

    // Expose API for headless / external modules
    window.SureSalezCurrency = {
        switch: switchCurrency,
        init: initCurrencySwitcher
    };
})();
