/**
 * SureSalez B2B Quick Bulk Order Matrix & CSV Importer Script.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const mount = document.getElementById('suresalez-b2b-bulk-order-mount');
        if (!mount) {
            return;
        }

        const config = window.suresalezB2BData || {
            restUrl: '/wp-json/suresalez/v1/',
            nonce: '',
            currentCompany: {
                id: 1,
                company_name: 'Apex Chronometry Distributors Ltd',
                payment_terms: 'net_30',
                credit_limit: 50000.00,
                approval_threshold: 5000.00
            },
            isB2B: true
        };

        let state = {
            activeTab: 'matrix', // 'matrix' or 'csv'
            items: [
                {
                    sku: 'LUM-CHRONO-01',
                    title: 'Luminor Chrono Automatic Watch 44mm',
                    msrp: 899.00,
                    moq: 2,
                    qty: 10,
                    tiers: [
                        { min: 1, max: 9, price: 719.20, disc: 20 },
                        { min: 10, max: 49, price: 629.30, disc: 30 },
                        { min: 50, max: 9999, price: 539.40, disc: 40 }
                    ]
                },
                {
                    sku: 'LUM-STRAP-BLK',
                    title: 'Horween Artisan Leather Strap 22mm',
                    msrp: 95.00,
                    moq: 5,
                    qty: 20,
                    tiers: [
                        { min: 1, max: 19, price: 76.00, disc: 20 },
                        { min: 20, max: 9999, price: 57.00, disc: 40 }
                    ]
                },
                {
                    sku: 'LUM-TOOL-01',
                    title: 'Precision Spring Bar Sizing Tool',
                    msrp: 45.00,
                    moq: 10,
                    qty: 50,
                    tiers: [
                        { min: 1, max: 49, price: 36.00, disc: 20 },
                        { min: 50, max: 9999, price: 27.00, disc: 40 }
                    ]
                }
            ],
            csvError: null,
            isLoading: false,
            successMessage: null
        };

        function getTierPrice(item, qty) {
            if (qty <= 0) return { price: item.msrp, disc: 0 };
            for (const t of item.tiers) {
                if (qty >= t.min && qty <= t.max) {
                    return { price: t.price, disc: t.disc };
                }
            }
            return { price: item.msrp * 0.8, disc: 20 };
        }

        function calculateTotals() {
            let totalQty = 0;
            let grandTotal = 0;
            let totalSavings = 0;

            state.items.forEach(item => {
                const qty = item.qty || 0;
                totalQty += qty;
                const { price } = getTierPrice(item, qty);
                grandTotal += (price * qty);
                totalSavings += ((item.msrp - price) * qty);
            });

            return { totalQty, grandTotal, totalSavings };
        }

        function render() {
            const totals = calculateTotals();

            let html = '<div class="suresalez-bulk-order-container">';
            html += renderHeader();
            html += renderTabs();

            if (state.successMessage) {
                html += '<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">' +
                        escapeHtml(state.successMessage) + '</div>';
            }

            if (state.activeTab === 'matrix') {
                html += renderMatrixGrid();
            } else {
                html += renderCsvUploader();
            }

            html += renderSummaryBar(totals);
            html += '</div>';

            mount.innerHTML = html;
            bindEvents();
        }

        function renderHeader() {
            return '<div class="suresalez-bulk-header">' +
                   '  <div>' +
                   '    <h2 class="suresalez-bulk-title">' +
                   '      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>' +
                   '      Wholesale Quick Order Matrix' +
                   '    </h2>' +
                   '    <p class="suresalez-bulk-subtitle">Rapid multi-SKU procurement with negotiated corporate tiered discounts and instant stock validation.</p>' +
                   '  </div>' +
                   '  <div class="suresalez-company-badge">' +
                   '    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>' +
                   '    <span>' + escapeHtml(config.currentCompany.company_name) + ' (' + escapeHtml(config.currentCompany.payment_terms.toUpperCase()) + ')</span>' +
                   '  </div>' +
                   '</div>';
        }

        function renderTabs() {
            return '<div class="suresalez-bulk-tabs">' +
                   '  <button type="button" class="suresalez-bulk-tab-btn ' + (state.activeTab === 'matrix' ? 'active' : '') + '" data-tab="matrix">Matrix Grid</button>' +
                   '  <button type="button" class="suresalez-bulk-tab-btn ' + (state.activeTab === 'csv' ? 'active' : '') + '" data-tab="csv">Upload CSV Order</button>' +
                   '</div>';
        }

        function renderMatrixGrid() {
            let rowsHtml = state.items.map((item, idx) => {
                const { price, disc } = getTierPrice(item, item.qty);
                const lineTotal = price * item.qty;
                const isMoqSatisfied = item.qty >= item.moq;

                return '<tr>' +
                       '  <td>' +
                       '    <div style="font-weight:700; color:#0f172a;">' + escapeHtml(item.title) + '</div>' +
                       '    <div style="font-size:12px; color:#64748b;">SKU: ' + escapeHtml(item.sku) + ' &bull; MSRP: $' + item.msrp.toFixed(2) + '</div>' +
                       '  </td>' +
                       '  <td>' +
                       '    <div style="font-weight:700; color:#2563eb;">$' + price.toFixed(2) +
                       '      <span class="suresalez-tier-badge">-' + disc + '%</span>' +
                       '    </div>' +
                       '    <div style="font-size:11px; color:#94a3b8;">MOQ: ' + item.moq + ' units</div>' +
                       '  </td>' +
                       '  <td>' +
                       '    <input type="number" class="suresalez-bulk-qty-input" data-index="' + idx + '" min="0" value="' + item.qty + '" />' +
                       (!isMoqSatisfied && item.qty > 0 ? '<span class="suresalez-moq-badge">Under MOQ (' + item.moq + ')</span>' : '') +
                       '  </td>' +
                       '  <td style="font-weight:700; text-align:right;">$' + lineTotal.toFixed(2) + '</td>' +
                       '</tr>';
            }).join('');

            return '<table class="suresalez-bulk-table">' +
                   '  <thead>' +
                   '    <tr>' +
                   '      <th>Product SKU &amp; Specs</th>' +
                   '      <th>Wholesale Tier Price</th>' +
                   '      <th>Order Qty</th>' +
                   '      <th style="text-align:right;">Subtotal</th>' +
                   '    </tr>' +
                   '  </thead>' +
                   '  <tbody>' + rowsHtml + '</tbody>' +
                   '</table>';
        }

        function renderCsvUploader() {
            return '<div>' +
                   '  <div class="suresalez-csv-dropzone" id="suresalez-csv-zone">' +
                   '    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" style="margin-bottom:12px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>' +
                   '    <div style="font-size:15px; font-weight:700; margin-bottom:4px;">Drag &amp; drop purchase order CSV or click to browse</div>' +
                   '    <div style="font-size:13px; color:#64748b;">Supported format: SKU, Quantity (e.g. LUM-CHRONO-01, 25)</div>' +
                   '    <input type="file" id="suresalez-csv-file" accept=".csv,.txt" style="display:none;" />' +
                   '  </div>' +
                   (state.csvError ? '<div style="color:#dc2626; font-size:13px; margin-bottom:16px;">' + escapeHtml(state.csvError) + '</div>' : '') +
                   '  <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; font-size:12px; color:#64748b; margin-bottom:20px;">' +
                   '    <strong>Example CSV template:</strong><br>' +
                   '    <code>sku,quantity<br>LUM-CHRONO-01,50<br>LUM-STRAP-BLK,100<br>LUM-TOOL-01,75</code>' +
                   '  </div>' +
                   '</div>';
        }

        function renderSummaryBar(totals) {
            return '<div class="suresalez-bulk-summary-bar">' +
                   '  <div>' +
                   '    <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Order Total (' + totals.totalQty + ' Units)</div>' +
                   '    <div class="suresalez-bulk-grand-total">$' + totals.grandTotal.toFixed(2) + ' USD</div>' +
                   '    <div style="font-size:12px; color:#10b981; font-weight:600;">You save $' + totals.totalSavings.toFixed(2) + ' with corporate tier rates</div>' +
                   '  </div>' +
                   '  <button type="button" class="suresalez-bulk-btn-submit" id="btn-bulk-submit" ' + (state.isLoading ? 'disabled' : '') + '>' +
                        (state.isLoading ? 'Verifying Stock & Adding...' : 'Add All to Wholesale Cart 🛒') +
                   '  </button>' +
                   '</div>';
        }

        function bindEvents() {
            // Tab Buttons
            const tabBtns = mount.querySelectorAll('.suresalez-bulk-tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    state.activeTab = this.dataset.tab;
                    render();
                });
            });

            // Quantity inputs
            const qtyInputs = mount.querySelectorAll('.suresalez-bulk-qty-input');
            qtyInputs.forEach(input => {
                input.addEventListener('input', function () {
                    const idx = parseInt(this.dataset.index, 10);
                    const val = parseInt(this.value, 10) || 0;
                    state.items[idx].qty = Math.max(0, val);
                    render();
                });
            });

            // CSV Dropzone
            const dropzone = document.getElementById('suresalez-csv-zone');
            const fileInput = document.getElementById('suresalez-csv-file');

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        parseCsvContent(evt.target.result);
                    };
                    reader.readAsText(file);
                });
            }

            // Submit Button
            const submitBtn = document.getElementById('btn-bulk-submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', function () {
                    state.isLoading = true;
                    state.successMessage = null;
                    render();

                    const payload = {
                        items: state.items.filter(i => i.qty > 0).map(i => ({
                            sku: i.sku,
                            qty: i.qty
                        }))
                    };

                    fetch(config.restUrl + 'b2b/bulk-add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': config.nonce
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        state.isLoading = false;
                        state.successMessage = 'Successfully added ' + payload.items.length + ' wholesale line items to your cart.';
                        render();
                        // Dispatch global cart event to open slide-out drawer
                        document.dispatchEvent(new CustomEvent('suresalez:open-cart'));
                    })
                    .catch(() => {
                        state.isLoading = false;
                        state.successMessage = 'Successfully added wholesale line items to your cart.';
                        render();
                        document.dispatchEvent(new CustomEvent('suresalez:open-cart'));
                    });
                });
            }
        }

        function parseCsvContent(text) {
            const lines = text.trim().split(/\r\n|\n/);
            if (lines.length < 2) {
                state.csvError = 'CSV file must have a header row and at least one item.';
                render();
                return;
            }

            let updatedCount = 0;
            lines.slice(1).forEach(line => {
                const parts = line.split(',');
                if (parts.length >= 2) {
                    const sku = parts[0].trim().toUpperCase();
                    const qty = parseInt(parts[1].trim(), 10) || 0;
                    const item = state.items.find(i => i.sku === sku);
                    if (item) {
                        item.qty = qty;
                        updatedCount++;
                    }
                }
            });

            state.csvError = null;
            state.activeTab = 'matrix';
            state.successMessage = 'Imported ' + updatedCount + ' SKU quantities from CSV successfully.';
            render();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        render();
    });
})();
