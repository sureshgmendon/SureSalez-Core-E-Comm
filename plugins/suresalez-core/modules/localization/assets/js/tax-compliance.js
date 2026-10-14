/**
 * SureSalez Tax Compliance & GST/Duty Client Script
 *
 * @package SureSalez\Modules\Localization
 */

(function () {
    'use strict';

    function initTaxCompliance() {
        var gstinInput = document.getElementById('billing_suresalez_gstin');
        if (gstinInput && !gstinInput.dataset.bound) {
            gstinInput.dataset.bound = 'true';

            var statusEl = document.createElement('div');
            statusEl.className = 'suresalez-gstin-status';
            gstinInput.parentNode.appendChild(statusEl);

            gstinInput.addEventListener('blur', function () {
                var gstin = this.value.trim().toUpperCase();
                this.value = gstin;

                if (!gstin) {
                    statusEl.className = 'suresalez-gstin-status';
                    statusEl.textContent = '';
                    return;
                }

                var stateEl = document.getElementById('billing_state');
                var state = stateEl ? stateEl.value.trim() : '';

                validateGstin(gstin, state, function (res) {
                    if (res.valid) {
                        statusEl.className = 'suresalez-gstin-status is-valid';
                        statusEl.textContent = '✓ Valid GSTIN (State: ' + (res.state_code || '') + ') - B2B ITC Eligible';
                    } else {
                        statusEl.className = 'suresalez-gstin-status is-invalid';
                        statusEl.textContent = '✕ ' + (res.message || 'Invalid GSTIN');
                    }
                });
            });
        }
    }

    function validateGstin(gstin, state, callback) {
        var pattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
        if (!pattern.test(gstin)) {
            callback({
                valid: false,
                message: 'Invalid GSTIN format (15 characters: e.g. 29ABCDE1234F1Z5)'
            });
            return;
        }

        // Call REST endpoint if available
        if (window.sureSalezTaxConfig && window.sureSalezTaxConfig.restUrl) {
            fetch(window.sureSalezTaxConfig.restUrl.replace('tax-calculate', 'validate-gstin'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.sureSalezTaxConfig.nonce || ''
                },
                body: JSON.stringify({ gstin: gstin, state: state })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                callback(data.data || { valid: data.success });
            })
            .catch(function () {
                callback({ valid: true, state_code: gstin.substring(0, 2) });
            });
        } else {
            callback({ valid: true, state_code: gstin.substring(0, 2) });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTaxCompliance);
    } else {
        initTaxCompliance();
    }

    window.SureSalezTax = {
        init: initTaxCompliance,
        validateGstin: validateGstin
    };
})();
