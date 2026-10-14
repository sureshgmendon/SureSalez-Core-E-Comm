/**
 * SureSalez Exit-Intent Lead Capture Client Script
 *
 * @package SureSalez\Modules\CRMAutomations
 */

(function () {
    'use strict';

    var STORAGE_KEY = 'suresalez_lead_modal_dismissed';

    function initLeadCapture() {
        var modal = document.getElementById('suresalez-lead-capture-mount');
        if (!modal || modal.dataset.bound) {
            return;
        }
        modal.dataset.bound = 'true';

        var closeBtn = document.getElementById('suresalez-lead-close');
        var form = document.getElementById('suresalez-lead-capture-form');
        var emailInput = document.getElementById('suresalez-lead-email');
        var successMsg = document.getElementById('suresalez-lead-success');

        function openModal() {
            if (sessionStorage.getItem(STORAGE_KEY)) {
                return;
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
            sessionStorage.setItem(STORAGE_KEY, 'true');
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display !== 'none') {
                closeModal();
            }
        });

        // Trigger on desktop mouse leaving top of screen (Exit Intent)
        document.addEventListener('mouseleave', function (e) {
            if (e.clientY <= 10) {
                openModal();
            }
        });

        if (form && emailInput) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var email = emailInput.value.trim();
                if (!email) return;

                if (window.sureSalezLeadConfig && window.sureSalezLeadConfig.restUrl) {
                    fetch(window.sureSalezLeadConfig.restUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': window.sureSalezLeadConfig.nonce || ''
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(function (r) { return r.json(); })
                    .then(function () {
                        showSuccess();
                    })
                    .catch(function () {
                        showSuccess();
                    });
                } else {
                    showSuccess();
                }
            });
        }

        function showSuccess() {
            if (form) form.style.display = 'none';
            if (successMsg) successMsg.style.display = 'block';
            sessionStorage.setItem(STORAGE_KEY, 'true');

            setTimeout(function () {
                closeModal();
            }, 3500);
        }

        // Expose open helper for demo triggers
        window.SureSalezLead = {
            open: openModal,
            close: closeModal
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLeadCapture);
    } else {
        initLeadCapture();
    }
})();
